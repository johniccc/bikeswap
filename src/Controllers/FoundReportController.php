<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\FoundReportRepository;
use App\Repository\FoundReportMessageRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\FoundReportService;
use App\Services\KarmaService;

class FoundReportController
{
    private BikeRepository $bikeRepository;
    private FoundReportRepository $foundReportRepository;
    private FoundReportMessageRepository $messageRepository;
    private UserRepository $userRepository;
    private FoundReportService $foundReportService;
    private AuthService $authService;
    private KarmaService $karmaService;
    private Session $session;
    private array $config;

    public function __construct(
        BikeRepository $bikeRepository,
        FoundReportRepository $foundReportRepository,
        FoundReportMessageRepository $messageRepository,
        UserRepository $userRepository,
        FoundReportService $foundReportService,
        AuthService $authService,
        KarmaService $karmaService,
        Session $session,
        array $config
    ) {
        $this->bikeRepository = $bikeRepository;
        $this->foundReportRepository = $foundReportRepository;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
        $this->foundReportService = $foundReportService;
        $this->authService = $authService;
        $this->karmaService = $karmaService;
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * Show the "report a found bike" form.
     * Accessible to everyone (registered and anonymous).
     * GET /found/report/{qrHash}
     */
    public function reportForm(Request $request): Response
    {
        $qrHash = $request->param('qrHash');
        $bike = $this->bikeRepository->findByQrHash($qrHash, withPhotos: true);

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isStolen()) {
            $this->session->flash('error', 'Toto kolo není hlášeno jako odcizené.');

            return redirect('/bike/' . $qrHash);
        }

        $currentUser = $this->authService->currentUser();

        return view('found/report', [
            'title' => 'Nahlásit nález kola – BikeSwap',
            'bike' => $bike,
            'currentUser' => $currentUser,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
            'turnstileSiteKey' => $this->config['turnstile']['site_key'] ?? '',
        ])->withLayout('layouts/public');
    }

    /**
     * Process found report submission.
     * POST /found/report/{qrHash}
     */
    public function report(Request $request): Response
    {
        $qrHash = $request->param('qrHash');
        $bike = $this->bikeRepository->findByQrHash($qrHash);

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isStolen()) {
            $this->session->flash('error', 'Toto kolo není hlášeno jako odcizené.');

            return redirect('/bike/' . $qrHash);
        }

        // CSRF check
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/found/report/{$qrHash}");
        }

        $currentUser = $this->authService->currentUser();

        // Validate — logged-in users skip email requirement (we have it)
        $validator = new Validator($request->all());

        if ($currentUser === null) {
            $validator->required('reporter_email', 'E-mail je povinný.')
                      ->email('reporter_email');

            // Turnstile CAPTCHA verification for anonymous users
            $secretKey = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';
            if ($secretKey !== '') {
                $token = $request->input('cf-turnstile-response', '');
                $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_POSTFIELDS     => http_build_query([
                        'secret'   => $secretKey,
                        'response' => $token,
                    ]),
                ]);
                $result = curl_exec($ch);
                $curlError = curl_errno($ch);
                curl_close($ch);

                if ($curlError !== 0 || $result === false) {
                    $this->session->setOldInput($request->all());
                    $this->session->flash('error', 'Ověření CAPTCHA selhalo (chyba spojení). Zkuste to znovu.');
                    return redirect("/found/report/{$qrHash}");
                }

                $data = json_decode($result, true) ?? [];
                if (!($data['success'] ?? false)) {
                    $this->session->setOldInput($request->all());
                    $this->session->flash('error', 'Ověření CAPTCHA selhalo. Zkuste to znovu.');

                    return redirect("/found/report/{$qrHash}");
                }
            }
        }

        $validator->required('found_location_text', 'Místo nálezu je povinné.');

        if ($validator->fails()) {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect("/found/report/{$qrHash}");
        }

        // Honeypot check (anti-bot)
        if ($request->input('website', '') !== '') {
            // Bot filled the honeypot field — silently redirect as if success
            $this->session->flash('success', 'Děkujeme za nahlášení nálezu.');

            return redirect('/bike/' . $qrHash);
        }

        try {
            $result = $this->foundReportService->createReport([
                'bike_id'              => $bike->getId(),
                'qr_hash_scanned'      => $qrHash,
                'reported_by'          => $currentUser?->getId(),
                'reporter_email'       => $currentUser?->getEmail() ?? $request->input('reporter_email'),
                'reporter_phone'       => $request->input('reporter_phone'),
                'reporter_ip'          => $request->ip(),
                'found_date'           => $request->input('found_date') ?: null,
                'found_location_text'  => $request->input('found_location_text'),
                'found_location_lat'   => $request->input('found_location_lat') ?: null,
                'found_location_lng'   => $request->input('found_location_lng') ?: null,
                'description'          => $request->input('description'),
            ]);

            // Redirect to the conversation (finder sees it immediately)
            $this->session->flash('success', 'Děkujeme za nahlášení nálezu! Majitel kola byl upozorněn.');

            return redirect('/found/conversation/' . $result['conversation_token']);
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());

            return redirect("/found/report/{$qrHash}");
        }
    }

    /**
     * Show conversation for the FINDER (accessed via token, no auth required).
     * GET /found/conversation/{token}
     */
    public function finderConversation(Request $request): Response
    {
        $token = $request->param('token');
        $report = $this->foundReportRepository->findByConversationToken($token);

        if ($report === null) {
            throw new \RuntimeException('Konverzace nenalezena.', 404);
        }

        // Load messages
        $messages = $this->messageRepository->findByReportId($report->getId());

        // Mark messages from owner/system as read for the finder
        $this->messageRepository->markAsReadForViewer($report->getId(), 'finder');

        // Load bike info
        $bike = $report->getBikeId() ? $this->bikeRepository->findById($report->getBikeId(), withPhotos: true) : null;

        $currentUser = $this->authService->currentUser();
        $layout = $currentUser ? 'layouts/app' : 'layouts/public';

        $policeUserIds = $this->getPoliceUserIds($messages);

        return view('found/conversation', [
            'title' => 'Konverzace o nálezu – BikeSwap',
            'report' => $report,
            'messages' => $messages,
            'bike' => $bike,
            'viewerType' => 'finder',
            'policeUserIds' => $policeUserIds,
            'currentUser' => $currentUser,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout($layout);
    }

    /**
     * Send a message as the FINDER.
     * POST /found/conversation/{token}/message
     */
    public function finderSendMessage(Request $request): Response
    {
        $token = $request->param('token');
        $report = $this->foundReportRepository->findByConversationToken($token);

        if ($report === null) {
            throw new \RuntimeException('Konverzace nenalezena.', 404);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/found/conversation/{$token}");
        }

        $currentUser = $this->authService->currentUser();

        try {
            $this->foundReportService->sendMessage(
                $report->getId(),
                'finder',
                $currentUser?->getId(),
                $request->input('message', '')
            );
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/found/conversation/{$token}");
    }

    /**
     * Show conversation for the OWNER (auth required).
     * GET /found/{reportId}/conversation
     */
    public function ownerConversation(Request $request): Response
    {
        $reportId = (int) $request->param('reportId');
        $report = $this->foundReportRepository->findById($reportId);
        $currentUser = $this->authService->currentUser();

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        // Verify the current user owns the bike
        $bike = $report->getBikeId() ? $this->bikeRepository->findById($report->getBikeId(), withPhotos: true) : null;

        if ($bike === null || (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin())) {
            throw new \RuntimeException('Nemáte oprávnění zobrazit tuto konverzaci.', 403);
        }

        $messages = $this->messageRepository->findByReportId($reportId);

        // Mark messages from finder/system as read for the owner
        $this->messageRepository->markAsReadForViewer($reportId, 'owner');

        // Get finder's karma display label (if registered)
        $finderLabel = 'Neregistrovaný uživatel';
        if ($report->getReportedBy() !== null) {
            $finderLabel = $this->karmaService->getDisplayLabel($report->getReportedBy());
        }

        $policeUserIds = $this->getPoliceUserIds($messages);

        return view('found/conversation', [
            'title' => 'Konverzace s nálezcem – BikeSwap',
            'report' => $report,
            'messages' => $messages,
            'bike' => $bike,
            'viewerType' => 'owner',
            'finderLabel' => $finderLabel,
            'policeUserIds' => $policeUserIds,
            'currentUser' => $currentUser,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Send a message as the OWNER (auth required).
     * POST /found/{reportId}/message
     */
    public function ownerSendMessage(Request $request): Response
    {
        $reportId = (int) $request->param('reportId');
        $report = $this->foundReportRepository->findById($reportId);
        $currentUser = $this->authService->currentUser();

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $bike = $report->getBikeId() ? $this->bikeRepository->findById($report->getBikeId()) : null;

        if ($bike === null || (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin())) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/found/{$reportId}/conversation");
        }

        try {
            $this->foundReportService->sendMessage(
                $reportId,
                'owner',
                $currentUser->getId(),
                $request->input('message', '')
            );
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/found/{$reportId}/conversation");
    }

    /**
     * Resolve a found report — owner confirms bike was returned.
     * POST /found/{reportId}/resolve
     */
    public function resolve(Request $request): Response
    {
        $reportId = (int) $request->param('reportId');
        $report = $this->foundReportRepository->findById($reportId);
        $currentUser = $this->authService->currentUser();

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $bike = $report->getBikeId() ? $this->bikeRepository->findById($report->getBikeId()) : null;

        if ($bike === null || (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin())) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/found/{$reportId}/conversation");
        }

        try {
            $this->foundReportService->resolveReport($reportId, $bike->getId());

            $this->session->flash('success', 'Kolo bylo označeno jako nalezené. Děkujeme!');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect('/bike/' . $bike->getQrHash());
    }

    /**
     * Close a found report conversation — owner dismisses the report.
     * POST /found/{reportId}/close
     */
    public function closeConversation(Request $request): Response
    {
        $reportId = (int) $request->param('reportId');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný token.');

            return redirect("/found/{$reportId}/conversation");
        }

        $user   = $this->authService->currentUser();
        $report = $this->foundReportRepository->findById($reportId);

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $bike = $report->getBikeId() ? $this->bikeRepository->findById($report->getBikeId()) : null;

        if ($bike === null || (!$bike->isOwnedBy($user->getId()) && !$user->isAdmin())) {
            throw new \RuntimeException('Přístup odepřen.', 403);
        }

        $this->foundReportRepository->updateStatus($reportId, 'closed');
        $this->session->flash('success', 'Konverzace byla uzavřena.');

        return redirect("/found/{$reportId}/conversation");
    }

    /**
     * Get IDs of police users who sent messages in this conversation.
     */
    private function getPoliceUserIds(array $messages): array
    {
        $userIds = [];
        foreach ($messages as $msg) {
            $uid = $msg->getSenderUserId();
            if ($uid !== null) {
                $userIds[$uid] = true;
            }
        }

        $policeIds = [];
        foreach (array_keys($userIds) as $uid) {
            $user = $this->userRepository->findById($uid);
            if ($user !== null && $user->isPolice()) {
                $policeIds[] = $uid;
            }
        }

        return $policeIds;
    }

    /**
     * Export conversation as PDF (police/admin only).
     * GET /found/{reportId}/export-pdf
     */
    public function exportPdf(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser || !$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $reportId = (int) $request->param('reportId');
        $report = $this->foundReportRepository->findById($reportId);

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $messages = $this->messageRepository->findByReportId($reportId);
        $bike = $report->getBikeId() ? $this->bikeRepository->findById($report->getBikeId()) : null;

        // Build HTML for PDF
        $html = '<html><head><meta charset="UTF-8"><style>';
        $html .= 'body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }';
        $html .= 'h1 { font-size: 18px; } h2 { font-size: 14px; }';
        $html .= '.msg { margin-bottom: 8px; padding: 6px; border-bottom: 1px solid #eee; }';
        $html .= '.meta { color: #666; font-size: 10px; }';
        $html .= '</style></head><body>';
        $html .= '<h1>BikeSwap — Export konverzace</h1>';
        $html .= '<p><strong>Nález #' . $reportId . '</strong></p>';

        if ($bike) {
            $html .= '<p>Kolo: ' . htmlspecialchars($bike->getFullName()) . '</p>';
        }
        if ($report->getFoundLocationText()) {
            $html .= '<p>Místo nálezu: ' . htmlspecialchars($report->getFoundLocationText()) . '</p>';
        }
        if ($report->getFoundDate()) {
            $html .= '<p>Datum nálezu: ' . htmlspecialchars($report->getFormattedFoundDate()) . '</p>';
        }

        $html .= '<h2>Zprávy</h2>';
        foreach ($messages as $msg) {
            $html .= '<div class="msg">';
            $html .= '<strong>' . htmlspecialchars($msg->getSenderLabel()) . '</strong>';
            $html .= ' <span class="meta">' . htmlspecialchars($msg->getFormattedTime()) . '</span><br>';
            $html .= nl2br(htmlspecialchars($msg->getMessage()));
            $html .= '</div>';
        }

        $html .= '<p class="meta">Exportováno: ' . date('d.m.Y H:i') . ' uživatelem ' . htmlspecialchars($currentUser->getName()) . '</p>';
        $html .= '</body></html>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'bikeswap-nalez-' . $reportId . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
    }
}