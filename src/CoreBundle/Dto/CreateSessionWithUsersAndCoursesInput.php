<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto;

use ApiPlatform\Metadata\ApiResource;
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
    private ?int $visibility = 1;

    #[Groups(['write'])]
    private array $courseIds = [];

    #[Groups(['write'])]
    private array $studentIds = [];

    #[Groups(['write'])]
    private array $tutorIds = [];

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function getVisibility(): ?int { return $this->visibility; }
    public function setVisibility(?int $visibility): void { $this->visibility = $visibility; }

    public function getCourseIds(): array { return $this->courseIds; }
    public function setCourseIds(array $courseIds): void { $this->courseIds = $courseIds; }

    public function getStudentIds(): array { return $this->studentIds; }
    public function setStudentIds(array $studentIds): void { $this->studentIds = $studentIds; }

    public function getTutorIds(): array { return $this->tutorIds; }
    public function setTutorIds(array $tutorIds): void { $this->tutorIds = $tutorIds; }
}
