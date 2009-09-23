<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
*
*	@package dokeos.exercise
* 	@author Julio Montoya Armas Added switchable fill in blank option added
* 	@version $Id: exercise_show.php 22256 2009-07-20 17:40:20Z ivantcholakov $
*
* 	@todo remove the debug code and use the general debug library
* 	@todo use the Database:: functions
* 	@todo small letters for table variables
*/

// name of the language file that needs to be included
$language_file=array('exercice','tracking');

// including the global dokeos file
include('../inc/global.inc.php');
include('../inc/lib/course.lib.php');
// including additional libraries
include_once('exercise.class.php');
include_once('question.class.php'); //also defines answer type constants
include_once('answer.class.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

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

$is_allowedToEdit=api_is_allowed_to_edit() || $is_courseTutor;
$nameTools=get_lang('CorrectTest');

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
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
	$interbreadcrumb[] = array ("url" => "../mySpace/lp_tracking.php?action=stats&course=".$cidReq."&student_id=".Security::remove_XSS($_GET['student'])."&lp_id=".$_GET['my_lp_id']."&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("LearningPathDetails"));

	$from_myspace = false;
	if (isset ($_GET['from']) && $_GET['from'] == 'myspace') {
		$fromlink = '&from=myspace';
		$this_section = "session_my_space";
	} else {
		$this_section = SECTION_COURSES;
	}

} else if($origin=='student_progress') {
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
$emailId   = $_REQUEST['email'];
$user_name = $_REQUEST['user'];
$test 	   = $_REQUEST['test'];
$dt	 	   = $_REQUEST['dt'];
$marks 	   = $_REQUEST['res'];
$id 	   = $_REQUEST['id'];

$sql_fb_type='SELECT feedback_type FROM '.$TBL_EXERCICES.' as exercises, '.$TBL_TRACK_EXERCICES.' as track_exercises WHERE exercises.id=track_exercises.exe_exo_id AND track_exercises.exe_id="'.Database::escape_string($id).'"';
$res_fb_type=Database::query($sql_fb_type,__FILE__,__LINE__);
$row_fb_type=Database::fetch_row($res_fb_type);
$feedback_type = $row_fb_type[0];

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

/**
 * This function gets the comments of an exercise
 *
 * @param int $id
 * @param int $question_id
 * @return str the comment
 */
function get_comments($id,$question_id)
{
	global $TBL_TRACK_ATTEMPT;
	$sql = "SELECT teacher_comment FROM ".$TBL_TRACK_ATTEMPT." where exe_id='".Database::escape_string($id)."' and question_id = '".Database::escape_string($question_id)."' ORDER by question_id";
	$sqlres = api_sql_query($sql, __FILE__, __LINE__);
	$comm = Database::result($sqlres,0,"teacher_comment");
	return $comm;
}
/**
 * Display the answers to a multiple choice question
 *
 * @param integer Answer type
 * @param integer Student choice
 * @param string  Textual answer
 * @param string  Comment on answer
 * @param string  Correct answer comment
 * @param integer Exercise ID
 * @param integer Question ID
 * @param boolean Whether to show the answer comment or not
 * @return void
 */
function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
{
	?>
	<tr>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $studentChoice?'_on':'_off'; ?>.gif"
		border="0" alt="" />
	</td>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $answerCorrect?'_on':'_off'; ?>.gif"
		border="0" alt=" " />
	</td>
	<td width="40%" style="border-bottom: 1px solid #4171B5;">
		<?php
		$answer=api_parse_tex($answer);
		echo $answer;
		?>
	</td>
	<td width="20%" style="border-bottom: 1px solid #4171B5;">
		<?php
		$answerComment=api_parse_tex($answerComment);
		if($studentChoice)
		{
			if(!$answerCorrect)
			{
				echo '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
			}
			else{
				echo '<span style="font-weight: bold; color: #008000;">'.nl2br(make_clickable($answerComment)).'</span>';
			}
		}
		else
		{
			echo '&nbsp;';
		}
		?>
	</td>
		<?php
	    if ($ans==1) {
	        $comm = get_comments($id,$questionId);
		}
	    ?>
	</tr>
	<?php
}
/**
 * Shows the answer to a fill-in-the-blanks question, as HTML
 * @param string    Answer text
 * @param int       Exercise ID
 * @param int       Question ID
 * @return void
 */

function display_fill_in_blanks_answer($answer,$id,$questionId)
{
	?>
		<tr>
		<td>
			<?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
		</td><?php
		if(!api_is_allowed_to_edit()) {?>
			<td>
			<?php
			$comm = get_comments($id,$questionId);
			?>
			</td>
			</tr>
	<?php }
}
/**
 * Shows the answer to a free-answer question, as HTML
 * @param string    Answer text
 * @param int       Exercise ID
 * @param int       Question ID
 * @return void
 */
function display_free_answer($answer,$id,$questionId) {
	?>
		<tr>
		<td>
			<?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
		</td> <?php if(!api_is_allowed_to_edit()) {?>
        <td>
        <?php
        $comm = get_comments($id,$questionId);
        ?>
        </td>
    <?php }?>
    </tr>
    <?php
}
/**
 * Displays the answer to a hotspot question
 *
 * @param int $answerId
 * @param string $answer
 * @param string $studentChoice
 * @param string $answerComment
 */
function display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment) {
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
		<td width="100px" valign="top" align="left">
			<div style="width:100%;">
				<div style="height:11px; width:11px; background-color:<?php echo $hotspot_colors[$answerId]; ?>; display:inline; float:left; margin-top:3px;"></div>
				<div style="float:left; padding-left:5px;">
				<?php echo $answerId; ?>
				</div>
				<div><?php echo '&nbsp;'.$answer ?></div>
			</div>
		</td>
		<td width="50px" style="padding-right:15px" valign="top" align="left">
			<?php
			$my_choice = ($studentChoice)?get_lang('Correct'):get_lang('Fault'); echo $my_choice; ?>
		</td>

		<td valign="top" align="left" >
			<?php
			$answerComment=api_parse_tex($answerComment);
			if($studentChoice) {
				echo '<span style="font-weight: bold; color: #008000;">'.nl2br(make_clickable($answerComment)).'</span>';
			} else {
				echo '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
			}
			?>
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
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="2">
<?php
$sql_test_name='SELECT title, description, results_disabled FROM '.$TBL_EXERCICES.' as exercises, '.$TBL_TRACK_EXERCICES.' as track_exercises WHERE exercises.id=track_exercises.exe_exo_id AND track_exercises.exe_id="'.Database::escape_string($id).'"';
$result=api_sql_query($sql_test_name);
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
		$result =api_sql_query($query, __FILE__, __LINE__);
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
				$course_code=Security::remove_XSS($_GET['cidReq']);
			} else {
				$course_code=api_get_course_id();
			}
			if (isset($_GET['student'])) {
				$user_id=Security::remove_XSS($_GET['student']);
			}else {
				$user_id=api_get_user_id();
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
			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
				$answer=$objAnswerTmp->selectAnswer($answerId);
				$answerComment=$objAnswerTmp->selectComment($answerId);
				$answerCorrect=$objAnswerTmp->isCorrect($answerId);
				$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
				$queryans = "select * from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
				$resultans = api_sql_query($queryans, __FILE__, __LINE__);
				while ($row = Database::fetch_array($resultans)) {
					$ind = $row['answer'];
					$choice[$ind] = 1;
				}
				$studentChoice=$choice[$answerId];
				if ($studentChoice) {
					$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
				}
				echo '<tr><td>';
				if ($answerId==1) {
						display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
						display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
				}
				echo '</td></tr>';
				$i++;
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
					<td><i><?php echo get_lang("Comment"); ?></i></td>
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
				$resultans = api_sql_query($queryans, __FILE__, __LINE__);
				$choice = Database::result($resultans,0,"answer");
				$studentChoice=($choice == $answerId)?1:0;
				if ($studentChoice) {
				  	$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
				}
				echo '<tr><td>';
				if ($answerId==1) {
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
				} else {
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
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

						$queryfill = "select answer from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
						$resfill = api_sql_query($queryfill, __FILE__, __LINE__);
						$str = Database::result($resfill,0,"answer");

						preg_match_all('#\[([^[]*)\]#', $str, $arr);
						$choice = $arr[1];
						$tmp=strrpos($choice[$j],' / ');
						$choice[$j]=substr($choice[$j],0,$tmp);
						$choice[$j]=trim($choice[$j]);


						// if the word entered by the student IS the same as the one defined by the professor
						if (api_strtolower(api_substr($temp,0,$pos)) == api_strtolower($choice[$j])) {
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
					$answer = str_replace('rn', '', $answer);

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
						$resfill = api_sql_query($queryfill, __FILE__, __LINE__);
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
				display_fill_in_blanks_answer($answer,$id,$questionId);
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
			$query = "select answer, marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
			$resq = api_sql_query($query);
			$choice = Database::result($resq,0,"answer");
			$choice = stripslashes($choice);
			$choice = str_replace('rn', '', $choice);

			$questionScore = Database::result($resq,0,"marks");
			if ($questionScore==-1) {
				$totalScore+=0;
			} else {
				$totalScore+=$questionScore;
			}
			echo '<tr>
			<td valign="top">'.display_free_answer($choice, $id, $questionId).'</td>
			</tr>
			</table>';

		} elseif ($answerType == MATCHING) {

			$objAnswerTmp=new Answer($questionId);
			$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
			$TBL_TRACK_ATTEMPT= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

			$sql_select_answer = 'SELECT id, answer, correct, position FROM '.$table_ans.' WHERE question_id="'.Database::escape_string($questionId).'" AND correct<>0';
			$sql_answer = 'SELECT position, answer FROM '.$table_ans.' WHERE question_id="'.Database::escape_string($questionId).'" AND correct=0';
			$res_answer = api_sql_query($sql_answer, __FILE__, __LINE__);
			// getting the real answer
			$real_list =array();
			while ($real_answer = Database::fetch_array($res_answer)) {
				$real_list[$real_answer['position']]= $real_answer['answer'];
			}

			$res_answers = api_sql_query($sql_select_answer, __FILE__, __LINE__);

			echo '<table width="100%" height="71" border="0" cellspacing="3" cellpadding="3" >';
			echo '<tr><td colspan="2">&nbsp;</td></tr>';
			echo '<tr>
					<td><span style="font-style: italic;">'.get_lang("ElementList").'</span> </td>
					<td><span style="font-style: italic;">'.get_lang("CorrespondsTo").'</span></td>
				  </tr>';
			echo '<tr><td colspan="2">&nbsp;</td></tr>';

			$questionScore=0;

			while ($a_answers = Database::fetch_array($res_answers)) {
				$i_answer_id = $a_answers['id']; //3
				$s_answer_label = $a_answers['answer'];  // your dady - you mother
				$i_answer_correct_answer = $a_answers['correct']; //1 - 2
				$i_answer_position = $a_answers['position']; // 3 - 4

				$sql_user_answer =
						'SELECT answers.answer
						FROM '.$TBL_TRACK_ATTEMPT.' as track_e_attempt
						INNER JOIN '.$table_ans.' as answers
							ON answers.position = track_e_attempt.answer
							AND track_e_attempt.question_id=answers.question_id
						WHERE answers.correct = 0
						AND track_e_attempt.exe_id = "'.Database::escape_string($id).'"
						AND track_e_attempt.question_id = "'.Database::escape_string($questionId).'"
						AND track_e_attempt.position="'.Database::escape_string($i_answer_position).'"';


				$res_user_answer = api_sql_query($sql_user_answer, __FILE__, __LINE__);
				if (Database::num_rows($res_user_answer)>0 ) {
					$s_user_answer = Database::result($res_user_answer,0,0); //  rich - good looking
				} else {
					$s_user_answer = '';
				}

				//$s_correct_answer = $s_answer_label; // your ddady - your mother
				$s_correct_answer = $real_list[$i_answer_correct_answer];

				$i_answerWeighting=$objAnswerTmp->selectWeighting($i_answer_id);

				//if($s_user_answer == $s_correct_answer) // rich == your ddady?? wrong
				//echo $s_user_answer.' - '.$real_list[$i_answer_correct_answer];

				if ($s_user_answer == $real_list[$i_answer_correct_answer]) { // rich == your ddady?? wrong
					$questionScore+=$i_answerWeighting;
					$totalScore+=$i_answerWeighting;
				} else {
					$s_user_answer = '<span style="color: #FF0000; text-decoration: line-through;">'.$s_user_answer.'</span>';
				}
				echo '<tr>';
				echo '<td>'.$s_answer_label.'</td><td>'.$s_user_answer.' / <b><span style="color: #008000;">'.$s_correct_answer.'</span></b></td>';
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
				$resq=api_sql_query($query);
				$choice = Database::result($resq,0,"hotspot_correct");
				display_hotspot_answer($answerId,$answer,$choice,$answerComment);

				$i++;
		 	}
		 	$queryfree = "select marks from ".$TBL_TRACK_ATTEMPT." where exe_id = '".Database::escape_string($id)."' and question_id= '".Database::escape_string($questionId)."'";
			$resfree = api_sql_query($queryfree, __FILE__, __LINE__);
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
			$comnt = trim(get_comments($id,$questionId));
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
			$comnt = get_comments($id,$questionId);
			${user.$questionId}['comments_'.$questionId] = $comnt;
			$feedback_form->addElement('html_editor', 'comments_'.$questionId, null, null, array('ToolbarSet' => 'TestAnswerFeedback', 'Width' => '100%', 'Height' => '120'));
			$feedback_form->addElement('html','<br>');
			//$feedback_form->addElement('submit','submitQuestion',get_lang('Ok'));
			$feedback_form->setDefaults(${user.$questionId});
			$feedback_form->display();
			echo '</div>';
		} else {
			$comnt = get_comments($id,$questionId);
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
				if ($questionScore==-1) {
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

		<div id="question_score">
		<?php
		$my_total_score  = float_format($questionScore,1);
		$my_total_weight = float_format($questionWeighting,1);
		echo get_lang('Score')." : $my_total_score/$my_total_weight";
		echo '</div>';
		unset($objAnswerTmp);
		$i++;
		$totalWeighting+=$questionWeighting;
	} // end of large foreach on questions
} //end of condition if $show_results

if ($origin!='learnpath' || ($origin == 'learnpath' && isset($_GET['fb_type']))) {
	//$query = "update ".$TBL_TRACK_EXERCICES." set exe_result = $totalScore where exe_id = '$id'";
	//api_sql_query($query,__FILE__,__LINE__);
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

		echo ' <form name="myform" id="myform" action="exercice.php?show=result&comments=update&exeid='.$id.'&test='.urlencode($test).'&emailid='.$emailId.'&origin='.$origin.'&student='.$_GET['student'].'&details=true&course='.$_GET['cidReq'].$fromlink.'" method="post">';
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

if ($origin=='student_progress') {?>
	<button type="button" class="back" onclick="window.back();" value="<?php echo get_lang('Back'); ?>" ><?php echo get_lang('Back');?></button>
<?php
} else if($origin=='myprogress') {
?>
	<button type="button" class="save" onclick="top.location.href='../auth/my_progress.php?course=<?php echo api_get_course_id()?>'" value="<?php echo get_lang('Finish'); ?>" ><?php echo get_lang('Finish');?></button>
<?php
}

if ($origin != 'learnpath') {
	//we are not in learnpath tool
	Display::display_footer();
} else {

	if (!isset($_GET['fb_type'])) {
		$lp_mode =  $_SESSION['lp_mode'];
		$url = '../newscorm/lp_controller.php?cidReq='.api_get_course_id().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId.'&fb_type='.$feedback_type;
		$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';
		echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";

		//record the results in the learning path, using the SCORM interface (API)
		echo '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$totalScore.','.$totalWeighting.');</script>'."\n";
		echo '</body></html>';
	} else {
		Display::display_normal_message(get_lang('ExerciseFinished'));
	}
}
//destroying the session
api_session_unregister('questionList');
unset ($questionList);

api_session_unregister('exerciseResult');
unset ($exerciseResult);
