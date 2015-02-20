<?php

require_once __DIR__ . '/../config.php';

// Protect test
api_protect_admin_script();

// exit;
$plugin = AdvancedSubscriptionPlugin::create();
// Get validation hash
$hash = Security::remove_XSS($_REQUEST['v']);
// Get data from request (GET or POST)
$data['a'] = 'confirm';
$data['current_user_id'] = 1;
$data['q'] = 0;
$data['is_connected'] = true;
$data['profile_completed'] = 90.0;
// Init result array

$data['s'] = 1;
$data['u'] = 4;


// Prepare data
// Get session data
// Assign variables
$fieldsArray = array('description', 'target', 'mode', 'publication_end_date', 'recommended_number_of_participants');
$sessionArray = api_get_session_info($data['s']);
$extraSession = new ExtraFieldValue('session');
$extraField = new ExtraField('session');
// Get session fields
$fieldList = $extraField->get_all(array(
    'field_variable IN ( ?, ?, ?, ?, ?)' => $fieldsArray
));
$fields = array();
// Index session fields
foreach ($fieldList as $field) {
    $fields[$field['id']] = $field['field_variable'];
}

$mergedArray = array_merge(array($data['s']), array_keys($fields));
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
// Get student data
$studentArray = api_get_user_info($data['u']);
$studentArray['picture'] = UserManager::get_user_picture_path_by_id($studentArray['user_id'], 'web', false, true);
$studentArray['picture'] = UserManager::get_picture_user($studentArray['user_id'], $studentArray['picture']['file'], 22, USER_IMAGE_SIZE_MEDIUM);
// Get superior data if exist
$superiorId = UserManager::getStudentBoss($data['u']);
if (!empty($superiorId)) {
    $superiorArray = api_get_user_info($superiorId);
} else {
    $superiorArray = api_get_user_info(3);
}
// Get admin data
$adminsArray = UserManager::get_all_administrators();
$isWesternNameOrder = api_is_western_name_order();
foreach ($adminsArray as &$admin) {
    $admin['complete_name'] = $isWesternNameOrder ?
        $admin['firstname'] . ', ' . $admin['lastname'] :
        $admin['lastname'] . ', ' . $admin['firstname']
    ;
}
unset($admin);
// Set data
$data['a'] = 'confirm';
$data['student'] = $studentArray;
$data['superior'] = $superiorArray;
$data['admins'] = $adminsArray;
$data['admin'] = current($adminsArray);
$data['session'] = $sessionArray;
$data['signature'] = api_get_setting('Institution');
$data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH) .
    'advancedsubscription/src/admin_view.php?s=' . $data['s'];
$data['e'] = ADV_SUB_QUEUE_STATUS_BOSS_APPROVED;
$data['student']['acceptUrl'] = $plugin->getQueueUrl($data);
$data['e'] = ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED;
$data['student']['rejectUrl'] = $plugin->getQueueUrl($data);
$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('data', $data);
$tplParams = array(
    'user',
    'student',
    'students',
    'superior',
    'admins',
    'admin',
    'session',
    'signature',
    'admin_view_url',
    'acceptUrl',
    'rejectUrl'
);
foreach ($tplParams as $tplParam) {
    $tpl->assign($tplParam, $data[$tplParam]);
}

$dir = __DIR__ . '/../views/';
$files = scandir($dir);

echo '<br>', '<pre>' , print_r($files, 1) , '</pre>';

foreach ($files as $k =>&$file) {
    if (
        is_file($dir . $file) &&
        strpos($file, '.tpl') &&
        $file != 'admin_view.tpl'
    ) {
        echo '<pre>', $file, '</pre>';
        echo $tpl->fetch('/advancedsubscription/views/' . $file);
    } else {
        unset($files[$k]);
    }
}
echo '<br>', '<pre>' , print_r($files, 1) , '</pre>';
