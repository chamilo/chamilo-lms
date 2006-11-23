<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Sebastien Piraux
	Copyright (c) Toon Van Hoecke
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
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
$TABLETRACK_LOGIN = $_configuration['statistics_database']."`.`track_e_login";
$TABLETRACK_OPEN = $_configuration['statistics_database']."`.`track_e_open";
$TABLETRACK_ACCESS = $_configuration['statistics_database']."`.`track_e_access";
$TABLETRACK_DOWNLOADS = $_configuration['statistics_database']."`.`track_e_downloads";
$TABLETRACK_UPLOADS = $_configuration['statistics_database']."`.`track_e_uploads";
$TABLETRACK_LINKS = $_configuration['statistics_database']."`.`track_e_links";
$TABLETRACK_EXERCICES = $_configuration['statistics_database']."`.`track_e_exercices";
$TABLETRACK_SUBSCRIPTIONS = $_configuration['statistics_database']."`.`track_e_subscriptions";
$TABLETRACK_LASTACCESS = $_configuration['statistics_database']."`.`track_e_lastaccess"; //for "what's new" notification


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
	$referer = $_SERVER['HTTP_REFERER'];
	// record informations only if user comes from another site
	//if(!eregi($_configuration['root_web'],$referer))
	$pos = strpos($referer, $_configuration['root_web']);
	if ($pos === false)
	{
		$remhost = @ getHostByAddr($_SERVER['REMOTE_ADDR']);
		if ($remhost == $_SERVER['REMOTE_ADDR'])
			$remhost = "Unknown"; // don't change this
		$reallyNow = time();
		$sql = "INSERT INTO `".$TABLETRACK_OPEN."`
		
						(`open_remote_host`,
						 `open_agent`,
						 `open_referer`,
						 `open_date`)
		
						VALUES
						('".$remhost."',
						 '".$_SERVER['HTTP_USER_AGENT']."', '".$referer."', FROM_UNIXTIME($reallyNow) )";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		//$mysql_query($sql);
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
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$reallyNow = time();
	$sql = "INSERT INTO `".$TABLETRACK_LOGIN."`
	
				(`login_user_id`,
				 `login_ip`,
				 `login_date`)
	
				 VALUES
					('".$_user['user_id']."',
					'".$_SERVER['REMOTE_ADDR']."',
					FROM_UNIXTIME(".$reallyNow."))";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	//$mysql_query($sql);
	//return 0;
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
	if ($_user['user_id'])
	{
		$user_id = "'".$_user['user_id']."'";
	}
	else // anonymous
		{
		$user_id = "NULL";
	}
	$sql = "INSERT INTO `".$TABLETRACK_ACCESS."`
	
				(`access_user_id`,
				 `access_cours_code`,
				 `access_date`)
	
				VALUES
	
				(".$user_id.",
				'".$_cid."',
				FROM_UNIXTIME(".$reallyNow."))";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	// added for "what's new" notification
	$sql = "   UPDATE `$TABLETRACK_LASTACCESS`
	                    SET access_date = FROM_UNIXTIME($reallyNow)
						WHERE `access_user_id` = ".$user_id." AND `access_cours_code` = '".$_cid."' AND `access_tool` IS NULL AND `access_session_id`=".$id_session;
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_affected_rows() == 0)
	{
		$sql = "	INSERT INTO `$TABLETRACK_LASTACCESS`
		                	    (`access_user_id`,`access_cours_code`,`access_date`, access_session_id)
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
	$user_id = $_user['user_id'] ? "'".$_user['user_id']."'" : "NULL"; // "NULL" is anonymous
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
			$sql = "INSERT INTO `".$TABLETRACK_ACCESS."`
							(`access_user_id`,
							 `access_cours_code`,
							 `access_tool`,
							 `access_date`)
			
							VALUES
			
							(".$user_id.",".// Don't add ' ' around value, it's already done.
	"'".$_cid."' ,
					'".htmlspecialchars($tool, ENT_QUOTES)."',
					FROM_UNIXTIME(".$reallyNow."))";
		$res = api_sql_query($sql,__FILE__,__LINE__);
	}
	// "what's new" notification
	$sql = "   UPDATE `$TABLETRACK_LASTACCESS`
						SET access_date = FROM_UNIXTIME($reallyNow)
						WHERE `access_user_id` = ".$user_id." AND `access_cours_code` = '".$_cid."' AND `access_tool` = '".htmlspecialchars($tool, ENT_QUOTES)."' AND `access_session_id`=".$id_session;
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_affected_rows() == 0)
	{
		$sql = "INSERT INTO `$TABLETRACK_LASTACCESS`
							(`access_user_id`,`access_cours_code`,`access_tool`, `access_date`, `access_session_id`)
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
	}
	else // anonymous
		{
		$user_id = "NULL";
	}
	$sql = "INSERT INTO `".$TABLETRACK_DOWNLOADS."`
				(
				 `down_user_id`,
				 `down_cours_id`,
				 `down_doc_path`,
				 `down_date`
				)
	
				VALUES
				(
				 ".$user_id.",
				 '".$_cid."',
				 '".htmlspecialchars($doc_url, ENT_QUOTES)."',
				 FROM_UNIXTIME(".$reallyNow.")
				)";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	//$mysql_query($sql);
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
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$reallyNow = time();
	if ($_user['user_id'])
	{
		$user_id = "'".$_user['user_id']."'";
	}
	else // anonymous
		{
		$user_id = "NULL";
	}
	$sql = "INSERT INTO `".$TABLETRACK_UPLOADS."`
				(
				 `upload_user_id`,
				 `upload_cours_id`,
				 `upload_work_id`,
				 `upload_date`
				)
	
				VALUES
				(
				 ".$user_id.",
				 '".$_cid."',
				 '".$doc_id."',
				 FROM_UNIXTIME(".$reallyNow.")
				)";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	//$mysql_query($sql);
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
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$reallyNow = time();
	if ($_user['user_id'])
	{
		$user_id = "'".$_user['user_id']."'";
	}
	else // anonymous
		{
		$user_id = "NULL";
	}
	$sql = "INSERT INTO `".$TABLETRACK_LINKS."`
				(
				 `links_user_id`,
				 `links_cours_id`,
				 `links_link_id`,
				 `links_date`
				)
	
				VALUES
				(
				 ".$user_id.",
				 '".$_cid."',
				 '".$link_id."',
				 FROM_UNIXTIME(".$reallyNow.")
				)";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	//$mysql_query($sql);
	return 1;
}

/**
 * @param exo_id ( id in courseDb exercices table )
 * @param result ( score @ exercice )
 * @param weighting ( higher score )
 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @desc Record result of user when an exercice was done
*/
function event_exercice($exo_id, $score, $weighting)
{
	global $_configuration;	
	global $_user;
	global $_cid;
	global $TABLETRACK_EXERCICES;
	global $origin, $learnpath_id, $learnpath_item_id;
	
	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$reallyNow = time();
	if ($_user['user_id'])
	{
		$user_id = "'".$_user['user_id']."'";
	}
	else // anonymous
		{
		$user_id = "NULL";
	}
	$sql = "INSERT INTO `".$TABLETRACK_EXERCICES."`
			  (
			   `exe_user_id`,
			   `exe_cours_id`,
			   `exe_exo_id`,
			   `exe_result`,
			   `exe_weighting`,
			   `exe_date`
			  )
	
			  VALUES
			  (
			  ".$user_id.",
			   '".$_cid."',
			   '".$exo_id."',
			   '".$score."',
			   '".$weighting."',
			   FROM_UNIXTIME(".$reallyNow.")
			  )";
	$tbl_learnpath_user = Database::get_course_table(LEARNPATH_USER_TABLE);
	if ($origin == 'learnpath')
	{
		if ($user_id == "NULL")
		{
			$user_id = '0';
		}
		$sql2 = "update $tbl_learnpath_user set score='$score' where (user_id=$user_id and learnpath_id='$learnpath_id' and learnpath_item_id='$learnpath_item_id')";
		$res2 = api_sql_query($sql2,__FILE__,__LINE__);
	}
	$res = api_sql_query($sql,__FILE__,__LINE__);
	//$mysql_query($sql);
	//return 0;
}

function exercise_attempt($score,$answer,$quesId,$exeId,$j)
{
	global $_configuration;	
	global $_user;
	global $_cid;
	global $TABLETRACK_ATTEMPT;
	global $origin, $learnpath_id, $learnpath_item_id;
	
	// if tracking is disabled record nothing
	if (!$_configuration['tracking_enabled'])
	{
		return 0;
	}

	$reallyNow = time();
	if ($_user['user_id'])
	{
		$user_id = "'".$_user['user_id']."'";
	}
	else // anonymous
		{
		$user_id = "NULL";
		}
	$sql = "INSERT INTO `".$TABLETRACK_ATTEMPT."` 
			  (`exe_id`,
			   `user_id`,
			   `question_id`,
			   `answer`,
			   `marks`,
			   `course_code`,
			   `position`
			   
			  )
	
			  VALUES
			  (
			  ".$exeId.",
			  ".$user_id.",
			   '".$quesId."',
			   '".addslashes($answer)."',
			   '".$score."',
			   '".$_cid."',
			   '".$j."'
			  
			   			    )";
	$tbl_learnpath_user = Database::get_course_table(LEARNPATH_USER_TABLE);
	if ($origin == 'learnpath')
	{
		if ($user_id == "NULL")
		{
			$user_id = '0';
		}
		$sql2 = "update $tbl_learnpath_user set score='$score' where (user_id=$user_id and learnpath_id='$learnpath_id' and learnpath_item_id='$learnpath_item_id')";
		$res2 = api_sql_query($sql2,__FILE__,__LINE__);
	}
	//$res = api_sql_query($sql,__FILE__,__LINE__);
	$res = mysql_query($sql) or die(mysql_error());
	//return 0;
}
?>
