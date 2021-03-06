<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackELastaccess.
 *
 * @ORM\Table(name="track_e_lastaccess", indexes={
 *     @ORM\Index(name="access_user_id", columns={"access_user_id"}),
 *     @ORM\Index(name="access_c_id", columns={"c_id"}),
 *     @ORM\Index(name="access_session_id", columns={"access_session_id"})
 * })
 * @ORM\Entity
 */
class TrackELastaccess
{
    /**
     * @ORM\Column(name="access_user_id", type="integer", nullable=true)
     */
    protected int $accessUserId;

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
     * @ORM\Column(name="access_session_id", type="integer", nullable=true)
     */
    protected ?int $accessSessionId = null;

    /**
     * @ORM\Column(name="access_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $accessId;

    /**
     * Set accessUserId.
     *
     * @return TrackELastaccess
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
     * @return TrackELastaccess
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
     * @return TrackELastaccess
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
     * @return TrackELastaccess
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
     * @return TrackELastaccess
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
     * Get accessId.
     *
     * @return int
     */
    public function getAccessId()
    {
        return $this->accessId;
    }
}
