<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ObjectIcon;
use Chamilo\CoreBundle\Component\Utils\StateIcon;
use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\ConferenceRecording;
use Chamilo\CoreBundle\Repository\ConferenceActivityRepository;
use Chamilo\CoreBundle\Repository\ConferenceMeetingRepository;
use Chamilo\CoreBundle\Repository\ConferenceRecordingRepository;
use Chamilo\UserBundle\Entity\User;

/**
 * Class Bbb
 * This script initiates a video conference session, calling the BigBlueButton
 * API BigBlueButton-Chamilo connector class
 */
class Bbb
{
    public $url;
    public $salt;
    public $api;
    public $userCompleteName = '';
    public $protocol = 'http://';
    public $debug = false;
    public $logoutUrl = '';
    public $pluginEnabled = false;
    public $enableGlobalConference = false;
    public $enableGlobalConferencePerUser = false;
    public $isGlobalConference = false;
    public $groupSupport = false;
    public $userSupport = false;
    public $accessUrl = 1;
    public $userId = 0;
    public $plugin;
    private $courseCode;
    private $courseId;
    private $sessionId;
    private $groupId;
    private $maxUsersLimit;

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the video conference server)
     *
     * @param string $host
     * @param string $salt
     * @param bool   $isGlobalConference
     * @param int    $isGlobalPerUser
     */
    public function __construct(
        $host = '',
        $salt = '',
        $isGlobalConference = false,
        $isGlobalPerUser = 0
    ) {
        $this->courseCode = api_get_course_id();
        $this->courseId = api_get_course_int_id();
        $this->sessionId = api_get_session_id();
        $this->groupId = api_get_group_id();

        // Initialize video server settings from global settings
        $this->plugin = BbbPlugin::create();
        $bbbPluginEnabled = $this->plugin->get('tool_enable');

        $bbb_host = !empty($host) ? $host : $this->plugin->get('host');
        $bbb_salt = !empty($salt) ? $salt : $this->plugin->get('salt');

        $this->table = Database::get_main_table('conference_meeting');
        $this->enableGlobalConference = $this->plugin->get('enable_global_conference') === 'true';
        $this->isGlobalConference = (bool) $isGlobalConference;

        $columns = Database::listTableColumns($this->table);
        $this->groupSupport = isset($columns['group_id']) ? true : false;
        $this->userSupport = isset($columns['user_id']) ? true : false;
        $this->accessUrl = api_get_current_access_url_id();

        $this->enableGlobalConferencePerUser = false;
        if ($this->userSupport && !empty($isGlobalPerUser)) {
            $this->enableGlobalConferencePerUser = $this->plugin->get('enable_global_conference_per_user') === 'true';
            $this->userId = $isGlobalPerUser;
        }

        if ($this->groupSupport) {
            // Plugin check
            $this->groupSupport = $this->plugin->get('enable_conference_in_course_groups') === 'true' ? true : false;
            if ($this->groupSupport) {
                // Platform check
                $bbbSetting = api_get_plugin_setting('bbb', 'enable_conference_in_course_groups');
                $bbbSetting = isset($bbbSetting['bbb']) ? $bbbSetting['bbb'] === 'true' : false;

                if ($bbbSetting) {
                    // Course check
                    $courseInfo = api_get_course_info();
                    if ($courseInfo) {
                        $this->groupSupport = api_get_course_plugin_setting(
                                'bbb',
                                'bbb_enable_conference_in_groups',
                                $courseInfo
                            ) === '1';
                    }
                }
            }
        }
        $this->maxUsersLimit = $this->plugin->get('max_users_limit');

        if ($bbbPluginEnabled === 'true') {
            $userInfo = api_get_user_info();
            if (empty($userInfo) && !empty($isGlobalPerUser)) {
                // If we are following a link to a global "per user" conference
                // then generate a random guest name to join the conference
                // because there is no part of the process where we give a name
                //$this->userCompleteName = 'Guest'.rand(1000, 9999);
            } else {
                $this->userCompleteName = $userInfo['complete_name'];
            }

            if (api_is_anonymous()) {
                $this->userCompleteName = get_lang('Guest').'_'.rand(1000, 9999);
            }

            $this->salt = $bbb_salt;
            if (!empty($bbb_host)) {
                if (substr($bbb_host, -1, 1) !== '/') {
                    $bbb_host .= '/';
                }
                $this->url = $bbb_host;
                if (!preg_match('#/bigbluebutton/$#', $bbb_host)) {
                    $this->url = $bbb_host.'bigbluebutton/';
                }
            }
            $info = parse_url($bbb_host);

            if (isset($info['scheme'])) {
                $this->protocol = $info['scheme'].'://';
                $this->url = str_replace($this->protocol, '', $this->url);
                $urlWithProtocol = $bbb_host;
            } else {
                // We assume it's an http, if user wants to use https, the host *must* include the protocol.
                $this->protocol = 'http://';
                $urlWithProtocol = $this->protocol.$bbb_host;
            }

            // Setting BBB api
            define('CONFIG_SECURITY_SALT', $this->salt);
            define('CONFIG_SERVER_URL_WITH_PROTOCOL', $urlWithProtocol);
            define('CONFIG_SERVER_BASE_URL', $this->url);
            define('CONFIG_SERVER_PROTOCOL', $this->protocol);

            $this->api = new BigBlueButtonBN();
            $this->pluginEnabled = true;
            $this->logoutUrl = $this->getListingUrl();
        }
    }

    /**
     * @param int $courseId  Optional. Course ID.
     * @param int $sessionId Optional. Session ID.
     * @param int $groupId   Optional. Group ID.
     *
     * @return string
     */
    public function getListingUrl($courseId = 0, $sessionId = 0, $groupId = 0)
    {
        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'
            .$this->getUrlParams($courseId, $sessionId, $groupId);
    }

    /**
     * @param int $courseId  Optional. Course ID.
     * @param int $sessionId Optional. Session ID.
     * @param int $groupId   Optional. Group ID.
     *
     * @return string
     */
    public function getUrlParams($courseId = 0, $sessionId = 0, $groupId = 0)
    {
        if (empty($this->courseId) && !$courseId) {
            if ($this->isGlobalConferencePerUserEnabled()) {
                return 'global=1&user_id='.$this->userId;
            }

            if ($this->isGlobalConference()) {
                return 'global=1';
            }

            return '';
        }

        $defaultCourseId = (int) $this->courseId;
        if (!empty($courseId)) {
            $defaultCourseId = (int) $courseId;
        }

        return http_build_query(
            [
                'cid' => $defaultCourseId,
                'sid' => (int) $sessionId ?: $this->sessionId,
                'gid' => (int) $groupId ?: $this->groupId,
            ]
        );
    }

    /**
     * @return bool
     */
    public function isGlobalConferencePerUserEnabled()
    {
        return $this->enableGlobalConferencePerUser;
    }

    /**
     * @return bool
     */
    public function isGlobalConference()
    {
        if ($this->isGlobalConferenceEnabled() === false) {
            return false;
        }

        return (bool) $this->isGlobalConference;
    }

    /**
     * @return bool
     */
    public function isGlobalConferenceEnabled()
    {
        return $this->enableGlobalConference;
    }

    /**
     * @param array $userInfo
     *
     * @return bool
     */
    public static function showGlobalConferenceLink($userInfo)
    {
        if (empty($userInfo)) {
            return false;
        }
        $setting = api_get_plugin_setting('bbb', 'enable_global_conference');
        $settingLink = api_get_plugin_setting('bbb', 'enable_global_conference_link');
        if ($setting === 'true' && $settingLink === 'true') {
            //$content = Display::url(get_lang('LaunchVideoConferenceRoom'), $url);
            $allowedRoles = api_get_plugin_setting(
                'bbb',
                'global_conference_allow_roles'
            );

            if (api_is_platform_admin()) {
                $userInfo['status'] = PLATFORM_ADMIN;
            }

            $showGlobalLink = true;
            if (!empty($allowedRoles)) {
                if (!in_array($userInfo['status'], $allowedRoles)) {
                    $showGlobalLink = false;
                }
            }

            return $showGlobalLink;
        }
    }

    /**
     * Gets the global limit of users in a video-conference room.
     * This value can be overridden by course-specific values
     * @return  int Maximum number of users set globally
     */
    public function getMaxUsersLimit()
    {
        $limit = $this->maxUsersLimit;
        if ($limit <= 0) {
            $limit = 0;
        }
        $courseLimit = 0;
        $sessionLimit = 0;
        // Check the extra fields for this course and session
        // Session limit takes priority over course limit
        // Course limit takes priority over global limit
        if (!empty($this->courseId)) {
            $extraField = new ExtraField('course');
            $fieldId = $extraField->get_all(
                array('variable = ?' => 'plugin_bbb_course_users_limit')
            );
            $extraValue = new ExtraFieldValue('course');
            $value = $extraValue->get_values_by_handler_and_field_id($this->courseId, $fieldId[0]['id']);
            if (!empty($value['value'])) {
                $courseLimit = (int) $value['value'];
            }
        }
        if (!empty($this->sessionId)) {
            $extraField = new ExtraField('session');
            $fieldId = $extraField->get_all(
                array('variable = ?' => 'plugin_bbb_session_users_limit')
            );
            $extraValue = new ExtraFieldValue('session');
            $value = $extraValue->get_values_by_handler_and_field_id($this->sessionId, $fieldId[0]['id']);
            if (!empty($value['value'])) {
                $sessionLimit = (int) $value['value'];
            }
        }

        if (!empty($sessionLimit)) {
            return $sessionLimit;
        } elseif (!empty($courseLimit)) {
            return $courseLimit;
        }

        return (int) $limit;
    }

    /**
     * Sets the global limit of users in a video-conference room.
     *
     * @param int Maximum number of users (globally)
     */
    public function setMaxUsersLimit($max)
    {
        if ($max < 0) {
            $max = 0;
        }
        $this->maxUsersLimit = (int) $max;
    }

    /**
     * See this file in you BBB to set up default values
     *
     * @param array $params Array of parameters that will be completed if not containing all expected variables
     *
     * /var/lib/tomcat6/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties
     *
     * More record information:
     * http://code.google.com/p/bigbluebutton/wiki/RecordPlaybackSpecification
     *
     * Default maximum number of users a meeting can have.
     * Doesn't get enforced yet but is the default value when the create
     * API doesn't pass a value.
     * defaultMaxUsers=20
     *
     * Default duration of the meeting in minutes.
     * Current default is 0 (meeting doesn't end).
     * defaultMeetingDuration=0
     *
     * Remove the meeting from memory when the end API is called.
     * This allows 3rd-party apps to recycle the meeting right-away
     * instead of waiting for the meeting to expire (see below).
     * removeMeetingWhenEnded=false
     *
     * The number of minutes before the system removes the meeting from memory.
     * defaultMeetingExpireDuration=1
     *
     * The number of minutes the system waits when a meeting is created and when
     * a user joins. If after this period, a user hasn't joined, the meeting is
     * removed from memory.
     * defaultMeetingCreateJoinDuration=5
     *
     * @return mixed
     */
    public function createMeeting($params)
    {
        $params['c_id'] = api_get_course_int_id();
        $params['session_id'] = api_get_session_id();

        if ($this->hasGroupSupport()) {
            $params['group_id'] = api_get_group_id();
        }

        if ($this->isGlobalConferencePerUserEnabled() && !empty($this->userId)) {
            $params['user_id'] = (int) $this->userId;
        }

        $params['attendee_pw'] = $params['attendee_pw'] ?? $this->getUserMeetingPassword();
        $attendeePassword = $params['attendee_pw'];
        $params['moderator_pw'] = $params['moderator_pw'] ?? $this->getModMeetingPassword();
        $moderatorPassword = $params['moderator_pw'];

        $params['record'] = api_get_course_plugin_setting('bbb', 'big_blue_button_record_and_store') == 1 ? 1 : 0;
        $max = api_get_course_plugin_setting('bbb', 'big_blue_button_max_students_allowed');
        $max = isset($max) ? $max : -1;

        $params['status'] = 1;
        // Generate a pseudo-global-unique-id to avoid clash of conferences on
        // the same BBB server with several Chamilo portals
        $params['remote_id'] = uniqid(true, true);
        // Each simultaneous conference room needs to have a different
        // voice_bridge composed of a 5 digits number, so generating a random one
        $params['voice_bridge'] = rand(10000, 99999);
        $params['created_at'] = api_get_utc_datetime();
        $params['access_url'] = $this->accessUrl;

        $em = Database::getManager();
        $meeting = new ConferenceMeeting();

        $meeting
            ->setCourse(api_get_course_entity($params['c_id']))
            ->setSession(api_get_session_entity($params['session_id']))
            ->setAccessUrl(api_get_url_entity($params['access_url']))
            ->setGroup($this->hasGroupSupport() ? api_get_group_entity($params['group_id']) : null)
            ->setUser($this->isGlobalConferencePerUserEnabled() ? api_get_user_entity($params['user_id']) : null)
            ->setRemoteId($params['remote_id'])
            ->setTitle($params['meeting_name'] ?? $this->getCurrentVideoConferenceName())
            ->setAttendeePw($attendeePassword)
            ->setModeratorPw($moderatorPassword)
            ->setRecord((bool) $params['record'])
            ->setStatus($params['status'])
            ->setVoiceBridge($params['voice_bridge'])
            ->setWelcomeMsg($params['welcome_msg'] ?? null)
            ->setVisibility(1)
            ->setHasVideoM4v(false)
            ->setServiceProvider('bbb');

        $em->persist($meeting);
        $em->flush();

        $id = $meeting->getId();

        Event::addEvent(
            'bbb_create_meeting',
            'meeting_id',
            $id,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        $meetingName = $meeting->getTitle();
        $record = $meeting->isRecord() ? 'true' : 'false';
        $duration = 300;
        $meetingDuration = (int) $this->plugin->get('meeting_duration');
        if (!empty($meetingDuration)) {
            $duration = $meetingDuration;
        }

        $bbbParams = [
            'meetingId' => $meeting->getRemoteId(),
            'meetingName' => $meetingName,
            'attendeePw' => $attendeePassword,
            'moderatorPw' => $moderatorPassword,
            'welcomeMsg' => $meeting->getWelcomeMsg(),
            'dialNumber' => '',
            'voiceBridge' => $meeting->getVoiceBridge(),
            'webVoice' => '',
            'logoutUrl' => $this->logoutUrl . '&action=logout&remote_id=' . $meeting->getRemoteId(),
            'maxParticipants' => $max,
            'record' => $record,
            'duration' => $duration,
        ];

        $status = false;
        $finalMeetingUrl = null;

        while ($status === false) {
            $result = $this->api->createMeetingWithXmlResponseArray($bbbParams);
            if (isset($result) && strval($result['returncode']) === 'SUCCESS') {
                if ($this->plugin->get('allow_regenerate_recording') === 'true') {
                    $meeting->setInternalMeetingId($result['internalMeetingID']);
                    $em->flush();
                }

                return $this->joinMeeting($meetingName, true);
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasGroupSupport()
    {
        return $this->groupSupport;
    }

    /**
     * Gets the password for a specific meeting for the current user
     *
     * @param string $courseCode
     *
     * @return string A moderator password if user is teacher, or the course code otherwise
     *
     */
    public function getUserMeetingPassword($courseCode = null)
    {
        if ($this->isGlobalConferencePerUserEnabled()) {
            return 'url_'.$this->userId.'_'.api_get_current_access_url_id();
        }

        if ($this->isGlobalConference()) {
            return 'url_'.api_get_current_access_url_id();
        }

        return empty($courseCode) ? api_get_course_id() : $courseCode;
    }

    /**
     * Generated a moderator password for the meeting.
     *
     * @param string $courseCode
     *
     * @return string A password for the moderation of the videoconference
     */
    public function getModMeetingPassword($courseCode = null)
    {
        if ($this->isGlobalConferencePerUserEnabled()) {
            return 'url_'.$this->userId.'_'.api_get_current_access_url_id().'_mod';
        }

        if ($this->isGlobalConference()) {
            return 'url_'.api_get_current_access_url_id().'_mod';
        }

        $courseCode = empty($courseCode) ? api_get_course_id() : $courseCode;

        return $courseCode.'mod';
    }

    /**
     * @return string
     */
    public function getCurrentVideoConferenceName()
    {
        if ($this->isGlobalConferencePerUserEnabled()) {
            return 'url_'.$this->userId.'_'.api_get_current_access_url_id();
        }

        if ($this->isGlobalConference()) {
            return 'url_'.api_get_current_access_url_id();
        }

        if ($this->hasGroupSupport()) {
            return api_get_course_id().'-'.api_get_session_id().'-'.api_get_group_id();
        }

        return api_get_course_id().'-'.api_get_session_id();
    }

    /**
     * Returns a meeting "join" URL
     *
     * @param string The name of the meeting (usually the course code)
     *
     * @return mixed The URL to join the meeting, or false on error
     * @todo implement moderator pass
     * @assert ('') === false
     * @assert ('abcdefghijklmnopqrstuvwxyzabcdefghijklmno') === false
     */
    public function joinMeeting($meetingName)
    {
        if ($this->debug) {
            error_log("joinMeeting: $meetingName");
        }

        if (empty($meetingName)) {
            return false;
        }

        $manager = $this->isConferenceManager();
        $pass = $manager ? $this->getModMeetingPassword() : $this->getUserMeetingPassword();

        $meetingData = Database::getManager()
            ->getRepository(ConferenceMeeting::class)
            ->findOneBy([
                'title' => $meetingName,
                'status' => 1,
                'accessUrl' => api_get_url_entity($this->accessUrl),
            ]);

        if (empty($meetingData)) {
            if ($this->debug) {
                error_log("meeting does not exist: $meetingName");
            }

            return false;
        }

        $params = [
            'meetingId' => $meetingData->getRemoteId(),
            'password' => $this->getModMeetingPassword(),
        ];

        $meetingInfoExists = false;
        $meetingIsRunningInfo = $this->getMeetingInfo($params);
        if ($this->debug) {
            error_log('Searching meeting with params:');
            error_log(print_r($params, 1));
            error_log('Result:');
            error_log(print_r($meetingIsRunningInfo, 1));
        }

        if ($meetingIsRunningInfo === false) {
            $params['meetingId'] = $meetingData->getId();
            $meetingIsRunningInfo = $this->getMeetingInfo($params);
            if ($this->debug) {
                error_log('Searching meetingId with params:');
                error_log(print_r($params, 1));
                error_log('Result:');
                error_log(print_r($meetingIsRunningInfo, 1));
            }
        }

        if (
            strval($meetingIsRunningInfo['returncode']) === 'SUCCESS' &&
            isset($meetingIsRunningInfo['meetingName']) &&
            !empty($meetingIsRunningInfo['meetingName'])
        ) {
            $meetingInfoExists = true;
        }

        if ($this->debug) {
            error_log("meeting is running: " . intval($meetingInfoExists));
        }

        if ($meetingInfoExists) {
            $joinParams = [
                'meetingId' => $meetingData->getRemoteId(),
                'username' => $this->userCompleteName,
                'password' => $pass,
                'userID' => api_get_user_id(),
                'webVoiceConf' => '',
            ];
            $url = $this->api->getJoinMeetingURL($joinParams);
            return $this->protocol . $url;
        }

        return false;
    }


    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    public function isConferenceManager()
    {
        if (api_is_coach() || api_is_platform_admin(false, true)) {
            return true;
        }

        if ($this->isGlobalConferencePerUserEnabled()) {
            $currentUserId = api_get_user_id();
            if ($this->userId === $currentUserId) {
                return true;
            } else {
                return false;
            }
        }

        $courseInfo = api_get_course_info();
        $groupId = api_get_group_id();
        if (!empty($groupId) && !empty($courseInfo)) {
            $groupEnabled = api_get_course_plugin_setting('bbb', 'bbb_enable_conference_in_groups') === '1';
            if ($groupEnabled) {
                $studentCanStartConference = api_get_course_plugin_setting(
                        'bbb',
                        'big_blue_button_students_start_conference_in_groups'
                    ) === '1';

                if ($studentCanStartConference) {
                    $isSubscribed = GroupManager::is_user_in_group(
                        api_get_user_id(),
                        GroupManager::get_group_properties($groupId)
                    );
                    if ($isSubscribed) {
                        return true;
                    }
                }
            }
        }

        if (!empty($courseInfo)) {
            return api_is_course_admin();
        }

        return false;
    }

    /**
     * Get information about the given meeting
     *
     * @param array ...?
     *
     * @return mixed Array of information on success, false on error
     * @assert (array()) === false
     */
    public function getMeetingInfo($params)
    {
        try {
            $result = $this->api->getMeetingInfoWithXmlResponseArray($params);
            if ($result == null) {
                if ($this->debug) {
                    error_log("Failed to get any response. Maybe we can't contact the BBB server.");
                }
            }

            return $result;
        } catch (Exception $e) {
            if ($this->debug) {
                error_log('Caught exception: ', $e->getMessage(), "\n");
            }
        }

        return false;
    }


    /**
     * @param int $meetingId
     * @param int $userId
     *
     * @return array
     */
    public function getMeetingParticipantInfo($meetingId, $userId): array
    {
        $em = Database::getManager();
        /** @var ConferenceActivityRepository $repo */
        $repo = $em->getRepository(ConferenceActivity::class);

        $activity = $repo->createQueryBuilder('a')
            ->join('a.meeting', 'm')
            ->join('a.participant', 'u')
            ->where('m.id = :meetingId')
            ->andWhere('u.id = :userId')
            ->setParameter('meetingId', $meetingId)
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$activity) {
            return [];
        }

        return [
            'id' => $activity->getId(),
            'meeting_id' => $activity->getMeeting()?->getId(),
            'participant_id' => $activity->getParticipant()?->getId(),
            'in_at' => $activity->getInAt()?->format('Y-m-d H:i:s'),
            'out_at' => $activity->getOutAt()?->format('Y-m-d H:i:s'),
            'close' => $activity->isClose(),
            'type' => $activity->getType(),
            'event' => $activity->getEvent(),
            'activity_data' => $activity->getActivityData(),
            'signature_file' => $activity->getSignatureFile(),
            'signed_at' => $activity->getSignedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Save a participant in a meeting room
     *
     * @param int $meetingId
     * @param int $participantId
     *
     * @return false|int The last inserted ID. Otherwise return false
     */
    public function saveParticipant(int $meetingId, int $participantId): false|int
    {
        $em = Database::getManager();

        /** @var ConferenceActivityRepository $repo */
        $repo = $em->getRepository(ConferenceActivity::class);

        $meeting = $em->getRepository(ConferenceMeeting::class)->find($meetingId);
        $user = api_get_user_entity($participantId);

        if (!$meeting || !$user) {
            return false;
        }

        $existing = $repo->createQueryBuilder('a')
            ->where('a.meeting = :meeting')
            ->andWhere('a.participant = :participant')
            ->andWhere('a.close = :open')
            ->setParameter('meeting', $meeting)
            ->setParameter('participant', $user)
            ->setParameter('open', \BbbPlugin::ROOM_OPEN)
            ->getQuery()
            ->getResult();

        foreach ($existing as $activity) {
            if ($activity->getInAt() != $activity->getOutAt()) {
                $activity->setClose(\BbbPlugin::ROOM_CLOSE);
            } else {
                $activity->setOutAt(new \DateTime());
                $activity->setClose(\BbbPlugin::ROOM_CLOSE);
            }
            $em->persist($activity);
        }

        $newActivity = new ConferenceActivity();
        $newActivity->setMeeting($meeting);
        $newActivity->setParticipant($user);
        $newActivity->setInAt(new \DateTime());
        $newActivity->setOutAt(new \DateTime());
        $newActivity->setClose(\BbbPlugin::ROOM_OPEN);

        $em->persist($newActivity);
        $em->flush();

        return $newActivity->getId();
    }

    /**
     * Tells whether the given meeting exists and is running
     * (using course code as name)
     *
     * @param string $meetingName Meeting name (usually the course code)
     *
     * @return bool True if meeting exists, false otherwise
     * @assert ('') === false
     * @assert ('abcdefghijklmnopqrstuvwxyzabcdefghijklmno') === false
     */
    public function meetingExists($meetingName)
    {
        $meetingData = $this->getMeetingByName($meetingName);

        return !empty($meetingData);
    }

    /**
     * @param string $meetingName
     *
     * @return array
     */
    public function getMeetingByName($meetingName)
    {
        if (empty($meetingName)) {
            return [];
        }

        $courseEntity = api_get_course_entity();
        $sessionEntity = api_get_session_entity();
        $accessUrlEntity = api_get_url_entity($this->accessUrl);

        $criteria = [
            'course' => $courseEntity,
            'session' => $sessionEntity,
            'title' => $meetingName,
            'status' => 1,
            'accessUrl' => $accessUrlEntity,
        ];

        if ($this->hasGroupSupport()) {
            $groupEntity = api_get_group_entity(api_get_group_id());
            $criteria['group'] = $groupEntity;
        }

        $meeting = Database::getManager()
            ->getRepository(ConferenceMeeting::class)
            ->findOneBy($criteria);

        if ($this->debug) {
            error_log('meeting_exists '.print_r($meeting ? ['id' => $meeting->getId()] : [], 1));
        }

        if (!$meeting) {
            return [];
        }

        return [
            'id' => $meeting->getId(),
            'c_id' => $meeting->getCourse()?->getId(),
            'session_id' => $meeting->getSession()?->getId(),
            'meeting_name' => $meeting->getTitle(),
            'status' => $meeting->getStatus(),
            'access_url' => $meeting->getAccessUrl()?->getId(),
            'group_id' => $meeting->getGroup()?->getIid(),
            'remote_id' => $meeting->getRemoteId(),
            'moderator_pw' => $meeting->getModeratorPw(),
            'attendee_pw' => $meeting->getAttendeePw(),
            'created_at' => $meeting->getCreatedAt()->format('Y-m-d H:i:s'),
            'closed_at' => $meeting->getClosedAt()?->format('Y-m-d H:i:s'),
            'visibility' => $meeting->getVisibility(),
            'video_url' => $meeting->getVideoUrl(),
            'has_video_m4v' => $meeting->isHasVideoM4v(),
            'record' => $meeting->isRecord(),
            'internal_meeting_id' => $meeting->getInternalMeetingId(),
        ];
    }

    /**
     * Gets a list from the database of all meetings attached to a course with the given status
     * @param int $courseId
     * @param int $sessionId
     * @param int $status 0 for closed meetings, 1 for open meetings
     *
     * @return array
     */
    public function getAllMeetingsInCourse($courseId, $sessionId, $status)
    {
        $em = Database::getManager();
        $courseEntity = api_get_course_entity($courseId);
        $sessionEntity = api_get_session_entity($sessionId);

        $meetings = $em->getRepository(ConferenceMeeting::class)->findBy([
            'course' => $courseEntity,
            'session' => $sessionEntity,
            'status' => $status,
        ]);

        $results = [];
        foreach ($meetings as $meeting) {
            $results[] = [
                'id' => $meeting->getId(),
                'c_id' => $meeting->getCourse()?->getId(),
                'session_id' => $meeting->getSession()?->getId(),
                'meeting_name' => $meeting->getTitle(),
                'status' => $meeting->getStatus(),
                'access_url' => $meeting->getAccessUrl()?->getId(),
                'group_id' => $meeting->getGroup()?->getIid(),
                'remote_id' => $meeting->getRemoteId(),
                'moderator_pw' => $meeting->getModeratorPw(),
                'attendee_pw' => $meeting->getAttendeePw(),
                'created_at' => $meeting->getCreatedAt()->format('Y-m-d H:i:s'),
                'closed_at' => $meeting->getClosedAt()?->format('Y-m-d H:i:s'),
                'visibility' => $meeting->getVisibility(),
                'video_url' => $meeting->getVideoUrl(),
                'has_video_m4v' => $meeting->isHasVideoM4v(),
                'record' => $meeting->isRecord(),
                'internal_meeting_id' => $meeting->getInternalMeetingId(),
            ];
        }

        return $results;
    }

    /**
     * Gets all the course meetings saved in the plugin_bbb_meeting table and
     * generate actionable links (join/close/delete/etc)
     *
     * @param int   $courseId
     * @param int   $sessionId
     * @param int   $groupId
     * @param bool  $isAdminReport Optional. Set to true then the report is for admins
     * @param array $dateRange     Optional
     *
     * @return array Array of current open meeting rooms
     * @throws Exception
     */
    public function getMeetings(
        $courseId = 0,
        $sessionId = 0,
        $groupId = 0,
        $isAdminReport = false,
        $dateRange = []
    ) {
        $em = Database::getManager();
        $repo = $em->getRepository(ConferenceMeeting::class);
        $manager = $this->isConferenceManager();
        $isGlobal = $this->isGlobalConference();
        $meetings = [];

        if (!empty($dateRange)) {
            $dateStart = (new \DateTime($dateRange['search_meeting_start']))->setTime(0, 0, 0);
            $dateEnd = (new \DateTime($dateRange['search_meeting_end']))->setTime(23, 59, 59);
            $meetings = $repo->findByDateRange($dateStart, $dateEnd);
        } elseif ($this->isGlobalConference()) {
            $meetings = $repo->findBy([
                'course' => null,
                'user' => api_get_user_entity($this->userId),
                'accessUrl' => api_get_url_entity($this->accessUrl),
            ]);
        } elseif ($this->isGlobalConferencePerUserEnabled()) {
            $meetings = $repo->findBy([
                'course' => api_get_course_entity($courseId),
                'session' => api_get_session_entity($sessionId),
                'user' => api_get_user_entity($this->userId),
                'accessUrl' => api_get_url_entity($this->accessUrl),
            ]);
        } else {
            $criteria = [
                'course' => api_get_course_entity($courseId),
                'session' => api_get_session_entity($sessionId),
                'accessUrl' => api_get_url_entity($this->accessUrl),
            ];
            if ($this->hasGroupSupport() && $groupId) {
                $criteria['group'] = api_get_group_entity($groupId);
            }
            $meetings = $repo->findBy($criteria, ['createdAt' => 'ASC']);
        }

        $result = [];
        foreach ($meetings as $meeting) {
            $meetingArray = $this->convertMeetingToArray($meeting);
            $recordLink = $this->plugin->get_lang('NoRecording');
            $meetingBBB = $this->getMeetingInfo([
                'meetingId' => $meeting->getRemoteId(),
                'password' => $manager ? $meeting->getModeratorPw() : $meeting->getAttendeePw(),
            ]);

            if (!$meetingBBB && $meeting->getId()) {
                $meetingBBB = $this->getMeetingInfo([
                    'meetingId' => $meeting->getId(),
                    'password' => $manager ? $meeting->getModeratorPw() : $meeting->getAttendeePw(),
                ]);
            }

            if (!$meeting->isVisible() && !$manager) {
                continue;
            }

            $meetingBBB['end_url'] = $this->endUrl(['id' => $meeting->getId()]);
            if (isset($meetingBBB['returncode']) && (string) $meetingBBB['returncode'] === 'FAILED') {
                if ($meeting->getStatus() === 1 && $manager) {
                    $this->endMeeting($meeting->getId(), $meeting->getCourse()?->getCode());
                }
            } else {
                $meetingBBB['add_to_calendar_url'] = $this->addToCalendarUrl($meetingArray);
            }

            if ($meeting->isRecord()) {
                $recordings = $this->api->getRecordingsWithXmlResponseArray(['meetingId' => $meeting->getRemoteId()]);
                if (!empty($recordings) && (!isset($recordings['messageKey']) || $recordings['messageKey'] !== 'noRecordings')) {
                    $record = end($recordings);
                    if (isset($record['playbackFormatUrl'])) {
                        $recordLink = Display::url(
                            $this->plugin->get_lang('ViewRecord'),
                            $record['playbackFormatUrl'],
                            ['target' => '_blank', 'class' => 'btn btn--plain']
                        );
                        $this->updateMeetingVideoUrl($meeting->getId(), $record['playbackFormatUrl']);
                    }
                }
            }

            $actionLinks = $this->getActionLinks($meetingArray, $record ?? [], $isGlobal, $isAdminReport);

            $item = array_merge($meetingArray, [
                'go_url' => '',
                'show_links' => $recordLink,
                'action_links' => implode(PHP_EOL, $actionLinks),
                'publish_url' => $this->publishUrl(['id' => $meeting->getId()]),
                'unpublish_url' => $this->unPublishUrl(['id' => $meeting->getId()]),
            ]);

            if ($meeting->getStatus() === 1) {
                $joinParams = [
                    'meetingId' => $meeting->getRemoteId(),
                    'username' => $this->userCompleteName,
                    'password' => $manager ? $meeting->getModeratorPw() : $meeting->getAttendeePw(),
                    'createTime' => '',
                    'userID' => '',
                    'webVoiceConf' => '',
                ];
                $item['go_url'] = $this->protocol.$this->api->getJoinMeetingURL($joinParams);
            }

            $result[] = array_merge($item, $meetingBBB);
        }

        return $result;
    }

    private function convertMeetingToArray(ConferenceMeeting $meeting): array
    {
        return [
            'id' => $meeting->getId(),
            'remote_id' => $meeting->getRemoteId(),
            'internal_meeting_id' => $meeting->getInternalMeetingId(),
            'meeting_name' => $meeting->getTitle(),
            'status' => $meeting->getStatus(),
            'visibility' => $meeting->getVisibility(),
            'created_at' => $meeting->getCreatedAt() instanceof \DateTime ? $meeting->getCreatedAt()->format('Y-m-d H:i:s') : '',
            'closed_at' => $meeting->getClosedAt() instanceof \DateTime ? $meeting->getClosedAt()->format('Y-m-d H:i:s') : '',
            'record' => $meeting->isRecord() ? 1 : 0,
            'c_id' => $meeting->getCourse()?->getId() ?? 0,
            'session_id' => $meeting->getSession()?->getId() ?? 0,
            'group_id' => $meeting->getGroup()?->getIid() ?? 0,
            'course' => $meeting->getCourse(),
            'session' => $meeting->getSession(),
            'title' => $meeting->getTitle(),
        ];
    }

    public function getMeetingsLight(
        $courseId = 0,
        $sessionId = 0,
        $groupId = 0,
        $dateRange = []
    ): array {
        $em = Database::getManager();
        $repo = $em->getRepository(ConferenceMeeting::class);
        $meetings = [];

        if (!empty($dateRange)) {
            $dateStart = (new \DateTime($dateRange['search_meeting_start']))->setTime(0, 0, 0);
            $dateEnd = (new \DateTime($dateRange['search_meeting_end']))->setTime(23, 59, 59);
            $meetings = $repo->findByDateRange($dateStart, $dateEnd);
        } else {
            $criteria = [
                'course' => api_get_course_entity($courseId),
                'session' => api_get_session_entity($sessionId),
                'accessUrl' => api_get_url_entity($this->accessUrl),
            ];
            if ($this->hasGroupSupport() && $groupId) {
                $criteria['group'] = api_get_group_entity($groupId);
            }
            $meetings = $repo->findBy($criteria, ['createdAt' => 'DESC']);
        }

        $result = [];
        foreach ($meetings as $meeting) {
            $meetingArray = $this->convertMeetingToArray($meeting);

            $item = array_merge($meetingArray, [
                'go_url' => '',
                'show_links' => $this->plugin->get_lang('NoRecording'),
                'action_links' => '',
                'publish_url' => $this->publishUrl(['id' => $meeting->getId()]),
                'unpublish_url' => $this->unPublishUrl(['id' => $meeting->getId()]),
            ]);

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array $meeting
     *
     * @return string
     */
    public function endUrl($meeting)
    {
        if (!isset($meeting['id'])) {
            return '';
        }

        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'.$this->getUrlParams().'&action=end&id='.$meeting['id'];
    }

    /**
     * Closes a meeting (usually when the user click on the close button from
     * the conferences listing.
     *
     * @param string The internal ID of the meeting (id field for this meeting)
     * @param string $courseCode
     *
     * @return void
     * @assert (0) === false
     */
    public function endMeeting($id, $courseCode = null)
    {
        if (empty($id)) {
            return false;
        }

        $em = Database::getManager();

        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        $meetingData = $repo->findOneAsArrayById((int) $id);
        if (!$meetingData) {
            return false;
        }

        $manager = $this->isConferenceManager();
        $pass = $manager ? $meetingData['moderatorPw'] : $meetingData['attendeePw'];

        Event::addEvent(
            'bbb_end_meeting',
            'meeting_id',
            (int) $id,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        $endParams = [
            'meetingId' => $meetingData['remoteId'],
            'password' => $pass,
        ];
        $this->api->endMeetingWithXmlResponseArray($endParams);

        $repo->closeMeeting((int) $id, new \DateTime());

        /** @var ConferenceActivityRepository $activityRepo */
        $activityRepo = $em->getRepository(ConferenceActivity::class);

        $activities = $activityRepo->findOpenWithSameInAndOutTime((int) $id);

        foreach ($activities as $activity) {
            $activity->setOutAt(new \DateTime());
            $activity->setClose(BbbPlugin::ROOM_CLOSE);
            $em->persist($activity);
        }

        $activityRepo->closeAllByMeetingId((int) $id);

        $em->flush();

        return true;
    }

    /**
     * @param array $meeting
     * @param array $record
     *
     * @return string
     */
    public function addToCalendarUrl($meeting, $record = []): string
    {
        $url = isset($record['playbackFormatUrl']) ? $record['playbackFormatUrl'] : '';

        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'.$this->getUrlParams(
            ).'&action=add_to_calendar&id='.$meeting['id'].'&start='.api_strtotime($meeting['created_at']).'&url='.$url;
    }

    /**
     * @param int    $meetingId
     * @param string $videoUrl
     *
     * @return bool|int
     */
    public function updateMeetingVideoUrl(int $meetingId, string $videoUrl): void
    {
        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);
        $repo->updateVideoUrl($meetingId, $videoUrl);
    }

    /**
     * Force the course, session and/or group IDs
     *
     * @param string $courseCode
     * @param int    $sessionId
     * @param int    $groupId
     */
    public function forceCIdReq($courseCode, $sessionId = 0, $groupId = 0)
    {
        $this->courseCode = $courseCode;
        $this->sessionId = (int) $sessionId;
        $this->groupId = (int) $groupId;
    }

    /**
     * @param array $meetingInfo
     * @param array $recordInfo
     * @param bool  $isGlobal
     * @param bool  $isAdminReport
     *
     * @return array
     */
    private function getActionLinks(
        $meetingInfo,
        $recordInfo,
        $isGlobal = false,
        $isAdminReport = false
    ) {
        $isVisible = $meetingInfo['visibility'] != 0;
        $linkVisibility = $isVisible
            ? Display::url(
                Display::getMdiIcon(StateIcon::ACTIVE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('MakeInvisible')),
                $this->unPublishUrl($meetingInfo)
            )
            : Display::url(
                Display::getMdiIcon(StateIcon::INACTIVE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('MakeVisible')),
                $this->publishUrl($meetingInfo)
            );

        $links = [];
        if ($this->plugin->get('allow_regenerate_recording') === 'true' && $meetingInfo['record'] == 1) {
            if (!empty($recordInfo)) {
                $links[] = Display::url(
                    Display::getMdiIcon(ActionIcon::REFRESH, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('RegenerateRecord')),
                    $this->regenerateRecordUrl($meetingInfo, $recordInfo)
                );
            } else {
                $links[] = Display::url(
                    Display::getMdiIcon(ActionIcon::REFRESH, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('RegenerateRecord')),
                    $this->regenerateRecordUrlFromMeeting($meetingInfo)
                );
            }
        }

        if (empty($recordInfo)) {
            if (!$isAdminReport) {
                if ($meetingInfo['status'] == 0) {
                    $links[] = Display::url(
                        Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')),
                        $this->deleteRecordUrl($meetingInfo)
                    );
                    $links[] = $linkVisibility;
                }

                return $links;
            } else {
                $links[] = Display::url(
                    Display::getMdiIcon(ObjectIcon::HOME, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('GoToCourse')),
                    $this->getListingUrl($meetingInfo['c_id'], $meetingInfo['session_id'], $meetingInfo['group_id'])
                );

                return $links;
            }
        }

        if (!$isGlobal) {
            $links[] = Display::url(
                Display::getMdiIcon(ObjectIcon::LINK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('UrlMeetingToShare')),
                $this->copyToRecordToLinkTool($meetingInfo)
            );
            $links[] = Display::url(
                Display::getMdiIcon(ObjectIcon::AGENDA, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('AddToCalendar')),
                $this->addToCalendarUrl($meetingInfo, $recordInfo)
            );
        }

        $hide = $this->plugin->get('disable_download_conference_link') === 'true' ? true : false;

        if ($hide == false) {
            if ($meetingInfo['has_video_m4v']) {
                $links[] = Display::url(
                    Display::getMdiIcon(ActionIcon::SAVE_FORM, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('DownloadFile')),
                    $recordInfo['playbackFormatUrl'].'/capture.m4v',
                    ['target' => '_blank']
                );
            } else {
                $links[] = Display::url(
                    Display::getMdiIcon(ActionIcon::SAVE_FORM, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('DownloadFile')),
                    '#',
                    [
                        'id' => "btn-check-meeting-video-{$meetingInfo['id']}",
                        'class' => 'check-meeting-video',
                        'data-id' => $meetingInfo['id'],
                    ]
                );
            }
        }


        if (!$isAdminReport) {
            $links[] = Display::url(
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')),
                $this->deleteRecordUrl($meetingInfo)
            );
            $links[] = $linkVisibility;
        } else {
            $links[] = Display::url(
                Display::getMdiIcon(ObjectIcon::HOME, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('GoToCourse')),
                $this->getListingUrl($meetingInfo['c_id'], $meetingInfo['session_id'], $meetingInfo['group_id'])
            );
        }


        return $links;
    }

    /**
     * @param array $meeting
     *
     * @return string
     */
    public function unPublishUrl($meeting)
    {
        if (!isset($meeting['id'])) {
            return null;
        }

        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'.$this->getUrlParams(
            ).'&action=unpublish&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     *
     * @return string
     */
    public function publishUrl($meeting)
    {
        if (!isset($meeting['id'])) {
            return '';
        }

        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'.$this->getUrlParams(
            ).'&action=publish&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     * @param array $recordInfo
     *
     * @return string
     */
    public function regenerateRecordUrl($meeting, $recordInfo)
    {
        if ($this->plugin->get('allow_regenerate_recording') !== 'true') {
            return '';
        }

        if (!isset($meeting['id'])) {
            return '';
        }

        if (empty($recordInfo) || (!empty($recordInfo['recordId']) && !isset($recordInfo['recordId']))) {
            return '';
        }

        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'.$this->getUrlParams().
            '&action=regenerate_record&id='.$meeting['id'].'&record_id='.$recordInfo['recordId'];
    }

    /**
     * @param array $meeting
     *
     * @return string
     */
    public function regenerateRecordUrlFromMeeting($meeting)
    {
        if ($this->plugin->get('allow_regenerate_recording') !== 'true') {
            return '';
        }

        if (!isset($meeting['id'])) {
            return '';
        }

        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'.$this->getUrlParams().
            '&action=regenerate_record&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     *
     * @return string
     */
    public function deleteRecordUrl($meeting)
    {
        if (!isset($meeting['id'])) {
            return '';
        }

        return api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?'.$this->getUrlParams(
            ).'&action=delete_record&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     *
     * @return string
     */
    public function copyToRecordToLinkTool($meeting)
    {
        if (!isset($meeting['id'])) {
            return '';
        }

        return api_get_path(WEB_PLUGIN_PATH).
            'Bbb/listing.php?'.$this->getUrlParams().'&action=copy_record_to_link_tool&id='.$meeting['id'];
    }

    /**
     * Function disabled
     */
    public function publishMeeting($id)
    {
        if (empty($id)) {
            return false;
        }

        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        $meeting = $repo->find($id);
        if (!$meeting) {
            return false;
        }

        $meeting->setVisibility(1);
        $em->flush();

        return true;
    }

    /**
     * Function disabled
     */
    public function unpublishMeeting($id)
    {
        if (empty($id)) {
            return false;
        }

        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        $meeting = $repo->find($id);
        if (!$meeting) {
            return false;
        }

        $meeting->setVisibility(0);
        $em->flush();

        return true;
    }

    /**
     * Get users online in the current course room.
     *
     * @return int The number of users currently connected to the videoconference
     * @assert () > -1
     */
    public function getUsersOnlineInCurrentRoom()
    {
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $em = Database::getManager();
        $repo = $em->getRepository(ConferenceMeeting::class);

        $qb = $repo->createQueryBuilder('m')
            ->where('m.status = 1')
            ->andWhere('m.accessUrl = :accessUrl')
            ->setParameter('accessUrl', $this->accessUrl)
            ->setMaxResults(1);

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $qb->andWhere('m.course = :courseId')
                ->andWhere('m.session = :sessionId')
                ->andWhere('m.group = :groupId')
                ->setParameter('courseId', $courseId)
                ->setParameter('sessionId', $sessionId)
                ->setParameter('groupId', $groupId);
        } elseif ($this->isGlobalConferencePerUserEnabled()) {
            $qb->andWhere('m.user = :userId')
                ->setParameter('userId', $this->userId);
        } else {
            $qb->andWhere('m.course = :courseId')
                ->andWhere('m.session = :sessionId')
                ->setParameter('courseId', $courseId)
                ->setParameter('sessionId', $sessionId);
        }

        $meetingData = $qb->getQuery()->getOneOrNullResult();

        if (!$meetingData) {
            return 0;
        }
        $pass = $meetingData->getModeratorPw();
        $info = $this->getMeetingInfo([
            'meetingId' => $meetingData->getRemoteId(),
            'password' => $pass,
        ]);
        if ($info === false) {
            $info = $this->getMeetingInfo([
                'meetingId' => $meetingData->getId(),
                'password' => $pass,
            ]);
        }

        if (!empty($info) && isset($info['participantCount'])) {
            return (int) $info['participantCount'];
        }

        return 0;
    }

    /**
     * @param int    $id
     * @param string $recordId
     *
     * @return bool
     */
    public function regenerateRecording($id, $recordId = '')
    {
        if ($this->plugin->get('allow_regenerate_recording') !== 'true') {
            return false;
        }

        if (empty($id)) {
            return false;
        }

        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        $meetingData = $repo->findOneAsArrayById((int) $id);
        if (!$meetingData) {
            return false;
        }

        Event::addEvent(
            'bbb_regenerate_record',
            'record_id',
            (int) $recordId,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        /** @var ConferenceRecordingRepository $recordingRepo */
        $recordingRepo = $em->getRepository(ConferenceRecordingRepository::class);
        $recordings = $recordingRepo->findByMeetingRemoteId($meetingData['remoteId']);

        if (!empty($recordings) && isset($recordings['messageKey']) && $recordings['messageKey'] === 'noRecordings') {
            if (!empty($meetingData['internalMeetingId'])) {
                return $this->api->generateRecording(['recordId' => $meetingData['internalMeetingId']]);
            }

            return false;
        }

        if (!empty($recordings['records'])) {
            foreach ($recordings['records'] as $record) {
                if ($recordId == $record['recordId']) {
                    return $this->api->generateRecording(['recordId' => $recordId]);
                }
            }
        }

        return false;
    }

    /**
     * Deletes a recording of a meeting
     *
     * @param int $id ID of the recording
     *
     * @return bool
     *
     * @assert () === false
     * @todo Also delete links and agenda items created from this recording
     */
    public function deleteRecording($id)
    {
        if (empty($id)) {
            return false;
        }

        $em = Database::getManager();

        /** @var ConferenceMeetingRepository $meetingRepo */
        $meetingRepo = $em->getRepository(ConferenceMeeting::class);
        $meetingData = $meetingRepo->findOneAsArrayById((int) $id);
        if (!$meetingData) {
            return false;
        }

        Event::addEvent(
            'bbb_delete_record',
            'meeting_id',
            $id,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        $delete = false;
        $recordings = [];

        if (!empty($meetingData['remoteId'])) {
            Event::addEvent(
                'bbb_delete_record',
                'remote_id',
                $meetingData['remoteId'],
                null,
                api_get_user_id(),
                api_get_course_int_id(),
                api_get_session_id()
            );

            /** @var ConferenceRecordingRepository $recordingRepo */
            $recordingRepo = $em->getRepository(ConferenceRecording::class);
            $recordings = $recordingRepo->findByMeetingRemoteId($meetingData['remoteId']);
        }

        if (!empty($recordings) && isset($recordings['messageKey']) && $recordings['messageKey'] === 'noRecordings') {
            $delete = true;
        } elseif (!empty($recordings['records'])) {
            $recordsToDelete = [];
            foreach ($recordings['records'] as $record) {
                $recordsToDelete[] = $record['recordId'];
            }

            if (!empty($recordsToDelete)) {
                $recordingParams = ['recordId' => implode(',', $recordsToDelete)];
                Event::addEvent(
                    'bbb_delete_record',
                    'record_id_list',
                    implode(',', $recordsToDelete),
                    null,
                    api_get_user_id(),
                    api_get_course_int_id(),
                    api_get_session_id()
                );

                $result = $this->api->deleteRecordingsWithXmlResponseArray($recordingParams);

                if (!empty($result) && isset($result['deleted']) && $result['deleted'] === 'true') {
                    $delete = true;
                }
            }
        }

        if (!$delete) {
            $delete = true;
        }

        if ($delete) {
            /** @var ConferenceActivityRepository $activityRepo */
            $activityRepo = $em->getRepository(ConferenceActivity::class);
            $activityRepo->closeAllByMeetingId((int) $id);

            $meeting = $meetingRepo->find((int) $id);
            if ($meeting) {
                $em->remove($meeting);
            }

            $em->flush();
        }

        return $delete;
    }

    /**
     * Creates a link in the links tool from the given videoconference recording
     *
     * @param int $id ID of the item in the plugin_bbb_meeting table
     * @param string Hash identifying the recording, as provided by the API
     *
     * @return mixed ID of the newly created link, or false on error
     * @assert (null, null) === false
     * @assert (1, null) === false
     * @assert (null, 'abcdefabcdefabcdefabcdef') === false
     */
    public function copyRecordingToLinkTool($id)
    {
        if (empty($id)) {
            return false;
        }

        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        $meetingData = $repo->findOneAsArrayById((int) $id);
        if (!$meetingData || empty($meetingData['remoteId'])) {
            return false;
        }

        $records = $this->api->getRecordingsWithXmlResponseArray([
            'meetingId' => $meetingData['remoteId']
        ]);

        if (!empty($records)) {
            if (isset($records['message']) && !empty($records['message'])) {
                if ($records['messageKey'] == 'noRecordings') {
                    return false;
                }
            } else {
                $record = $records[0];
                if (is_array($record) && isset($record['recordId'])) {
                    $url = $record['playbackFormatUrl'];
                    $link = new \Link();
                    $params = [
                        'url' => $url,
                        'title' => $meetingData['title'],
                    ];
                    $id = $link->save($params);

                    return $id;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the video conference server is running.
     * Function currently disabled (always returns 1)
     * @return bool True if server is running, false otherwise
     * @assert () === false
     */
    public function isServerRunning()
    {
        return true;
        //return BigBlueButtonBN::isServerRunning($this->protocol.$this->url);
    }

    /**
     * Checks if the video conference plugin is properly configured
     * @return bool True if plugin has a host and a salt, false otherwise
     * @assert () === false
     */
    public function isServerConfigured()
    {
        $host = $this->plugin->get('host');

        if (empty($host)) {
            return false;
        }

        $salt = $this->plugin->get('salt');

        if (empty($salt)) {
            return false;
        }

        return true;
        //return BigBlueButtonBN::isServerRunning($this->protocol.$this->url);
    }

    /**
     * Get active session in the all platform
     */
    public function getActiveSessionsCount(): int
    {
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();

        $qb->select('COUNT(m.id)')
            ->from(ConferenceMeeting::class, 'm')
            ->where('m.status = :status')
            ->andWhere('m.accessUrl = :accessUrl')
            ->setParameter('status', 1)
            ->setParameter('accessUrl', $this->accessUrl);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get active session in the all platform
     */
    public function getActiveSessions(): array
    {
        $em = Database::getManager();
        $repo = $em->getRepository(ConferenceMeeting::class);

        $qb = $repo->createQueryBuilder('m')
            ->where('m.status = :status')
            ->andWhere('m.accessUrl = :accessUrl')
            ->setParameter('status', 1)
            ->setParameter('accessUrl', $this->accessUrl);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param string $url
     */
    public function redirectToBBB($url)
    {
        if (file_exists(__DIR__.'/../config.vm.php')) {
            // Using VM
            echo Display::url($this->plugin->get_lang('ClickToContinue'), $url);
            exit;
        } else {
            // Classic
            header("Location: $url");
            exit;
        }
    }

    /**
     * @return string
     */
    public function getConferenceUrl()
    {
        return api_get_path(WEB_PLUGIN_PATH).'Bbb/start.php?launch=1&'.$this->getUrlParams();
    }

    /**
     * Get the meeting info from DB by its name
     *
     * @param string $name
     *
     * @return array
     */
    public function findMeetingByName(string $name): ?array
    {
        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        $qb = $repo->createQueryBuilder('m')
            ->where('m.title = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getArrayResult();

        return $result[0] ?? null;
    }

    /**
     * Get the meeting info from DB by its name
     *
     * @param int $id
     *
     * @return array
     */
    public function getMeeting(int $id): ?array
    {
        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        return $repo->findOneAsArrayById($id);
    }

    /**
     * Get the meeting info.
     *
     * @param int $id
     *
     * @return array
     */
    public function getMeetingByRemoteId(string $id): ?array
    {
        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        return $repo->findOneByRemoteIdAndAccessUrl($id, $this->accessUrl);
    }

    /**
     * @param int $meetingId
     *
     * @return array
     */
    public function findConnectedMeetingParticipants(int $meetingId): array
    {
        $em = Database::getManager();
        /** @var ConferenceActivityRepository $repo */
        $repo = $em->getRepository(ConferenceActivity::class);

        $activities = $repo->createQueryBuilder('a')
            ->where('a.meeting = :meetingId')
            ->andWhere('a.inAt IS NOT NULL')
            ->setParameter('meetingId', $meetingId)
            ->getQuery()
            ->getResult();

        $participantIds = [];
        $return = [];

        foreach ($activities as $activity) {
            $participant = $activity->getParticipant();
            $participantId = $participant?->getId();

            if (!$participantId || in_array($participantId, $participantIds)) {
                continue;
            }

            $participantIds[] = $participantId;

            $return[] = [
                'id' => $activity->getId(),
                'meeting_id' => $meetingId,
                'participant' => api_get_user_entity($participantId),
                'in_at' => $activity->getInAt()?->format('Y-m-d H:i:s'),
                'out_at' => $activity->getOutAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $return;
    }

    /**
     * Check if the meeting has a capture.m4v video file. If exists then the has_video_m4v field is updated
     *
     * @param int $meetingId
     *
     * @return bool
     */
    public function checkDirectMeetingVideoUrl(int $meetingId): bool
    {
        $em = Database::getManager();
        /** @var ConferenceMeetingRepository $repo */
        $repo = $em->getRepository(ConferenceMeeting::class);

        $meetingInfo = $repo->findOneAsArrayById($meetingId);

        if (empty($meetingInfo) || !isset($meetingInfo['videoUrl'])) {
            return false;
        }

        $hasCapture = SocialManager::verifyUrl($meetingInfo['videoUrl'].'/capture.m4v');

        if ($hasCapture) {
            $qb = $em->createQueryBuilder();
            $qb->update(ConferenceMeeting::class, 'm')
                ->set('m.hasVideoM4v', ':value')
                ->where('m.id = :id')
                ->setParameter('value', true)
                ->setParameter('id', $meetingId)
                ->getQuery()
                ->execute();

            return true;
        }

        return false;
    }
}
