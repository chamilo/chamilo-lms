<?php

require_once('../../../main/inc/global.inc.php');
require_once($_configuration['root_sys'].'/local/classes/mootochamlib.php');
require_once($_configuration['root_sys'].'/local/classes/database.class.php');
require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php');

global $DB;
$DB = new DatabaseManager();

$action = $_GET['what'];
define('CHAMILO_INTERNAL', true);

$plugininstance = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

api_protect_admin_script();

if ($action){
    require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/syncparams.controller.php');
}

$allparams = $DB->get_records('settings_current', array(), 'variable,subkey');

$table = new HTML_Table(array('class' => 'data_table', 'width' => '100%'));
$column = 0;
$row = 0;

// $table->set_additional_parameters($parameters);
$headers = array('', $plugininstance->get_lang('variable'), $plugininstance->get_lang('subkey'), $plugininstance->get_lang('category'), $plugininstance->get_lang('accessurl'), $plugininstance->get_lang('value'), '');
$attrs = array('center' => 'left');
$table->addRow($headers, $attrs, 'th');

foreach ($allparams as $param) {
    // $check = '<input type="checkbox" name="sync_'.$param->id.'" value="'.$param->selected_value.'" />';
    $check = '';
    $attrs = array('center' => 'left');
    $syncthisbutton = '<input type="button" name="syncthis" value="'.$plugininstance->get_lang('syncthis').'" onclick="ajax_sync_setting(\''.$_configuration['root_web'].'\', \''.$param->id.'\')" /> <input type="checkbox" name="del_'.$param->id.'" value="1" title="'.$plugininstance->get_lang('deleteifempty').'" /> <span id="res_'.$param->id.'"></span>';
    $data = array($check, $param->variable, $param->subkey, $param->category, $param->access_url, '<input type="text" name="value_'.$param->id.'" value="'.htmlspecialchars($param->selected_value, ENT_COMPAT, 'UTF-8').'" size="40" />', $syncthisbutton);
    $row = $table->addRow($data, $attrs, 'td');
    $table->setRowAttributes($row, array('id' => 'row_'.$param->id), true);
}

$content .=  '<form name="settingsform" action="'.$thisurl.'">';
$content .= '<input type="hidden" name="what" value="" />';
$content .=  $table->toHtml();
// $content .=  '<div class"vchamilo-right"><div></div><div><input type="button" name="syncall" value="'.$plugininstance->get_lang('syncall').'" onclick="this.form.what.value=\'syncall\';this.form.submit();">';
$content .=  '</form>';

$actions = '';
$message = '';

$message = require_js('ajax.js', 'vchamilo', true);

$tpl = new Template($tool_name, true, true, false, true, false);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
