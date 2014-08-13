<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BranchTransaction
 *
 * @ORM\Table(name="branch_transaction")
 * @ORM\Entity
 */
class BranchTransaction
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transaction_id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $transactionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="branch_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $branchId;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(name="item_id", type="string", length=36, precision=0, scale=0, nullable=true, unique=false)
     */
    private $itemId;

    /**
     * @var string
     *
     * @ORM\Column(name="dest_id", type="string", length=36, precision=0, scale=0, nullable=true, unique=false)
     */
    private $destId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_id", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $statusId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_insert", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timeInsert;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_update", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timeUpdate;


    /**
     * Set id
     *
     * @param integer $id
     * @return BranchTransaction
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
     * @return BranchTransaction
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
     * @return BranchTransaction
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
     * @return BranchTransaction
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
     * @return BranchTransaction
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
     * Set destId
     *
     * @param string $destId
     * @return BranchTransaction
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
     * @return BranchTransaction
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
     * @return BranchTransaction
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
     * @return BranchTransaction
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
