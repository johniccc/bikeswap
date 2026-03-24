<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\BikeRepository;
use App\Repository\BikeWarningRepository;
use App\Repository\UserRepository;

/**
 * Manages the bike warning lifecycle: creating warnings, confirming owner pickups,
 * seizing unclaimed bikes, and returning seized bikes.
 *
 * Each action creates a timeline event and sends notifications/emails to the owner.
 */
class BikeWarningService
{
    private BikeWarningRepository $bikeWarningRepository;
    private BikeRepository $bikeRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private EmailService $emailService;

    public function __construct(
        BikeWarningRepository $bikeWarningRepository,
        BikeRepository $bikeRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        EmailService $emailService
    ) {
        $this->bikeWarningRepository = $bikeWarningRepository;
        $this->bikeRepository = $bikeRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
    }

    /**
     * Create a new warning for a bike and notify the owner.
     *
     * @param int         $bikeId    Bike to warn about
     * @param int         $createdBy User ID of the police/admin creating the warning
     * @param string      $reason    Reason for the warning
     * @param string      $deadline  Pickup deadline date (Y-m-d format)
     * @param string|null $location  Where the bike was found
     * @return int Warning ID
     * @throws \RuntimeException If bike not found or already has an active warning
     */
    public function createWarning(int $bikeId, int $createdBy, string $reason, string $deadline, ?string $location): int
    {
        $bike = $this->bikeRepository->findById($bikeId);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nebylo nalezeno.');
        }

        $existing = $this->bikeWarningRepository->findActiveByBikeId($bikeId);
        if ($existing !== null) {
            throw new \RuntimeException('Pro toto kolo již existuje aktivní upozornění.');
        }

        $locationDesc = $location ?? 'Pardubice hlavní nádraží';

        $warningId = $this->bikeWarningRepository->create([
            'bike_id'              => $bikeId,
            'created_by'           => $createdBy,
            'reason'               => $reason,
            'deadline'             => $deadline,
            'location_description' => $locationDesc,
            'type'                 => 'warning',
        ]);

        $this->notificationService->notify(
            $bike->getOwnerId(),
            'bike_warning',
            'Upozornění na kolo: ' . $bike->getFullName(),
            '/bike/' . $bike->getQrHash(),
            'Vaše kolo bylo nalezeno na místě: ' . $locationDesc . '. Vyzvedněte si ho do ' . date('d.m.Y', strtotime($deadline)) . '.'
        );

        $this->emailService->sendBikeWarningNotification(
            $bike->getOwnerId(),
            $bike->getFullName(),
            $deadline,
            $locationDesc
        );

        return $warningId;
    }

    /**
     * Confirm that the owner picked up their warned bike.
     *
     * @param int $bikeId  Bike being picked up
     * @param int $ownerId Owner confirming the pickup
     * @return void
     * @throws \RuntimeException If bike not found, not owned by user, or no active warning
     */
    public function confirmPickup(int $bikeId, int $ownerId): void
    {
        $bike = $this->bikeRepository->findById($bikeId);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nebylo nalezeno.');
        }

        if (!$bike->isOwnedBy($ownerId)) {
            throw new \RuntimeException('Nemáte oprávnění potvrdit vyzvednutí tohoto kola.');
        }

        if (!$this->bikeWarningRepository->hasActiveWarning($bikeId)) {
            throw new \RuntimeException('Pro toto kolo neexistuje aktivní upozornění.');
        }

        // Find the last warning creator for notification
        $timeline = $this->bikeWarningRepository->findTimelineByBikeId($bikeId);
        $lastWarningCreator = null;
        foreach ($timeline as $event) {
            if ($event->isWarning() && $event->isActive()) {
                $lastWarningCreator = $event->getCreatedBy();
                break;
            }
        }

        // Resolve all active warnings
        $this->bikeWarningRepository->resolveActiveWarnings($bikeId);

        // Create pickup event
        $this->bikeWarningRepository->create([
            'bike_id'              => $bikeId,
            'created_by'           => $ownerId,
            'type'                 => 'owner_pickup',
            'status'               => 'resolved',
        ]);

        // Notify the warning creator
        if ($lastWarningCreator !== null) {
            $this->notificationService->notify(
                $lastWarningCreator,
                'warning_pickup',
                'Majitel si vyzvednul kolo: ' . $bike->getFullName(),
                '/bike/' . $bike->getQrHash(),
                'Majitel kola ' . $bike->getFullName() . ' potvrdil vyzvednutí.'
            );
        }
    }

    /**
     * Seize an unclaimed bike after the warning deadline passes.
     *
     * @param int $bikeId   Bike to seize
     * @param int $seizedBy User ID of the officer seizing the bike
     * @return void
     * @throws \RuntimeException If bike not found
     */
    public function seizeBike(int $bikeId, int $seizedBy): void
    {
        $bike = $this->bikeRepository->findById($bikeId);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nebylo nalezeno.');
        }

        // Resolve all active warnings
        $this->bikeWarningRepository->resolveActiveWarnings($bikeId);

        // Create seized event
        $this->bikeWarningRepository->create([
            'bike_id'              => $bikeId,
            'created_by'           => $seizedBy,
            'type'                 => 'seized',
            'status'               => 'resolved',
        ]);

        // Change bike status to seized
        $this->bikeRepository->updateStatus($bikeId, 'seized');

        // Notify owner
        $this->notificationService->notify(
            $bike->getOwnerId(),
            'warning_seized',
            'Vaše kolo bylo odvezeno: ' . $bike->getFullName(),
            '/bike/' . $bike->getQrHash(),
            'Vaše kolo ' . $bike->getFullName() . ' bylo odvezeno.'
        );

        $this->emailService->sendBikeSeizedNotification(
            $bike->getOwnerId(),
            $bike->getFullName()
        );
    }

    /**
     * Return a seized bike to its owner, restoring active status.
     *
     * @param int $bikeId     Bike being returned
     * @param int $returnedBy User ID of the officer returning the bike
     * @return void
     * @throws \RuntimeException If bike not found or not in seized status
     */
    public function returnBike(int $bikeId, int $returnedBy): void
    {
        $bike = $this->bikeRepository->findById($bikeId);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nebylo nalezeno.');
        }

        if (!$bike->isSeized()) {
            throw new \RuntimeException('Toto kolo není ve stavu odvezeno.');
        }

        // Change bike status back to active
        $this->bikeRepository->updateStatus($bikeId, 'active');

        // Create a return event in timeline
        $this->bikeWarningRepository->create([
            'bike_id'    => $bikeId,
            'created_by' => $returnedBy,
            'type'       => 'owner_pickup',
            'status'     => 'resolved',
            'reason'     => 'Kolo vráceno majiteli',
        ]);

        // Notify owner
        $this->notificationService->notify(
            $bike->getOwnerId(),
            'warning_returned',
            'Vaše kolo bylo vráceno: ' . $bike->getFullName(),
            '/bike/' . $bike->getQrHash(),
            'Vaše kolo ' . $bike->getFullName() . ' bylo vráceno.'
        );
    }

    /**
     * Manually resolve a single warning by ID.
     *
     * @param int $warningId Warning to resolve
     * @return void
     */
    public function resolveWarning(int $warningId): void
    {
        $this->bikeWarningRepository->updateStatus($warningId, 'resolved');
    }
}
