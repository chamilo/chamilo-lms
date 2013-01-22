<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCourseCategory
 *
 * @Table(name="course_category")
 * @Entity
 */
class EntityCourseCategory
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="name", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $code;

    /**
     * @var string
     *
     * @Column(name="parent_id", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @Column(name="tree_pos", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $treePos;

    /**
     * @var integer
     *
     * @Column(name="children_count", type="smallint", precision=0, scale=0, nullable=true, unique=false)
     */
    private $childrenCount;

    /**
     * @var string
     *
     * @Column(name="auth_course_child", type="string", precision=0, scale=0, nullable=true, unique=false)
     */
    private $authCourseChild;

    /**
     * @var string
     *
     * @Column(name="auth_cat_child", type="string", precision=0, scale=0, nullable=true, unique=false)
     */
    private $authCatChild;


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
     * Set name
     *
     * @param string $name
     * @return EntityCourseCategory
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
     * @return EntityCourseCategory
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
     * @return EntityCourseCategory
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
     * @return EntityCourseCategory
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
     * @return EntityCourseCategory
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
     * @return EntityCourseCategory
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
     * @return EntityCourseCategory
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
}
