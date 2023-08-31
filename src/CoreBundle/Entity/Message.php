<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Entity\Listener\MessageListener;
use Chamilo\CoreBundle\Repository\MessageRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'message')]
#[ORM\Index(columns: ['user_sender_id'], name: 'idx_message_user_sender')]
#[ORM\Index(columns: ['group_id'], name: 'idx_message_group')]
#[ORM\Index(columns: ['msg_type'], name: 'idx_message_type')]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\EntityListeners([MessageListener::class])]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(securityPostDenormalize: "is_granted('CREATE', object)"),
    ],
    normalizationContext: [
        'groups' => ['message:read'],
    ],
    denormalizationContext: [
        'groups' => ['message:write'],
    ],
    security: "is_granted('ROLE_USER')"
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['title', 'sendDate'])]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'msgType' => 'exact',
        'status' => 'exact',
        'sender' => 'exact',
        'receivers.receiver' => 'exact',
        'receivers.tags.tag' => 'exact',
        'parent' => 'exact',
    ]
)]
#[ApiFilter(
    BooleanFilter::class,
    properties: ['receivers.read']
)]
class Message
{
    public const MESSAGE_TYPE_INBOX = 1;
    public const MESSAGE_TYPE_GROUP = 5;
    public const MESSAGE_TYPE_INVITATION = 6;
    public const MESSAGE_TYPE_CONVERSATION = 7;
    // status
    public const MESSAGE_STATUS_DELETED = 3;
    public const MESSAGE_STATUS_DRAFT = 4;
    public const MESSAGE_STATUS_INVITATION_PENDING = 5;
    public const MESSAGE_STATUS_INVITATION_ACCEPTED = 6;
    public const MESSAGE_STATUS_INVITATION_DENIED = 7;

    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(name: 'user_sender_id', referencedColumnName: 'id', nullable: false)]
    protected User $sender;

    /**
     * @var Collection<int, MessageRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageRelUser::class, cascade: ['persist', 'remove'])]
    #[Groups(['message:write'])]
    protected Collection $receivers;

    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'msg_type', type: 'smallint', nullable: false)]
    protected int $msgType;

    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'status', type: 'smallint', nullable: false)]
    protected int $status;

    #[Groups(['message:read'])]
    #[ORM\Column(name: 'send_date', type: 'datetime', nullable: false)]
    protected DateTime $sendDate;

    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'content', type: 'text', nullable: false)]
    protected string $content;

    #[ORM\ManyToOne(targetEntity: Usergroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Usergroup $group = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    protected Collection $children;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    protected ?Message $parent = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'update_date', type: 'datetime', nullable: true)]
    protected ?DateTime $updateDate;

    #[ORM\Column(name: 'votes', type: 'integer', nullable: true)]
    protected ?int $votes;

    /**
     * @var Collection<int, MessageAttachment>
     */
    #[Groups(['message:read'])]
    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageAttachment::class, cascade: ['remove', 'persist'])]
    protected Collection $attachments;

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageFeedback::class, orphanRemoval: true)]
    protected Collection $likes;

    public function __construct()
    {
        $this->sendDate = new DateTime('now');
        $this->updateDate = $this->sendDate;
        $this->msgType = self::MESSAGE_TYPE_INBOX;
        $this->content = '';
        $this->attachments = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->receivers = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->votes = 0;
        $this->status = 0;
    }

    /**
     * @return Collection<int, MessageRelUser>
     */
    public function getReceivers(): Collection
    {
        return $this->receivers;
    }

    #[Groups(['message:read'])]
    public function getReceiversTo(): array
    {
        return $this->receivers
            ->filter(
                fn (MessageRelUser $messageRelUser) => MessageRelUser::TYPE_TO === $messageRelUser->getReceiverType()
            )->getValues();
    }

    #[Groups(['message:read'])]
    public function getReceiversCc(): array
    {
        return $this->receivers
            ->filter(
                fn (MessageRelUser $messageRelUser) => MessageRelUser::TYPE_CC === $messageRelUser->getReceiverType()
            )
            ->getValues()
        ;
    }

    #[Groups(['message:read'])]
    public function getFirstReceiver(): ?MessageRelUser
    {
        if ($this->receivers->count() > 0) {
            return $this->receivers->first();
        }

        return null;
    }

    public function hasUserReceiver(User $receiver): bool
    {
        if ($this->receivers->count()) {
            $criteria = Criteria::create()
                ->where(
                    Criteria::expr()->eq('receiver', $receiver)
                )
                ->andWhere(
                    Criteria::expr()->eq('message', $this)
                )
            ;

            return $this->receivers->matching($criteria)->count() > 0;
        }

        return false;
    }

    public function addReceiverTo(User $receiver): self
    {
        $messageRelUser = (new MessageRelUser())
            ->setReceiver($receiver)
            ->setReceiverType(MessageRelUser::TYPE_TO)
        ;

        $this->addReceiver($messageRelUser);

        return $this;
    }

    public function addReceiver(MessageRelUser $messageRelUser): self
    {
        if (!$this->receivers->contains($messageRelUser)) {
            $this->receivers->add($messageRelUser);

            $messageRelUser->setMessage($this);
        }

        return $this;
    }

    public function addReceiverCc(User $receiver): self
    {
        $messageRelUser = (new MessageRelUser())
            ->setReceiver($receiver)
            ->setReceiverType(MessageRelUser::TYPE_CC)
        ;

        $this->addReceiver($messageRelUser);

        return $this;
    }

    public function removeReceiver(MessageRelUser $messageRelUser): self
    {
        $this->receivers->removeElement($messageRelUser);

        return $this;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getMsgType(): int
    {
        return $this->msgType;
    }

    public function setMsgType(int $msgType): self
    {
        $this->msgType = $msgType;

        return $this;
    }

    public function getSendDate(): DateTime
    {
        return $this->sendDate;
    }

    public function setSendDate(DateTime $sendDate): self
    {
        $this->sendDate = $sendDate;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getUpdateDate(): ?DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVotes(): int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    /**
     * @return Collection<int, MessageAttachment>
     */
    public function getAttachments(): Collection
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

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    public function getGroup(): ?Usergroup
    {
        return $this->group;
    }

    public function setGroup(?Usergroup $group): self
    {
        //        $this->msgType = self::MESSAGE_TYPE_GROUP;
        $this->group = $group;

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
