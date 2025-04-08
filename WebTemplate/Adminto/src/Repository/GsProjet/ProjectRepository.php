<?php

namespace App\Repository\GsProjet;

use App\Entity\GsProjet\Project;
use App\Entity\GsProjet\User;
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

public function findFiltered(?string $status, ?string $sort): array
{
    $qb = $this->createQueryBuilder('p');

    // Filtrage par statut
    if ($status && $status !== 'all') {
        $qb->andWhere('p.status = :status')
           ->setParameter('status', $status);
    }

    // Tri
    if ($sort) {
        $sortParts = explode(' ', $sort);
        $qb->orderBy('p.' . $sortParts[0], $sortParts[1] ?? 'ASC');
    } else {
        $qb->orderBy('p.createdAt', 'DESC'); // Tri par défaut
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
