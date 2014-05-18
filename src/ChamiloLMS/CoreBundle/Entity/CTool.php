<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * CTool
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="c_tool", indexes={@ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class CTool
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $link;

    /**
     * @var string
     * @ORM\Column(name="image", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $image;

    /**
     * @var string
     * @ORM\Column(name="custom_icon", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $customIcon;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visibility", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $visibility;

    /**
     * @var string
     *
     * @ORM\Column(name="admin", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $admin;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $address;

    /**
     * @var boolean
     *
     * @ORM\Column(name="added_tool", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $addedTool;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", precision=0, scale=0, nullable=false, unique=false)
     */
    private $target;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $category;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    private $course;

    protected $originalImage;

    /**
     */
    public function __construct()
    {
    }

    /**
     * @param Course $course
     */
    public function setCourse($course)
    {
        $this->course = $course;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'customIcon',
            new Assert\File(array('mimeTypes' => array("image/png")))
        );
        $metadata->addPropertyConstraint(
            'customIcon',
            new Assert\Image(array('maxWidth' => 64, 'minHeight' => 64))
        );
        $metadata->addPropertyConstraint('cId', new Assert\NotBlank());
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @return null|string
     */
    public function getAbsolutePath()
    {
        return null === $this->getCustomIcon()
            ? null
            : $this->getUploadRootDir().'/'.$this->getCustomIcon();
    }

    /**
     * @return string
     */
    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        $dir = $this->getCourse()->getAbsoluteSysCoursePath().$this->getUploadDir();

        if (is_dir($dir)) {
            return $dir;
        } else {
            mkdir($dir);
            return $dir;
        }
    }

    /**
     * @return string
     */
    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'upload/course_home_icons';
    }

    /**
     * Called before saving the entity
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->getCustomIcon()) {

            // do whatever you want to generate a unique name
            //$filename = sha1(uniqid(mt_rand(), true));
            $this->originalImage = $this->getCustomIcon();
            $this->customIcon = $this->getName().'_'.$this->getSessionId().'.'.$this->getCustomIcon()->guessExtension();
        }
    }

    /**
     * Called before entity removal
     *
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    /**
     * Called after entity persistence
     *
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->getCustomIcon()) {
            return;
        }

        // use the original file name here but you should
        // sanitize it at least to avoid any security issues

        // move takes the target directory and then the
        // target filename to move to
        $this->originalImage->move(
            $this->getUploadRootDir(),
            $this->customIcon
        );

        // clean up the file property as you won't need it anymore
        $this->originalImage = null;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CTool
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CTool
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set name
     *
     * @param string $name
     * @return CTool
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
     * Set link
     *
     * @param string $link
     * @return CTool
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return CTool
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set visibility
     *
     * @param boolean $visibility
     * @return CTool
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set admin
     *
     * @param string $admin
     * @return CTool
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return string
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return CTool
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set addedTool
     *
     * @param boolean $addedTool
     * @return CTool
     */
    public function setAddedTool($addedTool)
    {
        $this->addedTool = $addedTool;

        return $this;
    }

    /**
     * Get addedTool
     *
     * @return boolean
     */
    public function getAddedTool()
    {
        return $this->addedTool;
    }

    /**
     * Set target
     *
     * @param string $target
     * @return CTool
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return CTool
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CTool
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
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
     * @return $this
     */
    public function setCustomIcon($customIcon)
    {
        $this->customIcon = $customIcon;
        return $this;
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
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Creates a gray icon.
     * @param \Imagine\Image\ImagineInterface $imagine
     * @return bool
     */
    public function createGrayIcon($imagine)
    {
        $customIcon = $this->getCustomIcon();
        if (empty($customIcon)) {
            return false;
        }
        if (file_exists($this->getAbsolutePath())) {
            $image = $imagine->open($this->getAbsolutePath());
            $fileInfo = pathinfo($this->getAbsolutePath());
            $originalFilename = $fileInfo['basename'];
            $filename = $fileInfo['filename'].'_na.'.$fileInfo['extension'];
            $newPath = str_replace($originalFilename, $filename, $this->getAbsolutePath());
            $transformation = new \Imagine\Filter\Advanced\Grayscale();
            $transformation->apply($image)->save($newPath);
        }
    }

    /**
     * Replace the $this->image png extension to gif
     * @return string
     */
    public function imageGifToPng()
    {
        return str_replace('.gif', '.png', $this->getImage());
    }
}
