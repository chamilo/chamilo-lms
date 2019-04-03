<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Message.
 *
 * @ORM\Table(name="message", indexes={
 *     @ORM\Index(name="idx_message_user_sender", columns={"user_sender_id"}),
 *     @ORM\Index(name="idx_message_user_receiver", columns={"user_receiver_id"}),
 *     @ORM\Index(name="idx_message_user_sender_user_receiver", columns={"user_sender_id", "user_receiver_id"}),
 *     @ORM\Index(name="idx_message_user_receiver_status", columns={"user_receiver_id", "msg_status"}),
 *     @ORM\Index(name="idx_message_group", columns={"group_id"}),
 *     @ORM\Index(name="idx_message_parent", columns={"parent_id"})
 * })
 * @ORM\Entity
 */
class Message
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_sender_id", type="integer", nullable=false)
     */
    protected $userSenderId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_receiver_id", type="integer", nullable=false)
     */
    protected $userReceiverId;

    /**
     * @var bool
     *
     * @ORM\Column(name="msg_status", type="boolean", nullable=false)
     */
    protected $msgStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_date", type="datetime", nullable=false)
     */
    protected $sendDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    protected $content;

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    protected $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    protected $parentId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetime", nullable=true)
     */
    protected $updateDate;

    /**
     * @var int
     *
     * @ORM\Column(name="votes", type="integer", nullable=true)
     */
    protected $votes;

    /**
     * @var ArrayCollection
     *
     * Add @ to the next line if api_get_configuration_value('social_enable_messages_feedback') is true
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\MessageFeedback", mappedBy="message", orphanRemoval=true)
     */
    protected $likes;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->likes = new ArrayCollection();
    }

    /**
     * Set userSenderId.
     *
     * @param int $userSenderId
     *
     * @return Message
     */
    public function setUserSenderId($userSenderId)
    {
        $this->userSenderId = $userSenderId;

        return $this;
    }

    /**
     * Get userSenderId.
     *
     * @return int
     */
    public function getUserSenderId()
    {
        return $this->userSenderId;
    }

    /**
     * Set userReceiverId.
     *
     * @param int $userReceiverId
     *
     * @return Message
     */
    public function setUserReceiverId($userReceiverId)
    {
        $this->userReceiverId = $userReceiverId;

        return $this;
    }

    /**
     * Get userReceiverId.
     *
     * @return int
     */
    public function getUserReceiverId()
    {
        return $this->userReceiverId;
    }

    /**
     * Set msgStatus.
     *
     * @param bool $msgStatus
     *
     * @return Message
     */
    public function setMsgStatus($msgStatus)
    {
        $this->msgStatus = $msgStatus;

        return $this;
    }

    /**
     * Get msgStatus.
     *
     * @return bool
     */
    public function getMsgStatus()
    {
        return $this->msgStatus;
    }

    /**
     * Set sendDate.
     *
     * @param \DateTime $sendDate
     *
     * @return Message
     */
    public function setSendDate($sendDate)
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    /**
     * Get sendDate.
     *
     * @return \DateTime
     */
    public function getSendDate()
    {
        return $this->sendDate;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Message
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
     * Set content.
     *
     * @param string $content
     *
     * @return Message
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return Message
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set parentId.
     *
     * @param int $parentId
     *
     * @return Message
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return Message
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
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
     * Set votes.
     *
     * @param int $votes
     *
     * @return Message
     */
    public function setVotes($votes)
    {
        $this->votes = $votes;

        return $this;
    }

    /**
     * Get votes.
     *
     * @return int
     */
    public function getVotes()
    {
        return $this->votes;
    }
}
