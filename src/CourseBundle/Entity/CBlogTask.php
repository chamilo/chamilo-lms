<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'c_blog_task')]
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(security: "object.getBlog() != null and is_granted('VIEW', object.getBlog().resourceNode)"),
        new GetCollection(security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')"),
        new Post(
            securityPostDenormalize: "object.getBlog() != null and is_granted('EDIT', object.getBlog().resourceNode)",
            processor: CBlogAssignAuthorProcessor::class
        ),
        new Patch(security: "object.getBlog() != null and (is_granted('EDIT', object.getBlog().resourceNode) or (object.getAuthor() === user and is_granted('VIEW', object.getBlog().resourceNode)))"),
        new Delete(security: "object.getBlog() != null and (is_granted('DELETE', object.getBlog().resourceNode) or (object.getAuthor() === user and is_granted('VIEW', object.getBlog().resourceNode)))"),
    ],
    normalizationContext: ['groups' => ['blog_task:read']],
    denormalizationContext: ['groups' => ['blog_task:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['blog' => 'exact'])]
class CBlogTask
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['blog_task:read', 'task_rel_user:read'])]
    protected ?int $iid = null;

    #[ORM\Column(name: 'task_id', type: 'integer', nullable: false, options: ['default' => 0])]
    #[Groups(['blog_task:read', 'blog_task:write', 'task_rel_user:read'])]
    protected int $taskId = 0;

    #[ORM\Column(name: 'title', type: 'string', length: 250, nullable: false)]
    #[Groups(['blog_task:read', 'blog_task:write', 'task_rel_user:read'])]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Groups(['blog_task:read', 'blog_task:write'])]
    protected string $description = '';

    #[ORM\Column(name: 'color', type: 'string', length: 10, nullable: false, options: ['default' => '#0ea5e9'])]
    #[Groups(['blog_task:read', 'blog_task:write'])]
    protected string $color = '#0ea5e9';

    #[ORM\Column(name: 'system_task', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Groups(['blog_task:read', 'blog_task:write'])]
    protected bool $systemTask = false;

    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['blog_task:read', 'blog_task:write'])]
    protected ?CBlog $blog = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['blog_task:read'])]
    protected ?User $author = null;

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): self
    {
        $this->taskId = $taskId;

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

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function isSystemTask(): bool
    {
        return $this->systemTask;
    }

    public function setSystemTask(bool $systemTask): self
    {
        $this->systemTask = $systemTask;

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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    #[Groups(['blog_task:read'])]
    public function getAuthorId(): ?int
    {
        return method_exists($this->author, 'getId') ? $this->author->getId() : null;
    }
}
