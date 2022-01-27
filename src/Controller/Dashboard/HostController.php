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

use App\Entity\Host;
use App\Entity\User;
use App\Form\AddHostType;
use App\Form\AddZoneType;
use App\Form\EditHostType;
use App\Repository\HostRepository;
use App\Repository\SecurityZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class HostController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws LogicException
     * @throws \LogicException
     * @throws RuntimeException
     */
    #[Route(path: '/dashboard/hosts/add', name: 'app.hosts.add')]
    public function add(#[CurrentUser] User $user, Request $request): Response
    {
        $form = $this->createForm(AddHostType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Host $host */
            $host = $form->getData();
            $host->setAddedBy($user);
            $this->entityManager->persist($host);
            $this->entityManager->flush();

            $this->addFlash('success', 'Host was created.');

            return $this->redirectToRoute('app.hosts.index');
        }

        return $this->render('dashboard/hosts/add.html.twig', [
            'addHostForm' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws LogicException
     * @throws RuntimeException
     * @throws \LogicException
     */
    #[Route(path: '/dashboard/hosts/zones/add', name: 'app.hosts.zones.add')]
    public function addZone(Request $request): Response
    {
        $form = $this->createForm(AddZoneType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $host = $form->getData();
            $this->entityManager->persist($host);
            $this->entityManager->flush();

            $this->addFlash('success', 'Security Zone was created.');

            return $this->redirectToRoute('app.hosts.index');
        }

        return $this->render('dashboard/hosts/add-zone.html.twig', [
            'addZoneForm' => $form->createView(),
        ]);
    }

    /**
     * @param Request        $request
     * @param string         $id
     * @param HostRepository $hostRepository
     *
     * @return Response
     * @throws LogicException
     * @throws RuntimeException
     * @throws \LogicException
     */
    #[Route(path: '/dashboard/hosts/{id}/edit', name: 'app.hosts.edit')]
    public function editHost(
        Request $request,
        string $id,
        HostRepository $hostRepository
    ): Response {
        $host = $hostRepository->find((int)$id);
        $form = $this->createForm(EditHostType::class, $host);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $host = $form->getData();

            $this->entityManager->persist($host);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'The host has been updated.'
            );

            return $this->redirectToRoute('app.hosts.index',
                $request->query->all()
            );
        }

        return $this->render('dashboard/hosts/edit.html.twig', [
            'editHostForm' => $form->createView(),
            'host' => $host,
        ]);
    }

    /**
     * @param Request                $request
     * @param HostRepository         $hostRepository
     * @param SecurityZoneRepository $zoneRepository
     *
     * @return Response
     * @throws InvalidArgumentException
     * @throws BadRequestException
     */
    #[Route(path: '/dashboard/hosts', name: 'app.hosts.index')]
    public function index(
        Request $request,
        HostRepository $hostRepository,
        SecurityZoneRepository $zoneRepository
    ): Response {
        $grouping = $request->query->get('group');
        $hosts = $grouping === 'zones'
            ? $hostRepository
                ->createQueryBuilder('h')
                ->where('h.securityZones is empty')
                ->getQuery()
                ->getResult()
            : $hostRepository->findAll();
        $zones = $zoneRepository->findAll();

        return $this->render('dashboard/hosts/index.html.twig', [
            'hosts' => $hosts,
            'zones' => $zones,
            'grouping' => $grouping,
        ]);
    }
}
