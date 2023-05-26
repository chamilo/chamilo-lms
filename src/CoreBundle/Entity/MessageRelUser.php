<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[UniqueEntity(fields: ['message', 'receiver'], errorPath: 'message', message: 'This message-receiver relation is already used.')]
#[ORM\Table(name: 'message_rel_user')]
#[ORM\UniqueConstraint(name: 'message_receiver', columns: ['message_id', 'user_id'])]
#[ORM\Entity]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['star' => 'exact', 'receiver' => 'exact', 'read' => 'exact', 'starred' => 'exact', 'tags.tag' => 'exact'])]
#[ApiResource(uriTemplate: '/messages/{id}/receivers.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Message::class, identifiers: ['id'])], status: 200, filters: ['annotated_chamilo_core_bundle_entity_message_rel_user_api_platform_core_bridge_doctrine_orm_filter_search_filter'], operations: [new GetCollection()])]
class MessageRelUser
{
    public const TYPE_TO = 1;
    public const TYPE_CC = 2;
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Message::class, inversedBy: 'receivers', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', nullable: false)]
    protected Message $message;
    #[Assert\NotNull]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, cascade: ['persist'], inversedBy: 'receivedMessages')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $receiver;
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'msg_read', type: 'boolean', nullable: false)]
    protected bool $read;
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'receiver_type', type: 'smallint', nullable: false)]
    protected int $receiverType;
    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'starred', type: 'boolean', nullable: false)]
    protected bool $starred;
    /**
     * @var Collection|MessageTag[]
     */
    #[Assert\Valid]
    #[Groups(['message:read', 'message:write'])]
    #[ORM\JoinTable(name: 'message_rel_user_rel_tags')]
    #[ORM\ManyToMany(targetEntity: \Chamilo\CoreBundle\Entity\MessageTag::class, inversedBy: 'messageRelUsers', cascade: ['persist', 'remove'])]
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
    public function getTags(): Collection|array
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
