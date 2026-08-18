<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestSecurityController extends AbstractController
{
    #[Route('/test-security', name: 'app_test_security')]
    public function test(): Response
    {
        $user = $this->getUser();
        $roles = $user ? $user->getRoles() : [];
        return new Response('User roles: ' . json_encode($roles));
    }
}
?>
