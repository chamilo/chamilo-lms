<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 * @ORM\Entity
 * @ORM\Table(name="asset")
 */
class Asset
{
    use TimestampableEntity;

    public const SCORM = 'scorm';
    public const WATERMARK = 'watermark';
    //public const CSS = 'css';
    public const EXTRA_FIELD = 'ef';
    public const COURSE_CATEGORY = 'course_category';
    public const SKILL = 'skill';

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid")
     */
    protected Uuid $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank]
    protected ?string $title = null;

    /**
     * @Assert\Choice({
     *     Asset::SCORM,
     *     Asset::WATERMARK,
     *     Asset::EXTRA_FIELD,
     *     Asset::COURSE_CATEGORY,
     *     Asset::SKILL,
     * },
     * message="Choose a valid category."
     * )
     *
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank]
    protected ?string $category = null;

    /**
     * @Vich\UploadableField(
     *     mapping="assets",
     *     fileNameProperty="title",
     *     size="size",
     *     mimeType="mimeType",
     *     originalName="originalName",
     *     dimensions="dimensions"
     * )
     */
//    #[Vich\UploadableField(
//        mapping: 'assets',
//        fileNameProperty: 'title',
//        size: 'size',
//        mimeType: 'mimeType',
//        originalName: 'originalName',
//        dimensions: 'dimensions'
//    )]
    #[Assert\NotNull]
    protected File $file;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $compressed;

    /**
     * @Groups({"resource_file:read", "resource_node:read", "document:read"})
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $mimeType = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $originalName = null;

    /**
     * @Groups({"resource_file:read", "resource_node:read", "document:read"})
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected ?array $dimensions;

    /**
     * @ORM\Column(type="integer")
     */
    protected ?int $size = null;

    /**
     * @ORM\Column(name="crop", type="string", length=255, nullable=true)
     */
    protected ?string $crop = null;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected ?array $metadata;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @var DateTime|DateTimeImmutable
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->metadata = [];
        $this->dimensions = [];
        $this->size = 0;
        $this->compressed = false;
        $this->crop = '';
    }

    public function __toString(): string
    {
        return $this->getOriginalName();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFolder(): string
    {
        return $this->category.'/'.$this->getOriginalName();
    }

    public function getFileUrl(): string
    {
        return $this->getFolder().'/'.$this->getOriginalName();
    }

    public function isImage(): bool
    {
        $mimeType = $this->getMimeType();

        return str_contains($mimeType, 'image');
    }

    public function isVideo(): bool
    {
        $mimeType = $this->getMimeType();

        return str_contains($mimeType, 'video');
    }

    public function getCrop(): ?string
    {
        return $this->crop;
    }

    public function setCrop(string $crop): self
    {
        $this->crop = $crop;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getOriginalName(): string
    {
        return (string) $this->originalName;
    }

    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    public function setDimensions(?array $dimensions): self
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    public function getWidth(): int
    {
        $data = $this->getDimensions();
        if ([] !== $data) {
            //$data = explode(',', $data);

            return (int) $data[0];
        }

        return 0;
    }

    public function getHeight(): int
    {
        $data = $this->getDimensions();

        if ([] !== $data) {
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

    public function hasFile(): bool
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
            $this->updatedAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCompressed(): bool
    {
        return $this->compressed;
    }

    public function setCompressed(bool $compressed): self
    {
        $this->compressed = $compressed;

        return $this;
    }
}
