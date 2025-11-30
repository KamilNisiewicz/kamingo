<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Tworzy nowego użytkownika',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username (email)')
            ->addArgument('password', InputArgument::REQUIRED, 'Hasło w plain text')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $plainPassword = $input->getArgument('password');

        // Sprawdź czy użytkownik już istnieje
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        if ($existingUser) {
            $io->error(sprintf('Użytkownik "%s" już istnieje!', $username));
            return Command::FAILURE;
        }

        // Utwórz nowego użytkownika
        $user = new User();
        $user->setUsername($username);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        // Zapisz do bazy
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Użytkownik "%s" został utworzony!', $username));

        return Command::SUCCESS;
    }
}
