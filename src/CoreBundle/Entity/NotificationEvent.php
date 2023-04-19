<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="notification_event")
 * @ORM\Entity
 */
class NotificationEvent
{

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected string $content;

    /**
     * @ORM\Column(name="link", type="text", nullable=true)
     */
    protected string $link;

    /**
     * @ORM\Column(name="persistent", type="integer", nullable=true)
     */
    protected int $persistent;

    /**
     * @ORM\Column(name="day_diff", type="integer", nullable=true)
     */
    protected int $dayDiff;

    /**
     * @ORM\Column(name="event_type", type="string", length=255, nullable=false)
     */
    protected string $eventType;

    /**
     * @ORM\Column(name="event_id", type="integer", nullable=true)
     */
    protected int $eventId;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return int
     */
    public function getPersistent(): int
    {
        return $this->persistent;
    }

    /**
     * @param int $persistent
     */
    public function setPersistent(int $persistent): self
    {
        $this->persistent = $persistent;

        return $this;
    }

    /**
     * @return int
     */
    public function getDayDiff(): int
    {
        return $this->dayDiff;
    }

    /**
     * @param int $dayDiff
     */
    public function setDayDiff(int $dayDiff): self
    {
        $this->dayDiff = $dayDiff;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * @param string $eventType
     */
    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * @return int
     */
    public function getEventId(): int
    {
        return $this->eventId;
    }

    /**
     * @param int $eventId
     */
    public function setEventId(int $eventId): self
    {
        $this->eventId = $eventId;

        return $this;
    }
}
