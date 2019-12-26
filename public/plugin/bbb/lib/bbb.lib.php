<?php

/* For licensing terms, see /license.txt */

/**
 * Class bbb
 * This script initiates a video conference session, calling the BigBlueButton
 * API.
 */
//namespace Chamilo\Plugin\BBB;

/**
 * Class bbb.
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
     * required for the connection to the video conference server).
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
        $this->enableGlobalConference = 'true' === $this->plugin->get('enable_global_conference') ? true : false;
        $this->isGlobalConference = (bool) $isGlobalConference;

        $columns = Database::listTableColumns($this->table);
        $this->groupSupport = isset($columns['group_id']) ? true : false;
        $this->userSupport = isset($columns['user_id']) ? true : false;
        $this->accessUrl = api_get_current_access_url_id();

        $this->enableGlobalConferencePerUser = false;
        if ($this->userSupport && !empty($isGlobalPerUser)) {
            $this->enableGlobalConferencePerUser = 'true' === $this->plugin->get('enable_global_conference_per_user') ? true : false;
            $this->userId = $isGlobalPerUser;
        }

        if ($this->groupSupport) {
            // Plugin check
            $this->groupSupport = 'true' === $this->plugin->get('enable_conference_in_course_groups') ? true : false;
            if ($this->groupSupport) {
                // Platform check
                $bbbSetting = api_get_setting('bbb_enable_conference_in_course_groups');
                $bbbSetting = isset($bbbSetting['bbb']) ? 'true' === $bbbSetting['bbb'] : false;

                if ($bbbSetting) {
                    // Course check
                    $courseInfo = api_get_course_info();
                    if ($courseInfo) {
                        $this->groupSupport = '1' === api_get_course_setting('bbb_enable_conference_in_groups', $courseInfo);
                    }
                }
            }
        }
        $this->maxUsersLimit = $this->plugin->get('max_users_limit');

        if ('true' === $bbbPluginEnabled) {
            $userInfo = api_get_user_info();
            if (empty($userInfo) && !empty($isGlobalPerUser)) {
                // If we are following a link to a global "per user" conference
                // then generate a random guest name to join the conference
                // because there is no part of the process where we give a name
                $this->userCompleteName = 'Guest'.rand(1000, 9999);
            } else {
                $this->userCompleteName = $userInfo['complete_name'];
            }

            $this->salt = $bbb_salt;
            $info = parse_url($bbb_host);
            $this->url = $bbb_host.'/bigbluebutton/';

            if (isset($info['scheme'])) {
                $this->protocol = $info['scheme'].'://';
                $this->url = str_replace($this->protocol, '', $this->url);
                $urlWithProtocol = $bbb_host;
            } else {
                // We asume it's an http, if user wants to use https host must include the protocol.
                $urlWithProtocol = 'http://'.$bbb_host;
            }

            // Setting BBB api
            define('CONFIG_SECURITY_SALT', $this->salt);
            define('CONFIG_SERVER_URL_WITH_PROTOCOL', $urlWithProtocol);
            define('CONFIG_SERVER_BASE_URL', $this->url);

            $this->api = new BigBlueButtonBN();
            $this->pluginEnabled = true;
            $this->logoutUrl = $this->getListingUrl();
        }
    }

    /**
     * Force the course, session and/or group IDs.
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
     * @return bool
     */
    public function isGlobalConferenceEnabled()
    {
        return $this->enableGlobalConference;
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
        if (false === $this->isGlobalConferenceEnabled()) {
            return false;
        }

        return (bool) $this->isGlobalConference;
    }

    /**
     * @return bool
     */
    public function hasGroupSupport()
    {
        return $this->groupSupport;
    }

    /**
     * Checks whether a user is teacher in the current course.
     *
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    public function isConferenceManager()
    {
        if (api_is_coach() || api_is_platform_admin()) {
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

        if (!empty($courseInfo)) {
            return api_is_course_admin();
        }

        return false;
    }

    /**
     * Gets the global limit of users in a video-conference room.
     * This value can be overridden by course-specific values.
     *
     * @return int Maximum number of users set globally
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
                ['variable = ?' => 'plugin_bbb_course_users_limit']
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
                ['variable = ?' => 'plugin_bbb_session_users_limit']
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
     * @param int $max Maximum number of users (globally)
     */
    public function setMaxUsersLimit($max)
    {
        if ($max < 0) {
            $max = 0;
        }
        $this->maxUsersLimit = (int) $max;
    }

    /**
     * See this file in you BBB to set up default values.
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

        $params['record'] = 1 == api_get_course_setting('big_blue_button_record_and_store') ? true : false;
        $max = api_get_course_setting('big_blue_button_max_students_allowed');
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

        // Check interface feature is installed
        $interfaceFeature = $this->plugin->get('interface');
        if (false === $interfaceFeature) {
            if (isset($params['interface'])) {
                unset($params['interface']);
            }
        }

        $id = Database::insert($this->table, $params);

        if ($id) {
            $meetingName = isset($params['meeting_name']) ? $params['meeting_name'] : $this->getCurrentVideoConferenceName();
            $welcomeMessage = isset($params['welcome_msg']) ? $params['welcome_msg'] : null;
            $record = isset($params['record']) && $params['record'] ? 'true' : 'false';
            //$duration = isset($params['duration']) ? intval($params['duration']) : 0;
            // This setting currently limits the maximum conference duration,
            // to avoid lingering sessions on the video-conference server #6261
            $duration = 300;
            $bbbParams = [
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
                //'meta_category' => '',  // Use to pass additional info to BBB server. See API docs.
            ];

            $status = false;
            $meeting = null;
            while (false === $status) {
                $result = $this->api->createMeetingWithXmlResponseArray($bbbParams);
                if (isset($result) && 'SUCCESS' == (string) ($result['returncode'])) {
                    if ('true' === $this->plugin->get('allow_regenerate_recording')) {
                        $internalId = Database::escape_string($result['internalMeetingID']);
                        $sql = "UPDATE $this->table SET internal_meeting_id = '".$internalId."' 
                                WHERE id = $id";
                        Database::query($sql);
                    }

                    return $this->joinMeeting($meetingName, true);
                }
            }
        }

        return false;
    }

    /**
     * Save a participant in a meeting room.
     *
     * @param int $meetingId
     * @param int $participantId
     * @param int $interface
     *
     * @return false|int The last inserted ID. Otherwise return false
     */
    public function saveParticipant($meetingId, $participantId, $interface = 0)
    {
        $params = [
            'meeting_id' => $meetingId,
            'participant_id' => $participantId,
            'in_at' => api_get_utc_datetime(),
            'out_at' => api_get_utc_datetime(),
        ];

        if (false !== $this->plugin->get('interface')) {
            $params['interface'] = $interface;
        }

        return Database::insert(
            'plugin_bbb_room',
            $params
        );
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
        $conditions = [
            'where' => [
                'c_id = ? AND session_id = ? AND meeting_name = ? AND status = 1 AND access_url = ?' => [$courseId, $sessionId, $meetingName, $this->accessUrl],
            ],
        ];

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $conditions = [
                'where' => [
                    'c_id = ? AND session_id = ? AND meeting_name = ? AND group_id = ? AND status = 1 AND access_url = ?' => [
                            $courseId,
                            $sessionId,
                            $meetingName,
                            $groupId,
                            $this->accessUrl,
                        ],
                ],
            ];
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
     * Tells whether the given meeting exists and is running
     * (using course code as name).
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
     * Returns a meeting "join" URL.
     *
     * @param string $meetingName The name of the meeting (usually the course code)
     *
     * @return mixed The URL to join the meeting, or false on error
     *
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
            [
                'where' => [
                    'meeting_name = ? AND status = 1 AND access_url = ?' => [
                        $meetingName,
                        $this->accessUrl,
                    ],
                ],
            ],
            'first'
        );

        if (empty($meetingData) || !is_array($meetingData)) {
            if ($this->debug) {
                error_log("meeting does not exist: $meetingName");
            }

            return false;
        }

        $params = [
            'meetingId' => $meetingData['remote_id'],
            //  -- REQUIRED - The unique id for the meeting
            'password' => $this->getModMeetingPassword(),
            //  -- REQUIRED - The moderator password for the meeting
        ];

        $meetingInfoExists = false;
        $meetingIsRunningInfo = $this->getMeetingInfo($params);
        if ($this->debug) {
            error_log('Searching meeting with params:');
            error_log(print_r($params, 1));
            error_log('Result:');
            error_log(print_r($meetingIsRunningInfo, 1));
        }

        if (false === $meetingIsRunningInfo) {
            // checking with the remote_id didn't work, so just in case and
            // to provide backwards support, check with the id
            $params = [
                'meetingId' => $meetingData['id'],
                //  -- REQUIRED - The unique id for the meeting
                'password' => $this->getModMeetingPassword(),
                //  -- REQUIRED - The moderator password for the meeting
            ];
            $meetingIsRunningInfo = $this->getMeetingInfo($params);
            if ($this->debug) {
                error_log('Searching meetingId with params:');
                error_log(print_r($params, 1));
                error_log('Result:');
                error_log(print_r($meetingIsRunningInfo, 1));
            }
        }

        if ('SUCCESS' === (string) ($meetingIsRunningInfo['returncode']) &&
            isset($meetingIsRunningInfo['meetingName']) &&
            !empty($meetingIsRunningInfo['meetingName'])
        ) {
            $meetingInfoExists = true;
        }

        if ($this->debug) {
            error_log(
                'meeting is running: '.(int) $meetingInfoExists
            );
        }

        $url = false;
        if ($meetingInfoExists) {
            $joinParams = [
                'meetingId' => $meetingData['remote_id'], //	-- REQUIRED - A unique id for the meeting
                'username' => $this->userCompleteName, //-- REQUIRED - The name that will display for the user in the meeting
                'password' => $pass, //-- REQUIRED - The attendee or moderator password, depending on what's passed here
                //'createTime' => api_get_utc_datetime(),			//-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                'userID' => api_get_user_id(), //-- OPTIONAL - string
                'webVoiceConf' => '', //	-- OPTIONAL - string
                'interface' => $this->checkInterface($meetingData),
            ];
            $url = $this->api->getJoinMeetingURL($joinParams);
            $url = $this->protocol.$url;
        }

        if ($this->debug) {
            error_log('return url :'.$url);
        }

        return $url;
    }

    /**
     * Get information about the given meeting.
     *
     * @param array $params ...?
     *
     * @return mixed Array of information on success, false on error
     * @assert (array()) === false
     */
    public function getMeetingInfo($params)
    {
        try {
            $result = $this->api->getMeetingInfoWithXmlResponseArray($params);
            if (null == $result) {
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
     * @param int $courseId
     * @param int $sessionId
     * @param int $status
     *
     * @return array
     */
    public function getAllMeetingsInCourse($courseId, $sessionId, $status)
    {
        $conditions = [
            'where' => [
                'status = ? AND c_id = ? AND session_id = ? ' => [
                    $status,
                    $courseId,
                    $sessionId,
                ],
            ],
        ];

        return Database::select(
            '*',
            $this->table,
            $conditions
        );
    }

    /**
     * Gets all the course meetings saved in the plugin_bbb_meeting table.
     *
     * @param int   $courseId
     * @param int   $sessionId
     * @param int   $groupId
     * @param bool  $isAdminReport Optional. Set to true then the report is for admins
     * @param array $dateRange     Optional
     *
     * @return array Array of current open meeting rooms
     */
    public function getMeetings(
        $courseId = 0,
        $sessionId = 0,
        $groupId = 0,
        $isAdminReport = false,
        $dateRange = []
    ) {
        $em = Database::getManager();
        $manager = $this->isConferenceManager();

        $conditions = [];
        if ($courseId || $sessionId || $groupId) {
            $conditions = [
                'where' => [
                    'c_id = ? AND session_id = ? ' => [$courseId, $sessionId],
                ],
            ];

            if ($this->hasGroupSupport()) {
                $conditions = [
                    'where' => [
                        'c_id = ? AND session_id = ? AND group_id = ? ' => [
                            $courseId,
                            $sessionId,
                            $groupId,
                        ],
                    ],
                ];
            }

            if ($this->isGlobalConferencePerUserEnabled()) {
                $conditions = [
                     'where' => [
                         'c_id = ? AND session_id = ? AND user_id = ?' => [
                             $courseId,
                             $sessionId,
                             $this->userId,
                         ],
                     ],
                 ];
            }
        }

        if (!empty($dateRange)) {
            $dateStart = date_create($dateRange['search_meeting_start']);
            $dateStart = date_format($dateStart, 'Y-m-d H:i:s');
            $dateEnd = date_create($dateRange['search_meeting_end']);
            $dateEnd = $dateEnd->add(new DateInterval('P1D'));
            $dateEnd = date_format($dateEnd, 'Y-m-d H:i:s');

            $conditions = [
                'where' => [
                    'created_at BETWEEN ? AND ? ' => [$dateStart, $dateEnd],
                ],
            ];
        }

        $conditions['order'] = 'created_at ASC';

        $meetingList = Database::select(
            '*',
            $this->table,
            $conditions
        );
        $isGlobal = $this->isGlobalConference();
        $newMeetingList = [];
        foreach ($meetingList as $meetingDB) {
            $item = [];
            $courseId = $meetingDB['c_id'];
            $courseInfo = api_get_course_info_by_id($courseId);
            $courseCode = '';
            if (!empty($courseInfo)) {
                $courseCode = $courseInfo['code'];
            }

            if ($manager) {
                $pass = $this->getUserMeetingPassword($courseCode);
            } else {
                $pass = $this->getModMeetingPassword($courseCode);
            }

            $meetingBBB = $this->getMeetingInfo(
                [
                    'meetingId' => $meetingDB['remote_id'],
                    'password' => $pass,
                ]
            );

            if (false === $meetingBBB) {
                // Checking with the remote_id didn't work, so just in case and
                // to provide backwards support, check with the id
                $params = [
                    'meetingId' => $meetingDB['id'],
                    //  -- REQUIRED - The unique id for the meeting
                    'password' => $pass,
                    //  -- REQUIRED - The moderator password for the meeting
                ];
                $meetingBBB = $this->getMeetingInfo($params);
            }

            if (0 == $meetingDB['visibility'] && false === $this->isConferenceManager()) {
                continue;
            }

            $meetingBBB['end_url'] = $this->endUrl($meetingDB);

            if (isset($meetingBBB['returncode']) && 'FAILED' === (string) $meetingBBB['returncode']) {
                if (1 == $meetingDB['status'] && $this->isConferenceManager()) {
                    $this->endMeeting($meetingDB['id'], $courseCode);
                }
            } else {
                $meetingBBB['add_to_calendar_url'] = $this->addToCalendarUrl($meetingDB);
            }

            if (1 == $meetingDB['record']) {
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
                    if (!isset($records['messageKey']) || 'noRecordings' !== $records['messageKey']) {
                        $record = end($records);
                        if (!is_array($record) || !isset($record['recordId'])) {
                            continue;
                        }

                        if (!empty($record['playbackFormatUrl'])) {
                            $this->updateMeetingVideoUrl($meetingDB['id'], $record['playbackFormatUrl']);
                        }

                        /*if (!$this->isConferenceManager()) {
                            $record = [];
                        }*/
                    }
                }

                if (isset($record['playbackFormatUrl']) && !empty($record['playbackFormatUrl'])) {
                    $recordLink = Display::url(
                        $this->plugin->get_lang('ViewRecord'),
                        $record['playbackFormatUrl'],
                        ['target' => '_blank', 'class' => 'btn btn-default']
                    );
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
            } else {
                $actionLinks = $this->getActionLinks(
                    $meetingDB,
                    [],
                    $isGlobal,
                    $isAdminReport
                );

                $item['show_links'] = $this->plugin->get_lang('NoRecording');
            }

            $item['action_links'] = implode(PHP_EOL, $actionLinks);
            $item['created_at'] = api_convert_and_format_date($meetingDB['created_at']);
            // created_at
            $meetingDB['created_at'] = $item['created_at']; //avoid overwrite in array_merge() below

            $item['publish_url'] = $this->publishUrl($meetingDB);
            $item['unpublish_url'] = $this->unPublishUrl($meetingBBB);

            if (1 == $meetingDB['status']) {
                $joinParams = [
                    'meetingId' => $meetingDB['remote_id'], //-- REQUIRED - A unique id for the meeting
                    'username' => $this->userCompleteName, //-- REQUIRED - The name that will display for the user in the meeting
                    'password' => $pass, //-- REQUIRED - The attendee or moderator password, depending on what's passed here
                    'createTime' => '', //-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                    'userID' => '', //	-- OPTIONAL - string
                    'webVoiceConf' => '', //	-- OPTIONAL - string
                    'interface' => $this->checkInterface($meetingDB),
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
     * @param $meetingInfo
     *
     * @return int
     */
    public function checkInterface($meetingInfo)
    {
        $interface = BBBPlugin::LAUNCH_TYPE_DEFAULT;

        $type = $this->plugin->get('launch_type');
        switch ($type) {
            case BBBPlugin::LAUNCH_TYPE_DEFAULT:
                $interface = $this->plugin->get('interface');

                break;
            case BBBPlugin::LAUNCH_TYPE_SET_BY_TEACHER:
                if (isset($meetingInfo['interface'])) {
                    $interface = $meetingInfo['interface'];
                }

                break;
            case BBBPlugin::LAUNCH_TYPE_SET_BY_STUDENT:
                if (isset($meetingInfo['id'])) {
                    $roomInfo = $this->getMeetingParticipantInfo($meetingInfo['id'], api_get_user_id());
                    if (!empty($roomInfo) && isset($roomInfo['interface'])) {
                        $interface = $roomInfo['interface'];
                    } else {
                        if (isset($_REQUEST['interface'])) {
                            $interface = isset($_REQUEST['interface']) ? (int) $_REQUEST['interface'] : 0;
                        }
                    }
                }

                break;
        }

        return $interface;
    }

    /**
     * Function disabled.
     */
    public function publishMeeting($id)
    {
        //return BigBlueButtonBN::setPublishRecordings($id, 'true', $this->url, $this->salt);
        if (empty($id)) {
            return false;
        }
        $id = (int) $id;
        Database::update($this->table, ['visibility' => 1], ['id = ? ' => $id]);

        return true;
    }

    /**
     * Function disabled.
     */
    public function unpublishMeeting($id)
    {
        //return BigBlueButtonBN::setPublishRecordings($id, 'false', $this->url, $this->salt);
        if (empty($id)) {
            return false;
        }
        $id = (int) $id;
        Database::update($this->table, ['visibility' => 0], ['id = ?' => $id]);

        return true;
    }

    /**
     * Closes a meeting (usually when the user click on the close button from
     * the conferences listing.
     *
     * @param string $id         The internal ID of the meeting (id field for this meeting)
     * @param string $courseCode
     *
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
            ['where' => ['id = ?' => [$id]]],
            'first'
        );
        $manager = $this->isConferenceManager();
        if ($manager) {
            $pass = $this->getUserMeetingPassword($courseCode);
        } else {
            $pass = $this->getModMeetingPassword($courseCode);
        }

        $endParams = [
            'meetingId' => $meetingData['remote_id'], // REQUIRED - We have to know which meeting to end.
            'password' => $pass, // REQUIRED - Must match moderator pass for meeting.
        ];
        $this->api->endMeetingWithXmlResponseArray($endParams);
        Database::update(
            $this->table,
            ['status' => 0, 'closed_at' => api_get_utc_datetime()],
            ['id = ? ' => $id]
        );
    }

    /**
     * Gets the password for a specific meeting for the current user.
     *
     * @param string $courseCode
     *
     * @return string A moderator password if user is teacher, or the course code otherwise
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
     * Get users online in the current course room.
     *
     * @return int The number of users currently connected to the videoconference
     * @assert () > -1
     */
    public function getUsersOnlineInCurrentRoom()
    {
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $conditions = [
            'where' => [
                'c_id = ? AND session_id = ? AND status = 1 AND access_url = ?' => [
                    $courseId,
                    $sessionId,
                    $this->accessUrl,
                ],
            ],
        ];

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $conditions = [
                'where' => [
                    'c_id = ? AND session_id = ? AND group_id = ? AND status = 1 AND access_url = ?' => [
                        $courseId,
                        $sessionId,
                        $groupId,
                        $this->accessUrl,
                    ],
                ],
            ];
        }

        if ($this->isGlobalConferencePerUserEnabled()) {
            $conditions = [
                'where' => [
                    'user_id = ? AND status = 1 AND access_url = ?' => [
                        $this->userId,
                        $this->accessUrl,
                    ],
                ],
            ];
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
        $pass = $this->getModMeetingPassword();
        $info = $this->getMeetingInfo(['meetingId' => $meetingData['remote_id'], 'password' => $pass]);
        if (false === $info) {
            //checking with the remote_id didn't work, so just in case and
            // to provide backwards support, check with the id
            $params = [
                'meetingId' => $meetingData['id'],
                //  -- REQUIRED - The unique id for the meeting
                'password' => $pass,
                //  -- REQUIRED - The moderator password for the meeting
            ];
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
        if ('true' !== $this->plugin->get('allow_regenerate_recording')) {
            return false;
        }

        if (empty($id)) {
            return false;
        }

        $meetingData = Database::select(
            '*',
            $this->table,
            ['where' => ['id = ?' => [$id]]],
            'first'
        );

        // Check if there are recordings for this meeting
        $recordings = $this->api->getRecordings(['meetingId' => $meetingData['remote_id']]);
        if (!empty($recordings) && isset($recordings['messageKey']) && 'noRecordings' === $recordings['messageKey']) {
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
     * Deletes a recording of a meeting.
     *
     * @param int $id ID of the recording
     *
     * @return bool
     *
     * @assert () === false
     *
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
            ['where' => ['id = ?' => [$id]]],
            'first'
        );

        $delete = false;
        // Check if there are recordings for this meeting
        $recordings = $this->api->getRecordings(['meetingId' => $meetingData['remote_id']]);
        if (!empty($recordings) && isset($recordings['messageKey']) && 'noRecordings' == $recordings['messageKey']) {
            $delete = true;
        } else {
            $recordsToDelete = [];
            if (!empty($recordings['records'])) {
                foreach ($recordings['records'] as $record) {
                    $recordsToDelete[] = $record['recordId'];
                }
                $recordingParams = ['recordId' => implode(',', $recordsToDelete)];
                $result = $this->api->deleteRecordingsWithXmlResponseArray($recordingParams);
                if (!empty($result) && isset($result['deleted']) && 'true' === $result['deleted']) {
                    $delete = true;
                }
            }
        }

        if ($delete) {
            Database::delete(
                'plugin_bbb_room',
                ['meeting_id = ?' => [$id]]
            );

            Database::delete(
                $this->table,
                ['id = ?' => [$id]]
            );
        }

        return $delete;
    }

    /**
     * Creates a link in the links tool from the given videoconference recording.
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
            ['where' => ['id = ?' => [$id]]],
            'first'
        );

        $records = $this->api->getRecordingsWithXmlResponseArray(
            ['meetingId' => $meetingData['remote_id']]
        );

        if (!empty($records)) {
            if (isset($records['message']) && !empty($records['message'])) {
                if ('noRecordings' == $records['messageKey']) {
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

                    return $link->save($params);
                }
            }
        }

        return false;
    }

    /**
     * Checks if the video conference server is running.
     * Function currently disabled (always returns 1).
     *
     * @return bool True if server is running, false otherwise
     * @assert () === false
     */
    public function isServerRunning()
    {
        $host = $this->plugin->get('host');

        if (empty($host)) {
            return false;
        }

        return true;
        //return BigBlueButtonBN::isServerRunning($this->protocol.$this->url);
    }

    /**
     * Get active session in the all platform.
     */
    public function getActiveSessionsCount()
    {
        $meetingList = Database::select(
            'count(id) as count',
            $this->table,
            ['where' => ['status = ? AND access_url = ?' => [1, $this->accessUrl]]],
            'first'
        );

        return $meetingList['count'];
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
    public function getUrlParams()
    {
        if (empty($this->courseCode)) {
            if ($this->isGlobalConferencePerUserEnabled()) {
                return 'global=1&user_id='.$this->userId;
            }

            if ($this->isGlobalConference()) {
                return 'global=1';
            }

            return '';
        }

        return http_build_query([
            'cidReq' => $this->courseCode,
            'id_session' => $this->sessionId,
            'gidReq' => $this->groupId,
        ]);
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
     * @return string
     */
    public function getConferenceUrl()
    {
        return api_get_path(WEB_PLUGIN_PATH).'bbb/start.php?launch=1&'.$this->getUrlParams();
    }

    /**
     * @return string
     */
    public function getListingUrl()
    {
        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams();
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
     * @param array $meeting
     * @param array $record
     *
     * @return string
     */
    public function addToCalendarUrl($meeting, $record = [])
    {
        $url = isset($record['playbackFormatUrl']) ? $record['playbackFormatUrl'] : '';

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=add_to_calendar&id='.$meeting['id'].'&start='.api_strtotime($meeting['created_at']).'&url='.$url;
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=publish&id='.$meeting['id'];
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=unpublish&id='.$meeting['id'];
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

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=delete_record&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     * @param array $recordInfo
     *
     * @return string
     */
    public function regenerateRecordUrl($meeting, $recordInfo)
    {
        if ('true' !== $this->plugin->get('allow_regenerate_recording')) {
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
        if ('true' !== $this->plugin->get('allow_regenerate_recording')) {
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
    public function copyToRecordToLinkTool($meeting)
    {
        if (!isset($meeting['id'])) {
            return '';
        }

        return api_get_path(WEB_PLUGIN_PATH).
            'bbb/listing.php?'.$this->getUrlParams().'&action=copy_record_to_link_tool&id='.$meeting['id'];
    }

    /**
     * Get the meeting info from DB by its name.
     *
     * @param string $name
     *
     * @return array
     */
    public function findMeetingByName($name)
    {
        return Database::select(
            '*',
            'plugin_bbb_meeting',
            ['where' => ['meeting_name = ? AND status = 1 ' => $name]],
            'first'
        );
    }

    /**
     * Get the meeting info from DB by its name.
     *
     * @param int $id
     *
     * @return array
     */
    public function getMeeting($id)
    {
        return Database::select(
            '*',
            'plugin_bbb_meeting',
            ['where' => ['id = ?' => $id]],
            'first'
        );
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
        return Database::select(
            '*',
            'plugin_bbb_meeting',
            ['where' => ['remote_id = ?' => $id]],
            'first'
        );
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
            ['where' => ['meeting_id = ? AND in_at IS NOT NULL' => $meetingId]]
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
            ['where' => ['meeting_id = ? AND participant_id = ?' => [$meetingId, $userId]]],
            'first'
        );

        if ($meetingData) {
            return $meetingData;
        }

        return [];
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
        $isVisible = 0 != $meetingInfo['visibility'];
        $linkVisibility = $isVisible
            ? Display::url(
                Display::return_icon('visible.png', get_lang('Make invisible')),
                $this->unPublishUrl($meetingInfo)
            )
            : Display::url(
                Display::return_icon('invisible.png', get_lang('Make Visible')),
                $this->publishUrl($meetingInfo)
            );

        $links = [];
        if ('true' === $this->plugin->get('allow_regenerate_recording') && 1 == $meetingInfo['record']) {
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
                if (0 == $meetingInfo['status']) {
                    $links[] = Display::url(
                        Display::return_icon('delete.png', get_lang('Delete')),
                        $this->deleteRecordUrl($meetingInfo)
                    );
                    $links[] = $linkVisibility;
                }

                return $links;
            } else {
                $links[] = Display::url(
                    Display::return_icon('course_home.png', get_lang('Go to the course')),
                    $this->getListingUrl()
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
                Display::return_icon('agenda.png', get_lang('Add to calendar')),
                $this->addToCalendarUrl($meetingInfo, $recordInfo)
            );
        }

        $hide = 'true' === $this->plugin->get('disable_download_conference_link') ? true : false;

        if (false == $hide) {
            if ($meetingInfo['has_video_m4v']) {
                $links[] = Display::url(
                    Display::return_icon('save.png', get_lang('Download file')),
                    $recordInfo['playbackFormatUrl'].'/capture.m4v',
                    ['target' => '_blank']
                );
            } else {
                $links[] = Display::url(
                    Display::return_icon('save.png', get_lang('Download file')),
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
                Display::return_icon('course_home.png', get_lang('Go to the course')),
                $this->getListingUrl()
            );
        }

        return $links;
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
            ['id = ?' => (int) $meetingId]
        );
    }

    /**
     * Check if the meeting has a capture.m4v video file. If exists then the has_video_m4v field is updated.
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
                'where' => ['id = ?' => (int) $meetingId],
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
                ['id = ?' => (int) $meetingId]
            );
        }

        return $hasCapture;
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
        if ('true' === $setting && 'true' === $settingLink) {
            //$content = Display::url(get_lang('Launch videoconference room'), $url);
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
}
