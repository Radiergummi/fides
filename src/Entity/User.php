<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

use function array_map;
use function assert;
use function in_array;

/**
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampTrait;

    public const ROLES = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
    ];

    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public const ROLE_USER = 'ROLE_USER';

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserCertificate::class, orphanRemoval: true)]
    private $certificates;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_LOOSE)]
    private string|null $email = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(min: 2, max: 250)]
    private string|null $name = null;

    #[ORM\Column(type: 'string')]
    private string|null $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var Collection<SecurityZone>
     */
    #[ORM\ManyToMany(targetEntity: SecurityZone::class, inversedBy: 'users')]
    private Collection $securityZones;

    #[Pure]
    public function __construct()
    {
        $this->securityZones = new ArrayCollection();
        $this->certificates = new ArrayCollection();
    }

    /**
     * @return Collection|UserCertificate[]
     */
    public function getCertificates(): Collection
    {
        return $this->certificates;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string|null $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return Collection<SecurityZone>
     */
    public function getSecurityZones(): Collection
    {
        return $this->securityZones;
    }

    public function addCertificate(UserCertificate $certificate): self
    {
        if ( ! $this->certificates->contains($certificate)) {
            $this->certificates[] = $certificate;
            $certificate->setUser($this);
        }

        return $this;
    }

    public function addSecurityZone(SecurityZone $securityZone): self
    {
        if ( ! $this->securityZones->contains($securityZone)) {
            $this->securityZones[] = $securityZone;
        }

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLE_USER;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        assert(array_map(static fn(string $role) => in_array(
            $role,
            self::ROLES,
            true
        ), $roles), 'Invalid roles');

        $this->roles = $roles;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function removeCertificate(UserCertificate $certificate): self
    {
        if ($this->certificates->removeElement($certificate)) {
            // set the owning side to null (unless already changed)
            if ($certificate->getUser() === $this) {
                $certificate->setUser(null);
            }
        }

        return $this;
    }

    public function removeSecurityZone(SecurityZone $securityZone): self
    {
        $this->securityZones->removeElement($securityZone);

        return $this;
    }
}
