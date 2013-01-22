<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySessionRelCourseRelUser
 *
 * @Table(name="session_rel_course_rel_user")
 * @Entity
 */
class EntitySessionRelCourseRelUser
{
    /**
     * @var integer
     *
     * @Column(name="id_session", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $idSession;

    /**
     * @var integer
     *
     * @Column(name="course_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $courseId;

    /**
     * @var integer
     *
     * @Column(name="id_user", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $idUser;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="visibility", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @Column(name="status", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @Column(name="legal_agreement", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $legalAgreement;


    /**
     * Set idSession
     *
     * @param integer $idSession
     * @return EntitySessionRelCourseRelUser
     */
    public function setIdSession($idSession)
    {
        $this->idSession = $idSession;

        return $this;
    }

    /**
     * Get idSession
     *
     * @return integer 
     */
    public function getIdSession()
    {
        return $this->idSession;
    }

    /**
     * Set courseId
     *
     * @param integer $courseId
     * @return EntitySessionRelCourseRelUser
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId
     *
     * @return integer 
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     * @return EntitySessionRelCourseRelUser
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer 
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntitySessionRelCourseRelUser
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode
     *
     * @return string 
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     * @return EntitySessionRelCourseRelUser
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return EntitySessionRelCourseRelUser
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set legalAgreement
     *
     * @param integer $legalAgreement
     * @return EntitySessionRelCourseRelUser
     */
    public function setLegalAgreement($legalAgreement)
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    /**
     * Get legalAgreement
     *
     * @return integer 
     */
    public function getLegalAgreement()
    {
        return $this->legalAgreement;
    }
}
