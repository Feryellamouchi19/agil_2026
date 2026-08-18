<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\RendezVous;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleCalendarService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $router,
        private string $googleClientId = '',
        private string $googleClientSecret = ''
    ) {
        $this->googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? $googleClientId;
        $this->googleClientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? $googleClientSecret;
    }

    /**
     * Obtenir l'URL d'autorisation OAuth pour le calendrier Google
     */
    public function getAuthorizationUrl(): string
    {
        $redirectUri = $this->router->generate('connect_google_calendar_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $params = [
            'client_id' => $this->googleClientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events email profile',
            'access_type' => 'offline',
            'prompt' => 'consent' // Force Google à renvoyer un refresh_token
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Échanger le code d'autorisation contre des jetons d'accès et de rafraîchissement
     */
    public function getTokensFromCode(string $code): array
    {
        $redirectUri = $this->router->generate('connect_google_calendar_check', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $this->googleClientId,
                'client_secret' => $this->googleClientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ],
        ]);

        try {
            return $response->toArray();
        } catch (\Exception $e) {
            $content = $response->getContent(false);
            throw new \Exception('Erreur Google OAuth : ' . $content);
        }
    }

    /**
     * Rafraîchir le jeton d'accès si expiré
     */
    public function refreshAccessToken(User $user): ?string
    {
        $refreshToken = $user->getGoogleRefreshToken();
        if (!$refreshToken) {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->googleClientId,
                    'client_secret' => $this->googleClientSecret,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $data = $response->toArray();
            
            $user->setGoogleAccessToken($data['access_token']);
            if (isset($data['expires_in'])) {
                $user->setGoogleTokenExpiresAt((new \DateTime())->modify('+' . $data['expires_in'] . ' seconds'));
            }
            
            $this->em->flush();

            return $data['access_token'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir un jeton d'accès valide pour le Gérant
     */
    public function getValidAccessToken(User $user): ?string
    {
        $expiresAt = $user->getGoogleTokenExpiresAt();
        
        // Si le jeton expire dans moins de 60 secondes, on le rafraîchit
        if (!$expiresAt || $expiresAt->getTimestamp() < (time() + 60)) {
            return $this->refreshAccessToken($user);
        }

        return $user->getGoogleAccessToken();
    }

    /**
    * Retrieve upcoming events from the user's Google Calendar.
    * Returns an array of event data (summary, start, end).
    */
    public function fetchEvents(User $user, int $maxResults = 10): array
    {
        $accessToken = $this->getValidAccessToken($user);
        if (!$accessToken) {
            return [];
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
        $query = http_build_query([
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => 'true',
            'timeMin' => (new \DateTime())->format(\DateTime::RFC3339),
        ]);

        try {
            $response = $this->httpClient->request('GET', $url . '?' . $query, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);
            $data = $response->toArray();
            return $data['items'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Créer un évènement dans le calendrier Google du Gérant
     */
    public function createGoogleCalendarEvent(RendezVous $rv): bool
    {
        $gerant = $rv->getGerant();
        if (!$gerant) {
            return false;
        }

        $accessToken = $this->getValidAccessToken($gerant);
        if (!$accessToken) {
            return false;
        }

        // Préparation des dates au format ISO 8601 (ex: 2026-08-17T11:00:00)
        $dateStr = $rv->getDateRv()->format('Y-m-d');
        $heureStr = $rv->getHeureRv()->format('H:i:s');
        
        $startDateTime = new \DateTime($dateStr . ' ' . $heureStr, new \DateTimeZone('Africa/Tunis'));
        
        // Durée par défaut de l'entretien : 30 minutes
        $endDateTime = clone $startDateTime;
        $endDateTime->modify('+30 minutes');

        $eventData = [
            'summary' => 'RDV Client AGIL : ' . $rv->getSujet(),
            'description' => sprintf(
                "Rendez-vous planifié avec le client.\nClient : %s %s\nEmail : %s\nCommentaire : %s",
                $rv->getClient()->getPrenom(),
                $rv->getClient()->getNom(),
                $rv->getClient()->getEmail(),
                $rv->getCommentaire() ?? 'Aucun commentaire'
            ),
            'start' => [
                'dateTime' => $startDateTime->format(\DateTime::RFC3339),
                'timeZone' => 'Africa/Tunis',
            ],
            'end' => [
                'dateTime' => $endDateTime->format(\DateTime::RFC3339),
                'timeZone' => 'Africa/Tunis',
            ],
            'attendees' => [
                ['email' => $rv->getClient()->getEmail()]
            ],
            'reminders' => [
                'useDefault' => true,
            ]
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $eventData,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
