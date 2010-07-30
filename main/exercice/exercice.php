<?php
/* For licensing terms, see /license.txt */

/**
*	Exercise list: This script shows the list of exercises for administrators and students.
*	@package chamilo.exercise
*	@author Olivier Brouckaert, original author
*	@author Denes Nagy, HotPotatoes integration
*	@author Wolfgang Schneider, code/html cleanup
*	@author Julio Montoya <gugli100@gmail.com>, lots of cleanup + several improvements
* 	@version $Id:exercice.php 12269 2007-05-03 14:17:37Z elixir_julian $
*/

// name of the language file that needs to be included
$language_file = 'exercice';

// including the global library
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
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

/*
	Constants and variables
*/
$is_allowedToEdit 			= api_is_allowed_to_edit(null,true);
$is_tutor 					= api_is_allowed_to_edit(true);
$is_tutor_course 			= api_is_course_tutor();
$tbl_course_rel_user		= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TBL_USER 					= Database :: get_main_table(TABLE_MAIN_USER);
$TBL_DOCUMENT 				= Database :: get_course_table(TABLE_DOCUMENT);
$TBL_ITEM_PROPERTY 			= Database :: get_course_table(TABLE_ITEM_PROPERTY);
$TBL_EXERCICE_ANSWER 		= Database :: get_course_table(TABLE_QUIZ_ANSWER);
$TBL_EXERCICE_QUESTION 		= Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES 				= Database :: get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS 				= Database :: get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCICES 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_HOTPOTATOES 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
$TBL_TRACK_ATTEMPT 			= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW 			= Database :: get_course_table(TABLE_LP_ITEM_VIEW);
$TBL_LP_ITEM 				= Database :: get_course_table(TABLE_LP_ITEM);
$TBL_LP_VIEW 				= Database :: get_course_table(TABLE_LP_VIEW);

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
	Database::query($sql);
	$filter=Security::remove_XSS($_GET['filter']);
	header('Location: exercice.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&show=result&filter=' . $filter . '');
	exit;
}

if ($show == 'result' && $_REQUEST['comments'] == 'update' && ($is_allowedToEdit || $is_tutor) && $_GET['exeid']== strval(intval($_GET['exeid']))) {
	$id 		= intval($_GET['exeid']); //filtered by post-condition
	$emailid 	= $_GET['emailid'];
	$test 		= $_GET['test'];
	$from 		= $_SESSION['_user']['mail'];
	$from_name  = api_get_person_name($_SESSION['_user']['firstName'], $_SESSION['_user']['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
	$url		= api_get_path(WEB_CODE_PATH) . 'exercice/exercice.php?' . api_get_cidreq() . '&show=result';
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
		$result =Database::query($sql);
		$ques_name = Database::result($result,0,"question");

		$query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '$my_marks',teacher_comment = '$my_comments'
				  WHERE question_id = '".$my_questionid."'
				  AND exe_id='".$id."'";

		Database::query($query);

		$qry = 'SELECT sum(marks) as tot
				FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.$id.'
				GROUP BY question_id';

		$res = Database::query($qry);
		$tot = Database::result($res,0,'tot');
		//updating also the total weight
		$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '".Database::escape_string($tot)."', exe_weighting = '".Database::escape_string($total_weighting)."'
					 WHERE exe_Id='".$id."'";
		Database::query($totquery);
		$recording_changes = 'INSERT INTO '.$TBL_RECORDING.' ' .
				'(exe_id,
				question_id,
				marks,
				insert_date,
				author,
				teacher_comment)
				VALUES
				('."'$id','".$my_questionid."','$my_marks','".date('Y-m-d H:i:s')."','".api_get_user_id()."'".',"'.$my_comments.'")';
		Database::query($recording_changes);
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
			$result = Database::query($sql);
			$ques_name = Database :: result($result, 0, "question");

			$query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '" . $v . "'
								  WHERE question_id = '" . $my_questionid . "'
								  AND exe_id='" . $id . "'";
			Database::query($query);

			$qry = 'SELECT sum(marks) as tot
								FROM ' . $TBL_TRACK_ATTEMPT . ' WHERE exe_id = ' . intval($id) . '
								GROUP BY question_id';

			$res = Database::query($qry);
			$tot = Database :: result($res, 0, 'tot');
			//updating also the total weight
			$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . Database :: escape_string($tot) . "', exe_weighting = '" . Database :: escape_string($total_weighting) . "'
									 WHERE exe_Id='" . Database :: escape_string($id) . "'";

			Database::query($totquery);

			$recording_changes = 'INSERT INTO ' . $TBL_RECORDING . ' ' .
			'(exe_id,
								question_id,
								marks,
								insert_date,
								author)
								VALUES
								(' . "'$id','" . $my_questionid . "','$v','" . date('Y-m-d H:i:s') . "','" . api_get_user_id() . "'" . ')';
			Database::query($recording_changes);
		} else {
			$query = "UPDATE $TBL_TRACK_ATTEMPT SET teacher_comment = '" . $v . "'
					  			WHERE question_id = '" . $my_questionid . "'
					  			AND exe_id = '" . $id . "'";
			Database::query($query);

			$recording_changes = 'INSERT INTO ' . $TBL_RECORDING . ' ' .
			'(exe_id,
								question_id,
								teacher_comment,
								insert_date,
								author)
								VALUES
								(' . "'$id','" . $my_questionid . "','$v','" . date('Y-m-d H:i:s') . "','" . api_get_user_id() . "'" . ')';
			Database::query($recording_changes);
		}
	}*/

	$qry = 'SELECT DISTINCT question_id, marks
			FROM ' . $TBL_TRACK_ATTEMPT . ' where exe_id = ' . intval($id) . '
			GROUP BY question_id';

	$res = Database::query($qry);
	$tot = 0;
	while ($row = Database :: fetch_array($res, 'ASSOC')) {
		$tot += $row['marks'];
	}

	$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . Database :: escape_string($tot) . "' WHERE exe_Id='" . Database :: escape_string($id) . "'";

	//search items
	if (isset($_POST['my_exe_exo_id']) && isset($_POST['student_id'])) {
		$sql_lp='SELECT li.id as lp_item_id,li.lp_id,li.item_type,li.path,liv.id AS lp_view_id,liv.user_id,max(liv.view_count) AS view_count FROM '.$TBL_LP_ITEM.' li
		INNER JOIN '.$TBL_LP_VIEW.' liv ON li.lp_id=liv.lp_id WHERE li.path="'.Database::escape_string($_POST['my_exe_exo_id']).'" AND li.item_type="quiz" AND user_id="'.Database::escape_string($_POST['student_id']).'" ';
		$rs_lp=Database::query($sql_lp);
		if (!($rs_lp===false)) {
			$row_lp=Database::fetch_array($rs_lp);
			//update score in learnig path
			$sql_lp_view='UPDATE '.$TBL_LP_ITEM_VIEW.' liv SET score ="'.$tot.'" WHERE liv.lp_item_id="'.(int)$row_lp['lp_item_id'].'" AND liv.lp_view_id="'.(int)$row_lp['lp_view_id'].'" AND liv.view_count="'.(int)$row_lp['view_count'].'" ;';
			$rs_lp_view=Database::query($sql_lp_view);
		}
	}
	Database::query($totquery);

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
	$headers = "From:$from_name\r\nReply-to: $to";
	//mail($emailid, $subject, $mess,$headers);

	@api_mail_html($emailid, $emailid, $subject, $mess, $from_name, $from);
	if (in_array($origin, array ('tracking_course','user_course'))) {

		if (isset ($_POST['lp_item_id']) && isset ($_POST['lp_item_view_id']) && isset ($_POST['student_id']) && isset ($_POST['total_score']) && isset ($_POST['total_time']) && isset ($_POST['totalWeighting'])) {
			$lp_item_id = $_POST['lp_item_id'];
			$lp_item_view_id = $_POST['lp_item_view_id'];
			$student_id = $_POST['student_id'];
			$totalWeighting = $_POST['totalWeighting'];

			if ($lp_item_id == strval(intval($lp_item_id)) && $lp_item_view_id == strval(intval($lp_item_view_id)) && $student_id == strval(intval($student_id))) {

				$score = Database :: escape_string($_POST['total_score']); //This is the new note
				$total_time = Database :: escape_string($_POST['total_time']);

				//I need the lp_item_view_id in order to update the record

				//@todo add the lp_item_view_id in the track_exercise table in order to have a real match between the lp_item_view and the track_exercise

				//$my_real_lp_item_view_id = Database :: escape_string($_POST['real_lp_item_view_id']);

				$lp_item_id 	 = Database :: escape_string($lp_item_id);
				$lp_item_view_id = Database :: escape_string($lp_item_view_id);
				$student_id		 = Database :: escape_string($student_id);
				$totalWeighting  = Database :: escape_string($totalWeighting);

				/*
				$sql = "SELECT (view_count) FROM $TBL_LP_ITEM_VIEW
						WHERE lp_item_id = '" . (int) $lp_item_view_id . "' AND lp_view_id = $my_real_lp_item_view_id ORDER BY id DESC LIMIT 1";
				$res_view_count = Database::query($sql);
				$res_view_count = Database :: fetch_row($res_view_count);
				$my_view_count =  intval($res_view_count[0]);
				*/

				//Checking if this is the lastest attempt
				$sql = "SELECT exe_id FROM $TBL_TRACK_EXERCICES
						WHERE exe_user_id = '" . Database :: escape_string($_POST['student_id']) . "' AND exe_cours_id = '" . api_get_course_id() . "' AND orig_lp_id = '$lp_item_id' AND orig_lp_item_id =  '$lp_item_view_id'  AND session_id =  '" . api_get_session_id() . "' AND status = ''
						ORDER BY exe_id DESC LIMIT 1 ";
				$res_view_count = Database::query($sql);
				$res_view_count = Database :: fetch_row($res_view_count);
				$my_view_count =  intval($res_view_count[0]);

				//Update lp_item_view if this attempts is the latest
				$sql = "SELECT MAX(view_count) FROM $TBL_LP_ITEM_VIEW
						WHERE lp_item_id = '" . (int) $lp_item_view_id . "' AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . (int) $student_id . "' and lp_id='" . (int) $lp_item_id . "')";
				$res_max_view_count = Database::query($sql);
				$row_max_view_count = Database :: fetch_row($res_max_view_count);
				$max_view_count =  intval($row_max_view_count[0]);

				//Only update if is the last attempt
				if ($my_view_count == $_GET['exeid']) {
					// update score and total_time from last attempt when you qualify the exercise in Learning path detail
					$sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '" . intval($tot) . "' WHERE lp_item_id = '" . (int) $lp_item_view_id . "'
					    					AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . (int) $student_id . "' and lp_id='" . (int) $lp_item_id . "') AND view_count = '$max_view_count'";
					Database::query($sql_update_score);
				}

				/*
				/*
				// update score and total_time from last attempt when you qualify the exercise in Learning path detail
				$sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '" . (float) $score . "',total_time = '" . (int) $total_time . "' WHERE lp_item_id = '" . (int) $lp_item_view_id . "'
				    					AND lp_view_id = (SELECT id from $TBL_LP_VIEW  WHERE user_id = '" . (int) $student_id . "' and lp_id='" . (int) $lp_item_id . "') AND view_count = '$max_view_count'";
				Database::query($sql_update_score);*/

				// update max_score from a exercise in lp
				//$sql_update_max_score = "UPDATE $TBL_LP_ITEM SET max_score = '" . (float) $totalWeighting . "'  WHERE id = '" . (int) $lp_item_view_id . "'";

				//Database::query($sql_update_max_score);

			}
		}
		if ($origin == 'tracking_course' && !empty($_POST['lp_item_id'])) {
			//Redirect to the course detail in lp
			header('location: ../mySpace/lp_tracking.php?course=' . Security :: remove_XSS($_GET['course']) . '&origin=' . $origin . '&lp_id=' . Security :: remove_XSS($_POST['lp_item_id']) . '&student_id=' . Security :: remove_XSS($_GET['student']).'&from='.Security::remove_XSS($_GET['from']));
			exit;
		} else {
			//Redirect to the reporting
			header('location: ../mySpace/myStudents.php?origin=' . $origin . '&student=' . Security :: remove_XSS($_GET['student']) . '&details=true&course=' . Security :: remove_XSS($_GET['course']));
			exit;
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
		'name' => get_lang('ToolGradebook')
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
require_once (api_get_path(LIBRARY_PATH) . 'statsUtils.lib.inc.php');

if ($is_allowedToEdit && !empty ($choice) && $choice == 'exportqti2') {
	require_once 'export/qti2/qti2_export.php';
	$export = export_exercise($exerciseId, true);

	require_once api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php';
	$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	$temp_dir_short = uniqid();
	$temp_zip_dir = $archive_path . "/" . $temp_dir_short;
	if (!is_dir($temp_zip_dir))
		mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
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
				$export->exportCompleteReportXLS($documentPath, $user_id, $_SESSION['export_user_fields'], $_POST['export_filter']);
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

event_access_tool(TOOL_QUIZ);

// Tool introduction
Display :: display_introduction_section(TOOL_QUIZ);

// selects $limitExPage exercises at the same time
$from = $page * $limitExPage;
$sql = "SELECT count(id) FROM $TBL_EXERCICES";
$res = Database::query($sql);
list ($nbrexerc) = Database :: fetch_array($res);

HotPotGCt($documentPath, 1, $_user['user_id']);
$tbl_grade_link = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
// only for administrator

if ($is_allowedToEdit) {
	if (!empty ($choice)) {
		// construction of Exercise

		$objExerciseTmp = new Exercise();
		$check = Security::check_token('get');

		if ($objExerciseTmp->read($exerciseId)) {
			if ($check) {
				switch ($choice) {
					case 'delete' : // deletes an exercise
						$objExerciseTmp->delete();

						//delete link of exercise of gradebook tool
						$sql = 'SELECT gl.id FROM ' . $tbl_grade_link . ' gl WHERE gl.type="1" AND gl.ref_id="' . $exerciseId . '";';
						$result = Database::query($sql);
						$row = Database :: fetch_array($result, 'ASSOC');
						//see
						if (!empty($row['id'])) {
	                 		$link = LinkFactory :: load($row['id']);
	                     		if ($link[0] != null) {
	                            	$link[0]->delete();
	                     		}
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
					case 'clean_results' : //clean student results
							$quantity_results_deleted= $objExerciseTmp->clean_results();
							Display :: display_confirmation_message(sprintf(get_lang('XResultsCleaned'),$quantity_results_deleted));
					break;
					case 'copy_exercise' : //copy an exercise
							$objExerciseTmp->copy_exercise();
							Display :: display_confirmation_message(get_lang('ExerciseCopied'));
					break;
				}
			}
		}

		// destruction of Exercise
		unset ($objExerciseTmp);
		Security::clear_token();
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
				$res = Database::query($query);
				$row = Database :: fetch_array($res, 'ASSOC');
				api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'visible', $_user['user_id']);
				//$dialogBox = get_lang('ViMod');

				break;
			case 'disable' : // disables an exercise
				$newVisibilityStatus = "0"; //"invisible"
				$query = "SELECT id FROM $TBL_DOCUMENT WHERE path='" . Database :: escape_string($file) . "'";
				$res = Database::query($query);
				$row = Database :: fetch_array($res, 'ASSOC');
				api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'invisible', $_user['user_id']);
				break;
			default :
				break;
		}
	}

	if ($show == 'test') {
		$sql = "SELECT id,title,type,active,description, results_disabled FROM $TBL_EXERCICES WHERE active<>'-1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
		$result = Database::query($sql);
	}

}
elseif ($show == 'test') { // only for students //fin
	$sql = "SELECT id,title,type,description, results_disabled FROM $TBL_EXERCICES WHERE active='1' ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
	$result = Database::query($sql);
}

// the actions
echo '<div class="actions">';

// display the next and previous link if needed
$from = $page * $limitExPage;
$sql = "SELECT count(id) FROM $TBL_EXERCICES";
$res = Database::query($sql);
list ($nbrexerc) = Database :: fetch_array($res);
HotPotGCt($documentPath, 1, $_user['user_id']);

//condition for the session
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id,true,true);

// only for administrator
if ($is_allowedToEdit) {
	if ($show == 'test') {
		$sql = "SELECT id, title, type, active, description, results_disabled, session_id, start_time FROM $TBL_EXERCICES WHERE active<>'-1' $condition_session ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
		$result = Database::query($sql);
	}
}
elseif ($show == 'test') { // only for students
	$sql = "SELECT id, title, type, description, results_disabled, session_id, start_time FROM $TBL_EXERCICES WHERE active='1' $condition_session ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
	$result = Database::query($sql);
}
if ($show == 'test') {
	$nbrExercises = Database :: num_rows($result);

	//get HotPotatoes files (active and inactive)
	$res = Database::query("SELECT *
						FROM $TBL_DOCUMENT
						WHERE
						path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'");
	$nbrTests = Database :: num_rows($res);
	$res = Database::query("SELECT *
						FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
						WHERE  d.id = ip.ref
						AND ip.tool = '" . TOOL_DOCUMENT . "'
						AND d.path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'
						AND ip.visibility='1'");
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

if (($is_allowedToEdit) and ($origin != 'learnpath')) {
	if ($_GET['show'] != 'result') {
		echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display :: return_icon('new_test.gif', get_lang('NewEx')) . get_lang('NewEx') . '</a>';
		echo '<a href="question_create.php?' . api_get_cidreq() . '">' . Display :: return_icon('question_add.gif', get_lang('AddQuestionToExercise')) . get_lang('AddQuestionToExercise') . '</a>';
		echo '<a href="hotpotatoes.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_db.png', get_lang('ImportHotPotatoesQuiz')) . get_lang('ImportHotPotatoesQuiz') . '</a>';
		// link to import qti2 ...
		echo '<a href="qti2.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_db.png', get_lang('ImportQtiQuiz')) . get_lang('ImportQtiQuiz') . '</a>';
		echo '<a href="exercice.php?' . api_get_cidreq() . '&show=result">' . Display :: return_icon('show_test_results.gif', get_lang('Results')) . get_lang('Results') . '</a>';
	}

	// the actions for the statistics
	if ($show == 'result') {
		// the form
		if (api_is_platform_admin() || api_is_course_admin() || api_is_course_tutor() || api_is_course_coach()) {
			if ($_SESSION['export_user_fields']) {
				$alt = get_lang('ExportWithUserFields');
				$extra_user_fields = '<input type="hidden" name="export_user_fields" value="export_user_fields">';
			} else {
				$alt = get_lang('ExportWithoutUserFields');
				$extra_user_fields = '<input type="hidden" name="export_user_fields" value="do_not_export_user_fields">';
			}
			echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=test') . '">' . Display :: return_icon('back.png', get_lang('GoBackToQuestionList')) . get_lang('GoBackToQuestionList') . '</a>';
			echo '<a href="javascript: void(0);" onclick="javascript: document.form1a.submit();">'.Display::return_icon('csv.gif',get_lang('ExportAsCSV')).get_lang('ExportAsCSV').'</a>';
			echo '<a href="javascript: void(0);" onclick="javascript: document.form1b.submit();">' . Display :: return_icon('excel.gif', get_lang('ExportAsXLS')) . get_lang('ExportAsXLS') . '</a>';
			echo '<form id="form1a" name="form1a" method="post" action="' . api_get_self() . '?show=' . Security :: remove_XSS($_GET['show']) . '" style="display:inline">';
			echo '<input type="hidden" name="export_report" value="export_report">';
			echo '<input type="hidden" name="export_format" value="csv">';
			echo '<input type="hidden" name="export_filter" value="'.(empty($filter)?1:intval($filter)).'">';
			echo '</form>';
			echo '<form id="form1b" name="form1b" method="post" action="' . api_get_self() . '?show=' . Security :: remove_XSS($_GET['show']) . '" style="display:inline">';
			echo '<input type="hidden" name="export_report" value="export_report">';
			echo '<input type="hidden" name="export_filter" value="'.(empty($filter)?1:intval($filter)).'">';
			echo '<input type="hidden" name="export_format" value="xls">';
			echo '</form>';
		}
	}
} else {
	//the student view
	if ($show == 'result') {
		echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=test') . '">' . Display :: return_icon('back.png', get_lang('GoBackToQuestionList')) . get_lang('GoBackToQuestionList') . '</a>';
	} else {
		echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=result') . '">' . Display :: return_icon('show_test_results.gif', get_lang('Results')) . get_lang('Results') . '</a>';
	}
}
if ($_configuration['tracking_enabled']) {
	if ($show == 'result') {
		if (api_is_allowed_to_edit(null,true)) {
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
			echo $view_result;
		}
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
		 <th><?php echo get_lang('Modify');?></th>
	  </tr>
	  <?php
	} else {
		//student only
?> <tr>
	     <th colspan="2"><?php echo get_lang('ExerciseName');?></th>
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

	/*
	 * Listing exercises
	 */
	if ($origin != 'learnpath') {
		//avoid sending empty parameters
		$myorigin = (empty ($origin) ? '' : '&origin=' . $origin);
		$mylpid = (empty ($learnpath_id) ? '' : '&learnpath_id=' . $learnpath_id);
		$mylpitemid = (empty ($learnpath_item_id) ? '' : '&learnpath_item_id=' . $learnpath_item_id);

		$token = Security::get_token();
				while ($row = Database :: fetch_array($result)) {
				//validacion when belongs to a session
				$session_img = api_get_session_image($row['session_id'], $_user['status']);

				// check if start time
				$is_actived_time = true;
				if ($row['start_time'] != '0000-00-00 00:00:00' && api_strtotime($row['start_time']) > time()) {
					$is_actived_time = false;
				}

				if ($i % 2 == 0)
					$s_class = "row_odd";
				else
					$s_class = "row_even";
				// prof only
				if ($is_allowedToEdit) {
					echo '<tr class="' . $s_class . '">';

					echo '<td width="30" align="left">'.Display::return_icon('quiz.gif', get_lang('Exercice')).'</td>';
					echo '<td width="15" valign="left">'.($i+($page*$limitExPage)).'.'.'</td>';

					//Showing exercise title
					$row['title']=api_parse_tex($row['title']);

					echo '<td>';
					$class_invisible = '';
					if (!$row['active']) {
						$class_invisible = 'class="invisible"';
					}
					echo '<a href="exercice_submit.php?'.api_get_cidreq().$myorigin.$mylpid.$mylpitemid.'&amp;exerciseId='.$row['id'].'" '.$class_invisible.'>';
					echo Security::remove_XSS($row['title']);
					echo '</a>';
					echo $session_img;
					echo '</td>';
					echo '<td align="center">';

					$exid = $row['id'];

					//count number exercice - teacher
					$sqlquery = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE exercice_id = '" . Database :: escape_string($exid) . "'";
					$sqlresult = Database::query($sqlquery);
					$rowi = Database :: result($sqlresult, 0);

					//count number random exercice - teacher
					$sql_random_query = 'SELECT type,random,active,results_disabled,max_attempt FROM ' . $TBL_EXERCICES . ' WHERE id="' . Database :: escape_string($exid) . '" ';
					$rs_random = Database::query($sql_random_query);
					$row_random = Database :: fetch_array($rs_random);
					if ($row_random['random'] > 0) {
						echo $row_random['random'] . ' ' . api_strtolower(get_lang(($row_random['random'] > 1 ? 'Questions' : 'Question'))) . '</td>';
					} else {
						echo $rowi . ' ' . api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question'))) . '</td>';
					}
					echo '<td align="center">';
					if ($session_id == $row['session_id']) {
						?>
						<a href="admin.php?<?php echo api_get_cidreq()?>&amp;exerciseId=<?php echo $row['id']; ?>"><img src="../img/wizard_small.gif" border="0" title="<?php echo api_htmlentities(get_lang('Edit'),ENT_QUOTES,$charset); ?>" alt="<?php echo api_htmlentities(get_lang('Edit'),ENT_QUOTES,$charset); ?>" /></a>
						<a href="exercice.php?<?php echo api_get_cidreq()?>&amp;choice=copy_exercise&amp;sec_token=<?php echo $token; ?>&amp;exerciseId=<?php echo $row['id']; ?>"  onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSureToCopy'),ENT_QUOTES,$charset)); echo " ".addslashes($row['title']); echo "?"; ?>')) return false;"><img width="16" src="../img/cd.gif" border="0" title="<?php echo api_htmlentities(get_lang('CopyExercise'),ENT_QUOTES,$charset); ?>" alt="<?php echo api_htmlentities(get_lang('CopyExercise'),ENT_QUOTES,$charset); ?>" /></a>
						<a href="exercice.php?<?php echo api_get_cidreq()?>&amp;choice=clean_results&amp;sec_token=<?php echo $token; ?>&amp;exerciseId=<?php echo $row['id']; ?>"  onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSureToDeleteResults'),ENT_QUOTES,$charset)); echo " ".addslashes($row['title']); echo "?"; ?>')) return false;" ><img width="16" src="../img/clean_group.gif" border="0" title="<?php echo api_htmlentities(get_lang('CleanStudentResults'),ENT_QUOTES,$charset); ?>" alt="<?php echo api_htmlentities(get_lang('CleanStudentResults'),ENT_QUOTES,$charset); ?>" /></a>
						<a href="exercice.php?<?php echo api_get_cidreq() ?>&choice=delete&sec_token=<?php echo$token; ?>&amp;exerciseId=<?php echo $row['id']; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSureToDelete'),ENT_QUOTES,$charset)); echo " ".addslashes($row['title']); echo "?"; ?>')) return false;"> <img src="../img/delete.gif" border="0" title="<?php echo get_lang('Delete'); ?>" alt="<?php echo api_htmlentities(get_lang('Delete'),ENT_QUOTES,$charset); ?>" /></a>
						<?php
						//if active
						if ($row['active']) {
							?>
							<a href="exercice.php?<?php echo api_get_cidreq() ?>&choice=disable&sec_token=<?php echo$token; ?>&amp;page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>"> <img src="../img/visible.gif"  border="0" title="<?php echo get_lang('Deactivate'); ?>" alt="<?php echo api_htmlentities(get_lang('Deactivate'),ENT_QUOTES,$charset); ?>" /></a>
							<?php
						} else { // else if not active
							?>
							<a href="exercice.php?<?php echo api_get_cidreq() ?>&choice=enable&sec_token=<?php echo$token; ?>&amp;page=<?php echo $page; ?>&exerciseId=<?php echo $row['id']; ?>"> <img src="../img/invisible.gif" border="0"  title="<?php echo get_lang('Activate'); ?>" alt="<?php echo api_htmlentities(get_lang('Activate'),ENT_QUOTES,$charset); ?>" /></a>
							<?php
						}
						// Export qti ...
						echo '<a href="exercice.php?choice=exportqti2&exerciseId='.$row['id'].'"><img src="../img/export_db.png" border="0" title="IMS/QTI" /></a>';
					} else { // not session resource
						echo get_lang('ExerciseEditionNotAvailableInSession');
					}
					echo "</td>";
					echo "</tr>";
				} else { // student only
					?>
					<tr>
					  <td><?php echo ($i+($page*$limitExPage)).'.'; ?></td>
					  <?php $row['title']=api_parse_tex($row['title']);?>
					  <td>

					<?php
					// if time is actived show link to exercise
					if ($is_actived_time) { ?>
						<a href="exercice_submit.php?<?php echo api_get_cidreq().$myorigin.$mylpid.$myllpitemid; ?>&exerciseId=<?php echo $row['id']; ?>"><?php echo $row['title']; ?></a>
					<?php
					} else {
						echo $row['title'];
					}
					echo '</td><td align="center">';
					$exid = $row['id'];
					//count number exercise questions
					$sqlquery = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE exercice_id = '" . Database :: escape_string($exid) . "'";
					$sqlresult = Database::query($sqlquery);
					$rowi = Database :: result($sqlresult, 0);
					//count number random exercice
					$sql_random_query = 'SELECT type,random,active,results_disabled,max_attempt FROM ' . $TBL_EXERCICES . ' WHERE id="' . Database :: escape_string($exid) . '" ';
					$rs_random = Database::query($sql_random_query);
					$row_random = Database :: fetch_array($rs_random);
					if ($row_random['random'] > 0) {
						echo $row_random['random'] . ' ' . api_strtolower(get_lang(($row_random['random'] > 1 ? 'Questions' : 'Question')));
					} else {
						//show results student
						echo $rowi . ' ' . api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question')));
					}
					echo '</td>';
					echo '<td align="center">';
					$eid = $row['id'];
					$uid = api_get_user_id();
					//this query might be improved later on by ordering by the new "tms" field rather than by exe_id
					$qry = "SELECT * FROM $TBL_TRACK_EXERCICES
							WHERE exe_exo_id = '" . Database :: escape_string($eid) . "' and exe_user_id = '" . Database :: escape_string($uid) . "' AND exe_cours_id = '" . api_get_course_id() . "' AND status <>'incomplete' AND orig_lp_id = 0 AND orig_lp_item_id = 0 AND session_id =  '" . api_get_session_id() . "'
							ORDER BY exe_id DESC";
					$qryres = Database::query($qry);
					$num = Database :: num_rows($qryres);

					//hide the results
					if (!$is_actived_time) {
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
								echo get_lang('WillBeActivated' .' '. $row['start_time']);
							}
						} else {
							echo get_lang('CantShowResults');
						}
						echo '</td></tr>';
					} else {
					    echo get_lang('NotAttempted');
					}
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
					WHERE   d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND (d.path LIKE '%htm%')
					AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' LIMIT " . (int) $from . "," . (int) $to; // only .htm or .html files listed
		} else {
			$sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
					FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
					WHERE d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND (d.path LIKE '%htm%')
					AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' AND ip.visibility='1' LIMIT " . (int) $from . "," . (int) $to;
		}

		$result = Database::query($sql);

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
					$title = basename($path);
				}
				// prof only
				if ($is_allowedToEdit) {
?>

    <tr>
      <td><img src="../img/import_db.png" alt="HotPotatoes" /></td>
	   <td><?php echo ($ind+($page*$limitExPage)).'.'; ?></td>
       <td><a href="showinframes.php?file=<?php echo $path?>&cid=<?php echo $_course['official_code'];?>&uid=<?php echo $_user['user_id'];?>" <?php if(!$active) echo 'class="invisible"'; ?>><?php echo $title?></a></td>
  <td></td>
      <td align="center"><a href="adminhp.php?<?php echo api_get_cidreq() ?>&hotpotatoesName=<?php echo $path; ?>"> <img src="../img/edit.gif" border="0" title="<?php echo get_lang('Modify'); ?>" alt="<?php echo api_htmlentities(get_lang('Modify'),ENT_QUOTES,$charset); ?>" /></a>
       <img src="../img/wizard_gray_small.gif" border="0" title="<?php echo api_htmlentities(get_lang('NotMarkActivity'),ENT_QUOTES,$charset); ?>" alt="<?php echo api_htmlentities(get_lang('Edit'),ENT_QUOTES,$charset); ?>" />
  <a href="<?php echo $exercicePath; ?>?<?php echo api_get_cidreq() ?>&amp;hpchoice=delete&amp;file=<?php echo $path; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('AreYouSure'),ENT_QUOTES,$charset).$title."?"); ?>')) return false;"><img src="../img/delete.gif" border="0" title="<?php echo get_lang('Delete'); ?>" alt="<?php echo api_htmlentities(get_lang('Delete'),ENT_QUOTES,$charset); ?>" /></a>
    <?php

					// if active
					if ($active) {
						$nbrActiveTests = $nbrActiveTests +1;
?>
      <a href="<?php echo $exercicePath; ?>?<?php echo api_get_cidreq() ?>&hpchoice=disable&amp;page=<?php echo $page; ?>&amp;file=<?php echo $path; ?>"><img src="../img/visible.gif" border="0" title="<?php echo get_lang('Deactivate'); ?>" alt="<?php echo api_htmlentities(get_lang('Deactivate'),ENT_QUOTES,$charset); ?>" /></a>
    <?php

					} else { // else if not active
?>
    <a href="<?php echo $exercicePath; ?>?<?php echo api_get_cidreq() ?>&hpchoice=enable&amp;page=<?php echo $page; ?>&amp;file=<?php echo $path; ?>"><img src="../img/invisible.gif" border="0" title="<?php echo get_lang('Activate'); ?>" alt="<?php echo api_htmlentities(get_lang('Activate'),ENT_QUOTES,$charset); ?>" /></a>
    <?php

					}
					echo '<img src="../img/lp_quiz_na.gif" border="0" title="'.get_lang('NotMarkActivity').'" alt="" />';
					echo '</td>';
				} else { // student only
					if ($active == 1) {
						$nbrActiveTests = $nbrActiveTests +1;
?>
    <tr>

        <td><?php echo ($ind+($page*$limitExPage)).'.'; ?><!--<img src="../img/jqz.jpg" alt="HotPotatoes" />--></td>
        <td><a href="showinframes.php?<?php echo api_get_cidreq()."&amp;file=".$path."&amp;cid=".$_course['official_code']."&amp;uid=".$_user['user_id'].'"'; if(!$active) echo 'class="invisible"'; ?>"><?php echo $title;?></a></td>
		<td style="text-align: center;">-</td><td style="text-align: center;">-</td>
  </tr>
  <?php

					}
				}
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
echo '</table>';
}

/* Exercise Results (uses tracking tool) */

// if tracking is enabled
if ($_configuration['tracking_enabled'] && ($show == 'result')) {

	$session_id_and = ' AND ce.session_id = ' . api_get_session_id() . ' ';
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

			$sql="SELECT ".(api_is_western_name_order() ? "CONCAT(firstname,' ',lastname)" : "CONCAT(lastname,' ',firstname)")." as users, ce.title, te.exe_result ,
								 te.exe_weighting, te.exe_date, te.exe_id, email, te.start_date, steps_counter,cuser.user_id,te.exe_duration
						  FROM $TBL_EXERCICES AS ce , $TBL_TRACK_EXERCICES AS te, $TBL_USER AS user,$tbl_course_rel_user AS cuser
						  WHERE  user.user_id=cuser.user_id AND cuser.relation_type<>".COURSE_RELATION_TYPE_RRHH." AND te.exe_exo_id = ce.id AND te.status != 'incomplete' AND cuser.user_id=te.exe_user_id AND te.exe_cours_id='" . Database :: escape_string($_cid) . "'
						  AND cuser.status<>1 $user_id_and $session_id_and AND ce.active <>-1 AND orig_lp_id = 0 AND orig_lp_item_id = 0
						  AND cuser.course_code=te.exe_cours_id ORDER BY users, te.exe_cours_id ASC, ce.title ASC, te.exe_date DESC";

			$hpsql="SELECT ".(api_is_western_name_order() ? "CONCAT(tu.firstname,' ',tu.lastname)" : "CONCAT(tu.lastname,' ',tu.firstname)").", tth.exe_name,
								tth.exe_result , tth.exe_weighting, tth.exe_date
							FROM $TBL_TRACK_HOTPOTATOES tth, $TBL_USER tu
							WHERE  tu.user_id=tth.exe_user_id AND tth.exe_cours_id = '" . Database :: escape_string($_cid) . " $user_id_and '
							ORDER BY tth.exe_cours_id ASC, tth.exe_date DESC";

	} else {
		// get only this user's results
		$user_id_and = ' AND te.exe_user_id = ' . api_get_user_id() . ' ';

				$sql="SELECT ".(api_is_western_name_order() ? "CONCAT(firstname,' ',lastname)" : "CONCAT(lastname,' ',firstname)")." as users,ce.title, te.exe_result ,
							 te.exe_weighting, te.exe_date, te.exe_id, email, te.start_date, steps_counter,cuser.user_id,te.exe_duration, ce.results_disabled
						  FROM $TBL_EXERCICES AS ce , $TBL_TRACK_EXERCICES AS te, $TBL_USER AS user,$tbl_course_rel_user AS cuser
						  WHERE  user.user_id=cuser.user_id AND te.exe_exo_id = ce.id AND te.status != 'incomplete' AND cuser.user_id=te.exe_user_id AND te.exe_cours_id='" . Database :: escape_string($_cid) . "'
						  AND cuser.status<>1 AND cuser.relation_type<>".COURSE_RELATION_TYPE_RRHH." $user_id_and $session_id_and AND ce.active <>-1 AND orig_lp_id = 0 AND orig_lp_item_id = 0
						  AND cuser.course_code=te.exe_cours_id ORDER BY users, te.exe_cours_id ASC, ce.title ASC, te.exe_date DESC";

		$hpsql = "SELECT '',exe_name, exe_result , exe_weighting, exe_date
						FROM $TBL_TRACK_HOTPOTATOES
						WHERE exe_user_id = '" . $_user['user_id'] . "' AND exe_cours_id = '" . Database :: escape_string($_cid) . "'
						ORDER BY exe_cours_id ASC, exe_date DESC";
	}
	$results = getManyResultsXCol($sql, 12);
	$hpresults = getManyResultsXCol($hpsql, 5);

	$has_test_results = false;
	$list_info = array();

	// Print test results.
	$lang_nostartdate = get_lang('NoStartDate') . ' / ';

	if (is_array($results)) {

		$has_test_results = true;

		$users_array_id = array ();
		if ($_GET['gradebook'] == 'view') {
			$filter_by_no_revised = true;
			$from_gradebook = true;
		}
		$sizeof = sizeof($results);
		$user_list_id = array ();
		$user_list_name = '';
		$quiz_name_list = '';
		$duration_list = '';
		$date_list = '';
		$result_list = '';
		$more_details_list = '';
		for ($i = 0; $i < $sizeof; $i++) {
			$revised = false;
			$sql_exe = 'SELECT exe_id FROM ' . Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING) . '
									  WHERE author != ' . "''" . ' AND exe_id = ' . "'" . Database :: escape_string($results[$i][5]) . "'" . ' LIMIT 1';
			$query = Database::query($sql_exe);

			if (Database :: num_rows($query) > 0) {
				$revised = true;
			}
			if ($filter_by_not_revised && $revised) {
				continue;
			}
			if ($filter_by_revised && !$revised) {
				continue;
			}
			if ($from_gradebook && ($is_allowedToEdit || $is_tutor)) {
				if (in_array($results[$i][1] . $results[$i][0], $users_array_id)) {
					continue;
				}
				$users_array_id[] = $results[$i][1] . $results[$i][0];
			}

			$user_list_name = $results[$i][0];
			$user_list_id[] = $results[$i][9];
			$id = $results[$i][5];
			$mailid = $results[$i][6];
			$user = $results[$i][0];
			$test = $results[$i][1];
			$quiz_name_list = $test;
			$dt = api_convert_and_format_date($results[$i][4], null, date_default_timezone_get());
			$res = $results[$i][2];

			$duration = intval($results[$i][10]);
			// we filter the results if we have the permission to
			if (isset ($results[$i][11]))
				$result_disabled = intval($results[$i][11]);
			else
				$result_disabled = 0;

			if ($result_disabled == 0) {
				$add_start_date = $lang_nostartdate;

				if ($is_allowedToEdit || $is_tutor) {
					$user = $results[$i][0];
				}
				if ($results[$i][7] != "0000-00-00 00:00:00") {
					//echo ceil((($results[$i][4] - $results[$i][7]) / 60)) . ' ' . get_lang('MinMinutes');
					$exe_date_timestamp = api_strtotime($results[$i][4], date_default_timezone_get());
					$start_date_timestamp = api_strtotime($results[$i][7], date_default_timezone_get());
					$duration_list = ceil((($exe_date_timestamp - $start_date_timestamp) / 60)) . ' ' . get_lang('MinMinutes');
					if ($results[$i][8] > 1) {
						//echo ' ( ' . $results[$i][8] . ' ' . get_lang('Steps') . ' )';
						$duration_list = ' ( ' . $results[$i][8] . ' ' . get_lang('Steps') . ' )';
					}
					$add_start_date = api_convert_and_format_date($results[$i][7], null, date_default_timezone_get()) . ' / ';
				} else {
					$duration_list = get_lang('NoLogOfDuration');
					//echo get_lang('NoLogOfDuration');
				}
				// Date conversion
				$date_list = api_get_local_time($results[$i][7], null, date_default_timezone_get()). ' / ' . api_get_local_time($results[$i][4], null, date_default_timezone_get());
				// there are already a duration test period calculated??
				//echo '<td>'.sprintf(get_lang('DurationFormat'), $duration).'</td>';

				// if the float look like 10.00 we show only 10

				$my_res		= float_format($results[$i][2],1);
				$my_total 	= float_format($results[$i][3],1);

				//echo '<td>' . round(($my_res / ($my_total != 0 ? $my_total : 1)) * 100, 2) . '% (' . $my_res . ' / ' . $my_total . ')</td>';
				$result_list = round(($my_res / ($my_total != 0 ? $my_total : 1)) * 100, 2) . '% (' . $my_res . ' / ' . $my_total . ')';
				// Is hard to read this!!
				/*
				echo '<td>'.(($is_allowedToEdit||$is_tutor)?
							"<a href='exercise_show.php?user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".
							(($revised)?get_lang('Edit'):get_lang('Qualify'))."</a>".
							((api_is_platform_admin() || $is_tutor)?' - <a href="exercice.php?cidReq='.htmlentities($_GET['cidReq']).'&show=result&filter='.$filter.'&delete=delete&did='.$id.'" onclick="javascript:if(!confirm(\''.sprintf(get_lang('DeleteAttempt'),$user,$dt).'\')) return false;">'.get_lang('Delete').'</a>':'')
							.(($is_allowedToEdit)?' - <a href="exercice_history.php?cidReq='.htmlentities($_GET['cidReq']).'&exe_id='.$id.'">'.get_lang('ViewHistoryChange').'</a>':'')
							:(($revised)?"<a href='exercise_show.php?dt=$dt&res=$res&id=$id'>".get_lang('Show')."</a>":'')).'</td>';
				*/

				//echo '<td>';
				$html_link = '';
				if ($is_allowedToEdit || $is_tutor) {
					if ($revised) {
						//echo "<a href='exercise_show.php?action=edit&user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".Display :: return_icon('edit.gif', get_lang('Edit'));
						//echo '&nbsp;';
						$html_link.= "<a href='exercise_show.php?".api_get_cidreq()."&action=edit&user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".Display :: return_icon('edit.gif', get_lang('Edit'));
						$html_link.= '&nbsp;';
					} else {
						//echo "<a href='exercise_show.php?action=qualify&user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".Display :: return_icon('quizz_small.gif', get_lang('Qualify'));
						//echo '&nbsp;';
						$html_link.="<a href='exercise_show.php?".api_get_cidreq()."&action=qualify&user=$user&dt=$dt&res=$res&id=$id&email=$mailid'>".Display :: return_icon('quizz_small.gif', get_lang('Qualify'));
						$html_link.='&nbsp;';
					}
					//echo "</a>";
					$html_link.="</a>";
					if (api_is_platform_admin() || $is_tutor) {
						//echo ' <a href="exercice.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&show=result&filter=' . $filter . '&delete=delete&did=' . $id . '" onclick="javascript:if(!confirm(\'' . sprintf(get_lang('DeleteAttempt'), $user, $dt) . '\')) return false;">'.Display :: return_icon('delete.gif', get_lang('Delete')).'</a>';
						//echo '&nbsp;';
						$html_link.=' <a href="exercice.php?'.api_get_cidreq().'&show=result&filter=' . $filter . '&delete=delete&did=' . $id . '" onclick="javascript:if(!confirm(\'' . sprintf(get_lang('DeleteAttempt'), $user, $dt) . '\')) return false;">'.Display :: return_icon('delete.gif', get_lang('Delete')).'</a>';
						$html_link.='&nbsp;';
					}
					if ($is_allowedToEdit) {
						//echo ' <a href="exercice_history.php?cidReq=' . security::remove_XSS($_GET['cidReq']) . '&exe_id=' . $id . '">' .Display :: return_icon('history.gif', get_lang('ViewHistoryChange')).'</a>';
						if ($filter==2){
							$html_link.=' <a href="exercice_history.php?'.api_get_cidreq().'&exe_id=' . $id . '">' .Display :: return_icon('history.gif', get_lang('ViewHistoryChange')).'</a>';
						}
					}
				} else {
					if ($revised) {
						//echo "<a href='exercise_show.php?dt=$dt&res=$res&id=$id'>" . get_lang('Show') . "</a> ";
						$html_link.="<a href='exercise_show.php?".api_get_cidreq()."&dt=$dt&res=$res&id=$id'>" . get_lang('Show') . "</a> ";

					} else {
					//	echo '&nbsp;' . get_lang('NoResult');
						$html_link.='&nbsp;' . get_lang('NoResult');
					}

				}
				$more_details_list = $html_link;
				if ($is_allowedToEdit || $is_tutor) {
					$list_info [] = array($user_list_name,$quiz_name_list,$duration_list,$date_list,$result_list,$more_details_list);
				} else {
					$list_info [] = array($quiz_name_list,$duration_list,$date_list,$result_list,$more_details_list);
				}
				//$list_info [] = array($user_list_name,$quiz_name_list,$duration_list,$date_list,$result_list,$more_details_list);
				//echo '</td>';

				//echo '</tr>';
			}
		}
	}

	// Print HotPotatoes test results.
	if (is_array($hpresults)) {

		$has_test_results = true;

		for ($i = 0; $i < sizeof($hpresults); $i++) {
			$hp_title = GetQuizName($hpresults[$i][1], $documentPath);
			if ($hp_title == '') {
				$hp_title = basename($hpresults[$i][1]);
			}
			//$hp_date = api_convert_and_format_date($hpresults[$i][4], null, date_default_timezone_get());
			$hp_date = api_get_local_time($hpresults[$i][4], null, date_default_timezone_get());
			$hp_result = round(($hpresults[$i][2] / ($hpresults[$i][3] != 0 ? $hpresults[$i][3] : 1)) * 100, 2).'% ('.$hpresults[$i][2].' / '.$hpresults[$i][3].')';
			if ($is_allowedToEdit) {
				$list_info[] = array($hpresults[$i][0], $hp_title, '-', $hp_date , $hp_result , '-');
			} else {
				$list_info[] = array($hp_title, '-', $hp_date , $hp_result , '-');
			}
		}
	}

	if ($has_test_results) {

		$parameters=array('cidReq'=>Security::remove_XSS($_GET['cidReq']),'show'=>Security::remove_XSS($_GET['show']),'filter' => Security::remove_XSS($_GET['filter']),'gradebook' =>Security::remove_XSS($_GET['gradebook']));

		$table = new SortableTableFromArrayConfig($list_info, 1,20,'quiz_table');
		$table->set_additional_parameters($parameters);
		if ($is_allowedToEdit || $is_tutor) {
			$table->set_header(0, get_lang('User'));
			$secuence = 0;
		} else {
			$secuence = 1;
		}
		$table->set_header(-$secuence + 1, get_lang('Exercice'));
		$table->set_header(-$secuence + 2, get_lang('Duration'),false);
		$table->set_header(-$secuence + 3, get_lang('Date'));
		$table->set_header(-$secuence + 4, get_lang('Result'),false);
		$table->set_header(-$secuence + 5, (($is_allowedToEdit||$is_tutor) ? get_lang('CorrectTest') : get_lang('ViewTest')), false);
		$table->display();

	} else {

		echo get_lang('NoResult');

	}

}

if ($origin != 'learnpath') { //so we are not in learnpath tool
	Display :: display_footer();
} else {
?>
<link rel="stylesheet" type="text/css" href="<?php echo $clarolineRepositoryWeb ?>css/default.css" />
<?php
}
?>