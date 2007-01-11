<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html
   
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.
 
    Contact: 
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author 
* 	@version $Id: surveytemp_white.php 10680 2007-01-11 21:26:23Z pcool $
*/

// name of the language file that needs to be included 
$language_file='survey_answer';
$cidReset=true;
session_start();
$lang = $_REQUEST['lang'];
$_SESSION["user_language_choice"]=$lang;
// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");


if(!isset($_SESSION['page'])){
	$_SESSION['page'] = 0;
}
else if($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']!=$_SERVER['HTTP_REFERER']){
	$_SESSION['page']++;
}
if(isset($_GET['page'])){
	$_SESSION['page'] = $_GET['page'];
}
$page = $_SESSION['page'];
if(!isset($_SESSION['page'.$page])){
	$_SESSION['page'.$page] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
}


$nbQuestionsPerPage = 5;

$db_name = stripslashes($_REQUEST['db_name']);
$surveyid = intval($_REQUEST['surveyid']);
$temp = stripslashes($_REQUEST['temp']);
$uid1 = intval($_REQUEST['uid1']);
$mail = stripslashes($_REQUEST['mail']);

$group_offset = $group_offset_dist = empty($_REQUEST['group_offset']) ? 0 : intval($_REQUEST['group_offset']);
$question_offset = $question_offset_dist = empty($_REQUEST['question_offset']) ? 0 : intval($_REQUEST['question_offset']);
$indiceQuestion = $indiceQuestionDist = empty($_REQUEST['indiceQuestion']) ? 1 : intval($_REQUEST['indiceQuestion']);


$sql = 'select * from '.$db_name.'.survey where survey_id='.$surveyid;

$rs = api_sql_query($sql,__FILE__,__LINE__);
$o_survey = mysql_fetch_object($rs);


if(isset($_POST['saveandexit']) || isset($_POST['next']) || isset($_POST['finish']) || isset($_POST['printall'])){	
	if(is_array($_POST['q'])){
		foreach($_POST['q'] as $question => $answer){
			if(is_array($answer)){
				$answer = implode(',',$answer);
			}
			$sql = 'SELECT 1
					FROM '.$db_name.'.survey_report 
					WHERE user_id='.$uid1.' 
					AND survey_id='.$surveyid.'
					AND qid='.intval($question);
			
        	$rsAnswers = api_sql_query($sql,__FILE__,__LINE__);
        	
        	// if the answer soon exists we update it, else we insert it
        	if(mysql_num_rows($rsAnswers)>0){
        		$sql = 'UPDATE '.$db_name.'.survey_report 
						SET answer="'.addslashes($answer).'" 
						WHERE qid='.intval($question).'
						AND user_id='.$uid1.' 
						AND survey_id='.$surveyid;
        	}
        	else {
        		$sql = 'INSERT INTO '.$db_name.'.survey_report
						(id, qid, answer, survey_id, user_id) VALUES
						("",'.intval($question).',"'.addslashes($answer).'", '.$surveyid.', '.$uid1.')';
        	}
        	$rs=api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	
	if(isset($_POST['saveandexit'])){
		header("Location:thanks1.php?temp=$temp&surveyid=$surveyid&uid1=$uid1&db_name=$db_name&mail=$mail&lang=$lang");
		exit;
	}
	
	if(isset($_POST['finish']) || isset($_POST['printall'])){
		$survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
		$sql = 'UPDATE '.$survey_user_info_table.' SET 
				attempted="yes" 
				WHERE survey_id='.$surveyid.' 
				AND db_name = "'.$db_name.'" 
				AND email = "'.$mail.'"';
		
		$rs=api_sql_query($sql,__FILE__,__LINE__);
		if(isset($_POST['finish'])){
			header("Location:thanks1.php?temp=$temp&surveyid=$surveyid&uid1=$uid1&db_name=$db_name&mail=$mail&lang=$lang");
			exit;
		}
	}
	
}







$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
$tool_name = $o_survey->title;
Display::display_header($tool_name);
api_display_tool_title("Survey Name : ".$o_survey->title);
ob_start();
// submit the answers



// select the groups

if(!isset($_POST['printall'])){
	$sql = 'SELECT DISTINCT survey_group.* 
			FROM '.$db_name.'.survey_group 		
			INNER JOIN '.$db_name.'.questions
			ON  survey_group.group_id = questions.gid
			AND questions.survey_id = survey_group.survey_id
			WHERE survey_group.survey_id='.$surveyid.'
			ORDER BY sortby ASC LIMIT '.$group_offset.',1';
}
else {
	$indiceQuestion = 1;
	$sql = 'SELECT DISTINCT survey_group.* 
			FROM '.$db_name.'.survey_group 		
			INNER JOIN '.$db_name.'.questions
			ON  survey_group.group_id = questions.gid
			AND questions.survey_id = survey_group.survey_id
			WHERE survey_group.survey_id='.$surveyid.'
			ORDER BY sortby ASC LIMIT 0,999';
}

$rsGroups = api_sql_query($sql);

$sql = 'SELECT COUNT(DISTINCT survey_group.group_id) 
		FROM '.$db_name.'.survey_group 
		INNER JOIN '.$db_name.'.questions
		ON  survey_group.group_id = questions.gid
		AND questions.survey_id = survey_group.survey_id
		WHERE survey_group.survey_id='.$surveyid;

$rscount = api_sql_query($sql, __FILE__, __LINE__);
list($nbGroups) = mysql_fetch_array($rscount);


if(isset($_POST['printall'])){
	//display the name of the guy
	$sql = 'SELECT lastname, firstname FROM '.Database::get_main_table(TABLE_MAIN_SURVEY_USER).' WHERE user_id='.$uid1;
	
	$rsname = api_sql_query($sql, __FILE__, __LINE__);
	$user = api_store_result($rsname);
	echo '<center><strong>'.$user[0]['firstname'].' '.$user[0]['lastname'].'</strong></center><br /><br />';
}

echo '<table width="600" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">
	<tr><td>';
while($o_group = mysql_fetch_object($rsGroups) ){
	
	$intro = strip_tags($o_group->introduction);
	if($o_group->groupname!='No Group' && !empty($intro))
	{
		echo "<tr><td><br><table cellpadding='2' border='0' style='border: 1px solid'><tr><td align='center'>";
		echo $o_group->introduction;
		echo "</td></tr></table><br><br>";
	}
	
	$sql = 'SELECT * 
			FROM '.$db_name.'.questions 
			WHERE survey_id='.$surveyid.' 
			AND gid='.intval($o_group->group_id).'
			ORDER BY sortby
			LIMIT '.$question_offset.',999';
	
	$rsQuestions = api_sql_query($sql);
	$nbQuestions = mysql_num_rows($rsQuestions);
	$iQuestions = 0;
	while($o_question = mysql_fetch_object($rsQuestions)){
		
		
		if(($iQuestions<$nbQuestionsPerPage || $o_group->groupname!='No Group' )|| isset($_POST['printall'])){
			
			// select the previous answer the user did
			$sql = '	SELECT answer 
						FROM '.$db_name.'.survey_report 
						WHERE user_id='.$uid1.' AND survey_id='.$surveyid.' AND qid='.$o_question->qid;
	
			$rsAttempt = api_sql_query($sql, __FILE__, __LINE__);
			list($answer) = mysql_fetch_array($rsAttempt);
			
			
			$o_question->caption = eregi_replace('^<p[^>]*>(.*)</p>','\\1', $o_question->caption);
			$o_question->caption = eregi_replace('(<[^ ]*) (style=."."[^>]*)(>)','\\1\\3', $o_question->caption);
			$o_question->caption = eregi_replace('(<[^ ]*) (style=.""[^>]*)(>)','\\1\\3', $o_question->caption);
			$o_question->caption = eregi_replace('(<[^ ]*)( style=."[^"]*")([^>]*)(>)','\\1\\2\\4', $o_question->caption);
		
			
			echo '<table><tr><td valign="top">'.$indiceQuestion.'- </td><td valign="top">'.stripslashes($o_question->caption).'</td></tr></table>';
			
			$sel1 = $sel2 = "";
			switch ($o_question -> qtype) {
				
				case 'Yes/No' :
					if($answer=='a1'){
						$sel1="checked";
					}
					else if($answer=='a2'){
						$sel2="checked";
					}
					echo "&nbsp;<input type=radio value='a1' $sel1 name='q[".$o_question->qid."]'>".$o_question->a1."
						  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio value='a2' $sel2 name='q[".$o_question->qid."]'>".$o_question->a2."<BR><BR>";
				break;	
				
				
				
				
				case 'Open Answer' :
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<textarea cols='50' rows='6' name='q[".$o_question->qid."]'>".stripslashes($o_question->a1).
						"$answer</textarea><br><br>";		
				break;
				
				
				
				case 'Multiple Choice (multiple answer)' :
					$answer = explode(',',$answer);
					
					$break = '';
					if($o_question->alignment=='vertical')
					{
						$break= "<tr>";
					}					
					echo '<table cellpadding="4" cellspacing="0" align="left"><tr>';
					for($i = 1 ; $i <=10 ; $i++) {
						$current = 'a'.$i;
						$checked = '';
						if(in_array($current,$answer)){
							$checked = 'checked';
						}
						if(!empty($o_question->$current)){
							echo '<td><input value="'.$current.'" '.$checked.' type="checkbox" name="q['.$o_question->qid.'][]"></td><td align="left">'.stripslashes($o_question->$current).'</td>'.$break;
						}
					}
					echo "</tr></table><br><br>";	
				break;
				
				
				case 'Multiple Choice (single answer)' :
					
					$break = '';
					if($o_question->alignment=='vertical')
					{
						$break= "<tr>";
					}
					echo '<table cellpadding="4" cellspacing="0" align="left"><tr>';
					for($i = 1 ; $i <=10 ; $i++) {
						$current = 'a'.$i;
						$checked = '';
						if($current == $answer){
							$checked = 'checked';
						}
						if(!empty($o_question->$current)){
							echo '<td align="left"><input value="'.$current.'" '.$checked.' type="radio" name="q['.$o_question->qid.']"></td><td align="left">'.stripslashes($o_question->$current).'</td>'.$break;
						}
					}
					echo "</tr></table><br><br>";
				
				
				break;
				
				
			
				case 'Numbered' :
				
					$answer = explode(',',$answer);
					echo '<table align="center">';
					for($i = 1 ; $i <=10 ; $i++) {
						$current = 'a'.$i;
						if(!empty($o_question->$current)){
							echo '<tr><td valign="top"><select name="q['.$o_question->qid.'][]">';							
							for($j=0;$j<=10;$j++){
								$selected="";
								if($answer[$i-1]==$j){
									$selected="selected";
								}
								echo "<option value=$j $selected>".stripslashes($j)."</option>";
							}
							echo '</select><td valign="top">&nbsp;&nbsp;&nbsp;&nbsp;'.$o_question->$current.'<br><br></tr></tr>';

						}
					}
					echo '</table>';
				break;
			}
			
			
			$iQuestions++;
			$indiceQuestion++;
		}
		
	}
	if($o_group->groupname!='No Group' || $nbQuestions <= $iQuestions){
		$group_offset++;
		$question_offset=0;
	}
	else {
		$question_offset += $iQuestions;
	}
}


echo '</td></tr></table><br /><br />';


$content = ob_get_contents();
ob_end_clean();
$content = '<form method="post" action="'.$_SERVER['PHP_SELF'].'?surveyid='.$surveyid.'&temp='.$temp.'&uid1='.$uid1.'&mail='.$mail.'&group_offset='.$group_offset.'&question_offset='.$question_offset.'&indiceQuestion='.$indiceQuestion.'&&lang='.$lang.'">'.$content;
echo $content;
?>

			  <table width="100%"  border="0" cellpadding="0" cellspacing="0">

				  <tr>
				 
					<td align="center">
					<?php
					if(!isset($_POST['printall'])){
						if($_SESSION['page']==0)
							echo "<input type=\"button\" name=\"Back\" value=\"".get_lang('Back')."\" onclick=\"document.location='welcome_1.php?surveyid=".$surveyid.'&db_name='.$db_name.'&temp='.$temp.'&uid1='.$uid1.'&mail='.$mail."';\">";
						else {
							echo "<input type=\"button\" name=\"Back\" value=\"".get_lang('Back')."\" onclick=\"document.location='".$_SESSION['page'.($_SESSION['page']-1)]."&page=".($_SESSION['page']-1)."';\">";
						}
						
						if($group_offset < $nbGroups){	
							echo '<input type="submit" name="saveandexit" value="'.get_lang('SaveAndExit').'" onclick="return confirm(\''.get_lang('AreYouSure').'\')">';					
							echo '<input type="submit" name="next" value="'.get_lang('Next').'">&nbsp;';						
						}
						else {
							echo '<input type="submit" name="printall" value="'.get_lang('PrintAll').'">';
							echo '<input type="submit" name="finish" value="'.get_lang('Next').'" onclick="return confirm(\''.get_lang('AreYouSure').'\')">';
						}
					}
					else {
						echo '<script type="text/javascript">window.print()</script>';
						echo '<input type="submit" name="finish" value="'.get_lang('Finish').'" onclick="return confirm(\''.get_lang('AreYouSure').'\')">';
					}
					?>
					
				  </tr>
		</table>
	  </form>
	  
	  
<?php

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>