<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message.
 *
 * @ORM\Table(name="message", indexes={
 *     @ORM\Index(name="idx_message_user_sender", columns={"user_sender_id"}),
 *     @ORM\Index(name="idx_message_group", columns={"group_id"}),
 *     @ORM\Index(name="idx_message_type", columns={"msg_type"})
 * })
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\MessageRepository")
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\MessageListener"})
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'security' => "is_granted('ROLE_USER')",  // the get collection is also filtered by MessageExtension.php
        ],
        'post' => [
            //'security' => "is_granted('ROLE_USER')",
            /*'messenger' => true,
            'output' => false,
            'status' => 202,*/
            'security_post_denormalize' => "is_granted('CREATE', object)",
            //            'deserialize' => false,
            //            'controller' => Create::class,
            //            'openapi_context' => [
            //                'requestBody' => [
            //                    'content' => [
            //                        'multipart/form-data' => [
            //                            'schema' => [
            //                                'type' => 'object',
            //                                'properties' => [
            //                                    'title' => [
            //                                        'type' => 'string',
            //                                    ],
            //                                    'content' => [
            //                                        'type' => 'string',
            //                                    ],
            //                                ],
            //                            ],
            //                        ],
            //                    ],
            //                ],
            //            ],
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('VIEW', object)",
        ],
        'put' => [
            'security' => "is_granted('EDIT', object)",
        ],
        'delete' => [
            'security' => "is_granted('DELETE', object)",
        ],
    ],
    attributes: [
        'security' => "is_granted('ROLE_USER')",
    ],
    denormalizationContext: [
        'groups' => ['message:write'],
    ],
    normalizationContext: [
        'groups' => ['message:read'],
    ],
)]
#[ApiFilter(OrderFilter::class, properties: ['title', 'sendDate'])]
#[ApiFilter(SearchFilter::class, properties: [
    'read' => 'exact',
    'status' => 'exact',
    'msgType' => 'exact',
    'sender' => 'exact',
    'tags' => 'exact',
    'receivers' => 'exact',
])]
class Message
{
    public const MESSAGE_TYPE_INBOX = 1;
    public const MESSAGE_TYPE_OUTBOX = 2;
    public const MESSAGE_TYPE_PROMOTED = 3;
    public const MESSAGE_TYPE_WALL = 4;
    public const MESSAGE_TYPE_GROUP = 5;
    public const MESSAGE_TYPE_INVITATION = 6;
    public const MESSAGE_TYPE_CONVERSATION = 7;

    // status
    public const MESSAGE_STATUS_DELETED = 3;
    public const MESSAGE_STATUS_DRAFT = 4;

    public const MESSAGE_STATUS_INVITATION_PENDING = 5;
    public const MESSAGE_STATUS_INVITATION_ACCEPTED = 6;
    public const MESSAGE_STATUS_INVITATION_DENIED = 7;

    public const MESSAGE_STATUS_PROMOTED = 13;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['message:read'])]
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="sentMessages")
     * @ORM\JoinColumn(name="user_sender_id", referencedColumnName="id", nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    protected User $sender;

    /**
     * @var Collection<int, User>|User[]
     *
     * @ORM\ManyToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\User",
     *     inversedBy="receivedMessages",
     *     cascade={"persist"}
     * )
     * @ORM\JoinTable(name="message_rel_user")
     */
    #[Groups(['message:read', 'message:write'])]
    protected array | null | Collection $receivers;

    /**
     * @ORM\Column(name="msg_type", type="smallint", nullable=false)
     */
    #[Assert\NotBlank]
    // @todo use enums with PHP 8.1
    /*#[Assert\Choice([
        self::MESSAGE_TYPE_INBOX,
        self::MESSAGE_TYPE_OUTBOX,
        self::MESSAGE_TYPE_PROMOTED,
    ])]*/
    /*#[ApiProperty(attributes: [
        'openapi_context' => [
            'type' => 'int',
            'enum' => [self::MESSAGE_TYPE_INBOX, self::MESSAGE_TYPE_OUTBOX],
        ],
    ])]*/
    #[Groups(['message:read', 'message:write'])]
    protected int $msgType;

    /**
     * @ORM\Column(name="status", type="smallint", nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    protected int $status;

    /**
     * @ORM\Column(name="msg_read", type="boolean", nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['message:read', 'message:write'])]
    protected bool $read;

    /**
     * @ORM\Column(name="starred", type="boolean", nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['message:read', 'message:write'])]
    protected bool $starred;

    /**
     * @ORM\Column(name="send_date", type="datetime", nullable=false)
     */
    #[Groups(['message:read'])]
    protected DateTime $sendDate;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
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

    /**
     * @var Collection|MessageTag[]
     *
     * @ORM\ManyToMany(targetEntity="Chamilo\CoreBundle\Entity\MessageTag", inversedBy="messages", cascade={"persist"})
     * @ORM\JoinTable(name="message_rel_tags")
     */
    #[Groups(['message:read', 'message:write'])]
    protected Collection $tags;

    public function __construct()
    {
        $this->sendDate = new DateTime('now');
        $this->updateDate = $this->sendDate;
        $this->content = '';
        $this->attachments = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->receivers = new ArrayCollection();
        $this->votes = 0;
        $this->status = 0;
        $this->read = false;
        $this->starred = false;
    }

    /**
     * @return null|Collection|User[]
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    public function addReceiver(User $receiver): self
    {
        if (!$this->receivers->contains($receiver)) {
            $this->receivers->add($receiver);
        }

        return $this;
    }

    public function setReceivers($receivers): self
    {
        $this->receivers = $receivers;

        return $this;
    }

    /**
     * @return Collection|MessageTag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function addTag(MessageTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(MessageTag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setMsgType(int $msgType): self
    {
        $this->msgType = $msgType;

        return $this;
    }

    public function getMsgType(): int
    {
        return $this->msgType;
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

    public function getContent(): string
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

    public function setGroup(?CGroup $group): self
    {
        $this->msgType = self::MESSAGE_TYPE_GROUP;
        $this->group = $group;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function setRead(bool $read): self
    {
        $this->read = $read;

        return $this;
    }

    public function isStarred(): bool
    {
        return $this->starred;
    }

    public function setStarred(bool $starred): self
    {
        $this->starred = $starred;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
