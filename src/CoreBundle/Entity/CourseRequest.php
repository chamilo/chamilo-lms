<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
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
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected User $user;

    /**
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    #[Assert\NotBlank]
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
    protected ?string $description = null;

    /**
     * @ORM\Column(name="category_code", type="string", length=40, nullable=true)
     */
    protected ?string $categoryCode = null;

    /**
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true)
     */
    protected ?string $tutorName = null;

    /**
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true)
     */
    protected ?string $visualCode = null;

    /**
     * @ORM\Column(name="request_date", type="datetime", nullable=false)
     */
    protected DateTime $requestDate;

    /**
     * @ORM\Column(name="objetives", type="text", nullable=true)
     */
    protected ?string $objetives = null;

    /**
     * @ORM\Column(name="target_audience", type="text", nullable=true)
     */
    protected ?string $targetAudience = null;

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

    public function __construct()
    {
        $this->requestDate = new DateTime();
    }

    public function setCode(string $code): self
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

    public function setCourseLanguage(string $courseLanguage): self
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

    public function setTitle(string $title): self
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

    public function setDescription(string $description): self
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

    public function setCategoryCode(string $categoryCode): self
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

    public function setTutorName(string $tutorName): self
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

    public function setVisualCode(string $visualCode): self
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

    public function setRequestDate(DateTime $requestDate): self
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

    public function setObjetives(string $objetives): self
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

    public function setTargetAudience(string $targetAudience): self
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

    public function setStatus(int $status): self
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

    public function setInfo(int $info): self
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

    public function setExemplaryContent(int $exemplaryContent): self
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
