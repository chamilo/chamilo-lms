<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityPhpSession
 *
 * @Table(name="php_session")
 * @Entity
 */
class EntityPhpSession
{
    /**
     * @var string
     *
     * @Column(name="session_id", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $sessionId;

    /**
     * @var string
     *
     * @Column(name="session_name", type="string", length=10, precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionName;

    /**
     * @var integer
     *
     * @Column(name="session_time", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionTime;

    /**
     * @var integer
     *
     * @Column(name="session_start", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionStart;

    /**
     * @var string
     *
     * @Column(name="session_value", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionValue;


    /**
     * Get sessionId
     *
     * @return string 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set sessionName
     *
     * @param string $sessionName
     * @return EntityPhpSession
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
     * @return EntityPhpSession
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
     * @return EntityPhpSession
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
     * @return EntityPhpSession
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
}
