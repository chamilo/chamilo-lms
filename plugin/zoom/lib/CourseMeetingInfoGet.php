<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class CourseMeetingInfoGet extends API\MeetingInfoGet
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
     * @param API\MeetingInfoGet $meeting
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromMeetingInfoGet($meeting)
    {
        $instance = new static();
        self::recursivelyCopyObjectProperties($meeting, $instance);
        $instance->decodeAndRemoveTag();
        $instance->loadCourse();
        $instance->loadSession();
        $instance->initializeDisplayableProperties();

        return $instance;
    }
}
