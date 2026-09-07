<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthenticationTest extends CrudWebTestCase
{
    public function testVisitorCanRegister(): void
    {
        $crawler = $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('S’inscrire')->form([
            'registration_form[prenom]' => 'Alice',
            'registration_form[nom]' => 'Martin',
            'registration_form[email]' => 'alice@example.test',
            'registration_form[plainPassword][first]' => 'MotDePasse123!',
            'registration_form[plainPassword][second]' => 'MotDePasse123!',
            'registration_form[agreeTerms]' => '1',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/login');
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'alice@example.test']);
        self::assertNotNull($user);
        self::assertSame(UserRole::UTILISATEUR, $user->getRole());
        self::assertTrue(
            static::getContainer()->get(UserPasswordHasherInterface::class)
                ->isPasswordValid($user, 'MotDePasse123!'),
        );
    }

    public function testUserCanLogInAndLogOut(): void
    {
        $user = $this->createUser('connexion@example.test', 'Secret123!');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $user->getEmail(),
            '_password' => 'Secret123!',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/login/redirect');
        $this->client->followRedirect();
        self::assertResponseRedirects('/profile');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/logout"]');

        $this->client->request('GET', '/logout');
        self::assertResponseRedirects('/');

        $this->client->request('GET', '/profile');
        self::assertResponseRedirects('http://localhost/login');
    }

    public function testLoginFailsWithInvalidPassword(): void
    {
        $this->createUser('erreur@example.test', 'BonMotDePasse123!');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'erreur@example.test',
            '_password' => 'MauvaisMotDePasse',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger');
        self::assertInputValueSame('_username', 'erreur@example.test');
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $user = (new User())
            ->setPrenom('Jean')
            ->setNom('Dupont')
            ->setEmail($email)
            ->setRole(UserRole::UTILISATEUR);
        $user->setPassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)
                ->hashPassword($user, $plainPassword),
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
