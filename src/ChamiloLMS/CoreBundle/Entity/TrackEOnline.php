<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEOnline
 *
 * @ORM\Table(name="track_e_online", indexes={@ORM\Index(name="login_user_id", columns={"login_user_id"}), @ORM\Index(name="course", columns={"course"}), @ORM\Index(name="session_id", columns={"session_id"}), @ORM\Index(name="idx_trackonline_uat", columns={"login_user_id", "access_url_id", "login_date"})})
 * @ORM\Entity
 */
class TrackEOnline
{
    /**
     * @var integer
     *
     * @ORM\Column(name="login_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $loginId;

    /**
     * @var integer
     *
     * @ORM\Column(name="login_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $loginUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $loginDate;

    /**
     * @var string
     *
     * @ORM\Column(name="login_ip", type="string", length=39, precision=0, scale=0, nullable=false, unique=false)
     */
    private $loginIp;

    /**
     * @var string
     *
     * @ORM\Column(name="course", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $course;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlId;


    /**
     * Get loginId
     *
     * @return integer 
     */
    public function getLoginId()
    {
        return $this->loginId;
    }

    /**
     * Set loginUserId
     *
     * @param integer $loginUserId
     * @return TrackEOnline
     */
    public function setLoginUserId($loginUserId)
    {
        $this->loginUserId = $loginUserId;

        return $this;
    }

    /**
     * Get loginUserId
     *
     * @return integer 
     */
    public function getLoginUserId()
    {
        return $this->loginUserId;
    }

    /**
     * Set loginDate
     *
     * @param \DateTime $loginDate
     * @return TrackEOnline
     */
    public function setLoginDate($loginDate)
    {
        $this->loginDate = $loginDate;

        return $this;
    }

    /**
     * Get loginDate
     *
     * @return \DateTime 
     */
    public function getLoginDate()
    {
        return $this->loginDate;
    }

    /**
     * Set loginIp
     *
     * @param string $loginIp
     * @return TrackEOnline
     */
    public function setLoginIp($loginIp)
    {
        $this->loginIp = $loginIp;

        return $this;
    }

    /**
     * Get loginIp
     *
     * @return string 
     */
    public function getLoginIp()
    {
        return $this->loginIp;
    }

    /**
     * Set course
     *
     * @param string $course
     * @return TrackEOnline
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course
     *
     * @return string 
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return TrackEOnline
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return TrackEOnline
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId
     *
     * @return integer 
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }
}
