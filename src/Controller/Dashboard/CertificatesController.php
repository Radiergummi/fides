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
use App\Cryptography\Signer;
use App\Cryptography\SshKeygen;
use App\Entity\User;
use App\Form\ConfirmCertificateRevokationType;
use App\Form\RequestUserCertificateType;
use App\Repository\CertificateAuthorityRepository;
use App\Repository\UserCertificateRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

use function explode;
use function str_split;

use const PHP_EOL;

class CertificatesController extends AbstractController
{
    /**
     * @param CertificateAuthorityRepository $certificateAuthorityRepository
     *
     * @return Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws \RuntimeException
     */
    #[Route(
        path: '/dashboard/certificates/authorities',
        name: 'app.certificates.authorities'
    )]
    public function authorities(
        CertificateAuthorityRepository $certificateAuthorityRepository
    ): Response {
        $certificateAuthorities = $certificateAuthorityRepository->findAll();
        $active = $certificateAuthorityRepository->getActive();

        return $this->render('dashboard/certificates/authorities.html.twig', [
            'certificateAuthorities' => $certificateAuthorities,
            'active' => $active,
        ]);
    }

    #[Route(path: '/dashboard/certificates', name: 'app.certificates.index')]
    public function index(UserCertificateRepository $certificateRepository): Response
    {
        $certificates = $certificateRepository->findValid();

        return $this->render('dashboard/certificates/index.html.twig', [
            'certificates' => $certificates,
        ]);
    }

    /**
     * @param Request $request
     * @param Signer  $signer
     * @param User    $user
     *
     * @return Response
     * @throws LogicException
     * @throws RuntimeException
     * @throws \LogicException
     */
    #[Route(path: '/dashboard/certificates/request', name: 'app.certificates.request')]
    public function request(
        Request $request,
        Signer $signer,
        #[CurrentUser] User $user
    ): Response {
        $form = $this->createForm(RequestUserCertificateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $certificate = $form->getData();
            $certificate->setUser($user);

            $certificate = $signer->sign($certificate);

            $this->addFlash(
                FlashCategory::Success->value,
                'Certificate created successfully'
            );

            return $this->redirectToRoute('app.certificates.single', [
                'id' => $certificate->getId(),
            ]);
        }

        return $this->render('dashboard/certificates/request.html.twig', [
            'requestUserCertificateForm' => $form->createView(),
        ]);
    }

    /**
     * @param Request                   $request
     * @param string                    $id
     * @param UserCertificateRepository $repository
     * @param EntityManagerInterface    $entityManager
     *
     * @return Response
     * @throws LogicException
     * @throws \LogicException
     */
    #[Route(path: '/dashboard/certificates/{id}/revoke', name: 'app.certificates.revoke')]
    public function revoke(
        Request $request,
        string $id,
        UserCertificateRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $certificate = $repository->find((int)$id);

        if ( ! $certificate) {
            $this->addFlash(
                FlashCategory::Error->value,
                'Certificate not found'
            );

            return $this->redirectToRoute('app.certificates.index');
        }

        $form = $this->createForm(ConfirmCertificateRevokationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $certificate->setRevokedAt(new DateTimeImmutable());
            $entityManager->persist($certificate);
            $entityManager->flush();

            $this->addFlash(
                FlashCategory::Success->value,
                'Certificate has been revoked'
            );

            return $this->redirectToRoute('app.certificates.index');
        }

        return $this->render('dashboard/certificates/revoke.twig', [
            'confirmCertificateRevokationForm' => $form->createView(),
            'certificate' => $certificate,
        ]);
    }

    #[Route(path: '/dashboard/certificates/revoked', name: 'app.certificates.revoked')]
    public function revoked(UserCertificateRepository $certificateRepository): Response
    {
        $certificates = $certificateRepository->findRevoked();

        return $this->render('dashboard/certificates/revoked.html.twig', [
            'certificates' => $certificates,
        ]);
    }

    /**
     * @param string                    $id
     * @param UserCertificateRepository $repository
     * @param SshKeygen                 $sshKeygen
     *
     * @return Response
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws IOException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    #[Route(
        path: '/dashboard/certificates/{id}',
        name: 'app.certificates.single'
    )]
    public function single(
        string $id,
        UserCertificateRepository $repository,
        SshKeygen $sshKeygen
    ): Response {
        $certificate = $repository->find((int)$id);

        if ( ! $certificate) {
            $this->addFlash(
                FlashCategory::Error->value,
                'Certificate not found'
            );

            return $this->redirectToRoute('app.certificates.index');
        }

        $visual = $sshKeygen->renderVisualFingerprint(
            $certificate->getContent()
        );

        return $this->render('dashboard/certificates/single.twig', [
            'certificate' => $certificate,
            'visual' => $visual,
        ]);
    }
}
