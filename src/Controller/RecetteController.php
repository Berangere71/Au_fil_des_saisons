<?php

namespace App\Controller;

use App\Entity\Recette;
use App\Entity\User;
use App\Entity\Avis;
use App\Entity\Favoris;
use App\Enum\RecetteStatut;
use App\Form\RecetteType;
use App\Service\CommentModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/recettes')]
#[IsGranted('ROLE_USER')]
final class RecetteController extends AbstractController
{
    #[Route('', name: 'app_recette_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $favoriteRecipeIds = array_map(
            static fn (Favoris $favori): ?int => $favori->getRecette()?->getId(),
            $entityManager->getRepository(Favoris::class)->findBy(['user' => $user]),
        );

        return $this->render('recette/index.html.twig', [
            'recettes' => $entityManager->getRepository(Recette::class)->findBy(['statut' => RecetteStatut::PUBLIEE], ['createdAt' => 'DESC']),
            'favoriteRecipeIds' => $favoriteRecipeIds,
        ]);
    }

    #[Route('/nouvelle', name: 'app_recette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $recette = (new Recette())->setUser($user)->setStatut(RecetteStatut::ATTENTE);

        return $this->processForm($request, $entityManager, $slugger, $recette, true);
    }

    #[Route('/{id}', name: 'app_recette_show', methods: ['GET'], priority: -1)]
    public function show(Recette $recette, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isPublished($recette) && $recette->getUser() !== $user) {
            throw $this->createNotFoundException('Cette recette est un brouillon privé.');
        }
        $avis = array_values(array_filter($recette->getAvis()->toArray(), static fn (Avis $avis) => $avis->getParentAvis() === null));
        usort($avis, static function (Avis $a, Avis $b): int {
            $aIsAdmin = in_array('ROLE_ADMIN', $a->getUser()?->getRoles() ?? [], true);
            $bIsAdmin = in_array('ROLE_ADMIN', $b->getUser()?->getRoles() ?? [], true);

            return ($bIsAdmin <=> $aIsAdmin) ?: ($b->getCreatedAt() <=> $a->getCreatedAt());
        });
        $notesByUser = [];
        foreach (array_reverse($avis) as $avisItem) {
            if (null !== $avisItem->getNote()) {
                $notesByUser[$avisItem->getUser()?->getId()] = $avisItem->getNote();
            }
        }
        $notes = array_values($notesByUser);
        $currentUserReview = $entityManager->getRepository(Avis::class)->findOneBy([
            'user' => $user,
            'recette' => $recette,
            'parentAvis' => null,
        ], ['createdAt' => 'DESC']);

        return $this->render('recette/show.html.twig', [
            'recette' => $recette,
            'avis' => $avis,
            'isFavorite' => $entityManager->getRepository(Favoris::class)->findOneBy(['user' => $user, 'recette' => $recette]) !== null,
            'averageRating' => $notes === [] ? null : array_sum($notes) / count($notes),
            'ratingCount' => count($notes),
            'currentUserRating' => $currentUserReview?->getNote(),
        ]);
    }

    #[Route('/{id}/favori', name: 'app_recette_favorite', methods: ['POST'])]
    public function favorite(Recette $recette, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectAdminToReview($recette);
        }
        if (!$this->isPublished($recette)) {
            return $this->redirectUnavailableRecipe($recette);
        }
        /** @var User $user */ $user = $this->getUser();
        if ($this->isCsrfTokenValid('favorite-'.$recette->getId(), (string) $request->request->get('_token'))) {
            $favori = $entityManager->getRepository(Favoris::class)->findOneBy(['user' => $user, 'recette' => $recette]);
            if ($favori) { $entityManager->remove($favori); } else { $entityManager->persist((new Favoris())->setUser($user)->setRecette($recette)); }
            $entityManager->flush();
        }
        if ($request->request->get('_redirect') === 'index') {
            return $this->redirectToRoute('app_recette_index');
        }

        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
    }

    #[Route('/{id}/avis', name: 'app_recette_review', methods: ['POST'])]
    public function review(Recette $recette, Request $request, EntityManagerInterface $entityManager, CommentModerationService $commentModeration): Response
    {
        if (!$this->isPublished($recette)) {
            return $this->redirectUnavailableRecipe($recette);
        }
        if ($this->isCsrfTokenValid('review-'.$recette->getId(), (string) $request->request->get('_token'))) {
            $note = $request->request->getInt('note');
            $commentaire = trim((string) $request->request->get('commentaire'));
            if ($note >= 1 && $note <= 5) {
                /** @var User $user */ $user = $this->getUser();
                $avis = $entityManager->getRepository(Avis::class)->findOneBy([
                    'user' => $user,
                    'recette' => $recette,
                    'parentAvis' => null,
                ], ['createdAt' => 'DESC']);
                if (!$avis instanceof Avis) {
                    $avis = (new Avis())->setUser($user)->setRecette($recette);
                    $entityManager->persist($avis);
                }
                $avis->setNote($note);

                if ('' !== $commentaire) {
                    $forbiddenTerm = $commentModeration->findForbiddenTerm($commentaire);
                    if (null !== $forbiddenTerm) {
                        $entityManager->flush();
                        $this->addFlash('danger', 'Votre note a été enregistrée, mais le commentaire contient un terme interdit et n’a pas été publié.');

                        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
                    }
                }

                if ('' !== $commentaire) {
                    $avis->setCommentaire($commentaire);
                }
                $entityManager->flush();
                $this->addFlash('success', 'Votre note a été enregistrée indépendamment de vos favoris.');
            } else { $this->addFlash('danger', 'Choisissez une note comprise entre 1 et 5 étoiles.'); }
        }
        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
    }

    #[Route('/{id}/avis/{avis}/repondre', name: 'app_recette_reply', methods: ['POST'])]
    public function reply(Recette $recette, Avis $avis, Request $request, EntityManagerInterface $entityManager, CommentModerationService $commentModeration): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectAdminToReview($recette);
        }
        if (!$this->isPublished($recette)) {
            return $this->redirectUnavailableRecipe($recette);
        }
        $commentaire = trim((string) $request->request->get('commentaire'));
        if ($avis->getRecette() === $recette && $commentaire !== '' && $this->isCsrfTokenValid('reply-'.$avis->getId(), (string) $request->request->get('_token'))) {
            if (null !== $commentModeration->findForbiddenTerm($commentaire)) {
                $this->addFlash('danger', 'Votre réponse contient un terme interdit et n’a pas été publiée.');

                return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
            }

            /** @var User $user */ $user = $this->getUser();
            $entityManager->persist((new Avis())->setUser($user)->setRecette($recette)->setParentAvis($avis)->setCommentaire($commentaire));
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
    }

    #[Route('/{id}/signaler', name: 'app_recette_report', methods: ['POST'])]
    public function report(Recette $recette, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectAdminToReview($recette);
        }
        if (!$this->isPublished($recette)) {
            return $this->redirectUnavailableRecipe($recette);
        }
        if ($this->isCsrfTokenValid('report-'.$recette->getId(), (string) $request->request->get('_token'))) {
            $recette->setSignale(true)->setMotifSignalement(trim((string) $request->request->get('motif')))->setStatut(RecetteStatut::SIGNALEE);
            $entityManager->flush();
            $this->addFlash('success', 'Le signalement a été transmis à l’administration.');
        }
        return $this->redirectToRoute('app_recette_index');
    }

    #[Route('/{id}/avis/{avis}/signaler', name: 'app_recette_review_report', methods: ['POST'])]
    public function reportReview(Recette $recette, Avis $avis, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectAdminToReview($recette);
        }
        if (!$this->isPublished($recette)) {
            return $this->redirectUnavailableRecipe($recette);
        }
        if ($avis->getRecette() === $recette && $this->isCsrfTokenValid('report-review-'.$avis->getId(), (string) $request->request->get('_token'))) {
            $avis->setSignale(true)->setMotifSignalement(trim((string) $request->request->get('motif')));
            $entityManager->flush();
            $this->addFlash('success', 'Le signalement a été transmis à l’administration.');
        }
        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
    }

    #[Route('/{id}/avis/{avis}/supprimer', name: 'app_recette_review_delete', methods: ['POST'])]
    public function deleteReview(Recette $recette, Avis $avis, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($avis->getRecette() !== $recette) {
            throw $this->createNotFoundException('Commentaire introuvable pour cette recette.');
        }

        if (!$this->isCsrfTokenValid('delete-review-'.$avis->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($avis->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres commentaires.');
        }

        $entityManager->remove($avis);
        $entityManager->flush();

        $this->addFlash('success', 'Votre commentaire a été supprimé.');

        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
    }

    #[Route('/{id}/modifier', name: 'app_recette_edit', methods: ['GET', 'POST'])]
    public function edit(Recette $recette, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyUnlessOwnerOrAdmin($recette);
        return $this->processForm($request, $entityManager, $slugger, $recette, false);
    }

    #[Route('/{id}/partager', name: 'app_recette_share', methods: ['POST'])]
    public function share(Recette $recette, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($recette->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Cette recette est un brouillon privé.');
        }

        if (!$this->isCsrfTokenValid('share-recette-'.$recette->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        if ($recette->getStatut() === RecetteStatut::ATTENTE) {
            $recette->setIsPublic(true)->setStatut(RecetteStatut::PUBLIEE);
            $entityManager->flush();
            $this->addFlash('success', 'La recette est maintenant publique et visible par tous.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/{id}/supprimer', name: 'app_recette_delete', methods: ['POST'])]
    public function delete(Recette $recette, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnerOrAdmin($recette);
        if ($this->isCsrfTokenValid('delete-recette-'.$recette->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($recette);
            $entityManager->flush();
            $this->addFlash('success', 'La recette a été supprimée.');
        }
        return $this->redirectToRoute('app_recette_index');
    }

    private function processForm(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, Recette $recette, bool $isNew): Response
    {
        $form = $this->createForm(RecetteType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photoFile')->getData();
            if ($photo) {
                $filename = $slugger->slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$photo->guessExtension();
                try { $photo->move($this->getParameter('recette_images_directory'), $filename); $recette->setPhoto($filename); }
                catch (FileException) { $this->addFlash('danger', 'La photo n’a pas pu être enregistrée.'); }
            }
            if ($this->isGranted('ROLE_ADMIN')) {
                $recette->setIsPublic(true)->setStatut(RecetteStatut::PUBLIEE);
            } elseif ($recette->isPublic()) {
                $recette->setStatut(RecetteStatut::PUBLIEE);
            } else {
                $recette->setStatut(RecetteStatut::ATTENTE);
            }
            $this->synchronizeSeasonsFromProducts($recette);
            if ($isNew) { $entityManager->persist($recette); }
            $entityManager->flush();
            $this->addFlash('success', $isNew ? 'Votre recette a été enregistrée.' : 'La recette a été mise à jour.');
            return $this->redirectToRoute('app_recette_index');
        }

        if ($form->isSubmitted()) {
            $this->addFlash('danger', 'La recette n’a pas été enregistrée : vérifiez les champs obligatoires, notamment le produit associé.');
        }

        return $this->render('recette/form.html.twig', ['recetteForm' => $form->createView(), 'isNew' => $isNew]);
    }

    private function denyUnlessOwnerOrAdmin(Recette $recette): void
    {
        if ($this->isGranted('ROLE_ADMIN')) { return; }
        if ($recette->getUser() !== $this->getUser()) { throw $this->createAccessDeniedException(); }
    }

    private function synchronizeSeasonsFromProducts(Recette $recette): void
    {
        foreach ($recette->getSeasons()->toArray() as $season) {
            $recette->removeSeason($season);
        }

        $primarySeason = null;

        foreach ($recette->getProducts() as $product) {
            foreach ($product->getSeasons() as $season) {
                $primarySeason ??= $season;
                $recette->addSeason($season);
            }
        }

        $recette->setPrimarySeason($primarySeason);
    }

    private function isPublished(Recette $recette): bool
    {
        return $recette->getStatut() === RecetteStatut::PUBLIEE;
    }

    private function redirectUnavailableRecipe(Recette $recette): Response
    {
        $this->addFlash('danger', 'Cette recette est en attente de publication : les favoris, notes et commentaires seront disponibles après validation.');

        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
    }

    private function redirectAdminToReview(Recette $recette): Response
    {
        $this->addFlash('info', 'Sur une recette publique, l’administrateur peut uniquement publier une note et un commentaire.');

        return $this->redirectToRoute('app_recette_show', ['id' => $recette->getId()]);
    }
}
