 <?php
// $Id: course_add.php,v 1.10 2005/05/30 11:46:48 bmol Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

require_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");

$table_survey = Database :: get_course_table('survey');

$surveyid=$_REQUEST['surveyid'];
 $cidReq=$_REQUEST['cidReq'];
 $db_name = $_REQUEST['db_name'];
if($_SESSION['status']==5)
{
	api_protect_admin_script();
}
$screen = isset($_POST['screen']) ? $_POST['screen'] : '1' ;

$tool_name = get_lang('SurveyReporting');
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
Display::display_header($tool_name);

switch($screen) {
	
	// first screen : base
	case '1' : 
		
		echo '<h1>'.get_lang('SelectDataYouWantToCompare').'</h1>';
		echo get_lang('HelpComparative');
		$questions = SurveyManager::listQuestions($surveyid);
		
		$menu_left = '<select name="left[]" size="20" multiple style="width:500px">';
		$menu_left .= '
			<option value="name">'.get_lang('Name').'</option>
			<option value="email">'.get_lang('EmailAddress').'</option>
			<option value="organisation">'.get_lang('Organisation').'</option>
		';
		foreach ($questions as $key=>$question) {
			//if($question['qtype']!='Numbered' && $question['qtype']!='Open Answer')
				$menu_left .= '<option value="'.$question['qid'].'">Q'.($key+1).' - '.substr(stripslashes(strip_tags($question['caption'])),0,80).'...</option>';
		}
		$menu_left .= '</select>';
		
		/*
		$menu_right = '<select name="right[]" size="10" multiple style="width:250px">';
		$menu_right .= '
			<option value="name">'.get_lang('Name').'</option>
			<option value="email">'.get_lang('EmailAddress').'</option>
			<option value="organisation">'.get_lang('Organisation').'</option>
		';
		foreach ($questions as $key=>$question) {
			$menu_right .= '<option value="'.$question['qid'].'">Q'.($key+1).' - '.substr(stripslashes(strip_tags($question['caption'])),0,50).'...</option>';
		}
		$menu_right .= '</select>';
		*/
		echo '	<form method="POST">
				<table>
					<tr>
						<td>'.$menu_left.'</td>
					</tr>
					<tr>
						<td align="center">
							<input type="hidden" name="screen" value="2" />
							<input type="submit" value="'.get_lang('Ok').'"
						</td>
				</table>
				</form>';
		break;
		
	
	//second screen : details
	case '2' :
		
		echo '<h1>'.get_lang('PreciseWhatYouWantToCompare').'</h1>';
		
		$left_questions = $_POST['left'];
		$right_questions = $_POST['right'];
		
		
		// prepare the left menu
		$menu_left = '<select id="leftmenu" name="left[]" size="20" multiple style="width:500px">';
		
		$users = SurveyManager :: listUsers($surveyid, $db_name);
		
		$keyname = array_search('name', $left_questions);
		
		if(is_int($keyname)){
			
			$menu_left .= '<option value="name" disabled onclick="selectAllItems(\'name\')">'.get_lang('Name').'</option>';
			foreach($users as $user) {
				$menu_left .= '<option value="name|'.$user['lastname'].' '.$user['firstname'].'">---- '.$user['lastname'].' '.$user['firstname'].'</option>';
			}
			
			unset($left_questions[$keyname]);
			
		}
		
		$keyorg = array_search('organisation', $left_questions);
		if(is_int($keyorg)){
			
			$menu_left .= '<option value="organisation" disabled onclick="selectAllItems(\'organisation\')">'.get_lang('Organisation').'</option>';
			foreach($users as $user) {
				$menu_left .= '<option value="organisation|'.$user['organization'].'">---- '.$user['organization'].'</option>';
			}
			unset($left_questions[$keyorg]);
			
		}
		
		$keymail = array_search('email', $left_questions);
		if(is_int($keymail)){
			
			$menu_left .= '<option value="email" disabled onclick="selectAllItems(\'email\')">'.get_lang('EmailAddress').'</option>';
			foreach($users as $user) {
				$menu_left .= '<option value="email|'.$user['email'].'">---- '.$user['email'].'</option>';
			}
			unset($left_questions[$keymail]);
			
		}
			
		foreach($left_questions as $question){
			
			$question = SurveyManager::get_question_data($question, $db_name);
			
			$menu_left .= '<option value="question|'.$question->qid.'" onclick="selectAllAnswers(\''.$question->qid.'\')">Q'.($question->sortby+1).' - '.substr(stripslashes(strip_tags($question->caption)),0,50).'...</option>';
			
			if($question->qtype!='Open Answer' && $question->qtype!='Numbered'){
				foreach($question as $key=>$reponse) {
					if(substr($key,0,1)=='a' && $key!='alignment' && !empty($reponse)){
						$menu_left .= '<option value="answer|'.$question->qid.'|'.stripslashes(strip_tags($key)).'">---- '.substr(stripslashes(strip_tags($reponse)),0,50).'</option>';
					}
				}
			}
			
		}
		
		$menu_left .= '</select>';
		
		
		// prepare the right menu
		/*
		$menu_right = '<select name="right[]" size="10" multiple style="width:250px">';
		
		$keyname = array_search('name', $right_questions);
		
		if(is_int($keyname)){
			
			$menu_right .= '<option value="name" disabled>'.get_lang('Name').'</option>';
			foreach($users as $user) {
				$menu_right .= '<option value="name|'.$user['lastname'].' '.$user['firstname'].'">---- '.$user['lastname'].' '.$user['firstname'].'</option>';
			}
			
			unset($right_questions[$keyname]);
			
		}
		
		$keyorg = array_search('organisation', $right_questions);
		if(is_int($keyorg)){
			
			$menu_right .= '<option value="organisation" disabled>'.get_lang('Organisation').'</option>';
			foreach($users as $user) {
				$menu_right .= '<option value="organisation|'.$user['organization'].'">---- '.$user['organization'].'</option>';
			}
			unset($right_questions[$keyorg]);
			
		}
		
		$keymail = array_search('email', $right_questions);
		if(is_int($keymail)){
			
			$menu_right .= '<option value="email" disabled>'.get_lang('EmailAddress').'</option>';
			foreach($users as $user) {
				$menu_right .= '<option value="email|'.$user['email'].'">---- '.$user['email'].'</option>';
			}
			unset($right_questions[$keymail]);
			
		}
			
		foreach($right_questions as $question){
			
			$question = SurveyManager::get_question_data($question, $db_name);
			
			$menu_right .= '<option value="question|'.$question->qid.'" selected>Q'.($question->sortby+1).' - '.substr(stripslashes(strip_tags($question->caption)),0,50).'...</option>';
			
			/*
			foreach($question as $key=>$reponse) {
				if(substr($key,0,1)=='a' && !empty($reponse)){
					$menu_right .= '<option value="answer|'.$question->qid.'|'.stripslashes(strip_tags($reponse)).'">---- '.substr(stripslashes(strip_tags($reponse)),0,50).'</option>';
				}
			}
			/*
			$list_answers = SurveyManager::listAnswers($question->qid);
			foreach($list_answers as $answer){
				
			}
			
		}
		
		$menu_right .= '</select>';
		*/
		echo '	<form method="POST">
				<table>
					<tr>
						<td>'.$menu_left.'</td>
					</tr>
					<tr>
						<td align="center">
							<input type="hidden" name="screen" value="3" />
							<input type="submit" value="'.get_lang('Ok').'"
						</td>
				</table>
				</form>';
		
		break;
	
	
	// screen 3 : results
	case '3' :
		
		echo '<h1>'.get_lang('ComparativeResults').'</h1>';
		
		$params = array_merge($_POST['left'] , $_POST['right']);
		
		$tbl_user_survey = Database::get_main_table(TABLE_MAIN_SURVEY_USER);
		$tbl_questions = Database::get_course_table('questions');
		$tbl_answers = Database::get_course_table('survey_report');
		
		
		//$which_answers = 'WHERE qid';
		//$which_questions = 'AND qid IN ()';
		
		$names = $emails = $organisations = $questions = array();
		$excel_file_name = 'export_survey-'.$surveyid.$db_name.'.csv';
		echo '<a href="../course_info/download.php?archive='.$excel_file_name.'"><img border="0" src="../img/xls.gif" align="middle"/>'.get_lang('ExportInExcel').'</a>';
		echo '<table border="1" class="data_table"><td class="cell_header"></td>';
		
		$with_answers = false;
		$answers_required = $header_order = array();
		$i=0;
		$nbColumns = 0;
		foreach($params as $param){
			$param = explode('|',$param);
			switch ($param[0]){ // which type ?
				
				case 'name' : 
					$names[]='"'.$param[1].'"'; 
					break;
				case 'email' : 
					$emails[]='"'.$param[1].'"'; 
					break;
				case 'organisation' : 
					$organisations[]='"'.$param[1].'"'; 
					break;
				case 'question' : 					
					$questions[]='"'.$param[1].'"';
					$sql = 'SELECT caption FROM '.$tbl_questions.' WHERE qid='.$param[1];
					$rs = api_sql_query($sql);
					if($row = mysql_fetch_array($rs)){
						$row[0] = eregi_replace('^<p[^>]*>(.*)</p>','\\1', $row[0]);
						$row[0] = eregi_replace('(<[^ ]*) (style=."."[^>]*)(>)','\\1\\3', $row[0]);
						$row[0] = eregi_replace('(<[^ ]*) (style=.""[^>]*)(>)','\\1\\3', $row[0]);
						$row[0] = eregi_replace('(<[^ ]*)( style=."[^"]*")([^>]*)(>)','\\1\\2\\4', $row[0]);
							
						$html_questions .= '<td class="cell_header">'.stripslashes($row[0]).'</td>';
						$excel_questions .= stripslashes($row[0]).';';
					}
					$header_order[$param[1]] = $i;
					$i++;
					break;
				case 'answer' : 
					$answers_required[] = $param;
					$sql = 'SELECT caption FROM '.$tbl_questions.' as questions
							INNER JOIN '.$tbl_answers.' as answers
								ON answers.qid = questions.qid
								AND answers.qid='.$param[1];
					
					$rs = api_sql_query($sql);
					if($row = mysql_fetch_array($rs)){
						if(!in_array('"'.$param[1].'"',$questions)){
							$row[0] = eregi_replace('^<p[^>]*>(.*)</p>','\\1', $row[0]);
							$row[0] = eregi_replace('(<[^ ]*) (style=."."[^>]*)(>)','\\1\\3', $row[0]);
							$row[0] = eregi_replace('(<[^ ]*) (style=.""[^>]*)(>)','\\1\\3', $row[0]);
							$row[0] = eregi_replace('(<[^ ]*)( style=."[^"]*")([^>]*)(>)','\\1\\2\\4', $row[0]);
							
							$html_questions .= '<td class="cell_header">'.stripslashes($row[0]).'</td>';
							$excel_questions .= stripslashes($row[0]).';';							
							$questions[]='"'.$param[1].'"';							
							$header_order[$param[1]] = $i;
							$i++;
						}
					}
					
					
					break;
				
			}
			
			
		}
		
		$select = array();
		$excel = '';		
		if(count($names)){
			$nbColumns++;
			$select[] =  'CONCAT(lastname," ",firstname) as name';
			$which_names = ' AND CONCAT(lastname," ",firstname) IN ('.implode(',',$names).')';
			echo '<td class="cell_header">'.get_lang('Name').'</td>';
			$excel .= get_lang('Name').';';
		}
		if(count($emails)) {
			$nbColumns++;
			$select[] =  'email';
			$which_emails = ' AND email IN ('.implode(',',$emails).')';
			echo '<td class="cell_header">'.get_lang('EmailAddress').'</td>';
			$excel .= get_lang('EmailAddress').';';
		}
		if(count($organisations)) {
			$nbColumns++;
			$select[] =  'organization';
			$which_organisations = ' AND organization IN ('.implode(',',$organisations).')';
			echo '<td class="cell_header">'.get_lang('Organisation').'</td>';
			$excel .= get_lang('Organisation').';';
		}
		if(count($questions)){
			$nbColumns += count($questions);
			$select[] =  'questions.qid, caption, answer';
			$which_questions = ' AND questions.qid IN ('.implode(',',$questions).')';
			echo $html_questions;			
		}
		
		$excel .= $excel_questions;
		echo '</tr>';
		$excel .="\r\n";
		
		$sql = 'SELECT  user_survey.id as id_user_survey, '.implode(',',$select).'
				FROM '.$tbl_answers.' as answers,'.$tbl_user_survey.' as user_survey,'.$tbl_questions.' as questions
				WHERE answers.qid = questions.qid
				AND answers.user_id = user_survey.user_id 
				AND user_survey.survey_id='.$surveyid.'
				AND user_survey.db_name="'.$db_name.'" ';
		
		$sql .= $which_names.' '.$which_emails.' '.$which_organisations.' '.$which_questions;
		
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$answers_to_keep = $user_survey_done = array();
		while($row = mysql_fetch_array($rs)) {
			
			$ok = false;
			if(count($answers_required)==0){
				$ok=true;
			}
			else {
				foreach($answers_required as $param){
					$key = $param[1];
					$answer = $param[2];
					$sql = 'SELECT 1 FROM '.$tbl_answers.' as answers
							INNER JOIN '.$tbl_user_survey.' as user_survey
								ON user_survey.id = '.$row['id_user_survey'].'
								AND user_survey.survey_id='.$surveyid.'
								AND user_survey.db_name="'.$db_name.'"
							WHERE answers.qid='.$key.'
							AND answers.answer = "'.$answer.'"';
					
					$rs2 = api_sql_query($sql);
					if(mysql_num_rows($rs2)>0){
						$ok = true;
					}
				}
			}
			
			if($ok){
				$answers_to_keep[] = $row;
			}
		}
		
		
		$stats = array();
		$users_survey = array();
		foreach($answers_to_keep as $answer) {
			if(in_array($answer['answer'],array('a1','a2','a3','a4','a5','a6','a7','a8','a9','a10',))){
				$sql = 'SELECT '.$answer['answer'].' 
						FROM '.$db_name.'.questions 
						WHERE survey_id = '.$surveyid.'
						AND questions.qid='.$answer['qid'];
			
				$rsAnswer = api_sql_query($sql, __FILE__,__LINE__);
				$answer['answer'] = mysql_result($rsAnswer,0);
			}
			$i=0;
			$users_survey[$answer['id_user_survey']][0]['answer'] = $answer['id_user_survey'];
			$i++;
			
			if(count($names)){
				$users_survey[$answer['id_user_survey']][$i]['answer'] = $answer['name'];
				$i++;
			}
			if(count($emails)){
				$users_survey[$answer['id_user_survey']][$i]['answer'] = $answer['email'];
				$i++;
			}
			if(count($organisations)){
				$users_survey[$answer['id_user_survey']][$i]['answer'] = $answer['organization'];
				$i++;
			}
			if(count($questions)){
				$users_survey[$answer['id_user_survey']][$i+$header_order[$answer['qid']]]['answer'] = $answer['answer'];
				$stats[$i+$header_order[$answer['qid']]][] = $answer['answer'];
			}			
		}
		
		foreach($users_survey as $user_survey){
			echo '<tr>';
			for($i=0; $i<=$nbColumns; $i++){
				
				echo '<td>'.(!empty($user_survey[$i]['answer']) ? $user_survey[$i]['answer'] : '-').'</td>';
				if($i!=0)
					$excel .= str_replace(array("\r","\n"),"",$user_survey[$i]['answer'].';');
			}
			$excel .= "\r\n";
			echo '</tr>';
		}
		
		$max = max(array_keys($stats));
		
		echo '<tr style="background-color:yellow">';
		echo '<td>Stats</td>';
		for($i=1 ; $i<$max+1; $i++){
			echo '<td>';
			$valeurs = array_count_values($stats[$i]);
			foreach($valeurs as $key=>$value){
				if(!empty($key))
					echo $key.' : '.$value.' votes<br />';
			}
			echo '</td>';
		}
		echo '</tr>';
		
		echo '</table>';
		
		
		$archivePath=api_get_path(SYS_PATH).$archiveDirName.'/';
		$handle = fopen($archivePath.$excel_file_name, 'w');
		fwrite($handle, $excel);
		fclose($handle);
		chmod($archivePath.$excel_file_name, 0755);
		
		
		break;
}


?>
<script type="text/javascript">
function selectAllItems(type){
	myselect = document.getElementById('leftmenu');
	for(i=0 ; i<myselect.options.length ; i++){
		if(myselect.options[i].value.indexOf(type)!=-1){
			myselect.options[i].selected = true;
		}
	}	
}
function selectAllAnswers(qid){
	myselect = document.getElementById('leftmenu');
	for(i=0 ; i<myselect.options.length ; i++){
		if(myselect.options[i].value.indexOf('|'+qid+'|')!=-1){
			myselect.options[i].selected = true;
		}
	}	
}
</script>
