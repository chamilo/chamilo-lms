<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Sequence
 *
 * @ORM\Table(name="sequence_value")
 * @ORM\Entity
 */
class SequenceValue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRowEntity")
     * @ORM\JoinColumn(name="sequence_row_entity_id", referencedColumnName="id")
     **/
    private $entity;

    /**
     * @var integer
     *
     * @ORM\Column(name="advance", type="float")
     */
    private $advance;

    /**
     * @var integer
     *
     * @ORM\Column(name="complete_items", type="integer")
     */
    private $completeItems;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_items", type="integer")
     */
    private $totalItems;

    /**
     * @var integer
     *
     * @ORM\Column(name="success", type="boolean")
     */
    private $success;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="success_date", type="datetime", nullable=true)
     */
    private $successDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="available", type="boolean")
     */
    private $available;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="available_start_date", type="datetime", nullable=true)
     */
    private $availableStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="available_end_date", type="datetime", nullable=true)
     */
    private $availableEndDate;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
