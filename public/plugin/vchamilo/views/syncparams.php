<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script();

$action = isset($_GET['what']) ? $_GET['what'] : '';
define('CHAMILO_INTERNAL', true);

$plugin = VChamiloPlugin::create();
$thisUrl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

if ($action) {
    require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/syncparams.controller.php';
}

$settings = api_get_settings();

$table = new HTML_Table(['class' => 'table']);
$column = 0;
$row = 0;
$headers = [
    '',
    $plugin->get_lang('variable').' ['.$plugin->get_lang('subkey').']',
    $plugin->get_lang('category'),
    $plugin->get_lang('accessurl'),
    $plugin->get_lang('value'),
    '',
];
$attrs = ['center' => 'left'];
$table->addRow($headers, $attrs, 'th');

foreach ($settings as $param) {
    if ($param['subkey'] == 'vchamilo') {
        continue;
    }
    $check = '';
    $attrs = ['center' => 'left'];
    $syncButton = '
        <input class="btn btn-default" type="button" name="syncthis" 
            value="'.$plugin->get_lang('syncthis').'" onclick="ajax_sync_setting(\''.$param['id'].'\')" />
        <span id="res_'.$param['id'].'"></span>';
    $data = [
        $check,
        isset($param['subkey']) && !empty($param['subkey']) ? $param['variable'].' ['.$param['subkey'].']' : $param['variable'],
        $param['category'],
        $param['access_url'],
        '<input type="text" disabled name="value_'.$param['id'].'" 
        value="'.htmlspecialchars($param['selected_value'], ENT_COMPAT, 'UTF-8').'" />'.
        '<br />Master value: '.$param['selected_value'],
        $syncButton,
    ];
    $row = $table->addRow($data, $attrs, 'td');
    $table->setRowAttributes($row, ['id' => 'row_'.$param['id']], true);
}

$content = '<form name="settingsform" action="'.$thisUrl.'">';
$content .= '<input type="hidden" name="what" value="" />';
$content .= $table->toHtml();
$content .= '</form>';

Display::addFlash(Display::return_message($plugin->get_lang('Sync your master settings to all instances.')));

$interbreadcrumb[] = ['url' => 'manage.php', 'name' => get_lang('VChamilo')];
$htmlHeadXtra[] = "<script>
function ajax_sync_setting(settingid) {
    var webUrl = '".api_get_path(WEB_PATH)."';
    var spare = $('#row_'+settingid).html();
    var formobj = document.forms['settingsform'];
    var url = webUrl + 'plugin/vchamilo/ajax/service.php?what=syncthis&settingid='+settingid+'&value='+encodeURIComponent(formobj.elements['value_'+settingid].value);    
    $('#row_'+settingid).html('<td colspan=\"7\"><img src=\"'+webUrl+'plugin/vchamilo/pix/ajax_waiter.gif\" /></td>');
    $.get(url, function (data) {
        $('#row_'+settingid).html(spare);
        $('#res_'+settingid).html(data);
    });
}
</script>";

$tpl = new Template($plugin->get_lang('SyncSettings'), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
