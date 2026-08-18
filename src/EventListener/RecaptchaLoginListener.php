<?php

namespace App\EventListener;

use App\Service\RecaptchaService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Vérifie le reCAPTCHA sur le formulaire de login avant que Symfony traite l'authentification.
 */
class RecaptchaLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private RecaptchaService $recaptchaService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité haute pour s'exécuter AVANT le firewall
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Seulement sur le POST du formulaire de login
        if ($request->getPathInfo() !== '/login' || !$request->isMethod('POST')) {
            return;
        }

        $recaptchaResponse = $request->request->get('g-recaptcha-response');

        if (!$this->recaptchaService->verify($recaptchaResponse)) {
            // Stocker l'erreur en session flash
            $request->getSession()->getFlashBag()->add('recaptcha_error', 'Veuillez compléter le reCAPTCHA.');
            // Sauvegarder le dernier email saisi
            $request->getSession()->set('_security.last_username', $request->request->get('email', ''));

            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
        }
    }
}
