<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/login/redirect', name: 'app_login_redirect')]
    public function loginRedirect(UserRepository $userRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if ($user instanceof User) {
            $reportedContentCount = $userRepository->countReportedContentForUser($user);
            if ($reportedContentCount > 0) {
                $this->addFlash(
                    'warning',
                    sprintf(
                        'Avertissement: votre compte est associé à %d contenu%s signalé%s. En cas de nouveau signalement, vous pourrez être banni du site.',
                        $reportedContentCount,
                        $reportedContentCount > 1 ? 's' : '',
                        $reportedContentCount > 1 ? 's' : '',
                    ),
                );
            }
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    
}
