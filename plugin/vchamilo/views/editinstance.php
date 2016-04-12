<?php

define('CHAMILO_INTERNAL', true);

global $plugininstance;

require_once '../../../main/inc/global.inc.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/editinstance_form.php';

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_PLUGIN_PATH).'vchamilo/js/host_form.js" type="text/javascript" language="javascript"></script>';

// get parameters
$id = (int)($_REQUEST['vid']);
$action = $_REQUEST['what'];
$registeronly = @$_REQUEST['registeronly'];
$plugininstance = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

// security
api_protect_admin_script();

if ($id) {
    $mode = 'update';
} else {
    $mode = ($registeronly) ? 'register' : 'add' ;
}

$form = new InstanceForm($plugininstance, $mode);
$form->definition();

$actions = '';
$message = '';
if ($data = $form->get_data()) {
    include(api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/editinstance.controller.php');
}

if ($id){
//    $vhost = $DB->get_record('vchamilo', array('id' => $id));
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

$tpl = new Template($tool_name, true, true, false, true, false);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
