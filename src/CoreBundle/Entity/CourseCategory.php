<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CourseCategory.
 *
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     normalizationContext={"groups"={"course_category:read", "course:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"course_category:write", "course:write"}},
 * )
 * @ApiFilter(SearchFilter::class, properties={"name": "partial", "code": "partial"})
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(OrderFilter::class, properties={"name", "code"})
 * @ORM\Table(
 *  name="course_category",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="code", columns={"code"})
 *  },
 *  indexes={
 *      @ORM\Index(name="parent_id", columns={"parent_id"}),
 *      @ORM\Index(name="tree_pos", columns={"tree_pos"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\CourseCategoryRepository")
 */
class CourseCategory
{
    /**
     * @var int
     * @Groups({"course_category:read", "course:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="CourseCategory", mappedBy="parent")
     */
    protected $children;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Groups({"course_category:read", "course_category:write", "course:read"})
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Groups({"course_category:read", "course_category:write", "course:read"})
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    protected $code;

    /**
     * @ORM\ManyToOne(targetEntity="CourseCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var int
     *
     * @ORM\Column(name="tree_pos", type="integer", nullable=true)
     */
    protected $treePos;

    /**
     * @var int
     *
     * @ORM\Column(name="children_count", type="smallint", nullable=true)
     */
    protected $childrenCount;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_course_child", type="string", length=40, nullable=true)
     */
    protected $authCourseChild;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_cat_child", type="string", length=40, nullable=true)
     */
    protected $authCatChild;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    protected $image;

    /**
     * @var string
     * @Groups({"course_category:read", "course_category:write"})
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelCourseCategory", mappedBy="courseCategory", cascade={"persist"}, orphanRemoval=true)
     */
    protected $urls;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Chamilo\CoreBundle\Entity\Course", mappedBy="categories")
     */
    protected $courses;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->childrenCount = 0;
        $this->children = new ArrayCollection();
        $this->courses = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $name = strip_tags($this->name);

        return "$name ({$this->code})";
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

    /**
     * @return CourseCategory
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(self $child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    public function setParent(self $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CourseCategory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return CourseCategory
     */
    public function setCode($code)
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

    /**
     * Set parentId.
     *
     * @param string $parentId
     *
     * @return CourseCategory
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set treePos.
     *
     * @param int $treePos
     *
     * @return CourseCategory
     */
    public function setTreePos($treePos)
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

    /**
     * Set childrenCount.
     *
     * @param int $childrenCount
     *
     * @return CourseCategory
     */
    public function setChildrenCount($childrenCount)
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

    /**
     * Set authCourseChild.
     *
     * @param string $authCourseChild
     *
     * @return CourseCategory
     */
    public function setAuthCourseChild($authCourseChild)
    {
        $this->authCourseChild = $authCourseChild;

        return $this;
    }

    /**
     * Get authCourseChild.
     *
     * @return string
     */
    public function getAuthCourseChild()
    {
        return $this->authCourseChild;
    }

    /**
     * Set authCatChild.
     *
     * @param string $authCatChild
     *
     * @return CourseCategory
     */
    public function setAuthCatChild($authCatChild)
    {
        $this->authCatChild = $authCatChild;

        return $this;
    }

    /**
     * Get authCatChild.
     *
     * @return string
     */
    public function getAuthCatChild()
    {
        return $this->authCatChild;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCourses(): ArrayCollection
    {
        return $this->courses;
    }

    public function setCourses(ArrayCollection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    public function addCourse(Course $course)
    {
        $this->courses[] = $course;
    }
}
