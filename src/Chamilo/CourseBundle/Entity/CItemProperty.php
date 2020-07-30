<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;
use Database;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;
use Exception;

/**
 * CItemProperty.
 *
 * @ORM\Table(name="c_item_property", indexes={@ORM\Index(name="idx_item_property_toolref", columns={"tool", "ref"})})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class CItemProperty
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Course",
     *     inversedBy="itemProperties",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=true)
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo", cascade={"persist"})
     * @ORM\JoinColumn(name="to_group_id", referencedColumnName="iid")
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="to_user_id", referencedColumnName="id")
     */
    protected $toUser;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="insert_user_id", referencedColumnName="id")
     */
    protected $insertUser;

    /**
     * @var Session
     *
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Session",
     *     inversedBy="itemProperties",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected $session;

    /**
     * @var string
     *
     * @ORM\Column(name="tool", type="string", length=100, nullable=false)
     */
    protected $tool;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="insert_date", type="datetime", nullable=false)
     */
    protected $insertDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="lastedit_date", type="datetime", nullable=false)
     */
    protected $lasteditDate;

    /**
     * @var int
     *
     * @ORM\Column(name="ref", type="integer", nullable=false)
     */
    protected $ref;

    /**
     * @var string
     *
     * @ORM\Column(name="lastedit_type", type="string", length=100, nullable=false)
     */
    protected $lasteditType;

    /**
     * @var int
     *
     * @ORM\Column(name="lastedit_user_id", type="integer", nullable=false)
     */
    protected $lasteditUserId;

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false)
     */
    protected $visibility;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="start_visible", type="datetime", nullable=true)
     */
    protected $startVisible;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="end_visible", type="datetime", nullable=true)
     */
    protected $endVisible;

    /**
     * CItemProperty constructor.
     *
     * @param Course $course
     *
     * @throws Exception
     */
    public function __construct($course)
    {
        $this->visibility = 1;
        $this->setCourse($course);
        $this->insertDate = new DateTime('now', new DateTimeZone('UTC'));
        $this->lasteditDate = new DateTime('now', new DateTimeZone('UTC'));
        $this->lasteditUserId = api_get_user_id() ?: api_get_anonymous_id();
    }

    /**
     * @return ItemPropertyRepository|EntityRepository
     */
    public static function getRepository()
    {
        return Database::getManager()->getRepository('ChamiloCourseBundle:CItemProperty');
    }

    /**
     * @ORM\PreUpdate
     *
     * @throws Exception
     */
    public function preUpdate()
    {
        $this->lasteditDate = new DateTime('now', new DateTimeZone('UTC'));
    }

    /**
     * Copies iid to id if not set yet.
     *
     * @ORM\PostPersist
     *
     * @throws OptimisticLockException
     */
    public function postPersist()
    {
        if (is_null($this->id)) {
            $this->id = $this->iid;
            Database::getManager()->persist($this);
            Database::getManager()->flush();
        }
    }

    /**
     * Set tool.
     *
     * @param string $tool
     *
     * @return CItemProperty
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * Get tool.
     *
     * @return string
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * Set insertDate.
     *
     * @param DateTime $insertDate
     *
     * @return CItemProperty
     */
    public function setInsertDate($insertDate)
    {
        $this->insertDate = $insertDate;

        return $this;
    }

    /**
     * Get insertDate.
     *
     * @return DateTime
     */
    public function getInsertDate()
    {
        return $this->insertDate;
    }

    /**
     * Set lasteditDate.
     *
     * @param DateTime $lasteditDate
     *
     * @return CItemProperty
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate.
     *
     * @return DateTime
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set ref.
     *
     * @param int $ref
     *
     * @return CItemProperty
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Get ref.
     *
     * @return int
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set lasteditType.
     *
     * @param string $lasteditType
     *
     * @return CItemProperty
     */
    public function setLasteditType($lasteditType)
    {
        $this->lasteditType = $lasteditType;

        return $this;
    }

    /**
     * Get lasteditType.
     *
     * @return string
     */
    public function getLasteditType()
    {
        return $this->lasteditType;
    }

    /**
     * Set lasteditUserId.
     *
     * @param int $lasteditUserId
     *
     * @return CItemProperty
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId.
     *
     * @return int
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set visibility.
     *
     * @param int $visibility
     *
     * @return CItemProperty
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
     * Set startVisible.
     *
     * @param DateTime $startVisible
     *
     * @return CItemProperty
     */
    public function setStartVisible(DateTime $startVisible = null)
    {
        $this->startVisible = $startVisible;

        return $this;
    }

    /**
     * Get startVisible.
     *
     * @return DateTime
     */
    public function getStartVisible()
    {
        return $this->startVisible;
    }

    /**
     * Set endVisible.
     *
     * @param DateTime $endVisible
     *
     * @return CItemProperty
     */
    public function setEndVisible(DateTime $endVisible = null)
    {
        $this->endVisible = $endVisible;

        return $this;
    }

    /**
     * Get endVisible.
     *
     * @return DateTime
     */
    public function getEndVisible()
    {
        return $this->endVisible;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CItemProperty
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
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     *
     * @return CItemProperty
     */
    public function setSession($session)
    {
        $this->session = $session;
        if (!is_null($session)) {
            $session->getItemProperties()->add($this);
        }

        return $this;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     *
     * @return CItemProperty
     */
    public function setCourse($course)
    {
        $this->course = $course;
        $this->course->getItemProperties()->add($this);

        return $this;
    }

    /**
     * @return CgroupInfo
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param CgroupInfo $group
     *
     * @return CItemProperty
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return User
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * @param User $toUser
     *
     * @return $this
     */
    public function setToUser($toUser)
    {
        $this->toUser = $toUser;

        return $this;
    }

    /**
     * @return User
     */
    public function getInsertUser()
    {
        return $this->insertUser;
    }

    /**
     * @param User $insertUser
     *
     * @return $this
     */
    public function setInsertUser($insertUser)
    {
        $this->insertUser = $insertUser;
        $this->lasteditUserId = $insertUser->getId();

        return $this;
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
