<?php

namespace App\Repository\Transport;

use App\Entity\Transport\Trajet;
use App\Entity\Transport\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicule>
 *
 * @method Vehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vehicule[]    findAll()
 * @method Vehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Vehicule[]    findAvailableForTrajet(Trajet $trajet)
 */
class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    public function save(Vehicule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Vehicule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function countReservationsForVehicule(int $vehiculeId): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(r.id)')
            ->join('v.reservations', 'r')
            ->where('v.id = :id')
            ->setParameter('id', $vehiculeId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les véhicules disponibles pour un trajet spécifique
     */
    public function findAvailableForTrajet(Trajet $trajet): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.trajet = :trajet')
            ->andWhere('v.disponibilite = :dispo')
            ->andWhere('v.nbrplace > 0')
            ->setParameter('trajet', $trajet)
            ->setParameter('dispo', 'Disponible')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les véhicules disponibles par départ/arrivée
     */
    public function findAvailableByDepartureArrival(string $depart, string $arrive): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.trajet', 't')
            ->where('t.depart LIKE :depart')
            ->andWhere('t.arrive LIKE :arrive')
            ->andWhere('v.disponibilite = :dispo')
            ->andWhere('v.nbrplace > 0')
            ->setParameter('depart', '%'.$depart.'%')
            ->setParameter('arrive', '%'.$arrive.'%')
            ->setParameter('dispo', 'Disponible')
            ->getQuery()
            ->getResult();
    }

    

    public function findByDepartureOrArrival(?string $depart, ?string $arrive): array
{
    $qb = $this->createQueryBuilder('v')
        ->join('v.trajet', 't')
        ->where('v.disponibilite = :disponible')
        ->setParameter('disponible', 'Disponible');

    if (!empty($depart)) {
        $qb->andWhere('t.depart LIKE :depart')
           ->setParameter('depart', '%'.$depart.'%');
    }

    if (!empty($arrive)) {
        $qb->andWhere('t.arrive LIKE :arrive')
           ->setParameter('arrive', '%'.$arrive.'%');
    }

    return $qb->getQuery()->getResult();
}




public function findByCoordinates($departLat, $departLon, $arriveLat, $arriveLon)
{
    // Implémentez votre logique de recherche ici
    // Exemple simple : recherche par proximité
    return $this->createQueryBuilder('v')
        ->join('v.trajet', 't')
        ->where('ACOS(SIN(:departLat)*SIN(t.latitudeDepart) + COS(:departLat)*COS(t.latitudeDepart)*COS(t.longitudeDepart-:departLon)) * 6371 <= 10') // 10km radius
        ->andWhere('ACOS(SIN(:arriveLat)*SIN(t.latitudeArrivee) + COS(:arriveLat)*COS(t.latitudeArrivee)*COS(t.longitudeArrivee-:arriveLon)) * 6371 <= 10')
        ->setParameters([
            'departLat' => deg2rad($departLat),
            'departLon' => deg2rad($departLon),
            'arriveLat' => deg2rad($arriveLat),
            'arriveLon' => deg2rad($arriveLon)
        ])
        ->getQuery()
        ->getResult();
}


}