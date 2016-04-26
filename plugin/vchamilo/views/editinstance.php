<?php

$cidReset = true;
define('CHAMILO_INTERNAL', true);

global $plugininstance;

require_once '../../../main/inc/global.inc.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/editinstance_form.php';

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/js/host_form.js" type="text/javascript" language="javascript"></script>';

// get parameters
$id = isset($_REQUEST['vid']) ? $_REQUEST['vid'] : '';
$action = isset($_REQUEST['what']) ? $_REQUEST['what'] : '';
$registeronly = isset($_REQUEST['registeronly']) ? $_REQUEST['registeronly'] : 0;
$plugininstance = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

$course_real_root = vchamilo_get_config('vchamilo', 'course_real_root');
$homePath = vchamilo_get_config('vchamilo', 'home_real_root');

if (empty($course_real_root) || empty($homePath)) {
    api_not_allowed('You have to setup the course real root');
}

// security
api_protect_admin_script();

if ($id) {
    $mode = 'update';
} else {
    $mode = $registeronly ? 'register' : 'add' ;
}

$form = new InstanceForm($plugininstance, $mode);
$form->definition();

$actions = '';
$message = '';
if ($data = $form->get_data()) {
    include api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/editinstance.controller.php';
}

if ($id) {
    $sql = "SELECT * FROM vchamilo WHERE id = $id";
    $result = Database::query($sql);
    $vhost = Database::fetch_array($result);
    $vhost['vid'] = $vhost['id'];
    unset($vhost['id']);
    $form->set_data($vhost);
} else {
    $data = array();
    $data['db_host'] = 'localhost';
    $data['single_database'] = 1;
    $data['registeronly'] = $registeronly;
    $form->set_data($data);
}

$content = $form->return_form();

$interbreadcrumb[] = array('url' => 'manage.php', 'name' => get_lang('VChamilo'));

$tpl = new Template(get_lang('Instances'), true, true, false, true, false);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
