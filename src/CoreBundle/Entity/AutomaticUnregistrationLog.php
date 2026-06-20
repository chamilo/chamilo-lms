<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\AutomaticUnregistrationLogRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'automatic_unregistration_log')]
#[ORM\Index(name: 'idx_auto_unregistration_user_course', columns: ['user_id', 'course_id'])]
#[ORM\Index(name: 'idx_auto_unregistration_deleted_at', columns: ['deleted_at'])]
#[ORM\UniqueConstraint(name: 'uniq_auto_unregistration_legacy_id', columns: ['legacy_id'])]
#[ORM\Entity(repositoryClass: AutomaticUnregistrationLogRepository::class)]
class AutomaticUnregistrationLog
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'legacy_id', type: 'integer', nullable: true)]
    protected ?int $legacyId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Course $course = null;

    #[ORM\Column(name: 'legacy_course_id', type: 'integer', nullable: true)]
    protected ?int $legacyCourseId = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $deletedAt = null;

    #[ORM\Column(name: 'deleted_at_raw', type: 'string', length: 255, nullable: true)]
    protected ?string $deletedAtRaw = null;

    #[ORM\Column(name: 'last_access_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $lastAccessAt = null;

    #[ORM\Column(name: 'last_access_raw', type: 'string', length: 255, nullable: true)]
    protected ?string $lastAccessRaw = null;

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

    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    public function setLegacyId(?int $legacyId): self
    {
        $this->legacyId = $legacyId;

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

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getLegacyCourseId(): ?int
    {
        return $this->legacyCourseId;
    }

    public function setLegacyCourseId(?int $legacyCourseId): self
    {
        $this->legacyCourseId = $legacyCourseId;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedAtRaw(): ?string
    {
        return $this->deletedAtRaw;
    }

    public function setDeletedAtRaw(?string $deletedAtRaw): self
    {
        $this->deletedAtRaw = $deletedAtRaw;

        return $this;
    }

    public function getLastAccessAt(): ?DateTimeInterface
    {
        return $this->lastAccessAt;
    }

    public function setLastAccessAt(?DateTimeInterface $lastAccessAt): self
    {
        $this->lastAccessAt = $lastAccessAt;

        return $this;
    }

    public function getLastAccessRaw(): ?string
    {
        return $this->lastAccessRaw;
    }

    public function setLastAccessRaw(?string $lastAccessRaw): self
    {
        $this->lastAccessRaw = $lastAccessRaw;

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
