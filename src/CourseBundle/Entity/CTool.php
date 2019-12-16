<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

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
 * @GRID\Source(columns="iid, name, resourceNode.createdAt", filterable=false, groups={"resource"})
 */
class CTool extends AbstractResource implements ResourceInterface
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
     * @Assert\NotBlank
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

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
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Default values
        $this->id = 0;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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
     */
    public function setSession(Session $session = null): self
    {
        $this->session = $session;

        return $this;
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

    public function getTool(): Tool
    {
        return $this->tool;
    }

    public function setTool(Tool $tool): self
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

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     *
     * @return CTool
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return (string) $this->getName();
    }
}
