<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityReservationItemRights
 *
 * @Table(name="reservation_item_rights")
 * @Entity
 */
class EntityReservationItemRights
{
    /**
     * @var integer
     *
     * @Column(name="item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $itemId;

    /**
     * @var integer
     *
     * @Column(name="class_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $classId;

    /**
     * @var boolean
     *
     * @Column(name="edit_right", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $editRight;

    /**
     * @var boolean
     *
     * @Column(name="delete_right", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $deleteRight;

    /**
     * @var boolean
     *
     * @Column(name="m_reservation", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $mReservation;

    /**
     * @var boolean
     *
     * @Column(name="view_right", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $viewRight;


    /**
     * Set itemId
     *
     * @param integer $itemId
     * @return EntityReservationItemRights
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set classId
     *
     * @param integer $classId
     * @return EntityReservationItemRights
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
     * Set editRight
     *
     * @param boolean $editRight
     * @return EntityReservationItemRights
     */
    public function setEditRight($editRight)
    {
        $this->editRight = $editRight;

        return $this;
    }

    /**
     * Get editRight
     *
     * @return boolean 
     */
    public function getEditRight()
    {
        return $this->editRight;
    }

    /**
     * Set deleteRight
     *
     * @param boolean $deleteRight
     * @return EntityReservationItemRights
     */
    public function setDeleteRight($deleteRight)
    {
        $this->deleteRight = $deleteRight;

        return $this;
    }

    /**
     * Get deleteRight
     *
     * @return boolean 
     */
    public function getDeleteRight()
    {
        return $this->deleteRight;
    }

    /**
     * Set mReservation
     *
     * @param boolean $mReservation
     * @return EntityReservationItemRights
     */
    public function setMReservation($mReservation)
    {
        $this->mReservation = $mReservation;

        return $this;
    }

    /**
     * Get mReservation
     *
     * @return boolean 
     */
    public function getMReservation()
    {
        return $this->mReservation;
    }

    /**
     * Set viewRight
     *
     * @param boolean $viewRight
     * @return EntityReservationItemRights
     */
    public function setViewRight($viewRight)
    {
        $this->viewRight = $viewRight;

        return $this;
    }

    /**
     * Get viewRight
     *
     * @return boolean 
     */
    public function getViewRight()
    {
        return $this->viewRight;
    }
}
