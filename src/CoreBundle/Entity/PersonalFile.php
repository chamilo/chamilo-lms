<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ArrayObject;
use Chamilo\CoreBundle\Controller\Api\CreatePersonalFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdatePersonalFileAction;
use Chamilo\CoreBundle\Entity\Listener\ResourceListener;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\State\CopyDocumentToPersonalFileProcessor;
use Chamilo\CoreBundle\State\DocumentProvider;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Put(
            controller: UpdatePersonalFileAction::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Delete(security: "is_granted('DELETE', object.resourceNode)"),
        new Post(
            controller: CreatePersonalFileAction::class,
            openapi: new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'comment' => ['type' => 'string'],
                                    'contentFile' => ['type' => 'string'],
                                    'uploadFile' => ['type' => 'string', 'format' => 'binary'],
                                    'parentResourceNodeId' => ['type' => 'integer'],
                                    'resourceLinkList' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'visibility' => ['type' => 'integer'],
                                                'c_id' => ['type' => 'integer'],
                                                'session_id' => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_USER')",
            validationContext: [
                'groups' => ['Default', 'media_object_create', 'personal_file:write'],
            ],
            deserialize: false
        ),
        new GetCollection(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: [
        'groups' => ['personal_file:read', 'resource_node:read'],
    ],
    denormalizationContext: [
        'groups' => ['personal_file:write'],
    ]
)]
#[ApiResource(
    uriTemplate: '/documents/{document_id}/personal_files',
    operations: [
        new Post(
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'cid',
                        in: 'query',
                        description: 'Course identifier',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sid',
                        in: 'query',
                        description: 'Session identifier',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'gid',
                        in: 'query',
                        description: 'Group identifier',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            read: true, // Explicit true forces the provider to run and sets $data in the pipeline
            deserialize: false, // No request body; the CDocument is resolved from the URI variable via the provider
            provider: DocumentProvider::class,
        ),
    ],
    uriVariables: [
        'document_id' => new Link(
            fromClass: CDocument::class,
            description: 'CDocument identifier',
        ),
    ],
    normalizationContext: ['groups' => ['personal_file:read', 'resource_node:read']],
    security: "is_granted('ROLE_USER')",
    processor: CopyDocumentToPersonalFileProcessor::class,
)]
#[ORM\Table(name: 'personal_file')]
#[ORM\EntityListeners([ResourceListener::class])]
#[ORM\Entity(repositoryClass: PersonalFileRepository::class)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'title' => 'partial',
        'resourceNode.parent' => 'exact',
    ]
)]
#[ApiFilter(
    filterClass: PropertyFilter::class
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'id',
        'resourceNode.title',
        'resourceNode.createdAt',
        'resourceNode.firstResourceFile.size',
        'resourceNode.updatedAt',
    ]
)]
class PersonalFile extends AbstractResource implements ResourceInterface, Stringable
{
    use TimestampableEntity;

    #[Groups(['personal_file:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['personal_file:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['personal_file:read'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private ?string $comment = null;

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
