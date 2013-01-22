<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityMigrationTransaction
 *
 * @Table(name="migration_transaction")
 * @Entity
 */
class EntityMigrationTransaction
{
    /**
     * @var integer
     *
     * @Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="transaction_id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $transactionId;

    /**
     * @var integer
     *
     * @Column(name="branch_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $branchId;

    /**
     * @var string
     *
     * @Column(name="action", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $action;

    /**
     * @var string
     *
     * @Column(name="item_id", type="string", length=36, precision=0, scale=0, nullable=true, unique=false)
     */
    private $itemId;

    /**
     * @var string
     *
     * @Column(name="orig_id", type="string", length=36, precision=0, scale=0, nullable=true, unique=false)
     */
    private $origId;

    /**
     * @var string
     *
     * @Column(name="dest_id", type="string", length=36, precision=0, scale=0, nullable=true, unique=false)
     */
    private $destId;

    /**
     * @var boolean
     *
     * @Column(name="status_id", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $statusId;

    /**
     * @var \DateTime
     *
     * @Column(name="time_insert", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timeInsert;

    /**
     * @var \DateTime
     *
     * @Column(name="time_update", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timeUpdate;


    /**
     * Set id
     *
     * @param integer $id
     * @return EntityMigrationTransaction
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
     * Set transactionId
     *
     * @param integer $transactionId
     * @return EntityMigrationTransaction
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * Get transactionId
     *
     * @return integer 
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Set branchId
     *
     * @param integer $branchId
     * @return EntityMigrationTransaction
     */
    public function setBranchId($branchId)
    {
        $this->branchId = $branchId;

        return $this;
    }

    /**
     * Get branchId
     *
     * @return integer 
     */
    public function getBranchId()
    {
        return $this->branchId;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return EntityMigrationTransaction
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set itemId
     *
     * @param string $itemId
     * @return EntityMigrationTransaction
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return string 
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set origId
     *
     * @param string $origId
     * @return EntityMigrationTransaction
     */
    public function setOrigId($origId)
    {
        $this->origId = $origId;

        return $this;
    }

    /**
     * Get origId
     *
     * @return string 
     */
    public function getOrigId()
    {
        return $this->origId;
    }

    /**
     * Set destId
     *
     * @param string $destId
     * @return EntityMigrationTransaction
     */
    public function setDestId($destId)
    {
        $this->destId = $destId;

        return $this;
    }

    /**
     * Get destId
     *
     * @return string 
     */
    public function getDestId()
    {
        return $this->destId;
    }

    /**
     * Set statusId
     *
     * @param boolean $statusId
     * @return EntityMigrationTransaction
     */
    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * Get statusId
     *
     * @return boolean 
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * Set timeInsert
     *
     * @param \DateTime $timeInsert
     * @return EntityMigrationTransaction
     */
    public function setTimeInsert($timeInsert)
    {
        $this->timeInsert = $timeInsert;

        return $this;
    }

    /**
     * Get timeInsert
     *
     * @return \DateTime 
     */
    public function getTimeInsert()
    {
        return $this->timeInsert;
    }

    /**
     * Set timeUpdate
     *
     * @param \DateTime $timeUpdate
     * @return EntityMigrationTransaction
     */
    public function setTimeUpdate($timeUpdate)
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }

    /**
     * Get timeUpdate
     *
     * @return \DateTime 
     */
    public function getTimeUpdate()
    {
        return $this->timeUpdate;
    }
}
