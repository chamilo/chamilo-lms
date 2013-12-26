<?php
/**
 * Chamilo-OpenMeetings integration plugin library, defining methods to connect
 * to OpenMeetings from Chamilo by calling its web services
 * @package chamilo.plugin.openmeetings
 */
/**
 * Initialization
 */
include_once 'services/getSession/getSession.class.php';
include_once 'services/loginUser/loginUser.class.php';
include_once 'services/addRoomWithModerationAndExternalType/addRoomWithModerationAndExternalType.class.php';
include_once 'services/getRoomWithCurrentUsersById/getRoomWithCurrentUsersById.class.php';
include_once 'services/setUserObjectAndGenerateRoomHashByURLAndRecFlag/setUserObjectAndGenerateRoomHashByURLAndRecFlag.class.php';
include_once 'services/closeRoom/closeRoom.class.php';
include_once 'services/getRoomById/getRoomById.class.php';
/**
 * Open Meetings-Chamilo connector class
 */
class OpenMeetings
{

    var $url;
    var $salt;
    var $api;
    var $user_complete_name = null;
    var $protocol = 'http://';
    var $debug = false;
    var $logout_url = null;
    var $plugin_enabled = false;
    var $sessionId = "";

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
            define('CONFIG_OPENMEETINGS_USER_SALT', $this->user);
            define('CONFIG_OPENMEETINGS_PASS_SALT', $this->pass);
            define('CONFIG_OPENMEETINGS_SERVER_BASE_URL', $this->url);

            $this->api = new OpenMeetingsAPI();
            $this->plugin_enabled = $om_plugin;
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
            $objGetSession = new getSession();
            $objloginUser = new loginUser();
            $urlWsdl = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "/services/UserService?wsdl";
            $omServices = new SoapClient( $urlWsdl );
            //Verifying if there is already an active session
            if (empty($_SESSION['sessOpenMeeting'])) {
                $gsFun = $omServices->getSession($objGetSession);
                $_SESSION['sessOpenMeeting'] = $objloginUser->SID = $this->sessionId = $gsFun->return->session_id;
                $objloginUser->username = CONFIG_OMUSER_SALT;
                $objloginUser->userpass = CONFIG_OMPASS_SALT;

                $luFn = $omServices->loginUser($objloginUser);

                if ($luFn->return > 0) {
                 return true;
                } else {
                 return false;
                }
            } else {
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
        $objAddRoom = new addRoomWithModerationAndExternalType();
        $roomTypeId = $isModerated = ( $this->isTeacher() ) ? 1 : 2 ;
        $params['c_id'] = api_get_course_int_id();
        $course_name = 'COURSE_ID_' . $params['c_id'] .'_NAME_' . $params['meeting_name'];
        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "/services/RoomService?wsdl";

        $objAddRoom->SID = $this->sessionId;
        $objAddRoom->name = $course_name;
        $objAddRoom->roomtypes_id = $roomTypeId;
        $objAddRoom->comment = get_lang('Course').': ' . $params['meeting_name'] . ' Plugin for Chamilo';
        $objAddRoom->numberOfPartizipants = 40;
        $objAddRoom->ispublic = true;
        $objAddRoom->appointment = false;
        $objAddRoom->isDemoRoom = false;
        $objAddRoom->demoTime = false;
        $objAddRoom->isModeratedRoom = $isModerated;
        $objAddRoom->externalRoomType = true;

        $omServices = new SoapClient( $urlWsdl );
        $adFun = $omServices->addRoomWithModerationAndExternalType( $objAddRoom );

        if ($adFun->return > -1) {
            $meetingId = $params['id'] = $adFun->return;
            $params['status'] = '1';
            $params['meeting_name'] = $course_name;
            $params['created_at'] = date('l jS \of F Y h:i:s A');

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
        //$urlWithoutProtocol = str_replace("http://",  CONFIG_OPENMEETINGS_SERVER_BASE_URL);
        //$imgWithoutProtocol = str_replace("http://", $_SESSION['_user']['avatar'] );

        $iframe = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "/?" .
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

        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "/services/UserService?wsdl";
        $omServices = new SoapClient( $urlWsdl );
        $objRec = new setUserObjectAndGenerateRecordingHashByURL();

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

        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "/services/UserService?wsdl";
        $omServices = new SoapClient( $urlWsdl );
        $objRec = new setUserObjectAndGenerateRoomHashByURLAndRecFlag();

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
        $pass = $this->getMeetingUserPassword();
        $this->loginUser();
        $meeting_list = Database::select('*', $this->table, array('where' => array('c_id = ? ' => api_get_course_int_id())));

        $urlWsdl = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "/services/RoomService?wsdl";
        $omServices = new SoapClient($urlWsdl);
        $objRoom = new getRoomById();
        $objCurrentUsers = new getRoomWithCurrentUsersById();
        $objRoom->SID = $objCurrentUsers->SID = $this->sessionId;

        foreach ($meeting_list as $meeting_db) {
            $objRoom->rooms_id = $objCurrentUsers->rooms_id = $meeting_db['id'];
            try {
                $objRoomId = $omServices->getRoomById($objRoom);
                if (empty($objRoomId->return)) {
                    Database::delete($this->table, "id = {$meeting_db['id']}");
                    continue;
                }
                $objCurUs = $omServices->getRoomWithCurrentUsersById($objCurrentUsers);
            } catch  (SoapFault $e) {
                echo $e->faultstring;
                exit;
            }
            //if( empty($objCurUs->returnMeetingID) ) continue;

            $current_room = array(
                'roomtype' => $objCurUs->return->roomtype->roomtypes_id,
                'meetingName' => $objCurUs->return->name,
                'meetingId' => $objCurUs->return->meetingID,
                'createTime' => $objCurUs->return->rooms_id,
                'showMicrophoneStatus' => $objCurUs->return->showMicrophoneStatus,
                'attendeePw' => $objCurUs->return->attendeePW,
                'moderatorPw' => $objCurUs->return->moderators,
                'isClosed' => $objCurUs->return->isClosed,
                'allowRecording' => $objCurUs->return->allowRecording,
                'startTime' => $objCurUs->return->startTime,
                'endTime' => $objCurUs->return->updatetime,
                'participantCount' => count($objCurUs->return->currentusers),
                'maxUsers' => $objCurUs->return->numberOfPartizipants,
                'moderatorCount' => count($objCurUs->return->moderators)
            );
                // Then interate through attendee results and return them as part of the array:
            if (!empty($objCurUs->return->currentusers)) {
                    foreach ($objCurUs->return->currentusers as $a)
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
//                $joinUrl = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "?" .
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
            $urlWsdl = CONFIG_OPENMEETINGS_SERVER_BASE_URL . "/services/RoomService?wsdl";
            $omServices = new SoapClient( $urlWsdl );
            $objClose = new closeRoom();
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