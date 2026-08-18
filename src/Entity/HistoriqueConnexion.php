<?php

namespace App\Entity;

use App\Repository\HistoriqueConnexionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueConnexionRepository::class)]
#[ORM\Table(name: '`historique_connexion`')]
class HistoriqueConnexion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $adresseIp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $navigateur = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateConnexion = null;

    public function __construct()
    {
        $this->dateConnexion = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getAdresseIp(): ?string
    {
        return $this->adresseIp;
    }

    public function setAdresseIp(?string $adresseIp): static
    {
        $this->adresseIp = $adresseIp;
        return $this;
    }

    public function getNavigateur(): ?string
    {
        return $this->navigateur;
    }

    public function setNavigateur(?string $navigateur): static
    {
        $this->navigateur = $navigateur;
        return $this;
    }

    public function getDateConnexion(): ?\DateTimeImmutable
    {
        return $this->dateConnexion;
    }

    public function setDateConnexion(\DateTimeImmutable $dateConnexion): static
    {
        $this->dateConnexion = $dateConnexion;
        return $this;
    }
}
