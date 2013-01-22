<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySkillRelSkill
 *
 * @Table(name="skill_rel_skill")
 * @Entity
 */
class EntitySkillRelSkill
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
     * @Column(name="parent_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $relationType;

    /**
     * @var integer
     *
     * @Column(name="level", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $level;


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
     * @return EntitySkillRelSkill
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
     * Set parentId
     *
     * @param integer $parentId
     * @return EntitySkillRelSkill
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer 
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     * @return EntitySkillRelSkill
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
     * Set level
     *
     * @param integer $level
     * @return EntitySkillRelSkill
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer 
     */
    public function getLevel()
    {
        return $this->level;
    }
}
