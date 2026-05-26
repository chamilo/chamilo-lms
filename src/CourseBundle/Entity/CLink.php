<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\Controller\Api\CheckCLinkAction;
use Chamilo\CoreBundle\Controller\Api\CLinkDetailsController;
use Chamilo\CoreBundle\Controller\Api\CLinkImageController;
use Chamilo\CoreBundle\Controller\Api\CreateCLinkAction;
use Chamilo\CoreBundle\Controller\Api\ExportCLinksAction;
use Chamilo\CoreBundle\Controller\Api\GetLinksCollectionController;
use Chamilo\CoreBundle\Controller\Api\UpdateCLinkAction;
use Chamilo\CoreBundle\Controller\Api\UpdatePositionLink;
use Chamilo\CoreBundle\Controller\Api\UpdateVisibilityLink;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Link',
    operations: [
        new Put(
            controller: UpdateCLinkAction::class,
            denormalizationContext: [
                'groups' => ['link:write'],
            ],
            security: "is_granted('EDIT', object.resourceNode)",
            validationContext: [
                'groups' => ['media_object_create', 'link:write'],
            ],
            deserialize: false
        ),
        new Put(
            uriTemplate: '/links/{iid}/toggle_visibility',
            controller: UpdateVisibilityLink::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false
        ),
        new Put(
            uriTemplate: '/links/{iid}/move',
            controller: UpdatePositionLink::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false
        ),
        new Post(
            controller: CreateCLinkAction::class,
            openapi: new Operation(
                summary: 'Create a new link resource',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'url' => ['type' => 'string'],
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'category_id' => ['type' => 'integer'],
                                    'target' => ['type' => 'string'],
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
                                ],
                                'required' => ['url', 'title'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_TEACHER')",
            validationContext: ['groups' => ['Default', 'media_object_create', 'link:write']],
            deserialize: false
        ),
        new Post(
            uriTemplate: '/links/{iid}/upload-image',
            controller: CLinkImageController::class,
            openapi: new Operation(
                summary: 'Upload a custom image for a link',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'customImage' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                                'required' => ['customImage'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false
        ),
        new Post(
            uriTemplate: '/links/export',
            controller: ExportCLinksAction::class,
            openapi: new Operation(
                summary: 'Export links',
                parameters: [
                    new Parameter(
                        name: 'format',
                        in: 'query',
                        description: 'Export format',
                        required: true,
                        schema: ['type' => 'string', 'enum' => ['pdf']],
                    ),
                ],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'cid' => ['type' => 'integer'],
                                    'sid' => ['type' => 'integer'],
                                ],
                                'required' => ['cid'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_TEACHER')",
            deserialize: false
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Get(
            uriTemplate: '/links/{iid}/details',
            controller: CLinkDetailsController::class,
            openapi: new Operation(
                summary: 'Gets the details of a link, including whether it is on the homepage',
            ),
            security: "is_granted('VIEW', object.resourceNode)"
        ),
        new Get(
            uriTemplate: '/links/{iid}/check',
            controller: CheckCLinkAction::class,
            openapi: new Operation(
                summary: 'Check if a link URL is valid',
            ),
            security: "is_granted('EDIT', object.resourceNode)"
        ),
        new Delete(security: "is_granted('DELETE', object.resourceNode)"),
        new GetCollection(
            controller: GetLinksCollectionController::class,
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'resourceNode.parent',
                        in: 'query',
                        description: 'Resource node Parent',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'cid',
                        in: 'query',
                        description: 'Course id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sid',
                        in: 'query',
                        description: 'Session id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            )
        ),
    ],
    normalizationContext: [
        'groups' => ['link:read', 'resource_node:read'],
    ],
    denormalizationContext: [
        'groups' => ['link:write'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial', 'resourceNode.parent' => 'exact'])]
#[ORM\Table(name: 'c_link')]
#[ORM\Entity(repositoryClass: CLinkRepository::class)]
class CLink extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['link:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['link:read', 'link:write', 'link:browse'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'url', type: 'text', nullable: false)]
    protected string $url;

    #[Groups(['link:read', 'link:write', 'link:browse'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['link:read', 'link:write', 'link:browse'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description;

    #[Groups(['link:read', 'link:write', 'link:browse'])]
    #[ORM\ManyToOne(targetEntity: CLinkCategory::class, inversedBy: 'links')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'iid', onDelete: 'SET NULL')]
    protected ?CLinkCategory $category = null;

    #[Groups(['link:read', 'link:write', 'link:browse'])]
    #[ORM\Column(name: 'target', type: 'string', length: 10, nullable: true)]
    protected ?string $target = null;

    #[Groups(['link:read', 'link:browse'])]
    protected bool $linkVisible = true;

    #[Groups(['cshortcut:read'])]
    #[ORM\ManyToOne(targetEntity: Asset::class, cascade: ['remove'])]
    #[ORM\JoinColumn(name: 'custom_image_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Asset $customImage = null;

    public function __construct()
    {
        $this->description = '';
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getCategory(): ?CLinkCategory
    {
        return $this->category;
    }

    public function setCategory(?CLinkCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCustomImage(): ?Asset
    {
        return $this->customImage;
    }

    public function setCustomImage(?Asset $customImage): self
    {
        $this->customImage = $customImage;

        return $this;
    }

    public function toggleVisibility(): void
    {
        $this->linkVisible = !$this->getFirstResourceLink()->getVisibility();
    }

    public function getLinkVisible(): bool
    {
        $this->linkVisible = (bool) $this->getFirstResourceLink()->getVisibility();

        return $this->linkVisible;
    }

    public function getResourceIdentifier(): int
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
}
