<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Jeśli już zalogowany, przekieruj do dashboardu
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Pobierz błąd logowania jeśli był
        $error = $authenticationUtils->getLastAuthenticationError();

        // Ostatnia wprowadzona nazwa użytkownika
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Ten kontroler może być pusty - zostanie obsłużony przez Symfony Security
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
