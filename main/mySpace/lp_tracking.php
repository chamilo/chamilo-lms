<?php
/*
 * Created on 26 mars 07 by Eric Marguin
 *
 * Script to display the tracking of the students in the learning paths.
 */
 

$language_file = array ('registration', 'index', 'tracking', 'exercice', 'scorm', 'learnpath');
$cidReset = true;
include ('../inc/global.inc.php');
include_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
include_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH).'course.lib.php');
include_once('../newscorm/learnpath.class.php');
include_once('../newscorm/learnpathItem.class.php');
require_once (api_get_path(LIBRARY_PATH).'export.lib.inc.php');

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if($export_csv)
{
	ob_start();
}
$csv_content = array();

if(!CourseManager :: is_course_teacher($_user['user_id'], $_GET['course']) && !Tracking :: is_allowed_to_coach_student($_user['user_id'],$_GET['student_id']))
{
	Display::display_header('');
	api_not_allowed();
	Display::display_footer();
}

$_course = CourseManager :: get_course_information($_GET['course']);
$_course['dbNameGlu'] = $_configuration['table_prefix'] . $_course['db_name'] . $_configuration['db_glue'];
$cidReq = $_GET['course'];

if(!empty($_GET['origin']) && $_GET['origin'] == 'user_course')
{
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$_GET['course'], "name" => get_lang("Users"));
}
else if(!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course')
{
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$_GET['course'].'&studentlist=true', "name" => get_lang("Tracking"));
}
else
{
	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
 	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".$_GET['student_id'], "name" => get_lang("StudentDetails"));
 	$nameTools=get_lang("DetailsStudentInCourse");
}

$interbreadcrumb[] = array("url" => "myStudents.php?student=".$_GET['student_id']."&course=".$_GET['course']."&details=true&origin=".$_GET['origin'] , "name" => get_lang("DetailsStudentInCourse"));

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

$user_id = intval($_GET['student_id']);
$lp_id = intval($_GET['lp_id']);

$sql = 'SELECT name 
		FROM '.Database::get_course_table(TABLE_LP_MAIN, $_course['db_name']).'
		WHERE id='.$lp_id;
$rs = api_sql_query($sql, __FILE__, __LINE__);
$lp_title = mysql_result($rs, 0, 0);

$sql = 'SELECT lastname, firstname 
		FROM '.Database::get_main_table(TABLE_MAIN_USER).'
		WHERE user_id='.$user_id;
$rs = api_sql_query($sql, __FILE__, __LINE__);
$name = mysql_result($rs, 0, 0).' '.mysql_result($rs, 0, 1);

echo '<div align="left" style="float:left"><h4>'.$_course['title'].' - '.$lp_title.' - '.$name.'</h4></div>
	  <div align="right">
			<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
			<a href="'.api_get_self().'?export=csv&'.$_SERVER['QUERY_STRING'].'"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
		 </div>
	<div class="clear"></div>';

$list = learnpath :: get_flat_ordered_items_list($lp_id);

$origin = 'tracking';

ob_start();
include_once('../newscorm/lp_stats.php');
$tracking_content = ob_get_contents();
ob_end_clean();
echo utf8_decode($tracking_content);


Display :: display_footer();
?>

