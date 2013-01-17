<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCCalendarEvent
 *
 * @Table(name="c_calendar_event")
 * @Entity
 */
class EntityCCalendarEvent
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @Column(name="start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @Column(name="end_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $endDate;

    /**
     * @var integer
     *
     * @Column(name="parent_event_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $parentEventId;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="all_day", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $allDay;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCCalendarEvent
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCCalendarEvent
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set title
     *
     * @param string $title
     * @return EntityCCalendarEvent
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
     * Set content
     *
     * @param string $content
     * @return EntityCCalendarEvent
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return EntityCCalendarEvent
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return EntityCCalendarEvent
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set parentEventId
     *
     * @param integer $parentEventId
     * @return EntityCCalendarEvent
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCCalendarEvent
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set allDay
     *
     * @param integer $allDay
     * @return EntityCCalendarEvent
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
