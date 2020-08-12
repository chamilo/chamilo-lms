<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Meeting.
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_zoom_meeting_activity")
 * @ORM\HasLifecycleCallbacks
 */
class MeetingActivity
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var Meeting
     *
     * @ORM\ManyToOne(targetEntity="Meeting", inversedBy="activities")
     * @ORM\JoinColumn(name="meeting_id")
     */
    protected $meeting;

    /**
     * @var Meeting
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="name", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="type", length=255, nullable=false)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(type="text", name="event", nullable=true)
     */
    protected $event;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Activity %d', $this->id);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Meeting
     */
    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * @param Meeting $meeting
     *
     * @return MeetingActivity
     */
    public function setMeeting($meeting)
    {
        $this->meeting = $meeting;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return MeetingActivity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return MeetingActivity
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return Meeting
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Meeting $user
     *
     * @return MeetingActivity
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function getEventDecoded()
    {
        if (!empty($this->event)) {
            return json_decode($this->event);
        }

        return '';
    }

    /**
     * @param string $event
     *
     * @return MeetingActivity
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }
}
