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
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="total_tickets", type="integer", nullable=false)
     */
    protected int $totalTickets;

    /**
     * @ORM\Column(name="course_required", type="boolean", nullable=false)
     */
    protected bool $courseRequired;

    /**
     * @ORM\ManyToOne(targetEntity="TicketProject")
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
    protected ?int $lastEditUserId;

    /**
     * @ORM\Column(name="sys_lastedit_datetime", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $lastEditDateTime;

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

    /**
     * @param string $name
     *
     * @return TicketCategory
     */
    public function setName($name)
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

    /**
     * @param string $description
     *
     * @return TicketCategory
     */
    public function setDescription($description)
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

    /**
     * @param int $totalTickets
     *
     * @return TicketCategory
     */
    public function setTotalTickets($totalTickets)
    {
        $this->totalTickets = $totalTickets;

        return $this;
    }

    public function isCourseRequired(): bool
    {
        return $this->courseRequired;
    }

    /**
     * @param bool $courseRequired
     *
     * @return TicketCategory
     */
    public function setCourseRequired($courseRequired)
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

    /**
     * @param TicketProject $project
     *
     * @return TicketCategory
     */
    public function setProject($project)
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
     * @param int $insertUserId
     *
     * @return TicketCategory
     */
    public function setInsertUserId($insertUserId)
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

    /**
     * @param DateTime $insertDateTime
     *
     * @return TicketCategory
     */
    public function setInsertDateTime($insertDateTime)
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
     * @param int $lastEditUserId
     *
     * @return TicketCategory
     */
    public function setLastEditUserId($lastEditUserId)
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

    /**
     * @param DateTime $lastEditDateTime
     *
     * @return TicketCategory
     */
    public function setLastEditDateTime($lastEditDateTime)
    {
        $this->lastEditDateTime = $lastEditDateTime;

        return $this;
    }
}
