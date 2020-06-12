<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

/**
 * Trait CourseMeetingTrait.
 * A Zoom meeting linked to a (course, session) pair.
 *
 * @package Chamilo\PluginBundle\Zoom
 */
trait CourseMeetingTrait
{
    use API\BaseMeetingTrait;

    /** @var bool whether the agenda contains the course and session identifiers */
    public $isTaggedWithCourseId;

    /** @var int meeting course id as found in the agenda field */
    public $courseId;

    /** @var array meeting course info */
    public $course;

    /** @var int meeting session id as found in the agenda field */
    public $sessionId;

    /** @var array meeting session info */
    public $session;

    public function loadCourse()
    {
        $this->course = api_get_course_info_by_id($this->courseId); // TODO cache
    }

    public function loadSession()
    {
        $this->session = api_get_session_info($this->sessionId); // TODO cache
    }

    public function setCourseAndSessionId($courseId, $sessionId)
    {
        $this->courseId = $courseId;
        $this->sessionId = $sessionId;
    }

    public function tagAgenda()
    {
        $this->agenda = $this->getUntaggedAgenda().$this->getTag();
    }

    public function untagAgenda()
    {
        $this->agenda = $this->getUntaggedAgenda();
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return bool whether both values match this CourseMeeting
     */
    public function matches($courseId, $sessionId)
    {
        return $courseId == $this->courseId && $sessionId == $this->sessionId;
    }

    protected function decodeAndRemoveTag()
    {
        $this->isTaggedWithCourseId = preg_match(self::getTagPattern(), $this->agenda, $matches);
        if ($this->isTaggedWithCourseId) {
            $this->setCourseAndSessionId($matches['courseId'], $matches['sessionId']);
            $this->untagAgenda();
        } else {
            $this->setCourseAndSessionId(0, 0);
        }
        $this->course = [];
        $this->session = [];
    }

    protected function getUntaggedAgenda()
    {
        return str_replace($this->getTag(), '', $this->agenda);
    }

    /**
     * @return string a tag to append to a meeting agenda so to link it to a (course, session) tuple
     */
    private function getTag()
    {
        return "\n(course $this->courseId, session $this->sessionId)";
    }

    private static function getTagPattern()
    {
        return '/course (?P<courseId>\d+), session (?P<sessionId>\d+)/m';
    }
}
