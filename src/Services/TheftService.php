<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Entity\Bike;
use App\Entity\TheftReport;
use App\Repository\BikeRepository;
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
    private Database $db;

    public function __construct(
        TheftReportRepository $theftReportRepository,
        BikeRepository $bikeRepository,
        Database $db
    ) {
        $this->theftReportRepository = $theftReportRepository;
        $this->bikeRepository = $bikeRepository;
        $this->db = $db;
    }

    /**
     * Report a bike as stolen.
     * 
     * Creates a theft report and changes the bike status to 'stolen' in a single transaction.
     * 
     * @return array{success: bool, report_id?: int, error?: string}
     */
    public function reportTheft(Bike $bike, int $reportedBy, array $data): array
    {
        // Check if bike is already reported as stolen
        $existing = $this->theftReportRepository->findActiveByBikeId($bike->getId());

        if ($existing !== null) {
            return ['success' => false, 'error' => 'Toto kolo je již nahlášeno jako odcizené.'];
        }

        // Only active or recovered bikes can be reported as stolen
        if (!$bike->isActive() && !$bike->isRecovered()) {
            return ['success' => false, 'error' => 'Toto kolo nelze nahlásit jako odcizené (aktuální stav: ' . $bike->getStatusLabel() . ').'];
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

            return ['success' => true, 'report_id' => $reportId];
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * Cancel/resolve a theft report (bike was found by owner).
     * Changes bike status back to 'recovered'.
     */
    public function resolveTheft(int $reportId, int $bikeId): array
    {
        $report = $this->theftReportRepository->findById($reportId);

        if ($report === null) {
            return ['success' => false, 'error' => 'Hlášení nenalezeno.'];
        }

        if ($report->isResolved()) {
            return ['success' => false, 'error' => 'Toto hlášení je již vyřešené.'];
        }

        try {
            $this->db->beginTransaction();

            $this->theftReportRepository->updateStatus($reportId, 'resolved');
            $this->bikeRepository->updateStatus($bikeId, 'recovered');

            $this->db->commit();

            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }
}