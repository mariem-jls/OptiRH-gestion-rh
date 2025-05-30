<?php

namespace App\Repository\Transport;

use App\Entity\Transport\Trajet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trajet>
 *
 * @method Trajet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trajet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trajet[]    findAll()
 * @method Trajet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrajetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trajet::class);
    }

    public function save(Trajet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Trajet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function getReservationStatsByVehicleType(): array
    {
        return $this->createQueryBuilder('t')
            ->select('v.type as vehicleType, COUNT(r.id) as reservationCount')
            ->join('t.vehicules', 'v')
            ->leftJoin('v.reservations', 'r')
            ->groupBy('v.type')
            ->orderBy('reservationCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getReservationStatsByPoints(): array
{
    return $this->createQueryBuilder('t')
        ->select(
            't.depart as pointDepart',
            't.arrive as pointArrive',
            'COUNT(rt.id) as reservationCount'
        )
        ->leftJoin(
            'App\Entity\Transport\ReservationTrajet', 
            'rt', 
            'WITH', 
            'rt.trajet = t.id'
        )
        ->groupBy('t.depart, t.arrive')
        ->orderBy('reservationCount', 'DESC')
        ->getQuery()
        ->getResult();
        
}
public function getTopStations(int $limit = 5): array
{
    return $this->createQueryBuilder('t')
        ->select('
            t.station as nomStation,
            COUNT(r.id) as totalReservations,
            COUNT(DISTINCT t.id) as nbTrajets
        ')
        ->leftJoin(
            'App\Entity\Transport\ReservationTrajet', 
            'r', 
            'WITH', 
            'r.trajet = t.id'
        )
        ->groupBy('t.station')
        ->orderBy('totalReservations', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
//    /**
//     * @return Trajet[] Returns an array of Trajet objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Trajet
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
