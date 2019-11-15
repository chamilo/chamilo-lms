<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

// Including necessary files
require_once 'work.lib.php';

$current_course_tool = TOOL_STUDENTPUBLICATION;

$workId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$sessionId = api_get_session_id();

if (empty($workId)) {
    api_not_allowed(true);
}

$my_folder_data = get_work_data_by_id($workId);
if (empty($my_folder_data)) {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list_all.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('AddUsers')];

switch ($action) {
    case 'add':
        $data = getUserToWork($userId, $workId, api_get_course_int_id());
        if (empty($data)) {
            addUserToWork($userId, $workId, api_get_course_int_id());
        }
        $url = api_get_path(WEB_CODE_PATH).'work/add_user.php?id='.$workId.'&'.api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('Added')));
        header('Location: '.$url);
        exit;
        break;
    case 'delete':
        if (!empty($workId) && !empty($userId)) {
            deleteUserToWork($userId, $workId, api_get_course_int_id());
            Display::addFlash(Display::return_message(get_lang('Deleted')));

            $url = api_get_path(WEB_CODE_PATH).'work/add_user.php?id='.$workId.'&'.api_get_cidreq();
            header('Location: '.$url);
            exit;
        }
        break;
}

Display::display_header(null);

$items = getAllUserToWork($workId, api_get_course_int_id());
$usersAdded = [];
if (!empty($items)) {
    echo Display::page_subheader(get_lang('UsersAdded'));
    echo '<ul class="list-group">';
    foreach ($items as $data) {
        $myUserId = $data['user_id'];
        $usersAdded[] = $myUserId;
        $userInfo = api_get_user_info($myUserId);
        $url = api_get_path(WEB_CODE_PATH).'work/add_user.php?action=delete&id='.$workId.'&user_id='.$myUserId;
        $link = Display::url(
            '<em class="fa fa-trash"></em> '.get_lang('Delete'),
            $url,
            ['class' => 'btn btn-danger btn-sm']
        );
        echo '<li class="list-group-item">'.
                $userInfo['complete_name_with_username'].'<div class="pull-right">'.$link.'</div></li>';
    }
    echo '</ul>';
}

$status = 0;
if (empty($sessionId)) {
    $status = STUDENT;
}

$userList = CourseManager::get_user_list_from_course_code(
    $courseInfo['code'],
    $sessionId,
    null,
    null,
    $status
);

$userToAddList = [];
foreach ($userList as $user) {
    if (!in_array($user['user_id'], $usersAdded)) {
        $userToAddList[] = $user;
    }
}

if (!empty($userToAddList)) {
    echo Display::page_subheader(get_lang('UsersToAdd'));
    echo '<ul class="list-group">';
    foreach ($userToAddList as $user) {
        $userName = api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].') ';
        $url = api_get_path(WEB_CODE_PATH).'work/add_user.php?action=add&id='.$workId.'&user_id='.$user['user_id'];
        $link = Display::url(
            '<em class="fa fa-plus"></em> '.get_lang('Add'),
            $url,
            ['class' => 'btn btn-primary btn-sm']
        );
        echo '<li class="list-group-item">'.$userName.'<div class="pull-right"> '.$link.'</div></li>';
    }
    echo '</ul>';
} else {
    echo Display::return_message(get_lang('NoUsersToAdd'), 'warning');
}

echo '<hr /><div class="clear"></div>';
Display::display_footer();
