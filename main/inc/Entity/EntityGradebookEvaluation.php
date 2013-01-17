<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradebookEvaluation
 *
 * @Table(name="gradebook_evaluation")
 * @Entity
 */
class EntityGradebookEvaluation
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
     * @var string
     *
     * @Column(name="name", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="category_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $categoryId;

    /**
     * @var \DateTime
     *
     * @Column(name="created_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdAt;

    /**
     * @var float
     *
     * @Column(name="weight", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $weight;

    /**
     * @var float
     *
     * @Column(name="max", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $max;

    /**
     * @var integer
     *
     * @Column(name="visible", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visible;

    /**
     * @var string
     *
     * @Column(name="type", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @Column(name="locked", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $locked;

    /**
     * @var integer
     *
     * @Column(name="evaluation_type_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $evaluationTypeId;


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
     * Set name
     *
     * @param string $name
     * @return EntityGradebookEvaluation
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
     * @return EntityGradebookEvaluation
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityGradebookEvaluation
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
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntityGradebookEvaluation
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode
     *
     * @return string 
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return EntityGradebookEvaluation
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer 
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EntityGradebookEvaluation
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
     * @param float $weight
     * @return EntityGradebookEvaluation
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return float 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set max
     *
     * @param float $max
     * @return EntityGradebookEvaluation
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max
     *
     * @return float 
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set visible
     *
     * @param integer $visible
     * @return EntityGradebookEvaluation
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return integer 
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return EntityGradebookEvaluation
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
     * Set locked
     *
     * @param integer $locked
     * @return EntityGradebookEvaluation
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return integer 
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set evaluationTypeId
     *
     * @param integer $evaluationTypeId
     * @return EntityGradebookEvaluation
     */
    public function setEvaluationTypeId($evaluationTypeId)
    {
        $this->evaluationTypeId = $evaluationTypeId;

        return $this;
    }

    /**
     * Get evaluationTypeId
     *
     * @return integer 
     */
    public function getEvaluationTypeId()
    {
        return $this->evaluationTypeId;
    }
}
