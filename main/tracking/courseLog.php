<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@author Thomas Depraetere
*	@author Hugues Peeters
*	@author Christophe Gesche
*	@author Sebastien Piraux
*	@author Toon Keppens (Vi-Host.net)
*
*	@package dokeos.tracking
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;
// name of the language file that needs to be included 

$language_file[] = 'tracking';
$language_file[] = 'scorm';

include('../inc/global.inc.php');


$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin;

if(!$is_allowedToTrack)
{
	Display :: display_header();
	api_not_allowed();
	Display :: display_footer();
}

//includes for SCORM and LP
require_once('../newscorm/learnpath.class.php');
require_once('../newscorm/learnpathItem.class.php');
require_once('../newscorm/learnpathList.class.php');
require_once('../newscorm/scorm.class.php');
require_once('../newscorm/scormItem.class.php');
require_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'export.lib.inc.php');



$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if($export_csv)
{
	ob_start();
}
$csv_content = array();

// charset determination
if ($_GET['scormcontopen'])
{
	$tbl_lp = Database::get_course_table('lp');
	$contopen = (int) $_GET['scormcontopen'];
	$sql = "SELECT default_encoding FROM $tbl_lp WHERE id = ".$contopen;
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_array($res);
	$lp_charset = $row['default_encoding'];
	//header('Content-Type: text/html; charset='. $row['default_encoding']);
}

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>

</style>";


/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
// regroup table names for maintenance purpose
$TABLETRACK_ACCESS      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$TABLETRACK_LINKS       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2    = Database::get_statistic_table("track_e_access");
$TABLECOURSUSER	        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE	        = Database::get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSE_LINKS      = Database::get_course_table(TABLE_LINK);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

//$table_scormdata = Database::get_scorm_table(TABLE_SCORM_SCO_DATA);
//$table_scormmain = Database::get_scorm_table(TABLE_SCORM_MAIN);
//$tbl_learnpath_main = Database::get_course_table(TABLE_LEARNPATH_MAIN);
//$tbl_learnpath_item = Database::get_course_table(TABLE_LEARNPATH_ITEM);
//$tbl_learnpath_chapter = Database::get_course_table(TABLE_LEARNPATH_CHAPTER);

$tbl_learnpath_main = Database::get_course_table('lp');
$tbl_learnpath_item = Database::get_course_table('lp_item');
$tbl_learnpath_view = Database::get_course_table('lp_view');
$tbl_learnpath_item_view = Database::get_course_table('lp_item_view');

$view = $_REQUEST['view'];

$nameTools = get_lang('Tracking');

Display::display_header($nameTools, "Tracking");
include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");
include("../resourcelinker/resourcelinker.inc.php");


 
$a_students = CourseManager :: get_student_list_from_course_code($_course['id'], true, $_SESSION['id_session']);
$nbStudents = count($a_students);

/**
 * count the number of students in this course (used for SortableTable)
 */
function count_student_in_course()
{
	global $nbStudents;
	return $nbStudents;
}



function sort_users($a, $b)
{
	global $tracking_column;
	if($a[$tracking_column] > $b[$tracking_column])
		return 1;
	else 
		return -1;
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/


if($_GET['studentlist'] == 'false')
{
	echo '<div style="float:left; clear:left">
			<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a>&nbsp;|
			'.get_lang('CourseTracking').'
		  </div>';
}
else
{
	echo '<div style="float:left; clear:left">
			'.get_lang('StudentsTracking').' |
			<a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a>&nbsp;
		  </div>';
}
echo '<div style="float:right; clear:right">
		<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>';
if($_GET['studentlist'] == 'false'){	
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&studentlist=false"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a></div>';
}
else{
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a></div>';
}
echo '<div class="clear"></div>';
if($_GET['studentlist'] == 'false')
{
	echo'<br /><br />';
	
	
	/***************************
	 * LEARNING PATHS
	 ***************************/
	 
	 echo "<div class='admin_section'>
				<h4>
					<img src='../img/scormbuilder.gif' align='absbottom'>&nbsp;".get_lang('AverageProgressInLearnpath')."
				</h4>
			<table class='data_table'>";
			
	$list = new LearnpathList($student);
	$flat_list = $list->get_flat_list();
	
	if($export_csv){
    	$temp=array(get_lang('AverageProgressInLearnpath'),'');
    	$csv_content[] = array('','');
    	$csv_content[] = $temp;
    }
	
	if(count($flat_list)>0)
	{
		foreach($flat_list as $lp_id => $lp)
		{
			$lp_avg_progress = 0;
			foreach($a_students as $student_id => $student)
			{
				
				// get the progress in learning pathes	
				$lp_avg_progress += learnpath::get_db_progress($lp_id,$student_id);
				
				
			}
			if($nbStudents > 0)
			{
				$lp_avg_progress = $lp_avg_progress / $nbStudents;
			}
			echo '<tr><td>'.$lp['lp_name'].'</td><td align="right">'.round($lp_avg_progress,1).' %</td></tr>';
			if($export_csv){
				$temp=array($lp['lp_name'],$lp_avg_progress);
				$csv_content[] = $temp;
			}
		}
	}
	else
	{
		echo '<tr><td>'.get_lang('NoLearningPath').'</td></tr>';
		if($export_csv){
    		$temp=array(get_lang('NoLearningPath'),'');
			$csv_content[] = $temp;
    	}
	}
	
	echo '</table></div>';
	
	
	echo '<div class="clear"></div>';
	
	
	/***************************
	 * EXERCICES
	 ***************************/
	 
	 echo "<div class='admin_section'>
				<h4>
					<img src='../img/quiz.gif' align='absbottom'>&nbsp;".get_lang('AverageResultsToTheExercices')." <a href='../exercice/exercice.php?".api_get_cidreq()."&show=result'>".get_lang('SeeDetail')."</a>
				</h4>
			<table class='data_table'>";
			
	$sql = "SELECT id, title
			FROM ".Database :: get_course_table(TABLE_QUIZ_TEST);
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	
	if($export_csv){
    	$temp=array(get_lang('AverageProgressInLearnpath'),'');
    	$csv_content[] = array('','');
    	$csv_content[] = $temp;
    }
	
	if(mysql_num_rows($rs)>0)
	{
		while($quiz = mysql_fetch_array($rs))
		{
			$quiz_avg_score = 0;
			
			// get the progress in learning pathes	
			$sql = 'SELECT exe_result , exe_weighting
					FROM '.Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES).'
					WHERE exe_exo_id = '.$quiz['id'].'
					ORDER BY exe_date DESC
					LIMIT 0, 1';
			$rsAttempt = api_sql_query($sql, __FILE__, __LINE__);
			$nb_attempts = 0;
			while($attempt = mysql_fetch_array($rsAttempt))
			{
				$nb_attempts++;
				$quiz_avg_score += $attempt['exe_result']/$attempt['exe_weighting']*100;
			}
			if($nb_attempts>0)
				$quiz_avg_score = $quiz_avg_score / $nb_attempts;
			
			echo '<tr><td>'.$quiz['title'].'</td><td align="right">'.round($quiz_avg_score,1).' %</td></tr>';
			if($export_csv){
				$temp=array($quiz['title'],$quiz_avg_score);
				$csv_content[] = $temp;
			}
		}
	}
	else
	{
		echo '<tr><td>'.get_lang('NoExercises').'</td></tr>';
		if($export_csv){
    		$temp=array(get_lang('NoExercises'),'');
			$csv_content[] = $temp;
    	}
	}
	
	echo '</table></div>';
	echo '<div class="clear"></div>';
	
	
	/**********************
	 * TOOLS
	 **********************/
	
	echo "<div class='admin_section'>
				<h4>
					<img src='../img/acces_tool.gif' align='absbottom'>&nbsp;".get_lang('ToolsMostUsed')."
				</h4>
			<table class='data_table'>";
			 
	$sql = "SELECT `access_tool`, COUNT(DISTINCT `access_user_id`),count( `access_tool` ) as count_access_tool
            FROM $TABLETRACK_ACCESS
            WHERE `access_tool` IS NOT NULL
                AND `access_cours_code` = '$_cid'
            GROUP BY `access_tool`
			ORDER BY count_access_tool DESC
			LIMIT 0, 3";
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	
	 if($export_csv){
    	$temp=array(get_lang('ToolsMostUsed'),'');
    	$csv_content[] = $temp;
    }
	
	while ($row = mysql_fetch_array($rs))
	{
		echo '	<tr>
					<td>'.get_lang(ucfirst($row['access_tool'])).'</td>
					<td align="right">'.$row['count_access_tool'].' '.get_lang('Clicks').'</td>
				</tr>';
		if($export_csv){
			$temp=array(get_lang(ucfirst($row['access_tool'])),$row['count_access_tool'].' '.get_lang('Clicks'));
			$csv_content[] = $temp;
		}
	}
	
	echo '</table></div>';
	
	echo '<div class="clear"></div>';
	
	
	/***************************
	 * DOCUMENTS
	 ***************************/
	 
	 echo "<div class='admin_section'>
				<h4>
					<img src='../img/documents.gif' align='absbottom'>&nbsp;".get_lang('DocumentsMostDownloaded')."
				</h4>
			<table class='data_table'>";
			
	$sql = "SELECT `down_doc_path`, COUNT(DISTINCT `down_user_id`), COUNT(`down_doc_path`) as count_down
            FROM $TABLETRACK_DOWNLOADS
            WHERE `down_cours_id` = '$_cid'
            GROUP BY `down_doc_path`
			ORDER BY count_down DESC
			LIMIT 0, 3";
    $rs = api_sql_query($sql, __FILE__, __LINE__);
    
    if($export_csv){
    	$temp=array(get_lang('DocumentsMostDownloaded'),'');
    	$csv_content[] = array('','');
    	$csv_content[] = $temp;
    }
    
    if(mysql_num_rows($rs)>0)
    {
	    while($row = mysql_fetch_array($rs))
	    {
	    	echo '	<tr>
						<td>'.$row['down_doc_path'].'</td>
						<td align="right">'.$row['count_down'].' '.get_lang('Clicks').'</td>
					</tr>';
					
			if($export_csv){
				$temp=array($row['down_doc_path'],$row['count_down'].' '.get_lang('Clicks'));
				$csv_content[] = $temp;
			}
	    }
    }
    else
    {
    	echo '<tr><td>'.get_lang('NoDocumentDownloaded').'</td></tr>';
    	if($export_csv){
    		$temp=array(get_lang('NoDocumentDownloaded'),'');
			$csv_content[] = $temp;
    	}
    }
	echo '</table></div>';
	
	echo '<div class="clear"></div>';
	
	
	/***************************
	 * LINKS
	 ***************************/
	 
	 echo "<div class='admin_section'>
				<h4>
					<img src='../img/link.gif' align='absbottom'>&nbsp;".get_lang('LinksMostClicked')."
				</h4>
			<table class='data_table'>";
			
	$sql = "SELECT `cl`.`title`, `cl`.`url`,count(DISTINCT `sl`.`links_user_id`), count(`cl`.`title`) as count_visits
            FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
            WHERE `sl`.`links_link_id` = `cl`.`id`
                AND `sl`.`links_cours_id` = '$_cid'
            GROUP BY `cl`.`title`, `cl`.`url`
			ORDER BY count_visits DESC
			LIMIT 0, 3";
    $rs = api_sql_query($sql, __FILE__, __LINE__);
    
    if($export_csv){
    	$temp=array(get_lang('LinksMostClicked'),'');
    	$csv_content[] = array('','');
    	$csv_content[] = $temp;
    }
    
    if(mysql_num_rows($rs)>0)
    {
	    while($row = mysql_fetch_array($rs))
	    {
	    	echo '	<tr>
						<td>'.$row['title'].'</td>
						<td align="right">'.$row['count_visits'].' '.get_lang('Clicks').'</td>
					</tr>';
			if($export_csv){
				$temp=array($row['title'],$row['count_visits'].' '.get_lang('Clicks'));
				$csv_content[] = $temp;
			}
	    }
    }
    else
    {
    	echo '<tr><td>'.get_lang('NoLinkVisited').'</td></tr>';
    	if($export_csv){
    		$temp=array(get_lang('NoLinkVisited'),'');
			$csv_content[] = $temp;
    	}
    }
	echo '</table></div>';
	
	
	echo '<div class="clear"></div>';	
	
	// send the csv file if asked
	if($export_csv)
	{
		ob_end_clean();
		Export :: export_table_csv($csv_content, 'reporting_course_tracking');
	}
	
}
// else display student list with all the informations
else {
	
	$tracking_column = isset($_GET['tracking_column']) ? $_GET['tracking_column'] : 0;
	$tracking_direction = isset($_GET['tracking_direction']) ? $_GET['tracking_direction'] : DESC;
	
	if(count($a_students)>0)
	{
		$table = new SortableTable('tracking', 'count_student_in_course');
		$table -> set_header(0, get_lang('OfficialCode'), false, 'align="center"');
		$table -> set_header(1, get_lang('LastName'), true, 'align="center"');
		$table -> set_header(2, get_lang('FirstName'), false, 'align="center"');
		$table -> set_header(3, get_lang('Time'),false);
		$table -> set_header(4, get_lang('Progress'),false);
		$table -> set_header(5, get_lang('Score'),false);	
		$table -> set_header(6, get_lang('Student_publication'),false);
		$table -> set_header(7, get_lang('Messages'),false);
		$table -> set_header(8, get_lang('LatestLogin'), false, 'align="center"');
		$table -> set_header(9, get_lang('Details'),false);
	     
	    if($export_csv)
		{
			$csv_content[] = array ( 
									get_lang('OfficialCode'),
									get_lang('LastName'),
									get_lang('FirstName'),
									get_lang('Time'),
									get_lang('Progress'),
									get_lang('Score'),
									get_lang('Student_publication'),
									get_lang('Messages'),
									get_lang('LatestLogin')
								   );
		}
	    
	    $all_datas = array();
	    $course_code = $_course['id'];
		foreach($a_students as $student_id => $student)
		{
			$student_datas = UserManager :: get_user_info_by_id($student_id);
			
			$avg_time_spent = $avg_student_score = $avg_student_progress = $total_assignments = $total_messages = 0 ;
			$nb_courses_student = 0;
			$avg_time_spent = Tracking :: get_time_spent_on_the_course($student_id, $course_code);
			$avg_student_score = Tracking :: get_avg_student_score($student_id, $course_code);
			$avg_student_progress = Tracking :: get_avg_student_progress($student_id, $course_code);
			$total_assignments = Tracking :: count_student_assignments($student_id, $course_code);
			$total_messages = Tracking :: count_student_messages($student_id, $course_code);
			
			$row = array();
			$row[] = $student_datas['official_code'];
			$row[] = $student_datas['lastname'];
			$row[] = 	$student_datas['firstname'];
			$row[] = api_time_to_hms($avg_time_spent);
			$row[] = $avg_student_progress.' %';
			$row[] = $avg_student_score.' %';		
			$row[] = $total_assignments;
			$row[] = $total_messages;
			$row[] = Tracking :: get_last_connection_date_on_the_course($student_id, $course_code);
			
			if($export_csv)
			{
				$row[8] = strip_tags($row[8]);
				$csv_content[] = $row;
			}
			
			$row[] = '<a href="../mySpace/myStudents.php?student='.$student_id.'&details=true&course='.$course_code.'&origin=tracking_course"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			
			$all_datas[] = $row;		
	
		}
		
		usort($all_datas, 'sort_users');
		if($tracking_direction == 'DESC')
			rsort($all_datas);
		
		$page = $table->get_pager()->getCurrentPageID();
		$all_datas = array_slice($all_datas, ($page-1)*$table -> per_page, $table -> per_page);
		
		if($export_csv)
		{
			usort($csv_content, 'sort_users');
		}
		
		foreach($all_datas as $row)
		{
			$table -> addRow($row,'align="right"');	
		}
		$table -> setColAttributes(0,array('align'=>'left'));
		$table -> setColAttributes(1,array('align'=>'left'));
		$table -> setColAttributes(2,array('align'=>'left'));
		$table -> setColAttributes(7,array('align'=>'right'));
		$table -> setColAttributes(8,array('align'=>'center'));
		$table -> display();
		
	}
	else
	{
		echo get_lang('NoUsersInCourseTracking');
	}
	
	// send the csv file if asked
	if($export_csv)
	{
		ob_end_clean();
		Export :: export_table_csv($csv_content, 'reporting_student_list');
	}
	
}
?>
</table>

<?php
Display::display_footer();
?>
