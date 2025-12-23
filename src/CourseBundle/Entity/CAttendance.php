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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Filter\SidFilter;
use Chamilo\CoreBundle\State\CAttendanceStateProcessor;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Attendance',
    operations: [
        new Put(
            uriTemplate: '/attendances/{iid}/toggle_visibility',
            openapi: new Operation(
                summary: 'Toggle visibility of the attendance\'s associated ResourceLink'
            ),
            security: "is_granted('EDIT', object.resourceNode)",
            name: 'toggle_visibility',
            processor: CAttendanceStateProcessor::class
        ),
        new Put(
            uriTemplate: '/attendances/{iid}/soft_delete',
            openapi: new Operation(
                summary: 'Soft delete the attendance'
            ),
            security: "is_granted('EDIT', object.resourceNode)",
            name: 'soft_delete',
            processor: CAttendanceStateProcessor::class
        ),
        new Delete(security: "is_granted('ROLE_TEACHER')"),
        new Post(
            uriTemplate: '/attendances/{iid}/calendars',
            openapi: new Operation(
                summary: 'Add a calendar to an attendance.'
            ),
            denormalizationContext: ['groups' => ['attendance:write']],
            name: 'calendar_add',
            processor: CAttendanceStateProcessor::class
        ),
        new GetCollection(
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'resourceNode.parent',
                        in: 'query',
                        description: 'Resource node Parent',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
        ),
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(
            denormalizationContext: ['groups' => ['attendance:write']],
            security: "is_granted('ROLE_TEACHER')",
            validationContext: ['groups' => ['Default']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['attendance:write']],
            security: "is_granted('ROLE_TEACHER')"
        ),
    ],
    normalizationContext: [
        'groups' => ['attendance:read', 'resource_node:read', 'resource_link:read'],
        'enable_max_depth' => true,
    ],
    denormalizationContext: ['groups' => ['attendance:write']],
    paginationEnabled: true,
)]
#[ApiFilter(SearchFilter::class, properties: ['active' => 'exact', 'title' => 'partial', 'resourceNode.parent' => 'exact'])]
#[ApiFilter(filterClass: SidFilter::class)]
#[ORM\Table(name: 'c_attendance')]
#[ORM\Index(columns: ['active'], name: 'active')]
#[ORM\Entity(repositoryClass: CAttendanceRepository::class)]
class CAttendance extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['attendance:read'])]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    #[Groups(['attendance:read', 'attendance:write'])]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Groups(['attendance:read', 'attendance:write'])]
    protected ?string $description = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'active', type: 'integer', nullable: false)]
    #[Groups(['attendance:read', 'attendance:write'])]
    protected int $active = 1;

    #[ORM\Column(name: 'attendance_qualify_title', type: 'string', length: 255, nullable: true)]
    #[Groups(['attendance:read', 'attendance:write'])]
    protected ?string $attendanceQualifyTitle = null;

    #[Assert\NotNull]
    #[ORM\Column(name: 'attendance_qualify_max', type: 'integer', nullable: false)]
    protected int $attendanceQualifyMax;

    #[Assert\NotNull]
    #[ORM\Column(name: 'attendance_weight', type: 'float', precision: 6, scale: 2, nullable: false)]
    #[Groups(['attendance:read', 'attendance:write'])]
    protected float $attendanceWeight = 0.0;

    #[Assert\NotNull]
    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    #[ORM\Column(name: 'require_unique', type: 'boolean', options: ['default' => false])]
    #[Groups(['attendance:read', 'attendance:write'])]
    protected bool $requireUnique = false;

    /**
     * @var Collection|CAttendanceCalendar[]
     */
    #[ORM\OneToMany(mappedBy: 'attendance', targetEntity: CAttendanceCalendar::class, cascade: ['persist', 'remove'])]
    #[Groups(['attendance:read'])]
    #[MaxDepth(1)]
    protected Collection $calendars;

    /**
     * @var Collection|CAttendanceResult[]
     */
    #[ORM\OneToMany(mappedBy: 'attendance', targetEntity: CAttendanceResult::class, cascade: ['persist', 'remove'])]
    protected Collection $results;

    /**
     * @var Collection|CAttendanceSheetLog[]
     */
    #[ORM\OneToMany(mappedBy: 'attendance', targetEntity: CAttendanceSheetLog::class, cascade: ['persist', 'remove'])]
    protected Collection $logs;

    #[Groups(['attendance:read'])]
    private ?int $doneCalendars = null;

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
        return $this->getTitle();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
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
     */
    public function getAttendanceQualifyMax(): int
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
     */
    public function getAttendanceWeight(): float
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
     */
    public function getLocked(): int
    {
        return $this->locked;
    }

    public function getIid(): ?int
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

    public function addCalendar(CAttendanceCalendar $calendar): self
    {
        if (!$this->calendars->contains($calendar)) {
            $this->calendars->add($calendar);
            $calendar->setAttendance($this);
        }

        return $this;
    }

    /**
     * @return CAttendanceSheetLog[]|Collection
     */
    public function getLogs(): array|Collection
    {
        return $this->logs;
    }

    public function getDoneCalendars(): int
    {
        return $this->calendars
            ->filter(fn (CAttendanceCalendar $calendar) => $calendar->getDoneAttendance())
            ->count()
        ;
    }

    public function setDoneCalendars(?int $count): self
    {
        $this->doneCalendars = $count;

        return $this;
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

    public function isRequireUnique(): bool
    {
        return $this->requireUnique;
    }

    public function setRequireUnique(bool $requireUnique): self
    {
        $this->requireUnique = $requireUnique;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
