<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ToolLaunch.
 *
 * @package Chamilo\PluginBundle\Entity\XApi
 *
 * @ORM\Table(name="xapi_tool_launch")
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\Entity\XApi\Repository\ToolLaunchRepository")
 */
class ToolLaunch
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="id")
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
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $course;
    /**
     * @var \Chamilo\CoreBundle\Entity\Session|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @ORM\Column(name="lrs_auth_username", type="string", nullable=true)
     */
    private $lrsAuthUsername;
    /**
     * @var string|null
     *
     * @ORM\Column(name="lrs_auth_password", type="string", nullable=true)
     */
    private $lrsAuthPassword;
    /*
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\XApi\Cmi5Item", mappedBy="tool", orphanRemoval=true,
     *                                                                          cascade="ALL")
     */
    private $items;

    /**
     * ToolLaunch constructor.
     */
    public function __construct()
    {
        $this->allowMultipleAttempts = true;
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ToolLaunch
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ToolLaunch
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): ToolLaunch
    {
        $this->description = $description;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): ToolLaunch
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): ToolLaunch
    {
        $this->session = $session;

        return $this;
    }

    public function getLaunchUrl(): string
    {
        return $this->launchUrl;
    }

    public function setLaunchUrl(string $launchUrl): ToolLaunch
    {
        $this->launchUrl = $launchUrl;

        return $this;
    }

    public function getActivityId(): ?string
    {
        return $this->activityId;
    }

    public function setActivityId(?string $activityId): ToolLaunch
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): ToolLaunch
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    public function setActivityType(?string $activityType): ToolLaunch
    {
        $this->activityType = $activityType;

        return $this;
    }

    public function isAllowMultipleAttempts(): bool
    {
        return $this->allowMultipleAttempts;
    }

    public function setAllowMultipleAttempts(bool $allowMultipleAttempts): ToolLaunch
    {
        $this->allowMultipleAttempts = $allowMultipleAttempts;

        return $this;
    }

    public function getLrsUrl(): ?string
    {
        return $this->lrsUrl;
    }

    public function setLrsUrl(?string $lrsUrl): ToolLaunch
    {
        $this->lrsUrl = $lrsUrl;

        return $this;
    }

    public function getLrsAuthUsername(): ?string
    {
        return $this->lrsAuthUsername;
    }

    public function setLrsAuthUsername(?string $lrsAuthUsername): ToolLaunch
    {
        $this->lrsAuthUsername = $lrsAuthUsername;

        return $this;
    }

    public function getLrsAuthPassword(): ?string
    {
        return $this->lrsAuthPassword;
    }

    public function setLrsAuthPassword(?string $lrsAuthPassword): ToolLaunch
    {
        $this->lrsAuthPassword = $lrsAuthPassword;

        return $this;
    }

    public function getItems(): ArrayCollection
    {
        return $this->items;
    }

    /**
     * @param \Chamilo\PluginBundle\Entity\XApi\Cmi5Item $cmi5Item
     *
     * @return $this
     */
    public function addItem(Cmi5Item $cmi5Item)
    {
        $cmi5Item->setTool($this);

        $this->items->add($cmi5Item);

        return $this;
    }
}
