<?php

namespace App\DataFixtures;

use App\Entity\Season;
use App\Enum\SeasonName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SeasonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $seasons = [
            SeasonName::PRINTEMPS,
            SeasonName::ETE,
            SeasonName::AUTOMNE,
            SeasonName::HIVER,
        ];

        foreach ($seasons as $seasonName) {

            $season = new Season();
            $season->setNameSeason($seasonName);

            $manager->persist($season);
        }

        $manager->flush();
    }
}
