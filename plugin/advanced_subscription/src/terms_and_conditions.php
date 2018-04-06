<?php
/* For license terms, see /license.txt */
/**
 * Script to show sessions terms and conditions.
 *
 * @package chamilo.plugin.advanced_subscription
 */
/**
 * Init.
 */
require_once __DIR__.'/../config.php';
// start plugin
$plugin = AdvancedSubscriptionPlugin::create();
// Session ID
$data['action'] = Security::remove_XSS($_REQUEST['a']);
$data['sessionId'] = isset($_REQUEST['s']) ? intval($_REQUEST['s']) : 0;
$data['currentUserId'] = isset($_REQUEST['current_user_id']) ? intval($_REQUEST['current_user_id']) : 0;
$data['studentUserId'] = isset($_REQUEST['u']) ? intval($_REQUEST['u']) : 0;
$data['queueId'] = isset($_REQUEST['q']) ? intval($_REQUEST['q']) : 0;
$data['newStatus'] = isset($_REQUEST['e']) ? intval($_REQUEST['e']) : 0;
$data['is_connected'] = true;
$data['profile_completed'] = isset($_REQUEST['profile_completed']) ? floatval($_REQUEST['profile_completed']) : 0;
$data['termsRejected'] = isset($_REQUEST['r']) ? intval($_REQUEST['r']) : 0;

// Init template
$tpl = new Template($plugin->get_lang('plugin_title'));

$isAllowToDoRequest = $plugin->isAllowedToDoRequest($data['studentUserId'], $data, true);

if (!$isAllowToDoRequest) {
    $tpl->assign('errorMessages', $plugin->getErrorMessages());
}

if (
    !empty($data['sessionId']) &&
    !empty($data['studentUserId']) &&
    api_get_plugin_setting('courselegal', 'tool_enable')
) {
    $lastMessageId = $plugin->getLastMessageId($data['studentUserId'], $data['sessionId']);
    if ($lastMessageId !== false) {
        // Render mail
        $url = $plugin->getRenderMailUrl(['queueId' => $lastMessageId]);
        header('Location: '.$url);
        exit;
    }
    $courses = SessionManager::get_course_list_by_session_id($data['sessionId']);
    $course = current($courses);
    $data['courseId'] = $course['id'];
    $legalEnabled = api_get_plugin_setting('courselegal', 'tool_enable');
    if ($legalEnabled) {
        $courseLegal = CourseLegalPlugin::create();
        $termsAndConditions = $courseLegal->getData($data['courseId'], $data['sessionId']);
        $termsAndConditions = $termsAndConditions['content'];
        $termFiles = $courseLegal->getCurrentFile($data['courseId'], $data['sessionId']);
    } else {
        $termsAndConditions = $plugin->get('terms_and_conditions');
        $termFiles = '';
    }

    $data['session'] = api_get_session_info($data['sessionId']);
    $data['student'] = api_get_user_info($data['studentUserId']);
    $data['course'] = api_get_course_info_by_id($data['courseId']);
    $data['acceptTermsUrl'] = $plugin->getQueueUrl($data);
    $data['rejectTermsUrl'] = $plugin->getTermsUrl($data, ADVANCED_SUBSCRIPTION_TERMS_MODE_REJECT);
    // Use Twig with String loader
    $termsContent = $plugin->renderTemplateString($termsAndConditions, $data);
} else {
    $termsContent = '';
    $termFiles = '';
    $data['acceptTermsUrl'] = '#';
    $data['rejectTermsUrl'] = '#';
}

// Assign into content
$tpl->assign('termsRejected', $data['termsRejected']);
$tpl->assign('acceptTermsUrl', $data['acceptTermsUrl']);
$tpl->assign('rejectTermsUrl', $data['rejectTermsUrl']);
$tpl->assign('session', $data['session']);
$tpl->assign('student', $data['student']);
$tpl->assign('sessionId', $data['sessionId']);
$tpl->assign('termsContent', $termsContent);
$tpl->assign('termsFiles', $termFiles);

$content = $tpl->fetch('/advanced_subscription/views/terms_and_conditions.tpl');
echo $content;
