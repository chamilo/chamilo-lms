<?php
/* For license terms, see /license.txt */
/**
 * Index of the Advanced subscription plugin courses list
 * @package chamilo.plugin.advancedsubscription
 */
/**
 * Init
 */
require_once __DIR__ . '/../config.php';
// protect
api_protect_admin_script();
// start plugin
$plugin = AdvancedSubscriptionPlugin::create();
// Session ID
$s = isset($_REQUEST['s']) ? intval($_REQUEST['s']) : null;

// Init template
$tpl = new Template('TESTING');
// Get all sessions
$sessionList = $plugin->listAllSessions();

if (!empty($s)) {
    // Get student list in queue
    $studentList = $plugin->listAllStudentsInQueueBySession($s);
    // Set selected to current session
    $sessionList[$s]['selected'] = 'selected="selected"';
    $studentList['session']['id'] = $s;
    // Assign variables

    // send mail to superior
    $sessionArray = api_get_session_info($s);
    $extraSession = new ExtraFieldValue('session');
    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'description');
    $sessionArray['description'] = $var['field_valiue'];
    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'target');
    $sessionArray['target'] = $var['field_valiue'];
    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'mode');
    $sessionArray['mode'] = $var['field_valiue'];
    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'publication_end_date');
    $sessionArray['publication_end_date'] = $var['field_value'];
    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'recommended_number_of_participants');
    $sessionArray['recommended_number_of_participants'] = $var['field_valiue'];
    $adminsArray = UserManager::get_all_administrators();

    $data['a'] = 'confirm';
    $data['s'] = $s;
    $data['current_user_id'] = api_get_user_id();

    foreach ($studentList['students'] as &$student) {
        $data['u'] = intval($student['user_id']);
        $data['q'] = intval($student['queue_id']);
        $data['e'] = ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED;
        $student['acceptUrl'] = $plugin->getQueueUrl($data);
        $data['e'] = ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED;
        $student['rejectUrl'] = $plugin->getQueueUrl($data);
        $student['complete_name'] = $student['lastname'] . ', ' . $student['firstname'];
        $student['picture'] = UserManager::get_user_picture_path_by_id($student['user_id'], 'web', false, true);
        $student['picture'] = UserManager::get_picture_user($student['user_id'], $student['picture']['file'], 22, USER_IMAGE_SIZE_MEDIUM);
    }
    $tpl->assign('session', $studentList['session']);
    $tpl->assign('students', $studentList['students']);
}

// Assign variables
$tpl->assign('sessionItems', $sessionList);
$tpl->assign('approveAdmin', ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED);
$tpl->assign('disapproveAdmin', ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED);
// Get rendered template
$content = $tpl->fetch('/advancedsubscription/views/admin_view.tpl');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
