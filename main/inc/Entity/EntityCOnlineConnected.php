<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCOnlineConnected
 *
 * @Table(name="c_online_connected")
 * @Entity
 */
class EntityCOnlineConnected
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @Column(name="last_connection", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lastConnection;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCOnlineConnected
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityCOnlineConnected
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set lastConnection
     *
     * @param \DateTime $lastConnection
     * @return EntityCOnlineConnected
     */
    public function setLastConnection($lastConnection)
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    /**
     * Get lastConnection
     *
     * @return \DateTime 
     */
    public function getLastConnection()
    {
        return $this->lastConnection;
    }
}
