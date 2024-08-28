<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_blog_task_rel_user')]
#[ORM\Entity]
class CBlogTaskRelUser
{
    use UserTrait;

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'target_date', type: 'date', nullable: false)]
    protected DateTime $targetDate;

    #[ORM\ManyToOne(targetEntity: CBlogTask::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CBlogTask $task = null;

    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CBlog $blog = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

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
}
