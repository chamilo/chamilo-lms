<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

require_once __DIR__.'/0_dal/dal.global_lib.php';

require_once __DIR__.'/teachdoc_hub.php';

require_once __DIR__.'0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

$language = 'en';
$platformLanguage = api_get_interface_language();
$iso = api_get_language_isocode($platformLanguage);

if (!api_is_anonymous()) {
    $user = api_get_user_info();

    if (isset($user['status'])) {
        if (SESSIONADMIN == $user['status']
        || COURSEMANAGER == $user['status']
        || PLATFORM_ADMIN == $user['status']) {
        } else {
            echo "<script>location.href = '../../index.php';</script>";

            exit;
        }
    }
} else {
    echo "<script>location.href = '../../index.php';</script>";

    exit;
}

$userId = (int) $user['id'];

$vers = 6;

$plugin = teachdoc_hub::create();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$table = 'plugin_oel_tools_teachdoc';

$idurl = api_get_current_access_url_id();
$UrlWhere = '';
if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
    $UrlWhere = " AND id_url = $idurl ";
}

$sql = "SELECT * FROM $table WHERE id_parent = 0 AND id_user = $userId $UrlWhere ORDER BY title";
if (isset($_GET['id'])) {
    $sql = "SELECT * FROM $table  WHERE id <> $id AND id_user = $userId $UrlWhere LIMIT 2";
}

$result = $VDB->query_to_array($sql);

$countData = count($result);

$action = isset($_GET['action']) ? $VDB->remove_XSS($_GET['action']) : 'add';

$term = null;

if ($id > 0) {
    $result = Database::query("SELECT * FROM $table WHERE id = $id AND id_user = $userId ");
    $term = Database::fetch_array($result, 'ASSOC');
    if (empty($term)) {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = '<script src="resources/js/teachdoc-app.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script src="resources/js/jquery.dataTables.min.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = "<script>
$(document).ready(function(){
	$('.data_table').DataTable({
		'iDisplayLength': 50
	});
} );
</script>";

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

// previous

$form = new FormValidator('dictionary', 'post', api_get_self().'?action='.$action.'&id='.$id);

$form->addText('title', 'Title', true);

$form->addButtonSave('&nbsp;&nbsp;'.get_lang('Save').'&nbsp;&nbsp;');

switch ($action) {
    case 'add':
        if ($form->validate()) {
            $values = $form->getSubmitValues();

            $date = new DateTime();
            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('j');

            $dateStr = $day.'/'.$month.'/'.$year;

            $params = [
                'title' => $values['title'],
                'date_create' => $dateStr,
                'id_parent' => 0,
                'id_user' => $userId,
                'type_base' => 0,
                'order_lst' => 1,
                'type_node' => 1,
                'id_url' => $idurl,
            ];

            $result = Database::insert($table, $params);
            if ($result) {
                Display::addFlash(Display::return_message(get_lang('Added')));
            }
            header('Location: '.api_get_self());

            exit;
        }

        break;

    case 'edit':
        $form->setDefaults($term);
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $params = [
                'title' => $values['title'],
            ];
            Database::update($table, $params, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Updated')));

            header('Location: '.api_get_self());

            exit;
        }

        break;

    case 'delete':
        if (!empty($term)) {
            Database::delete($table, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            header('Location: '.api_get_self());

            exit;
        }

        break;
}

$tpl = new Template('TeachDoc HUB');
$tpl->assign('terms', $terms);
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('/CStudio/view/page_list-v12.tpl');

$tpl->assign('content', $content);

$tpl->display_one_col_template();
