<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repository\BikeRepository;
use App\Repository\ReservationRepository;
use App\Repository\ReservationMessageRepository;
use App\Repository\FoundReportRepository;
use App\Repository\FoundReportMessageRepository;
use App\Response\Response;
use App\Services\AuthService;

class MessagePollController
{
    public function __construct(
        private AuthService $authService,
        private ReservationRepository $reservationRepo,
        private ReservationMessageRepository $messageRepo,
        private FoundReportRepository $foundReportRepo,
        private FoundReportMessageRepository $foundMessageRepo,
        private BikeRepository $bikeRepo,
    ) {}

    /**
     * GET /reservation/{id}/poll?after={lastId}
     *
     * Auth-guarded: only the owner or borrower of this reservation may poll.
     */
    public function reservationMessages(Request $request): Response
    {
        $id    = (int) $request->param('id');
        $after = (int) $request->query('after', '0');
        $user  = $this->authService->currentUser();

        if ($user === null) {
            return json(['error' => 'Unauthorized'], 401);
        }

        $reservation = $this->reservationRepo->findById($id);
        if ($reservation === null || !$reservation->involvesUser($user->getId())) {
            return json(['error' => 'Not found'], 404);
        }

        $isOwner = $reservation->isOwnedBy($user->getId());
        $msgs    = $this->messageRepo->findAfter($id, $after);

        $messages = array_map(function ($m) use ($isOwner) {
            $senderType = $m->getSenderType();
            $mine = ($isOwner && $senderType === 'owner') || (!$isOwner && $senderType === 'borrower');

            return [
                'id'     => $m->getId(),
                'text'   => $m->getMessage(),
                'sender' => $senderType,
                'mine'   => $mine,
                'label'  => $m->getSenderLabel(),
                'time'   => $m->getFormattedTime(),
                'system' => $m->isSystemMessage(),
            ];
        }, $msgs);

        return json(['messages' => $messages]);
    }

    /**
     * GET /found/{token}/poll?after={lastId}
     *
     * Token-based: accessible without auth (finder) or with auth (owner).
     * Determines viewerType from session — if the logged-in user owns the
     * bike for this report they are the owner, otherwise they are the finder.
     */
    public function foundMessages(Request $request): Response
    {
        $token = (string) $request->param('token');
        $after = (int) $request->query('after', '0');

        if ($token === '') {
            return json(['error' => 'Not found'], 404);
        }

        $report = $this->foundReportRepo->findByConversationToken($token);
        if ($report === null) {
            return json(['error' => 'Not found'], 404);
        }

        // Determine viewer type so the JS can mark messages as mine/theirs.
        $viewerType = 'finder';
        $user = $this->authService->currentUser();
        if ($user !== null && $report->getBikeId() !== null) {
            $bike = $this->bikeRepo->findById($report->getBikeId());
            if ($bike !== null && $bike->isOwnedBy($user->getId())) {
                $viewerType = 'owner';
            }
        }

        $msgs = $this->foundMessageRepo->findAfterByToken($token, $after);

        $messages = array_map(function ($m) use ($viewerType) {
            $senderType = $m->getSenderType();
            $mine = $senderType === $viewerType;

            return [
                'id'     => $m->getId(),
                'text'   => $m->getMessage(),
                'sender' => $senderType,
                'mine'   => $mine,
                'label'  => $m->getSenderLabel(),
                'time'   => $m->getFormattedTime(),
                'system' => $m->isSystemMessage(),
            ];
        }, $msgs);

        return json(['messages' => $messages]);
    }
}
