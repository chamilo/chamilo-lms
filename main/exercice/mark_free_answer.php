<?php
/* For licensing terms, see /license.txt */

/**
*	Free answer marking script
* 	This script allows a course tutor to mark a student's free answer.
*	@package dokeos.exercise
* 	@author Yannick Warnier <yannick.warnier@dokeos.com>
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*
* 	@todo respect coding guidelines
*/

/*
		INIT SECTION
*/
// name of the language file that needs to be included
$language_file='exercice';

// name of the language file that needs to be included
include('../inc/global.inc.php');

// including additional libraries
include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

// answer types
define('UNIQUE_ANSWER',	1);
define('MULTIPLE_ANSWER',	2);
define('FILL_IN_BLANKS',	3);
define('MATCHING',		4);
define('FREE_ANSWER', 5);
define('MULTIPLE_ANSWER_COMBINATION', 9);




/** @todo use the Database:: functions */
$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          = Database::get_course_table(TABLE_QUIZ_ANSWER);

//debug param. 0: no display - 1: debug display
$debug=0;
if($debug>0){echo str_repeat('&nbsp;',0).'Entered exercise_result.php'."<br />\n";var_dump($_POST);}

// general parameters passed via POST/GET
$my_course_code = $_GET['cid'];
if(!empty($_REQUEST['exe'])){
	$my_exe = $_REQUEST['exe'];
}else{
	$my_exe = null;
}
if(!empty($_REQUEST['qst'])){
	$my_qst = $_REQUEST['qst'];
}else{
	$my_qst = null;
}
if(!empty($_REQUEST['usr'])){
	$my_usr = $_REQUEST['usr'];
}else{
	$my_usr = null;
}
if(!empty($_REQUEST['cidReq'])){
	$my_cid = $_REQUEST['cidReq'];
}else{
	$my_cid = null;
}
if(!empty($_POST['action'])){
	$action = $_POST['action'];
}else{
	$action = '';
}

if (empty($my_qst) or empty($my_usr) or empty($my_cid) or empty($my_exe)){
	header('Location: exercice.php');
	exit();
}

if(!$is_courseTutor)
{
	api_not_allowed();
}

$obj_question = Question :: read($my_qst);

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('ToolGradebook')
		);
}

$nameTools=get_lang('Exercice');

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));

$my_msg = 'No change.';

if ($action == 'mark') {
	if (!empty($_POST['score']) AND $_POST['score'] < $obj_question->selectWeighting() AND $_POST['score'] >= 0) {
		//mark the user mark into the database using something similar to the following function:

		$exercise_table = Database::get_statistic_table('track_e_exercices');
		#$tbl_learnpath_user = Database::get_course_table('learnpath_user');
		#global $origin, $tbl_learnpath_user, $learnpath_id, $learnpath_item_id;
		$sql = "SELECT * FROM $exercise_table
			WHERE exe_user_id = '".Database::escape_string($my_usr)."' AND exe_cours_id = '".Database::escape_string($my_cid)."' AND exe_exo_id = '".Database::escape_string($my_exe)."'
			ORDER BY exe_date DESC";
		#echo $sql;
		$res = Database::query($sql);
		if(Database::num_rows($res)>0){
			$row = Database::fetch_array($res);
			//@todo Check that just summing past score and the new free answer mark doesn't come up
			// with a score higher than the possible score for that exercise
			$my_score = $row['exe_result'] + $_POST['score'];
			$sql = "UPDATE $exercise_table SET exe_result = '$my_score'
				WHERE exe_id = '".$row['exe_id']."'";
			#echo $sql;
			$res = Database::query($sql);
			$my_msg = get_lang('MarkIsUpdated');
		}else{
			$my_score = $_POST['score'];
			$reallyNow = time();
			$sql = "INSERT INTO $exercise_table (
					   exe_user_id,
					   exe_cours_id,
					   exe_exo_id,
					   exe_result,
					   exe_weighting,
					   exe_date
					  ) VALUES (
					   '".Database::escape_string($my_usr)."',
					   '".Database::escape_string($my_cid)."',
					   '".Database::escape_string($my_exe)."',
					   '".Database::escape_string($my_score)."',
					   '".Database::escape_string($obj_question->selectWeighting())."',
					   FROM_UNIXTIME(".$reallyNow.")
					  )";
			#if ($origin == 'learnpath')
			#{
			#	if ($user_id == "NULL")
			#	{
			#		$user_id = '0';
			#	}
			#	$sql2 = "update $tbl_learnpath_user set score='$score' where (user_id=$user_id and learnpath_id='$learnpath_id' and learnpath_item_id='$learnpath_item_id')";
			#	$res2 = Database::query($sql2);
			#}
			$res = Database::query($sql);
			$my_msg = get_lang('MarkInserted');
		}
		//Database::query($sql);
		//return 0;		
	} else {
		$my_msg .= get_lang('TotalScoreTooBig');
	}
}

Display::display_header($nameTools,"Exercise");

// Display simple marking interface

// 1a - result of previous marking then exit suggestion
// 1b - user answer and marking box + submit button
$objAnswerTmp = new Answer();
$objAnswerTmp->selectAnswer($answerId);

if($action == 'mark'){
	echo $my_msg.'<br />
		<a href="exercice.php?cidReq='.$cidReq.'">'.get_lang('Back').'</a>';
} else {
	echo '<h2>'.$obj_question->question .':</h2>
		'.$obj_question->selectTitle().'<br /><br />
		'.get_lang('PleaseGiveAMark').
		"<form action='' method='POST'>\n"
		."<input type='hidden' name='exe' value='$my_exe'>\n"
		."<input type='hidden' name='usr' value='$my_usr'>\n"
		."<input type='hidden' name='cidReq' value='$my_cid'>\n"
		."<input type='hidden' name='action' value='mark'>\n"
		."<select name='score'>\n";
		for($i=0 ; $i<$obj_question->selectWeighting() ; $i++){
			echo '<option>'.$i.'</option>';
		}
		echo "</select>".
		"<input type='submit' name='submit' value='".get_lang('Ok')."'>\n"
		."</form>";
}
Display::display_footer();