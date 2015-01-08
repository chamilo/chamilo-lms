<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Chamilo\CourseBundle\Entity\CGroupInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Chamilo\UserBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource_link")
 */
class ResourceLink
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode")
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id")
     */
    protected $resourceNode;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     **/
    protected $session;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=true)
     */
    protected $group;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceRights", mappedBy="resourceLink", cascade={"remove"})
     **/
    protected $rights;

    /**
     * @var boolean
     *
     * @ORM\Column(name="private", type="boolean", nullable=true, unique=false)
     */
    protected $private;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=true, unique=false)
     */
    protected $public;

    /**
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * @param boolean $private
     */
    public function setPrivate($private)
    {
        $this->private = $private;
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param boolean $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return ArrayCollection
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * @param ArrayCollection $rights
     * @return $this
     */
    public function setRights($rights)
    {
        $this->rights = $rights;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param Course $course
     * @return $this
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @param Session $session
     * @return $this
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @param CGroupInfo $group
     * @return $this
     */
    public function setGroup(CGroupInfo $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get course
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Get session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param ResourceNode $resourceNode
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
}
