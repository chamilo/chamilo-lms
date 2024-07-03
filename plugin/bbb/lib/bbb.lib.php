<?php

/* For licensing terms, see /license.txt */

/**
 * Class bbb
 * This script initiates a video conference session, calling the BigBlueButton
 * API BigBlueButton-Chamilo connector class
 */
class bbb
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
        $this->plugin = BBBPlugin::create();
        $bbbPluginEnabled = $this->plugin->get('tool_enable');

        $bbb_host = !empty($host) ? $host : $this->plugin->get('host');
        $bbb_salt = !empty($salt) ? $salt : $this->plugin->get('salt');

        $this->table = Database::get_main_table('plugin_bbb_meeting');
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
                $bbbSetting = api_get_setting('bbb_enable_conference_in_course_groups');
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
        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'
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
        if (empty($this->courseCode) && !$courseId) {
            if ($this->isGlobalConferencePerUserEnabled()) {
                return 'global=1&user_id='.$this->userId;
            }

            if ($this->isGlobalConference()) {
                return 'global=1';
            }

            return '';
        }

        $courseCode = $this->courseCode;
        if (!empty($courseId)) {
            $course = api_get_course_info_by_id($courseId);
            if ($course) {
                $courseCode = $course['code'];
            }
        }

        return http_build_query(
            [
                'cidReq' => $courseCode,
                'id_session' => $sessionId ?: $this->sessionId,
                'gidReq' => $groupId ?: $this->groupId,
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

        $params['attendee_pw'] = isset($params['attendee_pw']) ? $params['attendee_pw'] : $this->getUserMeetingPassword();
        $attendeePassword = $params['attendee_pw'];
        $params['moderator_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : $this->getModMeetingPassword();
        $moderatorPassword = $params['moderator_pw'];

        $params['record'] = api_get_course_plugin_setting('bbb', 'big_blue_button_record_and_store') == 1;
        $max = api_get_course_plugin_setting('bbb', 'max_users_limit');
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

        $id = Database::insert($this->table, $params);

        if ($id) {
            Event::addEvent(
                'bbb_create_meeting',
                'meeting_id',
                (int) $id,
                null,
                api_get_user_id(),
                api_get_course_int_id(),
                api_get_session_id()
            );

            $meetingName = $params['meeting_name'] ?? $this->generateVideoConferenceName();
            $welcomeMessage = $params['welcome_msg'] ?? null;
            $record = $params['record'] ? 'true' : 'false';
            //$duration = isset($params['duration']) ? intval($params['duration']) : 0;
            // This setting currently limits the maximum conference duration,
            // to avoid lingering sessions on the video-conference server #6261
            $duration = 300;
            $meetingDuration = (int) $this->plugin->get('meeting_duration');
            if (!empty($meetingDuration)) {
                $duration = $meetingDuration;
            }
            $url = api_get_access_url(api_get_current_access_url_id())['url'];
            $bbbParams = array(
                'meetingId' => $params['remote_id'], // REQUIRED
                'meetingName' => $meetingName, // REQUIRED
                'attendeePw' => $attendeePassword, // Match this value in getJoinMeetingURL() to join as attendee.
                'moderatorPw' => $moderatorPassword, // Match this value in getJoinMeetingURL() to join as moderator.
                'welcomeMsg' => $welcomeMessage, // ''= use default. Change to customize.
                'dialNumber' => '', // The main number to call into. Optional.
                'voiceBridge' => $params['voice_bridge'], // PIN to join voice. Required.
                'webVoice' => '', // Alphanumeric to join voice. Optional.
                'logoutUrl' => $this->logoutUrl.'&action=logout&remote_id='.$params['remote_id'],
                'maxParticipants' => $max, // Optional. -1 = unlimitted. Not supported in BBB. [number]
                'record' => $record, // New. 'true' will tell BBB to record the meeting.
                'duration' => $duration, // Default = 0 which means no set duration in minutes. [number]
                'meta_OriginURL' => $url, // Add url information to BBB meeting info (see 'meta' info at https://docs.bigbluebutton.org/dev/api.html#create)
                //'meta_category' => '',  // Use to pass additional info to BBB server. See API docs.
            );

            $status = false;
            $meeting = null;
            while ($status === false) {
                $result = $this->api->createMeetingWithXmlResponseArray($bbbParams);
                if (isset($result) && strval($result['returncode']) == 'SUCCESS') {
                    if ($this->plugin->get('allow_regenerate_recording') === 'true') {
                        $internalId = Database::escape_string($result['internalMeetingID']);
                        $sql = "UPDATE $this->table SET internal_meeting_id = '".$internalId."'
                                WHERE id = $id";
                        Database::query($sql);
                    }
                    $meeting = $this->joinMeeting($meetingName, true);

                    return $meeting;
                }
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
        $courseCode = empty($courseCode) ? api_get_course_id() : $courseCode;

        return $courseCode;
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
     * Get the info from the current open videoconference.
     * Otherwise, return false.
     *
     * @return array|bool
     */
    public function getCurrentVideoConference()
    {
        $whereConditions = [
            'status = ?' => 1,
        ];

        if ($this->isGlobalConferencePerUserEnabled()) {
            $whereConditions[' AND user_id = ?'] = $this->userId;
        }

        if ($this->isGlobalConference()) {
            $whereConditions[' AND access_url = ?'] = api_get_current_access_url_id();
        }

        if ($this->hasGroupSupport()) {
            $whereConditions[' AND group_id = ?'] = api_get_group_id();
        }

        $cId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        if ($cId) {
            $whereConditions[' AND c_id = ?'] = api_get_course_int_id();
        }

        if ($sessionId) {
            $whereConditions[' AND session_id = ?'] = api_get_session_id();
        }

        return Database::select(
            '*',
            $this->table,
            [
                'where' => $whereConditions,
                'order' => 'created_at DESC',
            ],
            'first'
        );
    }

    public function generateVideoConferenceName(string $defaultName = null): string
    {
        $nameFilter = function ($name) {
            return URLify::filter(
                $name,
                64,
                '',
                true,
                true,
                true,
                false
            );
        };

        if (!empty($defaultName)) {
            $name = $nameFilter($defaultName);

            if (!empty($name)) {
                return $name;
            }
        }

        $urlId = api_get_current_access_url_id();

        if ($this->isGlobalConferencePerUserEnabled()) {
            return $nameFilter("url_{$this->userId}_$urlId");
        }

        if ($this->isGlobalConference()) {
            return $nameFilter("url_$urlId");
        }

        $course = api_get_course_entity();
        $session = api_get_session_entity();
        $group = api_get_group_entity();

        if ($this->hasGroupSupport()) {
            $name = implode(
                '-',
                [
                    $course->getCode(),
                    $session ? $session->getName() : '',
                    $group ? $group->getName() : '',
                ]
            );

            return $nameFilter($name);
        }

        $name = implode(
            '-',
            [
                $course->getCode(),
                $session ? $session->getName() : '',
            ]
        );

        return $nameFilter($name);
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
        if ($manager) {
            $pass = $this->getModMeetingPassword();
        } else {
            $pass = $this->getUserMeetingPassword();
        }

        $meetingData = Database::select(
            '*',
            $this->table,
            array(
                'where' => array(
                    'meeting_name = ? AND status = 1' => array(
                        $meetingName,
                    ),
                ),
            ),
            'first'
        );

        if (empty($meetingData) || !is_array($meetingData)) {
            if ($this->debug) {
                error_log("meeting does not exist: $meetingName");
            }

            return false;
        }

        $params = array(
            'meetingId' => $meetingData['remote_id'],
            //  -- REQUIRED - The unique id for the meeting
            'password' => $this->getModMeetingPassword()
            //  -- REQUIRED - The moderator password for the meeting
        );

        $meetingInfoExists = false;
        $meetingIsRunningInfo = $this->getMeetingInfo($params);
        if ($this->debug) {
            error_log('Searching meeting with params:');
            error_log(print_r($params, 1));
            error_log('Result:');
            error_log(print_r($meetingIsRunningInfo, 1));
        }

        if ($meetingIsRunningInfo === false) {
            // checking with the remote_id didn't work, so just in case and
            // to provide backwards support, check with the id
            $params = array(
                'meetingId' => $meetingData['id'],
                //  -- REQUIRED - The unique id for the meeting
                'password' => $this->getModMeetingPassword()
                //  -- REQUIRED - The moderator password for the meeting
            );
            $meetingIsRunningInfo = $this->getMeetingInfo($params);
            if ($this->debug) {
                error_log('Searching meetingId with params:');
                error_log(print_r($params, 1));
                error_log('Result:');
                error_log(print_r($meetingIsRunningInfo, 1));
            }
        }

        if (strval($meetingIsRunningInfo['returncode']) === 'SUCCESS' &&
            isset($meetingIsRunningInfo['meetingName']) &&
            !empty($meetingIsRunningInfo['meetingName'])
        ) {
            $meetingInfoExists = true;
        }

        if ($this->debug) {
            error_log(
                "meeting is running: ".intval($meetingInfoExists)
            );
        }

        $url = false;
        if ($meetingInfoExists) {
            $joinParams = [
                'meetingId' => $meetingData['remote_id'],
                //	-- REQUIRED - A unique id for the meeting
                'username' => $this->userCompleteName,
                //-- REQUIRED - The name that will display for the user in the meeting
                'password' => $pass,
                //-- REQUIRED - The attendee or moderator password, depending on what's passed here
                //'createTime' => api_get_utc_datetime(),			//-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                'userID' => api_get_user_id(),
                //-- OPTIONAL - string
                'webVoiceConf' => '',
            ];
            $url = $this->api->getJoinMeetingURL($joinParams);
            $url = $this->protocol.$url;
        }

        if ($this->debug) {
            error_log("return url :".$url);
        }

        return $url;
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
    public function getMeetingParticipantInfo($meetingId, $userId)
    {
        $meetingData = Database::select(
            '*',
            'plugin_bbb_room',
            array('where' => array('meeting_id = ? AND participant_id = ?' => [$meetingId, $userId])),
            'first'
        );

        if ($meetingData) {
            return $meetingData;
        }

        return [];
    }

    /**
     * Save a participant in a meeting room
     *
     * @param int $meetingId
     * @param int $participantId
     *
     * @return false|int The last inserted ID. Otherwise return false
     */
    public function saveParticipant($meetingId, $participantId)
    {
        $meetingData = Database::select(
            '*',
            'plugin_bbb_room',
            [
                'where' => [
                    'meeting_id = ? AND participant_id = ? AND close = ?' => [
                        $meetingId,
                        $participantId,
                        BBBPlugin::ROOM_OPEN,
                    ],
                ],
            ]
        );

        foreach ($meetingData as $roomItem) {
            $inAt = $roomItem['in_at'];
            $outAt = $roomItem['out_at'];
            $roomId = $roomItem['id'];
            if (!empty($roomId)) {
                if ($inAt != $outAt) {
                    Database::update(
                        'plugin_bbb_room',
                        ['close' => BBBPlugin::ROOM_CLOSE],
                        ['id = ? ' => $roomId]
                    );
                } else {
                    Database::update(
                        'plugin_bbb_room',
                        ['out_at' => api_get_utc_datetime(), 'close' => BBBPlugin::ROOM_CLOSE],
                        ['id = ? ' => $roomId]
                    );
                }
            }
        }

        $params = [
            'meeting_id' => $meetingId,
            'participant_id' => $participantId,
            'in_at' => api_get_utc_datetime(),
            'out_at' => api_get_utc_datetime(),
            'close' => BBBPlugin::ROOM_OPEN,
        ];

        return Database::insert(
            'plugin_bbb_room',
            $params
        );
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

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $conditions = array(
            'where' => array(
                'c_id = ? AND session_id = ? AND meeting_name = ? AND status = 1' =>
                 array($courseId, $sessionId, $meetingName),
        ));

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $conditions = array(
                'where' => array(
                    'c_id = ? AND session_id = ? AND meeting_name = ? AND group_id = ? AND status = 1 AND access_url = ?' =>
                        array(
                            $courseId,
                            $sessionId,
                            $meetingName,
                            $groupId,
                            $this->accessUrl,
                        ),
                ),
            );
        }

        $meetingData = Database::select(
            '*',
            $this->table,
            $conditions,
            'first'
        );

        if ($this->debug) {
            error_log('meeting_exists '.print_r($meetingData, 1));
        }

        return $meetingData;
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
        $conditions = array(
            'where' => array(
                'status = ? AND c_id = ? AND session_id = ? ' => array(
                    $status,
                    $courseId,
                    $sessionId,
                ),
            ),
        );

        return Database::select(
            '*',
            $this->table,
            $conditions
        );
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
     * @param int   $start         Optional
     * @param int   $limit         Optional
     *
     * @return array Array of current open meeting rooms
     * @throws Exception
     */
    public function getMeetings(
        $courseId = 0,
        $sessionId = 0,
        $groupId = 0,
        $isAdminReport = false,
        $dateRange = [],
        $start = 0,
        $limit = 0,
        $order = "ASC"
    ) {
        $em = Database::getManager();
        $manager = $this->isConferenceManager();

        $conditions = [];
        if ($courseId || $sessionId || $groupId) {
            $conditions = array(
                'where' => array(
                    'c_id = ? AND session_id = ? ' => array($courseId, $sessionId),
                ),
            );

            if ($this->hasGroupSupport()) {
                $conditions = array(
                    'where' => array(
                        'c_id = ? AND session_id = ? AND group_id = ? ' => array(
                            $courseId,
                            $sessionId,
                            $groupId,
                        ),
                    ),
                );
            }

            if ($this->isGlobalConferencePerUserEnabled()) {
                $conditions = array(
                    'where' => array(
                        'c_id = ? AND session_id = ? AND user_id = ?' => array(
                            $courseId,
                            $sessionId,
                            $this->userId,
                        ),
                    ),
                );
            }
	}
        if ($this->isGlobalConference()) {
            $conditions = array(
                'where' => array(
                    'c_id = ? AND user_id = ?' => array(
                        0,
                        $this->userId,
                     ),
                 ),
            );
        }

        if (!empty($dateRange)) {
            $dateStart = date_create($dateRange['search_meeting_start']);
            $dateStart = date_format($dateStart, 'Y-m-d H:i:s');
            $dateEnd = date_create($dateRange['search_meeting_end']);
            $dateEnd = $dateEnd->add(new DateInterval('P1D'));
            $dateEnd = date_format($dateEnd, 'Y-m-d H:i:s');

            $conditions = array(
                'where' => array(
                    'created_at BETWEEN ? AND ? ' => array($dateStart, $dateEnd),
                ),
            );
        }

        $conditions['order'] = 'created_at ' . $order;

        if ($limit) {
            $conditions['limit'] = "$start , $limit";
        }

        $meetingList = Database::select(
            '*',
            $this->table,
            $conditions
        );
        $isGlobal = $this->isGlobalConference();
        $newMeetingList = array();
        foreach ($meetingList as $meetingDB) {
            $item = array();
            $item['metting_name'] = $meetingDB['meeting_name'];
            $courseId = $meetingDB['c_id'];
            $courseInfo = api_get_course_info_by_id($courseId);
            $courseCode = '';
            if (!empty($courseInfo)) {
                $courseCode = $courseInfo['code'];
            }

            if ($manager) {
                $pass = $meetingDB['moderator_pw'];
            } else {
                $pass = $meetingDB['attendee_pw'];
            }

            $meetingBBB = $this->getMeetingInfo(
                [
                    'meetingId' => $meetingDB['remote_id'],
                    'password' => $pass,
                ]
            );

            if ($meetingBBB === false) {
                // Checking with the remote_id didn't work, so just in case and
                // to provide backwards support, check with the id
                $params = array(
                    'meetingId' => $meetingDB['id'],
                    //  -- REQUIRED - The unique id for the meeting
                    'password' => $pass
                    //  -- REQUIRED - The moderator password for the meeting
                );
                $meetingBBB = $this->getMeetingInfo($params);
            }

            if ($meetingDB['visibility'] == 0 && $this->isConferenceManager() === false) {
                continue;
            }

            $meetingBBB['end_url'] = $this->endUrl($meetingDB);

            if (isset($meetingBBB['returncode']) && (string) $meetingBBB['returncode'] === 'FAILED') {
                if ($meetingDB['status'] == 1 && $this->isConferenceManager()) {
                    $this->endMeeting($meetingDB['id'], $courseCode);
                }
            } else {
                $meetingBBB['add_to_calendar_url'] = $this->addToCalendarUrl($meetingDB);
            }

            if ($meetingDB['record'] == 1) {
                // backwards compatibility (when there was no remote ID)
                $mId = $meetingDB['remote_id'];
                if (empty($mId)) {
                    $mId = $meetingDB['id'];
                }
                if (empty($mId)) {
                    // if the id is still empty (should *never* occur as 'id' is
                    // the table's primary key), skip this conference
                    continue;
                }

                $record = [];
                $recordingParams = ['meetingId' => $mId];
                $records = $this->api->getRecordingsWithXmlResponseArray($recordingParams);

                if (!empty($records)) {
                    if (!isset($records['messageKey']) || $records['messageKey'] !== 'noRecordings') {
                        $record = end($records);
                        if (!is_array($record) || !isset($record['recordId'])) {
                            continue;
                        }

                        if (!empty($record['playbackFormat'])) {
                            $this->updateMeetingVideoUrl($meetingDB['id'], $record['playbackFormatUrl']);
                        }
                    }
                }

                if (isset($record['playbackFormat']) && !empty($record['playbackFormat'])) {
                    $recordLink = [];
                    foreach ($record['playbackFormat'] as $format) {
                        $this->insertMeetingFormat(intval($meetingDB['id']), $format->type->__toString(), $format->url->__toString());
                        $recordLink['record'][] = 1;
                        $recordLink[] = Display::url(
                            $this->plugin->get_lang($format->type->__toString()),
                            $format->url->__toString(),
                            ['target' => '_blank', 'class' => 'btn btn-default']
                        );
                    }
                } else {
                    $recordLink = $this->plugin->get_lang('NoRecording');
                }

                if ($isAdminReport) {
                    $this->forceCIdReq(
                        $courseInfo['code'],
                        $meetingDB['session_id'],
                        $meetingDB['group_id']
                    );
                }

                $actionLinks = $this->getActionLinks(
                    $meetingDB,
                    $record,
                    $isGlobal,
                    $isAdminReport
                );
                $item['show_links'] = $recordLink;
                $item['record'] = true;
            } else {
                $actionLinks = $this->getActionLinks(
                    $meetingDB,
                    [],
                    $isGlobal,
                    $isAdminReport
                );

                $item['show_links'] = $this->plugin->get_lang('NoRecording');
                $item['record'] = false;
            }

            $item['action_links'] = implode(PHP_EOL, $actionLinks);
            $item['created_at'] = api_convert_and_format_date($meetingDB['created_at']);
            // created_at
            $meetingDB['created_at'] = $item['created_at']; //avoid overwrite in array_merge() below

            $item['closed_at'] = '';
            if (!empty($meetingDB['closed_at'])) {
                $item['closed_at'] = api_convert_and_format_date($meetingDB['closed_at']);
                $meetingDB['closed_at'] = $item['closed_at'];
            }

            $item['publish_url'] = $this->publishUrl($meetingDB);
            $item['unpublish_url'] = $this->unPublishUrl($meetingBBB);

            if ($meetingDB['status'] == 1) {
                $joinParams = [
                    'meetingId' => $meetingDB['remote_id'],
                    //-- REQUIRED - A unique id for the meeting
                    'username' => $this->userCompleteName,
                    //-- REQUIRED - The name that will display for the user in the meeting
                    'password' => $pass,
                    //-- REQUIRED - The attendee or moderator password, depending on what's passed here
                    'createTime' => '',
                    //-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                    'userID' => '',
                    //	-- OPTIONAL - string
                    'webVoiceConf' => '',
                ];
                $item['go_url'] = $this->protocol.$this->api->getJoinMeetingURL($joinParams);
            }
            $item = array_merge($item, $meetingDB, $meetingBBB);

            $item['course'] = $em->find('ChamiloCoreBundle:Course', $item['c_id']);
            $item['session'] = $em->find('ChamiloCoreBundle:Session', $item['session_id']);
            $newMeetingList[] = $item;
        }

        return $newMeetingList;
    }

    /**
     * Counts all the course meetings saved in the plugin_bbb_meeting table.
     *
     * @param int   $courseId
     * @param int   $sessionId
     * @param int   $groupId
     * @param array $dateRange
     *
     * @return int Count of meetings
     * @throws Exception
     */
    public function getCountMeetings(
        $courseId = 0,
        $sessionId = 0,
        $groupId = 0,
        $dateRange = []
    ) {
        $conditions = [];
        if ($courseId || $sessionId || $groupId) {
            $conditions = array(
                'where' => array(
                    'c_id = ? AND session_id = ? ' => array($courseId, $sessionId),
                ),
            );

            if ($this->hasGroupSupport()) {
                $conditions = array(
                    'where' => array(
                        'c_id = ? AND session_id = ? AND group_id = ? ' => array(
                            $courseId,
                            $sessionId,
                            $groupId,
                        ),
                    ),
                );
            }

            if ($this->isGlobalConferencePerUserEnabled()) {
                $conditions = array(
                    'where' => array(
                        'c_id = ? AND session_id = ? AND user_id = ?' => array(
                            $courseId,
                            $sessionId,
                            $this->userId,
                        ),
                    ),
                );
            }
        }

        if (!empty($dateRange)) {
            $dateStart = date_create($dateRange['search_meeting_start']);
            $dateStart = date_format($dateStart, 'Y-m-d H:i:s');
            $dateEnd = date_create($dateRange['search_meeting_end']);
            $dateEnd = $dateEnd->add(new DateInterval('P1D'));
            $dateEnd = date_format($dateEnd, 'Y-m-d H:i:s');

            $conditions = array(
                'where' => array(
                    'created_at BETWEEN ? AND ? ' => array($dateStart, $dateEnd),
                ),
            );
        }

        $row = Database::select(
            'count(*) as count',
            $this->table,
            $conditions,
            'first'
        );

        return $row['count'];
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=end&id='.$meeting['id'];
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

        $meetingData = Database::select(
            '*',
            $this->table,
            array('where' => array('id = ?' => array($id))),
            'first'
        );
        $manager = $this->isConferenceManager();
        if ($manager) {
            $pass = $meetingData['moderator_pw'];
        } else {
            $pass = $meetingData['attendee_pw'];
        }

        Event::addEvent(
            'bbb_end_meeting',
            'meeting_id',
            (int) $id,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        $endParams = array(
            'meetingId' => $meetingData['remote_id'], // REQUIRED - We have to know which meeting to end.
            'password' => $pass, // REQUIRED - Must match moderator pass for meeting.
        );
        $this->api->endMeetingWithXmlResponseArray($endParams);
        Database::update(
            $this->table,
            array('status' => 0, 'closed_at' => api_get_utc_datetime()),
            array('id = ? ' => $id)
        );

        // Update users with in_at y ou_at field equal
        $roomTable = Database::get_main_table('plugin_bbb_room');
        $conditions['where'] = ['meeting_id=? AND in_at=out_at AND close=?' => [$id, BBBPlugin::ROOM_OPEN]];
        $roomList = Database::select(
            '*',
            $roomTable,
            $conditions
        );

        foreach ($roomList as $roomDB) {
            $roomId = $roomDB['id'];
            if (!empty($roomId)) {
                Database::update(
                    $roomTable,
                    ['out_at' => api_get_utc_datetime(), 'close' => BBBPlugin::ROOM_CLOSE],
                    ['id = ? ' => $roomId]
                );
            }
        }

        // Close all meeting rooms with meeting ID
        Database::update(
            $roomTable,
            ['close' => BBBPlugin::ROOM_CLOSE],
            ['meeting_id = ? ' => $id]
        );
    }

    /**
     * @param array $meeting
     * @param array $record
     *
     * @return string
     */
    public function addToCalendarUrl($meeting, $record = [])
    {
        $url = isset($record['playbackFormatUrl']) ? $record['playbackFormatUrl'] : '';

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams(
            ).'&action=add_to_calendar&id='.$meeting['id'].'&start='.api_strtotime($meeting['created_at']).'&url='.$url;
    }

    /**
     * @param int    $meetingId
     * @param string $videoUrl
     *
     * @return bool|int
     */
    public function updateMeetingVideoUrl($meetingId, $videoUrl)
    {
        return Database::update(
            'plugin_bbb_meeting',
            ['video_url' => $videoUrl],
            ['id = ?' => intval($meetingId)]
        );
    }

    /**
     * @param int $meetingId
     * @param string $formatType
     * @param string $resourceUrl
     *
     * @return bool|int
     */
    public function insertMeetingFormat(int $meetingId, string $formatType, string $resourceUrl)
    {
        $em = Database::getManager();
        $sm = $em->getConnection()->getSchemaManager();
        if ($sm->tablesExist('plugin_bbb_meeting_format')) {
            return Database::insert(
                'plugin_bbb_meeting_format',
                [
                    'format_type' => $formatType,
                    'resource_url' => $resourceUrl,
                    'meeting_id' => $meetingId
                ]
            );
        }

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
                Display::return_icon('visible.png', get_lang('MakeInvisible')),
                $this->unPublishUrl($meetingInfo)
            )
            : Display::url(
                Display::return_icon('invisible.png', get_lang('MakeVisible')),
                $this->publishUrl($meetingInfo)
            );

        $links = [];
        if ($this->plugin->get('allow_regenerate_recording') === 'true' && $meetingInfo['record'] == 1) {
            if (!empty($recordInfo)) {
                $links[] = Display::url(
                    Display::return_icon('reload.png', get_lang('RegenerateRecord')),
                    $this->regenerateRecordUrl($meetingInfo, $recordInfo)
                );
            } else {
                $links[] = Display::url(
                    Display::return_icon('reload.png', get_lang('RegenerateRecord')),
                    $this->regenerateRecordUrlFromMeeting($meetingInfo)
                );
            }
        }

        if (empty($recordInfo)) {
            if (!$isAdminReport) {
                if ($meetingInfo['status'] == 0) {
                    $links[] = Display::url(
                        Display::return_icon('delete.png', get_lang('Delete')),
                        $this->deleteRecordUrl($meetingInfo)
                    );
                    $links[] = $linkVisibility;
                }

                return $links;
            } else {
                $links[] = Display::url(
                    Display::return_icon('course_home.png', get_lang('GoToCourse')),
                    $this->getListingUrl($meetingInfo['c_id'], $meetingInfo['session_id'], $meetingInfo['group_id'])
                );

                return $links;
            }
        }

        if (!$isGlobal) {
            $links[] = Display::url(
                Display::return_icon('link.gif', get_lang('UrlMeetingToShare')),
                $this->copyToRecordToLinkTool($meetingInfo)
            );
            $links[] = Display::url(
                Display::return_icon('agenda.png', get_lang('AddToCalendar')),
                $this->addToCalendarUrl($meetingInfo, $recordInfo)
            );
        }

        $hide = $this->plugin->get('disable_download_conference_link') === 'true' ? true : false;

        if ($hide == false) {
            if ($meetingInfo['has_video_m4v']) {
                $links[] = Display::url(
                    Display::return_icon('save.png', get_lang('DownloadFile')),
                    $recordInfo['playbackFormatUrl'].'/capture.m4v',
                    ['target' => '_blank']
                );
            } else {
                $links[] = Display::url(
                    Display::return_icon('save.png', get_lang('DownloadFile')),
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
                Display::return_icon('delete.png', get_lang('Delete')),
                $this->deleteRecordUrl($meetingInfo)
            );
            $links[] = $linkVisibility;
        } else {
            $links[] = Display::url(
                Display::return_icon('course_home.png', get_lang('GoToCourse')),
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams(
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams(
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams(
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
            'bbb/listing.php?'.$this->getUrlParams().'&action=copy_record_to_link_tool&id='.$meeting['id'];
    }

    /**
     * Function disabled
     */
    public function publishMeeting($id)
    {
        //return BigBlueButtonBN::setPublishRecordings($id, 'true', $this->url, $this->salt);
        if (empty($id)) {
            return false;
        }
        $id = intval($id);
        Database::update($this->table, array('visibility' => 1), array('id = ? ' => $id));

        return true;
    }

    /**
     * Function disabled
     */
    public function unpublishMeeting($id)
    {
        //return BigBlueButtonBN::setPublishRecordings($id, 'false', $this->url, $this->salt);
        if (empty($id)) {
            return false;
        }
        $id = intval($id);
        Database::update($this->table, array('visibility' => 0), array('id = ?' => $id));

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

        $conditions = array(
            'where' => array(
                'c_id = ? AND session_id = ? AND status = 1 AND access_url = ?' => array(
                    $courseId,
                    $sessionId,
                    $this->accessUrl,
                ),
            ),
        );

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $conditions = array(
                'where' => array(
                    'c_id = ? AND session_id = ? AND group_id = ? AND status = 1 AND access_url = ?' => array(
                        $courseId,
                        $sessionId,
                        $groupId,
                        $this->accessUrl,
                    ),
                ),
            );
        }

        if ($this->isGlobalConferencePerUserEnabled()) {
            $conditions = array(
                'where' => array(
                    'user_id = ? AND status = 1 AND access_url = ?' => array(
                        $this->userId,
                        $this->accessUrl,
                    ),
                ),
            );
        }

        $meetingData = Database::select(
            '*',
            $this->table,
            $conditions,
            'first'
        );

        if (empty($meetingData)) {
            return 0;
        }
        $pass = $meetingData['moderator_pw'];
        $info = $this->getMeetingInfo(array('meetingId' => $meetingData['remote_id'], 'password' => $pass));
        if ($info === false) {
            //checking with the remote_id didn't work, so just in case and
            // to provide backwards support, check with the id
            $params = array(
                'meetingId' => $meetingData['id'],
                //  -- REQUIRED - The unique id for the meeting
                'password' => $pass
                //  -- REQUIRED - The moderator password for the meeting
            );
            $info = $this->getMeetingInfo($params);
        }

        if (!empty($info) && isset($info['participantCount'])) {
            return $info['participantCount'];
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

        $meetingData = Database::select(
            '*',
            $this->table,
            array('where' => array('id = ?' => array($id))),
            'first'
        );

        Event::addEvent(
            'bbb_regenerate_record',
            'record_id',
            (int) $recordId,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        // Check if there are recordings for this meeting
        $recordings = $this->api->getRecordings(['meetingId' => $meetingData['remote_id']]);
        if (!empty($recordings) && isset($recordings['messageKey']) && $recordings['messageKey'] === 'noRecordings') {
            // Regenerate the meeting id
            if (!empty($meetingData['internal_meeting_id'])) {
                return $this->api->generateRecording(['recordId' => $meetingData['internal_meeting_id']]);
            }

            /*$pass = $this->getModMeetingPassword();
            $info = $this->getMeetingInfo(['meetingId' => $meetingData['remote_id'], 'password' => $pass]);
            if (!empty($info) && isset($info['internalMeetingID'])) {
                return $this->api->generateRecording(['recordId' => $meetingData['internal_meeting_id']]);
            }*/

            return false;
        } else {
            if (!empty($recordings['records'])) {
                $recordExists = false;
                foreach ($recordings['records'] as $record) {
                    if ($recordId == $record['recordId']) {
                        $recordExists = true;
                        break;
                    }
                }

                if ($recordExists) {
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

        $meetingData = Database::select(
            '*',
            $this->table,
            array('where' => array('id = ?' => array($id))),
            'first'
        );

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
        // Check if there are recordings for this meeting
        if (!empty($meetingData['remote_id'])) {
            Event::addEvent(
                'bbb_delete_record',
                'remote_id',
                $meetingData['remote_id'],
                null,
                api_get_user_id(),
                api_get_course_int_id(),
                api_get_session_id()
            );
            $recordings = $this->api->getRecordings(['meetingId' => $meetingData['remote_id']]);
        }
        if (!empty($recordings) && isset($recordings['messageKey']) && $recordings['messageKey'] == 'noRecordings') {
            $delete = true;
        } else {
            if (!empty($recordings['records'])) {
                $recordsToDelete = [];
                foreach ($recordings['records'] as $record) {
                    $recordsToDelete[] = $record['recordId'];
                }
                $delete = true;
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
        }

        if ($delete) {
            Database::delete(
                'plugin_bbb_room',
                array('meeting_id = ?' => array($id))
            );

            Database::delete(
                $this->table,
                array('id = ?' => array($id))
            );
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
        //$records =  BigBlueButtonBN::getRecordingsUrl($id);
        $meetingData = Database::select(
            '*',
            $this->table,
            array('where' => array('id = ?' => array($id))),
            'first'
        );

        $records = $this->api->getRecordingsWithXmlResponseArray(
            array('meetingId' => $meetingData['remote_id'])
        );

        if (!empty($records)) {
            if (isset($records['message']) && !empty($records['message'])) {
                if ($records['messageKey'] == 'noRecordings') {
                    $recordArray[] = $this->plugin->get_lang('NoRecording');
                } else {
                    //$recordArray[] = $records['message'];
                }

                return false;
            } else {
                $record = $records[0];
                if (is_array($record) && isset($record['recordId'])) {
                    $url = $record['playbackFormatUrl'];
                    $link = new Link();
                    $params['url'] = $url;
                    $params['title'] = $meetingData['meeting_name'];
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
    public function getActiveSessionsCount()
    {
        $meetingList = Database::select(
            'count(id) as count',
            $this->table,
            array('where' => array('status = ? AND access_url = ?' => array(1, $this->accessUrl))),
            'first'
        );

        return $meetingList['count'];
    }

    /**
     * Get active session in the all platform
     *
     * @param boolean $allSites Parameter to indicate whether to get the result from all sites
     *
     * @return array
     */
    public function getActiveSessions(bool $allSites = false): array
    {
        $where = ['where' => ['status = ?' => 1]];

        if (!$allSites) {
            $where['where'][' AND access_url = ?'] = $this->accessUrl;
        }

        return Database::select(
            '*',
            $this->table,
            $where
        );
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
        return api_get_path(WEB_PLUGIN_PATH).'bbb/start.php?launch=1&'.$this->getUrlParams();
    }

    /**
     * Get the meeting info from DB by its name
     *
     * @param string $name
     *
     * @return array
     */
    public function findMeetingByName($name)
    {
        $meetingData = Database::select(
            '*',
            'plugin_bbb_meeting',
            array('where' => array('meeting_name = ? AND status = 1 ' => $name)),
            'first'
        );

        return $meetingData;
    }

    /**
     * Get the meeting info from DB by its name
     *
     * @param int $id
     *
     * @return array
     */
    public function getMeeting($id)
    {
        $meetingData = Database::select(
            '*',
            'plugin_bbb_meeting',
            array('where' => array('id = ?' => $id)),
            'first'
        );

        return $meetingData;
    }

    /**
     * Get the meeting info.
     *
     * @param int $id
     *
     * @return array
     */
    public function getMeetingByRemoteId($id)
    {
        $meetingData = Database::select(
            '*',
            'plugin_bbb_meeting',
            array('where' => array('remote_id = ?' => $id)),
            'first'
        );

        return $meetingData;
    }

    /**
     * @param int $meetingId
     *
     * @return array
     */
    public function findConnectedMeetingParticipants($meetingId)
    {
        $meetingData = Database::select(
            '*',
            'plugin_bbb_room',
            array('where' => array('meeting_id = ? AND in_at IS NOT NULL' => $meetingId))
        );
        $participantIds = [];
        $return = [];

        foreach ($meetingData as $participantInfo) {
            if (in_array($participantInfo['participant_id'], $participantIds)) {
                continue;
            }

            $participantIds[] = $participantInfo['participant_id'];

            $return[] = [
                'id' => $participantInfo['id'],
                'meeting_id' => $participantInfo['meeting_id'],
                'participant' => api_get_user_entity($participantInfo['participant_id']),
                'in_at' => $participantInfo['in_at'],
                'out_at' => $participantInfo['out_at'],
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
    public function checkDirectMeetingVideoUrl($meetingId)
    {
        $meetingInfo = Database::select(
            '*',
            'plugin_bbb_meeting',
            [
                'where' => ['id = ?' => intval($meetingId)],
            ],
            'first'
        );

        if (!isset($meetingInfo['video_url'])) {
            return false;
        }

        $hasCapture = SocialManager::verifyUrl($meetingInfo['video_url'].'/capture.m4v');

        if ($hasCapture) {
            return Database::update(
                'plugin_bbb_meeting',
                ['has_video_m4v' => true],
                ['id = ?' => intval($meetingId)]
            );
        }

        return $hasCapture;
    }
}
