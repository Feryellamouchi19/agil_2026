<?php

namespace App\Service;

use App\Entity\Guichet;
use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;

class QueueManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Génère un numéro de ticket séquentiel global pour la journée (T001, T002...).
     * Le numéro est unique par jour, indépendant du service demandé.
     */
    public function generateTicketNumber(): string
    {
        $todayStart = new \DateTimeImmutable('today midnight');
        $todayEnd   = new \DateTimeImmutable('today 23:59:59');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.heureCreation >= :todayStart')
            ->andWhere('t.heureCreation <= :todayEnd')
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd);

        $count   = (int) $qb->getQuery()->getSingleScalarResult();
        $nextNum = $count + 1;

        return sprintf('T%03d', $nextNum);
    }

    /**
     * Calcule le temps d'attente estimé en minutes basé sur la file globale.
     * On prend le nombre total de tickets "En attente" / nombre de guichets ouverts.
     */
    public function calculateEstimatedWaitTime(): int
    {
        $todayStart = new \DateTimeImmutable('today midnight');

        // Nombre total de tickets en attente aujourd'hui (file globale)
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.statut = :statut')
            ->andWhere('t.heureCreation >= :todayStart')
            ->setParameter('statut', 'En attente')
            ->setParameter('todayStart', $todayStart);

        $peopleAhead = (int) $qb->getQuery()->getSingleScalarResult();

        // Nombre de guichets ouverts (tous services confondus)
        $qbG = $this->entityManager->createQueryBuilder();
        $qbG->select('COUNT(g.id)')
            ->from(Guichet::class, 'g')
            ->where('g.statut = :statut')
            ->setParameter('statut', 'Ouvert');

        $openGuichets = (int) $qbG->getQuery()->getSingleScalarResult();
        if ($openGuichets === 0) {
            $openGuichets = 1;
        }

        // Durée moyenne fixe de 5 minutes par ticket
        $avgDuration = 5;

        return (int) ceil(($peopleAhead * $avgDuration) / $openGuichets);
    }

    /**
     * Calcule la position d'un ticket dans la file d'attente GLOBALE.
     * Position = nombre de tickets "En attente" créés avant ce ticket.
     */
    public function getQueuePosition(Ticket $ticket): int
    {
        if ($ticket->getStatut() !== 'En attente') {
            return 0;
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.statut = :statut')
            ->andWhere('t.heureCreation <= :ticketTime')
            ->andWhere('t.id != :ticketId')
            ->setParameter('statut', 'En attente')
            ->setParameter('ticketTime', $ticket->getHeureCreation())
            ->setParameter('ticketId', $ticket->getId());

        // Position = nombre de personnes devant lui + 1
        return (int) $qb->getQuery()->getSingleScalarResult() + 1;
    }

    /**
     * Trouve le prochain ticket à appeler dans la file globale (FIFO).
     * Retourne le ticket "En attente" le plus ancien du jour.
     */
    public function getNextWaitingTicket(): ?Ticket
    {
        $todayStart = new \DateTimeImmutable('today midnight');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
            ->from(Ticket::class, 't')
            ->where('t.statut = :statut')
            ->andWhere('t.heureCreation >= :todayStart')
            ->setParameter('statut', 'En attente')
            ->setParameter('todayStart', $todayStart)
            ->orderBy('t.heureCreation', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve tous les tickets en attente dans la file globale, triés par heure de création.
     */
    public function getGlobalWaitingQueue(): array
    {
        $todayStart = new \DateTimeImmutable('today midnight');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
            ->from(Ticket::class, 't')
            ->leftJoin('t.client', 'c')
            ->leftJoin('t.service', 's')
            ->where('t.statut = :statut')
            ->andWhere('t.heureCreation >= :todayStart')
            ->setParameter('statut', 'En attente')
            ->setParameter('todayStart', $todayStart)
            ->orderBy('t.heureCreation', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
