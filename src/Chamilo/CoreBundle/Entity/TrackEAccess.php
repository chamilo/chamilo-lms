<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAccess.
 *
 * @ORM\Table(name="track_e_access", indexes={
 *     @ORM\Index(name="access_user_id", columns={"access_user_id"}),
 *     @ORM\Index(name="access_c_id", columns={"c_id"}),
 *     @ORM\Index(name="access_session_id", columns={"access_session_id"})
 * })
 * @ORM\Entity
 */
class TrackEAccess
{
    /**
     * @var int
     *
     * @ORM\Column(name="access_user_id", type="integer", nullable=true)
     */
    protected $accessUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_date", type="datetime", nullable=false)
     */
    protected $accessDate;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="access_tool", type="string", length=30, nullable=true)
     */
    protected $accessTool;

    /**
     * @var int
     *
     * @ORM\Column(name="access_session_id", type="integer", nullable=false)
     */
    protected $accessSessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected $userIp;

    /**
     * @var int
     *
     * @ORM\Column(name="access_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $accessId;

    /**
     * Set accessUserId.
     *
     * @param int $accessUserId
     *
     * @return TrackEAccess
     */
    public function setAccessUserId($accessUserId)
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
     * @param \DateTime $accessDate
     *
     * @return TrackEAccess
     */
    public function setAccessDate($accessDate)
    {
        $this->accessDate = $accessDate;

        return $this;
    }

    /**
     * Get accessDate.
     *
     * @return \DateTime
     */
    public function getAccessDate()
    {
        return $this->accessDate;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return TrackEAccess
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
     * Set accessTool.
     *
     * @param string $accessTool
     *
     * @return TrackEAccess
     */
    public function setAccessTool($accessTool)
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
     * @param int $accessSessionId
     *
     * @return TrackEAccess
     */
    public function setAccessSessionId($accessSessionId)
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
     * @param string $userIp
     *
     * @return TrackEAccess
     */
    public function setUserIp($userIp)
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
