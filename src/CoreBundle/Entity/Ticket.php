<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'ticket_ticket')]
#[ORM\Entity]
class Ticket
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'code', type: 'string', length: 255, nullable: false)]
    protected string $code;

    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: false)]
    protected string $subject;

    #[ORM\Column(name: 'message', type: 'text', nullable: true)]
    protected ?string $message = null;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\TicketProject::class)]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id')]
    protected TicketProject $project;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\TicketCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    protected TicketCategory $category;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\TicketPriority::class)]
    #[ORM\JoinColumn(name: 'priority_id', referencedColumnName: 'id')]
    protected TicketPriority $priority;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Session $session;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'personal_email', type: 'string', length: 255, nullable: false)]
    protected string $personalEmail;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class)]
    #[ORM\JoinColumn(name: 'assigned_last_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $assignedLastUser = null;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\TicketStatus::class)]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id')]
    protected TicketStatus $status;

    #[ORM\Column(name: 'total_messages', type: 'integer', nullable: false)]
    protected int $totalMessages;

    #[ORM\Column(name: 'keyword', type: 'string', length: 255, nullable: true)]
    protected ?string $keyword = null;

    #[ORM\Column(name: 'source', type: 'string', length: 255, nullable: true)]
    protected ?string $source = null;

    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $startDate = null;

    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $endDate = null;

    #[ORM\Column(name: 'sys_insert_user_id', type: 'integer')]
    protected int $insertUserId;

    #[ORM\Column(name: 'sys_insert_datetime', type: 'datetime')]
    protected DateTime $insertDateTime;

    #[ORM\Column(name: 'sys_lastedit_user_id', type: 'integer', nullable: true, unique: false)]
    protected int $lastEditUserId;

    #[ORM\Column(name: 'sys_lastedit_datetime', type: 'datetime', nullable: true, unique: false)]
    protected DateTime $lastEditDateTime;

    #[ORM\Column(name: 'exercise_id', type: 'integer', nullable: true, unique: false)]
    protected int $exerciseId;

    #[ORM\Column(name: 'lp_id', type: 'integer', nullable: true, unique: false)]
    protected int $lpId;

    public function __construct()
    {
        $this->totalMessages = 0;
        $this->insertDateTime = new DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getAssignedLastUser(): ?User
    {
        return $this->assignedLastUser;
    }

    public function setAssignedLastUser(?User $assignedLastUser): self
    {
        $this->assignedLastUser = $assignedLastUser;

        return $this;
    }

    public function getPersonalEmail(): string
    {
        return $this->personalEmail;
    }

    public function setPersonalEmail(string $personalEmail): self
    {
        $this->personalEmail = $personalEmail;

        return $this;
    }

    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }

    public function setTotalMessages(int $totalMessages): self
    {
        $this->totalMessages = $totalMessages;

        return $this;
    }

    public function getInsertUserId(): int
    {
        return $this->insertUserId;
    }

    public function setInsertUserId(int $insertUserId): self
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    public function getExerciseId(): int
    {
        return $this->exerciseId;
    }

    public function setExerciseId(int $exerciseId): self
    {
        $this->exerciseId = $exerciseId;

        return $this;
    }

    public function getLpId(): int
    {
        return $this->lpId;
    }

    public function setLpId(int $lpId): self
    {
        $this->lpId = $lpId;

        return $this;
    }
}
