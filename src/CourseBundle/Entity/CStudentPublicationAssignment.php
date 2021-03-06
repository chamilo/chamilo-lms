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
 *         @ORM\Index(name="course", columns={"c_id"})
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="expires_on", type="datetime", nullable=true)
     */
    protected ?DateTime $expiresOn;

    /**
     * @ORM\Column(name="ends_on", type="datetime", nullable=true)
     */
    protected ?DateTime $endsOn;

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

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * Set expiresOn.
     *
     * @param DateTime $expiresOn
     */
    public function setExpiresOn($expiresOn): self
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    /**
     * Get expiresOn.
     *
     * @return DateTime
     */
    public function getExpiresOn()
    {
        return $this->expiresOn;
    }

    /**
     * Set endsOn.
     *
     * @param DateTime $endsOn
     */
    public function setEndsOn($endsOn): self
    {
        $this->endsOn = $endsOn;

        return $this;
    }

    /**
     * Get endsOn.
     *
     * @return DateTime
     */
    public function getEndsOn()
    {
        return $this->endsOn;
    }

    /**
     * Set addToCalendar.
     *
     * @param bool $addToCalendar
     */
    public function setAddToCalendar($addToCalendar): self
    {
        $this->addToCalendar = $addToCalendar;

        return $this;
    }

    /**
     * Get addToCalendar.
     *
     * @return bool
     */
    public function getAddToCalendar()
    {
        return $this->addToCalendar;
    }

    /**
     * Set enableQualification.
     *
     * @param bool $enableQualification
     */
    public function setEnableQualification($enableQualification): self
    {
        $this->enableQualification = $enableQualification;

        return $this;
    }

    /**
     * Get enableQualification.
     *
     * @return bool
     */
    public function getEnableQualification()
    {
        return $this->enableQualification;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CStudentPublicationAssignment
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
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
