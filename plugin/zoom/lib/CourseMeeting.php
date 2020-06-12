<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class CourseMeeting extends API\Meeting
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
     * Creates a CourseMeeting instance from a topic.
     *
     * @param int    $courseId
     * @param int    $sessionId
     * @param string $topic
     * @param int    $type
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromCourseSessionTopicAndType($courseId, $sessionId, $topic, $type)
    {
        $instance = parent::fromTopicAndType($topic, $type);
        $instance->setCourseAndSessionId($courseId, $sessionId);
        $instance->initializeDisplayableProperties();
        return $instance;
    }
}
