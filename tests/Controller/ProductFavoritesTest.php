<?php

namespace App\Tests\Controller;

use App\Entity\Favoris;
use App\Entity\Month;
use App\Entity\Product;
use App\Entity\Recette;
use App\Entity\User;
use App\Enum\ProductCategory;
use App\Enum\RecetteStatut;
use App\Enum\UserRole;

final class ProductFavoritesTest extends CrudWebTestCase
{
    public function testUserCanSaveProductRecipesAsFavorites(): void
    {
        $month = (new Month())->setNameMonth('Septembre')->setMonthOrder(9);
        $user = (new User())->setPrenom('Mars')->setNom('Attack')->setEmail('mars@example.test')
            ->setPassword('unused')->setRole(UserRole::UTILISATEUR);
        $product = (new Product())->setNom('Pomme')->setCategory(ProductCategory::FRUIT)
            ->setConservation('Au frais')->setDebutRecolteMois($month)->setFinRecolteMois($month);
        $recipe = (new Recette())->setTitre('Pommes rôties aux noisettes')->setUser($user)
            ->setIngredient('Pommes et noisettes')->setPreparation('Faire rôtir.')
            ->setIsPublic(true)->setStatut(RecetteStatut::PUBLIEE)->addProduct($product);
        foreach ([$month, $user, $product, $recipe] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/products/'.$product->getId());
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('.product-favorites-form label input[type="checkbox"]'));
        self::assertCount(0, $crawler->filter('.product-favorites-form label span'));
        $form = $crawler->selectButton('Enregistrer dans mes favoris')->form([
            'recipe_ids' => [(string) $recipe->getId()],
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/profile');
        self::assertNotNull($this->entityManager->getRepository(Favoris::class)->findOneBy([
            'user' => $user,
            'recette' => $recipe,
        ]));
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#community-recipes-title', 'Mes recettes favorites');
        self::assertSelectorTextContains('.profile-favorite-recipe h3', 'Pommes rôties aux noisettes');
    }
}
