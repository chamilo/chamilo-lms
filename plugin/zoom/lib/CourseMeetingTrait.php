<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\UserBundle\Entity\User;
use Database;

/**
 * Trait CourseMeetingTrait.
 * A Zoom meeting linked to a (course, session) pair.
 * The course and session IDs are stored in the meeting agenda on write operations, read and removed on retrieval.
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

    /** @var Course meeting course */
    public $course;

    /** @var int meeting session id as found in the agenda field */
    public $sessionId;

    /** @var Session meeting session */
    public $session;

    public function loadCourse()
    {
        $this->course = Database::getManager()->getRepository('ChamiloCoreBundle:Course')->find($this->courseId);
    }

    public function loadSession()
    {
        $this->session = $this->sessionId
            ? Database::getManager()->getRepository('ChamiloCoreBundle:Session')->find($this->sessionId)
            : null;
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
     * Builds the list of users that can register into this meeting.
     *
     * @return User[] the list of users
     */
    public function getCourseAndSessionUsers()
    {
        if ($this->sessionId && is_null($this->session)) {
            $this->loadSession();
        }

        if (is_null($this->course)) {
            $this->loadCourse();
        }

        $users = [];

        if (is_null($this->session)) {
            $users = Database::getManager()->getRepository(
                'ChamiloCoreBundle:Course'
            )->getSubscribedUsers($this->course)->getQuery()->getResult();
        } else {
            $subscriptions = $this->session->getUserCourseSubscriptionsByStatus($this->course, Session::STUDENT);
            if ($subscriptions) {
                /** @var SessionRelCourseRelUser $sessionCourseUser */
                foreach ($subscriptions as $sessionCourseUser) {
                    $users[$sessionCourseUser->getUser()->getUserId()] = $sessionCourseUser->getUser();
                }
            }
        }

        return $users;
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
        $this->course = null;
        $this->session = null;
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
