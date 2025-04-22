<?php

namespace App\Repository;

use App\Entity\DemandeMatching;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DemandeMatchingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeMatching::class);
    }

    public function findByDemandeAndOffre(int $demandeId, int $offreId): ?DemandeMatching
    {
        return $this->createQueryBuilder('dm')
            ->where('dm.demande = :demandeId')
            ->andWhere('dm.offre = :offreId')
            ->setParameter('demandeId', $demandeId)
            ->setParameter('offreId', $offreId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}