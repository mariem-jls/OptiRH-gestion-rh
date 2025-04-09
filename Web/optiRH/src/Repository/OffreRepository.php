<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 *
 * @method Offre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offre[]    findAll()
 * @method Offre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    public function save(Offre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Offre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function findActiveOffres(): array
    {
        $query = $this->createQueryBuilder('o')
            ->where('o.statut = :statut')
            ->setParameter('statut', 'Active')
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery();

        // Debug la requête SQL
        dump($query->getSQL());
        dump($query->getParameters());

        return $query->getResult();
    }

    public function findOffreByKeywords(string $keywords, ?string $niveauExperience = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.poste LIKE :keywords OR o.description LIKE :keywords')
            ->andWhere('o.statut = :statut')
            ->setParameter('keywords', '%'.$keywords.'%')
            ->setParameter('statut', 'Active');

        if ($niveauExperience) {
            $qb->andWhere('o.niveauExperience = :niveauExperience')
                ->setParameter('niveauExperience', $niveauExperience);
        }

        return $qb->getQuery()->getResult();
    }
//    /**
//     * @return Offre[] Returns an array of Offre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Offre
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
