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
use Chamilo\CoreBundle\Controller\Api\CreateCGlossaryAction;
use Chamilo\CoreBundle\Controller\Api\ExportCGlossaryAction;
use Chamilo\CoreBundle\Controller\Api\ExportGlossaryToDocumentsAction;
use Chamilo\CoreBundle\Controller\Api\GetGlossaryCollectionController;
use Chamilo\CoreBundle\Controller\Api\ImportCGlossaryAction;
use Chamilo\CoreBundle\Controller\Api\UpdateCGlossaryAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course glossary.
 */
#[ApiResource(
    shortName: 'Glossary',
    operations: [
        new Put(
            controller: UpdateCGlossaryAction::class,
            security: "is_granted('EDIT', object.resourceNode)",
            validationContext: [
                'groups' => ['media_object_create', 'glossary:write']
            ],
            deserialize: false
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Delete(security: "is_granted('DELETE', object.resourceNode)"),
        new Post(
            controller: CreateCGlossaryAction::class,
            openapiContext: [
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'parentResourceNodeId' => ['type' => 'integer'],
                                    'resourceLinkList' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'visibility' => ['type' => 'integer'],
                                                'cid' => ['type' => 'integer'],
                                                'gid' => ['type' => 'integer'],
                                                'sid' => ['type' => 'integer']
                                            ]
                                        ]
                                    ]
                                ],
                                'required' => ['name'],
                            ],
                        ],
                    ],
                ],
            ],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            validationContext: ['groups' => ['Default', 'media_object_create', 'glossary:write']],
            deserialize: false
        ),
        new GetCollection(
            controller: GetGlossaryCollectionController::class,
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'resourceNode.parent',
                        'in' => 'query',
                        'required' => true,
                        'description' => 'Resource node Parent',
                        'schema' => ['type' => 'integer']
                    ],
                    [
                        'name' => 'cid',
                        'in' => 'query',
                        'required' => true,
                        'description' => 'Course id',
                        'schema' => [
                            'type' => 'integer'
                        ]
                    ],
                    [
                        'name' => 'sid',
                        'in' => 'query',
                        'required' => false,
                        'description' => 'Session id',
                        'schema' => [
                            'type' => 'integer'
                        ]
                    ],
                    [
                        'name' => 'q',
                        'in' => 'query',
                        'required' => false,
                        'description' => 'Search term',
                        'schema' => [
                            'type' => 'string'
                        ]
                    ]
                ]
            ]
        ),
        new Post(
            uriTemplate: '/glossaries/import',
            controller: ImportCGlossaryAction::class,
            openapiContext: [
                'summary' => 'Import a glossary',
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
                'responses' => [
                    '200' => [
                        'description' => 'Glossaries imported successfully',
                    ],
                ],
            ],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            validationContext: ['groups' => ['Default', 'media_object_create', 'glossary:write']],
            deserialize: false
        ),
        new Post(
            uriTemplate: '/glossaries/export',
            controller: ExportCGlossaryAction::class,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            validationContext: ['groups' => ['Default', 'media_object_create', 'glossary:write']],
            deserialize: false
        ),
        new Post(
            uriTemplate: '/glossaries/export_to_documents',
            controller: ExportGlossaryToDocumentsAction::class,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            validationContext: ['groups' => ['Default', 'media_object_create', 'glossary:write']],
            deserialize: false
        ),
    ],
    normalizationContext: [
        'groups' => ['glossary:read', 'resource_node:read'],
    ],
    denormalizationContext: [
        'groups' => ['glossary:write'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['iid', 'name', 'createdAt', 'updatedAt'])]
#[ORM\Table(name: 'c_glossary')]
#[ORM\Entity(repositoryClass: CGlossaryRepository::class)]
class CGlossary extends AbstractResource implements ResourceInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['glossary:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Groups(['glossary:read', 'glossary:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'name', type: 'text', nullable: false)]
    protected string $name;

    #[Groups(['glossary:read', 'glossary:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: false)]
    protected ?string $description = null;

    #[Groups(['glossary:read', 'glossary:write'])]
    #[ORM\Column(name: 'display_order', type: 'integer', nullable: true)]
    protected ?int $displayOrder = null;

    public function __toString(): string
    {
        return $this->getName();
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
