<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityAccessUrlRelUser
 *
 * @Table(name="access_url_rel_user")
 * @Entity
 */
class EntityAccessUrlRelUser
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
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $userId;


    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return EntityAccessUrlRelUser
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityAccessUrlRelUser
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
}
