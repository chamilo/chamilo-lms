<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySkillRelProfile
 *
 * @Table(name="skill_rel_profile")
 * @Entity
 */
class EntitySkillRelProfile
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
     * @Column(name="skill_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $skillId;

    /**
     * @var integer
     *
     * @Column(name="profile_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $profileId;


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
     * Set skillId
     *
     * @param integer $skillId
     * @return EntitySkillRelProfile
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
     * Set profileId
     *
     * @param integer $profileId
     * @return EntitySkillRelProfile
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * Get profileId
     *
     * @return integer 
     */
    public function getProfileId()
    {
        return $this->profileId;
    }
}
