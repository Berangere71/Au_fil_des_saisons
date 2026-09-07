<?php

namespace App\Repository;

use App\Entity\Recette;
use App\Enum\RecetteStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }

    /**
     * @return list<Recette>
     */
    public function findPublishedForSuggestions(): array
    {
        return $this->createQueryBuilder('recette')
            ->addSelect('product')
            ->leftJoin('recette.products', 'product')
            ->andWhere('recette.statut = :status')
            ->andWhere('recette.isPublic = :isPublic')
            ->setParameter('status', RecetteStatut::PUBLIEE)
            ->setParameter('isPublic', true)
            ->orderBy('recette.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Recette> */
    public function findPublishedForIndex(): array
    {
        return $this->createQueryBuilder('recette')
            ->addSelect('product', 'author')
            ->leftJoin('recette.products', 'product')
            ->innerJoin('recette.user', 'author')
            ->andWhere('recette.statut = :status')
            ->andWhere('recette.isPublic = :public')
            ->setParameter('status', RecetteStatut::PUBLIEE)
            ->setParameter('public', true)
            ->orderBy('recette.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    /** @return list<Recette> */
    public function findAllWithReviews(): array
    {
        return $this->createQueryBuilder('recette')
            ->addSelect('review', 'reviewer')
            ->leftJoin('recette.avis', 'review')
            ->leftJoin('review.user', 'reviewer')
            ->orderBy('recette.titre', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return list<Recette> */
    public function findReportedWithAuthor(): array
    {
        return $this->createQueryBuilder('recette')
            ->addSelect('author')
            ->innerJoin('recette.user', 'author')
            ->andWhere('recette.signale = :reported')
            ->setParameter('reported', true)
            ->orderBy('recette.createdAt', 'DESC')
            ->getQuery()->getResult();
    }
}
