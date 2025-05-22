<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Entity\Listener\ResourceLinkListener;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use LogicException;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ORM\Table(name: 'resource_link')]
#[ORM\Index(
    columns: ['c_id', 'session_id', 'usergroup_id', 'group_id', 'user_id', 'resource_type_group'],
    name: 'idx_resource_link_sortable_groups'
)]
#[ORM\Entity(repositoryClass: ResourceLinkRepository::class)]
#[ORM\EntityListeners([ResourceLinkListener::class])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class ResourceLink implements Stringable
{
    use SoftDeleteableEntity;
    use TimestampableTypedEntity;

    public const VISIBILITY_DRAFT = 0;
    public const VISIBILITY_PENDING = 1;
    public const VISIBILITY_PUBLISHED = 2;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ResourceNode::class, inversedBy: 'resourceLinks')]
    #[ORM\JoinColumn(name: 'resource_node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ResourceNode $resourceNode;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Course $course = null;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: Session::class, cascade: ['persist'], inversedBy: 'resourceLinks')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    #[Groups(['resource_link:read', 'resource_link:write', 'student_publication:read', 'student_publication_comment:read'])]
    protected ?Session $session = null;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: Usergroup::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Usergroup $userGroup = null;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: CGroup::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CGroup $group = null;

    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    /**
     * @var Collection<int, ResourceRight>
     */
    #[ORM\OneToMany(
        mappedBy: 'resourceLink',
        targetEntity: ResourceRight::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $resourceRights;

    #[Groups([
        'ctool:read',
        'c_tool_intro:read',
        'student_publication:read',
    ])]
    #[ORM\Column(name: 'visibility', type: 'integer', nullable: false)]
    protected int $visibility;

    #[Groups(['resource_node:read', 'resource_node:write', 'document:write', 'document:read'])]
    #[ORM\Column(name: 'start_visibility_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $startVisibilityAt = null;

    #[Groups(['resource_node:read', 'resource_node:write', 'document:write', 'document:read'])]
    #[ORM\Column(name: 'end_visibility_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $endVisibilityAt = null;

    #[Gedmo\SortablePosition]
    #[ORM\Column]
    private int $displayOrder;

    #[Gedmo\SortableGroup]
    #[ORM\Column]
    private ?int $resourceTypeGroup;

    public function __construct()
    {
        $this->resourceRights = new ArrayCollection();
        $this->visibility = self::VISIBILITY_DRAFT;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartVisibilityAt(): ?DateTimeInterface
    {
        return $this->startVisibilityAt;
    }

    public function setStartVisibilityAt(?DateTimeInterface $startVisibilityAt): self
    {
        $this->startVisibilityAt = $startVisibilityAt;

        return $this;
    }

    public function getEndVisibilityAt(): ?DateTimeInterface
    {
        return $this->endVisibilityAt;
    }

    public function setEndVisibilityAt(?DateTimeInterface $endVisibilityAt): self
    {
        $this->endVisibilityAt = $endVisibilityAt;

        return $this;
    }

    public function addResourceRight(ResourceRight $right): self
    {
        if (!$this->resourceRights->contains($right)) {
            $right->setResourceLink($this);
            $this->resourceRights->add($right);
        }

        return $this;
    }

    /**
     * @return Collection<int, ResourceRight>
     */
    public function getResourceRights(): Collection
    {
        return $this->resourceRights;
    }

    public function setResourceRights(Collection $rights): self
    {
        $this->resourceRights = $rights;

        /*foreach ($rights as $right) {
              $this->addResourceRight($right);
          }*/

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course = null): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session = null): self
    {
        $this->session = $session;

        return $this;
    }

    public function hasSession(): bool
    {
        return null !== $this->session;
    }

    public function hasCourse(): bool
    {
        return null !== $this->course;
    }

    public function hasGroup(): bool
    {
        return null !== $this->group;
    }

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group = null): self
    {
        $this->group = $group;

        return $this;
    }

    public function getUserGroup(): ?Usergroup
    {
        return $this->userGroup;
    }

    public function setUserGroup(?Usergroup $group = null): self
    {
        $this->userGroup = $group;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function hasUser(): bool
    {
        return null !== $this->user;
    }

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;
        $this->resourceTypeGroup = $resourceNode->getResourceType()->getId();

        return $this;
    }

    public function isPublished(): bool
    {
        return self::VISIBILITY_PUBLISHED === $this->getVisibility();
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        if (!\in_array($visibility, self::getVisibilityList(), true)) {
            $message = \sprintf(
                'The visibility is not valid. Valid options: %s',
                print_r(self::getVisibilityList(), true)
            );

            throw new LogicException($message);
        }
        $this->visibility = $visibility;

        return $this;
    }

    public static function getVisibilityList(): array
    {
        return [
            'Draft' => self::VISIBILITY_DRAFT,
            'Pending' => self::VISIBILITY_PENDING,
            'Published' => self::VISIBILITY_PUBLISHED,
        ];
    }

    public function isPending(): bool
    {
        return self::VISIBILITY_PENDING === $this->getVisibility();
    }

    public function isDraft(): bool
    {
        return self::VISIBILITY_DRAFT === $this->getVisibility();
    }

    public function getVisibilityName(): string
    {
        return array_flip(static::getVisibilityList())[$this->getVisibility()];
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getResourceTypeGroup(): ?int
    {
        return $this->resourceTypeGroup;
    }

    public function setResourceTypeGroup(int $resourceTypeGroup): static
    {
        $this->resourceTypeGroup = $resourceTypeGroup;

        return $this;
    }

    public function moveUpPosition(): static
    {
        $this->displayOrder--;

        return $this;
    }

    public function moveDownPosition(): static
    {
        $this->displayOrder++;

        return $this;
    }
}
