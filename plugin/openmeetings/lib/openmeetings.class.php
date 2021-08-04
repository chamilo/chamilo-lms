<?php
/**
 * Chamilo-OpenMeetings integration plugin library, defining methods to connect
 * to OpenMeetings from Chamilo by calling its web services.
 *
 * @package chamilo.plugin.openmeetings
 */

namespace Chamilo\Plugin\OpenMeetings;

include_once __DIR__.'/session.class.php';
include_once __DIR__.'/room.class.php';
include_once __DIR__.'/user.class.php';

/**
 * Open Meetings-Chamilo connector class.
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
    public $chamiloCourseId;
    public $chamiloSessionId;
    public $externalType;

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the video conference server).
     */
    public function __construct()
    {
        global $_configuration;

        // initialize video server settings from global settings
        $plugin = \OpenMeetingsPlugin::create();

        $om_plugin = (bool) $plugin->get('tool_enable');
        $om_host = $plugin->get('host');
        $om_user = $plugin->get('user');
        $om_pass = $plugin->get('pass');
        $accessUrl = api_get_access_url($_configuration['access_url']);
        $this->externalType = substr($accessUrl['url'], strpos($accessUrl['url'], '://') + 3, -1);
        if (strcmp($this->externalType, 'localhost') == 0) {
            $this->externalType = substr(api_get_path(WEB_PATH), strpos(api_get_path(WEB_PATH), '://') + 3, -1);
        }
        $this->externalType = 'chamilolms.'.$this->externalType;
        $this->table = \Database::get_main_table('plugin_openmeetings');

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

            $this->gateway = new \OpenMeetingsGateway($this->url, $this->user, $this->pass);
            $this->plugin_enabled = $om_plugin;
            // The room has a name composed of C + course ID + '-' + session ID
            $this->chamiloCourseId = api_get_course_int_id();
            $this->chamiloSessionId = api_get_session_id();
            $this->roomName = 'C'.$this->chamiloCourseId.'-'.$this->chamiloSessionId;
            $return = $this->gateway->loginUser();
            if ($return == 0) {
                $msg = 'Could not initiate session with server through OpenMeetingsGateway::loginUser()';
                error_log(__FILE__.'+'.__LINE__.': '.$msg);
                exit($msg);
            }
            $this->sessionId = $this->gateway->sessionId;
        }
    }

    /**
     * Checks whether a user is teacher in the current course.
     *
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    public function isTeacher()
    {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }

    /*
     * Creating a Room for the meeting
    * @return bool True if the user is correct and false when is incorrect
    */
    public function createMeeting($params)
    {
        global $_configuration;
        // First, try to see if there is an active room for this course and session.
        $roomId = null;

        $meetingData = \Database::select(
            '*',
            $this->table,
            [
                'where' => [
                    'c_id = ?' => $this->chamiloCourseId,
                    ' AND session_id = ? ' => $this->chamiloSessionId,
                    ' AND status <> ? ' => 2,
                ],
            ],
            'first'
        );

        if ($meetingData != false && count($meetingData) > 0) {
            // There has been a room in the past for this course. It should
            // still be on the server, so update (instead of creating a new one)
            // This fills the following attributes: status, name, comment, chamiloCourseId, chamiloSessionId
            $room = new Room();
            $room->loadRoomId($meetingData['room_id']);
            $roomArray = (array) $room;
            $roomArray['SID'] = $this->sessionId;
            $roomId = $this->gateway->updateRoomWithModeration($room);
            if ($roomId != $meetingData['room_id']) {
                $msg = 'Something went wrong: the updated room ID ('.$roomId.') is not the same as the one we had ('.$meetingData['room_id'].')';
                exit($msg);
            }
        } else {
            $room = new Room();
            $room->SID = $this->sessionId;
            $room->name = $this->roomName;
            //$room->roomtypes_id = $room->roomtypes_id;
            $room->comment = urlencode(get_lang('Course').': '.$params['meeting_name'].' - '.$_configuration['software_name']);
            //$room->numberOfPartizipants = $room->numberOfPartizipants;
            $room->ispublic = boolval($room->getString('isPublic', 'false'));
            //$room->appointment = $room->getString('appointment');
            //$room->isDemoRoom = $room->getString('isDemoRoom');
            //$room->demoTime = $room->demoTime;
            //$room->isModeratedRoom = $room->getString('isModeratedRoom');
            $roomId = $this->gateway->createRoomWithModAndType($room);
        }

        if (!empty($roomId)) {
            /*
            // Find the biggest room_id so far, and create a new one
            if (empty($roomId)) {
                $roomData = \Database::select('MAX(room_id) as room_id', $this->table, array(), 'first');
                $roomId = $roomData['room_id'] + 1;
            }*/

            $params['status'] = '1';
            $params['meeting_name'] = $room->name;
            $params['created_at'] = api_get_utc_datetime();
            $params['room_id'] = $roomId;
            $params['c_id'] = api_get_course_int_id();
            $params['session_id'] = api_get_session_id();
            $params['record'] = ($room->allowRecording ? 1 : 0);

            $id = \Database::insert($this->table, $params);

            $this->joinMeeting($id);
        } else {
            return -1;
        }
    }

    /**
     * Returns a meeting "join" URL.
     *
     * @param string The name of the meeting (usually the course code)
     *
     * @return mixed The URL to join the meeting, or false on error
     *
     * @todo implement moderator pass
     * @assert ('') === false
     * @assert ('abcdefghijklmnopqrstuvwxyzabcdefghijklmno') === false
     */
    public function joinMeeting($meetingId)
    {
        if (empty($meetingId)) {
            return false;
        }
        $meetingData = \Database::select(
            '*',
            $this->table,
            ['where' => ['id = ? AND status = 1 ' => $meetingId]],
            'first'
        );

        if (empty($meetingData)) {
            if ($this->debug) {
                error_log("meeting does not exist: $meetingId ");
            }

            return false;
        }
        $params = ['room_id' => $meetingData['room_id']];
        $returnVal = $this->setUserObjectAndGenerateRoomHashByURLAndRecFlag($params);
        $iframe = $this->url."/?"."secureHash=".$returnVal;
        printf("<iframe src='%s' width='%s' height = '%s' />", $iframe, "100%", 640);
    }

    /**
     * Checks if the videoconference server is running.
     * Function currently disabled (always returns 1).
     *
     * @return bool True if server is running, false otherwise
     * @assert () === false
     */
    public function isServerRunning()
    {
        // Always return true for now as this requires the openmeetings object
        // to have been instanciated and this includes a loginUser() which
        // connects to the server
        return true;
    }

    /**
     * Gets the password for a specific meeting for the current user.
     *
     * @return string A moderator password if user is teacher, or the course code otherwise
     */
    public function getMeetingUserPassword()
    {
        if ($this->isTeacher()) {
            return $this->getMeetingModerationPassword();
        } else {
            return api_get_course_id();
        }
    }

    /**
     * Generated a moderator password for the meeting.
     *
     * @return string A password for the moderation of the video conference
     */
    public function getMeetingModerationPassword()
    {
        return api_get_course_id().'mod';
    }

    /**
     * Get information about the given meeting.
     *
     * @param array ...?
     *
     * @return mixed Array of information on success, false on error
     * @assert (array()) === false
     */
    public function getMeetingInfo($params)
    {
        try {
            $result = $this->api->getMeetingInfoArray($params);
            if ($result == null) {
                if ($this->debug) {
                    error_log(__FILE__.'+'.__LINE__." Failed to get any response. Maybe we can't contact the OpenMeetings server.");
                }
            } else {
                return $result;
            }
        } catch (Exception $e) {
            if ($this->debug) {
                error_log(__FILE__.'+'.__LINE__.' Caught exception: ', $e->getMessage(), "\n");
            }
        }

        return false;
    }

    /**
     * @param array $params Array of parameters
     *
     * @return mixed
     */
    public function setUserObjectAndGenerateRecordingHashByURL($params)
    {
        $username = $_SESSION['_user']['username'];
        $firstname = $_SESSION['_user']['firstname'];
        $lastname = $_SESSION['_user']['lastname'];
        $userId = $_SESSION['_user']['user_id'];
        $systemType = 'chamilo';
        $room_id = $params['room_id'];

        $urlWsdl = $this->url."/services/UserService?wsdl";
        $omServices = new \SoapClient($urlWsdl);
        $objRec = new User();

        $objRec->SID = $this->sessionId;
        $objRec->username = $username;
        $objRec->firstname = $firstname;
        $objRec->lastname = $lastname;
        $objRec->externalUserId = $userId;
        $objRec->externalUserType = $systemType;
        $objRec->recording_id = $recording_id;
        $orFn = $omServices->setUserObjectAndGenerateRecordingHashByURL($objRec);

        return $orFn->return;
    }

    /**
     * @param array $params Array of parameters
     *
     * @return mixed
     */
    public function setUserObjectAndGenerateRoomHashByURLAndRecFlag($params)
    {
        $username = $_SESSION['_user']['username'];
        $firstname = $_SESSION['_user']['firstname'];
        $lastname = $_SESSION['_user']['lastname'];
        $profilePictureUrl = $_SESSION['_user']['avatar'];
        $email = $_SESSION['_user']['mail'];
        $userId = $_SESSION['_user']['user_id'];
        $systemType = 'Chamilo';
        $room_id = $params['room_id'];
        $becomeModerator = ($this->isTeacher() ? 1 : 0);
        $allowRecording = 1; //Provisional

        $urlWsdl = $this->url."/services/UserService?wsdl";
        $omServices = new \SoapClient($urlWsdl);
        $objRec = new User();

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
        $rcFn = $omServices->setUserObjectAndGenerateRoomHashByURLAndRecFlag($objRec);

        return $rcFn->return;
    }

    /**
     * Gets all the course meetings saved in the plugin_openmeetings table.
     *
     * @return array Array of current open meeting rooms
     */
    public function getCourseMeetings()
    {
        $newMeetingsList = [];
        $item = [];
        $meetingsList = \Database::select(
            '*',
            $this->table,
            ['where' => [
                    'c_id = ? ' => api_get_course_int_id(),
                    ' AND session_id = ? ' => api_get_session_id(),
                    ' AND status <> ? ' => 2, // status deleted
                ],
            ]
        );
        $room = new Room();
        $room->SID = $this->sessionId;
        if (!empty($meetingsList)) {
            foreach ($meetingsList as $meetingDb) {
                //$room->rooms_id = $meetingDb['room_id'];
                error_log(__FILE__.'+'.__LINE__.' Meetings found: '.print_r($meetingDb, 1));
                $remoteMeeting = [];
                $meetingDb['created_at'] = api_get_local_time($meetingDb['created_at']);
                $meetingDb['closed_at'] = (!empty($meetingDb['closed_at']) ? api_get_local_time($meetingDb['closed_at']) : '');
                // Fixed value for now
                $meetingDb['participantCount'] = 40;
                $rec = $this->gateway->getFlvRecordingByRoomId($meetingDb['room_id']);
                $links = [];
                // Links to videos look like these:
                // http://video2.openmeetings.com:5080/openmeetings/DownloadHandler?fileName=flvRecording_4.avi&moduleName=lzRecorderApp&parentPath=&room_id=&sid=dfc0cac396d384f59242aa66e5a9bbdd
                $link = $this->url.'/DownloadHandler?fileName=%s&moduleName=lzRecorderApp&parentPath=&room_id=%s&sid=%s';
                if (!empty($rec)) {
                    $link1 = sprintf($link, $rec['fileHash'], $meetingDb['room_id'], $this->sessionId);
                    $link2 = sprintf($link, $rec['alternateDownload'], $meetingDb['room_id'], $this->sessionId);
                    $links[] = $rec['fileName'].' '.
                        \Display::url('[.flv]', $link1, ['target' => '_blank']).' '.
                        \Display::url('[.avi]', $link2, ['target' => '_blank']);
                }
                $item['show_links'] = implode('<br />', $links);

                // The following code is currently commented because the web service
                // says this is not allowed by the SOAP user.
                /*
                try {
                    // Get the conference room object from OpenMeetings server - requires SID and rooms_id to be defined
                    $objRoomId = $this->gateway->getRoomById($meetingDb['room_id']);
                    if (empty($objRoomId->return)) {
                        error_log(__FILE__.'+'.__LINE__.' Emptyyyyy ');
                        //\Database::delete($this->table, "id = {$meetingDb['id']}");
                        // Don't delete expired rooms, just mark as closed
                        \Database::update($this->table, array('status' => 0, 'closed_at' => api_get_utc_datetime()), array('id = ? ' => $meetingDb['id']));
                        continue;
                    }
                    //$objCurUs = $omServices->getRoomWithCurrentUsersById($objCurrentUsers);
                } catch  (SoapFault $e) {
                    error_log(__FILE__.'+'.__LINE__.' '.$e->faultstring);
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
                $remoteMeeting = $current_room;
                */

                if (empty($remoteMeeting)) {
                    /*
                        error_log(__FILE__.'+'.__LINE__.' Empty remote Meeting for now');
                        if ($meetingDb['status'] == 1 && $this->isTeacher()) {
                            $this->endMeeting($meetingDb['id']);
                        }
                    */
                } else {
                    $remoteMeeting['add_to_calendar_url'] = api_get_self().'?action=add_to_calendar&id='.$meetingDb['id'].'&start='.api_strtotime($meetingDb['startTime']);
                }
                $remoteMeeting['end_url'] = api_get_self().'?action=end&id='.$meetingDb['id'];
                $remoteMeeting['delete_url'] = api_get_self().'?action=delete&id='.$meetingDb['id'];

                //$record_array = array();

                //            if ($meetingDb['record'] == 1) {
                //                $recordingParams = array(
                //                    'meetingId' => $meetingDb['id'],        //-- OPTIONAL - comma separate if multiple ids
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
                //                                    $url .= Display::url(Display::return_icon('link.gif',get_lang('CopyToLinkTool')), api_get_self().'?action=copy_record_to_link_tool&id='.$meetingDb['id'].'&record_id='.$record['recordId']);
                //                                    $url .= Display::url(Display::return_icon('agenda.png',get_lang('AddToCalendar')), api_get_self().'?action=add_to_calendar&id='.$meetingDb['id'].'&start='.api_strtotime($meetingDb['created_at']).'&url='.$record['playbackFormatUrl']);
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
                //$item['created_at'] = api_convert_and_format_date($meetingDb['created_at']);
                //            //created_at
                //
                //            $item['publish_url'] = api_get_self().'?action=publish&id='.$meetingDb['id'];
                //            $item['unpublish_url'] = api_get_self().'?action=unpublish&id='.$meetingDb['id'];
                //
                //if ($meetingDb['status'] == 1) {
                //                $joinParams = array(
                //                    'meetingId' => $meetingDb['id'],        //-- REQUIRED - A unique id for the meeting
                //                    'username' => $this->user_complete_name,    //-- REQUIRED - The name that will display for the user in the meeting
                //                    'password' => $pass,            //-- REQUIRED - The attendee or moderator password, depending on what's passed here
                //                    'createTime' => '',            //-- OPTIONAL - string. Leave blank ('') unless you set this correctly.
                //                    'userID' => '',            //    -- OPTIONAL - string
                //                    'webVoiceConf' => ''    //    -- OPTIONAL - string
                //                );
                //                $returnVal = $this->setUserObjectAndGenerateRoomHashByURLAndRecFlag( array('room_id' => $meetingDb['id']) );
                //                $joinUrl = CONFIG_OPENMEETINGS_SERVER_URL . "?" .
                //                           "secureHash=" . $returnVal;
                //
                //                $item['go_url'] = $joinUrl;
                //}
                $item = array_merge($item, $meetingDb, $remoteMeeting);
                //error_log(__FILE__.'+'.__LINE__.'  Item: '.print_r($item,1));
                $newMeetingsList[] = $item;
            } //end foreach $meetingsList
        }

        return $newMeetingsList;
    }

    /**
     * Send a command to the OpenMeetings server to close the meeting.
     *
     * @param int $meetingId
     *
     * @return int
     */
    public function endMeeting($meetingId)
    {
        try {
            $room = new Room($meetingId);
            $room->SID = $this->sessionId;
            $room->room_id = intval($meetingId);
            $room->status = false;

            $urlWsdl = $this->url."/services/RoomService?wsdl";
            $ws = new \SoapClient($urlWsdl);

            $roomClosed = $ws->closeRoom($room);
            if ($roomClosed > 0) {
                \Database::update(
                    $this->table,
                    [
                        'status' => 0,
                        'closed_at' => api_get_utc_datetime(),
                    ],
                    ['id = ? ' => $meetingId]
                );
            }
        } catch (SoapFault $e) {
            error_log(__FILE__.'+'.__LINE__.' Warning: We have detected some problems: Fault: '.$e->faultstring);
            exit;

            return -1;
        }
    }

    /**
     * @param int $id
     *
     * @return int
     */
    public function deleteMeeting($id)
    {
        try {
            $room = new Room();
            $room->loadRoomId($id);
            $this->gateway->deleteRoom($room);
            \Database::update(
                $this->table,
                [
                    'status' => 2,
                ],
                ['id = ? ' => $id]
            );

            return $id;
        } catch (SoapFault $e) {
            error_log(__FILE__.'+'.__LINE__.' Warning: We have detected some problems: Fault: '.$e->faultstring);
            exit;

            return -1;
        }
    }
}
