<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

trait DisplayableMeetingTrait
{
    use API\BaseMeetingTrait;

    /** @var string meeting type name */
    public $typeName;

    /** @var DateTime meeting start time as a DateTime instance */
    public $startDateTime;

    /** @var string meeting formatted start time */
    public $formattedStartTime;

    /** @var DateInterval meeting duration as a DateInterval instance */
    public $durationInterval;

    /** @var string meeting formatted duration */
    public $formattedDuration;

    /**
     * @throws Exception on unexpected start_time or duration
     */
    public function initializeDisplayableProperties()
    {
        $this->typeName = [
            API\Meeting::TYPE_INSTANT => get_lang('Instant'),
            API\Meeting::TYPE_SCHEDULED => get_lang('Scheduled'),
            API\Meeting::TYPE_RECURRING_WITH_NO_FIXED_TIME => get_lang('RecurringWithNoFixedTime'),
            API\Meeting::TYPE_RECURRING_WITH_FIXED_TIME => get_lang('RecurringWithFixedTime'),
        ][$this->type];
        $this->startDateTime = null;
        $this->formattedStartTime = '';
        $this->durationInterval = null;
        $this->formattedDuration = '';
        if (!empty($this->start_time)) {
            $this->startDateTime = new DateTime($this->start_time);
            $this->startDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $this->formattedStartTime = $this->startDateTime->format(get_lang('Y-m-d H:i'));
        }
        if (!empty($this->duration)) {
            $now = new DateTime();
            $later = new DateTime();
            $later->add(new DateInterval('PT'.$this->duration.'M'));
            $this->durationInterval = $later->diff($now);
            $this->formattedDuration = $this->durationInterval->format(get_lang('%Hh%I'));
        }
    }
}
