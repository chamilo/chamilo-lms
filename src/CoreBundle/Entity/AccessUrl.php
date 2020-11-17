<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     normalizationContext={"groups"={"access_url:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"access_url:write","course_category:write"}},
 * )
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="access_url")
 * @ORM\Entity
 */
class AccessUrl extends AbstractResource implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"access_url:read", "access_url:write"})
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelCourse", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected $courses;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelSession", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected $sessions;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelUser", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="SettingsCurrent", mappedBy="url",cascade={"persist"}, orphanRemoval=true)
     */
    protected $settings;

    /**
     * @ORM\OneToMany(targetEntity="SessionCategory", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected $sessionCategories;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelCourseCategory", mappedBy="url", cascade={"persist"},orphanRemoval=true)
     */
    protected $courseCategory;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrl",
     *     inversedBy="children"
     * )
     * @ORM\JoinColumns({@ORM\JoinColumn(onDelete="CASCADE")})
     */
    protected $parent;

    /**
     * @var AccessUrl[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrl",
     *     mappedBy="parent"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $children;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    protected $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    protected $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    protected $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="AccessUrl")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $root;

    /**
     * @var string
     * @Assert\NotBlank()
     *
     * @Groups({"access_url:read", "access_url:write"})
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false, unique=false)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", unique=false)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="active", type="integer", nullable=false, unique=false)
     */
    protected $active;

    /**
     * @var int
     *
     * @ORM\Column(name="created_by", type="integer", nullable=false, unique=false)
     */
    protected $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", nullable=true)
     */
    protected $tms;

    /**
     * @var bool
     *
     * @ORM\Column(name="url_type", type="boolean", nullable=true)
     */
    protected $urlType;

    /**
     * @var int
     *
     * @ORM\Column(name="limit_courses", type="integer", nullable=true, unique=false)
     */
    protected $limitCourses;

    /**
     * @var int
     *
     * @ORM\Column(name="limit_active_courses", type="integer", nullable=true, unique=false)
     */
    protected $limitActiveCourses;

    /**
     * @var int
     *
     * @ORM\Column(name="limit_sessions", type="integer", nullable=true, unique=false)
     */
    protected $limitSessions;

    /**
     * @var int
     *
     * @ORM\Column(name="limit_users", type="integer", nullable=true, unique=false)
     */
    protected $limitUsers;

    /**
     * @var int
     *
     * @ORM\Column(name="limit_teachers", type="integer", nullable=true, unique=false)
     */
    protected $limitTeachers;

    /**
     * @var int
     *
     * @ORM\Column(name="limit_disk_space", type="integer", nullable=true, unique=false)
     */
    protected $limitDiskSpace;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true, unique=false)
     */
    protected $email;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tms = new \DateTime();
        $this->createdBy = 1;
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
     *
     * @return AccessUrl
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
     * Set active.
     *
     * @param int $active
     *
     * @return AccessUrl
     */
    public function setActive($active)
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
     * @param \DateTime $tms
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
     * @return \DateTime
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
     *
     * @return AccessUrl
     */
    public function setLimitSessions($limitSessions)
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
     *
     * @return AccessUrl
     */
    public function setLimitUsers($limitUsers)
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
     *
     * @return AccessUrl
     */
    public function setLimitTeachers($limitTeachers)
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
     *
     * @return AccessUrl
     */
    public function setLimitDiskSpace($limitDiskSpace)
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
     * @return AccessUrl
     */
    public function setSettings($settings)
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
     *
     * @return AccessUrl
     */
    public function setLimitCourses($limitCourses)
    {
        $this->limitCourses = $limitCourses;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCourses()
    {
        return $this->courses;
    }

    /**
     * @param mixed $courses
     *
     * @return AccessUrl
     */
    public function setCourses($courses)
    {
        $this->courses = $courses;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSessionCategories()
    {
        return $this->sessionCategories;
    }

    /**
     * @param mixed $sessionCategories
     *
     * @return AccessUrl
     */
    public function setSessionCategories($sessionCategories)
    {
        $this->sessionCategories = $sessionCategories;

        return $this;
    }

    /**
     * Resource identifier.
     */
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
