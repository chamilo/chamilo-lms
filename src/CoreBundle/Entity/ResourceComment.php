<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\TimestampableAgoTrait;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Gedmo\Tree\Traits\NestedSetEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      normalizationContext={"groups"={"comment:read"}}
 * ).
 */
#[ORM\Table(name: 'resource_comment')]
#[Gedmo\Tree(type: 'nested')]
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
class ResourceComment
{
    use TimestampableTypedEntity;
    use TimestampableAgoTrait;
    use NestedSetEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['comment:read'])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ResourceNode::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'resource_node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ResourceNode $resourceNode;

    #[Groups(['comment:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $author;

    #[Assert\NotBlank]
    #[Groups(['comment:read'])]
    #[ORM\Column(name: 'content', type: 'string', nullable: false)]
    protected string $content;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    protected ?ResourceComment $parent = null;

    #[Groups(['comment:read'])]
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    protected DateTime $createdAt;

    #[Groups(['comment:read'])]
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    protected DateTime $updatedAt;

    /**
     * @var Collection<int, ResourceComment>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    protected Collection $children;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->content = '';
        $this->children = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

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
     * @return Collection<int, ResourceComment>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }
}
