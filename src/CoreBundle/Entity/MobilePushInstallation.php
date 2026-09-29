<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\MobilePushInstallationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'mobile_push_installation')]
#[ORM\Index(name: 'idx_mobile_push_installation_user_url', columns: ['user_id', 'access_url_id'])]
#[ORM\UniqueConstraint(name: 'uniq_mobile_push_installation_url_id', columns: ['access_url_id', 'installation_id'])]
#[ORM\UniqueConstraint(name: 'uniq_mobile_push_installation_url_token', columns: ['access_url_id', 'token_hash'])]
#[ORM\Entity(repositoryClass: MobilePushInstallationRepository::class)]
class MobilePushInstallation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AccessUrl $accessUrl;

    #[ORM\Column(name: 'installation_id', type: 'string', length: 36)]
    private string $installationId;

    #[ORM\Column(name: 'token', type: 'text')]
    private string $token;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(name: 'platform', type: 'string', length: 20)]
    private string $platform;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private DateTime $updatedAt;

    #[ORM\Column(name: 'last_seen_at', type: 'datetime')]
    private DateTime $lastSeenAt;

    public function __construct()
    {
        $now = new DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->lastSeenAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAccessUrl(): AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    public function getInstallationId(): string
    {
        return $this->installationId;
    }

    public function setInstallationId(string $installationId): self
    {
        $this->installationId = $installationId;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
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

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLastSeenAt(): DateTime
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(DateTime $lastSeenAt): self
    {
        $this->lastSeenAt = $lastSeenAt;

        return $this;
    }
}
