<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Chamilo\MediaBundle\Entity\Media;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="resource_file")
 */
class ResourceFile
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\MediaBundle\Entity\Media", cascade={"all"})
     */
    protected $media;

//    /**
//     * @var string
//     *
//     * @Assert\NotBlank()
//     *
//     * @ORM\Column(name="hash", type="string", nullable=false)
//     */
//    protected $hash;

//    /**
//     * @Assert\NotBlank()
//     *
//     * @var string
//     *
//     * @ORM\Column(name="original_filename", type="string", nullable=false)
//     */
//    protected $originalFilename;
//
//    /**
//     * @Assert\NotBlank()
//     *
//     * @var string
//     *
//     * @ORM\Column(name="size", type="string", nullable=false)
//     */
//    protected $size;
//
//    /**
//     * @Assert\NotBlank()
//     *
//     * @var string
//     *
//     * @ORM\Column(name="width", type="string", nullable=true)
//     */
//    protected $width;

//    /**
//     * @Assert\NotBlank()
//     *
//     * @var string
//     *
//     * @ORM\Column(name="height", type="string", nullable=true)
//     */
//    protected $height;
//
//    /**
//     * @var string
//     *
//     * @ORM\Column(name="copyright", type="string", nullable=true)
//     */
//    protected $copyright;

//    /**
//     * @var string
//     *
//     * @ORM\Column(name="contentType", type="string", nullable=true)
//     */
//    protected $contentType;
//
//    /**
//     * @var string
//     *
//     * @ORM\Column(name="extension", type="string", nullable=false)
//     */
//    protected $extension;

    /**
     * @var ResourceNode
     *
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", mappedBy="resourceFile")
     */
    protected $resourceNode;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    //protected $enabled;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->enabled = true;
        $this->setOriginalFilename(uniqid());
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return ResourceFile
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return ResourceFile
     */
    public function setHash(string $hash): ResourceFile
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    /**
     * @param string $originalFilename
     *
     * @return ResourceFile
     */
    public function setOriginalFilename(string $originalFilename): ResourceFile
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * @param string $size
     *
     * @return ResourceFile
     */
    public function setSize(string $size): ResourceFile
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return string
     */
    public function getWidth(): string
    {
        return $this->width;
    }

    /**
     * @param string $width
     *
     * @return ResourceFile
     */
    public function setWidth(string $width): ResourceFile
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeight(): string
    {
        return $this->height;
    }

    /**
     * @param string $height
     *
     * @return ResourceFile
     */
    public function setHeight(string $height): ResourceFile
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return string
     */
    public function getCopyright(): string
    {
        return (string) $this->copyright;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return (string) $this->contentType;
    }

    /**
     * @param string $contentType
     *
     * @return ResourceFile
     */
    public function setContentType(string $contentType): ResourceFile
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     *
     * @return ResourceFile
     */
    public function setExtension(string $extension): ResourceFile
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * @return ResourceNode
     */
    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

    /**
     * @param ResourceNode $resourceNode
     *
     * @return ResourceFile
     */
    public function setResourceNode(ResourceNode $resourceNode): ResourceFile
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return ResourceFile
     */
    public function setEnabled(bool $enabled): ResourceFile
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param Media $media
     *
     * @return ResourceFile
     */
    public function setMedia($media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return ResourceFile
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
