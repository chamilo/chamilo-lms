<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class UserMeetingRegistrantListItem extends API\MeetingRegistrantListItem
{
    use UserMeetingRegistrantTrait;

    /**
     * {@inheritdoc}
     */
    public static function fromJson($json)
    {
        $instance = parent::fromJson($json);
        $instance->decodeAndRemoveTag();
        $instance->computeFullName();

        return $instance;
    }

    /**
     * UserMeetingRegistrantListItem constructor.
     *
     * @param API\MeetingRegistrantListItem $meetingRegistrantListItem
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromMeetingRegistrantListItem($meetingRegistrantListItem)
    {
        $instance = new static();
        self::recursivelyCopyObjectProperties($meetingRegistrantListItem, $instance);
        $instance->decodeAndRemoveTag();
        $instance->computeFullName();

        return $instance;
    }
}
