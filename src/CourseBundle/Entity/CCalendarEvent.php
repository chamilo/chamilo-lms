<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CoreBundle\Controller\Api\CreateCCalendarEventAction;
use Chamilo\CoreBundle\Controller\Api\UpdateCCalendarEventAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Room;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Calendar events.
 *
 * @ORM\Table(
 *     name="c_calendar_event",
 *     indexes={
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CCalendarEventRepository")
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            //'security' => "is_granted('VIEW', object)",  // the get collection is also filtered by MessageExtension.php
            'security' => "is_granted('ROLE_USER')",
        ],
        'post' => [
            'controller' => CreateCCalendarEventAction::class,
            'security_post_denormalize' => "is_granted('CREATE', object)",
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('VIEW', object)",
        ],
        'put' => [
            'controller' => UpdateCCalendarEventAction::class,
            'deserialize' => false,
            'security' => "is_granted('EDIT', object)",
        ],
        'delete' => [
            'security' => "is_granted('DELETE', object)",
        ],
    ],
    attributes: [
        'security' => "is_granted('ROLE_USER')",
    ],
    denormalizationContext: [
        'groups' => ['calendar_event:write'],
    ],
    normalizationContext: [
        'groups' => ['calendar_event:read', 'resource_node:read'],
    ],
)]

#[ApiFilter(SearchFilter::class, properties: [
    'startDate' => 'exact',
    'endDate' => 'exact',
    'allDay' => 'boolean',
])]

#[ApiFilter(DateFilter::class, strategy: DateFilter::EXCLUDE_NULL)]

class CCalendarEvent extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    protected int $iid;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    #[Assert\NotBlank]
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    protected ?string $content = null;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    protected ?DateTime $startDate = null;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    protected ?DateTime $endDate = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CCalendarEvent", inversedBy="children")
     * @ORM\JoinColumn(name="parent_event_id", referencedColumnName="iid")
     */
    protected ?CCalendarEvent $parentEvent = null;

    /**
     * @var Collection|CCalendarEvent[]
     * @ORM\OneToMany(targetEntity="CCalendarEvent", mappedBy="parentEvent")
     */
    protected Collection $children;

    /**
     * @var Collection|CCalendarEventRepeat[]
     *
     * @ORM\OneToMany(targetEntity="CCalendarEventRepeat", mappedBy="event", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $repeatEvents;

    /**
     * @ORM\Column(name="all_day", type="boolean", nullable=false)
     */
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    protected bool $allDay;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?string $comment = null;

    /**
     * @ORM\Column(name="color", type="string", length=100, nullable=true)
     */
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
    protected ?string $color = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    protected ?Room $room = null;

    /**
     * @var Collection|CCalendarEventAttachment[]
     *
     * @ORM\OneToMany(
     *     targetEntity="CCalendarEventAttachment", mappedBy="event", cascade={"persist", "remove"}
     * )
     */
    protected Collection $attachments;

    /**
     * @ORM\Column(name="collective", type="boolean", options={"default": false}, nullable=false)
     */
    #[Groups(['calendar_event:read', 'calendar_event:write'])]
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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setStartDate(?DateTime $startDate): self
    {
        $this->startDate = $startDate;

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

    public function setEndDate(?DateTime $endDate): self
    {
        $this->endDate = $endDate;

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

    public function setParentEvent(self $parent): self
    {
        $this->parentEvent = $parent;

        return $this;
    }

    public function getParentEvent(): ?self
    {
        return $this->parentEvent;
    }

    /**
     * @return Collection|CCalendarEvent[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(self $event): self
    {
        if (!$this->getChildren()->contains($event)) {
            $this->getChildren()->add($event);
        }

        return $this;
    }

    /**
     * @param Collection|CCalendarEvent[] $children
     */
    public function setChildren(Collection $children): self
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
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
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
    public function getRepeatEvents()
    {
        return $this->repeatEvents;
    }

    /**
     * @param Collection|CCalendarEventRepeat[] $repeatEvents
     *
     * @return CCalendarEvent
     */
    public function setRepeatEvents(Collection $repeatEvents)
    {
        $this->repeatEvents = $repeatEvents;

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
