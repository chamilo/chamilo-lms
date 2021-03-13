<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationAssignment.
 *
 * @ORM\Table(
 *     name="c_student_publication_assignment",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CStudentPublicationAssignment
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="expires_on", type="datetime", nullable=true)
     */
    protected ?DateTime $expiresOn = null;

    /**
     * @ORM\Column(name="ends_on", type="datetime", nullable=true)
     */
    protected ?DateTime $endsOn = null;

    /**
     * @ORM\Column(name="add_to_calendar", type="integer", nullable=false)
     */
    protected bool $addToCalendar;

    /**
     * @ORM\Column(name="enable_qualification", type="boolean", nullable=false)
     */
    protected bool $enableQualification;

    /**
     * @ORM\OneToOne(targetEntity="CStudentPublication", inversedBy="assignment")
     * @ORM\JoinColumn(name="publication_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CStudentPublication $publication;

    public function getIid(): int
    {
        return $this->iid;
    }

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    public function setExpiresOn(DateTime $expiresOn): self
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    public function getExpiresOn(): ?DateTime
    {
        return $this->expiresOn;
    }

    public function setEndsOn(DateTime $endsOn): self
    {
        $this->endsOn = $endsOn;

        return $this;
    }

    public function getEndsOn(): ?DateTime
    {
        return $this->endsOn;
    }

    public function setAddToCalendar(bool $addToCalendar): self
    {
        $this->addToCalendar = $addToCalendar;

        return $this;
    }

    public function getAddToCalendar(): bool
    {
        return $this->addToCalendar;
    }

    public function setEnableQualification(bool $enableQualification): self
    {
        $this->enableQualification = $enableQualification;

        return $this;
    }

    public function getEnableQualification(): bool
    {
        return $this->enableQualification;
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
