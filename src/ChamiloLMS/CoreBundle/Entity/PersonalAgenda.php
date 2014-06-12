<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonalAgenda
 *
 * @ORM\Table(name="personal_agenda", indexes={@ORM\Index(name="idx_personal_agenda_user", columns={"user"}), @ORM\Index(name="idx_personal_agenda_parent", columns={"parent_event_id"})})
 * @ORM\Entity
 */
class PersonalAgenda
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $text;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="enddate", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $enddate;

    /**
     * @var string
     *
     * @ORM\Column(name="course", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $course;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_event_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $parentEventId;

    /**
     * @var integer
     *
     * @ORM\Column(name="all_day", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $allDay;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param integer $user
     * @return PersonalAgenda
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return integer 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return PersonalAgenda
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return PersonalAgenda
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return PersonalAgenda
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set enddate
     *
     * @param \DateTime $enddate
     * @return PersonalAgenda
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate
     *
     * @return \DateTime 
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set course
     *
     * @param string $course
     * @return PersonalAgenda
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course
     *
     * @return string 
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set parentEventId
     *
     * @param integer $parentEventId
     * @return PersonalAgenda
     */
    public function setParentEventId($parentEventId)
    {
        $this->parentEventId = $parentEventId;

        return $this;
    }

    /**
     * Get parentEventId
     *
     * @return integer 
     */
    public function getParentEventId()
    {
        return $this->parentEventId;
    }

    /**
     * Set allDay
     *
     * @param integer $allDay
     * @return PersonalAgenda
     */
    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Get allDay
     *
     * @return integer 
     */
    public function getAllDay()
    {
        return $this->allDay;
    }
}
