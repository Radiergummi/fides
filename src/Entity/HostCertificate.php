<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampTrait;
use App\Repository\HostCertificateRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HostCertificateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HostCertificate
{
    use TimestampTrait;

    #[ORM\ManyToOne(targetEntity: CertificateAuthority::class, inversedBy: 'hostCertificates')]
    #[ORM\JoinColumn(nullable: false)]
    private CertificateAuthority|null $certificateAuthority = null;

    #[ORM\Column(type: 'text')]
    private string|null $content = null;

    #[ORM\ManyToOne(targetEntity: Host::class, inversedBy: 'certificates')]
    #[ORM\JoinColumn(nullable: false)]
    private Host|null $host = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'text')]
    private string|null $publicKey = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $revokedAt = null;

    #[ORM\Column(type: 'bigint')]
    private string|null $serialNumber = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $validFrom = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $validUntil = null;

    public function getCertificateAuthority(): CertificateAuthority|null
    {
        return $this->certificateAuthority;
    }

    public function setCertificateAuthority(
        CertificateAuthority|null $certificateAuthority
    ): self {
        $this->certificateAuthority = $certificateAuthority;

        return $this;
    }

    public function getContent(): string|null
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getHost(): Host|null
    {
        return $this->host;
    }

    public function setHost(Host|null $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getPublicKey(): string|null
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getRevokedAt(): DateTimeImmutable|null
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(DateTimeImmutable|null $revokedAt): self
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    public function getSerialNumber(): string|null
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(string|int $serialNumber): self
    {
        $this->serialNumber = (string)$serialNumber;

        return $this;
    }

    public function getValidFrom(): DateTimeImmutable|null
    {
        return $this->validFrom;
    }

    public function setValidFrom(DateTimeImmutable|null $validFrom): self
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidUntil(): DateTimeImmutable|null
    {
        return $this->validUntil;
    }

    public function setValidUntil(DateTimeImmutable|null $validUntil): self
    {
        $this->validUntil = $validUntil;

        return $this;
    }
}
