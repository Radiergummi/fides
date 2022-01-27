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

namespace App\Cryptography;

use App\Entity\CertificateAuthority;
use App\Entity\HostCertificate;
use App\Entity\SecurityZone;
use App\Entity\UserCertificate;
use App\Repository\CertificateAuthorityRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use function array_filter;

class Signer
{
    public function __construct(
        private Filesystem $filesystem,
        private EntityManagerInterface $entityManager,
        private CertificateAuthorityRepository $certificateAuthorityRepository,
        private SshKeygen $sshKeygen,
        private string $caKeyPath
    ) {
    }

    public function sign(
        UserCertificate|HostCertificate $certificate
    ): UserCertificate|HostCertificate {
        // It's extremely important to issue the certificate within
        // a transaction, so the serial number increases monotonically.
        return $this->entityManager->wrapInTransaction(function () use (
            $certificate
        ) {
            $ca = $this->certificateAuthorityRepository->getActive();
            $serial = (int)$ca->getLastIssuedSerialNumber() + 1;
            $privateKey = $this->resolveCaPrivateKeyFile($ca);

            if ($certificate instanceof UserCertificate) {
                $identity = $certificate->getUser()?->getUserIdentifier();
                $principals = $certificate
                    ->getSecurityZones()
                    ->map(fn(SecurityZone $zone) => $zone->getIdentifier())
                    ->toArray();
                $options = $this->determineOptions($certificate);

                $content = $this->sshKeygen->signUserKey(
                    $privateKey,
                    $certificate->getPublicKey(),
                    $identity,
                    $principals,
                    $serial,
                    $certificate->getValidFrom(),
                    $certificate->getValidUntil(),
                    $options
                );
            } else {
                $identity = $certificate->getHost()?->getFullyQualifiedName();
                $principals = $certificate
                    ->getSecurityZones()
                    ->map(fn(SecurityZone $zone) => $zone->getIdentifier())
                    ->toArray();

                $content = $this->sshKeygen->signHostKey(
                    $privateKey,
                    $certificate->getPublicKey(),
                    $identity,
                    $principals,
                    $serial,
                    $certificate->getValidFrom(),
                    $certificate->getValidUntil()
                );
            }

            $certificate->setCertificateAuthority($ca);
            $certificate->setContent($content);
            $certificate->setSerialNumber($serial);
            $ca->setLastIssuedSerialNumber($serial);

            $this->entityManager->persist($ca);
            $this->entityManager->persist($certificate);
            $this->entityManager->flush();

            return $certificate;
        });
    }

    /**
     * Extracts all options from a certificate.
     *
     * @param UserCertificate|HostCertificate $certificate
     *
     * @return array<string, mixed>
     */
    private function determineOptions(
        UserCertificate|HostCertificate $certificate
    ): array {
        if ($certificate instanceof HostCertificate) {
            return [];
        }

        return array_filter([
            SshKeygen::OPTION_NO_AGENT_FORWARDING => $certificate->getAgentForwardingEnabled() === false,
            SshKeygen::OPTION_NO_PORT_FORWARDING => $certificate->getPortForwardingEnabled() === false,
            SshKeygen::OPTION_NO_X11_FORWARDING => $certificate->getX11ForwardingEnabled() === false,
            SshKeygen::OPTION_NO_PTY => $certificate->getPtyAllocationEnabled() === false,
            SshKeygen::OPTION_NO_USER_RC => $certificate->getUserRcExecutionEnabled() === false,
            SshKeygen::OPTION_FORCE_COMMAND => $certificate->getForceCommand(),
            SshKeygen::OPTION_SOURCE_ADDRESS => $certificate->getAllowedSourceAddresses(),
        ]);
    }

    /**
     * @param CertificateAuthority $certificateAuthority
     *
     * @return string
     * @throws IOException
     * @throws RuntimeException
     */
    private function resolveCaPrivateKeyFile(
        CertificateAuthority $certificateAuthority
    ): string {
        $path = "{$this->caKeyPath}/{$certificateAuthority->getIdentifier()}";

        if ( ! $this->filesystem->exists($path)) {
            throw new RuntimeException(
                "Could not resolve CA private key at expected " .
                "location: '{$path}'"
            );
        }

        return $path;
    }
}
