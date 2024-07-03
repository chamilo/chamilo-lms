<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiActivityProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiActivityProfileRepository::class)]
class XApiActivityProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $profileId = null;

    #[ORM\Column(length: 255)]
    private ?string $activityId = null;

    #[ORM\Column]
    private array $documentData = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function setProfileId(string $profileId): static
    {
        $this->profileId = $profileId;

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
