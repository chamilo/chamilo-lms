<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradebookCategory
 *
 * @Table(name="gradebook_category")
 * @Entity
 */
class EntityGradebookCategory
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
     * @Column(name="parent_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $parentId;

    /**
     * @var float
     *
     * @Column(name="weight", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $weight;

    /**
     * @var boolean
     *
     * @Column(name="visible", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visible;

    /**
     * @var integer
     *
     * @Column(name="certif_min_score", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $certifMinScore;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="document_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $documentId;

    /**
     * @var integer
     *
     * @Column(name="locked", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $locked;

    /**
     * @var boolean
     *
     * @Column(name="default_lowest_eval_exclude", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $defaultLowestEvalExclude;

    /**
     * @var integer
     *
     * @Column(name="grade_model_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $gradeModelId;


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
     * @return EntityGradebookCategory
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
     * @return EntityGradebookCategory
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
     * @return EntityGradebookCategory
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
     * @return EntityGradebookCategory
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
     * Set parentId
     *
     * @param integer $parentId
     * @return EntityGradebookCategory
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
     * Set weight
     *
     * @param float $weight
     * @return EntityGradebookCategory
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
     * @param boolean $visible
     * @return EntityGradebookCategory
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
     * Set certifMinScore
     *
     * @param integer $certifMinScore
     * @return EntityGradebookCategory
     */
    public function setCertifMinScore($certifMinScore)
    {
        $this->certifMinScore = $certifMinScore;

        return $this;
    }

    /**
     * Get certifMinScore
     *
     * @return integer 
     */
    public function getCertifMinScore()
    {
        return $this->certifMinScore;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityGradebookCategory
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set documentId
     *
     * @param integer $documentId
     * @return EntityGradebookCategory
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;

        return $this;
    }

    /**
     * Get documentId
     *
     * @return integer 
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Set locked
     *
     * @param integer $locked
     * @return EntityGradebookCategory
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
     * Set defaultLowestEvalExclude
     *
     * @param boolean $defaultLowestEvalExclude
     * @return EntityGradebookCategory
     */
    public function setDefaultLowestEvalExclude($defaultLowestEvalExclude)
    {
        $this->defaultLowestEvalExclude = $defaultLowestEvalExclude;

        return $this;
    }

    /**
     * Get defaultLowestEvalExclude
     *
     * @return boolean 
     */
    public function getDefaultLowestEvalExclude()
    {
        return $this->defaultLowestEvalExclude;
    }

    /**
     * Set gradeModelId
     *
     * @param integer $gradeModelId
     * @return EntityGradebookCategory
     */
    public function setGradeModelId($gradeModelId)
    {
        $this->gradeModelId = $gradeModelId;

        return $this;
    }

    /**
     * Get gradeModelId
     *
     * @return integer 
     */
    public function getGradeModelId()
    {
        return $this->gradeModelId;
    }
}
