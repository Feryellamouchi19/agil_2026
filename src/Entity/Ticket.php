<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: '`tickets`')]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $numeroTicket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Guichet::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Guichet $guichet = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $qrCode = null;

    // Monetary value of the ticket in Tunisian Dinar (TND)
    #[ORM\Column(type: 'float')]
    private ?float $value = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $heureCreation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $heureAppel = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $heureFin = null;

    #[ORM\Column]
    private ?int $tempsEstime = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = 'En attente';

    public function __construct()
    {
        $this->heureCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroTicket(): ?string
    {
        return $this->numeroTicket;
    }

    public function setNumeroTicket(string $numeroTicket): static
    {
        $this->numeroTicket = $numeroTicket;
        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getGuichet(): ?Guichet
    {
        return $this->guichet;
    }

    public function setGuichet(?Guichet $guichet): static
    {
        $this->guichet = $guichet;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): static
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getHeureCreation(): ?\DateTimeImmutable
    {
        return $this->heureCreation;
    }

    public function setHeureCreation(\DateTimeImmutable $heureCreation): static
    {
        $this->heureCreation = $heureCreation;
        return $this;
    }

    public function getHeureAppel(): ?\DateTimeImmutable
    {
        return $this->heureAppel;
    }

    public function setHeureAppel(?\DateTimeImmutable $heureAppel): static
    {
        $this->heureAppel = $heureAppel;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeImmutable
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeImmutable $heureFin): static
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getTempsEstime(): ?int
    {
        return $this->tempsEstime;
    }

    public function setTempsEstime(int $tempsEstime): static
    {
        $this->tempsEstime = $tempsEstime;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }
}
