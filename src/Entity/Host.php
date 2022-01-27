<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampTrait;
use App\Repository\HostRepository;
use Darsyn\IP\Version\IPv4;
use Darsyn\IP\Version\IPv6;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Host
{
    use TimestampTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User|null $addedBy = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $displayName = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Hostname(message: 'This value is not a valid hostname.', requireTld: false)]
    private string|null $fullyQualifiedName = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'ipv4', nullable: true)]
    #[Assert\Ip(version: 4, message: 'This is not a valid IP address.')]
    private IPv4|null $ipv4Address = null;

    #[ORM\Column(type: 'ipv6', nullable: true)]
    #[Assert\Ip(version: 6, message: 'This is not a valid IP address.')]
    private IPv6|null $ipv6Address = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private DateTimeImmutable|null $lastContactedAt = null;

    /**
     * @var Collection<SecurityZone>
     */
    #[ORM\ManyToMany(targetEntity: SecurityZone::class, inversedBy: 'hosts')]
    private Collection $securityZones;

    #[ORM\OneToMany(mappedBy: 'host', targetEntity: HostCertificate::class, orphanRemoval: true)]
    private $certificates;

    #[Pure]
    public function __construct()
    {
        $this->securityZones = new ArrayCollection();
        $this->certificates = new ArrayCollection();
    }

    public function getAddedBy(): User|null
    {
        return $this->addedBy;
    }

    public function setAddedBy(User|null $addedBy): self
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable|null
    {
        return $this->createdAt;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getUpdatedAt(): DateTimeImmutable|null
    {
        return $this->updatedAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDisplayName(): string|null
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getFullyQualifiedName(): string|null
    {
        return $this->fullyQualifiedName;
    }

    public function setFullyQualifiedName(string $fullyQualifiedName): self
    {
        $this->fullyQualifiedName = $fullyQualifiedName;

        return $this;
    }

    public function getIpv4Address(): IPv4|null
    {
        return $this->ipv4Address;
    }

    public function setIpv4Address(IPv4|null $ipv4Address): self
    {
        $this->ipv4Address = $ipv4Address;

        return $this;
    }

    public function getIpv6Address(): IPv6|null
    {
        return $this->ipv6Address;
    }

    public function setIpv6Address(IPv6|null $ipv6Address): self
    {
        $this->ipv6Address = $ipv6Address;

        return $this;
    }

    public function getLastContactedAt(): DateTimeImmutable|null
    {
        return $this->lastContactedAt;
    }

    public function setLastContactedAt(DateTimeImmutable $lastContactedAt): self
    {
        $this->lastContactedAt = $lastContactedAt;

        return $this;
    }

    /**
     * @return Collection<SecurityZone>
     */
    public function getSecurityZones(): Collection
    {
        return $this->securityZones;
    }

    public function addSecurityZone(SecurityZone $securityZone): self
    {
        if ( ! $this->securityZones->contains($securityZone)) {
            $this->securityZones[] = $securityZone;
        }

        return $this;
    }

    public function removeSecurityZone(SecurityZone $securityZone): self
    {
        $this->securityZones->removeElement($securityZone);

        return $this;
    }

    /**
     * @return Collection|HostCertificate[]
     */
    public function getCertificates(): Collection
    {
        return $this->certificates;
    }

    public function addCertificate(HostCertificate $certificate): self
    {
        if (!$this->certificates->contains($certificate)) {
            $this->certificates[] = $certificate;
            $certificate->setHost($this);
        }

        return $this;
    }

    public function removeCertificate(HostCertificate $certificate): self
    {
        if ($this->certificates->removeElement($certificate)) {
            // set the owning side to null (unless already changed)
            if ($certificate->getHost() === $this) {
                $certificate->setHost(null);
            }
        }

        return $this;
    }
}
