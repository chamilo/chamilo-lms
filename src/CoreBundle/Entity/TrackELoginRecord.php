<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Track Login Record.
 *
 * @ORM\Table(name="track_e_login_record")
 * @ORM\Entity
 */
class TrackELoginRecord
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="username", type="string", length=100, nullable=false)
     */
    protected string $username;

    /**
     * @ORM\Column(name="login_date", type="datetime", nullable=false)
     */
    protected DateTime $loginDate;

    /**
     * @ORM\Column(name="user_ip", type="string", length=45, nullable=false)
     */
    protected string $userIp;

    /**
     * @ORM\Column(name="success", type="boolean")
     */
    protected bool $success;

    /**
     * Get the username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set the username.
     *
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set Login date.
     *
     * @return $this
     */
    public function setLoginDate(DateTime $loginDate): self
    {
        $this->loginDate = $loginDate;

        return $this;
    }

    /**
     * Get login date.
     *
     * @return DateTime
     */
    public function getLoginDate()
    {
        return $this->loginDate;
    }

    /**
     * Set user ip.
     *
     * @return $this
     */
    public function setUserIp(string $userIp): self
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get user Ip.
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Get the success value.
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Set the success value.
     *
     * @return $this
     */
    public function setSuccess(bool $boolean): self
    {
        $this->success = $boolean;

        return $this;
    }
}
