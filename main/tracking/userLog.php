<?php // $Id: userLog.php 20472 2009-05-11 10:02:06Z ivantcholakov $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
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
$view  = $_REQUEST['view'];
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
if (isset($_GET['scormcontopen'])) {
	$tbl_lp = Database::get_course_table('lp');
	$contopen = Database::escape_string($_GET['scormcontopen']);
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

if(isset($uInfo)) {
	$interbreadcrumb[]= array ('url'=>'../user/userInfo.php?uInfo='.Security::remove_XSS($uInfo), "name"=> api_ucfirst(get_lang('Users')));
}

$nameTools = get_lang('ToolName');

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>
/*<![CDATA[*/
td {border-bottom: thin dashed gray;}
/*]]>*/
</style>";

Display::display_header($nameTools,"Tracking");

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

$TABLECOURSE_LINKS			= Database::get_course_table(TABLE_LINK);
$TABLECOURSE_WORK       	= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$TABLECOURSE_GROUPSUSER 	= Database::get_course_table(TABLE_GROUP_USER);
$TABLECOURSE_EXERCICES  	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_TRACK_HOTPOTATOES  	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);

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

$tbl_learnpath_main = Database::get_course_table('lp');
$tbl_learnpath_item = Database::get_course_table('lp_item');
$tbl_learnpath_view = Database::get_course_table('lp_view');
$tbl_learnpath_item_view = Database::get_course_table('lp_item_view');

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// the variables for the days and the months
// Defining the shorts for the days
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
	if(substr($view,0,1) == '1') {
		$new_view = substr_replace($view,'0',0,1);
		echo "
			<tr>
				<td valign='top'>
				<font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font>" .
				"<b>".myEnc(get_lang('LoginsAndAccessTools'))."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".myEnc(get_lang('Close'))."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=10000'>".get_lang('ExportAsCSV')."</a>]
				</td>
			</tr>
			";
		echo "<tr><td style='padding-left : 40px;' valign='top'>".myEnc(get_lang('LoginsDetails'))."<br>";

		$sql = "SELECT UNIX_TIMESTAMP(access_date), count(access_date)
					FROM $track_access_table
					WHERE access_user_id = '".Database::escape_string($user_id)."'
					AND access_cours_code = '".Database::escape_string($course_id)."'
					GROUP BY YEAR(access_date),MONTH(access_date)
					ORDER BY YEAR(access_date),MONTH(access_date) ASC";

		echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
		//$results = getManyResults2Col($sql);
		$results = getManyResults3Col($sql);

		echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
		echo "<tr>
				<td class='secLine'>
				".myEnc(get_lang('LoginsTitleMonthColumn'))."
				</td>
				<td class='secLine'>
				".myEnc(get_lang('LoginsTitleCountColumn'))."
				</td>
			</tr>";
		$total = 0;
		if (is_array($results)) {
			for($j = 0 ; $j < count($results) ; $j++) {
				echo "<tr>";
				//echo "<td class='content'><a href='logins_details.php?uInfo=$user_id&reqdate=".$results[$j][0]."'>".$langMonthNames['long'][date("n", $results[$j][0])-1]." ".date("Y", $results[$j][0])."</a></td>";
				echo "<td class='content'><a href='logins_details.php?uInfo=".Security::remove_XSS($user_id)."&reqdate=".$results[$j][0]."&view=".Security::remove_XSS($view)."'>".$MonthsLong[date('n', $results[$j][0])-1].' '.date('Y', $results[$j][0])."</a></td>";
				echo "<td valign='top' align='right' class='content'>".$results[$j][1]."</td>";
				echo"</tr>";
				$total = $total + $results[$j][1];
			}
			echo "<tr>";
			echo "<td>".myEnc(get_lang('Total'))."</td>";
			echo "<td align='right' class='content'>".$total."</td>";
			echo"</tr>";
		} else {
			echo "<tr>";
			echo "<td colspan='2'><center>".myEnc(get_lang('NoResult'))."</center></td>";
			echo"</tr>";
		}
		echo "</table>";
		echo "</td></tr>";
	} else {
		$new_view = substr_replace($view,'1',0,1);
		echo "
			<tr>
				<td valign='top'>
				+<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".myEnc(get_lang('LoginsAndAccessTools'))."</a>
				</td>
			</tr>
		";
	}
}

/**
* Displays the exercise results for a specific user in a specific course.
* @todo remove globals
*/
function display_exercise_tracking_info($view, $user_id, $course_id)
{
	global $TABLECOURSE_EXERCICES, $TABLETRACK_EXERCICES;
	if(substr($view,1,1) == '1')
	{
		$new_view = substr_replace($view,'0',1,1);
		echo "<tr>
				<td valign='top'>
					<font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".myEnc(get_lang('ExercicesResults'))."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".myEnc(get_lang('Close'))."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=01000'>".get_lang('ExportAsCSV')."</a>]
				</td>
			</tr>";
		echo "<tr><td style='padding-left : 40px;' valign='top'>".myEnc(get_lang('ExercicesDetails'))."<br />";

		$sql = "SELECT ce.title, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
			FROM $TABLECOURSE_EXERCICES AS ce , $TABLETRACK_EXERCICES AS te
			WHERE te.exe_cours_id = '".Database::escape_string($course_id)."'
				AND te.exe_user_id = '".Database::escape_string($user_id)."'
				AND te.exe_exo_id = ce.id
			ORDER BY ce.title ASC, te.exe_date ASC";

		$hpsql = "SELECT te.exe_name, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
			FROM $TBL_TRACK_HOTPOTATOES AS te
			WHERE te.exe_user_id = '".Database::escape_string($user_id)."' AND te.exe_cours_id = '".Database::escape_string($course_id)."'
			ORDER BY te.exe_cours_id ASC, te.exe_date ASC";

		$hpresults = getManyResultsXCol($hpsql, 4);

		$NoTestRes = 0;
		$NoHPTestRes = 0;

		echo "<tr>\n<td style='padding-left : 40px;padding-right : 40px;'>\n";
		$results = getManyResultsXCol($sql, 4);
		echo "<table cellpadding='2' cellspacing='1' border='0' align='center'>\n";
		echo "
			<tr bgcolor='#E6E6E6'>
				<td>
				".myEnc(get_lang('ExercicesTitleExerciceColumn'))."
				</td>
				<td>
				".myEnc(get_lang('Date'))."
				</td>
				<td>
				".myEnc(get_lang('ExercicesTitleScoreColumn'))."
				</td>
			</tr>";

		if (is_array($results)) {
			for($i = 0; $i < sizeof($results); $i++) {
				$display_date = format_locale_date(get_lang('dateTimeFormatLong'), $results[$i][3]);
				echo "<tr>\n";
				echo "<td class='content'>".$results[$i][0]."</td>\n";
				echo "<td class='content'>".$display_date."</td>\n";
				echo "<td valign='top' align='right' class='content'>".$results[$i][1]." / ".$results[$i][2]."</td>\n";
				echo "</tr>\n";
			}
		} else {
			// istvan begin
			$NoTestRes = 1;
		}

		// The Result of Tests
		if(is_array($hpresults)) {
			for($i = 0; $i < sizeof($hpresults); $i++) {
				$title = GetQuizName($hpresults[$i][0],'');
				if ($title == '')
					$title = GetFileName($hpresults[$i][0]);
				$display_date = format_locale_date(get_lang('dateTimeFormatLong'), $hpresults[$i][3]);
?>
				<tr>
					<td class="content"><?php echo $title; ?></td>
					<td class="content" align="center"><?php echo $display_date; ?></td>
					<td class="content" align="center"><?php echo $hpresults[$i][1]; ?> / <?php echo $hpresults[$i][2]; ?></td>
				</tr>
<?php		}
		} else {
			$NoHPTestRes = 1;
		}

		if ($NoTestRes == 1 && $NoHPTestRes == 1) {
			echo "<tr>\n";
			echo "<td colspan='3'><center>".myEnc(get_lang('NoResult'))."</center></td>\n";
			echo "</tr>\n";
		}
		echo "</table>";
		echo "</td>\n</tr>\n";
	} else {
		$new_view = substr_replace($view,'1',1,1);
		echo "
			<tr>
				<td valign='top'>
					+<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=$user_id&view=".$new_view."' class='specialLink'>".myEnc(get_lang('ExercicesResults'))."</a>
				</td>
			</tr>";
	}
}

/**
* Displays the student publications for a specific user in a specific course.
* @todo remove globals
*/
function display_student_publications_tracking_info($view, $user_id, $course_id)
{
	global $TABLETRACK_UPLOADS, $TABLECOURSE_WORK, $dateTimeFormatLong, $_course; 
	if(substr($view,2,1) == '1') {
		$new_view = substr_replace($view,'0',2,1);
		echo "<tr>
					<td valign='top'>
					<font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".myEnc(get_lang('WorkUploads'))."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".myEnc(get_lang('Close'))."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00100'>".get_lang('ExportAsCSV')."</a>]
					</td>
			</tr>";
		echo "<tr><td style='padding-left : 40px;' valign='top'>".myEnc(get_lang('WorksDetails'))."<br>";
		$sql = "SELECT u.upload_date, w.title, w.author,w.url
							FROM $TABLETRACK_UPLOADS u , $TABLECOURSE_WORK w
							WHERE u.upload_work_id = w.id
								AND u.upload_user_id = '".Database::escape_string($user_id)."'
								AND u.upload_cours_id = '".Database::escape_string($course_id)."'
							ORDER BY u.upload_date DESC";
		echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
		$results = getManyResultsXCol($sql,4);
		echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
		echo "<tr>
				<td class='secLine' width='40%'>
				".myEnc(get_lang('WorkTitle'))."
				</td>
				<td class='secLine' width='30%'>
				".myEnc(get_lang('WorkAuthors'))."
				</td>
				<td class='secLine' width='30%'>
				".myEnc(get_lang('Date'))."
				</td>
			</tr>";
		if (is_array($results)) {
			for($j = 0 ; $j < count($results) ; $j++) {
				$pathToFile = api_get_path(WEB_COURSE_PATH).$_course['path']."/".$results[$j][3];
				$timestamp = strtotime($results[$j][0]);
				$beautifulDate = format_locale_date($dateTimeFormatLong,$timestamp);
				echo "<tr>";
				echo "<td class='content'>"
						."<a href ='".$pathToFile."'>".$results[$j][1]."</a>"
						."</td>";
				echo "<td class='content'>".$results[$j][2]."</td>";
				echo "<td class='content'>".$beautifulDate."</td>";
				echo"</tr>";
			}
		} else {
			echo "<tr>";
			echo "<td colspan='3'><center>".myEnc(get_lang('NoResult'))."</center></td>";
			echo"</tr>";
		}
		echo "</table>";
		echo "</td></tr>";
	} else {
		$new_view = substr_replace($view,'1',2,1);
		echo "
			<tr>
					<td valign='top'>
					+<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".myEnc(get_lang('WorkUploads'))."</a>
					</td>
			</tr>
		";
	}
}

/**
* Displays the links followed for a specific user in a specific course.
* @todo remove globals
*/
function display_links_tracking_info($view, $user_id, $course_id)
{
	global $TABLETRACK_LINKS, $TABLECOURSE_LINKS;
	if(substr($view,3,1) == '1') {
		$new_view = substr_replace($view,'0',3,1);
		echo "
			<tr>
					<td valign='top'>
					<font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".myEnc(get_lang('LinksAccess'))."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".myEnc(get_lang('Close'))."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00010'>".get_lang('ExportAsCSV')."</a>]
					</td>
			</tr>
		";
		echo "<tr><td style='padding-left : 40px;' valign='top'>".myEnc(get_lang('LinksDetails'))."<br>";
		$sql = "SELECT cl.title, cl.url
					FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
					WHERE sl.links_link_id = cl.id
						AND sl.links_cours_id = '".Database::escape_string($course_id)."'
						AND sl.links_user_id = '".Database::escape_string($user_id)."'
					GROUP BY cl.title, cl.url";
		echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
		$results = getManyResults2Col($sql);
		echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
		echo "<tr>
				<td class='secLine'>
				".myEnc(get_lang('LinksTitleLinkColumn'))."
				</td>
			</tr>";
		if (is_array($results)) {
			for($j = 0 ; $j < count($results) ; $j++) {
					echo "<tr>";
					echo "<td class='content'><a href='".$results[$j][1]."'>".$results[$j][0]."</a></td>";
					echo"</tr>";
			}
		} else {
			echo "<tr>";
			echo "<td ><center>".myEnc(get_lang('NoResult'))."</center></td>";
			echo"</tr>";
		}
		echo "</table>";
		echo "</td></tr>";
	} else {
		$new_view = substr_replace($view,'1',3,1);
		echo "
			<tr>
					<td valign='top'>
					+<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".myEnc(get_lang('LinksAccess'))."</a>
					</td>
			</tr>
		";
	}
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
		echo "
			<tr>
					<td valign='top'>
					<font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".myEnc(get_lang('DocumentsAccess'))."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".myEnc(get_lang('Close'))."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00001'>".get_lang('ExportAsCSV')."</a>]
					</td>
			</tr>
		";
		echo "<tr><td style='padding-left : 40px;' valign='top'>".myEnc(get_lang('DocumentsDetails'))."<br>";

		$sql = "SELECT down_doc_path
					FROM $downloads_table
					WHERE down_cours_id = '".Database::escape_string($course_id)."'
						AND down_user_id = '".Database::escape_string($user_id)."'
					GROUP BY down_doc_path";

		echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
		$results = getManyResults1Col($sql);
		echo "<table cellpadding='2' cellspacing='1' border='0' align='center'>";
		echo "<tr>
				<td class='secLine'>
				".myEnc(get_lang('DocumentsTitleDocumentColumn'))."
				</td>
			</tr>";
		if (is_array($results)) {
			for($j = 0 ; $j < count($results) ; $j++) {
					echo "<tr>";
					echo "<td class='content'>".$results[$j]."</td>";
					echo"</tr>";
			}
		} else {
			echo "<tr>";
			echo "<td><center>".myEnc(get_lang('NoResult'))."</center></td>";
			echo"</tr>";
		}
		echo "</table>";
		echo "</td></tr>";
	} else {
		$new_view = substr_replace($view,'1',4,1);
		echo "
			<tr>
					<td valign='top'>
					+<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".myEnc(get_lang('DocumentsAccess'))."</a>
					</td>
			</tr>
		";
	}
}


/*
==============================================================================
		MAIN SECTION
==============================================================================
*/
?>
<h3>
	<?php echo $nameTools ?>
</h3>
<h4>
	<?php echo myEnc(get_lang('StatsOfUser')); ?>
</h4>
<table width="100%" cellpadding="2" cellspacing="3" border="0">
<?php
// check if uid is tutor of this group
if( ( $is_allowedToTrack || $is_allowedToTrackEverybodyInCourse ) && $_configuration['tracking_enabled'] ) {
	if(!$uInfo && !isset($uInfo) ) {
		/***************************************************************************
		*
		*		Display list of user of this group
		*
		***************************************************************************/
		
		echo "<h4>".myEnc(get_lang('ListStudents'))."</h4>";
		if( $is_allowedToTrackEverybodyInCourse ) {
			// if user can track everybody : list user of course
			if(api_get_setting('use_session_mode')) {
				$sql = "SELECT count(user_id)
						FROM $TABLECOURSUSER
						WHERE course_code = '".Database::escape_string($_cid)."'";
			} else {
				$sql = "SELECT count(id_user)
						FROM $tbl_session_course_user
						WHERE course_code = '".Database::escape_string($_cid)."'";
			}
		} else {
			// if user can only track one group : list users of this group
			$sql = "SELECT count(user)
					FROM $TABLECOURSE_GROUPSUSER
					WHERE group_id = '".Database::escape_string($_gid)."'";
		}
		$userGroupNb = getOneResult($sql);
		$step = 25; // number of student per page
		if ($userGroupNb > $step) {
			if(!isset($offset)) {
					$offset=0;
			}

			$next     = $offset + $step;
			$previous = $offset - $step;

			$navLink = "<table width='100%' border='0'>\n"
					."<tr>\n"
							."<td align='left'>";

			if ($previous >= 0) {
					$navLink .= "<a href='".api_get_self()."?offset=$previous'>&lt;&lt; ".myEnc(get_lang('PreviousPage'))."</a>";
			}

			$navLink .= "</td>\n"
					."<td align='right'>";

			if ($next < $userGroupNb) {
					$navLink .= "<a href='".api_get_self()."?offset=$next'>".myEnc(get_lang('NextPage'))." &gt;&gt;</a>";
			}

			$navLink .= "</td>\n"
					."</tr>\n"
					."</table>\n";
		} else {
			$offset = 0;
		}
		echo $navLink;

	if (!settype($offset, 'integer') || !settype($step, 'integer')) die('Offset or step variables are not integers.');	//sanity check of integer vars
		if( $is_allowedToTrackEverybodyInCourse ) {
			// list of users in this course
			$sql = "SELECT u.user_id, u.firstname,u.lastname
						FROM $TABLECOURSUSER cu , $TABLEUSER u
						WHERE cu.user_id = u.user_id
							AND cu.course_code = '".Database::escape_string($_cid)."'
						LIMIT $offset,$step";
		}
		else
		{
			// list of users of this group
			$sql = "SELECT u.user_id, u.firstname,u.lastname
						FROM $TABLECOURSE_GROUPSUSER gu , $TABLEUSER u
						WHERE gu.user_id = u.user_id
							AND gu.group_id = '".Database::escape_string($_gid)."'
						LIMIT $offset,$step";
		}
		$list_users = getManyResults3Col($sql);
		echo 	"<table width='100%' cellpadding='2' cellspacing='1' border='0'>\n"
					."<tr align='center' valign='top' bgcolor='#E6E6E6'>\n"
					."<td align='left'>",myEnc(get_lang('UserName')),"</td>\n"
					."</tr>\n";
		for($i = 0 ; $i < sizeof($list_users) ; $i++) {
			echo    "<tr valign='top' align='center'>\n"
					."<td align='left'>"
					."<a href='".api_get_self()."?uInfo=",$list_users[$i][0],"'>"
					.$list_users[$i][1]," ",$list_users[$i][2]
					."</a>".
					"</td>\n";
		}
		echo        "</table>\n";

		echo $navLink;
	} else {
		// if uInfo is set
		
		/***************************************************************************
		*
		*		Informations about student uInfo
		*
		***************************************************************************/
		// these checks exists for security reasons, neither a prof nor a tutor can see statistics of a user from
		// another course, or group
		if( $is_allowedToTrackEverybodyInCourse ) {
			// check if user is in this course
			$tracking_is_accepted = $is_course_member;
			$tracked_user_info = Database::get_user_info_from_id($uInfo);
		} else {
			
			// check if user is in the group of this tutor
			$sql = "SELECT u.firstname,u.lastname, u.email
						FROM $TABLECOURSE_GROUPSUSER gu , $TABLEUSER u
						WHERE gu.user_id = u.user_id`
							AND gu.group_id = '".Database::escape_string($_gid)."'
							AND u.user_id = '".Database::escape_string($uInfo)."'";
			$query = api_sql_query($sql,__FILE__,__LINE__);
			$tracked_user_info = @mysql_fetch_assoc($query);
			if(is_array($tracked_user_info)) $tracking_is_accepted = true;
		}

		if ($tracking_is_accepted) {
			$tracked_user_info['email'] == '' ? $mail_link = myEnc(get_lang('NoEmail')) : $mail_link = Display::encrypted_mailto_link($tracked_user_info['email']);

			echo "<tr><td>";
			echo get_lang('informationsAbout').' :';
			echo "<ul>\n"
					."<li>".myEnc(get_lang('FirstName'))." : ".$tracked_user_info['firstname']."</li>\n"
					."<li>".myEnc(get_lang('LastName'))." : ".$tracked_user_info['lastname']."</li>\n"
					."<li>".myEnc(get_lang('Email'))." : ".$mail_link."</li>\n"
					."</ul>";
			echo "</td></tr>\n";

			// show all : number of 1 is equal to or bigger than number of categories
			// show none : number of 0 is equal to or bigger than number of categories
			echo "<tr>
					<td>
					[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($uInfo)."&view=1111111'>".myEnc(get_lang('ShowAll'))."</a>]
					[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($uInfo)."&view=0000000'>".myEnc(get_lang('ShowNone'))."</a>]".
					//"||[<a href='".api_get_self()."'>".myEnc(get_lang('BackToList'))."</a>]".
					"</td>
				</tr>
			";
			if(!isset($view))
			{
				$view ='0000000';
			}
			//Logins
			display_login_tracking_info($view, $uInfo, $_cid);

			//Exercise results
			display_exercise_tracking_info($view, $uInfo, $_cid);

			//Student publications uploaded
			display_student_publications_tracking_info($view, $uInfo, $_cid);

			//Links usage
			display_links_tracking_info($view, $uInfo, $_cid);

			//Documents downloaded
			display_document_tracking_info($view, $uInfo, $_cid);
		} else {
			echo myEnc(get_lang('ErrorUserNotInGroup'));
		}


		/***************************************************************************
         *
         *		Scorm contents and Learning Path
         *
         ***************************************************************************/
        if(substr($view,5,1) == '1') {
            $new_view = substr_replace($view,'0',5,1);
            echo "<tr>
                        <td valign='top'>
                        <font     color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".myEnc(get_lang('ScormAccess'))."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?view=".Security::remove_XSS($new_view)."&uInfo=".Security::remove_XSS($uInfo)."'>".myEnc(get_lang('Close'))."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=000001'>".get_lang('ExportAsCSV')."</a>]
                        </td>
                </tr>";

            $sql = "SELECT id, name FROM $tbl_learnpath_main";
    		$result=api_sql_query($sql,__FILE__,__LINE__);
    	    $ar=Database::fetch_array($result);
    	    
	    	echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
            echo "<table cellpadding='2' cellspacing='1' border='0' align='center'><tr>
    				                <td class='secLine'>
    								&nbsp;".myEnc(get_lang('ScormContentColumn'))."&nbsp;
    				                </td>
    		</tr>";
            if (is_array($ar)) {
    			while ($ar['id'] != '') {
    				$lp_title = stripslashes($ar['name']);
    				echo "<tr><td>";
    				echo "<a href='".api_get_self()."?view=".$view."&scormcontopen=".$ar['id']."&uInfo=".Security::remove_XSS($uInfo)."' class='specialLink'>$lp_title</a>";
    				echo "</td></tr>";
    				if ($ar['id']==$scormcontopen) { //have to list the students here
        					$contentId=$ar['id'];
							$sql3 = "SELECT iv.status, iv.score, i.title, iv.total_time " .
									"FROM $tbl_learnpath_item i " .
									"INNER JOIN $tbl_learnpath_item_view iv ON i.id=iv.lp_item_id " .
									"INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id=v.id " .
									"WHERE (v.user_id=".Database::escape_string($uInfo)." and v.lp_id=$contentId) ORDER BY v.id, i.id";
   							$result3=api_sql_query($sql3,__FILE__,__LINE__);
   						    $ar3=Database::fetch_array($result3);
                            if (is_array($ar3)) {
                                echo "<tr><td>&nbsp;&nbsp;&nbsp;</td>
       				                <td class='secLine'>
       				                &nbsp;".myEnc(get_lang('ScormTitleColumn'))."&nbsp;
       				                </td>
       				                <td class='secLine'>
       				                &nbsp;".myEnc(get_lang('ScormStatusColumn'))."&nbsp;
       				                </td>
       				                <td class='secLine'>
       				                &nbsp;".myEnc(get_lang('ScormScoreColumn'))."&nbsp;
       				                </td>
       				                <td class='secLine'>
       				                &nbsp;".myEnc(get_lang('ScormTimeColumn'))."&nbsp;
       				                </td>
       					            </tr>";
       							while ($ar3['status'] != '') {
									require_once('../newscorm/learnpathItem.class.php');
									$time = learnpathItem::get_scorm_time('php',$ar3['total_time']);
									$title = api_htmlentities($ar3['title'],ENT_QUOTES,$lp_charset);
       								echo "<tr><td>&nbsp;&nbsp;&nbsp;</td><td>";
       								echo "$title</td><td align=right>{$ar3['status']}</td><td     align=right>{$ar3['score']}</td><td align=right>$time</td>";
       								echo "</tr>";
       								$ar3=Database::fetch_array($result3);
       							}
                            } else {
                                echo "<tr>";
                                echo "<td colspan='3'><center>".myEnc(get_lang('ScormNeverOpened'))."</center></td>";
                                echo"</tr>";
                            }
   					}
		    		$ar=Database::fetch_array($result);
    			}
            } else {
				$noscorm=true;
            }

			if ($noscorm) {
                echo "<tr>";
                echo "<td colspan='3'><center>".myEnc(get_lang('NoResult'))."</center></td>";
                echo "</tr>";
			}
            echo "</table>";
            echo "</td></tr>";
        } else {
            $new_view = substr_replace($view,'1',5,1);
            echo "
                <tr>
                        <td valign='top'>
                        +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?view=".Security::remove_XSS($new_view)."&uInfo=".Security::remove_XSS($uInfo)."' class='specialLink'>".myEnc(get_lang('ScormAccess'))."</a>
                        </td>
                </tr>
            ";
        }

    }
} else {
	// not allowed
    if(!$_configuration['tracking_enabled'])
    {
        echo myEnc(get_lang('TrackingDisabled'));
    } else {
        api_not_allowed();
    }
}
?>
</table>
<?php
Display::display_footer();
?>
