<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\CreatedRegistration;
use Chamilo\PluginBundle\Zoom\API\JWTClient;
use Chamilo\PluginBundle\Zoom\API\MeetingInstance;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\PluginBundle\Zoom\API\ParticipantListItem;
use Chamilo\PluginBundle\Zoom\API\PastMeeting;
use Chamilo\PluginBundle\Zoom\API\RecordingMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeetingInfoGet;
use Chamilo\PluginBundle\Zoom\CourseMeetingListItem;
use Chamilo\PluginBundle\Zoom\UserMeetingRegistrant;
use Chamilo\PluginBundle\Zoom\UserMeetingRegistrantListItem;

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
                'enableParticipantRegistration' => 'boolean',
                'enableCloudRecording' => 'boolean',
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
     * @param $meetingId
     *
     * @return MeetingInstance[]
     *
     * @throws Exception
     */
    public function getEndedMeetingInstances($meetingId)
    {
        return $this->jwtClient()->getEndedMeetingInstances($meetingId);
    }

    /**
     * @param string $instanceUUID
     *
     * @throws Exception
     *
     * @return PastMeeting
     */
    public function getPastMeetingInstanceDetails($instanceUUID)
    {
        return $this->jwtClient()->getPastMeetingInstanceDetails($instanceUUID);
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
        $meeting->settings->approval_type = $this->get('enableParticipantRegistration')
            ? MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE
            : MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED;

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
        $meeting->untagAgenda();
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
     * @param string $instanceUUID
     *
     * @throws Exception on API error
     *
     * @return RecordingMeeting the recordings of the meeting
     */
    public function getRecordings($instanceUUID)
    {
        return $this->jwtClient()->getRecordings($instanceUUID);
    }

    /**
     * @see JWTClient::getParticipants()
     *
     * @param string $instanceUUID
     *
     * @throws Exception
     *
     * @return ParticipantListItem[]
     */
    public function getParticipants($instanceUUID)
    {
        return $this->jwtClient()->getParticipants($instanceUUID);
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
        $meeting->settings->auto_recording = $this->get('enableCloudRecording')
            ? 'cloud'
            : 'local';
        $meeting->settings->registrants_email_notification = false;
        $meeting->tagAgenda();

        return CourseMeetingInfoGet::fromMeetingInfoGet($this->jwtClient()->createMeeting($meeting));
    }

    /**
     * @param $meetingId
     *
     * @throws Exception
     *
     * @return UserMeetingRegistrantListItem[]
     */
    public function getRegistrants($meetingId)
    {
        $registrants = [];
        foreach ($this->jwtClient()->getRegistrants($meetingId) as $registrant) {
            $registrants[] = UserMeetingRegistrantListItem::fromMeetingRegistrantListItem($registrant);
        }
        return $registrants;
    }

    /**
     * @param int $meetingId
     * @param \Chamilo\UserBundle\Entity\User[] $users
     *
     * @throws Exception
     *
     * @return CreatedRegistration[]
     */
    public function addRegistrants($meetingId, $users)
    {
        $createdRegistrations = [];
        foreach ($users as $user) {
            $registrant = UserMeetingRegistrant::fromUser($user);
            $registrant->tagEmail();
            $createdRegistrations[] = $this->jwtClient()->addRegistrant($meetingId, $registrant);
        }

        return $createdRegistrations;
    }

    /**
     * @param int                     $meetingId
     * @param UserMeetingRegistrant[] $registrants
     *
     * @throws Exception
     */
    public function removeRegistrants($meetingId, $registrants)
    {
        $this->jwtClient()->removeRegistrants($meetingId, $registrants);
    }

    /**
     * Updates meeting registrants list. Adds the missing registrants and removes the extra.
     *
     * @param int                               $meetingId meeting identifier
     * @param \Chamilo\UserBundle\Entity\User[] $users      list of users to be registred
     *
     * @throws Exception
     */
    public function updateRegistrantList($meetingId, $users)
    {
        $registrants = $this->getRegistrants($meetingId);
        $usersToAdd = [];
        foreach ($users as $user) {
            $found = false;
            foreach ($registrants as $registrant) {
                if ($registrant->matches($user->getId())) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $usersToAdd[] = $user;
            }
        }
        $registrantsToRemove = [];
        foreach ($registrants as $registrant) {
            $found = false;
            foreach ($users as $user) {
                if ($registrant->matches($user->getId())) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $registrantsToRemove[] = $registrant;
            }
        }
        $this->addRegistrants($meetingId, $usersToAdd);
        $this->removeRegistrants($meetingId, $registrantsToRemove);
    }

    /**
     * Deletes a meeting instance's recordings.
     *
     * @param string $instanceUUID
     *
     * @throws Exception
     */
    public function deleteRecordings($instanceUUID)
    {
        $this->jwtClient()->deleteRecordings($instanceUUID);
    }
}
