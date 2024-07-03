<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls for course chat.
 */

use Symfony\Component\HttpFoundation\JsonResponse as HttpResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../global.inc.php';

if (!api_protect_course_script(false)) {
    exit;
}

$courseId = api_get_course_int_id();
$userId = api_get_user_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$json = ['status' => false];

$httpRequest = HttpRequest::createFromGlobals();
$httpResponse = HttpResponse::create();

$courseChatUtils = new CourseChatUtils($courseId, $userId, $sessionId, $groupId);

$token = Security::getTokenFromSession('course_chat');

if ($httpRequest->headers->get('x-token') !== $token) {
    $_REQUEST['action'] = 'error';
}

switch ($_REQUEST['action']) {
    case 'chat_logout':
        $logInfo = [
            'tool' => TOOL_CHAT,
            'action' => 'exit',
            'action_details' => 'exit-chat',
        ];
        Event::registerLog($logInfo);
        break;
    case 'track':
        $courseChatUtils->keepUserAsConnected();
        $courseChatUtils->disconnectInactiveUsers();

        $friend = isset($_REQUEST['friend']) ? (int) $_REQUEST['friend'] : 0;
        $filePath = $courseChatUtils->getFileName(true, $friend);
        $newFileSize = file_exists($filePath) ? filesize($filePath) : 0;
        $oldFileSize = isset($_GET['size']) ? (int) $_GET['size'] : -1;
        $newUsersOnline = $courseChatUtils->countUsersOnline();
        $oldUsersOnline = isset($_GET['users_online']) ? (int) $_GET['users_online'] : 0;

        $json = [
            'status' => true,
            'data' => [
                'oldFileSize' => file_exists($filePath) ? filesize($filePath) : 0,
                'history' => $newFileSize !== $oldFileSize ? $courseChatUtils->readMessages(false, $friend) : null,
                'usersOnline' => $newUsersOnline,
                'userList' => $newUsersOnline != $oldUsersOnline ? $courseChatUtils->listUsersOnline() : null,
                'currentFriend' => $friend,
            ],
        ];

        break;
    case 'preview':
        $json = [
            'status' => true,
            'data' => [
                'message' => CourseChatUtils::prepareMessage($_REQUEST['message']),
            ],
        ];
        break;
    case 'reset':
        $friend = isset($_REQUEST['friend']) ? (int) $_REQUEST['friend'] : 0;

        $json = [
            'status' => true,
            'data' => $courseChatUtils->readMessages(true, $friend),
        ];
        break;
    case 'write':
        $friend = isset($_REQUEST['friend']) ? (int) $_REQUEST['friend'] : 0;
        $writed = $courseChatUtils->saveMessage($_POST['message'], $friend);

        $json = [
            'status' => $writed,
            'data' => [
                'writed' => $writed,
            ],
        ];
        break;
}

$token = Security::get_token('course_chat');

$httpResponse->headers->set('x-token', $token);
$httpResponse->setData($json);
$httpResponse->send();
