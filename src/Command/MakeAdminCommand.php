<?php

namespace App\Command;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:make-admin',
    description: 'Attribue le rôle administrateur à un compte existant.'
)]
final class MakeAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail du compte à promouvoir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = strtolower((string) $input->getArgument('email'));
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Aucun compte ne correspond à « %s ». Inscrivez d’abord ce compte.', $email));

            return Command::FAILURE;
        }

        $user->setRole(UserRole::ADMINISTRATEUR);
        $this->entityManager->flush();

        $io->success(sprintf('Le compte %s est maintenant administrateur.', $email));

        return Command::SUCCESS;
    }
}
