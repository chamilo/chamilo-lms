<?php
/**
 * @todo use constant for $this_section
 */
// name of the language file that needs to be included 
$language_file = array ('registration', 'index','tracking');
$cidReset=true;

require ('../inc/global.inc.php');
require (api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');

ob_start();

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$csv_content = array();

$nameTools= get_lang("MySpace");
$this_section = "session_my_space";
 
api_block_anonymous_users();
if(!$export_csv)
{
	Display :: display_header($nameTools);
}
 
// Database table definitions
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_class 					= Database :: get_main_table(TABLE_MAIN_CLASS);
$tbl_sessions 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_user 			= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_admin					= Database :: get_main_table(TABLE_MAIN_ADMIN);


/********************
 * FUNCTIONS
 ********************/
 
function count_teacher_courses()
{
	global $nb_teacher_courses;
	return $nb_teacher_courses;
}

function count_coaches()
{
	global $total_no_coachs;
	return $total_no_coachs;
}

function sort_users($a,$b){
	$a = trim(strtolower($a[$_SESSION['tracking_column']]));
	$b = trim(strtolower($b[$_SESSION['tracking_column']]));
	if($_SESSION['tracking_direction'] == 'DESC')
		return strcmp($b, $a);
	else
		return strcmp($a, $b);
}



/**************************
 * MAIN CODE
 ***************************/

$isCoach = api_is_coach();
$isPlatformAdmin = api_is_platform_admin();

$view = isset($_GET['view']) ? $_GET['view'] : 'teacher';

$menu_items = array();

if(api_is_allowed_to_create_course())
{
	
	$sqlNbCours = "	SELECT course_rel_user.course_code, course.title
					FROM $tbl_course_user as course_rel_user
					INNER JOIN $tbl_course as course
						ON course.code = course_rel_user.course_code
				  	WHERE course_rel_user.user_id='".$_user['user_id']."' AND course_rel_user.status='1'
				  	ORDER BY course.title";
	$resultNbCours = api_sql_query($sqlNbCours, __FILE__, __LINE__);
	$a_courses = api_store_result($resultNbCours);
	$nb_teacher_courses = count($a_courses);
	if($nb_teacher_courses)
	{
		if(!$isCoach && !api_is_platform_admin())
		{
			$view = 'teacher';
		}
		if($view=='teacher')
		{
			$menu_items[] = get_lang('TeacherInterface');
			$title = get_lang('YourCourseList');
		}
		else
		{
			$menu_items[] = '<a href="'.api_get_self().'?view=teacher">'.get_lang('TeacherInterface').'</a>';
		}
	}
}
if($isCoach)
{
	if($nb_teacher_courses==0 && !api_is_platform_admin())
	{
		$view = 'coach';
	}
	if($view=='coach')
	{
		$menu_items[] = get_lang('CoachInterface');
		$title = get_lang('YourStatistics');
	}
	else
	{
		$menu_items[] = '<a href="'.api_get_self().'?view=coach">'.get_lang('CoachInterface').'</a>';
	}
}
if(api_is_platform_admin())
{
	if(!$isCoach && $nb_teacher_courses==0)
	{
		$view = 'admin';
	}
	if($view=='admin')
	{
		$menu_items[] = get_lang('AdminInterface');
		$title = get_lang('CoachList');
	}
	else
	{
		$menu_items[] = '<a href="'.api_get_self().'?view=admin">'.get_lang('AdminInterface').'</a>';
	}
}
if($_user['status']==DRH)
{
	$view = 'drh';
	$title = get_lang('DrhInterface');
	$menu_items[] = '<a href="'.api_get_self().'?view=drh">'.get_lang('DrhInterface').'</a>';
}

$nb_menu_items = count($menu_items);
if($nb_menu_items>1)
{
	foreach($menu_items as $key=> $item)
	{
		echo $item;
		if($key!=$nb_menu_items-1)
		{
			echo ' | ';
		}
	}
	echo '<br />';
}
echo '<div align="left" style="float:left"><h4>'.$title.'</h4></div>
	  <div align="right">
		<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
		<a href="'.api_get_self().'?export=csv&view='.$view.'"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
	  </div>
	  <div class="clear"></div>';

if($_user['status']==DRH && $view=='drh')
{
	$a_students = Tracking :: get_student_followed_by_drh($_user['user_id']);
	$courses_of_the_platform = CourseManager :: get_real_course_list();
	foreach($courses_of_the_platform as $course)
		$a_courses[$course['code']] = $course['code'];
}

if($isCoach && $view=='coach')
{
	$a_students = Tracking :: get_student_followed_by_coach($_user['user_id']);
	$a_courses = Tracking :: get_courses_followed_by_coach($_user['user_id']);
	
}

if($view=='coach' || $view=='drh')
{
	$nbStudents = count($a_students);
	
	$totalTimeSpent = 0;
	$totalCourses = 0;
	$avgTotalProgress = 0;
	$avgResultsToExercises = 0;
	$nb_inactive_students = 0;
	$nb_posts = $nb_assignments = 0;
	foreach($a_students as $student_id)
	{
		// inactive students
		if($last_connection_date = Tracking :: get_last_connection_date($student_id))
		{
			list($last_connection_date, $last_connection_hour) = explode(' ',$last_connection_date);
			$last_connection_date = explode('-',$last_connection_date);
			$last_connection_hour = explode(':',$last_connection_hour);
			$last_connection_time = mktime($last_connection_hour[0],$last_connection_hour[1],$last_connection_hour[2],$last_connection_date[1],$last_connection_date[2],$last_connection_date[0]);			
			if(time()-(3600*24*7) > $last_connection_time)
			{
				$nb_inactive_students++;
			}
		}
		else
		{
			$nb_inactive_students++;
		}
		
		$totalTimeSpent += Tracking :: get_time_spent_on_the_platform($student_id);
		$totalCourses += Tracking :: count_course_per_student($student_id);
		$avgStudentProgress = $avgStudentScore = 0;
		$nb_courses_student = 0;
		foreach($a_courses as $course_code)
		{
			if(CourseManager :: is_user_subscribed_in_course($student_id, $course_code, true))
			{
				$nb_courses_student++;
				$nb_posts += Tracking :: count_student_messages($student_id,$course_code);
				$nb_assignments += Tracking :: count_student_assignments($student_id,$course_code);
				$avgStudentProgress += Tracking :: get_avg_student_progress($student_id,$course_code);
				$avgStudentScore += Tracking :: get_avg_student_score($student_id,$course_code);
				if($nb_posts !== null && $nb_assignments !== null && $avgStudentProgress !== null && $avgStudentScore !== null)
				{
					//if one of these scores is null, it means that we had a problem connecting to the right database, so don't count it in
					$nb_courses_student++;
				}
			}
		}
		// average progress of the student
		$avgStudentProgress = $avgStudentProgress / $nb_courses_student;
		$avgTotalProgress += $avgStudentProgress;
		
		// average test results of the student
		$avgStudentScore = $avgStudentScore / $nb_courses_student;
		$avgResultsToExercises += $avgStudentScore;
		
	}
	// average progress
	$avgTotalProgress = $avgTotalProgress/$nbStudents;
	
	// average results to the tests
	$avgResultsToExercises = $avgResultsToExercises/$nbStudents;
	
	// average courses by student
	$avgCoursesPerStudent = round($totalCourses / $nbStudents,2);
	
	// average time spent on the platform
	$avgTimeSpent = $totalTimeSpent / $nbStudents;
	
	// average assignments
	$nb_assignments = $nb_assignments / $nbStudents;
	
	// average posts
	$nb_posts = $nb_posts / $nbStudents;
	
	
	 
	 //csv part
	 if($export_csv)
	 {
		$csv_content[] = array( get_lang('Probationers'));
		$csv_content[] = array( get_lang('InactivesStudents'),$nb_inactive_students );
		$csv_content[] = array( get_lang('AverageTimeSpentOnThePlatform'),$avgTimeSpent);
		$csv_content[] = array( get_lang('AverageCoursePerStudent'),$avgCoursesPerStudent);
		$csv_content[] = array( get_lang('AverageProgressInLearnpath'),$avgTotalProgress);
		$csv_content[] = array( get_lang('AverageResultsToTheExercices'),$avgResultsToExercises);
		$csv_content[] = array( get_lang('AveragePostsInForum'),$nb_posts);
		$csv_content[] = array( get_lang('AverageAssignments'),$nb_assignments);
		$csv_content[] = array();
	 }
	 // html part
	 else
	 {
		 echo '
		 <div class="admin_section">
			<h4>
				<a href="student.php"><img src="'.api_get_path(WEB_IMG_PATH).'students.gif">&nbsp;'.get_lang('Probationers').' ('.$nbStudents.')'.'</a> 
			</h4>
			<table class="data_table">
				<tr>
					<td>
						'.get_lang('InactivesStudents').'
					</td>
					<td align="right">
						'.$nb_inactive_students.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageTimeSpentOnThePlatform').'
					</td>
					<td align="right">
						'.api_time_to_hms($avgTimeSpent).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageCoursePerStudent').'
					</td>
					<td align="right">
						'.$avgCoursesPerStudent.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageProgressInLearnpath').'
					</td>
					<td align="right">
						'.round($avgTotalProgress,2).' %
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageResultsToTheExercices').'
					</td>
					<td align="right">
						'.round($avgResultsToExercises,2).' %
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AveragePostsInForum').'
					</td>
					<td align="right">
						'.round($nb_posts,2).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageAssignments').'
					</td>
					<td align="right">
						'.round($nb_assignments,2).'
					</td>
				</tr>
			</table>
			<a href="student.php">'.get_lang('SeeStudentList').'</a>
		 </div>';
	 }
}
if($view == 'coach')
{
	 
	 /****************************************
	 * Infos about sessions of the coach
	 ****************************************/
	$a_sessions = Tracking :: get_sessions_coached_by_user($_user['user_id']);
	$nbSessions = count($a_sessions);
	$nb_sessions_past = $nb_sessions_future = $nb_sessions_current = 0;
	$a_courses = array();
	foreach($a_sessions as $a_session)
	{
		if($a_session['date_start'] == '0000-00-00')
		{
			$nb_sessions_current ++;
		}
		else
		{
			$date_start = explode('-',$a_session['date_start']);
			$time_start = mktime(0,0,0,$date_start[1],$date_start[2],$date_start[0]);			
			$date_end = explode('-',$a_session['date_end']);				
			$time_end = mktime(0,0,0,$date_end[1],$date_end[2],$date_end[0]);			
			if($time_start < time() && time() < $time_end)
			{
				$nb_sessions_current++;
			}
			else if(time() < $time_start)
			{
				$nb_sessions_future++;
			}
			else if(time() > $time_end)
			{
				$nb_sessions_past++;
			}
		}
		$a_courses = array_merge($a_courses, Tracking::get_courses_list_from_session($a_session['id']));		
	}
	$nb_courses_per_session = round(count($a_courses)/$nbSessions,2);
	
	
	 //csv part
	 if($export_csv)
	 {
		$csv_content[] = array( get_lang('Sessions'));
		$csv_content[] = array( get_lang('NbActiveSessions').';'.$nb_sessions_current);
		$csv_content[] = array( get_lang('NbPastSessions').';'.$nb_sessions_past);
		$csv_content[] = array( get_lang('NbFutureSessions').';'.$nb_sessions_future);
		$csv_content[] = array( get_lang('NbStudentPerSession').';'.round($nbStudents/$nbSessions,2));
		$csv_content[] = array( get_lang('NbCoursesPerSession').';'.$nb_courses_per_session);
		$csv_content[] = array();
	 }
	 // html part
	 else
	 {
	
		echo '
		 <div class="admin_section">
			<h4>
				<a href="session.php"><img src="'.api_get_path(WEB_IMG_PATH).'sessions.gif">&nbsp;'.get_lang('Sessions').' ('.$nbSessions.')'.'</a>
			</h4>
			<table class="data_table">
				<tr>
					<td>
						'.get_lang('NbActiveSessions').'
					</td>
					<td align="right">
						'.$nb_sessions_current.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbPastSessions').'
					</td>
					<td align="right">
						'.$nb_sessions_past.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbFutureSessions').'
					</td>
					<td align="right">
						'.$nb_sessions_future.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbStudentPerSession').'
					</td>
					<td align="right">
						'.round($nbStudents/$nbSessions,2).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbCoursesPerSession').'
					</td>
					<td align="right">
						'.$nb_courses_per_session.'
					</td>
				</tr>
			</table>
			<a href="session.php">'.get_lang('SeeSessionList').'</a>
		 </div>';
	 }
	 
	 
}

echo '<div class="clear">&nbsp;</div>';


if(api_is_allowed_to_create_course() && $view=='teacher')
{
	
	if($nb_teacher_courses)
	{
		
				  
		$table = new SortableTable('tracking_list_course', 'count_teacher_courses');
		$parameters['view'] = 'teacher';
		$table->set_additional_parameters($parameters);
		$table -> set_header(0, get_lang('CourseTitle'), false, 'align="center"');
		$table -> set_header(1, get_lang('NbStudents'), false);
		$table -> set_header(2, get_lang('AvgTimeSpentInTheCourse'), false);
		$table -> set_header(3, get_lang('AvgStudentsProgress'), false);
		$table -> set_header(4, get_lang('AvgStudentsScore'), false);
		$table -> set_header(5, get_lang('AvgMessages'), false);
		$table -> set_header(6, get_lang('AvgAssignments'), false);
		$table -> set_header(7, get_lang('Details'), false);

		$csv_content[] = array(
						get_lang('CourseTitle'),
						get_lang('NbStudents'),
						get_lang('AvgTimeSpentInTheCourse'),
						get_lang('AvgStudentsProgress'),
						get_lang('AvgStudentsScore'),
						get_lang('AvgMessages'),
						get_lang('AvgAssignments')
						);
				
		$a_course_students = array();
		
		foreach($a_courses as $course)
		{
			
			$course_code = $course['course_code'];
			
			$avg_assignments_in_course = $avg_messages_in_course = $nb_students_in_course = $avg_progress_in_course = $avg_score_in_course = $avg_time_spent_in_course = 0;
			
			// students directly subscribed to the course
			$sql = "SELECT user_id FROM $tbl_course_user as course_rel_user WHERE course_rel_user.status='5' AND course_rel_user.course_code='$course_code'";
			$rs = api_sql_query($sql, __FILE__, __LINE__);	
			while($row = Database::fetch_array($rs))
			{
				$nb_students_in_course++;
				
				// tracking datas
				$avg_progress_in_course += Tracking :: get_avg_student_progress ($row['user_id'], $course_code);
				$avg_score_in_course += Tracking :: get_avg_student_score ($row['user_id'], $course_code);
				$avg_time_spent_in_course += Tracking :: get_time_spent_on_the_course ($row['user_id'], $course_code);
				$avg_messages_in_course += Tracking :: count_student_messages ($row['user_id'], $course_code);
				$avg_assignments_in_course += Tracking :: count_student_assignments ($row['user_id'], $course_code);
				$a_course_students[] = $row['user_id'];
			}
			
			// students subscribed to the course through a session
			if(api_get_setting('use_session_mode') == 'true')
			{
				$sql = 'SELECT id_user as user_id
						FROM '.$tbl_session_course_user.'
						WHERE course_code="'.addslashes($course_code).'" ORDER BY course_code';
				$rs = api_sql_query($sql, __FILE__, __LINE__);
				while($row = Database::fetch_array($rs))
				{
					if(!in_array($row['user_id'], $a_course_students))
					{
						$nb_students_in_course++;
						
						// tracking datas
						$avg_progress_in_course += Tracking :: get_avg_student_progress ($row['user_id'], $course_code);
						$avg_score_in_course += Tracking :: get_avg_student_score ($row['user_id'], $course_code);
						$avg_time_spent_in_course += Tracking :: get_time_spent_on_the_course ($row['user_id'], $course_code);
						$avg_messages_in_course += Tracking :: count_student_messages ($row['user_id'], $course_code);
						$avg_assignments_in_course += Tracking :: count_student_assignments ($row['user_id'], $course_code);
						$a_course_students[] = $row['user_id'];
					}
				}
			}
			if($nb_students_in_course>0)
			{
				$avg_time_spent_in_course = api_time_to_hms($avg_time_spent_in_course / $nb_students_in_course);
				$avg_progress_in_course = round($avg_progress_in_course / $nb_students_in_course,2).' %';
				$avg_score_in_course = round($avg_score_in_course / $nb_students_in_course,2).' %';
				$avg_messages_in_course = round($avg_messages_in_course / $nb_students_in_course,2);
				$avg_assignments_in_course = round($avg_assignments_in_course / $nb_students_in_course,2);
			}
			
			$table_row = array();
			$table_row[] = $course['title'];
			$table_row[] = $nb_students_in_course;
			$table_row[] = $avg_time_spent_in_course;
			$table_row[] = $avg_progress_in_course;
			$table_row[] = $avg_score_in_course;
			$table_row[] = $avg_messages_in_course;
			$table_row[] = $avg_assignments_in_course;
			$table_row[] = '<a href="../tracking/courseLog.php?cidReq='.$course_code.'&studentlist=true"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			
			$csv_content[] = array(
								$course['title'],
								$nb_students_in_course,
								$avg_time_spent_in_course,
								$avg_progress_in_course,
								$avg_score_in_course,
								$avg_messages_in_course,
								$avg_assignments_in_course,
								);
			
			$table -> addRow($table_row, 'align="right"');
			
			$a_course_students = array();
			
		}
		$table -> updateColAttributes(0,array('align'=>'left'));
		$table -> updateColAttributes(7,array('align'=>'center'));
		$table -> display();
			
	}
}

if(api_is_platform_admin() && $view=='admin'){
	
	$tracking_column = isset($_GET['tracking_list_coaches_column']) ? $_GET['tracking_list_coaches_column'] : 0;
	$tracking_direction = (isset($_GET['tracking_list_coaches_direction']) && in_array(strtoupper($_GET['tracking_list_coaches_direction']),array('ASC','DESC','ASCENDING','DESCENDING','0','1'))) ? $_GET['tracking_list_coaches_direction'] : 'DESC';
	//prepare array for column order - when impossible, use lastname
	$order = array(0=>'firstname',1=>'lastname',2=>'lastname',3=>'login_date',4=>'lastname',5=>'lastname');
	
	$table = new SortableTable('tracking_list_coaches', 'count_coaches');
	$parameters['view'] = 'admin';
	$table->set_additional_parameters($parameters);
	$table -> set_header(0, get_lang('FirstName'), true, 'align="center"');
	$table -> set_header(1, get_lang('LastName'), true, 'align="center"');
	$table -> set_header(2, get_lang('TimeSpentOnThePlatform'), false);
	$table -> set_header(3, get_lang('LastConnexion'), true, 'align="center"');
	$table -> set_header(4, get_lang('NbStudents'), false);
	$table -> set_header(5, get_lang('CountCours'), false);
	$table -> set_header(6, get_lang('NumberOfSessions'), false);
	$table -> set_header(7, get_lang('Sessions'), false,'align="center"');
	
	$csv_content[] = array(
						get_lang('FirstName'),
						get_lang('LastName'),
						get_lang('TimeSpentOnThePlatform'),
						get_lang('LastConnexion'),
						get_lang('NbStudents'),
						get_lang('CountCours'),
						get_lang('NumberOfSessions')
						);
	
	$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
	
	$sqlCoachs = "	SELECT DISTINCT id_coach, user_id, lastname, firstname, MAX(login_date) as login_date 
					FROM $tbl_user, $tbl_session_course, $tbl_track_login 
					WHERE id_coach=user_id AND login_user_id=user_id
					GROUP BY user_id " ;
				//	ORDER BY login_date ".$tracking_direction;
	if(!empty($order[$tracking_column]))
	{
		$sqlCoachs .= "ORDER BY ".$order[$tracking_column]." ".$tracking_direction;
	}

	$result_coaches=api_sql_query($sqlCoachs, __FILE__, __LINE__);
	$total_no_coachs = Database::num_rows($result_coaches);

	$global_coachs=array();
	while($a_coach=Database::fetch_array($result_coaches)){
		$global_coachs[$a_coach['user_id']] = $a_coach;
	}
	
	$sql_session_coach = 'SELECT session.id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
							FROM '.$tbl_user.','.$tbl_sessions.' as session,'.$tbl_track_login.'
							WHERE id_coach=user_id AND login_user_id=user_id
							GROUP BY user_id
							ORDER BY login_date '.$tracking_direction;
	$result_sessions_coach=api_sql_query($sql_session_coach, __FILE__, __LINE__);
	$total_no_coachs += Database::num_rows($result_sessions_coach);
	while($a_coach=Database::fetch_array($result_sessions_coach)){
		$global_coachs[$a_coach['user_id']] = $a_coach;
	}	
	
	$all_datas=array();
	
	foreach($global_coachs as $id_coach => $a_coachs){
		
		$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($a_coachs['user_id']));
		$last_connection = Tracking :: get_last_connection_date($a_coachs['user_id']);
		$nb_students = count(Tracking :: get_student_followed_by_coach($a_coachs['user_id']));
		$nb_courses = count(Tracking :: get_courses_followed_by_coach($a_coachs['user_id']));
		$nb_sessions = count(Tracking :: get_sessions_coached_by_user($a_coachs['user_id']));
		
		$table_row = array();
		$table_row[] = $a_coachs['firstname'];
		$table_row[] = $a_coachs['lastname'];
		$table_row[] = $time_on_platform;
		$table_row[] = $last_connection;
		$table_row[] = $nb_students;
		$table_row[] = $nb_courses;
		$table_row[] = $nb_sessions;
		$table_row[] = '<a href="session.php?id_coach='.$a_coachs['user_id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
		$all_datas[] = $table_row;
		
		$csv_content[] = array(
								$a_coachs['firstname'],
								$a_coachs['lastname'],
								$time_on_platform,
								$last_connection,
								$nb_courses,
								$nb_sessions
								);
	
	}
	
	if($tracking_column != 3){
		usort($all_datas, 'sort_users');
		if($tracking_direction == 'DESC')
			rsort($all_datas);
	}
		
	if($export_csv && $tracking_column != 3)
	{
		usort($csv_content, 'sort_users');
	}
	
	foreach($all_datas as $row)
	{
		$table -> addRow($row,'align="right"');	
	}
	
	$table -> updateColAttributes(0,array('align'=>'left'));
	$table -> updateColAttributes(1,array('align'=>'left'));
	$table -> updateColAttributes(3,array('align'=>'left'));
	$table -> updateColAttributes(7,array('align'=>'center'));
	$table -> display();
	
}

// send the csv file if asked
if($export_csv)
{
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_index');
}
 
 /*
 ==============================================================================
		FOOTER
 ==============================================================================
 */
if(!$export_csv)
{
	Display::display_footer();
}
?>
