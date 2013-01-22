<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityReservationItem
 *
 * @Table(name="reservation_item")
 * @Entity
 */
class EntityReservationItem
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
     * @var integer
     *
     * @Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryId;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var string
     *
     * @Column(name="name", type="string", length=128, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     * @var boolean
     *
     * @Column(name="blackout", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $blackout;

    /**
     * @var integer
     *
     * @Column(name="creator", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $creator;

    /**
     * @var boolean
     *
     * @Column(name="always_available", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $alwaysAvailable;


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
     * Set categoryId
     *
     * @param integer $categoryId
     * @return EntityReservationItem
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer 
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntityReservationItem
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode
     *
     * @return string 
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return EntityReservationItem
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
     * Set description
     *
     * @param string $description
     * @return EntityReservationItem
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set blackout
     *
     * @param boolean $blackout
     * @return EntityReservationItem
     */
    public function setBlackout($blackout)
    {
        $this->blackout = $blackout;

        return $this;
    }

    /**
     * Get blackout
     *
     * @return boolean 
     */
    public function getBlackout()
    {
        return $this->blackout;
    }

    /**
     * Set creator
     *
     * @param integer $creator
     * @return EntityReservationItem
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return integer 
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set alwaysAvailable
     *
     * @param boolean $alwaysAvailable
     * @return EntityReservationItem
     */
    public function setAlwaysAvailable($alwaysAvailable)
    {
        $this->alwaysAvailable = $alwaysAvailable;

        return $this;
    }

    /**
     * Get alwaysAvailable
     *
     * @return boolean 
     */
    public function getAlwaysAvailable()
    {
        return $this->alwaysAvailable;
    }
}
