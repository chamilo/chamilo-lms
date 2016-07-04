<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TicketBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * MessageAttachment
 *
 * @ORM\Table(name="ticket_message_attachments")
 * @ORM\Entity
 */
class MessageAttachment
{
    /**
     * @var integer
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
     **/
    protected $ticket;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\TicketBundle\Entity\Message")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id")
     **/
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
     * @var integer
     *
     * @ORM\Column(name="size", type="integer", nullable=false, unique=false)
     */
    protected $size;

    /**
     * @var integer
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
     * @var integer
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

}
