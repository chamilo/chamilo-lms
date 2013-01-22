<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradebookLinkevalLog
 *
 * @Table(name="gradebook_linkeval_log")
 * @Entity
 */
class EntityGradebookLinkevalLog
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
     * @Column(name="id_linkeval_log", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $idLinkevalLog;

    /**
     * @var string
     *
     * @Column(name="name", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @Column(name="created_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @Column(name="weight", type="smallint", precision=0, scale=0, nullable=true, unique=false)
     */
    private $weight;

    /**
     * @var boolean
     *
     * @Column(name="visible", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $visible;

    /**
     * @var string
     *
     * @Column(name="type", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @Column(name="user_id_log", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userIdLog;


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
     * Set idLinkevalLog
     *
     * @param integer $idLinkevalLog
     * @return EntityGradebookLinkevalLog
     */
    public function setIdLinkevalLog($idLinkevalLog)
    {
        $this->idLinkevalLog = $idLinkevalLog;

        return $this;
    }

    /**
     * Get idLinkevalLog
     *
     * @return integer 
     */
    public function getIdLinkevalLog()
    {
        return $this->idLinkevalLog;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return EntityGradebookLinkevalLog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityGradebookLinkevalLog
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EntityGradebookLinkevalLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return EntityGradebookLinkevalLog
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     * @return EntityGradebookLinkevalLog
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean 
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return EntityGradebookLinkevalLog
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

    /**
     * Set userIdLog
     *
     * @param integer $userIdLog
     * @return EntityGradebookLinkevalLog
     */
    public function setUserIdLog($userIdLog)
    {
        $this->userIdLog = $userIdLog;

        return $this;
    }

    /**
     * Get userIdLog
     *
     * @return integer 
     */
    public function getUserIdLog()
    {
        return $this->userIdLog;
    }
}
