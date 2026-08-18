<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        // Cette route est le callback Google
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // 1. Chercher par googleId (déjà lié)
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['googleId' => $googleId]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2. Chercher par email (compte existant pas encore lié à Google)
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // Lier le compte Google à l'utilisateur existant
                    $existingUser->setGoogleId($googleId);
                    // Ensure the user has appropriate role if email matches known admin/gerant
                    $adminEmails = ['feryel@agil.tn'];
                    $gerantEmails = ['gerant@agil.tn']; // actual gerant email
                    if (in_array(strtolower($email), $adminEmails)) {
                        $existingUser->setRoles(['ROLE_ADMIN']);
                    } elseif (in_array(strtolower($email), $gerantEmails)) {
                        $existingUser->setRoles(['ROLE_GERANT']);
                    }
                    $this->entityManager->flush();
                    return $existingUser;
                }

                // 3. Nouveau utilisateur — créer un compte automatiquement
                $user = new User();
                $user->setEmail($email);
                $user->setGoogleId($googleId);
                $user->setNom($googleUser->getLastName() ?? 'Inconnu');
                $user->setPrenom($googleUser->getFirstName() ?? '');
                // Assign role based on email
                $adminEmails = ['feryel@agil.tn'];
                $gerantEmails = ['gerant@agil.tn']; // actual gerant email
                if (in_array(strtolower($email), $adminEmails)) {
                    $user->setRoles(['ROLE_ADMIN']);
                } elseif (in_array(strtolower($email), $gerantEmails)) {
                    $user->setRoles(['ROLE_GERANT']);
                } else {
                    $user->setRoles(['ROLE_CLIENT']);
                }
                $user->setStatut('Actif');

                // Photo de profil Google
                $avatar = $googleUser->getAvatar();
                if ($avatar) {
                    $user->setPhoto($avatar);
                }

                // Générer un mot de passe aléatoire sécurisé pour éviter les conflits avec form_login
                $user->setPassword(password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT));

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Rediriger selon le rôle (même logique que SecurityController)
        $user = $token->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_GERANT', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        if (in_array('ROLE_AGILISTE', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('app_agiliste_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        $request->getSession()->getFlashBag()->add('danger', 'Erreur de connexion Google : ' . $message);

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
