<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @Vich\Uploadable
 *
 * @ORM\Table(name="asset")
 */
class Asset
{
    use TimestampableEntity;

    public const SCORM = 'scorm';
    public const WATERMARK = 'watermark';
    public const CSS = 'css';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     *
     * @Assert\Choice({
     *     Asset::SCORM,
     *     Asset::WATERMARK,
     *     Asset::CSS
     *     },
     *     message="Choose a valid category."
     * )
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $category;

    /**
     * @var File
     *
     * @Assert\NotNull()
     * @Vich\UploadableField(
     *     mapping="assets",
     *     fileNameProperty="title",
     *     size="size",
     *     mimeType="mimeType",
     *     originalName="originalName",
     *     dimensions="dimensions"
     * )
     */
    protected $file;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $compressed;

    /**
     * @var string
     *
     * @Groups({"resource_file:read", "resource_node:read", "document:read"})
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
     * @Groups({"resource_file:read", "resource_node:read", "document:read"})
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected $dimensions;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $size;

    /**
     * @var string
     *
     * @ORM\Column(name="crop", type="string", length=255, nullable=true)
     */
    protected $crop;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    protected $metadata;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    public function __construct()
    {
        $this->metadata = [];
        $this->size = 0;
        $this->compressed = false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getFolder()
    {
        return $this->category.'/'.$this->getOriginalName();
    }

    public function getFileUrl()
    {
        return $this->getFolder().'/'.$this->getOriginalName();
    }

    public function __toString(): string
    {
        return $this->getOriginalName();
    }

    public function isImage(): bool
    {
        $mimeType = $this->getMimeType();
        if (false !== strpos($mimeType, 'image')) {
            return true;
        }

        return false;
    }

    public function isVideo(): bool
    {
        $mimeType = $this->getMimeType();
        if (false !== strpos($mimeType, 'video')) {
            return true;
        }

        return false;
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
    public function setCrop($crop): self
    {
        $this->crop = $crop;

        return $this;
    }

    public function getSize(): int
    {
        return (int) $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size): self
    {
        $this->size = $size;

        return $this;
    }

    /*public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }*/

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getOriginalName(): string
    {
        return (string) $this->originalName;
    }

    /**
     * @param string $originalName
     */
    public function setOriginalName($originalName): self
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
    public function setDimensions($dimensions): self
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

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function hasFile()
    {
        return null !== $this->file;
    }

    /**
     * @param File|UploadedFile $file
     */
    public function setFile(File $file = null): self
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title): Asset
    {
        $this->title = $title;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): Asset
    {
        $this->category = $category;

        return $this;
    }

    public function getCompressed(): bool
    {
        return $this->compressed;
    }

    public function setCompressed(bool $compressed)
    {
        $this->compressed = $compressed;

        return $this;
    }
}
