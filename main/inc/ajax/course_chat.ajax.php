<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls for course chat
 */

require_once '../global.inc.php';

if (!api_protect_course_script(false)) {
    exit;
}

$courseId = api_get_course_int_id();
$userId = api_get_user_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$json = ['status' => false];

$courseChatUtils = new CourseChatUtils($courseId, $userId, $sessionId, $groupId);

switch ($_REQUEST['action']) {
    case 'track':
        $courseChatUtils->keepUserAsConnected();
        $courseChatUtils->disconnectInactiveUsers();

        $friend = isset($_REQUEST['friend']) ? intval($_REQUEST['friend']) : 0;
        $filePath = $courseChatUtils->getFileName(true, $friend);
        $newFileSize = file_exists($filePath) ? filesize($filePath) : 0;
        $oldFileSize = isset($_GET['size']) ? intval($_GET['size']) : -1;
        $newUsersOnline = $courseChatUtils->countUsersOnline();
        $oldUsersOnline = isset($_GET['users_online']) ? intval($_GET['users_online']) : 0;

        $json = [
            'status' => true,
            'data' => [
                'oldFileSize' => file_exists($filePath) ? filesize($filePath) : 0,
                'history' => $newFileSize !== $oldFileSize ? $courseChatUtils->readMessages(false, $friend) : null,
                'usersOnline' => $newUsersOnline,
                'userList' => $newUsersOnline != $oldUsersOnline ? $courseChatUtils->listUsersOnline() : null,
                'currentFriend' => $friend
            ]
        ];

        break;
    case 'preview':
        $json = [
            'status' => true,
            'data' => [
                'message' => CourseChatUtils::prepareMessage($_REQUEST['message'])
            ]
        ];
        break;
    case 'reset':
        $friend = isset($_REQUEST['friend']) ? intval($_REQUEST['friend']) : 0;
    
        $json = [
            'status' => true,
            'data' => $courseChatUtils->readMessages(true, $friend)
        ];
        break;
    case 'write':
        $friend = isset($_REQUEST['friend']) ? intval($_REQUEST['friend']) : 0;
        $writed = $courseChatUtils->saveMessage($_POST['message'], $friend);

        $json = [
            'status' => $writed,
            'data' => [
                'writed' => $writed
            ]
        ];
        break;
}

header('Content-Type: application/json');
echo json_encode($json);
