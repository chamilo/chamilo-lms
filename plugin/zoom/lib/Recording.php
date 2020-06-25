<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

class Recording extends API\RecordingMeeting
{
    /** @var File[] List of recording file. */
    public $recording_files;

    /** @var DateTime */
    public $startDateTime;

    /** @var string */
    public $formattedStartTime;

    /** @var DateInterval */
    public $durationInterval;

    /** @var string */
    public $formattedDuration;

    /**
     * Builds a Recording from a RecordingMeeting.
     *
     * @param API\RecordingMeeting $recordingMeeting
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromRecodingMeeting($recordingMeeting)
    {
        $instance = new static();
        self::recursivelyCopyObjectProperties($recordingMeeting, $instance);

        $newRecordingFiles = [];
        foreach ($instance->recording_files as $file) {
            $newRecordingFiles[] = File::fromRecordingFile($file);
        }
        $instance->recording_files = $newRecordingFiles;

        $instance->startDateTime = new DateTime($instance->start_time);
        $instance->startDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $instance->formattedStartTime = $instance->startDateTime->format(get_lang('Y-m-d H:i'));

        $now = new DateTime();
        $later = new DateTime();
        $later->add(new DateInterval('PT'.$instance->duration.'M'));
        $instance->durationInterval = $later->diff($now);
        $instance->formattedDuration = $instance->durationInterval->format(get_lang('DurationFormat'));

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('recording_files' === $propertyName) {
            return File::class;
        }
        return parent::itemClass($propertyName);
    }
}
