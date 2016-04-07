<?php

require_once '../../../main/inc/global.inc.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php';

$action = $_GET['what'];
define('CHAMILO_INTERNAL', true);

$plugininstance = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

api_protect_admin_script();

//require_js('host_list.js', 'vchamilo');

if ($action) {
    require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/manage.controller.php');
}

$content = Display::page_header('VChamilo Instances');

$table = 'vchamilo';
$query = "SELECT * FROM $table";
$result = Database::query($query);
$instances = array();
while ($instance = Database::fetch_object($result)) {
    $instances[$instance->id] = $instance;
}

$table = new HTML_Table(array('class' => 'data_table'));
$column = 0;
$row = 0;

// $table->set_additional_parameters($parameters);
$headers = array('', $plugininstance->get_lang('sitename'), $plugininstance->get_lang('institution'), $plugininstance->get_lang('rootweb'), $plugininstance->get_lang('dbhost'), $plugininstance->get_lang('coursefolder'), $plugininstance->get_lang('enabled'), $plugininstance->get_lang('lastcron'), '');
$attrs = array('center' => 'left');
$table->addRow($headers, $attrs, 'th');

$i = 0;
foreach ($instances as $instance) {
    $checkbox = '<input type="checkbox" class="vnodessel" name="vids[]" value="'.$instance->id.'" />';

    $sitelink = '<a href="'.$instance->root_web.'" target="_blank">'.$instance->sitename.'</a>';

    if ($instance->visible) {
        $status = '<a href="'.$thisurl.'?what=disableinstances&vids[]='.$instance->id.'" ><img src="'.$plugininstance->pix_url('enabled').'" /></a>';
    } else {
        $status = '<a href="'.$thisurl.'?what=enableinstances&vids[]='.$instance->id.'" ><img src="'.$plugininstance->pix_url('disabled').'" /></a>';
    }

    if (!$instance->visible){
        $cmd = '<a href="'.$thisurl.'?what=fulldeleteinstances&vids[]='.$instance->id.'" title="'.$plugininstance->get_lang('destroyinstances').'"><img src="'.$plugininstance->pix_url('delete').'" /></a>';
    } else {
        $cmd = '<a href="'.$thisurl.'?what=deleteinstances&vids[]='.$instance->id.'" title="'.$plugininstance->get_lang('deleteinstances').'"><img src="'.$plugininstance->pix_url('delete').'" /></a>';
    }
    $cmd .= '&nbsp;<a href="'.$thisurl.'?what=editinstance&vid='.$instance->id.'" title="'.$plugininstance->get_lang('edit').'"><img src="'.$plugininstance->pix_url('edit').'" /></a>';
    $cmd .= '&nbsp;<a href="'.$thisurl.'?what=snapshotinstance&vid='.$instance->id.'" title="'.$plugininstance->get_lang('snapshotinstance').'"><img src="'.$plugininstance->pix_url('snapshot').'" /></a>';

    $crondate = ($instance->lastcron) ? date('r', $instance->lastcron) : '';
    $data = array($checkbox, $sitelink, $instance->institution, $instance->root_web, $instance->db_host, $instance->course_folder, $status, $crondate, $cmd);
    $attrs = array('center' => 'left');
    $table->addRow($data, $attrs, 'td');
    $i++;
}

$content .=  '<form action="'.$thisurl.'">';

$content .=  $table->toHtml();

$selectionoptions = array('<option value="0" selected="selected">'.$plugininstance->get_lang('choose').'</option>');
$selectionoptions[] = '<option value="deleteinstances">'.$plugininstance->get_lang('deleteinstances').'</option>';
$selectionoptions[] = '<option value="enableinstances">'.$plugininstance->get_lang('enableinstances').'</option>';
$selectionoptions[] = '<option value="fulldeleteinstances">'.$plugininstance->get_lang('destroyinstances').'</option>';
$selectionoptions[] = '<option value="clearcache">'.$plugininstance->get_lang('clearcache').'</option>';
$selectionoptions[] = '<option value="setconfigvalue">'.$plugininstance->get_lang('setconfigvalue').'</option>';
$selectionaction = '<select name="what" onchange="this.form.submit()">'.implode('', $selectionoptions).'</select>';

$content .=  '<div class"vchamilo-right"><div></div><div><a href="javascript:selectallhosts()">'.$plugininstance->get_lang('selectall').'</a> - <a href="javascript:deselectallhosts()">'.$plugininstance->get_lang('selectnone').'</a> - <a href="'.$thisurl.'?what=newinstance">'.$plugininstance->get_lang('newinstance').'</a> - <a href="'.$thisurl.'?what=instance&registeronly=1">'.$plugininstance->get_lang('registerinstance').'</a>&nbsp; - '.$plugininstance->get_lang('withselection').' '.$selectionaction.'</div></div>';
$content .=  '<div class"vchamilo-right"><div> <a href="'.$thisurl.'?what=snapshotinstance&vid=0">'.$plugininstance->get_lang('snapshotmaster').' <img src="'.$plugininstance->pix_url('snapshot').'" /></a></div>';
$content .=  '<div class"vchamilo-right"><div> <a href="'.$thisurl.'?what=clearcache&vid=0">'.$plugininstance->get_lang('clearmastercache').'</a></div></div>';

$content .=  '</form>';

$actions = '';
$message = '';

$tpl = new Template($tool_name, true, true, false, true, false);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
