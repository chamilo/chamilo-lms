<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradebookLink
 *
 * @Table(name="gradebook_link")
 * @Entity
 */
class EntityGradebookLink
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
     * @Column(name="type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @Column(name="ref_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $refId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
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
     * @var integer
     *
     * @Column(name="visible", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visible;

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
     * Set type
     *
     * @param integer $type
     * @return EntityGradebookLink
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set refId
     *
     * @param integer $refId
     * @return EntityGradebookLink
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * Get refId
     *
     * @return integer 
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityGradebookLink
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
     * @return EntityGradebookLink
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
     * @return EntityGradebookLink
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
     * @return EntityGradebookLink
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
     * @return EntityGradebookLink
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
     * Set visible
     *
     * @param integer $visible
     * @return EntityGradebookLink
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
     * Set locked
     *
     * @param integer $locked
     * @return EntityGradebookLink
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
     * @return EntityGradebookLink
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
