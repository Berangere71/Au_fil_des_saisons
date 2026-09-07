<?php

namespace App\Tests\Controller;

use App\Entity\Month;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\ProductCategory;
use App\Enum\UserRole;

final class ProductCrudTest extends CrudWebTestCase
{
    public function testAdminCanCreateReadUpdateAndDeleteProduct(): void
    {
        $january = (new Month())->setNameMonth('Janvier')->setMonthOrder(1);
        $february = (new Month())->setNameMonth('Février')->setMonthOrder(2);
        $admin = (new User())
            ->setPrenom('Ada')
            ->setNom('Admin')
            ->setEmail('admin@example.test')
            ->setPassword('unused')
            ->setRole(UserRole::ADMINISTRATEUR);
        $this->entityManager->persist($january);
        $this->entityManager->persist($february);
        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/products/new');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Enregistrer')->form([
            'product[nom]' => 'Topinambour test',
            'product[category]' => ProductCategory::LEGUME->value,
            'product[description]' => 'Un produit créé par le test.',
            'product[conservation]' => 'Conserver au frais.',
            'product[debutRecolteMois]' => (string) $january->getId(),
            'product[finRecolteMois]' => (string) $february->getId(),
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/products');

        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['nom' => 'Topinambour test']);
        self::assertNotNull($product);

        $this->client->request('GET', '/products/'.$product->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Topinambour test');

        $this->client->request('GET', '/products/'.$product->getId().'?from=home');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.product-close[href="/"]');

        $crawler = $this->client->request('GET', '/products/'.$product->getId().'/edit');
        $form = $crawler->selectButton('Enregistrer')->form([
            'product[nom]' => 'Topinambour modifié',
            'product[category]' => ProductCategory::LEGUME->value,
            'product[description]' => 'Description mise à jour.',
            'product[conservation]' => 'Toujours au frais.',
            'product[debutRecolteMois]' => (string) $january->getId(),
            'product[finRecolteMois]' => (string) $february->getId(),
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/products');
        $this->entityManager->refresh($product);
        self::assertSame('Topinambour modifié', $product->getNom());

        $productId = $product->getId();
        $crawler = $this->client->request('GET', '/products');
        $deleteForm = $crawler->filter(sprintf('form[action="/products/%d"]', $product->getId()))->form();
        $this->client->submit($deleteForm);
        self::assertResponseRedirects('/products');
        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Product::class)->find($productId));
    }

    public function testRegularUserCannotManageProducts(): void
    {
        $user = (new User())->setPrenom('Ursula')->setNom('User')->setEmail('user@example.test')
            ->setPassword('unused')->setRole(UserRole::UTILISATEUR);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->client->loginUser($user);

        $this->client->request('GET', '/products/new');
        self::assertResponseStatusCodeSame(403);
    }
}
