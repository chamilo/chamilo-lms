<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PhpSession
 *
 * @ORM\Table(name="php_session")
 * @ORM\Entity
 */
class PhpSession
{
    /**
     * @var string
     *
     * @ORM\Column(name="session_name", type="string", length=10, nullable=false)
     */
    private $sessionName;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_time", type="integer", nullable=false)
     */
    private $sessionTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_start", type="integer", nullable=false)
     */
    private $sessionStart;

    /**
     * @var string
     *
     * @ORM\Column(name="session_value", type="text", nullable=false)
     */
    private $sessionValue;

    /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=32)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $sessionId;



    /**
     * Set sessionName
     *
     * @param string $sessionName
     * @return PhpSession
     */
    public function setSessionName($sessionName)
    {
        $this->sessionName = $sessionName;

        return $this;
    }

    /**
     * Get sessionName
     *
     * @return string
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }

    /**
     * Set sessionTime
     *
     * @param integer $sessionTime
     * @return PhpSession
     */
    public function setSessionTime($sessionTime)
    {
        $this->sessionTime = $sessionTime;

        return $this;
    }

    /**
     * Get sessionTime
     *
     * @return integer
     */
    public function getSessionTime()
    {
        return $this->sessionTime;
    }

    /**
     * Set sessionStart
     *
     * @param integer $sessionStart
     * @return PhpSession
     */
    public function setSessionStart($sessionStart)
    {
        $this->sessionStart = $sessionStart;

        return $this;
    }

    /**
     * Get sessionStart
     *
     * @return integer
     */
    public function getSessionStart()
    {
        return $this->sessionStart;
    }

    /**
     * Set sessionValue
     *
     * @param string $sessionValue
     * @return PhpSession
     */
    public function setSessionValue($sessionValue)
    {
        $this->sessionValue = $sessionValue;

        return $this;
    }

    /**
     * Get sessionValue
     *
     * @return string
     */
    public function getSessionValue()
    {
        return $this->sessionValue;
    }

    /**
     * Get sessionId
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
