<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findLateMissionNotificationByMissionId(int $missionId): ?Notification
    {
        return $this->createQueryBuilder('n')
            ->where('n.type = :type')
            ->andWhere('n.context LIKE :context')
            ->setParameter('type', Notification::TYPE_LATE_MISSION)
            ->setParameter('context', '%"mission_id":' . $missionId . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findUnreadByUser(User $user, int $limit = null): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function markAllAsRead(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function createLateMissionNotification(
        User $user,
        string $missionTitle,
        int $missionId,
        int $projectId,
        int $daysLate,
        string $routeName = 'mission_show',
        array $routeParams = []
    ): Notification {
        $notification = new Notification();
        $notification->setRecipient($user);
        $notification->setType(Notification::TYPE_LATE_MISSION);
        $notification->setMessage(sprintf(
            'La mission "%s" est en retard de %d jours',
            $missionTitle,
            $daysLate
        ));
        $notification->setContext([
            'mission_id' => $missionId,
            'project_id' => $projectId,
            'days_late' => $daysLate
        ]);
        $notification->setRouteName($routeName);
        $notification->setRouteParams($routeParams);

        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();

        return $notification;
    }

    public function createNewMissionNotification(
        User $user,
        string $missionTitle,
        int $missionId,
        int $projectId,
        string $routeName = 'mission_show',
        array $routeParams = []
    ): Notification {
        $notification = new Notification();
        $notification->setRecipient($user);
        $notification->setType(Notification::TYPE_NEW_MISSION);
        $notification->setMessage(sprintf(
            'Nouvelle mission : "%s"',
            $missionTitle
        ));
        $notification->setContext([
            'mission_id' => $missionId,
            'project_id' => $projectId
        ]);
        $notification->setRouteName($routeName);
        $notification->setRouteParams($routeParams);

        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();

        return $notification;
    }

    public function paginateByUser(User $user, int $page = 1, int $limit = 10)
    {
        $query = $this->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery();

        return new \Doctrine\ORM\Tools\Pagination\Paginator($query);
    }
}