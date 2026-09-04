<?php

namespace App\Tests\Service;

use App\Entity\Recette;
use App\Enum\RecetteTypePlat;
use App\Repository\RecetteRepository;
use App\Service\RecipeSuggestionService;
use PHPUnit\Framework\TestCase;

final class RecipeSuggestionServiceTest extends TestCase
{
    public function testHotWeatherPrioritizesAFreshRecipe(): void
    {
        $salad = (new Recette())
            ->setTitre('Salade de melon')
            ->setIngredient('Melon')
            ->setTypePlat(RecetteTypePlat::ENTREE)
            ->setIsOven(false);
        $stew = (new Recette())
            ->setTitre('Plat mijoté')
            ->setIngredient('Carotte')
            ->setTypePlat(RecetteTypePlat::PLAT)
            ->setIsOven(true);

        $repository = $this->createStub(RecetteRepository::class);
        $repository->method('findPublishedForSuggestions')->willReturn([$stew, $salad]);

        $suggestions = (new RecipeSuggestionService($repository))
            ->getSuggestions(['temperature' => 29, 'condition' => 'Clear'], []);

        self::assertSame($salad, $suggestions[0]);
    }

    public function testColdRainyWeatherPrioritizesAWarmingRecipe(): void
    {
        $salad = (new Recette())
            ->setTitre('Salade de melon')
            ->setIngredient('Melon')
            ->setTypePlat(RecetteTypePlat::ENTREE);
        $soup = (new Recette())
            ->setTitre('Soupe de poireaux')
            ->setIngredient('Poireau')
            ->setTypePlat(RecetteTypePlat::PLAT);

        $repository = $this->createStub(RecetteRepository::class);
        $repository->method('findPublishedForSuggestions')->willReturn([$salad, $soup]);

        $suggestions = (new RecipeSuggestionService($repository))
            ->getSuggestions(['temperature' => 7, 'condition' => 'Rain'], []);

        self::assertSame($soup, $suggestions[0]);
    }

    public function testDailyMaximumBelowTwentyFivePrioritizesAMildWeatherRecipe(): void
    {
        $salad = (new Recette())
            ->setTitre('Salade fraîche')
            ->setIngredient('Concombre')
            ->setTypePlat(RecetteTypePlat::ENTREE);
        $fish = (new Recette())
            ->setTitre('Poisson en papillote')
            ->setIngredient('Poisson')
            ->setTypePlat(RecetteTypePlat::PLAT);

        $repository = $this->createStub(RecetteRepository::class);
        $repository->method('findPublishedForSuggestions')->willReturn([$salad, $fish]);

        $suggestions = (new RecipeSuggestionService($repository))
            ->getSuggestions([
                'temperature' => 19,
                'temperatureMax' => 24,
                'condition' => 'Clouds',
            ], []);

        self::assertSame($fish, $suggestions[0]);
    }
}
