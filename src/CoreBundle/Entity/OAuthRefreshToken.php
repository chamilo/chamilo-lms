<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\OAuthRefreshTokenRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * An OAuth 2.1 refresh token — also the "connected app" grant/consent record.
 *
 * The live (non-rotated, non-revoked, non-expired) row for a grantId IS the
 * grant: it carries the original consent metadata copied forward across every
 * rotation, so "Connected apps" is a single indexed query and revoking a grant
 * revokes this row's whole family.
 *
 * Rotation keeps the consumed row (rotatedAt + replacedBy) instead of
 * overwriting it, so replaying an already-rotated refresh token can be
 * detected and the whole family revoked (OAuth 2.1 refresh token rotation).
 */
#[ORM\Table(name: 'oauth_refresh_token')]
#[ORM\Index(name: 'idx_oauth_refresh_grant', columns: ['grant_id'])]
#[ORM\Index(name: 'idx_oauth_refresh_user', columns: ['user_id', 'revoked_at', 'rotated_at'])]
#[ORM\Index(name: 'idx_oauth_refresh_expires', columns: ['expires_at'])]
#[ORM\UniqueConstraint(name: 'uniq_oauth_refresh_hash', columns: ['token_hash'])]
#[ORM\Entity(repositoryClass: OAuthRefreshTokenRepository::class)]
class OAuthRefreshToken
{
    public const string REVOKED_REASON_USER = 'user';
    public const string REVOKED_REASON_REUSE_DETECTED = 'reuse_detected';
    public const string REVOKED_REASON_CLIENT_REVOKED = 'client_revoked';
    public const string REVOKED_REASON_ADMIN = 'admin';
    public const string REVOKED_REASON_ACCOUNT_DISABLED = 'account_disabled';

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64, nullable: false)]
    protected string $tokenHash;

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

    #[ORM\Column(name: 'consented_at', type: 'datetime', nullable: false)]
    protected DateTime $consentedAt;

    #[ORM\Column(name: 'consent_ip', type: 'string', length: 45, nullable: true)]
    protected ?string $consentIp = null;

    #[ORM\Column(name: 'consent_user_agent', type: 'string', length: 255, nullable: true)]
    protected ?string $consentUserAgent = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: false)]
    protected DateTime $expiresAt;

    #[ORM\Column(name: 'absolute_expires_at', type: 'datetime', nullable: false)]
    protected DateTime $absoluteExpiresAt;

    #[ORM\Column(name: 'rotated_at', type: 'datetime', nullable: true)]
    protected ?DateTime $rotatedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'replaced_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?self $replacedBy = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
    protected ?DateTime $revokedAt = null;

    #[ORM\Column(name: 'revoked_reason', type: 'string', length: 32, nullable: true)]
    protected ?string $revokedReason = null;

    #[ORM\Column(name: 'last_used_at', type: 'datetime', nullable: true)]
    protected ?DateTime $lastUsedAt = null;

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

    public function getConsentedAt(): DateTime
    {
        return $this->consentedAt;
    }

    public function setConsentedAt(DateTime $consentedAt): self
    {
        $this->consentedAt = $consentedAt;

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

    public function getAbsoluteExpiresAt(): DateTime
    {
        return $this->absoluteExpiresAt;
    }

    public function setAbsoluteExpiresAt(DateTime $absoluteExpiresAt): self
    {
        $this->absoluteExpiresAt = $absoluteExpiresAt;

        return $this;
    }

    public function getRotatedAt(): ?DateTime
    {
        return $this->rotatedAt;
    }

    public function setRotatedAt(?DateTime $rotatedAt): self
    {
        $this->rotatedAt = $rotatedAt;

        return $this;
    }

    public function getReplacedBy(): ?self
    {
        return $this->replacedBy;
    }

    public function setReplacedBy(?self $replacedBy): self
    {
        $this->replacedBy = $replacedBy;

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

    public function getRevokedReason(): ?string
    {
        return $this->revokedReason;
    }

    public function setRevokedReason(?string $revokedReason): self
    {
        $this->revokedReason = $revokedReason;

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

    public function isActiveAt(DateTime $date): bool
    {
        return null === $this->revokedAt
            && null === $this->rotatedAt
            && $this->expiresAt > $date
            && $this->absoluteExpiresAt > $date;
    }
}
