<?php
/* For licensing terms, see /license.txt */

/**
 * Config the plugin
 * @author Enrique Alcaraz Lopez
 * @package chamilo.plugin.redirection
 */

require_once __DIR__.'/config.php';

api_protect_admin_script();

$list = RedirectionPlugin::getAll();

if (isset($_REQUEST['id'])) {
    RedirectionPlugin::delete($_REQUEST['id']);
    Display::addFlash(Display::return_message(get_lang('Deleted')));
    header('Location: index.php');
    exit;
} elseif (isset($_POST['submit_button'])) {
    $result = RedirectionPlugin::insert($_POST['user_id'], $_POST['url']);
    if ($result) {
        Display::addFlash(Display::return_message(get_lang('Added')));
    } else {
        Display::addFlash(Display::return_message(get_lang('Error'), 'warning'));
    }
    header('Location: index.php');
    exit;
}

$content = '
<form action="./index.php" method="post">
    <div class="table-responsive well">
        <table class="table table-condensed">
            <thead>
            <td><input type="text" class="form-control" placeholder="User Id" name="user_id"/></td>
            <td><input type="text" class="form-control" placeholder="URL" name="url"/></td>
            <td><input type=\'submit\' value=\'Add\' name="submit_button" class=\'btn btn-primary\'/></td>
            </thead>
        </table>
    </div>
</form>
<div class="table-responsive">
    <table class="table table-bordered table-condensed">
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
    $content.= '<tr>';
    $content.= '<td>'.$userName.'</td>';
    $content.= '<td>'.$item['url'].'</td>';
    $content.= '<td><a class="btn btn-danger" href="index.php?id='.$item['id'].'">Delete</a></td>';
    $content.= '</tr>';
}

$content.= '
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
