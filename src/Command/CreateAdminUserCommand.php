<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create-admin',
    description: 'Create a new admin user with a given email and password.'
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'feryel@agil.tn';
        $plainPassword = 'ING_feryel2003';

        // Check if user already exists
        $existing = $this->userRepository->findOneBy(['email' => $email]);
        if ($existing) {
            $output->writeln("<comment>User with email $email already exists.</comment>");
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setNom('Feryel');
        $user->setPrenom('');
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln("<info>Admin user created: $email</info>");
        return Command::SUCCESS;
    }
}
?>
