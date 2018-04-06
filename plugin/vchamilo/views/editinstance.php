<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
define('CHAMILO_INTERNAL', true);

global $plugin;

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/editinstance_form.php';

api_protect_admin_script();

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/js/host_form.js" type="text/javascript" language="javascript"></script>';

// get parameters
$id = isset($_REQUEST['vid']) ? $_REQUEST['vid'] : '';
$action = isset($_REQUEST['what']) ? $_REQUEST['what'] : '';
$registeronly = isset($_REQUEST['registeronly']) ? $_REQUEST['registeronly'] : 0;
$plugin = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

if ($id) {
    $mode = 'update';
} else {
    $mode = $registeronly ? 'register' : 'add';
}

$vhost = (array) Virtual::getInstance($id);

$form = new InstanceForm($plugin, $mode, $vhost);

if ($data = $form->get_data()) {
    switch ($data->what) {
        case 'addinstance':
        case 'registerinstance':
            Virtual::addInstance($data);
            echo '<a class="btn btn-primary" href="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php'.'">Continue</a>';
            exit;
            break;
        case 'updateinstance':
            unset($data->what);
            unset($data->submitbutton);
            unset($data->registeronly);
            unset($data->template);
            $data->lastcron = 0;
            $data->lastcrongap = 0;
            $data->croncount = 0;
            $id = $data->vid;
            unset($data->vid);
            unset($data->testconnection);
            unset($data->testdatapath);
            unset($data->vid);

            Database::update('vchamilo', (array) $data, ['id = ?' => $id], false);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            Virtual::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
            break;
    }
}

if ($id) {
    $vhost['vid'] = $vhost['id'];
    unset($vhost['id']);
    $form->set_data($vhost);
} else {
    $vhost['db_host'] = 'localhost';
    $vhost['registeronly'] = $registeronly;
    $form->set_data($vhost);
}

$content = $form->return_form();

$interbreadcrumb[] = ['url' => 'manage.php', 'name' => get_lang('VChamilo')];

$tpl = new Template(get_lang('Instance'), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
