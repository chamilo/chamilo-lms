<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCAnnouncement
 *
 * @Table(name="c_announcement")
 * @Entity
 */
class EntityCAnnouncement
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
     * @Column(name="title", type="text", precision=0, scale=0, nullable=true, unique=false)
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
     * @Column(name="end_date", type="date", precision=0, scale=0, nullable=true, unique=false)
     */
    private $endDate;

    /**
     * @var integer
     *
     * @Column(name="display_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayOrder;

    /**
     * @var boolean
     *
     * @Column(name="email_sent", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $emailSent;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCAnnouncement
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
     * @return EntityCAnnouncement
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
     * @return EntityCAnnouncement
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
     * @return EntityCAnnouncement
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
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return EntityCAnnouncement
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
     * Set displayOrder
     *
     * @param integer $displayOrder
     * @return EntityCAnnouncement
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return integer 
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set emailSent
     *
     * @param boolean $emailSent
     * @return EntityCAnnouncement
     */
    public function setEmailSent($emailSent)
    {
        $this->emailSent = $emailSent;

        return $this;
    }

    /**
     * Get emailSent
     *
     * @return boolean 
     */
    public function getEmailSent()
    {
        return $this->emailSent;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCAnnouncement
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
