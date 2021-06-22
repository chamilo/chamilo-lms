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
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $loginId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="logins")
     * @ORM\JoinColumn(name="login_user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setLoginDate(DateTime $loginDate): self
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

    public function setUserIp(string $userIp): self
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

    public function setLogoutDate(DateTime $logoutDate): self
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
