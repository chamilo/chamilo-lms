<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\EventCollectiveTrait;
use Chamilo\CoreBundle\Traits\EventSubscribableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * PersonalAgenda.
 *
 * @ORM\Table(name="personal_agenda", indexes={@ORM\Index(name="idx_personal_agenda_user", columns={"user"}),
 * @ORM\Index(name="idx_personal_agenda_parent", columns={"parent_event_id"})})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\PersonalAgendaRepository")
 */
class PersonalAgenda
{
    // Uncomment next line when activating the agenda_collective_invitations configuration setting.
    //use EventCollectiveTrait;
    // Uncomment next line when activating the agenda_event_subscriptions configuration setting.
    //use EventSubscribableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user", type="integer", nullable=true)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    protected $text;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    protected $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="enddate", type="datetime", nullable=true)
     */
    protected $enddate;

    /**
     * @var string
     *
     * @ORM\Column(name="course", type="string", length=255, nullable=true)
     */
    protected $course;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_event_id", type="integer", nullable=true)
     */
    protected $parentEventId;

    /**
     * @var int
     *
     * @ORM\Column(name="all_day", type="integer", nullable=false)
     */
    protected $allDay;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=255, nullable=true)
     */
    protected $color;

    /**
     * Set user.
     *
     * @param int $user
     *
     * @return PersonalAgenda
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return PersonalAgenda
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set text.
     *
     * @param string $text
     *
     * @return PersonalAgenda
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return PersonalAgenda
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set enddate.
     *
     * @param \DateTime $enddate
     *
     * @return PersonalAgenda
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate.
     *
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set course.
     *
     * @param string $course
     *
     * @return PersonalAgenda
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course.
     *
     * @return string
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set parentEventId.
     *
     * @param int $parentEventId
     *
     * @return PersonalAgenda
     */
    public function setParentEventId($parentEventId)
    {
        $this->parentEventId = $parentEventId;

        return $this;
    }

    /**
     * Get parentEventId.
     *
     * @return int
     */
    public function getParentEventId()
    {
        return $this->parentEventId;
    }

    /**
     * Set allDay.
     *
     * @param int $allDay
     *
     * @return PersonalAgenda
     */
    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Get allDay.
     *
     * @return int
     */
    public function getAllDay()
    {
        return $this->allDay;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     *
     * @return PersonalAgenda
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }
}
