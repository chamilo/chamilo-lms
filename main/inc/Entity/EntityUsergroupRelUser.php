<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityUsergroupRelUser
 *
 * @Table(name="usergroup_rel_user")
 * @Entity
 */
class EntityUsergroupRelUser
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
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;


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
     * @return EntityUsergroupRelUser
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityUsergroupRelUser
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
