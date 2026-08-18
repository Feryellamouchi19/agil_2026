<?php
namespace App\Service;

use App\Entity\PointTransaction;
use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Voucher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PointsManager centralizes the business logic for the loyalty system.
 *
 * - When a ticket of type "BON_VALEUR" is completed, the client receives
 *   points equal to the monetary value (1 DT = 1 point).
 * - Every 1000 points automatically generate a voucher worth 50 DT.
 *   The points are deducted from the user's balance, and any remainder stays
 *   on the account.
 * - Each modification (point gain or voucher issuance) is recorded in a
 *   PointTransaction entity for full auditability.
 */
class PointsManager
{
    private EntityManagerInterface $em;
    private const POINTS_PER_DTN = 1; // 1 DT = 1 point
    private const VOUCHER_THRESHOLD = 1000; // points required for a voucher
    private const VOUCHER_VALUE = 50.0; // value of generated voucher (DT)

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Process a finished ticket and apply loyalty rules.
     */
    public function handleFinishedTicket(Ticket $ticket): void
    {
        // Ensure the ticket is linked to a user (client)
        $user = $ticket->getClient();
        if (!$user instanceof User) {
            return; // nothing to do if no associated client
        }

        // Only "BON_VALEUR" tickets generate points
        if (strtoupper($ticket->getService()->getType()) !== 'BON_VALEUR') {
            return;
        }

        // The ticket stores the monetary value in the "value" field (DT)
        $value = $ticket->getValue();
        if ($value <= 0) {
            return; // no points for zero or negative values
        }

        $pointsToAdd = (int)($value * self::POINTS_PER_DTN);
        $this->addPoints($user, $pointsToAdd);

        // After adding, check if we have enough points for a voucher
        $this->generateVoucherIfThresholdReached($user);
    }

    /**
     * Add points to a user and persist a PointTransaction.
     */
    private function addPoints(User $user, int $points): void
    {
        $newBalance = $user->getPoints() + $points;
        $user->setPoints($newBalance);

        $tx = new PointTransaction();
        $tx->setUser($user);
        $tx->setTicket(null);
        $tx->setVoucher(null);
        $tx->setType('gain');
        $tx->setValue($points);
        $tx->setPoints($newBalance);
        $tx->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($tx);
        $this->em->flush();
    }

    /**
     * Create vouchers for every full threshold of points.
     */
    private function generateVoucherIfThresholdReached(User $user): void
    {
        while ($user->getPoints() >= self::VOUCHER_THRESHOLD) {
            // Deduct points for the voucher
            $remaining = $user->getPoints() - self::VOUCHER_THRESHOLD;
            $user->setPoints($remaining);

            // Create a new voucher entity
            $voucher = new Voucher();
            $voucher->setUser($user);
            $voucher->setValue(self::VOUCHER_VALUE);
            $voucher->setStatus('active');
            $voucher->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($voucher);

            // Record the voucher issuance as a transaction (negative points)
            $tx = new PointTransaction();
            $tx->setUser($user);
            $tx->setTicket(null);
            $tx->setVoucher($voucher);
            $tx->setType('reward');
            $tx->setValue(self::VOUCHER_VALUE);
            $tx->setPoints($remaining);
            $tx->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($tx);

            // Flush after each iteration to keep DB consistent
            $this->em->flush();
        }
    }
}
?>
