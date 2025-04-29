<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

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
        return $this->createQueryBuilder('o')
            ->where('o.statut = :statut')
            ->andWhere('o.dateExpiration IS NULL OR o.dateExpiration >= :now')
            ->setParameter('statut', 'Active')
            ->setParameter('now', new \DateTime())
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
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
    public function updateExpiredOffresToBrouillon(): void
    {
        $qb = $this->createQueryBuilder('o')
            ->update()
            ->set('o.statut', ':brouillon')
            ->where('o.dateExpiration IS NOT NULL')
            ->andWhere('o.dateExpiration < :now')
            ->andWhere('o.statut = :active')
            ->setParameter('brouillon', 'Brouillon')
            ->setParameter('now', new \DateTime())
            ->setParameter('active', 'Active')
            ->getQuery();

        $qb->execute();
    }
    /**
     * Recherche multicritères pour les offres actives
     *
     * @param string|null $keyword
     * @param array $modeTravail
     * @param string|null $typeContrat
     * @param array $experience
     * @return Offre[]
     */

    public function findByFilters(?string $keyword, array $modeTravail, ?string $typeContrat, array $experience, string $sortBy = 'none'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.statut = :statut')
            ->andWhere('o.dateExpiration >= :today OR o.dateExpiration IS NULL')
            ->setParameter('statut', 'Active')
            ->setParameter('today', new \DateTime());

        if (!empty($keyword)) {
            $qb->andWhere('o.poste LIKE :keyword OR o.description LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%');
        }

        if (!empty($modeTravail)) {
            $qb->andWhere('o.modeTravail IN (:modeTravail)')
                ->setParameter('modeTravail', $modeTravail);
        }

        if (!empty($typeContrat)) {
            $qb->andWhere('o.typeContrat = :typeContrat')
                ->setParameter('typeContrat', $typeContrat);
        }

        if (!empty($experience)) {
            $qb->andWhere('o.niveauExperience IN (:experience)')
                ->setParameter('experience', $experience);
        }

        if ($sortBy === 'date') {
            $qb->orderBy('o.dateCreation', 'DESC');
        } elseif ($sortBy === 'title') {
            $qb->orderBy('o.poste', 'ASC');
        }

        return $qb;
    }
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
