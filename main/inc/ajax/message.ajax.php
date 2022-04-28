<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

$_dont_save_user_course_access = true;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_GET['a'];

switch ($action) {
    case 'get_count_notifications':
        if (api_get_configuration_value('notification_event')) {
            $notificationManager = new NotificationEvent();
            $notifications = $notificationManager->getNotificationsByUser(api_get_user_id());
            echo count($notifications);
        }
        break;
    case 'get_notifications':
        if (api_get_configuration_value('notification_event')) {
            $notificationManager = new NotificationEvent();
            $notifications = $notificationManager->getNotificationsByUser(api_get_user_id());
            echo json_encode($notifications);
        }
        break;
    case 'mark_notification_as_read':
        if (api_get_configuration_value('notification_event')) {
            $id = $_REQUEST['id'] ?? 0;
            $notificationManager = new NotificationEvent();
            $notificationManager->markAsRead($id);
            echo 1;
        }
        break;
    case 'get_count_message':
        $userId = api_get_user_id();
        $invitations = MessageManager::getMessagesCountForUser($userId);
        header('Content-type:application/json');
        echo json_encode($invitations);
        break;
    case 'send_message':
        api_block_anonymous_users(false);

        $subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : null;
        $messageContent = isset($_REQUEST['content']) ? trim($_REQUEST['content']) : null;

        if (empty($subject) || empty($messageContent)) {
            echo Display::return_message(get_lang('ErrorSendingMessage'), 'error');
            exit;
        }

        $courseId = isset($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : 0;
        $sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;

        // Add course info
        if (!empty($courseId)) {
            $courseInfo = api_get_course_info_by_id($courseId);
            if (!empty($courseInfo)) {
                if (empty($sessionId)) {
                    $courseNotification = sprintf(get_lang('ThisEmailWasSentViaCourseX'), $courseInfo['title']);
                } else {
                    $sessionInfo = api_get_session_info($sessionId);
                    if (!empty($sessionInfo)) {
                        $courseNotification = sprintf(
                            get_lang('ThisEmailWasSentViaCourseXInSessionX'),
                            $courseInfo['title'],
                            $sessionInfo['name']
                        );
                    }
                }
                $messageContent .= '<br /><br />'.$courseNotification;
            }
        }

        $result = MessageManager::send_message($_REQUEST['user_id'], $subject, $messageContent);
        if ($result) {
            echo Display::return_message(get_lang('MessageHasBeenSent'), 'confirmation');
        } else {
            echo Display::return_message(get_lang('ErrorSendingMessage'), 'confirmation');
        }
        break;
    case 'send_invitation':
        api_block_anonymous_users(false);

        $subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : null;
        $invitationContent = isset($_REQUEST['content']) ? trim($_REQUEST['content']) : null;

        SocialManager::sendInvitationToUser($_REQUEST['user_id'], $subject, $invitationContent);
        break;
    case 'find_users':
        if (api_is_anonymous()) {
            echo '';
            break;
        }

        $repo = UserManager::getRepository();
        $users = $repo->findUsersToSendMessage(
            api_get_user_id(),
            $_REQUEST['q'],
            $_REQUEST['page_limit']
        );

        $showEmail = api_get_setting('show_email_addresses') === 'true';
        $return = ['items' => []];

        /** @var User $user */
        foreach ($users as $user) {
            $userName = UserManager::formatUserFullName($user, true);

            if ($showEmail) {
                $userName .= " ({$user->getEmail()})";
            }

            $return['items'][] = [
                'text' => $userName,
                'id' => $user->getId(),
            ];
        }
        header('Content-type:application/json');
        echo json_encode($return);
        break;
    case 'add_tags':
        $idList = $_POST['id'] ?? [];
        $tagList = $_POST['tags'] ?? [];

        if (false === api_get_configuration_value('enable_message_tags')
            || api_is_anonymous()
            || api_get_setting('allow_message_tool') !== 'true'
            || empty($idList) || empty($tagList)
        ) {
            break;
        }

        $em = Database::getManager();
        $userId = api_get_user_id();

        $extraFieldValues = new ExtraFieldValue('message');

        foreach ($idList as $messageId) {
            $messageInfo = MessageManager::get_message_by_id($messageId);

            if ($messageInfo['msg_status'] == MESSAGE_STATUS_OUTBOX
                && $messageInfo['user_sender_id'] != $userId
            ) {
                continue;
            }

            if (in_array($messageInfo['msg_status'], [MESSAGE_STATUS_UNREAD, MESSAGE_STATUS_NEW])
                && $messageInfo['user_receiver_id'] != $userId
            ) {
                continue;
            }

            $extraParams = [
                'item_id' => $messageInfo['id'],
                'extra_tags' => $tagList,
            ];

            $extraFieldValues->saveFieldValues($extraParams, false, false, ['tags'], [], false, false);
        }
        break;
    default:
        echo '';
}
exit;
