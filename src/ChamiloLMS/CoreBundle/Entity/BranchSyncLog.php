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
     * @ORM\Column(name="transaction_id", type="bigint", nullable=false)
     */
    private $transactionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="import_time", type="datetime", nullable=true)
     */
    private $importTime;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=false)
     */
    private $message;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
