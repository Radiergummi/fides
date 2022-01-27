<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param Request       $request
     * @param UserInterface $user
     *
     * @throws VerifyEmailExceptionInterface
     * @throws InvalidArgumentException
     */
    public function handleEmailConfirmation(
        Request $request,
        UserInterface $user
    ): void {
        if ( ! $user instanceof User) {
            throw new InvalidArgumentException('Unexpected user entity');
        }

        $this->verifyEmailHelper->validateEmailConfirmation(
            $request->getUri(),
            $user->getId(),
            $user->getEmail()
        );

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @param string         $verifyEmailRouteName
     * @param UserInterface  $user
     * @param TemplatedEmail $email
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws TransportExceptionInterface
     */
    public function sendEmailConfirmation(
        string $verifyEmailRouteName,
        UserInterface $user,
        TemplatedEmail $email
    ): void {
        if ( ! $user instanceof User) {
            throw new InvalidArgumentException('Unexpected user entity');
        }

        $signature = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user->getId(),
            $user->getEmail()
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signature->getSignedUrl();
        $context['expiresAtMessageKey'] = $signature->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signature->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }
}
