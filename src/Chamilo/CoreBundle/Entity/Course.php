<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Course
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
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\CourseListener"})
 */
class Course
{
    const CLOSED = 0;
    const REGISTERED = 1;
    const OPEN_PLATFORM = 2;
    const OPEN_WORLD = 3;
    const HIDDEN = 4;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     * @Gedmo\Slug(
     *      fields={"title"},
     *      updatable = false,
     *      unique = true
     * )
     * @ORM\Column(name="code", type="string", length=40, nullable=false, unique=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="directory", type="string", length=40, nullable=true, unique=false)
     */
    private $directory;

    /**
     * @var string
     *
     * @ORM\Column(name="course_language", type="string", length=20, nullable=true, unique=false)
     */
    private $courseLanguage;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="category_code", type="string", length=40, nullable=true, unique=false)
     */
    private $categoryCode;

    /**
     * @var boolean
     * @Assert\NotBlank()
     * @ORM\Column(name="visibility", type="integer", nullable=true, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="show_score", type="integer", nullable=true, unique=false)
     */
    private $showScore;

    /**
     * @var string
     *
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true, unique=false)
     */
    private $tutorName;

    /**
     * @var string
     *
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true, unique=false)
     */
    private $visualCode;

    /**
     * @var string
     *
     * @ORM\Column(name="department_name", type="string", length=30, nullable=true, unique=false)
     */
    private $departmentName;

    /**
     * @var string
     * @Assert\Url()
     * @ORM\Column(name="department_url", type="string", length=180, nullable=true, unique=false)
     */
    private $departmentUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="disk_quota", type="bigint", nullable=true, unique=false)
     */
    private $diskQuota;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_visit", type="datetime", nullable=true, unique=false)
     */
    private $lastVisit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_edit", type="datetime", nullable=true, unique=false)
     */
    private $lastEdit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=true, unique=false)
     */
    private $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true, unique=false)
     */
    private $expirationDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="subscribe", type="boolean", nullable=true, unique=false)
     */
    private $subscribe;

    /**
     * @var boolean
     *
     * @ORM\Column(name="unsubscribe", type="boolean", nullable=true, unique=false)
     */
    private $unsubscribe;

    /**
     * @var string
     *
     * @ORM\Column(name="registration_code", type="string", length=255, nullable=true, unique=false)
     */
    private $registrationCode;

    /**
     * @var string
     *
     * @ORM\Column(name="legal", type="text", nullable=true, unique=false)
     */
    private $legal;

    /**
     * @var integer
     *
     * @ORM\Column(name="activate_legal", type="integer", nullable=true, unique=false)
     */
    private $activateLegal;

    /**
     * @var boolean
     *
     * @ORM\Column(name="add_teachers_to_sessions_courses", type="boolean", nullable=true)
     */
    private $addTeachersToSessionsCourses;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_type_id", type="integer", nullable=true, unique=false)
     */
    private $courseTypeId;

    /**
     * "orphanRemoval" is needed to delete the CourseRelUser relation
     * in the CourseAdmin class. The setUsers, getUsers, removeUsers and
     * addUsers methods need to be added.
     * @ORM\OneToMany(targetEntity="CourseRelUser", mappedBy="course", cascade={"persist"}, orphanRemoval=true)
     **/
    protected $users;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelCourse", mappedBy="course", cascade={"persist"}, orphanRemoval=true)
     **/
    protected $urls;

    /**
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="course", cascade={"persist"})
     **/
    protected $sessions;

    /**
     * @ORM\OneToMany(targetEntity="SessionRelCourseRelUser", mappedBy="course", cascade={"persist"})
     **/
    protected $sessionUserSubscriptions;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CItemProperty", mappedBy="course")
     **/
    //protected $items;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CTool", mappedBy="course", cascade={"persist"})
     **/
    protected $tools;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\NotebookBundle\Entity\CNotebook", mappedBy="course")
     **/
    //protected $notebooks;

    /**
     * ORM\OneToMany(targetEntity="CurriculumCategory", mappedBy="course")
     **/
    //protected $curriculumCategories;

    /**
     * @var Room
     *
     * @ORM\ManyToOne(targetEntity="Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     **/
    private $room;

    /**
     * @var Session
     **/
    protected $currentSession;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="course", cascade={"persist"})
     */
    protected $issuedSkills;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->users = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strval($this->getTitle());
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
    public function getNotebooks()
    {
        return $this->notebooks;
    }

    /**
     * @return ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

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
    public function addUrls(AccessUrlRelCourse $url)
    {
        $url->setCourse($this);
        $this->urls[] = $url;
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
     * @param CourseRelUser $subscription
     * @return bool
     */
    private function hasSubscription(CourseRelUser $subscription)
    {
        if ($this->getUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq("user", $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq("status", $subscription->getStatus())
            )->andWhere(
                Criteria::expr()->eq("relationType", $subscription->getRelationType())
            );

            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasUser(User $user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("user", $user)
        );

        return $this->getUsers()->matching($criteria)->count() > 0;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasStudent(User $user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("user", $user)
        );

        return $this->getStudents()->matching($criteria)->count() > 0;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasTeacher(User $user)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("user", $user)
        );

        return $this->getTeachers()->matching($criteria)->count() > 0;
    }

    /**
     * Remove $user
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
     * @param string $relationType
     * @param string $role
     * @param string $status
     */
    private function addUser(User $user, $relationType, $role, $status)
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
     * Set id
     *
     * @return integer
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
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
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set directory
     *
     * @param string $directory
     * @return Course
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get directory
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }


    /**
     * Set courseLanguage
     *
     * @param string $courseLanguage
     * @return Course
     */
    public function setCourseLanguage($courseLanguage)
    {
        $this->courseLanguage = $courseLanguage;

        return $this;
    }

    /**
     * Get courseLanguage
     *
     * @return string
     */
    public function getCourseLanguage()
    {
        return $this->courseLanguage;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Course
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Course
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set categoryCode
     *
     * @param string $categoryCode
     * @return Course
     */
    public function setCategoryCode($categoryCode)
    {
        $this->categoryCode = $categoryCode;

        return $this;
    }

    /**
     * Get categoryCode
     *
     * @return string
     */
    public function getCategoryCode()
    {
        return $this->categoryCode;
    }

    /**
     * Set visibility
     *
     * @param boolean $visibility
     * @return Course
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set showScore
     *
     * @param integer $showScore
     * @return Course
     */
    public function setShowScore($showScore)
    {
        $this->showScore = $showScore;

        return $this;
    }

    /**
     * Get showScore
     *
     * @return integer
     */
    public function getShowScore()
    {
        return $this->showScore;
    }

    /**
     * Set tutorName
     *
     * @param string $tutorName
     * @return Course
     */
    public function setTutorName($tutorName)
    {
        $this->tutorName = $tutorName;

        return $this;
    }

    /**
     * Get tutorName
     *
     * @return string
     */
    public function getTutorName()
    {
        return $this->tutorName;
    }

    /**
     * Set visualCode
     *
     * @param string $visualCode
     * @return Course
     */
    public function setVisualCode($visualCode)
    {
        $this->visualCode = $visualCode;

        return $this;
    }

    /**
     * Get visualCode
     *
     * @return string
     */
    public function getVisualCode()
    {
        return $this->visualCode;
    }

    /**
     * Set departmentName
     *
     * @param string $departmentName
     * @return Course
     */
    public function setDepartmentName($departmentName)
    {
        $this->departmentName = $departmentName;

        return $this;
    }

    /**
     * Get departmentName
     *
     * @return string
     */
    public function getDepartmentName()
    {
        return $this->departmentName;
    }

    /**
     * Set departmentUrl
     *
     * @param string $departmentUrl
     * @return Course
     */
    public function setDepartmentUrl($departmentUrl)
    {
        $this->departmentUrl = $departmentUrl;

        return $this;
    }

    /**
     * Get departmentUrl
     *
     * @return string
     */
    public function getDepartmentUrl()
    {
        return $this->departmentUrl;
    }

    /**
     * Set diskQuota
     *
     * @param integer $diskQuota
     * @return Course
     */
    public function setDiskQuota($diskQuota)
    {
        $this->diskQuota = intval($diskQuota);

        return $this;
    }

    /**
     * Get diskQuota
     *
     * @return integer
     */
    public function getDiskQuota()
    {
        return $this->diskQuota;
    }

    /**
     * Set lastVisit
     *
     * @param \DateTime $lastVisit
     * @return Course
     */
    public function setLastVisit($lastVisit)
    {
        $this->lastVisit = $lastVisit;

        return $this;
    }

    /**
     * Get lastVisit
     *
     * @return \DateTime
     */
    public function getLastVisit()
    {
        return $this->lastVisit;
    }

    /**
     * Set lastEdit
     *
     * @param \DateTime $lastEdit
     * @return Course
     */
    public function setLastEdit($lastEdit)
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get lastEdit
     *
     * @return \DateTime
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return Course
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set expirationDate
     *
     * @param \DateTime $expirationDate
     * @return Course
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get expirationDate
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Set subscribe
     *
     * @param boolean $subscribe
     *
     * @return Course
     */
    public function setSubscribe($subscribe)
    {
        $this->subscribe = intval($subscribe);

        return $this;
    }

    /**
     * Get subscribe
     *
     * @return boolean
     */
    public function getSubscribe()
    {
        return $this->subscribe;
    }

    /**
     * Set unsubscribe
     *
     * @param boolean $unsubscribe
     *
     * @return Course
     */
    public function setUnsubscribe($unsubscribe)
    {
        $this->unsubscribe = intval($unsubscribe);

        return $this;
    }

    /**
     * Get unsubscribe
     *
     * @return boolean
     */
    public function getUnsubscribe()
    {
        return $this->unsubscribe;
    }

    /**
     * Set registrationCode
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
     * Get registrationCode
     *
     * @return string
     */
    public function getRegistrationCode()
    {
        return $this->registrationCode;
    }

    /**
     * Set legal
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
     * Get legal
     *
     * @return string
     */
    public function getLegal()
    {
        return $this->legal;
    }

    /**
     * Set activateLegal
     *
     * @param integer $activateLegal
     *
     * @return Course
     */
    public function setActivateLegal($activateLegal)
    {
        $this->activateLegal = $activateLegal;

        return $this;
    }

    /**
     * Get activateLegal
     *
     * @return integer
     */
    public function getActivateLegal()
    {
        return $this->activateLegal;
    }

    /**
     * Set courseTypeId
     *
     * @param integer $courseTypeId
     *
     * @return Course
     */
    public function setCourseTypeId($courseTypeId)
    {
        $this->courseTypeId = $courseTypeId;

        return $this;
    }

    /**
     * Get courseTypeId
     *
     * @return integer
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
     * @return Course
     */
    public function setRoom($room)
    {
        $this->room = $room;

        return $this;
    }

    /**
     * @return string
     */
    public function getAbsoluteSysCoursePath()
    {
        return realpath(__DIR__.'/../../../app/courses/'.$this->getDirectory()).'/';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $activeVisibilityList = array(
            self::REGISTERED,
            self::OPEN_PLATFORM,
            self::OPEN_WORLD,
        );

        return in_array($this->visibility, $activeVisibilityList);
    }

    /**
     * Anybody can see this course
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
        return array(
            self::CLOSED => 'Closed',
            self::REGISTERED => 'Registered',
            self::OPEN_PLATFORM => 'Open platform',
            self::OPEN_WORLD => 'Open world',
            self::HIDDEN => 'Hidden',
        );
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
     * @return $this
     */
    public function setCurrentSession(Session $session)
    {
        // If the session is registered in the course session list.
        if ($this->getSessions()->contains($session->getId())) {
            $this->currentSession = $session;
        }
        return $this;
    }

    /**
     * Check if the course has a picture
     * @return bool
     */
    public function hasPicture()
    {
        return file_exists(api_get_path(SYS_COURSE_PATH) . $this->directory . '/course-pic85x85.png');
    }

    /**
     * Get the course picture path
     * @param bool $fullSize
     * @return null|string
     */
    public function getPicturePath($fullSize = false)
    {
        if (!$this->hasPicture()) {
            return null;
        }

        if ($fullSize) {
            return api_get_path(WEB_COURSE_PATH) . $this->directory . '/course-pic.png';
        }

        return api_get_path(WEB_COURSE_PATH) . $this->directory . '/course-pic85x85.png';
    }
}
