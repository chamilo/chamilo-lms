<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityNotification
 *
 * @Table(name="notification")
 * @Entity
 */
class EntityNotification
{
    /**
     * @var integer
     *
     * @Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="dest_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $destUserId;

    /**
     * @var string
     *
     * @Column(name="dest_mail", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $destMail;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var integer
     *
     * @Column(name="sender_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $senderId;

    /**
     * @var string
     *
     * @Column(name="content", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @Column(name="send_freq", type="smallint", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sendFreq;

    /**
     * @var \DateTime
     *
     * @Column(name="created_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @Column(name="sent_at", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sentAt;


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
     * Set destUserId
     *
     * @param integer $destUserId
     * @return EntityNotification
     */
    public function setDestUserId($destUserId)
    {
        $this->destUserId = $destUserId;

        return $this;
    }

    /**
     * Get destUserId
     *
     * @return integer 
     */
    public function getDestUserId()
    {
        return $this->destUserId;
    }

    /**
     * Set destMail
     *
     * @param string $destMail
     * @return EntityNotification
     */
    public function setDestMail($destMail)
    {
        $this->destMail = $destMail;

        return $this;
    }

    /**
     * Get destMail
     *
     * @return string 
     */
    public function getDestMail()
    {
        return $this->destMail;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityNotification
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
     * Set senderId
     *
     * @param integer $senderId
     * @return EntityNotification
     */
    public function setSenderId($senderId)
    {
        $this->senderId = $senderId;

        return $this;
    }

    /**
     * Get senderId
     *
     * @return integer 
     */
    public function getSenderId()
    {
        return $this->senderId;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return EntityNotification
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
     * Set sendFreq
     *
     * @param integer $sendFreq
     * @return EntityNotification
     */
    public function setSendFreq($sendFreq)
    {
        $this->sendFreq = $sendFreq;

        return $this;
    }

    /**
     * Get sendFreq
     *
     * @return integer 
     */
    public function getSendFreq()
    {
        return $this->sendFreq;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EntityNotification
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set sentAt
     *
     * @param \DateTime $sentAt
     * @return EntityNotification
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get sentAt
     *
     * @return \DateTime 
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }
}
