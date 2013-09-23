<?php
include_once 'services/getSession/getSession.class.php';
include_once 'services/loginUser/loginUser.class.php';
include_once 'services/addRoomWithModerationAndExternalType/addRoomWithModerationAndExternalType.class.php';
/**
 * Open Meetings-Chamilo connector class
 */
class om_integration {

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
    function __construct() {

        // initialize video server settings from global settings
        $plugin = om_integrationPlugin::create();

        $om_plugin = $plugin->get('tool_enable');
        $om_host   = $plugin->get('host');
        $om_user   = $plugin->get('user');
        $om_pass   = $plugin->get('pass');

        $this->table = Database::get_main_table('plugin_om_meeting');
        
        if ( $om_plugin ) {
            $user_info = api_get_user_info();
            $this->user_complete_name = $user_info['complete_name'];
            $this->user = $om_user;
            $this->pass = $om_pass;
            $this->url = $om_host;

            // Setting OM api
            define('CONFIG_OMUSER_SALT', $this->user);
            define('CONFIG_OMPASS_SALT', $this->pass);
            define('CONFIG_OMSERVER_BASE_URL', $this->url);

            $this->api = new OpenMeetings();
            $this->plugin_enabled = $om_plugin;
        }
    }
    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    function is_teacher() {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }
    /**
     * Login the user with OM Server
     * @return bool True if the user is correct and false when is incorrect
     */
    function loginUser() {
      try{  
        $objGetSession = new getSession();
        $objloginUser = new loginUser();
        $urlWsdl = CONFIG_OMSERVER_BASE_URL . "/services/UserService?wsdl";
	$omServices = new SoapClient( $urlWsdl );
        //Verifying if there is already an active session
        $gsFun = $omServices->getSession($objGetSession);
        $objloginUser->SID = $this->sessionId = $gsFun->return->session_id;
        $objloginUser->username = CONFIG_OMUSER_SALT;
        $objloginUser->userpass = CONFIG_OMPASS_SALT;
        
        $luFn = $omServices->loginUser($objloginUser);
       
        if ( $luFn->return > 0 )
         return true;
       else 
         return false;
      }catch( SoapFault $e){
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
    function create_meeting( $params ) {
        //$id = Database::insert($this->table, $params);
      try{    
        $objAddRoom = new addRoomWithModerationAndExternalType();
        $roomtypes_id = $isModerated = ( $this->is_teacher() ) ? 1 : 2 ;
        $params['c_id'] = api_get_course_int_id();
        $course_name = 'COURSE_ID_' . $params['c_id'] .'_NAME_' . $params['meeting_name'];
        $urlWsdl = CONFIG_OMSERVER_BASE_URL . "/services/RoomService?wsdl";

        $params['id'] = $objAddRoom->SID = $this->sessionId;
        $objAddRoom->name = $course_name;
        $objAddRoom->roomtypes_id = $roomtypes_id;
        $objAddRoom->comment = 'Curso: ' . $params['meeting_name'] . ' </br>Plugin for Chamilo';
        $objAddRoom->numberOfPartizipants = 4;
        $objAddRoom->ispublic = true;
        $objAddRoom->appointment = false;
        $objAddRoom->isDemoRoom = false;
        $objAddRoom->demoTime = false;
        $objAddRoom->isModeratedRoom = $isModerated;
        $objAddRoom->externalRoomType = 'Chamilo';
        
        $omServices = new SoapClient( $urlWsdl );
        $adFun = $omServices->addRoomWithModerationAndExternalType( $objAddRoom );
        
        //Database::insert($this->table, $params);
        
        //if( $adFun->return > -1 )
         //   $this->join_meeting($meeting_name);
        
        return $adFun->return;
      }catch( SoapFault $e){
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
    function join_meeting($meetingid) {
        if (empty($meeting_name)) { return false; }
        $pass = $this->get_user_meeting_password();
        $meeting_data = Database::select('*', $this->table, array('where' => array('meeting_name = ? AND status = 1 ' => $meeting_name)), 'first');
        if (empty($meeting_data)) {
            if ($this->debug) error_log("meeting does not exist: $meeting_name ");
            return false;
        }

        $meeting_is_running_info = $this->api->isMeetingRunningWithXmlResponseArray($meeting_data['id']);
        $meeting_is_running = $meeting_is_running_info['running'] == 'true' ? true : false;

        if ($this->debug) error_log("meeting is running: ".$meeting_is_running);

        $params = array(
			'meetingId' => $meeting_data['id'],	//	-- REQUIRED - The unique id for the meeting
			'password' => $this->get_mod_meeting_password()		//	-- REQUIRED - The moderator password for the meeting
		);

        $meeting_info_exists = $this->get_meeting_info($params);

        if (isset($meeting_is_running) && $meeting_info_exists) {
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
     * Checks if the videoconference server is running.
     * Function currently disabled (always returns 1)
     * @return bool True if server is running, false otherwise
     * @assert () === false
     */
    function is_server_running() {
        return true;
    }
}
