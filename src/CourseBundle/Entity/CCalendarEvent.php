<?php

declare (strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Controller\Api\CreateCCalendarEventAction;
use Chamilo\CoreBundle\Controller\Api\UpdateCCalendarEventAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Calendar events.
 */
#[ApiResource(
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new Put(
            controller: UpdateCCalendarEventAction::class,
            security: "is_granted('EDIT', object)",
            deserialize: false
        ),
        new Delete(security: "is_granted('DELETE', object)"),
        new GetCollection(security: 'is_granted(\'ROLE_USER\')'),
        new Post(controller: CreateCCalendarEventAction::class, securityPostDenormalize: "is_granted('CREATE', object)"),
    ],
    normalizationContext: ['groups' => ['calendar_event:read', 'resource_node:read']],
    denormalizationContext: ['groups' => ['calendar_event:write']],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Table(name: 'c_calendar_event')]
#[ORM\Entity(repositoryClass: CCalendarEventRepository::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['allDay' => 'boolean'])]
#[ApiFilter(filterClass: DateFilter::class, strategy: 'exclude_null')]
class CCalendarEvent extends AbstractResource implements ResourceInterface, Stringable
{
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotBlank]
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Assert\NotBlank]
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    protected ?string $content = null;

    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    protected ?DateTime $startDate = null;

    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    protected ?DateTime $endDate = null;

    #[ORM\ManyToOne(targetEntity: CCalendarEvent::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_event_id', referencedColumnName: 'iid')]
    protected ?CCalendarEvent $parentEvent = null;

    /**
     * @var Collection|CCalendarEvent[]
     */
    #[ORM\OneToMany(targetEntity: CCalendarEvent::class, mappedBy: 'parentEvent')]
    protected Collection $children;

    /**
     * @var Collection|CCalendarEventRepeat[]
     */
    #[ORM\OneToMany(targetEntity: CCalendarEventRepeat::class, mappedBy: 'event', cascade: ['persist'], orphanRemoval: true)]
    protected Collection $repeatEvents;
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[Assert\NotNull]
    #[ORM\Column(name: 'all_day', type: 'boolean', nullable: false)]
    protected bool $allDay;
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[ORM\Column(name: 'color', type: 'string', length: 20, nullable: true)]
    protected ?string $color = null;
    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id')]
    protected ?Room $room = null;
    /**
     * @var Collection|CCalendarEventAttachment[]
     */
    #[ORM\OneToMany(targetEntity: 'CCalendarEventAttachment', mappedBy: 'event', cascade: ['persist', 'remove'])]
    protected Collection $attachments;
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    #[Assert\NotNull]
    #[ORM\Column(name: 'collective', type: 'boolean', nullable: false)]
    protected bool $collective = false;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->repeatEvents = new ArrayCollection();
        $this->allDay = false;
        $this->collective = false;
    }

    public function __toString(): string
    {
        return $this->getTitle();
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getParentEvent(): ?self
    {
        return $this->parentEvent;
    }

    public function setParentEvent(self $parent): self
    {
        $this->parentEvent = $parent;

        return $this;
    }

    public function addChild(self $event): self
    {
        if (!$this->getChildren()->contains($event)) {
            $this->getChildren()->add($event);
        }

        return $this;
    }

    /**
     * @return Collection|CCalendarEvent[]
     */
    public function getChildren(): Collection|array
    {
        return $this->children;
    }

    /**
     * @param Collection|CCalendarEvent[] $children
     */
    public function setChildren(Collection|array $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
    {
        $this->room = $room;

        return $this;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    public function setAttachments(Collection $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function addAttachment(CCalendarEventAttachment $attachment): self
    {
        $this->attachments->add($attachment);

        return $this;
    }

    /**
     * @return Collection|CCalendarEventRepeat[]
     */
    public function getRepeatEvents(): Collection|array
    {
        return $this->repeatEvents;
    }

    /**
     * @param Collection|CCalendarEventRepeat[] $repeatEvents
     *
     * @return CCalendarEvent
     */
    public function setRepeatEvents(Collection|array $repeatEvents)
    {
        $this->repeatEvents = $repeatEvents;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }

    public function isCollective(): bool
    {
        return $this->collective;
    }

    public function setCollective(bool $collective): self
    {
        $this->collective = $collective;

        return $this;
    }
}
