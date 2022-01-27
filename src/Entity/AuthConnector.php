<?php

namespace App\Entity;

use App\Entity\Behaviour\TimestampTrait;
use App\Entity\Enum\AuthConnectorProvider;
use App\Repository\AuthConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthConnectorRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AuthConnector
{
    use TimestampTrait;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $authorizeUri = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $clientId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $clientSecret = null;

    #[ORM\Column(type: 'boolean')]
    private bool|null $enabled = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: AuthConnectorProvider::class, length: 255)]
    private AuthConnectorProvider|null $provider = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $redirectUri = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $tokenUri = null;

    public function getAuthorizeUri(): string|null
    {
        return $this->authorizeUri;
    }

    public function setAuthorizeUri(string $authorizeUri): self
    {
        $this->authorizeUri = $authorizeUri;

        return $this;
    }

    public function getClientId(): string|null
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecret(): string|null
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): AuthConnectorProvider|null
    {
        return $this->provider;
    }

    public function setProvider(AuthConnectorProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getRedirectUri(): string|null
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): self
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    public function getTokenUri(): string|null
    {
        return $this->tokenUri;
    }

    public function setTokenUri(string $tokenUri): self
    {
        $this->tokenUri = $tokenUri;

        return $this;
    }
}
