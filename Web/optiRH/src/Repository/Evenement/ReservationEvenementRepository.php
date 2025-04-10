<?php

namespace App\Repository\Evenement;

use App\Entity\Evenement\ReservationEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Evenement\Evenement;


/**
 * @extends ServiceEntityRepository<ReservationEvenement>
 *
 * @method ReservationEvenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservationEvenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservationEvenement[]    findAll()
 * @method ReservationEvenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationEvenement::class);
    }

    public function save(ReservationEvenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReservationEvenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserId(int $userId)
{
    $reservations = $this->createQueryBuilder('r')
        ->innerJoin('r.user', 'u')
        ->where('u.id = :userId')
        ->setParameter('userId', $userId)
        ->getQuery()
        ->getResult();

    return $reservations ?: [];
    
}

public function findByEvenementId(int $evenementId): array
{
    $reservations = $this->createQueryBuilder('r')
        ->andWhere('r.Evenement = :evenementId')
        ->setParameter('evenementId', $evenementId)
        ->orderBy('r.date_reservation', 'DESC')
        ->getQuery()
        ->getResult();
     return $reservations ?: [];
}

//    /**
//     * @return ReservationEvenement[] Returns an array of ReservationEvenement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ReservationEvenement
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
