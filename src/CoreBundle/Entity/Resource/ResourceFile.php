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

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    protected $metadata;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->enabled = true;
    }

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

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

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

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * @param $originalName
     */
    public function setOriginalName($originalName): ResourceFile
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    /**
     * @param $dimensions
     */
    public function setDimensions($dimensions): ResourceFile
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    public function getWidth(): int
    {
        $data = $this->getDimensions();
        if ($data) {
            //$data = explode(',', $data);

            return (int) $data[0];
        }

        return 0;
    }

    public function getHeight(): int
    {
        $data = $this->getDimensions();

        if ($data) {
            //$data = explode(',', $data);

            return (int) $data[1];
        }

        return 0;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): ResourceFile
    {
        $this->metadata = $metadata;

        return $this;
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
