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
    protected string $code = '';

    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: false)]
    protected string $subject = '';

    #[ORM\Column(name: 'message', type: 'text', nullable: true)]
    protected ?string $message = null;

    #[ORM\ManyToOne(targetEntity: TicketProject::class)]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id')]
    protected TicketProject $project;

    #[ORM\ManyToOne(targetEntity: TicketCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    protected TicketCategory $category;

    #[ORM\ManyToOne(targetEntity: TicketPriority::class)]
    #[ORM\JoinColumn(name: 'priority_id', referencedColumnName: 'id')]
    protected TicketPriority $priority;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Session $session = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'personal_email', type: 'string', length: 255, nullable: false)]
    protected string $personalEmail = '';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_last_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $assignedLastUser = null;

    #[ORM\ManyToOne(targetEntity: TicketStatus::class)]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id')]
    protected TicketStatus $status;

    #[ORM\Column(name: 'total_messages', type: 'integer', nullable: false)]
    protected int $totalMessages = 0;

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
    protected ?int $lastEditUserId = null;

    #[ORM\Column(name: 'sys_lastedit_datetime', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $lastEditDateTime = null;

    #[ORM\Column(name: 'exercise_id', type: 'integer', nullable: true, unique: false)]
    protected ?int $exerciseId = null;

    #[ORM\Column(name: 'lp_id', type: 'integer', nullable: true, unique: false)]
    protected ?int $lpId = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: true)]
    protected ?AccessUrl $accessUrl = null;

    public function __construct()
    {
        $this->insertDateTime = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getProject(): TicketProject
    {
        return $this->project;
    }

    public function setProject(TicketProject $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getCategory(): TicketCategory
    {
        return $this->category;
    }

    public function setCategory(TicketCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getPriority(): TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): self
    {
        $this->priority = $priority;

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

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

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

    public function getAssignedLastUser(): ?User
    {
        return $this->assignedLastUser;
    }

    public function setAssignedLastUser(?User $assignedLastUser): self
    {
        $this->assignedLastUser = $assignedLastUser;

        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): self
    {
        $this->status = $status;

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

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(?string $keyword): self
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate): self
    {
        $this->endDate = $endDate;

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

    public function getInsertDateTime(): DateTime
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(DateTime $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    public function getLastEditUserId(): ?int
    {
        return $this->lastEditUserId;
    }

    public function setLastEditUserId(?int $lastEditUserId): self
    {
        $this->lastEditUserId = $lastEditUserId;

        return $this;
    }

    public function getLastEditDateTime(): ?DateTime
    {
        return $this->lastEditDateTime;
    }

    public function setLastEditDateTime(?DateTime $lastEditDateTime): self
    {
        $this->lastEditDateTime = $lastEditDateTime;

        return $this;
    }

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }

    public function setExerciseId(?int $exerciseId): self
    {
        $this->exerciseId = $exerciseId;

        return $this;
    }

    public function getLpId(): ?int
    {
        return $this->lpId;
    }

    public function setLpId(?int $lpId): self
    {
        $this->lpId = $lpId;

        return $this;
    }

    public function getAccessUrl(): ?AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(?AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }
}
