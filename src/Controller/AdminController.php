<?php

namespace App\Controller;

use App\Entity\Recette;
use App\Entity\Avis;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        ProductRepository $productRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $recipeRatings = [];
        foreach ($entityManager->getRepository(Recette::class)->findBy([], ['titre' => 'ASC']) as $recette) {
            $notesByUser = [];
            foreach ($recette->getAvis() as $avis) {
                if (null === $avis->getParentAvis() && null !== $avis->getNote()) {
                    $notesByUser[$avis->getUser()?->getId()] = $avis->getNote();
                }
            }
            $notes = array_values($notesByUser);
            $recipeRatings[] = [
                'recette' => $recette,
                'average' => [] === $notes ? null : array_sum($notes) / count($notes),
                'count' => count($notes),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $productRepository->count(),
            'userCount' => $userRepository->count(),
            'recipeCount' => $entityManager->getRepository(Recette::class)->count(),
            'recipeRatings' => $recipeRatings,
        ]);
    }

    #[Route('/signalements', name: 'app_admin_reports', methods: ['GET'])]
    public function reports(EntityManagerInterface $entityManager): Response
    {
        return $this->render('admin/reports.html.twig', [
            'recettes' => $entityManager->getRepository(Recette::class)->findBy(['signale' => true]),
            'avis' => $entityManager->getRepository(Avis::class)->findBy(['signale' => true]),
        ]);
    }
}
