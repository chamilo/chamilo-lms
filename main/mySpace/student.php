<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
ob_start();

 // name of the language file that needs to be included 
$language_file = array ('registration', 'index', 'tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 require_once (api_get_path(LIBRARY_PATH).'tracking.lib.php');
 require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
 require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
 
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
 
 function exportCsv($a_header,$a_data)
 {
 	global $archiveDirName;

	$fileName = 'students.csv';
	$archivePath = api_get_path(SYS_PATH).$archiveDirName.'/';
	$archiveURL = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';
	
	if(!$open = fopen($archivePath.$fileName,'w+'))
	{
		$message = get_lang('noOpen');
	}
	else
	{
		$info = '';
		
		foreach($a_header as $header)
		{
			$info .= $header.';';
		}
		$info .= "\r\n";
		
		
		foreach($a_data as $data)
		{
			foreach($data as $infos)
			{
				$info .= $infos.';';
			}
			$info .= "\r\n";
		}
		
		fwrite($open,$info);
		fclose($open);
		chmod($fileName,0777);
		
		header("Location:".$archiveURL.$fileName);
	}
	
	return $message;
 }
 
 /**
  * Returns an array of students of courses (who belong to no sessions) which in the given user is a teacher
  * @param int teacher_id The id of the teacher
  */
 function getStudentsFromCoursesNoSession($i_teacher_id, $a_students){
 	
 	$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
 	$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
 	$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
 	$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
 	
 	
 	//$sql_select_courses="SELECT course_rel_user.course_code, src.course_code as test FROM $tbl_course_user as course_rel_user LEFT OUTER JOIN $tbl_session_course as src ON course_rel_user.course_code=src.course_code WHERE user_id='$i_teacher_id' AND status='1' AND src.course_code IS NULL";
 	$sql_select_courses="SELECT course_rel_user.course_code FROM $tbl_course_user as course_rel_user  WHERE user_id='$i_teacher_id' AND status='1'";

 	$result_courses=api_sql_query($sql_select_courses);
 	
 	while($a_courses=mysql_fetch_array($result_courses)){
 		
 		$s_course_code=$a_courses["course_code"];
 		$sqlStudents = "SELECT user.user_id,lastname,firstname,email FROM $tbl_course_user as course_rel_user, $tbl_user as user WHERE course_rel_user.user_id=user.user_id AND course_rel_user.status='5' AND course_rel_user.course_code='$s_course_code'";

 		$result_students=api_sql_query($sqlStudents);

 		if(mysql_num_rows($result_students)>0){
 		
	 		while($a_students_temp=mysql_fetch_array($result_students)){
	 			
	 			$a_current_student=array();
	 			
	 			//If the user has already been added to the table
	 			if(!array_key_exists($a_students,$a_students_temp["user_id"])){
	 			
		 			$a_current_student[]=$a_students_temp["user_id"];
		 			$a_current_student[]=$a_students_temp["lastname"];
		 			$a_current_student[]=$a_students_temp["firstname"];
		 			$a_current_student[]=$a_students_temp["email"];
		 			
		 			$a_students[$a_students_temp["user_id"]]=$a_current_student;
		 			$a_students[$a_students_temp["user_id"]]["teacher"]=true;
		 			
	 			}
	 			
	 		}
	 		
 		}
 		 
 	}
 	
 	return $a_students;
 	
 }
 
 
 /**
  * Returns an array of students of courses (who belong to at least one session) which in the given user is a teacher
  * @param int teacher_id The id of the teacher
  */
 function getStudentsFromCoursesFromSessions($i_teacher_id, $a_students){
 	
 	$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
 	$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
 	$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
 	$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
 	
 	$sql_select_courses="SELECT course_rel_user.course_code FROM $tbl_course_user as course_rel_user, $tbl_session_course as session_rel_course WHERE user_id='$i_teacher_id' AND status='1' AND session_rel_course.course_code=course_rel_user.course_code";
 	$result_courses=api_sql_query($sql_select_courses);

 	while($a_courses=mysql_fetch_array($result_courses)){
 		
 		$s_course_code=$a_courses["course_code"];
 		$sqlStudents = "SELECT DISTINCT user.user_id,lastname,firstname,email FROM $tbl_session_course_user as srcru, $tbl_user as user WHERE srcru.id_user=user.user_id AND srcru.course_code='$s_course_code'";
 		$result_students=api_sql_query($sqlStudents);

 		if(mysql_num_rows($result_students)>0){
 		
	 		while($a_students_temp=mysql_fetch_array($result_students)){
	 			
	 			$a_current_student=array();
	 			
	 			//If the user has already been added to the table
	 			if(!array_key_exists($a_students,$a_students_temp["user_id"])){
	 				
		 			$a_current_student[]=$a_students_temp["user_id"];
		 			$a_current_student[]=$a_students_temp["lastname"];
		 			$a_current_student[]=$a_students_temp["firstname"];
		 			$a_current_student[]=$a_students_temp["email"];
		 			
		 			$a_students[$a_students_temp["user_id"]]=$a_current_student;
		 			$a_students[$a_students_temp["user_id"]]["teacher"]=true;
		 			
	 			}
	 			
	 		}
	 		
 		}
 		 
 	}
 	
 	return $a_students;
 	
 }
 
 
 /**
  * Returns an array of students of courses which in the given user is a coach
  * @param int coach_id The id of the coach
  */
 function getStudentsFromCoursesFromSessionsCoach($i_coach_id, $a_students){
 	
 	$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
 	$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
 	$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
 	$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
 	
 	$sql_select_courses="SELECT course_code, id_session FROM $tbl_session_course as session_rel_course WHERE session_rel_course.id_coach='$i_coach_id'";
 	$result_courses=api_sql_query($sql_select_courses);

 	while($a_courses=mysql_fetch_array($result_courses)){
 		
 		$s_course_code=$a_courses["course_code"];
 		$i_id_session=$a_courses["id_session"];
 		
 		$sqlStudents="SELECT user.user_id,lastname,firstname,email 
						FROM $tbl_session_course_user as srcru, $tbl_user as user 
						WHERE srcru.course_code='$s_course_code' AND srcru.id_session='$i_id_session' AND srcru.id_user=user.user_id 
						";
 		$result_students=api_sql_query($sqlStudents);

 		if(mysql_num_rows($result_students)>0){
 		
	 		while($a_students_temp=mysql_fetch_array($result_students)){
	 			
	 			$a_current_student=array();
	 			
	 			//If the user has already been added to the table
	 			if(!array_key_exists($a_students,$a_students_temp["user_id"])){
	 			
		 			$a_current_student[]=$a_students_temp["user_id"];
		 			$a_current_student[]=$a_students_temp["lastname"];
		 			$a_current_student[]=$a_students_temp["firstname"];
		 			$a_current_student[]=$a_students_temp["email"];
		 			
		 			$a_students[$a_students_temp["user_id"]]=$a_current_student;
		 			$a_students[$a_students_temp["user_id"]]["teacher"]=false;
		 			
	 			}
	 			
	 		}
	 		
 		}
 		 
 	}
 	
 	return $a_students;
 	
 }
 
 
 function mysort($a , $b){
	if($a[1]>$b[1]){
		return 1;
	}
	else if($b[1]>$a[1]){
		return -1;
	}
	else {
		return 0;
	}
	
}

function count_student_coached()
{
	global $a_students;
	return count($a_students);
}

/*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
 
 
 
$a_courses = Tracking :: get_courses_followed_by_coach($_user['user_id']);
$a_students = Tracking :: get_student_followed_by_coach($_user['user_id']);

if(count($a_students)>0)
{
	$table = new SortableTable('tracking', 'count_student_coached');
	$table -> set_header(0, get_lang('Name'),false);
	$table -> set_header(1, get_lang('Time'),false);
	$table -> set_header(2, get_lang('Progress'),false);
	$table -> set_header(3, get_lang('Score'),false);	
	$table -> set_header(4, get_lang('Student_publication'),false);
	$table -> set_header(5, get_lang('Messages'),false);
	$table -> set_header(6, get_lang('LatestLogin'),false);
	$table -> set_header(7, get_lang('Details'),false);
     
      	 
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
		$row[] = $student_datas['firstname'].' '.$student_datas['lastname'];
		$row[] = api_time_to_hms($avg_time_spent);
		$row[] = $avg_student_progress.' %';
		$row[] = $avg_student_score.' %';		
		$row[] = $total_assignments;
		$row[] = $total_messages;
		$row[] = Tracking :: get_last_connection_date($student_id);
		$row[] = '<a href="myStudents.php?student='.$student_id.'">-></a>';
		
		$table -> addRow($row);

	}
	$table -> display();
	echo '</table>';
}
else
{
	echo get_lang('NoStudent');
}
exit;
 
 
 
 
 
 
 
	$a_students=array();
	
	//La personne est admin
	if(api_is_platform_admin() && !isset($_GET["user_id"])){
	
		$sqlStudent = "	SELECT user_id,lastname,firstname,email
						FROM $tbl_user
						WHERE status = '5'
						ORDER BY lastname ASC
					  ";
		$resultStudent = api_sql_query($sqlStudent);
		
		while($a_students_temp=mysql_fetch_array($resultStudent)){
			
			$a_current_student=array();
	 			
 			//If the user has already been added to the table
 			
 			$a_current_student[]=$a_students_temp["user_id"];
 			$a_current_student[]=$a_students_temp["lastname"];
 			$a_current_student[]=$a_students_temp["firstname"];
 			$a_current_student[]=$a_students_temp["email"];
 			
 			$a_students[$a_students_temp["user_id"]]=$a_current_student;
 			$a_students[$a_students_temp["user_id"]]["teacher"]=true;
	 		
		}
	}
	
	elseif(api_is_platform_admin() && isset($_GET["user_id"])){
		
		$sqlStudent = "	SELECT user_id,lastname,firstname,email
						FROM $tbl_user
						WHERE user_id='".$_GET["user_id"]."'
						ORDER BY lastname ASC
					  ";

		$resultStudent = api_sql_query($sqlStudent);
		
		$a_current_student[]=mysql_result($resultStudent,0,"user_id");
		$a_current_student[]=mysql_result($resultStudent,0,"lastname");
		$a_current_student[]=mysql_result($resultStudent,0,"firstname");
		$a_current_student[]=mysql_result($resultStudent,0,"email");
		
		$a_students[$_GET["user_id"]]=$a_current_student;
		$a_students[$_GET["user_id"]]["teacher"]=true;

	}
	
	else{

		if(isset($_GET["user_id"])){
			
			//It's a teacher
			if(!isset($_GET["type"])){
			
				$a_students=getStudentsFromCoursesNoSession($_GET["user_id"], $a_students);
				
				$a_students=getStudentsFromCoursesFromSessions($_GET["user_id"], $a_students);
				
			}
			
			//It's a coach
			else{
				$a_students=getStudentsFromCoursesFromSessionsCoach($_user['user_id'], $a_students);
			}
			
		}
		
		else{
			
			$a_students=getStudentsFromCoursesNoSession($_user['user_id'], $a_students);
	
			$a_students=getStudentsFromCoursesFromSessions($_user['user_id'], $a_students);
	
			$a_students=getStudentsFromCoursesFromSessionsCoach($_user['user_id'], $a_students);
			
		}

		
	}
	
	usort($a_students,"mysort");
	
	
	

	if(isset($_POST['export'])){
	
		exportCsv($a_header,$a_data);
		
	}
	
	echo "<br /><br />";
	echo "<form method='post' action='student.php'>
			<input type='submit' name='export' value='".get_lang('exportExcel')."'/>
		  <form>";	

	if(!empty($_GET['student']))
	{
		$sqlSessionSuivie = "	SELECT session.name
								FROM $tbl_session as session
								INNER JOIN $tbl_session_rel_user as relUser
									ON session.id = relUser.id_session
									AND relUser.id_user = ".$_GET['student']
							;
		
		$resultSessionSuivie = api_sql_query($sqlSessionSuivie);
		echo "<br /><br />";
		echo "<a name='sessionSuivie'></a>";
		
		if(mysql_num_rows($resultSessionSuivie)>0)
		{
			echo "<table class='data_table'>
					<tr class='tableName'>
						<td colspan='2'>
							<strong>".get_lang('TakenSessions')."</strong>
						</td>
					</tr>
					<tr>
						<th>
							".get_lang('Session')."
						</th>
						<th>
							".get_lang('FollowUp')."
						</th>
					</tr>
				 ";
			while($a_sessionSuivie = mysql_fetch_array($resultSessionSuivie))
			{
				echo "<tr>
						<td>
							".$a_sessionSuivie['name']."
						</td>
						<td align='center'>
							<a href='../../myStudents.php?student=".$_GET['student']."#infosStudent'> -> </a>
						</td>
					  </tr>
					 ";
			}
			echo "</table>";
		}
		else
		{
			echo "<h4>".get_lang('TakenSessions')."</h4><br />";
			echo get_lang('NoSession');
		}
	}

/*
 ==============================================================================
		FOOTER
 ==============================================================================
 */
	
	Display :: display_footer();
?>
