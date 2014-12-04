<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Session
 * @UniqueEntity("name")
 * @ORM\Table(name="session", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})}, indexes={@ORM\Index(name="idx_id_coach", columns={"id_coach"}), @ORM\Index(name="idx_id_session_admin_id", columns={"session_admin_id"})})
 * @ORM\Entity
 */
class Session
{
    const VISIBLE = 1;
    const READ_ONLY = 2;
    const INVISIBLE = 3;
    const AVAILABLE = 4;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=150, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbr_courses", type="smallint", precision=0, scale=0, nullable=true, unique=false)
     */
    private $nbrCourses;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbr_users", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $nbrUsers;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbr_classes", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $nbrClasses;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_admin_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionAdminId;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="promotion_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $promotionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_end_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_end_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessEndDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="coach_access_start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $coachAccessStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="coach_access_end_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
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
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     **/
    protected $courses;

    /**
     * @ORM\OneToMany(targetEntity="SessionRelUser", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     **/
    protected $users;

    /**
     *
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

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return
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
        $this->users[] = $user;
    }

    /**
     * @return
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
     * Remove $user
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
     * Set sessionCategoryId
     *
     * @param integer $sessionCategoryId
     * @return Session
     */
    public function setSessionCategoryId($sessionCategoryId)
    {
        $this->sessionCategoryId = $sessionCategoryId;

        return $this;
    }

    /**
     * Get sessionCategoryId
     *
     * @return integer
     */
    public function getSessionCategoryId()
    {
        return $this->sessionCategoryId;
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
     *
     * @return integer
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

    public function getCategory()
    {
        return $this->category;
    }

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
}
