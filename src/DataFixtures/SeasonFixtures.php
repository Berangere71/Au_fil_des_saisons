<?php

namespace App\DataFixtures;

use App\Entity\Season;
use App\Enum\SeasonName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class SeasonFixtures extends Fixture
{
    public const REFERENCE_PREFIX = 'season_';

    public function load(ObjectManager $manager): void
    {
        $seasons = [
            SeasonName::PRINTEMPS,
            SeasonName::ETE,
            SeasonName::AUTOMNE,
            SeasonName::HIVER,
        ];

        foreach ($seasons as $seasonName) {
            $season = $manager->getRepository(Season::class)->findOneBy(['nameSeason' => $seasonName]);

            if (!$season instanceof Season) {
                $season = (new Season())->setNameSeason($seasonName);
                $manager->persist($season);
            }

            $this->addReference(self::REFERENCE_PREFIX.$seasonName->value, $season);
        }

        $manager->flush();
    }
}
