<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\OAuthClientRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * A registered OAuth client application (e.g. "Claude"), never a person.
 *
 * Registered via Dynamic Client Registration (RFC 7591). Public clients using
 * PKCE (the expected case) have no secret: tokenEndpointAuthMethod is "none"
 * and clientSecretHash stays null.
 */
#[ORM\Table(name: 'oauth_client')]
#[ORM\Index(name: 'idx_oauth_client_url', columns: ['access_url_id', 'revoked_at'])]
#[ORM\Index(name: 'idx_oauth_client_created', columns: ['created_at'])]
#[ORM\UniqueConstraint(name: 'uniq_oauth_client_client_id', columns: ['client_id'])]
#[ORM\Entity(repositoryClass: OAuthClientRepository::class)]
class OAuthClient
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'client_id', type: 'string', length: 64, nullable: false)]
    protected string $clientId;

    #[ORM\Column(name: 'client_secret_hash', type: 'string', length: 64, nullable: true)]
    protected ?string $clientSecretHash = null;

    #[ORM\Column(name: 'client_secret_prefix', type: 'string', length: 32, nullable: true)]
    protected ?string $clientSecretPrefix = null;

    #[ORM\Column(name: 'token_endpoint_auth_method', type: 'string', length: 32, nullable: false)]
    protected string $tokenEndpointAuthMethod = 'none';

    #[ORM\Column(name: 'client_name', type: 'string', length: 255, nullable: true)]
    protected ?string $clientName = null;

    #[ORM\Column(name: 'client_uri', type: 'text', nullable: true)]
    protected ?string $clientUri = null;

    #[ORM\Column(name: 'logo_uri', type: 'text', nullable: true)]
    protected ?string $logoUri = null;

    #[ORM\Column(name: 'policy_uri', type: 'text', nullable: true)]
    protected ?string $policyUri = null;

    #[ORM\Column(name: 'tos_uri', type: 'text', nullable: true)]
    protected ?string $tosUri = null;

    #[ORM\Column(name: 'software_id', type: 'string', length: 255, nullable: true)]
    protected ?string $softwareId = null;

    #[ORM\Column(name: 'software_version', type: 'string', length: 64, nullable: true)]
    protected ?string $softwareVersion = null;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(name: 'redirect_uris', type: 'json', nullable: false)]
    protected array $redirectUris = [];

    /**
     * @var array<int, string>
     */
    #[ORM\Column(name: 'grant_types', type: 'json', nullable: false)]
    protected array $grantTypes = [];

    /**
     * @var array<int, string>
     */
    #[ORM\Column(name: 'response_types', type: 'json', nullable: false)]
    protected array $responseTypes = [];

    #[ORM\Column(name: 'scope', type: 'string', length: 255, nullable: true)]
    protected ?string $scope = null;

    #[ORM\Column(name: 'access_url_id', type: 'integer', nullable: true)]
    protected ?int $accessUrlId = null;

    #[ORM\Column(name: 'registration_ip', type: 'string', length: 45, nullable: true)]
    protected ?string $registrationIp = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'last_used_at', type: 'datetime', nullable: true)]
    protected ?DateTime $lastUsedAt = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
    protected ?DateTime $revokedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecretHash(): ?string
    {
        return $this->clientSecretHash;
    }

    public function setClientSecretHash(?string $clientSecretHash): self
    {
        $this->clientSecretHash = $clientSecretHash;

        return $this;
    }

    public function getClientSecretPrefix(): ?string
    {
        return $this->clientSecretPrefix;
    }

    public function setClientSecretPrefix(?string $clientSecretPrefix): self
    {
        $this->clientSecretPrefix = $clientSecretPrefix;

        return $this;
    }

    public function getTokenEndpointAuthMethod(): string
    {
        return $this->tokenEndpointAuthMethod;
    }

    public function setTokenEndpointAuthMethod(string $tokenEndpointAuthMethod): self
    {
        $this->tokenEndpointAuthMethod = $tokenEndpointAuthMethod;

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): self
    {
        $this->clientName = $clientName;

        return $this;
    }

    public function getClientUri(): ?string
    {
        return $this->clientUri;
    }

    public function setClientUri(?string $clientUri): self
    {
        $this->clientUri = $clientUri;

        return $this;
    }

    public function getLogoUri(): ?string
    {
        return $this->logoUri;
    }

    public function setLogoUri(?string $logoUri): self
    {
        $this->logoUri = $logoUri;

        return $this;
    }

    public function getPolicyUri(): ?string
    {
        return $this->policyUri;
    }

    public function setPolicyUri(?string $policyUri): self
    {
        $this->policyUri = $policyUri;

        return $this;
    }

    public function getTosUri(): ?string
    {
        return $this->tosUri;
    }

    public function setTosUri(?string $tosUri): self
    {
        $this->tosUri = $tosUri;

        return $this;
    }

    public function getSoftwareId(): ?string
    {
        return $this->softwareId;
    }

    public function setSoftwareId(?string $softwareId): self
    {
        $this->softwareId = $softwareId;

        return $this;
    }

    public function getSoftwareVersion(): ?string
    {
        return $this->softwareVersion;
    }

    public function setSoftwareVersion(?string $softwareVersion): self
    {
        $this->softwareVersion = $softwareVersion;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    /**
     * @param array<int, string> $redirectUris
     */
    public function setRedirectUris(array $redirectUris): self
    {
        $this->redirectUris = $redirectUris;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getGrantTypes(): array
    {
        return $this->grantTypes;
    }

    /**
     * @param array<int, string> $grantTypes
     */
    public function setGrantTypes(array $grantTypes): self
    {
        $this->grantTypes = $grantTypes;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getResponseTypes(): array
    {
        return $this->responseTypes;
    }

    /**
     * @param array<int, string> $responseTypes
     */
    public function setResponseTypes(array $responseTypes): self
    {
        $this->responseTypes = $responseTypes;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getAccessUrlId(): ?int
    {
        return $this->accessUrlId;
    }

    public function setAccessUrlId(?int $accessUrlId): self
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    public function getRegistrationIp(): ?string
    {
        return $this->registrationIp;
    }

    public function setRegistrationIp(?string $registrationIp): self
    {
        $this->registrationIp = $registrationIp;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastUsedAt(): ?DateTime
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?DateTime $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    public function getRevokedAt(): ?DateTime
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?DateTime $revokedAt): self
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    public function isPublicClient(): bool
    {
        return 'none' === $this->tokenEndpointAuthMethod;
    }

    public function isActiveAt(DateTime $date): bool
    {
        return null === $this->revokedAt;
    }

    public function supportsRedirectUri(string $redirectUri): bool
    {
        foreach ($this->redirectUris as $registered) {
            if (hash_equals($registered, $redirectUri)) {
                return true;
            }
        }

        return false;
    }
}
