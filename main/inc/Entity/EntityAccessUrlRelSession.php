<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityAccessUrlRelSession
 *
 * @Table(name="access_url_rel_session")
 * @Entity
 */
class EntityAccessUrlRelSession
{
    /**
     * @var integer
     *
     * @Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $sessionId;


    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return EntityAccessUrlRelSession
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityAccessUrlRelSession
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
}
