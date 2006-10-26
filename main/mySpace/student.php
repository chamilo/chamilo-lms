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

 
 $tbl_course = Database :: get_main_table(MAIN_COURSE_TABLE);
 $tbl_user = Database :: get_main_table(MAIN_USER_TABLE);
 $tbl_session = Database :: get_main_table(MAIN_SESSION_TABLE);
 $tbl_session_course = Database :: get_main_table(MAIN_SESSION_COURSE_TABLE);
 $tbl_session_rel_user = Database :: get_main_table(MAIN_SESSION_USER_TABLE);
 
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

/*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */

	$sqlStudent = "	SELECT user_id,lastname,firstname,email
					FROM $tbl_user
					WHERE status = 5
					ORDER BY lastname ASC
				  ";
	$resultStudent = api_sql_query($sqlStudent);
	
	$a_header[]=get_lang('Lastname');
	$a_header[]=get_lang('Firstname');
	$a_header[]=get_lang('Email');
	$a_header[]=get_lang('Tutor');
	
	$a_data=array();

	if(mysql_num_rows($resultStudent)>0)
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
						'.get_lang('Tutor').'
					</th>
					<th>
						'.get_lang('TakenSessions').'
					</th>
				</tr>
          	 ';
		while($a_student= mysql_fetch_array($resultStudent))
		{
			
			if($i%2==0){
				$s_css_class="row_odd";
				
				if($i%20==0 && $i!=0){
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
						'.get_lang('Tutor').'
					</th>
					<th>
						'.get_lang('TakenSessions').'
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
				 ';
			echo		$a_student['lastname'];
			echo '	</td>
					<td>
						'.$a_student['firstname'].'
					</td>
					<td>
				 ';
			if(!empty($a_student['email']))
			{	
				echo	'<a href="mailto:'.$s_email.'">'.$a_student['email'].'</a>';
			}
			else
			{
				//echo get_lang('NoEmail');
			}
			echo '	</td>
					<td>
						<a href="coaches.php?id_student='.$a_student['user_id'].'">-></a>
					</td>
					<td align="center">
						<a href="'.$_SERVER['PHP_SELF'].'?student='.$a_student['user_id'].'#sessionSuivie"> -> </a>
					</td>
				  </tr>
				 ';
			$a_data[$a_student['user_id']]["lastname"]=$a_student['lastname'];
			$a_data[$a_student['user_id']]["firstname"]=$a_student['firstname'];
			$a_data[$a_student['user_id']]["email"]=$a_student['email'];
			$a_data[$a_student['user_id']]["coach"]="";
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
