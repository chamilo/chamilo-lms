<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class Meeting
{
    /** @var string */
    public $topic;

    /** @var integer */
    public $type;
    const TYPE_INSTANT = 1;
    const TYPE_SCHEDULED = 2;
    const TYPE_RECURRING_WITH_NO_FIXED_TIME = 3;
    const TYPE_RECURRING_WITH_FIXED_TIME = 8;

    /** @var string "yyyy-MM-dd'T'HH:mm:ss'Z'" for GMT, same without 'Z' for local time (as set on zoom account) */
    public $start_time;

    /** @var integer in minutes, for scheduled meetings only */
    public $duration;

    /** @var string the timezone for start_time */
    public $timezone;

    /** @var string password to join. [a-z A-Z 0-9 @ - _ *]. Max of 10 characters.*/
    public $password;

    /** @var string description */
    public $agenda;

    /** @var array field => value */
    public $tracking_fields;

    /** @var object, only for a recurring meeting with fixed time (type 8) */
    public $recurrence;

    /** @var MeetingSettings */
    public $settings;

    /**
     * Meeting constructor.
     * @param string $topic
     * @param int $type
     */
    public function __construct($topic, $type = self::TYPE_SCHEDULED)
    {
        $this->topic = $topic;
        $this->type = $type;
        $this->tracking_fields = [];
        $this->settings = new MeetingSettings();
    }
}
