<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /** @param list<int> $excludedIds
     *  @return list<Avis>
     */
    public function findForModeration(array $excludedIds): array
    {
        $query = $this->createQueryBuilder('review')
            ->addSelect('author', 'recipe', 'parent')
            ->innerJoin('review.user', 'author')
            ->innerJoin('review.recette', 'recipe')
            ->leftJoin('review.parentAvis', 'parent')
            ->orderBy('review.createdAt', 'DESC');

        if ([] !== $excludedIds) {
            $query->andWhere('review.id NOT IN (:excludedIds)')
                ->setParameter('excludedIds', $excludedIds);
        }

        return $query->getQuery()->getResult();
    }
}
