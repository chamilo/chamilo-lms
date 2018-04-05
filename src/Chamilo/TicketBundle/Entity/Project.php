<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TicketBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Project.
 *
 * @ORM\Table(name="ticket_project")
 * @ORM\Entity
 */
class Project
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="other_area", type="integer", nullable=true)
     */
    protected $otherArea;

    /**
     * @var int
     *
     * @ORM\Column(name="sys_insert_user_id", type="integer", nullable=false, unique=false)
     */
    protected $insertUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sys_insert_datetime", type="datetime", nullable=false, unique=false)
     */
    protected $insertDateTime;

    /**
     * @var int
     *
     * @ORM\Column(name="sys_lastedit_user_id", type="integer", nullable=true, unique=false)
     */
    protected $lastEditUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sys_lastedit_datetime", type="datetime", nullable=true, unique=false)
     */
    protected $lastEditDateTime;

    /**
     * Project constructor.
     */
    public function __construct()
    {
        $this->insertDateTime = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Project
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * @return Project
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
     * @return Project
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Project
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getOtherArea()
    {
        return $this->otherArea;
    }

    /**
     * @param string $otherArea
     *
     * @return Project
     */
    public function setOtherArea($otherArea)
    {
        $this->otherArea = $otherArea;

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
     * @return Project
     */
    public function setInsertUserId($insertUserId)
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInsertDateTime()
    {
        return $this->insertDateTime;
    }

    /**
     * @param \DateTime $insertDateTime
     *
     * @return Project
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
     * @return Project
     */
    public function setLastEditUserId($lastEditUserId)
    {
        $this->lastEditUserId = $lastEditUserId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastEditDateTime()
    {
        return $this->lastEditDateTime;
    }

    /**
     * @param \DateTime $lastEditDateTime
     *
     * @return Project
     */
    public function setLastEditDateTime($lastEditDateTime)
    {
        $this->lastEditDateTime = $lastEditDateTime;

        return $this;
    }
}
