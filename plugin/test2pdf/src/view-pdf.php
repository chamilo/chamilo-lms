<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Test to Pdf plugin.
 *
 * @package chamilo.plugin.test2pdf
 */
require_once '../config.php';
api_protect_course_script(true);

$plugin = Test2pdfPlugin::create();
$enable = $plugin->get('enable_plugin') == 'true';

if ($enable) {
    $templateName = $plugin->get_lang('ViewExercises');
    $tpl = new Template($templateName);
    $courseId = api_get_course_int_id();
    $sessionId = api_get_session_id();

    $infoExercise = showExerciseCourse($courseId, $sessionId);
    $tpl->assign('infoExercise', $infoExercise);
    $tpl->assign('course_id', $courseId);

    $listing_tpl = 'test2pdf/view/view-pdf.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    Display::addFlash(Display::return_message($plugin->get_lang('PluginDisabledFromAdminPanel')));
    header(
        'Location:'.api_get_path(WEB_PATH).'courses/'.
        api_get_course_id().'/index.php?id_session='.api_get_session_id()
    );
    exit;
}
