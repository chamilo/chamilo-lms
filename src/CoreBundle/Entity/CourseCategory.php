<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     normalizationContext={"groups"={"course_category:read", "course:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"course_category:write", "course:write"}},
 * )
 * @ApiFilter(SearchFilter::class, properties={"name":"partial", "code":"partial"})
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(OrderFilter::class, properties={"name", "code"})
 * @ORM\Table(
 *     name="course_category",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="code", columns={"code"})
 *     },
 *     indexes={
 *         @ORM\Index(name="parent_id", columns={"parent_id"}),
 *         @ORM\Index(name="tree_pos", columns={"tree_pos"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\CourseCategoryRepository")
 */
class CourseCategory
{
    /**
     * @Groups({"course_category:read", "course:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @ORM\OneToMany(targetEntity="CourseCategory", mappedBy="parent")
     *
     * @var Collection|CourseCategory[]
     */
    protected Collection $children;

    /**
     * @Groups({"course_category:read", "course_category:write", "course:read"})
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    #[Assert\NotBlank]
    protected string $name;

    /**
     * @Groups({"course_category:read", "course_category:write", "course:read"})
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $code;

    /**
     * @ORM\ManyToOne(targetEntity="CourseCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?CourseCategory $parent = null;

    /**
     * @ORM\Column(name="tree_pos", type="integer", nullable=true)
     */
    protected ?int $treePos = null;

    /**
     * @ORM\Column(name="children_count", type="smallint", nullable=true)
     */
    protected ?int $childrenCount;

    /**
     * @ORM\Column(name="auth_course_child", type="string", length=40, nullable=true)
     */
    protected ?string $authCourseChild = null;

    /**
     * @ORM\Column(name="auth_cat_child", type="string", length=40, nullable=true)
     */
    protected ?string $authCatChild = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Asset", cascade={"remove"} )
     * @ORM\JoinColumn(name="asset_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?Asset $asset = null;

    /**
     * @Groups({"course_category:read", "course_category:write"})
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelCourseCategory",
     *     mappedBy="courseCategory", cascade={"persist"}, orphanRemoval=true
     * )
     *
     * @var AccessUrlRelCourseCategory[]|Collection
     */
    protected Collection $urls;

    /**
     * @var Course[]|Collection
     * @ORM\ManyToMany(targetEntity="Course", mappedBy="categories")
     */
    protected Collection $courses;

    public function __construct()
    {
        $this->childrenCount = 0;
        $this->authCatChild = 'TRUE';
        $this->authCourseChild = 'TRUE';
        $this->urls = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->courses = new ArrayCollection();
    }

    public function __toString(): string
    {
        $name = strip_tags($this->name);

        return sprintf('%s (%s)', $name, $this->code);
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function setTreePos(int $treePos): self
    {
        $this->treePos = $treePos;

        return $this;
    }

    /**
     * Get treePos.
     *
     * @return int
     */
    public function getTreePos()
    {
        return $this->treePos;
    }

    public function setChildrenCount(int $childrenCount): self
    {
        $this->childrenCount = $childrenCount;

        return $this;
    }

    /**
     * Get childrenCount.
     *
     * @return int
     */
    public function getChildrenCount()
    {
        return $this->childrenCount;
    }

    public function setAuthCourseChild(string $authCourseChild): self
    {
        $this->authCourseChild = $authCourseChild;

        return $this;
    }

    public function getAuthCourseChild(): ?string
    {
        return $this->authCourseChild;
    }

    public function setAuthCatChild(string $authCatChild): self
    {
        $this->authCatChild = $authCatChild;

        return $this;
    }

    public function getAuthCatChild(): ?string
    {
        return $this->authCatChild;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function hasAsset(): bool
    {
        return null !== $this->asset;
    }

    public function setAsset(?Asset $asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function setCourses(Collection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    public function addCourse(Course $course): void
    {
        $this->courses[] = $course;
    }

    /**
     * @return AccessUrlRelCourseCategory[]|Collection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @param AccessUrlRelCourseCategory[]|Collection $urls
     */
    public function setUrls($urls): self
    {
        $this->urls = $urls;

        return $this;
    }

    public function addUrl(AccessUrl $accessUrl): self
    {
        if (!$this->hasUrl($accessUrl)) {
            $item = (new AccessUrlRelCourseCategory())
                ->setCourseCategory($this)
                ->setUrl($accessUrl)
            ;
            $this->urls->add($item);
        }

        return $this;
    }

    public function hasUrl(AccessUrl $accessUrl): bool
    {
        if (0 !== $this->urls->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('url', $accessUrl)
            );
            $relation = $this->urls->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }
}
