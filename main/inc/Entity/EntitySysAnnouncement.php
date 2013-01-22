<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySysAnnouncement
 *
 * @Table(name="sys_announcement")
 * @Entity
 */
class EntitySysAnnouncement
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
     * @var \DateTime
     *
     * @Column(name="date_start", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateStart;

    /**
     * @var \DateTime
     *
     * @Column(name="date_end", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateEnd;

    /**
     * @var boolean
     *
     * @Column(name="visible_teacher", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibleTeacher;

    /**
     * @var boolean
     *
     * @Column(name="visible_student", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibleStudent;

    /**
     * @var boolean
     *
     * @Column(name="visible_guest", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibleGuest;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $content;

    /**
     * @var string
     *
     * @Column(name="lang", type="string", length=70, precision=0, scale=0, nullable=true, unique=false)
     */
    private $lang;

    /**
     * @var integer
     *
     * @Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlId;


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
     * Set dateStart
     *
     * @param \DateTime $dateStart
     * @return EntitySysAnnouncement
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * Get dateStart
     *
     * @return \DateTime 
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Set dateEnd
     *
     * @param \DateTime $dateEnd
     * @return EntitySysAnnouncement
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Get dateEnd
     *
     * @return \DateTime 
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Set visibleTeacher
     *
     * @param boolean $visibleTeacher
     * @return EntitySysAnnouncement
     */
    public function setVisibleTeacher($visibleTeacher)
    {
        $this->visibleTeacher = $visibleTeacher;

        return $this;
    }

    /**
     * Get visibleTeacher
     *
     * @return boolean 
     */
    public function getVisibleTeacher()
    {
        return $this->visibleTeacher;
    }

    /**
     * Set visibleStudent
     *
     * @param boolean $visibleStudent
     * @return EntitySysAnnouncement
     */
    public function setVisibleStudent($visibleStudent)
    {
        $this->visibleStudent = $visibleStudent;

        return $this;
    }

    /**
     * Get visibleStudent
     *
     * @return boolean 
     */
    public function getVisibleStudent()
    {
        return $this->visibleStudent;
    }

    /**
     * Set visibleGuest
     *
     * @param boolean $visibleGuest
     * @return EntitySysAnnouncement
     */
    public function setVisibleGuest($visibleGuest)
    {
        $this->visibleGuest = $visibleGuest;

        return $this;
    }

    /**
     * Get visibleGuest
     *
     * @return boolean 
     */
    public function getVisibleGuest()
    {
        return $this->visibleGuest;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntitySysAnnouncement
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
     * @return EntitySysAnnouncement
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
     * Set lang
     *
     * @param string $lang
     * @return EntitySysAnnouncement
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string 
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return EntitySysAnnouncement
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
}
