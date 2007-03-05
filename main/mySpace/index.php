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

$isCoach = api_is_coach(); 


if($isCoach)
{
	
	/****************************************
	 * Print and export
	 ****************************************/
	if(!$export_csv)
	{
		echo '<div align="right">
				<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
				<a href="'.$_SERVER['PHP_SELF'].'?export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
			  </div>';
	}
	
	
	
	/****************************************
	 * Infos about students of the coach
	 ****************************************/
	$a_students = Tracking :: get_student_followed_by_coach($_user['user_id']);
	$a_courses = Tracking :: get_courses_followed_by_coach($_user['user_id']);
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
	$avgCoursesPerStudent = round($totalCourses / $nbStudents,1);
	
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
						'.round($avgTotalProgress,1).' %
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageResultsToTheExercices').'
					</td>
					<td align="right">
						'.round($avgResultsToExercises,1).' %
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AveragePostsInForum').'
					</td>
					<td align="right">
						'.round($nb_posts,1).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageAssignments').'
					</td>
					<td align="right">
						'.round($nb_assignments,1).'
					</td>
				</tr>
			</table>
			<a href="student.php">'.get_lang('SeeStudentList').'</a>
		 </div>';
	 }
	 
	 
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
			$date_start = explode('-',$session['date_start']);
			$time_start = mktime(0,0,0,$date_start[1],$date_start[2],$date_start[0]);
			
			$date_end = explode('-',$session['date_end']);				
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
	$nb_courses_per_session = round(count($a_courses)/$nbSessions,1);
	
	
	 //csv part
	 if($export_csv)
	 {
		$csv_content[] = array( get_lang('Sessions'));
		$csv_content[] = array( get_lang('NbActiveSessions').';'.$nb_sessions_current);
		$csv_content[] = array( get_lang('NbPastSessions').';'.$nb_sessions_past);
		$csv_content[] = array( get_lang('NbFutureSessions').';'.$nb_sessions_future);
		$csv_content[] = array( get_lang('NbStudentPerSession').';'.round($nbStudents/$nbSessions,1));
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
						'.round($nbStudents/$nbSessions,1).'
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
			<a href="student.php">'.get_lang('SeeSessionList').'</a>
		 </div>';
	 }
	 
	 
}

else
{


	//Trainers
	if(api_is_platform_admin())
	{
		$sqlNbFormateurs = "SELECT COUNT(user_id) FROM $tbl_user WHERE status = 1";
		$resultNbFormateurs = api_sql_query($sqlNbFormateurs);
		$a_nbFormateurs = mysql_fetch_array($resultNbFormateurs);
		$nbFormateurs = $a_nbFormateurs[0];
	}
	 	
	//Coachs
	$nbCoachs=0;
	if(api_is_platform_admin())
	{
		$sqlNbCoachs = "SELECT COUNT(DISTINCT id_coach)	FROM $tbl_session_course WHERE id_coach<>'0'";
		$resultNbCoachs = api_sql_query($sqlNbCoachs);
		$a_nbCoachs = mysql_fetch_array($resultNbCoachs);
		$nbCoachs = $a_nbCoachs[0];
	}
	elseif($is_allowedCreateCourse)
	{
		$a_coach=array();
	  		
	  	$sqlNbCours = "	SELECT course_code
						FROM $tbl_course_user
					  	WHERE user_id='".$_user['user_id']."' AND status='1'
					  ";
		$resultNbCours = api_sql_query($sqlNbCours);
		
		while($a_courses=mysql_fetch_array($resultNbCours))
		{
			$sql="SELECT DISTINCT id_coach FROM $tbl_session_course WHERE course_code='".$a_courses["course_code"]."'";
				
			$resultCoach = api_sql_query($sql);
				
			if(mysql_num_rows($resultCoach)>0)
			{
				while($a_temp=mysql_fetch_array($resultCoach))
				{
					$a_coach[]=$a_temp["id_coach"];
				}
			}
		}
			
		$a_coach=array_unique($a_coach);
		$nbCoachs=count($a_coach);
	}
	
	
		
	//Nombre de stagiaires (cours dans lesquels il est coach ou formateurs)
		
		$nbStagiaire=0;
		$a_stagiaire_teacher=array();
		
		//La personne est admin
		if(api_is_platform_admin())
		{
	
			$sqlNbStagiaire = "	SELECT COUNT(user_id)
						  		FROM $tbl_user
						  		WHERE status = 5 
						 	  ";
			$resultNbStagiaire = api_sql_query($sqlNbStagiaire);
			$a_nbStagiaire = mysql_fetch_array($resultNbStagiaire);
			$nbStagiaire = $a_nbStagiaire[0];
		}
		else
		{
			//La personne a le statut de professeur
			if($is_allowedCreateCourse){
				
				//Cours ou la personne est formateur mais dont les cours ne sont pas dans une session
				$sql_select_courses="SELECT course_rel_user.course_code FROM $tbl_course_user as course_rel_user LEFT OUTER JOIN $tbl_session_course as src ON course_rel_user.course_code=src.course_code WHERE user_id='$_uid' AND status='1' AND src.course_code IS NULL";
				
				$result_courses=api_sql_query($sql_select_courses);
				
				while($a_courses=mysql_fetch_array($result_courses))
				{
					$s_course_code=$a_courses["course_code"];
			 		$sqlStudents = "SELECT user.user_id,lastname,firstname,email FROM $tbl_course_user as course_rel_user, $tbl_user as user WHERE course_rel_user.user_id=user.user_id AND course_rel_user.status='5' AND course_rel_user.course_code='$s_course_code'";
			 		$result_students=api_sql_query($sqlStudents);
			 		if(mysql_num_rows($result_students)>0)
			 		{
		 				while($a_students_temp=mysql_fetch_array($result_students))
		 				{
		 					$a_stagiaire_teacher[]=$a_students_temp["user_id"];
		 				}
			 		}
				}
	
				$sqlNbStagiaire="SELECT DISTINCT srcru.id_user FROM $tbl_course_user as course_rel_user, $tbl_session_course_user as srcru " .
								"WHERE course_rel_user.user_id='".$_user['user_id']."' AND course_rel_user.status='1' AND course_rel_user.course_code=srcru.course_code";
				
				$resultNbStagiaire = api_sql_query($sqlNbStagiaire);
				
				while($a_temp = mysql_fetch_array($resultNbStagiaire))
				{
					$a_stagiaire_teacher[]=$a_temp[0];
				}
		}
			
			if($isCoach)
			{
				$a_stagiaire_coach=array();
				
				$sql="SELECT id_session, course_code FROM $tbl_session_course WHERE id_coach='".$_user['user_id']."'";
	
				$result=api_sql_query($sql);
				
				while($a_courses=mysql_fetch_array($result))
				{
			    	$course_code=$a_courses["course_code"];
			    	$id_session=$a_courses["id_session"];
			    	
			    	$sqlStudents = "SELECT distinct	srcru.id_user  
									FROM $tbl_session_course_user AS srcru 
									INNER JOIN $tbl_user as user 
										ON srcru.id_user = user.user_id 
										AND user.status = 5 
									WHERE course_code='$course_code' AND id_session='$id_session'";
	
					$q_students=api_sql_query($sqlStudents);
					
					while($a_temp=mysql_fetch_array($q_students))
					{
						$a_stagiaire_coach[]=$a_temp[0];
					}
					
			    }
			    $a_stagiaires=array_merge($a_stagiaire_teacher,$a_stagiaire_coach);
					
				$a_stagiaires=array_unique($a_stagiaires);
				
				$nbStagiaire=count($a_stagiaires);
			    
			}
			else
			{
				$nbStagiaire=count($a_stagiaire_teacher);
			}
		}
		
		//Nombre de cours
		//La personne est admin donc on compte le nombre total de cours
		if(api_is_platform_admin())
		{
		
			$sqlNbCours = "	SELECT COUNT(code)
							FROM $tbl_course
						  ";
			$resultNbCours = api_sql_query($sqlNbCours);
			$a_nbCours = mysql_fetch_array($resultNbCours);
			$nbCours = $a_nbCours[0];
			
		}
		
		else{
			
			$a_cours=array();
			
			//La personne a le statut de professeur	
			if($is_allowedCreateCourse)
			{
				
				$sqlNbCours = "	SELECT DISTINCT course_code
								FROM $tbl_course_user
							  	WHERE user_id='".$_user['user_id']."' AND status='1'
							  ";
				$resultCours = api_sql_query($sqlNbCours);
				
				while($a_cours_teacher = mysql_fetch_array($resultCours)){
					$a_cours[]=$a_cours_teacher["course_code"];
				}
				
			}
			
			
			$a_cours=array_unique($a_cours);
			$nbCours=count($a_cours);
			
		}
		
		
		//Nombre de sessions
		
		//La personne est admin donc on compte le nombre total de sessions
		if(api_is_platform_admin())
		{
			$sqlNbSessions = "	SELECT COUNT(id)
								FROM $tbl_sessions
							 ";
			$resultNbSessions = api_sql_query($sqlNbSessions);
			$a_nbSessions= mysql_fetch_array($resultNbSessions);
			$nbSessions = $a_nbSessions[0];
		}
		else
		{
			$a_sessions=array();
			
			if($is_allowedCreateCourse)
			{
				
				$sqlNbSessions = "	SELECT DISTINCT id_session 
									FROM $tbl_session_course as session_course, $tbl_course_user as course_rel_user  
								  	WHERE session_course.course_code=course_rel_user.course_code AND course_rel_user.status='1' AND course_rel_user.user_id='".$_user['user_id']."' 
								  ";
				$resultNbSessions = api_sql_query($sqlNbSessions);
				
				while($a_temp = mysql_fetch_array($resultNbSessions))
				{
					$a_sessions[]=$a_temp["id_session"];
				}
				
			}
			
			if($isCoach)
			{
				$sqlNbSessions = "	SELECT DISTINCT id_session 
									FROM $tbl_session_course 
								  	WHERE id_coach='".$_user['user_id']."' 
								  ";
	
				$resultNbSessions = api_sql_query($sqlNbSessions);
				
				while($a_temp = mysql_fetch_array($resultNbSessions))
				{
					$a_sessions[]=$a_temp["id_session"];
				}
			}
			
			$a_sessions=array_unique($a_sessions);
			$nbSessions = count($a_sessions);
			
		}
	
	
	if(api_is_platform_admin())
	{
		echo '<div class="admin_section">
			<h4>
			<a href="teachers.php"><img src="'.api_get_path(WEB_IMG_PATH).'teachers.gif">&nbsp;'.get_lang('Trainers').' ('.$nbFormateurs.')</a>
			</h4>
		 </div>';
	}
	 
	if((api_is_platform_admin() || ($is_allowedCreateCourse && $nbCoachs>0)) && api_get_setting('use_session_mode')=='true')
	{ // if the user is platform admin, or if he's a teacher which manage coaches
		 echo '<div class="admin_section">
			<h4>
				<a href="coaches.php"><img src="'.api_get_path(WEB_IMG_PATH).'coachs.gif">&nbsp;'.get_lang("Tutors").' ('.$nbCoachs.')</a>
			</h4>
		 </div>';
	}
	
	
	if(api_is_platform_admin())
	{
		
		$sql_nb_admin="SELECT count(user_id) FROM $tbl_admin";
		$resultNbAdmin = api_sql_query($sql_nb_admin);
		$i_nb_admin=mysql_result($resultNbAdmin,0,0);
		echo '
		 <div class="admin_section">
			<h4>
				<a href="admin.php"><img src="'.api_get_path(WEB_IMG_PATH).'admins.gif>&nbsp;'.get_lang('Administrators').' ('.$i_nb_admin.')</a>
			</h4>
		 </div>';
	}
	if($nbCours)
	{
		echo '
		 <div class="admin_section">
			<h4>
				<a href="cours.php"><img src="'.api_get_path(WEB_IMG_PATH).'courses.gif">&nbsp;'.get_lang('Courses').' ('.$nbCours.')'.'</a>
			</h4>
		 </div>';
	}
	if(api_get_setting('use_session_mode')=='true'){
		echo '
		 <div class="admin_section">
			<h4>
				<a href="session.php"><img src="'.api_get_path(WEB_IMG_PATH).'sessions.gif">&nbsp;'.get_lang('Sessions').' ('.$nbSessions.')'.'</a>
			</h4>
		 </div>';
	}
}

// send the csv file if asked
if($export_csv)
{
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
