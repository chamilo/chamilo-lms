<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDownloads.
 *
 * @ORM\Table(name="track_e_downloads", indexes={
 *     @ORM\Index(name="idx_ted_user_id", columns={"down_user_id"}),
 *     @ORM\Index(name="idx_ted_c_id", columns={"c_id"}),
 *     @ORM\Index(name="session_id", columns={"session_id"})
 * })
 * @ORM\Entity
 */
class TrackEDownloads
{
    /**
     * @ORM\Column(name="down_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $downId;

    /**
     * @ORM\Column(name="down_user_id", type="integer", nullable=true)
     */
    protected ?int $downUserId = null;

    /**
     * @ORM\Column(name="down_date", type="datetime", nullable=false)
     */
    protected DateTime $downDate;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\Column(name="down_doc_path", type="string", length=255, nullable=false)
     */
    protected string $downDocPath;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * Set downUserId.
     *
     * @return TrackEDownloads
     */
    public function setDownUserId(int $downUserId)
    {
        $this->downUserId = $downUserId;

        return $this;
    }

    /**
     * Get downUserId.
     *
     * @return int
     */
    public function getDownUserId()
    {
        return $this->downUserId;
    }

    /**
     * Set downDate.
     *
     * @return TrackEDownloads
     */
    public function setDownDate(DateTime $downDate)
    {
        $this->downDate = $downDate;

        return $this;
    }

    /**
     * Get downDate.
     *
     * @return DateTime
     */
    public function getDownDate()
    {
        return $this->downDate;
    }

    /**
     * Set cId.
     *
     * @return TrackEDownloads
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
     * Set downDocPath.
     *
     * @return TrackEDownloads
     */
    public function setDownDocPath(string $downDocPath)
    {
        $this->downDocPath = $downDocPath;

        return $this;
    }

    /**
     * Get downDocPath.
     *
     * @return string
     */
    public function getDownDocPath()
    {
        return $this->downDocPath;
    }

    /**
     * Set sessionId.
     *
     * @return TrackEDownloads
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
     * Get downId.
     *
     * @return int
     */
    public function getDownId()
    {
        return $this->downId;
    }
}
