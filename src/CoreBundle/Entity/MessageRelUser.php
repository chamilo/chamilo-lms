<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="message_rel_user", indexes={
 * },
 * uniqueConstraints={
 *     @ORM\UniqueConstraint(name="message_receiver", columns={"message_id", "user_id"})
 *  },
 * )
 * @ORM\Entity()
 */
#[UniqueEntity(
    fields: ['message', 'receiver'],
    errorPath: 'message',
    message: 'This message-receiver relation is already used.',
)]
// @todo add security checks.
#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: [
    'star' => 'exact',
    'receiver' => 'exact',
    'read' => 'exact',
    'starred' => 'exact',
    'tags.tag' => 'exact',
])]
class MessageRelUser
{
    public const TYPE_TO = 1;
    public const TYPE_CC = 2;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    #[Groups(['message:read', 'message:write'])]
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="receivers", cascade={"persist"})
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=false)
     */
    protected Message $message;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", cascade={"persist"}, inversedBy="receivedMessages")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[Assert\NotNull]
    #[Groups(['message:read', 'message:write'])]
    protected User $receiver;

    /**
     * @ORM\Column(name="msg_read", type="boolean", nullable=false)
     */
    #[Groups(['message:read', 'message:write'])]
    protected bool $read;

    /**
     * @ORM\Column(name="receiver_type", type="smallint", nullable=false)
     */
    #[Groups(['message:read', 'message:write'])]
    protected int $receiverType;

    /**
     * @ORM\Column(name="starred", type="boolean", nullable=false)
     */
    #[Groups(['message:read', 'message:write'])]
    protected bool $starred;

    /**
     * @var Collection|MessageTag[]
     *
     * @ORM\ManyToMany(targetEntity="MessageTag", inversedBy="messageRelUsers", cascade={"persist"})
     * @ORM\JoinTable(name="message_rel_user_rel_tags")
     */
    #[Assert\Valid]
    #[Groups(['message:read', 'message:write'])]
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
