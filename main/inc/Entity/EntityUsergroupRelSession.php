<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityUsergroupRelSession
 *
 * @Table(name="usergroup_rel_session")
 * @Entity
 */
class EntityUsergroupRelSession
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="usergroup_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $usergroupId;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set usergroupId
     *
     * @param integer $usergroupId
     * @return EntityUsergroupRelSession
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId
     *
     * @return integer 
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityUsergroupRelSession
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
