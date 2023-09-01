<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

/**
 * CCalendarEventAttachment.
 */
#[ORM\Table(name: 'c_calendar_event_attachment')]
#[ORM\Entity(repositoryClass: CCalendarEventAttachmentRepository::class)]
class CCalendarEventAttachment extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[ORM\Column(name: 'filename', type: 'string', length: 255, nullable: false)]
    protected string $filename;

    #[ORM\ManyToOne(targetEntity: 'CCalendarEvent', cascade: ['persist'], inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'agenda_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CCalendarEvent $event;

    public function __toString(): string
    {
        return $this->getFilename();
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getEvent(): CCalendarEvent
    {
        return $this->event;
    }

    public function setEvent(CCalendarEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

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
