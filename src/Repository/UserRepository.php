<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Recette;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countAdministrators(): int
    {
        return (int) $this->createQueryBuilder('user')
            ->select('COUNT(user.id)')
            ->andWhere('user.role = :adminRole')
            ->setParameter('adminRole', UserRole::ADMINISTRATEUR)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<User>
     */
    public function findForAdmin(?string $search, ?UserRole $role, ?bool $isBlocked, int $offset, int $limit): array
    {
        return $this->createAdminListQueryBuilder($search, $role, $isBlocked)
            ->orderBy('user.createdAt', 'DESC')
            ->addOrderBy('user.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?string $search, ?UserRole $role, ?bool $isBlocked): int
    {
        return (int) $this->createAdminListQueryBuilder($search, $role, $isBlocked)
            ->select('COUNT(user.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countReportedContentForUser(User $user): int
    {
        $reportedRecipes = (int) $this->getEntityManager()
            ->getRepository(Recette::class)
            ->count(['user' => $user, 'signale' => true]);

        $reportedComments = (int) $this->getEntityManager()
            ->getRepository(Avis::class)
            ->count(['user' => $user, 'signale' => true]);

        return $reportedRecipes + $reportedComments;
    }

    private function createAdminListQueryBuilder(?string $search, ?UserRole $role, ?bool $isBlocked): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('user');

        if (null !== $role) {
            $queryBuilder
                ->andWhere('user.role = :role')
                ->setParameter('role', $role);
        }

        if (null !== $isBlocked) {
            $queryBuilder
                ->andWhere('user.isBlocked = :isBlocked')
                ->setParameter('isBlocked', $isBlocked);
        }

        if (null !== $search && '' !== trim($search)) {
            $normalizedSearch = '%'.mb_strtolower(trim($search)).'%';
            $queryBuilder
                ->andWhere('LOWER(user.prenom) LIKE :search OR LOWER(user.nom) LIKE :search OR LOWER(user.email) LIKE :search')
                ->setParameter('search', $normalizedSearch);
        }

        return $queryBuilder;
    }
}
