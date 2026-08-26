<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Recette;
use App\Entity\Favoris;
use App\Entity\Avis;
use App\Entity\ResetPasswordRequest;
use App\Enum\RecetteStatut;
use App\Enum\SeasonName;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $reportedContentCount = $userRepository->countReportedContentForUser($user);
        $favoriteRecipeIds = array_values(array_filter(array_map(
            static fn (Favoris $favori): ?int => $favori->getRecette()?->getId(),
            $entityManager->getRepository(Favoris::class)->findBy(['user' => $user]),
        )));
        $favoriteRecipes = [] === $favoriteRecipeIds
            ? []
            : $entityManager->getRepository(Recette::class)->findBy(
                ['id' => $favoriteRecipeIds, 'statut' => RecetteStatut::PUBLIEE],
                ['createdAt' => 'DESC'],
            );
        $favoriteRecipesBySeason = [];
        $favoriteRecipesWithoutSeason = [];
        foreach ($favoriteRecipes as $favoriteRecipe) {
            $primarySeason = $favoriteRecipe->getPrimarySeason();
            if (null === $primarySeason) {
                $favoriteRecipesWithoutSeason[] = $favoriteRecipe;

                continue;
            }

            $seasonId = $primarySeason->getId();
            if (null === $seasonId) {
                $favoriteRecipesWithoutSeason[] = $favoriteRecipe;

                continue;
            }

            if (!array_key_exists($seasonId, $favoriteRecipesBySeason)) {
                $favoriteRecipesBySeason[$seasonId] = [
                    'season' => $primarySeason,
                    'recipes' => [],
                ];
            }

            $favoriteRecipesBySeason[$seasonId]['recipes'][] = $favoriteRecipe;
        }
        $seasonOrder = [
            SeasonName::PRINTEMPS->value => 1,
            SeasonName::ETE->value => 2,
            SeasonName::AUTOMNE->value => 3,
            SeasonName::HIVER->value => 4,
        ];
        $favoriteRecipesBySeason = array_values($favoriteRecipesBySeason);
        usort(
            $favoriteRecipesBySeason,
            static function (array $left, array $right) use ($seasonOrder): int {
                $leftValue = $left['season']->getNameSeason()->value;
                $rightValue = $right['season']->getNameSeason()->value;

                return ($seasonOrder[$leftValue] ?? PHP_INT_MAX) <=> ($seasonOrder[$rightValue] ?? PHP_INT_MAX);
            },
        );

        $allMyRecipes = $entityManager->getRepository(Recette::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $myRecipesByTitle = [];
        foreach ($allMyRecipes as $recipe) {
            $normalizedTitle = mb_strtolower(trim($recipe->getTitre()));
            $existingRecipe = $myRecipesByTitle[$normalizedTitle] ?? null;

            if (null === $existingRecipe
                || ($recipe->getStatut() === RecetteStatut::PUBLIEE && $existingRecipe->getStatut() !== RecetteStatut::PUBLIEE)
            ) {
                $myRecipesByTitle[$normalizedTitle] = $recipe;
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'myRecettes' => array_values($myRecipesByTitle),
            'favoriteRecipeIds' => $favoriteRecipeIds,
            'favoriteRecipesBySeason' => $favoriteRecipesBySeason,
            'favoriteRecipesWithoutSeason' => $favoriteRecipesWithoutSeason,
            'reportedContentCount' => $reportedContentCount,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {


        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $photo = $form->get('photoFile')->getData();

            if ($photo) {

                $originalFilename = pathinfo(
                    $photo->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug($originalFilename);

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photo->guessExtension();

                try {

                    $photo->move(
                        $this->getParameter('users_directory'),
                        $newFilename
                    );

                    $user->setPhoto($newFilename);

                } catch (FileException $e) {

                    $this->addFlash(
                        'danger',
                        'Une erreur est survenue lors de l’envoi de la photo.'
                    );

                    return $this->redirectToRoute('app_profile_edit');
                }
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre profil a été modifié avec succès.'
            );

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/profile/password', name: 'app_profile_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $currentPassword = $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {

                $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');

            } else {

                $newPassword = $form->get('newPassword')->getData();

                $user->setPassword(
                    $passwordHasher->hashPassword($user, $newPassword)
                );

                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Votre mot de passe a été modifié avec succès.'
                );

                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/password.html.twig', [
            'passwordForm' => $form->createView(),
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Le compte administrateur ne peut pas être supprimé depuis cette page.');
        }

        if (!$this->isCsrfTokenValid('delete-account-'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        if ('1' !== $request->request->get('confirm_delete')) {
            $this->addFlash('danger', 'Vous devez confirmer la suppression définitive du compte.');

            return $this->redirectToRoute('app_profile');
        }

        $password = (string) $request->request->get('current_password');
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('danger', 'Le mot de passe actuel est incorrect. Le compte n’a pas été supprimé.');

            return $this->redirectToRoute('app_profile');
        }

        $userPhoto = $user->getPhoto();
        $recipePhotos = [];
        $userRecipes = $entityManager->getRepository(Recette::class)->findBy(['user' => $user]);
        foreach ($userRecipes as $recipe) {
            if (null !== $recipe->getPhoto()) {
                $recipePhotos[] = $recipe->getPhoto();
            }
            $entityManager->remove($recipe);
        }

        // Données créées par l'utilisateur sur les recettes des autres membres.
        foreach ($entityManager->getRepository(Favoris::class)->findBy(['user' => $user]) as $favorite) {
            if ($favorite->getRecette()?->getUser() !== $user) {
                $entityManager->remove($favorite);
            }
        }
        foreach ($entityManager->getRepository(Avis::class)->findBy(['user' => $user]) as $review) {
            if ($review->getRecette()?->getUser() !== $user) {
                $entityManager->remove($review);
            }
        }
        foreach ($entityManager->getRepository(ResetPasswordRequest::class)->findBy(['user' => $user]) as $resetRequest) {
            $entityManager->remove($resetRequest);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->deleteUploadedFile($this->getParameter('users_directory'), $userPhoto);
        foreach ($recipePhotos as $recipePhoto) {
            $this->deleteUploadedFile($this->getParameter('recette_images_directory'), $recipePhoto);
        }

        $tokenStorage->setToken(null);
        if ($request->hasSession()) {
            $request->getSession()->invalidate();
        }

        return $this->redirectToRoute('app_home');
    }

    private function deleteUploadedFile(string $directory, ?string $filename): void
    {
        if (null === $filename || '' === $filename) {
            return;
        }

        $path = $directory.'/'.$filename;
        if (is_file($path)) {
            unlink($path);
        }
    }

}
