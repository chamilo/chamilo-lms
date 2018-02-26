<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script();

$action = isset($_GET['what']) ? $_GET['what'] : '';
define('CHAMILO_INTERNAL', true);

$plugin = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

switch ($action) {
    case 'syncthis':
        $res = include_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/syncparams.controller.php';
        if (!$res) {
            echo '<span class="label label-success">Success</span>';
        } else {
            echo '<span class="label label-danger">Failure<br/>'.$errors.'</span>';
        }
        break;
}

exit;
