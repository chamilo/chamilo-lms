<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class Meeting
{
    use JsonDeserializable;

    const TYPE_INSTANT = 1;
    const TYPE_SCHEDULED = 2;
    const TYPE_RECURRING_WITH_NO_FIXED_TIME = 3;
    const TYPE_RECURRING_WITH_FIXED_TIME = 8;

    /** @var string */
    public $topic;

    /** @var int */
    public $type;

    /** @var string "yyyy-MM-dd'T'HH:mm:ss'Z'" for GMT, same without 'Z' for local time (as set on zoom account) */
    public $start_time;

    /** @var int in minutes, for scheduled meetings only */
    public $duration;

    /** @var string the timezone for start_time */
    public $timezone;

    /** @var string password to join. [a-z A-Z 0-9 @ - _ *]. Max of 10 characters. */
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
     */
    protected function __construct()
    {
        $this->tracking_fields = [];
        $this->settings = new MeetingSettings();
    }

    /**
     * @param string $topic
     * @param int $type
     * @return static
     */
    public static function fromTopicAndType($topic, $type = self::TYPE_SCHEDULED)
    {
        $instance = new static();
        $instance->topic = $topic;
        $instance->type = $type;
        return $instance;
    }
}
