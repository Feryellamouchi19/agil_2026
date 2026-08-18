<?php
namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:show-user-roles', description: 'Displays roles for a given user email.')]
class ShowUserRolesCommand extends Command
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user to inspect');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $output->writeln(sprintf('<error>User with email "%s" not found.</error>', $email));
            return Command::FAILURE;
        }
        $roles = $user->getRoles();
        $output->writeln('Roles: ' . json_encode($roles));
        return Command::SUCCESS;
    }
}
?>
