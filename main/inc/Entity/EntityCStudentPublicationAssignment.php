<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCStudentPublicationAssignment
 *
 * @Table(name="c_student_publication_assignment")
 * @Entity
 */
class EntityCStudentPublicationAssignment
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @Column(name="expires_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expiresOn;

    /**
     * @var \DateTime
     *
     * @Column(name="ends_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $endsOn;

    /**
     * @var boolean
     *
     * @Column(name="add_to_calendar", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $addToCalendar;

    /**
     * @var boolean
     *
     * @Column(name="enable_qualification", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $enableQualification;

    /**
     * @var integer
     *
     * @Column(name="publication_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $publicationId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCStudentPublicationAssignment
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
     * @return EntityCStudentPublicationAssignment
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
     * @return EntityCStudentPublicationAssignment
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
     * @return EntityCStudentPublicationAssignment
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
     * @return EntityCStudentPublicationAssignment
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
     * @return EntityCStudentPublicationAssignment
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
     * @return EntityCStudentPublicationAssignment
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
