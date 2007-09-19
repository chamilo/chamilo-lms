<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
$language_file = array ('registration', 'index','tracking');
require ('../inc/global.inc.php');
 
 $nameTools= get_lang('Progression');
 // name of the language file that needs to be included 

 $cidReset=true;

 
 $this_section = "session_my_space";
 
 api_block_anonymous_users();
  $interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 Display :: display_header($nameTools);
 
// Database Table Definitions 
$tbl_course 		= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 			= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session 		= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_track_exercice = Database :: get_statistic_table(STATISTIC_TRACK_E_EXERCICES_TABLE);
 
 /*
 ===============================================================================
 	FUNCTION
 ===============================================================================  
 */
 
 function exportCsv($a_header,$a_data)
 {
 	global $archiveDirName;
	
	$fileName = 'test.csv';
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
		$message = get_lang('UsageDatacreated');
		
		header("Location:".$archiveURL.$fileName);
	}
	
	return $message;
 }
 
 /*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
 $sqlCourse = "	SELECT 	title,code
					FROM $tbl_course as course
					ORDER BY title ASC
				  ";
	
	$resultCourse = api_sql_query($sqlCourse);

	if(mysql_num_rows($resultCourse)>0)
	{
		if(isset($_POST['export']))
		{
			$exportResult = exportCsv($header,$data);
			Display :: display_error_message($exportResult);
		}
		echo '<table class="data_table">
			 	<tr>
					<th>
						'.get_lang('Course').'
					</th>
					<th>
						'.get_lang('TempsFrequentation').'
					</th>
					<th>
						'.get_lang('Progression').'
					</th>
					<th>
						'.get_lang('MoyenneTest').'
					</th>
				</tr>
          	 ';
        $header = array(get_lang('Course'),get_lang('TempsFrequentation'),get_lang('Progression'),get_lang('MoyenneTest'));
		while($a_course= mysql_fetch_array($resultCourse))
		{
			$sqlMoyTest = "	SELECT exe_result,exe_weighting
							FROM $tbl_track_exercice
							WHERE exe_cours_id = '".$a_course['code']."'
						  ";
			$resultMoyTest = api_sql_query($sqlMoyTest);
			$result = 0;
			$weighting = 0;
			while($a_moyTest = mysql_fetch_array($resultMoyTest))
			{
				$result = $result + $a_moyTest['exe_result'];
				$weighting = $weighting + $a_moyTest['exe_weighting'];
			}
			$moyenneTest = round(($result*100)/$weighting);
			echo '<tr>
					<td>
				 		'.$a_course['title'].'
					</td>
					<td>
						
					</td>
					<td>
						
					</td>
					<td align="center">
						'.$moyenneTest.'%
					</td>
				  </tr>
				 ';
		}
		echo '</table>';
		
		echo "<br /><br />";
		echo "<form method='post'>
				<input type='submit' name='export' value='".get_lang('exportExcel')."'/>
			  <form>";
	}
	else
	{
		echo get_lang('NoCourse');
	}
 
 /*
 ==============================================================================
		FOOTER
 ==============================================================================
 */
	
	Display :: display_footer();
?>
