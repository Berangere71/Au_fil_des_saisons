<?php

namespace App\Controller;

use App\Enum\ProductCategory;
use App\Repository\ProductRepository;
use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/home')]
    public function index(
        Request $request,
        WeatherService $weatherService,
        ProductRepository $productRepository,
    ): Response {
        $now = new \DateTimeImmutable();
        $city = trim((string) $request->query->get('ville', 'Toulouse'));
        $city = '' !== $city ? mb_substr($city, 0, 100) : 'Toulouse';
        $weather = null;
        $weatherError = null;

        try {
            $weather = $weatherService->getCurrentWeather($city);
        } catch (\RuntimeException $exception) {
            $weatherError = $exception->getMessage();
        }

        $productsByCategory = array_fill_keys(
            array_map(static fn (ProductCategory $category): string => $category->value, ProductCategory::cases()),
            null,
        );
        $currentProducts = $productRepository->findInSeasonForMonth((int) $now->format('n'));
        $categoryOrder = ['fruit' => 0, 'legume' => 1, 'viande' => 2, 'poisson' => 3];
        usort($currentProducts, static function ($firstProduct, $secondProduct) use ($categoryOrder): int {
            $categoryComparison = $categoryOrder[$firstProduct->getCategory()->value]
                <=> $categoryOrder[$secondProduct->getCategory()->value];

            return $categoryComparison ?: strcasecmp($firstProduct->getNom(), $secondProduct->getNom());
        });

        foreach ($currentProducts as $product) {
            $category = $product->getCategory()->value;
            $productsByCategory[$category] ??= $product;
        }

        $calendarProducts = [];
        $calendarCategoryCounts = array_fill_keys(array_keys($categoryOrder), 0);
        foreach ($currentProducts as $product) {
            $category = $product->getCategory()->value;
            if ($calendarCategoryCounts[$category] >= 1) {
                continue;
            }

            $calendarProducts[] = $product;
            ++$calendarCategoryCounts[$category];
        }

        $dateFormatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            $now->getTimezone(),
            \IntlDateFormatter::GREGORIAN,
            'EEEE d MMMM y',
        );

        $currentSeason = match ((int) $now->format('n')) {
            3, 4, 5 => 'printemps',
            6, 7, 8 => 'ete',
            9, 10, 11 => 'automne',
            default => 'hiver',
        };

        return $this->render('home/index.html.twig', [
            'weather' => $weather,
            'weather_error' => $weatherError,
            'weather_city' => $city,
            'today_label' => ucfirst((string) $dateFormatter->format($now)),
            'current_season' => $currentSeason,
            'products_by_category' => $productsByCategory,
            'calendar_products' => $calendarProducts,
            'current_month' => (int) $now->format('n'),
            'categories' => [
                'fruit' => ['label' => 'Fruits', 'icon' => 'fa-apple-whole'],
                'legume' => ['label' => 'Légumes', 'icon' => 'fa-carrot'],
                'viande' => ['label' => 'Viande', 'icon' => 'fa-drumstick-bite'],
                'poisson' => ['label' => 'Poisson', 'icon' => 'fa-fish'],
            ],
            'months' => ['Jan.', 'Fév.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'],
        ]);
    }
}
