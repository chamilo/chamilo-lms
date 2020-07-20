<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/mindmap_plugin.class.php';

$language = 'en';
$platformLanguage = api_get_interface_language();
$iso = api_get_language_isocode($platformLanguage);

if (!api_is_anonymous()) {
    $user = api_get_user_info();
} else {
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$logInfo = [
    'tool' => 'Mindmap',
];
Event::registerLog($logInfo);

$plugin = MindmapPlugin::create();
$tool_name = $plugin->get_lang('plugin_title');
$tpl = new Template($tool_name);

$userId = $user['id'];
$version = 7;
$message = '';
$content = '';
$terms = [];
$form = '';
$cid = 0;
$sessionId = 0;
$isPublic = 0;
$isShared = 0;

if (!$plugin->isEnabled(true)) {
    api_not_allowed(true);
    exit;
} else {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
    $cid = 0;
    if (!empty($_GET['cidReq'])) {
        $cid = api_get_course_int_id($_GET['cidReq']);
    } else {
        $cid = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
    }
    if (!empty($_GET['id_session'])) {
        $sessionId = (int) $_GET['id_session'];
    } else {
        $sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
    }

    if ($cid == 0) {
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    $urlId = api_get_current_access_url_id();
    $UrlWhere = '';
    if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
        $UrlWhere = " AND url_id = $urlId ";
    }
    $sessionWhere = api_get_session_condition($sessionId);

    $sql = "SELECT * , 'mind' as typeedit FROM ".$plugin->table.
        " WHERE user_id = $userId AND c_id = $cid $sessionWhere $UrlWhere";
    if (isset($_GET['id'])) {
        $sql = "SELECT * , 'mind' as typeedit  FROM ".$plugin->table.
            " WHERE id = $id AND user_id = $userId AND c_id = $cid $sessionWhere $UrlWhere";
    }
    $sql .= " UNION SELECT * , 'public' as typeedit FROM ".$plugin->table.
        " WHERE user_id <> $userId AND is_public = 1 AND c_id = $cid $sessionWhere $UrlWhere";
    $result = Database::query($sql);
    $terms = Database::store_result($result, 'ASSOC');
    $countData = count($terms);

    $term = null;

    if ($id > 0) {
        if (!empty($id)) {
            $sql = "SELECT * FROM ".$plugin->table." WHERE id = $id AND user_id = $userId";
            $result = Database::query($sql);
            $term = Database::fetch_array($result, 'ASSOC');
            if (empty($term)) {
                api_not_allowed(true);
            }
        }
    }
    // Show create/edit form
    include 'inc/edit.form.php';

    $htmlHeadXtra[] = '<script src="resources/js/jquery.dataTables.min.js?v='.$version.'" language="javascript"></script>';

    if ($action != 'add' && $action != 'edit') {
        $htmlHeadXtra[] = "<script>
            $(document).ready(function(){
                $('.data_table').DataTable({
                    'iDisplayLength': 50
                });});</script>";
    }

    $htmlHeadXtra[] = "<style>
            .previous{
                margin-right:10px;
                cursor:pointer;
            }
            .next{
                cursor:pointer;
            }
        </style>";

    // Process actions (add/edit/delete)
    // Also exit and redirect to list if action successful
    include 'inc/action.switch.php';
}

$addButton = Display::url(
    Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH).'mindmap/list.php?action=add&cid='.$cid.'&sid='.$sessionId
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar-mindmap', [$addButton])
);

if ($action == 'add') {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PLUGIN_PATH).'mindmap/list.php?cid='.$cid.'&sid='.$sessionId,
        'name' => $plugin->get_lang('plugin_title'),
    ];
    $tpl->assign(
        'breadcrumb',
        return_breadcrumb($interbreadcrumb, null, get_lang('Add'))
    );
    $tpl->assign('terms', '');
} else {
    $tpl->assign('terms', $terms);
}
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('mindmap/view/list.tpl');

$tpl->assign('content', $content);

$tpl->display_one_col_template();
