<?php
/**
 * Chamilo-OpenMeetings integration plugin library, defining methods to connect
 * to OpenMeetings from Chamilo by calling its web services
 * @package chamilo.plugin.openmeetings
 */
/**
 * Initialization
 */
include_once __DIR__.'/session.class.php';
include_once __DIR__.'/room.class.php';
include_once __DIR__.'/user.class.php';
/**
 * Open Meetings-Chamilo connector class
 */
class OpenMeetings
{

    public $url;
    public $user;
    public $pass;
    public $api;
    public $user_complete_name = null;
    public $protocol = 'http://';
    public $debug = false;
    public $logout_url = null;
    public $plugin_enabled = false;
    public $sessionId = "";
    public $roomName = '';

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the videoconference server)
     */
    function __construct()
    {
        // initialize video server settings from global settings
        $plugin = OpenMeetingsPlugin::create();

        $om_plugin = $plugin->get('tool_enable');
        $om_host   = $plugin->get('host');
        $om_user   = $plugin->get('user');
        $om_pass   = $plugin->get('pass');

        $this->table = Database::get_main_table('plugin_openmeetings');

        if ($om_plugin) {
            $user_info = api_get_user_info();
            $this->user_complete_name = $user_info['complete_name'];
            $this->user = $om_user;
            $this->pass = $om_pass;
            $this->url = $om_host;

            // Setting OM api
            define('CONFIG_OPENMEETINGS_USER', $this->user);
            define('CONFIG_OPENMEETINGS_PASS', $this->pass);
            define('CONFIG_OPENMEETINGS_SERVER_URL', $this->url);

            $this->gateway = new OpenMeetingsGateway();
            $this->plugin_enabled = $om_plugin;
            // The room has a name composed of C + course ID + '-' + session ID
            $this->roomName = 'C'.api_get_course_int_id().'-'.api_get_session_id();
        }
    }
    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    function isTeacher()
    {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }
    /**
     * Login the user with OM Server
     * @return bool True if the user is correct and false when is incorrect
     */
    function loginUser()
    {
        try {
            //$objGetSession = new Chamilo\Plugin\OpenMeetings\Session();
            //$urlWsdl = CONFIG_OPENMEETINGS_SERVER_URL . "/services/UserService?wsdl";
            //$omServices = new SoapClient( $urlWsdl );
            //Verifying if there is already an active session
            if (empty($_SESSION['sessOpenMeeting'])) {
                //$gsFun = $omServices->getSession($objGetSession);

                //$loginUser = $omServices->loginUser(array('SID' => $this->sessionId, 'username' => $this->user, 'userpass' => $this->pass));
                $loginUser = $this->gateway->loginUser();
                $_SESSION['sessOpenMeeting'] = $this->sessionId = $this->gateway->session_id;

                if ($loginUser) {
                    error_log(__FILE__.' '.__LINE__.' user logged in');
                    return true;
                } else {
                    error_log(__FILE__.' '.__LINE__);
                    return false;
                }
            } else {
                error_log(__LINE__.' '.$_SESSION['sessOpenMeeting']);
                $this->sessionId = $_SESSION['sessOpenMeeting'];
                return true;
            }
        } catch (SoapFault $e) {
          echo "<h1>Warning</h1>
                <p>We have detected some problems </br>
                Fault: {$e->faultstring}</p>";
          return false;
        }
    }
    /*
     * Creating a Room for the meeting
    * @return bool True if the user is correct and false when is incorrect
    */
    function createMeeting($params)
    {
        //$id = Database::insert($this->table, $params);
      try {
        $objAddRoom = new Chamilo\Plugin\OpenMeetings\Room();
        $roomTypeId = $isModerated = ( $this->isTeacher() ) ? 1 : 2 ;
        $params['c_id'] = api_get_course_int_id();
        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_URL . "/services/RoomService?wsdl";

        $objAddRoom->SID = $this->sessionId;
        $objAddRoom->name = $this->roomName;
        $objAddRoom->roomtypes_id = $roomTypeId;
        $objAddRoom->comment = get_lang('Course').': ' . $params['meeting_name'] . ' Plugin for Chamilo';
        $objAddRoom->numberOfPartizipants = 40;
        $objAddRoom->ispublic = true;
        $objAddRoom->appointment = false;
        $objAddRoom->isDemoRoom = false;
        $objAddRoom->demoTime = false;
        $objAddRoom->isModeratedRoom = $isModerated;
        $objAddRoom->externalRoomType = 'chamilo';

        $omServices = new SoapClient($urlWsdl, array("trace" => 1, "exceptions" => true, "cache_wsdl" => WSDL_CACHE_NONE));

        try {
            $s = $omServices->addRoomWithModerationAndExternalType($objAddRoom);
            //error_log($omServices->__getLastRequest());
            //error_log($omServices->__getLastResponse());
        } catch (SoapFault $e) {
            echo "<h1>Warning</h1>
                <p>We have detected some problems </br>
                Fault: {$e->faultstring}</p>";
            return -1;
        }

        if ($s->return > -1) {
            $meetingId = $params['id'] = $s->return;
            $params['status'] = '1';
            $params['meeting_name'] = $this->roomName;
            $params['created_at'] = api_get_utc_datetime();

            Database::insert($this->table, $params);

            $this->joinMeeting($meetingId);
        } else {
            return -1;
        }

      } catch(SoapFault $e) {
          echo "<h1>Warning</h1>
                <p>We have detected some problems </br>
                Fault: {$e->faultstring}</p>";
          return -1;
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
    function joinMeeting($meetingId)
    {
        if (empty($meetingId)) {
            return false;
        }
        $meetingData = Database::select('*', $this->table, array('where' => array('id = ? AND status = 1 ' => $meetingId)), 'first');

        if (empty($meetingData)) {
            if ($this->debug) error_log("meeting does not exist: $meetingId ");
            return false;
        }
        $params = array( 'room_id' => $meetingId );

        $returnVal = $this->setUserObjectAndGenerateRoomHashByURLAndRecFlag( $params );
        //$urlWithoutProtocol = str_replace("http://",  CONFIG_OPENMEETINGS_SERVER_URL);
        //$imgWithoutProtocol = str_replace("http://", $_SESSION['_user']['avatar'] );

        $iframe = CONFIG_OPENMEETINGS_SERVER_URL . "/?" .
                "secureHash=" . $returnVal /*.
                '&username=FRAGOTE' .
                '&firstname=DD' .
                '&lastname=DDDD' .
                '&profilePictureUrl=X' .
                '&email=xxx' .
                '&externalUserId=fragote' .
                '&room_id=38' .
                '&scopeRoomId=38' .
                '&becomeModeratorAsInt=1' .
                '&showAudioVideoTestAsInt=0' .
                '&allowRecording=1'*/;

        printf("<iframe src='%s' width='%s' height = '%s' />", $iframe, "100%", 640);
    }
    /**
     * Checks if the videoconference server is running.
     * Function currently disabled (always returns 1)
     * @return bool True if server is running, false otherwise
     * @assert () === false
     */
    function isServerRunning()
    {
        return true;
    }
     /**
     * Gets the password for a specific meeting for the current user
     * @return string A moderator password if user is teacher, or the course code otherwise
     */
    function getMeetingUserPassword()
    {
        if ($this->isTeacher()) {
            return $this->getMeetingModerationPassword();
        } else {
            return api_get_course_id();
        }
    }
    /**
     * Generated a moderator password for the meeting
     * @return string A password for the moderation of the video conference
     */
    function getMeetingModerationPassword()
    {
        return api_get_course_id().'mod';
    }
    /**
     * Get information about the given meeting
     * @param array ...?
     * @return mixed Array of information on success, false on error
     * @assert (array()) === false
     */
    function getMeetingInfo($params)
    {
        try {
            $result = $this->api->getMeetingInfoArray($params);
            if ($result == null) {
                if ($this->debug) error_log("Failed to get any response. Maybe we can't contact the OpenMeetings server.");
            } else {
                return $result;
            }
        } catch (Exception $e) {
            if ($this->debug) error_log('Caught exception: ', $e->getMessage(), "\n");
        }
        return false;
    }

    /**
     * @param array $params Array of parameters
     * @return mixed
     */
    function setUserObjectAndGenerateRecordingHashByURL( $params )
    {
        $username = $_SESSION['_user']['username'];
        $firstname = $_SESSION['_user']['firstname'];
        $lastname = $_SESSION['_user']['lastname'];
        $userId = $_SESSION['_user']['user_id'];
        $systemType = 'chamilo';
        $room_id = $params['room_id'];

        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_URL . "/services/UserService?wsdl";
        $omServices = new SoapClient( $urlWsdl );
        $objRec = new Chamilo\Plugin\OpenMeetings\User();

        $objRec->SID = $this->sessionId;
        $objRec->username = $username;
        $objRec->firstname = $firstname;
        $objRec->lastname = $lastname;
        $objRec->externalUserId = $userId;
        $objRec->externalUserType = $systemType;
        $objRec->recording_id = $recording_id;

        $orFn = $omServices->setUserObjectAndGenerateRecordingHashByURL( $objRec );
        return $orFn->return;
     }

    /**
     * @param Array $params Array of parameters
     * @return mixed
     */
    function setUserObjectAndGenerateRoomHashByURLAndRecFlag( $params )
    {

        $username = $_SESSION['_user']['username'];
        $firstname = $_SESSION['_user']['firstname'];
        $lastname = $_SESSION['_user']['lastname'];
        $profilePictureUrl = $_SESSION['_user']['avatar'];
        $email = $_SESSION['_user']['mail'];
        $userId = $_SESSION['_user']['user_id'];
        $systemType = 'Chamilo';
        $room_id = $params['room_id'];
        $becomeModerator = ( $this->isTeacher() ? 1 : 0 );
        $allowRecording = 1; //Provisional

        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_URL . "/services/UserService?wsdl";
        $omServices = new SoapClient( $urlWsdl );
        $objRec = new Chamilo\Plugin\OpenMeetings\User();

        $objRec->SID = $this->sessionId;
        $objRec->username = $username;
        $objRec->firstname = $firstname;
        $objRec->lastname = $lastname;
        $objRec->profilePictureUrl = $profilePictureUrl;
        $objRec->email = $email;
        $objRec->externalUserId = $userId;
        $objRec->externalUserType = $systemType;
        $objRec->room_id = $room_id;
        $objRec->becomeModeratorAsInt = $becomeModerator;
        $objRec->showAudioVideoTestAsInt = 1;
        $objRec->allowRecording = $allowRecording;

        $rcFn = $omServices->setUserObjectAndGenerateRoomHashByURLAndRecFlag( $objRec );

        return $rcFn->return;
    }

    /**
     * Gets all the course meetings saved in the plugin_openmeetings table
     * @return array Array of current open meeting rooms
     */
    function getCourseMeetings()
    {
        $new_meeting_list = array();
        $item = array();
        $this->loginUser();
        $meeting_list = Database::select('*', $this->table, array('where' => array('c_id = ? ' => api_get_course_int_id(), ' AND session_id = ? ' => api_get_session_id())));

        error_log(__FILE__.' '.__FUNCTION__.' '.__LINE__.' '.print_r($meeting_list,1));

        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_URL . "/services/RoomService?wsdl";
        $omServices = new SoapClient($urlWsdl);
        $objRoom = new Chamilo\Plugin\OpenMeetings\Room();
        try {
            $rooms = $this->gateway->getRoomsWithCurrentUsersByType($this->sessionId);
            //$rooms = $omServices->getRoomsPublic(array(
                //'SID' => $this->sessionId,
                //'start' => 0,
                //'max' => 10,
                //'orderby' => 'name',
                //'asc' => 'true',
                //'externalRoomType' => 'chamilo',
                //'roomtypes_id' => 'chamilo',
                //)
            //);
        } catch (SoapFault $e) {
            error_log($e->faultstring);
            //error_log($rooms->getDebug());
            return false;
        }
        $objRoom->SID = $this->sessionId;

        foreach ($meeting_list as $meeting_db) {
            $objRoom->rooms_id = $meeting_db['id'];
            try {
                $objRoomId = $omServices->getRoomById($objRoom);
                if (empty($objRoomId->return)) {
                    Database::delete($this->table, "id = {$meeting_db['id']}");
                    continue;
                }
                //$objCurUs = $omServices->getRoomWithCurrentUsersById($objCurrentUsers);
            } catch  (SoapFault $e) {
                echo $e->faultstring;
                exit;
            }
            //if( empty($objCurUs->returnMeetingID) ) continue;

            $current_room = array(
                'roomtype' => $objRoomId->return->roomtype->roomtypes_id,
                'meetingName' => $objRoomId->return->name,
                'meetingId' => $objRoomId->return->meetingID,
                'createTime' => $objRoomId->return->rooms_id,
                'showMicrophoneStatus' => $objRoomId->return->showMicrophoneStatus,
                'attendeePw' => $objRoomId->return->attendeePW,
                'moderatorPw' => $objRoomId->return->moderators,
                'isClosed' => $objRoomId->return->isClosed,
                'allowRecording' => $objRoomId->return->allowRecording,
                'startTime' => $objRoomId->return->startTime,
                'endTime' => $objRoomId->return->updatetime,
                'participantCount' => count($objRoomId->return->currentusers),
                'maxUsers' => $objRoomId->return->numberOfPartizipants,
                'moderatorCount' => count($objRoomId->return->moderators)
            );
                // Then interate through attendee results and return them as part of the array:
            if (!empty($objRoomId->return->currentusers)) {
                    foreach ($objRoomId->return->currentusers as $a)
                      $current_room[] = array(
                                'userId' => $a->username,
                                'fullName' => $a->firstname . " " . $a->lastname,
                                'isMod' => $a->isMod
                      );
            }

            $meeting_om = $current_room;

            if (empty( $meeting_om )) {
                if ($meeting_db['status'] == 1 && $this->isTeacher()) {
                    $this->endMeeting($meeting_db['id']);
                }
            } else {
                $meeting_om['add_to_calendar_url'] = api_get_self().'?action=add_to_calendar&id='.$meeting_db['id'].'&start='.api_strtotime($meeting_db['startTime']);
            }
            $meeting_om['end_url'] = api_get_self().'?action=end&id='.$meeting_db['id'];

            $record_array = array();

//            if ($meeting_db['record'] == 1) {
//                $recordingParams = array(
//                    'meetingId' => $meeting_db['id'],        //-- OPTIONAL - comma separate if multiple ids
//                );
//
//                $records = $this->api->getRecordingsWithXmlResponseArray($recordingParams);
//                if (!empty($records)) {
//                    $count = 1;
//                    if (isset($records['message']) && !empty($records['message'])) {
//                        if ($records['messageKey'] == 'noRecordings') {
//                            $record_array[] = get_lang('NoRecording');
//                        } else {
//                            //$record_array[] = $records['message'];
//                        }
//                    } else {
//                        foreach ($records as $record) {
//                            if (is_array($record) && isset($record['recordId'])) {
//                                $url = Display::url(get_lang('ViewRecord'), $record['playbackFormatUrl'], array('target' => '_blank'));
//                                if ($this->is_teacher()) {
//                                    $url .= Display::url(Display::return_icon('link.gif',get_lang('CopyToLinkTool')), api_get_self().'?action=copy_record_to_link_tool&id='.$meeting_db['id'].'&record_id='.$record['recordId']);
//                                    $url .= Display::url(Display::return_icon('agenda.png',get_lang('AddToCalendar')), api_get_self().'?action=add_to_calendar&id='.$meeting_db['id'].'&start='.api_strtotime($meeting_db['created_at']).'&url='.$record['playbackFormatUrl']);
//                                    $url .= Display::url(Display::return_icon('delete.png',get_lang('Delete')), api_get_self().'?action=delete_record&id='.$record['recordId']);
//                                }
//                                //$url .= api_get_self().'?action=publish&id='.$record['recordID'];
//                                $count++;
//                                $record_array[] = $url;
//                            } else {
//
//                            }
//                        }
//                    }
//                }
//                //var_dump($record_array);
//                $item['show_links']  = implode('<br />', $record_array);
//
//            }
//
             //$item['created_at'] = api_convert_and_format_date($meeting_db['created_at']);
//            //created_at
//
//            $item['publish_url'] = api_get_self().'?action=publish&id='.$meeting_db['id'];
//            $item['unpublish_url'] = api_get_self().'?action=unpublish&id='.$meeting_db['id'];
//
            //if ($meeting_db['status'] == 1) {
//                $joinParams = array(
//                    'meetingId' => $meeting_db['id'],        //-- REQUIRED - A unique id for the meeting
//                    'username' => $this->user_complete_name,    //-- REQUIRED - The name that will display for the user in the meeting
//                    'password' => $pass,            //-- REQUIRED - The attendee or moderator password, depending on what's passed here
//                    'createTime' => '',            //-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
//                    'userID' => '',            //    -- OPTIONAL - string
//                    'webVoiceConf' => ''    //    -- OPTIONAL - string
//                );
//                $returnVal = $this->setUserObjectAndGenerateRoomHashByURLAndRecFlag( array('room_id' => $meeting_db['id']) );
//                $joinUrl = CONFIG_OPENMEETINGS_SERVER_URL . "?" .
//                           "secureHash=" . $returnVal;
//
//                $item['go_url'] = $joinUrl;
            //}
            $item = array_merge($item, $meeting_db, $meeting_om);
            $new_meeting_list[] = $item;
        }
        return $new_meeting_list;
    }

    /**
     * Send a command to the OpenMeetings server to close the meeting
     * @param $meetingId
     * @return int
     */
    function endMeeting($meetingId)
    {
        try {
            $this->loginUser();
            $urlWsdl = CONFIG_OPENMEETINGS_SERVER_URL . "/services/RoomService?wsdl";
            $omServices = new SoapClient( $urlWsdl );
            $objClose = new Chamilo\Plugin\OpenMeetings\Room();
            $objClose->SID = $this->sessionId;
            $objClose->room_id = $meetingId;
            $objClose->status = false;
            $crFn = $omServices->closeRoom( $objClose );
            if ($crFn > 0) {
                Database::update($this->table, array('status' => 0, 'closed_at' => api_get_utc_datetime()), array('id = ? ' => $meetingId));
            }
        } catch (SoapFault $e) {
            echo "<h1>Warning</h1>
            <p>We have detected some problems </br>
            Fault: {$e->faultstring}</p>";
            exit;
            return -1;
        }
    }
}