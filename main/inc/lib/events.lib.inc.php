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
$TABLETRACK_DOWNLOADS	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_UPLOADS 	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
$TABLETRACK_LINKS 		    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_EXERCICES 	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
//$TABLETRACK_SUBSCRIPTIONS   = $_configuration['statistics_database'].".track_e_subscriptions"; // this table is never use
$TABLETRACK_LASTACCESS 	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS); //for "what's new" notification
$TABLETRACK_DEFAULT 	    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);

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
}

/**
 * @param tool name of the tool (name in mainDb.accueil table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for access event for courses
 */
function event_access_course() {
	global $_user;
	global $_cid;
	global $TABLETRACK_ACCESS;
	global $TABLETRACK_LASTACCESS; //for "what's new" notification

	$id_session = api_get_session_id();	
	$reallyNow = api_get_utc_datetime();
	if ($_user['user_id']) {
		$user_id = "'".$_user['user_id']."'";
	} else {
		$user_id = "0"; // no one
	}
	$sql = "INSERT INTO ".$TABLETRACK_ACCESS."  (access_user_id, access_cours_code, access_date, access_session_id) VALUES
		    (".$user_id.", '".$_cid."', '".$reallyNow."','".$id_session."')";
	$res = Database::query($sql);
	// added for "what's new" notification
	$sql = "UPDATE $TABLETRACK_LASTACCESS  SET access_date = '$reallyNow' 
			WHERE access_user_id = $user_id AND access_cours_code = '$_cid' AND access_tool IS NULL AND access_session_id=".$id_session;
	$res = Database::query($sql);
	if (Database::affected_rows() == 0) {
		$sql = "INSERT INTO $TABLETRACK_LASTACCESS (access_user_id, access_cours_code, access_date, access_session_id)
				VALUES (".$user_id.", '".$_cid."', '$reallyNow', '".$id_session."')";
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
function update_event_exercice($exeid, $exo_id, $score, $weighting,$session_id,$learnpath_id=0, $learnpath_item_id=0, $learnpath_item_view_id = 0, $duration) {
	require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
    if ($exeid!='') {
		// Validation in case of fraud with actived control time
		if (!exercise_time_control_is_valid($exo_id)) {
			$score = 0;		
	    }
	    $now = time();
	    //Validation in case of wrong start_date
	    if (isset($_SESSION['exercice_start_date'])) {
	    	$start_date = $_SESSION['exercice_start_date'];
	    	$diff  = abs($start_date - $now);
	    	if ($diff > 14400) { // 14400 = 4h*60*60 more than 4h of diff
	    		$start_date = $now - 1800; //	Now - 30min
	    	}
	    }

		$TABLETRACK_EXERCICES = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);

		$sql = "UPDATE $TABLETRACK_EXERCICES SET
				   exe_exo_id 		= '".Database::escape_string($exo_id)."',
				   exe_result		= '".Database::escape_string($score)."',
				   exe_weighting 	= '".Database::escape_string($weighting)."',
				   session_id		= '".Database::escape_string($session_id)."',
				   orig_lp_id 		= '".Database::escape_string($learnpath_id)."',
				   orig_lp_item_id 	= '".Database::escape_string($learnpath_item_id)."',
                   orig_lp_item_view_id  = '".Database::escape_string($learnpath_item_view_id)."',
				   exe_duration 	= '".Database::escape_string($duration)."',
				   exe_date			= '".api_get_utc_datetime()."',
				   status 			= '',
				   start_date       = '".api_get_utc_datetime($start_date)."'
				 WHERE exe_id = '".Database::escape_string($exeid)."'";
		$res = @Database::query($sql);
        
        //Deleting control time session track		
		exercise_time_control_delete($exo_id);
        //error_log('update_event_exercice');
        //error_log($sql);
		return $res;
        
	} else
		return false;
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

    // First, check the exercise exists
    $sql_exe_id="SELECT exercises.id FROM $tbl_exe as exercises WHERE exercises.id=$exo_id";
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
function exercise_attempt($score, $answer, $quesId, $exeId, $j, $exercise_id = 0) {	
    require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
	$score 	= Database::escape_string($score);
	$answer = Database::escape_string($answer);
	$quesId = Database::escape_string($quesId);
	$exeId 	= Database::escape_string($exeId);
	$j 		= Database::escape_string($j);
		
	$TBL_TRACK_ATTEMPT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

	//Validation in case of fraud with actived control time	
	
    if (!exercise_time_control_is_valid($exercise_id)) {
    	$score = 0;
    	$answer = 0;
    	$j = 0;
	}
	$reallyNow 	= api_get_utc_datetime();
	$user_id 	= api_get_user_id();
	if (!empty($user_id)) {
		$user_id = "'".$user_id."'";
	} else {
		// anonymous
		$user_id = api_get_anonymous_id();
	}
    
	$sql = "INSERT INTO $TBL_TRACK_ATTEMPT (exe_id, user_id, question_id, answer, marks, course_code, session_id, position, tms )
			  VALUES (
			  ".$exeId.",
			  ".$user_id.",
			   '".$quesId."',
			   '".$answer."',
			   '".$score."',
			   '".api_get_course_id()."',
			   '".api_get_session_id()."',
			   '".$j."',
			   '".$reallyNow."'
			  )";
    //error_log($sql);
	if (!empty($quesId) && !empty($exeId) && !empty($user_id)) {
		$res = Database::query($sql);		
		if (defined('ENABLED_LIVE_EXERCISE_TRACKING')){
			$recording_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
			$recording_changes = "INSERT INTO $recording_table (exe_id, question_id, marks, insert_date, author, session_id) VALUES ('$exeId','$quesId','$score','".api_get_utc_datetime()."','', '".api_get_session_id()."') ";
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
function event_system($event_type, $event_value_type, $event_value, $datetime = null, $user_id=null, $course_code=null) {	
	global $_user;
	global $TABLETRACK_DEFAULT;

	$event_type         = Database::escape_string($event_type);
	$event_value_type   = Database::escape_string($event_value_type);
	$event_value        = Database::escape_string($event_value);
	$datetime           = Database::escape_string($datetime);
	$user_id            = Database::escape_string($user_id);
	$course_code        = Database::escape_string($course_code);
    
	if(!isset($datetime)) {
		$datetime = api_get_utc_datetime();
	}
	if(!isset($user_id)) {
		$user_id = 0;
	}
	if(!isset($course_code)) {
		$course_code = '';
	}
	$sql = "INSERT INTO $TABLETRACK_DEFAULT
				(default_user_id,
				 default_cours_code,
				 default_date,
				 default_event_type,
				 default_value_type,
				 default_value
				 )
				 VALUES
					('$user_id.',
					'$course_code',
					'$datetime',
					'$event_type',
					'$event_value_type',
					'$event_value')";
	$res = Database::query($sql);
	return true;
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
	
   	$sql = "SELECT count(*) as count FROM $stat_table WHERE exe_exo_id = '$exerciseId'
            AND exe_user_id = '$user_id' AND status != 'incomplete'
            AND orig_lp_id 	= $lp_id AND orig_lp_item_id = $lp_item_id AND orig_lp_item_view_id = $lp_item_view_id AND exe_cours_id = '".api_get_course_id()."' AND session_id = '" . api_get_session_id() . "'";

    $query = Database::query($sql);
    if (Database::num_rows($query) > 0 ) {
    	$attempt = Database :: fetch_array($query,'ASSOC');
    	return $attempt['count'];
    } else {
    	return 0; 
    } 
}


function delete_student_lp_events($user_id, $lp_id, $course, $session_id) {
        
	$lp_view_table         = Database::get_course_table(TABLE_LP_VIEW, $course['dbName']);
    $lp_item_view_table    = Database::get_course_table(TABLE_LP_ITEM_VIEW, $course['dbName']);
    
    $track_e_exercises     = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $track_attempts        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $recording_table       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
    
    $user_id               = intval($user_id);
    $lp_id                 = intval($lp_id);
    $session_id            = intval($session_id);
    
    //make sure we have the exact lp_view_id
    $sqlview       = "SELECT id FROM $lp_view_table WHERE user_id = $user_id AND lp_id = $lp_id AND session_id = $session_id ";                    
    $resultview    = Database::query($sqlview);
    $view          = Database::fetch_array($resultview, 'ASSOC');
    $lp_view_id    = $view['id'];                  
    
    $sql_delete = "DELETE FROM $lp_item_view_table  WHERE lp_view_id = $view_id ";
    $result = Database::query($sql_delete);
        
    $sql_delete = "DELETE FROM $lp_view_table  WHERE user_id = $user_id AND lp_id= $lp_id AND session_id= $session_id ";
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
function get_all_exercise_results($exercise_id, $course_code, $session_id = 0) {
	$TABLETRACK_EXERCICES  = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$TBL_TRACK_ATTEMPT     = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$course_code           = Database::escape_string($course_code);
	$exercise_id           = intval($exercise_id);
	$session_id            = intval($session_id);
	
	$sql = "SELECT * FROM $TABLETRACK_EXERCICES WHERE status = ''  AND exe_cours_id = '$course_code' AND exe_exo_id = '$exercise_id' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";	
	$res = Database::query($sql);
	$list = array();	
	while($row = Database::fetch_array($res,'ASSOC')) {		
		$list[$row['exe_id']] = $row;		
		$sql = "SELECT * FROM $TBL_TRACK_ATTEMPT WHERE exe_id = {$row['exe_id']}";
		$res_question = Database::query($sql);
		while($row_q = Database::fetch_array($res_question,'ASSOC')) {
			$list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
		}		
	}
	return $list;
}


/**
 * Gets all exercise results (NO Exercises in LPs ) from a given exercise id, course, session
 * @param   int     exercise id
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
	$sql = "SELECT $select FROM $table_track_exercises WHERE status = ''  AND exe_cours_id = '$course_code' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";	
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
function get_all_exercise_results_by_user($user_id, $exercise_id, $course_code, $session_id = 0) {
	$table_track_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$table_track_attempt   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $course_code = Database::escape_string($course_code);
    $exercise_id = intval($exercise_id);
    $session_id = intval($session_id);
    $user_id    = intval($user_id);
    
    $sql = "SELECT * FROM $table_track_exercises WHERE status = '' AND exe_user_id = $user_id AND exe_cours_id = '$course_code' AND exe_exo_id = $exercise_id AND session_id = $session_id AND orig_lp_id = 0 AND orig_lp_item_id = 0   ORDER by exe_id";    
    
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
	
	$sql = "SELECT count(*) as count FROM $TABLETRACK_EXERCICES WHERE status = ''  AND exe_user_id = '$user_id' AND exe_cours_id = '$course_code' AND exe_exo_id = '$exercise_id' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";
	
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
	/*
	echo count($list);
	echo '<br>';
	echo '<pre>'; print_r($list);*/
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
	/*
	echo count($best_score_return);
	echo '<pre>'; print_r($best_score_return);*/
	return $best_score_return;
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
	
	$sql = "SELECT DISTINCT exe_exo_id, exe_user_id FROM $table_track_exercises WHERE status = ''  AND exe_cours_id = '$course_code' AND session_id = $session_id  AND orig_lp_id =0 AND orig_lp_item_id = 0 ORDER BY exe_id";	
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
/*
function get_all_exercise_event_from_lp($exercise_id, $course_db, $session_id ) {
	$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM,$course_db);
	$lp_item_view_table = Database  :: get_course_table(TABLE_LP_ITEM_VIEW,$course_db);
	$lp_view_table = Database  :: get_course_table(TABLE_LP_VIEW,$course_db);
	
	$exercise_id 	= intval($exercise_id);
	$session_id 	= intval($session_id);
	
	$sql = "SELECT  title, user_id, score , iv.max_score, status, session_id 
			FROM $lp_item_table as i INNER JOIN $lp_item_view_table iv ON (i.id = iv.lp_item_id ) INNER JOIN $lp_view_table v ON iv.lp_view_id = v.id 
			WHERE path = $exercise_id AND status ='completed' AND session_id = $session_id";
	$res = Database::query($sql);
	$list = array();	
	
	while($row = Database::fetch_array($res,'ASSOC')) {		
		$list[$row['exe_id']]['question_list'][$row['question_id']] = $row;				
	}
	//echo '<pre>'; print_r($list);
	return $list;
}*/



function get_all_exercises_from_lp($lp_id, $course_db) {
	$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM,$course_db);
	$lp_id = intval($lp_id);
	$sql = "SELECT * FROM $lp_item_table WHERE lp_id = '".$lp_id."'  ORDER BY parent_item_id, display_order";
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

