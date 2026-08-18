<?php

namespace App\Entity;

use App\Repository\GuichetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GuichetRepository::class)]
#[ORM\Table(name: '`guichets`')]
class Guichet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $numero = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    private ?Service $typeService = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $agiliste = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'Fermé';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;
        return $this;
    }

    public function getTypeService(): ?Service
    {
        return $this->typeService;
    }

    public function setTypeService(?Service $typeService): static
    {
        $this->typeService = $typeService;
        return $this;
    }

    public function getAgiliste(): ?User
    {
        return $this->agiliste;
    }

    public function setAgiliste(?User $agiliste): static
    {
        $this->agiliste = $agiliste;
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
