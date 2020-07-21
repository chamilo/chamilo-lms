<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\UserBundle\Entity\User;
use CourseManager;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Exception;
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
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\CourseRepository")
 */
class Course
{
    const CLOSED = 0;
    const REGISTERED = 1;
    const OPEN_PLATFORM = 2;
    const OPEN_WORLD = 3;
    const HIDDEN = 4;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CourseRelUser[]|ArrayCollection
     *
     * "orphanRemoval" is needed to delete the CourseRelUser relation
     * in the CourseAdmin class. The setUsers, getUsers, removeUsers and
     * addUsers methods need to be added.
     *
     * @ORM\OneToMany(
     *     targetEntity="CourseRelUser",
     *     mappedBy="course",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $users;

    /**
     * @var AccessUrlRelCourse[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="AccessUrlRelCourse",
     *     mappedBy="course",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $urls;

    /**
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="course", cascade={"persist"})
     */
    protected $sessions;

    /**
     * @ORM\OneToMany(targetEntity="SessionRelCourseRelUser", mappedBy="course", cascade={"persist"})
     */
    protected $sessionUserSubscriptions;

    /**
     * @var ArrayCollection|CItemProperty[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CItemProperty",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $itemProperties;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CTool", mappedBy="course", cascade={"persist"})
     */
    protected $tools;

    /**
     * @var Session
     */
    protected $currentSession;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="course", cascade={"persist"})
     */
    protected $issuedSkills;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true, unique=false)
     */
    protected $title;

    /**
     * @var string
     * @Gedmo\Slug(
     *      fields={"title"},
     *      updatable = false,
     *      unique = true
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
     * @var bool
     * @Assert\NotBlank()
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
     * @Assert\Url()
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
     * @var DateTime
     *
     * @ORM\Column(name="last_visit", type="datetime", nullable=true, unique=false)
     */
    protected $lastVisit;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_edit", type="datetime", nullable=true, unique=false)
     */
    protected $lastEdit;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=true, unique=false)
     */
    protected $creationDate;

    /**
     * @var DateTime
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
     * @ORM\OneToMany(targetEntity="Chamilo\NotebookBundle\Entity\CNotebook", mappedBy="course")
     */
    //protected $notebooks;

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
     * @ORM\ManyToMany(targetEntity="AccessUrl")
     * @ORM\JoinTable(name="access_url_rel_course",
     *      joinColumns={@ORM\JoinColumn(name="c_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="access_url_id", referencedColumnName="id")}
     * )
     */
    protected $accessUrls;

    /**
     * @var ArrayCollection|CCourseSetting[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CCourseSetting",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $settings;

    /**
     * @var ArrayCollection|CLpCategory[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CLpCategory",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $learningPathCategories;

    /**
     * @var ArrayCollection|CLpItem[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CLpItem",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $learningPathItems;

    /**
     * @var ArrayCollection|CDocument[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CDocument",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $documents;

    /**
     * @var ArrayCollection|CForumForum[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CForumForum",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     *     )
     */
    protected $forums;

    /**
     * @var ArrayCollection|CLink[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CLink",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     *     )
     */
    protected $links;

    /**
     * @var ArrayCollection|CQuiz[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CQuiz",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     *     )
     */
    protected $quizzes;

    /**
     * @var ArrayCollection|CLp[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CLp",
     *     mappedBy="course",
     *     cascade={"persist", "remove"}
     *     )
     */
    protected $learningPaths;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->activateLegal = 0;
        $this->addTeachersToSessionsCourses = false;
        $this->creationDate = new DateTime('now', new DateTimeZone('utc'));
        $this->lastVisit = null;
        $this->showScore = 1;
        $this->unsubscribe = false;
        $this->accessUrls = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->forums = new ArrayCollection();
        $this->itemProperties = new ArrayCollection();
        $this->learningPathCategories = new ArrayCollection();
        $this->learningPathItems = new ArrayCollection();
        $this->learningPaths = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->tools = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('course %s ("%s")', $this->id, $this->getName());
    }

    /**
     * @return Repository\CourseRepository|EntityRepository
     */
    public static function getRepository()
    {
        return Database::getManager()->getRepository('ChamiloCoreBundle:Course');
    }

    /**
     * Sets sane values if still unset.
     * Makes directory if missing.
     *
     * @ORM\PrePersist
     *
     * @throws Exception
     */
    public function prePersist()
    {
        if (empty($this->title)) {
            throw new Exception('This course is missing a title');
        }
        if (empty($this->code)) {
            $this->code = substr(
                preg_replace('/[^A-Z0-9]/', '', strtoupper(api_replace_dangerous_char($this->title))),
                0,
                CourseManager::MAX_COURSE_LENGTH_CODE
            );
        }
        $originalCode = $this->code;
        $counter = 1;
        while (self::getRepository()->matching(
            Criteria::create()->where(
                Criteria::expr()->eq('code', $this->code)
            )
        )->exists(function ($other) {
            return $other !== $this;
        })) {
            $this->code = sprintf('%s_%d', $originalCode, $counter++);
        }
        if (empty($this->visualCode)) {
            $this->visualCode = $this->code;
        }
        if (empty($this->directory)) {
            $this->directory = $this->code;
        }
        $originalDirectory = $this->directory;
        $counter = 1;
        while (file_exists($this->getAbsolutePath())) {
            $this->directory = sprintf('%s_%d', $originalDirectory, $counter++);
        }
        if (is_null($this->courseLanguage)) {
            $this->courseLanguage = api_get_setting('platformLanguage');
        }
        if (is_null($this->description)) {
            $this->description = get_lang('CourseDescription');
        }
        if (is_null($this->categoryCode)) {
            $this->categoryCode = '';
        }
        if (is_null($this->visibility)) {
            $this->visibility = api_get_setting(
                'courses_default_creation_visibility'
            ) ?: COURSE_VISIBILITY_OPEN_PLATFORM;
        } elseif ($this->visibility < 0 || $this->visibility > 4) {
            throw new Exception('This course visibility in invalid:'.$this->visibility);
        }
        if (is_null($this->subscribe)) {
            $this->subscribe = (COURSE_VISIBILITY_OPEN_PLATFORM == $this->visibility);
        }
        if (is_null($this->diskQuota)) {
            $this->diskQuota = api_get_setting('default_document_quotum');
        }
        $this->lastEdit = new DateTime('now', new DateTimeZone('utc'));
        if (is_null($this->expirationDate)) {
            global $firstExpirationDelay;
            $this->expirationDate = new DateTime('now', new DateTimeZone('utc'));
            $this->expirationDate->add(new DateInterval(sprintf('PT%dS', $firstExpirationDelay)));
        }
        if (!empty($this->departmentUrl) && !preg_match("@^https?://@", $this->departmentUrl)) {
            $this->departmentUrl = 'https://'.$this->departmentUrl;
        }
        if ($this->accessUrls->isEmpty()) {
            $this->accessUrls->add(api_get_access_url_entity());
        }
        $this->prepareRepository();
        $this->createTools();
        $this->createSettings();
    }

    /**
     * Removes the course's directory.
     *
     * @ORM\PostRemove
     *
     * @throws Exception
     */
    public function postRemove()
    {
        $absolutePath = $this->getAbsolutePath();
        if (!file_exists($absolutePath)) {
            if (!rmdir($absolutePath)) {
                error_log('Could not remove the course directory '.$absolutePath);
            }
        }
    }

    /**
     * Builds the course's directory absolute path.
     *
     * @throws Exception on undefined directory
     *
     * @return string the course's directory absolute path
     */
    public function getAbsolutePath()
    {
        if (empty($this->directory)) {
            throw new Exception('this course does not have a directory yet');
        }

        return api_get_path(SYS_COURSE_PATH).$this->directory;
    }

    /**
     * @return ArrayCollection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @return ArrayCollection
     */
    /*public function getNotebooks()
    {
        return $this->notebooks;
    }*/

    /**
     * @return ArrayCollection
     */
    /*public function getItems()
    {
        return $this->items;
    }*/

    /**
     * @return ArrayCollection
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @param $tools
     */
    public function setTools($tools)
    {
        foreach ($tools as $tool) {
            $this->addTools($tool);
        }
    }

    public function setToolList($list)
    {
        $this->tools = $list;
    }

    public function addTools(CTool $tool)
    {
        $tool->setCourse($this);
        $this->tools[] = $tool;
    }

    /**
     * @return AccessUrlRelCourse[]|ArrayCollection
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
        foreach ($urls as $url) {
            $this->addUrls($url);
        }
    }

    public function addUrls(AccessUrlRelCourse $url)
    {
        $url->setCourse($this);
        $this->urls[] = $url;
    }

    /**
     * @return CourseRelUser[]|ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
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
    public function hasUser($user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("user", $user)
        );

        return $this->getUsers()->matching($criteria)->count() > 0;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasStudent($user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("user", $user)
        );

        return $this->getStudents()->matching($criteria)->count() > 0;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasTeacher($user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("user", $user)
        );

        return $this->getTeachers()->matching($criteria)->count() > 0;
    }

    /**
     * Remove $user.
     *
     * @param CourseRelUser $user
     */
    public function removeUsers($user)
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
    public function addTeacher($user)
    {
        $this->addUser($user, 0, "Trainer", User::COURSE_MANAGER);
    }

    /**
     * @param User $user
     */
    public function addStudent($user)
    {
        $this->addUser($user, 0, "", User::STUDENT);
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Course
     */
    public function setId($id)
    {
        $this->id = $id;

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

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
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
     * @param bool $visibility
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
     * @return bool
     */
    public function getVisibility()
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
        $this->subscribe = boolval($subscribe);

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
        $this->unsubscribe = boolval($unsubscribe);

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
    public function setCurrentSession($session)
    {
        // If the session is registered in the course session list.
        if ($this->getSessions()->contains($session->getId())) {
            $this->currentSession = $session;
        }

        return $this;
    }

    /**
     * @return CLpCategory[]|ArrayCollection
     */
    public function getLearningPathCategories()
    {
        return $this->learningPathCategories;
    }

    /**
     * @return CDocument[]|ArrayCollection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @return CForumForum[]|ArrayCollection
     */
    public function getForums()
    {
        return $this->forums;
    }

    /**
     * @return CQuiz[]|ArrayCollection
     */
    public function getQuizzes()
    {
        return $this->quizzes;
    }

    /**
     * @return CLink[]|ArrayCollection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @return CLp[]|ArrayCollection
     */
    public function getLearningPaths()
    {
        return $this->learningPaths;
    }

    /**
     * Searches and returns the resource of a specific type having a specific title.
     *
     * @param string $type  'dir', 'document', 'quiz'… (supported values are hardcoded in this function)
     * @param string $title the title of the specific resource to find
     *
     * @throws Exception when not found or more than one found
     *
     * @return object the resource
     */
    public function findResource($type, $title)
    {
        $collectionsAndColumns = [
            // type          collection           column
            'document' => [$this->documents,   'title'],
            'final_item' => [$this->documents,   'title'],
            'forum' => [$this->forums, 'forumTitle'],
            'link' => [$this->links,       'title'],
            'quiz' => [$this->quizzes,     'title'],
        ];
        if (!array_key_exists($type, $collectionsAndColumns)) {
            throw new Exception(sprintf('unsupported resource type "%s"', $type));
        }
        list($collection, $column) = $collectionsAndColumns[$type];
        $resources = $collection->matching(Criteria::create()->where(Criteria::expr()->eq($column, $title)));
        if (empty($resources)) {
            throw new Exception(sprintf('%s "%s" not found', $type, $title));
        }
        if (count($resources) > 1) {
            throw new Exception(sprintf('more than one %s "%s" found', $type, $title));
        }

        return $resources[0];
    }

    /**
     * @return CLpItem[]|ArrayCollection
     */
    public function getLearningPathItems()
    {
        return $this->learningPathItems;
    }

    /**
     * @return CItemProperty[]|ArrayCollection
     */
    public function getItemProperties()
    {
        return $this->itemProperties;
    }

    /**
     * @return CCourseSetting[]|ArrayCollection
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param CourseRelUser $subscription
     *
     * @return bool
     */
    protected function hasSubscription($subscription)
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
    protected function addUser($user, $relationType, $role, $status)
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
     * Initializes the course's file repository.
     * Replaces \AddCourse::prepare_course_repository.
     *
     * @throws Exception
     */
    private function prepareRepository()
    {
        $dirPermissions = api_get_permissions_for_new_directories();
        $filePermissions = api_get_permissions_for_new_files();
        $indexHtmlContents = '<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Not authorized</title></head><body></body></html>';
        $repositoryPath = $this->getAbsolutePath();
        if (!file_exists($repositoryPath)) {
            if (!mkdir($repositoryPath, $dirPermissions)) {
                throw new Exception(sprintf('Could not create course repository "%s"', $repositoryPath));
            }
        }
        foreach ([
                     'document',
                     'dropbox',
                     'exercises',
                     'group',
                     'page',
                     'scorm',
                     'upload',
                     'upload/announcements',
                     'upload/announcements/images',
                     'upload/blog',
                     'upload/calendar',
                     'upload/calendar/images',
                     'upload/forum',
                     'upload/forum/images',
                     'upload/learning_path',
                     'upload/learning_path/images',
                     'upload/test',
                     'work',
                 ] as $relativePath) {
            $subfolderPath = $repositoryPath.'/'.$relativePath;
            if (!file_exists($subfolderPath)) {
                if (!mkdir($subfolderPath, $dirPermissions)) {
                    throw new Exception(sprintf('Could not create course repository subfolder "%s"', $subfolderPath));
                }
            }
            $indexHtmlFilePath = $subfolderPath.'/index.html';
            if (!file_exists($indexHtmlFilePath)) {
                $indexHtmlFile = fopen($indexHtmlFilePath, 'w');
                if (false === $indexHtmlFile) {
                    throw new Exception(sprintf('Could not create course repository subfolder index file "%s"', $indexHtmlFilePath));
                }
                if (false === fwrite($indexHtmlFile, $indexHtmlContents)) {
                    throw new Exception(sprintf('Could not write to course repository subfolder index file "%s"', $indexHtmlFilePath));
                }
                if (!fclose($indexHtmlFile)) {
                    throw new Exception(sprintf('Could not close course repository subfolder index file "%s"', $indexHtmlFilePath));
                }
                if (!@chmod($indexHtmlFile, $filePermissions)) {
                    // never mind, on some platforms it is not possible anyway
                }
            }
        }

        // Create .htaccess in the dropbox directory.
        $dropboxHtAccessFilePath = $repositoryPath.'/dropbox/.htaccess';
        $dropboxHtAccessFile = fopen($dropboxHtAccessFilePath, 'w');
        if (false === $dropboxHtAccessFile) {
            throw new Exception(sprintf('Could not create course repository dropbox subfolder access control file "%s"', $dropboxHtAccessFilePath));
        }
        if (!fwrite(
            $dropboxHtAccessFile,
            "AuthName AllowLocalAccess
AuthType Basic

order deny,allow
deny from all

php_flag zlib.output_compression off"
        )) {
            throw new Exception(sprintf('Could not write to course repository dropbox subfolder access control file "%s"', $dropboxHtAccessFilePath));
        }
        if (!fclose($dropboxHtAccessFile)) {
            throw new Exception(sprintf('Could not close course repository dropbox subfolder access control file "%s"', $dropboxHtAccessFilePath));
        }
    }

    private function createTools()
    {
        $toolReference = [
            [TOOL_COURSE_DESCRIPTION, 'course_description/index.php', 'info.gif', 'course_description', 'authoring'],
            [TOOL_CALENDAR_EVENT, 'calendar/agenda.php', 'agenda.gif', 'agenda', 'interaction'],
            [TOOL_DOCUMENT, 'document/document.php', 'folder_document.gif', 'documents', 'authoring'],
            [TOOL_LEARNPATH, 'lp/lp_controller.php', 'scorms.gif', 'learning_path', 'authoring'],
            [TOOL_LINK, 'link/link.php', 'links.gif', 'links', 'authoring'],
            [TOOL_QUIZ, 'exercise/exercise.php', 'quiz.gif', 'quiz', 'authoring'],
            [TOOL_ANNOUNCEMENT, 'announcements/announcements.php', 'valves.gif', 'announcements', 'authoring'],
            [TOOL_FORUM, 'forum/index.php', 'forum.gif', 'forums', 'interaction'],
            [TOOL_DROPBOX, 'dropbox/index.php', 'dropbox.gif', 'dropbox', 'interaction'],
            [TOOL_USER, 'user/user.php', 'members.gif', 'users', 'interaction'],
            [TOOL_GROUP, 'group/group.php', 'group.gif', 'groups', 'interaction'],
            [TOOL_CHAT, 'chat/chat.php', 'chat.gif', 'chat', 'interaction'],
            [TOOL_STUDENTPUBLICATION, 'work/work.php', 'works.gif', 'student_publications', 'interaction'],
            [TOOL_SURVEY, 'survey/survey_list.php', 'survey.gif', 'survey', 'interaction'],
            [TOOL_WIKI, 'wiki/index.php', 'wiki.gif', 'wiki', 'interaction'],
            [TOOL_GRADEBOOK, 'gradebook/index.php', 'gradebook.gif', 'gradebook', 'authoring'],
            [TOOL_GLOSSARY, 'glossary/index.php', 'glossary.gif', 'glossary', 'authoring'],
            [TOOL_NOTEBOOK, 'notebook/index.php', 'notebook.gif', 'notebook', 'interaction'],
        ];
        if (api_get_configuration_value('allow_portfolio_tool')) {
            $toolReference[] = [TOOL_PORTFOLIO, 'portfolio/index.php', 'wiki_task.png', 'portfolio', 'interaction'];
        }
        $toolReference[] = [TOOL_ATTENDANCE, 'attendance/index.php', 'attendance.gif', 'attendances', 'authoring'];
        $toolReference[] =
            [TOOL_COURSE_PROGRESS, 'course_progress/index.php', 'course_progress.gif', 'course_progress', 'authoring'];
        $counter = 1;
        foreach ($toolReference as list($name, $link, $image, $key, $category)) {
            (new CTool())
                ->setCourse($this)
                ->setId($counter++)
                ->setName($name)
                ->setLink($link)
                ->setImage($image)
                ->setVisibility('true' === api_get_setting('course_create_active_tools', $key))
                ->setCategory($category);
        }
        if (api_get_setting('search_enabled') === 'true') {
            (new CTool())
                ->setCourse($this)
                ->setId($counter++)
                ->setName(TOOL_SEARCH)
                ->setLink('search/')
                ->setImage('info.gif')
                ->setVisibility('true' === api_get_setting('course_create_active_tools', 'enable_search'))
                ->setCategory('authoring')
                ->setAddress('search.gif');
        }
        (new CTool())
            ->setCourse($this)
            ->setId($counter++)
            ->setName(TOOL_BLOGS)
            ->setLink('blog/blog_admin.php')
            ->setImage('blog_admin.gif')
            ->setVisibility('true' === api_get_setting('course_create_active_tools', 'blogs'))
            ->setCategory('admin')
            ->setAdmin('1');
        foreach ([
                     [TOOL_TRACKING, 'tracking/courseLog.php', 'statistics.gif'],
                     [TOOL_COURSE_SETTING, 'course_info/infocours.php', 'reference.gif'],
                     [TOOL_COURSE_MAINTENANCE, 'course_info/maintenance.php', 'backup.gif'],
                 ] as list($name, $link, $image)) {
            (new CTool())
                ->setCourse($this)
                ->setId($counter++)
                ->setName($name)
                ->setLink($link)
                ->setImage($image)
                ->setVisibility(false)
                ->setCategory('admin')
                ->setAdmin('1');
        }
    }

    private function createSettings()
    {
        $settings = [
            'email_alert_manager_on_new_doc' => ['default' => 0, 'category' => 'work'],
            'email_alert_on_new_doc_dropbox' => ['default' => 0, 'category' => 'dropbox'],
            'allow_user_edit_agenda' => ['default' => 0, 'category' => 'agenda'],
            'allow_user_edit_announcement' => ['default' => 0, 'category' => 'announcement'],
            'email_alert_manager_on_new_quiz' => [
                'default' => (api_get_setting('email_alert_manager_on_new_quiz') === 'true') ? 1 : 0,
                'category' => 'quiz',
            ],
            'allow_user_image_forum' => ['default' => 1, 'category' => 'forum'],
            'course_theme' => ['default' => '', 'category' => 'theme'],
            'allow_learning_path_theme' => ['default' => 1, 'category' => 'theme'],
            'allow_open_chat_window' => ['default' => 1, 'category' => 'chat'],
            'email_alert_to_teacher_on_new_user_in_course' => ['default' => 0, 'category' => 'registration'],
            'allow_user_view_user_list' => ['default' => 1, 'category' => 'user'],
            'display_info_advance_inside_homecourse' => ['default' => 1, 'category' => 'thematic_advance'],
            'email_alert_students_on_new_homework' => ['default' => 0, 'category' => 'work'],
            'enable_lp_auto_launch' => ['default' => 0, 'category' => 'learning_path'],
            'enable_exercise_auto_launch' => ['default' => 0, 'category' => 'exercise'],
            'enable_document_auto_launch' => ['default' => 0, 'category' => 'document'],
            'pdf_export_watermark_text' => ['default' => '', 'category' => 'learning_path'],
            'allow_public_certificates' => [
                'default' => api_get_setting('allow_public_certificates') === 'true' ? 1 : '',
                'category' => 'certificates',
            ],
            'documents_default_visibility' => ['default' => 'visible', 'category' => 'document'],
            'show_course_in_user_language' => ['default' => 2, 'category' => null],
            'email_to_teachers_on_new_work_feedback' => ['default' => 1, 'category' => null],
        ];

        $counter = 1;
        foreach ($settings as $variable => $setting) {
            (new CCourseSetting())
                ->setId($counter++)
                ->setCourse($this)
                ->setVariable($variable)
                ->setValue($setting['default'])
                ->setCategory($setting['category']);
        }
    }
}
