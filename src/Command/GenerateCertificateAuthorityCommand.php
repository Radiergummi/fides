<?php

namespace App\Command;

use App\Cryptography\SshKeygen;
use App\Entity\CertificateAuthority;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Uid\Ulid;

use function in_array;

#[AsCommand(
    name: 'ca:generate',
    description: 'Generates a certificate authority',
)]
class GenerateCertificateAuthorityCommand extends Command
{
    public const COMMENT = 'comment';

    public const COMMENT_SHORTCUT = 'c';

    public const MODE = 'mode';

    public const MODE_HOST = 'host';

    public const MODE_SHORTCUT = 'm';

    public const MODE_USER = 'user';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SshKeygen $sshKeygen,
        private string $caKeyPath
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->addOption(
            self::MODE,
            self::MODE_SHORTCUT,
            InputOption::VALUE_REQUIRED,
            sprintf(
                'CA mode. Either "%s" or "%s".',
                self::MODE_USER, self::MODE_HOST
            ),
            self::MODE_USER
        );
        $this->addOption(
            self::COMMENT,
            self::COMMENT_SHORTCUT,
            InputOption::VALUE_NONE,
            'Optional comment for the CA. Will be displayed in the interface.'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $comment = $input->getOption(self::COMMENT);
        $mode = $input->getOption(self::MODE);

        if ( ! in_array(
            $mode,
            [self::MODE_USER, self::MODE_HOST],
            true
        )) {
            $io->error(sprintf(
                'Invalid mode: Must be %s or %s',
                self::MODE_USER,
                self::MODE_HOST
            ));

            return self::INVALID;
        }

        $ca = new CertificateAuthority();
        $ca->setIdentifier(new Ulid());

        $filePath = "{$this->caKeyPath}/{$ca->getIdentifier()}";
        $publicKey = $this->sshKeygen->generateCaCertificate(
            $mode,
            $filePath
        );

        $ca->setLastIssuedSerialNumber(0);
        $ca->setPublicKey($publicKey);
        $ca->setComment($comment);

        $this->entityManager->persist($ca);
        $this->entityManager->flush();

        $io->success("Generated new CA: {$ca->getIdentifier()}");

        return Command::SUCCESS;
    }
}
