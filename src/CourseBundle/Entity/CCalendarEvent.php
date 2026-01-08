<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

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
use Chamilo\CoreBundle\ApiResource\CalendarEvent;
use Chamilo\CoreBundle\Controller\Api\UpdateCCalendarEventAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceRestrictToGroupContextInterface;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Filter\CidFilter;
use Chamilo\CoreBundle\Filter\GlobalEventFilter;
use Chamilo\CoreBundle\Filter\SidFilter;
use Chamilo\CoreBundle\State\CalendarEventStateProvider;
use Chamilo\CoreBundle\State\CCalendarEventStateProcessor;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
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
        new GetCollection(
            paginationEnabled: false,
            security: "is_granted('ROLE_USER')",
            output: CalendarEvent::class,
            provider: CalendarEventStateProvider::class,
        ),
        new Post(
            securityPostDenormalize: "is_granted('CREATE', object)",
            processor: CCalendarEventStateProcessor::class
        ),
    ],
    normalizationContext: [
        'groups' => ['calendar_event:read', 'resource_node:read'],
        'enable_max_depth' => true,
    ],
    denormalizationContext: ['groups' => ['calendar_event:write']],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Table(name: 'c_calendar_event')]
#[ORM\Entity(repositoryClass: CCalendarEventRepository::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['allDay' => 'boolean'])]
#[ApiFilter(filterClass: DateFilter::class, strategy: 'exclude_null')]
#[ApiFilter(filterClass: CidFilter::class)]
#[ApiFilter(filterClass: SidFilter::class)]
#[ApiFilter(GlobalEventFilter::class, properties: ['type'])]
class CCalendarEvent extends AbstractResource implements ResourceInterface, ResourceRestrictToGroupContextInterface, Stringable
{
    public const COLOR_STUDENT_PUBLICATION = '#FF8C00';

    public const TYPE_INVITATION = 'invitation';
    public const TYPE_SUBSCRIPTION = 'subscription';

    public const SUBSCRIPTION_VISIBILITY_NO = 0;
    public const SUBSCRIPTION_VISIBILITY_ALL = 1;
    public const SUBSCRIPTION_VISIBILITY_CLASS = 2;

    #[Groups(['calendar_event:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['calendar_event:write', 'calendar_event:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['calendar_event:write', 'calendar_event:read'])]
    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    protected ?string $content = null;

    #[Groups(['calendar_event:write', 'calendar_event:read'])]
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    protected ?DateTime $startDate = null;

    #[Groups(['calendar_event:write', 'calendar_event:read'])]
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    protected ?DateTime $endDate = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_event_id', referencedColumnName: 'iid')]
    protected ?CCalendarEvent $parentEvent = null;

    /**
     * @var Collection<int, CCalendarEvent>
     */
    #[ORM\OneToMany(mappedBy: 'parentEvent', targetEntity: self::class)]
    protected Collection $children;

    /**
     * @var Collection<int, CCalendarEventRepeat>
     */
    #[ORM\OneToMany(
        mappedBy: 'event',
        targetEntity: CCalendarEventRepeat::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected Collection $repeatEvents;

    #[Assert\NotNull]
    #[ORM\Column(name: 'all_day', type: 'boolean', nullable: false)]
    protected bool $allDay;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[Groups(['calendar_event:write', 'calendar_event:read'])]
    #[ORM\Column(name: 'color', type: 'string', length: 20, nullable: true)]
    protected ?string $color = null;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id')]
    protected ?Room $room = null;

    /**
     * @var Collection<int, CCalendarEventAttachment>
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: CCalendarEventAttachment::class, cascade: ['persist', 'remove'])]
    protected Collection $attachments;

    #[Groups(['calendar_event:write'])]
    #[ORM\Column(name: 'invitation_type', type: 'string', nullable: true)]
    protected ?string $invitationType = null;

    #[Groups(['calendar_event:write'])]
    #[Assert\NotNull]
    #[ORM\Column(name: 'collective', type: 'boolean')]
    protected bool $collective = false;

    #[Groups(['calendar_event:write'])]
    #[ORM\Column(name: 'subscription_visibility', type: 'integer')]
    protected int $subscriptionVisibility = self::SUBSCRIPTION_VISIBILITY_NO;

    #[Groups(['calendar_event:write'])]
    #[ORM\Column(name: 'subscription_item_id', type: 'integer', nullable: true)]
    protected ?int $subscriptionItemId = null;

    #[Groups(['calendar_event:write'])]
    #[ORM\Column(name: 'max_attendees', type: 'integer')]
    protected int $maxAttendees = 0;

    /**
     * @var Collection<int, AgendaReminder>
     */
    #[Groups(['calendar_event:write'])]
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: AgendaReminder::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $reminders;

    #[ORM\ManyToOne(targetEntity: Career::class)]
    #[ORM\JoinColumn(name: 'career_id', referencedColumnName: 'id', nullable: true)]
    protected ?Career $career = null;

    #[ORM\ManyToOne(targetEntity: Promotion::class)]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', nullable: true)]
    protected ?Promotion $promotion = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->repeatEvents = new ArrayCollection();
        $this->allDay = false;
        $this->collective = false;
        $this->reminders = new ArrayCollection();
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

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTime
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
     * @return Collection<int, CCalendarEvent>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Collection<int, CCalendarEvent> $children
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

    public function getComment(): ?string
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, CCalendarEventAttachment>
     */
    public function getAttachments(): Collection
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
     * @return Collection<int, CCalendarEventRepeat>
     */
    public function getRepeatEvents(): Collection
    {
        return $this->repeatEvents;
    }

    /**
     * @param Collection<int, CCalendarEventRepeat> $repeatEvents
     */
    public function setRepeatEvents(Collection $repeatEvents): self
    {
        $this->repeatEvents = $repeatEvents;

        return $this;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getIid();
    }

    public function getIid(): ?int
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

    public function getInvitationType(): ?string
    {
        return $this->invitationType;
    }

    public function setInvitationType(string $invitationType): self
    {
        $this->invitationType = $invitationType;

        return $this;
    }

    public function getSubscriptionVisibility(): int
    {
        return $this->subscriptionVisibility;
    }

    public function setSubscriptionVisibility(int $subscriptionVisibility): self
    {
        $this->subscriptionVisibility = $subscriptionVisibility;

        return $this;
    }

    public function getSubscriptionItemId(): ?int
    {
        return $this->subscriptionItemId;
    }

    public function setSubscriptionItemId(?int $subscriptionItemId): self
    {
        $this->subscriptionItemId = $subscriptionItemId;

        return $this;
    }

    public function getMaxAttendees(): int
    {
        return $this->maxAttendees;
    }

    public function setMaxAttendees(int $maxAttendees): self
    {
        $this->maxAttendees = $maxAttendees;

        return $this;
    }

    /**
     * @return Collection<int, AgendaReminder>
     */
    public function getReminders(): Collection
    {
        return $this->reminders;
    }

    public function addReminder(AgendaReminder $reminder): static
    {
        if (!$this->reminders->contains($reminder)) {
            $this->reminders->add($reminder);
            $reminder->setEvent($this);
        }

        return $this;
    }

    public function removeReminder(AgendaReminder $reminder): static
    {
        if ($this->reminders->removeElement($reminder)) {
            // set the owning side to null (unless already changed)
            if ($reminder->getEvent() === $this) {
                $reminder->setEvent(null);
            }
        }

        return $this;
    }

    public function getCareer(): ?Career
    {
        return $this->career;
    }

    public function setCareer(?Career $career): self
    {
        $this->career = $career;

        return $this;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotion $promotion): self
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function determineType(): string
    {
        $resourceLinks = $this->resourceNode->getResourceLinks();

        foreach ($resourceLinks as $link) {
            if (null !== $link->getCourse() && null === $link->getSession()) {
                return 'course';
            }

            if (null !== $link->getSession()) {
                return 'session';
            }

            if (null === $link->getCourse()
                && null === $link->getSession()
                && null === $link->getGroup()
                && null === $link->getUser()
            ) {
                return 'global';
            }
        }

        return 'personal';
    }
}
