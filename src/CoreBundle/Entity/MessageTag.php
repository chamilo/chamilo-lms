<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message tag.
 *
 * @ORM\Table(
 *     name="message_tag",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(
 *            name="user_tag",
 *            columns={"user_id", "tag"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\MessageTagRepository")
 */
#[UniqueEntity(
    fields: ['user', 'tag'],
    errorPath: 'tag',
    message: 'This value is already used.',
)]
#[ApiResource(
    collectionOperations: [
        'get' => [
            //'security' => "is_granted('ROLE_ADMIN')",
        ],
        'post' => [
            //'security' => "is_granted('ROLE_ADMIN') or object.user == user",
        ],
    ],
    itemOperations: [
        'get' => [
            //'security' => "is_granted('ROLE_ADMIN')",
        ],
        'put' => [
            //'security' => "is_granted('ROLE_ADMIN') or object.user == user",
        ],
        'delete' => [
            //'security' => "is_granted('ROLE_ADMIN') or object.user == user",
        ],
    ],
    attributes: [
        'security' => 'is_granted("ROLE_USER") or object.user == user',
    ],
    denormalizationContext: [
        'groups' => ['message_tag:write'],
    ],
    normalizationContext: [
        'groups' => ['message_tag:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'user' => 'exact',
])]
class MessageTag
{
    use TimestampableTypedEntity;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    #[Groups(['message_tag:read'])]
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="messageTags")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="cascade")
     */
    #[Assert\NotBlank]
    #[Groups(['message_tag:read', 'message_tag:write'])]
    protected User $user;

    /**
     * @ORM\Column(name="tag", type="string", nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['message_tag:read', 'message_tag:write'])]
    protected string $tag;

    /**
     * @ORM\Column(name="color", type="string", nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['message_tag:read', 'message_tag:write'])]
    protected string $color;

    /**
     * @var Collection|Message[]
     *
     * @ORM\ManyToMany(targetEntity="Chamilo\CoreBundle\Entity\Message", mappedBy="tags", cascade={"persist"})
     */
    protected Collection $messages;

    public function __construct()
    {
        $this->color = 'blue';
        $this->messages = new ArrayCollection();
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

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->addTag($this);
        }

        return $this;
    }

    public function removeArticle(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            $message->removeTag($this);
        }

        return $this;
    }
}
