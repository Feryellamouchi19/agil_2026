<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\RendezVous;
use App\Entity\Service;
use App\Entity\Ticket;
use App\Repository\ServiceRepository;
use App\Repository\TicketRepository;
use App\Service\QueueManager;
use App\Service\QrCodeGenerator;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ClientController extends AbstractController
{
    #[Route('/client/ticket/new', name: 'app_client_take_ticket')]
    public function takeTicket(
        Request $request,
        ServiceRepository $serviceRepo,
        EntityManagerInterface $em,
        QueueManager $queueManager,
        QrCodeGenerator $qrGenerator,
        SmsService $smsService
    ): Response {
        $services = $serviceRepo->findAll();

        if ($request->isMethod('POST')) {
            $serviceId = $request->request->get('service_id');
            $service   = $serviceRepo->find($serviceId);

            if (!$service) {
                $this->addFlash('danger', 'Service invalide.');
                return $this->redirectToRoute('app_client_take_ticket');
            }

            /** @var \App\Entity\User $client */
            $client = $this->getUser();

            // Numéro de ticket global (T001, T002...)
            $numero      = $queueManager->generateTicketNumber();
            // Temps d'attente estimé basé sur la file globale
            $tempsEstime = $queueManager->calculateEstimatedWaitTime();

            $ticket = new Ticket();
            $ticket->setClient($client);
            $ticket->setService($service);   // service = besoin du client (informatif)
            $ticket->setGuichet(null);        // le guichet sera assigné quand l'agiliste appelle
            $ticket->setNumeroTicket($numero);
            $ticket->setTempsEstime($tempsEstime);
            $ticket->setStatut('En attente');

            // Si c'est un Bon valeur, capturer la valeur saisie par le client
            if (strtoupper($service->getType()) === 'BON_VALEUR') {
                $bonValue = (float) $request->request->get('bon_value', 0);
                $ticket->setValue(max(0.0, $bonValue));
            } else {
                $ticket->setValue(0.0);
            }

            $em->persist($ticket);
            $em->flush();

            // QR Code contenant l'URL de suivi (accessible en réseau local)
            $trackUrl  = $this->buildPublicTrackUrl($request, $ticket->getNumeroTicket());
            $qrDataUri = $qrGenerator->generateDataUri($trackUrl);
            $ticket->setQrCode($qrDataUri);

            $em->flush();

            // Envoi du SMS au client
            $smsService->sendTicketSms($ticket);

            $this->addFlash('success', 'Votre ticket ' . $numero . ' a été créé ! Vous êtes en position ' . $queueManager->getQueuePosition($ticket) . ' dans la file.');

            return $this->redirectToRoute('app_client_ticket_view', ['id' => $ticket->getId()]);
        }

        return $this->render('client/take_ticket.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/client/ticket/{id}', name: 'app_client_ticket_view')]
    public function viewTicket(
        Ticket $ticket,
        QueueManager $queueManager,
        QrCodeGenerator $qrGenerator,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        /** @var \App\Entity\User $client */
        $client = $this->getUser();

        if ($ticket->getClient() !== $client && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès non autorisé.');
        }

        // Si le QR code est manquant ou contient 127.0.0.1 (non accessible par le téléphone), on le régénère avec l'IP réseau local
        $trackUrl = $this->buildPublicTrackUrl($request, $ticket->getNumeroTicket());
        $qrDataUri = $qrGenerator->generateDataUri($trackUrl);
        $ticket->setQrCode($qrDataUri);
        $em->flush();

        $position = $queueManager->getQueuePosition($ticket);

        return $this->render('client/ticket_view.html.twig', [
            'ticket' => $ticket,
            'position' => $position,
        ]);
    }

    private function buildPublicTrackUrl(Request $request, string $numeroTicket): string
    {
        $host = $request->getHttpHost();
        if (str_contains($host, '127.0.0.1') || str_contains($host, 'localhost') || str_contains($host, '192.168.64.')) {
            $lanIp = $this->getRealLocalIp();
            if ($lanIp && $lanIp !== '127.0.0.1') {
                $port = $request->getPort();
                $host = $lanIp . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
            }
        }
        return $request->getScheme() . '://' . $host . '/ticket/track/' . $numeroTicket;
    }

    private function getRealLocalIp(): string
    {
        $client = @stream_socket_client('udp://8.8.8.8:53', $errno, $errstr, 1);
        if ($client) {
            $name = stream_socket_get_name($client, false);
            @fclose($client);
            if ($name) {
                $parts = explode(':', $name);
                if (!empty($parts[0]) && $parts[0] !== '127.0.0.1') {
                    return $parts[0];
                }
            }
        }

        $output = @shell_exec('ipconfig');
        if ($output) {
            preg_match_all('/IPv4 Address[^\:]*:\s*([0-9\.]+)/i', $output, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $ip) {
                    if ($ip !== '127.0.0.1' && !str_starts_with($ip, '169.254') && !str_starts_with($ip, '192.168.64.')) {
                        if (str_starts_with($ip, '192.168.1.') || str_starts_with($ip, '192.168.0.')) {
                            return $ip;
                        }
                    }
                }
                foreach ($matches[1] as $ip) {
                    if ($ip !== '127.0.0.1' && !str_starts_with($ip, '169.254')) {
                        return $ip;
                    }
                }
            }
        }

        return gethostbyname(gethostname());
    }

    #[Route('/client/ticket/{id}/pdf', name: 'app_client_ticket_pdf')]
    public function downloadPdf(Ticket $ticket): Response
    {
        /** @var \App\Entity\User $client */
        $client = $this->getUser();

        if ($ticket->getClient() !== $client && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès non autorisé.');
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('client/ticket_pdf.html.twig', [
            'ticket' => $ticket,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 300, 500], 'portrait'); // Taille ticket de caisse
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Ticket_' . $ticket->getNumeroTicket() . '.pdf"',
        ]);
    }

    #[Route('/ticket/track/{numeroTicket}', name: 'app_ticket_track')]
    public function trackPublic(
        string $numeroTicket,
        TicketRepository $ticketRepo,
        QueueManager $queueManager
    ): Response {
        $ticket = $ticketRepo->findOneBy(['numeroTicket' => $numeroTicket]);

        if (!$ticket) {
            throw $this->createNotFoundException('Ticket introuvable.');
        }

        $position = $queueManager->getQueuePosition($ticket);

        return $this->render('client/track_public.html.twig', [
            'ticket' => $ticket,
            'position' => $position,
        ]);
    }

    #[Route('/client/rendezvous/new', name: 'app_client_rv_new')]
    public function newRendezVous(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $sujet = trim($request->request->get('sujet'));
            $dateStr = $request->request->get('date_rv');
            $heureStr = $request->request->get('heure_rv');
            $commentaire = trim($request->request->get('commentaire'));

            if (!empty($sujet) && !empty($dateStr) && !empty($heureStr)) {
                $rv = new RendezVous();
                $rv->setClient($this->getUser());
                $rv->setSujet($sujet);
                $rv->setDateRv(new \DateTime($dateStr));
                $rv->setHeureRv(new \DateTime($heureStr));
                $rv->setCommentaire($commentaire);
                $rv->setStatut('En attente');

                $em->persist($rv);
                $em->flush();

                $this->addFlash('success', 'Votre demande de rendez-vous a été envoyée.');
                return $this->redirectToRoute('app_dashboard');
            } else {
                $this->addFlash('danger', 'Veuillez remplir tous les champs obligatoires.');
            }
        }

        return $this->render('client/rv_new.html.twig');
    }

    #[Route('/client/reclamation/new', name: 'app_client_reclamation_new')]
    public function newReclamation(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $sujet = trim($request->request->get('sujet'));
            $description = trim($request->request->get('description'));

            if (!empty($sujet) && !empty($description)) {
                $rec = new Reclamation();
                $rec->setClient($this->getUser());
                $rec->setSujet($sujet);
                $rec->setDescription($description);
                $rec->setStatut('Ouvert');

                $em->persist($rec);
                $em->flush();

                $this->addFlash('success', 'Votre réclamation a été soumise avec succès.');
                return $this->redirectToRoute('app_dashboard');
            } else {
                $this->addFlash('danger', 'Veuillez remplir les champs obligatoires.');
            }
        }

        return $this->render('client/reclamation_new.html.twig');
    }
}
