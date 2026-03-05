<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Entity\Bike;
use App\Entity\TheftReport;
use App\Repository\BikeRepository;
use App\Repository\FoundReportRepository;
use App\Repository\TheftReportRepository;

/**
 * Theft service.
 * 
 * Handles the business logic of reporting a stolen bike.
 * Uses a database transaction to ensure both the report and status change happen together.
 */
class TheftService
{
    private TheftReportRepository $theftReportRepository;
    private BikeRepository $bikeRepository;
    private FoundReportRepository $foundReportRepository;
    private Database $db;

    public function __construct(
        TheftReportRepository $theftReportRepository,
        BikeRepository $bikeRepository,
        FoundReportRepository $foundReportRepository,
        Database $db
    ) {
        $this->theftReportRepository = $theftReportRepository;
        $this->bikeRepository = $bikeRepository;
        $this->foundReportRepository = $foundReportRepository;
        $this->db = $db;
    }

    /**
     * Report a bike as stolen.
     *
     * Creates a theft report and changes the bike status to 'stolen' in a single transaction.
     *
     * @return int Theft report ID
     * @throws \RuntimeException If bike already reported as stolen or not active
     */
    public function reportTheft(Bike $bike, int $reportedBy, array $data): int
    {
        // Check if bike is already reported as stolen
        $existing = $this->theftReportRepository->findActiveByBikeId($bike->getId());

        if ($existing !== null) {
            throw new \RuntimeException('Toto kolo je již nahlášeno jako odcizené.', 409);
        }

        // Only active bikes can be reported as stolen
        if (!$bike->isActive()) {
            throw new \RuntimeException('Toto kolo nelze nahlásit jako odcizené (aktuální stav: ' . $bike->getStatusLabel() . ').', 400);
        }

        try {
            $this->db->beginTransaction();

            // Create the theft report
            $reportId = $this->theftReportRepository->create([
                'bike_id'             => $bike->getId(),
                'reported_by'         => $reportedBy,
                'theft_date'          => $data['theft_date'] ?? null,
                'theft_location_text' => $data['theft_location_text'] ?? null,
                'theft_location_lat'  => $data['theft_location_lat'] ?? null,
                'theft_location_lng'  => $data['theft_location_lng'] ?? null,
                'description'         => $data['description'] ?? null,
                'police_case_number'  => $data['police_case_number'] ?? null,
                'reporter_ip'         => $data['reporter_ip'] ?? null,
            ]);

            // Change bike status to stolen
            $this->bikeRepository->updateStatus($bike->getId(), 'stolen');

            $this->db->commit();

            return $reportId;
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * Resolve a theft report (bike was found by owner).
     *
     * Marks the report as resolved and returns the bike to 'active' status,
     * so the owner can continue using all features (sharing, re-reporting if needed).
     * The theft history is preserved in the theft_reports table.
     *
     * @throws \RuntimeException If report not found or already resolved
     */
    public function resolveTheft(int $reportId, int $bikeId): void
    {
        $report = $this->theftReportRepository->findById($reportId);

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        if ($report->isResolved()) {
            throw new \RuntimeException('Toto hlášení je již vyřešené.', 400);
        }

        try {
            $this->db->beginTransaction();

            $this->theftReportRepository->updateStatus($reportId, 'resolved');
            $this->bikeRepository->updateStatus($bikeId, 'active');

            // Close all open found report conversations for this bike
            $this->foundReportRepository->closeAllByBikeId($bikeId);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }
}