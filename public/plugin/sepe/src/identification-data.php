<?php
/* For licensing terms, see /license.txt */

/**
 * This script displays a basic info about data center.
 */
require_once '../config.php';

$plugin = SepePlugin::create();
$_cid = 0;

if (api_is_platform_admin()) {
    $info = getInfoIdentificationData();
    $templateName = $plugin->get_lang('DataCenter');
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
    $tpl->assign('info', $info);
    $listing_tpl = 'sepe/view/identification-data.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
}
