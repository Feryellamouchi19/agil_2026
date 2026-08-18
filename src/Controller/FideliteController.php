<?php

namespace App\Controller;

use App\Entity\Voucher;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
#[Route('/client/fidelite')]
class FideliteController extends AbstractController
{
    #[Route('', name: 'app_fidelite_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $vouchers = $em->getRepository(Voucher::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $transactions = $em->getRepository(\App\Entity\PointTransaction::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            20
        );

        $activeVouchers = array_filter($vouchers, fn(Voucher $v) => $v->isActive());

        $progress = min(($user->getPoints() / 1000) * 100, 100);

        return $this->render('fidelite/dashboard.html.twig', [
            'user'           => $user,
            'vouchers'       => $vouchers,
            'activeVouchers' => array_values($activeVouchers),
            'transactions'   => $transactions,
            'progress'       => $progress,
        ]);
    }

    #[Route('/utiliser/{id}', name: 'app_fidelite_use_voucher')]
    public function useVoucher(Voucher $voucher, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($voucher->getUser() !== $user) {
            $this->addFlash('danger', 'Ce bon ne vous appartient pas.');
            return $this->redirectToRoute('app_fidelite_dashboard');
        }

        if (!$voucher->isActive()) {
            $this->addFlash('warning', 'Ce bon a déjà été utilisé ou est expiré.');
            return $this->redirectToRoute('app_fidelite_dashboard');
        }

        $voucher->markAsUsed();
        $em->flush();

        $this->addFlash('success', sprintf('Bon de %.0f DT utilisé avec succès !', $voucher->getValue()));
        return $this->redirectToRoute('app_fidelite_dashboard');
    }
}
