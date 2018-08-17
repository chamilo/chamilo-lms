<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackELogin.
 *
 * @ORM\Table(name="track_e_login", indexes={
 *     @ORM\Index(name="login_user_id", columns={"login_user_id"})
 * })
 * @ORM\Entity
 */
class TrackELogin
{
    /**
     * @var int
     *
     * @ORM\Column(name="login_user_id", type="integer", nullable=false)
     */
    protected $loginUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_date", type="datetime", nullable=false)
     */
    protected $loginDate;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected $userIp;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logout_date", type="datetime", nullable=true)
     */
    protected $logoutDate;

    /**
     * @var int
     *
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $loginId;

    /**
     * Set loginUserId.
     *
     * @param int $loginUserId
     *
     * @return TrackELogin
     */
    public function setLoginUserId($loginUserId)
    {
        $this->loginUserId = $loginUserId;

        return $this;
    }

    /**
     * Get loginUserId.
     *
     * @return int
     */
    public function getLoginUserId()
    {
        return $this->loginUserId;
    }

    /**
     * Set loginDate.
     *
     * @param \DateTime $loginDate
     *
     * @return TrackELogin
     */
    public function setLoginDate($loginDate)
    {
        $this->loginDate = $loginDate;

        return $this;
    }

    /**
     * Get loginDate.
     *
     * @return \DateTime
     */
    public function getLoginDate()
    {
        return $this->loginDate;
    }

    /**
     * Set userIp.
     *
     * @param string $userIp
     *
     * @return TrackELogin
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
     * Set logoutDate.
     *
     * @param \DateTime $logoutDate
     *
     * @return TrackELogin
     */
    public function setLogoutDate($logoutDate)
    {
        $this->logoutDate = $logoutDate;

        return $this;
    }

    /**
     * Get logoutDate.
     *
     * @return \DateTime
     */
    public function getLogoutDate()
    {
        return $this->logoutDate;
    }

    /**
     * Get loginId.
     *
     * @return int
     */
    public function getLoginId()
    {
        return $this->loginId;
    }
}
