<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySkillRelUser
 *
 * @Table(name="skill_rel_user")
 * @Entity
 */
class EntitySkillRelUser
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
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="skill_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $skillId;

    /**
     * @var \DateTime
     *
     * @Column(name="acquired_skill_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $acquiredSkillAt;

    /**
     * @var integer
     *
     * @Column(name="assigned_by", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $assignedBy;


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
     * @return EntitySkillRelUser
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
     * Set skillId
     *
     * @param integer $skillId
     * @return EntitySkillRelUser
     */
    public function setSkillId($skillId)
    {
        $this->skillId = $skillId;

        return $this;
    }

    /**
     * Get skillId
     *
     * @return integer 
     */
    public function getSkillId()
    {
        return $this->skillId;
    }

    /**
     * Set acquiredSkillAt
     *
     * @param \DateTime $acquiredSkillAt
     * @return EntitySkillRelUser
     */
    public function setAcquiredSkillAt($acquiredSkillAt)
    {
        $this->acquiredSkillAt = $acquiredSkillAt;

        return $this;
    }

    /**
     * Get acquiredSkillAt
     *
     * @return \DateTime 
     */
    public function getAcquiredSkillAt()
    {
        return $this->acquiredSkillAt;
    }

    /**
     * Set assignedBy
     *
     * @param integer $assignedBy
     * @return EntitySkillRelUser
     */
    public function setAssignedBy($assignedBy)
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    /**
     * Get assignedBy
     *
     * @return integer 
     */
    public function getAssignedBy()
    {
        return $this->assignedBy;
    }
}
