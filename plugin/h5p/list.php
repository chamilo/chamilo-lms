<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/h5p_plugin.class.php';

$language = 'en';
$platformLanguage = api_get_interface_language();
$iso = api_get_language_isocode($platformLanguage);
$awp = api_get_path(WEB_PATH);

if (api_is_anonymous()) {
    header('Location: '.$awp.'index.php');
    exit;
} else {
    $user = api_get_user_info();
    if (!api_is_platform_admin(true, true)) {
        // Prevent non-admins to see this
        header('Location: '.$awp.'index.php');
        exit;
    }
}

$plugin = H5PPlugin::create();
if (!$plugin->isEnabled(true)) {
    api_not_allowed(true);
    exit;
}

//Update 1.5 comment after first update
$sqlUpdate = "SELECT COUNT(*) as nb FROM information_schema.COLUMNS WHERE COLUMN_NAME = 'terms_d' and TABLE_NAME LIKE 'plugin_h5p'";
$resultUpdate = Database::query($sqlUpdate);
$rowUpdate = Database::fetch_array($resultUpdate);
$intUpdate = (int) $rowUpdate['nb'];
if ($intUpdate == 0) {
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'h5p/update.php?version=1-5');
    exit;
}
//Update 1.5

$userId = $user['id'];

$vers = 6;

//wordsmatch
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$nodeType = isset($_GET['node_type']) ? Security::remove_XSS($_GET['node_type']) : '';
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'add';

$table = 'plugin_h5p';

$urlId = api_get_current_access_url_id();
$UrlWhere = "";
if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
    $UrlWhere = " AND url_id = $urlId ";
}

$sql = "SELECT * FROM $table WHERE user_id = $userId $UrlWhere ORDER BY title";
if (isset($_GET['id'])) {
    $sql = "SELECT * FROM $table  WHERE id <> $id AND user_id = $userId $UrlWhere LIMIT 2";
}

$result = Database::query($sql);
$terms = Database::store_result($result, 'ASSOC');
$countData = count($terms);

$term = null;

if ($id > 0) {
    if (!empty($id)) {
        $sql = "SELECT * FROM $table WHERE id = $id AND user_id = $userId ";
        $result = Database::query($sql);
        $term = Database::fetch_array($result, 'ASSOC');
        if (empty($term)) {
            api_not_allowed(true);
        }
    }
}

include __DIR__.'/inc/translate.php';
include __DIR__.'/inc/edit.form.php';

$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'vendor/studio-42/elfinder/js/elfinder.full.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'vendor/studio-42/elfinder/css/elfinder.full.css">';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'web/assets/jquery-ui/jquery-ui.min.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'web/assets/jquery-ui/themes/smoothness/jquery-ui.min.css">';

$htmlHeadXtra[] = '<link href="resources/js/pell.min.css?v='.$vers.'"  rel="stylesheet" type="text/css" />';
$htmlHeadXtra[] = '<script src="resources/js/pell.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';

$htmlHeadXtra[] = '<script src="resources/js/interface.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script src="resources/js/jquery.dataTables.min.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = "<script>
	$(document).ready(function(){
		$('.data_table').DataTable({
			'iDisplayLength': 50
		});
	});
	var GlobalTypeNode = '".$nodeType."';
	</script>";

if ($nodeType != '') {
    $htmlHeadXtra[] = "<script>
			$(document).ready(function(){interface$nodeType();});
		</script>";
}

$htmlHeadXtra[] = "<style>
		.previous{
			margin-right:10px;
			cursor:pointer;
		}
		.next{
			cursor:pointer;
		}
		input[type='radio'] {
			-ms-transform: scale(1.5);
			-webkit-transform: scale(1.5);
			transform: scale(1.5);
		}

	</style>";

include 'inc/action.switch.php';

$tpl = new Template('H5P');
if ($nodeType == '') {
    $tpl->assign('terms', $terms);
}

$tpl->assign('tables', $tableOfnodes);
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('h5p/view/list.tpl');

$tpl->assign('content', $content);

$tpl->display_one_col_template();
