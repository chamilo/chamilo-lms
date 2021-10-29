<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CTool;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="course",
 *     indexes={
 *     }
 * )
 * @UniqueEntity("code")
 * @UniqueEntity("visualCode")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\Node\CourseRepository")
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener", "Chamilo\CoreBundle\Entity\Listener\CourseListener"})
 */
#[ApiResource(
    iri: 'https://schema.org/Course',
    attributes: [
        'security' => "is_granted('ROLE_USER')",
        'filters' => [
            'course.sticky_boolean_filter',
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('VIEW', object)",
        ],
    ],
    normalizationContext: [
        'groups' => ['course:read'],
    ],
    denormalizationContext: [
        'groups' => ['course:write'],
    ],
)]

#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'code' => 'partial',
    //'sticky' => 'partial',
])]

//#[ApiFilter(BooleanFilter::class, properties: ['isSticky'])]
#[ApiFilter(PropertyFilter::class)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'title'])]

class Course extends AbstractResource implements ResourceInterface, ResourceWithAccessUrlInterface, ResourceIllustrationInterface, ExtraFieldItemInterface
{
    public const CLOSED = 0;
    public const REGISTERED = 1; // Only registered users in the course.
    public const OPEN_PLATFORM = 2; // All users registered in the platform (default).
    public const OPEN_WORLD = 3;
    public const HIDDEN = 4;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[Groups([
        'course:read',
        'course_rel_user:read',
        'session:read',
        'session_rel_course_rel_user:read',
        'session_rel_user:read',
        'session_rel_course:read',
    ])]
    protected ?int $id = null;

    /**
     * The course title.
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true, unique=false)
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
    protected ?string $title = null;

    /**
     * The course code.
     *
     * @Assert\Length(
     *     max = 40,
     *     maxMessage = "Code cannot be longer than {{ limit }} characters"
     * )
     * @ApiProperty(iri="http://schema.org/courseCode")
     *
     * @Gedmo\Slug(
     *     fields={"title"},
     *     updatable=false,
     *     unique=true,
     *     separator="",
     *     style="upper"
     * )
     * @ORM\Column(name="code", type="string", length=40, nullable=false, unique=true)
     */
    #[Groups(['course:read', 'user:write', 'course_rel_user:read'])]
    #[Assert\NotBlank]
    protected string $code;

    /**
     * @Assert\Length(
     *     max = 40,
     *     maxMessage = "Code cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true, unique=false)
     */
    protected ?string $visualCode = null;

    /**
     * @var Collection|CourseRelUser[]
     *
     * "orphanRemoval" is needed to delete the CourseRelUser relation
     * in the CourseAdmin class. The setUsers, getUsers, removeUsers and
     * addUsers methods need to be added.
     *
     * @ORM\OneToMany(targetEntity="CourseRelUser", mappedBy="course", cascade={"persist"}, orphanRemoval=true)
     */
    #[Groups(['course:read', 'user:read'])]
    #[ApiSubresource]
    protected Collection $users;

    /**
     * @var AccessUrlRelCourse[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelCourse",
     *     mappedBy="course", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected Collection $urls;

    /**
     * @var Collection|SessionRelCourse[]
     *
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $sessions;

    /**
     * @var Collection|SessionRelCourseRelUser[]
     *
     * @ORM\OneToMany(targetEntity="SessionRelCourseRelUser", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $sessionRelCourseRelUsers;

    /**
     * @var Collection|CTool[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CTool", mappedBy="course", cascade={"persist", "remove"})
     */
    #[Groups(['course:read'])]
    protected Collection $tools;

    protected Session $currentSession;

    protected AccessUrl $currentUrl;

    /**
     * @var Collection|SkillRelCourse[]
     *
     * @ORM\OneToMany(targetEntity="SkillRelCourse", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $skills;

    /**
     * @var Collection|SkillRelUser[]
     *
     * @ORM\OneToMany(targetEntity="SkillRelUser", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $issuedSkills;

    /**
     * @var Collection|GradebookCategory[]
     *
     * @ORM\OneToMany(targetEntity="GradebookCategory", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $gradebookCategories;

    /**
     * @var Collection|GradebookEvaluation[]
     *
     * @ORM\OneToMany(targetEntity="GradebookEvaluation", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $gradebookEvaluations;

    /**
     * @var Collection|GradebookLink[]
     *
     * @ORM\OneToMany(targetEntity="GradebookLink", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $gradebookLinks;

    /**
     * @var Collection|TrackEHotspot[]
     *
     * @ORM\OneToMany(targetEntity="TrackEHotspot", mappedBy="course", cascade={"persist", "remove"})
     */
    protected Collection $trackEHotspots;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SearchEngineRef", mappedBy="course", cascade={"persist", "remove"})
     *
     * @var SearchEngineRef[]|Collection
     */
    protected Collection $searchEngineRefs;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Templates", mappedBy="course", cascade={"persist", "remove"})
     *
     * @var Templates[]|Collection
     */
    protected Collection $templates;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SpecificFieldValues", mappedBy="course")
     */
    //protected $specificFieldValues;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SharedSurvey", mappedBy="course")
     */
    //protected $sharedSurveys;

    /**
     * @ORM\Column(name="directory", type="string", length=40, nullable=true, unique=false)
     */
    protected ?string $directory = null;

    /**
     * @ORM\Column(name="course_language", type="string", length=20, nullable=false, unique=false)
     */
    #[Groups(['course:read'])]
    #[Assert\NotBlank]
    protected string $courseLanguage;

    /**
     * @ORM\Column(name="description", type="text", nullable=true, unique=false)
     */
    #[Groups(['course:read', 'course_rel_user:read'])]
    protected ?string $description;

    /**
     * @ORM\Column(name="introduction", type="text", nullable=true)
     */
    #[Groups(['course:read', 'course_rel_user:read'])]
    protected ?string $introduction;

    /**
     * @var CourseCategory[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Chamilo\CoreBundle\Entity\CourseCategory", inversedBy="courses")
     * @ORM\JoinTable(
     *     name="course_rel_category",
     *     joinColumns={
     *         @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="course_category_id", referencedColumnName="id")}
     * )
     */
    #[ApiSubresource]
    #[Groups(['course:read', 'course:write', 'course_rel_user:read'])]
    protected Collection $categories;

    /**
     * @var int Course visibility
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false, unique=false)
     */
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write'])]
    protected int $visibility;

    /**
     * @ORM\Column(name="show_score", type="integer", nullable=true, unique=false)
     */
    protected ?int $showScore = null;

    /**
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true, unique=false)
     */
    protected ?string $tutorName;

    /**
     * @ORM\Column(name="department_name", type="string", length=30, nullable=true, unique=false)
     */
    #[Groups(['course:read'])]
    protected ?string $departmentName = null;

    /**
     * @ORM\Column(name="department_url", type="string", length=180, nullable=true, unique=false)
     */
    #[Assert\Url]
    #[Groups(['course:read', 'course:write'])]
    protected ?string $departmentUrl = null;

    /**
     * @ORM\Column(name="video_url", type="string", length=255)
     */
    #[Assert\Url]
    #[Groups(['course:read', 'course:write'])]
    protected string $videoUrl;

    /**
     * @ORM\Column(name="sticky", type="boolean")
     */
    #[Groups(['course:read', 'course:write'])]
    protected bool $sticky;

    /**
     * @ORM\Column(name="disk_quota", type="bigint", nullable=true, unique=false)
     */
    protected ?int $diskQuota = null;

    /**
     * @ORM\Column(name="last_visit", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $lastVisit;

    /**
     * @ORM\Column(name="last_edit", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $lastEdit;

    /**
     * @ORM\Column(name="creation_date", type="datetime", nullable=false, unique=false)
     */
    protected DateTime $creationDate;

    /**
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true, unique=false)
     */
    #[Groups(['course:read'])]
    protected ?DateTime $expirationDate = null;

    /**
     * @ORM\Column(name="subscribe", type="boolean", nullable=false, unique=false)
     */
    #[Assert\NotNull]
    protected bool $subscribe;

    /**
     * @ORM\Column(name="unsubscribe", type="boolean", nullable=false, unique=false)
     */
    #[Assert\NotNull]
    protected bool $unsubscribe;

    /**
     * @ORM\Column(name="registration_code", type="string", length=255, nullable=true, unique=false)
     */
    protected ?string $registrationCode;

    /**
     * @ORM\Column(name="legal", type="text", nullable=true, unique=false)
     */
    protected ?string $legal;

    /**
     * @ORM\Column(name="activate_legal", type="integer", nullable=true, unique=false)
     */
    protected ?int $activateLegal;

    /**
     * @ORM\Column(name="add_teachers_to_sessions_courses", type="boolean", nullable=true)
     */
    protected ?bool $addTeachersToSessionsCourses;

    /**
     * @ORM\Column(name="course_type_id", type="integer", nullable=true, unique=false)
     */
    protected ?int $courseTypeId;

    /**
     * ORM\OneToMany(targetEntity="CurriculumCategory", mappedBy="course").
     */
    //protected $curriculumCategories;

    /**
     * @ORM\ManyToOne(targetEntity="Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
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

    /**
     * @return SessionRelCourse[]|ArrayCollection|Collection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @return CTool[]|ArrayCollection|Collection
     */
    public function getTools()
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

    /**
     * @return AccessUrlRelCourse[]|Collection
     */
    public function getUrls()
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

    public function addUrlRelCourse(AccessUrlRelCourse $accessUrlRelCourse): self
    {
        $accessUrlRelCourse->setCourse($this);
        $this->urls->add($accessUrlRelCourse);

        return $this;
    }

    public function addAccessUrl(AccessUrl $url): self
    {
        $urlRelCourse = (new AccessUrlRelCourse())
            ->setCourse($this)
            ->setUrl($url)
        ;
        $this->addUrlRelCourse($urlRelCourse);

        return $this;
    }

    /**
     * @return Collection|CourseRelUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return Collection|CourseRelUser[]
     */
    public function getTeachers()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', CourseRelUser::TEACHER));

        return $this->users->matching($criteria);
    }

    /**
     * @return Collection|CourseRelUser[]
     */
    public function getStudents()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', CourseRelUser::STUDENT));

        return $this->users->matching($criteria);
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

    public function hasUser(User $user): bool
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->getUsers()->matching($criteria)->count() > 0;
    }

    public function hasStudent(User $user): bool
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->getStudents()->matching($criteria)->count() > 0;
    }

    public function hasTeacher(User $user): bool
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->getTeachers()->matching($criteria)->count() > 0;
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

    public function addTeacher(User $user): self
    {
        $this->addUser($user, 0, 'Trainer', CourseRelUser::TEACHER);

        return $this;
    }

    public function addStudent(User $user): self
    {
        $this->addUser($user, 0, '', CourseRelUser::STUDENT);

        return $this;
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

    public function setCode(string $code): self
    {
        $this->code = $code;
        $this->visualCode = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
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

    public function setCourseLanguage(string $courseLanguage): self
    {
        $this->courseLanguage = $courseLanguage;

        return $this;
    }

    public function getCourseLanguage(): string
    {
        return $this->courseLanguage;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getName(): string
    {
        return $this->getTitle();
    }

    public function getTitleAndCode(): string
    {
        return $this->getTitle().' ('.$this->getCode().')';
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

    public function setCategories(Collection $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return CourseCategory[]|Collection
     */
    public function getCategories()
    {
        return $this->categories;
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

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setShowScore(int $showScore): self
    {
        $this->showScore = $showScore;

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

    public function setTutorName(?string $tutorName): self
    {
        $this->tutorName = $tutorName;

        return $this;
    }

    public function getTutorName(): ?string
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

    public function setDepartmentName(string $departmentName): self
    {
        $this->departmentName = $departmentName;

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

    public function setDepartmentUrl(string $departmentUrl): self
    {
        $this->departmentUrl = $departmentUrl;

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

    public function setDiskQuota(int $diskQuota): self
    {
        $this->diskQuota = $diskQuota;

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

    public function setLastVisit(DateTime $lastVisit): self
    {
        $this->lastVisit = $lastVisit;

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

    public function setLastEdit(DateTime $lastEdit): self
    {
        $this->lastEdit = $lastEdit;

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

    public function setCreationDate(DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

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

    public function setExpirationDate(DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

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

    public function setSubscribe(bool $subscribe): self
    {
        $this->subscribe = $subscribe;

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

    public function setUnsubscribe(bool $unsubscribe): self
    {
        $this->unsubscribe = $unsubscribe;

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

    public function setRegistrationCode(string $registrationCode): self
    {
        $this->registrationCode = $registrationCode;

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

    public function setLegal(string $legal): self
    {
        $this->legal = $legal;

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

    public function setActivateLegal(int $activateLegal): self
    {
        $this->activateLegal = $activateLegal;

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

    public function setCourseTypeId(int $courseTypeId): self
    {
        $this->courseTypeId = $courseTypeId;

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
        $activeVisibilityList = [
            self::REGISTERED,
            self::OPEN_PLATFORM,
            self::OPEN_WORLD,
        ];

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

    /**
     * @return AccessUrl
     */
    public function getCurrentUrl()
    {
        return $this->currentUrl;
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

    public function hasSubscription(CourseRelUser $subscription): bool
    {
        if (0 !== $this->getUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('user', $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq('status', $subscription->getStatus())
            )->andWhere(
                Criteria::expr()->eq('relationType', $subscription->getRelationType())
            );

            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function addUser(User $user, int $relationType, ?string $role, int $status): self
    {
        $courseRelUser =
            (new CourseRelUser())
                ->setCourse($this)
                ->setUser($user)
                ->setRelationType($relationType)
                ->setStatus($status)
        ;
        $this->addUsers($courseRelUser);

        return $this;
    }

    /**
     * @return SessionRelCourseRelUser[]|Collection
     */
    public function getSessionRelCourseRelUsers()
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
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * @param SkillRelCourse[]|Collection $skills
     */
    public function setSkills(Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return GradebookCategory[]|Collection
     */
    public function getGradebookCategories()
    {
        return $this->gradebookCategories;
    }

    /**
     * @param GradebookCategory[]|Collection $gradebookCategories
     */
    public function setGradebookCategories(Collection $gradebookCategories): self
    {
        $this->gradebookCategories = $gradebookCategories;

        return $this;
    }

    /**
     * @return GradebookEvaluation[]|Collection
     */
    public function getGradebookEvaluations()
    {
        return $this->gradebookEvaluations;
    }

    /**
     * @param GradebookEvaluation[]|Collection $gradebookEvaluations
     */
    public function setGradebookEvaluations(Collection $gradebookEvaluations): self
    {
        $this->gradebookEvaluations = $gradebookEvaluations;

        return $this;
    }

    /**
     * @return GradebookLink[]|Collection
     */
    public function getGradebookLinks()
    {
        return $this->gradebookLinks;
    }

    /**
     * @param GradebookLink[]|Collection $gradebookLinks
     */
    public function setGradebookLinks(Collection $gradebookLinks): self
    {
        $this->gradebookLinks = $gradebookLinks;

        return $this;
    }

    /**
     * @return TrackEHotspot[]|Collection
     */
    public function getTrackEHotspots()
    {
        return $this->trackEHotspots;
    }

    /**
     * @param TrackEHotspot[]|Collection $trackEHotspots
     */
    public function setTrackEHotspots(Collection $trackEHotspots): self
    {
        $this->trackEHotspots = $trackEHotspots;

        return $this;
    }

    /**
     * @return SearchEngineRef[]|Collection
     */
    public function getSearchEngineRefs()
    {
        return $this->searchEngineRefs;
    }

    /**
     * @param SearchEngineRef[]|Collection $searchEngineRefs
     */
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
     * @return Templates[]|Collection
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param Templates[]|Collection $templates
     */
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
