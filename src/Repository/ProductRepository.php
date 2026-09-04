<?php

namespace App\Repository;

use App\Entity\Product;
use App\Enum\ProductCategory;
use App\Enum\SeasonName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return list<Product>
     */
    public function findInSeasonForMonth(int $month): array
    {
        return $this->createQueryBuilder('product')
            ->addSelect('startMonth', 'endMonth')
            ->innerJoin('product.debutRecolteMois', 'startMonth')
            ->innerJoin('product.finRecolteMois', 'endMonth')
            ->andWhere(
                '(startMonth.monthOrder <= endMonth.monthOrder AND startMonth.monthOrder <= :month AND endMonth.monthOrder >= :month)
                OR (startMonth.monthOrder > endMonth.monthOrder AND (startMonth.monthOrder <= :month OR endMonth.monthOrder >= :month))'
            )
            ->setParameter('month', $month)
            ->orderBy('product.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Product>
     */
    public function findByFilters(?ProductCategory $category, ?SeasonName $season, ?string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('product')
            ->orderBy('product.nom', 'ASC');

        if (null !== $category) {
            $queryBuilder
                ->andWhere('product.category = :category')
                ->setParameter('category', $category);
        }

        if (null !== $season) {
            $queryBuilder->innerJoin('product.debutRecolteMois', 'startMonth');
            $queryBuilder->innerJoin('product.finRecolteMois', 'endMonth');

            $seasonMonths = $season->months();

            $queryBuilder
                ->andWhere('startMonth.monthOrder IN (:seasonMonths) OR (MOD(endMonth.monthOrder - startMonth.monthOrder + 12, 12) + 1) = 12')
                ->setParameter('seasonMonths', $seasonMonths);
        }

        if (null !== $search && '' !== trim($search)) {
            $queryBuilder
                ->andWhere('LOWER(product.nom) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower(trim($search)).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
