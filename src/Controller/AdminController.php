<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Recette;
use App\Entity\Avis;
use App\Enum\UserRole;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function reports(Request $request, EntityManagerInterface $entityManager): Response
    {
        $avisRepository = $entityManager->getRepository(Avis::class);
        $allAvis = $avisRepository->findBy([], ['createdAt' => 'DESC']);
        $readCommentIds = $this->getReadCommentIds($request);
        $activeAvis = array_values(array_filter(
            $allAvis,
            static fn (Avis $avis): bool => !in_array($avis->getId(), $readCommentIds, true),
        ));
        $reportedAvis = array_values(array_filter(
            $activeAvis,
            static fn (Avis $avis): bool => $avis->isSignale(),
        ));
        $reportedRecettes = $entityManager->getRepository(Recette::class)->findBy(['signale' => true], ['createdAt' => 'DESC']);

        return $this->render('admin/reports.html.twig', [
            'reportedRecettes' => $reportedRecettes,
            'allAvis' => $activeAvis,
            'reportedAvisCount' => count($reportedAvis),
        ]);
    }

    #[Route('/signalements/commentaires/{avis}/archiver', name: 'app_admin_report_comment_archive', methods: ['POST'])]
    public function toggleArchivedComment(Avis $avis, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('archive-comment-'.$avis->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $shouldArchive = '1' === (string) $request->request->get('archive', '1');
        $readCommentIds = $this->getReadCommentIds($request);
        $avisId = $avis->getId();

        if (null !== $avisId) {
            if ($shouldArchive && !in_array($avisId, $readCommentIds, true)) {
                $readCommentIds[] = $avisId;
            }

            if (!$shouldArchive) {
                $readCommentIds = array_values(array_filter(
                    $readCommentIds,
                    static fn (int $id): bool => $id !== $avisId,
                ));
            }

            try {
                $request->getSession()->set('admin_read_comment_ids', $readCommentIds);
            } catch (SessionNotFoundException) {
                // If session is unavailable, keep graceful behavior without crashing.
            }
        }

        $this->addFlash('success', $shouldArchive ? 'Commentaire marqué comme lu.' : 'Commentaire restauré dans la liste principale.');

        return $this->redirectToRoute('app_admin_reports');
    }

    #[Route('/signalements/commentaires/lus', name: 'app_admin_report_comments_mark_read', methods: ['POST'])]
    public function markCommentsAsRead(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('mark-read-comments', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $selectedIds = $request->request->all('comment_ids');
        if (!is_array($selectedIds) || [] === $selectedIds) {
            $this->addFlash('danger', 'Aucun commentaire sélectionné.');

            return $this->redirectToRoute('app_admin_reports');
        }

        $validIds = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($selectedIds, static fn (mixed $id): bool => is_numeric($id)),
        )));

        if ([] === $validIds) {
            $this->addFlash('danger', 'Aucun commentaire valide sélectionné.');

            return $this->redirectToRoute('app_admin_reports');
        }

        $avisRepository = $entityManager->getRepository(Avis::class);
        $existingIds = array_map(
            static fn (Avis $avis): ?int => $avis->getId(),
            $avisRepository->findBy(['id' => $validIds]),
        );
        $existingIds = array_values(array_filter($existingIds, static fn (?int $id): bool => null !== $id));

        $readCommentIds = $this->getReadCommentIds($request);
        $mergedIds = array_values(array_unique(array_merge($readCommentIds, $existingIds)));

        try {
            $request->getSession()->set('admin_read_comment_ids', $mergedIds);
        } catch (SessionNotFoundException) {
            // If session is unavailable, keep graceful behavior without crashing.
        }

        $this->addFlash('success', sprintf('%d commentaire%s marqué%s comme lu%s.', count($existingIds), count($existingIds) > 1 ? 's' : '', count($existingIds) > 1 ? 's' : '', count($existingIds) > 1 ? 's' : ''));

        return $this->redirectToRoute('app_admin_reports');
    }

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $search = mb_substr(trim((string) $request->query->get('q')), 0, 100);
        $search = '' === $search ? null : $search;

        $role = UserRole::tryFrom((string) $request->query->get('role'));

        $selectedStatus = (string) $request->query->get('status');
        $isBlocked = match ($selectedStatus) {
            'active' => false,
            'blocked' => true,
            default => null,
        };

        $perPage = 12;
        $currentPage = max(1, $request->query->getInt('page', 1));
        $totalUsers = $userRepository->countForAdmin($search, $role, $isBlocked);
        $totalPages = max(1, (int) ceil($totalUsers / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $users = $userRepository->findForAdmin(
            $search,
            $role,
            $isBlocked,
            ($currentPage - 1) * $perPage,
            $perPage,
        );

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'adminCount' => $userRepository->countAdministrators(),
            'search' => $search,
            'selectedRole' => $role,
            'selectedStatus' => in_array($selectedStatus, ['active', 'blocked'], true) ? $selectedStatus : null,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
        ]);
    }

    #[Route('/users/{id}/toggle-block', name: 'app_admin_user_toggle_block', methods: ['POST'])]
    public function toggleUserBlock(User $managedUser, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('toggle-block-user-'.$managedUser->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($managedUser === $currentUser) {
            $this->addFlash('danger', 'Vous ne pouvez pas bloquer votre propre compte administrateur.');

            return $this->redirectToRoute('app_admin_users', $this->buildUsersListQuery($request));
        }

        if ($managedUser->getRole() === UserRole::ADMINISTRATEUR) {
            $this->addFlash('danger', 'Le blocage d’un autre administrateur n’est pas autorisé depuis cette page.');

            return $this->redirectToRoute('app_admin_users', $this->buildUsersListQuery($request));
        }

        $managedUser->setIsBlocked(!$managedUser->isBlocked());
        $entityManager->flush();

        $this->addFlash('success', $managedUser->isBlocked()
            ? sprintf('Le compte %s a été bloqué.', $managedUser->getEmail())
            : sprintf('Le compte %s a été débloqué.', $managedUser->getEmail()));

        return $this->redirectToRoute('app_admin_users', $this->buildUsersListQuery($request));
    }

    #[Route('/users/{id}/toggle-role', name: 'app_admin_user_toggle_role', methods: ['POST'])]
    public function toggleUserRole(User $managedUser, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('toggle-role-user-'.$managedUser->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($managedUser === $currentUser) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier votre propre rôle depuis cette page.');

            return $this->redirectToRoute('app_admin_users', $this->buildUsersListQuery($request));
        }

        if ($managedUser->getRole() === UserRole::ADMINISTRATEUR) {
            if ($userRepository->countAdministrators() <= 1) {
                $this->addFlash('danger', 'Impossible de rétrograder le dernier administrateur de la plateforme.');

                return $this->redirectToRoute('app_admin_users', $this->buildUsersListQuery($request));
            }

            $managedUser->setRole(UserRole::UTILISATEUR);
            $this->addFlash('success', sprintf('%s repasse en rôle utilisateur.', $managedUser->getEmail()));
        } else {
            $managedUser->setRole(UserRole::ADMINISTRATEUR);
            $managedUser->setIsBlocked(false);
            $this->addFlash('success', sprintf('%s est désormais administrateur.', $managedUser->getEmail()));
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_users', $this->buildUsersListQuery($request));
    }

    /**
     * @return array{q?: string, role?: string, status?: string, page?: int}
     */
    private function buildUsersListQuery(Request $request): array
    {
        $query = [];

        $search = mb_substr(trim((string) $request->query->get('q')), 0, 100);
        if ('' !== $search) {
            $query['q'] = $search;
        }

        $role = UserRole::tryFrom((string) $request->query->get('role'));
        if (null !== $role) {
            $query['role'] = $role->value;
        }

        $status = (string) $request->query->get('status');
        if (in_array($status, ['active', 'blocked'], true)) {
            $query['status'] = $status;
        }

        $page = max(1, $request->query->getInt('page', 1));
        if ($page > 1) {
            $query['page'] = $page;
        }

        return $query;
    }

    /**
     * @return list<int>
     */
    private function getReadCommentIds(Request $request): array
    {
        try {
            $rawIds = $request->getSession()->get('admin_read_comment_ids', []);
        } catch (SessionNotFoundException) {
            return [];
        }

        if (!is_array($rawIds)) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($rawIds, static fn (mixed $id): bool => is_numeric($id)),
        )));
    }
}
