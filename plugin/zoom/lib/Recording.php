<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class Recording. A RecordingMeeting with extra help properties and a list of File instances
 * (instead of RecordingFile instances).
 *
 * @package Chamilo\PluginBundle\Zoom
 */
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
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function initializeExtraProperties()
    {
        parent::initializeExtraProperties();

        $this->startDateTime = new DateTime($this->start_time);
        $this->startDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $this->formattedStartTime = $this->startDateTime->format(get_lang('Y-m-d H:i'));

        $now = new DateTime();
        $later = new DateTime();
        $later->add(new DateInterval('PT'.$this->duration.'M'));
        $this->durationInterval = $later->diff($now);
        $this->formattedDuration = $this->durationInterval->format(get_lang('DurationFormat'));
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
