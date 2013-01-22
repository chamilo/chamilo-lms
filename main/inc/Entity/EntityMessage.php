<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityMessage
 *
 * @Table(name="message")
 * @Entity
 */
class EntityMessage
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
     * @Column(name="user_sender_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userSenderId;

    /**
     * @var integer
     *
     * @Column(name="user_receiver_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userReceiverId;

    /**
     * @var boolean
     *
     * @Column(name="msg_status", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $msgStatus;

    /**
     * @var \DateTime
     *
     * @Column(name="send_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sendDate;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @Column(name="group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @Column(name="parent_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentId;

    /**
     * @var \DateTime
     *
     * @Column(name="update_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $updateDate;


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
     * Set userSenderId
     *
     * @param integer $userSenderId
     * @return EntityMessage
     */
    public function setUserSenderId($userSenderId)
    {
        $this->userSenderId = $userSenderId;

        return $this;
    }

    /**
     * Get userSenderId
     *
     * @return integer 
     */
    public function getUserSenderId()
    {
        return $this->userSenderId;
    }

    /**
     * Set userReceiverId
     *
     * @param integer $userReceiverId
     * @return EntityMessage
     */
    public function setUserReceiverId($userReceiverId)
    {
        $this->userReceiverId = $userReceiverId;

        return $this;
    }

    /**
     * Get userReceiverId
     *
     * @return integer 
     */
    public function getUserReceiverId()
    {
        return $this->userReceiverId;
    }

    /**
     * Set msgStatus
     *
     * @param boolean $msgStatus
     * @return EntityMessage
     */
    public function setMsgStatus($msgStatus)
    {
        $this->msgStatus = $msgStatus;

        return $this;
    }

    /**
     * Get msgStatus
     *
     * @return boolean 
     */
    public function getMsgStatus()
    {
        return $this->msgStatus;
    }

    /**
     * Set sendDate
     *
     * @param \DateTime $sendDate
     * @return EntityMessage
     */
    public function setSendDate($sendDate)
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    /**
     * Get sendDate
     *
     * @return \DateTime 
     */
    public function getSendDate()
    {
        return $this->sendDate;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityMessage
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
     * @return EntityMessage
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
     * Set groupId
     *
     * @param integer $groupId
     * @return EntityMessage
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer 
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return EntityMessage
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer 
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return EntityMessage
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime 
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }
}
