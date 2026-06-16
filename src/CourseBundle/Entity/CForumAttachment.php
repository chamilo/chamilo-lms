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
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\State\Forum\ForumAttachmentProcessor;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'ForumAttachment',
    operations: [
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Delete(
            uriTemplate: '/forum_attachments/{iid}',
            security: "is_granted('VIEW', object.resourceNode)",
            deserialize: false,
            name: 'delete_forum_attachment',
            processor: ForumAttachmentProcessor::class,
        ),
        new GetCollection(security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')"),
    ],
    normalizationContext: ['groups' => ['forum_attachment:read', 'resource_node:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['post' => 'exact'])]
#[ORM\Table(name: 'c_forum_attachment')]
#[ORM\Index(name: 'course', columns: ['c_id'])]
#[ORM\Entity(repositoryClass: CForumAttachmentRepository::class)]
class CForumAttachment extends AbstractResource implements ResourceInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_attachment:read', 'forum_post:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['forum_attachment:read'])]
    #[ORM\Column(name: 'c_id', type: 'integer')]
    protected int $cId;

    #[Groups(['forum_attachment:read', 'forum_post:read'])]
    #[ORM\Column(name: 'path', type: 'string', length: 255, nullable: false)]
    protected string $path;

    #[Groups(['forum_attachment:read', 'forum_post:read'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[Groups(['forum_attachment:read', 'forum_post:read'])]
    #[ORM\Column(name: 'size', type: 'integer', nullable: false)]
    protected int $size;

    #[ORM\ManyToOne(targetEntity: CForumPost::class, cascade: ['persist'], inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CForumPost $post;

    #[Groups(['forum_attachment:read', 'forum_post:read'])]
    #[ORM\Column(name: 'filename', type: 'string', length: 255, nullable: false)]
    protected string $filename;

    public function __construct() {}

    public function __toString(): string
    {
        return (string) $this->getFilename();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setCId(int $cId): self
    {
        $this->cId = $cId;

        return $this;
    }

    public function getCId(): int
    {
        return $this->cId;
    }

    public function getPost(): CForumPost
    {
        return $this->post;
    }

    public function setPost(CForumPost $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getFilename();
    }

    public function setResourceName(string $name): self
    {
        return $this->setFilename($name);
    }
}
