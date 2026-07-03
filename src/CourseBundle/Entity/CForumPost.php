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
use Chamilo\CoreBundle\ApiResource\Forum\ForumPostWriteInput;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\Forum\ForumPostActionProvider;
use Chamilo\CoreBundle\State\Forum\ForumPostCreateStateProvider;
use Chamilo\CoreBundle\State\Forum\ForumPostProcessor;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CForumPost.
 */
#[ApiResource(
    shortName: 'ForumPost',
    operations: [
        new Post(
            uriTemplate: '/forum_posts/reply',
            inputFormats: [
                'jsonld' => ['application/ld+json'],
                'json' => ['application/json'],
                'multipart' => ['multipart/form-data'],
            ],
            openapi: new Operation(
                summary: 'Reply to a forum thread',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'forumId' => ['type' => 'integer'],
                                    'threadId' => ['type' => 'integer'],
                                    'parentPostId' => ['type' => 'integer'],
                                    'title' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                    'csrfToken' => ['type' => 'string'],
                                ],
                                'required' => ['forumId', 'threadId', 'title', 'text', 'csrfToken'],
                            ],
                        ],
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'forumId' => ['type' => 'integer'],
                                    'threadId' => ['type' => 'integer'],
                                    'parentPostId' => ['type' => 'integer'],
                                    'title' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                    'csrfToken' => ['type' => 'string'],
                                    'attachments' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string', 'format' => 'binary'],
                                    ],
                                ],
                                'required' => ['forumId', 'threadId', 'title', 'text', 'csrfToken'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            input: ForumPostWriteInput::class,
            provider: ForumPostCreateStateProvider::class,
            processor: ForumPostProcessor::class,
            deserialize: false,
            read: true,
            name: 'create_forum_reply',
        ),
        new Put(
            uriTemplate: '/forum_posts/{iid}/update',
            security: "is_granted('VIEW', object.resourceNode)",
            deserialize: false,
            name: 'update_forum_post',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_posts/{iid}/toggle-visibility',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'toggle_forum_post_visibility',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_posts/{iid}/approve',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'approve_forum_post',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_posts/{iid}/reject',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'reject_forum_post',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_posts/{iid}/move',
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
            name: 'move_forum_post',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_posts/{iid}/ask-revision',
            security: "is_granted('VIEW', object.resourceNode)",
            deserialize: false,
            name: 'ask_forum_post_revision',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Put(
            uriTemplate: '/forum_posts/{iid}/report',
            security: "is_granted('VIEW', object.resourceNode)",
            deserialize: false,
            name: 'report_forum_post',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Delete(
            uriTemplate: '/forum_posts/{iid}',
            security: "is_granted('VIEW', object.resourceNode)",
            deserialize: false,
            name: 'delete_forum_post',
            provider: ForumPostActionProvider::class,
            processor: ForumPostProcessor::class,
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new GetCollection(
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'thread',
                        in: 'query',
                        description: 'Thread IRI',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                    new Parameter(
                        name: 'forum',
                        in: 'query',
                        description: 'Forum IRI',
                        required: false,
                        schema: ['type' => 'string'],
                    ),
                    new Parameter(
                        name: 'visible',
                        in: 'query',
                        description: 'Visible posts only',
                        required: false,
                        schema: ['type' => 'boolean'],
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
        'groups' => ['forum_post:read', 'resource_node:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'thread' => 'exact',
    'forum' => 'exact',
    'visible' => 'exact',
    'status' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['postDate', 'iid'])]
#[ORM\Table(name: 'c_forum_post')]
#[ORM\Index(name: 'forum_id', columns: ['forum_id'])]
#[ORM\Index(name: 'idx_forum_post_thread_id', columns: ['thread_id'])]
#[ORM\Index(name: 'idx_forum_post_visible', columns: ['visible'])]
#[ORM\Entity(repositoryClass: CForumPostRepository::class)]
class CForumPost extends AbstractResource implements ResourceInterface, Stringable
{
    public const STATUS_VALIDATED = 1;
    public const STATUS_WAITING_MODERATION = 2;
    public const STATUS_REJECTED = 3;

    #[ApiProperty(identifier: true)]
    #[Groups(['forum_post:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['forum_post:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 250, nullable: false)]
    protected string $title;

    #[Groups(['forum_post:read'])]
    #[ORM\Column(name: 'post_text', type: 'text', nullable: true)]
    protected ?string $postText = null;

    #[Assert\NotBlank]
    #[Groups(['forum_post:read'])]
    #[ORM\Column(name: 'post_date', type: 'datetime', nullable: false)]
    protected DateTime $postDate;

    #[ORM\Column(name: 'post_notification', type: 'boolean', nullable: true)]
    protected ?bool $postNotification = null;

    #[Assert\NotNull]
    #[Groups(['forum_post:read'])]
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false)]
    protected bool $visible;

    #[Groups(['forum_post:read'])]
    #[ORM\Column(name: 'status', type: 'integer', nullable: true)]
    protected ?int $status = null;

    #[Groups(['forum_post:read'])]
    #[ORM\ManyToOne(targetEntity: CForumThread::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'thread_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CForumThread $thread = null;

    #[Groups(['forum_post:read'])]
    #[ORM\ManyToOne(targetEntity: CForum::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'forum_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CForum $forum = null;

    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'poster_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'post_parent_id', referencedColumnName: 'iid', onDelete: 'SET NULL')]
    protected ?CForumPost $postParent = null;

    /**
     * @var Collection|CForumPost[]
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'postParent')]
    protected Collection $children;

    /**
     * @var Collection|CForumAttachment[]
     */
    #[Groups(['forum_post:read'])]
    #[ORM\OneToMany(targetEntity: CForumAttachment::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $attachments;

    public function __construct()
    {
        $this->postDate = new DateTime();
        $this->visible = false;
        $this->attachments = new ArrayCollection();
        $this->children = new ArrayCollection();
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

    public function setPostText(string $postText): self
    {
        $this->postText = $postText;

        return $this;
    }

    public function getPostText(): ?string
    {
        return $this->postText;
    }

    public function setThread(?CForumThread $thread = null): self
    {
        if (null !== $thread) {
            $thread->getPosts()->add($this);
        }
        $this->thread = $thread;

        return $this;
    }

    public function getThread(): ?CForumThread
    {
        return $this->thread;
    }

    public function setPostDate(DateTime $postDate): self
    {
        $this->postDate = $postDate;

        return $this;
    }

    /**
     * Get postDate.
     *
     * @return DateTime
     */
    public function getPostDate()
    {
        return $this->postDate;
    }

    public function setPostNotification(bool $postNotification): self
    {
        $this->postNotification = $postNotification;

        return $this;
    }

    /**
     * Get postNotification.
     *
     * @return bool
     */
    public function getPostNotification()
    {
        return $this->postNotification;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get iid.
     */
    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function removeAttachment(CForumAttachment $attachment): void
    {
        $this->attachments->removeElement($attachment);
    }

    public function getForum(): ?CForum
    {
        return $this->forum;
    }

    public function setForum(?CForum $forum): self
    {
        $forum->getPosts()->add($this);

        $this->forum = $forum;

        return $this;
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

    public function getPostParent(): ?self
    {
        return $this->postParent;
    }

    public function setPostParent(?self $postParent): self
    {
        $this->postParent = $postParent;

        return $this;
    }

    /**
     * @return CForumPost[]|Collection
     */
    public function getChildren(): array|Collection
    {
        return $this->children;
    }

    /**
     * @param CForumPost[]|Collection $children
     */
    public function setChildren(array|Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    #[Groups(['forum_post:read'])]
    public function getPosterFullName(): string
    {
        if (null === $this->user) {
            return '';
        }

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
