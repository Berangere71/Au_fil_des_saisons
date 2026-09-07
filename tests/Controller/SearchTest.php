<?php

namespace App\Tests\Controller;

use App\Entity\Month;
use App\Entity\Product;
use App\Entity\Recette;
use App\Entity\User;
use App\Enum\ProductCategory;
use App\Enum\RecetteStatut;
use App\Enum\UserRole;

final class SearchTest extends CrudWebTestCase
{
    public function testFooterSearchLinkOpensSearchPage(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSame('/recherche', $crawler->filter('.footer a')->last()->attr('href'));
        self::assertSame('Rechercher', $crawler->filter('.footer a')->last()->filter('img')->attr('alt'));
    }

    public function testSearchReturnsMatchingProduct(): void
    {
        $month = (new Month())->setNameMonth('Avril')->setMonthOrder(4);
        $product = (new Product())
            ->setNom('Asperge verte')
            ->setCategory(ProductCategory::LEGUME)
            ->setDescription('Légume de printemps')
            ->setConservation('Au réfrigérateur')
            ->setDebutRecolteMois($month)
            ->setFinRecolteMois($month);
        $this->entityManager->persist($month);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('GET', '/recherche?q=asperge&type=products');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.search-page__summary', '1 résultat');
        self::assertSelectorTextContains('.search-result-card strong', 'Asperge verte');
    }

    public function testSearchReturnsRecipeUsingSeveralKeywords(): void
    {
        $month = (new Month())->setNameMonth('Mai')->setMonthOrder(5);
        $product = (new Product())->setNom('Fraise')->setCategory(ProductCategory::FRUIT)
            ->setConservation('Au frais')->setDebutRecolteMois($month)->setFinRecolteMois($month);
        $user = (new User())->setPrenom('Julie')->setNom('Cuisine')->setEmail('julie@example.test')
            ->setPassword('unused')->setRole(UserRole::UTILISATEUR);
        $recipe = (new Recette())->setTitre('Tarte printanière')->setIngredient('Farine, beurre et fruits')
            ->setPreparation('Garnir la pâte puis cuire.')->setUser($user)->setIsPublic(true)
            ->setStatut(RecetteStatut::PUBLIEE)->addProduct($product);
        $this->entityManager->persist($month);
        $this->entityManager->persist($product);
        $this->entityManager->persist($user);
        $this->entityManager->persist($recipe);
        $this->entityManager->flush();

        $this->client->request('GET', '/recherche?q=tarte+fraise&type=all');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#search-recipes-title', 'Recettes 1');
        self::assertSelectorTextContains('.search-result-card strong', 'Tarte printanière');
    }
}
