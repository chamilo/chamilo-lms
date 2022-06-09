<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class Meeting, minimal meeting definition required to create one from scratch or update an existing one
 * Also referred to as MeetingUpdate in the API documentation
 * Does not represent an actual created meeting.
 */
class Meeting
{
    use BaseMeetingTrait;
    use JsonDeserializableTrait;

    public const TYPE_INSTANT = 1;
    public const TYPE_SCHEDULED = 2;
    public const TYPE_RECURRING_WITH_NO_FIXED_TIME = 3;
    public const TYPE_RECURRING_WITH_FIXED_TIME = 8;

    /** @var string password to join. [a-z A-Z 0-9 @ - _ *]. Max of 10 characters. */
    public $password;

    /** @var TrackingField[] Tracking fields */
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
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('tracking_fields' === $propertyName) {
            return TrackingField::class;
        }
        throw new Exception("no such array property $propertyName");
    }

    /**
     * Creates a meeting on the server and returns the resulting MeetingInfoGet.
     *
     * @throws Exception describing the error (message and code)
     *
     * @return MeetingInfoGet meeting
     */
    public function create($userId = null)
    {
        $userId = empty($userId) ? 'me' : $userId;

        return MeetingInfoGet::fromJson(
            Client::getInstance()->send('POST', "users/$userId/meetings", [], $this)
        );
    }

    /**
     * Creates a Meeting instance from a topic.
     *
     * @param string $topic
     * @param int    $type
     *
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
