<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationManager
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function createLateMissionNotification(
        User $user,
        string $missionTitle,
        int $missionId,
        int $projectId,
        int $daysLate
    ): Notification {
        return $this->notificationRepository->createLateMissionNotification(
            $user,
            $missionTitle,
            $missionId,
            $projectId,
            $daysLate,
            'mission_show',
            ['id' => $missionId]
        );
    }

    public function createNewMissionNotification(
        User $user,
        string $missionTitle,
        int $missionId,
        int $projectId
    ): Notification {
        return $this->notificationRepository->createNewMissionNotification(
            $user,
            $missionTitle,
            $missionId,
            $projectId,
            'mission_show',
            ['id' => $missionId]
        );
    }

    public function markAllAsRead(User $user): void
    {
        $this->notificationRepository->markAllAsRead($user);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
        $this->entityManager->flush();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->notificationRepository->countUnreadByUser($user);
    }

    public function getRecentNotifications(User $user, int $limit = 5): array
    {
        return $this->notificationRepository->findRecentByUser($user, $limit);
    }
}