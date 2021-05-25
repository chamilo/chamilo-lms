<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource()
 * @ORM\Entity
 * @ORM\Table(name="resource_link")
 */
class ResourceLink
{
    public const VISIBILITY_DRAFT = 0;
    public const VISIBILITY_PENDING = 1;
    public const VISIBILITY_PUBLISHED = 2;
    public const VISIBILITY_DELETED = 3;

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ResourceNode $resourceNode;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Course $course = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Session $session = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroup")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    protected ?CGroup $group = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup")
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Usergroup $userGroup = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected ?User $user = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\ResourceRight",
     *     mappedBy="resourceLink", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     *
     * @var Collection|ResourceRight[]
     */
    protected Collection $resourceRights;

    /**
     * @Groups({"ctool:read"})
     * @ORM\Column(name="visibility", type="integer", nullable=false)
     */
    protected int $visibility;

    /**
     * @Groups({"resource_node:read", "resource_node:write", "document:write", "document:read"})
     *
     * @ORM\Column(name="start_visibility_at", type="datetime", nullable=true)
     */
    protected ?DateTimeInterface $startVisibilityAt = null;

    /**
     * @Groups({"resource_node:read", "resource_node:write", "document:write", "document:read"})
     *
     * @ORM\Column(name="end_visibility_at", type="datetime", nullable=true)
     */
    protected ?DateTimeInterface $endVisibilityAt = null;

    public function __construct()
    {
        $this->resourceRights = new ArrayCollection();
        $this->visibility = self::VISIBILITY_DRAFT;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }

    public function getStartVisibilityAt()
    {
        return $this->startVisibilityAt;
    }

    public function setStartVisibilityAt(?DateTimeInterface $startVisibilityAt): self
    {
        $this->startVisibilityAt = $startVisibilityAt;

        return $this;
    }

    public function getEndVisibilityAt()
    {
        return $this->endVisibilityAt;
    }

    public function setEndVisibilityAt(?DateTimeInterface $endVisibilityAt): self
    {
        $this->endVisibilityAt = $endVisibilityAt;

        return $this;
    }

    public function setResourceRights($rights): self
    {
        $this->resourceRights = $rights;

        /*foreach ($rights as $right) {
            $this->addResourceRight($right);
        }*/

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
     * @return Collection|ResourceRight[]
     */
    public function getResourceRights()
    {
        return $this->resourceRights;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(Course $course = null): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(Session $session = null): self
    {
        $this->session = $session;

        return $this;
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

    public function setGroup(CGroup $group = null): self
    {
        $this->group = $group;

        return $this;
    }

    public function getUserGroup(): ?Usergroup
    {
        return $this->userGroup;
    }

    public function setUserGroup(Usergroup $group = null): self
    {
        $this->userGroup = $group;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function hasUser(): bool
    {
        return null !== $this->user;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        if (!\in_array($visibility, self::getVisibilityList(), true)) {
            throw new LogicException('The visibility is not valid');
        }

        $this->visibility = $visibility;

        return $this;
    }

    public function isPublished(): bool
    {
        return self::VISIBILITY_PUBLISHED === $this->getVisibility();
    }

    public function isPending(): bool
    {
        return self::VISIBILITY_PENDING === $this->getVisibility();
    }

    public function isDraft(): bool
    {
        return self::VISIBILITY_DRAFT === $this->getVisibility();
    }

    public static function getVisibilityList(): array
    {
        return [
            'Draft' => self::VISIBILITY_DRAFT,
            'Pending' => self::VISIBILITY_PENDING,
            'Published' => self::VISIBILITY_PUBLISHED,
            'Deleted' => self::VISIBILITY_DELETED,
        ];
    }

    public function getVisibilityName(): string
    {
        return array_flip($this->getVisibilityList())[$this->getVisibility()];
    }
}
