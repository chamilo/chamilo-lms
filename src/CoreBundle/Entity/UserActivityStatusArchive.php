<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\UserActivityStatusArchiveRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user_activity_status_archive')]
#[ORM\Index(name: 'idx_user_activity_archive_user_time', columns: ['user_id', 'session_time_at'])]
#[ORM\UniqueConstraint(name: 'uniq_user_activity_archive_legacy_tracking', columns: ['legacy_tracking_id'])]
#[ORM\Entity(repositoryClass: UserActivityStatusArchiveRepository::class)]
class UserActivityStatusArchive
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'legacy_tracking_id', type: 'integer', nullable: true)]
    protected ?int $legacyTrackingId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    #[ORM\Column(name: 'session_time_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $sessionTimeAt = null;

    #[ORM\Column(name: 'session_time_raw', type: 'string', length: 255, nullable: true)]
    protected ?string $sessionTimeRaw = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: false, options: ['default' => false])]
    protected bool $active = false;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getLegacyTrackingId(): ?int
    {
        return $this->legacyTrackingId;
    }

    public function setLegacyTrackingId(?int $legacyTrackingId): self
    {
        $this->legacyTrackingId = $legacyTrackingId;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSessionTimeAt(): ?DateTimeInterface
    {
        return $this->sessionTimeAt;
    }

    public function setSessionTimeAt(?DateTimeInterface $sessionTimeAt): self
    {
        $this->sessionTimeAt = $sessionTimeAt;

        return $this;
    }

    public function getSessionTimeRaw(): ?string
    {
        return $this->sessionTimeRaw;
    }

    public function setSessionTimeRaw(?string $sessionTimeRaw): self
    {
        $this->sessionTimeRaw = $sessionTimeRaw;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
