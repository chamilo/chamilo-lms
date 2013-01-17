<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCCourseSetting
 *
 * @Table(name="c_course_setting")
 * @Entity
 */
class EntityCCourseSetting
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="variable", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $variable;

    /**
     * @var string
     *
     * @Column(name="subkey", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $subkey;

    /**
     * @var string
     *
     * @Column(name="type", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @Column(name="category", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $category;

    /**
     * @var string
     *
     * @Column(name="value", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $value;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="comment", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @Column(name="subkeytext", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $subkeytext;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCCourseSetting
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCCourseSetting
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set variable
     *
     * @param string $variable
     * @return EntityCCourseSetting
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get variable
     *
     * @return string 
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Set subkey
     *
     * @param string $subkey
     * @return EntityCCourseSetting
     */
    public function setSubkey($subkey)
    {
        $this->subkey = $subkey;

        return $this;
    }

    /**
     * Get subkey
     *
     * @return string 
     */
    public function getSubkey()
    {
        return $this->subkey;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return EntityCCourseSetting
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return EntityCCourseSetting
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return EntityCCourseSetting
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityCCourseSetting
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return EntityCCourseSetting
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set subkeytext
     *
     * @param string $subkeytext
     * @return EntityCCourseSetting
     */
    public function setSubkeytext($subkeytext)
    {
        $this->subkeytext = $subkeytext;

        return $this;
    }

    /**
     * Get subkeytext
     *
     * @return string 
     */
    public function getSubkeytext()
    {
        return $this->subkeytext;
    }
}
