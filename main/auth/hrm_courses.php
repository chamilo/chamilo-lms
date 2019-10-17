<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$isHrm = api_is_drh();

if (!$isHrm) {
    api_not_allowed(true);
}

$hrm = api_get_user_entity(api_get_user_id());
$assignedUsers = UserManager::get_users_followed_by_drh($hrm->getId());
$users = [];

$courseController = new IndexManager('');

foreach ($assignedUsers as $assignedUserId => $assignedUserInfo) {
    $assignedUser = api_get_user_entity($assignedUserId);

    if (!$assignedUser) {
        continue;
    }

    $userInfo = [
        'username' => $assignedUser->getUsername(),
        'complete_name' => UserManager::formatUserFullName($assignedUser),
        'picture_url' => UserManager::getUserPicture($assignedUserId),
        'course_list' => $courseController->returnCoursesAndSessions($assignedUserId)['html'],
    ];

    $users[$assignedUser->getId()] = $userInfo;
}

$toolName = get_lang('HrmAssignedUsersCourseList');

$view = new Template($toolName);
$view->assign('users', $users);

$content = $view->fetch(
    $view->get_template('auth/hrm_courses.tpl')
);

$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
