<?php
/* For licensing terms, see /license.txt */

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
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var Room
     *
     * @ORM\ManyToOne(targetEntity="BranchTransactionStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     **/
    private $branchTransactionStatus;

    /**
     * @var integer
     *
     * @ORM\Column(name="transaction_id", type="bigint", nullable=false, unique=false)
     */
    private $externalTransactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=20, nullable=true, unique=false)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(name="item_id", type="string", length=255, nullable=true, unique=false)
     */
    private $itemId;

    /**
     * @var string
     *
     * @ORM\Column(name="origin", type="string", length=255, nullable=true, unique=false)
     */
    private $origin;

    /**
     * @var string
     *
     * @ORM\Column(name="dest_id", type="string", length=255, nullable=true, unique=false)
     */
    private $destId;

    /**
     * @var string
     *
     * @ORM\Column(name="external_info", type="string", length=255, nullable=true, unique=false)
     */
    private $externalInfo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_insert", type="datetime", nullable=false, unique=false)
     */
    private $timeInsert;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_update", type="datetime", nullable=false, unique=false)
     */
    private $timeUpdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="failed_attempts", type="integer", nullable=false, unique=false)
     */
    private $failedAttempts;

    /**
     * @var BranchSync
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\BranchSync")
     * @ORM\JoinColumn(name="branch_id", referencedColumnName="id")
     **/
    private $branch;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return BranchTransaction
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     * Set timeInsert
     *
     * @param \DateTime $timeInsert
     *
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
     *
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

    /**
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     *
     * @return BranchTransaction
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalInfo()
    {
        return $this->externalInfo;
    }

    /**
     * @param string $externalInfo
     *
     * @return BranchTransaction
     */
    public function setExternalInfo($externalInfo)
    {
        $this->externalInfo = $externalInfo;

        return $this;
    }

    /**
     * @return int
     */
    public function getFailedAttempts()
    {
        return $this->failedAttempts;
    }

    /**
     * @param int $failedAttempts
     *
     * @return BranchTransaction
     */
    public function setFailedAttempts($failedAttempts)
    {
        $this->failedAttempts = $failedAttempts;

        return $this;
    }

    /**
     * @return BranchSync
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param BranchSync $branch
     *
     * @return $this
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * @return Room
     */
    public function getBranchTransactionStatus()
    {
        return $this->branchTransactionStatus;
    }

    /**
     * @param Room $branchTransactionStatus
     * @return BranchTransaction
     */
    public function setBranchTransactionStatus($branchTransactionStatus)
    {
        $this->branchTransactionStatus = $branchTransactionStatus;

        return $this;
    }

    /**
     * @return int
     */
    public function getExternalTransactionId()
    {
        return $this->externalTransactionId;
    }

    /**
     * @param int $externalTransactionId
     * @return BranchTransaction
     */
    public function setExternalTransactionId($externalTransactionId)
    {
        $this->externalTransactionId = $externalTransactionId;

        return $this;
    }




}
