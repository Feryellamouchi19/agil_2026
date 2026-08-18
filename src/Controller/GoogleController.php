<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    /**
     * Redirige l'utilisateur vers Google pour autorisation OAuth.
     */
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Scopes: email et profil pour récupérer les infos de l'utilisateur
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile'
            ]);
    }

    /**
     * Callback Google — cette route est gérée par le GoogleAuthenticator.
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(): Response
    {
        // Cette méthode ne sera jamais exécutée car le GoogleAuthenticator
        // intercepte la requête avant qu'elle n'arrive ici.
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
