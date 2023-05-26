<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_attendance')]
#[ORM\Index(name: 'active', columns: ['active'])]
#[ORM\Entity(repositoryClass: \Chamilo\CourseBundle\Repository\CAttendanceRepository::class)]
class CAttendance extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'name', type: 'text', nullable: false)]
    protected string $name;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'active', type: 'integer', nullable: false)]
    protected int $active;

    #[ORM\Column(name: 'attendance_qualify_title', type: 'string', length: 255, nullable: true)]
    protected ?string $attendanceQualifyTitle = null;

    #[Assert\NotNull]
    #[ORM\Column(name: 'attendance_qualify_max', type: 'integer', nullable: false)]
    protected int $attendanceQualifyMax;

    #[Assert\NotNull]
    #[ORM\Column(name: 'attendance_weight', type: 'float', precision: 6, scale: 2, nullable: false)]
    protected float $attendanceWeight;

    #[Assert\NotNull]
    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    /**
     * @var Collection|CAttendanceCalendar[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CourseBundle\Entity\CAttendanceCalendar::class, mappedBy: 'attendance', cascade: ['persist', 'remove'])]
    protected Collection $calendars;

    /**
     * @var Collection|CAttendanceResult[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CourseBundle\Entity\CAttendanceResult::class, mappedBy: 'attendance', cascade: ['persist', 'remove'])]
    protected Collection $results;

    /**
     * @var Collection|CAttendanceSheetLog[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CourseBundle\Entity\CAttendanceSheetLog::class, mappedBy: 'attendance', cascade: ['persist', 'remove'])]
    protected Collection $logs;

    public function __construct()
    {
        $this->description = '';
        $this->active = 1;
        $this->attendanceQualifyMax = 0;
        $this->locked = 0;
        $this->calendars = new ArrayCollection();
        $this->results = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function setAttendanceQualifyTitle(string $attendanceQualifyTitle): self
    {
        $this->attendanceQualifyTitle = $attendanceQualifyTitle;

        return $this;
    }

    public function getAttendanceQualifyTitle(): ?string
    {
        return $this->attendanceQualifyTitle;
    }

    public function setAttendanceQualifyMax(int $attendanceQualifyMax): self
    {
        $this->attendanceQualifyMax = $attendanceQualifyMax;

        return $this;
    }

    /**
     * Get attendanceQualifyMax.
     *
     * @return int
     */
    public function getAttendanceQualifyMax()
    {
        return $this->attendanceQualifyMax;
    }

    public function setAttendanceWeight(float $attendanceWeight): self
    {
        $this->attendanceWeight = $attendanceWeight;

        return $this;
    }

    /**
     * Get attendanceWeight.
     *
     * @return float
     */
    public function getAttendanceWeight()
    {
        return $this->attendanceWeight;
    }

    public function setLocked(int $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return int
     */
    public function getLocked()
    {
        return $this->locked;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getCalendars(): Collection
    {
        return $this->calendars;
    }

    public function setCalendars(Collection $calendars): self
    {
        $this->calendars = $calendars;

        return $this;
    }

    /**
     * @return CAttendanceSheetLog[]|Collection
     */
    public function getLogs(): array|Collection
    {
        return $this->logs;
    }

    /**
     * @param CAttendanceSheetLog[]|Collection $logs
     */
    public function setLogs(array|Collection $logs): self
    {
        $this->logs = $logs;

        return $this;
    }

    /**
     * @return CAttendanceResult[]|Collection
     */
    public function getResults(): array|Collection
    {
        return $this->results;
    }

    public function setResults(Collection $results): self
    {
        $this->results = $results;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
