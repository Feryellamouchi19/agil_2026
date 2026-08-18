<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`point_transactions`')]
class PointTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: Voucher::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Voucher $voucher = null;

    #[ORM\Column(type: 'string')]
    private string $type = 'gain'; // 'gain' or 'reward'

    #[ORM\Column(type: 'float')]
    private float $value = 0.0; // monetary value for gain, 0 for reward

    #[ORM\Column]
    private int $points = 0; // positive for gain, negative for reward deduction

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private int $balanceAfter = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // getters / setters
    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getTicket(): ?Ticket { return $this->ticket; }
    public function setTicket(?Ticket $ticket): static { $this->ticket = $ticket; return $this; }
    public function getVoucher(): ?Voucher { return $this->voucher; }
    public function setVoucher(?Voucher $voucher): static { $this->voucher = $voucher; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getValue(): float { return $this->value; }
    public function setValue(float $value): static { $this->value = $value; return $this; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): static { $this->points = $points; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getBalanceAfter(): int { return $this->balanceAfter; }
    public function setBalanceAfter(int $balance): static { $this->balanceAfter = $balance; return $this; }
}
?>
