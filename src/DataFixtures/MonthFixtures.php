<?php

namespace App\DataFixtures;

use App\Entity\Month;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MonthFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
{
    $month = new \App\Entity\Month();
    $month->setNameMonth('TEST');
    $month->setMonthOrder(99);

    $manager->persist($month);
    $manager->flush();

    die('Fixture exécutée');
}
}