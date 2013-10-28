<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BranchRelSession
 *
 * @ORM\Table(name="branch_rel_session")
 * @ORM\Entity
 */
class BranchRelSession
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="branch_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $branchId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display_order", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayOrder;


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
     * Set branchId
     *
     * @param integer $branchId
     * @return BranchRelSession
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return BranchRelSession
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

    /**
     * Set displayOrder
     *
     * @param boolean $displayOrder
     * @return BranchRelSession
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return boolean
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }
}
