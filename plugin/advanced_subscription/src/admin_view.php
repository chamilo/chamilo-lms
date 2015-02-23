<?php
/* For license terms, see /license.txt */
/**
 * Index of the Advanced subscription plugin courses list
 * @package chamilo.plugin.advanced_subscription
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
$sessionId = isset($_REQUEST['s']) ? intval($_REQUEST['s']) : null;

// Init template
$tpl = new Template($plugin->get_lang('plugin_title'));
// Get all sessions
$sessionList = $plugin->listAllSessions();

if (!empty($sessionId)) {
    // Get student list in queue
    $studentList = $plugin->listAllStudentsInQueueBySession($sessionId);
    // Set selected to current session
    $sessionList[$sessionId]['selected'] = 'selected="selected"';
    $studentList['session']['id'] = $sessionId;
    // Assign variables
    $fieldsArray = array('description', 'target', 'mode', 'publication_end_date', 'recommended_number_of_participants');
    $sessionArray = api_get_session_info($sessionId);
    $extraSession = new ExtraFieldValue('session');
    $extraField = new ExtraField('session');
    // Get session fields
    $fieldList = $extraField->get_all(array(
        'field_variable IN ( ?, ?, ?, ?, ?)' => $fieldsArray
    ));
    // Index session fields
    foreach ($fieldList as $field) {
        $fields[$field['id']] = $field['field_variable'];
    }

    $mergedArray = array_merge(array($sessionId), array_keys($fields));
    $sessionFieldValueList = $extraSession->get_all(array('session_id = ? field_id IN ( ?, ?, ?, ?, ?, ?, ? )' => $mergedArray));
    foreach ($sessionFieldValueList as $sessionFieldValue) {
            // Check if session field value is set in session field list
        if (isset($fields[$sessionFieldValue['field_id']])) {
            $var = $fields[$sessionFieldValue['field_id']];
            $val = $sessionFieldValue['field_value'];
            // Assign session field value to session
            $sessionArray[$var] = $val;
        }
    }
    $adminsArray = UserManager::get_all_administrators();

    $data['action'] = 'confirm';
    $data['sessionId'] = $sessionId;
    $data['currentUserId'] = api_get_user_id();
    $isWesternNameOrder = api_is_western_name_order();

    foreach ($studentList['students'] as &$student) {
        $data['studentUserId'] = intval($student['user_id']);
        $data['queueId'] = intval($student['queue_id']);
        $data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED;
        $student['acceptUrl'] = $plugin->getQueueUrl($data);
        $data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_DISAPPROVED;
        $student['rejectUrl'] = $plugin->getQueueUrl($data);
        $student['complete_name'] = $isWesternNameOrder ?
            $student['firstname'] . ', ' . $student['lastname'] :
            $student['lastname'] . ', ' . $student['firstname']
        ;
        $student['picture'] = UserManager::get_user_picture_path_by_id($student['user_id'], 'web', false, true);
        $student['picture'] = UserManager::get_picture_user($student['user_id'], $student['picture']['file'], 22, USER_IMAGE_SIZE_MEDIUM);
    }
    $tpl->assign('session', $studentList['session']);
    $tpl->assign('students', $studentList['students']);
}

// Assign variables
$tpl->assign('sessionItems', $sessionList);
$tpl->assign('approveAdmin', ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED);
$tpl->assign('disapproveAdmin', ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_DISAPPROVED);
// Get rendered template
$content = $tpl->fetch('/advanced_subscription/views/admin_view.tpl');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
