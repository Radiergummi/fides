<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function bin2hex;
use function filter_var;
use function implode;
use function random_bytes;
use function sprintf;

use const FILTER_VALIDATE_EMAIL;

#[AsCommand(
    name: 'user:add',
    description: 'Create a new user account',
)]
class AddUserCommand extends Command
{
    public const EMAIL = 'email';

    public const NAME = 'name';

    public const NAME_SHORTCUT = 'N';

    public const PASSWORD = 'password';

    public const ROLE = 'role';

    public const ROLE_SHORTCUT = 'r';

    public const VERIFIED = 'verified';

    public const VERIFIED_SHORTCUT = 'w';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->addArgument(
            self::EMAIL,
            InputArgument::REQUIRED,
            'Email address of the new user account.'
        );

        $this->addOption(
            self::NAME,
            self::NAME_SHORTCUT,
            InputOption::VALUE_REQUIRED,
            'Display name to configure.'
        );

        $this->addOption(
            self::PASSWORD,
            null,
            InputOption::VALUE_REQUIRED,
            'Plain password to set. Will be randomly generated if omitted.'
        );

        $this->addOption(
            self::VERIFIED,
            self::VERIFIED_SHORTCUT,
            InputOption::VALUE_NEGATABLE,
            'Mark the user as verified.',
            true
        );

        $roles = implode(', ', User::ROLES);
        $this->addOption(
            self::ROLE,
            self::ROLE_SHORTCUT,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            "Roles to configure. One of {$roles}.",
            [User::ROLE_USER]
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument(self::EMAIL);
        $name = $input->getOption(self::NAME);
        $roles = $input->getOption(self::ROLE);

        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error("Email address seems to be invalid: '{$email}'");

            return Command::INVALID;
        }

        $plainPassword = $input->getOption(self::PASSWORD)
            ?: $this->generatePassword();

        $user = new User();

        if ($name) {
            $user->setName($name);
        }

        $user->setEmail($email);
        $user->setIsVerified(true);
        $hashedPassword = $this->userPasswordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPassword($hashedPassword);
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success([
            sprintf(
                'New user account%s created:',
                $name ? (" for {$name}") : ''
            ),
            '  ' . $user->getEmail(),
            '  ' . $plainPassword,
        ]);

        return Command::SUCCESS;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function generatePassword(): string
    {
        return bin2hex(random_bytes(12));
    }
}
