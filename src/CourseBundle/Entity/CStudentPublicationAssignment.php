<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CourseBundle\Repository\CStudentPublicationAssignmentRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_student_publication_assignment')]
#[ORM\Entity(repositoryClass: CStudentPublicationAssignmentRepository::class)]
class CStudentPublicationAssignment implements Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\Column(name: 'expires_on', type: 'datetime', nullable: true)]
    #[Groups(['c_student_publication:write'])]
    protected ?DateTime $expiresOn = null;

    #[ORM\Column(name: 'ends_on', type: 'datetime', nullable: true)]
    #[Groups(['c_student_publication:write'])]
    #[Assert\GreaterThanOrEqual(propertyPath: 'expiresOn')]
    protected ?DateTime $endsOn = null;

    #[ORM\Column(name: 'add_to_calendar', type: 'integer', nullable: false)]
    protected int $eventCalendarId = 0;

    #[ORM\Column(name: 'enable_qualification', type: 'boolean', nullable: false)]
    protected bool $enableQualification;

    #[ORM\OneToOne(inversedBy: 'assignment', targetEntity: CStudentPublication::class)]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CStudentPublication $publication;

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getExpiresOn(): ?DateTime
    {
        return $this->expiresOn;
    }

    public function setExpiresOn(?DateTime $expiresOn): self
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    public function getEndsOn(): ?DateTime
    {
        return $this->endsOn;
    }

    public function setEndsOn(?DateTime $endsOn): self
    {
        $this->endsOn = $endsOn;

        return $this;
    }

    public function getEventCalendarId(): int
    {
        return $this->eventCalendarId;
    }

    public function setEventCalendarId(int $eventCalendarId): self
    {
        $this->eventCalendarId = $eventCalendarId;

        return $this;
    }

    public function getEnableQualification(): bool
    {
        return $this->enableQualification;
    }

    public function setEnableQualification(bool $enableQualification): self
    {
        $this->enableQualification = $enableQualification;

        return $this;
    }

    public function getPublication(): CStudentPublication
    {
        return $this->publication;
    }

    public function setPublication(CStudentPublication $publication): self
    {
        $this->publication = $publication;

        $qualification = $this->publication->getQualification();

        $this->enableQualification = !empty($qualification);

        return $this;
    }

    /*
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return (string) $this->getIid();
    }

    public function setResourceName(string $name): self
    {
        //return $this->setTitle($name);
    }*/
}
