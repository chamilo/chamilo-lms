<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Message;

$_dont_save_user_course_access = true;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_GET['a'];

switch ($action) {
    case 'get_notifications_friends':
        $userId = api_get_user_id();
        $listInvitations = [];
        $temp = [];
        if ('true' === api_get_setting('allow_social_tool')) {
            $list = SocialManager::get_list_invitation_of_friends_by_user_id($userId, 3);

            foreach ($list as $row) {
                $user = api_get_user_info($row['user_sender_id']);
                $temp['title'] = $row['title'];
                $temp['content'] = $row['content'];
                $temp['date'] = $row['send_date'];
                $temp['user_id'] = $user['id'];
                $temp['fullname'] = $user['complete_name'];
                $temp['email'] = $user['email'];
                $temp['avatar'] = $user['avatar_small'];
                $listInvitations[] = $temp;
            }
        }
        header('Content-type:application/json');
        echo json_encode($listInvitations);
        break;
    case 'send_message':
        api_block_anonymous_users(false);

        $subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : null;
        $messageContent = isset($_REQUEST['content']) ? trim($_REQUEST['content']) : null;

        if (empty($subject) || empty($messageContent)) {
            echo Display::return_message(get_lang('There was an error while trying to send the message.'), 'error');
            exit;
        }

        $courseId = isset($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : 0;
        $sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;

        // Add course info
        if (!empty($courseId)) {
            $courseInfo = api_get_course_info_by_id($courseId);
            if (!empty($courseInfo)) {
                if (empty($sessionId)) {
                    $courseNotification = sprintf(get_lang('This e-mail was sent via course %s'), $courseInfo['title']);
                } else {
                    $sessionInfo = api_get_session_info($sessionId);
                    if (!empty($sessionInfo)) {
                        $courseNotification = sprintf(
                            get_lang('This e-mail was sent via course %sInSessionX'),
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
            echo Display::return_message(get_lang('Your message has been sent.'), 'confirmation');
        } else {
            echo Display::return_message(
                get_lang('There was an error while trying to send the message.'),
                'confirmation'
            );
        }
        break;
    case 'send_invitation':
        api_block_anonymous_users(false);

        $subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : null;
        $invitationContent = isset($_REQUEST['content']) ? trim($_REQUEST['content']) : null;

        $result = SocialManager::sendInvitationToUser($_REQUEST['user_id'], $subject, $invitationContent);
        echo $result ? 1 : 0;
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

        $showEmail = 'true' === api_get_setting('show_email_addresses');
        $return = ['items' => []];

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
    default:
        echo '';
}
exit;
