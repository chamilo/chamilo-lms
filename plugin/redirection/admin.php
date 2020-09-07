<?php
/* For licensing terms, see /license.txt */

/**
 * Admin interface for the plugin configuration.
 *
 * @author Enrique Alcaraz Lopez
 *
 * @package chamilo.plugin.redirection
 */
require_once __DIR__.'/config.php';

api_protect_admin_script();

$list = RedirectionPlugin::getAll();

$url = api_get_path(WEB_PLUGIN_PATH).'redirection/admin.php';

$form = new FormValidator('add', 'post', api_get_self());
$form->addHeader('Redirection');
$form->addSelectAjax(
    'user_id',
    get_lang('User'),
    null,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
        'id' => 'user_id',
    ]
);
$form->addUrl('url', 'URL');
$form->addButtonSend(get_lang('Add'));

if (isset($_REQUEST['id'])) {
    RedirectionPlugin::delete($_REQUEST['id']);
    Display::addFlash(Display::return_message(get_lang('Deleted')));
    header('Location: admin.php');
    exit;
}

if ($form->validate()) {
    $result = RedirectionPlugin::insert($_POST['user_id'], $_POST['url']);
    if ($result) {
        Display::addFlash(Display::return_message(get_lang('Added')));
    } else {
        Display::addFlash(Display::return_message(get_lang('Error'), 'warning'));
    }
    header('Location: '.$url);
    exit;
}

$content = $form->returnForm();
$content .= '
<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered table-condensed">
        <tr>
            <th>User</th>
            <th>URL</th>
            <th></th>
        </tr>
';

foreach ($list as $item) {
    $userInfo = api_get_user_info($item['user_id']);
    $userName = get_lang('Unknown');
    if (!empty($userInfo)) {
        $userName = $userInfo['complete_name_with_username'].' - '.$item['user_id'];
    }
    $content .= '<tr>';
    $content .= '<td>'.$userName.'</td>';
    $content .= '<td>'.$item['url'].'</td>';
    $content .= '<td><a class="btn btn-danger" href="'.$url.'?id='.$item['id'].'">Delete</a></td>';
    $content .= '</tr>';
}

$content .= '
</table>
</div>';

$tpl = new Template(
    '',
    true,
    true,
    false,
    false,
    false
);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
