<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\Recette;
use App\Enum\RecetteTypePlat;
use App\Repository\RecetteRepository;

final class RecipeSuggestionService
{
    public function __construct(
        private readonly RecetteRepository $recetteRepository,
    ) {
    }

    /**
     * @param array{temperature?: int, temperatureMax?: int, condition?: string}|null $weather
     * @param list<Product> $seasonalProducts
     *
     * @return list<Recette>
     */
    public function getSuggestions(?array $weather, array $seasonalProducts, int $limit = 3): array
    {
        return $this->rankSuggestions(
            $this->recetteRepository->findPublishedForSuggestions(),
            $seasonalProducts,
            $weather,
            $limit,
        );
    }

    /**
     * @param list<Recette> $recipes
     * @param list<Product> $seasonalProducts
     * @param array{temperature?: int, temperatureMax?: int, condition?: string}|null $weather
     *
     * @return list<Recette>
     */
    public function rankSuggestions(array $recipes, array $seasonalProducts, ?array $weather, int $limit = 3): array
    {
        $seasonalProductIds = [];
        foreach ($seasonalProducts as $product) {
            if (null !== $product->getId()) {
                $seasonalProductIds[$product->getId()] = true;
            }
        }

        $temperature = $weather['temperature'] ?? null;
        $temperatureMax = $weather['temperatureMax'] ?? $temperature;
        $condition = $weather['condition'] ?? '';
        $coldOrWet = null !== $temperature
            && ($temperature < 10 || in_array($condition, ['Rain', 'Drizzle', 'Thunderstorm', 'Snow'], true));
        $hot = null !== $temperatureMax && $temperatureMax >= 25;

        $scoredRecipes = [];
        foreach ($recipes as $recipe) {
            $score = 0;
            foreach ($recipe->getProducts() as $product) {
                if (null !== $product->getId() && isset($seasonalProductIds[$product->getId()])) {
                    $score += 5;
                }
            }

            $searchableText = mb_strtolower($recipe->getTitre().' '.$recipe->getIngredient());
            if ($coldOrWet) {
                $score += $recipe->getTypePlat() === RecetteTypePlat::PLAT ? 4 : 2;
                $score += $recipe->isOven() ? 2 : 0;
                $score += $this->containsAny($searchableText, ['soupe', 'velouté', 'mijot', 'rôti', 'gratin']) ? 6 : 0;
            } elseif ($hot) {
                $score += in_array($recipe->getTypePlat(), [RecetteTypePlat::ENTREE, RecetteTypePlat::DESSERT], true) ? 4 : 1;
                $score += !$recipe->isOven() ? 2 : 0;
                $score += $this->containsAny($searchableText, ['salade', 'frais', 'melon', 'pêche']) ? 6 : 0;
            } else {
                $score += $recipe->getTypePlat() === RecetteTypePlat::PLAT ? 4 : 2;
                $score += $this->containsAny(
                    $searchableText,
                    ['tarte', 'poisson', 'papillote', 'rôti', 'quiche', 'gratin'],
                ) ? 5 : 0;
            }

            $scoredRecipes[] = ['recipe' => $recipe, 'score' => $score];
        }

        usort($scoredRecipes, static function (array $first, array $second): int {
            return $second['score'] <=> $first['score']
                ?: strcasecmp($first['recipe']->getTitre(), $second['recipe']->getTitre());
        });

        return array_values(array_map(
            static fn (array $item): Recette => $item['recipe'],
            array_slice($scoredRecipes, 0, max(0, $limit)),
        ));
    }

    /**
     * @param list<string> $terms
     */
    private function containsAny(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($text, $term)) {
                return true;
            }
        }

        return false;
    }
}
