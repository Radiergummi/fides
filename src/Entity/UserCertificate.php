<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampTrait;
use App\Repository\UserCertificateRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

use function array_values;

#[ORM\Entity(repositoryClass: UserCertificateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserCertificate
{
    use TimestampTrait;

    #[ORM\Column(type: 'boolean')]
    private bool $agentForwardingEnabled = true;

    #[ORM\Column(type: 'json')]
    private array $allowedSourceAddresses = [];

    #[ORM\ManyToOne(
        targetEntity: CertificateAuthority::class,
        inversedBy: 'userCertificates'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private CertificateAuthority|null $certificateAuthority = null;

    #[ORM\Column(type: 'text')]
    private string|null $content = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private string|null $forceCommand = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'boolean')]
    private bool $portForwardingEnabled = true;

    #[ORM\Column(type: 'boolean')]
    private bool $ptyAllocationEnabled = true;

    #[ORM\Column(type: 'text')]
    private string|null $publicKey = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $revokedAt = null;

    /**
     * @var Collection<SecurityZone>
     */
    #[ORM\ManyToMany(
        targetEntity: SecurityZone::class,
        inversedBy: 'certificates'
    )]
    private Collection $securityZones;

    #[ORM\Column(type: 'bigint')]
    private string|null $serialNumber = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'certificates')]
    #[ORM\JoinColumn(nullable: false)]
    private User|null $user = null;

    #[ORM\Column(type: 'boolean')]
    private bool $userRcExecutionEnabled = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $validFrom = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $validUntil = null;

    #[ORM\Column(type: 'boolean')]
    private bool $x11ForwardingEnabled = true;

    #[Pure]
    public function __construct()
    {
        $this->securityZones = new ArrayCollection();
    }

    public function getAgentForwardingEnabled(): bool|null
    {
        return $this->agentForwardingEnabled;
    }

    public function setAgentForwardingEnabled(bool $agentForwardingEnabled): self
    {
        $this->agentForwardingEnabled = $agentForwardingEnabled;

        return $this;
    }

    public function getAllowedSourceAddresses(): array|null
    {
        return $this->allowedSourceAddresses;
    }

    public function setAllowedSourceAddresses(array|null $allowedSourceAddresses): self
    {
        $this->allowedSourceAddresses = array_values(
            $allowedSourceAddresses ?? []
        );

        return $this;
    }

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

    public function getForceCommand(): string|null
    {
        return $this->forceCommand;
    }

    public function setForceCommand(string|null $forceCommand): self
    {
        $this->forceCommand = $forceCommand;

        return $this;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getPortForwardingEnabled(): bool|null
    {
        return $this->portForwardingEnabled;
    }

    public function setPortForwardingEnabled(bool $portForwardingEnabled): self
    {
        $this->portForwardingEnabled = $portForwardingEnabled;

        return $this;
    }

    public function getPtyAllocationEnabled(): bool|null
    {
        return $this->ptyAllocationEnabled;
    }

    public function setPtyAllocationEnabled(bool $ptyAllocationEnabled): self
    {
        $this->ptyAllocationEnabled = $ptyAllocationEnabled;

        return $this;
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

    public function setRevokedAt(DateTimeImmutable $revokedAt): self
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    /**
     * @return Collection<SecurityZone>
     */
    public function getSecurityZones(): Collection
    {
        return $this->securityZones;
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

    public function getUser(): User|null
    {
        return $this->user;
    }

    public function setUser(User|null $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUserRcExecutionEnabled(): bool|null
    {
        return $this->userRcExecutionEnabled;
    }

    public function setUserRcExecutionEnabled(bool $userRcExecutionEnabled): self
    {
        $this->userRcExecutionEnabled = $userRcExecutionEnabled;

        return $this;
    }

    public function getValidFrom(): DateTimeImmutable|null
    {
        return $this->validFrom;
    }

    public function setValidFrom(DateTimeImmutable $validFrom): self
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

    public function getX11ForwardingEnabled(): bool|null
    {
        return $this->x11ForwardingEnabled;
    }

    public function setX11ForwardingEnabled(bool $x11ForwardingEnabled): self
    {
        $this->x11ForwardingEnabled = $x11ForwardingEnabled;

        return $this;
    }

    public function addSecurityZone(SecurityZone $securityZone): self
    {
        if ( ! $this->securityZones->contains($securityZone)) {
            $this->securityZones[] = $securityZone;
        }

        return $this;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function removeSecurityZone(SecurityZone $securityZone): self
    {
        $this->securityZones->removeElement($securityZone);

        return $this;
    }
}
