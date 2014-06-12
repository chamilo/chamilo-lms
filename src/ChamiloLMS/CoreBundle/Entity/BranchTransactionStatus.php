<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BranchTransactionStatus
 *
 * @ORM\Table(name="branch_transaction_status")
 * @ORM\Entity
 */
class BranchTransactionStatus
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="id", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;


    /**
     * Get id
     *
     * @return boolean 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return BranchTransactionStatus
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }
}
