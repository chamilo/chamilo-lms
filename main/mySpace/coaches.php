<?php

/*
 * Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
ob_start(); 

// name of the language file that needs to be included 
$language_file = array ('registration', 'index', 'tracking', 'admin');
$cidReset=true;
require ('../inc/global.inc.php');

$this_section = "session_my_space";

$nameTools= get_lang('Tutors');

api_block_anonymous_users();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));

if(isset($_GET["id_student"])){
	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang('Students'));
}

Display :: display_header($nameTools);

api_display_tool_title($nameTools);

// Database Table Definitions
$tbl_course 						= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 					= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_user 							= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session 						= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course 			= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session_rel_user 				= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_track_login 					= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);


/*
 ===============================================================================
 	FUNCTION
 ===============================================================================  
 */
 
 function exportCsv($a_header,$a_data)
 {
 	global $archiveDirName;

	$fileName = 'coaches.csv';
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
		$perm = api_get_setting('permissions_for_new_files');
		$perm = octdec(!empty($perm)?$perm:'0660');
		chmod($fileName,$perm);
		
		header("Location:".$archiveURL.$fileName);
	}
	
	return $message;
 }
 
 function calculHours($seconds)
	{
	  //combien d'heures ?
	  $hours = floor($seconds / 3600);
	
	  //combien de minutes ?
	  $min = floor(($seconds - ($hours * 3600)) / 60);
	  if ($min < 10)
	    $min = "0".$min;
	
	  //combien de secondes
	  $sec = $seconds - ($hours * 3600) - ($min * 60);
	  if ($sec < 10)
	    $sec = "0".$sec;
	        
	  //echo $hours."h".$min."m".$sec."s";
	
		return $hours."h".$min."m".$sec."s" ;
	
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


/**
 * MAIN PART
 */

if(isset($_GET["id_student"])){
	
	$i_id_student=$_GET["id_student"];
	$sqlCoachs = "SELECT DISTINCT src.id_coach " .
					"FROM $tbl_session_rel_course as src, $tbl_session_rel_course_rel_user as srcru " .
					"WHERE src.id_coach<>'0' AND src.course_code=srcru.course_code AND srcru.id_user='$i_id_student' AND srcru.id_session=src.id_session 
				  ";

}

else{
	if(api_is_platform_admin()){
		$sqlCoachs = "	SELECT DISTINCT id_coach, user_id, lastname, firstname
						FROM $tbl_user, $tbl_session_rel_course
						WHERE id_coach=user_id
						ORDER BY lastname ASC
					  ";
	}
	else{
		
		$sqlCoachs = "	SELECT DISTINCT id_coach, $tbl_user.user_id, lastname, firstname
						FROM $tbl_user as user, $tbl_session_rel_course as session_rel_course, $tbl_course_user as course_rel_user  
						WHERE course_rel_user.course_code=session_rel_course.course_code AND course_rel_user.status='1' AND course_rel_user.user_id='".$_SESSION["_uid"]."' 
						AND session_rel_course.id_coach=user.user_id 
						ORDER BY lastname ASC
					  ";
	
	}
}
$resultCoachs = api_sql_query($sqlCoachs);

echo '<table class="data_table">
	 	<tr>
			<th>
				'.get_lang('FirstName').'
			</th>
			<th>
				'.get_lang('LastName').'
			</th>
			<th>
				'.get_lang('ConnectionTime').'
			</th>
			<th>
				'.get_lang('AdminCourses').'
			</th>
			<th>
				'.get_lang('Students').'
			</th>
		</tr>
  	 ';

$a_header[]=get_lang('FirstName');
$a_header[]=get_lang('LastName');
$a_header[]=get_lang('ConnectionTime');

if(mysql_num_rows($resultCoachs)>0){
	
	while($a_coachs=mysql_fetch_array($resultCoachs)){
		
		$i_id_coach=$a_coachs["id_coach"];
		
		if(isset($_GET["id_student"])){
			$sql_infos_coach="SELECT lastname, firstname FROM $tbl_user WHERE user_id='$i_id_coach'";
			$resultCoachsInfos = api_sql_query($sql_infos_coach);
			$s_lastname=mysql_result($resultCoachsInfos,0,"lastname");
			$s_firstname=mysql_result($resultCoachsInfos,0,"firstname");
		}
		
		else{
			$s_lastname=$a_coachs["lastname"];
			$s_firstname=$a_coachs["firstname"];
		}
		
		$s_sql_connection_time="SELECT login_date, logout_date FROM $tbl_track_login WHERE login_user_id ='$i_id_coach' AND logout_date <> 'null'";

		$q_result_connection_time=api_sql_query($s_sql_connection_time);
		
		$i_nb_seconds=0;
		
		while($a_connections=mysql_fetch_array($q_result_connection_time)){
			
			$s_login_date=$a_connections["login_date"];
			$s_logout_date=$a_connections["logout_date"];
			
			$i_timestamp_login_date=strtotime($s_login_date);
			$i_timestamp_logout_date=strtotime($s_logout_date);
			
			$i_nb_seconds+=($i_timestamp_logout_date-$i_timestamp_login_date);
			
		}
		
		$s_connection_time=calculHours($i_nb_seconds);
		if($s_connection_time=="0h00m00s"){
			$s_connection_time="";
		}
		
		if($i%2==0){
			$s_css_class="row_odd";
			
			if($i%20==0 && $i!=0){
				echo '<tr>
					<th>
						'.get_lang('LastName').'
					</th>
					<th>
						'.get_lang('FirstName').'
					</th>
					<th>
						'.get_lang('ConnectionTime').'
					</th>
					<th>
						'.get_lang('AdminCourses').'
					</th>
					<th>
						'.get_lang('Students').'
					</th>
				</tr>';
			}
			
		}
		else{
			$s_css_class="row_even";
		}
		
		$i++;
		
		$a_data[$i_id_coach]["firstname"]=$s_firstname;
		$a_data[$i_id_coach]["lastname"]=$s_lastname;
		$a_data[$i_id_coach]["connection_time"]=$s_connection_time;
			
		echo '<tr class="'.$s_css_class.'"><td>'.$s_firstname.'</td><td>'.$s_lastname.'</td><td>'.$s_connection_time.'</td><td><a href="course.php?type=coach&user_id='.$i_id_coach.'">-></a></td><td><a href="student.php?type=coach&user_id='.$i_id_coach.'">-></a></td></tr>';
		
	}
	
}

//No results
else{
	
	echo '<tr><td colspan="5" "align=center">'.get_lang("NoResult").'</td></tr>';
	
}

echo '</table>';

if(isset($_POST['export'])){
	
	exportCsv($a_header,$a_data);
	
}

echo "<br /><br />";
echo "<form method='post' action='coaches.php'>
		<button type='submit' class='save' name='export' value='".get_lang('exportExcel')."'>".get_lang('exportExcel')."</button>
	  <form>";

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();

?>
