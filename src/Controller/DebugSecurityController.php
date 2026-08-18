<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DebugSecurityController extends AbstractController
{
    #[Route('/debug-security', name: 'app_debug_security')]
    public function debug(TokenStorageInterface $tokenStorage, AuthenticationTrustResolverInterface $trustResolver): Response
    {
        $token = $tokenStorage->getToken();
        if (!$token) {
            return new Response('No token (anonymous)');
        }
        $user = $token->getUser();
        $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];
        $full = $trustResolver->isFullFledged($token) ? 'yes' : 'no';
        $data = [
            'username' => $user instanceof \Stringable ? (string) $user : get_class($user),
            'roles' => $roles,
            'fullAuthenticated' => $full,
        ];
        return new Response('Debug: '.json_encode($data));
    }
}
?>
