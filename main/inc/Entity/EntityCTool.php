<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCTool
 *
 * @Table(name="c_tool")
 * @Entity
 */
class EntityCTool
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
     * @Column(name="image", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $image;

    /**
     * @var boolean
     *
     * @Column(name="visibility", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $visibility;

    /**
     * @var string
     *
     * @Column(name="admin", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $admin;

    /**
     * @var string
     *
     * @Column(name="address", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $address;

    /**
     * @var boolean
     *
     * @Column(name="added_tool", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $addedTool;

    /**
     * @var string
     *
     * @Column(name="target", type="string", precision=0, scale=0, nullable=false, unique=false)
     */
    private $target;

    /**
     * @var string
     *
     * @Column(name="category", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $category;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCTool
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
     * @return EntityCTool
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
     * Set name
     *
     * @param string $name
     * @return EntityCTool
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
     * @return EntityCTool
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
     * @return EntityCTool
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
     * Set visibility
     *
     * @param boolean $visibility
     * @return EntityCTool
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set admin
     *
     * @param string $admin
     * @return EntityCTool
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return string 
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return EntityCTool
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set addedTool
     *
     * @param boolean $addedTool
     * @return EntityCTool
     */
    public function setAddedTool($addedTool)
    {
        $this->addedTool = $addedTool;

        return $this;
    }

    /**
     * Get addedTool
     *
     * @return boolean 
     */
    public function getAddedTool()
    {
        return $this->addedTool;
    }

    /**
     * Set target
     *
     * @param string $target
     * @return EntityCTool
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return string 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return EntityCTool
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCTool
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
