<?php
/* For licensing terms, see /license.txt */

/**
 * This script displays a basic info of formative action.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (api_is_platform_admin()) {
    $actionId = getActionId($_GET['cid']);
    $info = getActionInfo($actionId);
    if ($info === false) {
        header("Location: formative-actions-list.php");
        exit;
    }
    $templateName = $plugin->get_lang('FormativeActionData');
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $interbreadcrumb[] = [
        "url" => "formative-actions-list.php",
        "name" => $plugin->get_lang('FormativesActionsList'),
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

    $tpl->assign('info', $info);
    $tpl->assign('start_date', date("d/m/Y", strtotime($info['start_date'])));
    $tpl->assign('end_date', date("d/m/Y", strtotime($info['end_date'])));
    $tpl->assign('action_id', $actionId);
    $listSpecialty = specialtyList($actionId);
    $tpl->assign('listSpecialty', $listSpecialty);
    $listParticipant = participantList($actionId);
    $tpl->assign('listParticipant', $listParticipant);
    $listing_tpl = 'sepe/view/formative-action.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
}
