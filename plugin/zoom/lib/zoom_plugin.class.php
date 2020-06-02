<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\JWTClient;
use Chamilo\PluginBundle\Zoom\Meeting;
use Chamilo\PluginBundle\Zoom\MeetingListItem;
use Chamilo\PluginBundle\Zoom\ParticipantListItem;

class ZoomPlugin extends Plugin
{
    const TABLE_NAME = 'plugin_zoom_meeting';

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
     * @return MeetingListItem[] matching meetings with extra_data
     */
    public function getPeriodMeetings($type, $startDate, $endDate)
    {
        $matchingMeetings = [];
        foreach ($this->jwtClient()->listAllMeetings($type) as $meeting) {
            if (property_exists($meeting, 'start_time')) {
                $startTime = new DateTime($meeting->start_time);
                if ($startDate <= $startTime && $startTime <= $endDate) {
                    $matchingMeetings[] = $meeting;
                }
            }
        }

        return $this->computeMeetingExtraData($matchingMeetings);
    }

    public function userIsConferenceManager()
    {
        return api_is_coach()
            || api_is_platform_admin()
            || api_get_course_id() && api_is_course_admin();
    }

    /**
     * Retrieves a meeting properties and extra_data.
     *
     * @param int $meetingId
     *
     * @throws Exception
     *
     * @return Meeting
     */
    public function getMeeting($meetingId)
    {
        return $this->computeMeetingExtraData([$this->jwtClient()->getMeeting($meetingId)])[0];
    }

    /**
     * Retrieves all live meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return Meeting[] matching meetings
     */
    public function getLiveMeetings()
    {
        return $this->computeMeetingExtraData($this->getMeetings(JWTClient::MEETING_LIST_TYPE_LIVE));
    }

    /**
     * Retrieves all scheduled meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return Meeting[] matching meetings
     */
    public function getScheduledMeetings()
    {
        return $this->computeMeetingExtraData($this->getMeetings(JWTClient::MEETING_LIST_TYPE_SCHEDULED));
    }

    /**
     * Retrieves all upcoming meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return Meeting[] matching meetings
     */
    public function getUpcomingMeetings()
    {
        return $this->computeMeetingExtraData($this->getMeetings(JWTClient::MEETING_LIST_TYPE_UPCOMING));
    }

    /**
     * Creates an instant meeting and returns it.
     *
     * @throws Exception describing the error (message and code)
     *
     * @return Meeting|object meeting
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

        return $this->createMeeting(new Meeting(Meeting::TYPE_INSTANT), $topic, '', '');
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
     * @return Meeting|object meeting
     */
    public function createScheduledMeeting($startTime, $duration, $topic, $agenda = '', $password = '')
    {
        $meeting = new Meeting(Meeting::TYPE_SCHEDULED);
        $meeting->duration = $duration;
        $meeting->start_time = $startTime->format(DateTimeInterface::ISO8601);

        return $this->createMeeting($meeting, $topic, $agenda, $password);
    }

    /**
     * Updates a meeting.
     *
     * @param integer $meetingId
     * @param Meeting $meeting   with updated properties
     *
     * @throws Exception on API error
     */
    public function updateMeeting($meetingId, $meeting)
    {
        $meeting->agenda .= $this->agendaTag();
        $this->jwtClient()->updateMeeting($meetingId, $meeting);
    }

    /**
     * Ends a current meeting.
     *
     * @param integer $meetingId
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
     * @param integer $meetingId
     *
     * @throws Exception on API error
     */
    public function deleteMeeting($meetingId)
    {
        $this->jwtClient()->deleteMeeting($meetingId);
    }

    /**
     * @see JWTClient::listRecordings()
     *
     * @param $meetingUUID
     *
     * @throws Exception on API error
     *
     * @return object
     */
    public function getRecordings($meetingUUID)
    {
        return $this->jwtClient()->listRecordings($meetingUUID);
    }

    /**
     * @see JWTClient::getAllParticipants()
     *
     * @param $meetingUUID
     *
     * @throws Exception
     *
     * @return ParticipantListItem[]
     */
    public function getParticipants($meetingUUID)
    {
        return $this->jwtClient()->getAllParticipants($meetingUUID);
    }

    /**
     * Adds a link to a meeting's recordings.
     *
     * @param string $meetingUUID UUID of the meeting
     *
     * @throws Exception on API error
     *
     * @return Link the newly added link
     */
    public function copyRecordingToLinkTool($meetingUUID)
    {
        $recordings = $this->jwtClient()->listRecordings($meetingUUID);
        $link = new Link();
        $link->save(
            [
                'url' => $recordings->share_url,
                'title' => $recordings->topic,
            ]
        );

        return $link;
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
     * @return string a tag to append to a meeting agenda so to link it to a (course, session) tuple
     */
    private function agendaTag()
    {
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        return "\n(course $courseId, session $sessionId)";
    }

    private function courseIdAndSessionIdFromAgenda($agenda)
    {
        return preg_match('/course (?P<courseId>\d+), session (?P<sessionId>\d+)/m', $agenda, $matches)
            ? $matches
            : [
                'courseId' => 0,
                'sessionId' => 0,
            ];
    }

    /**
     * Retrieves all meetings of a specific type and linked to current course and session.
     *
     * @param string $type MEETING_TYPE_LIVE, MEETING_TYPE_SCHEDULED or MEETING_TYPE_UPCOMING
     *
     * @throws Exception on API error
     *
     * @return MeetingListItem[] matching meetings
     */
    private function getMeetings($type)
    {
        $matchingMeetings = [];
        $tag = $this->agendaTag();
        foreach ($this->jwtClient()->listAllMeetings($type) as $meeting) {
            if (property_exists($meeting, 'agenda') && substr($meeting->agenda, -strlen($tag)) === $tag) {
                $matchingMeetings[] = $meeting;
            }
        }

        return $matchingMeetings;
    }

    /**
     * Computes and append extra data for each listed meeting.
     *
     * @param MeetingListItem[] $meetings list of retreived meeting objects
     *
     * @throws Exception on API error
     *
     * @return Meeting[]|MeetingListItem[] same meetings with extra_data added
     */
    private function computeMeetingExtraData($meetings)
    {
        $completeMeetings = [];
        $tag = $this->agendaTag();
        $typeNames = [
            Meeting::TYPE_INSTANT => get_lang('Instant'),
            Meeting::TYPE_SCHEDULED => get_lang('Scheduled'),
            Meeting::TYPE_RECURRING_WITH_NO_FIXED_TIME => get_lang('RecurringWithNoFixedTime'),
            Meeting::TYPE_RECURRING_WITH_FIXED_TIME => get_lang('RecurringWithFixedTime'),
        ];
        foreach ($meetings as $meeting) {
            $completeMeeting = $meeting;
            $startTime = new DateTime($meeting->start_time);
            $duration = new DateInterval('PT'.$meeting->duration.'M');
            $courseIdAndSessionId = $this->courseIdAndSessionIdFromAgenda($meeting->agenda);
            $courseId = $courseIdAndSessionId['courseId'];
            $sessionId = $courseIdAndSessionId['sessionId'];
            $completeMeeting->extra_data = [
                'stripped_agenda' => substr($meeting->agenda, 0, -strlen($tag)),
                'type_name' => $typeNames[$meeting->type],
                'formatted_start_time' => $startTime->format(get_lang('Y-m-d H:i')),
                'formatted_duration' => $duration->format(get_lang('%Hh%I')),
                'meeting_details_url' => 'meeting.php?meetingId='.$meeting->id,
                'course' => api_get_course_info_by_id($courseId),
                'session' => api_get_session_info($sessionId),
            ];
            $completeMeetings[] = $completeMeeting;
        }

        return $completeMeetings;
    }

    /**
     * Creates a meeting and returns it.
     *
     * @param Meeting $meeting  a meeting with at least a type.
     * @param string  $topic    short title of the meeting, required
     * @param string  $agenda   ordre du jour
     * @param string  $password meeting password
     *
     * @throws Exception describing the error (message and code)
     *
     * @return Meeting|object meeting
     */
    private function createMeeting($meeting, $topic, $agenda, $password)
    {
        $meeting->topic = $topic;
        $meeting->agenda = $agenda;
        $meeting->password = $password;
        $meeting->settings->auto_recording = 'cloud';
        $meeting->agenda .= $this->agendaTag();

        return $this->jwtClient()->createMeeting($meeting);
    }
}
