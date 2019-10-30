<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Course.
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *  name="course",
 *  indexes={
 *      @ORM\Index(name="category_code", columns={"category_code"}),
 *      @ORM\Index(name="directory", columns={"directory"}),
 *  }
 * )
 * @UniqueEntity("code")
 * @UniqueEntity("visualCode")
 * @UniqueEntity("directory")
 *
 * @ORM\Entity
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\CourseListener"})
 */
class Course extends AbstractResource implements ResourceInterface
{
    public const CLOSED = 0;
    public const REGISTERED = 1;
    public const OPEN_PLATFORM = 2;
    public const OPEN_WORLD = 3;
    public const HIDDEN = 4;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true, unique=false)
     */
    protected $title;

    /**
     * "orphanRemoval" is needed to delete the CourseRelUser relation
     * in the CourseAdmin class. The setUsers, getUsers, removeUsers and
     * addUsers methods need to be added.
     *
     * @ORM\OneToMany(targetEntity="CourseRelUser", mappedBy="course", cascade={"persist"}, orphanRemoval=true)
     */
    protected $users;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelCourse", mappedBy="course", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $urls;

    /**
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $sessions;

    /**
     * @ORM\OneToMany(targetEntity="SessionRelCourseRelUser", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $sessionUserSubscriptions;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $groups;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CItemProperty", mappedBy="course")
     */
    //protected $items;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CTool", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $tools;

    /**
     * @var Session
     */
    protected $currentSession;

    /**
     * @var AccessUrl
     */
    protected $currentUrl;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\SkillBundle\Entity\SkillRelCourse", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $skills;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $issuedSkills;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $gradebookCategories;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $gradebookEvaluations;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookLink", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $gradebookLinks;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\TrackEHotspot", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $trackEHotspots;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\TrackEAttempt", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $trackEAttempts;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SearchEngineRef", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $searchEngineRefs;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Templates", mappedBy="course", cascade={"persist", "remove"})
     */
    protected $templates;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceLink", mappedBy="course", cascade={"remove"}, orphanRemoval=true)
     */
    protected $resourceLinks;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SpecificFieldValues", mappedBy="course")
     */
    //protected $specificFieldValues;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SharedSurvey", mappedBy="course")
     */
    //protected $sharedSurveys;

    /**
     * @var string
     *
     * @Gedmo\Slug(
     *      fields={"title"},
     *      updatable = false,
     *      unique = true,
     *      style = "upper"
     * )
     * @ORM\Column(name="code", type="string", length=40, nullable=false, unique=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="directory", type="string", length=40, nullable=true, unique=false)
     */
    protected $directory;

    /**
     * @var string
     *
     * @ORM\Column(name="course_language", type="string", length=20, nullable=true, unique=false)
     */
    protected $courseLanguage;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true, unique=false)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="category_code", type="string", length=40, nullable=true, unique=false)
     */
    protected $categoryCode;

    /**
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="visibility", type="integer", nullable=true, unique=false)
     */
    protected $visibility;

    /**
     * @var int
     *
     * @ORM\Column(name="show_score", type="integer", nullable=true, unique=false)
     */
    protected $showScore;

    /**
     * @var string
     *
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true, unique=false)
     */
    protected $tutorName;

    /**
     * @var string
     *
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true, unique=false)
     */
    protected $visualCode;

    /**
     * @var string
     *
     * @ORM\Column(name="department_name", type="string", length=30, nullable=true, unique=false)
     */
    protected $departmentName;

    /**
     * @var string
     *
     * @Assert\Url()
     *
     * @ORM\Column(name="department_url", type="string", length=180, nullable=true, unique=false)
     */
    protected $departmentUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="disk_quota", type="bigint", nullable=true, unique=false)
     */
    protected $diskQuota;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_visit", type="datetime", nullable=true, unique=false)
     */
    protected $lastVisit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_edit", type="datetime", nullable=true, unique=false)
     */
    protected $lastEdit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=true, unique=false)
     */
    protected $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true, unique=false)
     */
    protected $expirationDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="subscribe", type="boolean", nullable=true, unique=false)
     */
    protected $subscribe;

    /**
     * @var bool
     *
     * @ORM\Column(name="unsubscribe", type="boolean", nullable=true, unique=false)
     */
    protected $unsubscribe;

    /**
     * @var string
     *
     * @ORM\Column(name="registration_code", type="string", length=255, nullable=true, unique=false)
     */
    protected $registrationCode;

    /**
     * @var string
     *
     * @ORM\Column(name="legal", type="text", nullable=true, unique=false)
     */
    protected $legal;

    /**
     * @var int
     *
     * @ORM\Column(name="activate_legal", type="integer", nullable=true, unique=false)
     */
    protected $activateLegal;

    /**
     * @var bool
     *
     * @ORM\Column(name="add_teachers_to_sessions_courses", type="boolean", nullable=true)
     */
    protected $addTeachersToSessionsCourses;

    /**
     * @var int
     *
     * @ORM\Column(name="course_type_id", type="integer", nullable=true, unique=false)
     */
    protected $courseTypeId;

    /**
     * ORM\OneToMany(targetEntity="CurriculumCategory", mappedBy="course").
     */
    //protected $curriculumCategories;

    /**
     * @var Room
     *
     * @ORM\ManyToOne(targetEntity="Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    protected $room;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->lastVisit = new \DateTime();
        $this->lastEdit = new \DateTime();

        $this->users = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->gradebookCategories = new ArrayCollection();
        $this->gradebookEvaluations = new ArrayCollection();
        $this->gradebookLinks = new ArrayCollection();
        $this->trackEHotspots = new ArrayCollection();
        $this->trackEAttempts = new ArrayCollection();
        $this->searchEngineRefs = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->specificFieldValues = new ArrayCollection();
        $this->sharedSurveys = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getTitle();
    }

    /**
     * @return ArrayCollection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param Session $session
     *
     * @return ArrayCollection
     */
    public function getTools(Session $session = null)
    {
        $orWhere = Criteria::expr()->eq('sessionId', 0);

        if ($session) {
            $orWhere = Criteria::expr()->in('sessionId', [0, $session->getId()]);
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('sessionId'))
            ->orWhere($orWhere);

        return $this->tools->matching($criteria);
    }

    /**
     * @param array $tools
     */
    public function setTools($tools)
    {
        foreach ($tools as $tool) {
            $this->addTools($tool);
        }
    }

    /**
     * @param CTool $tool
     */
    public function addTools(CTool $tool)
    {
        $tool->setCourse($this);
        $this->tools[] = $tool;
    }

    /**
     * @return ArrayCollection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @param $urls
     */
    public function setUrls($urls)
    {
        $this->urls = new ArrayCollection();

        foreach ($urls as $url) {
            $this->addUrls($url);
        }
    }

    /**
     * @param AccessUrlRelCourse $url
     */
    public function addUrlRelCourse(AccessUrlRelCourse $url)
    {
        $url->setCourse($this);
        $this->urls[] = $url;
    }

    /**
     * @param AccessUrl $url
     */
    public function addUrl(AccessUrl $url)
    {
        $urlRelCourse = new AccessUrlRelCourse();
        $urlRelCourse->setCourse($this);
        $urlRelCourse->setUrl($url);
        $this->addUrlRelCourse($urlRelCourse);
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return ArrayCollection
     */
    public function getTeachers()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('status', User::COURSE_MANAGER));

        return $this->users->matching($criteria);
    }

    /**
     * @return ArrayCollection
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
    }

    /**
     * @param CourseRelUser $courseRelUser
     */
    public function addUsers(CourseRelUser $courseRelUser)
    {
        $courseRelUser->setCourse($this);

        if (!$this->hasSubscription($courseRelUser)) {
            $this->users[] = $courseRelUser;
        }
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasUser(User $user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->getUsers()->matching($criteria)->count() > 0;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasStudent(User $user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->getStudents()->matching($criteria)->count() > 0;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasTeacher(User $user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->getTeachers()->matching($criteria)->count() > 0;
    }

    /**
     * @param CGroupInfo $group
     *
     * @return bool
     */
    public function hasGroup(CGroupInfo $group)
    {
        /*$criteria = Criteria::create()->where(
            Criteria::expr()->eq('groups', $group)
        );*/

        return $this->getGroups()->contains($group);
    }

    /**
     * Remove $user.
     *
     * @param CourseRelUser $user
     */
    public function removeUsers(CourseRelUser $user)
    {
        foreach ($this->users as $key => $value) {
            if ($value->getId() == $user->getId()) {
                unset($this->users[$key]);
            }
        }
    }

    /**
     * @param User $user
     */
    public function addTeacher(User $user)
    {
        $this->addUser($user, 0, "Trainer", User::COURSE_MANAGER);
    }

    /**
     * @param User $user
     */
    public function addStudent(User $user)
    {
        $this->addUser($user, 0, "", User::STUDENT);
    }

    /**
     * Set id.
     *
     * @return int
     */
    public function setId($id)
    {
        $this->id = $id;
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

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
     * Set categoryCode.
     *
     * @param string $categoryCode
     *
     * @return Course
     */
    public function setCategoryCode($categoryCode)
    {
        $this->categoryCode = $categoryCode;

        return $this;
    }

    /**
     * Get categoryCode.
     *
     * @return string
     */
    public function getCategoryCode()
    {
        return $this->categoryCode;
    }

    /**
     * Set visibility.
     *
     * @param int $visibility
     *
     * @return Course
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility.
     *
     * @return int
     */
    public function getVisibility()
    {
        return (int) $this->visibility;
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

    /**
     * Set tutorName.
     *
     * @param string $tutorName
     *
     * @return Course
     */
    public function setTutorName($tutorName)
    {
        $this->tutorName = $tutorName;

        return $this;
    }

    /**
     * Get tutorName.
     *
     * @return string
     */
    public function getTutorName()
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
     * @param int $diskQuota
     *
     * @return Course
     */
    public function setDiskQuota($diskQuota)
    {
        $this->diskQuota = intval($diskQuota);

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
     * @param \DateTime $lastVisit
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
     * @return \DateTime
     */
    public function getLastVisit()
    {
        return $this->lastVisit;
    }

    /**
     * Set lastEdit.
     *
     * @param \DateTime $lastEdit
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
     * @return \DateTime
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }

    /**
     * Set creationDate.
     *
     * @param \DateTime $creationDate
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
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set expirationDate.
     *
     * @param \DateTime $expirationDate
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
     * @return \DateTime
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

    /**
     * @param Room $room
     *
     * @return Course
     */
    public function setRoom($room)
    {
        $this->room = $room;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $activeVisibilityList = [
            self::REGISTERED,
            self::OPEN_PLATFORM,
            self::OPEN_WORLD,
        ];

        return in_array($this->visibility, $activeVisibilityList);
    }

    /**
     * Anybody can see this course.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->visibility == self::OPEN_WORLD;
    }

    /**
     * @return array
     */
    public static function getStatusList()
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

    /**
     * @param Session $session
     *
     * @return $this
     */
    public function setCurrentSession(Session $session)
    {
        // If the session is registered in the course session list.
        /*if ($this->getSessions()->contains($session->getId())) {
            $this->currentSession = $session;
        }*/

        $list = $this->getSessions();
        /** @var SessionRelCourse $item */
        foreach ($list as $item) {
            if ($item->getSession()->getId() == $session->getId()) {
                $this->currentSession = $session;
                break;
            }
        }

        return $this;
    }

    /**
     * @param AccessUrl $url
     *
     * @return $this
     */
    public function setCurrentUrl(AccessUrl $url)
    {
        $urlList = $this->getUrls();
        /** @var AccessUrlRelCourse $item */
        foreach ($urlList as $item) {
            if ($item->getUrl()->getId() == $url->getId()) {
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

    /**
     * @param CourseRelUser $subscription
     *
     * @return bool
     */
    public function hasSubscription(CourseRelUser $subscription)
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

    /**
     * @param User   $user
     * @param string $relationType
     * @param string $role
     * @param string $status
     */
    public function addUser(User $user, $relationType, $role, $status)
    {
        $courseRelUser = new CourseRelUser();
        $courseRelUser->setCourse($this);
        $courseRelUser->setUser($user);
        $courseRelUser->setRelationType($relationType);
        $courseRelUser->setRole($role);
        $courseRelUser->setStatus($status);
        $this->addUsers($courseRelUser);
    }

    /**
     * Resource identifier.
     *
     * @return int
     */
    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return $this->getCode();
    }

    /**
     * @return string
     */
    public function getToolName(): string
    {
        return 'Course';
    }
}
