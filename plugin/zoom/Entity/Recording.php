<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\PluginBundle\Zoom\API\RecordingMeeting;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Class RecordingEntity.
 *
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\Zoom\RecordingRepository")
 * @ORM\Table(
 *     name="plugin_zoom_recording",
 *     indexes={
 *         @ORM\Index(name="meeting_id_index", columns={"meeting_id"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class Recording
{
    /** @var DateTime */
    public $startDateTime;

    /** @var string */
    public $formattedStartTime;

    /** @var DateInterval */
    public $durationInterval;

    /** @var string */
    public $formattedDuration;

    /**
     * @var string
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $uuid;

    /**
     * @var Meeting
     *
     * @ORM\ManyToOne(targetEntity="Meeting", inversedBy="recordings")
     * @ORM\JoinColumn(name="meeting_id")
     */
    protected $meeting;

    /**
     * @var string
     *
     * @ORM\Column(type="text", name="recording_meeting_json", nullable=true)
     */
    protected $recordingMeetingJson;

    /** @var RecordingMeeting */
    protected $recordingMeeting;

    /**
     * @param $name
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __get($name)
    {
        $object = $this->getRecordingMeeting();
        if (property_exists($object, $name)) {
            return $object->$name;
        }
        throw new Exception(sprintf('%s does not know property %s', $this, $name));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Recording %d', $this->uuid);
    }

    /**
     * @return Meeting
     */
    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * @throws Exception
     *
     * @return RecordingMeeting
     */
    public function getRecordingMeeting()
    {
        return $this->recordingMeeting;
    }

    /**
     * @param Meeting $meeting
     *
     * @return $this
     */
    public function setMeeting($meeting)
    {
        $this->meeting = $meeting;
        $this->meeting->getRecordings()->add($this);

        return $this;
    }

    /**
     * @param RecordingMeeting $recordingMeeting
     *
     * @throws Exception
     *
     * @return Recording
     */
    public function setRecordingMeeting($recordingMeeting)
    {
        if (null === $this->uuid) {
            $this->uuid = $recordingMeeting->uuid;
        } elseif ($this->uuid !== $recordingMeeting->uuid) {
            throw new Exception('the RecordingEntity identifier differs from the RecordingMeeting identifier');
        }
        if (null === $this->meeting) {
            $this->meeting = Database::getManager()->getRepository(Meeting::class)->find($recordingMeeting->id);
        } elseif ($this->meeting->getMeetingId() != $recordingMeeting->id) {
            // $this->meeting remains null when the remote RecordingMeeting refers to a deleted meeting.
            throw new Exception('The RecordingEntity meeting id differs from the RecordingMeeting meeting id');
        }
        $this->recordingMeeting = $recordingMeeting;

        return $this;
    }

    /**
     * @ORM\PostLoad
     *
     * @throws Exception
     */
    public function postLoad()
    {
        if (null !== $this->recordingMeetingJson) {
            $this->recordingMeeting = RecordingMeeting::fromJson($this->recordingMeetingJson);
        }
        $this->initializeExtraProperties();
    }

    /**
     * @ORM\PreFlush
     */
    public function preFlush()
    {
        if (null !== $this->recordingMeeting) {
            $this->recordingMeetingJson = json_encode($this->recordingMeeting);
        }
    }

    /**
     * @throws Exception
     */
    public function initializeExtraProperties()
    {
        $this->startDateTime = new DateTime($this->recordingMeeting->start_time);
        $this->startDateTime->setTimezone(new DateTimeZone(api_get_timezone()));
        $this->formattedStartTime = $this->startDateTime->format('Y-m-d H:i');

        $now = new DateTime();
        $later = new DateTime();
        $later->add(new DateInterval('PT'.$this->recordingMeeting->duration.'M'));
        $this->durationInterval = $later->diff($now);
        $this->formattedDuration = $this->durationInterval->format(get_lang('DurationFormat'));
    }
}
