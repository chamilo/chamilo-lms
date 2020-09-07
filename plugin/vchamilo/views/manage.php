<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../../../main/inc/global.inc.php';

// Security
api_protect_admin_script();
Virtual::checkSettings();

$action = isset($_GET['what']) ? $_GET['what'] : '';
define('CHAMILO_INTERNAL', true);

$plugin = VChamiloPlugin::create();
$thisurl = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php';

Virtual::requireJs('host_list.js', 'vchamilo', 'head');

if ($action) {
    require_once api_get_path(SYS_PLUGIN_PATH).'vchamilo/views/manage.controller.php';
}

$query = "SELECT * FROM vchamilo";
$result = Database::query($query);
$instances = [];
while ($instance = Database::fetch_object($result)) {
    $instances[$instance->id] = $instance;
}

$templates = Virtual::getAvailableTemplates();

if (empty($templates)) {
    $url = api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php?what=snapshotinstance';
    $url = Display::url($url, $url);
    Display::addFlash(
        Display::return_message('You need to create a snapshot of master first here:'.$url, 'info', false)
    );
}

$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
$column = 0;
$row = 0;

// $table->set_additional_parameters($parameters);
$headers = [
    '',
    $plugin->get_lang('sitename'),
    $plugin->get_lang('dbhost').' - '.get_lang('Database'),
    $plugin->get_lang('coursefolder'),
    $plugin->get_lang('enabled'),
    $plugin->get_lang('lastcron'),
    '',
];
$attrs = ['center' => 'left'];
$table->addRow($headers, $attrs, 'th');

$i = 0;
foreach ($instances as $instance) {
    $checkbox = '<input type="checkbox" class="vnodessel" name="vids[]" value="'.$instance->id.'" />';
    $sitelink = $instance->sitename;

    if ($instance->visible) {
        $status = '<a href="'.$thisurl.'?what=disableinstances&vids[]='.$instance->id.'" >
                  '.Display::returnFontAwesomeIcon('toggle-on', 2).'</a>';
    } else {
        $status = '<a href="'.$thisurl.'?what=enableinstances&vids[]='.$instance->id.'" >
                  '.Display::returnFontAwesomeIcon('toggle-off', 2).'</a>';
    }

    $cmd = '&nbsp;<a href="'.$thisurl.'?what=editinstance&vid='.$instance->id.'" title="'.$plugin->get_lang('edit').'">
            '.Display::returnFontAwesomeIcon('pencil', 2).'</a>';
    $cmd .= '&nbsp;<a href="'.$thisurl.'?what=snapshotinstance&vid='.$instance->id.'" title="'.$plugin->get_lang('snapshotinstance').'">
        '.Display::returnFontAwesomeIcon('camera', 2).'</a>';

    $cmd .= '<a href="'.$thisurl.'?what=upgrade&vids[]='.$instance->id.'" title="'.$plugin->get_lang('Upgrade').'">
         &nbsp;'.Display::returnFontAwesomeIcon('wrench', 2).' </a>';

    if (!$instance->visible) {
        $cmd .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="'.$thisurl.'?what=fulldeleteinstances&vids[]='.$instance->id.'" title="'.$plugin->get_lang('destroyinstances').'">
        &nbsp;'.Display::returnFontAwesomeIcon('remove', 2).' </a>';
    } else {
        $cmd .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="'.$thisurl.'?what=deleteinstances&vids[]='.$instance->id.'" title="'.$plugin->get_lang('deleteinstances').'">
         &nbsp;'.Display::returnFontAwesomeIcon('remove', 2).' </a>';
    }

    $crondate = $instance->lastcron ? date('r', $instance->lastcron) : '';
    $data = [
        $checkbox,
        $sitelink.' '.$instance->institution.' ('.Display::url($instance->root_web, $instance->root_web, ['target' => '_blank']).')',
        $instance->db_host.' - '.$instance->main_database,
        $instance->slug,
        $status,
        $crondate,
        $cmd,
    ];
    $attrs = ['center' => 'left'];
    $table->addRow($data, $attrs, 'td');
    $i++;
}

$items = [
    [
        'url' => $thisurl.'?what=newinstance',
        'content' => $plugin->get_lang('newinstance'),
    ],
    [
        'url' => $thisurl.'?what=import',
        'content' => $plugin->get_lang('ImportInstance'),
    ],
    [
        'url' => $thisurl.'?what=snapshotinstance&vid=0',
        'content' => $plugin->get_lang('snapshotmaster'),
    ],
    [
        'url' => $thisurl.'?what=clearcache&vid=0',
        'content' => $plugin->get_lang('clearmastercache'),
    ],
    [
        'url' => api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/syncparams.php',
        'content' => $plugin->get_lang('sync_settings'),
    ],
    [
        'url' => api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?name=vchamilo',
        'content' => get_lang('Settings'),
    ],
];

$content = Display::page_header('VChamilo Instances');

$content .= Display::actions($items);
$content .= '<form action="'.$thisurl.'">';
$content .= $table->toHtml();

$selectionoptions = ['<option value="0" selected="selected">'.$plugin->get_lang('choose').'</option>'];
$selectionoptions[] = '<option value="deleteinstances">'.$plugin->get_lang('deleteinstances').'</option>';
$selectionoptions[] = '<option value="enableinstances">'.$plugin->get_lang('enableinstances').'</option>';
$selectionoptions[] = '<option value="fulldeleteinstances">'.$plugin->get_lang(
        'destroyinstances'
    ).'</option>';
$selectionoptions[] = '<option value="clearcache">'.$plugin->get_lang('clearcache').'</option>';
$selectionoptions[] = '<option value="setconfigvalue">'.$plugin->get_lang('setconfigvalue').'</option>';
$selectionaction = '<select name="what" onchange="this.form.submit()">'.implode('', $selectionoptions).'</select>';

$content .= '<div class="vchamilo-right"><div></div><div>
<a href="javascript:selectallhosts()">'.$plugin->get_lang('selectall').'</a> -
<a href="javascript:deselectallhosts()">'.$plugin->get_lang('selectnone').'</a> -
&nbsp; - '.$plugin->get_lang('withselection').' '.$selectionaction.'</div></div>';

$content .= '</form>';

if (empty($templates)) {
    $content = '';
}

$tpl = new Template(get_lang('VChamilo'), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
