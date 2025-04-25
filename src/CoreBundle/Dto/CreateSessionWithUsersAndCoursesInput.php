<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto;

use ApiPlatform\Metadata\ApiResource;
use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
)]
class CreateSessionWithUsersAndCoursesInput
{
    #[Assert\NotBlank]
    #[Groups(['write'])]
    private string $title;

    #[Groups(['write'])]
    private ?string $description = null;

    #[Groups(['write'])]
    private ?bool $showDescription = true;

    #[Groups(['write'])]
    private ?int $duration = 0;

    #[Groups(['write'])]
    private ?DateTime $displayStartDate = null;

    #[Groups(['write'])]
    private ?DateTime $displayEndDate = null;

    #[Groups(['write'])]
    private ?DateTime $accessStartDate = null;

    #[Groups(['write'])]
    private ?DateTime $accessEndDate = null;

    #[Groups(['write'])]
    private ?DateTime $coachAccessStartDate = null;

    #[Groups(['write'])]
    private ?DateTime $coachAccessEndDate = null;

    #[Groups(['write'])]
    private ?int $category = null;

    #[Groups(['write'])]
    private ?int $validityInDays = 0;

    #[Groups(['write'])]
    private ?int $visibility = 1;

    #[Groups(['write'])]
    private array $courseIds = [];

    #[Groups(['write'])]
    private array $studentIds = [];

    #[Groups(['write'])]
    private array $tutorIds = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getShowDescription(): ?bool
    {
        return $this->showDescription;
    }

    public function setShowDescription(?bool $showDescription): void
    {
        $this->showDescription = $showDescription;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function getDisplayStartDate(): ?DateTime
    {
        return $this->displayStartDate;
    }

    public function setDisplayStartDate(?DateTime $displayStartDate): void
    {
        $this->displayStartDate = $displayStartDate;
    }

    public function getDisplayEndDate(): ?DateTime
    {
        return $this->displayEndDate;
    }

    public function setDisplayEndDate(?DateTime $displayEndDate): void
    {
        $this->displayEndDate = $displayEndDate;
    }

    public function getAccessStartDate(): ?DateTime
    {
        return $this->accessStartDate;
    }

    public function setAccessStartDate(?DateTime $accessStartDate): void
    {
        $this->accessStartDate = $accessStartDate;
    }

    public function getAccessEndDate(): ?DateTime
    {
        return $this->accessEndDate;
    }

    public function setAccessEndDate(?DateTime $accessEndDate): void
    {
        $this->accessEndDate = $accessEndDate;
    }

    public function getCoachAccessStartDate(): ?DateTime
    {
        return $this->coachAccessStartDate;
    }

    public function setCoachAccessStartDate(?DateTime $coachAccessStartDate): void
    {
        $this->coachAccessStartDate = $coachAccessStartDate;
    }

    public function getCoachAccessEndDate(): ?DateTime
    {
        return $this->coachAccessEndDate;
    }

    public function setCoachAccessEndDate(?DateTime $coachAccessEndDate): void
    {
        $this->coachAccessEndDate = $coachAccessEndDate;
    }

    public function getCategory(): ?int
    {
        return $this->category;
    }

    public function setCategory(?int $category): void
    {
        $this->category = $category;
    }

    public function getValidityInDays(): ?int
    {
        return $this->validityInDays;
    }

    public function setValidityInDays(?int $validityInDays): void
    {
        $this->validityInDays = $validityInDays;
    }

    public function getVisibility(): ?int
    {
        return $this->visibility;
    }

    public function setVisibility(?int $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function getCourseIds(): array
    {
        return $this->courseIds;
    }

    public function setCourseIds(array $courseIds): void
    {
        $this->courseIds = $courseIds;
    }

    public function getStudentIds(): array
    {
        return $this->studentIds;
    }

    public function setStudentIds(array $studentIds): void
    {
        $this->studentIds = $studentIds;
    }

    public function getTutorIds(): array
    {
        return $this->tutorIds;
    }

    public function setTutorIds(array $tutorIds): void
    {
        $this->tutorIds = $tutorIds;
    }
}
