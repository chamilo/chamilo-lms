<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\Controller\Api\CreateBlogAttachmentAction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'c_blog_attachment')]
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Post(
            uriTemplate: '/c_blog_attachments/upload',
            controller: CreateBlogAttachmentAction::class,
            security: "is_granted('IS_AUTHENTICATED_REMEMBERED')",
            output: false,
            deserialize: false
        ),
    ],
    normalizationContext: ['groups' => ['blog_attachment:read']],
    denormalizationContext: ['groups' => ['blog_attachment:write']]
)]
class CBlogAttachment
{
    #[Groups(['blog_attachment:read', 'blog_post:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['blog_attachment:read','blog_attachment:write','blog_post:read'])]
    #[ORM\Column(name: 'path', type: 'string', length: 255, nullable: false)]
    protected string $path;

    #[Groups(['blog_attachment:read','blog_attachment:write','blog_post:read'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[Groups(['blog_attachment:read','blog_attachment:write','blog_post:read'])]
    #[ORM\Column(name: 'size', type: 'integer', nullable: false)]
    protected int $size;

    #[Groups(['blog_attachment:read','blog_attachment:write','blog_post:read'])]
    #[ORM\Column(name: 'filename', type: 'string', length: 255, nullable: false)]
    protected string $filename;

    #[Groups(['blog_attachment:read','blog_attachment:write'])]
    #[ORM\ManyToOne(targetEntity: CBlog::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CBlog $blog = null;

    #[Groups(['blog_attachment:read','blog_attachment:write'])]
    #[ORM\ManyToOne(targetEntity: CBlogPost::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CBlogPost $post = null;

    #[Groups(['blog_attachment:read','blog_attachment:write'])]
    #[ORM\ManyToOne(targetEntity: CBlogComment::class)]
    #[ORM\JoinColumn(name: 'comment_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CBlogComment $commentRef = null;

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

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

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

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

    public function getCommentRef(): ?CBlogComment
    {
        return $this->commentRef;
    }

    public function setCommentRef(?CBlogComment $comment): self
    {
        $this->commentRef = $comment;

        return $this;
    }
}
