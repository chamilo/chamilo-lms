<?php
/* See license terms in /license.txt */
/**
* EVENTS LIBRARY
*
* This is the events library for Chamilo.
* Include/require it in your code to use its functionality.
* Functions of this library are used to record informations when some kind
* of event occur. Each event has his own types of informations then each event
* use its own function.
*
* @package chamilo.library
*/
/*	   INIT SECTION */

$TABLETRACK_LOGIN           = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
$TABLETRACK_OPEN 		    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_OPEN);
$TABLETRACK_ACCESS          = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$course_tracking_table		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
$TABLETRACK_DOWNLOADS	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_UPLOADS         = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
$TABLETRACK_LINKS 		    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_EXERCICES 	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TABLETRACK_LASTACCESS 	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS); //for "what's new" notification
$TABLETRACK_DEFAULT         = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);


/* FUNCTIONS */
/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for open event (when homepage is opened)
 */
function event_open() {
	global $_configuration;
	global $TABLETRACK_OPEN;

	// @getHostByAddr($_SERVER['REMOTE_ADDR']) : will provide host and country information
	// $_SERVER['HTTP_USER_AGENT'] :  will provide browser and os information
	// $_SERVER['HTTP_REFERER'] : provide information about refering url
	if(isset($_SERVER['HTT_REFERER']))
	{
		$referer = Database::escape_string($_SERVER['HTTP_REFERER']);
	} else {
		$referer = '';
	}
	// record informations only if user comes from another site
	//if(!eregi($_configuration['root_web'],$referer))
	$pos = strpos($referer, $_configuration['root_web']);
	if ($pos === false && $referer != '') {
		$remhost = @ getHostByAddr($_SERVER['REMOTE_ADDR']);
		if ($remhost == $_SERVER['REMOTE_ADDR'])
			$remhost = "Unknown"; // don't change this
		$reallyNow = api_get_utc_datetime();
		$sql = "INSERT INTO ".$TABLETRACK_OPEN."
				(open_remote_host,
				 open_agent,
				 open_referer,
				 open_date)
				VALUES
				('".$remhost."',
				 '".Database::escape_string($_SERVER['HTTP_USER_AGENT'])."', '".Database::escape_string($referer)."', '$reallyNow')";
		$res = Database::query($sql);
	}
	return 1;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for login event
 * (when an user identifies himself with username & password)
 */
function event_login() {	
	
	global $_user;
	global $TABLETRACK_LOGIN;

	$reallyNow = api_get_utc_datetime();
	$sql = "INSERT INTO ".$TABLETRACK_LOGIN." (login_user_id, login_ip, login_date, logout_date)
		VALUES	('".$_user['user_id']."',
				'".Database::escape_string($_SERVER['REMOTE_ADDR'])."',
				'".$reallyNow."',
				'".$reallyNow."'		 
				)";
	$res = Database::query($sql);
	// autoSubscribe
	$user_status = $_user['status'];
	$user_status = $_user['status']  == SESSIONADMIN ? 'sessionadmin' : 
		$_user['status'] == COURSEMANAGER ? 'teacher' :
		$_user['status'] == DRH ? 'DRH' :
		'student';
	$autoSubscribe = api_get_setting($user_status.'_autosubscribe');
	if ($autoSubscribe) {
		$autoSubscribe = explode('|', $autoSubscribe);
		foreach ($autoSubscribe as $code) {
			if (CourseManager::course_exists($code)) { 
				CourseManager::subscribe_user($_user['user_id'], $code);
			}
		}
	}
}

/**
 * @param tool name of the tool (name in mainDb.accueil table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for access event for courses
 */
function event_access_course() {
	global $_user, $TABLETRACK_ACCESS,  $TABLETRACK_LASTACCESS;
	
	$id_session = api_get_session_id();	
	$now        = api_get_utc_datetime();    
    $_cid       = api_get_course_id();    
    
	if ($_user['user_id']) {
		$user_id = "'".$_user['user_id']."'";
	} else {
		$user_id = "0"; // no one
	}
	$sql = "INSERT INTO ".$TABLETRACK_ACCESS."  (access_user_id, access_cours_code, access_date, access_session_id) VALUES
		    (".$user_id.", '".$_cid."', '".$now."','".$id_session."')";
	$res = Database::query($sql);
	
	// added for "what's new" notification
	$sql = "UPDATE $TABLETRACK_LASTACCESS  SET access_date = '$now' 
			WHERE access_user_id = $user_id AND access_cours_code = '$_cid' AND access_tool IS NULL AND access_session_id=".$id_session;
	$res = Database::query($sql);
    
	if (Database::affected_rows() == 0) {
		$sql = "INSERT INTO $TABLETRACK_LASTACCESS (access_user_id, access_cours_code, access_date, access_session_id)
				VALUES (".$user_id.", '".$_cid."', '$now', '".$id_session."')";
		$res = Database::query($sql);
	}
	// end "what's new" notification	
    return 1;
}

/**
 * @param tool name of the tool (name in mainDb.accueil table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for access event for tools
 *
 *  $tool can take this values :
 *  Links, Calendar, Document, Announcements,
 *  Group, Video, Works, Users, Exercices, Course Desc
 *  ...
 *  Values can be added if new modules are created (15char max)
 *  I encourage to use $nameTool as $tool when calling this function
 *
 * 	Functionality for "what's new" notification is added by Toon Van Hoecke
 */
function event_access_tool($tool, $id_session=0) {
	global $_configuration;
	global $_user;
	global $_cid;
	global $TABLETRACK_ACCESS;	
	global $_course;
	global $TABLETRACK_LASTACCESS; //for "what's new" notification
	
	$id_session    = api_get_session_id();
	$tool          = Database::escape_string($tool);	
	$reallyNow     = api_get_utc_datetime();
	$user_id       = $_user['user_id'] ? "'".$_user['user_id']."'" : "0"; // no one
	// record information
	// only if user comes from the course $_cid
	//if( eregi($_configuration['root_web'].$_cid,$_SERVER['HTTP_REFERER'] ) )
	//$pos = strpos($_SERVER['HTTP_REFERER'],$_configuration['root_web'].$_cid);
	
	$pos = isset($_SERVER['HTTP_REFERER']) ? strpos(strtolower($_SERVER['HTTP_REFERER']), strtolower(api_get_path(WEB_COURSE_PATH).$_course['path'])) : false;
	// added for "what's new" notification
	$pos2 = isset($_SERVER['HTTP_REFERER']) ? strpos(strtolower($_SERVER['HTTP_REFERER']), strtolower($_configuration['root_web']."index")) : false;
	// end "what's new" notification
	if ($pos !== false || $pos2 !== false) {		
		$sql = "INSERT INTO ".$TABLETRACK_ACCESS."
					(access_user_id,
					 access_cours_code,
					 access_tool,
					 access_date,
					 access_session_id
					 )
				VALUES
					(".$user_id.",".// Don't add ' ' around value, it's already done.
					"'".$_cid."' ,
					'".$tool."',
					'".$reallyNow."', 
					'".$id_session."')";
		$res = Database::query($sql);
	}
	// "what's new" notification
	$sql = "UPDATE $TABLETRACK_LASTACCESS
			SET access_date = '$reallyNow'
			WHERE access_user_id = ".$user_id." AND access_cours_code = '".$_cid."' AND access_tool = '".$tool."' AND access_session_id=".$id_session;
	$res = Database::query($sql);
	if (Database::affected_rows() == 0) {
		$sql = "INSERT INTO $TABLETRACK_LASTACCESS (access_user_id,access_cours_code,access_tool, access_date, access_session_id)
				VALUES (".$user_id.", '".$_cid."' , '$tool', '$reallyNow', $id_session)";
		$res = Database::query($sql);
	}
	return 1;
}

/**
 * @param doc_id id of document (id in mainDb.document table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for download event
 * (when an user click to d/l a document)
 * it will be used in a redirection page
 * bug fixed: Roan Embrechts
 * Roan:
 * The user id is put in single quotes,
 * (why? perhaps to prevent sql insertion hacks?)
 * and later again.
 * Doing this twice causes an error, I remove one of them.
 */
function event_download($doc_url) {	
	$tbl_stats_downloads = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);	
    $doc_url = Database::escape_string($doc_url);

	$reallyNow = api_get_utc_datetime();
	$user_id = "'".api_get_user_id()."'";
	$_cid = api_get_course_id();
	
	$sql = "INSERT INTO $tbl_stats_downloads (
				 down_user_id,
				 down_cours_id,
				 down_doc_path,
				 down_date,
				 down_session_id
				)
				VALUES (
				 ".$user_id.",
				 '".$_cid."',
				 '".$doc_url."',
				 '".$reallyNow."',
				 '".api_get_session_id()."'
				)";
	$res = Database::query($sql);
	return 1;
}

/**
 * @param doc_id id of document (id in mainDb.document table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for upload event
 * used in the works tool to record informations when
 * an user upload 1 work
 */
function event_upload($doc_id) {	
	global $_user;
	global $_cid;
	global $TABLETRACK_UPLOADS;
	
	$reallyNow = api_get_utc_datetime();
	if (isset($_user['user_id']) && $_user['user_id']!='') {
		$user_id = "'".$_user['user_id']."'";
	} else {
		$user_id = "0"; // anonymous
	}
	$sql = "INSERT INTO ".$TABLETRACK_UPLOADS."
				( upload_user_id,
				  upload_cours_id,
				  upload_work_id,
				  upload_date,
				  upload_session_id
				)
				VALUES (
				 ".$user_id.",
				 '".$_cid."',
				 '".$doc_id."',
				 '".$reallyNow."',
				 '".api_get_session_id()."'
				)";
	$res = Database::query($sql);
	return 1;
}

/**
 * @param link_id (id in coursDb liens table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for link event (when an user click on an added link)
 * it will be used in a redirection page
*/
function event_link($link_id) {
	global $_user, $TABLETRACK_LINKS;
	$reallyNow = api_get_utc_datetime();
	if (isset($_user['user_id']) && $_user['user_id']!='') {
		$user_id = "'".Database::escape_string($_user['user_id'])."'";
	} else {
		// anonymous
		$user_id = "0";
	}
	$sql = "INSERT INTO ".$TABLETRACK_LINKS."
				( links_user_id,
				 links_cours_id,
				 links_link_id,
				 links_date,
				 links_session_id
				) VALUES (
				 ".$user_id.",
				 '".api_get_course_id()."',
				 '".Database::escape_string($link_id)."',
				 '".$reallyNow."',
				 '".api_get_session_id()."'
				)";
	$res = Database::query($sql);
	return 1;
}

/**
 * Update the TRACK_E_EXERCICES exercises
 * 
 * @param int 	exeid 	id of the attempt
 * @param int	exo_id 	exercise id
 * @param mixed	result 	score 
 * @param int	weighting ( higher score )
 * @param int	duration ( duration of the attempt, in seconds )
 * @param int	session_id
 * @param int	learnpath_id (id of the learnpath)
 * @param int	learnpath_item_id (id of the learnpath_item)
 * 
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @author Julio Montoya Armas <gugli100@gmail.com> Reworked 2010
 * @desc Record result of user when an exercice was done 
*/
function update_event_exercice($exeid, $exo_id, $score, $weighting,$session_id,$learnpath_id=0, $learnpath_item_id=0, $learnpath_item_view_id = 0, $duration, $question_list, $status = '', $remind_list = array()) {
	require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
    if ($exeid != '') {
		// Validation in case of fraud with actived control time
		if (!exercise_time_control_is_valid($exo_id)) {
			$score = 0;		
	    }
	                  
        /*  start_date wouldn't be updated
        $start_date_condition = '';        
	    //Validation in case of wrong start_date
	    if (isset($_SESSION['exercice_start_date'])) {
	    	$start_date = $_SESSION['exercice_start_date'];
	    	$diff  = abs($start_date - $now);
	    	if ($diff > 14400) { // 14400 = 4h*60*60 more than 4h of diff
	    		$start_date = $now - 1800; //	Now - 30min
	    	}            
	    }*/
	    
	    if (!isset($status) || empty($status)) {
	    	$status = '';
	    } else {
	    	$status = Database::escape_string($status);
	    }

		$TABLETRACK_EXERCICES = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
		$question_list = array_map('intval', $question_list);
		
		if (!empty($remind_list)) {
			$remind_list = array_map('intval', $remind_list);
			$remind_list = array_filter($remind_list);
			$remind_list = implode(",", $remind_list);
		} else {
			$remind_list = '';
		}		

		$sql = "UPDATE $TABLETRACK_EXERCICES SET
				   exe_exo_id 			= '".Database::escape_string($exo_id)."',
				   exe_result			= '".Database::escape_string($score)."',
				   exe_weighting 		= '".Database::escape_string($weighting)."',
				   session_id			= '".Database::escape_string($session_id)."',
				   orig_lp_id 			= '".Database::escape_string($learnpath_id)."',
				   orig_lp_item_id 		= '".Database::escape_string($learnpath_item_id)."',
                   orig_lp_item_view_id = '".Database::escape_string($learnpath_item_view_id)."',
				   exe_duration 		= '".Database::escape_string($duration)."',
				   exe_date				= '".api_get_utc_datetime()."',
				   status 				= '".$status."',
				   questions_to_check 	= '".$remind_list."',				   				   
				   data_tracking    	= '".implode(',', $question_list)."'
				 WHERE exe_id = '".Database::escape_string($exeid)."'";
		$res = Database::query($sql);
        
        if ($debug) error_log('update_event_exercice called ');
        if ($debug) error_log("$sql");
        
        //Deleting control time session track		
		//exercise_time_control_delete($exo_id);
		return $res;        
	} else {
		return false;
	}
}

/**
 * This function creates an empty Exercise in STATISTIC_TRACK_E_EXERCICES table.
 * After that in exercise_result.php we call the update_event_exercice() to update the exercise
 * @return $id the last id registered, or false on error
 * @author Julio Montoya <gugli100@gmail.com>
 * @desc Record result of user when an exercice was done
*/
function create_event_exercice($exo_id) {	
    if (empty($exo_id) or (intval($exo_id)!=$exo_id)) { return false; }
    //error_log('create_event_exercice');
	$tbl_track_exe = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$tbl_exe = Database::get_course_table(TABLE_QUIZ_TEST);
	$now = api_get_utc_datetime();
	$uid = api_get_user_id();
	
	$course_id = api_get_course_int_id();

    // First, check the exercise exists
    $sql_exe_id="SELECT exercises.id FROM $tbl_exe as exercises WHERE c_id = $course_id AND exercises.id=$exo_id";
    $res_exe_id=Database::query($sql_exe_id);
    if ($res_exe_id === false) { return false; } //sql error
    if (Database::num_rows($res_exe_id)<1) { return false;} //exe not found
    $row_exe_id=Database::fetch_row($res_exe_id);
    $exercise_id = intval($row_exe_id[0]);
    // Second, check if the record exists in the database (looking for incomplete records)
    $sql = "SELECT exe_id FROM $tbl_track_exe ";
    $condition = " WHERE exe_exo_id =   $exo_id AND " .
				"exe_user_id =  $uid AND " .
				"exe_cours_id = '".api_get_course_id()."' AND " .
				"status = 'incomplete' AND ".
				"session_id = ".api_get_session_id();
    $res = Database::query($sql.$condition);
    if ($res === false) {return false;}
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_array($res);
        return $row['exe_id'];
    }

	// No record was found, so create one
	// get expire time to insert into the tracking record
	require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
	$current_expired_time_key = get_time_control_key($exercise_id);
    if (isset($_SESSION['expired_time'][$current_expired_time_key])) { //Only for exercice of type "One page"
    	$expired_date = $_SESSION['expired_time'][$current_expired_time_key];
    } else {
    	$expired_date = '0000-00-00 00:00:00';
    }
	$sql = "INSERT INTO $tbl_track_exe ( exe_user_id, exe_cours_id, expired_time_control, exe_exo_id, session_id)
			VALUES (  $uid,  '".api_get_course_id()."' ,'$expired_date','$exo_id','".api_get_session_id()."')";
	$res = Database::query($sql);
	$id= Database::insert_id();
	return $id;
}

/**
 * Record an event for this attempt at answering an exercise
 * @param	float	Score achieved
 * @param	string	Answer given
 * @param	integer	Question ID
 * @param	integer Exercise ID
 * @param	integer	Position
 * @return	boolean	Result of the insert query
 */
function exercise_attempt($score, $answer, $question_id, $exe_id, $position, $exercise_id = 0, $nano = null) {	
    require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
    global $debug;
	$score          = Database::escape_string($score);
	$answer         = Database::escape_string($answer);
	$question_id    = Database::escape_string($question_id);
	$exe_id         = Database::escape_string($exe_id);
	$position 		= Database::escape_string($position);
    $now            = api_get_utc_datetime();
	$user_id        = api_get_user_id();
		
	$TBL_TRACK_ATTEMPT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    
    if ($debug) error_log("----- entering exercise_attempt function ------");
    
    if ($debug) error_log("answer: $answer");
    if ($debug) error_log("score: $score");
    if ($debug) error_log("question_id : $question_id");
    if ($debug) error_log("position: $position");

	//Validation in case of fraud with actived control time	    	
    if (!exercise_time_control_is_valid($exercise_id)) {
        if ($debug) error_log("exercise_time_control_is_valid is false");
    	$score = 0;
    	$answer = 0;    	
	}
	
    
	if (!empty($user_id)) {
		$user_id = "'".$user_id."'";
	} else {
		// anonymous
		$user_id = api_get_anonymous_id();
	}		
    
	$file = '';	
	if (isset($nano)) {	
		$file = Database::escape_string(basename($nano->load_filename_if_exists(false)));
	}
    
	$sql = "INSERT INTO $TBL_TRACK_ATTEMPT (exe_id, user_id, question_id, answer, marks, course_code, session_id, position, tms, filename)
			  VALUES (
			  ".$exe_id.",
			  ".$user_id.",
			   '".$question_id."',
			   '".$answer."',
			   '".$score."',
			   '".api_get_course_id()."',
			   '".api_get_session_id()."',
			   '".$position."',
			   '".$now."',
			   '".$file."'
			)";		
    
    if ($debug) error_log("Saving question attempt: ");
    if ($debug) error_log($sql);
    if ($debug) error_log("");
    
	if (!empty($question_id) && !empty($exe_id) && !empty($user_id)) {
		$res = Database::query($sql);		
		if (defined('ENABLED_LIVE_EXERCISE_TRACKING')){
			$recording_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
            if ($debug) error_log("Saving e attempt recording ");
			$recording_changes = "INSERT INTO $recording_table (exe_id, question_id, marks, insert_date, author, session_id) VALUES ('$exe_id','$question_id','$score','".api_get_utc_datetime()."','', '".api_get_session_id()."') ";
			Database::query($recording_changes);			
		}		
		return $res;
	} else {
		return false;
	}
}

/**
 * Record an hotspot spot for this attempt at answering an hotspot question
 * @param	int		Exercise ID
 * @param	int		Question ID
 * @param	int		Answer ID
 * @param	int		Whether this answer is correct (1) or not (0)
 * @param	string	Coordinates of this point (e.g. 123;324)
 * @return	boolean	Result of the insert query
 * @uses Course code and user_id from global scope $_cid and $_user
 */
function exercise_attempt_hotspot($exe_id, $question_id, $answer_id, $correct, $coords, $exerciseId = 0) {
    require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
	//Validation in case of fraud  with actived control time
    if (!exercise_time_control_is_valid($exerciseId)) {	
	    $correct = 0;	    
    }

	$tbl_track_e_hotspot = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
	$sql = "INSERT INTO $tbl_track_e_hotspot (hotspot_user_id, hotspot_course_code, hotspot_exe_id, hotspot_question_id, hotspot_answer_id, hotspot_correct, hotspot_coordinate)".
			" VALUES ('" . api_get_user_id() . "'," .
			" '" . api_get_course_id() . "', " .
			" '" . Database :: escape_string($exe_id) . "', " .
			" '" . Database :: escape_string($question_id) . "'," .
			" '" . Database :: escape_string($answer_id) . "'," .
			" '" . Database :: escape_string($correct) . "'," .
			" '" . Database :: escape_string($coords) . "')";
	return $result = Database::query($sql);
}

/**
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * @desc Record information for common (or admin) events (in the track_e_default table)
 * @param	string	Type of event
 * @param	string	Type of value
 * @param	string	Value
 * @param	string	Timestamp (defaults to null)
 * @param	integer	User ID (defaults to null)
 * @param	string	Course code (defaults to null)
 */
function event_system($event_type, $event_value_type, $event_value, $datetime = null, $user_id = null, $course_code = null) {	
	global $TABLETRACK_DEFAULT;

	$event_type         = Database::escape_string($event_type);
	$event_value_type   = Database::escape_string($event_value_type);
    
    //Clean the user_info
    if ($event_value_type == LOG_USER_OBJECT) {
        if (is_array($event_value)) {
            unset($event_value['complete_name']);
            unset($event_value['firstName']);
            unset($event_value['lastName']);
            unset($event_value['avatar_small']);
            unset($event_value['avatar']);
            unset($event_value['password']);
            unset($event_value['lastLogin']);  
            unset($event_value['picture_uri']);              
            $event_value = serialize($event_value);
        }
    }
    
	$event_value        = Database::escape_string($event_value);		        
    $course_info        = api_get_course_info($course_code);
    
    if (!empty($course_info)) {
        $course_id      = $course_info['real_id'];
        $course_code    = $course_info['code'];        
        $course_code    = Database::escape_string($course_code);
    } else {
        $course_id = null;
        $course_code = null;
    }
    
	if (!isset($datetime)) {
		$datetime = api_get_utc_datetime();
	}
            
    $datetime           = Database::escape_string($datetime);
    
	if (!isset($user_id)) {
		$user_id = api_get_user_id();
	}
    
    $user_id = intval($user_id);
    
	$sql = "INSERT INTO $TABLETRACK_DEFAULT
				(default_user_id,
				 default_cours_code,
                 c_id,
				 default_date,
				 default_event_type,
				 default_value_type,
				 default_value
				 )
				 VALUES('$user_id.',
					'$course_code',
                    '$course_id',
					'$datetime',
					'$event_type',
					'$event_value_type',
					'$event_value')";
	$res = Database::query($sql);
	
	//Sending notifications to users @todo check this
    $send_event_setting = api_get_setting('activate_send_event_by_mail');
    if (!empty($send_event_setting) && $send_event_setting == 'true') {
        global $language_file;

        //prepare message
        list($message, $subject) = get_event_message_and_subject($event_type);
        $mail_body=$message;
        if (is_array($notification_infos)) {
            foreach ($notification_infos as $variable => $value) {
                $mail_body = str_replace('%'.$variable.'%',$value,$mail_body);
            }
        }

        //prepare mail common variables
        if (empty($subject)) {
            $subject = $event_type;
        }
        $mail_subject = '['.api_get_setting('siteName').'] '.$subject;
        $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
        $email_admin = api_get_setting('emailAdministrator');
        $emailfromaddr = api_get_setting('emailAdministrator');
        $emailfromname = api_get_setting('siteName');

        //Send mail to all subscribed users
        $users_arr = get_users_subscribed_to_event($event_type);
        foreach ($users_arr as $user) {
            $recipient_name = api_get_person_name($user['firstname'], $user['lastname']);
            $email = $user['email'];
            @api_mail($recipient_name, $email, $mail_subject, $mail_body, $sender_name, $email_admin);
        }
    }
	return true;
}

/**
 * Get the message and the subject for a event
 *
 * @param string event's name
 */
function get_event_message_and_subject($event_name){
    $event_name = Database::escape_string($event_name);
    $sql = 'SELECT m.message, m.subject FROM '.Database::get_main_table(TABLE_MAIN_EVENT_TYPE).' e,'
		.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_MESSAGE).' m
		WHERE m.event_type_id = e.id '.
		"AND e.name = '$event_name'";

    $res = Database::store_result(Database::query($sql),'ASSOC');
    
    $ret = array();
	
    if ( isset($res[0]['message']) ) {
        $ret[0] = $res[0]['message'];
    } else {
	$ret[0] = '';
    }
	
    if ( isset($res[0]['subject']) ) {
	$ret[1] = $res[0]['subject'];
    } else {
	$ret[1] = '';
    }
	
    return $ret;
}

/**
 * Get every email stored in the database
 *
 * @param int $etId
 * @return type 
 */
function get_all_event_types() 
{
    global $event_config;
	
    $sql = 'SELECT etm.id, event_type_name, activated, language_id, message, subject, dokeos_folder 
            FROM '.Database::get_main_table(TABLE_MAIN_EVENT_EMAIL_TEMPLATE).' etm 
            INNER JOIN '.Database::get_main_table(TABLE_MAIN_LANGUAGE).' l 
            ON etm.language_id = l.id;
            ';
	
    $events_types = Database::store_result(Database::query($sql),'ASSOC');
	// echo $sql;
    $to_return = array(); 
    foreach ($events_types as $et) {
        $et['nameLangVar'] = get_lang($event_config[$et["event_type_name"]]["name_lang_var"]);
	$et['descLangVar'] = get_lang($event_config[$et["event_type_name"]]["desc_lang_var"]);
	$to_return[] = $et;
    }
    return $to_return;
}

/**
 * Get users linked to an event
 *
 * @param int $etId
 * @return type 
 */
function get_users_subscribed_to_event($event_name){
    $event_name = Database::escape_string($event_name);
    $sql = 'SELECT u.* FROM '. Database::get_main_table(TABLE_MAIN_USER).' u,'
						.Database::get_main_table(TABLE_MAIN_EVENT_TYPE).' e,'
						.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_REL_USER).' ue
			WHERE ue.user_id = u.user_id
			AND e.name = \''.$event_name.'\'
			AND e.id = ue.event_type_id';

    return Database::store_result(Database::query($sql),'ASSOC');
}

/**
 * Get the users related to one event
 *
 * @param string $event_name
 */
function get_event_users($event_name) 
{
    $sql = 'SELECT user.* FROM '.Database::get_main_table(TABLE_MAIN_USER).' user
	JOIN '.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_REL_USER).' relUser ON relUser.user_id = user.user_id
	WHERE relUser.event_type_name = "'.$event_name.'"
	';
	
    $events_types = Database::store_result(Database::query($sql),'ASSOC');
	
	return $events_types;
}

/**
 * Save the new message for one event and for one language
 *
 * @param string $eventName
 * @param array $users
 * @param string $message
 * @param string $subject
 * @param string $eventMessageLanguage 
 * @param int $activated 
 */
function save_event_type_message($event_name,$users,$message,$subject, $event_message_language, $activated) 
{
    // Deletes then re-adds the users linked to the event
    $sql = 'DELETE FROM '.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_REL_USER).'
	WHERE event_type_name = "'.$event_name.'"
	';
    Database::query($sql);
	
    foreach ($users as $user) {
        $sql = 'INSERT INTO '.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_REL_USER).'
		(user_id,event_type_name)
		VALUES('.intval($user).',"'.  Database::escape_string($event_name).'")
		';
	Database::query($sql);
    }
	
    // check if this template in this language already exists or not
    $sql = 'SELECT COUNT(id) as total FROM '.Database::get_main_table(TABLE_MAIN_EVENT_EMAIL_TEMPLATE).'
		WHERE event_type_name = "'.$event_name.'" AND language_id = (SELECT id FROM '.Database::get_main_table(TABLE_MAIN_LANGUAGE).' 
                    WHERE dokeos_folder = "'.$event_message_language.'")
                ';
    $sql = Database::store_result(Database::query($sql),'ASSOC');
                
    // if already exists, we update
    if ($sql[0]["total"] > 0) {
        $sql = 'UPDATE '.Database::get_main_table(TABLE_MAIN_EVENT_EMAIL_TEMPLATE).'
            SET message = "'.Database::escape_string($message).'",
            subject = "'.Database::escape_string($subject).'", 
            activated = '.$activated.'
            WHERE event_type_name = "'.Database::escape_string($event_name).'" AND language_id = (SELECT id FROM '.Database::get_main_table(TABLE_MAIN_LANGUAGE).' 
                WHERE dokeos_folder = "'.$event_message_language.'")
            ';
        Database::query($sql);
    } else { // else we create a new record
        // gets the language_-_id
        $lang_id = '(SELECT id FROM '.Database::get_main_table(TABLE_MAIN_LANGUAGE).' 
                WHERE dokeos_folder = "'.$event_message_language.'")';
        $lang_id = Database::store_result(Database::query($lang_id),'ASSOC');
            
        $sql = 'INSERT INTO '.Database::get_main_table(TABLE_MAIN_EVENT_EMAIL_TEMPLATE).'
            (event_type_name, language_id, message, subject, activated) 
            VALUES("'.Database::escape_string($event_name).'", '.$lang_id[0]["id"].', "'.Database::escape_string($message).'",
            "'.Database::escape_string($subject).'", '.$activated.')
            ';
        Database::query($sql);
    }
        
    // set activated at every save
    $sql = 'UPDATE '.Database::get_main_table(TABLE_MAIN_EVENT_EMAIL_TEMPLATE).'
                SET activated = '.$activated.'
                WHERE event_type_name = "'.Database::escape_string($event_name).'"
                ';
    Database::query($sql);
}

function eventType_mod($etId,$users,$message,$subject) {
	$etId = intval($etId);
	
	$sql = 'DELETE FROM '.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_REL_USER).'
	WHERE event_type_id = '.$etId.'
	';
	
	Database::query($sql);
	
	foreach($users as $user) {
		$sql = 'INSERT INTO '.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_REL_USER).'
		(user_id,event_type_id)
		VALUES('.intval($user).','.$etId.')
		';
		
		Database::query($sql);
	}
	
	$sql = 'UPDATE '.Database::get_main_table(TABLE_MAIN_EVENT_TYPE_MESSAGE).'
	SET message = "'.Database::escape_string($message).'",
	subject = "'.Database::escape_string($subject).'"
	WHERE event_type_id = '.$etId.'
	';
	
	
	Database::query($sql);
}


/**
 * Gets the last attempt of an exercise based in the exe_id
 */
function get_last_attempt_date_of_exercise($exe_id) {
	$exe_id = intval($exe_id);
	$track_attempts 		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$track_exercises 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	
	$sql_track_attempt 		= 'SELECT max(tms) as last_attempt_date FROM '.$track_attempts.' WHERE exe_id='.$exe_id;
		 	       
	$rs_last_attempt 		= Database::query($sql_track_attempt);	
	$row_last_attempt 		= Database::fetch_array($rs_last_attempt);	
	$last_attempt_date 		= $row_last_attempt['last_attempt_date'];//Get the date of last attempt
	return $last_attempt_date;
}

/**
 * Gets how many attempts exists by user, exercise, learning path
 * @param   int user id
 * @param   int exercise id
 * @param   int lp id
 * @param   int lp item id
 * @param   int lp item view id
 */
function get_attempt_count($user_id, $exerciseId, $lp_id, $lp_item_id,$lp_item_view_id) {
	$stat_table 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$user_id 		= intval($user_id);
	$exerciseId 	= intval($exerciseId);
	$lp_id 			= intval($lp_id);
	$lp_item_id 	= intval($lp_item_id);
    $lp_item_view_id= intval($lp_item_view_id);
	
    $sql = "SELECT count(*) as count FROM $stat_table WHERE 	
   				exe_exo_id 				= $exerciseId AND 
   				exe_user_id 			= $user_id AND
   				status 			   	   != 'incomplete' AND 
   				orig_lp_id 				= $lp_id AND 
   				orig_lp_item_id 		= $lp_item_id AND 
   				orig_lp_item_view_id 	= $lp_item_view_id AND 
   				exe_cours_id 			= '".api_get_course_id()."' AND 
   				session_id 				= '" . api_get_session_id() . "'";

    $query = Database::query($sql);
    if (Database::num_rows($query) > 0 ) {
    	$attempt = Database :: fetch_array($query,'ASSOC');    	
    	return $attempt['count'];
    } else {
    	return 0; 
    } 
}



function get_attempt_count_not_finished($user_id, $exerciseId, $lp_id, $lp_item_id) {
	$stat_table 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$user_id 		= intval($user_id);
	$exerciseId 	= intval($exerciseId);
	$lp_id 			= intval($lp_id);
	$lp_item_id 	= intval($lp_item_id);
	$lp_item_view_id= intval($lp_item_view_id);

	$sql = "SELECT count(*) as count FROM $stat_table WHERE
   				exe_exo_id 			= $exerciseId AND 
   				exe_user_id 		= $user_id AND
   				status 				!= 'incomplete' AND 
   				orig_lp_id 			= $lp_id AND 
   				orig_lp_item_id 	= $lp_item_id AND
   				exe_cours_id = '".api_get_course_id()."' AND 
   				session_id = '" . api_get_session_id() . "'";

	$query = Database::query($sql);
	if (Database::num_rows($query) > 0 ) {
		$attempt = Database :: fetch_array($query,'ASSOC');
		return $attempt['count'];
	} else {
		return 0;
	}
}



function delete_student_lp_events($user_id, $lp_id, $course, $session_id) {
        
	$lp_view_table         = Database::get_course_table(TABLE_LP_VIEW);
    $lp_item_view_table    = Database::get_course_table(TABLE_LP_ITEM_VIEW);
    $course_id 			   = $course['real_id'];
    if (empty($course_id)) {
        $course_id = api_get_course_int_id();
    }
    
    $track_e_exercises     = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $track_attempts        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $recording_table       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
    
    $user_id               = intval($user_id);
    $lp_id                 = intval($lp_id);
    $session_id            = intval($session_id);
    
    //make sure we have the exact lp_view_id
    $sqlview       = "SELECT id FROM $lp_view_table WHERE c_id = $course_id AND user_id = $user_id AND lp_id = $lp_id AND session_id = $session_id ";                        
    $resultview    = Database::query($sqlview);
    
    if (Database::num_rows($sqlview)) {    
        $view          = Database::fetch_array($resultview, 'ASSOC');
        $lp_view_id    = $view['id'];        

        $sql_delete = "DELETE FROM $lp_item_view_table WHERE c_id = $course_id AND lp_view_id = $lp_view_id ";        
        $result = Database::query($sql_delete);
    }
        
    $sql_delete = "DELETE FROM $lp_view_table WHERE c_id = $course_id AND user_id = $user_id AND lp_id= $lp_id AND session_id= $session_id ";
    $result = Database::query($sql_delete);
    
    $select_all_attempts = "SELECT exe_id FROM $track_e_exercises WHERE exe_user_id = $user_id AND session_id= $session_id  AND exe_cours_id = '{$course['code']}' AND orig_lp_id = $lp_id";    
    $result    = Database::query($select_all_attempts);
    $exe_list = array();
    while ($row = Database::fetch_array($result, 'ASSOC')) {
    	$exe_list[] = $row['exe_id'];
    }    
    
    if (!empty($exe_list) && is_array($exe_list) && count($exe_list) > 0) {        
        $sql_delete = "DELETE FROM $track_e_exercises  WHERE exe_id IN (".implode(',',$exe_list).")";
        $result = Database::query($sql_delete);
        
        $sql_delete = "DELETE FROM $track_attempts  WHERE exe_id IN (".implode(',',$exe_list).")";
        $result = Database::query($sql_delete);
        
        $sql_delete = "DELETE FROM $recording_table  WHERE exe_id IN (".implode(',',$exe_list).")";
        $result = Database::query($sql_delete);        
    }
}

/**
 * Delete all exercise attempts (included in LP or not) 
 * 
 * @param 	int		user id
 * @param 	int		exercise id 
 * @param 	string	course code
 * @param 	int		session id
  */
function delete_all_incomplete_attempts($user_id, $exercise_id, $course_code, $session_id = 0) {
    $track_e_exercises    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $track_attempts       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $user_id              = intval($user_id);
    $exercise_id          = intval($exercise_id);
    $course_code          = Database::escape_string($course_code);
    $session_id           = intval($session_id);
    if (!empty($user_id) && !empty($exercise_id) && !empty($course_code)) {
        $sql = "DELETE FROM $track_e_exercises  WHERE exe_user_id = $user_id AND exe_exo_id = $exercise_id AND exe_cours_id = '$course_code' AND session_id = $session_id AND status = 'incomplete' ";
        $result = Database::query($sql); 
    }    
}

/**
 * Gets all exercise results (NO Exercises in LPs ) from a given exercise id, course, session
 * @param   int     exercise id
 * @param   string  course code
 * @param   int     session id
 * @return  array   with the results
 * 
 */
function get_all_exercise_results($exercise_id, $course_code, $session_id = 0, $load_question_list = true) {
	$TABLETRACK_EXERCICES  = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$TBL_TRACK_ATTEMPT     = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$course_code           = Database::escape_string($course_code);
	$exercise_id           = intval($exercise_id);
	$session_id            = intval($session_id);
	
	$sql = "SELECT * FROM $TABLETRACK_EXERCICES 
	        WHERE status = ''  AND exe_cours_id = '$course_code' AND exe_exo_id = '$exercise_id' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";	
	$res = Database::query($sql);
	$list = array();	
	while($row = Database::fetch_array($res,'ASSOC')) {		
		$list[$row['exe_id']] = $row;		
        if ($load_question_list) {
    		$sql = "SELECT * FROM $TBL_TRACK_ATTEMPT WHERE exe_id = {$row['exe_id']}";
    		$res_question = Database::query($sql);
    		while($row_q = Database::fetch_array($res_question,'ASSOC')) {
    			$list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
            }
        }
	}
	return $list;
}


/**
 * Gets all exercise results (NO Exercises in LPs ) from a given exercise id, course, session
 * @param   string  course code
 * @param   int     session id
 * @return  array   with the results
 * 
 */
function get_all_exercise_results_by_course($course_code, $session_id = 0, $get_count = true) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$course_code           = Database::escape_string($course_code);
	
	$session_id = intval($session_id);
	$select = '*';
	if ($get_count) {
	    $select = 'count(*) as count';    
	}	
	$sql = "SELECT $select FROM $table_track_exercises WHERE status = ''  AND exe_cours_id = '$course_code' AND session_id = $session_id  AND orig_lp_id = 0 AND orig_lp_item_id = 0 ORDER BY exe_id";    	
	$res = Database::query($sql);	
	if ($get_count) {
	    $row = Database::fetch_array($res,'ASSOC');	    
	    return $row['count'];
	} else {
    	$list = array();	
    	while($row = Database::fetch_array($res,'ASSOC')) {	    	   	
    		$list[$row['exe_id']] = $row;		
    		$sql = "SELECT * FROM $table_track_attempt WHERE exe_id = {$row['exe_id']}";
    		$res_question = Database::query($sql);
    		while($row_q = Database::fetch_array($res_question,'ASSOC')) {
    			$list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
    		}		    	    
        }    
	    return $list;
	}
}

/**
* Gets all exercise results (NO Exercises in LPs) from a given exercise id, course, session
* @param   int     exercise id
* @param   string  course code
* @param   int     session id
* @return  array   with the results
*
*/
function get_all_exercise_results_by_user($user_id,  $course_code, $session_id = 0) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$course_code = Database::escape_string($course_code);
	$exercise_id = intval($exercise_id);
	$session_id = intval($session_id);
	$user_id    = intval($user_id);

	$sql = "SELECT * FROM $table_track_exercises WHERE status = '' AND exe_user_id = $user_id AND exe_cours_id = '$course_code' AND session_id = $session_id AND orig_lp_id = 0 AND orig_lp_item_id = 0   ORDER by exe_id";

	$res = Database::query($sql);
	$list = array();
	while($row = Database::fetch_array($res,'ASSOC')) {
        $list[$row['exe_id']] = $row;       
	$sql = "SELECT * FROM $table_track_attempt WHERE exe_id = {$row['exe_id']}";
        $res_question = Database::query($sql);
	while($row_q = Database::fetch_array($res_question,'ASSOC')) {
	$list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
        }       
    }
    //echo '<pre>'; print_r($list);
	return $list;
}

/**
* Gets exercise results (NO Exercises in LPs) from a given exercise id, course, session
* @param   int     exercise id
* @param   string  course code
* @param   int     session id
* @return  array   with the results
*
*/
function get_exercise_results_by_attempt($exe_id) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$table_track_attempt_recording   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);	
	$exe_id 		= intval($exe_id);
	
	$sql = "SELECT * FROM $table_track_exercises WHERE status = '' AND exe_id = $exe_id";    

	$res = Database::query($sql);
	$list = array();
	if (Database::num_rows($res)) {
	 	$row = Database::fetch_array($res,'ASSOC');
	 	
		//Checking if this attempt was revised by a teacher
		$sql_revised = 'SELECT exe_id FROM ' . $table_track_attempt_recording . ' WHERE author != "" AND exe_id = '.$exe_id.' LIMIT 1';
		$res_revised = Database::query($sql_revised);
		$row['attempt_revised'] = 0;
		if (Database::num_rows($res_revised) > 0) {
			$row['attempt_revised'] = 1;
		}
		$list[$exe_id] = $row;
		$sql = "SELECT * FROM $table_track_attempt WHERE exe_id = $exe_id ORDER BY tms ASC";
		$res_question = Database::query($sql);
		while ($row_q = Database::fetch_array($res_question,'ASSOC')) {
			$list[$exe_id]['question_list'][$row_q['question_id']] = $row_q;
		}
	}
	// echo '<pre>'; print_r($list); echo "</pre>";
	return $list;
}

/**
 * Gets exercise results (NO Exercises in LPs) from a given user, exercise id, course, session, lp_id, lp_item_id
 * @param   int     user id
 * @param   int     exercise id
 * @param   string  course code
 * @param   int     session id
 * @param   int     lp id
 * @param   int     lp item id
 * @param   string 	order asc or desc 
 * @return  array   with the results
 * 
 */
function get_exercise_results_by_user($user_id, $exercise_id, $course_code, $session_id = 0, $lp_id = 0, $lp_item_id = 0, $order = null) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$table_track_attempt_recording   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
    $course_code 	= Database::escape_string($course_code);
    $exercise_id 	= intval($exercise_id);
    $session_id 	= intval($session_id);
    $user_id    	= intval($user_id);
    $lp_id			= intval($lp_id);
    $lp_item_id		= intval($lp_item_id);
    
    if (!in_array(strtolower($order), array('asc', 'desc'))) {
    	$order = 'asc'; 
    }
    
    $sql = "SELECT * FROM $table_track_exercises 
    		WHERE 	status 			= '' AND 
    				exe_user_id 	= $user_id AND 
    				exe_cours_id 	= '$course_code' AND 
    				exe_exo_id 		= $exercise_id AND 
    				session_id 		= $session_id AND 
    				orig_lp_id 		= $lp_id AND 
    				orig_lp_item_id = $lp_item_id 
    				ORDER by exe_id $order ";    
    
    $res = Database::query($sql);
    $list = array();    
    while($row = Database::fetch_array($res,'ASSOC')) {     
    	//Checking if this attempt was revised by a teacher
    	$sql_revised = 'SELECT exe_id FROM ' . $table_track_attempt_recording . ' WHERE author != "" AND exe_id = '.$row['exe_id'].' LIMIT 1';
    	$res_revised = Database::query($sql_revised);
    	$row['attempt_revised'] = 0;
     	if (Database::num_rows($res_revised) > 0) {
			$row['attempt_revised'] = 1;
        }    	
        $list[$row['exe_id']] = $row;       
        $sql = "SELECT * FROM $table_track_attempt WHERE exe_id = {$row['exe_id']}";
        $res_question = Database::query($sql);
        while ($row_q = Database::fetch_array($res_question,'ASSOC')) {
            $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
        }       
    }    
    return $list;
}



/**
 * Count exercise attempts (NO Exercises in LPs ) from a given exercise id, course, session
 * @param   int     exercise id
 * @param   string  course code
 * @param   int     session id
 * @return  array   with the results
 * 
 */
function count_exercise_attempts_by_user($user_id, $exercise_id, $course_code, $session_id = 0) {
	$TABLETRACK_EXERCICES  = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$TBL_TRACK_ATTEMPT     = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$course_code           = Database::escape_string($course_code);
	$exercise_id           = intval($exercise_id);
	$session_id            = intval($session_id);
	$user_id               = intval($user_id);
	
	$sql = "SELECT count(*) as count FROM $TABLETRACK_EXERCICES 
			WHERE status = ''  AND exe_user_id = '$user_id' AND exe_cours_id = '$course_code' AND exe_exo_id = '$exercise_id' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";
	$res = Database::query($sql);
	$result = 0;
	if (Database::num_rows($res) > 0 ) {		
	    $row = Database::fetch_array($res,'ASSOC');
	    $result = $row['count'];
	}	
	return $result;
}

/**
 * Gets all exercise BEST results attempts (NO Exercises in LPs ) from a given exercise id, course, session per user
 * @param   int     exercise id
 * @param   string  course code
 * @param   int     session id
 * @return  array   with the results
 * @todo rename this function
 * 
 */
function get_best_exercise_results_by_user($exercise_id, $course_code, $session_id = 0) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$course_code           = Database::escape_string($course_code);
	$exercise_id           = intval($exercise_id);
	$session_id            = intval($session_id);
	
	$sql = "SELECT * FROM $table_track_exercises WHERE status = ''  AND exe_cours_id = '$course_code' AND exe_exo_id = '$exercise_id' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";
	
	$res = Database::query($sql);
	$list = array();
	while($row = Database::fetch_array($res,'ASSOC')) {		
		$list[$row['exe_id']] = $row;		
		$sql = "SELECT * FROM $table_track_attempt WHERE exe_id = {$row['exe_id']}";
		$res_question = Database::query($sql);
		while($row_q = Database::fetch_array($res_question,'ASSOC')) {
			$list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
		}		
	}	
	//Getting the best results of every student	
	$best_score_return = array();
	
	foreach($list as $student_result) {	    
	    $user_id = $student_result['exe_user_id'];	    
	    $current_best_score[$user_id] = $student_result['exe_result'];	    
	    //echo $current_best_score[$user_id].' - '.$best_score_return[$user_id]['exe_result'].'<br />';	    
	    if ($current_best_score[$user_id] > $best_score_return[$user_id]['exe_result']) {
	        $best_score_return[$user_id] = $student_result;
	    }
    }
	return $best_score_return;
}

function get_best_attempt_exercise_results_per_user($user_id, $exercise_id, $course_code, $session_id = 0) {
    $table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $course_code           = Database::escape_string($course_code);
    $exercise_id           = intval($exercise_id);
    $session_id            = intval($session_id);
    $user_id               = intval($user_id);
    
    $sql = "SELECT * FROM $table_track_exercises 
            WHERE   status = ''  AND 
                    exe_cours_id = '$course_code' AND 
                    exe_exo_id = '$exercise_id' AND 
                    session_id = $session_id  AND 
                    exe_user_id = $user_id AND
                    orig_lp_id =0 AND 
                    orig_lp_item_id = 0 
                    ORDER BY exe_id";
    
    $res = Database::query($sql);
    $list = array();
    while($row = Database::fetch_array($res,'ASSOC')) {     
        $list[$row['exe_id']] = $row;  /*     
        $sql = "SELECT * FROM $table_track_attempt WHERE exe_id = {$row['exe_id']}";
        $res_question = Database::query($sql);
        while($row_q = Database::fetch_array($res_question,'ASSOC')) {
            $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
        }       */
    }   
    //Getting the best results of every student
    $best_score_return = array(); 
    $best_score_return['exe_result'] = 0;
    
    foreach($list as $result) {
        $current_best_score = $result;
        if ($current_best_score['exe_result'] > $best_score_return['exe_result']) {
            $best_score_return = $result;
        }
    }    
    if (!isset($best_score_return['exe_weighting'])) {
        $best_score_return = array();
    }
    return $best_score_return;
}



function count_exercise_result_not_validated($exercise_id, $course_code, $session_id = 0) {
    $table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);     
    $course_code           = Database::escape_string($course_code);    
    $session_id     = intval($session_id);
    $exercise_id    = intval($exercise_id);
        
    $status = Database::escape_string($status);
        
    $sql = "SELECT count(e.exe_id) as count FROM $table_track_exercises e LEFT JOIN $table_track_attempt a  ON e.exe_id = a.exe_id 
            WHERE   exe_exo_id = $exercise_id AND 
                    exe_cours_id = '$course_code' AND
                    e.session_id = $session_id  AND 
                    orig_lp_id = 0 AND
                    marks IS NULL AND
                    status = '' AND
                    orig_lp_item_id = 0 ORDER BY e.exe_id";                          
    $res = Database::query($sql);
    $row = Database::fetch_array($res,'ASSOC');
    
    return $row['count'];
    
}


/**
 * Gets all exercise BEST results attempts (NO Exercises in LPs ) from a given exercise id, course, session per user
 * @param   int     exercise id
 * @param   string  course code
 * @param   int     session id
 * @return  array   with the results
 * 
 */
function get_count_exercises_attempted_by_course($course_code, $session_id = 0) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$course_code           = Database::escape_string($course_code);	
	$session_id            = intval($session_id);
	
	$sql = "SELECT DISTINCT exe_exo_id, exe_user_id FROM $table_track_exercises WHERE status = '' AND exe_cours_id = '$course_code' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";	
	$res = Database::query($sql);
	$count = 0;
	if (Database::num_rows($res) > 0) {
	    $count = Database::num_rows($res);
	}
	return $count;
}


/**
 * Gets all exercise events from a Learning Path within a Course 	nd Session
 * @param	int		exercise id
 * @param	string	course_code
 * @param 	int		session id
 * @return 	array
 */
function get_all_exercise_event_from_lp($exercise_id, $course_code, $session_id = 0) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $course_code = Database::escape_string($course_code);
    $exercise_id = intval($exercise_id);
    $session_id = intval($session_id);    
  
    $sql = "SELECT * FROM $table_track_exercises WHERE status = ''  AND exe_cours_id = '$course_code' AND exe_exo_id = '$exercise_id' AND session_id = $session_id AND orig_lp_id !=0 AND orig_lp_item_id != 0";
    
    $res = Database::query($sql);
    $list = array();    
    while($row = Database::fetch_array($res,'ASSOC')) {     
        $list[$row['exe_id']] = $row;       
        $sql = "SELECT * FROM $table_track_attempt WHERE exe_id = {$row['exe_id']}";
        $res_question = Database::query($sql);
        while($row_q = Database::fetch_array($res_question,'ASSOC')) {
            $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
        }       
    }
    return $list;
}


function get_all_exercises_from_lp($lp_id, $course_id) {
	$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM);
	$course_id = intval($course_id);
	$lp_id = intval($lp_id);
	$sql = "SELECT * FROM $lp_item_table WHERE c_id = $course_id AND lp_id = '".$lp_id."'  ORDER BY parent_item_id, display_order";
	$res = Database::query($sql);
	$my_exercise_list = array();	
	while($row = Database::fetch_array($res,'ASSOC')) {
		if ($row['item_type'] == 'quiz') {
			$my_exercise_list[] = $row;
		}		
	}
	return $my_exercise_list; 
}


/**
 * This function gets the comments of an exercise
 *
 * @param int $id
 * @param int $question_id
 * @return str the comment
 */
function get_comments($id,$question_id) {
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $sql = "SELECT teacher_comment FROM ".$table_track_attempt." where exe_id='".Database::escape_string($id)."' and question_id = '".Database::escape_string($question_id)."' ORDER by question_id";
    $sqlres = Database::query($sql);
    $comm = Database::result($sqlres,0,"teacher_comment");
    return $comm;
}


function get_all_exercise_event_by_exe_id($exe_id) {
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$exe_id = intval($exe_id);
	$list = array();

	$sql = "SELECT * FROM $table_track_attempt WHERE exe_id = $exe_id ORDER BY position";
	$res_question = Database::query($sql);
	if (Database::num_rows($res_question))
	while($row_q = Database::fetch_array($res_question,'ASSOC')) {
		$list[$row_q['question_id']][] = $row_q;
	}
	return $list;
}


function delete_attempt($exe_id, $user_id, $course_code, $session_id, $question_id) {
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

	$exe_id          = intval($exe_id);
	$user_id         = intval($user_id);
	$course_code     = Database::escape_string($course_code);
	$session_id      = intval($session_id);
	$question_id     = intval($question_id);

	$sql = "DELETE FROM $table_track_attempt WHERE exe_id = $exe_id AND user_id = $user_id AND course_code = '$course_code' AND session_id = $session_id AND question_id = $question_id ";
	Database::query($sql);
}

function delete_attempt_hotspot($exe_id, $user_id, $course_code, $question_id) {
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);

	$exe_id          = intval($exe_id);
	$user_id         = intval($user_id);
	$course_code     = Database::escape_string($course_code);
	//$session_id      = intval($session_id);
	$question_id     = intval($question_id);

	$sql = "DELETE FROM $table_track_attempt WHERE hotspot_exe_id = $exe_id AND hotspot_user_id = $user_id AND hotspot_course_code = '$course_code' AND hotspot_question_id = $question_id ";
	Database::query($sql);
}

/**
 * User logs in for the first time to a course
 */
function event_course_login($course_code, $user_id, $session_id) {
	global $course_tracking_table;
	
	//@todo use api_get_utc_datetime
	$time		 = api_get_datetime();
	
	$course_code = Database::escape_string($course_code);
	$user_id	 = Database::escape_string($user_id);
	$session_id  = Database::escape_string($session_id);
	
	$sql	= "INSERT INTO $course_tracking_table(course_code, user_id, login_course_date, logout_course_date, counter, session_id) 
			  VALUES('".$course_code."', '".$user_id."', '$time', '$time', '1', '".$session_id."')";
	Database::query($sql);
	
	
    //Course catalog stats modifications see #4191    
    CourseManager::update_course_ranking(null, null, null, null, true, false);
}
