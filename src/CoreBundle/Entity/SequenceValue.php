<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'sequence_value')]
#[ORM\Entity]
class SequenceValue
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'sequenceValues')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\SequenceRowEntity::class)]
    #[ORM\JoinColumn(name: 'sequence_row_entity_id', referencedColumnName: 'id')]
    protected ?SequenceRowEntity $entity = null;

    #[ORM\Column(name: 'advance', type: 'float')]
    protected float $advance;

    #[ORM\Column(name: 'complete_items', type: 'integer')]
    protected int $completeItems;

    #[ORM\Column(name: 'total_items', type: 'integer')]
    protected int $totalItems;

    #[ORM\Column(name: 'success', type: 'boolean')]
    protected bool $success;

    #[ORM\Column(name: 'success_date', type: 'datetime', nullable: true)]
    protected ?DateTime $successDate = null;

    #[ORM\Column(name: 'available', type: 'boolean')]
    protected bool $available;

    #[ORM\Column(name: 'available_start_date', type: 'datetime', nullable: true)]
    protected ?DateTime $availableStartDate = null;

    #[ORM\Column(name: 'available_end_date', type: 'datetime', nullable: true)]
    protected ?DateTime $availableEndDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntity(): ?SequenceRowEntity
    {
        return $this->entity;
    }

    public function setEntity(?SequenceRowEntity $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getAdvance(): float
    {
        return $this->advance;
    }

    public function setAdvance(float $advance): static
    {
        $this->advance = $advance;

        return $this;
    }

    public function getCompleteItems(): int
    {
        return $this->completeItems;
    }

    public function setCompleteItems(int $completeItems): self
    {
        $this->completeItems = $completeItems;

        return $this;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function setTotalItems(int $totalItems): self
    {
        $this->totalItems = $totalItems;

        return $this;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function getSuccessDate(): ?DateTime
    {
        return $this->successDate;
    }

    public function setSuccessDate(DateTime $successDate): self
    {
        $this->successDate = $successDate;

        return $this;
    }

    public function getAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;

        return $this;
    }

    public function getAvailableStartDate(): ?DateTime
    {
        return $this->availableStartDate;
    }

    public function setAvailableStartDate(DateTime $availableStartDate): self
    {
        $this->availableStartDate = $availableStartDate;

        return $this;
    }

    public function getAvailableEndDate(): ?DateTime
    {
        return $this->availableEndDate;
    }

    public function setAvailableEndDate(DateTime $availableEndDate): self
    {
        $this->availableEndDate = $availableEndDate;

        return $this;
    }
}
