<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Test to Pdf plugin
 * @package chamilo.plugin.test2pdf
 */
/**
 * Initialization
 */
require_once dirname(__FILE__) . '/test2pdf.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once 'test2pdf_plugin.class.php';

api_protect_course_script(true);
$plugin = Test2pdfPlugin::create();
$t2p_plugin = $plugin->get('enable_plugin');

if ($t2p_plugin === 'true') {
    $templateName = $plugin->get_lang('ViewExercises');
    $tpl = new Template($templateName);
    $course_id = api_get_course_int_id();
    
    // Leer Datos y Mostrar tabla
    $iconInfo = api_get_path(WEB_PLUGIN_PATH) . 'test2pdf/resources/img/24/info.png';
    $iconDownload = api_get_path(WEB_PLUGIN_PATH) . 'test2pdf/resources/img/32/download.png';
    $info_exercise = showExerciseCourse($course_id);
    $tpl->assign('infoExercise', $info_exercise);
    $tpl->assign('course_id', $course_id);
    $tpl->assign('iconInfo', $iconInfo);
    $tpl->assign('iconDownload', $iconDownload);
    
    $listing_tpl = 'test2pdf/view/view-pdf.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location: ../../../index.php');
}
