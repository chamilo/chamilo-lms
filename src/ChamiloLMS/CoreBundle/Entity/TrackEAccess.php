<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAccess
 *
 * @ORM\Table(name="track_e_access", indexes={@ORM\Index(name="access_user_id", columns={"access_user_id"}), @ORM\Index(name="access_cid_user", columns={"c_id", "access_user_id"}), @ORM\Index(name="access_session_id", columns={"access_session_id"})})
 * @ORM\Entity
 */
class TrackEAccess
{
    /**
     * @var integer
     *
     * @ORM\Column(name="access_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $accessId;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="access_tool", type="string", length=30, precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessTool;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessSessionId;


    /**
     * Get accessId
     *
     * @return integer
     */
    public function getAccessId()
    {
        return $this->accessId;
    }

    /**
     * Set accessUserId
     *
     * @param integer $accessUserId
     * @return TrackEAccess
     */
    public function setAccessUserId($accessUserId)
    {
        $this->accessUserId = $accessUserId;

        return $this;
    }

    /**
     * Get accessUserId
     *
     * @return integer
     */
    public function getAccessUserId()
    {
        return $this->accessUserId;
    }

    /**
     * Set accessDate
     *
     * @param \DateTime $accessDate
     * @return TrackEAccess
     */
    public function setAccessDate($accessDate)
    {
        $this->accessDate = $accessDate;

        return $this;
    }

    /**
     * Get accessDate
     *
     * @return \DateTime
     */
    public function getAccessDate()
    {
        return $this->accessDate;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackEAccess
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
     * Set accessTool
     *
     * @param string $accessTool
     * @return TrackEAccess
     */
    public function setAccessTool($accessTool)
    {
        $this->accessTool = $accessTool;

        return $this;
    }

    /**
     * Get accessTool
     *
     * @return string
     */
    public function getAccessTool()
    {
        return $this->accessTool;
    }

    /**
     * Set accessSessionId
     *
     * @param integer $accessSessionId
     * @return TrackEAccess
     */
    public function setAccessSessionId($accessSessionId)
    {
        $this->accessSessionId = $accessSessionId;

        return $this;
    }

    /**
     * Get accessSessionId
     *
     * @return integer
     */
    public function getAccessSessionId()
    {
        return $this->accessSessionId;
    }
}
