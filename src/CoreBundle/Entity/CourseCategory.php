<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CourseCategory.
 *
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
     *
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
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
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
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelCourseCategory", mappedBy="courseCategory", cascade={"persist"}, orphanRemoval=true)
     */
    protected $urls;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->childrenCount = 0;
        $this->children = new ArrayCollection();
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

    /**
     * @param CourseCategory $child
     */
    public function addChild(CourseCategory $child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * @param CourseCategory $parent
     */
    public function setParent(CourseCategory $parent)
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

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     *
     * @return CourseCategory
     */
    public function setImage(string $image): CourseCategory
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return CourseCategory
     */
    public function setDescription(string $description): CourseCategory
    {
        $this->description = $description;

        return $this;
    }
}
