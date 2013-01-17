<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackECourseAccess
 *
 * @Table(name="track_e_course_access")
 * @Entity
 */
class EntityTrackECourseAccess
{
    /**
     * @var integer
     *
     * @Column(name="course_access_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $courseAccessId;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @Column(name="login_course_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $loginCourseDate;

    /**
     * @var \DateTime
     *
     * @Column(name="logout_course_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $logoutCourseDate;

    /**
     * @var integer
     *
     * @Column(name="counter", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $counter;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * Get courseAccessId
     *
     * @return integer 
     */
    public function getCourseAccessId()
    {
        return $this->courseAccessId;
    }

    /**
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntityTrackECourseAccess
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityTrackECourseAccess
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set loginCourseDate
     *
     * @param \DateTime $loginCourseDate
     * @return EntityTrackECourseAccess
     */
    public function setLoginCourseDate($loginCourseDate)
    {
        $this->loginCourseDate = $loginCourseDate;

        return $this;
    }

    /**
     * Get loginCourseDate
     *
     * @return \DateTime 
     */
    public function getLoginCourseDate()
    {
        return $this->loginCourseDate;
    }

    /**
     * Set logoutCourseDate
     *
     * @param \DateTime $logoutCourseDate
     * @return EntityTrackECourseAccess
     */
    public function setLogoutCourseDate($logoutCourseDate)
    {
        $this->logoutCourseDate = $logoutCourseDate;

        return $this;
    }

    /**
     * Get logoutCourseDate
     *
     * @return \DateTime 
     */
    public function getLogoutCourseDate()
    {
        return $this->logoutCourseDate;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return EntityTrackECourseAccess
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;

        return $this;
    }

    /**
     * Get counter
     *
     * @return integer 
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityTrackECourseAccess
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
