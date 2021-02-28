<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Sequence.
 *
 * @ORM\Table(name="sequence_value")
 * @ORM\Entity
 */
class SequenceValue
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="sequenceValues")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="SequenceRowEntity")
     * @ORM\JoinColumn(name="sequence_row_entity_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\SequenceRowEntity $entity = null;

    /**
     * @ORM\Column(name="advance", type="float")
     */
    protected int $advance;

    /**
     * @ORM\Column(name="complete_items", type="integer")
     */
    protected int $completeItems;

    /**
     * @ORM\Column(name="total_items", type="integer")
     */
    protected int $totalItems;

    /**
     * @ORM\Column(name="success", type="boolean")
     */
    protected int $success;

    /**
     * @ORM\Column(name="success_date", type="datetime", nullable=true)
     */
    protected ?DateTime $successDate;

    /**
     * @ORM\Column(name="available", type="boolean")
     */
    protected int $available;

    /**
     * @ORM\Column(name="available_start_date", type="datetime", nullable=true)
     */
    protected ?DateTime $availableStartDate;

    /**
     * @ORM\Column(name="available_end_date", type="datetime", nullable=true)
     */
    protected ?DateTime $availableEndDate;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return SequenceValue
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return int
     */
    public function getAdvance()
    {
        return $this->advance;
    }

    /**
     * @param int $advance
     *
     * @return SequenceValue
     */
    public function setAdvance($advance)
    {
        $this->advance = $advance;

        return $this;
    }

    /**
     * @return int
     */
    public function getCompleteItems()
    {
        return $this->completeItems;
    }

    /**
     * @param int $completeItems
     *
     * @return SequenceValue
     */
    public function setCompleteItems($completeItems)
    {
        $this->completeItems = $completeItems;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * @param int $totalItems
     *
     * @return SequenceValue
     */
    public function setTotalItems($totalItems)
    {
        $this->totalItems = $totalItems;

        return $this;
    }

    /**
     * @return int
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @param int $success
     *
     * @return SequenceValue
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSuccessDate()
    {
        return $this->successDate;
    }

    /**
     * @param DateTime $successDate
     *
     * @return SequenceValue
     */
    public function setSuccessDate($successDate)
    {
        $this->successDate = $successDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * @param int $available
     *
     * @return SequenceValue
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAvailableStartDate()
    {
        return $this->availableStartDate;
    }

    /**
     * @param DateTime $availableStartDate
     *
     * @return SequenceValue
     */
    public function setAvailableStartDate($availableStartDate)
    {
        $this->availableStartDate = $availableStartDate;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAvailableEndDate()
    {
        return $this->availableEndDate;
    }

    /**
     * @param DateTime $availableEndDate
     *
     * @return SequenceValue
     */
    public function setAvailableEndDate($availableEndDate)
    {
        $this->availableEndDate = $availableEndDate;

        return $this;
    }
}
