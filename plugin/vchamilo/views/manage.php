<?php

$cidReset = true;
require_once '../../../main/inc/global.inc.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib.php';
require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php';

// security
api_protect_admin_script();

vchamilo_check_settings();

$action = isset($_GET['what']) ? $_GET['what'] : '';
define('CHAMILO_INTERNAL', true);

$plugininstance = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

require_js('host_list.js', 'vchamilo');

if ($action) {
    require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/manage.controller.php';
}

$query = "SELECT * FROM vchamilo";
$result = Database::query($query);
$instances = array();
while ($instance = Database::fetch_object($result)) {
    $instances[$instance->id] = $instance;
}

$templates = vchamilo_get_available_templates(false);

if (empty($templates)) {
    $url = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php?what=snapshotinstance';
    $url = Display::url($url, $url);
    Display::addFlash(
        Display::return_message('You need to create a snapshot of master first here:'.$url, 'info', false)
    );
}

$table = new HTML_Table(array('class' => 'data_table'));
$column = 0;
$row = 0;

// $table->set_additional_parameters($parameters);
$headers = array(
    '',
    $plugininstance->get_lang('sitename'),
    $plugininstance->get_lang('institution'),
    $plugininstance->get_lang('dbhost').' - '.get_lang('Database'),
    $plugininstance->get_lang('coursefolder'),
    $plugininstance->get_lang('enabled'),
    $plugininstance->get_lang('lastcron'),
    '',
);
$attrs = array('center' => 'left');
$table->addRow($headers, $attrs, 'th');

$i = 0;
foreach ($instances as $instance) {
    $checkbox = '<input type="checkbox" class="vnodessel" name="vids[]" value="'.$instance->id.'" />';

    //$sitelink = '<a href="'.$instance->root_web.'" target="_blank">'.$instance->sitename.'</a>';
    $sitelink = $instance->sitename;

    if ($instance->visible) {
        $status = '<a href="'.$thisurl.'?what=disableinstances&vids[]='.$instance->id.'" >
                  <img src="'.$plugininstance->pix_url('enabled').'" /></a>';
    } else {
        $status = '<a href="'.$thisurl.'?what=enableinstances&vids[]='.$instance->id.'" >
                   <img src="'.$plugininstance->pix_url('disabled').'" /></a>';
    }

    $cmd = '&nbsp;<a href="'.$thisurl.'?what=editinstance&vid='.$instance->id.'" title="'.$plugininstance->get_lang('edit').'">
            <img src="'.$plugininstance->pix_url('edit').'" /></a>';
    $cmd .= '&nbsp;<a href="'.$thisurl.'?what=snapshotinstance&vid='.$instance->id.'" title="'.$plugininstance->get_lang('snapshotinstance').'">
        <img src="'.$plugininstance->pix_url('snapshot').'" /></a>';

    if (!$instance->visible) {
        $cmd .= '<a href="'.$thisurl.'?what=fulldeleteinstances&vids[]='.$instance->id.'" title="'.$plugininstance->get_lang('destroyinstances').'">
        <img src="'.$plugininstance->pix_url('delete').'" /></a>';
    } else {
        $cmd .= '<a href="'.$thisurl.'?what=deleteinstances&vids[]='.$instance->id.'" title="'.$plugininstance->get_lang('deleteinstances').'">
        <img src="'.$plugininstance->pix_url('delete').'" /></a>';
    }

    $crondate = ($instance->lastcron) ? date('r', $instance->lastcron) : '';
    $data = array(
        $checkbox,
        $sitelink.' ('.Display::url($instance->root_web, $instance->root_web).')',
        $instance->institution,
        $instance->db_host.' - '.$instance->main_database,
        $instance->slug,
        $status,
        $crondate,
        $cmd,
    );
    $attrs = array('center' => 'left');
    $table->addRow($data, $attrs, 'td');
    $i++;
}

$items = [
    [
        'url' => $thisurl.'?what=newinstance',
        'content' => $plugininstance->get_lang('newinstance')
    ],
    [
        'url' => $thisurl.'?what=instance&registeronly=1',
        'content' => $plugininstance->get_lang('registerinstance')
    ],
    [
        'url' => $thisurl.'?what=snapshotinstance&vid=0',
        'content' => $plugininstance->get_lang('snapshotmaster')
    ],
    [
        'url' => $thisurl.'?what=clearcache&vid=0',
        'content' => $plugininstance->get_lang('clearmastercache')
    ],
    [
        'url' => api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/syncparams.php',
        'content' => $plugininstance->get_lang('sync_settings')
    ],
    [
        'url' => api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?name=vchamilo',
        'content' => get_lang('Settings')
    ]
];

$content = Display::page_header('VChamilo Instances');

$content .= Display::actions($items);
$content .= '<form action="'.$thisurl.'">';
$content .= $table->toHtml();

$selectionoptions = array('<option value="0" selected="selected">'.$plugininstance->get_lang('choose').'</option>');
$selectionoptions[] = '<option value="deleteinstances">'.$plugininstance->get_lang('deleteinstances').'</option>';
$selectionoptions[] = '<option value="enableinstances">'.$plugininstance->get_lang('enableinstances').'</option>';
$selectionoptions[] = '<option value="fulldeleteinstances">'.$plugininstance->get_lang(
        'destroyinstances'
    ).'</option>';
$selectionoptions[] = '<option value="clearcache">'.$plugininstance->get_lang('clearcache').'</option>';
$selectionoptions[] = '<option value="setconfigvalue">'.$plugininstance->get_lang('setconfigvalue').'</option>';
$selectionaction = '<select name="what" onchange="this.form.submit()">'.implode('', $selectionoptions).'</select>';

$content .= '<div class"vchamilo-right"><div></div><div>
<a href="javascript:selectallhosts()">'.$plugininstance->get_lang('selectall').'</a> - 
<a href="javascript:deselectallhosts()">'.$plugininstance->get_lang('selectnone').'</a> - 
&nbsp; - '.$plugininstance->get_lang('withselection').' '.$selectionaction.'</div></div>';

$content .= '</form>';

if (empty($templates)) {
    $content = '';
}

$tpl = new Template(get_lang('VChamilo'), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
