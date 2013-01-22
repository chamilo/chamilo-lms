<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEItemProperty
 *
 * @Table(name="track_e_item_property")
 * @Entity
 */
class EntityTrackEItemProperty
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
     * @var integer
     *
     * @Column(name="course_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseId;

    /**
     * @var integer
     *
     * @Column(name="item_property_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $itemPropertyId;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @Column(name="progress", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $progress;

    /**
     * @var \DateTime
     *
     * @Column(name="lastedit_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditDate;

    /**
     * @var integer
     *
     * @Column(name="lastedit_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditUserId;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


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
     * Set courseId
     *
     * @param integer $courseId
     * @return EntityTrackEItemProperty
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId
     *
     * @return integer 
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Set itemPropertyId
     *
     * @param integer $itemPropertyId
     * @return EntityTrackEItemProperty
     */
    public function setItemPropertyId($itemPropertyId)
    {
        $this->itemPropertyId = $itemPropertyId;

        return $this;
    }

    /**
     * Get itemPropertyId
     *
     * @return integer 
     */
    public function getItemPropertyId()
    {
        return $this->itemPropertyId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityTrackEItemProperty
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
     * @return EntityTrackEItemProperty
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
     * Set progress
     *
     * @param integer $progress
     * @return EntityTrackEItemProperty
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return integer 
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set lasteditDate
     *
     * @param \DateTime $lasteditDate
     * @return EntityTrackEItemProperty
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate
     *
     * @return \DateTime 
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set lasteditUserId
     *
     * @param integer $lasteditUserId
     * @return EntityTrackEItemProperty
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId
     *
     * @return integer 
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityTrackEItemProperty
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
}
