<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="track_e_login_attempt",
 *     indexes={
 *         @ORM\Index(name="idx_track_e_login_attempt_username_success", columns={"username", "success"})
 *     }
 * )
 * Add @ to the next line if api_get_configuration_value('login_max_attempt_before_blocking_account') is enabled.
 * ORM\Entity
 */
class TrackELoginAttempt
{
    /**
     * @var int
     *
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=100)
     */
    protected $username;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_date", type="datetime")
     */
    protected $loginDate;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39)
     */
    protected $userIp;

    /**
     * @var bool
     *
     * @ORM\Column(name="success", type="boolean")
     */
    protected $success;

    public function __construct()
    {
        $this->success = false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): TrackELoginAttempt
    {
        $this->username = $username;

        return $this;
    }

    public function getLoginDate(): \DateTime
    {
        return $this->loginDate;
    }

    public function setLoginDate(\DateTime $loginDate): TrackELoginAttempt
    {
        $this->loginDate = $loginDate;

        return $this;
    }

    public function getUserIp(): string
    {
        return $this->userIp;
    }

    public function setUserIp(string $userIp): TrackELoginAttempt
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): TrackELoginAttempt
    {
        $this->success = $success;

        return $this;
    }
}
