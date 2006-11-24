<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
ob_start();
 $nameTools= 'Students';
 $langFile = array ('registration', 'index','trad4all', 'tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 
 $this_section = "session_my_space";
 
 api_block_anonymous_users();
  $interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
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
 	
 	
 	$sql_select_courses="SELECT course_rel_user.course_code FROM $tbl_course_user as course_rel_user LEFT OUTER JOIN $tbl_session_course as src ON course_rel_user.course_code=src.course_code WHERE user_id='$i_teacher_id' AND status='1' AND src.course_code IS NULL";
	
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
 

/*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
	$a_students=array();
	
	//La personne est admin
	if(api_is_platform_admin()){
	
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
	
	else{
		
		$a_students=getStudentsFromCoursesNoSession($_user['user_id'], $a_students);

		$a_students=getStudentsFromCoursesFromSessions($_user['user_id'], $a_students);

		$a_students=getStudentsFromCoursesFromSessionsCoach($_user['user_id'], $a_students);

		
	}	  
	
	usort($a_students,"mysort");
	
	$a_header[]=get_lang('Lastname');
	$a_header[]=get_lang('Firstname');
	$a_header[]=get_lang('Email');
	
	$a_data=array();

	if(count($a_students)>0)
	{
		echo '<table class="data_table">
			 	<tr>
					<th>
						'.get_lang('Lastname').'
					</th>
					<th>
						'.get_lang('Firstname').'
					</th>
					<th>
						'.get_lang('Email').'
					</th>
					<th>
						'.get_lang('Tracking').'
					</th>
				</tr>
          	 ';
          	 
		foreach($a_students as $a_current_student)
		{
			
			if($i%2==0){
				$s_css_class="row_odd";
				
				if($i%20==0 && $i!=0){
					/*echo '<tr>
							<th>
								'.get_lang('Lastname').'
							</th>
							<th>
								'.get_lang('Firstname').'
							</th>
							<th>
								'.get_lang('Email').'
							</th>
							<th>
								'.get_lang('Tutor').'
							</th>
							<th>
								'.get_lang('Courses').'
							</th>
							<th>
								'.get_lang('TakenSessions').'
							</th>
						</tr>
		          	 ';*/
		          	 echo '<tr>
							<th>
								'.get_lang('Lastname').'
							</th>
							<th>
								'.get_lang('Firstname').'
							</th>
							<th>
								'.get_lang('Email').'
							</th>
							<th>
								'.get_lang('Tracking').'
							</th>
						</tr>
		          	 ';
				}
			}
			else{
				$s_css_class="row_even";
			}
			
			$i++;
			
			echo '<tr class="'.$s_css_class.'">
					<td>
				 ';
			echo		"<a href='myStudents.php?student=".$a_current_student[0]."#infosStudent'>".$a_current_student[1]."</a>";
			echo '	</td>
					<td>
						<a href="myStudents.php?student='.$a_current_student[0].'#infosStudent">'.$a_current_student[2].'</a>
					</td>
					<td>
				 ';
			if(!empty($a_current_student[3]))
			{	
				echo	'<a href="mailto:'.$a_current_student[3].'">'.$a_current_student[3].'</a>';
			}
			else
			{
				//echo get_lang('NoEmail');
			}
			echo '	</td>';
			
			
			if($a_current_student["teacher"]==true){
							
				echo '<td align="center"><a href="coaches.php?id_student='.$a_current_student[0].'"><img src="'.api_get_path(WEB_IMG_PATH).'coachs.gif" alt="'.get_lang("StudentTutors").'" title="'.get_lang("StudentTutors").'"></a>&nbsp;<a href="cours.php?type=student&user_id='.$a_current_student[0].'"><img src="'.api_get_path(WEB_IMG_PATH).'course.gif" alt="'.get_lang("StudentCourses").'" title="'.get_lang("StudentCourses").'"></a>&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?student='.$a_current_student[0].'#sessionSuivie"><img src="'.api_get_path(WEB_IMG_PATH).'agenda.gif" alt="'.get_lang("StudentSessions").'" title="'.get_lang("StudentSessions").'"></a>';
				
			}
			else{
				echo '<td></td>';
			}
			  echo '</tr>';

				 
			$a_data[$a_student['user_id']]["lastname"]=$a_student['lastname'];
			$a_data[$a_student['user_id']]["firstname"]=$a_student['firstname'];
			$a_data[$a_student['user_id']]["email"]=$a_student['email'];

		}
		echo '</table>';
	}
	else
	{
		echo get_lang('NoStudent');
	}
	

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
