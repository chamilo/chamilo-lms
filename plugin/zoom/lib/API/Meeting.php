<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class Meeting
{
    use BaseMeetingTrait;
    use JsonDeserializableTrait;

    const TYPE_INSTANT = 1;
    const TYPE_SCHEDULED = 2;
    const TYPE_RECURRING_WITH_NO_FIXED_TIME = 3;
    const TYPE_RECURRING_WITH_FIXED_TIME = 8;

    /** @var string password to join. [a-z A-Z 0-9 @ - _ *]. Max of 10 characters. */
    public $password;

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
     * Creates a Meeting instance from a topic.
     *
     * @param string $topic
     * @param int    $type
     *
     * @throws Exception
     *
     * @return static
     */
    protected static function fromTopicAndType($topic, $type = self::TYPE_SCHEDULED)
    {
        $instance = new static();
        $instance->topic = $topic;
        $instance->type = $type;

        return $instance;
    }

    /**
     * @inheritDoc
     */
    protected function itemClass($propertyName)
    {
        throw new Exception("no such array property $propertyName");
    }
}
