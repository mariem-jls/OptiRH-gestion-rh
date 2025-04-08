<?php

namespace App\Repository\GsProjet;

use App\Entity\GsProjet\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setMotDePasse($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByEmailOrName(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :query OR u.nom LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->getQuery()
            ->getResult();
    }

    public function countByRole(string $role): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentlyRegistered(int $maxResults = 5): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function paginationQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');
    }
}