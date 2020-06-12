<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\JWTClient;
use Chamilo\PluginBundle\Zoom\API\ParticipantListItem;
use Chamilo\PluginBundle\Zoom\API\RecordingMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeetingInfoGet;
use Chamilo\PluginBundle\Zoom\CourseMeetingListItem;

class ZoomPlugin extends Plugin
{
    public $isCoursePlugin = true;

    protected function __construct()
    {
        parent::__construct(
            '0.0.1',
            'Sébastien Ducoulombier',
            [
                'tool_enable' => 'boolean',
                'apiKey' => 'text',
                'apiSecret' => 'text',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * Caches and returns an instance of this class.
     *
     * @return ZoomPlugin the instance to use
     */
    public static function create()
    {
        static $instance = null;

        return $instance ? $instance : $instance = new self();
    }

    /**
     * Creates this plugin's related data and data structure in the internal database.
     */
    public function install()
    {
        $this->install_course_fields_in_all_courses();
    }

    /**
     * Drops this plugins' related data from the internal database.
     */
    public function uninstall()
    {
        $this->uninstall_course_fields_in_all_courses();
    }

    /**
     * Retrieves information about meetings having a start_time between two dates.
     *
     * @param string   $type      MEETING_TYPE_LIVE, MEETING_TYPE_SCHEDULED or MEETING_TYPE_UPCOMING
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getPeriodMeetings($type, $startDate, $endDate)
    {
        $matchingMeetings = [];
        foreach ($this->jwtClient()->getMeetings($type) as $meeting) {
            if (property_exists($meeting, 'start_time')) {
                $startTime = new DateTime($meeting->start_time);
                if ($startDate <= $startTime && $startTime <= $endDate) {
                    $matchingMeeting = CourseMeetingListItem::fromMeetingListItem($meeting);
                    $matchingMeeting->loadCourse();
                    $matchingMeeting->loadSession();
                    $matchingMeetings[] = $matchingMeeting;
                }
            }
        }

        return $matchingMeetings;
    }

    /**
     * @return bool whether the logged-in user can manage conferences in this context, that is either
     *              the current course or session coach, the platform admin or the current course admin
     */
    public function userIsConferenceManager()
    {
        return api_is_coach()
            || api_is_platform_admin()
            || api_get_course_id() && api_is_course_admin();
    }

    /**
     * Retrieves a meeting properties.
     *
     * @param int $meetingId
     *
     * @throws Exception
     *
     * @return CourseMeetingInfoGet
     */
    public function getMeeting($meetingId)
    {
        return CourseMeetingInfoGet::fromMeetingInfoGet($this->jwtClient()->getMeeting($meetingId));
    }

    /**
     * Retrieves all live meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getLiveMeetings()
    {
        return $this->getMeetings(JWTClient::MEETING_LIST_TYPE_LIVE);
    }

    /**
     * Retrieves all scheduled meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getScheduledMeetings()
    {
        return $this->getMeetings(JWTClient::MEETING_LIST_TYPE_SCHEDULED);
    }

    /**
     * Retrieves all upcoming meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getUpcomingMeetings()
    {
        return $this->getMeetings(JWTClient::MEETING_LIST_TYPE_UPCOMING);
    }

    /**
     * Creates an instant meeting and returns it.
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CourseMeetingInfoGet meeting
     */
    public function createInstantMeeting()
    {
        // default meeting topic is based on session name, course title and current date
        $topic = '';
        $sessionName = api_get_session_name();
        if ($sessionName) {
            $topic = $sessionName.', ';
        }
        $courseInfo = api_get_course_info();
        $topic .= $courseInfo['title'].', '.date('yy-m-d H:i');
        $meeting = CourseMeeting::fromCourseSessionTopicAndType(
            api_get_course_int_id(),
            api_get_session_id(),
            $topic,
            CourseMeeting::TYPE_INSTANT
        );

        return $this->createMeeting($meeting);
    }

    /**
     * Schedules a meeting and returns it.
     *
     * @param DateTime $startTime meeting local start date-time (configure local timezone on your Zoom account)
     * @param int      $duration  in minutes
     * @param string   $topic     short title of the meeting, required
     * @param string   $agenda    ordre du jour
     * @param string   $password  meeting password
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CourseMeetingInfoGet meeting
     */
    public function createScheduledMeeting($startTime, $duration, $topic, $agenda = '', $password = '')
    {
        $meeting = CourseMeeting::fromCourseSessionTopicAndType(
            api_get_course_int_id(),
            api_get_session_id(),
            $topic,
            CourseMeeting::TYPE_SCHEDULED
        );
        $meeting->duration = $duration;
        $meeting->start_time = $startTime->format(DateTimeInterface::ISO8601);
        $meeting->agenda = $agenda;
        $meeting->password = $password;

        return $this->createMeeting($meeting);
    }

    /**
     * Updates a meeting.
     *
     * @param int                  $meetingId
     * @param CourseMeetingInfoGet $meeting   with updated properties
     *
     * @throws Exception on API error
     */
    public function updateMeeting($meetingId, $meeting)
    {
        $meeting->tagAgenda();
        $this->jwtClient()->updateMeeting($meetingId, $meeting);
    }

    /**
     * Ends a current meeting.
     *
     * @param int $meetingId
     *
     * @throws Exception on API error
     */
    public function endMeeting($meetingId)
    {
        $this->jwtClient()->endMeeting($meetingId);
    }

    /**
     * Deletes a meeting.
     *
     * @param int $meetingId
     *
     * @throws Exception on API error
     */
    public function deleteMeeting($meetingId)
    {
        $this->jwtClient()->deleteMeeting($meetingId);
    }

    /**
     * @see JWTClient::getRecordings()
     *
     * @param string $meetingUUID
     *
     * @throws Exception on API error
     *
     * @return RecordingMeeting the recordings of the meeting
     */
    public function getRecordings($meetingUUID)
    {
        return $this->jwtClient()->getRecordings($meetingUUID);
    }

    /**
     * @see JWTClient::getParticipants()
     *
     * @param string $meetingUUID
     *
     * @throws Exception
     *
     * @return ParticipantListItem[]
     */
    public function getParticipants($meetingUUID)
    {
        return $this->jwtClient()->getParticipants($meetingUUID);
    }

    /**
     * Caches and returns the JWT client instance, initialized with plugin settings.
     *
     * @return JWTClient object that provides means of communications with the Zoom servers
     */
    protected function jwtClient()
    {
        static $jwtClient = null;
        if (is_null($jwtClient)) {
            $jwtClient = new JWTClient($this->get('apiKey'), $this->get('apiSecret'));
        }

        return $jwtClient;
    }

    /**
     * Retrieves all meetings of a specific type and linked to current course and session.
     *
     * @param string $type MEETING_TYPE_LIVE, MEETING_TYPE_SCHEDULED or MEETING_TYPE_UPCOMING
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    private function getMeetings($type)
    {
        $matchingMeetings = [];
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        foreach ($this->jwtClient()->getMeetings($type) as $meeting) {
            $candidateMeeting = CourseMeetingListItem::fromMeetingListItem($meeting);
            if ($candidateMeeting->matches($courseId, $sessionId)) {
                $matchingMeetings[] = $candidateMeeting;
            }
        }

        return $matchingMeetings;
    }

    /**
     * Creates a meeting and returns it.
     *
     * @param CourseMeeting $meeting a meeting with at least a type and a topic
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CourseMeetingInfoGet meeting
     */
    private function createMeeting($meeting)
    {
        $meeting->settings->auto_recording = 'cloud';
        $meeting->tagAgenda();

        return CourseMeetingInfoGet::fromMeetingInfoGet($this->jwtClient()->createMeeting($meeting));
    }
}
