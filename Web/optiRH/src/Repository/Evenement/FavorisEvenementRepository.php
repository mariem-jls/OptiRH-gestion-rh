<?php

namespace App\Repository\Evenement;

use App\Entity\Evenement\FavorisEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FavorisEvenement>
 */
class FavorisEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavorisEvenement::class);
    }

    // src/Repository/FavorisEvenementRepository.php

public function findFavorisEventIdsByUser($user): array
{
    $result = $this->createQueryBuilder('f')
        ->select('IDENTITY(f.idEvenement) AS id')
        ->where('f.idUser = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();

    return array_column($result, 'id'); // Résultat final : [3, 5, 7]
}


    //    /**
    //     * @return FavorisEvenement[] Returns an array of FavorisEvenement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FavorisEvenement
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
