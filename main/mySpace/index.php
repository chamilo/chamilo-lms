<?php // $Id: index.php 16620 2008-10-25 20:03:54Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) various contributors

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
 
// access control
api_block_anonymous_users();
if(!$export_csv)
{
	Display :: display_header($nameTools);
}
else
{
	if ($_GET['view'] == 'admin' AND $_GET['display'] == 'useroverview')
	{
		export_tracking_user_overview();
		exit;
	}
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
	return api_strcmp(trim(api_strtolower($a[$_SESSION['tracking_column']])), trim(api_strtolower($b[$_SESSION['tracking_column']])));
}

function rsort_users($a,$b) {
	return api_strcmp(trim(api_strtolower($b[$_SESSION['tracking_column']])), trim(api_strtolower($a[$_SESSION['tracking_column']])));
}


/**************************
 * MAIN CODE
 ***************************/

$isCoach = api_is_coach();
$isPlatformAdmin = api_is_platform_admin();

$view = isset($_GET['view']) ? $_GET['view'] : 'teacher';

$menu_items = array();
global $_configuration;

if(api_is_allowed_to_create_course()) { 	
	$sqlNbCours = "	SELECT course_rel_user.course_code, course.title
					FROM $tbl_course_user as course_rel_user
					INNER JOIN $tbl_course as course
						ON course.code = course_rel_user.course_code
				  	WHERE course_rel_user.user_id='".$_user['user_id']."' AND course_rel_user.status='1'
				  	ORDER BY course.title";
				  	
		
	if ($_configuration['multiple_access_urls']==true) {	
		$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);	
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){			
			$sqlNbCours = "	SELECT course_rel_user.course_code, course.title
				FROM $tbl_course_user as course_rel_user
				INNER JOIN $tbl_course as course
				ON course.code = course_rel_user.course_code
			  	INNER JOIN $tbl_course_rel_access_url course_rel_url
				ON (course_rel_url.course_code= course.code)	  	
			  	WHERE access_url_id =  $access_url_id  AND course_rel_user.user_id='".$_user['user_id']."' AND course_rel_user.status='1'
			  	ORDER BY course.title";			  			
		}
	}
	
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

echo '<div class="actions">';
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
}
echo '
		<a href="javascript: void(0);" onclick="javascript: window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
		<a href="'.api_get_self().'?export=csv&view='.$view.'"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
';
echo '</div>';
echo '<h4>'.$title.'</h4>';

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
		$last_connection_date = Tracking :: get_last_connection_date($student_id,true,true);		
		if($last_connection_date!=false)
		{
			/*
			list($last_connection_date, $last_connection_hour) = explode(' ',$last_connection_date);
			$last_connection_date = explode('-',$last_connection_date);
			$last_connection_hour = explode(':',$last_connection_hour);
			$last_connection_hour[0];
			$last_connection_time = mktime($last_connection_hour[0],$last_connection_hour[1],$last_connection_hour[2],$last_connection_date[1],$last_connection_date[2],$last_connection_date[0]);
			*/			
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
	$avgTotalProgress = 0;
	$avgResultsToExercises = 0;
	$avgCoursesPerStudent = 0;
	$avgTimeSpent = 0;
	$nb_assignments = 0;
	$nb_posts = 0;
	
	if ($nbStudents > 0) {
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
	} 
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
		$parameters['class'] = 'data_table';
		
		$table->set_additional_parameters($parameters);
		$table -> set_header(0, get_lang('CourseTitle'), false, 'align="center"');
		$table -> set_header(1, get_lang('NbStudents'), false);
		$table -> set_header(2, get_lang('AvgTimeSpentInTheCourse').Display :: return_icon('info3.gif', get_lang('TimeOfActiveByTraining'), array('align' => 'absmiddle', 'hspace' => '3px')),false);
		$table -> set_header(3, get_lang('AvgStudentsProgress').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(4, get_lang('AvgCourseScore').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(5, get_lang('AvgExercisesScore').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(6, get_lang('AvgMessages'), false);
		$table -> set_header(7, get_lang('AvgAssignments'), false);
		$table -> set_header(8, get_lang('Details'), false);

		$csv_content[] = array(
						get_lang('CourseTitle'),
						get_lang('NbStudents'),
						get_lang('AvgTimeSpentInTheCourse'),
						get_lang('AvgStudentsProgress'),
						get_lang('AvgCourseScore'),
						get_lang('AvgExercisesScore'),
						get_lang('AvgMessages'),
						get_lang('AvgAssignments')
						);
				
		$a_course_students = array();
		//
		foreach($a_courses as $course)
		{
			
			$course_code = $course['course_code'];
			$avg_assignments_in_course = $avg_messages_in_course = $nb_students_in_course = $avg_progress_in_course = $avg_score_in_course = $avg_time_spent_in_course = $avg_score_in_exercise = 0;
			
			// students directly subscribed to the course
			$sql = "SELECT user_id FROM $tbl_course_user as course_rel_user WHERE course_rel_user.status='5' AND course_rel_user.course_code='$course_code'";
			$rs = api_sql_query($sql, __FILE__, __LINE__);	
			while($row = Database::fetch_array($rs))
			{
				$nb_students_in_course++;
				
				// tracking datas
				$avg_progress_in_course += Tracking :: get_avg_student_progress ($row['user_id'], $course_code);
				$avg_score_in_course += Tracking :: get_avg_student_score ($row['user_id'], $course_code);
				$avg_score_in_exercise += Tracking :: get_avg_student_exercise_score ($row['user_id'], $course_code);
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
						$avg_score_in_exercise += Tracking :: get_avg_student_exercise_score ($row['user_id'], $course_code);														
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
				$avg_score_in_exercise = round($avg_score_in_exercise / $nb_students_in_course,2).' %';
				$avg_messages_in_course = round($avg_messages_in_course / $nb_students_in_course,2);
				$avg_assignments_in_course = round($avg_assignments_in_course / $nb_students_in_course,2);
			}
			
			$table_row = array();
			$table_row[] = $course['title'];
			$table_row[] = $nb_students_in_course;
			$table_row[] = $avg_time_spent_in_course;
			$table_row[] = $avg_progress_in_course;
			$table_row[] = $avg_score_in_course;
			$table_row[] = $avg_score_in_exercise;
			$table_row[] = $avg_messages_in_course;
			$table_row[] = $avg_assignments_in_course;
			$table_row[] = '<center><a href="../tracking/courseLog.php?cidReq='.$course_code.'&studentlist=true"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></center>';
			
			$csv_content[] = array(
								api_html_entity_decode($course['title'], ENT_QUOTES, $charset),
								$nb_students_in_course,
								$avg_time_spent_in_course,
								$avg_progress_in_course,
								$avg_score_in_course,
								$avg_score_in_exercise,
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

if(api_is_platform_admin() && $view=='admin')
{
	echo '<a href="'.api_get_self().'?view=admin&amp;display=coaches">'.get_lang('DisplayCoaches').'</a> | ';
	echo '<a href="'.api_get_self().'?view=admin&amp;display=useroverview">'.get_lang('DisplayUserOverview').'</a>';
	if ($_GET['display'] == 'useroverview')
	{
		echo ' | <a href="'.api_get_self().'?view=admin&amp;display=useroverview&amp;export=options">'.get_lang('ExportUserOverviewOptions').'</a>';
	}
	
	if ($_GET['display'] === 'useroverview')
	{
		display_tracking_user_overview();
	}
	else
	{
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
				
	
	if ($_configuration['multiple_access_urls']==true) {	
		$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){			
			$sqlCoachs = "	SELECT DISTINCT id_coach, user_id, lastname, firstname, MAX(login_date) as login_date 
							FROM $tbl_user, $tbl_session_course, $tbl_track_login , $tbl_session_rel_access_url session_rel_url
							WHERE id_coach=user_id AND login_user_id=user_id AND access_url_id = $access_url_id AND session_rel_url.session_id=id_session
							GROUP BY user_id " ;			  			
		}
	}	
	
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
		
	if ($_configuration['multiple_access_urls']==true) {	
		$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){			
			$sql_session_coach = 'SELECT session.id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
							FROM '.$tbl_user.','.$tbl_sessions.' as session,'.$tbl_track_login.' , '.$tbl_session_rel_access_url.' as session_rel_url
							WHERE id_coach=user_id AND login_user_id=user_id  AND access_url_id = '.$access_url_id.' AND  session_rel_url.session_id=session.id
							GROUP BY user_id
							ORDER BY login_date '.$tracking_direction;				  			
		}
	}
	
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
		$table_row[] = '<center><a href="session.php?id_coach='.$a_coachs['user_id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></center>';
		$all_datas[] = $table_row;
		
		$csv_content[] = array(
								api_html_entity_decode($a_coachs['firstname'], ENT_QUOTES, $charset),
								api_html_entity_decode($a_coachs['lastname'], ENT_QUOTES, $charset),
								$time_on_platform,
								$last_connection,
								$nb_courses,
								$nb_sessions
								);
	
	}
	
	if($tracking_column != 3){
		if ($tracking_direction == 'DESC') {
			usort($all_datas, 'rsort_users');
		} else {
			usort($all_datas, 'sort_users');
		}
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
}

// send the csv file if asked
if($export_csv)
{
	ob_end_clean();
	/*echo "<pre>";
	print_r($csv_content);
	echo "</pre>";*/
	Export :: export_table_csv($csv_content, 'reporting_index');
}
 
//footer
if(!$export_csv)
{
	Display::display_footer();
}


/**
 * This function exports the table that we see in display_tracking_user_overview()
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since October 2008
 */
function export_tracking_user_overview()
{
	// database table definitions
	$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);

	// the values of the sortable table
	if ($_GET['tracking_user_overview_page_nr'])
	{
		$from = $_GET['tracking_user_overview_page_nr'];
	}
	else
	{
		$from = 0;
	}
	if ($_GET['tracking_user_overview_column'])
	{
		$orderby = $_GET['tracking_user_overview_column'];
	}
	else
	{
		$orderby = 0;
	}
	if ($_GET['tracking_user_overview_direction'])
	{
		$direction = $_GET['tracking_user_overview_direction'];
	}
	else
	{
		$direction = 'ASC';
	}

	$user_data = get_user_data_tracking_overview($from, 1000 , $orderby, $direction);
	
	// the first line of the csv file with the column headers
	$csv_row = array();
	$csv_row[] = get_lang('OfficialCode');
	$csv_row[] = get_lang('LastName');
	$csv_row[] = get_lang('FirstName');
	$csv_row[] = get_lang('LoginName');	
	$csv_row[] = get_lang('CourseCode');
	// the additional user defined fields (only those that were selected to be exported)
	require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
	$fields = UserManager::get_extra_fields(0,50,5,'ASC');
	foreach ($_SESSION['additional_export_fields'] as $key=>$extra_field_export)
	{
		$csv_row[] = $fields[$extra_field_export][3];
		$field_names_to_be_exported[] = 'extra_'.$fields[$extra_field_export][1];
	}
	$csv_row[] = get_lang('AvgTimeSpentInTheCourse');
	$csv_row[] = get_lang('AvgStudentsProgress');
	$csv_row[] = get_lang('AvgCourseScore');
	$csv_row[] = get_lang('AvgExercisesScore');
	$csv_row[] = get_lang('AvgMessages');
	$csv_row[] = get_lang('AvgAssignments');
	$csv_row[] = get_lang('TotalExercisesScoreObtained');
	$csv_row[] = get_lang('TotalExercisesScorePossible');
	$csv_row[] = get_lang('TotalExercisesAnswered');
	$csv_row[] = get_lang('TotalExercisesScorePercentage');
	$csv_row[] = get_lang('FirstLogin');
	$csv_row[] = get_lang('LatestLogin');
	$csv_content[] = $csv_row;
	
	// the other lines (the data)
	foreach($user_data as $key=>$user)
	{
		// getting all the courses of the user
		$sql = "SELECT * FROM $tbl_course_user WHERE user_id = '".Database::escape_string($user[4])."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_row($result))
		{
			$csv_row = array();
			// user official code
			$csv_row[] = $user[0];
			// user last name
			$csv_row[] = $user[1];
			// user first name
			$csv_row[] = $user[2];
			// user login name
			$csv_row[] = $user[3];
			// course code
			$csv_row[] = $row[0];
			// the additional defined user fields
			$extra_fields = get_user_overview_export_extra_fields($user[4]);
			foreach ($field_names_to_be_exported as $key=>$extra_field_export)
			{
				$csv_row[] = $extra_fields[$extra_field_export];
			}
			// time spent in the course
			$csv_row[] = api_time_to_hms(Tracking :: get_time_spent_on_the_course ($user[4], $row[0]));
			// student progress in course
			$csv_row[] = round(Tracking :: get_avg_student_progress ($user[4], $row[0]),2);
			// student score
			$csv_row[] = round(Tracking :: get_avg_student_score ($user[4], $row[0]),2);
			// student tes score
			$csv_row[] = round(Tracking :: get_avg_student_exercise_score ($user[4], $row[0]),2);			
			// student messages
			$csv_row[] = Tracking :: count_student_messages ($user[4], $row[0]);
			// student assignments
			$csv_row[] = Tracking :: count_student_assignments ($user[4], $row[0]);
			// student exercises results
			$exercises_results = exercises_results($user[4], $row[0]);
			$csv_row[] = $exercises_results['score_obtained'];
			$csv_row[] = $exercises_results['score_possible'];
			$csv_row[] = $exercises_results['questions_answered'];
			$csv_row[] = $exercises_results['percentage'];			
			// first connection
			$csv_row[] = Tracking :: get_first_connection_date_on_the_course ($user[4], $row[0]);
			// last connection
			$csv_row[] = strip_tags(Tracking :: get_last_connection_date_on_the_course ($user[4], $row[0]));

			$csv_content[] = $csv_row;
		}
	}
	Export :: export_table_csv($csv_content, 'reporting_user_overview');
}

/**
 * Display a sortable table that contains an overview off all the reporting progress of all users and all courses the user is subscribed to
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since October 2008
 */
function display_tracking_user_overview()
{
	display_user_overview_export_options();
	
	
	
	$t_head .= '	<table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
	$t_head .= '	<caption>'.get_lang('CourseInformation').'</caption>';			
	$t_head .=		'<tr>';
	$t_head .= '		<th width="155px" style="border-left:0;border-bottom:0"><span>'.get_lang('Course').'</span></th>';
	$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'),6,true).'</span></th>';
	$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'),6,true).'</span></th>';
	$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'),6,true).'</span></th>';
	//$t_head .= '		<th><div style="width:40px">'.get_lang('AvgExercisesScore').'</div></th>';
	$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgMessages'),6,true).'</span></th>';
	$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgAssignments'),6,true).'</span></th>';
	$t_head .= '		<th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
	//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePossible').'</div></th>';
	$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'),6,true).'</span></th>';
	//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePercentage').'</div></th>';
	//$t_head .= '		<th><div style="width:60px">'.get_lang('FirstLogin').'</div></th>';
	$t_head .= '		<th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
	$t_head .= '	</tr></table>';


	$addparams= array ('view' => 'admin','display' => 'useroverview');
	
	$table = new SortableTable('tracking_user_overview', 'get_number_of_users_tracking_overview', 'get_user_data_tracking_overview',0);	
	$table->additional_parameters = $addparams;	
	
	$table->set_header(0, get_lang('OfficialCode'),true,array ('style' => 'font-size:8pt'),array ('style' => 'font-size:8pt'));
	$table->set_header(1, get_lang('LastName'),true,array ('style' => 'font-size:8pt'),array ('style' => 'font-size:8pt'));
	$table->set_header(2, get_lang('FirstName'),true,array ('style' => 'font-size:8pt'),array ('style' => 'font-size:8pt'));
	$table->set_header(3, get_lang('LoginName'),true,array ('style' => 'font-size:8pt'),array ('style' => 'font-size:8pt'));
	$table->set_header(4,$t_head, false,array ('style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'),array ('style' => 'width:90%;padding:0;font-size:7.5pt;'));
	$table->set_column_filter(4, 'course_info_tracking_filter');
	$table -> display();
}

/**
 * get the numer of users of the platform
 *
 * @return integer
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since October 2008
 */
function get_number_of_users_tracking_overview()
{
	// database table definition
	$main_user_table = Database :: get_main_table(TABLE_MAIN_USER);

	// query
	$sql = 'SELECT user_id FROM '.$main_user_table;
	$result = api_sql_query($sql, __FILE__, __LINE__);

	// return the number of results
	return Database::num_rows($result);
}

/**
 * get all the data for the sortable table of the reporting progress of all users and all the courses the user is subscribed to.
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since October 2008
 */
function get_user_data_tracking_overview($from, $number_of_items, $column, $direction)
{
	// database table definition
	$main_user_table = Database :: get_main_table(TABLE_MAIN_USER);

	$sql = "SELECT
				official_code 	AS col0,
				lastname 		AS col1,
				firstname 		AS col2,
				username		AS col3,
				user_id 		AS col4
			FROM 
				$main_user_table
			";
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$return = array ();
	while ($user = Database::fetch_row($result))
	{
		$return[] = $user;
	}
	return $return;
}

/**
 * Creates a small table in the last column of the table with the user overview
 *
 * @param integer $user_id the id of the user
 * @param array $url_params additonal url parameters
 * @param array $row the row information (the other columns)
 * @return html code
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since October 2008
 */
function course_info_tracking_filter($user_id,$url_params,$row)
{
	// the table header
	$return .= '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
	/*$return .= '	<tr>';
	$return .= '		<th>'.get_lang('Course').'</th>';
	$return .= '		<th>'.get_lang('AvgTimeSpentInTheCourse').'</th>';
	$return .= '		<th>'.get_lang('AvgStudentsProgress').'</th>';
	$return .= '		<th>'.get_lang('AvgCourseScore').'</th>';
	$return .= '		<th>'.get_lang('AvgExercisesScore').'</th>';
	$return .= '		<th>'.get_lang('AvgMessages').'</th>';
	$return .= '		<th>'.get_lang('AvgAssignments').'</th>';
	$return .= '		<th>'.get_lang('TotalExercisesScoreObtained').'</th>';
	$return .= '		<th>'.get_lang('TotalExercisesScorePossible').'</th>';
	$return .= '		<th>'.get_lang('TotalExercisesAnswered').'</th>';
	$return .= '		<th>'.get_lang('TotalExercisesScorePercentage').'</th>';
	$return .= '		<th>'.get_lang('FirstLogin').'</th>';
	$return .= '		<th>'.get_lang('LatestLogin').'</th>';
	$return .= '	</tr>';*/

	// database table definition
	$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);

	// getting all the courses of the user
	$sql = "SELECT * FROM $tbl_course_user WHERE user_id = '".Database::escape_string($user_id)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_row($result))
	{
		$return .= '<tr>';
		// course code
		$return .= '	<td width="157px" >'.cut($row[0],20,true).'</td>';
		// time spent in the course
		$return .= '	<td><div>'.api_time_to_hms(Tracking :: get_time_spent_on_the_course ($user_id, $row[0])).'</div></td>';
		// student progress in course
		$return .= '	<td><div>'.round(Tracking :: get_avg_student_progress ($user_id, $row[0]),2).'</div></td>';
		// student score
		$return .= '	<td><div>'.round(Tracking :: get_avg_student_score ($user_id, $row[0]),2).'</div></td>';
		// student tes score
		//$return .= '	<td><div style="width:40px">'.round(Tracking :: get_avg_student_exercise_score ($user_id, $row[0]),2).'%</div></td>';		
		// student messages
		$return .= '	<td><div>'.Tracking :: count_student_messages ($user_id, $row[0]).'</div></td>';
		// student assignments
		$return .= '	<td><div>'.Tracking :: count_student_assignments ($user_id, $row[0]).'</div></td>';
		// student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
		$exercises_results = exercises_results($user_id, $row[0]);
		$return .= '	<td width="105px"><div>'.$exercises_results['score_obtained'].'/'.$exercises_results['score_possible'].'('.$exercises_results['percentage'].'%)</div></td>';
		//$return .= '	<td><div>'.$exercises_results['score_possible'].'</div></td>';
		$return .= '	<td><div>'.$exercises_results['questions_answered'].'</div></td>';
		//$return .= '	<td><div>'.$exercises_results['percentage'].'% </div></td>';
		// first connection
		//$return .= '	<td width="60px">'.Tracking :: get_first_connection_date_on_the_course ($user_id, $row[0]).'</td>';
		// last connection
		$return .= '	<td><div>'.Tracking :: get_last_connection_date_on_the_course ($user_id, $row[0]).'</div></td>';
		$return .= '<tr>';
	}
	$return .= '</table>';
	return $return;
}


/**
 * Get general information about the exercise performance of the user
 * the total obtained score (all the score on all the questions)
 * the maximum score that could be obtained
 * the number of questions answered
 * the success percentage
 *
 * @param integer $user_id the id of the user
 * @param string $course_code the course code
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since November 2008
 */
function exercises_results($user_id, $course_code)
{
	$questions_answered = 0;
	$sql = 'SELECT exe_result , exe_weighting
					FROM '.Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES)."
					WHERE exe_cours_id = '".Database::escape_string($course_code)."'
					AND exe_user_id = '".Database::escape_string($user_id)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$score_obtained = 0;
	$score_possible = 0;
	$questions_answered = 0;
	while ($row = Database::fetch_array($result))
	{
		$score_obtained += $row['exe_result'];
		$score_possible += $row['exe_weighting'];
		$questions_answered ++;
	}
	
	if ($score_possible != 0) {
		$percentage = round(($score_obtained / $score_possible * 100),2);	
	} 
	
	return array('score_obtained' => $score_obtained, 'score_possible' => $score_possible, 'questions_answered' => $questions_answered, 'percentage' => $percentage);
}

/**
 * Displays a form with all the additionally defined user fields of the profile 
 * and give you the opportunity to include these in the CSV export
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since November 2008
 */
function display_user_overview_export_options()
{
	// include the user manager and formvalidator library
	require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
	require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

	if ($_GET['export'] == 'options')
	{
		// get all the defined extra fields
		$extrafields = UserManager::get_extra_fields(0,50,5,'ASC');

		// creating the form with all the defined extra fields
		$form = new FormValidator('exportextrafields', 'post', api_get_self()."?view=".Security::remove_XSS($_GET['view']).'&display='.Security::remove_XSS($_GET['display']).'&export='.Security::remove_XSS($_GET['export']));
		foreach ($extrafields as $key=>$extra)
		{
			$form->addElement('checkbox', 'extra_export_field'.$extra[0], '', $extra[3]);
		}
		$form->addElement('submit', null, get_lang('Ok'));
		
		// setting the default values for the form that contains all the extra fields
		if(is_array($_SESSION['additional_export_fields']))
		foreach($_SESSION['additional_export_fields'] as $key => $value)
		{
			$defaults['extra_export_field'.$value]=1;
		}
		$form->setDefaults($defaults);

		if ($form->validate())
		{
			// exporting the form values
			$values = $form->exportValues();

			// re-initialising the session that contains the additional fields that need to be exported
			$_SESSION['additional_export_fields'] = array();

			// adding the fields that are checked to the session
			$message='';
			foreach ($values as $field_ids => $value)
			{
				if ($value == 1 AND strstr($field_ids,'extra_export_field'))
				{
					$_SESSION['additional_export_fields'][] = str_replace('extra_export_field','',$field_ids);
				}

			}
			
			// adding the fields that will be also exported to a message string
			foreach ($_SESSION['additional_export_fields'] as $key=>$extra_field_export)
			{
				$message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
			}			

			// Displaying a feedback message
			if (!empty($_SESSION['additional_export_fields']))
			{
				Display::display_confirmation_message(get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>',false);
			}
			else 
			{
				Display::display_confirmation_message(get_lang('NoAdditionalFieldsWillBeExported'),false);
			}
			$message = '';
		}
		else
		{
			$form->display();
		}
	}
	else
	{
		if (!empty($_SESSION['additional_export_fields']))
		{
			// get all the defined extra fields
			$extrafields = UserManager::get_extra_fields(0,50,5,'ASC');

			foreach ($_SESSION['additional_export_fields'] as $key=>$extra_field_export)
			{
				$message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
			}

			Display::display_normal_message(get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>',false);
			$message = '';
		}
	}
}

/**
 * Get all information that the user with user_id = $user_data has 
 * entered in the additionally defined profile fields
 *
 * @param integer $user_id the id of the user
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version Dokeos 1.8.6
 * @since November 2008
 */
function get_user_overview_export_extra_fields($user_id)
{
	// include the user manager
	require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');	
	
	$extra_data = UserManager::get_extra_user_data($user_id,true);
	return $extra_data;
}
?>
