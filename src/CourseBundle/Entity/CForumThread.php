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
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\ApiResource\Forum\ForumThreadWriteInput;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\Forum\ForumThreadCollectionStateProvider;
use Chamilo\CoreBundle\State\Forum\ForumThreadCreateStateProvider;
use Chamilo\CoreBundle\State\Forum\ForumThreadProcessor;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CForumThread.
 */
#[ApiResource(
    shortName: 'ForumThread',
    operations: [
        new Post(
            uriTemplate: '/forum_threads/create',
            name: 'create_forum_thread',
            input: ForumThreadWriteInput::class,
            provider: ForumThreadCreateStateProvider::class,
            processor: ForumThreadProcessor::class,
            deserialize: false,
            read: true,
            inputFormats: [
                'jsonld' => ['application/ld+json'],
                'json' => ['application/json'],
                'multipart' => ['multipart/form-data'],
            ],
            openapi: new Operation(
                summary: 'Create a new forum thread',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'forumId' => ['type' => 'integer'],
                                    'title' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                    'csrfToken' => ['type' => 'string'],
                                    'threadSticky' => ['type' => 'boolean'],
                                ],
                                'required' => ['forumId', 'title', 'text', 'csrfToken'],
                            ],
                        ],
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'forumId' => ['type' => 'integer'],
                                    'title' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                    'csrfToken' => ['type' => 'string'],
                                    'threadSticky' => ['type' => 'boolean'],
                                    'attachments' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string', 'format' => 'binary'],
                                    ],
                                ],
                                'required' => ['forumId', 'title', 'text', 'csrfToken'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
        ),
        new Put(
            uriTemplate: '/forum_threads/{iid}/update',
            name: 'update_forum_thread',
            processor: ForumThreadProcessor::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
        ),
        new Put(
            uriTemplate: '/forum_threads/{iid}/toggle-lock',
            name: 'toggle_forum_thread_lock',
            processor: ForumThreadProcessor::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
        ),
        new Put(
            uriTemplate: '/forum_threads/{iid}/toggle-sticky',
            name: 'toggle_forum_thread_sticky',
            processor: ForumThreadProcessor::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
        ),
        new Put(
            uriTemplate: '/forum_threads/{iid}/toggle-visibility',
            name: 'toggle_forum_thread_visibility',
            processor: ForumThreadProcessor::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
        ),
        new Put(
            uriTemplate: '/forum_threads/{iid}/move',
            name: 'move_forum_thread',
            processor: ForumThreadProcessor::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
        ),
        new Put(
            uriTemplate: '/forum_threads/{iid}/toggle-subscription',
            name: 'toggle_forum_thread_subscription',
            processor: ForumThreadProcessor::class,
            security: "is_granted('VIEW', object.resourceNode)",
            deserialize: false,
        ),
        new Delete(
            uriTemplate: '/forum_threads/{iid}',
            name: 'delete_forum_thread',
            processor: ForumThreadProcessor::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new GetCollection(
            provider: ForumThreadCollectionStateProvider::class,
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'forum',
                        in: 'query',
                        description: 'Forum IRI',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                    new Parameter(
                        name: 'resourceNode.parent',
                        in: 'query',
                        description: 'Resource node parent',
                        required: false,
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
        ),
    ],
    normalizationContext: [
        'groups' => ['forum_thread:read', 'resource_node:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'resourceNode.parent' => 'exact',
    'forum' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['threadSticky', 'threadDate', 'iid'])]
#[ORM\Table(name: 'c_forum_thread')]
#[ORM\Entity(repositoryClass: CForumThreadRepository::class)]
class CForumThread extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_thread:read', 'forum_post:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['forum_thread:read', 'forum_post:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['forum_thread:read', 'forum_post:read'])]
    #[ORM\ManyToOne(targetEntity: CForum::class, inversedBy: 'threads')]
    #[ORM\JoinColumn(name: 'forum_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CForum $forum = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'thread_poster_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: CForumPost::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'thread_last_post', referencedColumnName: 'iid', onDelete: 'SET NULL')]
    protected ?CForumPost $threadLastPost = null;

    #[ORM\ManyToOne(targetEntity: CLpItem::class)]
    #[ORM\JoinColumn(name: 'lp_item_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CLpItem $item = null;

    /**
     * @var Collection<int, CForumPost>
     */
    #[ORM\OneToMany(mappedBy: 'thread', targetEntity: CForumPost::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $posts;

    /**
     * @var Collection<int, CForumThreadQualify>
     */
    #[ORM\OneToMany(mappedBy: 'thread', targetEntity: CForumThreadQualify::class, cascade: ['persist', 'remove'])]
    protected Collection $qualifications;

    #[Assert\NotBlank]
    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'thread_date', type: 'datetime', nullable: false)]
    protected DateTime $threadDate;

    #[Assert\NotBlank]
    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'thread_replies', type: 'integer', nullable: false, options: ['unsigned' => true, 'default' => 0])]
    protected int $threadReplies;

    #[Assert\NotBlank]
    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'thread_views', type: 'integer', nullable: false, options: ['unsigned' => true, 'default' => 0])]
    protected int $threadViews;

    #[Assert\NotNull]
    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'thread_sticky', type: 'boolean', nullable: false)]
    protected bool $threadSticky;

    #[Assert\NotBlank]
    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    #[ORM\Column(name: 'thread_title_qualify', type: 'string', length: 255, nullable: true)]
    protected ?string $threadTitleQualify = null;

    #[ORM\Column(name: 'thread_qualify_max', type: 'float', precision: 6, scale: 2, nullable: false)]
    protected float $threadQualifyMax;

    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'thread_close_date', type: 'datetime', nullable: true)]
    protected ?DateTime $threadCloseDate = null;

    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'thread_weight', type: 'float', precision: 6, scale: 2, nullable: false)]
    protected float $threadWeight;

    #[Groups(['forum_thread:read'])]
    #[ORM\Column(name: 'thread_peer_qualify', type: 'boolean')]
    protected bool $threadPeerQualify;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->qualifications = new ArrayCollection();
        $this->threadDate = new DateTime();
        $this->threadPeerQualify = false;
        $this->threadReplies = 0;
        $this->threadViews = 0;
        $this->locked = 0;
        $this->threadQualifyMax = 0;
        $this->threadWeight = 0;
        $this->threadSticky = false;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function isThreadPeerQualify(): bool
    {
        return $this->threadPeerQualify;
    }

    public function setThreadPeerQualify(bool $threadPeerQualify): self
    {
        $this->threadPeerQualify = $threadPeerQualify;

        return $this;
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

    public function setForum(?CForum $forum = null): self
    {
        if (null !== $forum) {
            $forum->getThreads()->add($this);
        }
        $this->forum = $forum;

        return $this;
    }

    public function getForum(): ?CForum
    {
        return $this->forum;
    }

    public function setThreadReplies(int $threadReplies): self
    {
        $this->threadReplies = $threadReplies;

        return $this;
    }

    public function getThreadReplies(): int
    {
        return $this->threadReplies;
    }

    public function setThreadViews(int $threadViews): self
    {
        $this->threadViews = $threadViews;

        return $this;
    }

    public function getThreadViews(): int
    {
        return $this->threadViews;
    }

    public function setThreadDate(DateTime $threadDate): self
    {
        $this->threadDate = $threadDate;

        return $this;
    }

    public function getThreadDate(): DateTime
    {
        return $this->threadDate;
    }

    public function setThreadSticky(bool $threadSticky): self
    {
        $this->threadSticky = $threadSticky;

        return $this;
    }

    public function getThreadSticky(): bool
    {
        return $this->threadSticky;
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

    public function setThreadTitleQualify(string $threadTitleQualify): self
    {
        $this->threadTitleQualify = $threadTitleQualify;

        return $this;
    }

    public function getThreadTitleQualify(): ?string
    {
        return $this->threadTitleQualify;
    }

    public function setThreadQualifyMax(float $threadQualifyMax): self
    {
        $this->threadQualifyMax = $threadQualifyMax;

        return $this;
    }

    public function getThreadQualifyMax(): float
    {
        return $this->threadQualifyMax;
    }

    public function setThreadCloseDate(DateTime $threadCloseDate): self
    {
        $this->threadCloseDate = $threadCloseDate;

        return $this;
    }

    public function getThreadCloseDate(): ?DateTime
    {
        return $this->threadCloseDate;
    }

    public function setThreadWeight(float $threadWeight): self
    {
        $this->threadWeight = $threadWeight;

        return $this;
    }

    public function getThreadWeight(): float
    {
        return $this->threadWeight;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, CForumPost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getThreadLastPost(): ?CForumPost
    {
        return $this->threadLastPost;
    }

    public function setThreadLastPost(?CForumPost $threadLastPost): self
    {
        $this->threadLastPost = $threadLastPost;

        return $this;
    }

    /**
     * @return Collection<int, CForumThreadQualify>
     */
    public function getQualifications(): Collection
    {
        return $this->qualifications;
    }

    public function setQualifications(Collection $qualifications): self
    {
        $this->qualifications = $qualifications;

        return $this;
    }

    public function getItem(): ?CLpItem
    {
        return $this->item;
    }

    public function setItem(?CLpItem $item): self
    {
        $this->item = $item;

        return $this;
    }

    #[Groups(['forum_thread:read'])]
    public function getPosterFullName(): string
    {
        return $this->user->getFullName();
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
