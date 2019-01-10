<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource_link")
 */
class ResourceLink implements ResourceInterface
{
    public const VISIBILITY_DRAFT = 0;
    public const VISIBILITY_PENDING = 1;
    public const VISIBILITY_PUBLISHED = 2;
    public const VISIBILITY_DELETED = 3;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id")
     */
    protected $resourceNode;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected $session;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=true)
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup")
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id", nullable=true)
     */
    protected $userGroup;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceRight",
     *     mappedBy="resourceLink", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $resourceRight;

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false)
     */
    protected $visibility;

    /**
     * @ORM\Column(name="start_visibility_at", type="datetime", nullable=true)
     */
    protected $startVisibilityAt;

    /**
     * @ORM\Column(name="end_visibility_at", type="datetime", nullable=true)
     */
    protected $endVisibilityAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->resourceRight = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * @return mixed
     */
    public function getStartVisibilityAt()
    {
        return $this->startVisibilityAt;
    }

    /**
     * @param mixed $startVisibilityAt
     *
     * @return ResourceLink
     */
    public function setStartVisibilityAt($startVisibilityAt)
    {
        $this->startVisibilityAt = $startVisibilityAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEndVisibilityAt()
    {
        return $this->endVisibilityAt;
    }

    /**
     * @param mixed $endVisibilityAt
     *
     * @return ResourceLink
     */
    public function setEndVisibilityAt($endVisibilityAt)
    {
        $this->endVisibilityAt = $endVisibilityAt;

        return $this;
    }

    /**
     * @param ArrayCollection $rights
     *
     * @return $this
     */
    public function setResourceRight($rights)
    {
        $this->resourceRight = $rights;

        /*foreach ($rights as $right) {
            $this->addResourceRight($right);
        }*/

        return $this;
    }

    /**
     * @param ResourceRight $right
     *
     * @return $this
     */
    public function addResourceRight(ResourceRight $right)
    {
        $right->setResourceLink($this);
        $this->resourceRight[] = $right;

        return $this;
    }

    public function getResourceRight()
    {
        return $this->resourceRight;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param Course $course
     *
     * @return $this
     */
    public function setCourse(Course $course = null)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @param Session $session
     *
     * @return $this
     */
    public function setSession(Session $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return CGroupInfo
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param CGroupInfo $group
     *
     * @return $this
     */
    public function setGroup(CGroupInfo $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return Usergroup
     */
    public function getUserGroup()
    {
        return $this->userGroup;
    }

    /**
     * @param Usergroup $group
     *
     * @return $this
     */
    public function setUserGroup(Usergroup $group = null)
    {
        $this->userGroup = $group;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get course.
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Get session.
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param ResourceNode $resourceNode
     *
     * @return $this
     */
    public function setResourceNode(ResourceNode $resourceNode)
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    /**
     * @return ResourceNode
     */
    public function getResourceNode()
    {
        return $this->resourceNode;
    }

    /**
     * @return int
     */
    public function getVisibility(): int
    {
        return $this->visibility;
    }

    /**
     * @param int $visibility
     *
     * @return ResourceLink
     */
    public function setVisibility(int $visibility): ResourceLink
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return $this
     */
    public function getResource()
    {
        return $this;
    }

    /**
     * @return array
     */
    public static function getVisibilityList(): array
    {
        return [
            'Draft' => self::VISIBILITY_DRAFT,
            'Pending' => self::VISIBILITY_PENDING,
            'Published' => self::VISIBILITY_PUBLISHED,
            'Deleted' => self::VISIBILITY_DELETED,
        ];
    }
}
