<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Entity\FoundReport;
use App\Repository\BikeRepository;
use App\Repository\FoundReportRepository;
use App\Repository\FoundReportMessageRepository;

/**
 * Found Report service.
 * 
 * Handles business logic for the found bike reporting workflow:
 * 1. Finder submits a found report (with or without registration)
 * 2. Owner gets notified and can message the finder anonymously
 * 3. Either party can send messages through the conversation
 * 4. Owner can mark the bike as found/resolved
 */
class FoundReportService
{
    private FoundReportRepository $reportRepository;
    private FoundReportMessageRepository $messageRepository;
    private BikeRepository $bikeRepository;
    private NotificationService $notificationService;
    private EmailService $emailService;
    private KarmaService $karmaService;
    private Database $db;

    public function __construct(
        FoundReportRepository $reportRepository,
        FoundReportMessageRepository $messageRepository,
        BikeRepository $bikeRepository,
        NotificationService $notificationService,
        EmailService $emailService,
        KarmaService $karmaService,
        Database $db
    ) {
        $this->reportRepository = $reportRepository;
        $this->messageRepository = $messageRepository;
        $this->bikeRepository = $bikeRepository;
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
        $this->karmaService = $karmaService;
        $this->db = $db;
    }

    /**
     * Create a new found report.
     * 
     * Generates a conversation token, creates the report,
     * notifies the bike owner, and sends confirmation email to finder.
     * 
     * @return array{success: bool, report_id?: int, conversation_token?: string, error?: string}
     */
    public function createReport(array $data): array
    {
        $bikeId = $data['bike_id'] ?? null;

        if ($bikeId === null) {
            return ['success' => false, 'error' => 'Kolo nebylo identifikováno.'];
        }

        $bike = $this->bikeRepository->findById($bikeId);

        if ($bike === null) {
            return ['success' => false, 'error' => 'Kolo nenalezeno.'];
        }

        // Generate unique conversation token
        $conversationToken = bin2hex(random_bytes(32));

        try {
            $this->db->beginTransaction();

            $reportId = $this->reportRepository->create([
                'bike_id'              => $bikeId,
                'qr_hash_scanned'      => $data['qr_hash_scanned'] ?? null,
                'reported_by'          => $data['reported_by'] ?? null,
                'reporter_email'       => $data['reporter_email'] ?? null,
                'reporter_phone'       => $data['reporter_phone'] ?? null,
                'reporter_ip'          => $data['reporter_ip'] ?? null,
                'conversation_token'   => $conversationToken,
                'found_date'           => $data['found_date'] ?? null,
                'found_location_text'  => $data['found_location_text'] ?? null,
                'found_location_lat'   => $data['found_location_lat'] ?? null,
                'found_location_lng'   => $data['found_location_lng'] ?? null,
                'description'          => $data['description'] ?? null,
            ]);

            // Create initial system message in the conversation
            $this->messageRepository->create(
                $reportId,
                'system',
                null,
                'Nález byl nahlášen. Majitel kola byl upozorněn a může vám odpovědět prostřednictvím této konverzace.'
            );

            // Notify the bike owner (in-app)
            $this->notificationService->notifyFoundReport($bike->getOwnerId(), $bike, $reportId);

            // Send email to the bike owner (if they have it enabled)
            $this->emailService->sendFoundReportNotification($bike->getOwnerId(), $bike, $reportId);

            // Send confirmation email to the finder with conversation link
            if (!empty($data['reporter_email'])) {
                $this->emailService->sendFinderConfirmation(
                    $data['reporter_email'],
                    $bike,
                    $conversationToken
                );
            }

            // Recalculate karma if finder is a registered user
            if (!empty($data['reported_by'])) {
                $this->karmaService->recalculate((int) $data['reported_by']);
            }

            $this->db->commit();

            return [
                'success' => true,
                'report_id' => $reportId,
                'conversation_token' => $conversationToken,
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * Send a message in a found report conversation.
     * 
     * @return array{success: bool, message_id?: int, error?: string}
     */
    public function sendMessage(int $reportId, string $senderType, ?int $senderUserId, string $message): array
    {
        $report = $this->reportRepository->findById($reportId);

        if ($report === null) {
            return ['success' => false, 'error' => 'Hlášení nenalezeno.'];
        }

        if ($report->isResolved()) {
            return ['success' => false, 'error' => 'Tato konverzace je již uzavřena.'];
        }

        $message = trim($message);

        if ($message === '') {
            return ['success' => false, 'error' => 'Zpráva nemůže být prázdná.'];
        }

        $messageId = $this->messageRepository->create($reportId, $senderType, $senderUserId, $message);

        // Update report status to 'contacted' on first real message exchange
        if ($report->isPending()) {
            $this->reportRepository->updateStatus($reportId, 'contacted');
        }

        // Notify the other party
        $bike = $this->bikeRepository->findById($report->getBikeId());

        if ($senderType === 'finder' && $bike !== null) {
            // Finder sent a message → notify owner
            $this->notificationService->notifyNewMessage($bike->getOwnerId(), $bike, $reportId);
            $this->emailService->sendMessageNotification($bike->getOwnerId(), $bike, $reportId);
        } elseif ($senderType === 'owner' && !empty($report->getReporterEmail())) {
            // Owner sent a message → email the finder (no in-app notification, finder may not be registered)
            $this->emailService->sendMessageToFinder(
                $report->getReporterEmail(),
                $bike,
                $report->getConversationToken()
            );
        }

        return ['success' => true, 'message_id' => $messageId];
    }

    /**
     * Resolve a found report (bike was returned to owner).
     * 
     * Marks the report as resolved, adds a system message,
     * and resolves the associated theft report.
     * 
     * @return array{success: bool, error?: string}
     */
    public function resolveReport(int $reportId, int $bikeId): array
    {
        $report = $this->reportRepository->findById($reportId);

        if ($report === null) {
            return ['success' => false, 'error' => 'Hlášení nenalezeno.'];
        }

        if ($report->isResolved()) {
            return ['success' => false, 'error' => 'Toto hlášení je již vyřešené.'];
        }

        try {
            $this->db->beginTransaction();

            // Resolve the found report
            $this->reportRepository->updateStatus($reportId, 'resolved');

            // Add system message to conversation
            $this->messageRepository->create(
                $reportId,
                'system',
                null,
                'Majitel označil kolo jako nalezené. Děkujeme za pomoc!'
            );

            // Change bike status back to active
            $this->bikeRepository->updateStatus($bikeId, 'active');

            // Recalculate karma for the finder
            if ($report->getReportedBy() !== null) {
                $this->karmaService->recalculate($report->getReportedBy());
            }

            $this->db->commit();

            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }
}