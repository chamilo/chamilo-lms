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
class bbb
{
    public $url;
    public $salt;
    public $api;
    public $user_complete_name = null;
    public $protocol = 'http://';
    public $debug = false;
    public $logout_url = null;
    public $plugin_enabled = false;

    /**
     *
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the video conference server)
     * @param string $host
     * @param string $salt
     */
    public function __construct($host = null, $salt = null)
    {
        // Initialize video server settings from global settings
        $plugin = BBBPlugin::create();

        $bbb_plugin = $plugin->get('tool_enable');

        if (empty($host)) {
            $bbb_host = $plugin->get('host');
        } else {
            $bbb_host = $host;
        }
        if (empty($salt)) {
            $bbb_salt = $plugin->get('salt');
        } else {
            $bbb_salt = $salt;
        }

        $this->logout_url = api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php?'.api_get_cidreq();
        $this->table = Database::get_main_table('plugin_bbb_meeting');

        if ($bbb_plugin == true) {
            $user_info = api_get_user_info();
            $this->user_complete_name = $user_info['complete_name'];
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
            $this->plugin_enabled = true;
        }
    }

    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    public function is_teacher()
    {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }

    /**
     * See this file in you BBB to set up default values

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
     */
    public function create_meeting($params)
    {
        $params['c_id'] = api_get_course_int_id();
        $course_code = api_get_course_id();
        $params['session_id'] = api_get_session_id();

        $attende_password = $params['attendee_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : api_get_course_id();
        $moderator_password = $params['moderator_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : $this->get_mod_meeting_password();

        $params['record'] = api_get_course_setting('big_blue_button_record_and_store', $course_code) == 1 ? true : false;
        $max = api_get_course_setting('big_blue_button_max_students_allowed', $course_code);
        $max =  isset($max) ? $max : -1;
        $params['status'] = 1;

        if ($this->debug) error_log("enter create_meeting ".print_r($params, 1));

        $params['created_at'] = api_get_utc_datetime();
        $id = Database::insert($this->table, $params);

        if ($id) {
            if ($this->debug) error_log("create_meeting: $id ");

            $meeting_name       = isset($params['meeting_name']) ? $params['meeting_name'] : api_get_course_id().'-'.api_get_session_id();
            $welcome_msg        = isset($params['welcome_msg']) ? $params['welcome_msg'] : null;
            $record             = isset($params['record']) && $params['record'] ? 'true' : 'false';
            $duration           = isset($params['duration']) ? intval($params['duration']) : 0;
            // This setting currently limits the maximum conference duration,
            // to avoid lingering sessions on the videoconference server #6261
            $duration = 300;

            $bbb_params = array(
                'meetingId' => $id, 					// REQUIRED
                'meetingName' => $meeting_name, 	// REQUIRED
                'attendeePw' => $attende_password, 					// Match this value in getJoinMeetingURL() to join as attendee.
                'moderatorPw' => $moderator_password, 					// Match this value in getJoinMeetingURL() to join as moderator.
                'welcomeMsg' => $welcome_msg, 					// ''= use default. Change to customize.
                'dialNumber' => '', 					// The main number to call into. Optional.
                'voiceBridge' => '12345', 					// PIN to join voice. Required.
                'webVoice' => '', 						// Alphanumeric to join voice. Optional.
                'logoutUrl' =>  $this->logout_url,
                'maxParticipants' => $max, 				// Optional. -1 = unlimitted. Not supported in BBB. [number]
                'record' => $record, 					// New. 'true' will tell BBB to record the meeting.
                'duration' => $duration, 				// Default = 0 which means no set duration in minutes. [number]
                //'meta_category' => '', 				// Use to pass additional info to BBB server. See API docs.
            );

            if ($this->debug) error_log("create_meeting params: ".print_r($bbb_params,1));

            $status = false;
            $meeting = null;

            while ($status == false) {
                $result = $this->api->createMeetingWithXmlResponseArray(
                    $bbb_params
                );
                if (isset($result) && strval(
                        $result['returncode']
                    ) == 'SUCCESS'
                ) {
                    if ($this->debug) {
                        error_log(
                            "create_meeting result: " . print_r($result, 1)
                        );
                    }
                    $meeting = $this->join_meeting($meeting_name, true);

                    return $meeting;
                }
            }
            return $this->logout;
        }
    }

    /**
     * Tells whether the given meeting exists and is running
     * (using course code as name)
     * @param string $meeting_name Meeting name (usually the course code)
     *
     * @return bool True if meeting exists, false otherwise
     * @assert ('') === false
     * @assert ('abcdefghijklmnopqrstuvwxyzabcdefghijklmno') === false
     */
    public function meeting_exists($meeting_name)
    {
        if (empty($meeting_name)) { return false; }
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $meeting_data = Database::select(
            '*',
            $this->table,
            array(
                'where' => array(
                    'c_id = ? AND session_id = ? AND meeting_name = ? AND status = 1 ' =>
                        array($course_id, $session_id, $meeting_name)
                )
            ),
            'first'
        );
        if ($this->debug) error_log("meeting_exists ".print_r($meeting_data,1));
        if (empty($meeting_data)) {
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
    public function join_meeting($meeting_name, $loop = false)
    {
        if (empty($meeting_name)) {
            return false;
        }

        $pass = $this->get_user_meeting_password();

        $meeting_data = Database::select(
            '*',
            $this->table,
            array('where' => array('meeting_name = ? AND status = 1 ' => $meeting_name)),
            'first'
        );

        if (empty($meeting_data) || !is_array($meeting_data)) {
            if ($this->debug) error_log("meeting does not exist: $meeting_name ");

            return false;
        }

        $params = array(
            'meetingId' => $meeting_data['id'],
            //	-- REQUIRED - The unique id for the meeting
            'password' => $this->get_mod_meeting_password()
            //	-- REQUIRED - The moderator password for the meeting
        );

        $status = false;
        $meeting_info_exists = false;
        while ($status == false) {

            $meeting_is_running_info = $this->get_meeting_info($params);

            error_log(print_r($meeting_is_running_info, 1));

            if (strval($meeting_is_running_info['returncode']) == 'SUCCESS' &&
                isset($meeting_is_running_info['meetingName']) &&
                !empty($meeting_is_running_info['meetingName'])
                //strval($meeting_is_running_info['running']) == 'true'
            ) {
                $meeting_info_exists = true;
            }

            if ($this->debug) {
                error_log(
                    "meeting is running: " . intval($meeting_info_exists)
                );
            }

            if ($meeting_info_exists) {
                $status = true;
            }

            if ($loop) {
                continue;
            } else {
                break;
            }
        }

        if ($meeting_info_exists) {
            $joinParams = array(
                'meetingId' => $meeting_data['id'],	//	-- REQUIRED - A unique id for the meeting
                'username' => $this->user_complete_name,	//-- REQUIRED - The name that will display for the user in the meeting
                'password' => $pass,			//-- REQUIRED - The attendee or moderator password, depending on what's passed here
                //'createTime' => api_get_utc_datetime(),			//-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                'userID' => api_get_user_id(),				//-- OPTIONAL - string
                'webVoiceConf' => ''	//	-- OPTIONAL - string
            );
            $url = $this->api->getJoinMeetingURL($joinParams);
            $url = $this->protocol.$url;
        } else {
            $url = $this->logout_url;
        }
        if ($this->debug) error_log("return url :".$url);
        return $url;
    }

    /**
     * Get information about the given meeting
     * @param array ...?
     * @return mixed Array of information on success, false on error
     * @assert (array()) === false
     */
    public function get_meeting_info($params)
    {
        try {
            $result = $this->api->getMeetingInfoWithXmlResponseArray($params);
            if ($result == null) {
                if ($this->debug) error_log("Failed to get any response. Maybe we can't contact the BBB server.");
            } else {
                return $result;
            }
        } catch (Exception $e) {
            if ($this->debug) error_log('Caught exception: ', $e->getMessage(), "\n");
        }
        return false;
    }

    /**
     * Gets all the course meetings saved in the plugin_bbb_meeting table
     * @return array Array of current open meeting rooms
     */
    public function get_course_meetings()
    {
        $pass = $this->get_user_meeting_password();
        $meeting_list = Database::select('*', $this->table, array('where' => array('c_id = ? AND session_id = ? ' => array(api_get_course_int_id(), api_get_session_id()))));
        $new_meeting_list = array();

        $item = array();

        foreach ($meeting_list as $meeting_db) {
            $meeting_bbb = $this->get_meeting_info(array('meetingId' => $meeting_db['id'], 'password' => $pass));

            $meeting_bbb['end_url'] = api_get_self().'?'.api_get_cidreq().'&action=end&id='.$meeting_db['id'];

            if ((string)$meeting_bbb['returncode'] == 'FAILED') {
                if ($meeting_db['status'] == 1 && $this->is_teacher()) {
                    $this->end_meeting($meeting_db['id']);
                }
            } else {
                $meeting_bbb['add_to_calendar_url'] = api_get_self().'?'.api_get_cidreq().'&action=add_to_calendar&id='.$meeting_db['id'].'&start='.api_strtotime($meeting_db['created_at']);
            }

            $record_array = array();

            if ($meeting_db['record'] == 1) {
                $recordingParams = array(
                    'meetingId' => $meeting_db['id'],		//-- OPTIONAL - comma separate if multiple ids
                );

                //To see the recording list in your BBB server do: bbb-record --list
                $records = $this->api->getRecordingsWithXmlResponseArray($recordingParams);
                if (!empty($records)) {
                    $count = 1;
                    if (isset($records['message']) && !empty($records['message'])) {
                        if ($records['messageKey'] == 'noRecordings') {
                            $record_array[] = get_lang('NoRecording');
                        } else {
                            //$record_array[] = $records['message'];
                        }
                    } else {
                        foreach ($records as $record) {
                            if (is_array($record) && isset($record['recordId'])) {
                                $url = Display::url(get_lang('ViewRecord'), $record['playbackFormatUrl'], array('target' => '_blank'));
                                if ($this->is_teacher()) {
                                    $url .= Display::url(Display::return_icon('link.gif',get_lang('CopyToLinkTool')), api_get_self().'?'.api_get_cidreq().'&action=copy_record_to_link_tool&id='.$meeting_db['id'].'&record_id='.$record['recordId']);
                                    $url .= Display::url(Display::return_icon('agenda.png',get_lang('AddToCalendar')), api_get_self().'?'.api_get_cidreq().'&action=add_to_calendar&id='.$meeting_db['id'].'&start='.api_strtotime($meeting_db['created_at']).'&url='.$record['playbackFormatUrl']);
                                    $url .= Display::url(Display::return_icon('delete.png',get_lang('Delete')), api_get_self().'?'.api_get_cidreq().'&action=delete_record&id='.$record['recordId']);
                                }
                                //$url .= api_get_self().'?action=publish&id='.$record['recordID'];
                                $count++;
                                $record_array[] = $url;
                            } else {
                                /*if (is_array($record) && isset($record['recordID']) && isset($record['playbacks'])) {

                                    //Fix the bbb timestamp
                                    //$record['startTime'] = substr($record['startTime'], 0, strlen($record['startTime']) -3);
                                    //$record['endTime']   = substr($record['endTime'], 0, strlen($record['endTime']) -3);
                                    //.' - '.api_convert_and_format_date($record['startTime']).' - '.api_convert_and_format_date($record['endTime'])
                                    foreach($record['playbacks'] as $item) {
                                        $url = Display::url(get_lang('ViewRecord'), $item['url'], array('target' => '_blank'));
                                        //$url .= Display::url(get_lang('DeleteRecord'), api_get_self().'?action=delete_record&'.$record['recordID']);
                                        if ($this->is_teacher()) {
                                            $url .= Display::url(Display::return_icon('link.gif',get_lang('CopyToLinkTool')), api_get_self().'?action=copy_record_to_link_tool&id='.$meeting_db['id'].'&record_id='.$record['recordID']);
                                            $url .= Display::url(Display::return_icon('agenda.png',get_lang('AddToCalendar')), api_get_self().'?action=add_to_calendar&id='.$meeting_db['id'].'&start='.api_strtotime($meeting_db['created_at']).'&url='.$item['url']);
                                            $url .= Display::url(Display::return_icon('delete.png',get_lang('Delete')), api_get_self().'?action=delete_record&id='.$record['recordID']);
                                        }
                                        //$url .= api_get_self().'?action=publish&id='.$record['recordID'];
                                        $count++;
                                        $record_array[] = $url;
                                    }
                                }*/
                            }
                        }
                    }
                }
                //var_dump($record_array);
                $item['show_links']  = implode('<br />', $record_array);
            }

            $item['created_at'] = api_convert_and_format_date($meeting_db['created_at']);
            //created_at
            $meeting_db['created_at'] = $item['created_at']; //avoid overwrite in array_merge() below

            $item['publish_url'] = api_get_self().'?'.api_get_cidreq().'&action=publish&id='.$meeting_db['id'];
            $item['unpublish_url'] = api_get_self().'?'.api_get_cidreq().'&action=unpublish&id='.$meeting_db['id'];

            if ($meeting_db['status'] == 1) {
                $joinParams = array(
                    'meetingId' => $meeting_db['id'],		//-- REQUIRED - A unique id for the meeting
                    'username' => $this->user_complete_name,	//-- REQUIRED - The name that will display for the user in the meeting
                    'password' => $pass,			//-- REQUIRED - The attendee or moderator password, depending on what's passed here
                    'createTime' => '',			//-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                    'userID' => '',			//	-- OPTIONAL - string
                    'webVoiceConf' => ''	//	-- OPTIONAL - string
                );
                $item['go_url'] = $this->protocol.$this->api->getJoinMeetingURL($joinParams);
            }
            $item = array_merge($item, $meeting_db, $meeting_bbb);
            $new_meeting_list[] = $item;
        }
        return $new_meeting_list;
    }

    /**
     * Function disabled
     */
    public function publish_meeting($id)
    {
        //return BigBlueButtonBN::setPublishRecordings($id, 'true', $this->url, $this->salt);
    }

    /**
     * Function disabled
     */
    public function unpublish_meeting($id)
    {
        //return BigBlueButtonBN::setPublishRecordings($id, 'false', $this->url, $this->salt);
    }

    /**
     * Closes a meeting (usually when the user click on the close button from
     * the conferences listing.
     * @param string The name of the meeting (usually the course code)
     * @return void
     * @assert (0) === false
     */
    public function end_meeting($id)
    {
        if (empty($id)) { return false; }
        $pass = $this->get_user_meeting_password();
        $endParams = array(
            'meetingId' => $id, 			// REQUIRED - We have to know which meeting to end.
            'password' => $pass,				// REQUIRED - Must match moderator pass for meeting.
        );
        $this->api->endMeetingWithXmlResponseArray($endParams);
        Database::update($this->table, array('status' => 0, 'closed_at' => api_get_utc_datetime()), array('id = ? ' => $id));
    }

    /**
     * Gets the password for a specific meeting for the current user
     * @return string A moderator password if user is teacher, or the course code otherwise
     */
    public function get_user_meeting_password()
    {
        if ($this->is_teacher()) {
            return $this->get_mod_meeting_password();
        } else {
            return api_get_course_id();
        }
    }

    /**
     * Generated a moderator password for the meeting
     * @return string A password for the moderation of the videoconference
     */
    public function get_mod_meeting_password()
    {
        return api_get_course_id().'mod';
    }

    /**
     * Get users online in the current course room
     * @return int The number of users currently connected to the videoconference
     * @assert () > -1
     */
    public function get_users_online_in_current_room()
    {
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $meeting_data = Database::select('*', $this->table, array('where' => array('c_id = ? AND session_id = ? AND status = 1 ' => array($course_id, $session_id))), 'first');
        if (empty($meeting_data)) {
            return 0;
        }
        $pass = $this->get_mod_meeting_password();
        $info = $this->get_meeting_info(array('meetingId' => $meeting_data['id'], 'password' => $pass));

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
    public function delete_record($ids)
    {
        if (empty($ids) or (is_array($ids) && count($ids)==0)) { return false; }
        $recordingParams = array(
           /*
            * NOTE: Set the recordId below to a valid id after you have
            * created a recorded meeting, and received a real recordID
            * back from your BBB server using the
            * getRecordingsWithXmlResponseArray method.
            */

            // REQUIRED - We have to know which recording:
            'recordId' => $ids,
        );
        return $this->api->deleteRecordingsWithXmlResponseArray($recordingParams);
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
    public function copy_record_to_link_tool($id, $record_id)
    {
        if (empty($id) or empty($record_id)) {
            return false;
        }
        require_once api_get_path(LIBRARY_PATH).'link.lib.php';
        $records =  BigBlueButtonBN::getRecordingsArray($id, $this->url, $this->salt);
        if (!empty($records)) {
            foreach ($records as $record) {
                if ($record['recordID'] == $record_id) {
                    if (is_array($record) && isset($record['recordID']) && isset($record['playbacks'])) {
                        foreach ($record['playbacks'] as $item) {
                            $link = new Link();
                            $params['url'] = $item['url'];
                            $params['title'] = 'bbb 1';
                            $id = $link->save($params);
                            return $id;
                        }
                    }

                }
            }
        }

        return false;
    }

    /**
     * Checks if the videoconference server is running.
     * Function currently disabled (always returns 1)
     * @return bool True if server is running, false otherwise
     * @assert () === false
     */
    public function is_server_running()
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

        // js
        /*echo '<script>';
        echo 'window.location = "'.$url.'"';
        echo '</script>';
        exit;*/
    }
}
