<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampTrait;
use App\Repository\CertificateAuthorityRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: CertificateAuthorityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CertificateAuthority
{
    use TimestampTrait;

    #[ORM\Column(type: 'text')]
    private string|null $comment = null;

    /**
     * @var Collection<HostCertificate>
     */
    #[ORM\OneToMany(
        mappedBy: 'certificateAuthority',
        targetEntity: HostCertificate::class,
        orphanRemoval: true
    )]
    private Collection $hostCertificates;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'ulid')]
    private Ulid|null $identifier = null;

    #[ORM\Column(type: 'bigint')]
    private string|null $lastIssuedSerialNumber = null;

    #[ORM\Column(type: 'text')]
    private string|null $publicKey = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $revokedAt = null;

    /**
     * @var Collection<UserCertificate>
     */
    #[ORM\OneToMany(
        mappedBy: 'certificateAuthority',
        targetEntity: UserCertificate::class,
        orphanRemoval: true
    )]
    private Collection $userCertificates;

    #[Pure]
    public function __construct()
    {
        $this->userCertificates = new ArrayCollection();
        $this->hostCertificates = new ArrayCollection();
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Collection<HostCertificate>
     */
    public function getHostCertificates(): Collection
    {
        return $this->hostCertificates;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): string|null
    {
        return $this->identifier;
    }

    public function setIdentifier(Ulid $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getLastIssuedSerialNumber(): string|null
    {
        return $this->lastIssuedSerialNumber;
    }

    public function setLastIssuedSerialNumber(
        string|int $lastIssuedSerialNumber
    ): self {
        $this->lastIssuedSerialNumber = (string)$lastIssuedSerialNumber;

        return $this;
    }

    public function getPublicKey(): ?string
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

    /**
     * @return Collection<UserCertificate>
     */
    public function getUserCertificates(): Collection
    {
        return $this->userCertificates;
    }

    public function addCertificate(UserCertificate $certificate): self
    {
        if ( ! $this->userCertificates->contains($certificate)) {
            $this->userCertificates[] = $certificate;
            $certificate->setCertificateAuthority($this);
        }

        return $this;
    }

    public function addHostCertificate(HostCertificate $hostCertificate): self
    {
        if ( ! $this->hostCertificates->contains($hostCertificate)) {
            $this->hostCertificates[] = $hostCertificate;
            $hostCertificate->setCertificateAuthority($this);
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function generateIdentifier(): void
    {
        if ($this->getIdentifier() === null) {
            $this->setIdentifier(new Ulid());
        }
    }

    public function removeCertificate(UserCertificate $certificate): self
    {
        // set the owning side to null (unless already changed)
        if (
            $this->userCertificates->removeElement($certificate) &&
            $this === $certificate->getCertificateAuthority()
        ) {
            $certificate->setCertificateAuthority(null);
        }

        return $this;
    }

    public function removeHostCertificate(HostCertificate $hostCertificate): self
    {
        // set the owning side to null (unless already changed)
        if (
            $this->hostCertificates->removeElement($hostCertificate) &&
            $this === $hostCertificate->getCertificateAuthority()
        ) {
            $hostCertificate->setCertificateAuthority(null);
        }

        return $this;
    }
}
