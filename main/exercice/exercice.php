<?php
// $Id: exercice.php 21366 2009-06-11 06:51:37Z pcool $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
*	Exercise list: This script shows the list of exercises for administrators and students.
*	@package dokeos.exercise
*	@author Olivier Brouckaert, original author
*	@author Denes Nagy, HotPotatoes integration
*	@author Wolfgang Schneider, code/html cleanup
* 	@version $Id:exercice.php 12269 2007-05-03 14:17:37Z elixir_julian $
*/

// name of the language file that needs to be included
$language_file = 'exercice';

// including the global library
require_once '../inc/global.inc.php';
require_once '../gradebook/lib/be.inc.php';
// setting the tabs
$this_section = SECTION_COURSES;

// access control
api_protect_course_script(true);

$show = (isset ($_GET['show']) && $_GET['show'] == 'result') ? 'result' : 'test'; // moved down to fix bug: http://www.dokeos.com/forum/viewtopic.php?p=18609#18609

// including additional libraries
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';
require_once 'hotpotatoes.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'document.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'events.lib.inc.php';

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$is_allowedToEdit = api_is_allowed_to_edit();
$is_tutor = api_is_allowed_to_edit(true);
$is_tutor_course = api_is_course_tutor();
$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TBL_USER = Database :: get_main_table(TABLE_MAIN_USER);
$TBL_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT);
$TBL_ITEM_PROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
$TBL_EXERCICE_ANSWER = Database :: get_course_table(TABLE_QUIZ_ANSWER);
$TBL_EXERCICE_QUESTION = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES = Database :: get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS = Database :: get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCICES = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_HOTPOTATOES = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
$TBL_TRACK_ATTEMPT = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
$TBL_LP_ITEM = Database :: get_course_table(TABLE_LP_ITEM);
$TBL_LP_VIEW = Database :: get_course_table(TABLE_LP_VIEW);

// document path
$documentPath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . "/document";
// picture path
$picturePath = $documentPath . '/images';
// audio path
$audioPath = $documentPath . '/audio';

// hotpotatoes
$uploadPath = DIR_HOTPOTATOES; //defined in main_api
$exercicePath = api_get_self();
$exfile = explode('/', $exercicePath);
$exfile = strtolower($exfile[sizeof($exfile) - 1]);
$exercicePath = substr($exercicePath, 0, strpos($exercicePath, $exfile));
$exercicePath = $exercicePath . "exercice.php";

// maximum number of exercises on a same page
$limitExPage = 50;

// Clear the exercise session
if (isset ($_SESSION['objExercise'])) {
	api_session_unregister('objExercise');
}
if (isset ($_SESSION['objQuestion'])) {
	api_session_unregister('objQuestion');
}
if (isset ($_SESSION['objAnswer'])) {
	api_session_unregister('objAnswer');
}
if (isset ($_SESSION['questionList'])) {
	api_session_unregister('questionList');
}
if (isset ($_SESSION['exerciseResult'])) {
	api_session_unregister('exerciseResult');
}

//general POST/GET/SESSION/COOKIES parameters recovery
if (empty ($origin)) {
	$origin = Security::remove_XSS($_REQUEST['origin']);
}
if (empty ($choice)) {
	$choice = $_REQUEST['choice'];
}
if (empty ($hpchoice)) {
	$hpchoice = $_REQUEST['hpchoice'];
}
if (empty ($exerciseId)) {
	$exerciseId = Database :: escape_string($_REQUEST['exerciseId']);
}
if (empty ($file)) {
	$file = Database :: escape_string($_REQUEST['file']);
}
$learnpath_id = Database :: escape_string(Security::remove_XSS($_REQUEST['learnpath_id']));
$learnpath_item_id = Database :: escape_string(Security::remove_XSS($_REQUEST['learnpath_item_id']));
$page = Database :: escape_string($_REQUEST['page']);

if ($origin == 'learnpath') {
	$show = 'result';
}

if ($_GET['delete'] == 'delete' && ($is_allowedToEdit || api_is_coach()) && !empty ($_GET['did']) && $_GET['did'] == strval(intval($_GET['did']))) {
	$sql = 'DELETE FROM ' . Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES) . ' WHERE exe_id = ' . $_GET['did']; //_GET[did] filtered by entry condition
	api_sql_query($sql, __FILE__, __LINE__);
	$filter=Security::remove_XSS($_GET['filter']);
	header('Location: exercice.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&show=result&filter=' . $filter . '');
	exit;
}

if ($show == 'result' && $_REQUEST['comments'] == 'update' && ($is_allowedToEdit || $is_tutor) && $_GET['exeid']== strval(intval($_GET['exeid']))) {
	$id = $_GET['exeid']; //filtered by post-condition
	$emailid = $_GET['emailid'];
	$test = $_GET['test'];
	$from = $_SESSION['_user']['mail'];
	$from_name = $_SESSION['_user']['firstName'] . " " . $_SESSION['_user']['lastName'];
	$url = api_get_path(WEB_CODE_PATH) . 'exercice/exercice.php?' . api_get_cidreq() . '&show=result';
	$TBL_RECORDING = Database :: get_statistic_table('track_e_attempt_recording');
	$total_weighting = $_REQUEST['totalWeighting'];
	
	$my_post_info=array(); 
	$post_content_id=array();
	$comments_exist=false;
	foreach ($_POST as $key_index=>$key_value) {
		$my_post_info=explode('_',$key_index);
		$post_content_id[]=$my_post_info[1];
		if ($my_post_info[0]=='comments') {
			$comments_exist=true;
		}
	}

	$loop_in_track=($comments_exist===true) ? (count($_POST)/2) : count($_POST);
	$array_content_id_exe=array();
	if ($comments_exist===true) {
		$array_content_id_exe=array_slice($post_content_id,$loop_in_track);
	} else {
		$array_content_id_exe=$post_content_id;
	}

	for ($i=0;$i<$loop_in_track;$i++) {
		
		$my_marks=Database::escape_string($_POST['marks_'.$array_content_id_exe[$i]]);
		$contain_comments=Database::escape_string($_POST['comments_'.$array_content_id_exe[$i]]);
		
		if (isset($contain_comments)) {
			$my_comments=Database::escape_string($_POST['comments_'.$array_content_id_exe[$i]]);
		} else {
			$my_comments='';
		}
			$my_questionid=$array_content_id_exe[$i];
			$sql = "SELECT question from $TBL_QUESTIONS WHERE id = '$my_questionid'";
			$result =api_sql_query($sql, __FILE__, __LINE__);
			$ques_name = Database::result($result,0,"question");

			$query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '$my_marks',teacher_comment = '$my_comments'
					  WHERE question_id = '".$my_questionid."'
					  AND exe_id='".$id."'";
			api_sql_query($query, __FILE__, __LINE__);
			

			$qry = 'SELECT sum(marks) as tot
					FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.intval($id).'
					GROUP BY question_id';

			$res = api_sql_query($qry,__FILE__,__LINE__);
			$tot = Database::result($res,0,'tot');
			//updating also the total weight 
			$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '".Database::escape_string($tot)."', exe_weighting = '".Database::escape_string($total_weighting)."'
						 WHERE exe_Id='".Database::escape_string($id)."'";
			api_sql_query($totquery, __FILE__, __LINE__);
			$recording_changes = 'INSERT INTO '.$TBL_RECORDING.' ' .
					'(exe_id,
					question_id,
					marks,
					insert_date,
					author,
					teacher_comment)
					VALUES
					('."'$id','".$my_questionid."','$my_marks','".date('Y-m-d H:i:s')."','".api_get_user_id()."'".',"'.$my_comments.'")';
			api_sql_query($recording_changes, __FILE__, __LINE__);	
	
			
	}
	$post_content_id=array();
	$array_content_id_exe=array();
	/*foreach ($_POST as $key => $v) {
		$keyexp = explode('_', $key);

		$id = Database :: escape_string($id);
		$v = Database :: escape_string($v);
		$my_questionid = Database :: escape_string($keyexp[1]);

		if ($keyexp[0] == "marks") {
			$sql = "SELECT question from $TBL_QUESTIONS WHERE id = '$my_questionid'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$ques_name = Database :: result($result, 0, "question");

			$query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '" . $v . "'
								  WHERE question_id = '" . $my_questionid . "'
								  AND exe_id='" . $id . "'";
			api_sql_query($query, __FILE__, __LINE__);

			$qry = 'SELECT sum(marks) as tot
								FROM ' . $TBL_TRACK_ATTEMPT . ' WHERE exe_id = ' . intval($id) . '
								GROUP BY question_id';

			$res = api_sql_query($qry, __FILE__, __LINE__);
			$tot = Database :: result($res, 0, 'tot');
			//updating also the total weight 
			$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . Database :: escape_string($tot) . "', exe_weighting = '" . Database :: escape_string($total_weighting) . "'
									 WHERE exe_Id='" . Database :: escape_string($id) . "'";

			api_sql_query($totquery, __FILE__, __LINE__);

			$recording_changes = 'INSERT INTO ' . $TBL_RECORDING . ' ' .
			'(exe_id,
								question_id,
								marks,
								insert_date,
								author)
								VALUES
								(' . "'$id','" . $my_questionid . "','$v','" . date('Y-m-d H:i:s') . "','" . api_get_user_id() . "'" . ')';
			api_sql_query($recording_changes, __FILE__, __LINE__);
		} else {
			$query = "UPDATE $TBL_TRACK_ATTEMPT SET teacher_comment = '" . $v . "'
					  			WHERE question_id = '" . $my_questionid . "'
					  			AND exe_id = '" . $id . "'";
			api_sql_query($query, __FILE__, __LINE__);

			$recording_changes = 'INSERT INTO ' . $TBL_RECORDING . ' ' .
			'(exe_id,
								question_id,
								teacher_comment,
								insert_date,
								author)
								VALUES
								(' . "'$id','" . $my_questionid . "','$v','" . date('Y-m-d H:i:s') . "','" . api_get_user_id() . "'" . ')';
			api_sql_query($recording_changes, __FILE__, __LINE__);
		}
	}*/

	$qry = 'SELECT DISTINCT question_id, marks
			FROM ' . $TBL_TRACK_ATTEMPT . ' where exe_id = ' . intval($id) . '
			GROUP BY question_id';

	$res = api_sql_query($qry, __FILE__, __LINE__);
	$tot = 0;
	while ($row = Database :: fetch_array($res, 'ASSOC')) {
		$tot += $row['marks'];
	}

	$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . Database :: escape_string($tot) . "' WHERE exe_Id='" . Database :: escape_string($id) . "'";

	//search items 
	if (isset($_POST['my_exe_exo_id']) && isset($_POST['student_id'])) {
		$sql_lp='SELECT li.id as lp_item_id,li.lp_id,li.item_type,li.path,liv.id AS lp_view_id,liv.user_id,max(liv.view_count) AS view_count FROM '.$TBL_LP_ITEM.' li 
		INNER JOIN '.$TBL_LP_VIEW.' liv ON li.lp_id=liv.lp_id WHERE li.path="'.Database::escape_string(Security::remove_XSS($_POST['my_exe_exo_id'])).'" AND li.item_type="quiz" AND user_id="'.Database::escape_string(Security::remove_XSS($_POST['student_id'])).'" ';
		$rs_lp=Database::query($sql_lp,__FILE__,__LINE__);
		if (!($rs_lp===false)) {
			$row_lp=Database::fetch_array($rs_lp);	
			//update score in learnig path
			$sql_lp_view='UPDATE '.$TBL_LP_ITEM_VIEW.' liv SET score ="'.$tot.'" WHERE liv.lp_item_id="'.(int)$row_lp['lp_item_id'].'" AND liv.lp_view_id="'.(int)$row_lp['lp_view_id'].'" AND liv.view_count="'.(int)$row_lp['view_count'].'" ;';		
			$rs_lp_view=Database::query($sql_lp_view,__FILE__, __LINE__);
		}
	}
	Database::query($totquery, __FILE__, __LINE__);
	
	$subject = get_lang('ExamSheetVCC');
	$htmlmessage = '<html>' .
	'<head>' .
	'<style type="text/css">' .
	'<!--' .
	'.body{' .
	'font-family: Verdana, Arial, Helvetica, sans-serif;' .
	'font-weight: Normal;' .
	'color: #000000;' .
	'}' .
	'.style8 {font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; color: #006699; }' .
	'.style10 {' .
	'	font-family: Verdana, Arial, Helvetica, sans-serif;' .
	'	font-size: 12px;' .
	'	font-weight: bold;' .
	'}' .
	'.style16 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; }' .
	'-->' .
	'</style>' .
	'</head>' .
	'<body>' .
	'<div>' .
	'  <p>' . get_lang('DearStudentEmailIntroduction') . '</p>' .
	'  <p class="style10"> ' . get_lang('AttemptVCC') . ' </p>' .
	'  <table width="417">' .
	'    <tr>' .
	'      <td width="229" valign="top" bgcolor="E5EDF8">&nbsp;&nbsp;<span class="style10">' . get_lang('Question') . '</span></td>' .
	'      <td width="469" valign="top" bgcolor="#F3F3F3"><span class="style16">#ques_name#</span></td>' .
	'    </tr>' .
	'    <tr>' .
	'      <td width="229" valign="top" bgcolor="E5EDF8">&nbsp;&nbsp;<span class="style10">' . get_lang('Exercice') . '</span></td>' .
	'       <td width="469" valign="top" bgcolor="#F3F3F3"><span class="style16">#test#</span></td>' .
	'    </tr>' .
	'  </table>' .
	'  <p>' . get_lang('ClickLinkToViewComment') . ' <a href="#url#">#url#</a><br />' .
	'    <br />' .
	'  ' . get_lang('Regards') . ' </p>' .
	'  </div>' .
	'  </body>' .
	'  </html>';
	$message = '<p>' . sprintf(get_lang('AttemptVCCLong'), Security::remove_XSS($test)) . ' <A href="#url#">#url#</A></p><br />';
	$mess = str_replace("#test#", Security::remove_XSS($test), $message);
	//$message= str_replace("#ques_name#",$ques_name,$mess);
	$message = str_replace("#url#", $url, $mess);
	$mess = $message;
	$headers = " MIME-Version: 1.0 \r\n";
	$headers .= "User-Agent: Dokeos/1.6";
	$headers .= "Content-Transfer-Encoding: 7bit";
	$headers .= 'From: ' . $from_name . ' <' . $from . '>' . "\r\n";
	$headers = "From:$from_name\r\nReply-to: $to\r\nContent-type: text/html; charset=" . ($charset ? $charset : 'ISO-8859-15');
	//mail($emailid, $subject, $mess,$headers);

	api_mail_html($emailid, $emailid, $subject, $mess, $from_name, $from);
	if (in_array($origin, array (
			'tracking_course',
			'user_course'
		))) {
		if (isset ($_POST['lp_item_id']) && isset ($_POST['lp_item_view_id']) && isset ($_POST['student_id']) && isset ($_POST['total_score']) && isset ($_POST['total_time']) && isset ($_POST['totalWeighting'])) {
			$lp_item_id = $_POST['lp_item_id'];
			$lp_item_view_id = $_POST['lp_item_view_id'];
			$student_id = $_POST['student_id'];
			$totalWeighting = $_POST['totalWeighting'];

			if ($lp_item_id == strval(intval($lp_item_id)) && $lp_item_view_id == strval(intval($lp_item_view_id)) && $student_id == strval(intval($student_id))) {
				$score = Database :: escape_string($_POST['total_score']);
				$total_time = Database :: escape_string($_POST['total_time']);
				$lp_item_id = Database :: escape_string($lp_item_id);
				$lp_item_view_id = Database :: escape_string($lp_item_view_id);
				$student_id = Database :: escape_string($student_id);
				$totalWeighting = Database :: escape_string($totalWeighting);
				// get max view_count from lp_item_view    			
				/*$sql = "SELECT MAX(view_count) FROM $TBL_LP_ITEM_VIEW WHERE lp_item_id = '" . (int) $lp_item_view_id . "'
				    					AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . (int) $student_id . "' and lp_id='" . (int) $lp_item_id . "')";
				$res_max_view_count = api_sql_query($sql, __FILE__, __LINE__);
				$row_max_view_count = Database :: fetch_row($res_max_view_count);
				$max_view_count = (int) $row_max_view_count[0];

				// update score and total_time from last attempt when you qualify the exercise in Learning path detail 			
				$sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '" . (float) $score . "',total_time = '" . (int) $total_time . "' WHERE lp_item_id = '" . (int) $lp_item_view_id . "'
				    					AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . (int) $student_id . "' and lp_id='" . (int) $lp_item_id . "') AND view_count = '$max_view_count'";
				api_sql_query($sql_update_score, __FILE__, __LINE__);

				// update score and total_time from last attempt when you qualify the exercise in Learning path detail 			
				$sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '" . (float) $score . "',total_time = '" . (int) $total_time . "' WHERE lp_item_id = '" . (int) $lp_item_view_id . "'
				    					AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . (int) $student_id . "' and lp_id='" . (int) $lp_item_id . "') AND view_count = '$max_view_count'";
				api_sql_query($sql_update_score, __FILE__, __LINE__);*/

				// update max_score from a exercise in lp
				$sql_update_max_score = "UPDATE $TBL_LP_ITEM SET max_score = '" . (float) $totalWeighting . "'  WHERE id = '" . (int) $lp_item_view_id . "'";

				api_sql_query($sql_update_max_score, __FILE__, __LINE__);

			}
		}
		if ($origin == 'tracking_course' && !empty($_POST['lp_item_id'])) {
			//Redirect to the course detail in lp		
			header('location: ../mySpace/lp_tracking.php?course=' . Security :: remove_XSS($_GET['course']) . '&origin=' . $origin . '&lp_id=' . Security :: remove_XSS($_POST['lp_item_id']) . '&student_id=' . Security :: remove_XSS($_GET['student']));
		} else {
			//Redirect to the reporting		
			header('location: ../mySpace/myStudents.php?origin=' . $origin . '&student=' . Security :: remove_XSS($_GET['student']) . '&details=true&course=' . Security :: remove_XSS($_GET['course']));
		}
	}
}

if (!empty($_GET['gradebook']) && $_GET['gradebook']=='view' ) {
	$_SESSION['gradebook']=Security::remove_XSS($_GET['gradebook']);
	$gradebook=	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
	unset($_SESSION['gradebook']);
	$gradebook=	'';
} 

if (!empty($gradebook) && $gradebook=='view') {	
	$interbreadcrumb[] = array (
		'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
		'name' => get_lang('Gradebook')
	);
}

if ($show != 'result') {
	$nameTools = get_lang('Exercices');
} else {
	if ($is_allowedToEdit || $is_tutor) {
		$nameTools = get_lang('StudentScore');
		$interbreadcrumb[] = array (
			"url" => "exercice.php?gradebook=$gradebook",
			"name" => get_lang('Exercices')
		);
	} else {
		$nameTools = get_lang('YourScore');
		$interbreadcrumb[] = array (
			"url" => "exercice.php?gradebook=$gradebook",
			"name" => get_lang('Exercices')
		);
	}
}

// need functions of statsutils lib to display previous exercices scores
include_once (api_get_path(LIBRARY_PATH) . 'statsUtils.lib.inc.php');

if ($is_allowedToEdit && !empty ($choice) && $choice == 'exportqti2') {
	require_once ('export/qti2/qti2_export.php');
	$export = export_exercise($exerciseId, true);

	require_once (api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php');
	$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	$temp_dir_short = uniqid();
	$temp_zip_dir = $archive_path . "/" . $temp_dir_short;
	if (!is_dir($temp_zip_dir))
		mkdir($temp_zip_dir);
	$temp_zip_file = $temp_zip_dir . "/" . md5(time()) . ".zip";
	$temp_xml_file = $temp_zip_dir . "/qti2export_" . $exerciseId . '.xml';
	file_put_contents($temp_xml_file, $export);
	$zip_folder = new PclZip($temp_zip_file);
	$zip_folder->add($temp_xml_file, PCLZIP_OPT_REMOVE_ALL_PATH);
	$name = 'qti2_export_' . $exerciseId . '.zip';

	//DocumentManager::string_send_for_download($export,true,'qti2export_'.$exerciseId.'.xml');
	DocumentManager :: file_send_for_download($temp_zip_file, true, $name);
	unlink($temp_zip_file);
	unlink($temp_xml_file);
	rmdir($temp_zip_dir);
	exit (); //otherwise following clicks may become buggy
}
if (!empty ($_POST['export_user_fields'])) {
	switch ($_POST['export_user_fields']) {
		case 'export_user_fields' :
			$_SESSION['export_user_fields'] = true;
			break;
		case 'do_not_export_user_fields' :
		default :
			$_SESSION['export_user_fields'] = false;
			break;
	}
}
if (!empty ($_POST['export_report']) && $_POST['export_report'] == 'export_report') {
	if (api_is_platform_admin() || api_is_course_admin() || api_is_course_tutor() || api_is_course_coach()) {
		$user_id = null;
		if (empty ($_SESSION['export_user_fields']))
			$_SESSION['export_user_fields'] = false;
		if (!$is_allowedToEdit and !$is_tutor) {
			$user_id = api_get_user_id();
		}
		require_once ('exercise_result.class.php');
		switch ($_POST['export_format']) {
			case 'xls' :
				$export = new ExerciseResult();
				$export->exportCompleteReportXLS($documentPath, $user_id, $_SESSION['export_user_fields']);
				exit;
				break;
			case 'csv' :
			default :
				$export = new ExerciseResult();
				$export->exportCompleteReportCSV($documentPath, $user_id, $_SESSION['export_user_fields']);
				exit;
				break;
		}
	} else {
		api_not_allowed(true);
	}
}

if ($origin != 'learnpath') {
	//so we are not in learnpath tool
	Display :: display_header($nameTools, "Exercise");
	if (isset ($_GET['message'])) {
		if (in_array($_GET['message'], array (
				'ExerciseEdited'
			))) {
			Display :: display_confirmation_message(get_lang($_GET['message']));
		}
	}

} else {
	echo '<link rel="stylesheet" type="text/css" href="' . api_get_path(WEB_CODE_PATH) . 'css/default.css"/>';
}

// used for stats
include_once (api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');

event_access_tool(TOOL_QUIZ);

// Tool introduction
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';
Display :: display_introduction_section(TOOL_QUIZ);
$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

// selects $limitExPage exercises at the same time
$from = $page * $limitExPage;
$sql = "SELECT count(id) FROM $TBL_EXERCICES";
$res = api_sql_query($sql, __FILE__, __LINE__);
list ($nbrexerc) = Database :: fetch_array($res);

HotPotGCt($documentPath, 1, $_user['user_id']);
$tbl_grade_link = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
// only for administrator

if ($is_allowedToEdit) {

	if (!empty ($choice)) {
		// construction of Exercise

		$objExerciseTmp = new Exercise();

		if ($objExerciseTmp->read($exerciseId)) {
			switch ($choice) {
				case 'delete' : // deletes an exercise
					$objExerciseTmp->delete();

					//delete link of exercise of gradebook tool
					$sql = 'SELECT gl.id FROM ' . $tbl_grade_link . ' gl WHERE gl.type="1" AND gl.ref_id="' . $exerciseId . '";';
					$result = api_sql_query($sql, __FILE__, __LINE__);
					$row = Database :: fetch_array($result, 'ASSOC');

					$link = LinkFactory :: load($row['id']);
					if ($link[0] != null) {
						$link[0]->delete();
					}
					Display :: display_confirmation_message(get_lang('ExerciseDeleted'));
					break;
				case 'enable' : // enables an exercise
					$objExerciseTmp->enable();
					$objExerciseTmp->save();
					// "WHAT'S NEW" notification: update table item_property (previously last_tooledit)								
					Display :: display_confirmation_message(get_lang('VisibilityChanged'));

					break;
				case 'disable' : // disables an exercise
					$objExerciseTmp->disable();
					$objExerciseTmp->save();
					Display :: display_confirmation_message(get_lang('VisibilityChanged'));
					break;
				case 'disable_results' : //disable the results for the learners
					$objExerciseTmp->disable_results();
					$objExerciseTmp->save();
					Display :: display_confirmation_message(get_lang('ResultsDisabled'));
					break;
				case 'enable_results' : //disable the results for the learners
					$objExerciseTmp->enable_results();
					$objExerciseTmp->save();
					Display :: display_confirmation_message(get_lang('ResultsEnabled'));
					break;
			}
		}

		// destruction of Exercise
		unset ($objExerciseTmp);
	}

	if (!empty ($hpchoice)) {
		switch ($hpchoice) {
			case 'delete' : // deletes an exercise
				$imgparams = array ();
				$imgcount = 0;
				GetImgParams($file, $documentPath, $imgparams, $imgcount);
				$fld = GetFolderName($file);
				for ($i = 0; $i < $imgcount; $i++) {
					my_delete($documentPath . $uploadPath . "/" . $fld . "/" . $imgparams[$i]);
					update_db_info("delete", $uploadPath . "/" . $fld . "/" . $imgparams[$i]);
				}

				if (my_delete($documentPath . $file)) {
					update_db_info("delete", $file);
				}
				my_delete($documentPath . $uploadPath . "/" . $fld . "/");
				break;
			case 'enable' : // enables an exercise
				$newVisibilityStatus = "1"; //"visible"
				$query = "SELECT id FROM $TBL_DOCUMENT WHERE path='" . Database :: escape_string($file) . "'";
				$res = api_sql_query($query, __FILE__, __LINE__);
				$row = Database :: fetch_array($res, 'ASSOC');
				api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'visible', $_user['user_id']);
				//$dialogBox = get_lang('ViMod');

				break;
			case 'disable' : // disables an exercise
				$newVisibilityStatus = "0"; //"invisible"
				$query = "SELECT id FROM $TBL_DOCUMENT WHERE path='" . Database :: escape_string($file) . "'";
				$res = api_sql_query($query, __FILE__, __LINE__);
				$row = Database :: fetch_array($res, 'ASSOC');
				api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'invisible', $_user['user_id']);
				#$query = "UPDATE $TBL_DOCUMENT SET visibility='$newVisibilityStatus' WHERE path=\"".$file."\""; //added by Toon
				#api_sql_query($query,__FILE__,__LINE__);
				//$dialogBox = get_lang('ViMod');
				break;
			default :
				break;
		}
	}

	if ($show == 'test') {
		$sql = "SELECT id,title,type,active,description, results_disabled FROM $TBL_EXERCICES WHERE active<>'-1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}

}
elseif ($show == 'test') { // only for students //fin
	$sql = "SELECT id,title,type,description, results_disabled FROM $TBL_EXERCICES WHERE active='1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
	$result = api_sql_query($sql, __FILE__, __LINE__);
}

// the actions
echo '<div class="actions">';

// display the next and previous link if needed
$from = $page * $limitExPage;
$sql = "SELECT count(id) FROM $TBL_EXERCICES";
$res = api_sql_query($sql, __FILE__, __LINE__);
list ($nbrexerc) = Database :: fetch_array($res);
HotPotGCt($documentPath, 1, $_user['user_id']);
// only for administrator
if ($is_allowedToEdit) {
	if ($show == 'test') {
		$sql = "SELECT id,title,type,active,description, results_disabled FROM $TBL_EXERCICES WHERE active<>'-1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}
}
elseif ($show == 'test') { // only for students
	$sql = "SELECT id,title,type,description, results_disabled FROM $TBL_EXERCICES WHERE active='1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
	$result = api_sql_query($sql, __FILE__, __LINE__);
}
if ($show == 'test') {
	$nbrExercises = Database :: num_rows($result);

	//get HotPotatoes files (active and inactive)
	$res = api_sql_query("SELECT *
						FROM $TBL_DOCUMENT
						WHERE
						path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'", __FILE__, __LINE__);
	$nbrTests = Database :: num_rows($res);
	$res = api_sql_query("SELECT *
						FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
						WHERE  d.id = ip.ref
						AND ip.tool = '" . TOOL_DOCUMENT . "'
						AND d.path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'
						AND ip.visibility='1'", __FILE__, __LINE__);
	$nbrActiveTests = Database :: num_rows($res);

	if ($is_allowedToEdit) {
		//if user is allowed to edit, also show hidden HP tests
		$nbrHpTests = $nbrTests;
	} else {
		$nbrHpTests = $nbrActiveTests;
	}
	$nbrNextTests = $nbrexerc - $nbrHpTests - (($page * $limitExPage));

	echo '<span style="float:right">';
	//show pages navigation link for previous page
	if ($page) {
		echo "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page -1) . "\">" . Display :: return_icon('previous.gif') . get_lang("PreviousPage") . "</a> | ";
	}
	elseif ($nbrExercises + $nbrNextTests > $limitExPage) {
		echo Display :: return_icon('previous.gif') . get_lang('PreviousPage') . " | ";
	}

	//show pages navigation link for previous page
	if ($nbrExercises + $nbrNextTests > $limitExPage) {
		echo "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page +1) . "\">" . get_lang("NextPage") . Display :: return_icon('next.gif') . "</a>";
	}
	elseif ($page) {
		echo get_lang("NextPage") . Display :: return_icon('next.gif');
	}
	echo '</span>';
}

if ($_configuration['tracking_enabled']) {
	if ($show == 'result') {
		/*if (!function_exists('make_select')) 
		{
			function make_select($name,$values,$checked='')
			{
				$output .= '<select name="'.$name.'" >';
					foreach($values as $key => $value) 
					{
						//$output .= '<option value="'.$key.'" '.(($checked==$key)?'selected="selected"':'').'>'.$value.'</option>';
					}
					$output .= '</select>';
				return $output;
			}
		}*/

		/*if (!function_exists('make_select_users')) 
		{
			function make_select_users($name,$values,$checked='')
			{
				$output .= '<select name="'.$name.'" >';
				$output .= '<option value="all">'.get_lang('EveryBody').'</option>';
					foreach($values as $key => $value) 
					{
						$output .= '<option value="'.$key.'" '.(($checked==$key)?'selected="selected"':'').'>'.$value.'</option>';
					}
					$output .= '</select>';
					return $output;
			}
		}*/

		if (api_is_allowed_to_edit()) {
			if (!$_GET['filter']) {
				$filter_by_not_revised = true;
				$filter = 1;
			} else {
				$filter=Security::remove_XSS($_GET['filter']);
			}
			$filter = (int) $_GET['filter'];

			switch ($filter) {
				case 1 :
					$filter_by_not_revised = true;
					break;
				case 2 :
					$filter_by_revised = true;
					break;
				default :
					null;
			}
			if ($_GET['filter'] == '1' or !isset ($_GET['filter']) or $_GET['filter'] == 0 ) {
				$view_result = '<a href="' . api_get_self() . '?cidReq=' . api_get_course_id() . '&show=result&filter=2&gradebook='.$gradebook.'" >'.Display :: return_icon('check.gif', get_lang('ShowCorrectedOnly')).get_lang('ShowCorrectedOnly').'</a>'; 
			} else {
				$view_result = '<a href="' .api_get_self() . '?cidReq=' . api_get_course_id() . '&show=result&filter=1&gradebook='.$gradebook.'" >'.Display :: return_icon('un_check.gif', get_lang('ShowUnCorrectedOnly')).get_lang('ShowUnCorrectedOnly').'</a>'; 
			}
			//$form_filter = '<form method="post" action="'.api_get_self().'?cidReq='.api_get_course_id().'&show=result">';
			//$form_filter .= make_select('filter',array(1=>get_lang('FilterByNotRevised'),2=>get_lang('FilterByRevised')),$filter);
			//$form_filter .= '<button class="save" type="submit">'.get_lang('FilterExercices').'</button></form>';
			echo $view_result;
		}
	}
	/*if (api_is_allowed_to_edit()) 
	{
		$user_count = count($user_list_name);
		if ($user_count >0 ) {
			$form_filter = '<form method="post" action="'.api_get_self().'?cidReq='.api_get_course_id().'&show=result">';
			$user_list_for_select =array();
			for ($i=0;$i<$user_count;$i++) {
				$user_list_for_select[$user_list_id[$i]]=$user_list_name[$i];
			}
			$form_filter .= make_select_users('filter_by_user',$user_list_for_select,(int)$_REQUEST['filter_by_user']);
			$form_filter .= '<input type="hidden" name="filter" value="'.$filter.'">';
			$form_filter .= '<input type="submit" value="'.get_lang('FilterExercicesByUsers').'"></form>';
			echo $form_filter;
		}
	}	*/
}

if (($is_allowedToEdit) and ($origin != 'learnpath')) {
	if ($_GET['show'] != 'result') {
		echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display :: return_icon('new_test.gif', get_lang('NewEx')) . get_lang('NewEx') . '</a>';
		echo '<a href="question_create.php?' . api_get_cidreq() . '">' . Display :: return_icon('question_add.gif', get_lang('AddQuestion')) . get_lang('AddQuestion') . '</a>';
		echo '<a href="hotpotatoes.php">' . Display :: return_icon('jqz.gif', get_lang('ImportHotPotatoesQuiz')) . get_lang('ImportHotPotatoesQuiz') . '</a>';
		echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=result') . '">' . Display :: return_icon('show_test_results.gif', get_lang('Results')) . get_lang('Results') . '</a>';
	}

	// the actions for the statistics
	if ($show == 'result') {
		// the form
		if (api_is_platform_admin() || api_is_course_admin() || api_is_course_tutor() || api_is_course_coach()) {
			if ($_SESSION['export_user_fields'] == true) {
				$alt = get_lang('ExportWithUserFields');
				$extra_user_fields = '<input type="hidden" name="export_user_fields" value="export_user_fields">';
			} else {
				$alt = get_lang('ExportWithoutUserFields');
				$extra_user_fields = '<input type="hidden" name="export_user_fields" value="do_not_export_user_fields">';
			}
			//echo '<a href="#" onclick="document.form1a.submit();">'.Display::return_icon('excel.gif',get_lang('ExportAsCSV')).get_lang('ExportAsCSV').'</a>';
			echo '<a href="#" onclick="document.form1b.submit();">' . Display :: return_icon('excel.gif', get_lang('ExportAsXLS')) . get_lang('ExportAsXLS') . '</a>';
			//echo '<a href="#" onclick="document.form1c.submit();">'.Display::return_icon('synthese_view.gif',$alt).$alt.'</a>';			
			echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=test') . '">' . Display :: return_icon('quiz.gif', get_lang('BackToExercisesList')) . get_lang('BackToExercisesList') . '</a>';
			echo '<form id="form1a" name="form1a" method="post" action="' . api_get_self() . '?show=' . Security :: remove_XSS($_GET['show']) . '">';
			echo '<input type="hidden" name="export_report" value="export_report">';
			echo '<input type="hidden" name="export_format" value="csv">';
			echo '</form>';
			echo '<form id="form1b" name="form1b" method="post" action="' . api_get_self() . '?show=' . Security :: remove_XSS($_GET['show']) . '">';
			echo '<input type="hidden" name="export_report" value="export_report">';
			echo '<input type="hidden" name="export_format" value="xls">';
			echo '</form>';
			//echo '<form id="form1c" name="form1c" method="post" action="'.api_get_self().'?show='.Security::remove_XSS($_GET['show']).'">';
			//echo $extra_user_fields;
			//echo '</form>';
		}
	}
} else {
	//the student view
	if ($show == 'result') {
		echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=test') . '">' . Display :: return_icon('quiz.gif', get_lang('BackToExercisesList')) . get_lang('BackToExercisesList') . '</a>';
	} else {
		echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=result') . '">' . Display :: return_icon('show_test_results.gif', get_lang('Results')) . get_lang('Results') . '</a>';
	}
}

echo '</div>'; // closing the actions div

if ($show == 'test') {
?>
<table class="data_table">
  <?php

	if (($is_allowedToEdit) and ($origin != 'learnpath')) {
?>
	  <tr class="row_odd">
	    <th colspan="3"><?php  echo get_lang('ExerciseName');?></th>
	     <th><?php echo get_lang('QuantityQuestions');?></th>
		 <!--<th>--><?php

?>

<!--</th>-->
		 <th><?php echo get_lang('Modify');?></th>
	
	  </tr>
	  <?php

	} else {
?> <tr>
	     <th colspan="3"><?php echo get_lang('ExerciseName');?></th>
	     <th><?php echo get_lang('QuantityQuestions');?></th>
		 <th><?php echo get_lang('State');?></th>
	
	  </tr>
		<?php

	}

	// show message if no HP test to show
	if (!($nbrExercises + $nbrHpTests)) {
?>
	  <tr>
	    <td <?php echo ($is_allowedToEdit?'colspan="6"':'colspan="5"'); ?>><?php echo get_lang("NoEx"); ?></td>
	  </tr>
	  <?php

	}
	$i = 1;
	// while list exercises
	if ($origin != 'learnpath') {
		//avoid sending empty parameters
		$myorigin = (empty ($origin) ? '' : '&origin=' . $origin);
		$mylpid = (empty ($learnpath_id) ? '' : '&learnpath_id=' . $learnpath_id);
		$mylpitemid = (empty ($learnpath_item_id) ? '' : '&learnpath_item_id=' . $learnpath_item_id);
		while ($row = Database :: fetch_array($result)) {
			if ($i % 2 == 0)
				$s_class = "row_odd";
			else
				$s_class = "row_even";
			// prof only
			if ($is_allowedToEdit) {
				echo '<tr class="' . $s_class . '">' . "\n";
?>
			<td width="30" align="left"><?php Display::display_icon('quiz.gif', get_lang('Exercice'))?></td>
			<td width="15" valign="left"><?php echo ($i+($page*$limitExPage)).'.'; ?></td>
				<?php $row['title']=api_parse_tex($row['title']); ?>
			<td><a href="exercice_submit.php?<?php echo api_get_cidreq().$myorigin.$mylpid.$mylpitemid; ?>&amp;exerciseId=<?php echo $row['id']; ?>" <?php if(!$row['active']) echo 'class="invisible"'; ?>><?php echo $row['title']; ?></a></td>
			<td align="center"> <?php

				$exid = $row['id'];
				//count number exercice - teacher
				$sqlquery = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE exercice_id = '" . Database :: escape_string($exid) . "'";
				$sqlresult = api_sql_query($sqlquery);
				$rowi = Database :: result($sqlresult, 0);

				//count number random exercice - teacher
				$sql_random_query = 'SELECT type,random,active,results_disabled,max_attempt FROM ' . $TBL_EXERCICES . ' WHERE id="' . Database :: escape_string($exid) . '" ';
				$rs_random = api_sql_query($sql_random_query, __FILE__, __LINE__);
				$row_random = Database :: fetch_array($rs_random);
				if ($row_random['random'] > 0) {
					echo $row_random['random'] . ' ' . api_strtolower(get_lang(($row_random['random'] > 1 ? 'Questions' : 'Question'))) . '</td>';
				} else {
					echo $rowi . ' ' . api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question'))) . '</td>';
				}

				//echo '<td><a href="exercice.php?choice=exportqti2&exerciseId='.$row['id'].'"><img src="../img/export.png" border="0" title="IMS/QTI" /></a></td>';
?>
		    <td>
		    <a href="admin.php?exerciseId=<?php echo $row['id']; ?>"><img src="../img/wizard_small.gif" border="0" title="<?php echo api_htmlentities(get_lang('Edit'),ENT_QUOTES,$charset); ?>" alt="<?php echo api_htmlentities(get_lang('Edit'),ENT_QUOTES,$charset); ?>" /></a>	      
			<?php

				if ($row['results_disabled']) {
					//echo '<a href="exercice.php?choice=enable_results&page='.$page.'&exerciseId='.$row['id'].'" title="'.get_lang('EnableResults').'" alt="'.get_lang('EnableResults').'"><img src="../img/lp_quiz_na.gif" border="0" alt="'.api_htmlentities(get_lang('EnableResults'),ENT_QUOTES,$charset).'" /></a>';
				} else {
					//echo '<a href="exercice.php?choice=disable_results&page='.$page.'&exerciseId='.$row['id'].'" title="'.get_lang('DisableResults').'" alt="'.get_lang('DisableResults').'"><img src="../img/lp_quiz.gif" border="0" alt="'.api_htmlentities(get_lang('DisableResults'),ENT_QUOTES,$charset).'" /></a>';
				}
?>
			<!--<a href="exercise_admin.php?modifyExercise=yes&exerciseId=--><?php

?>

<!--"> <img src="../img/edit.gif" border="0" title="--><?php

?>

<!--" alt="--><?php

?>

<!--" /></a>-->	    
			<a href="exercice.php?choice=delete&exerciseId=<?php echo $row['id']; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSureToDelete'),ENT_QUOTES,$charset)); echo " ".$row['title']; echo "?"; ?>')) return false;"> <img src="../img/delete.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Delete'),ENT_QUOTES,$charset); ?>" /></a>					
			<?php

				//if active
				if ($row['active']) {
?>
	      		<a href="exercice.php?choice=disable&page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>"> <img src="../img/visible.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Deactivate'),ENT_QUOTES,$charset); ?>" /></a>
	    		<?php

				} else {
					// else if not active
?>
	      		<a href="exercice.php?choice=enable&page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>"> <img src="../img/invisible.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Activate'),ENT_QUOTES,$charset); ?>" /></a>
	    		<?php

				}
				echo "</td>";
				echo "</tr>\n";
			} else { // student only
?>
	          <tr>
	            <td><?php echo ($i+($page*$limitExPage)).'.'; ?></td>
	            <td>&nbsp;</td>
	            <?php $row['title']=api_parse_tex($row['title']);?>
	            <td><a href="exercice_submit.php?<?php echo api_get_cidreq().$myorigin.$mylpid.$myllpitemid; ?>&exerciseId=<?php echo $row['id']; ?>"><?php echo $row['title']; ?></a></td>
				 <td align="center"> <?php


				$exid = $row['id'];
				//count number exercise questions
				$sqlquery = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE exercice_id = '" . Database :: escape_string($exid) . "'";
				$sqlresult = api_sql_query($sqlquery);
				$rowi = Database :: result($sqlresult, 0);
				//count number random exercice
				$sql_random_query = 'SELECT type,random,active,results_disabled,max_attempt FROM ' . $TBL_EXERCICES . ' WHERE id="' . Database :: escape_string($exid) . '" ';
				$rs_random = api_sql_query($sql_random_query, __FILE__, __LINE__);
				$row_random = Database :: fetch_array($rs_random);
				if ($row_random['random'] > 0) {
					echo $row_random['random'] . ' ' . api_strtolower(get_lang(($row_random['random'] > 1 ? 'Questions' : 'Question')));
				} else {
					//show results student
					echo $rowi . ' ' . api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question')));
				}
?> </td>
  <td align="center"><?php

				$eid = $row['id'];
				$uid = api_get_user_id();
				//this query might be improved later on by ordering by the new "tms" field rather than by exe_id
				$qry = "SELECT * FROM $TBL_TRACK_EXERCICES 
						WHERE exe_exo_id = '" . Database :: escape_string($eid) . "' and exe_user_id = '" . Database :: escape_string($uid) . "' AND exe_cours_id = '" . api_get_course_id() . "' AND status <>'incomplete' AND orig_lp_id = 0 AND orig_lp_item_id = 0 AND session_id =  '" . api_get_session_id() . "' 
						ORDER BY exe_id DESC";
				$qryres = api_sql_query($qry);
				$num = Database :: num_rows($qryres);

				//hide the results 
				$my_result_disabled = $row['results_disabled'];
				if ($my_result_disabled == 0) {
					if ($num > 0) {
						$row = Database :: fetch_array($qryres);
						$percentage = 0;
						if ($row['exe_weighting'] != 0) {
							$percentage = ($row['exe_result'] / $row['exe_weighting']) * 100;
						}
						echo get_lang('Attempted') . ' (' . get_lang('Score') . ': ';
						printf("%1.2f\n", $percentage);
						echo " %)";
					} else {
						echo get_lang('NotAttempted');
					}
				} else {
					echo get_lang('CantShowResults');
				}
?></td>
  </tr>
  <?php

			}
			// skips the last exercise, that is only used to know if we have or not to create a link "Next page"
			if ($i == $limitExPage) {
				break;
			}
			$i++;
		} // end while()
		$ind = $i;
		if (($from + $limitExPage -1) > $nbrexerc) {
			if ($from > $nbrexerc) {
				$from = $from - $nbrexerc;
				$to = $limitExPage;
			} else {
				$to = $limitExPage - ($nbrexerc - $from);
				$from = 0;
			}
		} else {
			$to = $limitExPage;
		}

		if ($is_allowedToEdit) {
			$sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
							FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
										WHERE   d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND
										 (d.path LIKE '%htm%')
										AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' LIMIT " . (int) $from . "," . (int) $to; // only .htm or .html files listed
		} else {
			$sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
							FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
											WHERE d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND
											 (d.path LIKE '%htm%')
											AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' AND ip.visibility='1' LIMIT " . (int) $from . "," . (int) $to;
		}

		$result = api_sql_query($sql, __FILE__, __LINE__);

		while ($row = Database :: fetch_array($result, 'ASSOC')) {
			$attribute['path'][] = $row['path'];
			$attribute['visibility'][] = $row['visibility'];
			$attribute['comment'][] = $row['comment'];
		}
		$nbrActiveTests = 0;
		if (is_array($attribute['path'])) {
			while (list ($key, $path) = each($attribute['path'])) {
				list ($a, $vis) = each($attribute['visibility']);
				if (strcmp($vis, "1") == 0) {
					$active = 1;
				} else {
					$active = 0;
				}
				echo "<tr>\n";

				$title = GetQuizName($path, $documentPath);
				if ($title == '') {
					$title = GetFileName($path);
				}
				// prof only
				if ($is_allowedToEdit) {
					/************/
?>
  
    <tr>
      <td><img src="../img/jqz.gif" alt="HotPotatoes" /></td>
	   <td><?php echo ($ind+($page*$limitExPage)).'.'; ?></td>
       <td><a href="showinframes.php?file=<?php echo $path?>&cid=<?php echo $_course['official_code'];?>&uid=<?php echo $_user['user_id'];?>" <?php if(!$active) echo 'class="invisible"'; ?>><?php echo $title?></a></td>    
  <td></td>
      <td><a href="adminhp.php?hotpotatoesName=<?php echo $path; ?>"> <img src="../img/edit.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Modify'),ENT_QUOTES,$charset); ?>" /></a>
       <img src="../img/wizard_gray_small.gif" border="0" title="<?php echo api_htmlentities(get_lang('Edit'),ENT_QUOTES,$charset); ?>" alt="<?php echo api_htmlentities(get_lang('Edit'),ENT_QUOTES,$charset); ?>" />
  <a href="<?php echo $exercicePath; ?>?hpchoice=delete&amp;file=<?php echo $path; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSure'),ENT_QUOTES,$charset).$title."?"); ?>')) return false;"><img src="../img/delete.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Delete'),ENT_QUOTES,$charset); ?>" /></a>
    <?php

					// if active
					if ($active) {
						$nbrActiveTests = $nbrActiveTests +1;
?>
      <a href="<?php echo $exercicePath; ?>?hpchoice=disable&amp;page=<?php echo $page; ?>&amp;file=<?php echo $path; ?>"><img src="../img/visible.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Deactivate'),ENT_QUOTES,$charset); ?>" /></a>
    <?php

					} else { // else if not active
?>
    <a href="<?php echo $exercicePath; ?>?hpchoice=enable&amp;page=<?php echo $page; ?>&amp;file=<?php echo $path; ?>"><img src="../img/invisible.gif" border="0" alt="<?php echo api_htmlentities(get_lang('Activate'),ENT_QUOTES,$charset); ?>" /></a>
    <?php

					}
					echo '<img src="../img/lp_quiz_na.gif" border="0" alt="" />';
					/****************/
?></td>
      <?php

				} else { // student only
					if ($active == 1) {
						$nbrActiveTests = $nbrActiveTests +1;
?>
    <tr>

        <td><?php echo ($ind+($page*$limitExPage)).'.'; ?><!--<img src="../img/jqz.jpg" alt="HotPotatoes" />--></td>
       <td>&nbsp;</td>
        <td><a href="showinframes.php?<?php echo api_get_cidreq()."&amp;file=".$path."&amp;cid=".$_course['official_code']."&amp;uid=".$_user['user_id'].'"'; if(!$active) echo 'class="invisible"'; ?>"><?php echo $title;?></a></td>
		<td>&nbsp;</td><td>&nbsp;</td>
  </tr>
  <?php

					}
				}
?>
  <?php

				if ($ind == $limitExPage) {
					break;
				}
				if ($is_allowedToEdit) {
					$ind++;
				} else {
					if ($active == 1) {
						$ind++;
					}
				}
			}
		}

	} //end if ($origin != 'learnpath') {
?>
</table>
<?php

}

/*****************************************/
/* Exercise Results (uses tracking tool) */
/*****************************************/

// if tracking is enabled
if ($_configuration['tracking_enabled'] && ($show == 'result')) {
?>
		<table class="data_table">
		 <tr class="row_odd">
		  <?php if($is_allowedToEdit || $is_tutor): ?>
		  <th><?php  echo get_lang('User'); ?></th><?php endif; ?>
		  <th><?php echo get_lang('Exercice'); ?></th>
		  <th><?php echo get_lang('Duration'); ?></th>
		  <th><?php echo get_lang('Date'); ?></th>
		  <th><?php echo get_lang('Result'); ?></th>
		  <th><?php echo (($is_allowedToEdit||$is_tutor)?get_lang("CorrectTest"):get_lang("ViewTest")); ?></th>
		 </tr>
		<?php

	$session_id_and = '';
	if (api_get_session_id() != 0) {
		$session_id_and = ' AND session_id = ' . api_get_session_id() . ' ';
	}

	if ($is_allowedToEdit || $is_tutor) {
		$user_id_and = '';
		if (!empty ($_POST['filter_by_user'])) {
			if ($_POST['filter_by_user'] == 'all') {
				$user_id_and = " AND user_id like '%'";
			} else {
				$user_id_and = " AND user_id = '" . Database :: escape_string((int) $_POST['filter_by_user']) . "' ";
			}
		}
		if ($_GET['gradebook'] == 'view') {
			$exercise_where_query = 'te.exe_exo_id =ce.id AND ';
		}

		$sql = "SELECT CONCAT(lastname,' ',firstname) as users, ce.title, te.exe_result ,
								 te.exe_weighting, UNIX_TIMESTAMP(te.exe_date), te.exe_id, email, UNIX_TIMESTAMP(te.start_date), steps_counter,cuser.user_id,te.exe_duration
						  FROM $TBL_EXERCICES AS ce , $TBL_TRACK_EXERCICES AS te, $TBL_USER AS user,$tbl_course_rel_user AS cuser
						  WHERE  user.user_id=cuser.user_id AND te.exe_exo_id = ce.id AND te.status != 'incomplete' AND cuser.user_id=te.exe_user_id AND te.exe_cours_id='" . Database :: escape_string($_cid) . "'
						  AND cuser.status<>1 $user_id_and $session_id_and AND ce.active <>-1 AND orig_lp_id = 0 AND orig_lp_item_id = 0 
						  AND cuser.course_code=te.exe_cours_id ORDER BY users, te.exe_cours_id ASC, ce.title ASC, te.exe_date DESC";

		$hpsql = "SELECT CONCAT(tu.lastname,' ',tu.firstname), tth.exe_name,
								tth.exe_result , tth.exe_weighting, UNIX_TIMESTAMP(tth.exe_date)
							FROM $TBL_TRACK_HOTPOTATOES tth, $TBL_USER tu
							WHERE  tu.user_id=tth.exe_user_id AND tth.exe_cours_id = '" . Database :: escape_string($_cid) . " $user_id_and '
							ORDER BY tth.exe_cours_id ASC, tth.exe_date DESC";

	} else {
		// get only this user's results
		$user_id_and = ' AND te.exe_user_id = ' . Database :: escape_string(api_get_user_id()) . ' ';

		$sql = "SELECT CONCAT(lastname,' ',firstname) as users, ce.title, te.exe_result ,
							 te.exe_weighting, UNIX_TIMESTAMP(te.exe_date), te.exe_id, email, UNIX_TIMESTAMP(te.start_date), steps_counter,cuser.user_id,te.exe_duration, ce.results_disabled
						  FROM $TBL_EXERCICES AS ce , $TBL_TRACK_EXERCICES AS te, $TBL_USER AS user,$tbl_course_rel_user AS cuser
						  WHERE  user.user_id=cuser.user_id AND te.exe_exo_id = ce.id AND te.status != 'incomplete' AND cuser.user_id=te.exe_user_id AND te.exe_cours_id='" . Database :: escape_string($_cid) . "'
						  AND cuser.status<>1 $user_id_and $session_id_and AND ce.active <>-1 AND orig_lp_id = 0 AND orig_lp_item_id = 0 
						  AND cuser.course_code=te.exe_cours_id ORDER BY users, te.exe_cours_id ASC, ce.title ASC, te.exe_date DESC";

		$hpsql = "SELECT '',exe_name, exe_result , exe_weighting, UNIX_TIMESTAMP(exe_date)
						FROM $TBL_TRACK_HOTPOTATOES
						WHERE exe_user_id = '" . $_user['user_id'] . "' AND exe_cours_id = '" . Database :: escape_string($_cid) . "'
						ORDER BY exe_cours_id ASC, exe_date DESC";
	}
	$results = getManyResultsXCol($sql, 12);
	$hpresults = getManyResultsXCol($hpsql, 5);

	$NoTestRes = 0;
	$NoHPTestRes = 0;
	//Print the results of tests
	$lang_nostartdate = get_lang('NoStartDate') . ' / ';

	if (is_array($results)) {
		$users_array_id = array ();
		if ($_GET['gradebook'] == 'view') {
			$filter_by_no_revised = true;
			$from_gradebook = true;
		}
		$sizeof = sizeof($results);
		$user_list_name = $user_list_id = array ();

		for ($i = 0; $i < $sizeof; $i++) {
			$revised = false;
			$sql_exe = 'SELECT exe_id FROM ' . Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING) . '
									  WHERE author != ' . "''" . ' AND exe_id = ' . "'" . Database :: escape_string($results[$i][5]) . "'" . ' LIMIT 1';
			$query = api_sql_query($sql_exe, __FILE__, __LINE__);

			if (Database :: num_rows($query) > 0) {
				$revised = true;
			}
			if ($filter_by_not_revised && $revised == true) {
				continue;
			}
			if ($filter_by_revised && $revised == false) {
				continue;
			}
			if ($from_gradebook && ($is_allowedToEdit || $is_tutor)) {
				if (in_array($results[$i][1] . $results[$i][0], $users_array_id)) {
					continue;
				}
				$users_array_id[] = $results[$i][1] . $results[$i][0];
			}

			$user_list_name[] = $results[$i][0];
			$user_list_id[] = $results[$i][9];
			$id = $results[$i][5];
			$mailid = $results[$i][6];
			$user = $results[$i][0];
			$test = $results[$i][1];
			$dt = strftime($dateTimeFormatLong, $results[$i][4]);
			$res = $results[$i][2];
			
			$duration = intval($results[$i][10]);
			// we filter the results if we have the permission to
			if (isset ($results[$i][11]))
				$result_disabled = intval($results[$i][11]);
			else
				$result_disabled = 0;

			if ($result_disabled == 0) {
				echo '<tr';
				if ($i % 2 == 0) {
					echo 'class="row_odd"';
				} else {
					echo 'class="row_even"';
				}
				echo '>';
				$add_start_date = $lang_nostartdate;

				if ($is_allowedToEdit || $is_tutor) {
					$user = $results[$i][0];
					echo '<td>' . $user . ' </td>';
				}
				echo '<td>' . $test . '</td>';
				echo '<td>';
				if ($results[$i][7] > 1) {
					echo ceil((($results[$i][4] - $results[$i][7]) / 60)) . ' ' . get_lang('MinMinutes');
					if ($results[$i][8] > 1) {
						echo ' ( ' . $results[$i][8] . ' ' . get_lang('Steps') . ' )';
					}
					$add_start_date = format_locale_date('%b %d, %Y %H:%M', $results[$i][7]) . ' / ';
				} else {
					echo get_lang('NoLogOfDuration');
				}
				echo '</td>';
				echo '<td>' . $add_start_date . format_locale_date('%b %d, %Y %H:%M', $results[$i][4]) . '</td>'; //get_lang('dateTimeFormatLong')

				// there are already a duration test period calculated??
				//echo '<td>'.sprintf(get_lang('DurationFormat'), $duration).'</td>';
				
				// if the float look like 10.00 we show only 10  
				
				$my_res		= float_format($results[$i][2],1);
				$my_total 	= float_format($results[$i][3],1);
				
				echo '<td>' . round(($my_res / ($my_total != 0 ? $my_total : 1)) * 100, 2) . '% (' . $my_res . ' / ' . $my_total . ')</td>';

				// Is hard to read this!!
				/*
				echo '<td>'.(($is_allowedToEdit||$is_tutor)?
							"<a href='exercise_show.php?user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".
							(($revised)?get_lang('Edit'):get_lang('Qualify'))."</a>".
							((api_is_platform_admin() || $is_tutor)?' - <a href="exercice.php?cidReq='.htmlentities($_GET['cidReq']).'&show=result&filter='.$filter.'&delete=delete&did='.$id.'" onclick="javascript:if(!confirm(\''.sprintf(get_lang('DeleteAttempt'),$user,$dt).'\')) return false;">'.get_lang('Delete').'</a>':'')
							.(($is_allowedToEdit)?' - <a href="exercice_history.php?cidReq='.htmlentities($_GET['cidReq']).'&exe_id='.$id.'">'.get_lang('ViewHistoryChange').'</a>':'')
							:(($revised)?"<a href='exercise_show.php?dt=$dt&res=$res&id=$id'>".get_lang('Show')."</a>":'')).'</td>';
				*/

				echo '<td>';
				if ($is_allowedToEdit || $is_tutor) {
					if ($revised) {
						echo "<a href='exercise_show.php?action=edit&user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".Display :: return_icon('edit.gif', get_lang('Edit'));
						echo '&nbsp;';
					} else {
						echo "<a href='exercise_show.php?action=qualify&user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".Display :: return_icon('quizz_small.gif', get_lang('Qualify'));
						echo '&nbsp;';
					}
					echo "</a>";

					if (api_is_platform_admin() || $is_tutor)
						echo ' <a href="exercice.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&show=result&filter=' . $filter . '&delete=delete&did=' . $id . '" onclick="javascript:if(!confirm(\'' . sprintf(get_lang('DeleteAttempt'), $user, $dt) . '\')) return false;">'.Display :: return_icon('delete.gif', get_lang('Delete')).'</a>';
						echo '&nbsp;';
					if ($is_allowedToEdit)
						echo ' <a href="exercice_history.php?cidReq=' . security::remove_XSS($_GET['cidReq']) . '&exe_id=' . $id . '">' .Display :: return_icon('history.gif', get_lang('ViewHistoryChange')).'</a>';
				} else {
					if ($revised)
						echo "<a href='exercise_show.php?dt=$dt&res=$res&id=$id'>" . get_lang('Show') . "</a> ";
					else
						echo '&nbsp;' . get_lang('NoResult');
				}
				echo '</td>';

				echo '</tr>';

			}
		}
	} else {
		$NoTestRes = 1;
	}

	// Print the Result of Hotpotatoes Tests
	if (is_array($hpresults)) {
		for ($i = 0; $i < sizeof($hpresults); $i++) {
			$title = GetQuizName($hpresults[$i][1], $documentPath);
			if ($title == '') {
				$title = GetFileName($hpresults[$i][1]);
			}
			echo '<tr>';
			if ($is_allowedToEdit) {
				echo '<td class="content">' . $hpresults[$i][0] . '</td>';
			}
			echo '<td class="content">' . $title . '</td>';
			echo '<td class="content">' . strftime($dateTimeFormatLong, $hpresults[$i][4]) . '</td>';
			echo '<td class="content">' . round(($hpresults[$i][2] / ($hpresults[$i][3] != 0 ? $hpresults[$i][3] : 1)) * 100, 2) . '% (' . $hpresults[$i][2] . ' / ' . $hpresults[$i][3] . ')</td>';
			echo '<td></td>'; //there is no possibility to edit the results of a Hotpotatoes test
			echo '</tr>';
		}
	} else {
		$NoHPTestRes = 1;
	}

	if ($NoTestRes == 1 && $NoHPTestRes == 1) {
?>
			 <tr>
			  <td colspan="6"><?php echo get_lang("NoResult"); ?></td>
			 </tr>
			<?php

	}
?>
		</table>
		<br />
		<?php

}

if ($origin != 'learnpath') { //so we are not in learnpath tool
	Display :: display_footer();
} else {
?>
	<link rel="stylesheet" type="text/css" href="<?php echo $clarolineRepositoryWeb ?>css/default.css" />
	<?php
}
?>
