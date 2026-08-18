<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`vouchers`')]
class Voucher
{
    const STATUS_ACTIVE   = 'active';
    const STATUS_USED     = 'used';
    const STATUS_EXPIRED  = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** Monetary value in TND */
    #[ORM\Column(type: 'float')]
    private float $value = 0.0;

    /** Status: active | used | expired */
    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getValue(): float { return $this->value; }
    public function setValue(float $value): static { $this->value = $value; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUsedAt(): ?\DateTimeImmutable { return $this->usedAt; }
    public function setUsedAt(?\DateTimeImmutable $usedAt): static { $this->usedAt = $usedAt; return $this; }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }

    public function markAsUsed(): static
    {
        $this->status = self::STATUS_USED;
        $this->usedAt = new \DateTimeImmutable();
        return $this;
    }
}
