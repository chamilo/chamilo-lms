<?php
/* For licensing terms, see /license.txt */
/**
*	Exercise result
*	This script gets informations from the script "exercise_submit.php",
*	through the session, and calculates the score of the student for
*	that exercise.
*	Then it shows the results on the screen.
*	@package chamilo.exercise
*	@author Olivier Brouckaert, main author
*	@author Roan Embrechts, some refactoring
* 	@author Julio Montoya Armas switchable fill in blank option added
* 	@version $Id: exercise_result.php 22201 2009-07-17 19:57:03Z cfasanando $
*
*	@todo	split more code up in functions, move functions to library?
*/
/*	INIT SECTION	*/
require_once 'exercise.class.php';
require_once 'exercise.lib.php';
require_once 'question.class.php';
require_once 'answer.class.php';

// Name of the language file that needs to be included
$language_file='exercice';

require_once '../inc/global.inc.php';

if ($_GET['origin']=='learnpath') {
	require_once '../newscorm/learnpath.class.php';
	require_once '../newscorm/learnpathItem.class.php';
	require_once '../newscorm/scorm.class.php';
	require_once '../newscorm/scormItem.class.php';
	require_once '../newscorm/aicc.class.php';
	require_once '../newscorm/aiccItem.class.php';
}
require_once api_get_path(LIBRARY_PATH).'exercise_show_functions.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';

$this_section=SECTION_COURSES;

/* 	ACCESS RIGHTS  */
// notice for unauthorized people.
api_protect_course_script(true);

// Database table definitions
$TBL_EXERCICE_QUESTION 	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          	= Database::get_course_table(TABLE_QUIZ_ANSWER);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$main_user_table 		= Database::get_main_table(TABLE_MAIN_USER);
$main_admin_table       = Database::get_main_table(TABLE_MAIN_ADMIN);
$main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

if($debug>0){error_log('Entered exercise_result.php: '.print_r($_POST,1));}

// general parameters passed via POST/GET
if ( empty ( $origin ) ) {                  $origin                 = Security::remove_XSS($_REQUEST['origin']);}
if ( empty ( $learnpath_id ) ) {            $learnpath_id           = intval($_REQUEST['learnpath_id']);}
if ( empty ( $learnpath_item_id ) ) {       $learnpath_item_id      = intval($_REQUEST['learnpath_item_id']);}
if ( empty ( $learnpath_item_view_id ) ) {  $learnpath_item_view_id = intval($_REQUEST['learnpath_item_view_id']);}
if ( empty ( $formSent ) ) {                $formSent               = $_REQUEST['formSent'];}
if ( empty ( $exerciseResult ) ) {          $exerciseResult         = $_SESSION['exerciseResult'];}
if ( empty ( $exerciseResultCoordinates)){  $exerciseResultCoordinates = $_SESSION['exerciseResultCoordinates'];}
if ( empty ( $questionId ) ) {              $questionId             = $_REQUEST['questionId'];}
if ( empty ( $choice ) ) {                  $choice                 = $_REQUEST['choice'];}
if ( empty ( $questionNum ) ) {             $questionNum            = $_REQUEST['questionNum'];}
if ( empty ( $nbrQuestions ) ) {            $nbrQuestions           = $_REQUEST['nbrQuestions'];}
if ( empty ( $questionList ) ) {            $questionList           = $_SESSION['questionList'];}
if ( empty ( $objExercise ) ) {             $objExercise            = $_SESSION['objExercise'];}
if ( empty ( $exerciseType ) ) {            $exerciseType           = $_REQUEST['exerciseType'];}

//@todo There should be some doc about this settings
$_configuration['live_exercise_tracking'] = false;
if ($_configuration['live_exercise_tracking']) define('ENABLED_LIVE_EXERCISE_TRACKING',1);

if ($_configuration['live_exercise_tracking'] && $exerciseType == 1){
	$_configuration['live_exercise_tracking'] = false;
}
$arrques = array();
$arrans  = array();

// set admin name as person who sends the results e-mail (lacks policy about whom should really send the results)

$query      = "SELECT user_id FROM $main_admin_table LIMIT 1"; //get all admins from admin table
$admin_id   = Database::result(Database::query($query),0,"user_id");
$uinfo      = api_get_user_info($admin_id);
$from       = $uinfo['mail'];
$from_name  = api_get_person_name($uinfo['firstname'], $uinfo['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
$str        = $_SERVER['REQUEST_URI'];
$url        = api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'&show=result';

// if the above variables are empty or incorrect, we don't have any result to show, so stop the script
if (!is_array($exerciseResult) || !is_array($questionList) || !is_object($objExercise)) {
    if ($debug) {error_log('Exit exercise result'); error_log('$exerciseResult: '.print_r($exerciseResult,1)); error_log('$questionList:'.print_r($questionList,1));error_log('$objExercise:'.print_r($objExercise,1));}
	header('Location: exercice.php');
	exit();
}

$gradebook = '';
if (isset($_SESSION['gradebook'])) {
	$gradebook=	$_SESSION['gradebook'];
}
if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array ('url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('ToolGradebook'));
}

$nameTools=get_lang('Exercice');

$interbreadcrumb[]=array("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
//$htmlHeadXtra[] = $objExercise->show_lp_javascript();

if ($origin != 'learnpath') {
	//so we are not in learnpath tool
	Display::display_header($nameTools,get_lang('Exercise'));
} else {
	header('Content-Type: text/html; charset='.api_get_system_encoding());
	$document_language = api_get_language_isocode();
	/* HTML HEADER  */
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css'; ?>" />
</head>

<body dir="<?php echo api_get_text_direction(); ?>">
<?php
}
//Hide results
$show_results     = false;
$show_only_score  = false;

if ($objExercise->results_disabled == 0) {
    $show_results = true;	
}

if ($objExercise->results_disabled == 2) {
    $show_only_score = true;
}

/* DISPLAY AND MAIN PROCESS */

// I'm in a preview mode as course admin. Display the action menu.
if (api_is_course_admin() && $origin != 'learnpath') {
	echo '<div class="actions">';
	echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'">'.Display::return_icon('back.png', get_lang('GoBackToQuestionList'), array(), 32).'</a>';
	echo '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->id.'">'.Display::return_icon('edit.png', get_lang('ModifyExercise'), array(), 32).'</a>';
	echo '</div>';
}

$exerciseTitle=text_filter($objExercise->selectTitle());
$feedback_type = $objExercise->feedbacktype;

//show exercise title
if($origin == 'learnpath') { ?>
	<form method="get" action="exercice.php?<?php echo api_get_cidreq() ?>">
	<input type="hidden" name="origin" value="<?php echo $origin; ?>" />
    <input type="hidden" name="learnpath_id" value="<?php echo $learnpath_id; ?>" />
    <input type="hidden" name="learnpath_item_id" value="<?php echo $learnpath_item_id; ?>" />
    <input type="hidden" name="learnpath_item_view_id" value="<?php echo $learnpath_item_view_id; ?>" />
<?php
}
$i = $totalScore = $totalWeighting=0;
if ($debug>0){error_log ("ExerciseResult: ".print_r($exerciseResult,1)); error_log("QuestionList: ".print_r($questionList,1));}


$safe_lp_id             = $learnpath_id==''?0:(int)$learnpath_id;
$safe_lp_item_id        = $learnpath_item_id==''?0:(int)$learnpath_item_id;
$safe_lp_item_view_id   = $learnpath_item_view_id==''?0:(int)$learnpath_item_view_id;

//We check if the user attempts before sending to the exercise_result.php                
if ($objExercise->selectAttempts() > 0) {
    $attempt_count = get_attempt_count(api_get_user_id(), $objExercise->id, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id);                
    if ($attempt_count >= $objExercise->selectAttempts()) {        
        Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttempts'), $exerciseTitle, $objExercise->selectAttempts()), false);
        if ($origin != 'learnpath') {
            //we are not in learnpath tool
            Display::display_footer();
        }                                      
        exit;
    }
}

// Create an empty exercise
if (api_is_allowed_to_session_edit()) {
    $exeId = create_event_exercice($objExercise->selectId());
}
$counter=0;

$user_info   = api_get_user_info(api_get_user_id());     
if ($show_results || $show_only_score) {
    echo $exercise_header = $objExercise->show_exercise_result_header(api_get_person_name($user_info['firstName'], $user_info['lastName']));
}
	     
// Loop over all question to show results for each of them, one by one
foreach ($questionList as $questionId) {
    // destruction of the Question object
	unset($objQuestionTmp);
	
	$counter++;
	// gets the student choice for this question
	$choice                = $exerciseResult[$questionId];
    
	// creates a temporary Question object
	$objQuestionTmp        = Question :: read($questionId);
	// initialize question information
	$questionName          = $objQuestionTmp->selectTitle();
	$questionDescription   = $objQuestionTmp->selectDescription();
	$questionWeighting     = $objQuestionTmp->selectWeighting();
	$answerType            = $objQuestionTmp->selectType();
	$quesId                = $objQuestionTmp->selectId();
	
	//this variable commes from exercise_submit_modal.php
	$hotspot_delineation_result = $_SESSION['hotspot_delineation_result'][$objExercise->selectId()][$quesId]; 
	
	if ($show_results) {
    	// show titles
    	if ($origin != 'learnpath') { 
    		echo $objQuestionTmp->return_header($objExercise->feedbacktype);
    		if ($answerType == HOT_SPOT) {
    			?>
    				<tr>
    					<td valign="top" colspan="2">
    						<table width="552" border="1" bordercolor="#A4A4A4" style="border-collapse: collapse;">
    							<tr>
    								<td width="152" valign="top">
    									<i><?php echo get_lang("CorrectAnswer"); ?></i><br /><br />
    								</td>
    								<td width="100" valign="top">
    									<i><?php echo get_lang('HotspotHit'); ?></i><br /><br />
    								</td>
    								<?php if ($objExercise->feedbacktype != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
    								<td width="300" valign="top">
    									<i><?php echo get_lang("Comment"); ?></i><br /><br />
    								</td>
    								<?php } else { ?>
    									<td>&nbsp;</td>
    								<?php } ?>
    							</tr>
    			<?php
    		}
    	}
	}

	// We're inside *one* question. Go through each possible answer for this question
	$result = $objExercise->manage_answer($exeId, $questionId, $choice,'exercise_result', $exerciseResultCoordinates, true, false, $show_results, $objExercise->selectPropagateNeg(), $hotspot_delineation_result);   	
    $totalScore        += $result['score'];    
    $totalWeighting    += $result['weight'];    
} // end foreach() block that loops over all questions


if ($origin != 'learnpath') {
    if ($show_results || $show_only_score) {
        echo '<div id="question_score">';
        echo get_lang('YourTotalScore')." ";	
        if ($objExercise->selectPropagateNeg() == 0 && $totalScore < 0) {
    	    $totalScore = 0;
        }     
        echo show_score($totalScore, $totalWeighting, false);	
        echo '</div>';
    }
    /* <button type="submit" class="save"><?php echo get_lang('Finish');?></button> */
}

// Tracking of results

//	Updates the empty exercise

$quizDuration = (!empty($_SESSION['quizStartTime']) ? time() - $_SESSION['quizStartTime'] : 0);

if (api_is_allowed_to_session_edit() ) {
	update_event_exercice($exeId, $objExercise->selectId(), $totalScore, $totalWeighting, api_get_session_id(), $safe_lp_id,$safe_lp_item_id,$safe_lp_item_view_id, $quizDuration);
}

if ($origin != 'learnpath') {
	Display :: display_normal_message(get_lang('ExerciseFinished').'<br /><a href="exercice.php" />'.get_lang('Back').'</a>',false);
} else {
	Display :: display_normal_message(get_lang('ExerciseFinished').'<br /><br />',false);

	$lp_mode =  $_SESSION['lp_mode'];
	$url = '../newscorm/lp_controller.php?cidReq='.api_get_course_id().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId.'&fb_type='.$objExercise->feedbacktype;
	$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';
	echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";
	//record the results in the learning path, using the SCORM interface (API)
	echo '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$totalScore.','.$totalWeighting.');</script>'."\n";
	echo '</body></html>';
}

if ($origin != 'learnpath') {
	//we are not in learnpath tool
	Display::display_footer();
}

// Email configuration settings
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
$user_info	= UserManager::get_user_info_by_id(api_get_user_id());

$firstName 	= $user_info['firstname'];
$lastName 	= $user_info['lastname'];
$mail 		= $user_info['email'];
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
if ($num>1) {
	$to = array();
	foreach($teachers as $teacher) {
		$to[] = $teacher['email'];
	}
} elseif($num>0) {
	foreach($teachers as $teacher) {
		$to = $teacher['email'];
	}
} else {
	//this is a problem (it means that there is no admin for this course)
}

// we are able to send emails to the teachers?
if (api_get_course_setting('email_alert_manager_on_new_quiz') == 1 ) {
	// only for "simple tests"
	if ($origin != 'learnpath') {
		//has a unique answer?
		$mycharset = api_get_system_encoding();
		$msg = '<html><head>
			<link rel="stylesheet" href="'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css" type="text/css">
			<meta content="text/html; charset='.$mycharset.'" http-equiv="content-type"></head>';

		if (count($arrques)>0) {
			$msg .= '
			<body><br />
			<p>'.get_lang('OpenQuestionsAttempted').' :
			</p>
			<p>'.get_lang('AttemptDetails').' : <br />
			</p>
			<table width="730" height="136" border="0" cellpadding="3" cellspacing="3">
								<tr>
			    <td width="229" valign="top"><h2>&nbsp;&nbsp;'.get_lang('CourseName').'</h2></td>
			    <td width="469" valign="top"><h2>#course#</h2></td>
			  </tr>
			  <tr>
			    <td width="229" valign="top" class="outerframe">&nbsp;&nbsp;'.get_lang('TestAttempted').'</span></td>
			    <td width="469" valign="top" class="outerframe">#exercise#</td>
			  </tr>
			  <tr>
			    <td valign="top">&nbsp;&nbsp;<span class="style10">'.get_lang('StudentName').'</span></td>
			    '.(api_is_western_name_order() ? '<td valign="top" >#firstName# #lastName#</td>' : '<td valign="top" >#lastName# #firstName#</td>').'
			  </tr>
			  <tr>
			    <td valign="top" >&nbsp;&nbsp;'.get_lang('StudentEmail').' </td>
			    <td valign="top"> #mail#</td>
			</tr></table>
			<p><br />'.get_lang('OpenQuestionsAttemptedAre').' :</p>
			 <table width="730" height="136" border="0" cellpadding="3" cellspacing="3">';

			for($i=0;$i<sizeof($arrques);$i++) {
				  $msg.='
					<tr>
				    <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;<span class="style10">'.get_lang('Question').'</span></td>
				    <td width="473" valign="top" bgcolor="#F3F3F3"><span class="style16"> #questionName#</span></td>
				  	</tr>
				  	<tr>
				    <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;<span class="style10">'.get_lang('Answer').' </span></td>
				    <td valign="top" bgcolor="#F3F3F3"><span class="style16"> #answer#</span></td>
				  	</tr>';
					$msg1= str_replace("#exercise#",$exerciseTitle,$msg);
					$msg= str_replace("#firstName#",$firstName,$msg1);
					$msg1= str_replace("#lastName#",$lastName,$msg);
					$msg= str_replace("#mail#",$mail,$msg1);
					$msg1= str_replace("#questionName#",$arrques[$i],$msg);
					$msg= str_replace("#answer#",$arrans[$i],$msg1);
					$msg1= str_replace("#i#",$i,$msg);
					$msg= str_replace("#course#",$courseName,$msg1);
			}
			$msg.='</table><br>
		 			<span class="style16">'.get_lang('ClickToCommentAndGiveFeedback').',<br />
					<a href="#url#">#url#</a></span></body></html>';

			$msg1= str_replace("#url#",$url,$msg);
			$mail_content = $msg1;

			$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
			$email_admin = api_get_setting('emailAdministrator');

			$subject = get_lang('OpenQuestionsAttempted');
			$result = @api_mail_html('', $to, $subject, $mail_content, $sender_name, $email_admin, array('charset'=>$mycharset));
		} else {
			$msg .= '<body>
			<p>'.get_lang('ExerciseAttempted').' <br />
	    	</p>
			<table width="730" height="136" border="0" cellpadding="3" cellspacing="3">
				<tr>
			    <td width="229" valign="top"><h2>&nbsp;&nbsp;'.get_lang('CourseName').'</h2></td>
			    <td width="469" valign="top"><h2>#course#</h2></td>
			  </tr>
			  <tr>
			    <td width="229" valign="top" class="outerframe">&nbsp;&nbsp;'.get_lang('TestAttempted').'</span></td>
			    <td width="469" valign="top" class="outerframe">#exercise#</td>
			  </tr>
			  <tr>
			    <td valign="top">&nbsp;&nbsp;<span class="style10">'.get_lang('StudentName').'</span></td>
			    '.(api_is_western_name_order() ? '<td valign="top" >#firstName# #lastName#</td>' : '<td valign="top" >#lastName# #firstName#</td>').'
			  </tr>
			  <tr>
			    <td valign="top" >&nbsp;&nbsp;'.get_lang('StudentEmail').' </td>
			    <td valign="top"> #mail#</td>
			</tr></table>';

			$msg= str_replace("#exercise#",$exerciseTitle,$msg);
			$msg= str_replace("#firstName#",$firstName,$msg);
			$msg= str_replace("#lastName#",$lastName,$msg);
			$msg= str_replace("#mail#",$mail,$msg);
			$msg= str_replace("#course#",$courseName,$msg);

			$msg.='<br />
		 			<span class="style16">'.get_lang('ClickToCommentAndGiveFeedback').',<br />
					<a href="#url#">#url#</a></span></body></html>';

			$msg= str_replace("#url#",$url,$msg);
			$mail_content = $msg;

			$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
			$email_admin = api_get_setting('emailAdministrator');

			$subject = get_lang('ExerciseAttempted');
			$result = @api_mail_html('', $to, $subject, $mail_content, $sender_name, $email_admin, array('charset'=>$mycharset));
		}
	}
}