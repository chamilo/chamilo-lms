<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationAssignment.
 *
 * @ORM\Table(
 *  name="c_student_publication_assignment",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CStudentPublicationAssignment
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_on", type="datetime", nullable=true)
     */
    protected $expiresOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ends_on", type="datetime", nullable=true)
     */
    protected $endsOn;

    /**
     * @var bool
     *
     * @ORM\Column(name="add_to_calendar", type="integer", nullable=false)
     */
    protected $addToCalendar;

    /**
     * @var bool
     *
     * @ORM\Column(name="enable_qualification", type="boolean", nullable=false)
     */
    protected $enableQualification;

    /**
     * @var int
     *
     * @ORM\Column(name="publication_id", type="integer", nullable=false)
     */
    protected $publicationId;

    /**
     * Set expiresOn.
     *
     * @param \DateTime $expiresOn
     *
     * @return CStudentPublicationAssignment
     */
    public function setExpiresOn($expiresOn)
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    /**
     * Get expiresOn.
     *
     * @return \DateTime
     */
    public function getExpiresOn()
    {
        return $this->expiresOn;
    }

    /**
     * Set endsOn.
     *
     * @param \DateTime $endsOn
     *
     * @return CStudentPublicationAssignment
     */
    public function setEndsOn($endsOn)
    {
        $this->endsOn = $endsOn;

        return $this;
    }

    /**
     * Get endsOn.
     *
     * @return \DateTime
     */
    public function getEndsOn()
    {
        return $this->endsOn;
    }

    /**
     * Set addToCalendar.
     *
     * @param bool $addToCalendar
     *
     * @return CStudentPublicationAssignment
     */
    public function setAddToCalendar($addToCalendar)
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
     *
     * @return CStudentPublicationAssignment
     */
    public function setEnableQualification($enableQualification)
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
     * Set publicationId.
     *
     * @param int $publicationId
     *
     * @return CStudentPublicationAssignment
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;

        return $this;
    }

    /**
     * Get publicationId.
     *
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CStudentPublicationAssignment
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
}
