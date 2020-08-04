<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingInstances. The list of one meeting's ended instances.
 *
 * @see MeetingInstance
 */
class MeetingInstances
{
    use JsonDeserializableTrait;

    /** @var MeetingInstance[] List of ended meeting instances. */
    public $meetings;

    /**
     * MeetingInstances constructor.
     */
    public function __construct()
    {
        $this->meetings = [];
    }

    /**
     * Retrieves a meeting's instances.
     *
     * @param int $meetingId
     *
     * @throws Exception
     *
     * @return MeetingInstances the meeting's instances
     */
    public static function fromMeetingId($meetingId)
    {
        return static::fromJson(Client::getInstance()->send('GET', "past_meetings/$meetingId/instances"));
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return MeetingInstance::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
