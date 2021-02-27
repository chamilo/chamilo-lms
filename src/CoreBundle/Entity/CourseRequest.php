<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CourseRequest.
 *
 * @ORM\Table(name="course_request", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="code", columns={"code"})
 * })
 * @ORM\Entity
 */
class CourseRequest
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected User $user;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    protected string $code;

    /**
     * @ORM\Column(name="course_language", type="string", length=20, nullable=false)
     */
    protected string $courseLanguage;

    /**
     * @ORM\Column(name="title", type="string", length=250, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="category_code", type="string", length=40, nullable=true)
     */
    protected ?string $categoryCode;

    /**
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true)
     */
    protected ?string $tutorName;

    /**
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true)
     */
    protected ?string $visualCode;

    /**
     * @ORM\Column(name="request_date", type="datetime", nullable=false)
     */
    protected DateTime $requestDate;

    /**
     * @ORM\Column(name="objetives", type="text", nullable=true)
     */
    protected ?string $objetives;

    /**
     * @ORM\Column(name="target_audience", type="text", nullable=true)
     */
    protected ?string $targetAudience;

    /**
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected int $status;

    /**
     * @ORM\Column(name="info", type="integer", nullable=false)
     */
    protected int $info;

    /**
     * @ORM\Column(name="exemplary_content", type="integer", nullable=false)
     */
    protected int $exemplaryContent;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->requestDate = new DateTime();
    }

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
     * @param DateTime $requestDate
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
     * @return DateTime
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
