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
use ApiPlatform\Serializer\Filter\PropertyFilter;
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
#[ApiFilter(filterClass: PropertyFilter::class)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id', 'title'])]
class Course extends AbstractResource implements ResourceInterface, ResourceWithAccessUrlInterface, ResourceIllustrationInterface, ExtraFieldItemInterface, Stringable
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
    #[ORM\Column(name: 'title', type: 'string', length: 250, nullable: true, unique: false)]
    protected ?string $title = null;
    /**
     * The course code.
     */
    #[ApiProperty(iris: ['http://schema.org/courseCode'])]
    #[Groups(['course:read', 'user:write', 'course_rel_user:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 40, maxMessage: 'Code cannot be longer than {{ limit }} characters')]
    #[Gedmo\Slug(fields: ['title'], updatable: false, unique: true, separator: '', style: 'upper')]
    #[ORM\Column(name: 'code', type: 'string', length: 40, nullable: false, unique: true)]
    protected string $code;
    #[Assert\Length(max: 40, maxMessage: 'Code cannot be longer than {{ limit }} characters')]
    #[ORM\Column(name: 'visual_code', type: 'string', length: 40, nullable: true, unique: false)]
    protected ?string $visualCode = null;
    /**
     * @var Collection|CourseRelUser[]
     *
     * "orphanRemoval" is needed to delete the CourseRelUser relation
     * in the CourseAdmin class. The setUsers, getUsers, removeUsers and
     * addUsers methods need to be added.
     */
    #[Groups(['course:read', 'user:read'])]
    #[ORM\OneToMany(targetEntity: CourseRelUser::class, mappedBy: 'course', cascade: ['persist'], orphanRemoval: true)]
    protected Collection $users;
    /**
     * @var Collection|CourseRelUser[]
     *
     * "orphanRemoval" is needed to delete the CourseRelUser relation
     * in the CourseAdmin class. The setUsers, getUsers, removeUsers and
     * addUsers methods need to be added.
     */
    #[Groups(['course:read', 'user:read'])]
    #[ORM\OneToMany(targetEntity: CourseRelUser::class, mappedBy: 'course', cascade: ['persist'])]
    protected Collection $teachers;
    /**
     * @var AccessUrlRelCourse[]|Collection
     */
    #[ORM\OneToMany(targetEntity: AccessUrlRelCourse::class, mappedBy: 'course', cascade: [
        'persist',
        'remove',
    ], orphanRemoval: true)]
    protected Collection $urls;
    /**
     * @var Collection|SessionRelCourse[]
     */
    #[ORM\OneToMany(targetEntity: SessionRelCourse::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $sessions;
    /**
     * @var Collection|SessionRelCourseRelUser[]
     */
    #[ORM\OneToMany(targetEntity: SessionRelCourseRelUser::class, mappedBy: 'course', cascade: [
        'persist',
        'remove',
    ])]
    protected Collection $sessionRelCourseRelUsers;
    /**
     * @var Collection|CTool[]
     */
    #[Groups(['course:read'])]
    #[ORM\OneToMany(targetEntity: CTool::class, mappedBy: 'course', cascade: [
        'persist',
        'remove',
    ])]
    protected Collection $tools;
    /**
     * @var TrackCourseRanking
     */
    #[Groups(['course:read'])]
    #[ORM\OneToOne(targetEntity: TrackCourseRanking::class, mappedBy: 'course', cascade: [
        'persist',
        'remove',
    ], orphanRemoval: true)]
    protected TrackCourseRanking|null $trackCourseRanking = null;
    protected Session $currentSession;
    protected AccessUrl $currentUrl;
    /**
     * @var Collection|SkillRelCourse[]
     */
    #[ORM\OneToMany(targetEntity: SkillRelCourse::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $skills;
    /**
     * @var Collection|SkillRelUser[]
     */
    #[ORM\OneToMany(targetEntity: SkillRelUser::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $issuedSkills;
    /**
     * @var Collection|GradebookCategory[]
     */
    #[ORM\OneToMany(targetEntity: GradebookCategory::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $gradebookCategories;
    /**
     * @var Collection|GradebookEvaluation[]
     */
    #[ORM\OneToMany(targetEntity: GradebookEvaluation::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $gradebookEvaluations;
    /**
     * @var Collection|GradebookLink[]
     */
    #[ORM\OneToMany(targetEntity: GradebookLink::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $gradebookLinks;
    /**
     * @var Collection|TrackEHotspot[]
     */
    #[ORM\OneToMany(targetEntity: TrackEHotspot::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $trackEHotspots;
    /**
     * @var SearchEngineRef[]|Collection
     */
    #[ORM\OneToMany(targetEntity: SearchEngineRef::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $searchEngineRefs;
    /**
     * @var Templates[]|Collection
     */
    #[ORM\OneToMany(targetEntity: Templates::class, mappedBy: 'course', cascade: ['persist', 'remove'])]
    protected Collection $templates;
    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SpecificFieldValues", mappedBy="course").
     */
    //protected $specificFieldValues;
    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SharedSurvey", mappedBy="course").
     */
    //protected $sharedSurveys;
    #[ORM\Column(name: 'directory', type: 'string', length: 40, nullable: true, unique: false)]
    protected ?string $directory = null;
    #[Groups(['course:read', 'session:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'course_language', type: 'string', length: 20, nullable: false, unique: false)]
    protected string $courseLanguage;
    #[Groups(['course:read', 'course_rel_user:read'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true, unique: false)]
    protected ?string $description;
    #[Groups(['course:read', 'course_rel_user:read'])]
    #[ORM\Column(name: 'introduction', type: 'text', nullable: true)]
    protected ?string $introduction;
    /**
     * @var CourseCategory[]|Collection
     */
    #[Groups(['course:read', 'course:write', 'course_rel_user:read', 'session:read'])]
    #[ORM\JoinTable(name: 'course_rel_category')]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'course_category_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: CourseCategory::class, inversedBy: 'courses')]
    protected Collection $categories;
    /**
     * @var int Course visibility
     */
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'visibility', type: 'integer', nullable: false, unique: false)]
    protected int $visibility;
    #[ORM\Column(name: 'show_score', type: 'integer', nullable: true, unique: false)]
    protected ?int $showScore = null;
    #[ORM\Column(name: 'tutor_name', type: 'string', length: 200, nullable: true, unique: false)]
    protected ?string $tutorName;
    #[Groups(['course:read'])]
    #[ORM\Column(name: 'department_name', type: 'string', length: 30, nullable: true, unique: false)]
    protected ?string $departmentName = null;
    #[Assert\Url]
    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'department_url', type: 'string', length: 180, nullable: true, unique: false)]
    protected ?string $departmentUrl = null;
    #[Assert\Url]
    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'video_url', type: 'string', length: 255)]
    protected string $videoUrl;
    #[Groups(['course:read', 'course:write'])]
    #[ORM\Column(name: 'sticky', type: 'boolean')]
    protected bool $sticky;
    #[ORM\Column(name: 'disk_quota', type: 'bigint', nullable: true, unique: false)]
    protected ?int $diskQuota = null;
    #[ORM\Column(name: 'last_visit', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $lastVisit;
    #[ORM\Column(name: 'last_edit', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $lastEdit;
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false, unique: false)]
    protected DateTime $creationDate;
    #[Groups(['course:read'])]
    #[ORM\Column(name: 'expiration_date', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $expirationDate = null;
    #[Assert\NotNull]
    #[ORM\Column(name: 'subscribe', type: 'boolean', nullable: false, unique: false)]
    protected bool $subscribe;
    #[Assert\NotNull]
    #[ORM\Column(name: 'unsubscribe', type: 'boolean', nullable: false, unique: false)]
    protected bool $unsubscribe;
    #[ORM\Column(name: 'registration_code', type: 'string', length: 255, nullable: true, unique: false)]
    protected ?string $registrationCode;
    #[ORM\Column(name: 'legal', type: 'text', nullable: true, unique: false)]
    protected ?string $legal;
    #[ORM\Column(name: 'activate_legal', type: 'integer', nullable: true, unique: false)]
    protected ?int $activateLegal;
    #[ORM\Column(name: 'add_teachers_to_sessions_courses', type: 'boolean', nullable: true)]
    protected ?bool $addTeachersToSessionsCourses;
    #[ORM\Column(name: 'course_type_id', type: 'integer', nullable: true, unique: false)]
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

    public function __toString(): string
    {
        return $this->getTitle();
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
     * @return CTool[]|ArrayCollection|Collection
     */
    public function getTools(): array|ArrayCollection|Collection
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

    public function getTrackCourseRanking(): TrackCourseRanking|null
    {
        return $this->trackCourseRanking;
    }

    public function setTrackCourseRanking($trackCourseRanking): self
    {
        $this->trackCourseRanking = $trackCourseRanking;

        return $this;
    }

    public function hasUser(User $user): bool
    {
        if (0 === $this->getUsers()->count()) {
            return false;
        }
        $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));

        return $this->getUsers()->matching($criteria)->count() > 0;
    }

    /**
     * @return Collection|CourseRelUser[]
     */
    public function getUsers(): Collection|array
    {
        return $this->users;
    }

    public function setUsers(Collection $users): self
    {
        $this->users = new ArrayCollection();
        foreach ($users as $user) {
            $this->addUsers($user);
        }

        return $this;
    }

    public function addUsers(CourseRelUser $courseRelUser): self
    {
        $courseRelUser->setCourse($this);
        if (!$this->hasSubscription($courseRelUser)) {
            $this->users->add($courseRelUser);
        }

        return $this;
    }

    public function hasSubscription(CourseRelUser $subscription): bool
    {
        if (0 !== $this->getUsers()->count()) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $subscription->getUser()))->andWhere(
                Criteria::expr()->eq('status', $subscription->getStatus())
            )->andWhere(Criteria::expr()->eq('relationType', $subscription->getRelationType()));
            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function hasStudent(User $user): bool
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));

        return $this->getStudents()->matching($criteria)->count() > 0;
    }

    /**
     * @return Collection|CourseRelUser[]
     */
    public function getStudents(): Collection|array
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', CourseRelUser::STUDENT));

        return $this->users->matching($criteria);
    }

    public function hasTeacher(User $user): bool
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));

        return $this->getTeachers()->matching($criteria)->count() > 0;
    }

    /**
     * @return Collection|CourseRelUser[]
     */
    public function getTeachers(): Collection|array
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', CourseRelUser::TEACHER));

        return $this->users->matching($criteria);
    }

    public function hasGroup(CGroup $group): void
    {
        /*$criteria = Criteria::create()->where(
              Criteria::expr()->eq('groups', $group)
          );*/
        //return $this->getGroups()->contains($group);
    }

    /**
     * Remove $user.
     */
    public function removeUsers(CourseRelUser $user): void
    {
        foreach ($this->users as $key => $value) {
            if ($value->getId() === $user->getId()) {
                unset($this->users[$key]);
            }
        }
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

    public function addTeacher(User $user): self
    {
        $this->addUser($user, 0, 'Trainer', CourseRelUser::TEACHER);

        return $this;
    }

    public function addUser(User $user, int $relationType, ?string $role, int $status): self
    {
        $courseRelUser = (new CourseRelUser())->setCourse($this)->setUser($user)->setRelationType(
            $relationType
        )->setStatus($status);
        $this->addUsers($courseRelUser);

        return $this;
    }

    public function addStudent(User $user): self
    {
        $this->addUser($user, 0, '', CourseRelUser::STUDENT);

        return $this;
    }

    /**
     * Get directory, needed in migrations.
     *
     * @return string
     */
    public function getDirectory()
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
     * @return CourseCategory[]|Collection
     */
    public function getCategories(): array|Collection
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

    /**
     * Get showScore.
     *
     * @return int
     */
    public function getShowScore()
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

    /**
     * Get visualCode.
     *
     * @return string
     */
    public function getVisualCode()
    {
        return $this->visualCode;
    }

    public function setVisualCode(string $visualCode): self
    {
        $this->visualCode = $visualCode;

        return $this;
    }

    /**
     * Get departmentName.
     *
     * @return string
     */
    public function getDepartmentName()
    {
        return $this->departmentName;
    }

    public function setDepartmentName(string $departmentName): self
    {
        $this->departmentName = $departmentName;

        return $this;
    }

    /**
     * Get departmentUrl.
     *
     * @return string
     */
    public function getDepartmentUrl()
    {
        return $this->departmentUrl;
    }

    public function setDepartmentUrl(string $departmentUrl): self
    {
        $this->departmentUrl = $departmentUrl;

        return $this;
    }

    /**
     * Get diskQuota.
     *
     * @return int
     */
    public function getDiskQuota()
    {
        return $this->diskQuota;
    }

    public function setDiskQuota(int $diskQuota): self
    {
        $this->diskQuota = $diskQuota;

        return $this;
    }

    /**
     * Get lastVisit.
     *
     * @return DateTime
     */
    public function getLastVisit()
    {
        return $this->lastVisit;
    }

    public function setLastVisit(DateTime $lastVisit): self
    {
        $this->lastVisit = $lastVisit;

        return $this;
    }

    /**
     * Get lastEdit.
     *
     * @return DateTime
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }

    public function setLastEdit(DateTime $lastEdit): self
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get expirationDate.
     *
     * @return DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get subscribe.
     *
     * @return bool
     */
    public function getSubscribe()
    {
        return $this->subscribe;
    }

    public function setSubscribe(bool $subscribe): self
    {
        $this->subscribe = $subscribe;

        return $this;
    }

    /**
     * Get unsubscribe.
     *
     * @return bool
     */
    public function getUnsubscribe()
    {
        return $this->unsubscribe;
    }

    public function setUnsubscribe(bool $unsubscribe): self
    {
        $this->unsubscribe = $unsubscribe;

        return $this;
    }

    /**
     * Get registrationCode.
     *
     * @return string
     */
    public function getRegistrationCode()
    {
        return $this->registrationCode;
    }

    public function setRegistrationCode(string $registrationCode): self
    {
        $this->registrationCode = $registrationCode;

        return $this;
    }

    /**
     * Get legal.
     *
     * @return string
     */
    public function getLegal()
    {
        return $this->legal;
    }

    public function setLegal(string $legal): self
    {
        $this->legal = $legal;

        return $this;
    }

    /**
     * Get activateLegal.
     *
     * @return int
     */
    public function getActivateLegal()
    {
        return $this->activateLegal;
    }

    public function setActivateLegal(int $activateLegal): self
    {
        $this->activateLegal = $activateLegal;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAddTeachersToSessionsCourses()
    {
        return $this->addTeachersToSessionsCourses;
    }

    public function setAddTeachersToSessionsCourses(bool $addTeachersToSessionsCourses): self
    {
        $this->addTeachersToSessionsCourses = $addTeachersToSessionsCourses;

        return $this;
    }

    /**
     * Get courseTypeId.
     *
     * @return int
     */
    public function getCourseTypeId()
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

        return \in_array($this->visibility, $activeVisibilityList, true);
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

    /**
     * @return Session
     */
    public function getCurrentSession()
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
     * @return SessionRelCourse[]|ArrayCollection|Collection
     */
    public function getSessions(): array|ArrayCollection|Collection
    {
        return $this->sessions;
    }

    /**
     * @return AccessUrl
     */
    public function getCurrentUrl()
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

    public function setUrls(Collection $urls): self
    {
        $this->urls = new ArrayCollection();
        foreach ($urls as $url) {
            $this->addAccessUrl($url);
        }

        return $this;
    }

    public function addAccessUrl(?AccessUrl $url): self
    {
        $urlRelCourse = (new AccessUrlRelCourse())->setCourse($this)->setUrl($url);
        $this->addUrlRelCourse($urlRelCourse);

        return $this;
    }

    public function addUrlRelCourse(AccessUrlRelCourse $accessUrlRelCourse): self
    {
        $accessUrlRelCourse->setCourse($this);
        $this->urls->add($accessUrlRelCourse);

        return $this;
    }

    /**
     * Get issuedSkills.
     *
     * @return Collection
     */
    public function getIssuedSkills()
    {
        return $this->issuedSkills;
    }

    /**
     * @return SessionRelCourseRelUser[]|Collection
     */
    public function getSessionRelCourseRelUsers(): array|Collection
    {
        return $this->sessionRelCourseRelUsers;
    }

    public function setSessionRelCourseRelUsers(Collection $sessionUserSubscriptions): self
    {
        $this->sessionRelCourseRelUsers = $sessionUserSubscriptions;

        return $this;
    }

    /**
     * @return SkillRelCourse[]|Collection
     */
    public function getSkills(): array|Collection
    {
        return $this->skills;
    }

    /**
     * @param SkillRelCourse[]|Collection $skills
     */
    public function setSkills(array|Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return GradebookCategory[]|Collection
     */
    public function getGradebookCategories(): array|Collection
    {
        return $this->gradebookCategories;
    }

    /**
     * @param GradebookCategory[]|Collection $gradebookCategories
     */
    public function setGradebookCategories(array|Collection $gradebookCategories): self
    {
        $this->gradebookCategories = $gradebookCategories;

        return $this;
    }

    /**
     * @return GradebookEvaluation[]|Collection
     */
    public function getGradebookEvaluations(): array|Collection
    {
        return $this->gradebookEvaluations;
    }

    /**
     * @param GradebookEvaluation[]|Collection $gradebookEvaluations
     */
    public function setGradebookEvaluations(array|Collection $gradebookEvaluations): self
    {
        $this->gradebookEvaluations = $gradebookEvaluations;

        return $this;
    }

    /**
     * @return GradebookLink[]|Collection
     */
    public function getGradebookLinks(): array|Collection
    {
        return $this->gradebookLinks;
    }

    /**
     * @param GradebookLink[]|Collection $gradebookLinks
     */
    public function setGradebookLinks(array|Collection $gradebookLinks): self
    {
        $this->gradebookLinks = $gradebookLinks;

        return $this;
    }

    /**
     * @return TrackEHotspot[]|Collection
     */
    public function getTrackEHotspots(): array|Collection
    {
        return $this->trackEHotspots;
    }

    /**
     * @param TrackEHotspot[]|Collection $trackEHotspots
     */
    public function setTrackEHotspots(array|Collection $trackEHotspots): self
    {
        $this->trackEHotspots = $trackEHotspots;

        return $this;
    }

    /**
     * @return SearchEngineRef[]|Collection
     */
    public function getSearchEngineRefs(): array|Collection
    {
        return $this->searchEngineRefs;
    }

    /**
     * @param SearchEngineRef[]|Collection $searchEngineRefs
     */
    public function setSearchEngineRefs(array|Collection $searchEngineRefs): self
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
     * @return Templates[]|Collection
     */
    public function getTemplates(): array|Collection
    {
        return $this->templates;
    }

    /**
     * @param Templates[]|Collection $templates
     */
    public function setTemplates(array|Collection $templates): self
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
