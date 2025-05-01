<?php

namespace App\Repository\Evenement;

use App\Entity\Evenement\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 *
 * @method Evenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evenement[]    findAll()
 * @method Evenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function save(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }




/*employe*/
public function findByCombinedFilters(?string $searchTerm, ?string $modalite, ?string $type)
{
    $qb = $this->createQueryBuilder('e');

    if ($searchTerm) {
        $qb->andWhere('e.titre LIKE :term')
           ->setParameter('term', "%$searchTerm%");
    }

    if ($modalite) {
        $qb->andWhere('e.modalite = :modalite')
           ->setParameter('modalite', $modalite);
    }

    if ($type) {
        $qb->andWhere('e.type = :type')
           ->setParameter('type', $type);
    }

    return $qb->getQuery()->getResult();
}

/*admin*/
public function findByTitleLieuModalite(?string $searchTerm)
{
    $qb = $this->createQueryBuilder('e');

    if ($searchTerm) {
        $qb->andWhere('e.titre LIKE :term')
           ->orWhere('e.lieu LIKE :term')
           ->orWhere('e.modalite LIKE :term')
           ->setParameter('term', "%$searchTerm%");
    }

    return $qb->getQuery()->getResult();
}

// Méthode pour récupérer tous les événements
public function findAllEvents()
{
    return $this->findAll();
}


public function countByModalite(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.modalite, COUNT(e.id) as count')
            ->groupBy('e.modalite')
            ->getQuery()
            ->getResult();
    }

    public function countByType(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.type, COUNT(e.id) as count')
            ->groupBy('e.type')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(): array
    {
        // Mise à jour des statuts avant calcul
        $this->updateAllStatuses();
        
        return $this->createQueryBuilder('e')
            ->select('e.status, COUNT(e.id) as count')
            ->groupBy('e.status')
            ->getQuery()
            ->getResult();
    }

    private function updateAllStatuses(): void
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            UPDATE evenement e
            SET status = CASE
                WHEN CURRENT_DATE() BETWEEN e.date_debut AND e.date_fin THEN 'EN_COURS'
                WHEN CURRENT_DATE() < e.date_debut THEN 'A_VENIR'
                WHEN CURRENT_DATE() > e.date_fin THEN 'TERMINE'
                ELSE status
            END
        ";
        
        $conn->executeStatement($sql);
    }


//    /**
//     * @return Evenement[] Returns an array of Evenement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Evenement
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
