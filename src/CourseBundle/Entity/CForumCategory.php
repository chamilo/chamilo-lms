<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

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
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\ApiResource\Forum\ForumWriteInput;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\State\Forum\ForumCategoryCollectionStateProvider;
use Chamilo\CoreBundle\State\Forum\ForumCategoryProcessor;
use Chamilo\CoreBundle\State\Forum\ForumDeleteProcessor;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'ForumCategory',
    operations: [
        new Post(
            uriTemplate: '/forum_categories/create',
            openapi: new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'comment' => ['type' => 'string'],
                                    'locked' => ['type' => 'boolean'],
                                    'language' => ['type' => 'string'],
                                    'parentResourceNodeId' => ['type' => 'integer'],
                                    'csrfToken' => ['type' => 'string'],
                                ],
                                'required' => ['title', 'parentResourceNodeId', 'csrfToken'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            input: ForumWriteInput::class,
            read: false,
            name: 'create_forum_category',
            processor: ForumCategoryProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_categories/{iid}/update',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'update_forum_category',
            processor: ForumCategoryProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_categories/{iid}/toggle-lock',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'toggle_forum_category_lock',
            processor: ForumCategoryProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_categories/{iid}/toggle-visibility',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'toggle_forum_category_visibility',
            processor: ForumCategoryProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_categories/{iid}/move',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'move_forum_category',
            processor: ForumCategoryProcessor::class,
        ),
        new Delete(
            uriTemplate: '/forum_categories/{iid}',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'delete_forum_category',
            processor: ForumDeleteProcessor::class,
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new GetCollection(
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'resourceNode.parent',
                        in: 'query',
                        description: 'Resource node parent',
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
                    new Parameter(
                        name: 'gid',
                        in: 'query',
                        description: 'Group id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            provider: ForumCategoryCollectionStateProvider::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['forum_category:read', 'resource_node:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial', 'resourceNode.parent' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['iid', 'title'])]
#[ORM\Table(name: 'c_forum_category')]
#[ORM\Entity(repositoryClass: CForumCategoryRepository::class)]
class CForumCategory extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_category:read', 'forum:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['forum_category:read', 'forum:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['forum_category:read', 'forum:read'])]
    #[ORM\Column(name: 'cat_comment', type: 'text', nullable: true)]
    protected ?string $catComment;

    #[Groups(['forum_category:read'])]
    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    /**
     * @var Collection<int, CForum>
     */
    #[ORM\OneToMany(mappedBy: 'forumCategory', targetEntity: CForum::class)]
    protected Collection $forums;

    public function __construct()
    {
        $this->catComment = '';
        $this->locked = 0;
        $this->forums = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCatComment(string $catComment): self
    {
        $this->catComment = $catComment;

        return $this;
    }

    public function getCatComment(): ?string
    {
        return $this->catComment;
    }

    public function setLocked(int $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked(): int
    {
        return $this->locked;
    }

    #[Groups(['forum_category:read'])]
    public function getForumCategoryVisible(): bool
    {
        $link = $this->getFirstResourceLink();

        if (null === $link) {
            return true;
        }

        return ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    #[Groups(['forum_category:read'])]
    public function getPosition(): int
    {
        return $this->getFirstResourceLink()?->getDisplayOrder() ?? 0;
    }

    /**
     * @return Collection<int, CForum>
     */
    public function getForums(): Collection
    {
        return $this->forums;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getIid();
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
