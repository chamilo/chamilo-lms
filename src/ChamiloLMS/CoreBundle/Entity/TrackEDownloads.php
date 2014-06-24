<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDownloads
 *
 * @ORM\Table(name="track_e_downloads", indexes={@ORM\Index(name="down_session_id", columns={"down_session_id"})})
 * @ORM\Entity
 */
class TrackEDownloads
{
    /**
     * @var integer
     *
     * @ORM\Column(name="down_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $downId;

    /**
     * @var integer
     *
     * @ORM\Column(name="down_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $downUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="down_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $downDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="down_doc_path", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $downDocPath;

    /**
     * @var integer
     *
     * @ORM\Column(name="down_session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $downSessionId;


    /**
     * Get downId
     *
     * @return integer
     */
    public function getDownId()
    {
        return $this->downId;
    }

    /**
     * Set downUserId
     *
     * @param integer $downUserId
     * @return TrackEDownloads
     */
    public function setDownUserId($downUserId)
    {
        $this->downUserId = $downUserId;

        return $this;
    }

    /**
     * Get downUserId
     *
     * @return integer
     */
    public function getDownUserId()
    {
        return $this->downUserId;
    }

    /**
     * Set downDate
     *
     * @param \DateTime $downDate
     * @return TrackEDownloads
     */
    public function setDownDate($downDate)
    {
        $this->downDate = $downDate;

        return $this;
    }

    /**
     * Get downDate
     *
     * @return \DateTime
     */
    public function getDownDate()
    {
        return $this->downDate;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackEDownloads
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set downDocPath
     *
     * @param string $downDocPath
     * @return TrackEDownloads
     */
    public function setDownDocPath($downDocPath)
    {
        $this->downDocPath = $downDocPath;

        return $this;
    }

    /**
     * Get downDocPath
     *
     * @return string
     */
    public function getDownDocPath()
    {
        return $this->downDocPath;
    }

    /**
     * Set downSessionId
     *
     * @param integer $downSessionId
     * @return TrackEDownloads
     */
    public function setDownSessionId($downSessionId)
    {
        $this->downSessionId = $downSessionId;

        return $this;
    }

    /**
     * Get downSessionId
     *
     * @return integer
     */
    public function getDownSessionId()
    {
        return $this->downSessionId;
    }
}
