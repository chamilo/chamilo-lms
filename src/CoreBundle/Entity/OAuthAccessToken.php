<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\OAuthAccessTokenRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * An OAuth 2.1 bearer access token minted for a real, already-existing
 * Chamilo user on behalf of an OAuthClient.
 *
 * Stores only a SHA-256 hash of the plaintext bearer token, same convention
 * as UserApiKey.
 */
#[ORM\Table(name: 'oauth_access_token')]
#[ORM\Index(name: 'idx_oauth_access_grant', columns: ['grant_id'])]
#[ORM\Index(name: 'idx_oauth_access_user', columns: ['user_id', 'revoked_at'])]
#[ORM\Index(name: 'idx_oauth_access_expires', columns: ['expires_at'])]
#[ORM\UniqueConstraint(name: 'uniq_oauth_access_hash', columns: ['token_hash'])]
#[ORM\Entity(repositoryClass: OAuthAccessTokenRepository::class)]
class OAuthAccessToken
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64, nullable: false)]
    protected string $tokenHash;

    #[ORM\Column(name: 'token_prefix', type: 'string', length: 32, nullable: true)]
    protected ?string $tokenPrefix = null;

    #[ORM\Column(name: 'grant_id', type: 'string', length: 36, nullable: false)]
    protected string $grantId;

    #[ORM\ManyToOne(targetEntity: OAuthClient::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected OAuthClient $client;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(name: 'access_url_id', type: 'integer', nullable: true)]
    protected ?int $accessUrlId = null;

    #[ORM\Column(name: 'scope', type: 'string', length: 255, nullable: true)]
    protected ?string $scope = null;

    #[ORM\Column(name: 'resource', type: 'text', nullable: true)]
    protected ?string $resource = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: false)]
    protected DateTime $expiresAt;

    #[ORM\Column(name: 'last_used_at', type: 'datetime', nullable: true)]
    protected ?DateTime $lastUsedAt = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
    protected ?DateTime $revokedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getTokenPrefix(): ?string
    {
        return $this->tokenPrefix;
    }

    public function setTokenPrefix(?string $tokenPrefix): self
    {
        $this->tokenPrefix = $tokenPrefix;

        return $this;
    }

    public function getGrantId(): string
    {
        return $this->grantId;
    }

    public function setGrantId(string $grantId): self
    {
        $this->grantId = $grantId;

        return $this;
    }

    public function getClient(): OAuthClient
    {
        return $this->client;
    }

    public function setClient(OAuthClient $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

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

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(?string $resource): self
    {
        $this->resource = $resource;

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

    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

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

    public function isActiveAt(DateTime $date): bool
    {
        return null === $this->revokedAt && $this->expiresAt > $date;
    }
}
