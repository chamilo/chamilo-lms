<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"}
 * )
 * @ORM\Entity
 * @ORM\Table(name="resource_link")
 */
class ResourceLink
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
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $resourceNode;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=true)
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected $session;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    protected $user;

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

    public function getStartVisibilityAt()
    {
        return $this->startVisibilityAt;
    }

    /**
     * @return ResourceLink
     */
    public function setStartVisibilityAt($startVisibilityAt)
    {
        $this->startVisibilityAt = $startVisibilityAt;

        return $this;
    }

    public function getEndVisibilityAt()
    {
        return $this->endVisibilityAt;
    }

    /**
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
     * @return $this
     */
    public function addResourceRight(ResourceRight $right)
    {
        $right->setResourceLink($this);
        $this->resourceRight[] = $right;

        return $this;
    }

    /**
     * @return ArrayCollection|ResourceRight[]
     */
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

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        if (!in_array($visibility, self::getVisibilityList())) {
            throw new \LogicException('The visibility is not valid');
        }

        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return self::VISIBILITY_PUBLISHED === $this->getVisibility();
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return self::VISIBILITY_PENDING === $this->getVisibility();
    }

    /**
     * @return bool
     */
    public function isDraft()
    {
        return self::VISIBILITY_DRAFT === $this->getVisibility();
    }

    public static function getVisibilityList(): array
    {
        return [
            'Draft' => self::VISIBILITY_DRAFT,
            'Pending' => self::VISIBILITY_PENDING,
            'Published' => self::VISIBILITY_PUBLISHED,
            'Deleted' => self::VISIBILITY_DELETED,
        ];
    }

    /**
     * @return string
     */
    public function getVisibilityName()
    {
        return array_flip($this->getVisibilityList())[$this->getVisibility()];
    }
}
