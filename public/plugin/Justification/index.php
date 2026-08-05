<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = Justification::create();
$allowSessionAdmins = $plugin->canSessionAdminsManageUsers();

if (api_is_platform_admin() || ($allowSessionAdmins && api_is_session_admin())) {
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'Justification/list.php');
    exit;
}

api_block_anonymous_users();

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'Justification/upload.php');
exit;
