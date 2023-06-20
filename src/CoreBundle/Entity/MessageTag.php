<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(securityPostDenormalize: "is_granted('CREATE', object)"),
    ],
    normalizationContext: [
        'groups' => ['message_tag:read'],
    ],
    denormalizationContext: [
        'groups' => ['message_tag:write'],
    ],
    security: 'is_granted("ROLE_USER") or object.user == user'
)]
#[UniqueEntity(
    fields: ['user', 'tag'],
    message: 'This user-tag relation is already used.',
    errorPath: 'tag'
)]
#[ORM\Table(name: 'message_tag')]
#[ORM\UniqueConstraint(name: 'user_tag', columns: ['user_id', 'tag'])]
#[ORM\Entity(repositoryClass: MessageTagRepository::class)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: ['user' => 'exact', 'tag' => 'word_start']
)]
class MessageTag
{
    use TimestampableTypedEntity;

    #[Groups(['message_tag:read', 'message:read'])]
    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['message_tag:read', 'message_tag:write'])]
    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messageTags')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    protected User $user;

    #[Assert\NotBlank]
    #[Groups(['message_tag:read', 'message_tag:write', 'message:read'])]
    #[ORM\Column(name: 'tag', type: 'string', nullable: false)]
    protected string $tag;

    #[Assert\NotBlank]
    #[Groups(['message_tag:read', 'message_tag:write'])]
    #[ORM\Column(name: 'color', type: 'string', nullable: false)]
    protected string $color;

    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'position', type: 'integer')]
    protected int $position;

    /**
     * @var Collection<int, MessageRelUser>
     */
    #[ORM\ManyToMany(targetEntity: MessageRelUser::class, mappedBy: 'tags', cascade: ['persist'])]
    protected Collection $messageRelUsers;

    public function __construct()
    {
        $this->color = 'blue';
        $this->messageRelUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messageRelUsers;
    }

    public function addMessage(MessageRelUser $message): self
    {
        if (!$this->messageRelUsers->contains($message)) {
            $this->messageRelUsers->add($message);
            $message->addTag($this);
        }

        return $this;
    }

    public function removeMessage(MessageRelUser $message): self
    {
        if ($this->messageRelUsers->contains($message)) {
            $this->messageRelUsers->removeElement($message);
            $message->removeTag($this);
        }

        return $this;
    }
}
