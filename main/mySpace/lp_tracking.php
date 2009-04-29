<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008-2009 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/*
 * Created on 26 mars 07 by Eric Marguin
 * Script to display the tracking of the students in the learning paths.
 */
 
$language_file = array ('registration', 'index', 'tracking', 'exercice', 'scorm', 'learnpath');
$cidReset = true;
include ('../inc/global.inc.php');
include_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
include_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH).'course.lib.php');
include_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
include_once('../newscorm/learnpath.class.php');
include_once('../newscorm/learnpathItem.class.php');
require_once (api_get_path(LIBRARY_PATH).'export.lib.inc.php');

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if($export_csv)
{
	ob_start();
}
$csv_content = array();


$user_id = intval($_GET['student_id']);
$user_infos = UserManager :: get_user_info_by_id($user_id);
$name = $user_infos['firstname'].' '.$user_infos['lastname'];

if(!api_is_platform_admin(true) && !CourseManager :: is_course_teacher($_user['user_id'], $_GET['course']) && !Tracking :: is_allowed_to_coach_student($_user['user_id'],$_GET['student_id']) && $user_infos['hr_dept_id']!==$_user['user_id']) {
	Display::display_header('');
	api_not_allowed();
	Display::display_footer();
}

$_course = CourseManager :: get_course_information($_GET['course']);
$_course['dbNameGlu'] = $_configuration['table_prefix'] . $_course['db_name'] . $_configuration['db_glue'];
$cidReq = Security::remove_XSS($_GET['course']);

if(!empty($_GET['origin']) && $_GET['origin'] == 'user_course') {
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".Security::remove_XSS($_GET['course']), "name" => get_lang("Users"));
} else if(!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course') {
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".Security::remove_XSS($_GET['course']).'&studentlist=true&id_session='.$_SESSION['id_session'], "name" => get_lang("Tracking"));
} else {
	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
 	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student_id']), "name" => get_lang("StudentDetails"));
 	$nameTools=get_lang("DetailsStudentInCourse");
}

$interbreadcrumb[] = array("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student_id'])."&course=".Security::remove_XSS($_GET['course'])."&details=true&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("DetailsStudentInCourse"));
$nameTools = get_lang('LearningPathDetails');

$htmlHeadXtra[] = '
<style>
div.title {
	font-weight : bold;
	text-align : left;
}
div.mystatusfirstrow {
	font-weight : bold;
	text-align : left;
}
div.description {
	font-family : Arial, Helvetica, sans-serif;
	font-size: 10px;
	color: Silver;
}
 .data_table
  {
  	border-collapse: collapse;
  }
 .data_table th{
	padding-right: 0px;
  	border: 1px  solid gray;
  	background-color: #eef;
  }
  .data_table tr.row_odd
  {
  	background-color: #fafafa;
  }
  .data_table tr.row_odd:hover, .data_table tr.row_even:hover
  {
  	background-color: #f0f0f0;
  }
  .data_table tr.row_even
  {
  	background-color: #fff;
  }
 .data_table td
  {
  	padding: 5px;
	vertical-align: top;
  	border-bottom: 1px solid #b1b1b1;
  	border-right: 1px dotted #e1e1e1; 
  	border-left: 1px dotted #e1e1e1;
 }

 .margin_table
 {
	margin-left : 3px;
        width: 80%;
 }
 .margin_table td.title
 {
    background-color: #ffff99;
 }
 .margin_table td.content
 {
    background-color: #ddddff;
 }
</style>';

Display :: display_header($nameTools);

$lp_id = intval($_GET['lp_id']);

$sql = 'SELECT name 
		FROM '.Database::get_course_table(TABLE_LP_MAIN, $_course['db_name']).'
		WHERE id='.Database::escape_string($lp_id);
$rs = api_sql_query($sql, __FILE__, __LINE__);
$lp_title = Database::result($rs, 0, 0);	

echo '<div class ="actions"><div align="left" style="float:left;margin-top:2px;" ><strong>'.$_course['title'].' - '.$lp_title.' - '.$name.'</strong></div>
	  <div  align="right">
			<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
			<a href="'.api_get_self().'?export=csv&'.Security::remove_XSS($_SERVER['QUERY_STRING']).'"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
		 </div></div>
	<div class="clear"></div>';

$list = learnpath :: get_flat_ordered_items_list($lp_id);
$origin = 'tracking';

if($export_csv) {	
	include_once('../newscorm/lp_stats.php');	
	//Export :: export_table_csv($csv_content, 'reporting_student');
} else {
	ob_start();
	include_once('../newscorm/lp_stats.php');
	$tracking_content = ob_get_contents();
	ob_end_clean();
	echo utf8_decode($tracking_content);
}
Display :: display_footer();
?>