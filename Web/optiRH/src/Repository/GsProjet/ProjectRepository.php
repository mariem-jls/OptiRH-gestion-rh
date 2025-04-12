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
    public function findByIndependentFilters(
        ?string $search,
        ?string $status,
        ?string $sort,
        int $page = 1,
        int $limit = 10
    ) {
        $qb = $this->createQueryBuilder('p');
    
        // Filtre de recherche indépendant
        if ($search) {
            $qb->andWhere('p.nom LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
    
        // Filtre de statut indépendant
        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }
    
        // Tri indépendant
        $sortField = match($sort) {
            'nom' => 'p.nom',
            'status' => 'p.status',
            default => 'p.createdAt'
        };
        $qb->orderBy($sortField, 'DESC');
    
        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }
   // Dans ProjectRepository.php
   public function findByFilters(array $filters, int $page = 1, int $limit = 10)
   {
       $qb = $this->createQueryBuilder('p');
       
       // Filtre de recherche
       if (!empty($filters['search'])) {
           $qb->andWhere('p.nom LIKE :search OR p.description LIKE :search')
              ->setParameter('search', '%'.$filters['search'].'%');
       }
   
       // Filtre de statut
       if (!empty($filters['status'])) {
           $qb->andWhere('p.status = :status')
              ->setParameter('status', $filters['status']);
       }
   
       // Gestion du tri avec mapping sécurisé
       $sortMapping = [
           'nom' => 'p.nom',
           'status' => 'p.status',
           'createdAt' => 'p.createdAt'
       ];
       
       $sortKey = $filters['sort'] ?? 'createdAt'; // Clé par défaut
       $sortField = $sortMapping[$sortKey] ?? 'p.createdAt';
       
       $qb->orderBy($sortField, 'DESC');
   
       return $this->paginator->paginate(
           $qb->getQuery(),
           $page,
           $limit,
           [
               'defaultSortFieldName' => $sortField,
               'defaultSortDirection' => 'DESC'
           ]
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
    
    
}
