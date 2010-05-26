<?php //$id: $
/* For licensing terms, see /license.txt */
/**
**	@package chamilo.exercise
* 	@author Julio Montoya Armas Added switchable fill in blank option added
* 	@version $Id: exercise_show.php 22256 2009-07-20 17:40:20Z ivantcholakov $
*	@package chamilo.exercise
* 	@todo remove the debug code and use the general debug library
* 	@todo small letters for table variables
*/

// name of the language file that needs to be included
$language_file=array('exercice','tracking');

// including the global dokeos file
require_once '../inc/global.inc.php';
require_once '../inc/lib/course.lib.php';
// including additional libraries
require_once 'exercise.class.php';
require_once 'question.class.php'; //also defines answer type constants
require_once 'answer.class.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'exercise_show_functions.lib.php';

if ( empty ( $origin ) ) {
    $origin = $_REQUEST['origin'];
}

if ($origin == 'learnpath')
	api_protect_course_script();
else
	api_protect_course_script(true);

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

if($debug>0) {
	echo str_repeat('&nbsp;',0).'Entered exercise_result.php'."<br />\n";var_dump($_POST);
}
// general parameters passed via POST/GET

if ( empty ( $learnpath_id ) ) {
    $learnpath_id       = $_REQUEST['learnpath_id'];
}
if ( empty ( $learnpath_item_id ) ) {
    $learnpath_item_id  = $_REQUEST['learnpath_item_id'];
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
    $questionNum    = $_REQUEST['questionNum'];
}
if ( empty ( $nbrQuestions ) ) {
    $nbrQuestions   = $_REQUEST['nbrQuestions'];
}
if ( empty ( $questionList ) ) {
    $questionList = $_SESSION['questionList'];
}
if ( empty ( $objExercise ) ) {
    $objExercise = $_SESSION['objExercise'];
}
if ( empty ( $exeId ) ) {
    $exeId = $_REQUEST['id'];
}

if ( empty ( $action ) ) {
    $action = $_GET['action'];
}
$current_user_id = api_get_user_id();
$current_user_id = "'".$current_user_id."'";
$current_attempt = $_SESSION['current_exercice_attempt'][$current_user_id];

//Is fraudulent exercice
$current_time = time();

$emailId   = $_REQUEST['email'];
$user_name = $_REQUEST['user'];
$test 	   = $_REQUEST['test'];
$dt	 	   = $_REQUEST['dt'];
$marks 	   = $_REQUEST['res'];
$id 	   = $_REQUEST['id'];

$sql_fb_type='SELECT feedback_type, exercises.id FROM '.$TBL_EXERCICES.' as exercises, '.$TBL_TRACK_EXERCICES.' as track_exercises WHERE exercises.id=track_exercises.exe_exo_id AND track_exercises.exe_id="'.Database::escape_string($id).'"';
$res_fb_type=Database::query($sql_fb_type);
$row_fb_type=Database::fetch_row($res_fb_type);
$feedback_type = $row_fb_type[0];
$exercise_id = intval($row_fb_type[1]);

$course_code = api_get_course_id();
$session_id  = api_get_session_id();
$current_expired_time_key = $course_code.'_'.$session_id.'_'.$exercise_id;

if (isset($_SESSION['expired_time'][$current_expired_time_key])) { //Only for exercice of type "One page"
	$expired_date = $_SESSION['expired_time'][$current_expired_time_key];
	$expired_time = strtotime($expired_date);

	//Validation in case of fraud
	$total_time_allowed = $expired_time + 30;
	if ($total_time_allowed < $current_time) {
	  $sql_fraud = "UPDATE $TBL_TRACK_ATTEMPT SET answer = 0, marks=0, position=0 WHERE exe_id = '$current_attempt' ";
	  Database::query($sql_fraud);
	}
}

//Unset session for clock time
unset($_SESSION['current_exercice_attempt'][$current_user_id]);
unset($_SESSION['expired_time'][$current_expired_time_key]);
unset($_SESSION['end_expired_time'][$current_expired_time_key]);

$is_allowedToEdit=api_is_allowed_to_edit(null,true) || $is_courseTutor;
$nameTools=get_lang('CorrectTest');

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array ('url' => '../gradebook/'.$_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
}
$fromlink = '';
if($origin=='user_course') {
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".Security::remove_XSS($_GET['course']), "name" => get_lang("Users"));
	$interbreadcrumb[] = array("url" => "../mySpace/myStudents.php?student=".Security::remove_XSS($_GET['student'])."&course=".$_course['id']."&details=true&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("DetailsStudentInCourse"));
} else if($origin=='tracking_course') {

	//$interbreadcrumb[] = array ("url" => "../mySpace/index.php", "name" => get_lang('MySpace'));
 	//$interbreadcrumb[] = array ("url" => "../mySpace/myStudents.php?student=".Security::remove_XSS($_GET['student']).'&details=true&origin='.$origin.'&course='.Security::remove_XSS($_GET['cidReq']), "name" => get_lang("DetailsStudentInCourse"));
 	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$cidReq.'&studentlist=true&id_session='.$_SESSION['id_session'], "name" => get_lang("Tracking"));
	$interbreadcrumb[] = array ("url" => "../mySpace/myStudents.php?student=".Security::remove_XSS($_GET['student']).'&details=true&origin='.$origin.'&course='.Security::remove_XSS($_GET['cidReq']), "name" => get_lang("DetailsStudentInCourse"));
	$interbreadcrumb[] = array ("url" => "../mySpace/lp_tracking.php?action=stats&course=".$cidReq."&student_id=".Security::remove_XSS($_GET['student'])."&lp_id=".Security::remove_XSS($_GET['my_lp_id'])."&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("LearningPathDetails"));

	$from_myspace = false;
	if (isset ($_GET['from']) && $_GET['from'] == 'myspace') {
		$fromlink = '&from=myspace';
		$this_section = SECTION_TRACKING;
	} else {
		$this_section = SECTION_COURSES;
	}

} elseif($origin=='student_progress') {
	$this_section = SECTION_TRACKING;
	$interbreadcrumb[] = array ("url" => "../auth/my_progress.php?id_session".Security::remove_XSS($_GET['id_session'])."&course=".$_cid, "name" => get_lang('MyProgress'));
	unset($_cid);
} else {
	$interbreadcrumb[]=array("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
	$this_section=SECTION_COURSES;
}


if ($origin != 'learnpath') {
	Display::display_header($nameTools,"Exercise");
} else {
	Display::display_reduced_header();
}

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
	document.getElementById(sid).style.display='block';
	document.getElementById(marksid).style.display='block';
	var comment = 'feedback_'+sid;
	document.getElementById(comment).style.display='none';
}

function getFCK(vals,marksid)
{
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

/*
		MAIN CODE
*/

// Email configuration settings

$coursecode = api_get_course_id();
$courseName = $_SESSION['_course']['name'];

$to = '';
$teachers = array();
if(api_get_setting('use_session_mode')=='true' && !empty($_SESSION['id_session'])) {
	$teachers = CourseManager::get_coach_list_from_course_code($coursecode,$_SESSION['id_session']);
} else {
	$teachers = CourseManager::get_teacher_list_from_course_code($coursecode);
}

$num = count($teachers);
if($num>1) {
	$to = array();
	foreach($teachers as $teacher) {
		$to[] = $teacher['email'];
	}
} elseif ($num>0){
	foreach($teachers as $teacher) {
		$to = $teacher['email'];
	}
} else {
	//this is a problem (it means that there is no admin for this course)
}


?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="2">
<?php
$sql_test_name='SELECT title, description, results_disabled FROM '.$TBL_EXERCICES.' as exercises, '.$TBL_TRACK_EXERCICES.' as track_exercises WHERE exercises.id=track_exercises.exe_exo_id AND track_exercises.exe_id="'.Database::escape_string($id).'"';
$result=Database::query($sql_test_name);
$show_results = true;
// Avoiding the "Score 0/0" message  when the exe_id is not set
if (Database::num_rows($result)>0 && isset($id)) {
	$test=Database::result($result,0,0);
	$exerciseTitle=api_parse_tex($test);
	$exerciseDescription=Database::result($result,0,1);

	// if the results_disabled of the Quiz is 1 when block the script
	$result_disabled=Database::result($result,0,2);
	if (!(api_is_platform_admin() || api_is_course_admin()) ) {
		if ($result_disabled==1) {
			//api_not_allowed();
			$show_results = false;
			//Display::display_warning_message(get_lang('CantViewResults'));
			if ($origin!='learnpath') {
				Display::display_warning_message(get_lang('ThankYouForPassingTheTest').'<br /><br /><a href="exercice.php">'.(get_lang('BackToExercisesList')).'</a>', false);
				echo '</td>
				</tr>
				</table>';
			}
		}
	}
	if ($show_results == true) {
		$user_restriction = $is_allowedToEdit ? '' :  "AND user_id=".intval($_user['user_id'])." ";
		$query = "SELECT attempts.question_id, answer  from ".$TBL_TRACK_ATTEMPT." as attempts
						INNER JOIN ".$TBL_TRACK_EXERCICES." as stats_exercices ON stats_exercices.exe_id=attempts.exe_id
						INNER JOIN ".$TBL_EXERCICE_QUESTION." as quizz_rel_questions ON quizz_rel_questions.exercice_id=stats_exercices.exe_exo_id AND quizz_rel_questions.question_id = attempts.question_id
						INNER JOIN ".$TBL_QUESTIONS." as questions ON questions.id=quizz_rel_questions.question_id
				  WHERE attempts.exe_id='".Database::escape_string($id)."' $user_restriction
				  GROUP BY quizz_rel_questions.question_order, attempts.question_id";
					//GROUP BY questions.position, attempts.question_id";
		$result =Database::query($query);
	}
} else {
	Display::display_warning_message(get_lang('CantViewResults'));
	$show_results = false;
	echo '</td>
	</tr>
	</table>';
}
if ($origin == 'learnpath' && !isset($_GET['fb_type']) ) {
	$show_results = false;
}

if ($show_results == true ) {
	?>
	<table width="100%">
		<tr>
			<td style="font-weight:bold" width="10%"><div class="actions-message"><?php echo '&nbsp;'.get_lang('CourseTitle')?> : </div></td>
			<td><div class="actions-message" width="90%"><?php echo $_course['name'] ?></div></td>
		</tr>
		<tr>
			<td style="font-weight:bold" width="10%"><div class="actions-message"><?php echo '&nbsp;'.get_lang('User')?> : </div></td>
			<td><div class="actions-message" width="90%"><?php
			if (isset($_GET['cidReq'])) {
				$course_code = Security::remove_XSS($_GET['cidReq']);
			} else {
				$course_code = api_get_course_id();
			}
			if (isset($_GET['student'])) {
				$user_id	= Security::remove_XSS($_GET['student']);
			}else {
				$user_id	= api_get_user_id();
			}

			$status_info=CourseManager::get_user_in_course_status($user_id,$course_code);
			if (STUDENT==$status_info) {
				$user_info=api_get_user_info($user_id);
				echo api_get_person_name($user_info['firstName'], $user_info['lastName']);
			} elseif(COURSEMANAGER==$status_info && !isset($_GET['user'])) {
				$user_info=api_get_user_info($user_id);
				echo api_get_person_name($user_info['firstName'], $user_info['lastName']);
			} else {
				echo $user_name;
			}

			?></div></td>
		</tr>
		<tr>
			<td style="font-weight:bold" width="10%" class="actions-message">
				<?php echo '&nbsp;'.get_lang("Exercise").' :'; ?>
			</td>
			<td width="90%">
			<?php echo $test; ?><br />
			<?php echo $exerciseDescription; ?>
			</td>
		</tr>
	</table>
	<br />
	</table>
  	<?php
}
$i=$totalScore=$totalWeighting=0;

if($debug>0){echo "ExerciseResult: "; var_dump($exerciseResult); echo "QuestionList: ";var_dump($questionList);}
$arrques = array();
$arrans = array();

if ($show_results) {
	$questionList = array();
	$exerciseResult = array();
	$k=0;
	$counter=0;
	while ($row = Database::fetch_array($result)) {
		$questionList[] = $row['question_id'];
		$exerciseResult[] = $row['answer'];
	}
	// for each question
	foreach($questionList as $questionId) {
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
		
		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER) {
			$colspan=2;
		}
		if($answerType == MATCHING || $answerType == FREE_ANSWER) {
			$colspan=2;
		} else {
			$colspan=2;
		}
		?>
    	<div id="question_title" class="sectiontitle">
    		<?php echo get_lang("Question").' '.($counter).' : '.$questionName; ?>
    	</div>
    	<div id="question_description">
    		<?php echo $questionDescription; ?>
    	</div>

	 	<?php
		if ($answerType == MULTIPLE_ANSWER) {
			$choice=array();
			?>
			<table width="100%" border="0" cellspacing="3" cellpadding="3">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
				<td><i><?php echo get_lang("Choice"); ?></i> </td>
				<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
				<td><i><?php echo get_lang("Answer"); ?></i></td>
				<?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
				<td><i><?php echo get_lang("Comment"); ?></i></td>
				<?php } else { ?>
				<td>&nbsp;</td>
				<?php } ?>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>
			<?php
			// construction of the Answer object
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select * from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resultans = Database::query($queryans);
				while ($row = Database::fetch_array($resultans)) {
					$ind = $row['answer'];
					$choice[$ind] = 1;
				}

				$numAnswer=$objAnswerTmp->selectAutoId($answerId);

				$studentChoice=$choice[$numAnswer];

				if ($studentChoice) {
					$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
				}
				echo '<tr><td>';
				if ($answerId==1) {
						ExerciseShowFunctions::display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
						ExerciseShowFunctions::display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}
				echo '</td></tr>';
				$i++;
		 	}
		 	echo '</table>';
		} elseif ($answerType == MULTIPLE_ANSWER_COMBINATION) {
			$choice=array();
			?>
			<table width="100%" border="0" cellspacing="3" cellpadding="3">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
				<td><i><?php echo get_lang("Choice"); ?></i> </td>
				<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
				<td><i><?php echo get_lang("Answer"); ?></i></td>
				<td><i><?php echo get_lang("Comment"); ?></i></td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>
			<?php
			// construction of the Answer object
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;

			$real_answers = array();

			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select * from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resultans = Database::query($queryans);
				while ($row = Database::fetch_array($resultans)) {
					$ind = $row['answer'];
					$choice[$ind] = 1;
				}
				$numAnswer=$objAnswerTmp->selectAutoId($answerId);
				$studentChoice=$choice[$numAnswer];

				if ($answerCorrect == 1) {
					if ($studentChoice) {
						$real_answers[$answerId] = true;
					} else {
						$real_answers[$answerId] = false;
					}
				} else {
					if ($studentChoice) {
						$real_answers[$answerId] = false;
					} else {
						$real_answers[$answerId] = true;
					}
				}

				echo '<tr><td>';
				if ($answerId==1) {
						ExerciseShowFunctions::display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
						ExerciseShowFunctions::display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}
				echo '</td></tr>';
				$i++;
		 	}

		 	$final_answer = true;
		 	foreach($real_answers as $my_answer) {
		 		if (!$my_answer) {
		 			$final_answer = false;
		 		}
		 	}

		 	if ($final_answer) {
		 		//getting only the first score where we save the weight of all the question
		 		$answerWeighting=$objAnswerTmp->selectWeighting(1);
				$questionScore+=$answerWeighting;
				$totalScore+=$answerWeighting;
			}

		 	echo '</table>';
		} elseif ($answerType == UNIQUE_ANSWER) {
			?>
			<table width="100%" border="0" cellspacing="3" cellpadding="3">
				<tr>
				<td>&nbsp;</td>
				</tr>
				<tr>
					<td><i><?php echo get_lang("Choice"); ?></i> </td>
					<td><i><?php echo get_lang("ExpectedChoice"); ?></i></td>
					<td><i><?php echo get_lang("Answer"); ?></i></td>
					<?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
					<td><i><?php echo get_lang("Comment"); ?></i></td>
					<?php } else { ?>
					<td>&nbsp;</td>
					<?php } ?>
				</tr>
				<tr>
				<td>&nbsp;</td>
				</tr>
			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select answer from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resultans = Database::query($queryans);
				$choice = Database::result($resultans,0,"answer");

				$numAnswer=$objAnswerTmp->selectAutoId($answerId);

				$studentChoice=($choice == $numAnswer)?1:0;
				if ($studentChoice) {
				  	$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
				}
				echo '<tr><td>';
				if ($answerId==1) {
					ExerciseShowFunctions::display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
					ExerciseShowFunctions::display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}
				echo '</td></tr>';
				$i++;
			}
			echo '</table>';

		} elseif ($answerType == FILL_IN_BLANKS) {

			?>
			<table width="100%" border="0" cellspacing="3" cellpadding="3">
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

			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer = $objAnswerTmp->selectAnswer($answerId);
				$answerComment = $objAnswerTmp->selectComment($answerId);
				$answerCorrect = $objAnswerTmp->isCorrect($answerId);
				$answerWeighting = $objAnswerTmp->selectWeighting($answerId);

			    // the question is encoded like this
			    // [A] B [C] D [E] F::10,10,10@1
			    // number 1 before the "@" means that is a switchable fill in blank question
			    // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
			    // means that is a normal fill blank question

				$pre_array = explode('::', $answer);

				// is switchable fill blank or not
				$is_set_switchable = explode('@', $pre_array[1]);
				$switchable_answer_set=false;
				if ($is_set_switchable[1]==1) {
					$switchable_answer_set=true;
				}

				$answer = $pre_array[0];			

				// splits weightings that are joined with a comma
				$answerWeighting = explode(',',$is_set_switchable[0]);
				//list($answer,$answerWeighting)=explode('::',$multiple[0]);

				//$answerWeighting=explode(',',$answerWeighting);
				// we save the answer because it will be modified
			    $temp=$answer;

				// TeX parsing
				// 1. find everything between the [tex] and [/tex] tags
				$startlocations=api_strpos($temp,'[tex]');
				$endlocations=api_strpos($temp,'[/tex]');
				if ($startlocations !== false && $endlocations !== false) {
					$texstring=api_substr($temp,$startlocations,$endlocations-$startlocations+6);
					// 2. replace this by {texcode}
					$temp=str_replace($texstring,'{texcode}',$temp);
				}
				$j=0;
				// the loop will stop at the end of the text
				$i=0;
				//normal fill in blank
				if (!$switchable_answer_set) {
					while (1) {
						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,'[')) === false) {
							// adds the end of the text
							$answer.=$temp;
							// TeX parsing
							$texstring = api_parse_tex($texstring);
							break;
						}
					    $temp=api_substr($temp,$pos+1);
						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,']')) === false) {
							break;
						}
	
						$queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".Database::escape_string($id)."' AND question_id= '".Database::escape_string($questionId)."'";
						$resfill = Database::query($queryfill);
						$str = Database::result($resfill,0,'answer');
						
						preg_match_all('#\[([^[]*)\]#', $str, $arr);
						$str = str_replace('\r\n', '', $str);
						$choice = $arr[1];
						
						$tmp=strrpos($choice[$j],' / ');
						$choice[$j]=substr($choice[$j],0,$tmp);
						$choice[$j]=trim($choice[$j]);
			
						//Needed to let characters ' and " to work as part of an answer
						$choice[$j] = stripslashes($choice[$j]);


						// if the word entered by the student IS the same as the one defined by the professor
						if (api_strtolower(api_substr($temp,0,$pos)) == api_strtolower($choice[$j])) {
						//if ((api_substr($temp,0,$pos)) == ($choice[$j])) {
							// gives the related weighting to the student
							$questionScore+=$answerWeighting[$j];
							// increments total score
							$totalScore+=$answerWeighting[$j];
						}
						// else if the word entered by the student IS NOT the same as the one defined by the professor
						$j++;
						$temp=api_substr($temp,$pos+1);
						$i=$i+1;
					}
					$answer = stripslashes($str);					

				} else {
					//multiple fill in blank
					while (1) {
						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,'[')) === false) {
							// adds the end of the text
							$answer.=$temp;
							// TeX parsing
							$texstring = api_parse_tex($texstring);
							//$answer=str_replace("{texcode}",$texstring,$answer);
							break;
						}
						// adds the piece of text that is before the blank and ended by [
						$real_text[]=api_substr($temp,0,$pos+1);
						$answer.=api_substr($temp,0,$pos+1);
						$temp=api_substr($temp,$pos+1);

						// quits the loop if there are no more blanks
						if (($pos = api_strpos($temp,']')) === false) {
							// adds the end of the text
							//$answer.=$temp;
							break;
						}

						$queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
						$resfill = Database::query($queryfill);
						$str=Database::result($resfill,0,"answer");
						preg_match_all ('#\[([^[/]*)/#', $str, $arr);
						$choice = $arr[1];

						$choice[$j]=trim($choice[$j]);
						$user_tags[]=api_strtolower($choice[$j]);
						$correct_tags[]=api_strtolower(api_substr($temp,0,$pos));

						$j++;
						$temp=api_substr($temp,$pos+1);
						$i=$i+1;
					}
					$answer='';
					for ($i=0;$i<count($correct_tags);$i++) {
						if (in_array($user_tags[$i],$correct_tags)) {
							// gives the related weighting to the student
							$questionScore+=$answerWeighting[$i];
							// increments total score
							$totalScore+=$answerWeighting[$i];
						}
					}
					$answer = stripslashes($str);
					$answer = str_replace('rn', '', $answer);
				}
				//echo $questionScore."-".$totalScore;
				echo '<tr><td>';
				ExerciseShowFunctions::display_fill_in_blanks_answer($answer,$id,$questionId);
				echo '</td></tr>';
				$i++;
			}
			echo '</table>';
		} elseif ($answerType == FREE_ANSWER) {$answer = $str;
			?>
			<table width="100%" border="0" cellspacing="3" cellpadding="3">
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
			$objAnswerTmp = new Answer($questionId);
			$nbrAnswers = $objAnswerTmp->selectNbrAnswers();
			$questionScore = 0;
			$query 	= "SELECT answer, marks FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".Database::escape_string($id)."' AND question_id= '".Database::escape_string($questionId)."'";
			$resq	= Database::query($query);
			$choice = Database::result($resq,0,'answer');
			$choice = str_replace('\r\n', '', $choice);
			$choice = stripslashes($choice);			

			$questionScore = Database::result($resq,0,"marks");
			if ($questionScore==-1) {
				$totalScore+=0;
			} else {
				$totalScore+=$questionScore;
			}

			$arrques[] = $questionName;
            $arrans[]  = $choice;

			echo '<tr>
			<td valign="top">'.ExerciseShowFunctions::display_free_answer($choice, $id, $questionId).'</td>
			</tr>
			</table>';

		} elseif ($answerType == MATCHING) {

			$objAnswerTmp=new Answer($questionId);
			$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
			$TBL_TRACK_ATTEMPT= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
			$sql_answer = 'SELECT id, answer FROM '.$table_ans.' WHERE question_id="'.Database::escape_string($questionId).'" AND correct=0';
			$res_answer = Database::query($sql_answer);
			// getting the real answer
			$real_list =array();
			while ($real_answer = Database::fetch_array($res_answer)) {
				$real_list[$real_answer['id']]= $real_answer['answer'];
			}
	
			$sql_select_answer = 'SELECT id, answer, correct, id_auto FROM '.$table_ans.'
								  WHERE question_id="'.Database::escape_string($questionId).'" AND correct <> 0 ORDER BY id_auto';
								  
			$res_answers = Database::query($sql_select_answer);

			echo '<table width="100%" height="71" border="0" cellspacing="3" cellpadding="3" >';
			echo '<tr><td colspan="2">&nbsp;</td></tr>';
			echo '<tr>
					<td><span style="font-style: italic;">'.get_lang('ElementList').'</span> </td>
					<td><span style="font-style: italic;">'.get_lang('CorrespondsTo').'</span></td>
				  </tr>';
			echo '<tr><td colspan="2">&nbsp;</td></tr>';

			$questionScore = 0;

			while ($a_answers = Database::fetch_array($res_answers)) {

				$i_answer_id 	= $a_answers['id']; //3
				$s_answer_label = $a_answers['answer'];  // your daddy - your mother
				$i_answer_correct_answer = $a_answers['correct']; //1 - 2
				$i_answer_id_auto = $a_answers['id_auto']; // 3 - 4

				$sql_user_answer = "SELECT answer FROM $TBL_TRACK_ATTEMPT
									WHERE exe_id = '$id' AND question_id = '$questionId' AND position='$i_answer_id_auto'";
				
				$res_user_answer = Database::query($sql_user_answer);

				if (Database::num_rows($res_user_answer)>0 ) {
					$s_user_answer = Database::result($res_user_answer,0,0); //  rich - good looking
				} else {
					$s_user_answer = 0;
				}

				$i_answerWeighting=$objAnswerTmp->selectWeighting($i_answer_id);

				$user_answer = '';

				if (!empty($s_user_answer)) {
					if ($s_user_answer == $i_answer_correct_answer)	{
						$questionScore	+= $i_answerWeighting;
						$totalScore		+= $i_answerWeighting;
						$user_answer = '<span>'.$real_list[$i_answer_correct_answer].'</span>';
					} else {
						$user_answer = '<span style="color: #FF0000; text-decoration: line-through;">'.$real_list[$s_user_answer].'</span>';
					}
				}
				echo '<tr>';
				echo '<td>'.$s_answer_label.'</td><td>'.$user_answer.' / <b><span style="color: #008000;">'.$real_list[$i_answer_correct_answer].'</span></b></td>';
				echo '</tr>';
			}
			echo '</table>';
		} elseif ($answerType == HOT_SPOT) {
			?>
			<table width="500" border="0">

			<?php
			$objAnswerTmp=new Answer($questionId);
			$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
			$questionScore=0;
			?>
				<tr>
					<td valign="top" align="center" style="padding-left:0px;" >
						<table border="1" bordercolor="#A4A4A4" style="border-collapse: collapse;" width="552">
			<?php
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);

				$TBL_TRACK_HOTSPOT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
				$query = "select hotspot_correct from ".$TBL_TRACK_HOTSPOT." where hotspot_exe_id = '".Database::escape_string($id)."' and hotspot_question_id= '".Database::escape_string($questionId)."' AND hotspot_answer_id='".Database::escape_string($answerId)."'";
				$resq=Database::query($query);
				$choice = Database::result($resq,0,"hotspot_correct");
				ExerciseShowFunctions::display_hotspot_answer($answerId,$answer,$choice,$answerComment);

				$i++;
		 	}
		 	$queryfree = "select marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
			$resfree = Database::query($queryfree);
			$questionScore= Database::result($resfree,0,"marks");
			$totalScore+=$questionScore;
			echo '</table></td></tr>';
		 	echo '<tr>
				<td colspan="2">'.
					//<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'&exe_id='.$id.'&from_db=1" width="556" height="421">
					'<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.Security::remove_XSS($questionId).'&exe_id='.$id.'&from_db=1" width="552" height="352">
						<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.Security::remove_XSS($questionId).'&exe_id='.$id.'&from_db=1" />
					</object>

				</td>
			</tr>
			</table><br/>';
		}

		echo '<table width="100%" border="0" cellspacing="3" cellpadding="0">';
		if ($is_allowedToEdit) {
			echo '<tr><td>';
			$name = "fckdiv".$questionId;
			$marksname = "marksName".$questionId;
			?>
			<br />
			<a href="javascript://" onclick="showfck('<?php echo $name; ?>','<?php echo $marksname; ?>');">
			<?php
			if ($answerType == FREE_ANSWER) {
				echo get_lang('EditCommentsAndMarks');
			} else {
				if ($action=='edit') {
					echo '<img src="../img/edit.gif"/>'.get_lang('EditIndividualComment');
				} else {
					echo get_lang('AddComments');
				}
			}
			echo '</a><br /><div id="feedback_'.$name.'" style="width:100%">';
			$comnt = trim(ExerciseShowFunctions::get_comments($id,$questionId));
			if (empty($comnt)) {
				echo '<br />';
			} else {
				echo '<div id="question_feedback">'.$comnt.'</div>';
			}
			echo '</div><div id="'.$name.'" style="display:none">';
			$arrid[] = $questionId;

			$feedback_form = new FormValidator('frmcomments'.$questionId,'post','');
			$feedback_form->addElement('html','<br>');
			$renderer =& $feedback_form->defaultRenderer();
			$renderer->setFormTemplate('<form{attributes}><div align="left">{content}</div></form>');
			$renderer->setElementTemplate('<div align="left">{element}</div>');
			$comnt = ExerciseShowFunctions::get_comments($id,$questionId);
			${user.$questionId}['comments_'.$questionId] = $comnt;
			$feedback_form->addElement('html_editor', 'comments_'.$questionId, null, null, array('ToolbarSet' => 'TestAnswerFeedback', 'Width' => '100%', 'Height' => '120'));
			$feedback_form->addElement('html','<br>');
			//$feedback_form->addElement('submit','submitQuestion',get_lang('Ok'));
			$feedback_form->setDefaults(${user.$questionId});
			$feedback_form->display();
			echo '</div>';
		} else {
			$comnt = ExerciseShowFunctions::get_comments($id,$questionId);
			echo '<tr><td><br />';
			if (!empty($comnt)) {
				echo '<b>'.get_lang('Feedback').'</b>';
				echo '<div id="question_feedback">'.$comnt.'</div>';
			}
			echo '</td><td>';
		}
		if ($is_allowedToEdit) {
			if ($answerType == FREE_ANSWER) {
				$marksname = "marksName".$questionId;
				?>
				<div id="<?php echo $marksname; ?>" style="display:none">
				<form name="marksform_<?php echo $questionId; ?>" method="post" action="">
			    <?php
				$arrmarks[] = $questionId;
				echo get_lang("AssignMarks");
				echo "&nbsp;<select name='marks' id='marks'>";
				for ($i=0;$i<=$questionWeighting;$i++) {
					echo '<option '.(($i==$questionScore)?"selected='selected'":'').'>'.$i.'</option>';
				}
				echo '</select>';
				echo '</form><br/ ></div>';
				if ($questionScore==-1 ) {
					$questionScore=0;
				  	echo '<br />'.get_lang('notCorrectedYet');
				}
			} else {
				$arrmarks[] = $questionId;
				echo '<div id="'.$marksname.'" style="display:none"><form name="marksform_'.$questionId.'" method="post" action="">
					  <select name="marks" id="marks" style="display:none;"><option>'.$questionScore.'</option></select></form><br/ ></div>';
			}
		} else {
			if ($questionScore==-1) {
				 $questionScore=0;
			}
		}
		?>
		</td>
		</tr>
		</table>


		<?php
		$my_total_score  = float_format($questionScore,1);
		$my_total_weight = float_format($questionWeighting,1);

		echo '<div id="question_score">';
		echo get_lang('Score')." : $my_total_score/$my_total_weight";
		echo '</div>';

		unset($objAnswerTmp);
		$i++;
		$totalWeighting+=$questionWeighting;
	} // end of large foreach on questions
} //end of condition if $show_results

if ($origin!='learnpath' || ($origin == 'learnpath' && isset($_GET['fb_type']))) {
	//$query = "update ".$TBL_TRACK_EXERCICES." set exe_result = $totalScore where exe_id = '$id'";
	//Database::query($query);
	if ($show_results) {
		echo '<div id="question_score">'.get_lang('YourTotalScore')." ";
		if($dsp_percent == true) {
			$my_result = number_format(($totalScore/$totalWeighting)*100,1,'.','');
			$my_result = float_format($my_result,1);
			echo $my_result."%";
		} else {
			$my_total_score  = float_format($totalScore,1);
			$my_total_weight = float_format($totalWeighting,1);
			echo $my_total_score."/".$my_total_weight;
		}
		echo '!</div>';
	}
}

if (is_array($arrid) && is_array($arrmarks)) {
	$strids = implode(",",$arrid);
	$marksid = implode(",",$arrmarks);
}

if ($is_allowedToEdit) {
	if (in_array($origin, array('tracking_course','user_course'))) {

		echo ' <form name="myform" id="myform" action="exercice.php?show=result&comments=update&exeid='.$id.'&test='.urlencode($test).'&emailid='.$emailId.'&origin='.$origin.'&student='.Security::remove_XSS($_GET['student']).'&details=true&course='.Security::remove_XSS($_GET['cidReq']).$fromlink.'" method="post">';
		echo ' <input type = "hidden" name="totalWeighting" value="'.$totalWeighting.'">';
		if (isset($_GET['myid']) && isset($_GET['my_lp_id']) && isset($_GET['student'])) {
			?>
			<input type = "hidden" name="lp_item_id" value="<?php echo Security::remove_XSS($_GET['myid']); ?>">
			<input type = "hidden" name="lp_item_view_id" value="<?php echo Security::remove_XSS($_GET['my_lp_id']); ?>">
			<input type = "hidden" name="student_id" value="<?php echo Security::remove_XSS($_GET['student']);?>">
			<input type = "hidden" name="total_score" value="<?php echo $totalScore; ?>">
			<input type = "hidden" name="total_time" value="<?php echo Security::remove_XSS($_GET['total_time']);?>">
			<input type = "hidden" name="my_exe_exo_id" value="<?php echo Security::remove_XSS($_GET['my_exe_exo_id']); ?>">
			<?php
		}
	} else {
		echo ' <form name="myform" id="myform" action="exercice.php?show=result&comments=update&exeid='.$id.'&test='.$test.'&emailid='.$emailId.'&totalWeighting='.$totalWeighting.'" method="post">';
	}
	if ($origin!='learnpath' && $origin!='student_progress') {
		?>
		<button type="submit" class="save" value="<?php echo get_lang('Ok'); ?>" onclick="getFCK('<?php echo $strids; ?>','<?php echo $marksid; ?>');"><?php echo get_lang('FinishTest'); ?></button>
		</form>
		<?php
	}
}


if ($origin=='student_progress' && !isset($_GET['my_lp_id'])) {?>
	<button type="button" class="back" onclick="window.back();" value="<?php echo get_lang('Back'); ?>" ><?php echo get_lang('Backs');?></button>
<?php
} else if($origin=='myprogress') {
?>
	<button type="button" class="save" onclick="top.location.href='../auth/my_progress.php?course=<?php echo api_get_course_id()?>'" value="<?php echo get_lang('Finish'); ?>" ><?php echo get_lang('Finish');?></button>
<?php
}

if ($origin != 'learnpath') {
	$url_email = api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'&show=result';

	//we are not in learnpath tool
	Display::display_footer();
} else {
	$url_email = api_get_path(WEB_CODE_PATH).'mySpace/lp_tracking.php?course='.api_get_course_id().'&origin=tracking_course&lp_id='.$learnpath_id.'&student_id='.api_get_user_id();

	if (!isset($_GET['fb_type'])) {
		$lp_mode =  $_SESSION['lp_mode'];
		$url = '../newscorm/lp_controller.php?cidReq='.api_get_course_id().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId.'&fb_type='.$feedback_type;
		$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';
		echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";

		//record the results in the learning path, using the SCORM interface (API)
		echo '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$totalScore.','.$totalWeighting.');</script>'."\n";
		echo '</body></html>';
	} else {
		if (!$is_allowedToEdit) {
			ExerciseShowFunctions::send_notification($arrques, $arrans, $to);
		}
		Display::display_normal_message(get_lang('ExerciseFinished').' '.get_lang('ToContinueUseMenu'));
	}
}

if (!$is_allowedToEdit) {
	if ($origin != 'learnpath') {
 		ExerciseShowFunctions::send_notification($arrques, $arrans, $to);
	}
}

//destroying the session
api_session_unregister('questionList');
unset ($questionList);

api_session_unregister('exerciseResult');
unset ($exerciseResult);