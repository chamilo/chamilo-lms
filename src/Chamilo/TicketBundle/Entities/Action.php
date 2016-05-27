<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TicketBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\TicketBundle\Entity\Project;
use Chamilo\TicketBundle\Entity\Priority;

/**
 * Ticket
 *
 * @ORM\Table(
 *  name="ticket_ticket",
 * )
 * @ORM\Entity
 */
class Ticket
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ticket_code", type="string", length=255, nullable=false)
     */
    protected $code;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\TicketBundle\Project")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id")
     **/
    protected $project;


    /**
     * @var Priority
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\TicketBundle\Project\Priority")
     * @ORM\JoinColumn(name="priority_id", referencedColumnName="id")
     **/
    protected $priority;


    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     **/
    protected $course;

    /**
     * @var Session
     *
     * @ORM\ManyToOne(targetEntity="Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     **/
    protected $session;

    /**
     * @var string
     *
     * @ORM\Column(name="personal_email", type="string", length=255, nullable=false)
     */
    protected $personalEmail;


    /**
     * @var integer
     *
     * @ORM\Column(name="assigned_last_user", type="integer", nullable=true)
     */
    protected $assignedLastUser;
    protected $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_messages", type="int", length=255, nullable=false)
     */
    protected $totalMessages;

    /**
     * @var string
     *
     * @ORM\Column(name="keyword", type="string", length=255, nullable=true)
     */
    protected $keyword;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    protected $source;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true, unique=false)
     */
    protected $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true, unique=false)
     */
    protected $endDate;

    /**
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="sys_lastedit_user_id", type="integer", nullable=false, unique=false)
     */
    protected $lastEditUserId

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sys_lastedit_datetime", type="datetime", nullable=true, unique=false)
     */
    protected $lastEditDateTime;

    /**
     *
     * @Gedmo\SortableGroup
     * @ORM\ManyToOne(targetEntity="Chamilo\SkillBundle\Entity\Profile", inversedBy="level")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     **/
    protected $category;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
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
     * @return Level
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
     * @return Level
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     * @return Level
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * @param mixed $shortName
     * @return Level
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;

        return $this;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param mixed $profile
     * @return Level
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;

        return $this;
    }


}
