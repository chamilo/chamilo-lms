<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'c_blog_task')]
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
    ],
    normalizationContext: ['groups' => ['blog_task:read']],
    denormalizationContext: ['groups' => ['blog_task:write']]
)]
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

    #[ORM\Column(name: 'description', type: 'text', nullable: false, options: ['default' => ''])]
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
        return $this->description;
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
}
