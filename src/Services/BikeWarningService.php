<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\BikeRepository;
use App\Repository\BikeWarningRepository;
use App\Repository\UserRepository;

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

    public function resolveWarning(int $warningId): void
    {
        $this->bikeWarningRepository->updateStatus($warningId, 'resolved');
    }
}
