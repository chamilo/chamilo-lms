<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Database;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;

/**
 * CLink.
 *
 * @ORM\Table(
 *  name="c_link",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CLink
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="text", nullable=false)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=150, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    protected $categoryId;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    protected $displayOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="on_homepage", type="string", length=10, nullable=false)
     */
    protected $onHomepage;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=10, nullable=true)
     */
    protected $target;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var Course
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="links")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    public function __construct()
    {
        $this->description = '';
        $this->categoryId = 0;
        $this->displayOrder = 0;
        $this->onHomepage = '0';
        $this->target = '_self';
        $this->sessionId = 0;
    }

    /**
     * @return EntityRepository
     */
    public static function getRepository()
    {
        return Database::getManager()->getRepository('ChamiloCourseBundle:CLink');
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
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;
        $this->course->getLinks()->add($this);

        return $this;
    }

    /**
     * @ORM\PostPersist
     *
     * @throws OptimisticLockException
     */
    public function postPersist()
    {
        if (is_null($this->id)) {
            $this->id = $this->iid;
            Database::getManager()->persist($this);
            Database::getManager()->flush($this);
        }
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return CLink
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CLink
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
     * Set description.
     *
     * @param string $description
     *
     * @return CLink
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
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return CLink
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CLink
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set onHomepage.
     *
     * @param string $onHomepage
     *
     * @return CLink
     */
    public function setOnHomepage($onHomepage)
    {
        $this->onHomepage = $onHomepage;

        return $this;
    }

    /**
     * Get onHomepage.
     *
     * @return string
     */
    public function getOnHomepage()
    {
        return $this->onHomepage;
    }

    /**
     * Set target.
     *
     * @param string $target
     *
     * @return CLink
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CLink
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CLink
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
     * Set cId.
     *
     * @deprecated use setCourse wherever possible
     *
     * @param int $cId
     *
     * @return CLink
     */
    public function setCId($cId)
    {
        $this->cId = $cId;
        $this->setCourse(api_get_course_entity($cId));

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
