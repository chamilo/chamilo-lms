<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_blog_task')]
#[ORM\Entity]
class CBlogTask
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'task_id', type: 'integer', nullable: false)]
    protected int $taskId;

    #[ORM\Column(name: 'title', type: 'string', length: 250, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: false)]
    protected string $description;

    #[ORM\Column(name: 'color', type: 'string', length: 10, nullable: false)]
    protected string $color;

    #[ORM\Column(name: 'system_task', type: 'boolean', nullable: false)]
    protected bool $systemTask;

    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
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
