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
use Stringable;

#[ORM\Entity(repositoryClass: RecordingRepository::class)]
#[ORM\Table(name: 'plugin_zoom_recording')]
#[ORM\Index(columns: ['meeting_id'], name: 'meeting_id_index')]
#[ORM\HasLifecycleCallbacks]
class Recording implements Stringable
{
    public DateTime $startDateTime;

    public string $formattedStartTime;

    public DateInterval $durationInterval;

    public string $formattedDuration;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\Column(type: 'string')]
    protected ?string $uuid;

    #[ORM\ManyToOne(targetEntity: Meeting::class, inversedBy: 'recordings')]
    #[ORM\JoinColumn(name: 'meeting_id')]
    protected ?Meeting $meeting;

    #[ORM\Column(name: 'recording_meeting_json', type: 'text', nullable: true)]
    protected ?string $recordingMeetingJson;

    protected ?RecordingMeeting $recordingMeeting;

    public function __get(string $name)
    {
        $object = $this->getRecordingMeeting();
        if (property_exists($object, $name)) {
            return $object->$name;
        }
        throw new Exception(sprintf('%s does not know property %s', $this, $name));
    }

    public function __toString()
    {
        return sprintf('Recording %d', $this->uuid);
    }

    public function getMeeting(): ?Meeting
    {
        return $this->meeting;
    }

    public function getRecordingMeeting(): RecordingMeeting
    {
        return $this->recordingMeeting;
    }

    public function setMeeting(Meeting $meeting): static
    {
        $this->meeting = $meeting;
        $this->meeting->getRecordings()->add($this);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setRecordingMeeting(RecordingMeeting $recordingMeeting): static
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
     * @throws Exception
     */
    #[ORM\PostLoad]
    public function postLoad(): void
    {
        if (null !== $this->recordingMeetingJson) {
            $this->recordingMeeting = RecordingMeeting::fromJson($this->recordingMeetingJson);
        }
        $this->initializeExtraProperties();
    }

    #[ORM\PreFlush]
    public function preFlush(): void
    {
        if (null !== $this->recordingMeeting) {
            $this->recordingMeetingJson = json_encode($this->recordingMeeting);
        }
    }

    /**
     * @throws Exception
     */
    public function initializeExtraProperties(): void
    {
        $this->startDateTime = new DateTime($this->recordingMeeting->start_time);
        $this->startDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->formattedStartTime = $this->startDateTime->format('Y-m-d H:i');

        $now = new DateTime();
        $later = new DateTime();
        $later->add(new DateInterval('PT'.$this->recordingMeeting->duration.'M'));
        $this->durationInterval = $later->diff($now);
        $this->formattedDuration = $this->durationInterval->format(get_lang('%1 seconds'));
    }
}
