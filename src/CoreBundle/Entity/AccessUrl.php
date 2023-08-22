<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: [
        'groups' => ['access_url:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups' => ['access_url:write', 'course_category:write'],
    ],
    security: "is_granted('ROLE_ADMIN')"
)]
#[ORM\Table(name: 'access_url')]
#[Gedmo\Tree(type: 'nested')]
#[ORM\Entity(repositoryClass: AccessUrlRepository::class)]
class AccessUrl extends AbstractResource implements ResourceInterface, Stringable
{
    public const DEFAULT_ACCESS_URL = 'http://localhost/';
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['access_url:read', 'access_url:write'])]
    protected ?int $id = null;

    /**
     * @var Collection<int, AccessUrlRelCourse>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: AccessUrlRelCourse::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $courses;

    /**
     * @var Collection<int, AccessUrlRelSession>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: AccessUrlRelSession::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $sessions;

    /**
     * @var Collection<int, AccessUrlRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: AccessUrlRelUser::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $users;

    /**
     * @var Collection<int, SettingsCurrent>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: SettingsCurrent::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $settings;

    /**
     * @var Collection<int, SessionCategory>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: SessionCategory::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $sessionCategories;

    /**
     * @var Collection<int, AccessUrlRelCourseCategory>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: AccessUrlRelCourseCategory::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $courseCategory;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    protected ?AccessUrl $parent = null;

    /**
     * @var Collection<int, AccessUrl>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    protected Collection $children;

    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: 'integer')]
    protected int $lft;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: 'integer')]
    protected int $lvl;

    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: 'integer')]
    protected int $rgt;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'tree_root', onDelete: 'CASCADE')]
    protected ?AccessUrl $root = null;

    #[Assert\NotBlank]
    #[Groups(['access_url:read', 'access_url:write'])]
    #[ORM\Column(name: 'url', type: 'string', length: 255)]
    protected string $url;

    #[ORM\Column(name: 'description', type: 'text')]
    protected ?string $description = null;

    #[ORM\Column(name: 'active', type: 'integer')]
    protected int $active;

    #[ORM\Column(name: 'created_by', type: 'integer')]
    protected int $createdBy;

    #[ORM\Column(name: 'tms', type: 'datetime', nullable: true)]
    protected ?DateTime $tms;

    #[ORM\Column(name: 'url_type', type: 'boolean', nullable: true)]
    protected ?bool $urlType = null;

    #[ORM\Column(name: 'limit_courses', type: 'integer', nullable: true)]
    protected ?int $limitCourses = null;

    #[ORM\Column(name: 'limit_active_courses', type: 'integer', nullable: true)]
    protected ?int $limitActiveCourses = null;

    #[ORM\Column(name: 'limit_sessions', type: 'integer', nullable: true)]
    protected ?int $limitSessions = null;

    #[ORM\Column(name: 'limit_users', type: 'integer', nullable: true)]
    protected ?int $limitUsers = null;

    #[ORM\Column(name: 'limit_teachers', type: 'integer', nullable: true)]
    protected ?int $limitTeachers = null;

    #[ORM\Column(name: 'limit_disk_space', type: 'integer', nullable: true)]
    protected ?int $limitDiskSpace = null;

    #[Assert\Email]
    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    protected ?string $email = null;

    public function __construct()
    {
        $this->description = '';
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getTms(): ?DateTime
    {
        return $this->tms;
    }

    public function setTms(DateTime $tms): self
    {
        $this->tms = $tms;

        return $this;
    }

    public function getUrlType(): ?bool
    {
        return $this->urlType;
    }

    public function setUrlType(bool $urlType): self
    {
        $this->urlType = $urlType;

        return $this;
    }

    public function getLimitActiveCourses(): ?int
    {
        return $this->limitActiveCourses;
    }

    public function setLimitActiveCourses(int $limitActiveCourses): self
    {
        $this->limitActiveCourses = $limitActiveCourses;

        return $this;
    }

    public function getLimitSessions(): ?int
    {
        return $this->limitSessions;
    }

    public function setLimitSessions(int $limitSessions): self
    {
        $this->limitSessions = $limitSessions;

        return $this;
    }

    public function getLimitUsers(): ?int
    {
        return $this->limitUsers;
    }

    public function setLimitUsers(int $limitUsers): self
    {
        $this->limitUsers = $limitUsers;

        return $this;
    }

    public function getLimitTeachers(): ?int
    {
        return $this->limitTeachers;
    }

    public function setLimitTeachers(int $limitTeachers): self
    {
        $this->limitTeachers = $limitTeachers;

        return $this;
    }

    public function getLimitDiskSpace(): ?int
    {
        return $this->limitDiskSpace;
    }

    public function setLimitDiskSpace(int $limitDiskSpace): self
    {
        $this->limitDiskSpace = $limitDiskSpace;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, SettingsCurrent>
     */
    public function getSettings(): Collection
    {
        return $this->settings;
    }

    /**
     * @param Collection<int, SettingsCurrent> $settings
     */
    public function setSettings(Collection $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    public function getLimitCourses(): ?int
    {
        return $this->limitCourses;
    }

    public function setLimitCourses(int $limitCourses): self
    {
        $this->limitCourses = $limitCourses;

        return $this;
    }

    /**
     * @return Collection<int, AccessUrlRelCourse>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function setCourses(Collection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    public function addCourse(Course $course): self
    {
        if (!$this->hasCourse($course)) {
            $urlRelCourse = (new AccessUrlRelCourse())->setCourse($course)->setUrl($this);

            $this->courses->add($urlRelCourse);
        }

        return $this;
    }

    public function hasCourse(Course $course): bool
    {
        if ($this->courses->count() > 0) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('course', $course));
            $found = $this->courses->matching($criteria);

            return $found->count() > 0;
        }

        return false;
    }

    /**
     * @return Collection<int, SessionCategory>
     */
    public function getSessionCategories(): Collection
    {
        return $this->sessionCategories;
    }

    public function setSessionCategories(Collection $sessionCategories): self
    {
        $this->sessionCategories = $sessionCategories;

        return $this;
    }

    /**
     * @return Collection<int, AccessUrlRelSession>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    /**
     * @return Collection<int, AccessUrl>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return Collection<int, AccessUrlRelUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->hasUser($user)) {
            $accessUrlRelUser = (new AccessUrlRelUser())->setUser($user)->setUrl($this);
            $this->users->add($accessUrlRelUser);
        }

        return $this;
    }

    public function hasUser(User $user): bool
    {
        if (0 !== $this->users->count()) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));
            $relation = $this->users->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * @return Collection<int, AccessUrlRelCourseCategory>
     */
    public function getCourseCategory(): Collection
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

    public function getId(): ?int
    {
        return $this->id;
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
