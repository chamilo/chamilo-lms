<?php

define('CHAMILO_INTERNAL', true);

global $plugininstance;

require_once('../../../main/inc/global.inc.php');
require_once($_configuration['root_sys'].'/local/classes/mootochamlib.php');
require_once($_configuration['root_sys'].'/local/classes/database.class.php');

global $DB;
$DB = new DatabaseManager();

require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php');
require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/editinstance_form.php');
HTML_QuickForm::registerElementType('cancel', api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/QuickForm/cancel.php', 'HTML_QuickForm_cancel');

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

// call controller
// $data = $form->get_data();
if ($data = $form->get_data()){
    include(api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/editinstance.controller.php');
}

if ($id){
    $vhost = $DB->get_record('vchamilo', array('id' => $id));
    $vhost->vid = $vhost->id;
    unset($vhost->id);
    $form->set_data((array)$vhost);
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
