<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     normalizationContext={"groups"={"access_url:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"access_url:write", "course_category:write"}},
 * )
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="access_url")
 * @ORM\Entity
 */
class AccessUrl extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     *
     * @Groups({"access_url:read", "access_url:write"})
     */
    protected int $id;

    /**
     * @var AccessUrlRelCourse[]|Collection<int, AccessUrlRelCourse>
     *
     * @ORM\OneToMany(targetEntity="AccessUrlRelCourse", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $courses;

    /**
     * @var AccessUrlRelSession[]|Collection<int, AccessUrlRelSession>
     *
     * @ORM\OneToMany(targetEntity="AccessUrlRelSession", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $sessions;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelUser", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     *
     * @var AccessUrlRelUser[]|Collection<int, AccessUrlRelUser>
     */
    protected Collection $user;

    /**
     * @ORM\OneToMany(targetEntity="SettingsCurrent", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     *
     * @var Collection<int, SettingsCurrent>|SettingsCurrent[]
     */
    protected Collection $settings;

    /**
     * @ORM\OneToMany(targetEntity="SessionCategory", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     *
     * @var Collection<int, SessionCategory>|SessionCategory[]
     */
    protected Collection $sessionCategories;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelCourseCategory", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     *
     * @var AccessUrlRelCourseCategory[]|Collection<int, AccessUrlRelCourseCategory>
     */
    protected Collection $courseCategory;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrl",
     *     inversedBy="children"
     * )
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(onDelete="CASCADE")
     * })
     */
    protected ?AccessUrl $parent = null;

    /**
     * @var AccessUrl[]|Collection<int, AccessUrl>
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrl",
     *     mappedBy="parent"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected Collection $children;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    protected int $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    protected int $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    protected int $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="AccessUrl")
     * @ORM\JoinColumn(name="tree_root", onDelete="CASCADE")
     */
    protected ?AccessUrl $root = null;

    /**
     * @Assert\NotBlank()
     * @Groups({"access_url:read", "access_url:write"})
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    protected string $url;

    /**
     * @ORM\Column(name="description", type="text")
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="active", type="integer")
     */
    protected int $active;

    /**
     * @ORM\Column(name="created_by", type="integer")
     */
    protected int $createdBy;

    /**
     * @ORM\Column(name="tms", type="datetime", nullable=true)
     */
    protected ?DateTime $tms;

    /**
     * @ORM\Column(name="url_type", type="boolean", nullable=true)
     */
    protected ?bool $urlType = null;

    /**
     * @ORM\Column(name="limit_courses", type="integer", nullable=true)
     */
    protected ?int $limitCourses = null;

    /**
     * @ORM\Column(name="limit_active_courses", type="integer", nullable=true)
     */
    protected ?int $limitActiveCourses = null;

    /**
     * @ORM\Column(name="limit_sessions", type="integer", nullable=true)
     */
    protected ?int $limitSessions = null;

    /**
     * @ORM\Column(name="limit_users", type="integer", nullable=true)
     */
    protected ?int $limitUsers = null;

    /**
     * @ORM\Column(name="limit_teachers", type="integer", nullable=true)
     */
    protected ?int $limitTeachers = null;

    /**
     * @ORM\Column(name="limit_disk_space", type="integer", nullable=true)
     */
    protected ?int $limitDiskSpace = null;

    /**
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    protected ?string $email = null;

    public function __construct()
    {
        $this->tms = new DateTime();
        $this->createdBy = 1;
        $this->courses = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->sessionCategories = new ArrayCollection();
        $this->courseCategory = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getUrl();
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

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return AccessUrl
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description): self
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
     * Set active.
     *
     * @param int $active
     */
    public function setActive($active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set createdBy.
     *
     * @param int $createdBy
     *
     * @return AccessUrl
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set tms.
     *
     * @param DateTime $tms
     *
     * @return AccessUrl
     */
    public function setTms($tms)
    {
        $this->tms = $tms;

        return $this;
    }

    /**
     * Get tms.
     *
     * @return DateTime
     */
    public function getTms()
    {
        return $this->tms;
    }

    /**
     * Set urlType.
     *
     * @param bool $urlType
     *
     * @return AccessUrl
     */
    public function setUrlType($urlType)
    {
        $this->urlType = $urlType;

        return $this;
    }

    /**
     * Get urlType.
     *
     * @return bool
     */
    public function getUrlType()
    {
        return $this->urlType;
    }

    /**
     * @return int
     */
    public function getLimitActiveCourses()
    {
        return $this->limitActiveCourses;
    }

    /**
     * @param int $limitActiveCourses
     *
     * @return AccessUrl
     */
    public function setLimitActiveCourses($limitActiveCourses)
    {
        $this->limitActiveCourses = $limitActiveCourses;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimitSessions()
    {
        return $this->limitSessions;
    }

    /**
     * @param int $limitSessions
     */
    public function setLimitSessions($limitSessions): self
    {
        $this->limitSessions = $limitSessions;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimitUsers()
    {
        return $this->limitUsers;
    }

    /**
     * @param int $limitUsers
     */
    public function setLimitUsers($limitUsers): self
    {
        $this->limitUsers = $limitUsers;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimitTeachers()
    {
        return $this->limitTeachers;
    }

    /**
     * @param int $limitTeachers
     */
    public function setLimitTeachers($limitTeachers): self
    {
        $this->limitTeachers = $limitTeachers;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimitDiskSpace()
    {
        return $this->limitDiskSpace;
    }

    /**
     * @param int $limitDiskSpace
     */
    public function setLimitDiskSpace($limitDiskSpace): self
    {
        $this->limitDiskSpace = $limitDiskSpace;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return AccessUrl
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param Collection<int, SettingsCurrent>|SettingsCurrent[] $settings
     */
    public function setSettings($settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimitCourses()
    {
        return $this->limitCourses;
    }

    /**
     * @param int $limitCourses
     */
    public function setLimitCourses($limitCourses): self
    {
        $this->limitCourses = $limitCourses;

        return $this;
    }

    public function getCourses()
    {
        return $this->courses;
    }

    /**
     * @param AccessUrlRelCourse[]|Collection<int, AccessUrlRelCourse> $courses
     */
    public function setCourses($courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    public function getSessionCategories()
    {
        return $this->sessionCategories;
    }

    /**
     * @param Collection<int, SessionCategory>|SessionCategory[] $sessionCategories
     */
    public function setSessionCategories($sessionCategories): self
    {
        $this->sessionCategories = $sessionCategories;

        return $this;
    }

    /**
     * @return AccessUrlRelSession[]|Collection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @return AccessUrl[]|Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return AccessUrlRelUser[]|Collection
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @return AccessUrlRelCourseCategory[]|Collection
     */
    public function getCourseCategory()
    {
        return $this->courseCategory;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function getLvl(): int
    {
        return $this->lvl;
    }

    public function getRgt(): int
    {
        return $this->rgt;
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        $url = $this->getUrl();
        $url = parse_url($url);

        return $url['host'];
    }

    public function setResourceName(string $name): self
    {
        return $this->setUrl($name);
    }
}
