<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TicketBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MessageAttachment.
 *
 * @ORM\Table(name="ticket_message_attachments")
 * @ORM\Entity
 */
class MessageAttachment
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var Ticket
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\TicketBundle\Entity\Ticket")
     * @ORM\JoinColumn(name="ticket_id", referencedColumnName="id")
     */
    protected $ticket;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\TicketBundle\Entity\Message")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id")
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="text", nullable=false)
     */
    protected $filename;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="integer", nullable=false, unique=false)
     */
    protected $size;

    /**
     * @var int
     *
     * @ORM\Column(name="sys_insert_user_id", type="integer", nullable=false, unique=false)
     */
    protected $insertUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sys_insert_datetime", type="datetime", nullable=false, unique=false)
     */
    protected $insertDateTime;

    /**
     * @var int
     *
     * @ORM\Column(name="sys_lastedit_user_id", type="integer", nullable=true, unique=false)
     */
    protected $lastEditUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sys_lastedit_datetime", type="datetime", nullable=true, unique=false)
     */
    protected $lastEditDateTime;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return MessageAttachment
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Message $message
     *
     * @return MessageAttachment
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
     * @return MessageAttachment
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
     * @return MessageAttachment
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
     * @return MessageAttachment
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }
}
