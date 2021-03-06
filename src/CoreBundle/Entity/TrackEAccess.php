<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAccess.
 *
 * @ORM\Table(name="track_e_access", indexes={
 *     @ORM\Index(name="access_user_id", columns={"access_user_id"}),
 *     @ORM\Index(name="access_c_id", columns={"c_id"}),
 *     @ORM\Index(name="access_session_id", columns={"access_session_id"}),
 *     @ORM\Index(name="user_course_session_date", columns={"access_user_id", "c_id", "access_session_id", "access_date"})
 * })
 * @ORM\Entity
 */
class TrackEAccess
{
    /**
     * @ORM\Column(name="access_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $accessId;

    /**
     * @ORM\Column(name="access_user_id", type="integer", nullable=true)
     */
    protected ?int $accessUserId = null;

    /**
     * @ORM\Column(name="access_date", type="datetime", nullable=false)
     */
    protected DateTime $accessDate;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\Column(name="access_tool", type="string", length=30, nullable=true)
     */
    protected ?string $accessTool = null;

    /**
     * @ORM\Column(name="access_session_id", type="integer", nullable=false)
     */
    protected int $accessSessionId;

    /**
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected string $userIp;

    /**
     * Set accessUserId.
     *
     * @return TrackEAccess
     */
    public function setAccessUserId(int $accessUserId)
    {
        $this->accessUserId = $accessUserId;

        return $this;
    }

    /**
     * Get accessUserId.
     *
     * @return int
     */
    public function getAccessUserId()
    {
        return $this->accessUserId;
    }

    /**
     * Set accessDate.
     *
     * @return TrackEAccess
     */
    public function setAccessDate(DateTime $accessDate)
    {
        $this->accessDate = $accessDate;

        return $this;
    }

    /**
     * Get accessDate.
     *
     * @return DateTime
     */
    public function getAccessDate()
    {
        return $this->accessDate;
    }

    /**
     * Set cId.
     *
     * @return TrackEAccess
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
     * Set accessTool.
     *
     * @return TrackEAccess
     */
    public function setAccessTool(string $accessTool)
    {
        $this->accessTool = $accessTool;

        return $this;
    }

    /**
     * Get accessTool.
     *
     * @return string
     */
    public function getAccessTool()
    {
        return $this->accessTool;
    }

    /**
     * Set accessSessionId.
     *
     * @return TrackEAccess
     */
    public function setAccessSessionId(int $accessSessionId)
    {
        $this->accessSessionId = $accessSessionId;

        return $this;
    }

    /**
     * Get accessSessionId.
     *
     * @return int
     */
    public function getAccessSessionId()
    {
        return $this->accessSessionId;
    }

    /**
     * Set userIp.
     *
     * @return TrackEAccess
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
     * Get accessId.
     *
     * @return int
     */
    public function getAccessId()
    {
        return $this->accessId;
    }
}
