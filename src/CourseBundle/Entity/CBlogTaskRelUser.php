<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'c_blog_task_rel_user')]
#[ORM\UniqueConstraint(name: 'uniq_task_user_blog_date', columns: ['task_id', 'user_id', 'blog_id', 'target_date'])]
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(security: "
            object.getUser() === user
            or is_granted('ROLE_CURRENT_COURSE_TEACHER')
            or is_granted('ROLE_TEACHER')
            or is_granted('ROLE_ADMIN')
        "),
    ],
    normalizationContext: ['groups' => ['task_rel_user:read']],
    denormalizationContext: ['groups' => ['task_rel_user:write']]
)]
class CBlogTaskRelUser
{
    use UserTrait;

    public const STATUS_OPEN = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_WAITING_TEST = 2;
    public const STATUS_DONE = 3;

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['task_rel_user:read'])]
    protected ?int $iid = null;

    #[ORM\Column(name: 'target_date', type: 'date', nullable: false)]
    #[Groups(['task_rel_user:read', 'task_rel_user:write'])]
    protected DateTime $targetDate;

    #[ORM\ManyToOne(targetEntity: CBlogTask::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['task_rel_user:read', 'task_rel_user:write'])]
    protected ?CBlogTask $task = null;

    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['task_rel_user:read', 'task_rel_user:write'])]
    protected ?CBlog $blog = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['task_rel_user:read', 'task_rel_user:write'])]
    protected User $user;

    #[ORM\Column(name: 'status', type: 'smallint', options: ['default' => 0])]
    #[Groups(['task_rel_user:read', 'task_rel_user:write'])]
    protected int $status = self::STATUS_OPEN;

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getTargetDate(): DateTime
    {
        return $this->targetDate;
    }

    public function setTargetDate(DateTime $targetDate): self
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    public function getTask(): ?CBlogTask
    {
        return $this->task;
    }

    public function setTask(?CBlogTask $task): self
    {
        $this->task = $task;

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

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
