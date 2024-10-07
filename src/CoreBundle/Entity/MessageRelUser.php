<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new Put(security: "is_granted('EDIT', object)"),
        new Patch(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)"),
    ],
    normalizationContext: [
        'groups' => ['message_rel_user:read'],
    ],
    denormalizationContext: [
        'groups' => ['message_rel_user:write'],
    ],
)]
#[UniqueEntity(
    fields: ['message', 'receiver'],
    message: 'This message-receiver relation is already used.',
    errorPath: 'message'
)]
#[ORM\Table(name: 'message_rel_user')]
#[ORM\UniqueConstraint(name: 'message_receiver', columns: ['message_id', 'user_id', 'receiver_type'])]
#[ORM\Entity]
#[Gedmo\SoftDeleteable(timeAware: true)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'star' => 'exact',
        'receiver' => 'exact',
        'read' => 'exact',
        'starred' => 'exact',
        'tags.tag' => 'exact',
    ]
)]
class MessageRelUser
{
    use SoftDeleteableEntity;

    // Type indicating the message is sent to the main recipient
    public const TYPE_TO = 1;
    // Type indicating the message is sent as a carbon copy (CC) to the recipient
    public const TYPE_CC = 2;
    // Type indicating the message is promoted
    public const TYPE_PROMOTED = 3;
    // Type indicating the message is posted on the user's wall
    public const TYPE_WALL = 4;
    // Type indicating the message is sent to a group
    public const TYPE_GROUP = 5;
    // Type indicating the message is an invitation
    public const TYPE_INVITATION = 6;
    // Type indicating the message is part of a conversation
    public const TYPE_CONVERSATION = 7;
    // Type indicating the message is sent by the sender and should appear in the sender's outbox
    public const TYPE_SENDER = 8;

    #[Groups(['message_rel_user:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups(['message_rel_user:read'])]
    #[ORM\ManyToOne(targetEntity: Message::class, cascade: ['persist'], inversedBy: 'receivers')]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', nullable: false)]
    protected Message $message;

    #[Assert\NotNull]
    #[Groups(['message:read', 'message:write', 'message_rel_user:read'])]
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'receivedMessages')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $receiver;

    #[Groups(['message:read', 'message:write', 'message_rel_user:read', 'message_rel_user:write'])]
    #[ORM\Column(name: 'msg_read', type: 'boolean', nullable: false)]
    protected bool $read;

    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'receiver_type', type: 'smallint', nullable: false)]
    protected int $receiverType;

    #[Groups(['message:read', 'message:write', 'message_rel_user:read', 'message_rel_user:write'])]
    #[ORM\Column(name: 'starred', type: 'boolean', nullable: false)]
    protected bool $starred;

    /**
     * @var Collection<int, MessageTag>
     */
    #[Assert\Valid]
    #[Groups(['message:read', 'message_rel_user:read', 'message_rel_user:write'])]
    #[ORM\JoinTable(name: 'message_rel_user_rel_tags')]
    #[ORM\ManyToMany(targetEntity: MessageTag::class, inversedBy: 'messageRelUsers', cascade: ['persist', 'remove'])]
    protected Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->read = false;
        $this->starred = false;
        $this->receiverType = self::TYPE_TO;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, MessageTag>
     */
    public function getTags(): Collection
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

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getReceiver(): User
    {
        return $this->receiver;
    }

    public function setReceiver(User $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getReceiverType(): int
    {
        return $this->receiverType;
    }

    public function setReceiverType(int $receiverType): self
    {
        $this->receiverType = $receiverType;

        return $this;
    }
}
