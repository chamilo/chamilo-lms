<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\PluginBundle\Zoom\API\BaseMeetingTrait;
use Chamilo\PluginBundle\Zoom\API\WebinarSchema;
use Chamilo\PluginBundle\Zoom\API\WebinarSettings;
use DateInterval;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use ZoomPlugin;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Webinar extends Meeting
{
    /**
     * @var string
     *
     * @ORM\Column(name="webinar_schema_json", type="text", nullable=true)
     */
    protected $webinarSchemaJson;

    /**
     * @var WebinarSchema
     */
    protected $webinarSchema;

    public function preFlush()
    {
        if (null !== $this->webinarSchema) {
            $this->webinarSchemaJson = json_encode($this->webinarSchema);
        }
    }

    public function postLoad()
    {
        if (null !== $this->webinarSchemaJson) {
            $this->webinarSchema = WebinarSchema::fromJson($this->webinarSchemaJson);
        }

        $this->initializeDisplayableProperties();
    }

    /**
     * @param WebinarSchema|BaseMeetingTrait $webinarSchema
     *
     * @throws Exception
     */
    public function setWebinarSchema(WebinarSchema $webinarSchema): Webinar
    {
        if (null === $this->meetingId) {
            $this->meetingId = $webinarSchema->id;
        } elseif ($this->meetingId != $webinarSchema->id) {
            throw new Exception('the Meeting identifier differs from the MeetingInfoGet identifier');
        }

        $this->webinarSchema = $webinarSchema;

        $this->initializeDisplayableProperties();

        return $this;
    }

    public function getWebinarSchema(): WebinarSchema
    {
        return $this->webinarSchema;
    }

    public function hasCloudAutoRecordingEnabled(): bool
    {
        return $this->webinarSchema->settings->auto_recording !== ZoomPlugin::RECORDING_TYPE_NONE;
    }

    public function requiresDateAndDuration(): bool
    {
        return WebinarSchema::TYPE_WEBINAR == $this->webinarSchema->type;
    }

    public function requiresRegistration(): bool
    {
        return in_array(
            $this->webinarSchema->settings->approval_type,
            [
                WebinarSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE,
                WebinarSettings::APPROVAL_TYPE_MANUALLY_APPROVE,
            ]
        );
    }

    public function getTopic(): string
    {
        return $this->webinarSchema->topic;
    }

    public function getAgenda(): ?string
    {
        return $this->webinarSchema->agenda;
    }

    protected function initializeDisplayableProperties()
    {
        $zoomPlugin = ZoomPlugin::create();

        $namedTypes = [
            WebinarSchema::TYPE_WEBINAR => $zoomPlugin->get_lang('Webinar'),
            WebinarSchema::TYPE_RECURRING_NO_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithNoFixedTime'),
            WebinarSchema::TYPE_RECURRING_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithFixedTime'),
        ];

        $this->typeName = $namedTypes[$this->webinarSchema->type];

        if ($this->webinarSchema->start_time) {
            $this->startDateTime = new DateTime(
                $this->webinarSchema->start_time,
                new \DateTimeZone(api_get_timezone())
            );
            $this->formattedStartTime = $this->startDateTime->format('Y-m-d H:i');
        }

        if ($this->webinarSchema->duration) {
            $now = new DateTime();
            $later = new DateTime();
            $later->add(
                new DateInterval('PT'.$this->webinarSchema->duration.'M')
            );
            $this->durationInterval = $now->diff($later);
            $this->formattedDuration = $this->durationInterval->format(
                $zoomPlugin->get_lang('DurationFormat')
            );
        }
    }
}
