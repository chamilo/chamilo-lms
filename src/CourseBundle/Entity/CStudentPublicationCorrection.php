<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Controller\Api\CreateStudentPublicationCorrectionFileAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_student_publication_correction')]
#[ORM\Entity(repositoryClass: CStudentPublicationCorrectionRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/c_student_publication_corrections/upload',
            controller: CreateStudentPublicationCorrectionFileAction::class,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_TEACHER')",
            deserialize: false,
        ),
    ]
)]
class CStudentPublicationCorrection extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    #[Groups(['student_publication:read'])]
    protected string $title;

    public function __toString(): string
    {
        return $this->title;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
