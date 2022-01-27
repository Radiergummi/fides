<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function filter_var;

use const FILTER_VALIDATE_EMAIL;

#[AsCommand(
    name: 'debug:mailing',
    description: 'Send a test email',
)]
class DebugMailingCommand extends Command
{
    public const RECIPIENT = 'recipient';

    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->addArgument(
            self::RECIPIENT,
            InputArgument::REQUIRED,
            'Email address to send the test email to'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $recipient = $input->getArgument(self::RECIPIENT);

        if ( ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $io->error("Not a valid email address: '{$recipient}'");
        }

        $message = (new Email())
            ->to($recipient)
            ->subject('Mailing set up successfully')
            ->text(
                'Congratulations! You have configured mailing ' .
                'properly: Fides is able to send emails, as evident from this' .
                "message having arrived in your inbox.\nYou can now continue " .
                'the setup process.'
            )
            ->html(
                '<h1>Congratulations!</h1>' .
                '<p>You have configured mailing properly: Fides is able to ' .
                'send emails, as evident from this message having arrived in ' .
                'your inbox.<br>You can now continue the setup process.</p>'
            );

        $this->mailer->send($message);

        return Command::SUCCESS;
    }
}
