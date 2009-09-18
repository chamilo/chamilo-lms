<?php // $Id: events.lib.inc.php 22205 2009-07-17 21:11:52Z cfasanando $
/* See license terms in /dokeos_license.txt */
/**
==============================================================================
* EVENTS LIBRARY
*
* This is the events library for Dokeos.
* Include/require it in your code to use its functionality.
* Functions of this library are used to record informations when some kind
* of event occur. Each event has his own types of informations then each event
* use its own function.
*
* @package dokeos.library
* @todo convert queries to use Database API
==============================================================================
*/
/*
==============================================================================
	   INIT SECTION
==============================================================================
*/
// REGROUP TABLE NAMES FOR MAINTENANCE PURPOSE
$TABLETRACK_LOGIN = $_configuration['statistics_database'].".track_e_login";
$TABLETRACK_OPEN = $_configuration['statistics_database'].".track_e_open";
$TABLETRACK_ACCESS = $_configuration['statistics_database'].".track_e_access";
$TABLETRACK_DOWNLOADS = $_configuration['statistics_database'].".track_e_downloads";
$TABLETRACK_UPLOADS = $_configuration['statistics_database'].".track_e_uploads";
$TABLETRACK_LINKS = $_configuration['statistics_database'].".track_e_links";
$TABLETRACK_EXERCICES = $_configuration['statistics_database'].".track_e_exercices";
$TABLETRACK_SUBSCRIPTIONS = $_configuration['statistics_database'].".track_e_subscriptions";
$TABLETRACK_LASTACCESS = $_configuration['statistics_database'].".track_e_lastaccess"; //for "what's new" notification
$TABLETRACK_DEFAULT = $_configuration['statistics_database'].".track_e_default";

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for open event (when homepage is opened)
 */
function event_open()
{
	global $_configuration;
	global $TABLETRACK_OPEN;

	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	// @getHostByAddr($_SERVER['REMOTE_ADDR']) : will provide host and country information
	// $_SERVER['HTTP_USER_AGENT'] :  will provide browser and os information
	// $_SERVER['HTTP_REFERER'] : provide information about refering url
	if(isset($_SERVER['HTT_REFERER']))
	{
		$referer = Database::escape_string($_SERVER['HTTP_REFERER']);
	}
	else
	{
		$referer = '';
	}
	// record informations only if user comes from another site
	//if(!eregi($_configuration['root_web'],$referer))
	$pos = strpos($referer, $_configuration['root_web']);
	if ($pos === false && $referer != '')
	{
		$remhost = @ getHostByAddr($_SERVER['REMOTE_ADDR']);
		if ($remhost == $_SERVER['REMOTE_ADDR'])
			$remhost = "Unknown"; // don't change this
		$reallyNow = time();
		$sql = "INSERT INTO ".$TABLETRACK_OPEN."
						(open_remote_host,
						 open_agent,
						 open_referer,
						 open_date)
						VALUES
						('".$remhost."',
						 '".Database::escape_string($_SERVER['HTTP_USER_AGENT'])."', '".Database::escape_string($referer)."', FROM_UNIXTIME($reallyNow) )";
		$res = api_sql_query($sql,__FILE__,__LINE__);
	}
	return 1;
}

/**
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for login event
 * (when an user identifies himself with username & password)
 */
function event_login()
{
	global $_configuration;
	global $_user;
	global $TABLETRACK_LOGIN;

	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled']) {
		return 0;
	}
	$reallyNow = time();
	$sql = "INSERT INTO ".$TABLETRACK_LOGIN."
				(login_user_id,
				 login_ip,
				 login_date)
				 VALUES
					('".$_user['user_id']."',
					'".Database::escape_string($_SERVER['REMOTE_ADDR'])."',
					FROM_UNIXTIME(".$reallyNow."))";
	$res = api_sql_query($sql,__FILE__,__LINE__);
}

/**
 * @param tool name of the tool (name in mainDb.accueil table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for access event for courses
 */
function event_access_course()
{
	global $_configuration;
	global $_user;
	global $_cid;
	global $TABLETRACK_ACCESS;
	global $TABLETRACK_LASTACCESS; //for "what's new" notification

	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	if(api_get_setting('use_session_mode')=='true' && isset($_SESSION['id_session']))
	{
		$id_session = intval($_SESSION['id_session']);
	}
	else
	{
		$id_session = 0;
	}

	$reallyNow = time();
	if ($_user['user_id']) {
		$user_id = "'".$_user['user_id']."'";
	} else {
		$user_id = "0"; // no one
	}
	$sql = "INSERT INTO ".$TABLETRACK_ACCESS."
				(access_user_id,
				 access_cours_code,
				 access_date)
				VALUES
				(".$user_id.",
				'".$_cid."',
				FROM_UNIXTIME(".$reallyNow."))";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	// added for "what's new" notification
	$sql = "   UPDATE $TABLETRACK_LASTACCESS
	                    SET access_date = FROM_UNIXTIME($reallyNow)
						WHERE access_user_id = ".$user_id." AND access_cours_code = '".$_cid."' AND access_tool IS NULL AND access_session_id=".$id_session;
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (Database::affected_rows() == 0)
	{
		$sql = "	INSERT INTO $TABLETRACK_LASTACCESS
		                	    (access_user_id,access_cours_code,access_date, access_session_id)
		                    	VALUES
		                	    (".$user_id.", '".$_cid."', FROM_UNIXTIME($reallyNow), ".$id_session.")";
		$res = api_sql_query($sql,__FILE__,__LINE__);
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
function event_access_tool($tool, $id_session=0)
{
	global $_configuration;
	// if tracking is disabled record nothing
	// if( ! $_configuration['tracking_enabled'] ) return 0; //commented because "what's new" notification must always occur
	global $_user;
	global $_cid;
	global $TABLETRACK_ACCESS;
	global $_configuration;
	global $_course;
	global $TABLETRACK_LASTACCESS; //for "what's new" notification

	if(api_get_setting('use_session_mode')=='true' && isset($_SESSION['id_session']))
	{
		$id_session = intval($_SESSION['id_session']);
	}
	else
	{
		$id_session = 0;
	}

	$reallyNow = time();
	$user_id = $_user['user_id'] ? "'".$_user['user_id']."'" : "0"; // no one
	// record information
	// only if user comes from the course $_cid
	//if( eregi($_configuration['root_web'].$_cid,$_SERVER['HTTP_REFERER'] ) )
	//$pos = strpos($_SERVER['HTTP_REFERER'],$_configuration['root_web'].$_cid);
	$pos = strpos(strtolower($_SERVER['HTTP_REFERER']), strtolower(api_get_path(WEB_COURSE_PATH).$_course['path']));
	// added for "what's new" notification
	$pos2 = strpos(strtolower($_SERVER['HTTP_REFERER']), strtolower($_configuration['root_web']."index"));
	// end "what's new" notification
	if ($_configuration['tracking_enabled'] && ($pos !== false || $pos2 !== false))
	{
			$sql = "INSERT INTO ".$TABLETRACK_ACCESS."
							(access_user_id,
							 access_cours_code,
							 access_tool,
							 access_date)
							VALUES
							(".$user_id.",".// Don't add ' ' around value, it's already done.
					"'".$_cid."' ,
					'".htmlspecialchars($tool, ENT_QUOTES)."',
					FROM_UNIXTIME(".$reallyNow."))";
		$res = api_sql_query($sql,__FILE__,__LINE__);
	}
	// "what's new" notification
	$sql = "   UPDATE $TABLETRACK_LASTACCESS
						SET access_date = FROM_UNIXTIME($reallyNow)
						WHERE access_user_id = ".$user_id." AND access_cours_code = '".$_cid."' AND access_tool = '".htmlspecialchars($tool, ENT_QUOTES)."' AND access_session_id=".$id_session;
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (Database::affected_rows() == 0)
	{
		$sql = "INSERT INTO $TABLETRACK_LASTACCESS
							(access_user_id,access_cours_code,access_tool, access_date, access_session_id)
						VALUES
							(".$user_id.", '".$_cid."' , '".htmlspecialchars($tool, ENT_QUOTES)."', FROM_UNIXTIME($reallyNow), $id_session)";
		$res = api_sql_query($sql,__FILE__,__LINE__);
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
function event_download($doc_url)
{
	global $_configuration;
	global $_user;
	global $_cid;
	global $TABLETRACK_DOWNLOADS;

	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$reallyNow = time();
	if ($_user['user_id'])
	{
		$user_id = "'".$_user['user_id']."'";
	} else {
		$user_id = "0";
	}
	$sql = "INSERT INTO ".$TABLETRACK_DOWNLOADS."
				(
				 down_user_id,
				 down_cours_id,
				 down_doc_path,
				 down_date
				)

				VALUES
				(
				 ".$user_id.",
				 '".$_cid."',
				 '".htmlspecialchars($doc_url, ENT_QUOTES)."',
				 FROM_UNIXTIME(".$reallyNow.")
				)";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	return 1;
}

/**
 * @param doc_id id of document (id in mainDb.document table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for upload event
 * used in the works tool to record informations when
 * an user upload 1 work
 */
function event_upload($doc_id)
{
	global $_configuration;
	global $_user;
	global $_cid;
	global $TABLETRACK_UPLOADS;
	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled']) {
		return 0;
	}

	$reallyNow = time();
	if (isset($_user['user_id']) && $_user['user_id']!='') {
		$user_id = "'".$_user['user_id']."'";
	} else {
		$user_id = "0"; // anonymous
	}
	$sql = "INSERT INTO ".$TABLETRACK_UPLOADS."
				( upload_user_id,
				  upload_cours_id,
				  upload_work_id,
				  upload_date
				)
				VALUES (
				 ".$user_id.",
				 '".$_cid."',
				 '".$doc_id."',
				 FROM_UNIXTIME(".$reallyNow.")
				)";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	return 1;
}

/**
 * @param link_id (id in coursDb liens table)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record information for link event (when an user click on an added link)
 * it will be used in a redirection page
*/
function event_link($link_id)
{
	global $_configuration;
	global $_user;
	global $_cid;
	global $TABLETRACK_LINKS;

	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled']) {
		return 0;
	}

	$reallyNow = time();
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
				 links_date
				)
				VALUES
				(
				 ".$user_id.",
				 '".$_cid."',
				 '".Database::escape_string($link_id)."',
				 FROM_UNIXTIME(".$reallyNow.")
				)";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	return 1;
}

/**
 * @param exeid ( id of the exercise)
 * @param exo_id ( id in courseDb exercices table )
 * @param result ( score @ exercice )
 * @param weighting ( higher score )
 * @param duration ( duration of the attempt, in seconds )
 * @param session_id
 * @param learnpath_id (id of the learnpath)
 * @param learnpath_item_id (id of the learnpath_item)
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @author Julio Montoya Armas <gugli100@gmail.com>
 * @desc Record result of user when an exercice was done
*/
function update_event_exercice($exeid,$exo_id, $score, $weighting,$session_id,$learnpath_id=0,$learnpath_item_id=0, $duration)
{
	if ($exeid!='') {
		$TABLETRACK_EXERCICES = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
		$reallyNow = time();
		$sql = "UPDATE $TABLETRACK_EXERCICES SET
				   exe_exo_id 	= 	'".Database::escape_string($exo_id)."',
				   exe_result	=	  '".Database::escape_string($score)."',
				   exe_weighting = '".Database::escape_string($weighting)."',
				   session_id		= '".Database::escape_string($session_id)."',
				   orig_lp_id = '".Database::escape_string($learnpath_id)."',
				   orig_lp_item_id = '".Database::escape_string($learnpath_item_id)."',
				   exe_duration = '".Database::escape_string($duration)."',
				   exe_date= FROM_UNIXTIME(".$reallyNow."),status = '', data_tracking='',start_date =FROM_UNIXTIME(".Database::escape_string($_SESSION['exercice_start_date']).")
				 WHERE exe_id = '".Database::escape_string($exeid)."'";
		$res = @api_sql_query($sql,__FILE__,__LINE__);
		return $res;
	} else
		return false;
}

/**
 * This function creates an empty Exercise in STATISTIC_TRACK_E_EXERCICES table.
 * After that in exercise_result.php we call the update_event_exercice() to update the exercise
 * @return $id the last id registered
 * @author Julio Montoya <gugli100@gmail.com>
 * @desc Record result of user when an exercice was done
*/
function create_event_exercice($exo_id)
{
	global $_user, $_cid, $_configuration;
	$TABLETRACK_EXERCICES = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$reallyNow = time();
	if (isset($_user['user_id']) && $_user['user_id']!='') {
		$user_id = "'".$_user['user_id']."'";
	} else {
		// anonymous
		$user_id = "0";
	}

	if(defined('ENABLED_LIVE_EXERCISE_TRACKING')){
		$condition = ' WHERE ' .
				'exe_exo_id =   '."'".Database::escape_string($exo_id)."'".' AND ' .
				'exe_user_id =  '."'".api_get_user_id()."'".' AND ' .
				'exe_cours_id = '."'".$_cid."'".' AND ' .
				'status = '."'incomplete'".' AND '.
				'session_id = '."'".api_get_session_id()."'";
		$sql = api_sql_query('SELECT exe_id FROM '.$TABLETRACK_EXERCICES.$condition,__FILE__,__LINE__);
		$row = Database::fetch_array($sql);
		return $row['exe_id'];
	}

	$sql = "INSERT INTO $TABLETRACK_EXERCICES ( exe_user_id, exe_cours_id )
			VALUES (  ".$user_id.",  '".$_cid."' )";
	$res = @api_sql_query($sql,__FILE__,__LINE__);
	$id= Database::get_last_insert_id();
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
function exercise_attempt($score,$answer,$quesId,$exeId,$j)
{
	$score = Database::escape_string($score);
	$answer = Database::escape_string($answer);
	$quesId = Database::escape_string($quesId);
	$exeId = Database::escape_string($exeId);
	$j = Database::escape_string($j);

	global $_configuration, $_user, $_cid;
	$TBL_TRACK_ATTEMPT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$reallyNow = time();
	if (isset($_user['user_id']) && $_user['user_id']!='')
	{
		$user_id = "'".$_user['user_id']."'";
	}
	else // anonymous
	{
		$user_id = api_get_anonymous_id();
	}

	$sql = "INSERT INTO $TBL_TRACK_ATTEMPT
			  (
			   exe_id,
			   user_id,
			   question_id,
			   answer,
			   marks,
			   course_code,
			   position,
			   tms
			  )
			  VALUES
			  (
			  ".$exeId.",
			  ".$user_id.",
			   '".$quesId."',
			   '".addslashes($answer)."',
			   '".$score."',
			   '".$_cid."',
			   '".$j."',
			   FROM_UNIXTIME(".$reallyNow.")
			  )";

	if(defined('ENABLED_LIVE_EXERCISE_TRACKING')){
		$TBL_RECORDING = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
		$recording_changes = 'INSERT INTO '.$TBL_RECORDING.' ' .
		'(exe_id,
		question_id,
		marks,
		insert_date,
		author)
		VALUES
		('."'$exeId','".$quesId."','$score','".date('Y-m-d H:i:s')."',''".')';
		api_sql_query($recording_changes,__FILE__,__LINE__);
	}
	if (isset($quesId) && isset($exeId) && isset($user_id)) {
		$res = api_sql_query($sql,__FILE__,__LINE__);
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
function exercise_attempt_hotspot($exe_id, $question_id, $answer_id, $correct, $coords)
{
	global $_configuration, $_user, $_cid;
	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$tbl_track_e_hotspot = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
	$sql = "INSERT INTO $tbl_track_e_hotspot " .
			"(hotspot_user_id, hotspot_course_code, hotspot_exe_id, hotspot_question_id, hotspot_answer_id, hotspot_correct, hotspot_coordinate)".
			" VALUES ('" . Database :: escape_string($_user['user_id']) . "'," .
			" '" . Database :: escape_string($_cid) . "', " .
			" '" . Database :: escape_string($exe_id) . "', " .
			" '" . Database :: escape_string($question_id) . "'," .
			" '" . Database :: escape_string($answer_id) . "'," .
			" '" . Database :: escape_string($correct) . "'," .
			" '" . Database :: escape_string($coords) . "')";
	return $result = api_sql_query($sql, __FILE__, __LINE__);
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
function event_system($event_type, $event_value_type, $event_value, $timestamp = null, $user_id=null, $course_code=null)
{
	global $_configuration;
	global $_user;
	global $TABLETRACK_DEFAULT;

	$event_type = Database::escape_string($event_type);
	$event_value_type = Database::escape_string($event_value_type);
	$event_value = Database::escape_string($event_value);
	$timestamp = Database::escape_string($timestamp);
	$user_id = Database::escape_string($user_id);
	$course_code = Database::escape_string($course_code);


	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}
	if(!isset($timestamp))
	{
		$timestamp = time();
	}
	if(!isset($user_id))
	{
		$user_id = 0;
	}
	if(!isset($course_code))
	{
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
					FROM_UNIXTIME($timestamp),
					'$event_type',
					'$event_value_type',
					'$event_value')";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	return true;
}
?>
