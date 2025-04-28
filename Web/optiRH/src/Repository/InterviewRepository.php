<?php
// src/Repository/InterviewRepository.php
namespace App\Repository;

use App\Entity\Interview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interview::class);
    }

    public function save(Interview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Interview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function isSlotTaken(\DateTimeInterface $dateTime): bool
    {
        return $this->createQueryBuilder('i')
                ->andWhere('i.dateTime = :dateTime')
                ->setParameter('dateTime', $dateTime)
                ->getQuery()
                ->getOneOrNullResult() !== null;
    }
}