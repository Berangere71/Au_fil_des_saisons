<?php

namespace App\Tests\Controller;

use App\Entity\Month;
use App\Entity\Product;
use App\Entity\Recette;
use App\Entity\User;
use App\Enum\ProductCategory;
use App\Enum\RecetteStatut;
use App\Enum\UserRole;

final class RecetteCrudTest extends CrudWebTestCase
{
    public function testOwnerCanCreateReadUpdateAndDeleteRecipe(): void
    {
        $month = (new Month())->setNameMonth('Mars')->setMonthOrder(3);
        $user = (new User())->setPrenom('Rémi')->setNom('Cuisine')->setEmail('remi@example.test')
            ->setPassword('unused')->setRole(UserRole::UTILISATEUR);
        $product = (new Product())->setNom('Carotte test')->setCategory(ProductCategory::LEGUME)
            ->setConservation('Au frais')->setDebutRecolteMois($month)->setFinRecolteMois($month);
        $this->entityManager->persist($month);
        $this->entityManager->persist($user);
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/recettes/nouvelle');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Enregistrer')->form([
            'recette[titre]' => 'Velouté de test',
            'recette[typePlat]' => '1',
            'recette[nbrPerson]' => '4',
            'recette[timePrepa]' => '20',
            'recette[ingredient]' => '4 carottes',
            'recette[preparation]' => 'Mixer les carottes.',
            'recette[products]' => [(string) $product->getId()],
            'recette[isPublic]' => '1',
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/recettes');

        $recipe = $this->entityManager->getRepository(Recette::class)->findOneBy(['titre' => 'Velouté de test']);
        self::assertNotNull($recipe);
        self::assertSame(RecetteStatut::PUBLIEE, $recipe->getStatut());

        $this->client->request('GET', '/recettes/'.$recipe->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.recette-detail__header h1', 'Velouté de test');

        $this->client->request('GET', '/recettes/'.$recipe->getId().'?from=home');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.page-close-button[href="/"]');

        $this->client->request('GET', '/recettes/'.$recipe->getId().'?from=recipes');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.page-close-button[href="/recettes"]');
        self::assertSelectorExists(sprintf(
            'a[href="/products/%d?from=recipe&recipe=%d&recipe_from=recipes"]',
            $product->getId(),
            $recipe->getId(),
        ));

        $this->client->request('GET', sprintf(
            '/products/%d?from=recipe&recipe=%d&recipe_from=recipes',
            $product->getId(),
            $recipe->getId(),
        ));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf(
            '.product-close[href="/recettes/%d?from=recipes"]',
            $recipe->getId(),
        ));

        $crawler = $this->client->request('GET', '/recettes/'.$recipe->getId().'/modifier');
        $form = $crawler->selectButton('Enregistrer')->form([
            'recette[titre]' => 'Velouté modifié',
            'recette[typePlat]' => '0',
            'recette[nbrPerson]' => '2',
            'recette[timePrepa]' => '15',
            'recette[ingredient]' => '2 carottes',
            'recette[preparation]' => 'Cuire puis mixer.',
            'recette[products]' => [(string) $product->getId()],
            'recette[isPublic]' => '1',
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/recettes');
        $this->entityManager->refresh($recipe);
        self::assertSame('Velouté modifié', $recipe->getTitre());

        $recipeId = $recipe->getId();
        $token = $this->csrfToken('delete-recette-'.$recipeId);
        $this->client->request('POST', '/recettes/'.$recipe->getId().'/supprimer', ['_token' => $token]);
        self::assertResponseRedirects('/recettes');
        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Recette::class)->find($recipeId));
    }
}
