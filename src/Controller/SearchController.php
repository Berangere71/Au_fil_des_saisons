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
            $keywords = array_slice(array_values(array_filter(
                preg_split('/[\s,;]+/u', mb_strtolower($query)) ?: [],
                static fn (string $keyword): bool => mb_strlen($keyword) >= 2,
            )), 0, 8);

            if ([] !== $keywords && 'recipes' !== $type) {
                $productQuery = $entityManager->getRepository(Product::class)->createQueryBuilder('product');
                $productQuery
                    ->addSelect('startMonth', 'endMonth')
                    ->innerJoin('product.debutRecolteMois', 'startMonth')
                    ->innerJoin('product.finRecolteMois', 'endMonth');
                foreach ($keywords as $index => $keyword) {
                    $parameter = 'productTerm'.$index;
                    $productQuery
                        ->andWhere(sprintf(
                            '(LOWER(product.nom) LIKE :%1$s OR LOWER(product.description) LIKE :%1$s OR LOWER(product.conservation) LIKE :%1$s)',
                            $parameter,
                        ))
                        ->setParameter($parameter, '%'.$keyword.'%');
                }
                $products = $productQuery->orderBy('product.nom', 'ASC')->setMaxResults(20)->getQuery()->getResult();
            }

            if ([] !== $keywords && 'products' !== $type) {
                $recipeQuery = $entityManager->getRepository(Recette::class)->createQueryBuilder('recipe')
                    ->addSelect('recipeProduct')
                    ->leftJoin('recipe.products', 'recipeProduct')
                    ->distinct()
                    ->andWhere('recipe.statut = :published')
                    ->andWhere('recipe.isPublic = :public')
                    ->setParameter('published', RecetteStatut::PUBLIEE)
                    ->setParameter('public', true);
                foreach ($keywords as $index => $keyword) {
                    $parameter = 'recipeTerm'.$index;
                    $recipeQuery
                        ->andWhere(sprintf(
                            '(LOWER(recipe.titre) LIKE :%1$s OR LOWER(recipe.ingredient) LIKE :%1$s OR LOWER(recipe.preparation) LIKE :%1$s OR LOWER(recipeProduct.nom) LIKE :%1$s)',
                            $parameter,
                        ))
                        ->setParameter($parameter, '%'.$keyword.'%');
                }
                $recipes = $recipeQuery->orderBy('recipe.titre', 'ASC')->setMaxResults(20)->getQuery()->getResult();
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
