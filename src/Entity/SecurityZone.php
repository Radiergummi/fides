<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampTrait;
use App\Repository\SecurityZoneRepository;
use App\Validator\SecurityZoneIdentifier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SecurityZoneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SecurityZone implements Stringable
{
    use TimestampTrait;

    public const DEFAULT_PRINCIPAL = 'root';

    /**
     * @var Collection<UserCertificate>
     */
    #[ORM\ManyToMany(targetEntity: UserCertificate::class, mappedBy: 'securityZones')]
    private Collection $certificates;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(min: 1, max: 250)]
    private string|null $displayName = null;

    /**
     * @var Collection<Host>
     */
    #[ORM\ManyToMany(targetEntity: Host::class, mappedBy: 'securityZones')]
    private Collection $hosts;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'string', length: 63)]
    #[SecurityZoneIdentifier]
    private string|null $identifier = null;

    #[ORM\Column(type: 'string', length: 31)]
    #[Assert\Regex(
        pattern: '/^[a-z_]([a-z0-9_-]{0,31}|[a-z0-9_-]{0,30}\$)$/',
        message: 'This is not a valid UNIX username.'
    )]
    private string|null $principal = self::DEFAULT_PRINCIPAL;

    /**
     * @var Collection<User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'securityZones')]
    private Collection $users;

    #[Pure]
    public function __construct()
    {
        $this->hosts = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->certificates = new ArrayCollection();
    }

    /**
     * @return Collection<UserCertificate>
     */
    public function getCertificates(): Collection
    {
        return $this->certificates;
    }

    public function getDisplayName(): string|null
    {
        return $this->displayName;
    }

    public function setDisplayName(string|null $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return Collection<Host>
     */
    public function getHosts(): Collection
    {
        return $this->hosts;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getIdentifier(): string|null
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getPrincipal(): string|null
    {
        return $this->principal;
    }

    public function setPrincipal(string $principal): self
    {
        $this->principal = $principal;

        return $this;
    }

    /**
     * @return Collection<User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function __toString(): string
    {
        return $this->identifier;
    }

    public function addCertificate(UserCertificate $certificate): self
    {
        if ( ! $this->certificates->contains($certificate)) {
            $this->certificates[] = $certificate;
            $certificate->addSecurityZone($this);
        }

        return $this;
    }

    public function addHost(Host $host): self
    {
        if ( ! $this->hosts->contains($host)) {
            $this->hosts[] = $host;
            $host->addSecurityZone($this);
        }

        return $this;
    }

    public function addUser(User $user): self
    {
        if ( ! $this->users->contains($user)) {
            $this->users[] = $user;
            $user->addSecurityZone($this);
        }

        return $this;
    }

    public function removeCertificate(UserCertificate $certificate): self
    {
        if ($this->certificates->removeElement($certificate)) {
            $certificate->removeSecurityZone($this);
        }

        return $this;
    }

    public function removeHost(Host $host): self
    {
        if ($this->hosts->removeElement($host)) {
            $host->removeSecurityZone($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeSecurityZone($this);
        }

        return $this;
    }
}
