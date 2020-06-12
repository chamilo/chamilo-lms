<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class CourseMeetingListItem extends API\MeetingListItem
{
    use CourseMeetingTrait;
    use DisplayableMeetingTrait;

    /**
     * @inheritDoc
     */
    public static function fromJson($json)
    {
        $instance = parent::fromJson($json);
        $instance->decodeAndRemoveTag();
        $instance->initializeDisplayableProperties();
        return $instance;
    }

    /**
     * CourseMeetingListItem constructor.
     *
     * @param API\MeetingListItem $meetingListItem
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromMeetingListItem($meetingListItem)
    {
        $instance = new static();
        self::recursivelyCopyObjectProperties($meetingListItem, $instance);
        $instance->decodeAndRemoveTag();
        $instance->initializeDisplayableProperties();

        return $instance;
    }
}
