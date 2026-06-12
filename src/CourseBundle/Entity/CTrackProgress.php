<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores legacy learning path completion flags by course, user and learning path.
 */
#[ORM\Table(name: 'track_progress')]
#[ORM\Index(name: 'idx_track_progress_course_user_lp', columns: ['cId', 'userId', 'lpId'])]
#[ORM\Index(name: 'idx_track_progress_lp', columns: ['lpId'])]
#[ORM\Entity]
class CTrackProgress
{
    #[ORM\Column(name: 'progressId', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?string $progressId = null;

    #[ORM\Column(name: 'cId', type: 'integer', nullable: false)]
    protected int $courseId;

    #[ORM\Column(name: 'userId', type: 'bigint', nullable: false)]
    protected string $userId;

    #[ORM\Column(name: 'lpId', type: 'integer', nullable: false)]
    protected int $lpId;

    #[ORM\Column(name: 'complete', type: 'string', length: 250, nullable: false)]
    protected string $complete;

    public function getProgressId(): ?string
    {
        return $this->progressId;
    }

    public function getCourseId(): int
    {
        return $this->courseId;
    }

    public function setCourseId(int $courseId): self
    {
        $this->courseId = $courseId;

        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string|int $userId): self
    {
        $this->userId = (string) $userId;

        return $this;
    }

    public function getLpId(): int
    {
        return $this->lpId;
    }

    public function setLpId(int $lpId): self
    {
        $this->lpId = $lpId;

        return $this;
    }

    public function getComplete(): string
    {
        return $this->complete;
    }

    public function setComplete(string $complete): self
    {
        $this->complete = $complete;

        return $this;
    }
}
