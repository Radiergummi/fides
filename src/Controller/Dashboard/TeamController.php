<?php

/**
 * This file is part of fides, a Matchory application.
 *
 * Unauthorized copying of this file, via any medium, is strictly prohibited.
 * Its contents are strictly confidential and proprietary.
 *
 * @copyright 2020–2022 Matchory GmbH · All rights reserved
 * @author    Moritz Friedrich <moritz@matchory.com>
 */

declare(strict_types=1);

namespace App\Controller\Dashboard;

use App\Controller\FlashCategory;
use App\Entity\AuthConnector;
use App\Entity\Enum\AuthConnectorProvider as Provider;
use App\Form\EditUserType;
use App\Repository\AuthConnectorRepository;
use App\Repository\UserCertificateRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function array_filter;
use function array_map;
use function in_array;

class TeamController extends AbstractController
{
    #[Route(path: '/dashboard/team/connectors', name: 'app.team.connectors')]
    public function connectors(AuthConnectorRepository $connectorRepository): Response
    {
        $connectors = $connectorRepository->findAll();
        $connectedProviders = array_map(
            static fn(AuthConnector $connector) => $connector->getProvider(), $connectors
        );
        $missingConnectors = array_filter(
            Provider::cases(),
            static fn(Provider $provider) => ! in_array(
                $provider,
                $connectedProviders,
                true
            )
        );

        return $this->render('dashboard/team/connectors.html.twig', [
            'connectors' => $connectors,
            'missingConnectors' => $missingConnectors,
        ]);
    }

    /**
     * @param Request                $request
     * @param string                 $id
     * @param UserRepository         $userRepository
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     * @throws LogicException
     * @throws RuntimeException
     * @throws \LogicException
     */
    #[Route(path: '/dashboard/team/users/{id}/edit', name: 'app.team.users.edit')]
    public function editUser(
        Request $request,
        string $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $userRepository->find((int)$id);
        $form = $this->createForm(EditUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                FlashCategory::Success->value,
                'User has been updated.'
            );

            return $this->redirectToRoute('app.team.users');
        }

        return $this->render('dashboard/team/edit.html.twig', [
            'editUserForm' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route(path: '/dashboard/team', name: 'app.team.index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app.team.users');
    }

    /**
     * @param string                    $id
     * @param UserRepository            $userRepository
     * @param UserCertificateRepository $certificateRepository
     *
     * @return Response
     * @throws \LogicException
     */
    #[Route(path: '/dashboard/team/users/{id}', name: 'app.team.users.single')]
    public function singleUser(
        string $id,
        UserRepository $userRepository,
        UserCertificateRepository $certificateRepository
    ): Response {
        $user = $userRepository->find((int)$id);

        if ( ! $user) {
            $this->addFlash(
                FlashCategory::Error->value,
                'User not found'
            );

            return $this->redirectToRoute('app.team.users');
        }

        $certificates = $certificateRepository->findValidByUser($user);

        return $this->render('dashboard/team/single.html.twig', [
            'user' => $user,
            'certificates' => $certificates,
        ]);
    }

    #[Route(path: '/dashboard/team/users', name: 'app.team.users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('dashboard/team/users.html.twig', [
            'users' => $users,
        ]);
    }
}
