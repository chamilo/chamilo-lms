<?php
/* For license terms, see /license.txt */
/**
 * Index of the Advanced subscription plugin courses list.
 *
 * @package chamilo.plugin.advanced_subscription
 */
/**
 * Init.
 */
require_once __DIR__.'/../config.php';
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
    $fieldsArray = [
        'description',
        'target',
        'mode',
        'publication_end_date',
        'recommended_number_of_participants',
        'vacancies',
    ];
    $sessionArray = api_get_session_info($sessionId);
    $extraSession = new ExtraFieldValue('session');
    $extraField = new ExtraField('session');
    // Get session fields
    $fieldList = $extraField->get_all([
        'variable IN ( ?, ?, ?, ?, ?, ?)' => $fieldsArray,
    ]);
    // Index session fields
    foreach ($fieldList as $field) {
        $fields[$field['id']] = $field['variable'];
    }
    $params = [' item_id = ? ' => $sessionId];
    $sessionFieldValueList = $extraSession->get_all(['where' => $params]);
    foreach ($sessionFieldValueList as $sessionFieldValue) {
        // Check if session field value is set in session field list
        if (isset($fields[$sessionFieldValue['field_id']])) {
            $var = $fields[$sessionFieldValue['field_id']];
            $val = $sessionFieldValue['value'];
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
        $studentId = intval($student['user_id']);
        $data['studentUserId'] = $studentId;

        $fieldValue = new ExtraFieldValue('user');
        $areaField = $fieldValue->get_values_by_handler_and_field_variable($studentId, 'area', true);

        $student['area'] = $areaField['value'];
        if (substr($student['area'], 0, 6) == 'MINEDU') {
            $student['institution'] = 'Minedu';
        } else {
            $student['institution'] = 'Regiones';
        }
        $student['userLink'] = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$studentId;
        $data['queueId'] = intval($student['queue_id']);
        $data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED;
        $data['profile_completed'] = 100;
        $student['acceptUrl'] = $plugin->getQueueUrl($data);
        $data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_DISAPPROVED;
        $student['rejectUrl'] = $plugin->getQueueUrl($data);
        $student['complete_name'] = $isWesternNameOrder ?
            $student['firstname'].', '.$student['lastname'] : $student['lastname'].', '.$student['firstname'];
    }
    $tpl->assign('session', $sessionArray);
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
