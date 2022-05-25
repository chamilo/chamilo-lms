<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Category.
 *
 * @ORM\Table(name="ticket_category")
 * @ORM\Entity
 */
class TicketCategory
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="total_tickets", type="integer", nullable=false)
     */
    protected int $totalTickets;

    /**
     * @ORM\Column(name="course_required", type="boolean", nullable=false)
     */
    protected bool $courseRequired;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TicketProject")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id")
     */
    protected TicketProject $project;

    /**
     * @ORM\Column(name="sys_insert_user_id", type="integer")
     */
    protected int $insertUserId;

    /**
     * @ORM\Column(name="sys_insert_datetime", type="datetime")
     */
    protected DateTime $insertDateTime;

    /**
     * @ORM\Column(name="sys_lastedit_user_id", type="integer", nullable=true, unique=false)
     */
    protected ?int $lastEditUserId = null;

    /**
     * @ORM\Column(name="sys_lastedit_datetime", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $lastEditDateTime = null;

    public function __construct()
    {
        $this->totalTickets = 0;
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
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalTickets()
    {
        return $this->totalTickets;
    }

    public function setTotalTickets(int $totalTickets): self
    {
        $this->totalTickets = $totalTickets;

        return $this;
    }

    public function isCourseRequired(): bool
    {
        return $this->courseRequired;
    }

    public function setCourseRequired(bool $courseRequired): self
    {
        $this->courseRequired = $courseRequired;

        return $this;
    }

    /**
     * @return TicketProject
     */
    public function getProject()
    {
        return $this->project;
    }

    public function setProject(TicketProject $project): self
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return int
     */
    public function getInsertUserId()
    {
        return $this->insertUserId;
    }

    /**
     * @return TicketCategory
     */
    public function setInsertUserId(int $insertUserId)
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getInsertDateTime()
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(DateTime $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getLastEditUserId()
    {
        return $this->lastEditUserId;
    }

    /**
     * @return TicketCategory
     */
    public function setLastEditUserId(int $lastEditUserId)
    {
        $this->lastEditUserId = $lastEditUserId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastEditDateTime()
    {
        return $this->lastEditDateTime;
    }

    public function setLastEditDateTime(DateTime $lastEditDateTime): self
    {
        $this->lastEditDateTime = $lastEditDateTime;

        return $this;
    }
}
