<?php
/* For license terms, see /license.txt */
/**
 * A script to render all mails templates.
 *
 * @package chamilo.plugin.advanced_subscription
 */
require_once __DIR__.'/../config.php';

// Protect test
api_protect_admin_script();

// exit;
$plugin = AdvancedSubscriptionPlugin::create();
// Get validation hash
$hash = Security::remove_XSS($_REQUEST['v']);
// Get data from request (GET or POST)
$data['action'] = 'confirm';
$data['currentUserId'] = 1;
$data['queueId'] = 0;
$data['is_connected'] = true;
$data['profile_completed'] = 90.0;
// Init result array

$data['sessionId'] = 1;
$data['studentUserId'] = 4;

// Prepare data
// Get session data
// Assign variables
$fieldsArray = [
    'description',
    'target',
    'mode',
    'publication_end_date',
    'recommended_number_of_participants',
];
$sessionArray = api_get_session_info($data['sessionId']);
$extraSession = new ExtraFieldValue('session');
$extraField = new ExtraField('session');
// Get session fields
$fieldList = $extraField->get_all([
    'variable IN ( ?, ?, ?, ?, ?)' => $fieldsArray,
]);
$fields = [];
// Index session fields
foreach ($fieldList as $field) {
    $fields[$field['id']] = $field['variable'];
}

$mergedArray = array_merge([$data['sessionId']], array_keys($fields));
$sessionFieldValueList = $extraSession->get_all(
    ['item_id = ? field_id IN ( ?, ?, ?, ?, ?, ?, ? )' => $mergedArray]
);
foreach ($sessionFieldValueList as $sessionFieldValue) {
    // Check if session field value is set in session field list
    if (isset($fields[$sessionFieldValue['field_id']])) {
        $var = $fields[$sessionFieldValue['field_id']];
        $val = $sessionFieldValue['value'];
        // Assign session field value to session
        $sessionArray[$var] = $val;
    }
}
// Get student data
$studentArray = api_get_user_info($data['studentUserId']);
$studentArray['picture'] = $studentArray['avatar'];

// Get superior data if exist
$superiorId = UserManager::getFirstStudentBoss($data['studentUserId']);
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
        $admin['firstname'].', '.$admin['lastname'] : $admin['lastname'].', '.$admin['firstname']
    ;
}
unset($admin);
// Set data
$data['action'] = 'confirm';
$data['student'] = $studentArray;
$data['superior'] = $superiorArray;
$data['admins'] = $adminsArray;
$data['admin'] = current($adminsArray);
$data['session'] = $sessionArray;
$data['signature'] = api_get_setting('Institution');
$data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH).
    'advanced_subscription/src/admin_view.php?s='.$data['sessionId'];
$data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED;
$data['student']['acceptUrl'] = $plugin->getQueueUrl($data);
$data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_DISAPPROVED;
$data['student']['rejectUrl'] = $plugin->getQueueUrl($data);
$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('data', $data);
$tplParams = [
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
    'rejectUrl',
];
foreach ($tplParams as $tplParam) {
    $tpl->assign($tplParam, $data[$tplParam]);
}

$dir = __DIR__.'/../views/';
$files = scandir($dir);

echo '<br>', '<pre>', print_r($files, 1), '</pre>';

foreach ($files as $k => &$file) {
    if (
        is_file($dir.$file) &&
        strpos($file, '.tpl') &&
        $file != 'admin_view.tpl'
    ) {
        echo '<pre>', $file, '</pre>';
        echo $tpl->fetch('/advanced_subscription/views/'.$file);
    } else {
        unset($files[$k]);
    }
}
echo '<br>', '<pre>', print_r($files, 1), '</pre>';
