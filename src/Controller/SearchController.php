<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Recette;
use App\Enum\RecetteStatut;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/recherche', name: 'app_search', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = mb_substr(trim((string) $request->query->get('q')), 0, 100);
        $type = (string) $request->query->get('type', 'all');
        $type = in_array($type, ['all', 'products', 'recipes'], true) ? $type : 'all';
        $products = [];
        $recipes = [];

        if (mb_strlen($query) >= 2) {
            $term = '%'.mb_strtolower($query).'%';

            if ('recipes' !== $type) {
                $products = $entityManager->getRepository(Product::class)->createQueryBuilder('product')
                    ->andWhere('LOWER(product.nom) LIKE :term OR LOWER(product.description) LIKE :term')
                    ->setParameter('term', $term)
                    ->orderBy('product.nom', 'ASC')
                    ->setMaxResults(20)
                    ->getQuery()
                    ->getResult();
            }

            if ('products' !== $type) {
                $recipes = $entityManager->getRepository(Recette::class)->createQueryBuilder('recipe')
                    ->andWhere('recipe.statut = :published')
                    ->andWhere('recipe.isPublic = :public')
                    ->andWhere('LOWER(recipe.titre) LIKE :term OR LOWER(recipe.ingredient) LIKE :term')
                    ->setParameter('published', RecetteStatut::PUBLIEE)
                    ->setParameter('public', true)
                    ->setParameter('term', $term)
                    ->orderBy('recipe.titre', 'ASC')
                    ->setMaxResults(20)
                    ->getQuery()
                    ->getResult();
            }
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'type' => $type,
            'products' => $products,
            'recipes' => $recipes,
        ]);
    }
}
