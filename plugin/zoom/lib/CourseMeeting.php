<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

/**
 * Class CourseMeeting. A remote Zoom meeting linked to a local course.
 * An instance of this class is required to create a remote meeting from scratch.
 *
 * @package Chamilo\PluginBundle\Zoom
 */
class CourseMeeting extends API\Meeting
{
    use CourseMeetingTrait;
    use DisplayableMeetingTrait;

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
        $instance = static::fromTopicAndType($topic, $type);
        $instance->setCourseAndSessionId($courseId, $sessionId);
        $instance->initializeDisplayableProperties();

        return $instance;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function initializeExtraProperties()
    {
        parent::initializeExtraProperties();
        $this->decodeAndRemoveTag();
        $this->initializeDisplayableProperties();
    }

    /**
     * {@inheritdoc}
     *
     * Creates a tagged meeting
     *
     * @return CourseMeetingInfoGet
     */
    public function create($client)
    {
        $new = new CourseMeetingInfoGet();

        $this->tagAgenda();
        static::recursivelyCopyObjectProperties(parent::create($client), $new);
        $this->untagAgenda();

        return $new;
    }
}
