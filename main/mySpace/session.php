<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
ob_start();
 $nameTools= 'Sessions';
 $langFile = array ('registration', 'index','trad4all','tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 
 $this_section = "session_my_space";
 
 api_block_anonymous_users();
  $interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 Display :: display_header($nameTools);

 
 $tbl_course_user = Database :: get_main_table(MAIN_COURSE_USER_TABLE);
 $tbl_sessions = Database :: get_main_table(MAIN_SESSION_TABLE);
 $tbl_session_course = Database :: get_main_table(MAIN_SESSION_COURSE_TABLE);
 $tbl_course = Database :: get_main_table(MAIN_COURSE_TABLE);
 
 
 /*
 ===============================================================================
 	FUNCTION
 ===============================================================================  
 */
 
 function exportCsv($a_header,$a_data)
 {
 	global $archiveDirName;

	$fileName = 'sessions.csv';
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
 
 
 
 /*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
	
	
	//Nombre de sessions
	
	//La personne est admin donc on récupère toutes les sessions
	if(api_is_platform_admin()){
	
		$sqlSessions = "	SELECT id, name
							FROM $tbl_sessions
						 ";
		
	}
	
	else{
		
		$a_sessions=array();
		
		if($is_allowedCreateCourse){
			
			$sqlSessions = "	SELECT DISTINCT $tbl_sessions.id, name 
								FROM $tbl_session_course as session_course, $tbl_course_user as course_rel_user, $tbl_sessions as session  
							  	WHERE session.id=session_course.id_session AND session_course.course_code=course_rel_user.course_code AND course_rel_user.status='1' AND (course_rel_user.user_id='".$_user['user_id']."' OR session_course.id_coach='".$_user['user_id']."') 
							  ";

			$resultSessions = api_sql_query($sqlSessions);
			
			while($a_temp = mysql_fetch_array($resultSessions)){
				$a_sessions[]=$a_temp["id_session"];
			}
			
		}
		
		$a_sessions=array_unique($a_sessions);
		$nbSessions = count($a_sessions);
		
	}
	
	$a_header[]=get_lang('Title');
	
	$resultSessions = api_sql_query($sqlSessions);
	
	if(mysql_num_rows($resultSessions)>0)
	{
		echo '<table class="data_table">
			 	<tr>
					<th>
						'.get_lang('Title').'
					</th>
					<th>
						'.get_lang('Courses').'
					</th>
				</tr>
          	 ';
		while($a_sessions = mysql_fetch_array($resultSessions))
		{
			
			$i_id_session=$a_sessions['id'];
			/*
			//On récupère tous les cours de la session courante
			$sql="SELECT course.title FROM $tbl_course as course, $tbl_session_course as session_rel_course " .
					"WHERE session_rel_course.id_session='$i_id_session' AND session_rel_course.course_code=course.code";
			
			$resultCourses = api_sql_query($sql);*/
			
			echo '<tr>
					<td>
				 ';
			echo		$a_sessions['name'];
			echo '	</td>
					<td>
						<a href="cours.php?id_session='.$i_id_session.'">-></a>
					</td>
				  </tr>
				 ';
				 
			$a_data[$i_id_session]["name"]=$a_sessions['name'];		
			
		}
		echo '</table>';
	}
	else
	{
		echo get_lang('NoSession');
	}

if(isset($_POST['export'])){
	
	exportCsv($a_header,$a_data);
	
}

echo "<br /><br />";
echo "<form method='post' action='session.php'>
		<input type='submit' name='export' value='".get_lang('exportExcel')."'/>
	  <form>";

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();

?>
