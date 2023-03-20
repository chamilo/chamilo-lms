<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ticket_message_attachments")
 * @ORM\Entity
 */
class TicketMessageAttachment extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Ticket")
     * @ORM\JoinColumn(name="ticket_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Ticket $ticket;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TicketMessage")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected TicketMessage $message;

    /**
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    protected string $path;

    /**
     * @ORM\Column(name="filename", type="text", nullable=false)
     */
    protected string $filename;

    /**
     * @ORM\Column(name="size", type="integer")
     */
    protected int $size;

    /**
     * @ORM\Column(name="sys_insert_user_id", type="integer")
     */
    protected int $insertUserId;

    /**
     * @ORM\Column(name="sys_insert_datetime", type="datetime")
     */
    protected DateTime $insertDateTime;

    /**
     * @ORM\Column(name="sys_lastedit_user_id", type="integer", nullable=true, unique=false)
     */
    protected ?int $lastEditUserId = null;

    /**
     * @ORM\Column(name="sys_lastedit_datetime", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $lastEditDateTime = null;

    public function __toString(): string
    {
        return $this->getFilename();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TicketMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(TicketMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getResourceName(): string
    {
        return $this->getFilename();
    }

    public function setResourceName(string $name)
    {
        return $this->setFilename($name);
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function setInsertUserId(int $insertUserId): self
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    public function setInsertDateTime(DateTime $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }
}
