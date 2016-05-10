<?php
/* For licensing terms, see /license.txt */

/**
 * Class bbb
 * This script initiates a video conference session, calling the BigBlueButton
 * API
 * @package chamilo.plugin.bigbluebutton
 *
 * BigBlueButton-Chamilo connector class
 */
//namespace Chamilo\Plugin\BBB;

/**
 * Class bbb
 * @package Chamilo\Plugin\BBB
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
    public $isGlobalConference = false;
    public $groupSupport = false;

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the video conference server)
     * @param string $host
     * @param string $salt
     * @param bool $isGlobalConference
     */
    public function __construct($host = '', $salt = '', $isGlobalConference = false)
    {
        // Initialize video server settings from global settings
        $plugin = BBBPlugin::create();

        $bbbPlugin = $plugin->get('tool_enable');

        $bbb_host = !empty($host) ? $host : $plugin->get('host');
        $bbb_salt = !empty($salt) ? $salt : $plugin->get('salt');

        $this->logoutUrl = $this->getListingUrl();
        $this->table = Database::get_main_table('plugin_bbb_meeting');
        $this->enableGlobalConference = $plugin->get('enable_global_conference');
        $this->isGlobalConference = (bool) $isGlobalConference;

        $columns = Database::listTableColumns($this->table);
        $this->groupSupport = isset($columns['group_id']) ? true : false;

        if ($this->groupSupport) {
            $this->groupSupport = (bool) $plugin->get('enable_conference_in_course_groups');
            if ($this->groupSupport) {
                $courseInfo = api_get_course_info();
                if ($courseInfo) {
                    $this->groupSupport = api_get_course_setting('bbb_enable_conference_in_groups') === '1';
                }
            }
        }

        if ($bbbPlugin === true) {
            $userInfo = api_get_user_info();
            $this->userCompleteName = $userInfo['complete_name'];
            $this->salt = $bbb_salt;
            $info = parse_url($bbb_host);
            $this->url = $bbb_host.'/bigbluebutton/';
            if (isset($info['scheme'])) {
                $this->protocol = $info['scheme'].'://';
                $this->url = str_replace($this->protocol, '', $this->url);
            }

            // Setting BBB api
            define('CONFIG_SECURITY_SALT', $this->salt);
            define('CONFIG_SERVER_BASE_URL', $this->url);

            $this->api = new BigBlueButtonBN();
            $this->pluginEnabled = true;
        }
    }

    /**
     * @return bool
     */
    public function isGlobalConferenceEnabled()
    {
        return (bool) $this->enableGlobalConference;
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
    public function hasGroupSupport()
    {
        return $this->groupSupport;
    }

    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    public function isConferenceManager()
    {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }

    /**
     * See this file in you BBB to set up default values
     * @param   array $params Array of parameters that will be completed if not containing all expected variables

       /var/lib/tomcat6/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties
     *
       More record information:
       http://code.google.com/p/bigbluebutton/wiki/RecordPlaybackSpecification

       # Default maximum number of users a meeting can have.
        # Doesn't get enforced yet but is the default value when the create
        # API doesn't pass a value.
        defaultMaxUsers=20

        # Default duration of the meeting in minutes.
        # Current default is 0 (meeting doesn't end).
        defaultMeetingDuration=0

        # Remove the meeting from memory when the end API is called.
        # This allows 3rd-party apps to recycle the meeting right-away
        # instead of waiting for the meeting to expire (see below).
        removeMeetingWhenEnded=false

        # The number of minutes before the system removes the meeting from memory.
        defaultMeetingExpireDuration=1

        # The number of minutes the system waits when a meeting is created and when
        # a user joins. If after this period, a user hasn't joined, the meeting is
        # removed from memory.
        defaultMeetingCreateJoinDuration=5
     * 
     * @return mixed
     */
    public function createMeeting($params)
    {
        $courseCode = api_get_course_id();
        $params['c_id'] = api_get_course_int_id();
        $params['session_id'] = api_get_session_id();

        if ($this->hasGroupSupport()) {
            $params['group_id'] = api_get_group_id();
        }

        $params['attendee_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : $courseCode;
        $attendeePassword =  $params['attendee_pw'];
        $params['moderator_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : $this->getModMeetingPassword();
        $moderatorPassword = $params['moderator_pw'];

        $params['record'] = api_get_course_setting('big_blue_button_record_and_store', $courseCode) == 1 ? true : false;
        $max = api_get_course_setting('big_blue_button_max_students_allowed', $courseCode);
        $max =  isset($max) ? $max : -1;

        $params['status'] = 1;
        // Generate a pseudo-global-unique-id to avoid clash of conferences on
        // the same BBB server with several Chamilo portals
        $params['remote_id'] = uniqid(true, true);
        // Each simultaneous conference room needs to have a different
        // voice_bridge composed of a 5 digits number, so generating a random one
        $params['voice_bridge'] = rand(10000, 99999);

        if ($this->debug) {
            error_log("enter create_meeting ".print_r($params, 1));
        }

        $params['created_at'] = api_get_utc_datetime();
        $id = Database::insert($this->table, $params);

        if ($id) {
            if ($this->debug) {
                error_log("create_meeting: $id ");
            }

            $meetingName = isset($params['meeting_name']) ? $params['meeting_name'] : $this->getCurrentVideoConferenceName();
            $welcomeMessage = isset($params['welcome_msg']) ? $params['welcome_msg'] : null;
            $record = isset($params['record']) && $params['record'] ? 'true' : 'false';
            $duration = isset($params['duration']) ? intval($params['duration']) : 0;
            // This setting currently limits the maximum conference duration,
            // to avoid lingering sessions on the video-conference server #6261
            $duration = 300;

            $bbbParams = array(
                'meetingId' => $params['remote_id'], 					// REQUIRED
                'meetingName' => $meetingName, 	// REQUIRED
                'attendeePw' => $attendeePassword, 					// Match this value in getJoinMeetingURL() to join as attendee.
                'moderatorPw' => $moderatorPassword, 					// Match this value in getJoinMeetingURL() to join as moderator.
                'welcomeMsg' => $welcomeMessage, 					// ''= use default. Change to customize.
                'dialNumber' => '', 					// The main number to call into. Optional.
                'voiceBridge' => $params['voice_bridge'], 					// PIN to join voice. Required.
                'webVoice' => '', 						// Alphanumeric to join voice. Optional.
                'logoutUrl' =>  $this->logoutUrl,
                'maxParticipants' => $max, 				// Optional. -1 = unlimitted. Not supported in BBB. [number]
                'record' => $record, 					// New. 'true' will tell BBB to record the meeting.
                'duration' => $duration, 				// Default = 0 which means no set duration in minutes. [number]
                //'meta_category' => '', 				// Use to pass additional info to BBB server. See API docs.
            );

            if ($this->debug) {
                error_log("create_meeting params: ".print_r($bbbParams,1));
            }

            $status = false;
            $meeting = null;

            while ($status === false) {
                $result = $this->api->createMeetingWithXmlResponseArray(
                    $bbbParams
                );
                if (isset($result) && strval($result['returncode']) == 'SUCCESS') {
                    if ($this->debug) {
                        error_log(
                            "create_meeting result: " . print_r($result, 1)
                        );
                    }
                    $meeting = $this->joinMeeting($meetingName, true);

                    return $meeting;
                }
            }

            return $this->logoutUrl;
        }
    }

    /**
     * Tells whether the given meeting exists and is running
     * (using course code as name)
     * @param string $meetingName Meeting name (usually the course code)
     *
     * @return bool True if meeting exists, false otherwise
     * @assert ('') === false
     * @assert ('abcdefghijklmnopqrstuvwxyzabcdefghijklmno') === false
     */
    public function meetingExists($meetingName)
    {
        if (empty($meetingName)) {

            return false;
        }

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $conditions =  array(
            'where' => array(
                'c_id = ? AND session_id = ? AND meeting_name = ? AND status = 1 ' =>
                    array($courseId, $sessionId, $meetingName)
            )
        );

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $conditions =  array(
                'where' => array(
                    'c_id = ? AND session_id = ? AND meeting_name = ? AND group_id = ? AND status = 1 ' =>
                        array($courseId, $sessionId, $meetingName, $groupId)
                )
            );
        }

        $meetingData = Database::select(
            '*',
            $this->table,
            $conditions,
            'first'
        );


        if ($this->debug) {
            error_log("meeting_exists ".print_r($meetingData, 1));
        }

        if (empty($meetingData)) {

            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns a meeting "join" URL
     * @param string The name of the meeting (usually the course code)
     * @return mixed The URL to join the meeting, or false on error
     * @todo implement moderator pass
     * @assert ('') === false
     * @assert ('abcdefghijklmnopqrstuvwxyzabcdefghijklmno') === false
     */
    public function joinMeeting($meetingName, $loop = false)
    {
        if (empty($meetingName)) {
            return false;
        }

        $pass = $this->getUserMeetingPassword();

        $meetingData = Database::select(
            '*',
            $this->table,
            array('where' => array('meeting_name = ? AND status = 1 ' => $meetingName)),
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

        $status = false;
        $meetingInfoExists = false;
        while ($status === false) {

            $meetingIsRunningInfo = $this->getMeetingInfo($params);
            if ($meetingIsRunningInfo === false) {
                //checking with the remote_id didn't work, so just in case and
                // to provide backwards support, check with the id
                $params = array(
                    'meetingId' => $meetingData['id'],
                    //  -- REQUIRED - The unique id for the meeting
                    'password' => $this->getModMeetingPassword()
                    //  -- REQUIRED - The moderator password for the meeting
                );
                $meetingIsRunningInfo = $this->getMeetingInfo($params);
            }

            if ($this->debug) {
                error_log(print_r($meetingIsRunningInfo, 1));
            }

            if (strval($meetingIsRunningInfo['returncode']) == 'SUCCESS' &&
                isset($meetingIsRunningInfo['meetingName']) &&
                !empty($meetingIsRunningInfo['meetingName'])
                //strval($meetingIsRunningInfo['running']) == 'true'
            ) {
                $meetingInfoExists = true;
            }

            if ($this->debug) {
                error_log(
                    "meeting is running: " . intval($meetingInfoExists)
                );
            }

            if ($meetingInfoExists) {
                $status = true;
            }

            if ($loop) {
                continue;
            } else {
                break;
            }
        }

        if ($meetingInfoExists) {
            $joinParams = array(
                'meetingId' => $meetingData['remote_id'],	//	-- REQUIRED - A unique id for the meeting
                'username' => $this->userCompleteName,	//-- REQUIRED - The name that will display for the user in the meeting
                'password' => $pass,			//-- REQUIRED - The attendee or moderator password, depending on what's passed here
                //'createTime' => api_get_utc_datetime(),			//-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                'userID' => api_get_user_id(),				//-- OPTIONAL - string
                'webVoiceConf' => ''	//	-- OPTIONAL - string
            );
            $url = $this->api->getJoinMeetingURL($joinParams);
            $url = $this->protocol.$url;
        } else {
            $url = $this->logoutUrl;
        }
        if ($this->debug) {
            error_log("return url :" . $url);
        }

        return $url;
    }

    /**
     * Get information about the given meeting
     * @param array ...?
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
            } else {
                return $result;
            }
        } catch (Exception $e) {
            if ($this->debug) {
                error_log('Caught exception: ', $e->getMessage(), "\n");
            }
        }

        return false;
    }

    /**
     * Gets all the course meetings saved in the plugin_bbb_meeting table
     * @return array Array of current open meeting rooms
     */
    public function getMeetings()
    {
        $pass = $this->getUserMeetingPassword();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $conditions =  array(
            'where' => array(
                'c_id = ? AND session_id = ? ' => array(
                    $courseId,
                    $sessionId,
                ),
            ),
        );

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $conditions =  array(
                'where' => array(
                    'c_id = ? AND session_id = ? AND group_id = ? ' =>
                        array($courseId, $sessionId, $groupId)
                )
            );
        }

        $meetingList = Database::select(
            '*',
            $this->table,
            $conditions
        );
        $isGlobal = $this->isGlobalConference();
        $newMeetingList = array();
        $item = array();
        foreach ($meetingList as $meetingDB) {
            $meetingBBB = $this->getMeetingInfo(['meetingId' => $meetingDB['remote_id'], 'password' => $pass]);
            if ($meetingBBB === false) {
                //checking with the remote_id didn't work, so just in case and
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

            if ((string)$meetingBBB['returncode'] == 'FAILED') {
                if ($meetingDB['status'] == 1 && $this->isConferenceManager()) {
                    $this->endMeeting($meetingDB['id']);
                }
            } else {
                $meetingBBB['add_to_calendar_url'] = $this->addToCalendarUrl($meetingDB);
            }

            $recordArray = array();
            $actionLinksArray = array();

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
                $recordingParams = array(
                    'meetingId' => $mId, //-- OPTIONAL - comma separate if multiple ids
                );

                //To see the recording list in your BBB server do: bbb-record --list
                $records = $this->api->getRecordingsWithXmlResponseArray($recordingParams);
                if (!empty($records)) {
                    $count = 1;
                    if (isset($records['message']) && !empty($records['message'])) {
                        if ($records['messageKey'] == 'noRecordings') {
                            $recordArray[] = get_lang('NoRecording');
                            if ($meetingDB['visibility'] == 0) {
                                $actionLinksArray[] = Display::url(
                                    Display::return_icon(
                                        'invisible.png',
                                        get_lang('MakeVisible'),
                                        array(),
                                        ICON_SIZE_MEDIUM
                                    ),
                                    $this->publishUrl($meetingDB)
                                );
                            } else {
                                $actionLinksArray[] = Display::url(
                                    Display::return_icon(
                                        'visible.png',
                                        get_lang('MakeInvisible'),
                                        array(),
                                        ICON_SIZE_MEDIUM
                                    ),
                                    $this->unPublishUrl($meetingDB)
                                );
                            }
                        }
                    } else {
                        foreach ($records as $record) {
                            //if you get several recordings here and you used a
                            // previous version of Chamilo, you might want to
                            // only keep the last result for each chamilo conf
                            // (see show_links after the end of this loop)
                            if (is_array($record) && isset($record['recordId'])) {
                                $url = Display::url(
                                    get_lang('ViewRecord')." [~".$record['playbackFormatLength']."']",
                                    $record['playbackFormatUrl'],
                                    array('target' => '_blank')
                                );
                                $actionLinks = '';
                                if ($this->isConferenceManager()) {
                                    if ($isGlobal === false) {
                                        $actionLinks .= Display::url(
                                            Display::return_icon(
                                                'link.gif',
                                                get_lang('CopyToLinkTool')
                                            ),
                                            $this->copyToRecordToLinkTool($meetingDB)
                                        );
                                        $actionLinks .= Display::url(
                                            Display::return_icon(
                                                'agenda.png',
                                                get_lang('AddToCalendar')
                                            ),
                                            $this->addToCalendarUrl($meetingDB, $record)
                                        );
                                    }
                                    $actionLinks .= Display::url(
                                        Display::return_icon(
                                            'delete.png',
                                            get_lang('Delete')
                                        ),
                                        $this->deleteRecordUrl($meetingDB)
                                    );

                                    if ($meetingDB['visibility'] == 0) {
                                        $actionLinks .= Display::url(
                                            Display::return_icon(
                                                'invisible.png',
                                                get_lang('MakeVisible'),
                                                array(),
                                                ICON_SIZE_MEDIUM
                                            ),
                                            $this->publishUrl($meetingDB)
                                        );
                                    } else {
                                        $actionLinks .= Display::url(
                                            Display::return_icon(
                                                'visible.png',
                                                get_lang('MakeInvisible'),
                                                array(),
                                                ICON_SIZE_MEDIUM
                                            ),
                                            $this->unPublishUrl($meetingDB)
                                        );
                                    }
                                }
                                $count++;
                                $recordArray[] = $url;
                                $actionLinksArray[] = $actionLinks;
                            } else {
                                /*if (is_array($record) && isset($record['recordID']) && isset($record['playbacks'])) {

                                    //Fix the bbb timestamp
                                    //$record['startTime'] = substr($record['startTime'], 0, strlen($record['startTime']) -3);
                                    //$record['endTime']   = substr($record['endTime'], 0, strlen($record['endTime']) -3);
                                    //.' - '.api_convert_and_format_date($record['startTime']).' - '.api_convert_and_format_date($record['endTime'])
                                    foreach($record['playbacks'] as $item) {
                                        $url = Display::url(get_lang('ViewRecord'), $item['url'], array('target' => '_blank'));
                                        //$url .= Display::url(get_lang('DeleteRecord'), api_get_self().'?action=delete_record&'.$record['recordID']);
                                        if ($this->isConferenceManager()) {
                                            $url .= Display::url(Display::return_icon('link.gif',get_lang('CopyToLinkTool')), api_get_self().'?action=copy_record_to_link_tool&id='.$meetingDB['id'].'&record_id='.$record['recordID']);
                                            $url .= Display::url(Display::return_icon('agenda.png',get_lang('AddToCalendar')), api_get_self().'?action=add_to_calendar&id='.$meetingDB['id'].'&start='.api_strtotime($meetingDB['created_at']).'&url='.$item['url']);
                                            $url .= Display::url(Display::return_icon('delete.png',get_lang('Delete')), api_get_self().'?action=delete_record&id='.$record['recordID']);
                                        }
                                        //$url .= api_get_self().'?action=publish&id='.$record['recordID'];
                                        $count++;
                                        $recordArray[] = $url;
                                    }
                                }*/
                            }
                        }
                    }
                } else {
                    $actionLinks = '';
                    if ($this->isConferenceManager()) {
                        if ($meetingDB['visibility'] == 0) {
                            $actionLinks .= Display::url(
                                Display::return_icon(
                                    'invisible.png',
                                    get_lang('MakeVisible'),
                                    array(),
                                    ICON_SIZE_MEDIUM
                                ),
                                $this->publishUrl($meetingDB)
                            );
                        } else {
                            $actionLinks .= Display::url(
                                Display::return_icon(
                                    'visible.png',
                                    get_lang('MakeInvisible'),
                                    array(),
                                    ICON_SIZE_MEDIUM
                                ),
                                $this->unPublishUrl($meetingDB)
                            );
                        }
                    }
                    $actionLinksArray[] = $actionLinks;
                    $item['action_links'] = implode('<br />', $actionLinksArray);
                }
                //var_dump($recordArray);
                $item['show_links']  = implode('<br />', $recordArray);
                $item['action_links'] = implode('<br />', $actionLinksArray);
            }

            $item['created_at'] = api_convert_and_format_date($meetingDB['created_at']);
            //created_at
            $meetingDB['created_at'] = $item['created_at']; //avoid overwrite in array_merge() below

            $item['publish_url'] = $this->publishUrl($meetingDB);
            $item['unpublish_url'] = $this->unPublishUrl($meetingBBB);

            if ($meetingDB['status'] == 1) {
                $joinParams = array(
                    'meetingId' => $meetingDB['remote_id'],		//-- REQUIRED - A unique id for the meeting
                    'username' => $this->userCompleteName,	//-- REQUIRED - The name that will display for the user in the meeting
                    'password' => $pass,			//-- REQUIRED - The attendee or moderator password, depending on what's passed here
                    'createTime' => '',			//-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                    'userID' => '',			//	-- OPTIONAL - string
                    'webVoiceConf' => ''	//	-- OPTIONAL - string
                );
                $item['go_url'] = $this->protocol.$this->api->getJoinMeetingURL($joinParams);
            }
            $item = array_merge($item, $meetingDB, $meetingBBB);
            $newMeetingList[] = $item;
        }

        return $newMeetingList;
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
     * Closes a meeting (usually when the user click on the close button from
     * the conferences listing.
     * @param string The internal ID of the meeting (id field for this meeting)
     * @return void
     * @assert (0) === false
     */
    public function endMeeting($id)
    {
        if (empty($id)) {

            return false;
        }
        $meetingData = Database::select('*', $this->table, array('where' => array('id = ?' => array($id))), 'first');
        $pass = $this->getUserMeetingPassword();

        $endParams = array(
            'meetingId' => $meetingData['remote_id'],   // REQUIRED - We have to know which meeting to end.
            'password' => $pass,        // REQUIRED - Must match moderator pass for meeting.
        );
        $this->api->endMeetingWithXmlResponseArray($endParams);
        Database::update(
            $this->table,
            array('status' => 0, 'closed_at' => api_get_utc_datetime()),
            array('id = ? ' => $id)
        );
    }

    /**
     * Gets the password for a specific meeting for the current user
     * @return string A moderator password if user is teacher, or the course code otherwise
     */
    public function getUserMeetingPassword()
    {
        if ($this->isConferenceManager()) {

            return $this->getModMeetingPassword();
        } else {

            if ($this->isGlobalConference()) {

                return 'url_'.api_get_current_access_url_id();
            }

            return api_get_course_id();
        }
    }

    /**
     * Generated a moderator password for the meeting
     * @return string A password for the moderation of the videoconference
     */
    public function getModMeetingPassword()
    {
        if ($this->isGlobalConference()) {

            return 'url_'.api_get_current_access_url_id().'_mod';
        }

        return api_get_course_id().'mod';
    }

    /**
     * Get users online in the current course room
     * @return int The number of users currently connected to the videoconference
     * @assert () > -1
     */
    public function getUsersOnlineInCurrentRoom()
    {
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $conditions = array(
            'where' => array(
                'c_id = ? AND session_id = ? AND status = 1 ' => array(
                    $courseId,
                    $sessionId,
                ),
            ),
        );

        if ($this->hasGroupSupport()) {
            $groupId = api_get_group_id();
            $conditions = array(
                'where' => array(
                    'c_id = ? AND session_id = ? AND group_id = ? AND status = 1 ' => array(
                        $courseId,
                        $sessionId,
                        $groupId
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
        $pass = $this->getModMeetingPassword();
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
     * Deletes a previous recording of a meeting
     * @param int integral ID of the recording
     * @return array ?
     * @assert () === false
     * @todo Also delete links and agenda items created from this recording
     */
    public function deleteRecord($id)
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

        $recordingParams = array(
            /*
             * NOTE: Set the recordId below to a valid id after you have
             * created a recorded meeting, and received a real recordID
             * back from your BBB server using the
             * getRecordingsWithXmlResponseArray method.
             */

            // REQUIRED - We have to know which recording:
            'recordId' => $meetingData['remote_id'],
        );

        $result = $this->api->deleteRecordingsWithXmlResponseArray($recordingParams);

        if (!empty($result) && isset($result['deleted']) && $result['deleted'] == 'true') {
            Database::delete(
                $this->table,
                array('id = ?' => array($id))
            );
        }

        return $result;
    }

    /**
     * Creates a link in the links tool from the given videoconference recording
     * @param int ID of the item in the plugin_bbb_meeting table
     * @param string Hash identifying the recording, as provided by the API
     * @return mixed ID of the newly created link, or false on error
     * @assert (null, null) === false
     * @assert (1, null) === false
     * @assert (null, 'abcdefabcdefabcdefabcdef') === false
     */
    public function copyRecordToLinkTool($id)
    {
        if (empty($id)) {
            return false;
        }
        //$records =  BigBlueButtonBN::getRecordingsUrl($id);
        $meetingData = Database::select('*', $this->table, array('where' => array('id = ?' => array($id))), 'first');

        $records = $this->api->getRecordingsWithXmlResponseArray(array('meetingId' => $meetingData['remote_id']));

        if (!empty($records)) {
            if (isset($records['message']) && !empty($records['message'])) {
                if ($records['messageKey'] == 'noRecordings') {
                    $recordArray[] = get_lang('NoRecording');
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
     * Get active session in the all platform
     */
    public function getActiveSessionsCount()
    {
        $meetingList = Database::select(
            'count(id) as count',
            $this->table,
            array('where' => array('status = ?' => array(1))),
            'first'
        );

        return $meetingList['count'];
    }

    /**
     * @param string $url
     */
    public function redirectToBBB($url)
    {
        if (file_exists(__DIR__ . '/../config.vm.php')) {
            // Using VM
            echo Display::url(get_lang('ClickToContinue'), $url);
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
        $courseInfo = api_get_course_info();

        if (empty($courseInfo)) {

            if ($this->isGlobalConference()) {
                return 'global=1';
            }

            return '';
        }

        return api_get_cidreq();
    }

    /**
     * @return string
     */
    public function getCurrentVideoConferenceName()
    {
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
     * @return string
     */
    public function endUrl($meeting)
    {
        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=end&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     * @param array $record
     * @return string
     */
    public function addToCalendarUrl($meeting, $record = [])
    {
        $url = isset($record['playbackFormatUrl']) ? $record['playbackFormatUrl'] : '';

        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=add_to_calendar&id='.$meeting['id'].'&start='.api_strtotime($meeting['created_at']).'&url='.$url;
    }

    /**
     * @param array $meeting
     * @return string
     */
    public function publishUrl($meeting)
    {
        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=publish&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     * @return string
     */
    public function unPublishUrl($meeting)
    {
        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=unpublish&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     * @return string
     */
    public function deleteRecordUrl($meeting)
    {
        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=delete_record&id='.$meeting['id'];
    }

    /**
     * @param array $meeting
     * @return string
     */
    public function copyToRecordToLinkTool($meeting)
    {
        return api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.$this->getUrlParams().'&action=copy_record_to_link_tool&id='.$meeting['id'];
    }
}
