<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Entity\Listener\CourseListener;
use Chamilo\CoreBundle\Entity\Listener\ResourceListener;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CTool;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use function in_array;

#[ApiResource(
    types: ['https://schema.org/Course'],
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new Post(),
        new GetCollection(),
    ],
    normalizationContext: [
        'groups' => ['course:read'],
    ],
    denormalizationContext: [
        'groups' => ['course:write'],
    ],
    filters: [
        'course.sticky_boolean_filter',
    ],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Table(name: 'course')]
#[UniqueEntity('code')]
#[UniqueEntity('visualCode')]
#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ORM\EntityListeners([ResourceListener::class, CourseListener::class])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['title' => 'partial', 'code' => 'partial'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id', 'title'])]
class Course extends AbstractResource implements
    ResourceInterface,
    ResourceWithAccessUrlInterface,
    ResourceIllustrationInterface,
    ExtraFieldItemInterface,
    Stringable
{
    public const CLOSED = 0;
    public const REGISTERED = 1;
    // Only registered users in the course.
    public const OPEN_PLATFORM = 2;
    // All users registered in the platform (default).
    public const OPEN_WORLD = 3;
    public const HIDDEN = 4;

    #[Groups([
        'course:read',
        'course_rel_user:read',
        'session:read',
        'session_rel_course_rel_user:read',
        'session_rel_user:read',
        'session_rel_course:read',
        'track_e_exercise:read',
    ])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * The course title.
     */
    #[Groups([
        'course:read',
        'course:write',
        'course_rel_user:read',
        'session:read',
        'session_rel_course_rel_user:read',
        'session_rel_user:read',
        'session_rel_course:read',
    ])]
    #[Assert\NotBlank(message: 'A Course requires a title')]
    #[ORM\Column(name: 'title', type: 'string', length: 250, unique: false, nullable: true)]
    protected ?string $title = null;

    /**
     * The course code.
     */
    #[ApiProperty(iris: ['http://schema.org/courseCode'])]
    #[Groups(['course:read', 'user:write', 'course_rel_user:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 40, maxMessage: 'Code cannot be longer than {{ limit }} characters')]
    #[Gedmo\Slug(fields: ['title'], updatable: false, style: 'upper', unique: true, separator: '')]
    #[ORM\Column(name: 'code', type: 'string', length: 40, unique: true, nullable: false)]
    protected string $code;

    #[Assert\Length(max: 40, maxMessage: 'Code cannot be longer than {{ limit }} characters')]
    #[ORM\Column(name: 'visual_code', type: 'string', length: 40, unique: false, nullable: true)]
    protected ?string $visualCode = null;

    /**
     * @var Collection<int, CourseRelUser>
     */
    #[Groups([
        'course:read',
        'user:read',
        'course_rel_user:read',
    ])]
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: CourseRelUser::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $users;

    /**
     * @var Collection<int, EntityAccessUrlInterface>
     */
    #[ORM\OneToMany(
        mappedBy: 'course',
        targetEntity: AccessUrlRelCourse::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $urls;

    /**
     * @var Collection<int, SessionRelCourse>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: SessionRelCourse::class, cascade: ['persist', 'remove'])]
    protected Collection $sessions;

    /**
     * @var Collection<int, SessionRelCourseRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: SessionRelCourseRelUser::class, cascade: [
        'persist',
        'remove',
    ])]
    protected Collection $sessionRelCourseRelUsers;

    /**
     * @var Collection<int, CTool>
     */
    #[Groups(['course:read'])]
    #[ORM\OneToMany(
        mappedBy: 'course',
        targetEntity: CTool::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true
    )]
    protected Collection $tools;

    #[Groups(['course:read'])]
    #[ORM\OneToOne(
        mappedBy: 'course',
        targetEntity: TrackCourseRanking::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true
    )]
    protected ?TrackCourseRanking $trackCourseRanking = null;

    protected Session $currentSession;

    protected AccessUrl $currentUrl;

    /**
     * @var Collection<int, SkillRelCourse>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: SkillRelCourse::class, cascade: ['persist', 'remove'])]
    protected Collection $skills;

    /**
     * @var Collection<int, SkillRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: SkillRelUser::class, cascade: ['persist', 'remove'])]
    protected Collection $issuedSkills;

    /**
     * @var Collection<int, GradebookCategory>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: GradebookCategory::class, cascade: ['persist', 'remove'])]
    protected Collection $gradebookCategories;

    /**
     * @var Collection<int, GradebookEvaluation>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: GradebookEvaluation::class, cascade: ['persist', 'remove'])]
    protected Collection $gradebookEvaluations;

    /**
     * @var Collection<int, GradebookLink>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: GradebookLink::class, cascade: ['persist', 'remove'])]
    protected Collection $gradebookLinks;

    /**
     * @var Collection<int, TrackEHotspot>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: TrackEHotspot::class, cascade: ['persist', 'remove'])]
    protected Collection $trackEHotspots;

    /**
     * @var Collection<int, SearchEngineRef>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: SearchEngineRef::class, cascade: ['persist', 'remove'])]
    protected Collection $searchEngineRefs;

    /**
     * @var Collection<int, Templates>
     */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Templates::class, cascade: ['persist', 'remove'])]
    protected Collection $templates;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SpecificFieldValues", mappedBy="course").
     */
    //protected $specificFieldValues;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SharedSurvey", mappedBy="course").
     */
    //protected $sharedSurveys;

    #[ORM\Column(name: 'directory', type: 'string', length: 40, unique: false, nullable: true)]
    protected ?string $directory = null;

    #[Groups(['course:read', 'session:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'course_language', type: 'string', length: 20, unique: false, nullable: false)]
    protected string $courseLanguage;

    #[Groups(['course:read', 'course_rel_user:read'])]
    #[ORM\Column(name: 'description', type: 'text', unique: false, nullable: true)]
    protected ?string $description;

    #[Groups(['course:read', 'course_rel_user:read'])]
    #[ORM\Column(name: 'introduction', type: 'text', nullable: true)]
    protected ?string $introduction;

    /**
     * @var Collection<int, CourseCategory>
     */
    #[Groups(['course:read', 'course:write', 'course_rel_user:read', 'session:read'])]
    #[ORM\JoinTable(name: 'course_rel_category')]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'course_category_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: CourseCategory::class, inversedBy: 'courses')]
    protected Collection $categories;

    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'visibility', type: 'integer', unique: false, nullable: false)]
    protected int $visibility;

    #[ORM\Column(name: 'show_score', type: 'integer', unique: false, nullable: true)]
    protected ?int $showScore = null;

    #[ORM\Column(name: 'tutor_name', type: 'string', length: 200, unique: false, nullable: true)]
    protected ?string $tutorName;

    #[Groups(['course:read'])]
    #[ORM\Column(name: 'department_name', type: 'string', length: 30, unique: false, nullable: true)]
    protected ?string $departmentName = null;

    #[Assert\Url]
    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'department_url', type: 'string', length: 180, unique: false, nullable: true)]
    protected ?string $departmentUrl = null;

    #[Assert\Url]
    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'video_url', type: 'string', length: 255)]
    protected string $videoUrl;

    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'sticky', type: 'boolean')]
    protected bool $sticky;

    #[ORM\Column(name: 'disk_quota', type: 'bigint', unique: false, nullable: true)]
    protected ?int $diskQuota = null;

    #[ORM\Column(name: 'last_visit', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $lastVisit;

    #[ORM\Column(name: 'last_edit', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $lastEdit;

    #[ORM\Column(name: 'creation_date', type: 'datetime', unique: false, nullable: false)]
    protected DateTime $creationDate;

    #[Groups(['course:read'])]
    #[ORM\Column(name: 'expiration_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $expirationDate = null;

    #[Assert\NotNull]
    #[ORM\Column(name: 'subscribe', type: 'boolean', unique: false, nullable: false)]
    protected bool $subscribe;

    #[Assert\NotNull]
    #[ORM\Column(name: 'unsubscribe', type: 'boolean', unique: false, nullable: false)]
    protected bool $unsubscribe;

    #[ORM\Column(name: 'registration_code', type: 'string', length: 255, unique: false, nullable: true)]
    protected ?string $registrationCode;

    #[ORM\Column(name: 'legal', type: 'text', unique: false, nullable: true)]
    protected ?string $legal;

    #[ORM\Column(name: 'activate_legal', type: 'integer', unique: false, nullable: true)]
    protected ?int $activateLegal;

    #[ORM\Column(name: 'add_teachers_to_sessions_courses', type: 'boolean', nullable: true)]
    protected ?bool $addTeachersToSessionsCourses;

    #[ORM\Column(name: 'course_type_id', type: 'integer', unique: false, nullable: true)]
    protected ?int $courseTypeId;

    /**
     * ORM\OneToMany(targetEntity="CurriculumCategory", mappedBy="course").
     */
    //protected $curriculumCategories;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id')]
    protected ?Room $room;

    public function __construct()
    {
        $this->visibility = self::OPEN_PLATFORM;
        $this->sessions = new ArrayCollection();
        $this->sessionRelCourseRelUsers = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->issuedSkills = new ArrayCollection();
        $this->creationDate = new DateTime();
        $this->lastVisit = new DateTime();
        $this->lastEdit = new DateTime();
        $this->description = '';
        $this->introduction = '';
        $this->tutorName = '';
        $this->legal = '';
        $this->videoUrl = '';
        $this->registrationCode = null;
        $this->users = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->tools = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->gradebookCategories = new ArrayCollection();
        $this->gradebookEvaluations = new ArrayCollection();
        $this->gradebookLinks = new ArrayCollection();
        $this->trackEHotspots = new ArrayCollection();
        $this->searchEngineRefs = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->activateLegal = 0;
        $this->addTeachersToSessionsCourses = false;
        $this->courseTypeId = null;
        $this->room = null;
        $this->courseLanguage = 'en';
        $this->subscribe = true;
        $this->unsubscribe = false;
        $this->sticky = false;
        //$this->specificFieldValues = new ArrayCollection();
        //$this->sharedSurveys = new ArrayCollection();
    }

    public static function getStatusList(): array
    {
        return [
            self::CLOSED => 'Closed',
            self::REGISTERED => 'Registered',
            self::OPEN_PLATFORM => 'Open platform',
            self::OPEN_WORLD => 'Open world',
            self::HIDDEN => 'Hidden',
        ];
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        // Set the code based in the title if it doesnt exists.
        if (empty($this->code)) {
            $this->setCode($title);
        }

        return $this;
    }

    /**
     * @return Collection<int, CTool>
     */
    public function getTools(): Collection
    {
        return $this->tools;
    }

    public function setTools(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->addTool($tool);
        }

        return $this;
    }

    public function addTool(CTool $tool): self
    {
        $tool->setCourse($this);
        $this->tools->add($tool);

        return $this;
    }

    public function getTrackCourseRanking(): ?TrackCourseRanking
    {
        return $this->trackCourseRanking;
    }

    public function setTrackCourseRanking(?TrackCourseRanking $trackCourseRanking): self
    {
        $this->trackCourseRanking = $trackCourseRanking;

        return $this;
    }

    public function hasSubscriptionByUser(User $user): bool
    {
        if (0 === $this->users->count()) {
            return false;
        }
        $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));

        return $this->users->matching($criteria)->count() > 0;
    }

    /**
     * @return Collection<int, CourseRelUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addSubscription(CourseRelUser $courseRelUser): self
    {
        $courseRelUser->setCourse($this);
        if (!$this->hasUsers($courseRelUser)) {
            $this->users->add($courseRelUser);
        }

        return $this;
    }

    public function removeSubscription(CourseRelUser $user): void
    {
        foreach ($this->users as $key => $value) {
            if ($value->getId() === $user->getId()) {
                unset($this->users[$key]);
            }
        }
    }

    public function hasUsers(CourseRelUser $subscription): bool
    {
        if (0 !== $this->users->count()) {
            $criteria = Criteria::create()
                ->where(
                    Criteria::expr()->eq('user', $subscription->getUser())
                )
                ->andWhere(
                    Criteria::expr()->eq('status', $subscription->getStatus())
                )
                ->andWhere(
                    Criteria::expr()->eq('relationType', $subscription->getRelationType())
                );
            $relation = $this->users->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function addSubscriptionForUser(User $user, int $relationType, ?string $role, int $status): self
    {
        $courseRelUser = (new CourseRelUser())
            ->setCourse($this)
            ->setUser($user)
            ->setRelationType($relationType)
            ->setStatus($status);
        $this->addSubscription($courseRelUser);

        return $this;
    }

    public function hasUserAsStudent(User $user): bool
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));

        return $this->getStudentSubscriptions()->matching($criteria)->count() > 0;
    }

    public function getStudentSubscriptions(): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', CourseRelUser::STUDENT));

        return $this->users->matching($criteria);
    }

    public function addUserAsStudent(User $user): self
    {
        $this->addSubscriptionForUser($user, 0, '', CourseRelUser::STUDENT);

        return $this;
    }

    public function hasUserAsTeacher(User $user): bool
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));

        return $this->getTeachersSubscriptions()->matching($criteria)->count() > 0;
    }

    public function getTeachersSubscriptions(): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', CourseRelUser::TEACHER));

        return $this->users->matching($criteria);
    }

    public function addUserAsTeacher(User $user): self
    {
        $this->addSubscriptionForUser($user, 0, 'Trainer', CourseRelUser::TEACHER);

        return $this;
    }

    public function hasGroup(CGroup $group): void
    {
        /*$criteria = Criteria::create()->where(
              Criteria::expr()->eq('groups', $group)
          );*/
        //return $this->getGroups()->contains($group);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get directory, needed in migrations.
     */
    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    public function getCourseLanguage(): string
    {
        return $this->courseLanguage;
    }

    public function setCourseLanguage(string $courseLanguage): self
    {
        $this->courseLanguage = $courseLanguage;

        return $this;
    }

    public function getName(): string
    {
        return $this->getTitle();
    }

    public function getTitleAndCode(): string
    {
        return $this->getTitle().' ('.$this->getCode().')';
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        $this->visualCode = $code;

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

    /**
     * @return Collection<int, CourseCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function setCategories(Collection $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    public function addCategory(CourseCategory $category): self
    {
        $this->categories[] = $category;

        return $this;
    }

    public function removeCategory(CourseCategory $category): void
    {
        $this->categories->removeElement($category);
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getShowScore(): ?int
    {
        return $this->showScore;
    }

    public function setShowScore(int $showScore): self
    {
        $this->showScore = $showScore;

        return $this;
    }

    public function getTutorName(): ?string
    {
        return $this->tutorName;
    }

    public function setTutorName(?string $tutorName): self
    {
        $this->tutorName = $tutorName;

        return $this;
    }

    public function getVisualCode(): ?string
    {
        return $this->visualCode;
    }

    public function setVisualCode(string $visualCode): self
    {
        $this->visualCode = $visualCode;

        return $this;
    }

    public function getDepartmentName(): ?string
    {
        return $this->departmentName;
    }

    public function setDepartmentName(string $departmentName): self
    {
        $this->departmentName = $departmentName;

        return $this;
    }

    public function getDepartmentUrl(): ?string
    {
        return $this->departmentUrl;
    }

    public function setDepartmentUrl(string $departmentUrl): self
    {
        $this->departmentUrl = $departmentUrl;

        return $this;
    }

    public function getDiskQuota(): ?int
    {
        return $this->diskQuota;
    }

    public function setDiskQuota(int $diskQuota): self
    {
        $this->diskQuota = $diskQuota;

        return $this;
    }

    public function getLastVisit(): ?DateTime
    {
        return $this->lastVisit;
    }

    public function setLastVisit(DateTime $lastVisit): self
    {
        $this->lastVisit = $lastVisit;

        return $this;
    }

    public function getLastEdit(): ?DateTime
    {
        return $this->lastEdit;
    }

    public function setLastEdit(DateTime $lastEdit): self
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getExpirationDate(): ?DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getSubscribe(): bool
    {
        return $this->subscribe;
    }

    public function setSubscribe(bool $subscribe): self
    {
        $this->subscribe = $subscribe;

        return $this;
    }

    public function getUnsubscribe(): bool
    {
        return $this->unsubscribe;
    }

    public function setUnsubscribe(bool $unsubscribe): self
    {
        $this->unsubscribe = $unsubscribe;

        return $this;
    }

    public function getRegistrationCode(): ?string
    {
        return $this->registrationCode;
    }

    public function setRegistrationCode(string $registrationCode): self
    {
        $this->registrationCode = $registrationCode;

        return $this;
    }

    public function getLegal(): ?string
    {
        return $this->legal;
    }

    public function setLegal(string $legal): self
    {
        $this->legal = $legal;

        return $this;
    }

    public function getActivateLegal(): ?int
    {
        return $this->activateLegal;
    }

    public function setActivateLegal(int $activateLegal): self
    {
        $this->activateLegal = $activateLegal;

        return $this;
    }

    public function isAddTeachersToSessionsCourses(): ?bool
    {
        return $this->addTeachersToSessionsCourses;
    }

    public function setAddTeachersToSessionsCourses(bool $addTeachersToSessionsCourses): self
    {
        $this->addTeachersToSessionsCourses = $addTeachersToSessionsCourses;

        return $this;
    }

    public function getCourseTypeId(): ?int
    {
        return $this->courseTypeId;
    }

    public function setCourseTypeId(int $courseTypeId): self
    {
        $this->courseTypeId = $courseTypeId;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function isActive(): bool
    {
        $activeVisibilityList = [self::REGISTERED, self::OPEN_PLATFORM, self::OPEN_WORLD];

        return in_array($this->visibility, $activeVisibilityList, true);
    }

    /**
     * Anybody can see this course.
     */
    public function isPublic(): bool
    {
        return self::OPEN_WORLD === $this->visibility;
    }

    public function isHidden(): bool
    {
        return self::HIDDEN === $this->visibility;
    }

    public function getCurrentSession(): Session
    {
        return $this->currentSession;
    }

    public function setCurrentSession(Session $session): self
    {
        // If the session is registered in the course session list.
        /*if ($this->getSessions()->contains($session->getId())) {
              $this->currentSession = $session;
          }*/
        $list = $this->getSessions();
        /** @var SessionRelCourse $item */
        foreach ($list as $item) {
            if ($item->getSession()->getId() === $session->getId()) {
                $this->currentSession = $session;

                break;
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SessionRelCourse>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function getCurrentUrl(): AccessUrl
    {
        return $this->currentUrl;
    }

    public function setCurrentUrl(AccessUrl $url): self
    {
        $urlList = $this->getUrls();
        /** @var AccessUrlRelCourse $item */
        foreach ($urlList as $item) {
            if ($item->getUrl()->getId() === $url->getId()) {
                $this->currentUrl = $url;

                break;
            }
        }

        return $this;
    }

    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function addUrls(AccessUrlRelCourse $urlRelCourse): static
    {
        $urlRelCourse->setCourse($this);

        $this->urls->add($urlRelCourse);

        return $this;
    }

    public function addAccessUrl(?AccessUrl $url): self
    {
        $urlRelCourse = (new AccessUrlRelCourse())->setCourse($this)->setUrl($url);
        $this->addUrls($urlRelCourse);

        return $this;
    }

    public function addUrlRelCourse(AccessUrlRelCourse $accessUrlRelCourse): self
    {
        $accessUrlRelCourse->setCourse($this);
        $this->urls->add($accessUrlRelCourse);

        return $this;
    }

    /**
     * @return Collection<int, SkillRelUser>
     */
    public function getIssuedSkills(): Collection
    {
        return $this->issuedSkills;
    }

    /**
     * @return Collection<int, SessionRelCourseRelUser>
     */
    public function getSessionRelCourseRelUsers(): Collection
    {
        return $this->sessionRelCourseRelUsers;
    }

    public function setSessionRelCourseRelUsers(Collection $sessionUserSubscriptions): self
    {
        $this->sessionRelCourseRelUsers = $sessionUserSubscriptions;

        return $this;
    }

    /**
     * @return Collection<int, SkillRelCourse>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function setSkills(Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return Collection<int, GradebookCategory>
     */
    public function getGradebookCategories(): Collection
    {
        return $this->gradebookCategories;
    }

    public function setGradebookCategories(Collection $gradebookCategories): self
    {
        $this->gradebookCategories = $gradebookCategories;

        return $this;
    }

    /**
     * @return Collection<int, GradebookEvaluation>
     */
    public function getGradebookEvaluations(): Collection
    {
        return $this->gradebookEvaluations;
    }

    public function setGradebookEvaluations(Collection $gradebookEvaluations): self
    {
        $this->gradebookEvaluations = $gradebookEvaluations;

        return $this;
    }

    /**
     * @return Collection<int, GradebookLink>
     */
    public function getGradebookLinks(): Collection
    {
        return $this->gradebookLinks;
    }

    public function setGradebookLinks(Collection $gradebookLinks): self
    {
        $this->gradebookLinks = $gradebookLinks;

        return $this;
    }

    /**
     * @return Collection<int, TrackEHotspot>
     */
    public function getTrackEHotspots(): Collection
    {
        return $this->trackEHotspots;
    }

    public function setTrackEHotspots(Collection $trackEHotspots): self
    {
        $this->trackEHotspots = $trackEHotspots;

        return $this;
    }

    /**
     * @return Collection<int, SearchEngineRef>
     */
    public function getSearchEngineRefs(): Collection
    {
        return $this->searchEngineRefs;
    }

    public function setSearchEngineRefs(Collection $searchEngineRefs): self
    {
        $this->searchEngineRefs = $searchEngineRefs;

        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): self
    {
        $this->introduction = $introduction;

        return $this;
    }

    /**
     * @return Collection<int, Templates>
     */
    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function setTemplates(Collection $templates): self
    {
        $this->templates = $templates;

        return $this;
    }

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function isSticky(): bool
    {
        return $this->sticky;
    }

    public function setSticky(bool $sticky): self
    {
        $this->sticky = $sticky;

        return $this;
    }

    public function getDefaultIllustration(int $size): string
    {
        return '/img/session_default.svg';
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        return $this->getCode();
    }

    public function setResourceName(string $name): self
    {
        return $this->setCode($name);
    }
}
