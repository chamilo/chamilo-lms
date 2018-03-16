<?php
/* For licensing terms, see /license.txt */

/**
 * This script displays setting api key user.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (api_is_platform_admin()) {
    $tUser = Database::get_main_table(TABLE_MAIN_USER);
    $tApi = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
    $login = 'SEPE';
    $sql = "SELECT a.api_key AS api 
            FROM $tUser u, $tApi a 
            WHERE u.username='".$login."' and u.user_id = a.user_id AND a.api_service = 'dokeos';";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $tmp = Database::fetch_assoc($result);
        $info = $tmp['api'];
    } else {
        $info = '';
    }
    $templateName = $plugin->get_lang('Setting');
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $tpl = new Template($templateName);

    $tpl->assign('info', $info);

    $listing_tpl = 'sepe/view/configuration.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
