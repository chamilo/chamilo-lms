<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ToolLaunch.
 *
 * @package Chamilo\PluginBundle\Entity\XApi
 *
 * @ORM\Table(name="xapi_tool_launch")
 * @ORM\Entity()
 */
class ToolLaunch
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string")
     */
    private $title;
    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;
    /**
     * @var \Chamilo\CoreBundle\Entity\Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    private $course;
    /**
     * @var \Chamilo\CoreBundle\Entity\Session|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;
    /**
     * @var string
     *
     * @ORM\Column(name="launch_url", type="string")
     */
    private $launchUrl;
    /**
     * @var string|null
     *
     * @ORM\Column(name="activity_id", type="string", nullable=true)
     */
    private $activityId;
    /**
     * @var string|null
     *
     * @ORM\Column(name="activity_type", type="string", nullable=true)
     */
    private $activityType;
    /**
     * @var bool
     *
     * @ORM\Column(name="allow_multiple_attempts", type="boolean", options={"default": true})
     */
    private $allowMultipleAttempts;
    /**
     * @var string|null
     *
     * @ORM\Column(name="lrs_url", type="string", nullable=true)
     */
    private $lrsUrl;
    /**
     * @var string|null
     *
     * @ORM\Column(name="lrs_auth", type="string", nullable=true)
     */
    private $lrsAuth;
    /***
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * ToolLaunch constructor.
     */
    public function __construct()
    {
        $this->allowMultipleAttempts = true;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return ToolLaunch
     */
    public function setId(int $id): ToolLaunch
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return ToolLaunch
     */
    public function setTitle(string $title): ToolLaunch
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return ToolLaunch
     */
    public function setDescription(?string $description): ToolLaunch
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\Course
     */
    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\Course $course
     *
     * @return ToolLaunch
     */
    public function setCourse(Course $course): ToolLaunch
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\Session|null
     */
    public function getSession(): ?Session
    {
        return $this->session;
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\Session|null $session
     *
     * @return ToolLaunch
     */
    public function setSession(?Session $session): ToolLaunch
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return string
     */
    public function getLaunchUrl(): string
    {
        return $this->launchUrl;
    }

    /**
     * @param string $launchUrl
     *
     * @return ToolLaunch
     */
    public function setLaunchUrl(string $launchUrl): ToolLaunch
    {
        $this->launchUrl = $launchUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getActivityId(): ?string
    {
        return $this->activityId;
    }

    /**
     * @param string|null $activityId
     *
     * @return ToolLaunch
     */
    public function setActivityId(?string $activityId): ToolLaunch
    {
        $this->activityId = $activityId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return ToolLaunch
     */
    public function setCreatedAt(DateTime $createdAt): ToolLaunch
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    /**
     * @param string|null $activityType
     *
     * @return ToolLaunch
     */
    public function setActivityType(?string $activityType): ToolLaunch
    {
        $this->activityType = $activityType;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowMultipleAttempts(): bool
    {
        return $this->allowMultipleAttempts;
    }

    /**
     * @param bool $allowMultipleAttempts
     *
     * @return ToolLaunch
     */
    public function setAllowMultipleAttempts(bool $allowMultipleAttempts): ToolLaunch
    {
        $this->allowMultipleAttempts = $allowMultipleAttempts;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLrsUrl(): ?string
    {
        return $this->lrsUrl;
    }

    /**
     * @param string|null $lrsUrl
     *
     * @return ToolLaunch
     */
    public function setLrsUrl(?string $lrsUrl): ToolLaunch
    {
        $this->lrsUrl = $lrsUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLrsAuth(): ?string
    {
        return $this->lrsAuth;
    }

    /**
     * @param string|null $lrsAuth
     *
     * @return ToolLaunch
     */
    public function setLrsAuth(?string $lrsAuth): ToolLaunch
    {
        $this->lrsAuth = $lrsAuth;

        return $this;
    }
}
