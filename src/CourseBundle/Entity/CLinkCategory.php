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
use Chamilo\CoreBundle\Controller\Api\CreateCLinkCategoryAction;
use Chamilo\CoreBundle\Controller\Api\UpdateCLinkCategoryAction;
use Chamilo\CoreBundle\Controller\Api\UpdateVisibilityLinkCategory;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CLinkCategory.
 */
#[ApiResource(
    shortName: 'LinkCategories',
    operations: [
        new Put(
            controller: UpdateCLinkCategoryAction::class,
            security: "is_granted('EDIT', object.resourceNode)",
            validationContext: [
                'groups' => ['media_object_create', 'link_category:write'],
            ],
            deserialize: false
        ),
        new Put(
            uriTemplate: '/link_categories/{iid}/toggle_visibility',
            controller: UpdateVisibilityLinkCategory::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Delete(security: "is_granted('DELETE', object.resourceNode)"),
        new Post(
            controller: CreateCLinkCategoryAction::class,
            openapiContext: [
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'category_title' => ['type' => 'string'],
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
                                                'sid' => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                ],
                                'required' => ['category_title'],
                            ],
                        ],
                    ],
                ],
            ],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            validationContext: ['groups' => ['Default', 'media_object_create', 'link_category:write']],
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
                    [
                        'name' => 'cid',
                        'in' => 'query',
                        'required' => true,
                        'description' => 'Course id',
                        'schema' => [
                            'type' => 'integer',
                        ],
                    ],
                    [
                        'name' => 'sid',
                        'in' => 'query',
                        'required' => false,
                        'description' => 'Session id',
                        'schema' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        ),
    ],
    normalizationContext: [
        'groups' => ['link_category:read', 'resource_node:read'],
    ],
    denormalizationContext: [
        'groups' => ['link_category:write'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['category_title' => 'partial', 'resourceNode.parent' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['iid', 'resourceNode.title', 'resourceNode.createdAt', 'resourceNode.updatedAt'])]
#[ORM\Table(name: 'c_link_category')]
#[ORM\Entity(repositoryClass: \Chamilo\CourseBundle\Repository\CLinkCategoryRepository::class)]
class CLinkCategory extends AbstractResource implements ResourceInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['link_category:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['link_category:read', 'link_category:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'category_title', type: 'string', length: 255, nullable: false)]
    protected string $categoryTitle;

    #[Groups(['link_category:read', 'link_category:write'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description;

    #[Groups(['link_category:read', 'link_category:write'])]
    #[ORM\Column(name: 'display_order', type: 'integer', nullable: false)]
    protected int $displayOrder;

    #[Groups(['link_category:read', 'link_category:browse'])]
    protected bool $linkCategoryVisible = true;

    /**
     * @var Collection|CLink[]
     */
    #[ORM\OneToMany(targetEntity: CLink::class, mappedBy: 'category')]
    protected Collection $links;

    public function __construct()
    {
        $this->description = '';
        $this->displayOrder = 0;
        $this->links = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCategoryTitle();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setCategoryTitle(string $categoryTitle): self
    {
        $this->categoryTitle = $categoryTitle;

        return $this;
    }

    public function getCategoryTitle(): string
    {
        return $this->categoryTitle;
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

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function toggleVisibility(): void
    {
        $this->linkCategoryVisible = !($this->getFirstResourceLink()->getVisibility());
    }

    public function getLinkCategoryVisible(): bool
    {
        $this->linkCategoryVisible = (bool) $this->getFirstResourceLink()->getVisibility();

        return $this->linkCategoryVisible;
    }

    /**
     * @return CLink[]|Collection
     */
    public function getLinks(): array|Collection
    {
        return $this->links;
    }

    public function getResourceIdentifier(): int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getCategoryTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setCategoryTitle($name);
    }
}
