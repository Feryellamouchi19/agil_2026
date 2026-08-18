<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom'));
            $prenom = trim($request->request->get('prenom'));
            $telephone = trim($request->request->get('telephone'));
            $password = trim($request->request->get('password'));

            // Managing photo upload
            $photoFile = $request->files->get('photo');
            if ($photoFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = uniqid('profile_') . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move($uploadsDir, $newFilename);
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Impossible de télécharger la photo.');
                }
            }

            if (!empty($nom) && !empty($prenom)) {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setTelephone($telephone);

                if (!empty($password)) {
                    $user->setPassword($hasher->hashPassword($user, $password));
                }

                $em->flush();

                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
