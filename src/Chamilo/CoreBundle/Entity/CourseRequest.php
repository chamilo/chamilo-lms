<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseRequest.
 *
 * @todo fix objetives variable
 *
 * @ORM\Table(name="course_request", uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})})
 * @ORM\Entity
 */
class CourseRequest
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    protected $code;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="directory", type="string", length=40, nullable=true)
     */
    protected $directory;

    /**
     * @var string
     *
     * @ORM\Column(name="db_name", type="string", length=40, nullable=true)
     */
    protected $dbName;

    /**
     * @var string
     *
     * @ORM\Column(name="course_language", type="string", length=20, nullable=true)
     */
    protected $courseLanguage;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="category_code", type="string", length=40, nullable=true)
     */
    protected $categoryCode;

    /**
     * @var string
     *
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true)
     */
    protected $tutorName;

    /**
     * @var string
     *
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true)
     */
    protected $visualCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="request_date", type="datetime", nullable=false)
     */
    protected $requestDate;

    /**
     * @var string
     *
     * @ORM\Column(name="objetives", type="text", nullable=true)
     */
    protected $objetives;

    /**
     * @var string
     *
     * @ORM\Column(name="target_audience", type="text", nullable=true)
     */
    protected $targetAudience;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var int
     *
     * @ORM\Column(name="info", type="integer", nullable=false)
     */
    protected $info;

    /**
     * @var int
     *
     * @ORM\Column(name="exemplary_content", type="integer", nullable=false)
     */
    protected $exemplaryContent;

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return CourseRequest
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CourseRequest
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set directory.
     *
     * @param string $directory
     *
     * @return CourseRequest
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get directory.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set dbName.
     *
     * @param string $dbName
     *
     * @return CourseRequest
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;

        return $this;
    }

    /**
     * Get dbName.
     *
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Set courseLanguage.
     *
     * @param string $courseLanguage
     *
     * @return CourseRequest
     */
    public function setCourseLanguage($courseLanguage)
    {
        $this->courseLanguage = $courseLanguage;

        return $this;
    }

    /**
     * Get courseLanguage.
     *
     * @return string
     */
    public function getCourseLanguage()
    {
        return $this->courseLanguage;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CourseRequest
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CourseRequest
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set categoryCode.
     *
     * @param string $categoryCode
     *
     * @return CourseRequest
     */
    public function setCategoryCode($categoryCode)
    {
        $this->categoryCode = $categoryCode;

        return $this;
    }

    /**
     * Get categoryCode.
     *
     * @return string
     */
    public function getCategoryCode()
    {
        return $this->categoryCode;
    }

    /**
     * Set tutorName.
     *
     * @param string $tutorName
     *
     * @return CourseRequest
     */
    public function setTutorName($tutorName)
    {
        $this->tutorName = $tutorName;

        return $this;
    }

    /**
     * Get tutorName.
     *
     * @return string
     */
    public function getTutorName()
    {
        return $this->tutorName;
    }

    /**
     * Set visualCode.
     *
     * @param string $visualCode
     *
     * @return CourseRequest
     */
    public function setVisualCode($visualCode)
    {
        $this->visualCode = $visualCode;

        return $this;
    }

    /**
     * Get visualCode.
     *
     * @return string
     */
    public function getVisualCode()
    {
        return $this->visualCode;
    }

    /**
     * Set requestDate.
     *
     * @param \DateTime $requestDate
     *
     * @return CourseRequest
     */
    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    /**
     * Get requestDate.
     *
     * @return \DateTime
     */
    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * Set objetives.
     *
     * @param string $objetives
     *
     * @return CourseRequest
     */
    public function setObjetives($objetives)
    {
        $this->objetives = $objetives;

        return $this;
    }

    /**
     * Get objetives.
     *
     * @return string
     */
    public function getObjetives()
    {
        return $this->objetives;
    }

    /**
     * Set targetAudience.
     *
     * @param string $targetAudience
     *
     * @return CourseRequest
     */
    public function setTargetAudience($targetAudience)
    {
        $this->targetAudience = $targetAudience;

        return $this;
    }

    /**
     * Get targetAudience.
     *
     * @return string
     */
    public function getTargetAudience()
    {
        return $this->targetAudience;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return CourseRequest
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set info.
     *
     * @param int $info
     *
     * @return CourseRequest
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info.
     *
     * @return int
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set exemplaryContent.
     *
     * @param int $exemplaryContent
     *
     * @return CourseRequest
     */
    public function setExemplaryContent($exemplaryContent)
    {
        $this->exemplaryContent = $exemplaryContent;

        return $this;
    }

    /**
     * Get exemplaryContent.
     *
     * @return int
     */
    public function getExemplaryContent()
    {
        return $this->exemplaryContent;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
