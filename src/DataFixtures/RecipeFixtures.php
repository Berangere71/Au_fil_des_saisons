<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Recette;
use App\Entity\Season;
use App\Entity\User;
use App\Enum\RecetteStatut;
use App\Enum\RecetteTypePlat;
use App\Enum\SeasonName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class RecipeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $author = $this->getReference(UserFixtures::RECIPE_AUTHOR_REFERENCE, User::class);

        foreach ($this->getRecipes() as [$title, $type, $productNames, $primarySeason, $preparationTime, $oven]) {
            $recipe = $manager->getRepository(Recette::class)->findOneBy(['titre' => $title, 'user' => $author]);

            if (!$recipe instanceof Recette) {
                $recipe = (new Recette())->setTitre($title)->setUser($author);
                $manager->persist($recipe);
            }

            foreach ($recipe->getProducts()->toArray() as $product) {
                $recipe->removeProduct($product);
            }
            foreach ($recipe->getSeasons()->toArray() as $season) {
                $recipe->removeSeason($season);
            }

            $products = [];
            foreach ($productNames as $productName) {
                $product = $this->getReference(ProductFixtures::REFERENCE_PREFIX.$productName, Product::class);
                $products[] = $product;
                $recipe->addProduct($product);
                foreach ($product->getSeasons() as $season) {
                    $recipe->addSeason($season);
                }
            }

            $primarySeasonEntity = $this->getReference(SeasonFixtures::REFERENCE_PREFIX.$primarySeason->value, Season::class);
            $recipe->addSeason($primarySeasonEntity);
            $recipe
                ->setPrimarySeason($primarySeasonEntity)
                ->setTypePlat($type)
                ->setNbrPerson(4)
                ->setTimePrepa($preparationTime)
                ->setIngredient($this->buildIngredients($products))
                ->setPreparation($this->buildPreparation($title, $products))
                ->setIsPublic(true)
                ->setStatut(RecetteStatut::PUBLIEE)
                ->setIsOven($oven)
                ->setTempOven($oven ? 180 : null)
                ->setTimeOven($oven ? 30 : null);

            $fixtureImage = $this->copyFixtureImage($title);
            if (null !== $fixtureImage) {
                $recipe->setPhoto($fixtureImage);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProductFixtures::class, UserFixtures::class];
    }

    /**
     * @return list<array{string, RecetteTypePlat, list<string>, SeasonName, int, bool}>
     */
    private function getRecipes(): array
    {
        return [
            // Printemps
            ['Salade d’asperges et petits pois', RecetteTypePlat::ENTREE, ['Asperge', 'Petit pois'], SeasonName::PRINTEMPS, 25, false],
            ['Artichauts vinaigrette', RecetteTypePlat::ENTREE, ['Artichaut'], SeasonName::PRINTEMPS, 20, false],
            ['Navarin d’agneau printanier', RecetteTypePlat::PLAT, ['Agneau', 'Carotte', 'Petit pois', 'Navet'], SeasonName::PRINTEMPS, 35, true],
            ['Sole aux asperges', RecetteTypePlat::PLAT, ['Sole', 'Asperge'], SeasonName::PRINTEMPS, 30, true],
            ['Tarte aux fraises et citron', RecetteTypePlat::DESSERT, ['Fraise', 'Citron'], SeasonName::PRINTEMPS, 40, true],
            ['Clafoutis aux cerises', RecetteTypePlat::DESSERT, ['Cerise'], SeasonName::PRINTEMPS, 20, true],

            // Été
            ['Ratatouille provençale', RecetteTypePlat::PLAT, ['Aubergine', 'Courgette', 'Tomate', 'Poivron'], SeasonName::ETE, 30, false],
            ['Salade de melon et concombre', RecetteTypePlat::ENTREE, ['Melon', 'Concombre'], SeasonName::ETE, 15, false],
            ['Sardines grillées à la tomate', RecetteTypePlat::PLAT, ['Sardine', 'Tomate'], SeasonName::ETE, 25, false],
            ['Brochettes de poulet et courgette', RecetteTypePlat::PLAT, ['Poulet', 'Courgette', 'Poivron'], SeasonName::ETE, 25, false],
            ['Tarte aux abricots', RecetteTypePlat::DESSERT, ['Abricot'], SeasonName::ETE, 25, true],
            ['Salade de pêches et myrtilles', RecetteTypePlat::DESSERT, ['Pêche', 'Myrtille'], SeasonName::ETE, 15, false],

            // Automne
            ['Velouté de potimarron', RecetteTypePlat::ENTREE, ['Potimarron', 'Carotte', 'Oignon'], SeasonName::AUTOMNE, 25, false],
            ['Salade de betterave et noix', RecetteTypePlat::ENTREE, ['Betterave', 'Noix'], SeasonName::AUTOMNE, 20, false],
            ['Sanglier au chou rouge', RecetteTypePlat::PLAT, ['Sanglier', 'Chou rouge', 'Oignon'], SeasonName::AUTOMNE, 40, true],
            ['Maquereau rôti au fenouil', RecetteTypePlat::PLAT, ['Maquereau', 'Fenouil'], SeasonName::AUTOMNE, 25, true],
            ['Tarte aux figues et noix', RecetteTypePlat::DESSERT, ['Figue', 'Noix'], SeasonName::AUTOMNE, 30, true],
            ['Pommes rôties aux noisettes', RecetteTypePlat::DESSERT, ['Pomme', 'Noisette'], SeasonName::AUTOMNE, 15, true],

            // Hiver
            ['Soupe de poireaux et céleri', RecetteTypePlat::ENTREE, ['Poireau', 'Céleri-rave', 'Navet'], SeasonName::HIVER, 25, false],
            ['Salade de kiwi et clémentine', RecetteTypePlat::ENTREE, ['Kiwi', 'Clémentine'], SeasonName::HIVER, 15, false],
            ['Bœuf aux carottes', RecetteTypePlat::PLAT, ['Bœuf', 'Carotte', 'Oignon'], SeasonName::HIVER, 35, true],
            ['Cabillaud fondant aux poireaux', RecetteTypePlat::PLAT, ['Cabillaud', 'Poireau'], SeasonName::HIVER, 25, true],
            ['Canard à la clémentine', RecetteTypePlat::PLAT, ['Canard', 'Clémentine'], SeasonName::HIVER, 30, true],
            ['Crumble pomme et kiwi', RecetteTypePlat::DESSERT, ['Pomme', 'Kiwi'], SeasonName::HIVER, 20, true],
        ];
    }

    /** @param list<Product> $products */
    private function buildIngredients(array $products): string
    {
        $lines = array_map(
            static fn (Product $product): string => '- '.$product->getNom(),
            $products,
        );
        $lines[] = '- 2 cuillères à soupe d’huile d’olive';
        $lines[] = '- Sel, poivre et herbes de saison';

        return implode("\n", $lines);
    }

    /** @param list<Product> $products */
    private function buildPreparation(string $title, array $products): string
    {
        $productNames = implode(', ', array_map(static fn (Product $product): string => $product->getNom(), $products));

        return sprintf(
            "1. Laver et préparer les produits : %s.\n2. Découper les ingrédients de manière régulière.\n3. Assaisonner puis cuire selon la recette « %s ».\n4. Dresser et servir sans attendre.",
            $productNames,
            $title,
        );
    }

    private function copyFixtureImage(string $recipeTitle): ?string
    {
        $slug = strtolower((string) (new AsciiSlugger('fr'))->slug($recipeTitle));
        $sourceDirectory = __DIR__.'/images/recipes';
        $destinationDirectory = dirname(__DIR__, 2).'/public/uploads/recettes';

        foreach (['webp', 'avif', 'jpg', 'jpeg', 'png'] as $extension) {
            $filename = $slug.'.'.$extension;
            $source = $sourceDirectory.'/'.$filename;
            if (!is_file($source)) {
                continue;
            }
            if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0775, true) && !is_dir($destinationDirectory)) {
                throw new \RuntimeException(sprintf('Impossible de créer le dossier « %s ».', $destinationDirectory));
            }
            if (!copy($source, $destinationDirectory.'/'.$filename)) {
                throw new \RuntimeException(sprintf('Impossible de copier « %s ».', $source));
            }

            return $filename;
        }

        return null;
    }
}
