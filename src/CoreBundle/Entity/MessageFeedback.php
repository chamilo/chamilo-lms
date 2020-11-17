<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class MessageFeedback.
 *
 * @ORM\Table(
 *     name="message_feedback",
 *     indexes={
 *         @Index(name="idx_message_feedback_uid_mid", columns={"message_id", "user_id"})
 *     }
 * )
 * @ORM\Entity()
 */
class MessageFeedback
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Message", inversedBy="likes")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $message;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var bool
     *
     * @ORM\Column(name="liked", type="boolean", options={"default": false})
     */
    protected $liked;

    /**
     * @var bool
     *
     * @ORM\Column(name="disliked", type="boolean", options={"default": false})
     */
    protected $disliked;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt;

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
