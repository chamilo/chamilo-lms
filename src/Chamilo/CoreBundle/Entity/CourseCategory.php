<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseCategory
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
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\CourseCategoryRepository")
 */
class CourseCategory
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_id", type="string", length=40, nullable=true)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="tree_pos", type="integer", nullable=true)
     */
    private $treePos;

    /**
     * @var integer
     *
     * @ORM\Column(name="children_count", type="smallint", nullable=true)
     */
    private $childrenCount;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_course_child", type="string", length=40, nullable=true)
     */
    private $authCourseChild;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_cat_child", type="string", length=40, nullable=true)
     */
    private $authCatChild;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set name
     *
     * @param string $name
     * @return CourseCategory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return CourseCategory
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set parentId
     *
     * @param string $parentId
     * @return CourseCategory
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set treePos
     *
     * @param integer $treePos
     * @return CourseCategory
     */
    public function setTreePos($treePos)
    {
        $this->treePos = $treePos;

        return $this;
    }

    /**
     * Get treePos
     *
     * @return integer
     */
    public function getTreePos()
    {
        return $this->treePos;
    }

    /**
     * Set childrenCount
     *
     * @param integer $childrenCount
     * @return CourseCategory
     */
    public function setChildrenCount($childrenCount)
    {
        $this->childrenCount = $childrenCount;

        return $this;
    }

    /**
     * Get childrenCount
     *
     * @return integer
     */
    public function getChildrenCount()
    {
        return $this->childrenCount;
    }

    /**
     * Set authCourseChild
     *
     * @param string $authCourseChild
     * @return CourseCategory
     */
    public function setAuthCourseChild($authCourseChild)
    {
        $this->authCourseChild = $authCourseChild;

        return $this;
    }

    /**
     * Get authCourseChild
     *
     * @return string
     */
    public function getAuthCourseChild()
    {
        return $this->authCourseChild;
    }

    /**
     * Set authCatChild
     *
     * @param string $authCatChild
     * @return CourseCategory
     */
    public function setAuthCatChild($authCatChild)
    {
        $this->authCatChild = $authCatChild;

        return $this;
    }

    /**
     * Get authCatChild
     *
     * @return string
     */
    public function getAuthCatChild()
    {
        return $this->authCatChild;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $name = strip_tags($this->name);

        return "({$this->code}) $name";
    }
}
