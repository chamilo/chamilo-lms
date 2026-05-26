<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = Justification::create();
$allowSessionAdmins = $plugin->canSessionAdminsManageUsers();

api_protect_admin_script($allowSessionAdmins);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'Justification/list.php');
exit;
