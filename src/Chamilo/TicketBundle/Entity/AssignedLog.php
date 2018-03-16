<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TicketBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AssignedLog.
 *
 * @ORM\Table(
 *  name="ticket_assigned_log",
 * )
 * @ORM\Entity
 *
 * @package Chamilo\TicketBundle\Entity
 */
class AssignedLog
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="sys_insert_user_id", type="integer", nullable=false)
     */
    protected $insertUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="assigned_date", type="datetime", nullable=false)
     */
    protected $assignedDate;
}
