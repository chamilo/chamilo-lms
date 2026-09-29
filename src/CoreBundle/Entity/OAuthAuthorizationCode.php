<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\OAuthAuthorizationCodeRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * A one-time OAuth 2.1 authorization code (RFC 6749 §4.1 + PKCE, RFC 7636).
 *
 * Single-use: usedAt must be set via a conditional UPDATE (WHERE used_at IS
 * NULL), never via a read-then-write, so two concurrent /token calls cannot
 * both succeed against the same code.
 */
#[ORM\Table(name: 'oauth_authorization_code')]
#[ORM\Index(name: 'idx_oauth_code_grant', columns: ['grant_id'])]
#[ORM\Index(name: 'idx_oauth_code_expires', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_oauth_code_user', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'uniq_oauth_code_hash', columns: ['code_hash'])]
#[ORM\Entity(repositoryClass: OAuthAuthorizationCodeRepository::class)]
class OAuthAuthorizationCode
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'code_hash', type: 'string', length: 64, nullable: false)]
    protected string $codeHash;

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

    #[ORM\Column(name: 'redirect_uri', type: 'text', nullable: false)]
    protected string $redirectUri;

    #[ORM\Column(name: 'code_challenge', type: 'string', length: 128, nullable: false)]
    protected string $codeChallenge;

    #[ORM\Column(name: 'code_challenge_method', type: 'string', length: 10, nullable: false)]
    protected string $codeChallengeMethod = 'S256';

    #[ORM\Column(name: 'scope', type: 'string', length: 255, nullable: true)]
    protected ?string $scope = null;

    #[ORM\Column(name: 'resource', type: 'text', nullable: true)]
    protected ?string $resource = null;

    #[ORM\Column(name: 'consent_ip', type: 'string', length: 45, nullable: true)]
    protected ?string $consentIp = null;

    #[ORM\Column(name: 'consent_user_agent', type: 'string', length: 255, nullable: true)]
    protected ?string $consentUserAgent = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: false)]
    protected DateTime $expiresAt;

    #[ORM\Column(name: 'used_at', type: 'datetime', nullable: true)]
    protected ?DateTime $usedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function setCodeHash(string $codeHash): self
    {
        $this->codeHash = $codeHash;

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

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): self
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    public function getCodeChallenge(): string
    {
        return $this->codeChallenge;
    }

    public function setCodeChallenge(string $codeChallenge): self
    {
        $this->codeChallenge = $codeChallenge;

        return $this;
    }

    public function getCodeChallengeMethod(): string
    {
        return $this->codeChallengeMethod;
    }

    public function setCodeChallengeMethod(string $codeChallengeMethod): self
    {
        $this->codeChallengeMethod = $codeChallengeMethod;

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

    public function getConsentIp(): ?string
    {
        return $this->consentIp;
    }

    public function setConsentIp(?string $consentIp): self
    {
        $this->consentIp = $consentIp;

        return $this;
    }

    public function getConsentUserAgent(): ?string
    {
        return $this->consentUserAgent;
    }

    public function setConsentUserAgent(?string $consentUserAgent): self
    {
        $this->consentUserAgent = $consentUserAgent;

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

    public function getUsedAt(): ?DateTime
    {
        return $this->usedAt;
    }

    public function setUsedAt(?DateTime $usedAt): self
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    public function isActiveAt(DateTime $date): bool
    {
        return null === $this->usedAt && $this->expiresAt > $date;
    }
}
