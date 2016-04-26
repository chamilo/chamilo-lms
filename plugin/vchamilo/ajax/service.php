<?php

require_once '../../../main/inc/global.inc.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php';

$action = isset($_GET['what']) ? $_GET['what'] : '';
define('CHAMILO_INTERNAL', true);

$plugininstance = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

api_protect_admin_script();

if ($action == 'syncthis') {
    $res = include_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/syncparams.controller.php';
    if (!$res) {
        echo '<span class="ok">Success</span>';
    } else {
        echo '<span class="failed">Failure<br/>'.$errors.'</span>';
    }
}