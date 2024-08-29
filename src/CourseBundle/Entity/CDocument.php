<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Controller\Api\CreateDocumentFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdateDocumentFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdateVisibilityDocument;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Listener\ResourceListener;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Filter\CidFilter;
use Chamilo\CoreBundle\Filter\SidFilter;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Documents',
    operations: [
        new Put(
            controller: UpdateDocumentFileAction::class,
            security: "is_granted('EDIT', object.resourceNode)",
            validationContext: [
                'groups' => ['media_object_create', 'document:write'],
            ],
            deserialize: false
        ),
        new Put(
            uriTemplate: '/documents/{iid}/toggle_visibility',
            controller: UpdateVisibilityDocument::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Delete(security: "is_granted('DELETE', object.resourceNode)"),
        new Post(
            controller: CreateDocumentFileAction::class,
            openapiContext: [
                'requestBody' => [
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'filetype' => [
                                        'type' => 'string',
                                        'enum' => ['folder', 'file'],
                                    ],
                                    'comment' => ['type' => 'string'],
                                    'contentFile' => ['type' => 'string'],
                                    'uploadFile' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                    'parentResourceNodeId' => ['type' => 'integer'],
                                    'resourceLinkList' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'visibility' => ['type' => 'integer'],
                                                'cid' => ['type' => 'integer'],
                                                'gid' => ['type' => 'integer'],
                                                'sid' => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                    'isUncompressZipEnabled' => ['type' => 'boolean'],
                                    'fileExistsOption' => [
                                        'type' => 'string',
                                        'enum' => ['overwrite', 'skip', 'rename'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_TEACHER')",
            validationContext: ['groups' => ['Default', 'media_object_create', 'document:write']],
            deserialize: false
        ),
        new GetCollection(
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'resourceNode.parent',
                        'in' => 'query',
                        'required' => true,
                        'description' => 'Resource node Parent',
                        'schema' => ['type' => 'integer'],
                    ],
                ],
            ]
        ),
    ],
    normalizationContext: [
        'groups' => ['document:read', 'resource_node:read'],
    ],
    denormalizationContext: [
        'groups' => ['document:write'],
    ]
)]
#[ORM\Table(name: 'c_document')]
#[ORM\Index(columns: ['filetype'], name: 'idx_cdoc_type')]
#[ORM\Entity(repositoryClass: CDocumentRepository::class)]
#[ORM\EntityListeners([ResourceListener::class])]
#[ApiFilter(filterClass: PropertyFilter::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['title' => 'partial', 'resourceNode.parent' => 'exact', 'filetype' => 'exact'])]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'iid',
        'filetype',
        'resourceNode.title',
        'resourceNode.createdAt',
        'resourceNode.firstResourceFile.size',
        'resourceNode.updatedAt',
    ]
)]
#[ApiFilter(filterClass: CidFilter::class)]
#[ApiFilter(filterClass: SidFilter::class)]
class CDocument extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['document:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['document:read', 'document:write', 'document:browse'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment;

    #[Groups(['document:read', 'document:write'])]
    #[Assert\Choice(['folder', 'file', 'certificate'], message: 'Choose a valid filetype.')]
    #[ORM\Column(name: 'filetype', type: 'string', length: 15, nullable: false)]
    protected string $filetype;

    #[ORM\Column(name: 'readonly', type: 'boolean', nullable: false)]
    protected bool $readonly;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\Column(name: 'template', type: 'boolean', nullable: false)]
    protected bool $template;

    #[ORM\OneToMany(mappedBy: 'document', targetEntity: GradebookCategory::class)]
    #[Groups(['document:read'])]
    protected Collection $gradebookCategories = null;

    public function __construct()
    {
        $this->comment = '';
        $this->filetype = 'folder';
        $this->readonly = false;
        $this->template = false;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function isTemplate(): bool
    {
        return $this->template;
    }

    public function setTemplate(bool $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getFiletype(): string
    {
        return $this->filetype;
    }

    public function setFiletype(string $filetype): self
    {
        $this->filetype = $filetype;

        return $this;
    }

    public function getReadonly(): bool
    {
        return $this->readonly;
    }

    public function setReadonly(bool $readonly): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getIid();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }

    public function getGradebookCategory(): ?GradebookCategory
    {
        return $this->gradebookCategory;
    }
}
