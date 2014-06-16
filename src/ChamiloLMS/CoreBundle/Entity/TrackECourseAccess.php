<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackECourseAccess
 *
 * @ORM\Table(name="track_e_course_access", indexes={@ORM\Index(name="user_id", columns={"user_id"}), @ORM\Index(name="login_course_date", columns={"login_course_date"}), @ORM\Index(name="c_id", columns={"c_id"}), @ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class TrackECourseAccess
{
    /**
     * @var integer
     *
     * @ORM\Column(name="course_access_id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $courseAccessId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_course_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $loginCourseDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logout_course_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $logoutCourseDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="counter", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $counter;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;


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
     * Set userId
     *
     * @param integer $userId
     * @return TrackECourseAccess
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
     * @return TrackECourseAccess
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
     * @return TrackECourseAccess
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
     * @return TrackECourseAccess
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
     * @return TrackECourseAccess
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

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackECourseAccess
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
}
