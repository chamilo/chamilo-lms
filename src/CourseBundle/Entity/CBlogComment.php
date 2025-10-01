<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\CBlogAssignAuthorProcessor;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: CBlogAssignAuthorProcessor::class
        ),
        new Patch(security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_TEACHER') or (object.getAuthor() != null and object.getAuthor() === user)"),
        new Delete(security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_TEACHER') or (object.getAuthor() != null and object.getAuthor() === user)"),
    ],
    normalizationContext: ['groups' => ['blog_comment:read']],
    denormalizationContext: ['groups' => ['blog_comment:write']],
    paginationEnabled: true
)]
#[ApiFilter(SearchFilter::class, properties: [
    'post' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'dateCreation' => 'DESC',
])]
#[ORM\Table(name: 'c_blog_comment')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CBlogComment
{
    #[Groups(['blog_comment:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    /**
     * LEGACY: required non-null column kept for backwards compatibility.
     * We set 0 by default so inserts don't fail.
     */
    #[Groups(['blog_comment:read'])]
    #[ORM\Column(name: 'comment_id', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $commentId = 0;

    #[Groups(['blog_comment:read','blog_comment:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 250, nullable: false)]
    protected string $title = '';

    #[Groups(['blog_comment:read','blog_comment:write'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: false)]
    protected string $comment;

    #[Groups(['blog_comment:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $author = null;

    #[Groups(['blog_comment:read'])]
    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    protected DateTime $dateCreation;

    #[Groups(['blog_comment:read','blog_comment:write'])]
    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CBlog $blog = null;

    /** Real relation to the blog post (keeps DB column name post_id). */
    #[Groups(['blog_comment:read','blog_comment:write'])]
    #[ORM\ManyToOne(targetEntity: CBlogPost::class)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected ?CBlogPost $post = null;

    /** Optional: parent comment (threading). Kept nullable, matches parent_comment_id column. */
    #[Groups(['blog_comment:read','blog_comment:write'])]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_comment_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CBlogComment $parentComment = null;

    public function __construct()
    {
        $this->dateCreation = new DateTime();
        // $this->title already defaults to ''
        // $this->commentId already defaults to 0 (legacy)
    }

    #[ORM\PrePersist]
    public function ensureRequiredFields(): void
    {
        if (!isset($this->commentId)) {
            $this->commentId = 0; // legacy default
        }
        if (!isset($this->dateCreation)) {
            $this->dateCreation = new DateTime();
        }
        if (!isset($this->title)) {
            $this->title = '';
        }
        if ($this->blog === null && $this->post instanceof CBlogPost) {
            $this->blog = $this->post->getBlog();
        }
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getCommentId(): int
    {
        return $this->commentId;
    }

    public function setCommentId(int $commentId): self
    {
        $this->commentId = $commentId;

        return $this;
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

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getDateCreation(): DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getBlog(): ?CBlog
    {
        return $this->blog;
    }

    public function setBlog(?CBlog $blog): self
    {
        $this->blog = $blog;

        return $this;
    }

    public function getPost(): ?CBlogPost
    {
        return $this->post;
    }

    public function setPost(?CBlogPost $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getParentComment(): ?CBlogComment
    {
        return $this->parentComment;
    }

    public function setParentComment(?CBlogComment $parentComment): self
    {
        $this->parentComment = $parentComment;

        return $this;
    }

    #[Groups(['blog_comment:read'])]
    public function getAuthorInfo(): array
    {
        $u = $this->getAuthor();
        if (!$u) {
            return ['id' => null, 'name' => '—'];
        }
        $name = method_exists($u, 'getFullName') ? $u->getFullName()
            : (method_exists($u, 'getUsername') ? $u->getUsername() : 'User');

        return [
            'id'   => method_exists($u, 'getId') ? $u->getId() : null,
            'name' => $name,
        ];
    }
}
