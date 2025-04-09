<?php

namespace App\Repository\GsProjet;

use App\Entity\GsProjet\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nom LIKE :name')
            ->setParameter('name', '%'.$name.'%')
            ->getQuery()
            ->getResult();
    }
    // ...
    public function findByFilters(
        ?string $search = null,
        ?string $status = null,
        ?string $sort = null
    ): array {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.nom LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
            $field = ltrim($sort, '-');
            $qb->orderBy('p.' . $field, $direction);
        }

        return $qb->getQuery()->getResult();
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
