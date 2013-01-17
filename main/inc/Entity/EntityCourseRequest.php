<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCourseRequest
 *
 * @Table(name="course_request")
 * @Entity
 */
class EntityCourseRequest
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
     * @Column(name="code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $code;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @Column(name="directory", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $directory;

    /**
     * @var string
     *
     * @Column(name="db_name", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $dbName;

    /**
     * @var string
     *
     * @Column(name="course_language", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $courseLanguage;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="category_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $categoryCode;

    /**
     * @var string
     *
     * @Column(name="tutor_name", type="string", length=200, precision=0, scale=0, nullable=true, unique=false)
     */
    private $tutorName;

    /**
     * @var string
     *
     * @Column(name="visual_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $visualCode;

    /**
     * @var \DateTime
     *
     * @Column(name="request_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $requestDate;

    /**
     * @var string
     *
     * @Column(name="objetives", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $objetives;

    /**
     * @var string
     *
     * @Column(name="target_audience", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $targetAudience;

    /**
     * @var integer
     *
     * @Column(name="status", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @Column(name="info", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $info;

    /**
     * @var integer
     *
     * @Column(name="exemplary_content", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exemplaryContent;


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
     * Set code
     *
     * @param string $code
     * @return EntityCourseRequest
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityCourseRequest
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
     * Set directory
     *
     * @param string $directory
     * @return EntityCourseRequest
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get directory
     *
     * @return string 
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set dbName
     *
     * @param string $dbName
     * @return EntityCourseRequest
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;

        return $this;
    }

    /**
     * Get dbName
     *
     * @return string 
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Set courseLanguage
     *
     * @param string $courseLanguage
     * @return EntityCourseRequest
     */
    public function setCourseLanguage($courseLanguage)
    {
        $this->courseLanguage = $courseLanguage;

        return $this;
    }

    /**
     * Get courseLanguage
     *
     * @return string 
     */
    public function getCourseLanguage()
    {
        return $this->courseLanguage;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityCourseRequest
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityCourseRequest
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
     * Set categoryCode
     *
     * @param string $categoryCode
     * @return EntityCourseRequest
     */
    public function setCategoryCode($categoryCode)
    {
        $this->categoryCode = $categoryCode;

        return $this;
    }

    /**
     * Get categoryCode
     *
     * @return string 
     */
    public function getCategoryCode()
    {
        return $this->categoryCode;
    }

    /**
     * Set tutorName
     *
     * @param string $tutorName
     * @return EntityCourseRequest
     */
    public function setTutorName($tutorName)
    {
        $this->tutorName = $tutorName;

        return $this;
    }

    /**
     * Get tutorName
     *
     * @return string 
     */
    public function getTutorName()
    {
        return $this->tutorName;
    }

    /**
     * Set visualCode
     *
     * @param string $visualCode
     * @return EntityCourseRequest
     */
    public function setVisualCode($visualCode)
    {
        $this->visualCode = $visualCode;

        return $this;
    }

    /**
     * Get visualCode
     *
     * @return string 
     */
    public function getVisualCode()
    {
        return $this->visualCode;
    }

    /**
     * Set requestDate
     *
     * @param \DateTime $requestDate
     * @return EntityCourseRequest
     */
    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    /**
     * Get requestDate
     *
     * @return \DateTime 
     */
    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * Set objetives
     *
     * @param string $objetives
     * @return EntityCourseRequest
     */
    public function setObjetives($objetives)
    {
        $this->objetives = $objetives;

        return $this;
    }

    /**
     * Get objetives
     *
     * @return string 
     */
    public function getObjetives()
    {
        return $this->objetives;
    }

    /**
     * Set targetAudience
     *
     * @param string $targetAudience
     * @return EntityCourseRequest
     */
    public function setTargetAudience($targetAudience)
    {
        $this->targetAudience = $targetAudience;

        return $this;
    }

    /**
     * Get targetAudience
     *
     * @return string 
     */
    public function getTargetAudience()
    {
        return $this->targetAudience;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return EntityCourseRequest
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set info
     *
     * @param integer $info
     * @return EntityCourseRequest
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return integer 
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set exemplaryContent
     *
     * @param integer $exemplaryContent
     * @return EntityCourseRequest
     */
    public function setExemplaryContent($exemplaryContent)
    {
        $this->exemplaryContent = $exemplaryContent;

        return $this;
    }

    /**
     * Get exemplaryContent
     *
     * @return integer 
     */
    public function getExemplaryContent()
    {
        return $this->exemplaryContent;
    }
}
