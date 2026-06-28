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
use Chamilo\CoreBundle\State\Forum\ForumCollectionStateProvider;
use Chamilo\CoreBundle\State\Forum\ForumDeleteProcessor;
use Chamilo\CoreBundle\State\Forum\ForumProcessor;
use Chamilo\CourseBundle\Repository\CForumRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course forums.
 */
#[ApiResource(
    shortName: 'Forum',
    operations: [
        new Post(
            uriTemplate: '/forums/create',
            openapi: new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'comment' => ['type' => 'string'],
                                    'categoryId' => ['type' => 'integer'],
                                    'moderated' => ['type' => 'boolean'],
                                    'studentsCanEdit' => ['type' => 'boolean'],
                                    'requiresApproval' => ['type' => 'boolean'],
                                    'allowAttachments' => ['type' => 'boolean'],
                                    'allowNewThreads' => ['type' => 'boolean'],
                                    'defaultView' => ['type' => 'string'],
                                    'startTime' => ['type' => 'string', 'format' => 'date-time'],
                                    'endTime' => ['type' => 'string', 'format' => 'date-time'],
                                    'locked' => ['type' => 'boolean'],
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
            name: 'create_forum',
            processor: ForumProcessor::class,
        ),
        new Put(
            uriTemplate: '/forums/{iid}/update',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'update_forum',
            processor: ForumProcessor::class,
        ),
        new Put(
            uriTemplate: '/forums/{iid}/toggle-lock',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'toggle_forum_lock',
            processor: ForumProcessor::class,
        ),
        new Put(
            uriTemplate: '/forums/{iid}/toggle-visibility',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'toggle_forum_visibility',
            processor: ForumProcessor::class,
        ),
        new Put(
            uriTemplate: '/forums/{iid}/move',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'move_forum',
            processor: ForumProcessor::class,
        ),
        new Put(
            uriTemplate: '/forums/{iid}/toggle-subscription',
            security: "is_granted('VIEW', object.resourceNode)",
            deserialize: false,
            name: 'toggle_forum_subscription',
            processor: ForumProcessor::class,
        ),
        new Post(
            uriTemplate: '/forums/{iid}/image',
            openapi: new Operation(
                summary: 'Upload or remove a forum image',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'image' => ['type' => 'string', 'format' => 'binary'],
                                    'removeImage' => ['type' => 'boolean'],
                                    'csrfToken' => ['type' => 'string'],
                                ],
                                'required' => ['csrfToken'],
                            ],
                        ],
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'removeImage' => ['type' => 'boolean'],
                                    'csrfToken' => ['type' => 'string'],
                                ],
                                'required' => ['csrfToken'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            inputFormats: [
                'jsonld' => ['application/ld+json'],
                'json' => ['application/json'],
                'multipart' => ['multipart/form-data'],
            ],
            name: 'upload_forum_image',
            processor: ForumProcessor::class,
        ),
        new Delete(
            uriTemplate: '/forums/{iid}',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'delete_forum',
            processor: ForumDeleteProcessor::class,
        ),
        new Get(
            uriTemplate: '/forums/{iid}',
            security: "is_granted('VIEW', object.resourceNode)",
        ),
        new GetCollection(
            uriTemplate: '/forums',
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
                        name: 'forumCategory',
                        in: 'query',
                        description: 'Forum category IRI',
                        required: false,
                        schema: ['type' => 'string'],
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
            provider: ForumCollectionStateProvider::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['forum:read', 'resource_node:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'resourceNode.parent' => 'exact',
    'forumCategory' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['iid', 'title'])]
#[ORM\Table(name: 'c_forum_forum')]
#[ORM\Entity(repositoryClass: CForumRepository::class)]
class CForum extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum:read', 'forum_thread:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['forum:read', 'forum_thread:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'forum_comment', type: 'text', nullable: true)]
    protected ?string $forumComment;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'forum_threads', type: 'integer', nullable: true)]
    protected ?int $forumThreads = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'forum_posts', type: 'integer', nullable: true)]
    protected ?int $forumPosts;

    #[ORM\ManyToOne(targetEntity: CForumPost::class)]
    #[ORM\JoinColumn(name: 'forum_last_post', referencedColumnName: 'iid')]
    protected ?CForumPost $forumLastPost = null;

    #[Groups(['forum:read'])]
    #[ORM\ManyToOne(targetEntity: CForumCategory::class, inversedBy: 'forums')]
    #[ORM\JoinColumn(name: 'forum_category', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CForumCategory $forumCategory = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'allow_anonymous', type: 'integer', nullable: true)]
    protected ?int $allowAnonymous = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'allow_edit', type: 'integer', nullable: true)]
    protected ?int $allowEdit = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'approval_direct_post', type: 'string', length: 20, nullable: true)]
    protected ?string $approvalDirectPost = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'allow_attachments', type: 'integer', nullable: true)]
    protected ?int $allowAttachments = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'allow_new_threads', type: 'integer', nullable: true)]
    protected ?int $allowNewThreads = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'default_view', type: 'string', length: 20, nullable: true)]
    protected ?string $defaultView = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'forum_of_group', type: 'string', length: 20, nullable: true)]
    protected ?string $forumOfGroup;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'forum_group_public_private', type: 'string', length: 20, nullable: true)]
    protected ?string $forumGroupPublicPrivate;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'forum_image', type: 'string', length: 255, nullable: false)]
    protected string $forumImage;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'start_time', type: 'datetime', nullable: true)]
    protected ?DateTime $startTime = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'end_time', type: 'datetime', nullable: true)]
    protected ?DateTime $endTime = null;

    #[ORM\ManyToOne(targetEntity: CLp::class, cascade: ['remove'], inversedBy: 'forums')]
    #[ORM\JoinColumn(name: 'lp_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CLp $lp = null;

    #[Groups(['forum:read'])]
    #[ORM\Column(name: 'moderated', type: 'boolean', nullable: true)]
    protected ?bool $moderated = null;

    /**
     * @var Collection<int, CForumThread>
     */
    #[ORM\OneToMany(mappedBy: 'forum', targetEntity: CForumThread::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $threads;

    /**
     * @var Collection<int, CForumPost>
     */
    #[ORM\OneToMany(mappedBy: 'forum', targetEntity: CForumPost::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $posts;

    public function __construct()
    {
        $this->threads = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->locked = 0;
        $this->forumComment = '';
        $this->forumImage = '';
        $this->forumOfGroup = '';
        $this->forumPosts = 0;
        $this->forumGroupPublicPrivate = '';
    }

    public function __toString(): string
    {
        return $this->getTitle();
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

    public function setForumComment(string $forumComment): self
    {
        $this->forumComment = $forumComment;

        return $this;
    }

    public function getForumComment(): string
    {
        return $this->forumComment;
    }

    public function setForumThreads(int $forumThreads): self
    {
        $this->forumThreads = $forumThreads;

        return $this;
    }

    public function getForumThreads(): ?int
    {
        return $this->forumThreads;
    }

    public function hasThread(CForumThread $thread): bool
    {
        return $this->threads->contains($thread);
    }

    public function setForumPosts(int $forumPosts): self
    {
        $this->forumPosts = $forumPosts;

        return $this;
    }

    public function getForumPosts(): ?int
    {
        return $this->forumPosts;
    }

    public function setForumCategory(?CForumCategory $forumCategory = null): self
    {
        $forumCategory?->getForums()->add($this);
        $this->forumCategory = $forumCategory;

        return $this;
    }

    public function getForumCategory(): ?CForumCategory
    {
        return $this->forumCategory;
    }

    public function setAllowAnonymous(int $allowAnonymous): self
    {
        $this->allowAnonymous = $allowAnonymous;

        return $this;
    }

    public function getAllowAnonymous(): ?int
    {
        return $this->allowAnonymous;
    }

    public function setAllowEdit(int $allowEdit): self
    {
        $this->allowEdit = $allowEdit;

        return $this;
    }

    public function getAllowEdit(): ?int
    {
        return $this->allowEdit;
    }

    public function setApprovalDirectPost(string $approvalDirectPost): self
    {
        $this->approvalDirectPost = $approvalDirectPost;

        return $this;
    }

    public function getApprovalDirectPost(): ?string
    {
        return $this->approvalDirectPost;
    }

    public function setAllowAttachments(int $allowAttachments): self
    {
        $this->allowAttachments = $allowAttachments;

        return $this;
    }

    public function getAllowAttachments(): ?int
    {
        return $this->allowAttachments;
    }

    public function setAllowNewThreads(int $allowNewThreads): self
    {
        $this->allowNewThreads = $allowNewThreads;

        return $this;
    }

    public function getAllowNewThreads(): ?int
    {
        return $this->allowNewThreads;
    }

    public function setDefaultView(string $defaultView): self
    {
        $this->defaultView = $defaultView;

        return $this;
    }

    public function getDefaultView(): ?string
    {
        return $this->defaultView;
    }

    public function setForumOfGroup(string $forumOfGroup): self
    {
        $this->forumOfGroup = $forumOfGroup;

        return $this;
    }

    public function getForumOfGroup(): ?string
    {
        return $this->forumOfGroup;
    }

    public function getForumGroupPublicPrivate(): string
    {
        return $this->forumGroupPublicPrivate;
    }

    public function setForumGroupPublicPrivate(string $forumGroupPublicPrivate): static
    {
        $this->forumGroupPublicPrivate = $forumGroupPublicPrivate;

        return $this;
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

    public function setForumImage(string $forumImage): self
    {
        $this->forumImage = $forumImage;

        return $this;
    }

    public function getForumImage(): string
    {
        return $this->forumImage;
    }

    public function setStartTime(?DateTime $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getStartTime(): ?DateTime
    {
        return $this->startTime;
    }

    public function setEndTime(?DateTime $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getEndTime(): ?DateTime
    {
        return $this->endTime;
    }

    public function isModerated(): bool
    {
        // Always return a strict bool, even if DB/entity has NULL
        return (bool) ($this->moderated ?? false);
    }

    public function setModerated(bool $moderated): self
    {
        $this->moderated = $moderated;

        return $this;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getThreads(): Collection
    {
        return $this->threads;
    }

    public function getForumLastPost(): ?CForumPost
    {
        return $this->forumLastPost;
    }

    public function setForumLastPost(?CForumPost $forumLastPost): self
    {
        $this->forumLastPost = $forumLastPost;

        return $this;
    }

    public function getLp(): ?CLp
    {
        return $this->lp;
    }

    public function setLp(?CLp $lp): self
    {
        $this->lp = $lp;

        return $this;
    }

    /**
     * @return Collection<int, CForumPost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    #[Groups(['forum:read'])]
    public function getForumVisible(): bool
    {
        $link = $this->getFirstResourceLink();

        if (null === $link) {
            return true;
        }

        return ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    #[Groups(['forum:read'])]
    public function getPosition(): int
    {
        return $this->getFirstResourceLink()?->getDisplayOrder() ?? 0;
    }

    public function getResourceIdentifier(): int
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
