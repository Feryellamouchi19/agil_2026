<?php
namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:assign-user-role', description: 'Assigns one or more roles to a user identified by email.')]
class AssignUserRoleCommand extends Command
{
    private UserRepository $userRepository;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address of the user')
            ->addArgument('roles', InputArgument::REQUIRED, 'Comma‑separated list of roles (e.g. ROLE_GERANT,ROLE_CLIENT)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $rolesInput = $input->getArgument('roles');
        $roles = array_map('trim', explode(',', $rolesInput));

        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $output->writeln(sprintf('<error>User with email "%s" not found.</error>', $email));
            return Command::FAILURE;
        }

        $user->setRoles($roles);
        $this->em->flush();

        $output->writeln(sprintf('<info>Roles %s assigned to user %s.</info>', json_encode($roles), $email));
        return Command::SUCCESS;
    }
}
?>
