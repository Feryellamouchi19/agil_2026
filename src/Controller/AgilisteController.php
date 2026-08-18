<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\GuichetRepository;
use App\Repository\TicketRepository;
use App\Service\PointsManager;
use App\Service\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGILISTE')]
class AgilisteController extends AbstractController
{
    #[Route('/agiliste', name: 'app_agiliste_dashboard')]
    public function index(
        GuichetRepository $guichetRepo,
        TicketRepository $ticketRepo,
        QueueManager $queueManager
    ): Response {
        /** @var \App\Entity\User $agiliste */
        $agiliste = $this->getUser();
        $guichet  = $guichetRepo->findOneBy(['agiliste' => $agiliste]);

        if (!$guichet) {
            return $this->render('agiliste/index.html.twig', [
                'guichet'         => null,
                'waitingTickets'  => [],
                'currentTicket'   => null,
                'todayServedCount'=> 0,
                'avgDuration'     => 0,
                'totalWaiting'    => 0,
            ]);
        }

        $todayStart = new \DateTimeImmutable('today midnight');

        // ── File d'attente GLOBALE (tous services confondus, triée par heure) ──
        $waitingTickets = $queueManager->getGlobalWaitingQueue();

        // ── Ticket actuellement en cours à CE guichet ──
        $currentTicket = $ticketRepo->createQueryBuilder('t')
            ->where('t.guichet = :guichet')
            ->andWhere('t.statut IN (:statuts)')
            ->setParameter('guichet', $guichet)
            ->setParameter('statuts', ['Appelé', 'En cours'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // ── Statistiques du jour pour cet agiliste ──
        $todayServedCount = (int) $ticketRepo->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.guichet = :guichet')
            ->andWhere('t.statut = :statut')
            ->andWhere('t.heureCreation >= :todayStart')
            ->setParameter('guichet', $guichet)
            ->setParameter('statut', 'Terminé')
            ->setParameter('todayStart', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        // ── Temps moyen de traitement (en minutes) ──
        $finishedTickets = $ticketRepo->createQueryBuilder('t')
            ->where('t.guichet = :guichet')
            ->andWhere('t.statut = :statut')
            ->andWhere('t.heureCreation >= :todayStart')
            ->setParameter('guichet', $guichet)
            ->setParameter('statut', 'Terminé')
            ->setParameter('todayStart', $todayStart)
            ->getQuery()
            ->getResult();

        $totalDurationSeconds = 0;
        $count = count($finishedTickets);
        foreach ($finishedTickets as $t) {
            if ($t->getHeureAppel() && $t->getHeureFin()) {
                $totalDurationSeconds += ($t->getHeureFin()->getTimestamp() - $t->getHeureAppel()->getTimestamp());
            }
        }
        $avgDuration = $count > 0 ? (int) round(($totalDurationSeconds / $count) / 60) : 0;

        return $this->render('agiliste/index.html.twig', [
            'guichet'          => $guichet,
            'waitingTickets'   => $waitingTickets,
            'currentTicket'    => $currentTicket,
            'todayServedCount' => $todayServedCount,
            'avgDuration'      => $avgDuration,
            'totalWaiting'     => count($waitingTickets),
        ]);
    }

    #[Route('/agiliste/call-next', name: 'app_agiliste_call_next')]
    public function callNext(
        GuichetRepository $guichetRepo,
        QueueManager $queueManager,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $agiliste */
        $agiliste = $this->getUser();
        $guichet  = $guichetRepo->findOneBy(['agiliste' => $agiliste]);

        if (!$guichet || $guichet->getStatut() !== 'Ouvert') {
            $this->addFlash('warning', 'Votre guichet doit être ouvert pour appeler un client.');
            return $this->redirectToRoute('app_agiliste_dashboard');
        }

        // ── Prendre le prochain ticket de la file GLOBALE ──
        $nextTicket = $queueManager->getNextWaitingTicket();

        if ($nextTicket) {
            $nextTicket->setGuichet($guichet);
            $nextTicket->setStatut('Appelé');
            $nextTicket->setHeureAppel(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Ticket ' . $nextTicket->getNumeroTicket() . ' appelé au guichet ' . $guichet->getNumero() . ' !');
        } else {
            $this->addFlash('info', 'La file d\'attente est vide. Aucun client à appeler.');
        }

        return $this->redirectToRoute('app_agiliste_dashboard');
    }

    #[Route('/agiliste/action/{id}/{action}', name: 'app_agiliste_ticket_action')]
    public function ticketAction(Ticket $ticket, string $action, EntityManagerInterface $em, PointsManager $pointsManager): Response
    {
        switch ($action) {
            case 'start':
                $ticket->setStatut('En cours');
                $this->addFlash('info', 'Traitement du ticket ' . $ticket->getNumeroTicket() . ' démarré.');
                break;
            case 'finish':
                $ticket->setStatut('Terminé');
                $ticket->setHeureFin(new \DateTimeImmutable());
                $em->flush(); // flush before points so IDs are set
                $pointsManager->handleFinishedTicket($ticket);
                $this->addFlash('success', 'Ticket ' . $ticket->getNumeroTicket() . ' terminé avec succès.');
                break;
            case 'hold':
                // Remettre en attente : libérer le guichet
                $ticket->setStatut('En attente');
                $ticket->setGuichet(null);
                $ticket->setHeureAppel(null);
                $this->addFlash('warning', 'Ticket ' . $ticket->getNumeroTicket() . ' remis en fin de file d\'attente.');
                break;
            case 'cancel':
                $ticket->setStatut('Annulé');
                $ticket->setHeureFin(new \DateTimeImmutable());
                $this->addFlash('danger', 'Ticket ' . $ticket->getNumeroTicket() . ' annulé.');
                break;
        }

        $em->flush();

        return $this->redirectToRoute('app_agiliste_dashboard');
    }

    #[Route('/agiliste/toggle-guichet', name: 'app_agiliste_toggle_guichet')]
    public function toggleGuichet(GuichetRepository $guichetRepo, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $agiliste */
        $agiliste = $this->getUser();
        $guichet  = $guichetRepo->findOneBy(['agiliste' => $agiliste]);

        if ($guichet) {
            $newStatut = ($guichet->getStatut() === 'Ouvert') ? 'Fermé' : 'Ouvert';
            $guichet->setStatut($newStatut);
            $em->flush();

            $this->addFlash('info', 'Guichet ' . $guichet->getNumero() . ' est maintenant : ' . $newStatut);
        }

        return $this->redirectToRoute('app_agiliste_dashboard');
    }
}
