<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 ob_start();
 $nameTools= 'Cours';
 // name of the language file that needs to be included 
$language_file = array ('registration', 'index','trad4all', 'tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 
 $this_section = "session_my_space";
 
 api_block_anonymous_users();
 $interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 
 if(isset($_GET["id_session"]) && $_GET["id_session"]!=""){
	$interbreadcrumb[] = array ("url" => "session.php", "name" => get_lang('Sessions'));
 }
 
 if(isset($_GET["user_id"]) && $_GET["user_id"]!="" && isset($_GET["type"]) && $_GET["type"]=="coach"){
 	 $interbreadcrumb[] = array ("url" => "coaches.php", "name" => get_lang('Tutors'));
 }
 
 if(isset($_GET["user_id"]) && $_GET["user_id"]!="" && isset($_GET["type"]) && $_GET["type"]=="student"){
 	 $interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang('Students'));
 }
 
 if(isset($_GET["user_id"]) && $_GET["user_id"]!="" && !isset($_GET["type"])){
 	 $interbreadcrumb[] = array ("url" => "teachers.php", "name" => get_lang('Teachers'));
 }
 
 Display :: display_header($nameTools);
 
// Database Table Definitions 
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user_course 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
 
/*
 ===============================================================================
 	FUNCTION
 ===============================================================================  
 */
 
 function exportCsv($a_header,$a_data)
 {
 	global $archiveDirName;

	$fileName = 'courses.csv';
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
 
 function is_coach(){
  	
  	global $tbl_session_course;
  	
	$sql="SELECT course_code FROM $tbl_session_course WHERE id_coach='".$_SESSION["_uid"]."'";

	$result=api_sql_query($sql);
	  
	if(mysql_num_rows($result)>0){
	    return true;	    
	}
	else{
		return false;
	}
  }
  
  function isDisplayed($s_code, $a_courses){
  	
  	if(array_key_exists($s_code,$a_courses)){
  		return true;
  	}
  	else{
  		return false;
  	}
  	
  }
  

/*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
	
	$a_courses=array();
	$a_coursesRelUser=array();
	
	if(isset($_GET["id_session"]) && $_GET["id_session"]!=""){
		
		$i_id_session=intval($_GET["id_session"]);
		
		$sqlCourse="SELECT DISTINCT code, title " .
 					"FROM $tbl_course as course, $tbl_session_course as src " .
 					"WHERE course.code=src.course_code AND src.id_session='$i_id_session'";

		$resultCourses = api_sql_query($sqlCourse);
		
		$a_courses = api_store_result($resultCourses);
		
	}
	
	if(isset($_GET["user_id"]) && $_GET["user_id"]!=""){
 		
 		$i_user_id=$_GET["user_id"];
 		
 		//We want to display the course where this user is a coach
 		if(isset($_GET["type"]) && $_GET["type"]=="coach"){
 			
 			$sqlCourse="SELECT title,code " .
 					"FROM $tbl_course as course, $tbl_session_course as src " .
 					"WHERE course.code=src.course_code AND id_coach='$i_user_id'";
 			
 		}
 		
 		elseif(isset($_GET["type"]) && $_GET["type"]=="student"){
 			
 			$sqlCourse="SELECT title,code " .
	 					"FROM $tbl_course as course, $tbl_session_course_user as srcu " .
	 					"WHERE course.code=srcu.course_code AND srcu.id_user='$i_user_id'";
	 					
	 		$sqlCourseRelUser="SELECT title,code " .
	 					"FROM $tbl_course as course, $tbl_user_course as src " .
	 					"WHERE course.code=src.course_code AND src.user_id='$i_user_id' AND src.status='5'";

			$resultCourseRelUser = api_sql_query($sqlCourseRelUser);
		
			$a_coursesRelUser = api_store_result($resultCourseRelUser);
			
			
			
 		}
 		
 		//It's a teacher
 		else{
	 		$sqlCourse = "	SELECT 	title,code
							FROM $tbl_course as course, $tbl_user_course as cru
							WHERE course.code=cru.course_code AND cru.user_id='$i_user_id' AND cru.status='1'
							ORDER BY title ASC
						  ";

 		}
 		
 		$resultCourses = api_sql_query($sqlCourse);
		
		$a_courses = api_store_result($resultCourses);
		
		$a_courses=array_merge($a_courses,$a_coursesRelUser);

 	}
 	if(!isset($_GET["user_id"]) && !isset($_GET["id_session"])){
 		
 		//La personne est admin
		if(api_is_platform_admin()){
 		
	 		$sqlCourse = "	SELECT 	title,code
							FROM $tbl_course as course
							ORDER BY title ASC
						  ";
			$resultCourses = api_sql_query($sqlCourse);
		
			$a_courses = api_store_result($resultCourses);

		}
		else{
			
			if($is_allowedCreateCourse){
				
				$sqlCourse = "	SELECT title,code
								FROM $tbl_course as course, $tbl_user_course as course_rel_user 
								WHERE course_rel_user.course_code=course.code AND course_rel_user.user_id='".$_user['user_id']."' AND course_rel_user.status='1'
								ORDER BY title ASC
							  ";

			}
			
			$resultCoursesTeacher = api_sql_query($sqlCourse);
		
			$a_courses_teacher = api_store_result($resultCoursesTeacher);
			
			if(is_coach()){
				
				$sqlCourse = "	SELECT DISTINCT code, title 
								FROM $tbl_course as course, $tbl_session_course as session_rel_course 
							  	WHERE session_rel_course.course_code=course.code AND id_coach='".$_user['user_id']."' 
							  ";

				$resultCoursesCoach = api_sql_query($sqlCourse);
				$a_courses = array_merge($a_courses_teacher,api_store_result($resultCoursesCoach));
				
				
			}
			
			else{
				$a_courses=$a_courses_teacher;
			}
			
		}
 	}
	
	$a_header[]=get_lang('Title');
	$a_header[]=get_lang('Tutor');
	$a_header[]=get_lang('Teachers');
	
	if(count($a_courses)>0)
	{
		echo '<table class="data_table">
			 	<tr>
					<th>
						'.get_lang('Title').'
					</th>
					<th>
						'.get_lang('Tutor').'
					</th>
					<th>
						'.get_lang('Teachers').'
					</th>
				</tr>
          	 ';
		
		$a_alreadydisplay=array();
		
		foreach($a_courses as $a_course){
			
			if(!isDisplayed($a_course["code"],$a_alreadydisplay)){
				
				$a_alreadydisplay[$a_course["code"]]=1;
				
				$sqlCoach = "SELECT CONCAT(user.firstname,' ',user.lastname) as tutor_name
							 FROM $tbl_user
							 INNER JOIN $tbl_session_course as sessionCourse
								ON sessionCourse.course_code = '".$a_course['code']."'
								AND sessionCourse.id_coach = user.user_id
							";
				$resultCoach = api_sql_query($sqlCoach);
				$a_coach = mysql_fetch_array($resultCoach);
				
				/*$sqlFormateur = "	SELECT CONCAT(user.firstname,' ',user.lastname) as formateur_name
							 		FROM $tbl_user
									INNER JOIN $tbl_session_course as sessionCourse
										ON sessionCourse.course_code = '".$a_course['code']."'
									INNER JOIN $tbl_session AS session
										ON session.id = sessionCourse.id_session
										AND session.id_coach = user.user_id
								";*/
				$sqlFormateur = "	SELECT CONCAT(user.firstname,' ',user.lastname) as formateur_name
							 		FROM $tbl_user as user, $tbl_user_course as cru
									WHERE user.user_id=cru.user_id AND cru.status='1' AND cru.course_code='".$a_course['code']."'
								";
	
				$resultFormateur = api_sql_query($sqlFormateur);
				$a_formateur = mysql_fetch_array($resultFormateur);
				
				if($i%2==0){
					$s_css_class="row_odd";
					
					if($i%20==0 && $i!=0){
							echo '<tr>
							<th>
								'.get_lang('Title').'
							</th>
							<th>
								'.get_lang('Tutor').'
							</th>
							<th>
								'.get_lang('Teachers').'
							</th>
						</tr>';
						}
					
				}
				else{
					$s_css_class="row_even";
				}
				
				$i++;
	
				echo '<tr class="'.$s_css_class.'">
						<td>
					 		<a href="'.api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?cidReq='.$a_course['code'].'">'.$a_course['title'].'</a></td>
						<td>
							'.$a_coach['tutor_name'].'
						</td>
					 	<td>
							'.$a_formateur['formateur_name'].'
						</td>
					  </tr>
					 ';
	
				$a_data[$index]["title"]=$a_course['title'];
				$a_data[$index]["tutor_name"]=$a_coach['tutor_name'];
				$a_data[$index]["formateur_name"]=$a_formateur['formateur_name'];
				
				$index++;			
			}
		}
		echo '</table>';
	}
	else
	{
		echo get_lang('NoCourse');
	}

	if(isset($_POST['export'])){
	
		exportCsv($a_header,$a_data);
		
	}
	
	echo "<br /><br />";
	echo "<form method='post' action='cours.php'>
			<input type='submit' name='export' value='".get_lang('exportExcel')."'/>
		  <form>";
	
 
/*
 ==============================================================================
		FOOTER
 ==============================================================================
 */

 Display :: display_footer();
?>
