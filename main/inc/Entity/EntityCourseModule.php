<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCourseModule
 *
 * @Table(name="course_module")
 * @Entity
 */
class EntityCourseModule
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
     * @Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="link", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $link;

    /**
     * @var string
     *
     * @Column(name="image", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $image;

    /**
     * @var integer
     *
     * @Column(name="row", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $row;

    /**
     * @var integer
     *
     * @Column(name="column", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $column;

    /**
     * @var string
     *
     * @Column(name="position", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $position;


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
     * @return EntityCourseModule
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
     * Set link
     *
     * @param string $link
     * @return EntityCourseModule
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return EntityCourseModule
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set row
     *
     * @param integer $row
     * @return EntityCourseModule
     */
    public function setRow($row)
    {
        $this->row = $row;

        return $this;
    }

    /**
     * Get row
     *
     * @return integer 
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * Set column
     *
     * @param integer $column
     * @return EntityCourseModule
     */
    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Get column
     *
     * @return integer 
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return EntityCourseModule
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string 
     */
    public function getPosition()
    {
        return $this->position;
    }
}
