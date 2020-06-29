<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\CreatedRegistration;
use Chamilo\PluginBundle\Zoom\API\JWTClient;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\PluginBundle\Zoom\API\ParticipantList;
use Chamilo\PluginBundle\Zoom\API\ParticipantListItem;
use Chamilo\PluginBundle\Zoom\API\PastMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeetingInfoGet;
use Chamilo\PluginBundle\Zoom\CourseMeetingList;
use Chamilo\PluginBundle\Zoom\CourseMeetingListItem;
use Chamilo\PluginBundle\Zoom\File;
use Chamilo\PluginBundle\Zoom\Recording;
use Chamilo\PluginBundle\Zoom\RecordingList;
use Chamilo\PluginBundle\Zoom\UserMeetingRegistrant;
use Chamilo\PluginBundle\Zoom\UserMeetingRegistrantListItem;

/**
 * Class ZoomPlugin. Integrates Zoom meetings in courses.
 */
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
     * @param string   $type      MeetingList::TYPE_LIVE, MeetingList::TYPE_SCHEDULED or MeetingList::TYPE_UPCOMING
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
        /** @var CourseMeetingListItem $meeting */
        foreach (CourseMeetingList::loadMeetings($this->jwtClient(), $type) as $meeting) {
            if (property_exists($meeting, 'start_time')) {
                if ($startDate <= $meeting->startDateTime && $meeting->startDateTime <= $endDate) {
                    $meeting->loadCourse();
                    $meeting->loadSession();
                    $matchingMeetings[] = $meeting;
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
     * Retrieves a meeting.
     *
     * @param int $id the meeting numeric identifier
     *
     * @throws Exception
     *
     * @return CourseMeetingInfoGet
     */
    public function getMeeting($id)
    {
        return CourseMeetingInfoGet::fromId($this->jwtClient(), $id);
    }

    /**
     * Retrieves a past meeting instance details.
     *
     * @param string $instanceUUID
     *
     * @throws Exception
     *
     * @return PastMeeting
     */
    public function getPastMeetingInstanceDetails($instanceUUID)
    {
        return PastMeeting::fromUUID($this->jwtClient(), $instanceUUID);
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
        return $this->getMeetings(CourseMeetingList::TYPE_LIVE);
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
        return $this->getMeetings(CourseMeetingList::TYPE_SCHEDULED);
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
        return $this->getMeetings(CourseMeetingList::TYPE_UPCOMING);
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
     * @param CourseMeetingInfoGet $meeting the meeting with updated properties
     *
     * @throws Exception on API error
     */
    public function updateMeeting($meeting)
    {
        $meeting->update($this->jwtClient());
    }

    /**
     * Deletes a meeting.
     *
     * @param CourseMeetingInfoGet $meeting
     *
     * @throws Exception on API error
     */
    public function deleteMeeting($meeting)
    {
        $meeting->delete($this->jwtClient());
    }

    /**
     * Retrieves all recordings from a period of time.
     *
     * @param DateTime $startDate start date
     * @param DateTime $endDate   end date
     *
     * @throws Exception
     *
     * @return Recording[] all recordings
     */
    public function getRecordings($startDate, $endDate)
    {
        return RecordingList::loadRecordings($this->jwtClient(), $startDate, $endDate);
    }

    /**
     * Retrieves a meetings instances' recordings.
     *
     * @param CourseMeetingInfoGet $meeting
     *
     * @throws Exception
     *
     * @return Recording[] meeting instances' recordings
     */
    public function getMeetingRecordings($meeting)
    {
        $interval = new DateInterval('P1M');
        $startDate = clone $meeting->startDateTime;
        $startDate->sub($interval);
        $endDate = clone $meeting->startDateTime;
        $endDate->add($interval);
        $recordings = [];
        foreach ($this->getRecordings($startDate, $endDate) as $recording) {
            if ($recording->id == $meeting->id) {
                $recordings[] = $recording;
            }
        }

        return $recordings;
    }

    /**
     * Retrieves a meeting instance's participants.
     *
     * @param string $instanceUUID the meeting instance UUID
     *
     * @throws Exception
     *
     * @return ParticipantListItem[]
     */
    public function getParticipants($instanceUUID)
    {
        return ParticipantList::loadInstanceParticipants($this->jwtClient(), $instanceUUID);
    }

    /**
     * Retrieves a meeting's registrants.
     *
     * @param CourseMeetingInfoGet $meeting
     *
     * @throws Exception
     *
     * @return UserMeetingRegistrantListItem[] the meeting registrants
     */
    public function getRegistrants($meeting)
    {
        return $meeting->getUserRegistrants($this->jwtClient());
    }

    /**
     * Registers users to a meeting.
     *
     * @param CourseMeetingInfoGet              $meeting
     * @param \Chamilo\UserBundle\Entity\User[] $users
     *
     * @throws Exception
     *
     * @return CreatedRegistration[] the created registrations
     */
    public function addRegistrants($meeting, $users)
    {
        $createdRegistrations = [];
        foreach ($users as $user) {
            $registrant = UserMeetingRegistrant::fromUser($user);
            $registrant->tagEmail();
            $createdRegistrations[] = $meeting->addRegistrant($this->jwtClient(), $registrant);
        }

        return $createdRegistrations;
    }

    /**
     * Removes registrants from a meeting.
     *
     * @param CourseMeetingInfoGet    $meeting
     * @param UserMeetingRegistrant[] $registrants
     *
     * @throws Exception
     */
    public function removeRegistrants($meeting, $registrants)
    {
        $meeting->removeRegistrants($this->jwtClient(), $registrants);
    }

    /**
     * Updates meeting registrants list. Adds the missing registrants and removes the extra.
     *
     * @param CourseMeetingInfoGet              $meeting
     * @param \Chamilo\UserBundle\Entity\User[] $users   list of users to be registred
     *
     * @throws Exception
     */
    public function updateRegistrantList($meeting, $users)
    {
        $registrants = $this->getRegistrants($meeting);
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
        $this->addRegistrants($meeting, $usersToAdd);
        $this->removeRegistrants($meeting, $registrantsToRemove);
    }

    /**
     * Adds to the meeting course documents a link to a meeting instance recording file.
     *
     * @param CourseMeetingInfoGet $meeting
     * @param File                 $file
     * @param string               $name
     *
     * @throws Exception
     */
    public function createLinkToFileInCourse($meeting, $file, $name)
    {
        $courseInfo = api_get_course_info_by_id($meeting->courseId);
        if (empty($courseInfo)) {
            throw new Exception('This meeting is not linked to a valid course');
        }
        $path = '/zoom_meeting_recording_file_'.$file->id.'.'.$file->file_type;
        $docId = DocumentManager::addCloudLink($courseInfo, $path, $file->play_url, $name);
        if (!$docId) {
            throw new Exception(
                get_lang(
                    DocumentManager::cloudLinkExists(
                        $courseInfo,
                        $path,
                        $file->play_url
                    ) ? 'UrlAlreadyExists' : 'ErrorAddCloudLink'
                )
            );
        }
    }

    /**
     * Copies a recording file to a meeting's course.
     *
     * @param CourseMeetingInfoGet $meeting
     * @param File                 $file
     * @param string               $name
     *
     * @throws Exception
     */
    public function copyFileToCourse($meeting, $file, $name)
    {
        $courseInfo = api_get_course_info_by_id($meeting->courseId);
        if (empty($courseInfo)) {
            throw new Exception('This meeting is not linked to a valid course');
        }
        $tmpFile = tmpfile();
        if (false === $tmpFile) {
            throw new Exception('tmpfile() returned false');
        }
        $curl = curl_init($file->getFullDownloadURL($this->jwtClient()->token));
        if (false === $curl) {
            throw new Exception('Could not init curl: '.curl_error($curl));
        }
        if (!curl_setopt_array(
            $curl,
            [
                CURLOPT_FILE => $tmpFile,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 120,
            ]
        )) {
            throw new Exception("Could not set curl options: ".curl_error($curl));
        }
        if (false === curl_exec($curl)) {
            throw new Exception("curl_exec failed: ".curl_error($curl));
        }
        $newPath = handle_uploaded_document(
            $courseInfo,
            [
                'name' => $name,
                'tmp_name' => stream_get_meta_data($tmpFile)['uri'],
                'size' => filesize(stream_get_meta_data($tmpFile)['uri']),
                'from_file' => true,
                'type' => $file->file_type,
            ],
            '/',
            api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document',
            api_get_user_id(),
            0,
            null,
            0,
            '',
            true,
            false,
            null,
            $meeting->sessionId,
            true
        );
        fclose($tmpFile);
        if (false === $newPath) {
            throw new Exception('could not handle uploaded document');
        }
    }

    /**
     * Deletes a meeting instance's recordings.
     *
     * @param Recording $recording
     *
     * @throws Exception
     */
    public function deleteRecordings($recording)
    {
        $recording->delete($this->jwtClient());
    }

    /**
     * Deletes a meeting instance recording file.
     *
     * @param File $file
     *
     * @throws Exception
     */
    public function deleteFile($file)
    {
        $file->delete($this->jwtClient());
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
     * @param string $type MeetingList::TYPE_LIVE, MeetingList::TYPE_SCHEDULED or MeetingList::TYPE_UPCOMING
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
        /** @var CourseMeetingListItem $candidateMeeting */
        foreach (CourseMeetingList::loadMeetings($this->jwtClient(), $type) as $candidateMeeting) {
            if ($candidateMeeting->matches($courseId, $sessionId)) {
                $matchingMeetings[] = $candidateMeeting;
            }
        }

        return $matchingMeetings;
    }

    /**
     * Creates a meeting on the server and returns it.
     *
     * @param CourseMeeting $meeting a meeting with at least a type and a topic
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CourseMeetingInfoGet the new meeting
     */
    private function createMeeting($meeting)
    {
        $meeting->settings->auto_recording = $this->get('enableCloudRecording')
            ? 'cloud'
            : 'local';
        $meeting->settings->registrants_email_notification = false;

        return $meeting->create($this->jwtClient());
    }
}
