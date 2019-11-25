<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 *
 * @Vich\Uploadable
 *
 * @ORM\Table(name="resource_file")
 */
class ResourceFile
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @Assert\NotBlank()
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var float
     */
    protected $length;

    /**
     * @var string
     */
    protected $copyright;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $mimeType;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $originalName;

    /**
     * @var string
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected $dimensions;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $size;

    /**
     * @var File
     *
     * @Vich\UploadableField(
     *     mapping="resources",
     *     fileNameProperty="name",
     *     size="size",
     *     mimeType="mimeType",
     *     originalName="originalName",
     *     dimensions="dimensions"
     * )
     */
    protected $file;

    /**
     * @var string
     *
     * @ORM\Column(name="crop", type="string", length=255, nullable=true)
     */
    protected $crop;

    /**
     * @var ResourceNode
     *
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", mappedBy="resourceFile")
     */
    protected $resourceNode;

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
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    //protected $enabled;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->enabled = true;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getOriginalName();
    }

    /**
     * @return string
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
    public function getCrop()
    {
        return $this->crop;
    }

    /**
     * @param string $crop
     *
     * @return $this
     */
    public function setCrop($crop)
    {
        $this->crop = $crop;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): ResourceFile
    {
        $this->hash = $hash;

        return $this;
    }

    public function getSize(): int
    {
        return (int) $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size): ResourceFile
    {
        $this->size = $size;

        return $this;
    }

    public function getCopyright(): string
    {
        return (string) $this->copyright;
    }

    public function getContentType(): string
    {
        return (string) $this->contentType;
    }

    public function setContentType(string $contentType): ResourceFile
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): ResourceFile
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return int
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): ResourceFile
    {
        $this->description = $description;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType): ResourceFile
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * @param $originalName
     *
     * @return ResourceFile
     */
    public function setOriginalName($originalName): ResourceFile
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * @return array
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    /**
     * @param $dimensions
     *
     * @return ResourceFile
     */
    public function setDimensions($dimensions): ResourceFile
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        $data = $this->getDimensions();
        if ($data) {
            //$data = explode(',', $data);

            return (int) $data[0];
        }

        return 0;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        $data = $this->getDimensions();

        if ($data) {
            //$data = explode(',', $data);

            return (int) $data[1];
        }

        return 0;
    }

    /**
     * @return File
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param File|UploadedFile $file
     */
    public function setFile(File $file = null): void
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
}
