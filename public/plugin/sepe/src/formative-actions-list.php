<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a formatives actions list.
 */
require_once '../config.php';

$plugin = SepePlugin::create();

if (api_is_platform_admin()) {
    $templateName = $plugin->get_lang('FormativesActionsList');
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $tpl = new Template($templateName);

    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $courseActionList = listCourseAction();
    $courseFreeList = listCourseFree();
    $actionFreeList = listActionFree();

    $tpl->assign('course_action_list', $courseActionList);
    $tpl->assign('course_free_list', $courseFreeList);
    $tpl->assign('action_free_list', $actionFreeList);

    $listing_tpl = 'sepe/view/formative-actions-list.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
