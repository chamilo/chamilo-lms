<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackECourseAccess.
 *
 * @ORM\Table(
 *     name="track_e_course_access",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="user_id", columns={"user_id"}),
 *         @ORM\Index(name="login_course_date", columns={"login_course_date"}),
 *         @ORM\Index(name="session_id", columns={"session_id"}),
 *         @ORM\Index(name="user_course_session_date", columns={"user_id", "c_id", "session_id", "login_course_date"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\TrackECourseAccessRepository")
 */
class TrackECourseAccess
{
    use UserTrait;

    /**
     * @ORM\Column(name="course_access_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $courseAccessId;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="trackECourseAccess")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="login_course_date", type="datetime", nullable=false)
     */
    protected DateTime $loginCourseDate;

    /**
     * @ORM\Column(name="logout_course_date", type="datetime", nullable=true)
     */
    protected ?DateTime $logoutCourseDate = null;

    /**
     * @ORM\Column(name="counter", type="integer", nullable=false)
     */
    protected int $counter;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected string $userIp;

    /**
     * Set cId.
     *
     * @return TrackECourseAccess
     */
    public function setCId(int $cId)
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
     * Set loginCourseDate.
     *
     * @return TrackECourseAccess
     */
    public function setLoginCourseDate(DateTime $loginCourseDate)
    {
        $this->loginCourseDate = $loginCourseDate;

        return $this;
    }

    /**
     * Get loginCourseDate.
     *
     * @return DateTime
     */
    public function getLoginCourseDate()
    {
        return $this->loginCourseDate;
    }

    /**
     * Set logoutCourseDate.
     *
     * @return TrackECourseAccess
     */
    public function setLogoutCourseDate(DateTime $logoutCourseDate)
    {
        $this->logoutCourseDate = $logoutCourseDate;

        return $this;
    }

    /**
     * Get logoutCourseDate.
     *
     * @return null|DateTime
     */
    public function getLogoutCourseDate()
    {
        return $this->logoutCourseDate;
    }

    /**
     * Set counter.
     *
     * @return TrackECourseAccess
     */
    public function setCounter(int $counter)
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
     * @return TrackECourseAccess
     */
    public function setSessionId(int $sessionId)
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
     * @return TrackECourseAccess
     */
    public function setUserIp(string $userIp)
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
