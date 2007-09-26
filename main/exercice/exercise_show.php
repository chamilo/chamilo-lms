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
*
*	@package dokeos.exercise
* 	@author
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*
* 	@todo remove the debug code and use the general debug library
* 	@todo use the Database:: functions
* 	@todo small letters for table variables
*/

// name of the language file that needs to be included
$language_file=array('exercice','tracking');

// including the global dokeos file
include('../inc/global.inc.php');

// including additional libraries
include_once('exercise.class.php');
include_once('question.class.php');
include_once('answer.class.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// answer types
define('UNIQUE_ANSWER',	1);
define('MULTIPLE_ANSWER',	2);
define('FILL_IN_BLANKS',	3);
define('MATCHING',		4);
define('FREE_ANSWER', 5);
define('HOTSPOT', 6);

api_protect_course_script();

// Database table definitions
$TBL_EXERCICE_QUESTION 	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          	= Database::get_course_table(TABLE_QUIZ_ANSWER);
$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

$dsp_percent = false;
$debug=0;
if($debug>0)
{
	echo str_repeat('&nbsp;',0).'Entered exercise_result.php'."<br />\n";var_dump($_POST);
}
// general parameters passed via POST/GET
if ( empty ( $origin ) )
{
    $origin = $_REQUEST['origin'];
}
if ( empty ( $learnpath_id ) ) {
    $learnpath_id       = mysql_real_escape_string($_REQUEST['learnpath_id']);
}
if ( empty ( $learnpath_item_id ) ) {
    $learnpath_item_id  = mysql_real_escape_string($_REQUEST['learnpath_item_id']);
}
if ( empty ( $formSent ) ) {
    $formSent= $_REQUEST['formSent'];
}
if ( empty ( $exerciseResult ) ) {
    $exerciseResult = $_SESSION['exerciseResult'];
}
if ( empty ( $questionId ) ) {
    $questionId = $_REQUEST['questionId'];
}
if ( empty ( $choice ) ) {
    $choice = $_REQUEST['choice'];
}
if ( empty ( $questionNum ) ) {
    $questionNum    = mysql_real_escape_string($_REQUEST['questionNum']);
}
if ( empty ( $nbrQuestions ) ) {
    $nbrQuestions   = mysql_real_escape_string($_REQUEST['nbrQuestions']);
}
if ( empty ( $questionList ) ) {
    $questionList = $_SESSION['questionList'];
}
if ( empty ( $objExercise ) ) {
    $objExercise = $_SESSION['objExercise'];
}
$is_allowedToEdit=api_is_allowed_to_edit() || $is_courseTutor;
$nameTools=get_lang('CorrectTest');

if($origin=='user_course')
{
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$_GET['course'], "name" => get_lang("Users"));
	$interbreadcrumb[] = array("url" => "../mySpace/myStudents.php?student=".$_GET['student']."&course=".$_course['id']."&details=true&origin=".$_GET['origin'] , "name" => get_lang("DetailsStudentInCourse"));
}
else if($origin=='tracking_course')
{
	$interbreadcrumb[] = array ("url" => "../mySpace/index.php", "name" => get_lang('MySpace'));
 	$interbreadcrumb[] = array ("url" => "../mySpace/myStudents.php?student=".$_GET['student'].'&details=true&origin='.$origin.'&course='.$_GET['cidReq'], "name" => get_lang("DetailsStudentInCourse"));
}
else if($origin=='student_progress')
{
	$interbreadcrumb[] = array ("url" => "../auth/my_progress.php?id_session".$_GET['id_session']."&course=".$_cid, "name" => get_lang('MyProgress'));
	unset($_cid);
}
else {
	$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));
	$this_section=SECTION_COURSES;
}

Display::display_header($nameTools,"Exercise");

$emailId = $_REQUEST['email'];
$user_name = $_REQUEST['user'];
$test 	   = $_REQUEST['test'];
$dt	 	   = $_REQUEST['dt'];
$marks 	   = $_REQUEST['res'];
$id 	   = $_REQUEST['id'];
?>
<style type="text/css">
<!--
#comments {
	position:absolute;
	left:795px;
	top:0px;
	width:200px;
	height:75px;
	z-index:1;
}

-->
</style>
<script language="javascript">
function showfck(sid,marksid)
{
document.getElementById(sid).style.visibility='visible';
document.getElementById(marksid).style.visibility='visible';
}

function getFCK(vals,marksid){
  var f=document.getElementById('myform');

  var m_id = marksid.split(',');
  for(var i=0;i<m_id.length;i++){
  var oHidn = document.createElement("input");
			oHidn.type = "hidden";
			var selname = oHidn.name = "marks_"+m_id[i];
			var selid = document.forms['marksform_'+m_id[i]].marks.selectedIndex;
			oHidn.value = document.forms['marksform_'+m_id[i]].marks.options[selid].text;
			f.appendChild(oHidn);
	}

	var ids = vals.split(',');
	for(var k=0;k<ids.length;k++){
			var oHidden = document.createElement("input");
			oHidden.type = "hidden";
			oHidden.name = "comments_"+ids[k];
			oEditor = FCKeditorAPI.GetInstance(oHidden.name) ;
			oHidden.value = oEditor.GetXHTML(true);
			f.appendChild(oHidden);
	}
//f.submit();
}
</script>
<?php

//functions
function get_comments($id,$question_id)
	{
	global $TBL_TRACK_ATTEMPT;
	$sql = "select teacher_comment from ".$TBL_TRACK_ATTEMPT." where exe_id=$id and question_id = '$question_id' order by question_id";
	$sqlres = api_sql_query($sql, __FILE__, __LINE__);
	$comm = mysql_result($sqlres,0,"teacher_comment");
	return $comm;
	}
function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$ans)
{
	?>
	<tr valign="top">
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $studentChoice?'_on':'_off'; ?>.gif"
		border="0" alt="" />
	</td>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $answerCorrect?'_on':'_off'; ?>.gif"
		border="0" alt=" " />
	</td>
	<td width="45%">
		<?php
		$answer=api_parse_tex($answer);
		echo $answer; ?><hr  style="border-top: 0.5px solid #4171B5;">
	</td>
	<?php if($ans==1)
	{$comm = get_comments($id,$questionId);
	//echo $comm;
	}?>
	</tr>
	<?php
}
function display_fill_in_blanks_answer($answer,$id,$questionId)
{

	?>
		<tr>
		<td>
			<?php echo nl2br($answer); ?>
		</td><?php
		if(!$is_allowedToEdit) {?>
		<td>
		<?php
		$comm = get_comments($id,$questionId);
		//echo $comm;
		?>
		</td>
		</tr>
	<?php }
}

function display_free_answer($answer,$id,$questionId)
{
	?>
		<tr>
		<td>
			<?php echo nl2br(stripslashes($answer)); ?>
		</td> <?php if(!$is_allowedToEdit) {?>
   <td>
    <?php

	$comm = get_comments($id,$questionId);
	/*if ($comm!='')
	echo $comm;
	else
	echo  get_lang('notCorrectedYet');
	*/?>

   </td> <?php }?>
		</tr>
	<?php
}

function display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment)
{
	//global $hotspot_colors;
	$hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
            						"#4271B5",
									"#FE8E16",
									"#3B3B3B",
									"#BCD631",
									"#D63173",
									"#D7D7D7",
									"#90AFDD",
									"#AF8640",
									"#4F9242",
									"#F4EB24",
									"#ED2024",
									"#45C7F0",
									"#F7BDE2");
	?>
		<tr>
				<td width="50%" valign="top">
					<div style="width:100%;">
						<div style="height:11px; width:11px; background-color:<?php echo $hotspot_colors[$answerId]; ?>; float:left; margin:3px;"></div>
						<div><?php echo $answer ?></div>
					</div>
				</td>
				<td width="25%" valign="top"><?php echo $answerId; ?></td>
				<td width="25%" valign="top">
					<?php $studentChoice = ($studentChoice)?get_lang('Correct'):get_lang('Fault'); echo $studentChoice; ?>
				</td>
		</tr>
	<?php
}


/*
==============================================================================
		MAIN CODE
==============================================================================
*/

?>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
  <tr>
    <td colspan="2">
	<?php
		$sql_test_name='SELECT title, description FROM '.$TBL_EXERCICES.' as exercises, '.$TBL_TRACK_EXERCICES.' as track_exercises WHERE exercises.id=track_exercises.exe_exo_id AND track_exercises.exe_id="'.$id.'"';
		$result=api_sql_query($sql_test_name);
		$test=mysql_result($result,0,0);
		$exerciseTitle=api_parse_tex($test);
		$exerciseDexcription=mysql_result($result,0,1);

$user_restriction = $is_allowedToEdit ? '' :  "AND user_id=".intval($_user['user_id'])." ";
$query = "select * from ".$TBL_TRACK_ATTEMPT." as attempts  
						INNER JOIN ".$TBL_TRACK_EXERCICES." as stats_exercices ON stats_exercices.exe_id=attempts.exe_id 
						INNER JOIN ".$TBL_EXERCICE_QUESTION." as quizz_rel_questions ON quizz_rel_questions.exercice_id=stats_exercices.exe_exo_id AND quizz_rel_questions.question_id = attempts.question_id
						INNER JOIN ".$TBL_QUESTIONS." as questions ON questions.id=quizz_rel_questions.question_id    
					WHERE attempts.exe_id='$id' $user_restriction
					GROUP BY questions.position, attempts.question_id";

$result =api_sql_query($query, __FILE__, __LINE__);
?>
	<h3><?php echo stripslashes($test)?> : <?php echo get_lang("Result"); ?></h3>
	<?php echo $exerciseDexcription.'<br>'; ?>
	 </td>
  </tr>
  <?php


	$i=$totalScore=$totalWeighting=0;
 if($debug>0){echo "ExerciseResult: "; var_dump($exerciseResult); echo "QuestionList: ";var_dump($questionList);}

	// for each question
	$questionList = array();
	$exerciseResult = array();
	$k=0;
	$counter=0;
	while ($row = mysql_fetch_array($result))
			{
			$questionList[] = $row['question_id'];
			$exerciseResult[] = $row['answer'];
			}
		foreach($questionList as $questionId)
			{
				$counter++;
				$k++;
				$choice=$exerciseResult[$questionId];
				// creates a temporary Question object
				$objQuestionTmp = Question::read($questionId);
				$questionName=$objQuestionTmp->selectTitle();
				$questionDescription=$objQuestionTmp->selectDescription();
				$questionWeighting=$objQuestionTmp->selectWeighting();
				$answerType=$objQuestionTmp->selectType();
				$quesId =$objQuestionTmp->selectId(); //added by priya saini

				// destruction of the Question object
					unset($objQuestionTmp);



				if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
				{
					$colspan=2;
				}
				if($answerType == MATCHING || $answerType == FREE_ANSWER)
				{
					$colspan=2;
				}
				else
				{
					$colspan=2;
				}?>

  <tr bgcolor="#E6E6E6">
    <td colspan="2" > <?php echo get_lang("Question").' '.($counter); ?> </td>
  </tr>
  <tr>
    <td colspan="2"><?php echo $questionName; ?> </td>
  </tr>
   <tr>
    <td colspan="2"><?php echo $questionDescription; ?> </td>
  </tr>
  <tr>
  <td width="200" height="90" valign="top">
  <?php
		if($answerType == MULTIPLE_ANSWER)
		{
			$choice=array();
			?>
			<table width="355" border="0" cellspacing="3" cellpadding="3">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i><?php echo get_lang("Choice"); ?></i> </td>
			<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
			<td><i><?php echo get_lang("Answer"); ?></i></td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>
			<?php
			// construction of the Answer object
				$objAnswerTmp=new Answer($questionId);
				$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
				$questionScore=0;
				for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
					{
						$answer=$objAnswerTmp->selectAnswer($answerId);
						$answerComment=$objAnswerTmp->selectComment($answerId);
						$answerCorrect=$objAnswerTmp->isCorrect($answerId);
						$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
						$queryans = "select * from ".$TBL_TRACK_ATTEMPT." where exe_id = $id and question_id= $questionId";
						$resultans = api_sql_query($queryans, __FILE__, __LINE__);
						while ($row = mysql_fetch_array($resultans))
								{
								$ind = $row['answer'];
								$choice[$ind] = 1;
								}
						$studentChoice=$choice[$answerId];
						if($studentChoice)
							{
							$questionScore+=$answerWeighting;
							$totalScore+=$answerWeighting;
							}

				?>
			<tr>
			<td> <?php
			if($answerId==1)
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
			else
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				?>
			</td>
			</tr>
			<?php
		$i++;
		 }?>
			</table>
	<?php }
		else if ($answerType == UNIQUE_ANSWER)
		{?>
		<table width="355" border="0">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i><?php echo get_lang("Choice"); ?></i> </td>
			<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
			<td><i><?php echo get_lang("Answer"); ?></i></td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>
			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
				{
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select answer from ".$TBL_TRACK_ATTEMPT." where exe_id = $id and question_id= $questionId";
				$resultans = api_sql_query($queryans, __FILE__, __LINE__);
				$choice = mysql_result($resultans,0,"answer");
				$studentChoice=($choice == $answerId)?1:0;
				if($studentChoice)
					{
				  	$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
					}?>

			<tr>
			<td><?php if($answerId==1)
				display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				else
				display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				 ?>
			</td>
			</tr><?php
		$i++;


		}?>
			</table>
	<?php  }
		elseif($answerType == FILL_IN_BLANKS)
		{?>
			<table width="355" border="0">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i><?php echo get_lang("Answer"); ?></i> </td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>
			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
				{
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				list($answer,$answerWeighting)=explode('::',$answer);
				// splits weightings that are joined with a comma
				$answerWeighting=explode(',',$answerWeighting);
				// we save the answer because it will be modified
			    $temp=$answer;
				// TeX parsing
				// 1. find everything between the [tex] and [/tex] tags
				$startlocations=strpos($temp,'[tex]');
				$endlocations=strpos($temp,'[/tex]');
				if($startlocations !== false && $endlocations !== false)
					{
					$texstring=substr($temp,$startlocations,$endlocations-$startlocations+6);
					// 2. replace this by {texcode}
					$temp=str_replace($texstring,'{texcode}',$temp);
					}
				$j=0;
				// the loop will stop at the end of the text
				$i=0;
				while(1)
					{
					// quits the loop if there are no more blanks
					if(($pos = strpos($temp,'[')) === false)
						{
						// adds the end of the text
						 $answer.=$temp;
						// TeX parsing
						$texstring = api_parse_tex($texstring);
						break;
						}
				    $temp=substr($temp,$pos+1);
				// quits the loop if there are no more blanks
					if(($pos = strpos($temp,']')) === false)
						{
							break;
						}
					$queryfill = "select answer from ".$TBL_TRACK_ATTEMPT." where exe_id = $id and question_id= $questionId";
					$resfill = api_sql_query($queryfill, __FILE__, __LINE__);
					$str=mysql_result($resfill,0,"answer");
					preg_match_all ('#\[([^[/]*)/#', $str, $arr);
					$choice = $arr[1];
					$choice[$j]=trim($choice[$j]);
				// if the word entered by the student IS the same as the one defined by the professor
					if(strtolower(substr($temp,0,$pos)) == stripslashes(strtolower($choice[$j])))
						{
					// gives the related weighting to the student
						$questionScore+=$answerWeighting[$j];
						// increments total score
						$totalScore+=$answerWeighting[$j];
						}
					// else if the word entered by the student IS NOT the same as the one defined by the professor
					$j++;
					$temp=substr($temp,$pos+1);
					$i=$i+1;
				} $answer = $str;
?>
			<tr>
			<td> <?php display_fill_in_blanks_answer($answer,$id,$questionId); ?> </td>
			</tr><?php
		$i++;


		}?>
			</table>
	<?php  }
		elseif($answerType == FREE_ANSWER)
		{?>
			<table width="355" border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i><?php echo get_lang("Answer"); ?></i> </td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>

			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			$query = "select answer, marks from ".$TBL_TRACK_ATTEMPT." where exe_id = $id and question_id= $questionId";
			$resq=api_sql_query($query);
			$choice = mysql_result($resq,0,"answer");
			$questionScore = mysql_result($resq,0,"marks");
			if($questionScore==-1){
				$totalScore+=0;
			}
			else{
				$totalScore+=$questionScore;
			}
			?>
			<tr>
			<td valign="top"><?php display_free_answer($choice, $id, $questionId);?> </td>
			</tr>
			</table>
	<?php  }
	
	elseif($answerType == MATCHING)
	{

		$objAnswerTmp=new Answer($questionId);
		
		$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
		$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
		
		$sql_select_answer = 'SELECT id, answer, correct, position FROM '.$table_ans.' WHERE question_id="'.$questionId.'" AND correct<>0';
		$res_answers = api_sql_query($sql_select_answer, __FILE__, __LINE__);
		
		echo '<table width="355" height="71" border="0">';
		echo '<tr><td colspan="2">&nbsp;</td></tr>';
		echo '<tr>
				<td><span style="font-style: italic;">'.get_lang("ElementList").'</span> </td>
				<td><span style="font-style: italic;">'.get_lang("CorrespondsTo").'</span></td>
			  </tr>';
		echo '<tr><td colspan="2">&nbsp;</td></tr>';
		
		$questionScore=0;
		
		while($a_answers = mysql_fetch_array($res_answers)){
			
			$i_answer_id = $a_answers['id'];
			$s_answer_label = $a_answers['answer'];
			$i_answer_correct_answer = $a_answers['correct'];
			$i_answer_position = $a_answers['position'];
			
			$sql_user_answer = 'SELECT answers.answer 
								FROM '.$TBL_TRACK_ATTEMPT.' as track_e_attempt, '.$table_ans.' as answers 
								WHERE track_e_attempt.answer=answers.position 
								AND track_e_attempt.position="'.$i_answer_position.'" 
								AND answers.question_id ="'.$questionId.'" 
								AND exe_id = "'.$id.'"';

			$res_user_answer = api_sql_query($sql_user_answer, __FILE__, __LINE__);
			$s_user_answer = mysql_result($res_user_answer,0,0);
			
			$sql_correct_answer = 'SELECT answer FROM '.$table_ans.' WHERE position = "'.$i_answer_correct_answer.'" AND question_id="'.$questionId.'"';
			$res_correct_answer = api_sql_query($sql_correct_answer, __FILE__, __LINE__);
			$s_correct_answer = mysql_result($res_correct_answer,0,0);
			
			$i_answerWeighting=$objAnswerTmp->selectWeighting($i_answer_id);
			
			if($s_user_answer == $s_correct_answer){
				$questionScore+=$i_answerWeighting;
				$totalScore+=$i_answerWeighting;
			}
			else{
				$s_user_answer = '<span style="color: #FF0000; text-decoration: line-through;">'.$s_user_answer.'</span>';
			}
			
			echo '<tr>';
			echo '<td>'.$s_answer_label.'</td><td>'.$s_user_answer.' / <span style="color: #008000;">'.$s_correct_answer.'</span></td>';
			echo '</tr>';
			
		}
		
		echo '</table>';
	
	}
	else if($answerType == HOTSPOT){


		?>

		<table width="355" border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i><?php echo get_lang("Answer"); ?></i> </td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>

			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			?>
			<tr>
				<td valign="top" align="left">
					<table style="border: 1px solid #4271b5;border-bottom:none" width="730">
					<?php 
					for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
					{
						$answer=$objAnswerTmp->selectAnswer($answerId);
						$answerComment=$objAnswerTmp->selectComment($answerId);
						$answerCorrect=$objAnswerTmp->isCorrect($answerId);
						$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
						
						$TBL_TRACK_HOTSPOT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
						$query = "select hotspot_correct from ".$TBL_TRACK_HOTSPOT." where hotspot_exe_id = $id and hotspot_question_id= $questionId AND hotspot_answer_id=$answerId";
						$resq=api_sql_query($query);
						$choice = mysql_result($resq,0,"correct");
						display_hotspot_answer($answerId,$answer,$choice,$answerComment);
		
						$i++;
				 	}
				 	$queryfree = "select marks from ".$TBL_TRACK_ATTEMPT." where exe_id = $id and question_id= $questionId";
					$resfree = api_sql_query($queryfree, __FILE__, __LINE__);
					$questionScore= mysql_result($resfree,0,"marks");
					$totalScore+=$questionScore;
		 			?>
		 			</table>
		 		</td></tr>
		 		<?php
		 		
		 	echo '
			<tr>
				<td colspan="2">
					<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'" width="730" height="570">
						<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'" />
					</object>
				</td>
			</tr>
			</table>';

	}
	?>


		 </td>
		<td width="346" valign = "top"><i>
		<?php echo get_lang("Comment"); ?></i>
			 <?php if($is_allowedToEdit)
					 		{
							//if (isset($_REQUEST['showdiv']) && $questionId==$_REQUEST['ques_id'])
								//{
								$name = "fckdiv".$questionId;
								$marksname = "marksName".$questionId;
								?>
								<a href="javascript://" onclick="showfck('<?php echo $name; ?>','<?php echo $marksname; ?>');"><?php if ($answerType == FREE_ANSWER) echo "&nbsp;".get_lang('EditCommentsAndMarks'); else echo "&nbsp;".get_lang('AddComments');?></a>
								<?php
								$comnt = get_comments($id,$questionId);
								echo "<br> <br>".$comnt;
								?>
								<div id="<?php echo $name; ?>" style="visibility:hidden">
								<?php
								$arrid[] = $questionId;
								$fck_attribute['Width'] = '400';
								$fck_attribute['Height'] = '150';
								$fck_attribute['ToolbarSet'] = 'Comment';
								$fck_attribute['Config']['IMUploadPath'] = 'upload/test/';
								$$questionId = new FormValidator('frmcomments'.$questionId,'post','');
								$renderer =& $$questionId->defaultRenderer();
								$renderer->setFormTemplate(
	'<form{attributes}><div align="left">{content}</div></form>');

								$renderer->setElementTemplate(
	'<div align="left">{element}</div>'
);
								$comnt =get_comments($id,$questionId);
								${user.$questionId}['comments_'.$questionId] = $comnt;
								$$questionId->addElement('html_editor','comments_'.$questionId,false);
								//$$questionId->addElement('submit','submitQuestion',get_lang('Ok'));
								$$questionId->setDefaults(${user.$questionId});
								$$questionId->display();
								?>
								</div>
							<?php


								}
							else
								{

							$comnt = get_comments($id,$questionId);
							echo "<br> <br>".$comnt;
								}
					?>

		 </td>
	</tr>
  <tr>
  <td></td>
  <td align= "left" >

  <?php
				 if($is_allowedToEdit)
						{
							if ($answerType == FREE_ANSWER)
								{
								$marksname = "marksName".$questionId;
								?>
							 <div id="<?php echo $marksname; ?>" style="visibility:hidden">
							 <form name="marksform_<?php echo $questionId; ?>" method="post" action="">


								  <?php
								  $arrmarks[] = $questionId;
								 echo get_lang("AssignMarks");
								  echo "<select name='marks' id='marks'>";
								  for($i=0;$i<=$questionWeighting;$i++)
									{?>
									<option <?php if ($i==$questionScore) echo "selected='selected'";?>>
										<?php echo $i;
											?></option>
    									<?php } ?>
								  </select>
								  </form></div><?php
								  if($questionScore==-1){
								  	$questionScore=0;
								  	echo '<br>'.get_lang('notCorrectedYet');
								  }
								 }
								 else{
								 	$arrmarks[] = $questionId;
								 	echo '<div id="'.$marksname.'" style="visibility:hidden"><form name="marksform_'.$questionId.'" method="post" action=""><select name="marks" id="marks" style="display:none;"><option>'.$questionScore.'</option></select></form></div>';
								 }
						}
						else{
							if($questionScore==-1){
								  	$questionScore=0;
						}
						}?>

  </td><tr><td></td><td align="right"><b><?php echo get_lang('Score')." : $questionScore/$questionWeighting"; ?></b></td>
  </tr>
	<?php  unset($objAnswerTmp);
		$i++;
$totalWeighting+=$questionWeighting;
		}
?>
<tr><td></td><td align=right><b><?php
			//$query = "update ".$TBL_TRACK_EXERCICES." set exe_result = $totalScore where exe_id = '$id'";
			//api_sql_query($query,__FILE__,__LINE__);
			echo '<br/>'.get_lang('YourTotalScore')." ";
			if($dsp_percent == true)
				{
			  	echo number_format(($totalScore/$totalWeighting)*100,1,'.','')."%";
				}
			else
				{
			  echo $totalScore."/".$totalWeighting;
				}
                  ?> !</b>
	</td></tr>
	<tr><td></td>
		<td align="right">
		<br />
		<?php $strids = implode(",",$arrid);
			$marksid = implode(",",$arrmarks);
			if($is_allowedToEdit)
			{
		?>
		
		<?php
			if(in_array($origin, array('tracking_course','user_course'))){
				echo ' <form name="myform" id="myform" action="exercice.php?show=result&comments=update&exeid='.$id.'&test='.$test.'&emailid='.$emailId.'&origin='.$origin.'&student='.$_GET['student'].'&details=true&course='.$_GET['cidReq'].'" method="post">';
			}
			else{
				echo ' <form name="myform" id="myform" action="exercice.php?show=result&comments=update&exeid='.$id.'&test='.$test.'&emailid='.$emailId.'" method="post">';
			}
		?>
			
			 <input type="submit" value="<?php echo get_lang('Ok'); ?>" onclick="getFCK('<?php echo $strids; ?>','<?php echo $marksid; ?>');"/>
			 </form>
		<?php } ?>
		</td>
		</tr>
</table>