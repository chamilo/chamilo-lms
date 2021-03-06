<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEOnline.
 *
 * @ORM\Table(
 *     name="track_e_online",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="login_user_id", columns={"login_user_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     }
 * )
 * @ORM\Entity
 */
class TrackEOnline
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
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    protected int $accessUrlId;

    /**
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $loginId;

    /**
     * Set loginUserId.
     *
     * @return TrackEOnline
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
     * @return TrackEOnline
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
     * @return TrackEOnline
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
     * Set cId.
     *
     * @return TrackEOnline
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
     * Set sessionId.
     *
     * @return TrackEOnline
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
     * Set accessUrlId.
     *
     * @return TrackEOnline
     */
    public function setAccessUrlId(int $accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId.
     *
     * @return int
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
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
