<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * MessageAttachment.
 *
 * @ORM\Table(name="ticket_message_attachments")
 * @ORM\Entity
 */
class TicketMessageAttachment
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Ticket")
     * @ORM\JoinColumn(name="ticket_id", referencedColumnName="id")
     */
    protected Ticket $ticket;

    /**
     * @ORM\ManyToOne(targetEntity="TicketMessage")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id")
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
    protected ?int $lastEditUserId;

    /**
     * @ORM\Column(name="sys_lastedit_datetime", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $lastEditDateTime;

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

    /**
     * @param TicketMessage $message
     *
     * @return TicketMessageAttachment
     */
    public function setMessage($message)
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

    /**
     * @param string $path
     *
     * @return TicketMessageAttachment
     */
    public function setPath($path)
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

    /**
     * @param string $filename
     *
     * @return TicketMessageAttachment
     */
    public function setFilename($filename)
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

    /**
     * @param int $size
     *
     * @return TicketMessageAttachment
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }
}
