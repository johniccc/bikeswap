<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\TheftReportRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\TheftService;

class TheftController
{
    private BikeRepository $bikeRepository;
    private TheftReportRepository $theftReportRepository;
    private TheftService $theftService;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        BikeRepository $bikeRepository,
        TheftReportRepository $theftReportRepository,
        TheftService $theftService,
        AuthService $authService,
        Session $session
    ) {
        $this->bikeRepository = $bikeRepository;
        $this->theftReportRepository = $theftReportRepository;
        $this->theftService = $theftService;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Show theft report form.
     * GET /theft/report/{bikeId}
     */
    public function reportForm(Request $request): Response
    {
        $bikeId = (int) $request->param('bikeId');
        $bike = $this->bikeRepository->findById($bikeId, withPhotos: true);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Krádež může nahlásit pouze majitel kola.', 403);
        }

        if ($bike->isStolen()) {
            $this->session->flash('error', 'Toto kolo je již nahlášeno jako odcizené.');

            return redirect('/bike/' . $bike->getQrHash());
        }

        return view('theft/report', [
            'title' => 'Nahlásit krádež – BikeSwap',
            'bike' => $bike,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ]);
    }

    /**
     * Process theft report submission.
     * POST /theft/report/{bikeId}
     */
    public function report(Request $request): Response
    {
        $bikeId = (int) $request->param('bikeId');
        $bike = $this->bikeRepository->findById($bikeId);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/theft/report/{$bikeId}");
        }

        // Validate
        $validator = new Validator($request->all());
        $validator->required('theft_location_text', 'Místo krádeže je povinné.');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect("/theft/report/{$bikeId}");
        }

        // Report the theft
        try {
            $reportId = $this->theftService->reportTheft($bike, $currentUser->getId(), [
                'theft_date'          => $request->input('theft_date') ?: null,
                'theft_location_text' => $request->input('theft_location_text'),
                'theft_location_lat'  => $request->input('theft_location_lat') ?: null,
                'theft_location_lng'  => $request->input('theft_location_lng') ?: null,
                'description'         => $request->input('description'),
                'police_case_number'  => $request->input('police_case_number'),
                'reporter_ip'         => $request->ip(),
            ]);
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());

            return redirect("/theft/report/{$bikeId}");
        }

        if ($request->wantsJson()) {
            return json(['report_id' => $reportId], 201);
        }

        $this->session->flash('success', 'Krádež byla nahlášena. Vaše kolo je nyní v databázi odcizených kol.');

        return redirect('/bike/' . $bike->getQrHash());
    }

    /**
     * Public list of stolen bikes (searchable by anyone).
     * GET /stolen
     */
    public function publicList(Request $request): Response
    {
        $filters = [
            'brand' => $request->query('brand'),
            'color' => $request->query('color'),
            'frame_number' => $request->query('frame_number'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        $stolenBikes = $this->bikeRepository->findStolen($filters, withPhotos: true);

        if ($request->wantsJson()) {
            return json(['bikes' => array_map(fn($b) => [
                'id' => $b->getId(),
                'brand' => $b->getBrand(),
                'model' => $b->getModel(),
                'color' => $b->getColor(),
                'qr_hash' => $b->getQrHash(),
            ], $stolenBikes)]);
        }

        return view('theft/stolen-list', [
            'title' => 'Odcizená kola – BikeSwap',
            'bikes' => $stolenBikes,
            'filters' => $filters,
            'session' => $this->session,
        ]);
    }

    /**
     * Resolve a theft report (owner found their bike).
     * POST /theft/{reportId}/resolve
     */
    public function resolve(Request $request): Response
    {
        $reportId = (int) $request->param('reportId');
        $report = $this->theftReportRepository->findById($reportId);
        $currentUser = $this->authService->currentUser();

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $bike = $this->bikeRepository->findById($report->getBikeId());

        if ($bike === null || (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin())) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect('/bike/' . $bike->getQrHash());
        }

        try {
            $this->theftService->resolveTheft($reportId, $bike->getId());
            $this->session->flash('success', 'Kolo bylo označeno jako nalezené. Děkujeme!');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect('/bike/' . $bike->getQrHash());
    }
}