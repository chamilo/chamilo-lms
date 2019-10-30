<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

//use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
//use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Session.
 * UniqueEntity("name").
 *
 * @ORM\Table(
 *      name="session",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})},
 *      indexes={
 *          @ORM\Index(name="idx_id_coach", columns={"id_coach"}),
 *          @ORM\Index(name="idx_id_session_admin_id", columns={"session_admin_id"})
 *      }
 * )
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\SessionListener"})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\SessionRepository")
 */
class Session
{
    public const VISIBLE = 1;
    public const READ_ONLY = 2;
    public const INVISIBLE = 3;
    public const AVAILABLE = 4;

    public const STUDENT = 0;
    public const DRH = 1;
    public const COACH = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\SkillBundle\Entity\SkillRelCourse", mappedBy="session", cascade={"persist", "remove"}
     * )
     */
    protected $skills;

    /**
     * @var ArrayCollection
     *
     * @ORM\OrderBy({"position" = "ASC"})
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     */
    protected $courses;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SessionRelUser", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     */
    protected $users;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="SessionRelCourseRelUser",
     *     mappedBy="session",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    protected $userCourseSubscriptions;

    /**
     * @var Course
     */
    protected $currentCourse;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="session", cascade={"persist"})
     */
    protected $issuedSkills;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelSession",
     *     mappedBy="session",
     *     cascade={"persist"}, orphanRemoval=true
     * )
     */
    protected $urls;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceLink", mappedBy="session", cascade={"remove"}, orphanRemoval=true)
     */
    protected $resourceLinks;

    /**
     * @var AccessUrl
     */
    protected $currentUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=150, nullable=false, unique=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true, unique=false)
     */
    protected $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="show_description", type="boolean", nullable=true)
     */
    protected $showDescription;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    protected $duration;

    /**
     * @var int
     *
     * @ORM\Column(name="nbr_courses", type="smallint", nullable=true, unique=false)
     */
    protected $nbrCourses;

    /**
     * @var int
     *
     * @ORM\Column(name="nbr_users", type="integer", nullable=true, unique=false)
     */
    protected $nbrUsers;

    /**
     * @var int
     *
     * @ORM\Column(name="nbr_classes", type="integer", nullable=true, unique=false)
     */
    protected $nbrClasses;

    /**
     * @var int
     *
     * @ORM\Column(name="session_admin_id", type="integer", nullable=true, unique=false)
     */
    protected $sessionAdminId;

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false, unique=false)
     */
    protected $visibility;

    /**
     * @var int
     *
     * @ORM\Column(name="promotion_id", type="integer", nullable=true, unique=false)
     */
    protected $promotionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_start_date", type="datetime", nullable=true, unique=false)
     */
    protected $displayStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_end_date", type="datetime", nullable=true, unique=false)
     */
    protected $displayEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_start_date", type="datetime", nullable=true, unique=false)
     */
    protected $accessStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_end_date", type="datetime", nullable=true, unique=false)
     */
    protected $accessEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="coach_access_start_date", type="datetime", nullable=true, unique=false)
     */
    protected $coachAccessStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="coach_access_end_date", type="datetime", nullable=true, unique=false)
     */
    protected $coachAccessEndDate;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false, options={"default":0})
     */
    protected $position;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CItemProperty", mappedBy="session")
     */
    //protected $items;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="sessionAsGeneralCoach")
     * @ORM\JoinColumn(name="id_coach", referencedColumnName="id")
     */
    protected $generalCoach;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SessionCategory", inversedBy="session")
     * @ORM\JoinColumn(name="session_category_id", referencedColumnName="id")
     */
    protected $category;

    /**
     * @var bool
     *
     * @ORM\Column(name="send_subscription_notification", type="boolean", nullable=false, options={"default":false})
     */
    protected $sendSubscriptionNotification;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CStudentPublication",
     *     mappedBy="session",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    protected $studentPublications;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->urls = new ArrayCollection();

        $this->nbrClasses = 0;
        $this->nbrUsers = 0;

        $this->displayStartDate = new \DateTime();
        $this->displayEndDate = new \DateTime();
        $this->accessStartDate = new \DateTime();
        $this->accessEndDate = new \DateTime();
        $this->coachAccessStartDate = new \DateTime();
        $this->coachAccessEndDate = new \DateTime();
        $this->visibility = 1;

        $this->courses = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->userCourseSubscriptions = new ArrayCollection();
        $this->showDescription = false;
        $this->category = null;
        $this->studentPublications = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     *
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return string
     */
    public function getShowDescription()
    {
        return $this->showDescription;
    }

    /**
     * @param string $showDescription
     *
     * @return $this
     */
    public function setShowDescription($showDescription)
    {
        $this->showDescription = $showDescription;

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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param $users
     *
     * @return $this
     */
    public function setUsers($users)
    {
        $this->users = new ArrayCollection();

        foreach ($users as $user) {
            $this->addUser($user);
        }

        return $this;
    }

    /**
     * @param SessionRelUser $user
     */
    public function addUser(SessionRelUser $user)
    {
        $user->setSession($this);

        if (!$this->hasUser($user)) {
            $this->users[] = $user;
        }
    }

    /**
     * @param int  $status
     * @param User $user
     */
    public function addUserInSession($status, User $user)
    {
        $sessionRelUser = new SessionRelUser();
        $sessionRelUser->setSession($this);
        $sessionRelUser->setUser($user);
        $sessionRelUser->setRelationType($status);

        $this->addUser($sessionRelUser);
    }

    /**
     * @param SessionRelUser $subscription
     *
     * @return bool
     */
    public function hasUser(SessionRelUser $subscription)
    {
        if ($this->getUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('user', $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq('session', $subscription->getSession())
            )->andWhere(
                Criteria::expr()->eq('relationType', $subscription->getRelationType())
            );

            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * @return ArrayCollection
     */
    public function getCourses()
    {
        return $this->courses;
    }

    /**
     * @param $courses
     */
    public function setCourses($courses)
    {
        $this->courses = new ArrayCollection();

        foreach ($courses as $course) {
            $this->addCourses($course);
        }
    }

    /**
     * @param SessionRelCourse $course
     */
    public function addCourses(SessionRelCourse $course)
    {
        $course->setSession($this);
        $this->courses[] = $course;
    }

    /**
     * @param Course $course
     *
     * @return bool
     */
    public function hasCourse(Course $course)
    {
        if ($this->getCourses()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('course', $course)
            );
            $relation = $this->getCourses()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * Remove $course.
     *
     * @param SessionRelCourse $course
     */
    public function removeCourses($course)
    {
        foreach ($this->courses as $key => $value) {
            if ($value->getId() == $course->getId()) {
                unset($this->courses[$key]);
            }
        }
    }

    /**
     * Remove course subscription for a user.
     * If user status in session is student, then decrease number of course users.
     *
     * @param User   $user
     * @param Course $course
     */
    public function removeUserCourseSubscription(User $user, Course $course)
    {
        /** @var SessionRelCourseRelUser $courseSubscription */
        foreach ($this->userCourseSubscriptions as $i => $courseSubscription) {
            if ($courseSubscription->getCourse()->getId() === $course->getId() &&
                $courseSubscription->getUser()->getId() === $user->getId()) {
                if ($this->userCourseSubscriptions[$i]->getStatus() === self::STUDENT) {
                    $sessionCourse = $this->getCourseSubscription($course);

                    $sessionCourse->setNbrUsers(
                        $sessionCourse->getNbrUsers() - 1
                    );
                }

                unset($this->userCourseSubscriptions[$i]);
            }
        }
    }

    /**
     * @param User   $user
     * @param Course $course
     * @param int    $status if not set it will check if the user is registered
     *                       with any status
     *
     * @return bool
     */
    public function hasUserInCourse(User $user, Course $course, $status = null): bool
    {
        $relation = $this->getUserInCourse($user, $course, $status);

        return $relation->count() > 0;
    }

    /**
     * @param User   $user
     * @param Course $course
     *
     * @return bool
     */
    public function hasStudentInCourse(User $user, Course $course)
    {
        return $this->hasUserInCourse($user, $course, self::STUDENT);
    }

    /**
     * @param User   $user
     * @param Course $course
     *
     * @return bool
     */
    public function hasCoachInCourseWithStatus(User $user, Course $course = null): bool
    {
        if (empty($course)) {
            return false;
        }

        return $this->hasUserInCourse($user, $course, self::COACH);
    }

    /**
     * @param User   $user
     * @param Course $course
     * @param string $status
     *
     * @return \Doctrine\Common\Collections\Collection|static
     */
    public function getUserInCourse(User $user, Course $course, $status = null)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('course', $course)
        )->andWhere(
            Criteria::expr()->eq('user', $user)
        );

        if (!is_null($status)) {
            $criteria->andWhere(
                Criteria::expr()->eq('status', $status)
            );
        }

        return $this->getUserCourseSubscriptions()->matching($criteria);
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return $this
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
     * Set nbrCourses.
     *
     * @param int $nbrCourses
     *
     * @return Session
     */
    public function setNbrCourses($nbrCourses)
    {
        $this->nbrCourses = $nbrCourses;

        return $this;
    }

    /**
     * Get nbrCourses.
     *
     * @return int
     */
    public function getNbrCourses()
    {
        return $this->nbrCourses;
    }

    /**
     * Set nbrUsers.
     *
     * @param int $nbrUsers
     *
     * @return Session
     */
    public function setNbrUsers($nbrUsers)
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }

    /**
     * Get nbrUsers.
     *
     * @return int
     */
    public function getNbrUsers()
    {
        return $this->nbrUsers;
    }

    /**
     * Set nbrClasses.
     *
     * @param int $nbrClasses
     *
     * @return Session
     */
    public function setNbrClasses($nbrClasses)
    {
        $this->nbrClasses = $nbrClasses;

        return $this;
    }

    /**
     * Get nbrClasses.
     *
     * @return int
     */
    public function getNbrClasses()
    {
        return $this->nbrClasses;
    }

    /**
     * Set sessionAdminId.
     *
     * @param int $sessionAdminId
     *
     * @return Session
     */
    public function setSessionAdminId($sessionAdminId)
    {
        $this->sessionAdminId = $sessionAdminId;

        return $this;
    }

    /**
     * Get sessionAdminId.
     *
     * @return int
     */
    public function getSessionAdminId()
    {
        return $this->sessionAdminId;
    }

    /**
     * Set visibility.
     *
     * @param int $visibility
     *
     * @return Session
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
        return $this->visibility;
    }

    /**
     * Set promotionId.
     *
     * @param int $promotionId
     *
     * @return Session
     */
    public function setPromotionId($promotionId)
    {
        $this->promotionId = $promotionId;

        return $this;
    }

    /**
     * Get promotionId.
     *
     * @return int
     */
    public function getPromotionId()
    {
        return $this->promotionId;
    }

    /**
     * Set displayStartDate.
     *
     * @param \DateTime $displayStartDate
     *
     * @return Session
     */
    public function setDisplayStartDate($displayStartDate)
    {
        $this->displayStartDate = $displayStartDate;

        return $this;
    }

    /**
     * Get displayStartDate.
     *
     * @return \DateTime
     */
    public function getDisplayStartDate()
    {
        return $this->displayStartDate;
    }

    /**
     * Set displayEndDate.
     *
     * @param \DateTime $displayEndDate
     *
     * @return Session
     */
    public function setDisplayEndDate($displayEndDate)
    {
        $this->displayEndDate = $displayEndDate;

        return $this;
    }

    /**
     * Get displayEndDate.
     *
     * @return \DateTime
     */
    public function getDisplayEndDate()
    {
        return $this->displayEndDate;
    }

    /**
     * Set accessStartDate.
     *
     * @param \DateTime $accessStartDate
     *
     * @return Session
     */
    public function setAccessStartDate($accessStartDate)
    {
        $this->accessStartDate = $accessStartDate;

        return $this;
    }

    /**
     * Get accessStartDate.
     *
     * @return \DateTime
     */
    public function getAccessStartDate()
    {
        return $this->accessStartDate;
    }

    /**
     * Set accessEndDate.
     *
     * @param \DateTime $accessEndDate
     *
     * @return Session
     */
    public function setAccessEndDate($accessEndDate)
    {
        $this->accessEndDate = $accessEndDate;

        return $this;
    }

    /**
     * Get accessEndDate.
     *
     * @return \DateTime
     */
    public function getAccessEndDate()
    {
        return $this->accessEndDate;
    }

    /**
     * Set coachAccessStartDate.
     *
     * @param \DateTime $coachAccessStartDate
     *
     * @return Session
     */
    public function setCoachAccessStartDate($coachAccessStartDate)
    {
        $this->coachAccessStartDate = $coachAccessStartDate;

        return $this;
    }

    /**
     * Get coachAccessStartDate.
     *
     * @return \DateTime
     */
    public function getCoachAccessStartDate()
    {
        return $this->coachAccessStartDate;
    }

    /**
     * Set coachAccessEndDate.
     *
     * @param \DateTime $coachAccessEndDate
     *
     * @return Session
     */
    public function setCoachAccessEndDate($coachAccessEndDate)
    {
        $this->coachAccessEndDate = $coachAccessEndDate;

        return $this;
    }

    /**
     * Get coachAccessEndDate.
     *
     * @return \DateTime
     */
    public function getCoachAccessEndDate()
    {
        return $this->coachAccessEndDate;
    }

    /**
     * @return User
     */
    public function getGeneralCoach()
    {
        return $this->generalCoach;
    }

    /**
     * @param $coach
     *
     * @return $this
     */
    public function setGeneralCoach($coach)
    {
        $this->generalCoach = $coach;

        return $this;
    }

    /**
     * @return mixed
     * @return SessionCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return array
     */
    public static function getStatusList(): array
    {
        return [
            self::VISIBLE => 'status_visible',
            self::READ_ONLY => 'status_read_only',
            self::INVISIBLE => 'status_invisible',
            self::AVAILABLE => 'status_available',
        ];
    }

    /**
     * Check if session is visible.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $now = new \Datetime('now');

        if ($now > $this->getAccessStartDate()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isActiveForStudent(): bool
    {
        $start = $this->getAccessStartDate();
        $end = $this->getAccessEndDate();

        return $this->compareDates($start, $end);
    }

    /**
     * @return bool
     */
    public function isActiveForCoach()
    {
        $start = $this->getCoachAccessStartDate();
        $end = $this->getCoachAccessEndDate();

        return $this->compareDates($start, $end);
    }

    /**
     * @param Course $course
     */
    public function addCourse(Course $course)
    {
        $entity = new SessionRelCourse();
        $entity->setCourse($course);
        $this->addCourses($entity);
    }

    /**
     * @return ArrayCollection
     */
    public function getUserCourseSubscriptions()
    {
        return $this->userCourseSubscriptions;
    }

    /**
     * @param ArrayCollection $userCourseSubscriptions
     *
     * @return $this
     */
    public function setUserCourseSubscriptions($userCourseSubscriptions)
    {
        $this->userCourseSubscriptions = new ArrayCollection();

        foreach ($userCourseSubscriptions as $item) {
            $this->addUserCourseSubscription($item);
        }

        return $this;
    }

    /**
     * @param SessionRelCourseRelUser $subscription
     */
    public function addUserCourseSubscription(SessionRelCourseRelUser $subscription)
    {
        $subscription->setSession($this);
        if (!$this->hasUserCourseSubscription($subscription)) {
            $this->userCourseSubscriptions[] = $subscription;
        }
    }

    /**
     * @param Course $course
     *
     * @return SessionRelCourse
     */
    public function getCourseSubscription(Course $course)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('course', $course)
        );

        /** @var SessionRelCourse $sessionCourse */
        $sessionCourse = $this->courses
            ->matching($criteria)
            ->current();

        return $sessionCourse;
    }

    /**
     * Add a user course subscription.
     * If user status in session is student, then increase number of course users.
     *
     * @param int    $status
     * @param User   $user
     * @param Course $course
     */
    public function addUserInCourse($status, User $user, Course $course)
    {
        $userRelCourseRelSession = new SessionRelCourseRelUser();
        $userRelCourseRelSession->setCourse($course);
        $userRelCourseRelSession->setUser($user);
        $userRelCourseRelSession->setSession($this);
        $userRelCourseRelSession->setStatus($status);
        $this->addUserCourseSubscription($userRelCourseRelSession);

        if ($status === self::STUDENT) {
            $sessionCourse = $this->getCourseSubscription($course);

            $sessionCourse->setNbrUsers(
                $sessionCourse->getNbrUsers() + 1
            );
        }
    }

    /**
     * @param SessionRelCourseRelUser $subscription
     *
     * @return bool
     */
    public function hasUserCourseSubscription(SessionRelCourseRelUser $subscription)
    {
        if ($this->getUserCourseSubscriptions()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('user', $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq('course', $subscription->getCourse())
            )->andWhere(
                Criteria::expr()->eq('session', $subscription->getSession())
            );
            $relation = $this->getUserCourseSubscriptions()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * currentCourse is set in CourseListener.
     *
     * @return Course
     */
    public function getCurrentCourse()
    {
        return $this->currentCourse;
    }

    /**
     * currentCourse is set in CourseListener.
     *
     * @param Course $course
     *
     * @return $this
     */
    public function setCurrentCourse(Course $course)
    {
        // If the session is registered in the course session list.
        $exists = $this->getCourses()->exists(
            function ($key, $element) use ($course) {
                /** @var SessionRelCourse $element */
                return $course->getId() == $element->getCourse()->getId();
            }
        );

        if ($exists) {
            $this->currentCourse = $course;
        }

        return $this;
    }

    /**
     * Set $sendSubscriptionNotification.
     *
     * @param bool $sendNotification
     *
     * @return Session
     */
    public function setSendSubscriptionNotification($sendNotification)
    {
        $this->sendSubscriptionNotification = $sendNotification;

        return $this;
    }

    /**
     * Get $sendSubscriptionNotification.
     *
     * @return bool
     */
    public function getSendSubscriptionNotification()
    {
        return $this->sendSubscriptionNotification;
    }

    /**
     * Get user from course by status.
     *
     * @param Course $course
     * @param int    $status
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getUserCourseSubscriptionsByStatus(Course $course, $status)
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('course', $course)
            )
            ->andWhere(
                Criteria::expr()->eq('status', $status)
            );

        return $this->userCourseSubscriptions->matching($criteria);
    }

    /**
     * @param Collection $studentPublications
     *
     * @return Session
     */
    public function setStudentPublications(Collection $studentPublications)
    {
        $this->studentPublications = new ArrayCollection();

        foreach ($studentPublications as $studentPublication) {
            $this->addStudentPublication($studentPublication);
        }

        return $this;
    }

    /**
     * @param CStudentPublication $studentPublication
     *
     * @return Session
     */
    public function addStudentPublication(CStudentPublication $studentPublication)
    {
        $this->studentPublications[] = $studentPublication;

        return $this;
    }

    /**
     * Get studentPublications.
     *
     * @return ArrayCollection
     */
    public function getStudentPublications()
    {
        return $this->studentPublications;
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
     * @param AccessUrlRelSession $url
     */
    public function addUrls(AccessUrlRelSession $url)
    {
        $url->setSession($this);
        $this->urls[] = $url;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     *
     * @return Session
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function isUserGeneralCoach(User $user): bool
    {
        $generalCoach = $this->getGeneralCoach();

        if (!$generalCoach) {
            return false;
        }

        if ($user->getId() === $generalCoach->getId()) {
            return true;
        }

        return false;
    }

    /**
     * Check if $user is course coach in any course.
     *
     * @param User $user
     *
     * @return bool
     */
    public function hasCoachInCourseList(User $user)
    {
        /** @var SessionRelCourse $sessionCourse */
        foreach ($this->courses as $sessionCourse) {
            if ($this->hasCoachInCourseWithStatus($user, $sessionCourse->getCourse())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if $user is student in any course.
     *
     * @param User $user
     *
     * @return bool
     */
    public function hasStudentInCourseList(User $user)
    {
        /** @var SessionRelCourse $sessionCourse */
        foreach ($this->courses as $sessionCourse) {
            if ($this->hasStudentInCourse($user, $sessionCourse->getCourse())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return bool
     */
    protected function compareDates($start, $end): bool
    {
        $now = new \Datetime('now');

        if (!empty($start) && !empty($end)) {
            if ($now >= $start && $now <= $end) {
                return true;
            }
        }

        if (!empty($start)) {
            if ($now >= $start) {
                return true;
            }
        }

        if (!empty($end)) {
            if ($now <= $end) {
                return true;
            }
        }

        return false;
    }
}
