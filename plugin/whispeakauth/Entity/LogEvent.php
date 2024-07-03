<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\WhispeakAuth;

use Chamilo\UserBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LogEvent.
 *
 * @package Chamilo\PluginBundle\Entity\WhispeakAuth
 *
 * @ORM\Table(name="whispeak_log_event")
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "log_event" = "Chamilo\PluginBundle\Entity\WhispeakAuth\LogEvent",
 *     "log_event_lp" = "Chamilo\PluginBundle\Entity\WhispeakAuth\LogEventLp",
 *     "log_event_quiz" = "Chamilo\PluginBundle\Entity\WhispeakAuth\LogEventQuiz"
 * })
 */
class LogEvent
{
    public const STATUS_FAILED = 0;
    public const STATUS_SUCCESS = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var DateTime
     *
     * @ORM\Column(name="datetime", type="datetime")
     */
    private $datetime;
    /**
     * @var int
     *
     * @ORM\Column(name="action_status", type="smallint")
     */
    private $actionStatus;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

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
     * @return LogEvent
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param DateTime $datetime
     *
     * @return LogEvent
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * @return int
     */
    public function getActionStatus()
    {
        return $this->actionStatus;
    }

    /**
     * @param int $actionStatus
     *
     * @return LogEvent
     */
    public function setActionStatus($actionStatus)
    {
        $this->actionStatus = $actionStatus;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return LogEvent
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getTypeString()
    {
        return '-';
    }
}
