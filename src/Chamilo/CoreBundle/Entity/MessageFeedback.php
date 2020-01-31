<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * Class MessageFeedback.
 *
 * @package Chamilo\CoreBundle\Entity
 *
 * @ORM\Table(
 *     name="message_feedback",
 *     indexes={
 *         @Index(name="idx_message_feedback_uid_mid", columns={"message_id", "user_id"})
 *     }
 * )
 * Add @ to the next line if api_get_configuration_value('social_enable_messages_feedback') is true
 * ORM\Entity()
 */
class MessageFeedback
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Message", inversedBy="likes")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $message;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $user;
    /**
     * @var bool
     *
     * @ORM\Column(name="liked", type="boolean", options={"default": false})
     */
    private $liked;
    /**
     * @var bool
     *
     * @ORM\Column(name="disliked", type="boolean", options={"default": false})
     */
    private $disliked;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return MessageFeedback
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return MessageFeedback
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return MessageFeedback
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLiked()
    {
        return $this->liked;
    }

    /**
     * @param bool $liked
     *
     * @return MessageFeedback
     */
    public function setLiked($liked)
    {
        $this->liked = $liked;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisliked()
    {
        return $this->disliked;
    }

    /**
     * @param bool $disliked
     *
     * @return MessageFeedback
     */
    public function setDisliked($disliked)
    {
        $this->disliked = $disliked;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return MessageFeedback
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
