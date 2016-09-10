<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Chamilo\CoreBundle\Entity\SessionCategory;

//use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
//use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Session
 * UniqueEntity("name")
 * @ORM\Table(
 *      name="session",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})},
 *      indexes={
 *          @ORM\Index(name="idx_id_coach", columns={"id_coach"}),
 *          @ORM\Index(name="idx_id_session_admin_id", columns={"session_admin_id"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\SessionRepository")
 */
class Session
{
    const VISIBLE = 1;
    const READ_ONLY = 2;
    const INVISIBLE = 3;
    const AVAILABLE = 4;

    const STUDENT = 0;
    const DRH = 1;
    const COACH = 2;

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
     * @ORM\Column(name="name", type="string", length=150, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="show_description", type="boolean", nullable=true)
     */
    private $showDescription;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbr_courses", type="smallint", nullable=true, unique=false)
     */
    private $nbrCourses;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbr_users", type="integer", nullable=true, unique=false)
     */
    private $nbrUsers;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbr_classes", type="integer", nullable=true, unique=false)
     */
    private $nbrClasses;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_admin_id", type="integer", nullable=true, unique=false)
     */
    private $sessionAdminId;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="promotion_id", type="integer", nullable=true, unique=false)
     */
    private $promotionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_start_date", type="datetime", nullable=true, unique=false)
     */
    private $displayStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_end_date", type="datetime", nullable=true, unique=false)
     */
    private $displayEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_start_date", type="datetime", nullable=true, unique=false)
     */
    private $accessStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_end_date", type="datetime", nullable=true, unique=false)
     */
    private $accessEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="coach_access_start_date", type="datetime", nullable=true, unique=false)
     */
    private $coachAccessStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="coach_access_end_date", type="datetime", nullable=true, unique=false)
     */
    private $coachAccessEndDate;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CItemProperty", mappedBy="session")
     **/
    //private $items;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="sessionAsGeneralCoach")
     * @ORM\JoinColumn(name="id_coach", referencedColumnName="id")
     **/
    private $generalCoach;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SessionCategory", inversedBy="session")
     * @ORM\JoinColumn(name="session_category_id", referencedColumnName="id")
     **/
    private $category;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     **/
    protected $courses;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="SessionRelUser", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     **/
    protected $users;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="SessionRelCourseRelUser", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     **/
    protected $userCourseSubscriptions;

    /**
     * @var Course
     **/
    protected $currentCourse;

    /**
     * @var boolean
     * @ORM\Column(name="send_subscription_notification", type="boolean", nullable=false, options={"default":false})
     */
    private $sendSubscriptionNotification;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CStudentPublication", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     */
    private $studentPublications;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();

        $this->nbrClasses = 0;
        $this->nbrUsers = 0;
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
        $this->showDescription = 0;
        $this->category = null;
        $this->studentPublications = new ArrayCollection();
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
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
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
     */
    public function setShowDescription($showDescription)
    {
        $this->showDescription = $showDescription;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
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
     */
    public function setUsers($users)
    {
        $this->users = new ArrayCollection();

        foreach ($users as $user) {
            $this->addUser($user);
        }
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
     * @param int $status
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
     * @return bool
     */
    public function hasUser(SessionRelUser $subscription)
    {
        if ($this->getUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq("user", $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq("session", $subscription->getSession())
            )->andWhere(
                Criteria::expr()->eq("relationType", $subscription->getRelationType())
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
                Criteria::expr()->eq("course", $course)
            );
            $relation = $this->getCourses()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }


    /**
     * Remove $course
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
     * @param User $user
     * @param Course $course
     * @param int $status if not set it will check if the user is registered
     * with any status
     *
     * @return bool
     */
    public function hasUserInCourse(User $user, Course $course, $status = null)
    {
        $relation = $this->getUserInCourse($user, $course, $status);

        return $relation->count() > 0;
    }

    /**
     * @param User $user
     * @param Course $course
     *
     * @return bool
     */
    public function hasStudentInCourse(User $user, Course $course)
    {
        return $this->hasUserInCourse($user, $course, self::STUDENT);
    }

    /**
     * @param User $user
     * @param Course $course
     *
     * @return bool
     */
    public function hasCoachInCourseWithStatus(User $user, Course $course)
    {
        return $this->hasUserInCourse($user, $course, self::COACH);
    }

    /**
     * @param User $user
     * @param Course $course
     * @param string $status
     *
     * @return \Doctrine\Common\Collections\Collection|static
     */
    public function getUserInCourse(User $user, Course $course, $status = null)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("course", $course)
        )->andWhere(
            Criteria::expr()->eq("user", $user)
        );

        if (!is_null($status))  {
            $criteria->andWhere(
                Criteria::expr()->eq("status", $status)
            );
        }

        return $this->getUserCourseSubscriptions()->matching($criteria);
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Session
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Groups
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
     * Set nbrCourses
     *
     * @param integer $nbrCourses
     * @return Session
     */
    public function setNbrCourses($nbrCourses)
    {
        $this->nbrCourses = $nbrCourses;

        return $this;
    }

    /**
     * Get nbrCourses
     *
     * @return integer
     */
    public function getNbrCourses()
    {
        return $this->nbrCourses;
    }

    /**
     * Set nbrUsers
     *
     * @param integer $nbrUsers
     * @return Session
     */
    public function setNbrUsers($nbrUsers)
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }

    /**
     * Get nbrUsers
     *
     * @return integer
     */
    public function getNbrUsers()
    {
        return $this->nbrUsers;
    }

    /**
     * Set nbrClasses
     *
     * @param integer $nbrClasses
     * @return Session
     */
    public function setNbrClasses($nbrClasses)
    {
        $this->nbrClasses = $nbrClasses;

        return $this;
    }

    /**
     * Get nbrClasses
     *
     * @return integer
     */
    public function getNbrClasses()
    {
        return $this->nbrClasses;
    }

    /**
     * Set sessionAdminId
     *
     * @param integer $sessionAdminId
     * @return Session
     */
    public function setSessionAdminId($sessionAdminId)
    {
        $this->sessionAdminId = $sessionAdminId;

        return $this;
    }

    /**
     * Get sessionAdminId
     *
     * @return integer
     */
    public function getSessionAdminId()
    {
        return $this->sessionAdminId;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     * @return Session
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set promotionId
     *
     * @param integer $promotionId
     * @return Session
     */
    public function setPromotionId($promotionId)
    {
        $this->promotionId = $promotionId;

        return $this;
    }

    /**
     * Get promotionId
     *
     * @return integer
     */
    public function getPromotionId()
    {
        return $this->promotionId;
    }

    /**
     * Set displayStartDate
     *
     * @param \DateTime $displayStartDate
     * @return Session
     */
    public function setDisplayStartDate($displayStartDate)
    {
        $this->displayStartDate = $displayStartDate;

        return $this;
    }

    /**
     * Get displayStartDate
     *
     * @return \DateTime
     */
    public function getDisplayStartDate()
    {
        return $this->displayStartDate;
    }

    /**
     * Set displayEndDate
     *
     * @param \DateTime $displayEndDate
     * @return Session
     */
    public function setDisplayEndDate($displayEndDate)
    {
        $this->displayEndDate = $displayEndDate;

        return $this;
    }

    /**
     * Get displayEndDate
     *
     * @return \DateTime
     */
    public function getDisplayEndDate()
    {
        return $this->displayEndDate;
    }

    /**
     * Set accessStartDate
     *
     * @param \DateTime $accessStartDate
     * @return Session
     */
    public function setAccessStartDate($accessStartDate)
    {
        $this->accessStartDate = $accessStartDate;

        return $this;
    }

    /**
     * Get accessStartDate
     *
     * @return \DateTime
     */
    public function getAccessStartDate()
    {
        return $this->accessStartDate;
    }

    /**
     * Set accessEndDate
     *
     * @param \DateTime $accessEndDate
     * @return Session
     */
    public function setAccessEndDate($accessEndDate)
    {
        $this->accessEndDate = $accessEndDate;

        return $this;
    }

    /**
     * Get accessEndDate
     *
     * @return \DateTime
     */
    public function getAccessEndDate()
    {
        return $this->accessEndDate;
    }

    /**
     * Set coachAccessStartDate
     *
     * @param \DateTime $coachAccessStartDate
     * @return Session
     */
    public function setCoachAccessStartDate($coachAccessStartDate)
    {
        $this->coachAccessStartDate = $coachAccessStartDate;

        return $this;
    }

    /**
     * Get coachAccessStartDate
     *
     * @return \DateTime
     */
    public function getCoachAccessStartDate()
    {
        return $this->coachAccessStartDate;
    }

    /**
     * Set coachAccessEndDate
     *
     * @param \DateTime $coachAccessEndDate
     * @return Session
     */
    public function setCoachAccessEndDate($coachAccessEndDate)
    {
        $this->coachAccessEndDate = $coachAccessEndDate;

        return $this;
    }

    /**
     * Get coachAccessEndDate
     *
     * @return \DateTime
     */
    public function getCoachAccessEndDate()
    {
        return $this->coachAccessEndDate;
    }

    /**
     * Get id
     * @return User
     */
    public function getGeneralCoach()
    {
        return $this->generalCoach;
    }

    /**
     * @param $coach
     */
    public function setGeneralCoach($coach)
    {
        $this->generalCoach = $coach;
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
    public static function getStatusList()
    {
        return array(
            self::VISIBLE => 'status_visible',
            self::READ_ONLY => 'status_read_only',
            self::INVISIBLE => 'status_invisible',
            self::AVAILABLE => 'status_available',
        );
    }

    /**
     * Check if session is visible
     * @return bool
     */
    public function isActive()
    {
        $now = new \Datetime('now');

        if ($now > $this->getAccessStartDate()) {

            return true;
        }

        return false;
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
     */
    public function setUserCourseSubscriptions($userCourseSubscriptions)
    {
        $this->userCourseSubscriptions = new ArrayCollection();

        foreach ($userCourseSubscriptions as $item) {
            $this->addUserCourseSubscription($item);
        }
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
     * @param int $status
     * @param User $user
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
    }

    /**
     * @param SessionRelCourseRelUser $subscription
     * @return bool
     */
    public function hasUserCourseSubscription(SessionRelCourseRelUser $subscription)
    {
        if ($this->getUserCourseSubscriptions()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq("user", $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq("course", $subscription->getCourse())
            )->andWhere(
                Criteria::expr()->eq("session", $subscription->getSession())
            );
            $relation = $this->getUserCourseSubscriptions()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * @return Course
     */
    public function getCurrentCourse()
    {
        return $this->currentCourse;
    }

    /**
     * @param Course $course
     * @return $this
     */
    public function setCurrentCourse(Course $course)
    {
        // If the session is registered in the course session list.
        if ($this->getCourses()->contains($course->getId())) {
            $this->currentCourse = $course;
        }
        return $this;
    }

    /**
     * Set $sendSubscriptionNotification
     * @param boolean $sendNotification
     * @return \Chamilo\CoreBundle\Entity\Session
     */
    public function setSendSubscriptionNotification($sendNotification)
    {
        $this->sendSubscriptionNotification = $sendNotification;

        return $this;
    }

    /**
     * Get $sendSubscriptionNotification
     * @return boolean
     */
    public function getSendSubscriptionNotification()
    {
        return $this->sendSubscriptionNotification;
    }

    /**
     * Get user from course by status
     * @param \Chamilo\CoreBundle\Entity\Course $course
     * @param string $status
     * @return \Doctrine\Common\Collections\Collection|static
     */
    public function getUserCourseSubscriptionsByStatus(Course $course, $status)
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq("course", $course)
            )
            ->andWhere(
                Criteria::expr()->eq("status", $status)
            );

        return $this->userCourseSubscriptions->matching($criteria);
    }

    public function getBuyCoursePluginPrice()
    {
        // start buycourse validation
        // display the course price and buy button if the buycourses plugin is enabled and this course is configured
        $plugin = \BuyCoursesPlugin::create();
        $isThisCourseInSale = $plugin->buyCoursesForGridCatalogVerificator($this->id, \BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
        $return = [];

        if ($isThisCourseInSale) {
            // set the Price label
            $return['html'] = $isThisCourseInSale['html'];
            // set the Buy button instead register.
            if ($isThisCourseInSale['verificator']) {
                $return['buy_button'] = $plugin->returnBuyCourseButton($this->id, \BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
            }
        }
        // end buycourse validation
        return $return;
    }

    /**
     * @param ArrayCollection $studentPublications
     * @return Session
     */
    public function setStudentPublications(ArrayCollection $studentPublications)
    {
        $this->studentPublications = new ArrayCollection();

        foreach ($studentPublications as $studentPublication) {
            $this->addStudentPublication($studentPublication);
        }

        return $this;
    }

    /**
     * @param CStudentPublication $studentPublication
     * @return Session
     */
    public function addStudentPublication(CStudentPublication $studentPublication)
    {
        $this->studentPublications[] = $studentPublication;

        return $this;
    }

    /**
     * Get studentPublications
     * @return ArrayCollection
     */
    public function getStudentPublications()
    {
        return $this->studentPublications;
    }
}
