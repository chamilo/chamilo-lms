<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * One configured action for a manual course opening or closing event.
 * A ResourceLink target is scoped to the same course/session as this record.
 */
#[ORM\Entity]
#[ORM\Table(name: 'course_automation_action')]
#[ORM\UniqueConstraint(name: 'uniq_course_automation_configuration', columns: ['configuration_key'])]
#[ORM\Index(columns: ['c_id', 'session_id', 'event_type'], name: 'idx_course_automation_scope')]
class CourseAutomationAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: ResourceLink::class)]
    #[ORM\JoinColumn(name: 'resource_link_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?ResourceLink $resourceLink = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(name: 'event_type', type: 'string', length: 16)]
    private string $eventType;

    #[ORM\Column(name: 'action_type', type: 'string', length: 32)]
    private string $actionType;

    #[ORM\Column(name: 'target_id', type: 'integer', nullable: true)]
    private ?int $targetId = null;

    #[ORM\Column(name: 'setting_value', type: 'integer', nullable: true)]
    private ?int $settingValue = null;

    #[ORM\Column(name: 'configuration_key', type: 'string', length: 64)]
    private string $configurationKey;

    #[ORM\Column(name: 'status', type: 'string', length: 16, options: ['default' => 'pending'])]
    private string $status = 'pending';

    #[ORM\Column(name: 'attempts', type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'last_executed_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $lastExecutedBy = null;

    #[ORM\Column(name: 'last_error', type: 'text', nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(name: 'last_executed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastExecutedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(Course $course, ?Session $session, string $eventType, string $actionType, User $createdBy)
    {
        $this->course = $course;
        $this->session = $session;
        $this->eventType = $eventType;
        $this->actionType = $actionType;
        $this->createdBy = $createdBy;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCourse(): Course
    {
        return $this->course;
    }
    public function getSession(): ?Session
    {
        return $this->session;
    }
    public function getEventType(): string
    {
        return $this->eventType;
    }
    public function getActionType(): string
    {
        return $this->actionType;
    }
    public function getResourceLink(): ?ResourceLink
    {
        return $this->resourceLink;
    }
    public function setResourceLink(?ResourceLink $link): void
    {
        $this->resourceLink = $link;
    }
    public function getTargetId(): ?int
    {
        return $this->targetId;
    }
    public function setTargetId(?int $id): void
    {
        $this->targetId = $id;
    }
    public function getSettingValue(): ?int
    {
        return $this->settingValue;
    }
    public function setSettingValue(?int $value): void
    {
        $this->settingValue = $value;
    }
    public function setConfigurationKey(string $key): void
    {
        $this->configurationKey = $key;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getLastError(): ?string
    {
        return $this->lastError;
    }
    public function getAttempts(): int
    {
        return $this->attempts;
    }
    public function getLastExecutedAt(): ?DateTimeImmutable
    {
        return $this->lastExecutedAt;
    }
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getLastExecutedBy(): ?User
    {
        return $this->lastExecutedBy;
    }

    public function markRunning(User $user): void
    {
        $this->status = 'running';
        $this->lastExecutedBy = $user;
        $this->lastError = null;
        ++$this->attempts;
    }

    public function markSuccess(): void
    {
        $this->status = 'success';
        $this->lastExecutedAt = new DateTimeImmutable();
        $this->lastError = null;
    }

    public function markFailed(string $reason): void
    {
        $this->status = 'failed';
        $this->lastError = mb_substr($reason, 0, 1000);
        $this->lastExecutedAt = new DateTimeImmutable();
    }
}
