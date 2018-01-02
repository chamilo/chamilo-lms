<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEOnline
 *
 * @ORM\Table(
 *  name="track_e_online",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="login_user_id", columns={"login_user_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class TrackEOnline
{
    /**
     * @var integer
     *
     * @ORM\Column(name="login_user_id", type="integer", nullable=false)
     */
    private $loginUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_date", type="datetime", nullable=false)
     */
    private $loginDate;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    private $userIp;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $loginId;



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
     * Set userIp
     *
     * @param string $userIp
     * @return TrackEOnline
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackEOnline
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

    /**
     * Get loginId
     *
     * @return integer
     */
    public function getLoginId()
    {
        return $this->loginId;
    }
}
