<?php


namespace App\Controller;

use App\Entity\Product;
use App\Entity\Recette;
use App\Entity\Season;
use App\Enum\ProductCategory;
use App\Enum\RecetteStatut;
use App\Enum\SeasonName;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/products')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $category = ProductCategory::tryFrom((string) $request->query->get('categorie'));
        $season = SeasonName::tryFrom((string) $request->query->get('saison'));
        $search = mb_substr(trim((string) $request->query->get('q')), 0, 100);
        $products = $productRepository->findByFilters($category, $season, $search);
        $productsByCategory = array_fill_keys(['fruit', 'legume', 'viande', 'poisson'], []);

        foreach ($products as $product) {
            $productsByCategory[$product->getCategory()->value][] = $product;
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'productsByCategory' => $productsByCategory,
            'selectedCategory' => $category,
            'selectedSeason' => $season,
            'search' => $search,
            'categories' => ProductCategory::cases(),
            'seasons' => SeasonName::cases(),
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'], priority: -1)]
    public function show(Product $product): Response
    {
        $publishedRecipes = array_values(array_filter(
            $product->getRecettes()->toArray(),
            static fn ($recette): bool => $recette->getStatut() === RecetteStatut::PUBLIEE,
        ));

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'publishedRecipes' => $publishedRecipes,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('photoFile')->getData();

            if ($imageFile) {

                $originalFilename = pathinfo(
                    $imageFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug($originalFilename);

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {

                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );

                    $product->setPhoto($newFilename);

                } catch (FileException $e) {

                    $this->addFlash(
                        'danger',
                        "Impossible d'envoyer l'image."
                    );

                    return $this->redirectToRoute('app_product_new');
                }
            }

            $this->synchronizeSeasonsFromHarvestMonths($product, $entityManager);
            $this->synchronizeRecettesFromProductName(
                $product,
                $entityManager,
                $form->get('recettes')->getData()->toArray(),
            );

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été ajouté avec succès.');

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('photoFile')->getData();

            if ($imageFile) {

                $originalFilename = pathinfo(
                    $imageFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug($originalFilename);

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );

                    $product->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Impossible d'envoyer l'image.");

                    return $this->redirectToRoute('app_product_edit', [
                        'id' => $product->getId(),
                    ]);
                }
            }

            $this->synchronizeSeasonsFromHarvestMonths($product, $entityManager);
            $this->synchronizeRecettesFromProductName(
                $product,
                $entityManager,
                $form->get('recettes')->getData()->toArray(),
            );

            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès.');

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        foreach ($product->getRecettes() as $recette) {
            $recette->removeProduct($product);
        }

        foreach ($product->getSeasons() as $season) {
            $product->removeSeason($season);
        }

        $photo = $product->getPhoto();

        $entityManager->remove($product);
        $entityManager->flush();

        if ($photo) {
            $photoPath = $this->getParameter('product_images_directory') . '/' . $photo;

            if (is_file($photoPath)) {
                unlink($photoPath);
            }
        }

        $this->addFlash('success', 'Le produit a été supprimé avec succès.');

        return $this->redirectToRoute('app_product_index');
    }

    private function synchronizeSeasonsFromHarvestMonths(Product $product, EntityManagerInterface $entityManager): void
    {
        foreach ($product->getSeasons()->toArray() as $season) {
            $product->removeSeason($season);
        }

        $startMonth = $product->getDebutRecolteMois()?->getMonthOrder();
        $endMonth = $product->getFinRecolteMois()?->getMonthOrder();

        if (null === $startMonth || null === $endMonth) {
            return;
        }

        $activeMonths = [];
        $month = $startMonth;
        while (true) {
            $activeMonths[] = $month;
            if ($month === $endMonth) {
                break;
            }
            $month = 12 === $month ? 1 : $month + 1;
        }

        $seasonNames = [
            SeasonName::PRINTEMPS,
            SeasonName::ETE,
            SeasonName::AUTOMNE,
            SeasonName::HIVER,
        ];
        $seasonMonths = [
            SeasonName::PRINTEMPS->value => [3, 4, 5],
            SeasonName::ETE->value => [6, 7, 8],
            SeasonName::AUTOMNE->value => [9, 10, 11],
            SeasonName::HIVER->value => [12, 1, 2],
        ];

        foreach ($seasonNames as $seasonName) {
            if ([] === array_intersect($activeMonths, $seasonMonths[$seasonName->value])) {
                continue;
            }

            $season = $entityManager->getRepository(Season::class)->findOneBy(['nameSeason' => $seasonName]);
            if ($season instanceof Season) {
                $product->addSeason($season);
            }
        }
    }

    /**
     * @param list<Recette> $selectedRecettes
     */
    private function synchronizeRecettesFromProductName(
        Product $product,
        EntityManagerInterface $entityManager,
        array $selectedRecettes
    ): void {
        foreach ($product->getRecettes()->toArray() as $linkedRecette) {
            $product->removeRecette($linkedRecette);
        }

        foreach ($selectedRecettes as $selectedRecette) {
            if ($selectedRecette instanceof Recette) {
                $product->addRecette($selectedRecette);
            }
        }

        $productName = trim(mb_strtolower($product->getNom()));
        if ('' === $productName) {
            return;
        }

        $matchingRecettes = $entityManager->getRepository(Recette::class)
            ->createQueryBuilder('r')
            ->where('LOWER(r.titre) LIKE :term OR LOWER(r.ingredient) LIKE :term')
            ->setParameter('term', '%' . $productName . '%')
            ->getQuery()
            ->getResult();

        foreach ($matchingRecettes as $matchingRecette) {
            if ($matchingRecette instanceof Recette) {
                $product->addRecette($matchingRecette);
            }
        }
    }

}
