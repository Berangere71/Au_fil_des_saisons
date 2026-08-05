<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Recette;
use App\Entity\Season;
use App\Entity\Favoris;
use App\Enum\RecetteStatut;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $favoriteRecipeIds = array_map(
            static fn (Favoris $favori): ?int => $favori->getRecette()?->getId(),
            $entityManager->getRepository(Favoris::class)->findBy(['user' => $user]),
        );
        $favoriteRecipes = [] === $favoriteRecipeIds
            ? []
            : $entityManager->getRepository(Recette::class)->findBy(
                ['id' => $favoriteRecipeIds, 'statut' => RecetteStatut::PUBLIEE],
                ['createdAt' => 'DESC'],
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
            'publicRecettes' => $favoriteRecipes,
            'myRecettes' => array_values($myRecipesByTitle),
            'seasons' => $entityManager->getRepository(Season::class)->findAll(),
            'favoriteRecipeIds' => $favoriteRecipeIds,
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

}
