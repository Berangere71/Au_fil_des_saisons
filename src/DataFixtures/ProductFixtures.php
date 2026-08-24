<?php

namespace App\DataFixtures;

use App\Entity\Month;
use App\Entity\Product;
use App\Entity\Season;
use App\Enum\ProductCategory;
use App\Enum\SeasonName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFERENCE_PREFIX = 'product_';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getProducts() as [$name, $category, $startMonth, $endMonth, $conservation]) {
            $product = $manager->getRepository(Product::class)->findOneBy(['nom' => $name]);

            if (!$product instanceof Product) {
                $product = (new Product())->setNom($name);
                $manager->persist($product);
            }

            $product
                ->setCategory($category)
                ->setDebutRecolteMois($this->getReference(MonthFixtures::REFERENCE_PREFIX.$startMonth, Month::class))
                ->setFinRecolteMois($this->getReference(MonthFixtures::REFERENCE_PREFIX.$endMonth, Month::class))
                ->setConservation($conservation)
                ->setDescription(sprintf(
                    '%s est un produit de saison disponible de %s à %s. Il peut être utilisé dans de nombreuses recettes.',
                    $name,
                    $this->getReference(MonthFixtures::REFERENCE_PREFIX.$startMonth, Month::class)->getNameMonth(),
                    $this->getReference(MonthFixtures::REFERENCE_PREFIX.$endMonth, Month::class)->getNameMonth(),
                ));

            $fixtureImage = $this->copyFixtureImage($name);
            if (null !== $fixtureImage) {
                $product->setPhoto($fixtureImage);
            }

            foreach ($this->getSeasonsForRange($startMonth, $endMonth) as $seasonName) {
                $product->addSeason($this->getReference(SeasonFixtures::REFERENCE_PREFIX.$seasonName->value, Season::class));
            }

            $this->addReference(self::REFERENCE_PREFIX.$name, $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [MonthFixtures::class, SeasonFixtures::class];
    }

    /**
     * @return list<array{string, ProductCategory, int, int, string}>
     */
    private function getProducts(): array
    {
        $fresh = 'À conserver au réfrigérateur et à consommer rapidement.';
        $cool = 'À conserver dans un endroit frais, sec et à l’abri de la lumière.';
        $freeze = 'À conserver au réfrigérateur ou à congeler le jour de l’achat.';

        return [
            // 25 fruits
            ['Abricot', ProductCategory::FRUIT, 6, 8, $fresh],
            ['Airelle', ProductCategory::FRUIT, 8, 10, $fresh],
            ['Cassis', ProductCategory::FRUIT, 6, 8, $fresh],
            ['Cerise', ProductCategory::FRUIT, 5, 7, $fresh],
            ['Châtaigne', ProductCategory::FRUIT, 9, 11, $cool],
            ['Citron', ProductCategory::FRUIT, 11, 3, $cool],
            ['Clémentine', ProductCategory::FRUIT, 10, 2, $cool],
            ['Coing', ProductCategory::FRUIT, 9, 11, $cool],
            ['Figue', ProductCategory::FRUIT, 7, 10, $fresh],
            ['Fraise', ProductCategory::FRUIT, 4, 7, $fresh],
            ['Framboise', ProductCategory::FRUIT, 6, 9, $fresh],
            ['Groseille', ProductCategory::FRUIT, 6, 8, $fresh],
            ['Kaki', ProductCategory::FRUIT, 10, 1, $cool],
            ['Kiwi', ProductCategory::FRUIT, 11, 5, $cool],
            ['Melon', ProductCategory::FRUIT, 6, 9, $fresh],
            ['Mirabelle', ProductCategory::FRUIT, 8, 9, $fresh],
            ['Mûre', ProductCategory::FRUIT, 7, 9, $fresh],
            ['Myrtille', ProductCategory::FRUIT, 6, 10, $fresh],
            ['Nectarine', ProductCategory::FRUIT, 6, 9, $fresh],
            ['Noisette', ProductCategory::FRUIT, 9, 11, $cool],
            ['Noix', ProductCategory::FRUIT, 9, 11, $cool],
            ['Pêche', ProductCategory::FRUIT, 6, 9, $fresh],
            ['Poire', ProductCategory::FRUIT, 7, 3, $cool],
            ['Pomme', ProductCategory::FRUIT, 8, 4, $cool],
            ['Prune', ProductCategory::FRUIT, 7, 10, $fresh],

            // 25 légumes
            ['Artichaut', ProductCategory::LEGUME, 4, 9, $fresh],
            ['Asperge', ProductCategory::LEGUME, 3, 6, $fresh],
            ['Aubergine', ProductCategory::LEGUME, 6, 10, $fresh],
            ['Betterave', ProductCategory::LEGUME, 5, 3, $cool],
            ['Blette', ProductCategory::LEGUME, 4, 11, $fresh],
            ['Brocoli', ProductCategory::LEGUME, 6, 11, $fresh],
            ['Carotte', ProductCategory::LEGUME, 1, 12, $cool],
            ['Céleri branche', ProductCategory::LEGUME, 7, 1, $fresh],
            ['Céleri-rave', ProductCategory::LEGUME, 9, 3, $cool],
            ['Chou blanc', ProductCategory::LEGUME, 9, 3, $fresh],
            ['Chou-fleur', ProductCategory::LEGUME, 9, 5, $fresh],
            ['Chou rouge', ProductCategory::LEGUME, 9, 3, $fresh],
            ['Concombre', ProductCategory::LEGUME, 5, 9, $fresh],
            ['Courge butternut', ProductCategory::LEGUME, 9, 2, $cool],
            ['Courgette', ProductCategory::LEGUME, 5, 10, $fresh],
            ['Épinard', ProductCategory::LEGUME, 3, 6, $fresh],
            ['Fenouil', ProductCategory::LEGUME, 5, 12, $fresh],
            ['Haricot vert', ProductCategory::LEGUME, 6, 10, $fresh],
            ['Navet', ProductCategory::LEGUME, 9, 5, $cool],
            ['Oignon', ProductCategory::LEGUME, 7, 4, $cool],
            ['Petit pois', ProductCategory::LEGUME, 4, 7, $fresh],
            ['Poireau', ProductCategory::LEGUME, 9, 4, $fresh],
            ['Poivron', ProductCategory::LEGUME, 6, 10, $fresh],
            ['Potimarron', ProductCategory::LEGUME, 9, 2, $cool],
            ['Tomate', ProductCategory::LEGUME, 5, 10, $fresh],

            // 25 viandes et volailles
            ['Agneau', ProductCategory::VIANDE, 3, 6, $freeze],
            ['Bœuf', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Canard', ProductCategory::VIANDE, 9, 3, $freeze],
            ['Canette', ProductCategory::VIANDE, 10, 2, $freeze],
            ['Chapon', ProductCategory::VIANDE, 11, 1, $freeze],
            ['Chevreau', ProductCategory::VIANDE, 3, 6, $freeze],
            ['Côte de bœuf', ProductCategory::VIANDE, 5, 9, $freeze],
            ['Dinde', ProductCategory::VIANDE, 9, 1, $freeze],
            ['Entrecôte', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Faisan', ProductCategory::VIANDE, 9, 2, $freeze],
            ['Filet mignon', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Jambon', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Lapin', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Magret de canard', ProductCategory::VIANDE, 9, 3, $freeze],
            ['Merguez', ProductCategory::VIANDE, 5, 9, $freeze],
            ['Oie', ProductCategory::VIANDE, 10, 1, $freeze],
            ['Pintade', ProductCategory::VIANDE, 9, 3, $freeze],
            ['Porc', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Poulet', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Sanglier', ProductCategory::VIANDE, 9, 2, $freeze],
            ['Saucisse', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Steak haché', ProductCategory::VIANDE, 1, 12, $freeze],
            ['Travers de porc', ProductCategory::VIANDE, 5, 9, $freeze],
            ['Veau', ProductCategory::VIANDE, 3, 10, $freeze],
            ['Venaison', ProductCategory::VIANDE, 9, 2, $freeze],

            // 25 poissons et produits de la mer
            ['Anchois', ProductCategory::POISSON, 4, 10, $freeze],
            ['Bar', ProductCategory::POISSON, 3, 11, $freeze],
            ['Barbue', ProductCategory::POISSON, 3, 10, $freeze],
            ['Baudroie', ProductCategory::POISSON, 10, 5, $freeze],
            ['Bulot', ProductCategory::POISSON, 1, 12, $freeze],
            ['Cabillaud', ProductCategory::POISSON, 10, 4, $freeze],
            ['Calamar', ProductCategory::POISSON, 9, 3, $freeze],
            ['Coquille Saint-Jacques', ProductCategory::POISSON, 10, 5, $freeze],
            ['Dorade grise', ProductCategory::POISSON, 2, 9, $freeze],
            ['Églefin', ProductCategory::POISSON, 9, 5, $freeze],
            ['Hareng', ProductCategory::POISSON, 10, 3, $freeze],
            ['Homard', ProductCategory::POISSON, 4, 8, $freeze],
            ['Huître', ProductCategory::POISSON, 9, 4, $freeze],
            ['Langoustine', ProductCategory::POISSON, 4, 8, $freeze],
            ['Lieu jaune', ProductCategory::POISSON, 2, 9, $freeze],
            ['Lieu noir', ProductCategory::POISSON, 1, 12, $freeze],
            ['Lotte', ProductCategory::POISSON, 9, 5, $freeze],
            ['Maquereau', ProductCategory::POISSON, 3, 10, $freeze],
            ['Merlan', ProductCategory::POISSON, 10, 4, $freeze],
            ['Merlu', ProductCategory::POISSON, 3, 9, $freeze],
            ['Moule', ProductCategory::POISSON, 7, 2, $freeze],
            ['Rouget', ProductCategory::POISSON, 5, 10, $freeze],
            ['Sardine', ProductCategory::POISSON, 4, 11, $freeze],
            ['Sole', ProductCategory::POISSON, 2, 10, $freeze],
            ['Tourteau', ProductCategory::POISSON, 5, 11, $freeze],
        ];
    }

    /**
     * @return list<SeasonName>
     */
    private function getSeasonsForRange(int $startMonth, int $endMonth): array
    {
        $activeMonths = [];
        $month = $startMonth;

        while (true) {
            $activeMonths[] = $month;
            if ($month === $endMonth) {
                break;
            }
            $month = 12 === $month ? 1 : $month + 1;
        }

        $seasonMonths = [
            SeasonName::PRINTEMPS->value => [3, 4, 5],
            SeasonName::ETE->value => [6, 7, 8],
            SeasonName::AUTOMNE->value => [9, 10, 11],
            SeasonName::HIVER->value => [12, 1, 2],
        ];

        return array_values(array_filter(
            SeasonName::cases(),
            static fn (SeasonName $season): bool => [] !== array_intersect($activeMonths, $seasonMonths[$season->value]),
        ));
    }

    private function copyFixtureImage(string $productName): ?string
    {
        $slug = strtolower((string) (new AsciiSlugger('fr'))->slug($productName));
        $sourceDirectory = __DIR__.'/images/products';
        $destinationDirectory = dirname(__DIR__, 2).'/public/uploads/products';

        foreach (['webp', 'avif', 'jpg', 'jpeg', 'png'] as $extension) {
            $filename = $slug.'.'.$extension;
            $source = $sourceDirectory.'/'.$filename;

            if (!is_file($source)) {
                continue;
            }

            if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0775, true) && !is_dir($destinationDirectory)) {
                throw new \RuntimeException(sprintf('Impossible de créer le dossier d’images « %s ».', $destinationDirectory));
            }

            if (!copy($source, $destinationDirectory.'/'.$filename)) {
                throw new \RuntimeException(sprintf('Impossible de copier l’image de fixture « %s ».', $source));
            }

            return $filename;
        }

        return null;
    }
}
