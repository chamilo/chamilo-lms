<?php

namespace ChamiloLMS\CoreBundle\Entity;

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
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=20, nullable=true)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(name="item_id", type="string", length=36, nullable=true)
     */
    private $itemId;

    /**
     * @var string
     *
     * @ORM\Column(name="dest_id", type="string", length=36, nullable=true)
     */
    private $destId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_id", type="boolean", nullable=false)
     */
    private $statusId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_insert", type="datetime", nullable=false)
     */
    private $timeInsert;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_update", type="datetime", nullable=false)
     */
    private $timeUpdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transaction_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $transactionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="branch_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $branchId;


}
