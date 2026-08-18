<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Simple service to store and retrieve the authenticated user ID during the Google OAuth flow.
 * Uses the Symfony session to keep the state between the "connect" and "callback" actions.
 */
class OAuthStateService
{
    private const SESSION_KEY = 'google_oauth_user_id';
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    private function getSession(): \Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return $this->requestStack->getSession();
    }

    public function storeUserId(int $userId): void
    {
        $this->getSession()->set(self::SESSION_KEY, $userId);
    }

    public function retrieveUserId(): ?int
    {
        $id = $this->getSession()->get(self::SESSION_KEY);
        return $id !== null ? (int) $id : null;
    }

    public function clearUserId(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }
}
