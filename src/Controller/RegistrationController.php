<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    /**
     * @param Request                     $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface      $entityManager
     *
     * @return Response
     * @throws LogicException
     * @throws OutOfBoundsException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'app.registration')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            ));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app.registration.verify_email',
                $user,
                (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate(
                        'registration/confirmation_email.html.twig'
                    )
            );

            // do anything else you need here, like send an email

            return $this->redirectToRoute('index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws LogicException
     * @throws AccessDeniedException
     */
    #[Route('/verify/email', name: 'app.registration.verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true
        // and persists
        try {
            $this->emailVerifier->handleEmailConfirmation(
                $request,
                $this->getUser()
            );
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(
                'verify_email_error',
                $exception->getReason()
            );

            return $this->redirectToRoute('app.registration');
        }

        // TODO: Change the redirect on success and handle or remove the flash
        //       message in your templates
        $this->addFlash(
            'success',
            'Your email address has been verified.'
        );

        return $this->redirectToRoute('app.registration');
    }
}
