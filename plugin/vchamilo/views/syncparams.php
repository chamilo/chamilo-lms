<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once '../../../main/inc/global.inc.php';

api_protect_admin_script();

$action = isset($_GET['what']) ? $_GET['what'] : '';
define('CHAMILO_INTERNAL', true);

$plugin = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

if ($action) {
    require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/syncparams.controller.php');
}

$settings = api_get_settings();

$table = new HTML_Table(array('class' => 'data_table', 'width' => '100%'));
$column = 0;
$row = 0;

// $table->set_additional_parameters($parameters);
$headers = array(
    '',
    $plugin->get_lang('variable').' ['.$plugin->get_lang('subkey').']',
    $plugin->get_lang('category'),
    $plugin->get_lang('accessurl'),
    $plugin->get_lang('value'),
    '',
);
$attrs = array('center' => 'left');
$table->addRow($headers, $attrs, 'th');

foreach ($settings as $param) {

    if ($param['subkey'] == 'vchamilo') {
        continue;
    }
    // $check = '<input type="checkbox" name="sync_'.$param->id.'" value="'.$param->selected_value.'" />';
    //<input type="checkbox" name="del_'.$param['id'].'" value="1" title="'.$plugin->get_lang('deleteifempty').'" />
    $check = '';
    $attrs = array('center' => 'left');
    $syncthisbutton = '<input type="button" name="syncthis" value="'.$plugin->get_lang('syncthis').'" onclick="ajax_sync_setting(\''.$_configuration['root_web'].'\', \''.$param['id'].'\')" /> 
         <span id="res_'.$param['id'].'"></span>';
    $data = array(
        $check,
        isset($param['subkey']) && !empty($param['subkey']) ? $param['variable'].' ['.$param['subkey'].']' : $param['variable'],
        $param['category'],
        $param['access_url'],
        '<input type="text" name="value_'.$param['id'].'" value="'.htmlspecialchars($param['selected_value'], ENT_COMPAT, 'UTF-8' ).'" />'.
        '<br />Master value: '.$param['selected_value'],
        $syncthisbutton,
    );
    $row = $table->addRow($data, $attrs, 'td');
    $table->setRowAttributes($row, array('id' => 'row_'.$param['id']), true);
}

$content  = '<form name="settingsform" action="'.$thisurl.'">';
$content .= '<input type="hidden" name="what" value="" />';
$content .=  $table->toHtml();
// $content .=  '<div class"vchamilo-right"><div></div><div><input type="button" name="syncall" value="'.$plugin->get_lang('syncall').'" onclick="this.form.what.value=\'syncall\';this.form.submit();">';
$content .=  '</form>';
$actions = '';

Display::addFlash(Display::return_message($plugin->get_lang('Sync your master settings to all instances.')));

$message = require_js('ajax.js', 'vchamilo', true);

$interbreadcrumb[] = array('url' => 'manage.php', 'name' => get_lang('VChamilo'));

$tpl = new Template($plugin->get_lang('sync_settings'), true, true, false, true, false);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
