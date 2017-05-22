<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Message
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
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_sender_id", type="integer", nullable=false)
     */
    private $userSenderId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_receiver_id", type="integer", nullable=false)
     */
    private $userReceiverId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="msg_status", type="boolean", nullable=false)
     */
    private $msgStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_date", type="datetime", nullable=false)
     */
    private $sendDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    private $parentId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetime", nullable=true)
     */
    private $updateDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="votes", type="integer", nullable=true)
     */
    private $votes;

    /**
     * Set userSenderId
     *
     * @param integer $userSenderId
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * Set votes
     *
     * @param integer $votes
     * @return Message
     */
    public function setVotes($votes)
    {
        $this->votes = $votes;

        return $this;
    }

    /**
     * Get votes
     *
     * @return integer
     */
    public function getVotes()
    {
        return $this->votes;
    }
}
