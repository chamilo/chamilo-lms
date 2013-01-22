<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySysCalendar
 *
 * @Table(name="sys_calendar")
 * @Entity
 */
class EntitySysCalendar
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
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
     * @Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @Column(name="all_day", type="integer", precision=0, scale=0, nullable=false, unique=false)
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
     * Set title
     *
     * @param string $title
     * @return EntitySysCalendar
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
     * @return EntitySysCalendar
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
     * @return EntitySysCalendar
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
     * @return EntitySysCalendar
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
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return EntitySysCalendar
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId
     *
     * @return integer 
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set allDay
     *
     * @param integer $allDay
     * @return EntitySysCalendar
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
