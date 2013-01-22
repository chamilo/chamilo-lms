<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCLinkCategory
 *
 * @Table(name="c_link_category")
 * @Entity
 */
class EntityCLinkCategory
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
     * @Column(name="category_title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryTitle;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @Column(name="display_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayOrder;

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
     * @return EntityCLinkCategory
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
     * @return EntityCLinkCategory
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
     * Set categoryTitle
     *
     * @param string $categoryTitle
     * @return EntityCLinkCategory
     */
    public function setCategoryTitle($categoryTitle)
    {
        $this->categoryTitle = $categoryTitle;

        return $this;
    }

    /**
     * Get categoryTitle
     *
     * @return string 
     */
    public function getCategoryTitle()
    {
        return $this->categoryTitle;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityCLinkCategory
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
     * Set displayOrder
     *
     * @param integer $displayOrder
     * @return EntityCLinkCategory
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return integer 
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCLinkCategory
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
