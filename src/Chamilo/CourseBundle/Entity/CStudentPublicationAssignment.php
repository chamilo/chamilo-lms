<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationAssignment
 *
 * @ORM\Table(name="c_student_publication_assignment")
 * @ORM\Entity
 */
class CStudentPublicationAssignment
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expiresOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ends_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $endsOn;

    /**
     * @var boolean
     *
     * @ORM\Column(name="add_to_calendar", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $addToCalendar;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable_qualification", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $enableQualification;

    /**
     * @var integer
     *
     * @ORM\Column(name="publication_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $publicationId;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CStudentPublicationAssignment
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CStudentPublicationAssignment
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set expiresOn
     *
     * @param \DateTime $expiresOn
     * @return CStudentPublicationAssignment
     */
    public function setExpiresOn($expiresOn)
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    /**
     * Get expiresOn
     *
     * @return \DateTime
     */
    public function getExpiresOn()
    {
        return $this->expiresOn;
    }

    /**
     * Set endsOn
     *
     * @param \DateTime $endsOn
     * @return CStudentPublicationAssignment
     */
    public function setEndsOn($endsOn)
    {
        $this->endsOn = $endsOn;

        return $this;
    }

    /**
     * Get endsOn
     *
     * @return \DateTime
     */
    public function getEndsOn()
    {
        return $this->endsOn;
    }

    /**
     * Set addToCalendar
     *
     * @param boolean $addToCalendar
     * @return CStudentPublicationAssignment
     */
    public function setAddToCalendar($addToCalendar)
    {
        $this->addToCalendar = $addToCalendar;

        return $this;
    }

    /**
     * Get addToCalendar
     *
     * @return boolean
     */
    public function getAddToCalendar()
    {
        return $this->addToCalendar;
    }

    /**
     * Set enableQualification
     *
     * @param boolean $enableQualification
     * @return CStudentPublicationAssignment
     */
    public function setEnableQualification($enableQualification)
    {
        $this->enableQualification = $enableQualification;

        return $this;
    }

    /**
     * Get enableQualification
     *
     * @return boolean
     */
    public function getEnableQualification()
    {
        return $this->enableQualification;
    }

    /**
     * Set publicationId
     *
     * @param integer $publicationId
     * @return CStudentPublicationAssignment
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;

        return $this;
    }

    /**
     * Get publicationId
     *
     * @return integer
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }
}

