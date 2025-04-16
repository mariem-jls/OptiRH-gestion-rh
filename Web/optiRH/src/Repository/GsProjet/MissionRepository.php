<?php

namespace App\Repository\GsProjet;

use App\Entity\GsProjet\Mission;
use App\Entity\GsProjet\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Mission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Mission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Mission[]    findAll()
 * @method Mission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mission::class);
    }

    public function getProjectStats(Project $project): array
{
    $qb = $this->createQueryBuilder('m');
    
    $result = $qb->select(
            'COUNT(m.id) as total',
            'SUM(CASE WHEN m.status = :done THEN 1 ELSE 0 END) as completed',
            'SUM(CASE WHEN m.dateTerminer < CURRENT_DATE() AND m.status != :done THEN 1 ELSE 0 END) as overdue',
            'COUNT(DISTINCT m.assignedTo) as members'
        )
        ->where('m.project = :project')
        ->setParameters([
            'project' => $project,
            'done' => 'Done'
        ])
        ->getQuery()
        ->getSingleResult();

    return [
        'total' => (int) $result['total'],
        'completed' => (int) $result['completed'],
        'overdue' => (int) $result['overdue'],
        'members' => (int) $result['members']
    ];
}

public function findGroupedByStatus(Project $project): array
{
    $missions = $this->createQueryBuilder('m')
        ->where('m.project = :project')
        ->setParameter('project', $project)
        ->getQuery()
        ->getResult();

    $grouped = ['To Do' => [], 'In Progress' => [], 'Done' => []];
    
    foreach ($missions as $mission) {
        $status = $mission->getStatus() ?? 'To Do';
        $grouped[$status][] = $mission;
    }

    return $grouped;
}
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.status = :status')
            ->setParameter('status', $status)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMissionsByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateTerminer', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingMissions(\DateTime $date): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.dateTerminer > :date')
            ->setParameter('date', $date)
            ->orderBy('m.dateTerminer', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedMissions(): array
    {
        return $this->findByStatus('Done');
    }

    public function countMissionsByProject(Project $project): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function paginationQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC');
    }

    public function searchByTitleOrDescription(string $query): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.titre LIKE :query OR m.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->getQuery()
            ->getResult();
    }
    public function countActiveMissionsByProject(int $projectId): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.project = :projectId')
            ->andWhere('m.status != :doneStatus')
            ->setParameter('projectId', $projectId)
            ->setParameter('doneStatus', 'Done')
            ->getQuery()
            ->getSingleScalarResult();
    }



}