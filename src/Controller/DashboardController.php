<?php

namespace App\Controller;

use App\Repository\ReclamationRepository;
use App\Repository\RendezVousRepository;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        TicketRepository $ticketRepo,
        RendezVousRepository $rvRepo,
        ReclamationRepository $recRepo
    ): Response {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_GERANT')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        if ($this->isGranted('ROLE_AGILISTE')) {
            return $this->redirectToRoute('app_agiliste_dashboard');
        }

        /** @var \App\Entity\User $client */
        $client = $this->getUser();

        // Vos tickets actifs
        $activeTickets = $ticketRepo->createQueryBuilder('t')
            ->where('t.client = :client')
            ->andWhere('t.statut IN (:statuts)')
            ->setParameter('client', $client)
            ->setParameter('statuts', ['En attente', 'Appelé', 'En cours'])
            ->orderBy('t.heureCreation', 'DESC')
            ->getQuery()
            ->getResult();

        // Historique des tickets
        $pastTickets = $ticketRepo->createQueryBuilder('t')
            ->where('t.client = :client')
            ->andWhere('t.statut IN (:statuts)')
            ->setParameter('client', $client)
            ->setParameter('statuts', ['Terminé', 'Annulé'])
            ->orderBy('t.heureCreation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Vos rendez-vous
        $rendezvousList = $rvRepo->findBy(['client' => $client], ['dateRv' => 'DESC']);

        // Vos réclamations
        $reclamations = $recRepo->findBy(['client' => $client], ['dateCreation' => 'DESC']);

        return $this->render('dashboard/index.html.twig', [
            'activeTickets' => $activeTickets,
            'pastTickets' => $pastTickets,
            'rendezvousList' => $rendezvousList,
            'reclamations' => $reclamations,
        ]);
    }
}
