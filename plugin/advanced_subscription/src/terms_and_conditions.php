<?php
/* For license terms, see /license.txt */
/**
 * Script to show sessions terms and conditions
 * @package chamilo.plugin.advanced_subscription
 */
/**
 * Init
 */
require_once __DIR__ . '/../config.php';
// start plugin
$plugin = AdvancedSubscriptionPlugin::create();
$courseLegal = CourseLegalPlugin::create();
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

if (
    !empty($data['sessionId']) &&
    !empty($data['studentUserId']) &&
    api_get_plugin_setting('courselegal', 'tool_enable')
) {
    $courses = SessionManager::get_course_list_by_session_id($data['sessionId']);
    $course = current($courses);
    $data['courseId'] = $course['id'];
    $termsAndConditions = $courseLegal->getData($data['courseId'], $data['sessionId']);
    $termsAndConditions = $termsAndConditions['content'];
    $termFiles = $courseLegal->getCurrentFile($data['courseId'], $data['sessionId']);
    $data['session'] = api_get_session_info($data['sessionId']);
    $data['student'] = Usermanager::get_user_info_by_id($data['studentUserId']);
    $data['acceptTermsUrl'] = $plugin->getQueueUrl($data);
    $data['rejectTermsUrl'] = $plugin->getTermsUrl($data, 1);
    // Use Twig with String loader
    $twigString = new \Twig_Environment(new \Twig_Loader_String());
    $termsContent = $twigString->render(
        $termsAndConditions,
        array(
            'session' => $data['session'],
            'student' => $data['student'],
        )
    );

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
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
