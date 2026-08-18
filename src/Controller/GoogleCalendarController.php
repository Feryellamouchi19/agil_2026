<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use App\Repository\UserRepository;
use App\Service\OAuthStateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GERANT')]
class GoogleCalendarController extends AbstractController
{
    #[Route('/admin/google/calendar/connect', name: 'connect_google_calendar_start')]
    public function connect(GoogleCalendarService $calendarService, OAuthStateService $oauthState, UserRepository $userRepo): Response
    {
        // Store the current authenticated user's ID in session for later retrieval
        $user = $this->getUser();
        if ($user) {
            $oauthState->storeUserId($user->getId());
        }
        return $this->redirect($calendarService->getAuthorizationUrl());
    }

    #[IsGranted('ROLE_GERANT')]
    #[Route('/connect/google/calendar/check', name: 'connect_google_calendar_check')]
    public function callback(Request $request, GoogleCalendarService $calendarService, EntityManagerInterface $em, OAuthStateService $oauthState, UserRepository $userRepo): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('danger', 'Code de vérification Google manquant.');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        try {
            $tokens = $calendarService->getTokensFromCode($code);
            
            // Retrieve the user ID stored during the connect step
            $userId = $oauthState->retrieveUserId();
            $oauthState->clearUserId();
            /** @var \App\Entity\User|null $user */
            $user = $userId ? $userRepo->find($userId) : null;
            if (!$user) {
                $this->addFlash('danger', 'Utilisateur non trouvé pour la liaison Google Calendar.');
                return $this->redirectToRoute('app_admin_dashboard');
            }

            $user->setGoogleAccessToken($tokens['access_token'] ?? null);
            if (isset($tokens['refresh_token'])) {
                $user->setGoogleRefreshToken($tokens['refresh_token']);
            }
            if (isset($tokens['expires_in'])) {
                $user->setGoogleTokenExpiresAt((new \DateTime())->modify('+' . $tokens['expires_in'] . ' seconds'));
            }

            $em->flush();

            $this->addFlash('success', 'Votre compte Google Calendar a été connecté avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la connexion avec Google Calendar : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/admin/google/calendar/events', name: 'google_calendar_events')]
    public function events(GoogleCalendarService $calendarService, UserRepository $userRepo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Utilisateur non authentifié.');
            return $this->redirectToRoute('app_admin_dashboard');
        }
        $events = $calendarService->fetchEvents($user);
        return $this->render('admin/google_calendar_events.html.twig', [
            'events' => $events,
        ]);
    }
}
