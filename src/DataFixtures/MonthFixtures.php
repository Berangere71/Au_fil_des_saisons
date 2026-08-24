<?php

namespace App\DataFixtures;

use App\Entity\Month;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class MonthFixtures extends Fixture
{
    public const REFERENCE_PREFIX = 'month_';

    public function load(ObjectManager $manager): void
    {
        $months = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];

        foreach ($months as $order => $name) {
            $month = $manager->getRepository(Month::class)->findOneBy(['monthOrder' => $order]);

            if (!$month instanceof Month) {
                $month = new Month();
                $manager->persist($month);
            }

            $month->setNameMonth($name)->setMonthOrder($order);
            $this->addReference(self::REFERENCE_PREFIX.$order, $month);
        }

        $manager->flush();
    }
}
