<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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
    public const DEFAULT_ACCESS_URL = 'http://localhost/';

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
    protected Collection $users;

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
        $this->users = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->sessionCategories = new ArrayCollection();
        $this->courseCategory = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getUrl();
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

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setCreatedBy(int $createdBy): self
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

    public function setTms(DateTime $tms): self
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

    public function setUrlType(bool $urlType): self
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

    public function setLimitActiveCourses(int $limitActiveCourses): self
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

    public function setLimitSessions(int $limitSessions): self
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

    public function setLimitUsers(int $limitUsers): self
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

    public function setLimitTeachers(int $limitTeachers): self
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

    public function setLimitDiskSpace(int $limitDiskSpace): self
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

    public function setEmail(string $email): self
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
    public function setSettings(Collection $settings): self
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

    public function setLimitCourses(int $limitCourses): self
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
    public function setCourses(Collection $courses): self
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
    public function setSessionCategories(Collection $sessionCategories): self
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
    public function getUsers()
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->hasUser($user)) {
            $accessUrlRelUser = (new AccessUrlRelUser())
                ->setUser($user)
                ->setUrl($this)
            ;
            $this->users->add($accessUrlRelUser);
        }

        return $this;
    }

    public function hasUser(User $user): bool
    {
        if (0 !== $this->users->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('user', $user)
            );
            $relation = $this->users->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
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
