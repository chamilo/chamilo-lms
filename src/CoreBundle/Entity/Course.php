<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
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
 * Class Course.
 *
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     iri="https://schema.org/Course",
 *     normalizationContext={"groups"={"course:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"course:write"}},
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"title": "partial", "code": "partial"})
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(OrderFilter::class, properties={"id", "title"})
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *     name="course",
 *     indexes={
 *         @ORM\Index(name="directory", columns={"directory"}),
 *     }
 * )
 * @UniqueEntity("code")
 * @UniqueEntity("visualCode")
 * @UniqueEntity("directory")
 * @ORM\Entity
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener", "Chamilo\CoreBundle\Entity\Listener\CourseListener"})
 */
class Course extends AbstractResource implements ResourceInterface, ResourceWithAccessUrlInterface, ResourceToRootInterface, ResourceIllustrationInterface
{
    public const CLOSED = 0;
    public const REGISTERED = 1;
    public const OPEN_PLATFORM = 2;
    public const OPEN_WORLD = 3;
    public const HIDDEN = 4;

    /**
     * @Groups({"course:read", "course_rel_user:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * The course title.
     *
     * @Assert\NotBlank(message="A Course requires a title")
     *
     * @Groups({"course:read", "course:write", "course_rel_user:read", "session_rel_course_rel_user:read"})
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true, unique=false)
     */
    protected ?string $title;

    /**
     * The course code.
     *
     * @Assert\NotBlank()
     * @ApiProperty(iri="http://schema.org/courseCode")
     * @Groups({"course:read", "course:write", "course_rel_user:read"})
     *
     * @Gedmo\Slug(
     *     fields={"title"},
     *     updatable=false,
     *     unique=true,
     *     style="upper"
     * )
     * @ORM\Column(name="code", type="string", length=40, nullable=false, unique=true)
     */
    protected string $code;

    /**
     * @var ArrayCollection|CourseRelUser[]
     *
     * @ApiSubresource()
     * Groups({"course:read"})
     * "orphanRemoval" is needed to delete the CourseRelUser relation
     * in the CourseAdmin class. The setUsers, getUsers, removeUsers and
     * addUsers methods need to be added.
     *
     * @ORM\OneToMany(targetEntity="CourseRelUser", mappedBy="course", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $users;

    /**
     * @var ArrayCollection|ResourceLink[]
     *
     * ApiSubresource()
     * Groups({"course:read"})
     * @ORM\OneToMany(targetEntity="ResourceLink", mappedBy="course", cascade={"persist"}, orphanRemoval=true)
     */
    protected $resourceLinks;

    /**
     * @var AccessUrlRelCourse[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelCourse",
     *     mappedBy="course", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $urls;

    /**
     * @var ArrayCollection|SessionRelCourse[]
     *
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $sessions;

    /**
     * @var ArrayCollection|SessionRelCourseRelUser[]
     *
     * @ORM\OneToMany(targetEntity="SessionRelCourseRelUser", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $sessionUserSubscriptions;

    /**
     * @var ArrayCollection|CTool[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CTool", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $tools;

    protected Session $currentSession;

    protected AccessUrl $currentUrl;

    /**
     * @var ArrayCollection|SkillRelCourse[]
     *
     * @ORM\OneToMany(targetEntity="SkillRelCourse", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $skills;

    /**
     * @var ArrayCollection|SkillRelUser[]
     *
     * @ORM\OneToMany(targetEntity="SkillRelUser", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $issuedSkills;

    /**
     * @var ArrayCollection|GradebookCategory[]
     *
     * @ORM\OneToMany(targetEntity="GradebookCategory", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $gradebookCategories;

    /**
     * @var ArrayCollection|GradebookEvaluation[]
     *
     * @ORM\OneToMany(targetEntity="GradebookEvaluation", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $gradebookEvaluations;

    /**
     * @var ArrayCollection|GradebookLink[]
     *
     * @ORM\OneToMany(targetEntity="GradebookLink", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $gradebookLinks;

    /**
     * @var ArrayCollection|TrackEHotspot[]
     *
     * @ORM\OneToMany(targetEntity="TrackEHotspot", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $trackEHotspots;

    /**
     * @var ArrayCollection|TrackEAttempt[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\TrackEAttempt", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $trackEAttempts;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SearchEngineRef", mappedBy="course", cascade={"persist", "remove"})
     *
     * @var \Chamilo\CoreBundle\Entity\SearchEngineRef[]|\Doctrine\Common\Collections\ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    protected $searchEngineRefs;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Templates", mappedBy="course", cascade={"persist", "remove"})
     *
     * @var \Chamilo\CoreBundle\Entity\Templates[]|\Doctrine\Common\Collections\ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    protected $templates;

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
    protected ?string $directory;

    /**
     * @Groups({"course:read", "list"})
     * @ORM\Column(name="course_language", type="string", length=20, nullable=false, unique=false)
     */
    protected string $courseLanguage;

    /**
     * @ORM\Column(name="description", type="text", nullable=true, unique=false)
     */
    protected ?string $description;

    /**
     * @ApiSubresource()
     * @Groups({"course:read", "course:write"})
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
    protected Collection $categories;

    /**
     * @var int Course visibility
     *
     * @Groups({"course:read", "course:write"})
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false, unique=false)
     */
    protected int $visibility;

    /**
     * @ORM\Column(name="show_score", type="integer", nullable=true, unique=false)
     */
    protected ?int $showScore;

    /**
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true, unique=false)
     */
    protected ?string $tutorName;

    /**
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true, unique=false)
     */
    protected ?string $visualCode;

    /**
     * @Groups({"course:read", "list"})
     * @ORM\Column(name="department_name", type="string", length=30, nullable=true, unique=false)
     */
    protected ?string $departmentName;

    /**
     * @Groups({"course:read", "list"})
     * @Assert\Url()
     *
     * @ORM\Column(name="department_url", type="string", length=180, nullable=true, unique=false)
     */
    protected ?string $departmentUrl;

    /**
     * @ORM\Column(name="disk_quota", type="bigint", nullable=true, unique=false)
     */
    protected ?int $diskQuota;

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
     * @Groups({"course:read", "list"})
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $expirationDate;

    /**
     * @ORM\Column(name="subscribe", type="boolean", nullable=false, unique=false)
     */
    protected bool $subscribe;

    /**
     * @ORM\Column(name="unsubscribe", type="boolean", nullable=false, unique=false)
     */
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
    protected Room $room;

    public function __construct()
    {
        $this->creationDate = new DateTime();
        $this->lastVisit = new DateTime();
        $this->lastEdit = new DateTime();
        $this->description = '';
        $this->tutorName = '';
        $this->users = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->tools = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->gradebookCategories = new ArrayCollection();
        $this->gradebookEvaluations = new ArrayCollection();
        $this->gradebookLinks = new ArrayCollection();
        $this->trackEHotspots = new ArrayCollection();
        $this->trackEAttempts = new ArrayCollection();
        $this->searchEngineRefs = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->activateLegal = 0;
        //$this->specificFieldValues = new ArrayCollection();
        //$this->sharedSurveys = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getSessions()
    {
        return $this->sessions;
    }

    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @param array $tools
     */
    public function setTools($tools)
    {
        foreach ($tools as $tool) {
            $this->addTool($tool);
        }

        return $this;
    }

    public function addTool(CTool $tool)
    {
        $tool->setCourse($this);
        $this->tools[] = $tool;

        return $this;
    }

    /**
     * @return AccessUrlRelCourse[]|ArrayCollection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    public function setUrls(ArrayCollection $urls)
    {
        $this->urls = new ArrayCollection();
        foreach ($urls as $url) {
            $this->addUrl($url);
        }

        return $this;
    }

    public function addUrlRelCourse(AccessUrlRelCourse $url)
    {
        $url->setCourse($this);
        $this->urls[] = $url;

        return $this;
    }

    public function addUrl(AccessUrl $url)
    {
        $urlRelCourse = new AccessUrlRelCourse();
        $urlRelCourse->setCourse($this);
        $urlRelCourse->setUrl($url);
        $this->addUrlRelCourse($urlRelCourse);

        return $this;
    }

    /**
     * @return ArrayCollection|CourseRelUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return ArrayCollection|CourseRelUser[]
     */
    public function getTeachers()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', User::COURSE_MANAGER));

        return $this->users->matching($criteria);
    }

    /**
     * @return ArrayCollection|CourseRelUser[]
     */
    public function getStudents()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', User::STUDENT));

        return $this->users->matching($criteria);
    }

    /**
     * @param ArrayCollection $users
     */
    public function setUsers($users)
    {
        $this->users = new ArrayCollection();

        foreach ($users as $user) {
            $this->addUsers($user);
        }

        return $this;
    }

    public function addUsers(CourseRelUser $courseRelUser)
    {
        $courseRelUser->setCourse($this);

        if (!$this->hasSubscription($courseRelUser)) {
            $this->users[] = $courseRelUser;
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

    public function addTeacher(User $user)
    {
        $this->addUser($user, 0, 'Trainer', User::COURSE_MANAGER);

        return $this;
    }

    public function addStudent(User $user)
    {
        $this->addUser($user, 0, '', User::STUDENT);

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

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Course
     */
    public function setCode($code)
    {
        $this->code = $code;
        $this->visualCode = $code;
        $this->directory = $code;

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

    /**
     * Set directory.
     *
     * @param string $directory
     *
     * @return Course
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get directory.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set courseLanguage.
     *
     * @param string $courseLanguage
     *
     * @return Course
     */
    public function setCourseLanguage($courseLanguage)
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

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Course
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getName()
    {
        return $this->getTitle();
    }

    /**
     * @return string
     */
    public function getTitleAndCode()
    {
        return $this->getTitle().' ('.$this->getCode().')';
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Course
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
     * Set category.
     *
     * @return Course
     */
    public function setCategories(ArrayCollection $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

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

    /**
     * Set showScore.
     *
     * @param int $showScore
     *
     * @return Course
     */
    public function setShowScore($showScore)
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

    /**
     * Set visualCode.
     *
     * @param string $visualCode
     *
     * @return Course
     */
    public function setVisualCode($visualCode)
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

    /**
     * Set departmentName.
     *
     * @param string $departmentName
     *
     * @return Course
     */
    public function setDepartmentName($departmentName)
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

    /**
     * Set departmentUrl.
     *
     * @param string $departmentUrl
     *
     * @return Course
     */
    public function setDepartmentUrl($departmentUrl)
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

    /**
     * Set diskQuota.
     *
     * @return Course
     */
    public function setDiskQuota(int $diskQuota)
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

    /**
     * Set lastVisit.
     *
     * @param DateTime $lastVisit
     *
     * @return Course
     */
    public function setLastVisit($lastVisit)
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

    /**
     * Set lastEdit.
     *
     * @param DateTime $lastEdit
     *
     * @return Course
     */
    public function setLastEdit($lastEdit)
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

    /**
     * Set creationDate.
     *
     * @param DateTime $creationDate
     *
     * @return Course
     */
    public function setCreationDate($creationDate)
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

    /**
     * Set expirationDate.
     *
     * @param DateTime $expirationDate
     *
     * @return Course
     */
    public function setExpirationDate($expirationDate)
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

    /**
     * Set subscribe.
     *
     * @param bool $subscribe
     *
     * @return Course
     */
    public function setSubscribe($subscribe)
    {
        $this->subscribe = (bool) $subscribe;

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

    /**
     * Set unsubscribe.
     *
     * @param bool $unsubscribe
     *
     * @return Course
     */
    public function setUnsubscribe($unsubscribe)
    {
        $this->unsubscribe = (bool) $unsubscribe;

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

    /**
     * Set registrationCode.
     *
     * @param string $registrationCode
     *
     * @return Course
     */
    public function setRegistrationCode($registrationCode)
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

    /**
     * Set legal.
     *
     * @param string $legal
     *
     * @return Course
     */
    public function setLegal($legal)
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

    /**
     * Set activateLegal.
     *
     * @param int $activateLegal
     *
     * @return Course
     */
    public function setActivateLegal($activateLegal)
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

    /**
     * @param bool $addTeachersToSessionsCourses
     */
    public function setAddTeachersToSessionsCourses($addTeachersToSessionsCourses): self
    {
        $this->addTeachersToSessionsCourses = $addTeachersToSessionsCourses;

        return $this;
    }

    /**
     * Set courseTypeId.
     *
     * @param int $courseTypeId
     *
     * @return Course
     */
    public function setCourseTypeId($courseTypeId)
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

    /**
     * @return Room
     */
    public function getRoom()
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
     * @return ArrayCollection
     */
    public function getIssuedSkills()
    {
        return $this->issuedSkills;
    }

    public function hasSubscription(CourseRelUser $subscription): bool
    {
        if ($this->getUsers()->count()) {
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

    public function addUser(User $user, int $relationType, $role, int $status): self
    {
        $courseRelUser = new CourseRelUser();
        $courseRelUser->setCourse($this);
        $courseRelUser->setUser($user);
        $courseRelUser->setRelationType($relationType);
        //$courseRelUser->setRole($role);
        $courseRelUser->setStatus($status);
        $this->addUsers($courseRelUser);

        return $this;
    }

    public function getDefaultIllustration(int $size): string
    {
        return '/img/icons/32/course.png';
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
