<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\SettingsType;
use App\Form\UpdateUserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SettingsController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws LogicException
     * @throws \LogicException
     */
    #[Route('/dashboard/settings', name: 'app.settings.index')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SettingsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('app.settings.index');
        }

        return $this->render('dashboard/settings/index.html.twig', [
            'settingsForm' => $form->createView(),
        ]);
    }

    /**
     * @param User                        $user
     * @param Request                     $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     *
     * @return Response
     * @throws LogicException
     * @throws OutOfBoundsException
     * @throws RuntimeException
     * @throws \LogicException
     */
    #[Route('/dashboard/settings/password', name: 'app.settings.password')]
    public function password(
        #[CurrentUser] User $user,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
    ): Response {
        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($userPasswordHasher->hashPassword(
                $user,
                $form
                    ->get(ChangePasswordType::FIELD_PLAIN_PASSWORD)
                    ->getData()
            ));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Your password has been reset.'
            );

            return $this->redirectToRoute('app.settings.index');
        }

        return $this->render('dashboard/settings/password.html.twig', [
            'changePasswordForm' => $form->createView(),
        ]);
    }

    /**
     * @param User    $user
     * @param Request $request
     *
     * @return Response
     * @throws LogicException
     * @throws RuntimeException
     */
    #[Route('/dashboard/settings/profile', name: 'app.settings.profile')]
    public function profile(
        #[CurrentUser] User $user,
        Request $request
    ): Response {
        $form = $this->createForm(UpdateUserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Your profile has been updated.'
            );

            return $this->redirectToRoute('app.settings.profile');
        }

        return $this->render('dashboard/settings/profile.html.twig', [
            'userProfileForm' => $form->createView(),
        ]);
    }
}
