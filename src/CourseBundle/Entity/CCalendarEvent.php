<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Room;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CCalendarEvent.
 *
 * @ORM\Table(
 *  name="c_calendar_event",
 *  indexes={
 *  }
 * )
 * @ORM\Entity
 */
class CCalendarEvent extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected ?string $content;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    protected ?DateTime $startDate;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    protected ?DateTime $endDate;

    /**
     * @var ArrayCollection|CCalendarEvent[]
     * @ORM\OneToMany(targetEntity="CCalendarEvent", mappedBy="parentEvent")
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CCalendarEvent", inversedBy="children")
     * @ORM\JoinColumn(name="parent_event_id", referencedColumnName="iid")
     */
    protected ?CCalendarEvent $parentEvent = null;

    /**
     * @var ArrayCollection|CCalendarEventRepeat[]
     *
     * @ORM\OneToMany(targetEntity="CCalendarEventRepeat", mappedBy="event", cascade={"persist"}, orphanRemoval=true)
     */
    protected $repeatEvents;

    /**
     * @ORM\Column(name="all_day", type="integer", nullable=false)
     */
    protected int $allDay;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?string $comment;

    /**
     * @ORM\Column(name="color", type="string", length=100, nullable=true)
     */
    protected ?string $color;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    protected ?Room $room = null;

    /**
     * @var ArrayCollection|CCalendarEventAttachment[]
     *
     * @ORM\OneToMany(
     *   targetEntity="CCalendarEventAttachment", mappedBy="event", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $attachments;

    public function __construct()
    {
        $this->children = new ArrayCollection();
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

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
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
     * @return ArrayCollection|CCalendarEvent[]
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
     * @param ArrayCollection|CCalendarEvent[] $children
     */
    public function setChildren($children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Set allDay.
     *
     * @param int $allDay
     */
    public function setAllDay($allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function getAllDay(): int
    {
        return $this->allDay;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Room
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * @param Room $room
     */
    public function setRoom($room): self
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

    /**
     * @param string $color
     */
    public function setColor($color): self
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
     * @return ArrayCollection|CCalendarEventAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param ArrayCollection|CCalendarEventAttachment[] $attachments
     */
    public function setAttachments($attachments): self
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
     * @return ArrayCollection|CCalendarEventRepeat[]
     */
    public function getRepeatEvents()
    {
        return $this->repeatEvents;
    }

    /**
     * @param ArrayCollection|CCalendarEventRepeat[] $repeatEvents
     *
     * @return CCalendarEvent
     */
    public function setRepeatEvents($repeatEvents)
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
}
