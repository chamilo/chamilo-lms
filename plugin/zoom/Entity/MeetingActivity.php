<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
use Chamilo\PluginBundle\Zoom\API\MeetingListItem;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\UserBundle\Entity\User;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

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
     * @ORM\ManyToOne(targetEntity="Meeting", inversedBy="activities")
     * @ORM\JoinColumn(name="meeting_id")
     */
    protected $meeting;

    /**
     * @var string
     * @ORM\Column(type="string", name="name", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", name="type", length=255, nullable=false)
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(type="text", name="event", nullable=true)
     */
    protected $event;

    public function __construct()
    {
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
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
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
