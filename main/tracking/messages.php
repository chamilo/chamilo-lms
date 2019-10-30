<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$allow = api_get_configuration_value('allow_user_message_tracking');

if (!$allow) {
    api_not_allowed(true);
}

$allowUser = api_is_platform_admin() || api_is_drh();

if (!$allowUser) {
    api_not_allowed(true);
}

$fromUserId = isset($_GET['from_user']) ? (int) $_GET['from_user'] : 0;
$toUserId = isset($_GET['to_user']) ? (int) $_GET['to_user'] : 0;
if (empty($fromUserId) || empty($toUserId)) {
    api_not_allowed(true);
}

if (api_is_drh()) {
    $isFollowed = UserManager::is_user_followed_by_drh($fromUserId, api_get_user_id());
    if (api_drh_can_access_all_session_content()) {
        $students = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
            'drh_all',
            api_get_user_id(),
            false,
            0, //$from,
            null, //$limit,
            null, //$column,
            'desc', //$direction,
            null, //$keyword,
            null, //$active,
            null, //$lastConnectionDate,
            null,
            null,
            STUDENT
        );

        if (empty($students)) {
            api_not_allowed(true);
        }
        $userIdList = [];
        foreach ($students as $student) {
            $userIdList[] = $student['user_id'];
        }

        if (!in_array($fromUserId, $userIdList)) {
            api_not_allowed(true);
        }
    } else {
        if (!$isFollowed) {
            api_not_allowed(true);
        }
    }
}

$usersData[$toUserId] = api_get_user_info($toUserId);
$usersData[$fromUserId] = api_get_user_info($fromUserId);
$messages = MessageManager::getAllMessagesBetweenStudents($toUserId, $fromUserId);

$content = Display::page_subheader2(sprintf(
    get_lang('Messages exchange between %s and %s'),
    $usersData[$toUserId]['complete_name'],
    $usersData[$fromUserId]['complete_name']
));

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'mySpace/student.php',
    'name' => get_lang('My learners'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$fromUserId,
    'name' => get_lang('Learner details'),
];

$uniqueMessageList = [];
foreach ($messages as $message) {
    $message['title'].
    $subText = get_lang('From').': '.$usersData[$message['user_sender_id']]['complete_name'];
    $title = empty($message['title']) ? get_lang('Untitled') : $message['title'];
    $title = $title.' - '.$subText.'<span class="pull-right">'.Display::dateToStringAgoAndLongDate($message['send_date']).'</span>';
    $messageId = $message['id'];

    $hash = sha1($message['title'].$message['content'].$message['send_date']);
    if (in_array($hash, $uniqueMessageList)) {
        continue;
    }

    $content .= Display::panelCollapse(
        $title,
        $message['content'].'<br />'.Display::dateToStringAgoAndLongDate($message['send_date']),
        'message-'.$message['id'],
        null,
        'message-'.$message['id'],
        'collapse-'.$message['id'],
        false
    );
    $uniqueMessageList[] = $hash;
}

$template = new Template(get_lang('Message tracking'));
$template->assign('content', $content);
$template->display_one_col_template();
