<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityReservationCategoryRights
 *
 * @Table(name="reservation_category_rights")
 * @Entity
 */
class EntityReservationCategoryRights
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
     * @var integer
     *
     * @Column(name="class_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $classId;

    /**
     * @var boolean
     *
     * @Column(name="m_items", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $mItems;


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
     * @return EntityReservationCategoryRights
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
     * Set classId
     *
     * @param integer $classId
     * @return EntityReservationCategoryRights
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;

        return $this;
    }

    /**
     * Get classId
     *
     * @return integer 
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * Set mItems
     *
     * @param boolean $mItems
     * @return EntityReservationCategoryRights
     */
    public function setMItems($mItems)
    {
        $this->mItems = $mItems;

        return $this;
    }

    /**
     * Get mItems
     *
     * @return boolean 
     */
    public function getMItems()
    {
        return $this->mItems;
    }
}
