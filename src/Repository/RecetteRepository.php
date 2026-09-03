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
}
