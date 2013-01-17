<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySkillRelGradebook
 *
 * @Table(name="skill_rel_gradebook")
 * @Entity
 */
class EntitySkillRelGradebook
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
     * @Column(name="gradebook_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $gradebookId;

    /**
     * @var integer
     *
     * @Column(name="skill_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $skillId;

    /**
     * @var string
     *
     * @Column(name="type", type="string", length=10, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;


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
     * Set gradebookId
     *
     * @param integer $gradebookId
     * @return EntitySkillRelGradebook
     */
    public function setGradebookId($gradebookId)
    {
        $this->gradebookId = $gradebookId;

        return $this;
    }

    /**
     * Get gradebookId
     *
     * @return integer 
     */
    public function getGradebookId()
    {
        return $this->gradebookId;
    }

    /**
     * Set skillId
     *
     * @param integer $skillId
     * @return EntitySkillRelGradebook
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
     * Set type
     *
     * @param string $type
     * @return EntitySkillRelGradebook
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }
}
