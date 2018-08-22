<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
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
    protected $originalImage;

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
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=false)
     */
    protected $link;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    protected $image;

    /**
     * @var bool
     *
     * @ORM\Column(name="visibility", type="boolean", nullable=true)
     */
    protected $visibility;

    /**
     * @var string
     *
     * @ORM\Column(name="admin", type="string", length=255, nullable=true)
     */
    protected $admin;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    protected $address;

    /**
     * @var bool
     *
     * @ORM\Column(name="added_tool", type="boolean", nullable=true)
     */
    protected $addedTool;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=20, nullable=false)
     */
    protected $target;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=20, nullable=false, options={"default" = "authoring"})
     */
    protected $category;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

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
     * Constructor.
     */
    public function __construct()
    {
        // Default values
        $this->id = 0;
        $this->sessionId = 0;
        $this->address = 'squaregrey.gif';
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
     * @param Course $course
     *
     * @return $this
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return Course
     */
    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'customIcon',
            new Assert\File(['mimeTypes' => ['image/png']])
        );
        $metadata->addPropertyConstraint(
            'customIcon',
            new Assert\Image(['maxWidth' => 64, 'minHeight' => 64])
        );
        $metadata->addPropertyConstraint('cId', new Assert\NotBlank());
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CTool
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
     * Set link.
     *
     * @param string $link
     *
     * @return CTool
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link.
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set image.
     *
     * @param string $image
     *
     * @return CTool
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
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
     * Set admin.
     *
     * @param string $admin
     *
     * @return CTool
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin.
     *
     * @return string
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set address.
     *
     * @param string $address
     *
     * @return CTool
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set addedTool.
     *
     * @param bool $addedTool
     *
     * @return CTool
     */
    public function setAddedTool($addedTool)
    {
        $this->addedTool = $addedTool;

        return $this;
    }

    /**
     * Get addedTool.
     *
     * @return bool
     */
    public function getAddedTool()
    {
        return $this->addedTool;
    }

    /**
     * Set target.
     *
     * @param string $target
     *
     * @return CTool
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
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CTool
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return CTool
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
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
