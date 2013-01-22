<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityUserRelUser
 *
 * @Table(name="user_rel_user")
 * @Entity
 */
class EntityUserRelUser
{
    /**
     * @var integer
     *
     * @Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="friend_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $friendUserId;

    /**
     * @var integer
     *
     * @Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $relationType;

    /**
     * @var \DateTime
     *
     * @Column(name="last_edit", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lastEdit;


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
     * Set userId
     *
     * @param integer $userId
     * @return EntityUserRelUser
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
     * Set friendUserId
     *
     * @param integer $friendUserId
     * @return EntityUserRelUser
     */
    public function setFriendUserId($friendUserId)
    {
        $this->friendUserId = $friendUserId;

        return $this;
    }

    /**
     * Get friendUserId
     *
     * @return integer 
     */
    public function getFriendUserId()
    {
        return $this->friendUserId;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     * @return EntityUserRelUser
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType
     *
     * @return integer 
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Set lastEdit
     *
     * @param \DateTime $lastEdit
     * @return EntityUserRelUser
     */
    public function setLastEdit($lastEdit)
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get lastEdit
     *
     * @return \DateTime 
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }
}
