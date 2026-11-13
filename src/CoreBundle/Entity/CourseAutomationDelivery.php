<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Persistent recipient-level deduplication for course certificate mail.
 * A row in 'sending' may represent an email sent just before process failure;
 * it is deliberately never retried automatically.
 */
#[ORM\Entity]
#[ORM\Table(name: 'course_automation_delivery')]
#[ORM\UniqueConstraint(name: 'uniq_course_automation_recipient', columns: ['action_id', 'user_id'])]
#[ORM\Index(columns: ['user_id'], name: 'idx_course_automation_delivery_user')]
class CourseAutomationDelivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CourseAutomationAction::class)]
    #[ORM\JoinColumn(name: 'action_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CourseAutomationAction $action;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'status', type: 'string', length: 16, options: ['default' => 'sending'])]
    private string $status = 'sending';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'sent_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $sentAt = null;

    #[ORM\Column(name: 'last_error', type: 'text', nullable: true)]
    private ?string $lastError = null;

    public function __construct(CourseAutomationAction $action, User $user)
    {
        $this->action = $action;
        $this->user = $user;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
