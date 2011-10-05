<?php


$language_file = array ('registration', 'index', 'tracking', 'exercice','admin');

require_once '../inc/global.inc.php';
$this_section = SECTION_TRACKING;

require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php';

$filename = 'reporting.xls';

if (!api_is_allowed_to_create_course()) {
	api_not_allowed(true);
}

$user_id = api_get_user_id();
$my_courses = CourseManager::get_course_list_of_user_as_course_admin($user_id);
$array = array();

$i = 0;
if (!empty($my_courses)) {
	foreach ($my_courses as $course) {
		//$array[$i]['course'] = $course;
		
		$course_code = $course['course_code'];
		$course_info = api_get_course_info($course_code);
		
		$t_lp 	= Database :: get_course_table(TABLE_LP_MAIN, $course_info['dbName']);
		$sql_lp = "SELECT lp.name, lp.id FROM $t_lp lp WHERE lp.session_id = 0 LIMIT 1";
		$rs_lp 	= Database::query($sql_lp);
		
		$array[$i]['lp'] = '';
		if (Database :: num_rows($rs_lp) > 0) {			
			$learnpath = Database :: fetch_row($rs_lp);
			$array[$i]['lp'] = $learnpath[0];
		}
		
		$teachers = CourseManager::get_teacher_list_from_course_code($course_code);
		$teacher_list = array();
		
		$array[$i]['teachers'] = '';
		if (!empty($teachers)) {
			foreach($teachers as $teacher) {
				$teacher_list[]= $teacher['firstname'].' '.$teacher['lastname'];
			}		
			$array[$i]['teachers'] = implode(', ', $teacher_list);
		}
				 
		$array[$i]['course_name'] = $course['title'];
		
		
		$students = CourseManager :: get_student_list_from_course_code($course['course_code'], false);
		$count_students = 0;
		$tools = 0;
		$session_id = 0;
		$count_students_accessing = 0;
		$count_students_complete_all_activities = 0;
		$count_students_complete_all_activities_at_50 = 0;
		$total_time_spent = 0;
		$total_tools = 0;		
		$total_average_progress = 0;
		
		if (!empty($students)) {
			foreach ($students  as $student) {
				$student_id = $student['user_id'];
			
				$avg_student_progress += Tracking :: get_avg_student_progress($student_id, $course_code);
				$myavg_temp = Tracking :: get_avg_student_score($student_id, $course_code);
				
				$avg_progress_in_course = Tracking::get_avg_student_progress($student_id, $course_code, array(), $session_id);
			
				if (intval($avg_progress_in_course) == 100) {
					$count_students_complete_all_activities++;
				}
				if (intval($avg_progress_in_course) > 0 && intval($avg_progress_in_course) <= 50) { 
					$count_students_complete_all_activities_at_50 ++;
				}				
				$total_average_progress +=$avg_progress_in_course;
				
				$time_spent  = Tracking::get_time_spent_on_the_course($student_id, $course_code, $session_id);				
				$total_time_spent += $time_spent; 				
				if (!empty($time_spent)) {
					$count_students_accessing++;
				}				
			}
			
			$student_list = array();
			foreach($students as $student) {
				$student_list []=$student['user_id'];
			}
			
			$nb_assignments 		= Tracking::count_student_assignments($student_list, $course_code, $session_id);
			$messages 				= Tracking::count_student_messages($student_list, $course_code, $session_id);
			$links 					= Tracking::count_student_visited_links($student_list, $course_code, $session_id);
			$chat_last_connection 	= Tracking::chat_last_connection($student_list, $course_code, $session_id);			
			$documents				= Tracking::count_student_downloaded_documents($student_list, $course_code, $session_id);

			$total_tools += $nb_assignments +  $messages + $links + $chat_last_connection + $documents;
			
		}
		
		$student_count = count($students);
		
		$array[$i]['count_students'] = $student_count;
		$array[$i]['count_students_accessing'] = 0;
		$array[$i]['count_students_accessing_percentage'] = 0;
		$array[$i]['count_students_complete_all_activities_at_50'] = 0;
		$array[$i]['count_students_complete_all_activities'] = 0;
		$array[$i]['average_percentage_activities_completed_per_student'] = 0;
		$array[$i]['total_time_spent'] = 0;
		$array[$i]['average_time_spent_per_student'] = 0;
		$array[$i]['total_time_spent'] = 0;
		$array[$i]['average_time_spent_per_student'] = 0;		
		$array[$i]['tools_used'] = 0;		
		
		//@todo don't know what means this value
		$count_students_complete_all_activities_at_50 = 0;
		
		if (!empty($student_count)) {
			$array[$i]['count_students_accessing'] = $count_students_accessing;
			$array[$i]['count_students_accessing_percentage'] = round($count_students_accessing / $student_count *100 , 0);
			$array[$i]['count_students_complete_all_activities_at_50'] = $count_students_complete_all_activities_at_50;
			$array[$i]['count_students_complete_all_activities'] = round($count_students_complete_all_activities / $student_count *100 , 0);;			
			$array[$i]['average_percentage_activities_completed_per_student'] = round($count_students_complete_all_activities/$student_count*100,2);			
			$array[$i]['total_time_spent'] = 0;
			$array[$i]['average_time_spent_per_student'] = 0;
			
			if (!empty($total_time_spent)) {
				$array[$i]['total_time_spent'] = api_time_to_hms($total_time_spent);
				$array[$i]['average_time_spent_per_student'] = api_time_to_hms($total_time_spent / $student_count);
			}			
			$array[$i]['tools_used'] = $total_tools;
		}	
		$i++;
	}
}


$headers = array(
	get_lang('LearningPath'),
	get_lang('Teachers'),
	get_lang('Courses'),
	get_lang('NumberOfStudents'),
	get_lang('NumberStudentsAccessingCourse'),
	get_lang('PercentageStudentsAccessingCourse'),
	get_lang('NumberStudentsCompleteAllActivities'),
	get_lang('PercentageStudentsCompleteAllActivities'),
	get_lang('AverageOfActivitiesCompletedPerStudent'),
	get_lang('TotalTimeSpentInTheCourse'),
	get_lang('AverageTimePerStudentInCourse'),
	get_lang('NumberOfToolsAddedOrUsedByTeachers')
);
	


if (isset($_GET['export'])) {
	global $charset;
	$workbook = new Spreadsheet_Excel_Writer();
	$workbook ->setTempDir(api_get_path(SYS_ARCHIVE_PATH));
	$workbook->send($filename);
	$workbook->setVersion(8); // BIFF8
	$worksheet =& $workbook->addWorksheet('Report');
	//$worksheet->setInputEncoding(api_get_system_encoding());
	$worksheet->setInputEncoding($charset);
	
	$line = 0;
	$column = 0; //skip the first column (row titles)
	
	foreach($headers as $header) {
		$worksheet->write($line,$column, $header);
		$column++;
	}
	$line++;
	foreach ($array as $row) {
		$column = 0;
		foreach ($row as $item) {
			$worksheet->write($line,$column,html_entity_decode(strip_tags($item)));
			$column++;
		}
		$line++;
	}
	$line++;
	$workbook->close();
	exit;
}

Display::display_header();

if (!class_exists('HTML_Table')) {
	require_once api_get_path(LIBRARY_PATH).'pear/HTML/Table.php';
}

$table = new HTML_Table(array('class' => 'data_table'));
$row = 0;
$column = 0;
foreach ($headers as $header) {
	$table->setHeaderContents($row, $column, $header);
	$column++;
}
$row++;

foreach ($array as $row_table) {	
	$column = 0;	
	foreach ($row_table as $cell) {
		$table->setCellContents($row, $column, $cell);
		$table->updateCellAttributes($row, $column, 'align="center"');
		$column++;
	}
	$table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
	$row++;
}

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace"><img align="absbottom" src="../img/back.png">&nbsp;'.get_lang('Back').'</a> ';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/current_courses.php?export=1"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('CurrentCoursesReport').'</a> ';
echo '</div>';
echo $table->toHtml();

Display::display_footer();