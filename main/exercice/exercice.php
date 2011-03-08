<?php
/* For licensing terms, see /license.txt */
/**
*	Exercise list: This script shows the list of exercises for administrators and students.
*	@package chamilo.exercise
*	@author Olivier Brouckaert, original author
*	@author Denes Nagy, HotPotatoes integration
*	@author Wolfgang Schneider, code/html cleanup
*	@author Julio Montoya <gugli100@gmail.com>, lots of cleanup + several improvements
*/

// name of the language file that needs to be included
$language_file = array('exercice','tracking');

// including the global library
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once '../gradebook/lib/be.inc.php';

// Setting the tabs
$this_section = SECTION_COURSES;

// Access control
api_protect_course_script(true);

$show = (isset ($_GET['show']) && $_GET['show'] == 'result') ? 'result' : 'test'; // moved down to fix bug: http://www.dokeos.com/forum/viewtopic.php?p=18609#18609

// including additional libraries
require_once 'exercise.class.php';
require_once 'exercise.lib.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';
require_once 'hotpotatoes.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'document.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'usermanager.lib.php';

/*	Constants and variables */
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
$picturePath  = $documentPath . '/images';
// audio path
$audioPath    = $documentPath . '/audio';

// hotpotatoes
$uploadPath = DIR_HOTPOTATOES; //defined in main_api
$exercicePath = api_get_self();
$exfile = explode('/', $exercicePath);
$exfile = strtolower($exfile[sizeof($exfile) - 1]);
$exercicePath = substr($exercicePath, 0, strpos($exercicePath, $exfile));
$exercicePath = $exercicePath . "exercice.php";


if ($show == 'result') {    
    if (empty($_GET['exerciseId']) && empty($_GET['path']) ) {
       //header('Location: exercice.php?' . api_get_cidreq());
    }
}   
    
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

//General POST/GET/SESSION/COOKIES parameters recovery
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
	$exerciseId = intval($_REQUEST['exerciseId']);
}
if (empty ($file)) {
	$file = Database :: escape_string($_REQUEST['file']);
}
$learnpath_id       = intval($_REQUEST['learnpath_id']);
$learnpath_item_id  = intval($_REQUEST['learnpath_item_id']);
$page               = intval($_REQUEST['page']);

if ($origin == 'learnpath') {
	$show = 'result';
}

//Deleting an attempt
if ($_GET['delete'] == 'delete' && ($is_allowedToEdit || api_is_coach()) && !empty ($_GET['did']) && $_GET['did'] == strval(intval($_GET['did']))) {
	$sql = 'DELETE FROM ' . Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES) . ' WHERE exe_id = ' . $_GET['did']; //_GET[did] filtered by entry condition
	Database::query($sql);
	$filter=Security::remove_XSS($_GET['filter']);
	header('Location: exercice.php?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&show=result&filter=' . $filter . '&exerciseId='.$exerciseId);
	exit;
}

//Send student email @todo move this code in a class, library
if ($show == 'result' && $_REQUEST['comments'] == 'update' && ($is_allowedToEdit || $is_tutor) && $_GET['exeid']== strval(intval($_GET['exeid']))) {
	$id 		= intval($_GET['exeid']); //filtered by post-condition    
	$track_exercise_info = get_exercise_track_exercise_info($id);
    if (empty($track_exercise_info)) {
    	api_not_allowed();
    }
	$test 		       = $track_exercise_info['title'];
	$student_id        = $track_exercise_info['exe_user_id'];
    $course_id         = $track_exercise_info['exe_cours_id'];
    $session_id        = $track_exercise_info['session_id'];
    $lp_id             = $track_exercise_info['orig_lp_id'];
    $lp_item_id        = $track_exercise_info['orig_lp_item_id'];
    $lp_item_view_id   = $track_exercise_info['orig_lp_item_view_id'];
    
	$user_info         = api_get_user_info($student_id);	
	$student_email 	   = $user_info['mail'];
    
	//Teacher data?
	$from 		       = $_SESSION['_user']['mail'];
	$from_name         = api_get_person_name($_SESSION['_user']['firstName'], $_SESSION['_user']['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
	$url		       = api_get_path(WEB_CODE_PATH) . 'exercice/exercice.php?' . api_get_cidreq() . '&show=result';	
	$total_weighting   = $_REQUEST['totalWeighting'];

	$my_post_info      = array();
	$post_content_id   = array();
	$comments_exist    = false;
	foreach ($_POST as $key_index=>$key_value) {
		$my_post_info  = explode('_',$key_index);
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
		$my_marks			= Database::escape_string($_POST['marks_'.$array_content_id_exe[$i]]);
		$contain_comments	= Database::escape_string($_POST['comments_'.$array_content_id_exe[$i]]);
		if (isset($contain_comments)) {
			$my_comments	= Database::escape_string($_POST['comments_'.$array_content_id_exe[$i]]);
		} else {
			$my_comments	= '';
		}
		$my_questionid=$array_content_id_exe[$i];
		$sql = "SELECT question from $TBL_QUESTIONS WHERE id = '$my_questionid'";
		$result =Database::query($sql);
		$ques_name = Database::result($result,0,"question");

		$query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '$my_marks',teacher_comment = '$my_comments' WHERE question_id = '".$my_questionid."' AND exe_id='".$id."'";
		Database::query($query);
        //Not necessary to update the weight
        /*
		$qry = 'SELECT sum(marks) as tot FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.$id;
		$res = Database::query($qry);
		$tot = Database::result($res,0,'tot');                
		//updating also the total weight
		$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '".intval($tot)."', exe_weighting = '".Database::escape_string($total_weighting)."'
					 WHERE exe_Id='".$id."'";
		Database::query($totquery);
        */
		
		//Saving results in the track recording table
		$recording_changes = 'INSERT INTO '.$TBL_TRACK_ATTEMPT_RECORDING.' (exe_id, question_id, marks, insert_date, author, teacher_comment) VALUES
							  ('."'$id','".$my_questionid."','$my_marks','".api_get_utc_datetime()."','".api_get_user_id()."'".',"'.$my_comments.'")';
		Database::query($recording_changes);
	}
	
	$qry = 'SELECT DISTINCT question_id, marks FROM ' . $TBL_TRACK_ATTEMPT . ' where exe_id = ' . $id . ' GROUP BY question_id';
	$res = Database::query($qry);
	$tot = 0;
	while ($row = Database :: fetch_array($res, 'ASSOC')) {
		$tot += $row['marks'];
	}
	$totquery = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '" . floatval($tot) . "' WHERE exe_id='" . $id . "'";
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
	$headers .= "User-Agent: Chamilo/1.8";
	$headers .= "Content-Transfer-Encoding: 7bit";
	$headers .= 'From: ' . $from_name . ' <' . $from . '>' . "\r\n";
	$headers = "From:$from_name\r\nReply-to: $to";
	@api_mail_html($student_email, $student_email, $subject, $mess, $from_name, $from);
    
    //Updating LP score here
    
	if (in_array($origin, array ('tracking_course','user_course','correct_exercise_in_lp'))) {
        
        /*
         * We do not need this because lp_item_view_id comes to the rescue 
         * 
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
		}*/        
        $sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '" . intval($tot) . "' WHERE id = " .$lp_item_view_id;
        Database::query($sql_update_score);

		if ($origin == 'tracking_course') {
			//Redirect to the course detail in lp
            header('location: exercise.php?course=' . Security :: remove_XSS($_GET['course']));            
			//header('location: ../mySpace/lp_tracking.php?course=' . api_get_course_id() . '&origin=' . $origin . '&my_lp_id=' . $lp_item_id . '&lp_id=' . $lp_id . '&student_id=' . $student_id.'&extend_attempt=1&from='.Security::remove_XSS($_GET['from']));
			exit;
		} else {
			//Redirect to the reporting
			header('location: ../mySpace/myStudents.php?origin=' . $origin . '&student=' . $student_id . '&details=true&course=' . $course_id.'&session_id='.$session_id);
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
	$interbreadcrumb[] = array ('url' => '../gradebook/' . $_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
}

if ($show != 'result') {
	$nameTools = get_lang('Exercices');
} else {
	if ($is_allowedToEdit || $is_tutor) {
		$nameTools = get_lang('StudentScore');
		$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
	} else {
		$nameTools = get_lang('YourScore');
		$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));
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
				$export->exportCompleteReportXLS($documentPath, $user_id, $_SESSION['export_user_fields'], $_POST['export_filter'],$_POST['exerciseId'], $_POST['hotpotato_name']);
				exit;
				break;
			case 'csv' :
			default :
				$export = new ExerciseResult();
				$export->exportCompleteReportCSV($documentPath, $user_id, $_SESSION['export_user_fields'], $_POST['export_filter'],$_POST['exerciseId'], $_POST['hotpotato_name']);
				exit;
				break;
		}
	} else {
		api_not_allowed(true);
	}
}

if ($origin != 'learnpath') {
	//so we are not in learnpath tool
	Display :: display_header($nameTools, get_lang('Exercise'));
	if (isset ($_GET['message'])) {
		if (in_array($_GET['message'], array ('ExerciseEdited'))) {
			Display :: display_confirmation_message(get_lang($_GET['message']));
		}
	}
} else {
	echo '<link rel="stylesheet" type="text/css" href="' . api_get_path(WEB_CODE_PATH) . 'css/default.css"/>';
}

event_access_tool(TOOL_QUIZ);

// Tool introduction
Display :: display_introduction_section(TOOL_QUIZ);

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
}

// Actions div bar
echo '<div class="actions">';

// Display the next and previous link if needed
// Selects $limitExPage exercises at the same time
$from = $page * $limitExPage;
$sql = "SELECT count(id) FROM $TBL_EXERCICES";
$res = Database::query($sql);
list ($nbrexerc) = Database :: fetch_array($res);
HotPotGCt($documentPath, 1, api_get_user_id());

//condition for the session
$session_id         = api_get_session_id();
$condition_session  = api_get_session_condition($session_id,true,true);

if ($show == 'test') {
    
    // Only for administrators
    if ($is_allowedToEdit) {
        $sql = "SELECT id, title, type, active, description, results_disabled, session_id, start_time, end_time, random, max_attempt FROM $TBL_EXERCICES WHERE active<>'-1' $condition_session ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
        $result = Database::query($sql);   
    } else { 
        // Only for students
        $sql = "SELECT id, title, type, description, results_disabled, session_id, start_time, end_time , max_attempt FROM $TBL_EXERCICES WHERE active='1' $condition_session ORDER BY title LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
        $result = Database::query($sql);
    }
        
	$nbrExercises = Database :: num_rows($result);

	//get HotPotatoes files (active and inactive)
	$res = Database::query("SELECT * FROM $TBL_DOCUMENT WHERE path LIKE '" . Database :: escape_string($uploadPath) . "/%/%'");
	$nbrTests = Database :: num_rows($res);	
	$res = Database::query("SELECT * FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
    						WHERE d.id = ip.ref  AND ip.tool = '" . TOOL_DOCUMENT . "'
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
		echo "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page -1) . "\">" . Display :: return_icon('previous.gif') . get_lang('PreviousPage') . "</a> | ";
	} elseif ($nbrExercises + $nbrNextTests > $limitExPage) {
		echo Display :: return_icon('previous.gif') . get_lang('PreviousPage') . " | ";
	}

	//show pages navigation link for previous page
	if ($nbrExercises + $nbrNextTests > $limitExPage) {
		echo "<a href=\"" . api_get_self() . "?" . api_get_cidreq() . "&amp;page=" . ($page +1) . "\">" . get_lang("NextPage") . Display :: return_icon('next.gif') . "</a>";
	} elseif ($page) {
		echo get_lang('NextPage') . Display :: return_icon('next.gif');
	}
	echo '</span>';
}

if ($is_allowedToEdit && $origin != 'learnpath') {
	if ($_GET['show'] != 'result') {
		echo '<a href="exercise_admin.php?' . api_get_cidreq() . '">' . Display :: return_icon('new_exercice.png', get_lang('NewEx'),'','32').'</a>';
		echo '<a href="question_create.php?' . api_get_cidreq() . '">' . Display :: return_icon('new_question.png', get_lang('AddQ'),'','32').'</a>';
		echo '<a href="hotpotatoes.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_hotpotatoes.png', get_lang('ImportHotPotatoesQuiz'),'','32').'</a>';
		// link to import qti2 ...
		echo '<a href="qti2.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_qti2.png', get_lang('ImportQtiQuiz'),'','32') .'</a>';
                echo '<a href="upload_exercise.php?' . api_get_cidreq() . '">' . Display :: return_icon('import_excel.png', get_lang('ImportExcelQuiz'),'','32') .'</a>';
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
			echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=test') . '">' . Display :: return_icon('back.png', get_lang('GoBackToQuestionList'),'','32').'</a>';
			echo '<a href="javascript: void(0);" onclick="javascript: document.form1a.submit();">'.Display::return_icon('export_csv.png',get_lang('ExportAsCSV'),'','32').'</a>';
			echo '<a href="javascript: void(0);" onclick="javascript: document.form1b.submit();">' . Display :: return_icon('export_excel.png', get_lang('ExportAsXLS'),'','32').'</a>';
			echo '<form id="form1a" name="form1a" method="post" action="' . api_get_self() . '?show=' . Security :: remove_XSS($_GET['show']) . '" style="display:inline">';
			echo '<input type="hidden" name="export_report" value="export_report">';
			echo '<input type="hidden" name="export_format" value="csv">';
            echo '<input type="hidden" name="exerciseId" value="'.intval($_GET['exerciseId']).'">';
            echo '<input type="hidden" name="hotpotato_name" value="'.Security::remove_XSS($_GET['path']).'">';            
            
            if ($_GET['filter'] == '1' or !isset ($_GET['filter']) or $_GET['filter'] == 0 ) {
                $filter = 1;
            } else {
            	$filter = 2;
            }            
            echo '<input type="hidden" name="export_filter" value="'.(empty($filter)?1:intval($filter)).'">';
			echo '</form>';
			echo '<form id="form1b" name="form1b" method="post" action="' . api_get_self() . '?show=' . Security :: remove_XSS($_GET['show']) . '" style="display:inline">';
			echo '<input type="hidden" name="export_report" value="export_report">';
			echo '<input type="hidden" name="export_filter" value="'.(empty($filter)?1:intval($filter)).'">';			
			echo '<input type="hidden" name="hotpotato_name" value="'.Security::remove_XSS($_GET['path']).'">';			
			echo '<input type="hidden" name="export_format" value="xls">';
            echo '<input type="hidden" name="exerciseId" value="'.intval($_GET['exerciseId']).'">';
			echo '</form>';
		}
	}
} else {
	//Student view
	if ($show == 'result') {
		echo '<a href="' . api_add_url_param($_SERVER['REQUEST_URI'], 'show=test') . '">' . Display :: return_icon('back.png', get_lang('GoBackToQuestionList'),'','32').'</a>';
	} else {
		//echo '<a href="' . api_get_self() .'?'.api_get_cidreq().'&show=result'.'">' . Display :: return_icon('show_test_results.gif', get_lang('Results')) . get_lang('Results') . '</a>';
	}
}

if ($show == 'result') {
	if (api_is_allowed_to_edit(null,true)) {
		if (!$_GET['filter']) {
			$filter_by_not_revised = true;
			$filter = 1;
		} else {
			$filter=Security::remove_XSS($_GET['filter']);
		}
		$filter = (int)$_GET['filter'];

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
		if (!empty($_GET['exerciseId'])) {
    		if ($_GET['filter'] == '1' or !isset ($_GET['filter']) or $_GET['filter'] == 0 ) {
    			$view_result = '<a href="' . api_get_self() . '?cidReq=' . api_get_course_id() . '&show=result&filter=2&id_session='.intval($_GET['id_session']).'&exerciseId='.intval($_GET['exerciseId']).'&gradebook='.$gradebook.'" >'.Display :: return_icon('exercice_check.png', get_lang('ShowCorrectedOnly'),'','32').'</a>';
    		} else {
    			$view_result = '<a href="' .api_get_self() . '?cidReq=' . api_get_course_id() . '&show=result&filter=1&id_session='.intval($_GET['id_session']).'&exerciseId='.intval($_GET['exerciseId']).'&gradebook='.$gradebook.'" >'.Display :: return_icon('exercice_uncheck.png', get_lang('ShowUnCorrectedOnly'),'','32').'</a>';
    		}
    		echo $view_result;
		}
	}
}
echo '</div>'; // closing the actions div

if ($show == 'test') {
    ?>    
    <script>
    $(function() {
        /*
                
        $( "a", ".operations" ).button();        
        $(".tabs-left").tabs().addClass('ui-tabs-vertical ui-helper-clearfix');
        $(".tabs-left li").removeClass('ui-corner-top').addClass('ui-corner-left');
        
        */
    });
    </script>
    <style>
        /*
        New interface not yet ready for 1.8.8
         Vertical Tabs 
        .ui-tabs-vertical { width: 99%; }
        .ui-tabs-vertical .ui-tabs-nav { padding: .2em .1em .2em .2em; float: left; width: 20%; }
        .ui-tabs-vertical .ui-tabs-nav li { clear: left; width: 100%; border-bottom-width: 1px !important; border-right-width: 0 !important; margin: 0 -1px .2em 0;   white-space:normal;}
        .ui-tabs-vertical .ui-tabs-nav li a { display:block; width:100%; }
        .ui-tabs-vertical .ui-tabs-nav li.ui-tabs-selected { padding-bottom: 0; padding-right: .1em; border-right-width: 1px; border-right-width: 1px; }
        .ui-tabs-vertical .ui-tabs-panel { padding: 1em; float: left; width: 40em;}     
        
        */       
    </style>      
  
  <?php    
    $i =1;
    $lis = '';
    $exercise_list = array();
    $online_icon  = Display::return_icon('online.png', get_lang('Visible'),array('width'=>'12px'));
    $offline_icon = Display::return_icon('offline.png',get_lang('Invisible'),array('width'=>'12px'));
    
    while ($row = Database :: fetch_array($result,'ASSOC')) {        
        /*$status = $online_icon;
        if (empty($row['active'])) {
            $status = $offline_icon;    
        }
        if (!(api_is_platform_admin() || api_is_allowed_to_edit()) ) {
        	$status = '';
        }
        $lis.= Display::tag('li','<a href="#tabs-'.$i.'">'.$status.' '.$row['title'].'</a>');
        $i++;*/
        $exercise_list[] = $row;
    }    
    if (!empty($exercise_list)) {
        
        //echo '<div id="exercise_tabs" class="tabs-left">';
        echo '<div>';
        //echo Display::tag('ul', $lis);
    
        /*  Listing exercises  */
        
        if ($origin != 'learnpath') {
            //avoid sending empty parameters
            $myorigin     = (empty ($origin)              ? '' : '&origin=' . $origin);
            $mylpid       = (empty ($learnpath_id)        ? '' : '&learnpath_id=' . $learnpath_id);
            $mylpitemid   = (empty ($learnpath_item_id)   ? '' : '&learnpath_item_id=' . $learnpath_item_id);
    
            $token = Security::get_token();
            $i=1;
            echo '<table class="data_table">';
            if ($is_allowedToEdit) {
                $headers = array(get_lang('ExerciseName'),get_lang('QuantityQuestions'), get_lang('Actions'));
            } else {
            	$headers = array(get_lang('ExerciseName'), get_lang('Attempts'), get_lang('Status'), get_lang('Actions'));
            }
            $header_list = '';
            foreach($headers as $header) {
                $header_list .= Display::tag('th',$header);	
            }
            echo Display::tag('tr',$header_list);
            
            if (!empty($exercise_list))
            foreach ($exercise_list as $row) {
                
                //echo '<div  id="tabs-'.$i.'">';                
                           
                $i++;                    
                //validacion when belongs to a session
                $session_img = api_get_session_image($row['session_id'], $_user['status']);
                
                $time_limits = false;                            
                if ($row['start_time'] != '0000-00-00 00:00:00' && $row['end_time'] != '0000-00-00 00:00:00') {
                    $time_limits = true;    
                }                        
                if ($time_limits) {
                    // check if start time
                    $start_time = api_strtotime($row['start_time'],'UTC');
                    $end_time   = api_strtotime($row['end_time'],'UTC');                                      
                    $now        = time();
                    $is_actived_time = false;                    
                    if ($now > $start_time && $end_time > $now ) {
                        $is_actived_time = true;
                    }
                }                
                      
                // Teacher only
                if ($is_allowedToEdit) {
                                    
                    //Showing exercise title
                    $row['title']=text_filter($row['title']);
                    //echo Display::tag('h1',$row['title']);                             
                     
                    if ($session_id == $row['session_id']) {
                        //Settings                                                                
                        //echo Display::url(Display::return_icon('settings.png',get_lang('Edit'), array('width'=>'22px'))." ".get_lang('Edit'), 'exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$row['id']);
                    }                                  
                    //echo '<p>';
                    //echo $session_img;
                    
                    $url = '<a href="exercice_submit.php?'.api_get_cidreq().$myorigin.$mylpid.$myllpitemid.'&exerciseId='.$row['id'].'"><img src="../img/quiz.gif" alt="HotPotatoes" /> '.$row['title'].'</a>';                    
                    $item =  Display::tag('td',$url.' '.$session_img);  
                    $exid = $row['id'];
    
                    //count number exercice - teacher
                    $sqlquery   = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE exercice_id = '" . $exid . "'";
                    $sqlresult  = Database::query($sqlquery);
                    $rowi       = Database :: result($sqlresult, 0);
                                        
                    if ($session_id == $row['session_id']) {
                        //Settings                                                                
                        //$actions  = Display::url(Display::return_icon('edit.png',get_lang('Edit'), array('width'=>'20px')), 'exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$row['id']);
                        
                        $actions =  Display::url(Display::return_icon('edit.png',get_lang('Edit'),array('width' =>'22px')), 'admin.php?'.api_get_cidreq().'&exerciseId='.$row['id']);
                        
                        $actions .='<a href="exercice.php?' . api_get_cidreq() . '&show=result&exerciseId='.$row['id'].'">' . Display :: return_icon('show_test_results.gif', get_lang('Results')).'</a>';
                        
                        //Export
                        $actions .= Display::url(Display::return_icon('cd.gif',          get_lang('CopyExercise')),       '', array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToCopy'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=copy_exercise&sec_token='.$token.'&exerciseId='.$row['id']));
                        //Clean exercise                    
                        $actions .= Display::url(Display::return_icon('clean_group.png', get_lang('CleanStudentResults')),'', array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToDeleteResults'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=clean_results&sec_token='.$token.'&exerciseId='.$row['id']));                      
                        //Visible / invisible
                        if ($row['active']) {
                            $actions .= Display::url(Display::return_icon('visible.gif', get_lang('Deactivate')) , 'exercice.php?'.api_get_cidreq().'&choice=disable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']);                        
                        } else { // else if not active                    
                            $actions .= Display::url(Display::return_icon('invisible.gif', get_lang('Activate')) , 'exercice.php?'.api_get_cidreq().'&choice=enable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']);                      
                        }                        
                        // Export qti ...                    
                        $actions .= Display::url(Display::return_icon('export_qti2.png','IMS/QTI','','22'),        'exercice.php?choice=exportqti2&exerciseId='.$row['id']);
                    } else { // not session resource                
                        $actions = Display::return_icon('edit_na.png', get_lang('ExerciseEditionNotAvailableInSession'));                        
                        $actions .='<a href="exercice.php?' . api_get_cidreq() . '&show=result&exerciseId='.$row['id'].'">' . Display :: return_icon('show_test_results.gif', get_lang('Results')).'</a>';                        
                        $actions .= Display::url(Display::return_icon('cd.gif',   get_lang('CopyExercise')),     '',  array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToCopy'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=copy_exercise&sec_token='.$token.'&exerciseId='.$row['id']));                           
                    }
                    
                    //Delete
                    if ($session_id == $row['session_id']) {
                        $actions .= Display::url(Display::return_icon('delete.png', get_lang('Delete')), '', array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToDelete'),ENT_QUOTES,$charset))." ".addslashes($row['title'])."?"."')) return false;",'href'=>'exercice.php?'.api_get_cidreq().'&choice=delete&sec_token='.$token.'&exerciseId='.$row['id']));            
                    }
                    //$actions .= '<br />';

                    $random_label = '';                    
                    if ($row['random'] > 0) {
                       $random_label = ' ('.get_lang('Random').') ';
                       $number_of_questions = $row['random'] . ' ' . api_strtolower(get_lang(($row['random'] > 1 ? 'Questions' : 'Question'))) .$random_label;
                    } else {                    
                       $number_of_questions = $rowi . ' ' . api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question')));
                    }                
     
                    //Attempts                    
                    //$attempts = get_count_exam_results($row['id']).' '.get_lang('Attempts');
                    
                    //$item .=  Display::tag('td',$attempts);
                    $item .=  Display::tag('td',$number_of_questions);
                    $item .=  Display::tag('td',$actions);    
                    
                    //Special buttons
                    /*
                    echo '<div class="operations">';
                        echo Display::url(Display::return_icon('quiz.gif',get_lang('Questions'), array('width'=>'22px'))." ".get_lang('Questions'),'admin.php?'.api_get_cidreq().'&exerciseId='.$row['id']);
                        echo ' ';      
                        echo Display::url(Display::return_icon('preview.gif',get_lang('Preview'), array('width'=>'22px'))." ".get_lang('Preview'), 'exercice_submit.php?'.api_get_cidreq().$myorigin.$mylpid.$mylpitemid.'&exerciseId='.$row['id']);
                        echo ' ';
                        echo Display::url(Display::return_icon('show_test_results.gif',get_lang('Results'), array('width'=>'22px'))." ".get_lang('Results'), 'exercice.php?'.api_get_cidreq().'&show=result&exerciseId='.$row['id']);
                    echo '</div>';*/
                    echo  Display::tag('tr',$item);
                } else {
                     
                    // Student only
                    
                    $row['title'] = text_filter($row['title']);                 
    
                    // if time is actived show link to exercise
                    if ($time_limits) {                 
                        if ($is_actived_time) {
                            $url =  '<a href="exercice_submit.php?'.api_get_cidreq().$myorigin.$mylpid.$myllpitemid.'&exerciseId='.$row['id'].'">'.$row['title'].'</a>';
                        } else {
                            $url = $row['title'];                            
                        }                       
                    } else {
                        $url = '<a href="exercice_submit.php?'.api_get_cidreq().$myorigin.$mylpid.$myllpitemid.'&exerciseId='.$row['id'].'">'.$row['title'].'</a>';                       
                    }                   
                    
                    //Link of the exercise             
                    $item =  Display::tag('td',$url.' '.$session_img);  
                           
                    //count number exercise questions
                    $sqlquery   = "SELECT count(*) FROM $TBL_EXERCICE_QUESTION WHERE exercice_id = '" . $row['id'] . "'";
                    $sqlresult  = Database::query($sqlquery);
                    $rowi       = Database::result($sqlresult, 0);
                    
                    if ($row['random'] > 0) {
                        $row['random'] . ' ' . api_strtolower(get_lang(($row['random'] > 1 ? 'Questions' : 'Question')));
                    } else {
                        //show results student
                        $rowi . ' ' . api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question')));
                    }      
                                        
                    //This query might be improved later on by ordering by the new "tms" field rather than by exe_id
                    $qry = "SELECT * FROM $TBL_TRACK_EXERCICES
                            WHERE exe_exo_id = '" . $row['id'] . "' and exe_user_id = '" . api_get_user_id() . "' AND exe_cours_id = '" . api_get_course_id() . "' AND status <>'incomplete' AND orig_lp_id = 0 AND orig_lp_item_id = 0 AND session_id =  '" . api_get_session_id() . "'
                            ORDER BY exe_id DESC";
                    $qryres = Database::query($qry);
                    $num    = Database :: num_rows($qryres);
            
                    //Hide the results
                    $my_result_disabled = $row['results_disabled'];
                    
                    //Time limits are on    
                    if ($time_limits) {
                        // Examn is ready to be taken    
                        if ($is_actived_time) {                     
                            if ($my_result_disabled == 0) {                   
                                if ($num > 0) {
                                    $row_track = Database :: fetch_array($qryres);                                
                                    $attempt_text =  get_lang('LatestAttempt') . ' : ';                                
                                    $attempt_text .= show_score($row_track['exe_result'], $row_track['exe_weighting']);
                                } else {
                                    //$attempt_text =  get_lang('NotAttempted');
                                    $attempt_text =  sprintf(get_lang('ExerciseWillBeActivatedFromXToY'), api_convert_and_format_date($row['start_time']), api_convert_and_format_date($row['end_time']));
                                }                           
                            } else {
                                $attempt_text =  get_lang('CantShowResults');
                            }
                        } else {
                            //Examn not ready
                            //$attempt_text = get_lang('ExamNotAvailableAtThisTime');
                            $attempt_text =  sprintf(get_lang('ExerciseWillBeActivatedFromXToY'), api_convert_and_format_date($row['start_time']), api_convert_and_format_date($row['end_time']));
                        }
                    } else {
                        //Normal behaviour
                        //Show results
                        if ($my_result_disabled == 0) {                         
                            if ($num > 0) {
                                $row_track = Database :: fetch_array($qryres);                                
                                $attempt_text =  get_lang('LatestAttempt') . ' : ';                                
                                $attempt_text .= show_score($row_track['exe_result'], $row_track['exe_weighting']);                                
                            } else {
                                $attempt_text =  get_lang('NotAttempted');
                            }
                        } else {                            
                            $attempt_text = get_lang('CantShowResults');
                        }
                    }
                    
                    //User Attempts    
                    if (empty($row['max_attempt'])) {
                        $item .=  Display::tag('td',$num);     
                    } else {
                        if (empty($num)) {
                        	$num = 0;
                        }
                        $item .=  Display::tag('td',$num.' / '.$row['max_attempt']);                        
                    }
                    $item .=  Display::tag('td', $attempt_text);                    
                    //See results
                    $actions ='<a href="exercice.php?' . api_get_cidreq() . '&show=result&exerciseId='.$row['id'].'">' . Display :: return_icon('show_test_results.gif', get_lang('Results')).'</a>';                    
                    $item .=  Display::tag('td', $actions);
                                     
                    echo Display::tag('tr',$item);                  
                }
                /*echo '</p>';
                echo '</div>';*/
                
            } // end foreach()
            
            
            //Hotpotatoes results        
            
            if ($is_allowedToEdit) {
                $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
                        FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
                        WHERE   d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND (d.path LIKE '%htm%')
                        AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' LIMIT " . (int) $from . "," . (int) ($limitExPage +1); // only .htm or .html files listed
            } else {
                $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
                        FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
                        WHERE d.id = ip.ref AND ip.tool = '" . TOOL_DOCUMENT . "' AND (d.path LIKE '%htm%')
                        AND   d.path  LIKE '" . Database :: escape_string($uploadPath) . "/%/%' AND ip.visibility='1' LIMIT " . (int) $from . "," . (int) ($limitExPage +1);
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
                    $item = '';
                    list ($a, $vis) = each($attribute['visibility']);
                    if (strcmp($vis, "1") == 0) {
                        $active = 1;
                    } else {
                        $active = 0;
                    }
                    $title = GetQuizName($path, $documentPath);
                    if ($title == '') {
                        $title = basename($path);
                    }
                    // prof only
                    if ($is_allowedToEdit) {
                        $item  = Display::tag('td','<img src="../img/hotpotatoes_s.png" alt="HotPotatoes" /> <a href="showinframes.php?file='.$path.'&cid='.api_get_course_id().'&uid='.api_get_user_id().'"'.(!$active?'class="invisible"':'').'>'.$title.'</a> ');
                        $item .= Display::tag('td','-');
                                         
                        $actions =  Display::url(Display::return_icon('edit.png',get_lang('Edit'),array('width' =>'22px')), 'adminhp.php?'.api_get_cidreq().'&hotpotatoesName='.$path);
                        $actions .='<a href="exercice.php?' . api_get_cidreq() . '&show=result&path='.$path.'">' . Display :: return_icon('show_test_results.gif', get_lang('Results')).'</a>';
                                                
                        // if active
                        if ($active) {
                            $nbrActiveTests = $nbrActiveTests +1;
                            $actions .= '      <a href="'.$exercicePath.'?'.api_get_cidreq().'&hpchoice=disable&amp;page='.$page.'&amp;file='.$path.'"><img src="../img/visible.gif" border="0" title="'.get_lang('Deactivate').'" alt="'.api_htmlentities(get_lang('Deactivate'),ENT_QUOTES,$charset).'" /></a>';
                        } else { // else if not active
                            $actions .='    <a href="'.$exercicePath.'?'.api_get_cidreq().'&hpchoice=enable&amp;page='.$page.'&amp;file='.$path.'"><img src="../img/invisible.gif" border="0" title="'.get_lang('Activate').'" alt="'.api_htmlentities(get_lang('Activate'),ENT_QUOTES,$charset).'" /></a>';
                        }
                        $actions .= '<a href="'.$exercicePath.'?'.api_get_cidreq().'&amp;hpchoice=delete&amp;file='.$path.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('AreYouSureToDelete'),ENT_QUOTES,$charset).' '.$title."?").'\')) return false;"><img src="../img/delete.png" border="0" title="'.get_lang('Delete').'" alt="'.api_htmlentities(get_lang('Delete'),ENT_QUOTES,$charset).'" /></a>';
                                                
                        //$actions .='<img src="../img/lp_quiz_na.gif" border="0" title="'.get_lang('NotMarkActivity').'" alt="" />';
                        $item .= Display::tag('td', $actions);
                        echo Display::tag('tr',$item);                     
                    } else { // student only
                        if ($active == 1) {
                            $nbrActiveTests = $nbrActiveTests +1;
                            $item .= Display::tag('td', '<a href="showinframes.php?'.api_get_cidreq().'&file='.$path.'&cid='.api_get_course_id().'&uid='.api_get_user_id().'"'.(!$active?'class="invisible"':'').'">'.$title.'</a>');
                            $item .= Display::tag('td', '');                            
                            $item .= Display::tag('td', '');
                            $actions ='<a href="exercice.php?' . api_get_cidreq() . '&show=result&path='.$path.'">' . Display :: return_icon('show_test_results.gif', get_lang('Results')).'</a>';
                            $item .= Display::tag('td', $actions);                            
                            echo Display::tag('tr',$item);
                        }                        
                    }
                }
            }
            echo '</table>';
            echo '</div>';
        }         
    } else {
        echo Display::display_warning_message(get_lang('NoExercises'));
    }
    Display :: display_footer();    
    exit;
}
    

/* Exercise Results (uses tracking tool) */

// if tracking is enabled
if ($show == 'result') {
	$parameters=array('cidReq'=>Security::remove_XSS($_GET['cidReq']),'show'=>Security::remove_XSS($_GET['show']),'filter' => Security::remove_XSS($_GET['filter']),'gradebook' =>Security::remove_XSS($_GET['gradebook']));
    $exercise_id = intval($_GET['exerciseId']);
    if (!empty($exercise_id))
        $parameters['exerciseId'] = $exercise_id;
    if (!empty($_GET['path'])) {
        $parameters['path'] = Security::remove_XSS($_GET['path']);
    }
	$table = new SortableTable('quiz_results', 'get_count_exam_results', 'get_exam_results_data');
	$table->set_additional_parameters($parameters);
    
    if ($is_allowedToEdit || $is_tutor) {
		if (api_is_western_name_order()) {
			$table->set_header(0, get_lang('FirstName'));
			$table->set_header(1, get_lang('LastName'));    			
		} else {
			$table->set_header(0, get_lang('LastName'));
			$table->set_header(1, get_lang('FirstName'));    			
		}		
		$table->set_header(2, get_lang('Exercice'));
    	$table->set_header(3, get_lang('Duration'),false);
    	$table->set_header(4, get_lang('Date'));
    	$table->set_header(5, get_lang('Score'),false);
    	$table->set_header(6, get_lang('CorrectTest'), false);   
    	
    } else {
        $table->set_header(0, get_lang('Exercice'));
    	$table->set_header(1, get_lang('Duration'),false);
    	$table->set_header(2, get_lang('Date'));
    	$table->set_header(3, get_lang('Score'),false);
    	$table->set_header(4, get_lang('Result'), false);   
    }
	 
	$table->display();	
}

if ($origin != 'learnpath') { //so we are not in learnpath tool
	Display :: display_footer();
}
