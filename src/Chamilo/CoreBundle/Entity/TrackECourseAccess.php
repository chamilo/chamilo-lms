<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackECourseAccess.
 *
 * @ORM\Table(
 *  name="track_e_course_access",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user_id", columns={"user_id"}),
 *      @ORM\Index(name="login_course_date", columns={"login_course_date"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\TrackECourseAccessRepository")
 */
class TrackECourseAccess
{
    /**
     * @var int
     *
     * @ORM\Column(name="course_access_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $courseAccessId;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_course_date", type="datetime", nullable=false)
     */
    protected $loginCourseDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logout_course_date", type="datetime", nullable=true)
     */
    protected $logoutCourseDate;

    /**
     * @var int
     *
     * @ORM\Column(name="counter", type="integer", nullable=false)
     */
    protected $counter;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected $userIp;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return TrackECourseAccess
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

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return TrackECourseAccess
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set loginCourseDate.
     *
     * @param \DateTime $loginCourseDate
     *
     * @return TrackECourseAccess
     */
    public function setLoginCourseDate($loginCourseDate)
    {
        $this->loginCourseDate = $loginCourseDate;

        return $this;
    }

    /**
     * Get loginCourseDate.
     *
     * @return \DateTime
     */
    public function getLoginCourseDate()
    {
        return $this->loginCourseDate;
    }

    /**
     * Set logoutCourseDate.
     *
     * @param \DateTime $logoutCourseDate
     *
     * @return TrackECourseAccess
     */
    public function setLogoutCourseDate($logoutCourseDate)
    {
        $this->logoutCourseDate = $logoutCourseDate;

        return $this;
    }

    /**
     * Get logoutCourseDate.
     *
     * @return \DateTime
     */
    public function getLogoutCourseDate()
    {
        return $this->logoutCourseDate;
    }

    /**
     * Set counter.
     *
     * @param int $counter
     *
     * @return TrackECourseAccess
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;

        return $this;
    }

    /**
     * Get counter.
     *
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return TrackECourseAccess
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set userIp.
     *
     * @param string $userIp
     *
     * @return TrackECourseAccess
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp.
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Get courseAccessId.
     *
     * @return int
     */
    public function getCourseAccessId()
    {
        return $this->courseAccessId;
    }
}
