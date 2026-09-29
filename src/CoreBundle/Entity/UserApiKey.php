<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\UserApiKeyRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * API keys associated with Chamilo users.
 *
 * Legacy services continue to use rows with api_service = "default".
 * MCP rows use api_service = "mcp" and store a SHA-256 hash instead of the
 * recoverable secret.
 */
#[ORM\Table(name: 'user_api_key')]
#[ORM\Index(name: 'idx_user_api_keys_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_user_api_key_mcp_lookup', columns: ['api_service', 'api_key', 'access_url_id', 'revoked_at'])]
#[ORM\UniqueConstraint(name: 'uniq_user_api_key_service_url', columns: ['user_id', 'api_service', 'access_url_id'])]
#[ORM\Entity(repositoryClass: UserApiKeyRepository::class)]
class UserApiKey
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: false)]
    protected int $userId;

    #[ORM\Column(name: 'api_key', type: 'string', length: 64, nullable: false)]
    protected string $apiKey;

    #[ORM\Column(name: 'api_service', type: 'string', length: 10, nullable: false)]
    protected string $apiService;

    #[ORM\Column(name: 'api_end_point', type: 'text', nullable: true)]
    protected ?string $apiEndPoint = null;

    #[ORM\Column(name: 'created_date', type: 'datetime', nullable: true)]
    protected ?DateTime $createdDate = null;

    #[ORM\Column(name: 'validity_start_date', type: 'datetime', nullable: true)]
    protected ?DateTime $validityStartDate = null;

    #[ORM\Column(name: 'validity_end_date', type: 'datetime', nullable: true)]
    protected ?DateTime $validityEndDate = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'access_url_id', type: 'integer', nullable: true)]
    protected ?int $accessUrlId = null;

    #[ORM\Column(name: 'key_prefix', type: 'string', length: 32, nullable: true)]
    protected ?string $keyPrefix = null;

    #[ORM\Column(name: 'last_used_at', type: 'datetime', nullable: true)]
    protected ?DateTime $lastUsedAt = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
    protected ?DateTime $revokedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getApiService(): string
    {
        return $this->apiService;
    }

    public function setApiService(string $apiService): self
    {
        $this->apiService = $apiService;

        return $this;
    }

    public function getApiEndPoint(): ?string
    {
        return $this->apiEndPoint;
    }

    public function setApiEndPoint(?string $apiEndPoint): self
    {
        $this->apiEndPoint = $apiEndPoint;

        return $this;
    }

    public function getCreatedDate(): ?DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getValidityStartDate(): ?DateTime
    {
        return $this->validityStartDate;
    }

    public function setValidityStartDate(?DateTime $validityStartDate): self
    {
        $this->validityStartDate = $validityStartDate;

        return $this;
    }

    public function getValidityEndDate(): ?DateTime
    {
        return $this->validityEndDate;
    }

    public function setValidityEndDate(?DateTime $validityEndDate): self
    {
        $this->validityEndDate = $validityEndDate;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getKeyPrefix(): ?string
    {
        return $this->keyPrefix;
    }

    public function setKeyPrefix(?string $keyPrefix): self
    {
        $this->keyPrefix = $keyPrefix;

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
        if (null !== $this->revokedAt) {
            return false;
        }

        if (null !== $this->validityStartDate && $this->validityStartDate > $date) {
            return false;
        }

        return null === $this->validityEndDate || $this->validityEndDate > $date;
    }
}
