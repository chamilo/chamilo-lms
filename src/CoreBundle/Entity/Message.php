<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message.
 *
 * @ORM\Table(name="message", indexes={
 *     @ORM\Index(name="idx_message_user_sender", columns={"user_sender_id"}),
 *     @ORM\Index(name="idx_message_user_receiver", columns={"user_receiver_id"}),
 *     @ORM\Index(name="idx_message_user_sender_user_receiver", columns={"user_sender_id", "user_receiver_id"}),
 *     @ORM\Index(name="idx_message_user_receiver_status", columns={"user_receiver_id", "msg_status"}),
 *     @ORM\Index(name="idx_message_receiver_status_send_date", columns={"user_receiver_id", "msg_status", "send_date"}),
 *     @ORM\Index(name="idx_message_group", columns={"group_id"}),
 *     @ORM\Index(name="idx_message_status", columns={"msg_status"})
 * })
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\MessageRepository")
 */
class Message
{
    public const MESSAGE_TYPE_INBOX = 1;
    public const MESSAGE_TYPE_OUTBOX = 2;
    public const MESSAGE_TYPE_PROMOTED = 3;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    #[Assert\NotBlank]
    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="sentMessages")
     * @ORM\JoinColumn(name="user_sender_id", referencedColumnName="id", nullable=false)
     */
    protected User $userSender;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="receivedMessages")
     * @ORM\JoinColumn(name="user_receiver_id", referencedColumnName="id", nullable=true)
     */
    protected User $userReceiver;

    /**
     * @ORM\Column(name="msg_status", type="smallint", nullable=false)
     */
    protected int $msgStatus;

    /**
     * @ORM\Column(name="send_date", type="datetime", nullable=false)
     */
    protected DateTime $sendDate;

    #[Assert\NotBlank]
    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    #[Assert\NotBlank]
    /**
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    protected string $content;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroup")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    protected ?CGroup $group = null;

    /**
     * @var Collection|Message[]
     * @ORM\OneToMany(targetEntity="Message", mappedBy="parent")
     */
    protected Collection $children;

    /**
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected ?Message $parent = null;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="update_date", type="datetime", nullable=true)
     */
    protected ?DateTime $updateDate;

    /**
     * @ORM\Column(name="votes", type="integer", nullable=true)
     */
    protected ?int $votes;

    /**
     * @var Collection|MessageAttachment[]
     *
     * @ORM\OneToMany(targetEntity="MessageAttachment", mappedBy="message")
     */
    protected Collection $attachments;

    /**
     * @var Collection|MessageFeedback[]
     *
     * @ORM\OneToMany(targetEntity="MessageFeedback", mappedBy="message", orphanRemoval=true)
     */
    protected Collection $likes;

    public function __construct()
    {
        $this->sendDate = new DateTime('now');
        $this->updateDate = $this->sendDate;
        $this->content = '';
        $this->attachments = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->votes = 0;
    }

    public function setUserSender(User $userSender): self
    {
        $this->userSender = $userSender;

        return $this;
    }

    public function getUserSender(): User
    {
        return $this->userSender;
    }

    public function setUserReceiver(User $userReceiver): self
    {
        $this->userReceiver = $userReceiver;

        return $this;
    }

    /**
     * Get userReceiver.
     *
     * @return User
     */
    public function getUserReceiver()
    {
        return $this->userReceiver;
    }

    public function setMsgStatus(int $msgStatus): self
    {
        $this->msgStatus = $msgStatus;

        return $this;
    }

    public function getMsgStatus(): int
    {
        return $this->msgStatus;
    }

    public function setSendDate(DateTime $sendDate): self
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    /**
     * Get sendDate.
     *
     * @return DateTime
     */
    public function getSendDate()
    {
        return $this->sendDate;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setContent(string $content): self
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

    public function setUpdateDate(DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return DateTime
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

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    public function getVotes(): int
    {
        return $this->votes;
    }

    /**
     * Get attachments.
     *
     * @return Collection|MessageAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    public function addAttachment(MessageAttachment $attachment): self
    {
        $this->attachments->add($attachment);
        $attachment->setMessage($this);

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @return Collection|Message[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    public function setParent(self $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return MessageFeedback[]|Collection
     */
    public function getLikes()
    {
        return $this->likes;
    }

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group): Message
    {
        $this->group = $group;

        return $this;
    }
}
