<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture
{
    public const RECIPE_AUTHOR_REFERENCE = 'user_recipe_author';
    public const RECIPE_AUTHOR_EMAIL = 'cuisine.fixture@aufildessaisons.test';

    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email' => self::RECIPE_AUTHOR_EMAIL]);

        if (!$user instanceof User) {
            $user = (new User())
                ->setEmail(self::RECIPE_AUTHOR_EMAIL)
                ->setPassword(password_hash('fixture-recettes', PASSWORD_BCRYPT));
            $manager->persist($user);
        }

        $user
            ->setPrenom('Camille')
            ->setNom('Cuisine de saison')
            ->setRole(UserRole::UTILISATEUR)
            ->setIsBlocked(false);

        $this->addReference(self::RECIPE_AUTHOR_REFERENCE, $user);
        $manager->flush();
    }
}
