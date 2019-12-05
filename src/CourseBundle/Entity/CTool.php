<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * CTool.
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *  name="c_tool",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CTool
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
     * @var bool
     *
     * @ORM\Column(name="visibility", type="boolean", nullable=true)
     */
    protected $visibility;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=20, nullable=false, options={"default" = "authoring"})
     */
    protected $category;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_icon", type="string", length=255, nullable=true)
     */
    protected $customIcon;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="tools")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected $course;

    /**
     * @var Session
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected $session;

    /**
     * @var Tool
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Tool")
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id", nullable=false)
     */
    protected $tool;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Default values
        $this->id = 0;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     *
     * @return CTool
     */
    public function setIid($iid)
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * @return Session
     */
    public function getSession(): ?Session
    {
        return $this->session;
    }

    /**
     * @param Session $session
     *
     * @return CTool
     */
    public function setSession(Session $session = null): CTool
    {
        $this->session = $session;

        return $this;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        /*$metadata->addPropertyConstraint(
            'customIcon',
            new Assert\File(['mimeTypes' => ['image/png']])
        );
        $metadata->addPropertyConstraint(
            'customIcon',
            new Assert\Image(['maxWidth' => 64, 'minHeight' => 64])
        );
        $metadata->addPropertyConstraint('cId', new Assert\NotBlank());*/
    }

    /**
     * Set visibility.
     *
     * @param bool $visibility
     *
     * @return CTool
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
     * Set category.
     *
     * @param string $category
     *
     * @return CTool
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CTool
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
     * @return string
     */
    public function getCustomIcon()
    {
        return $this->customIcon;
    }

    /**
     * @param string $customIcon
     *
     * @return CTool
     */
    public function setCustomIcon($customIcon)
    {
        $this->customIcon = $customIcon;

        return $this;
    }

    /**
     * @return Tool
     */
    public function getTool(): Tool
    {
        return $this->tool;
    }

    /**
     * @param Tool $tool
     *
     * @return CTool
     */
    public function setTool(Tool $tool): CTool
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        // Update id with iid value
        $em = $args->getEntityManager();
        $this->setId($this->iid);
        $em->persist($this);
        $em->flush($this);
    }
}
