<?php

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiActivityStateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiActivityStateRepository::class)]
class XApiActivityState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $stateId = null;

    #[ORM\Column(length: 255)]
    private ?string $activityId = null;

    #[ORM\Column]
    private array $agent = [];

    #[ORM\Column]
    private array $documentData = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStateId(): ?string
    {
        return $this->stateId;
    }

    public function setStateId(string $stateId): static
    {
        $this->stateId = $stateId;

        return $this;
    }

    public function getActivityId(): ?string
    {
        return $this->activityId;
    }

    public function setActivityId(string $activityId): static
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function getAgent(): array
    {
        return $this->agent;
    }

    public function setAgent(array $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    public function setDocumentData(array $documentData): static
    {
        $this->documentData = $documentData;

        return $this;
    }
}
