<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BranchSyncLog
 *
 * @ORM\Table(name="branch_sync_log")
 * @ORM\Entity
 */
class BranchSyncLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transaction_id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $transactionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="import_time", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $importTime;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $message;


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
     * @return BranchSyncLog
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
     * Set importTime
     *
     * @param \DateTime $importTime
     * @return BranchSyncLog
     */
    public function setImportTime($importTime)
    {
        $this->importTime = $importTime;

        return $this;
    }

    /**
     * Get importTime
     *
     * @return \DateTime 
     */
    public function getImportTime()
    {
        return $this->importTime;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return BranchSyncLog
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }
}
