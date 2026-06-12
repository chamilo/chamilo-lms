<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores legacy learning path scheduling metadata.
 */
#[ORM\Table(name: 'paramedic')]
#[ORM\Index(name: 'idx_lp_schedule_course_lp', columns: ['cId', 'lpId'])]
#[ORM\Entity]
class CLpSchedule
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'cId', type: 'bigint', nullable: false)]
    protected string $courseId;

    #[ORM\Column(name: 'lpId', type: 'bigint', nullable: false)]
    protected string $lpId;

    #[ORM\Column(name: 'title', type: 'string', length: 100, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'weekofday', type: 'string', length: 100, nullable: true)]
    protected ?string $weekOfDay = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourseId(): string
    {
        return $this->courseId;
    }

    public function setCourseId(string|int $courseId): self
    {
        $this->courseId = (string) $courseId;

        return $this;
    }

    public function getLpId(): string
    {
        return $this->lpId;
    }

    public function setLpId(string|int $lpId): self
    {
        $this->lpId = (string) $lpId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getWeekOfDay(): ?string
    {
        return $this->weekOfDay;
    }

    public function setWeekOfDay(?string $weekOfDay): self
    {
        $this->weekOfDay = $weekOfDay;

        return $this;
    }
}
