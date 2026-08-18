<?php

namespace App\Controller;

use App\Entity\Guichet;
use App\Entity\HistoriqueConnexion;
use App\Entity\Reclamation;
use App\Entity\RendezVous;
use App\Entity\Service;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\GuichetRepository;
use App\Repository\HistoriqueConnexionRepository;
use App\Repository\ReclamationRepository;
use App\Repository\RendezVousRepository;
use App\Repository\ServiceRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;


class AdminController extends AbstractController
{
    // Access control handled by security.yaml
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        TicketRepository $ticketRepo,
        RendezVousRepository $rvRepo,
        ReclamationRepository $recRepo
    ): Response {
        $todayStart = new \DateTimeImmutable('today midnight');

        $totalClients = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_CLIENT%')
            ->getQuery()->getSingleScalarResult();

        $totalAgilistes = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_AGILISTE%')
            ->getQuery()->getSingleScalarResult();

        $todayTickets = (int) $ticketRepo->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.heureCreation >= :todayStart')
            ->setParameter('todayStart', $todayStart)
            ->getQuery()->getSingleScalarResult();

        $todayFinishedTickets = (int) $ticketRepo->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.heureCreation >= :todayStart')
            ->andWhere('t.statut = :statut')
            ->setParameter('todayStart', $todayStart)
            ->setParameter('statut', 'Terminé')
            ->getQuery()->getSingleScalarResult();

        $todayWaitingTickets = (int) $ticketRepo->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.heureCreation >= :todayStart')
            ->andWhere('t.statut = :statut')
            ->setParameter('todayStart', $todayStart)
            ->setParameter('statut', 'En attente')
            ->getQuery()->getSingleScalarResult();

        $totalRendezVous = (int) $rvRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()->getSingleScalarResult();

        $totalReclamations = (int) $recRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'totalClients' => $totalClients,
            'totalAgilistes' => $totalAgilistes,
            'todayTickets' => $todayTickets,
            'todayFinishedTickets' => $todayFinishedTickets,
            'todayWaitingTickets' => $todayWaitingTickets,
            'totalRendezVous' => $totalRendezVous,
            'totalReclamations' => $totalReclamations,
        ]);
    }

    // GESTION UTILISATEURS (Admin uniquement)
    #[Route('/admin/users', name: 'app_admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function usersList(Request $request, UserRepository $userRepo): Response
    {
        $q = trim($request->query->get('q', ''));
        $role = trim($request->query->get('role', ''));
        $statut = trim($request->query->get('statut', ''));

        $qb = $userRepo->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC');

        if (!empty($q)) {
            $qb->andWhere('u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q OR u.telephone LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($role)) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%' . $role . '%');
        }

        if (!empty($statut)) {
            $qb->andWhere('u.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $this->render('admin/users.html.twig', [
            'users' => $qb->getQuery()->getResult(),
            'q' => $q,
            'role' => $role,
            'statut' => $statut,
        ]);
    }

    #[Route('/admin/users/new', name: 'app_admin_user_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function newUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email'));
            $nom = trim($request->request->get('nom'));
            $prenom = trim($request->request->get('prenom'));
            $telephone = trim($request->request->get('telephone'));
            $role = $request->request->get('role');
            $pass = $request->request->get('password');

            if (!empty($email) && !empty($nom) && !empty($pass)) {
                $user = new User();
                $user->setEmail($email);
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setTelephone($telephone);
                $user->setRoles([$role]);
                $user->setPassword($hasher->hashPassword($user, $pass));

                $photoFile = $request->files->get('photo');
                if ($photoFile) {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }
                    $newFilename = uniqid('profile_') . '.' . $photoFile->guessExtension();
                    $photoFile->move($uploadsDir, $newFilename);
                    $user->setPhoto($newFilename);
                }

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Utilisateur créé avec succès.');
                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/user_form.html.twig');
    }

    #[Route('/admin/users/{id}', name: 'app_admin_user_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function showUser(User $user): Response
    {
        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/toggle', name: 'app_admin_user_toggle')]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleUserStatus(User $user, EntityManagerInterface $em): Response
    {
        $newStatus = ($user->getStatut() === 'Actif') ? 'Inactif' : 'Actif';
        $user->setStatut($newStatus);
        $em->flush();

        $this->addFlash('info', 'Le statut de ' . $user->getEmail() . ' est maintenant ' . $newStatus);

        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/admin/users/{id}/edit', name: 'app_admin_user_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email'));
            $nom = trim($request->request->get('nom'));
            $prenom = trim($request->request->get('prenom'));
            $telephone = trim($request->request->get('telephone'));
            $role = $request->request->get('role');
            $pass = trim($request->request->get('password'));

            if (!empty($email) && !empty($nom)) {
                $user->setEmail($email);
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setTelephone($telephone);
                $user->setRoles([$role]);

                if (!empty($pass)) {
                    $user->setPassword($hasher->hashPassword($user, $pass));
                }

                $photoFile = $request->files->get('photo');
                if ($photoFile) {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }
                    $newFilename = uniqid('profile_') . '.' . $photoFile->guessExtension();
                    $photoFile->move($uploadsDir, $newFilename);
                    $user->setPhoto($newFilename);
                }

                $em->flush();

                $this->addFlash('success', 'Utilisateur modifié avec succès.');
                return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST', 'GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser->getId() === $user->getId()) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte !');
            return $this->redirectToRoute('app_admin_users');
        }

        $email = $user->getEmail();
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'L\'utilisateur ' . $email . ' a été supprimé.');

        return $this->redirectToRoute('app_admin_users');
    }

    // GESTION GUICHETS
    #[Route('/admin/guichets', name: 'app_admin_guichets')]
    public function guichetsList(Request $request, GuichetRepository $guichetRepo, UserRepository $userRepo, ServiceRepository $serviceRepo): Response
    {
        $q = trim($request->query->get('q', ''));
        $statut = trim($request->query->get('statut', ''));
        $serviceId = $request->query->get('service_id', '');

        $qb = $guichetRepo->createQueryBuilder('g')
            ->leftJoin('g.agiliste', 'a')
            ->leftJoin('g.typeService', 's')
            ->orderBy('g.numero', 'ASC');

        if (!empty($q)) {
            $qb->andWhere('g.numero LIKE :q OR a.nom LIKE :q OR a.prenom LIKE :q OR s.nomService LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($statut)) {
            $qb->andWhere('g.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if (!empty($serviceId)) {
            $qb->andWhere('s.id = :serviceId')
               ->setParameter('serviceId', $serviceId);
        }

        $agilistes = $userRepo->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_AGILISTE%')
            ->getQuery()->getResult();

        return $this->render('admin/guichets.html.twig', [
            'guichets' => $qb->getQuery()->getResult(),
            'agilistes' => $agilistes,
            'services' => $serviceRepo->findAll(),
            'q' => $q,
            'statut' => $statut,
            'serviceId' => $serviceId,
        ]);
    }

    #[Route('/admin/guichets/update', name: 'app_admin_guichet_update')]
    public function updateGuichet(Request $request, GuichetRepository $guichetRepo, UserRepository $userRepo, ServiceRepository $serviceRepo, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $guichetId = $request->request->get('guichet_id');
            $agilisteId = $request->request->get('agiliste_id');
            $serviceId = $request->request->get('service_id');
            $statut = $request->request->get('statut');

            $guichet = $guichetRepo->find($guichetId);
            if ($guichet) {
                $guichet->setAgiliste($agilisteId ? $userRepo->find($agilisteId) : null);
                $guichet->setTypeService($serviceId ? $serviceRepo->find($serviceId) : null);
                $guichet->setStatut($statut);
                $em->flush();

                $this->addFlash('success', 'Guichet ' . $guichet->getNumero() . ' mis à jour.');
            }
        }

        return $this->redirectToRoute('app_admin_guichets');
    }

    // GESTION RECLAMATIONS (Gérant / Admin)
    #[Route('/admin/reclamations', name: 'app_admin_reclamations')]
    public function reclamationsList(Request $request, ReclamationRepository $recRepo): Response
    {
        $q = trim($request->query->get('q', ''));
        $statut = trim($request->query->get('statut', ''));

        $qb = $recRepo->createQueryBuilder('r')
            ->leftJoin('r.client', 'c')
            ->orderBy('r.dateCreation', 'DESC');

        if (!empty($q)) {
            $qb->andWhere('r.sujet LIKE :q OR r.description LIKE :q OR c.nom LIKE :q OR c.prenom LIKE :q OR c.email LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($statut)) {
            $qb->andWhere('r.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $this->render('admin/reclamations.html.twig', [
            'reclamations' => $qb->getQuery()->getResult(),
            'q' => $q,
            'statut' => $statut,
        ]);
    }

    #[Route('/admin/reclamations/{id}/reply', name: 'app_admin_reclamation_reply')]
    public function replyReclamation(Reclamation $rec, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $reponse = trim($request->request->get('reponse'));
            $statut = $request->request->get('statut');

            $rec->setReponse($reponse);
            $rec->setStatut($statut);
            $em->flush();

            $this->addFlash('success', 'Réponse enregistrée pour la réclamation.');
            return $this->redirectToRoute('app_admin_reclamations');
        }

        return $this->render('admin/reclamation_reply.html.twig', [
            'reclamation' => $rec,
        ]);
    }

    // GESTION RENDEZ-VOUS (Gérant / Admin)
    #[Route('/admin/rendezvous', name: 'app_admin_rendezvous')]
    public function rendezVousList(Request $request, RendezVousRepository $rvRepo): Response
    {
        $q = trim($request->query->get('q', ''));
        $statut = trim($request->query->get('statut', ''));

        $qb = $rvRepo->createQueryBuilder('rv')
            ->leftJoin('rv.client', 'c')
            ->orderBy('rv.dateRv', 'ASC')
            ->addOrderBy('rv.heureRv', 'ASC');

        if (!empty($q)) {
            $qb->andWhere('rv.sujet LIKE :q OR rv.commentaire LIKE :q OR c.nom LIKE :q OR c.prenom LIKE :q OR c.email LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($statut)) {
            $qb->andWhere('rv.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $this->render('admin/rendezvous.html.twig', [
            'rendezvousList' => $qb->getQuery()->getResult(),
            'q' => $q,
            'statut' => $statut,
        ]);
    }

    #[Route('/admin/rendezvous/{id}/{status}', name: 'app_admin_rv_status')]
    public function updateRvStatus(RendezVous $rv, string $status, EntityManagerInterface $em, GoogleCalendarService $calendarService): Response
    {
        if (in_array($status, ['Accepté', 'Refusé', 'Terminé'])) {
            $rv->setStatut($status);
            $rv->setGerant($this->getUser());
            $em->flush();

            $this->addFlash('info', 'Le rendez-vous a été marqué comme : ' . $status);

            if ($status === 'Accepté') {
                /** @var \App\Entity\User $gerant */
                $gerant = $this->getUser();
                if ($gerant->getGoogleRefreshToken()) {
                    if ($calendarService->createGoogleCalendarEvent($rv)) {
                        $this->addFlash('success', 'L\'évènement a été ajouté à votre Google Calendar.');
                    } else {
                        $this->addFlash('warning', 'Impossible de synchroniser avec Google Calendar.');
                    }
                } else {
                    $this->addFlash('info', 'Connectez votre compte Google Calendar pour planifier automatiquement cet entretien.');
                }
            }
        }

        return $this->redirectToRoute('app_admin_rendezvous');
    }

    // JOURNAUX DE CONNEXION (Logs)
    #[Route('/admin/logs', name: 'app_admin_logs')]
    #[IsGranted('ROLE_ADMIN')]
    public function logsList(Request $request, HistoriqueConnexionRepository $logRepo): Response
    {
        $q = trim($request->query->get('q', ''));
        $statut = trim($request->query->get('statut', ''));

        $qb = $logRepo->createQueryBuilder('l')
            ->orderBy('l.dateConnexion', 'DESC')
            ->setMaxResults(100);

        if (!empty($q)) {
            $qb->andWhere('l.email LIKE :q OR l.adresseIp LIKE :q OR l.userAgent LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if (!empty($statut)) {
            $qb->andWhere('l.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $this->render('admin/logs.html.twig', [
            'logs' => $qb->getQuery()->getResult(),
            'q' => $q,
            'statut' => $statut,
        ]);
    }
}
