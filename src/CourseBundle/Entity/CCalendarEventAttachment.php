<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventAttachment.
 *
 * @ORM\Table(
 *     name="c_calendar_event_attachment",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CCalendarEventAttachment extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?string $comment;

    /**
     * @ORM\Column(name="filename", type="string", length=255, nullable=false)
     */
    protected string $filename;

    /**
     * @ORM\ManyToOne(targetEntity="CCalendarEvent", cascade={"persist"}, inversedBy="attachments")
     * @ORM\JoinColumn(name="agenda_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CCalendarEvent $event;

    public function __toString(): string
    {
        return $this->getFilename();
    }

    /**
     * Set comment.
     *
     * @param string $comment
     */
    public function setComment($comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return CCalendarEventAttachment
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    public function getEvent(): CCalendarEvent
    {
        return $this->event;
    }

    /**
     * @return CCalendarEventAttachment
     */
    public function setEvent(CCalendarEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getFilename();
    }

    public function setResourceName(string $name): self
    {
        return $this->setFilename($name);
    }
}
