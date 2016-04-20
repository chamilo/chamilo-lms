<?php
/* For license terms, see /license.txt */
/**
 * AJAX Call for wamirecorder
 */
require_once '../global.inc.php';

$action = $_REQUEST['a'];

//$js_path = api_get_path(WEB_LIBRARY_PATH) . 'javascript/';

$courseId = isset($_REQUEST['course_id']) ? $_REQUEST['course_id'] : 0;
$sessionId = isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : 0;
$userId = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
$exerciseId = isset($_REQUEST['exercise_id']) ? $_REQUEST['exercise_id'] : 0;
$questionId = isset($_REQUEST['question_id']) ? $_REQUEST['question_id'] : 0;
$exeId = isset($_REQUEST['exe_id']) ? $_REQUEST['exe_id'] : 0;

$wamiRecorder = new WamiRecorder($courseId, $sessionId, $userId, $exerciseId, $questionId, $exeId);

switch ($action) {
    case 'show_form':
                api_protect_course_script(true);
        		Display::display_reduced_header();
                $wamiRecorder->showJS();
                $wamiRecorder->showForm();
                break;
}
