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

$data['action'] = 'confirm';
$data['currentUserId'] = 1;
$data['queueId'] = 0;
$data['is_connected'] = true;
$data['profile_completed'] = 90.0;
$data['sessionId'] = intval($_REQUEST['s']);
$data['studentUserId'] = intval($_REQUEST['u']);
$data['student'] = api_get_user_info($data['studentUserId']);
$data['session'] = api_get_session_info($data['sessionId']);

if (!empty($data['sessionId']) && !empty($data['studentUserId'])) {
    $plugin = AdvancedSubscriptionPlugin::create();

    if (api_get_plugin_setting('courselegal', 'tool_enable')) {
        $courseLegal = CourseLegalPlugin::create();
        $courses = SessionManager::get_course_list_by_session_id($data['sessionId']);
        $course = current($courses);
        $data['courseId'] = $course['id'];
        $data['course'] = api_get_course_info_by_id($data['courseId']);
        $termsAndConditions = $courseLegal->getData($data['courseId'], $data['sessionId']);
        $termsAndConditions = $termsAndConditions['content'];
        $termsAndConditions = $plugin->renderTemplateString($termsAndConditions, $data);
        $tpl = new Template($plugin->get_lang('Terms'));
        $tpl->assign('session', $data['session']);
        $tpl->assign('student', $data['student']);
        $tpl->assign('sessionId', $data['sessionId']);
        $tpl->assign('termsContent', $termsAndConditions);
        $termsAndConditions = $tpl->fetch('/advanced_subscription/views/terms_and_conditions_to_pdf.tpl');
        $pdf = new PDF();
        $filename = 'terms'.sha1(rand(0, 99999));
        $pdf->content_to_pdf($termsAndConditions, null, $filename, null, 'F');
        $fileDir = api_get_path(WEB_ARCHIVE_PATH).$filename.'.pdf';
        echo '<pre>', print_r($fileDir, 1), '</pre>';
    }
}
