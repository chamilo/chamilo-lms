<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Controller\CreateResourceFileAction;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

//
//*     attributes={"security"="is_granted('ROLE_ADMIN')"},
/**
 * @Vich\Uploadable
 */
#[ApiResource(
    types: ['http://schema.org/MediaObject'],
    operations: [
        new Get(),
        new Post(
            controller: CreateResourceFileAction::class,
            openapiContext: [
                'requestBody' => [
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            security: 'is_granted(\'ROLE_USER\')',
            validationContext: [
                'groups' => ['Default', 'media_object_create', 'document:write'],
            ],
            deserialize: false
        ),
        new GetCollection(),
    ],
    normalizationContext: [
        'groups' => [
            'resource_file:read',
            'resource_node:read',
            'document:read',
            'media_object_read',
            'message:read',
        ],
    ]
)]
#[ORM\Table(name: 'resource_file')]
#[ORM\Entity]
#[ApiFilter(filterClass: PropertyFilter::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id', 'name', 'size', 'updatedAt'])]
class ResourceFile implements Stringable
{
    use TimestampableEntity;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'message:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Assert\NotBlank]
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read'])]
    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $name = null;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'message:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $mimeType = null;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'message:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $originalName = null;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read'])]
    #[ORM\Column(type: 'simple_array', nullable: true)]
    protected ?array $dimensions;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'message:read'])]
    #[ORM\Column(type: 'integer')]
    protected ?int $size = 0;
    /**
     * @Vich\UploadableField(
     *     mapping="resources",
     *     fileNameProperty="name",
     *     size="size",
     *     mimeType="mimeType",
     *     originalName="originalName",
     *     dimensions="dimensions"
     * )
     */
    //    #[Vich\UploadableField(
    //        mapping: 'resources',
    //        fileNameProperty: 'name',
    //        size: 'size',
    //        mimeType: 'mimeType',
    //        originalName: 'originalName',
    //        dimensions: 'dimensions'
    //    )]
    protected ?File $file = null;
    #[ORM\Column(name: 'crop', type: 'string', length: 255, nullable: true)]
    protected ?string $crop = null;
    #[ORM\OneToOne(mappedBy: 'resourceFile', targetEntity: ResourceNode::class)]
    protected ResourceNode $resourceNode;
    /**
     * @var string[]
     */
    #[ORM\Column(type: 'array', nullable: true)]
    protected ?array $metadata = [];
    #[Groups(['message:read'])]
    protected ?bool $audio = null;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'message:read'])]
    protected ?bool $image = null;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'message:read'])]
    protected ?bool $video = null;
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'message:read'])]
    protected ?bool $text = null;
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;
    /**
     * @var DateTime|DateTimeImmutable
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    protected $updatedAt;
    public function __construct()
    {
        $this->size = 0;
        $this->metadata = [];
        $this->dimensions = [];
    }
    public function __toString(): string
    {
        return $this->getOriginalName();
    }
    public function isText(): bool
    {
        $mimeType = $this->getMimeType();

        return str_contains($mimeType, 'text');
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
    public function isAudio(): bool
    {
        $mimeType = $this->getMimeType();

        return str_contains($mimeType, 'audio');
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getCrop(): ?string
    {
        return $this->crop;
    }
    /**
     * $crop example: 100,100,100,100 = width,height,x,y.
     */
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
    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }
    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }
    /*public function isEnabled(): bool
        {
            return $this->enabled;
        }

        public function setEnabled(bool $enabled): self
        {
            $this->enabled = $enabled;

            return $this;
        }*/
    public function getId(): ?int
    {
        return $this->id;
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
        return $this->originalName;
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

    public function setFile(File|UploadedFile|null $file = null): self
    {
        $this->file = $file;
        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new DateTimeImmutable();
        }

        return $this;
    }
}
