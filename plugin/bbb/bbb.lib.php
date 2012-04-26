<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */

class bbb {
    var $url;
    var $salt;
    var $api;
    var $user_complete_name = null;
    var $protocol = 'http://';
    var $debug = true;
    var $logout_url = null;
    
    function __construct() {
        
        // initialize video server settings from global settings
        $settings = api_get_settings('Extra','list',api_get_current_access_url_id());
        $bbb_settings = array();
        foreach ($settings as $setting) {
            if (substr($setting['variable'],0,4)==='bbb_') {
                $bbb_settings[$setting['variable']] = $setting['selected_value'];
            }
        }
        $bbb_plugin = $bbb_settings['bbb_plugin'] === 'true';
        $bbb_host   = $bbb_settings['bbb_plugin_host'];
        $bbb_salt   = $bbb_settings['bbb_plugin_salt'];
        
        $course_code = api_get_course_id();
        
        $this->logout_url = api_get_path(WEB_COURSE_PATH).$course_code;  
        
        if ($bbb_plugin) {
            $user_info = api_get_user_info();
            $this->user_complete_name = $user_info['complete_name'];        
            $this->salt = $bbb_salt;
            $this->url  = $bbb_host.'/bigbluebutton/';        
            $this->table = Database::get_main_table('plugin_bbb_meeting');
            return true;
        }
        return false;
    }
    
    function create_meeting($params) {        
        $params['c_id'] = api_get_course_int_id();  
        $course_code = api_get_course_id();
        
        $attende_password = $params['attendee_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : api_get_course_id();
        $moderator_password = $params['moderator_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : api_get_course_id().'mod';
        
        $params['record'] = api_get_course_setting('big_blue_button_record_and_store', $course_code) == 1 ? true : false;
        $max = api_get_course_setting('big_blue_button_max_students_allowed', $course_code);
        
        $max =  isset($max) ? $max : -1;
        $params['status'] = 1;
        
        if ($this->debug) error_log("enter create_meeting ".print_r($params, 1));
        
        $params['created_at'] = api_get_utc_datetime();
        $id = Database::insert($this->table, $params);
        
        if ($id) {
            if ($this->debug) error_log("create_meeting $id ");
            
            $meeting_name       = isset($params['meeting_name']) ? $params['meeting_name'] : api_get_course_id();            
            $welcome_msg        = isset($params['welcome_msg']) ? $params['welcome_msg'] : null;
            $record             = isset($params['record']) && $params['record'] ? 'true' : 'false';
            $duration           = isset($params['duration']) ? intval($params['duration']) : 0;
            

            // ?? 
            $voiceBridge = 0;
            $metadata = array('maxParticipants' => $max);                      
            return $this->protocol.BigBlueButtonBN::createMeetingAndGetJoinURL($this->user_complete_name, $meeting_name, $id, $welcome_msg, $moderator_password, $attende_password, 
                            $this->salt, $this->url, $this->logout_url, $record, $duration, $voiceBridge, $metadata);
            
            //$id = Database::update($this->table, array('created_at' => ''));
        }
    }
    
    function is_meeting_exist($meeting_name) {
        $course_id = api_get_course_int_id();
        $meeting_data = Database::select('*', $this->table, array('where' => array('c_id = ? AND meeting_name = ? AND status = 1 ' => array($course_id, $meeting_name))), 'first');
        if ($this->debug) error_log("is_meeting_exist ".print_r($meeting_data,1));
        if (empty($meeting_data)) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * @todo implement moderator pass
     */
    function join_meeting($meeting_name) {        
        $pass = $this->get_user_metting_password();
        $meeting_data = Database::select('*', $this->table, array('where' => array('meeting_name = ? AND status = 1 ' => $meeting_name)), 'first');
        if (empty($meeting_data)) {
            if ($this->debug) error_log("meeting does not exist: $meeting_name ");
            return false;
        }        
        $meeting_is_running = BigBlueButtonBN::isMeetingRunning($meeting_data['id'], $this->url, $this->salt);
        $url = false;
        if ($this->debug) error_log("meeting is running".$meeting_is_running);
        if (isset($meeting_is_running)) {        
            $url = $this->protocol.BigBlueButtonBN::joinURL($meeting_data['id'], $this->user_complete_name, $pass, $this->salt, $this->url);        
        }
        if ($this->debug) error_log("url :".$url);
        return $url;
    }    
        
    function get_course_meetings() {
        $pass = $this->get_user_metting_password();
        $meeting_list = Database::select('*', $this->table, array('where' => array('c_id = ? ' => api_get_course_int_id())));                     
        $new_meeting_list = array();
        
        foreach ($meeting_list as $meeting) {
            $item_meeting = $meeting;
            $item_meeting['info'] = BigBlueButtonBN::getMeetingInfoArray($meeting['id'], $pass, $this->url, $this->salt);
            
            if ($meeting['info']['returncode'] == 'FAILED') {
                
            } else {
                $item_meeting['end_url'] = api_get_self().'?action=end&id='.$meeting['id'];    
            }   
            $record_array = array();
            
            if ($meeting['record'] == 1) {                
                $records =  BigBlueButtonBN::getRecordingsArray($meeting['id'], $this->url, $this->salt);                      
                
                if (!empty($records)) {
                    foreach ($records as $record) {
                    //var_dump($record);
                        if (is_array($record) && isset($record['recordID']) && isset($record['playbacks'])) {
                            foreach ($record['playbacks'] as $item) {                            
                                $url = Display::url(get_lang('ViewRecord'), $item['url'], array('target' => '_blank'));
                                //$url .= api_get_self().'?action=publish&id='.$record['recordID'];
                                $record_array[] = $url;
                            }
                        }
                    }
                }                
                $item_meeting['show_links']  = implode('<br />', $record_array);
            }
            
            $item_meeting['created_at'] = api_get_local_time($item_meeting['created_at']);            
            //created_at
            
            $item_meeting['publish_url'] = api_get_self().'?action=publish&id='.$meeting['id'];
            $item_meeting['unpublish_url'] = api_get_self().'?action=unpublish&id='.$meeting['id'];    
            
            if ($meeting['status'] == 1) {
                $item_meeting['go_url'] = $this->protocol.BigBlueButtonBN::joinURL($meeting['id'], $this->user_complete_name, $pass, $this->salt, $this->url);
            }
            $new_meeting_list[] = $item_meeting;
        }              
        return $new_meeting_list;
    }
    
    function publish_meeting($id) {
        return BigBlueButtonBN::setPublishRecordings($id, 'true', $this->url, $this->salt);
    }
    
    function unpublish_meeting($id) {
        return BigBlueButtonBN::setPublishRecordings($id, 'false', $this->url, $this->salt);
    }
    
    function end_meeting($id) {
        $pass = $this->get_user_metting_password();
        BigBlueButtonBN::endMeeting($id, $pass, $this->url, $this->salt);
        Database::update($this->table, array('status' => 0), array('id = ? ' => $id));
    }
    
    function get_user_metting_password() {
        $teacher = api_is_course_admin() || api_is_coach() || api_is_platform_admin();
        if ($teacher) {
            return api_get_course_id().'mod';
        } else {
            return api_get_course_id();
        }
    }
    
    /**
     * Get users online in the current course room 
     */
    function get_users_online_in_current_room() {
        $course_id = api_get_course_int_id();
        $meeting_data = Database::select('*', $this->table, array('where' => array('c_id = ? AND status = 1 ' => $course_id)), 'first');        
        if (empty($meeting_data)) {
            return 0;
        }        
        $pass = $this->get_user_metting_password();        
        //$meeting_is_running = BigBlueButtonBN::isMeetingRunning($meeting_data['id'], $this->url, $this->salt);
        $info = BigBlueButtonBN::getMeetingInfoArray($meeting_data['id'], $pass, $this->url, $this->salt);
        
        if (!empty($info) && isset($info['participantCount'])) {
            return $info['participantCount'];
            
        }
        return 0;
    }    
}



/**
 * Create string where we check if the meeting is running
 */
function wc_isMeetingRunningURL($myIP,$mySecuritySalt,$myMeetingID) {
    $checkAPI = "/bigbluebutton/api/isMeetingRunning?";
    $queryStr = "meetingID=".$myMeetingID;
    $checksum = sha1('isMeetingRunning'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$checkAPI.$queryStr."&checksum=".$checksum;
    return $secQueryURL;
}


/**
 * Determine if the meeting is already running (e.g. has attendees in it)
 */
function wc_isMeetingRunning($myIP,$mySecuritySalt,$myMeetingID) {
    $secQueryURL = wc_isMeetingRunningURL($myIP,$mySecuritySalt,$myMeetingID);
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false;}
    $doc = new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    $runningNode = $doc->getElementsByTagName("running");
    $isRunning = $runningNode->item(0)->nodeValue;
    return $isRunning;
}

/**
 * Create meeting if it's not already running
 */
function wc_createMeeting($myIP, $mySecuritySalt, $myMeetingName, $myMeetingID, $myAttendeePW, $myModeratorPW, $myWelcomeMsg, $myLogoutURL, $record = false, $duration = null) {
    
    $createAPI = "/bigbluebutton/api/create?";
    $myVoiceBridge = rand(70000,79999);
    
    if (isset($record)) {
        $record = 'true';
    } else {
        $record = 'false';
    }
    $duration_param = '';
    if (!empty($duration)) {
        $duration_param = '&duration='.intval($duration);
    }    
    
    $queryStr = "name=".urlencode($myMeetingName)."&meetingID=".urlencode($myMeetingID)."&attendeePW=".urlencode($myAttendeePW)."&moderatorPW=".urlencode($myModeratorPW).
                "&voiceBridge=".$myVoiceBridge."&welcome=".urlencode($myWelcomeMsg)."&logoutURL=".urlencode($myLogoutURL)."&record=".$record.$duration_param;    
    $checksum = sha1('create'.$queryStr.$mySecuritySalt);
    
    $secQueryURL = "http://".$myIP.$createAPI.$queryStr."&checksum=".$checksum;    
    $myResponse = @file_get_contents($secQueryURL);        
    if ($myResponse === false) { 
        return false;         
    }
    $doc= new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue; 
    if ($returnCode=="SUCCESS") {
      return $returnCode;
    } else {
      $messageKeyNode = $doc->getElementsByTagName("messageKey");
      $messageKey = $messageKeyNode->item(0)->nodeValue;
      return $messageKey;
    }
}


/**
 * Create a URL to join the meeting
 */
function wc_joinMeetingURL($myIP,$mySecuritySalt,$myName,$myMeetingID,$myPassword,$userID) {
    $joinAPI = "/bigbluebutton/api/join?";
    $queryStr = "fullName=".urlencode($myName)."&meetingID=".urlencode($myMeetingID)."&password=".urlencode($myPassword)."&userID=".$userID;
    $checksum = sha1('join'.$queryStr.$mySecuritySalt);
    $createStr = "http://".$myIP.$joinAPI.$queryStr."&checksum=".$checksum;
    
    return $createStr;
}

/**
 * This API is not yet supported in bigbluebutton
 */
function wc_endMeeting($myIP,$mySecuritySalt,$myMeetingID,$myModeratorPW) {
    $endAPI = "/bigbluebutton/api/end?";
    $myVoiceBridge = rand(70000,79999);
    $queryStr = "meetingID=".$myMeetingID."&moderatorPW=".$myModeratorPW."&voiceBridge=".$myVoiceBridge;
    $checksum = sha1('create'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$endAPI.$queryStr."&checksum=".$checksum;
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false; }
    $doc= new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    if ($returnCode=="SUCCESS") {
      return $returnCode;
    } else {
      $messageKeyNode = $doc->getElementsByTagName("messageKey");
      $messageKey = $messageKeyNode->item(0)->nodeValue;
      return $messageKey;
    }
}

/**
 * This API is not yet supported in bigbluebutton
 */
function wc_listAttendees() {
    return false;
}

/**
 * This API is not yet supported in bigbluebutton
 */
function wc_getMeetingInfo($myIP,$mySecuritySalt,$meetingID,$modPW) {
    $checkAPI = "/bigbluebutton/api/getMeetingInfo?";
    $queryStr = 'meetingID='.$meetingID.'&password='.$modPW;
    $checksum = sha1('getMeetingInfo'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$checkAPI.$queryStr."&checksum=".$checksum;
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false;}
    $doc = new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    $createTimeNode = $doc->getElementsByTagName("createTime");
    $createTime = $createTimeNode->item(0)->nodeValue;
    $runningNode = $doc->getElementsByTagName("running");
    $running = $runningNode->item(0)->nodeValue;
    $attendeesNode = $doc->getElementsByTagName("attendee");
    $attendees = array();
    foreach ($attendeesNode as $attendeeNode) {
        $attendee = array();
        if ($attendeeNode->childNodes->length) {
            foreach ($attendeeNode->childNodes as $i) {
                //see http://code.google.com/p/bigbluebutton/wiki/API#Get_Meeting_Info for details
                $attendee[$i->nodeName] = $i->nodeValue;
            }
        }
        $attendees[] = $attendee;
    }
    $info = array('returnCode'=>$returnCode,'createTime'=>$createTime,'attendees'=>$attendees,'running'=>$running);
    return $info;
}

/**
 * Determine the URL of the current page (for logoutURL)
 */
function wc_currentPageURL() {
  $isHTTPS = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on");
  $port = (isset($_SERVER["SERVER_PORT"]) && ((!$isHTTPS && $_SERVER["SERVER_PORT"] != "80") || ($isHTTPS && $_SERVER["SERVER_PORT"] != "443")));
  $port = ($port) ? ':'.$_SERVER["SERVER_PORT"] : '';
  $pageURL = ($isHTTPS ? 'https://' : 'http://').$_SERVER["SERVER_NAME"].$port.$_SERVER["REQUEST_URI"];
  return $pageURL;
}


/**
 * Determine the IP/Domain of the current Corporate University
 */
function wc_currentDomain() {
  $currentDomain = $_SERVER["SERVER_NAME"];
  return $currentDomain;
}


/**
 * Determine if a new version of the plug-in is available
 */
function wc_needUpgrade() {
  $returnValue = false;
  $installedVersion = "20100805";
  $availableVersion = dc_getVersion();
  if ((int)$installedVersion < (int)$availableVersion) {
      $returnValue = true;
  }
  return $returnValue;
}

/**
 * Gets a list of all meetings currently running 
 */
function wc_getRunningMeetings($myIP,$mySecuritySalt) {
    $checkAPI = "/bigbluebutton/api/getMeetings?";
    $queryStr = '';
    $checksum = sha1('getMeetings'.$queryStr.$mySecuritySalt);
    $secQueryURL = "http://".$myIP.$checkAPI.$queryStr."&checksum=".$checksum;
    $myResponse = @file_get_contents($secQueryURL);
    if ($myResponse === false) { return false;}
    $doc = new DOMDocument();
    $doc->loadXML($myResponse);
    $returnCodeNode = $doc->getElementsByTagName("returncode");
    $returnCode = $returnCodeNode->item(0)->nodeValue;
    $meetingsNode = $doc->getElementsByTagName("meeting");
    $meetings = array();
    foreach ($meetingsNode as $meetingNode) {
        $meeting = array();
        if ($meetingNode->childNodes->length) {
            foreach ($meetingNode->childNodes as $i) {
                //see http://code.google.com/p/bigbluebutton/wiki/API#Get_Meetings for details
                $meeting[$i->nodeName] = $i->nodeValue;
            }
        }
        $meetings[] = $meeting;
    }
    return $meetings;
}

function wc_getRecordingsURL($meetingID, $URL, $SALT) {
    $base_url_record = $URL."api/getRecordings?";
    $params = "meetingID=".urlencode($meetingID);

    return ($base_url_record.$params."&checksum=".sha1("getRecordings".$params.$SALT) );
}


    /**
	*This method calls getMeetings on the bigbluebuttonbn server, then calls getMeetingInfo for each meeting and concatenates the result.
	*
	*@param URL -- the url of the bigbluebuttonbn server
	*@param SALT -- the security salt of the bigbluebuttonbn server
	*
	*@return 
	*	- Null if the server is unreachable
	*	- If FAILED then returns an array containing a returncode, messageKey, message.
	*	- If SUCCESS then returns an array of all the meetings. Each element in the array is an array containing a meetingID, 
		  moderatorPW, attendeePW, hasBeenForciblyEnded, running.
	*/
	function wc_getRecordingsArray($meetingID, $URL, $SALT ) {
        $xml = _wrap_simplexml_load_file(wc_getRecordingsURL( $meetingID, $URL, $SALT));
        
        if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {
              ////The meetings were returned
            return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
        } else if($xml && $xml->returncode == 'SUCCESS') { //If there were meetings already created
            $recordings = array();			
            foreach ($xml->recordings->recording as $recording) {
                $recordings[(string) $recording->recordID] = array( 'recordID' => (string) $recording->recordID, 'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name, 'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime, 'endTime' => (string) $recording->endTime );
                $recordings[(string) $recording->recordID]['playbacks'] = array();
                foreach ( $recording->playback->format as $format ){
                    $recordings[(string) $recording->recordID]['playbacks'][(string) $format->type] = array( 'type' => (string) $format->type, 'url' => (string) $format->url );
                }
                // THIS IS FOR TESTING MULTIPLE FORMATS, DO REMOVE IT FOR FINAL RELEASE
                //$recordings[(string) $recording->recordID]['playbacks']['desktop'] = array( 'type' => 'desktop', 'url' => (string) $recording->playback->format->url );

                //Add the metadata to the recordings array
                $metadata = get_object_vars($recording->metadata);
                while ($data = current($metadata)) {
                    $recordings[(string) $recording->recordID]['meta_'.key($metadata)] = $data;
                    next($metadata);
                }
            }                
            ksort($recordings);
            return $recordings;
        } else if( $xml ) { //If the xml packet returned failure it displays the message to the user
            return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
        } else { //If the server is unreachable, then prompts the user of the necessary action
            return NULL;
        }
	}

    
 function _wrap_simplexml_load_file($url){	
    if (extension_loaded('curl')) {
        $ch = curl_init() or die ( curl_error() );
        $timeout = 10;
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);	
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec( $ch );
        curl_close( $ch );

        if($data)
            return (new SimpleXMLElement($data,LIBXML_NOCDATA));
        else
            return false;
    }
    return (simplexml_load_file($url,'SimpleXMLElement', LIBXML_NOCDATA));
}


 function getMeetingsURL($URL, $SALT) { 
		$base_url = $URL."api/getMeetings?";
		$params = '';
		return ( $base_url.$params.'&checksum='.sha1("getMeetings".$params.$SALT));
}


/**
*This method calls getMeetings on the bigbluebuttonbn server, then calls getMeetingInfo for each meeting and concatenates the result.
*
*@param URL -- the url of the bigbluebuttonbn server
*@param SALT -- the security salt of the bigbluebuttonbn server
*
*@return 
*	- Null if the server is unreachable
*	- If FAILED then returns an array containing a returncode, messageKey, message.
*	- If SUCCESS then returns an array of all the meetings. Each element in the array is an array containing a meetingID, 
        moderatorPW, attendeePW, hasBeenForciblyEnded, running.
*/
function getMeetingsArray( $URL, $SALT ) {

    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }
    else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created

        foreach ($xml->meetings->meeting as $meeting)
        {
            $meetings[] = array( 'meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running );
        }
        return $meetings;

    }
    else if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }
    else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}