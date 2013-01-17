<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySessionRelCourse
 *
 * @Table(name="session_rel_course")
 * @Entity
 */
class EntitySessionRelCourse
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
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="nbr_users", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $nbrUsers;


    /**
     * Set idSession
     *
     * @param integer $idSession
     * @return EntitySessionRelCourse
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
     * @return EntitySessionRelCourse
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
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntitySessionRelCourse
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
     * Set nbrUsers
     *
     * @param integer $nbrUsers
     * @return EntitySessionRelCourse
     */
    public function setNbrUsers($nbrUsers)
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }

    /**
     * Get nbrUsers
     *
     * @return integer 
     */
    public function getNbrUsers()
    {
        return $this->nbrUsers;
    }
}
