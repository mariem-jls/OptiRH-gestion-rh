<?php

namespace App\Repository\GsProjet;

use App\Entity\GsProjet\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface; 
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private PaginatorInterface $paginator
    ) {
        parent::__construct($registry, Project::class);
    }
    public function findByFilters(array $filters, int $page = 1)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        
        // Filtre par recherche
        if (!empty($filters['search'])) {
            $queryBuilder
                ->andWhere('p.nom LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%'.$filters['search'].'%');
        }
        
        // Filtre par statut
        if (!empty($filters['status'])) {
            $queryBuilder
                ->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }
        
        // Tri
        switch ($filters['sort'] ?? 'date_desc') {
            case 'name_asc':
                $queryBuilder->orderBy('p.nom', 'ASC');
                break;
            case 'name_desc':
                $queryBuilder->orderBy('p.nom', 'DESC');
                break;
            case 'date_asc':
                $queryBuilder->orderBy('p.createdAt', 'ASC');
                break;
            case 'date_desc':
            default:
                $queryBuilder->orderBy('p.createdAt', 'DESC');
        }
        
        return $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            10
        );
    }
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nom LIKE :name')
            ->setParameter('name', '%'.$name.'%')
            ->getQuery()
            ->getResult();
    }
    public function findWithFilters(?string $search, ?string $status, int $page = 1)
    {
        $queryBuilder = $this->createQueryBuilder('p');
    
        // Filtre par recherche
        if (!empty(trim($search))) {
            $queryBuilder
                ->andWhere('p.nom LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }
    
        // Filtre par statut avec vérification des constantes
        if ($status && in_array($status, [
            Project::STATUS_ACTIVE,
            Project::STATUS_INACTIVE,
            Project::STATUS_COMPLETED,
            Project::STATUS_DELAYED
        ])) {
            $queryBuilder
                ->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }
    
        // Tri et pagination
        return $this->paginator->paginate(
            $queryBuilder
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery(),
            $page,
            10
        );
    }
    public function filterProjects(?string $search, ?string $status, int $page = 1)
{
    $query = $this->createQueryBuilder('p');

    // Filtre recherche
    if (!empty($search)) {
        $query->andWhere('p.nom LIKE :search OR p.description LIKE :search')
             ->setParameter('search', '%'.$search.'%');
    }

    // Filtre statut avec les constantes
    if (!empty($status) && in_array($status, [
        Project::STATUS_ACTIVE,
        Project::STATUS_INACTIVE, 
        Project::STATUS_COMPLETED,
        Project::STATUS_DELAYED
    ])) {
        $query->andWhere('p.status = :status')
             ->setParameter('status', $status);
    }

    $query->orderBy('p.createdAt', 'DESC');

    return $this->paginator->paginate(
        $query->getQuery(),
        $page,
        10
    );
}
    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdBy = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findActiveProjects(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status != :status OR p.status IS NULL')
            ->setParameter('status', 'Done')
            ->getQuery()
            ->getResult();
    }

    public function countProjectsByStatus(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as project_count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();
    }
    

    public function paginationQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');
    }


    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function hasActiveMissions(int $projectId): bool
{
    return $this->createQueryBuilder('p')
        ->select('COUNT(m.id)')
        ->join('p.missions', 'm')
        ->where('p.id = :projectId')
        ->andWhere('m.status NOT IN (:completedStatuses)')
        ->setParameter('projectId', $projectId)
        ->setParameter('completedStatuses', ['Done', 'Cancelled'])
        ->getQuery()
        ->getSingleScalarResult() > 0;
}
    
}
