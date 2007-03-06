<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 // name of the language file that needs to be included 
$language_file = array ('registration', 'index', 'tracking');
$cidReset=true;
require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once (api_get_path(LIBRARY_PATH).'export.lib.inc.php');
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
 


$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if($export_csv)
{
	ob_start();
}
$csv_content = array();
 
$nameTools= get_lang("Students");
 
$this_section = "session_my_space";
 
api_block_anonymous_users();
 
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 
if(isset($_GET["user_id"]) && $_GET["user_id"]!="" && !isset($_GET["type"])){
	$interbreadcrumb[] = array ("url" => "teachers.php", "name" => get_lang('Teachers'));
}
 
if(isset($_GET["user_id"]) && $_GET["user_id"]!="" && isset($_GET["type"]) && $_GET["type"]=="coach"){
 	$interbreadcrumb[] = array ("url" => "coaches.php", "name" => get_lang('Tutors'));
}

$isCoach = api_is_coach(); 

Display :: display_header($nameTools);

// Database Table Definitions
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session_rel_user 		= Database :: get_main_table(TABLE_MAIN_SESSION_USER);

/*
 ===============================================================================
 	FUNCTION
 ===============================================================================  
 */

function count_student_coached()
{
	global $a_students;
	return count($a_students);
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
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */ 

if($isCoach)
{

	echo '<div align="right">
			<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
			<a href="'.$_SERVER['PHP_SELF'].'?export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
		  </div>';
	 
	$a_courses = Tracking :: get_courses_followed_by_coach($_user['user_id']);
	$a_students = Tracking :: get_student_followed_by_coach($_user['user_id']);
	
	$tracking_column = isset($_GET['tracking_column']) ? $_GET['tracking_column'] : 0;
	$tracking_direction = isset($_GET['tracking_direction']) ? $_GET['tracking_direction'] : DESC;
	
	if(count($a_students)>0)
	{
		$table = new SortableTable('tracking', 'count_student_coached');
		$table -> set_header(0, get_lang('LastName'));
		$table -> set_header(1, get_lang('FirstName'));
		$table -> set_header(2, get_lang('Time'),false);
		$table -> set_header(3, get_lang('Progress'),false);
		$table -> set_header(4, get_lang('Score'),false);	
		$table -> set_header(5, get_lang('Student_publication'),false);
		$table -> set_header(6, get_lang('Messages'),false);
		$table -> set_header(7, get_lang('LatestLogin'),false);
		$table -> set_header(8, get_lang('Details'),false);
	     
	    if($export_csv)
		{
			$csv_content[] = array ( 
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
		foreach($a_students as $student_id)
		{
			$student_datas = UserManager :: get_user_info_by_id($student_id);
			
			$avg_time_spent = $avg_student_score = $avg_student_progress = $total_assignments = $total_messages = 0 ;
			$nb_courses_student = 0;
			foreach($a_courses as $course_code)
			{
				if(CourseManager :: is_user_subscribed_in_course($student_id,$course_code, true))
				{
					$avg_time_spent += Tracking :: get_time_spent_on_the_platform($student_id, $course_code);
					$avg_student_score += Tracking :: get_avg_student_score($student_id, $course_code);
					$avg_student_progress += Tracking :: get_avg_student_progress($student_id, $course_code);
					$total_assignments += Tracking :: count_student_assignments($student_id, $course_code);
					$total_messages += Tracking :: count_student_messages($student_id, $course_code);
					$nb_courses_student++;
				}
			}
			$avg_time_spent = $avg_time_spent / $nb_courses_student;
			$avg_student_score = $avg_student_score / $nb_courses_student;
			$avg_student_progress = $avg_student_progress / $nb_courses_student;
			
			$row = array();
			$row[] = $student_datas['lastname'];
			$row[] = 	$student_datas['firstname'];
			$row[] = api_time_to_hms($avg_time_spent);
			$row[] = $avg_student_progress.' %';
			$row[] = $avg_student_score.' %';		
			$row[] = $total_assignments;
			$row[] = $total_messages;
			$row[] = Tracking :: get_last_connection_date($student_id);
			
			if($export_csv)
			{
				$csv_content[] = $row;
			}
			
			$row[] = '<a href="myStudents.php?student='.$student_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			
			$all_datas[] = $row;		
	
		}
		
		usort($all_datas, 'sort_users');
		if($tracking_direction == 'ASC')
			rsort($all_datas);
		
		if($export_csv)
		{
			usort($csv_content, 'sort_users');
		}
		
		foreach($all_datas as $row)
		{
			$table -> addRow($row,'align="right"');	
		}
		
		$table -> display();
		
	}
	else
	{
		echo get_lang('NoStudent');
	}
	
	// send the csv file if asked
	if($export_csv)
	{
		ob_end_clean();
		Export :: export_table_csv($csv_content, 'reporting_student_list');
	}
}


/*
 ==============================================================================
		FOOTER
 ==============================================================================
 */
	
Display :: display_footer();
?>
