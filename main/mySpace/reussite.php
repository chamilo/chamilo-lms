<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
 $nameTools= 'Réussite';
 // name of the language file that needs to be included 
$language_file = array ('registration', 'index','trad4all','tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 
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
 	MAIN CODE
 ===============================================================================  
 */
 if(!empty($_GET['session']))
 {
 	
 	$sqlSession = "	SELECT name,id
					FROM $tbl_session
					ORDER BY name ASC
				  ";
	$resultSession = api_sql_query($sqlSession);
	
	echo "<a href='".api_get_self()."'>".get_lang('MoyCourse')."</a>";
	echo "<br /><br />";
	
	if(mysql_num_rows($resultSession)>0)
	{
		
		
		echo '<table class="data_table">
			 	<tr>
					<th>
						'.get_lang('Session').'
					</th>
					<th>
						'.get_lang('MoyenneTest').'
					</th>
					<th>
						'.get_lang('MoyenneExamen').'
					</th>
				</tr>
          	 ';
		
		while($a_session = mysql_fetch_array($resultSession))
		{
			$sqlCourse = "	SELECT 	title,code
							FROM $tbl_course as course
							INNER JOIN $tbl_session_course AS rel_course
								ON course.code = rel_course.course_code
								AND rel_course.id_session = ".$a_session['id']."
							ORDER BY title ASC
				  		 ";
	
			$resultCourse = api_sql_query($sqlCourse);
			$totalResult = 0;
			$totalWeighting = 0;
			while($a_course = mysql_fetch_array($resultCourse))
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
				$totalResult = $totalResult + $result;
				$totalWeighting = $totalWeighting + $weighting;
			}
			$moyenneTest = round(($totalResult*100) / $totalWeighting);
			
			
			echo '<tr>
					<td>
				 		'.$a_session['name'].'
					</td>
					<td align="center">
						'.$moyenneTest.'%
					</td>
				 	<td>
						
					</td>
				  </tr>
				 ';
			
		}
		echo '</table>';
	}
	else
	{
		echo get_lang('NoSession');
	}
 }
 else
 {
 	$sqlCourse = "	SELECT 	title,code
					FROM $tbl_course as course
					ORDER BY title ASC
				  ";
	
	$resultCourse = api_sql_query($sqlCourse);
	
	echo "<a href='".api_get_self()."?session=true'>".get_lang('MoySession')."</a>";
	echo "<br /><br />";
	if(mysql_num_rows($resultCourse)>0)
	{
		echo '<table class="data_table">
			 	<tr>
					<th>
						'.get_lang('Course').'
					</th>
					<th>
						'.get_lang('MoyenneTest').'
					</th>
					<th>
						'.get_lang('MoyenneExamen').'
					</th>
				</tr>
          	 ';
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
					<td align="center">
						'.$moyenneTest.'%
					</td>
				 	<td>
						
					</td>
				  </tr>
				 ';
		}
		echo '</table>';
	}
	else
	{
		echo get_lang('NoCourse');
	}
 }
 /*
 ==============================================================================
		FOOTER
 ==============================================================================
 */
	
	Display :: display_footer();
?>
