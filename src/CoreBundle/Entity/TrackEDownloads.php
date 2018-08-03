<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDownloads.
 *
 * @ORM\Table(name="track_e_downloads", indexes={
 *     @ORM\Index(name="idx_ted_user_id", columns={"down_user_id"}),
 *     @ORM\Index(name="idx_ted_c_id", columns={"c_id"}),
 *     @ORM\Index(name="down_session_id", columns={"down_session_id"})
 * })
 * @ORM\Entity
 */
class TrackEDownloads
{
    /**
     * @var int
     *
     * @ORM\Column(name="down_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $downId;

    /**
     * @var int
     *
     * @ORM\Column(name="down_user_id", type="integer", nullable=true)
     */
    protected $downUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="down_date", type="datetime", nullable=false)
     */
    protected $downDate;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="down_doc_path", type="string", length=255, nullable=false)
     */
    protected $downDocPath;

    /**
     * @var int
     *
     * @ORM\Column(name="down_session_id", type="integer", nullable=false)
     */
    protected $downSessionId;

    /**
     * Set downUserId.
     *
     * @param int $downUserId
     *
     * @return TrackEDownloads
     */
    public function setDownUserId($downUserId)
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
     * @param \DateTime $downDate
     *
     * @return TrackEDownloads
     */
    public function setDownDate($downDate)
    {
        $this->downDate = $downDate;

        return $this;
    }

    /**
     * Get downDate.
     *
     * @return \DateTime
     */
    public function getDownDate()
    {
        return $this->downDate;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return TrackEDownloads
     */
    public function setCId($cId)
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
     * @param string $downDocPath
     *
     * @return TrackEDownloads
     */
    public function setDownDocPath($downDocPath)
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
     * Set downSessionId.
     *
     * @param int $downSessionId
     *
     * @return TrackEDownloads
     */
    public function setDownSessionId($downSessionId)
    {
        $this->downSessionId = $downSessionId;

        return $this;
    }

    /**
     * Get downSessionId.
     *
     * @return int
     */
    public function getDownSessionId()
    {
        return $this->downSessionId;
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
