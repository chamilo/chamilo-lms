<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2006 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)
    Copyright (c) Sebastien Piraux  <piraux_seb@hotmail.com>

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

// TODO: Is this file deprecated?

/**
==============================================================================
* @package dokeos.tracking
* @todo clean code - structure is unclear and difficult to modify
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$uInfo = $_REQUEST['uInfo'];
$view = $_REQUEST['view'];
// name of the language file that needs to be included
$language_file = 'tracking';

include('../inc/global.inc.php');

// Roles and rights system
$user_id = api_get_user_id();
$course_id = api_get_course_id();

/*
$role_id = RolesRights::get_local_user_role_id($user_id, $course_id);
$location_id = RolesRights::get_course_tool_location_id($course_id, TOOL_TRACKING);
$is_allowed = RolesRights::is_allowed_which_rights($role_id, $location_id);

//block users without view right
RolesRights::protect_location($role_id, $location_id);
*/
//YW Hack security to quick fix RolesRights bug
$is_allowed = true;
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
include(api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php');
include(api_get_path(LIBRARY_PATH).'course.lib.php');
include(api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php');

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
// charset determination
if ($_GET['scormcontopen'])
{
	$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
	$contopen = (int) $_GET['scormcontopen'];
	$sql = "SELECT default_encoding FROM $tbl_lp WHERE id = ".$contopen;
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_array($res);
	$lp_charset = $row['default_encoding'];
	//header('Content-Type: text/html; charset='. $row['default_encoding']);
}


/*
$interbreadcrumb[]= array ("url"=>"../group/group.php", "name"=> get_lang('BredCrumpGroups'));
$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=$_gid", "name"=> get_lang('BredCrumpGroupSpace'));
*/

if($uInfo)
{
	$interbreadcrumb[]= array ("url"=>"../user/userInfo.php?uInfo=$uInfo", "name"=> get_lang('BredCrumpUsers'));
}

$nameTools = get_lang('ToolName');


/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$is_allowedToTrack = $is_courseAdmin;
$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_id);

// Database Table Definitions
$TABLECOURSUSER	        	= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLEUSER	        		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_course_user 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session 				= Database::get_main_table(TABLE_MAIN_SESSION);

$TABLETRACK_ACCESS      	= $_configuration['statistics_database']."`.`track_e_access";
$TABLETRACK_LINKS       	= $_configuration['statistics_database']."`.`track_e_links";
$TABLETRACK_LOGIN       	= $_configuration['statistics_database']."`.`track_e_login";
$TABLETRACK_DOWNLOADS   	= $_configuration['statistics_database']."`.`track_e_downloads";
$TABLETRACK_UPLOADS     	= $_configuration['statistics_database']."`.`track_e_uploads";
$TABLETRACK_EXERCICES   	= $_configuration['statistics_database']."`.`track_e_exercices";
$TABLETRACK_HOTPOTATOES		= $_configuration['statistics_database']."`.`track_e_hotpotatoes";

$TABLECOURSE_LINKS			= Database::get_course_table(TABLE_LINK);
$TABLECOURSE_WORK       	= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$TABLECOURSE_GROUPSUSER 	= Database::get_course_table(TABLE_GROUP_USER);
$TABLECOURSE_EXERCICES  	= Database::get_course_table(TABLE_QUIZ_TEST);
//$TBL_TRACK_HOTPOTATOES  	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);


if(api_get_setting('use_session_mode') == "true") {
	$sql = "SELECT 1
			FROM $tbl_session_course_user AS session_course_user
			INNER JOIN $tbl_session AS session
				ON session_course_user.id_session = session.id
				AND ((date_start<=NOW()
				AND date_end>=NOW())
				OR (date_start='0000-00-00' AND date_end='0000-00-00'))
			WHERE id_session='".$_SESSION['id_session']."' AND course_code='$_cid'";
	//echo $sql;
	$result=api_sql_query($sql,__FILE__,__LINE__);
	if(!mysql_num_rows($result)){
		$disabled = true;
	}
}

$tbl_learnpath_main = Database::get_course_table(TABLE_LP_MAIN);
$tbl_learnpath_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_learnpath_view = Database::get_course_table(TABLE_LP_VIEW);
$tbl_learnpath_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// the variables for the days and the months
// Defining the shorts for the days
// TODO: The function myEnc() should be eliminated. The following arrays should be constructed using the correspondent API-functions in the internationalization library.
$DaysShort = array (myEnc(get_lang("SundayShort")), myEnc(get_lang("MondayShort")), myEnc(get_lang("TuesdayShort")), myEnc(get_lang("WednesdayShort")), myEnc(get_lang("ThursdayShort")), myEnc(get_lang("FridayShort")), myEnc(get_lang("SaturdayShort")));
// Defining the days of the week to allow translation of the days
$DaysLong = array (myEnc(get_lang("SundayLong")), myEnc(get_lang("MondayLong")), myEnc(get_lang("TuesdayLong")), myEnc(get_lang("WednesdayLong")), myEnc(get_lang("ThursdayLong")), myEnc(get_lang("FridayLong")), myEnc(get_lang("SaturdayLong")));
// Defining the months of the year to allow translation of the months
$MonthsLong = array (myEnc(get_lang("JanuaryLong")), myEnc(get_lang("FebruaryLong")), myEnc(get_lang("MarchLong")), myEnc(get_lang("AprilLong")), myEnc(get_lang("MayLong")), myEnc(get_lang("JuneLong")), myEnc(get_lang("JulyLong")), myEnc(get_lang("AugustLong")), myEnc(get_lang("SeptemberLong")), myEnc(get_lang("OctoberLong")), myEnc(get_lang("NovemberLong")), myEnc(get_lang("DecemberLong")));
// Defining the months of the year to allow translation of the months
$MonthsShort = array (myEnc(get_lang("JanuaryShort")), myEnc(get_lang("FebruaryShort")), myEnc(get_lang("MarchShort")), myEnc(get_lang("AprilShort")), myEnc(get_lang("MayShort")), myEnc(get_lang("JuneShort")), myEnc(get_lang("JulyShort")), myEnc(get_lang("AugustShort")), myEnc(get_lang("SeptemberShort")), myEnc(get_lang("OctoberShort")), myEnc(get_lang("NovemberShort")), myEnc(get_lang("DecemberShort")));

//$is_allowedToTrack = $is_groupTutor; // allowed to track only user of one group
//$is_allowedToTrackEverybodyInCourse = $is_allowed[EDIT_RIGHT]; // allowed to track all students in course
//YW hack security to fix RolesRights bug
$is_allowedToTrack = true; // allowed to track only user of one group
$is_allowedToTrackEverybodyInCourse = $is_allowedToTrack; // allowed to track all students in course

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
 * Shortcut function to use htmlentities on many, many strings in this script
 * @param		string	String in a supposed encoding
 * @param		string	Supposed initial encoding (default: 'ISO-8859-15')
 * @return	string	HTML string (no encoding dependency)
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
function myEnc($isostring,$supposed_encoding='ISO-8859-15')
{
	return api_htmlentities($isostring,ENT_QUOTES,$supposed_encoding);
}

/**
* Displays the number of logins every month for a specific user in a specific course.
*/
function display_login_tracking_info($view, $user_id, $course_id)
{
	$MonthsLong = $GLOBALS['MonthsLong'];
	$track_access_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
	$tempView = $view;
	if(substr($view,0,1) == '1')
	{
		$new_view = substr_replace($view,'0',0,1);
		$title[1]= myEnc(get_lang('LoginsAndAccessTools')).myEnc(get_lang('LoginsDetails'));

		$sql = "SELECT UNIX_TIMESTAMP(`access_date`), count(`access_date`)
					FROM $track_access_table
					WHERE `access_user_id` = '$user_id'
					AND `access_cours_code` = '".$course_id."'
					GROUP BY YEAR(`access_date`),MONTH(`access_date`)
					ORDER BY YEAR(`access_date`),MONTH(`access_date`) ASC";

		//$results = getManyResults2Col($sql);
		$results = getManyResults3Col($sql);

		$title_line= myEnc(get_lang('LoginsTitleMonthColumn')).';'.myEnc(get_lang('LoginsTitleCountColumn'))."\n";
		$line='';
		$total = 0;
		if (is_array($results))
		{
			for($j = 0 ; $j < count($results) ; $j++)
			{
				$line .= $results[$j][0].';'.$results[$j][1]."\n";
				$total = $total + $results[$j][1];
			}
		$line .= myEnc(get_lang('Total')).";".$total."\n";
		}
		else
		{
			$line= myEnc(get_lang('NoResult'))."</center></td>";
		}
	}
	else
	{
		$new_view = substr_replace($view,'1',0,1);
	}
	return array($title_line, $line);
}

/**
* Displays the exercise results for a specific user in a specific course.
* @todo remove globals
*/
function display_exercise_tracking_info($view, $user_id, $course_id)
{
	global $TABLECOURSE_EXERCICES, $TABLETRACK_EXERCICES, $TABLETRACK_HOTPOTATOES;
	if(substr($view,1,1) == '1')
	{
		$new_view = substr_replace($view,'0',1,1);

		$title[1]= myEnc(get_lang('ExercicesDetails'));
		$line='';

		$sql = "SELECT `ce`.`title`, `te`.`exe_result` , `te`.`exe_weighting`, UNIX_TIMESTAMP(`te`.`exe_date`)
			FROM $TABLECOURSE_EXERCICES AS ce , `$TABLETRACK_EXERCICES` AS `te`
			WHERE `te`.`exe_cours_id` = '$course_id'
				AND `te`.`exe_user_id` = '$user_id'
				AND `te`.`exe_exo_id` = `ce`.`id`
			ORDER BY `ce`.`title` ASC, `te`.`exe_date` ASC";

		$hpsql = "SELECT `te`.`exe_name`, `te`.`exe_result` , `te`.`exe_weighting`, UNIX_TIMESTAMP(`te`.`exe_date`)
			FROM `$TABLETRACK_HOTPOTATOES` AS te
			WHERE `te`.`exe_user_id` = '$user_id' AND `te`.`exe_cours_id` = '$course_id'
			ORDER BY `te`.`exe_cours_id` ASC, `te`.`exe_date` ASC";

		$hpresults = getManyResultsXCol($hpsql, 4);

		$NoTestRes = 0;
		$NoHPTestRes = 0;

		$results = getManyResultsXCol($sql, 4);
		$title_line=myEnc(get_lang('ExercicesTitleExerciceColumn')).";".myEnc(get_lang('Date')).';'.myEnc(get_lang('ExercicesTitleScoreColumn'))."\n";

		if (is_array($results))
		{
			for($i = 0; $i < sizeof($results); $i++)
			{
				$display_date = format_locale_date(get_lang('dateTimeFormatLong'), $results[$i][3]);
				$line .= $results[$i][0].";".$display_date.";".$results[$i][1]." / ".$results[$i][2]."\n";
			}
		}
		else // istvan begin
		{
			$NoTestRes = 1;
		}

		// The Result of Tests
		if(is_array($hpresults))
		{
			for($i = 0; $i < sizeof($hpresults); $i++)
			{
				$title = GetQuizName($hpresults[$i][0],'');

				if ($title == '')
					$title = basename($hpresults[$i][0]);

				$display_date = format_locale_date(get_lang('dateTimeFormatLong'), $hpresults[$i][3]);
				$line .= $title.';'.$display_date.';'.$hpresults[$i][1].'/'.$hpresults[$i][2]."\n";
			}
		}
		else
		{
			$NoHPTestRes = 1;
		}

		if ($NoTestRes == 1 && $NoHPTestRes == 1)
		{
			$line=get_lang('NoResult');
		}
	}
	else
	{
		$new_view = substr_replace($view,'1',1,1);

	}
	return array($title_line, $line);
}

/**
* Displays the student publications for a specific user in a specific course.
* @todo remove globals
*/
function display_student_publications_tracking_info($view, $user_id, $course_id)
{
	global $TABLETRACK_UPLOADS, $TABLECOURSE_WORK, $dateTimeFormatLong;
	if(substr($view,2,1) == '1')
	{
		$new_view = substr_replace($view,'0',2,1);
		$sql = "SELECT `u`.`upload_date`, `w`.`title`, `w`.`author`,`w`.`url`
				FROM `$TABLETRACK_UPLOADS` `u` , $TABLECOURSE_WORK `w`
				WHERE `u`.`upload_work_id` = `w`.`id`
					AND `u`.`upload_user_id` = '$user_id'
					AND `u`.`upload_cours_id` = '$course_id'
				ORDER BY `u`.`upload_date` DESC";
		$results = getManyResultsXCol($sql,4);

		$title[1]=myEnc(get_lang('WorksDetails'));
		$line='';
		$title_line=myEnc(get_lang('WorkTitle')).";".myEnc(get_lang('WorkAuthors')).";".myEnc(get_lang('Date'))."\n";

		if (is_array($results))
		{
			for($j = 0 ; $j < count($results) ; $j++)
			{
				$pathToFile = api_get_path(WEB_COURSE_PATH).$_course['path']."/".$results[$j][3];
				$timestamp = strtotime($results[$j][0]);
				$beautifulDate = format_locale_date($dateTimeFormatLong,$timestamp);
				$line .= $results[$j][1].";".$results[$j][2].";".$beautifulDate."\n";
			}

		}
		else
		{
			$line= myEnc(get_lang('NoResult'));
		}
	}
	else
	{
		$new_view = substr_replace($view,'1',2,1);
	}
	return array($title_line, $line);
}

/**
* Displays the links followed for a specific user in a specific course.
* @todo remove globals
*/
function display_links_tracking_info($view, $user_id, $course_id)
{
	global $TABLETRACK_LINKS, $TABLECOURSE_LINKS;
	if(substr($view,3,1) == '1')
	{
		$new_view = substr_replace($view,'0',3,1);
		$title[1]=myEnc(get_lang('LinksDetails'));
		$sql = "SELECT `cl`.`title`, `cl`.`url`
					FROM `$TABLETRACK_LINKS` AS sl, $TABLECOURSE_LINKS AS cl
					WHERE `sl`.`links_link_id` = `cl`.`id`
						AND `sl`.`links_cours_id` = '$course_id'
						AND `sl`.`links_user_id` = '$user_id'
					GROUP BY `cl`.`title`, `cl`.`url`";
		$results = getManyResults2Col($sql);
		$title_line= myEnc(get_lang('LinksTitleLinkColumn'))."\n";
		if (is_array($results))
		{
			for($j = 0 ; $j < count($results) ; $j++)
			{
					$line .= $results[$j][0]."\n";

			}

		}
		else
		{
			$line=myEnc(get_lang('NoResult'));
		}
	}
	else
	{
		$new_view = substr_replace($view,'1',3,1);
	}
	return array($title_line, $line);
}

/**
* Displays the documents downloaded for a specific user in a specific course.
*/
function display_document_tracking_info($view, $user_id, $course_id)
{
	$downloads_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
	if(substr($view,4,1) == '1')
	{
		$new_view = substr_replace($view,'0',4,1);
		$title[1]= myEnc(get_lang('DocumentsDetails'));

		$sql = "SELECT `down_doc_path`
					FROM $downloads_table
					WHERE `down_cours_id` = '$course_id'
						AND `down_user_id` = '$user_id'
					GROUP BY `down_doc_path`";

		$results = getManyResults1Col($sql);
		$title_line = myEnc(get_lang('DocumentsTitleDocumentColumn'))."\n";
		if (is_array($results))
		{
			for($j = 0 ; $j < count($results) ; $j++)
			{
					$line .= $results[$j]."\n";
			}

		}
		else
		{
			$line=myEnc(get_lang('NoResult'));
		}
	}
	else
	{
		$new_view = substr_replace($view,'1',4,1);
	}
	return array($title_line, $line);
}


/*
==============================================================================
		MAIN SECTION
==============================================================================
*/
$title[0]='';
$title[1]='';
$line='';
$title_line='';

// check if uid is tutor of this group
if( ( $is_allowedToTrack || $is_allowedToTrackEverybodyInCourse ) && $_configuration['tracking_enabled'] )
{
	if(!$uInfo && !isset($uInfo) )
	{
		/***************************************************************************
		*
		*		Display list of user of this group
		*
		***************************************************************************/

		if( $is_allowedToTrackEverybodyInCourse )
		{
			// if user can track everybody : list user of course
			if(api_get_setting('use_session_mode')) {
				$sql = "SELECT count(user_id)
							FROM $TABLECOURSUSER
							WHERE `course_code` = '$_cid'";
			}
			else {
				$sql = "SELECT count(id_user)
							FROM $tbl_session_course_user
							WHERE `course_code` = '$_cid'";
			}
		}
		else
		{
			// if user can only track one group : list users of this group
			$sql = "SELECT count(user)
						FROM $TABLECOURSE_GROUPSUSER
						WHERE `group_id` = '$_gid'";
		}
		$userGroupNb = getOneResult($sql);
		$step = 25; // number of student per page
		if ($userGroupNb > $step)
		{
			if(!isset($offset))
			{
					$offset=0;
			}

			$next     = $offset + $step;
			$previous = $offset - $step;

			$navLink = "";

			if ($previous >= 0)
			{
			}


			if ($next < $userGroupNb)
			{
			}

		}
		else
		{
			$offset = 0;
		}

		echo $navLink;

	if (!settype($offset, 'integer') || !settype($step, 'integer')) die('Offset or step variables are not integers.');	//sanity check of integer vars
		if( $is_allowedToTrackEverybodyInCourse )
		{
			// list of users in this course
			$sql = "SELECT `u`.`user_id`, `u`.`firstname`,`u`.`lastname`
						FROM $TABLECOURSUSER cu , $TABLEUSER u
						WHERE `cu`.`user_id` = `u`.`user_id`
							AND `cu`.`course_code` = '$_cid'
						LIMIT $offset,$step";
		}
		else
		{
			// list of users of this group
			$sql = "SELECT `u`.`user_id`, `u`.`firstname`,`u`.`lastname`
						FROM $TABLECOURSE_GROUPSUSER gu , $TABLEUSER u
						WHERE `gu`.`user_id` = `u`.`user_id`
							AND `gu`.`group_id` = '$_gid'
						LIMIT $offset,$step";
		}
		$list_users = getManyResults3Col($sql);
		for($i = 0 ; $i < sizeof($list_users) ; $i++)
		{
	}

	}
	else // if uInfo is set
	{
		/***************************************************************************
		*
		*		Informations about student uInfo
		*
		***************************************************************************/
		// these checks exists for security reasons, neither a prof nor a tutor can see statistics of a user from
		// another course, or group
		if( $is_allowedToTrackEverybodyInCourse )
		{
			// check if user is in this course
			$tracking_is_accepted = $is_course_member;
			$tracked_user_info = Database::get_user_info_from_id($uInfo);
			$title[0]=$tracked_user_info[1].'_'.$tracked_user_info[2];
		}
		else
		{
			// check if user is in the group of this tutor
			$sql = "SELECT `u`.`firstname`,`u`.`lastname`, `u`.`email`
						FROM $TABLECOURSE_GROUPSUSER gu , $TABLEUSER u
						WHERE `gu`.`user_id` = `u`.`user_id`
							AND `gu`.`group_id` = '$_gid'
							AND `u`.`user_id` = '$uInfo'";
			$query = api_sql_query($sql,__FILE__,__LINE__);
			$tracked_user_info = @mysql_fetch_assoc($query);
			if(is_array($tracked_user_info)) $tracking_is_accepted = true;

       		$title[0] = $tracked_user_info['firstname'].'_'.$tracked_user_info['lastname'];
		}

		if ($tracking_is_accepted)
		{
			$tracked_user_info['email'] == '' ? $mail_link = myEnc(get_lang('NoEmail')) : $mail_link = Display::encrypted_mailto_link($tracked_user_info['email']);

			if(!isset($view))
			{
				$view ='0000000';
			}
			//Logins
			list($title_line1, $line1) = display_login_tracking_info($view, $uInfo, $_cid);

			//Exercise results
			list($title_line2, $line2) = display_exercise_tracking_info($view, $uInfo, $_cid);

			//Student publications uploaded
			list($title_line3, $line3) = display_student_publications_tracking_info($view, $uInfo, $_cid);

			//Links usage
			list($title_line4, $line4) = display_links_tracking_info($view, $uInfo, $_cid);

			//Documents downloaded
			list($title_line5, $line5) = display_document_tracking_info($view, $uInfo, $_cid);

			$title_line = $title_line1.$title_line2.$title_line3.$title_line4.$title_line5;
			$line= $line1.$line2.$line3.$line4.$line5;
		}
		else
		{
			echo myEnc(get_lang('ErrorUserNotInGroup'));
		}


		/***************************************************************************
         *
         *		Scorm contents and Learning Path
         *
         ***************************************************************************/
         //TODO: scorm tools is in work and the logs will change in few days...
        /*if(substr($view,5,1) == '1')
        {
            $new_view = substr_replace($view,'0',5,1);
            $title[1]=myEnc(get_lang('ScormContentColumn'));
			$line ='';
            $sql = "SELECT id, name FROM $tbl_learnpath_main";
    		$result=api_sql_query($sql,__FILE__,__LINE__);
    	    $ar=Database::fetch_array($result);

          if (is_array($ar))
            {
    			while ($ar['id'] != '') {
    				$lp_title = stripslashes($ar['name']);
    				echo "<tr><td>";
    				echo "<a href='".api_get_self()."?view=".$view."&scormcontopen=".$ar['id']."&uInfo=$uInfo' class='specialLink'>$lp_title</a>";
    				echo "</td></tr>";
    				if ($ar['id']==$scormcontopen) { //have to list the students here
        					$contentId=$ar['id'];
							$sql3 = "SELECT iv.status, iv.score, i.title, iv.total_time " .
									"FROM $tbl_learnpath_item i " .
									"INNER JOIN $tbl_learnpath_item_view iv ON i.id=iv.lp_item_id " .
									"INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id=v.id " .
									"WHERE (v.user_id=$uInfo and v.lp_id=$contentId) ORDER BY v.id, i.id";
   							$result3=api_sql_query($sql3,__FILE__,__LINE__);
   						    $ar3=Database::fetch_array($result3);
                            if (is_array($ar3)) {
                                $title_line=myEnc(get_lang('ScormTitleColumn')).";".myEnc(get_lang('ScormStatusColumn')).";".myEnc(get_lang('ScormScoreColumn')).";".myEnc(get_lang('ScormTimeColumn'))."\n";

       							while ($ar3['status'] != '') {
									require_once('../newscorm/learnpathItem.class.php');
									$time = learnpathItem::get_scorm_time('php',$ar3['total_time']);
									$title = api_htmlentities($ar3['title'],ENT_QUOTES,$lp_charset);
       								$line .= $title.';'.$ar3['status'].';'.$ar3['score'].';'.$time."\n";
       								$ar3=Database::fetch_array($result3);
       							}
                            } else {
                                $line .= myEnc(get_lang('ScormNeverOpened'));
                            }
   					}
		    		$ar=Database::fetch_array($result);
    			}

            }
            else
            {
				$noscorm=true;
            }

			if ($noscorm) {
                $line=myEnc(get_lang('NoResult'));
			}
         }
        else
        {
            $new_view = substr_replace($view,'1',5,1);
        }*/

    }
	 /***************************************************************************
     *
     *		Export to a CSV file
     *		force the browser to save the file instead of opening it
     ***************************************************************************/

	$len = strlen($title_line.$line);
	header('Content-type: application/octet-stream');
	//header('Content-Type: application/force-download');
	header('Content-length: '.$len);
	$filename = html_entity_decode(str_replace(":","",str_replace(" ","_", $title[0].'_'.$title[1].'.csv')));
	if(preg_match("/MSIE 5.5/",$_SERVER['HTTP_USER_AGENT']))
	{
		header('Content-Disposition: filename= '.$filename);
	}
	else
	{
		header('Content-Disposition: attachment; filename= '.$filename);
	}
	if(strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
	{
		header('Pragma: ');
		header('Cache-Control: ');
		header('Cache-Control: public'); // IE cannot download from sessions without a cache
	}
	header('Content-Description: '.$filename);
	header('Content-transfer-encoding: binary');

	echo api_html_entity_decode($title_line, ENT_QUOTES, $charset);
	echo api_html_entity_decode($line, ENT_QUOTES, $charset);
	exit;


}
// not allowed
else
{
    if(!$_configuration['tracking_enabled'])
    {
        echo myEnc(get_lang('TrackingDisabled'));
    }
    else
    {
        api_not_allowed();
    }
}
