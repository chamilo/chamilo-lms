<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackELogin.
 *
 * @ORM\Table(name="track_e_login", indexes={
 *     @ORM\Index(name="login_user_id", columns={"login_user_id"}),
 *     @ORM\Index(name="idx_track_e_login_date", columns={"login_date"})
 * })
 * @ORM\Entity
 */
class TrackELogin
{
    /**
     * @ORM\Column(name="login_user_id", type="integer", nullable=false)
     */
    protected int $loginUserId;

    /**
     * @ORM\Column(name="login_date", type="datetime", nullable=false)
     */
    protected DateTime $loginDate;

    /**
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected string $userIp;

    /**
     * @ORM\Column(name="logout_date", type="datetime", nullable=true)
     */
    protected ?DateTime $logoutDate = null;

    /**
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $loginId;

    /**
     * Set loginUserId.
     *
     * @return TrackELogin
     */
    public function setLoginUserId(int $loginUserId)
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
     * @return TrackELogin
     */
    public function setLoginDate(DateTime $loginDate)
    {
        $this->loginDate = $loginDate;

        return $this;
    }

    /**
     * Get loginDate.
     *
     * @return DateTime
     */
    public function getLoginDate()
    {
        return $this->loginDate;
    }

    /**
     * Set userIp.
     *
     * @return TrackELogin
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
     * Set logoutDate.
     *
     * @return TrackELogin
     */
    public function setLogoutDate(DateTime $logoutDate)
    {
        $this->logoutDate = $logoutDate;

        return $this;
    }

    /**
     * Get logoutDate.
     *
     * @return null|DateTime
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
