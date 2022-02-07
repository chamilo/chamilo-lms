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
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SequenceRowEntity")
     * @ORM\JoinColumn(name="sequence_row_entity_id", referencedColumnName="id")
     */
    protected ?SequenceRowEntity $entity = null;

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
    protected ?DateTime $successDate = null;

    /**
     * @ORM\Column(name="available", type="boolean")
     */
    protected int $available;

    /**
     * @ORM\Column(name="available_start_date", type="datetime", nullable=true)
     */
    protected ?DateTime $availableStartDate = null;

    /**
     * @ORM\Column(name="available_end_date", type="datetime", nullable=true)
     */
    protected ?DateTime $availableEndDate = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getEntity(): ?SequenceRowEntity
    {
        return $this->entity;
    }

    /**
     * @return SequenceValue
     */
    public function setEntity(?SequenceRowEntity $entity): self
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
     * @return SequenceValue
     */
    public function setAdvance(int $advance)
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
     * @return SequenceValue
     */
    public function setCompleteItems(int $completeItems): self
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
     * @return SequenceValue
     */
    public function setTotalItems(int $totalItems): self
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
     * @return SequenceValue
     */
    public function setSuccess(int $success): self
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
     * @return SequenceValue
     */
    public function setSuccessDate(DateTime $successDate): self
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
     * @return SequenceValue
     */
    public function setAvailable(int $available): self
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
     * @return SequenceValue
     */
    public function setAvailableStartDate(DateTime $availableStartDate): self
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
     * @return SequenceValue
     */
    public function setAvailableEndDate(DateTime $availableEndDate): self
    {
        $this->availableEndDate = $availableEndDate;

        return $this;
    }
}
