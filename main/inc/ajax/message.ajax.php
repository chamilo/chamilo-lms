<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;
use Chamilo\UserBundle\Entity\Repository\UserRepository;

$_dont_save_user_course_access = true;

/**
 * Responses to AJAX calls
 */

require_once __DIR__.'/../global.inc.php';

$action = $_GET['a'];

switch ($action) {
    case 'get_count_message':
        $userId = api_get_user_id();
        $total_invitations = 0;
        $group_pending_invitations = 0;

        // Setting notifications
        $count_unread_message = 0;
        if (api_get_setting('allow_message_tool') == 'true') {
            // get count unread message and total invitations
            $count_unread_message = MessageManager::getNumberOfMessages(true);
        }

        if (api_get_setting('allow_social_tool') == 'true') {
            $number_of_new_messages_of_friend = SocialManager::get_message_number_invitation_by_user_id(
                $userId
            );
            $usergroup = new UserGroup();
            $group_pending_invitations = $usergroup->get_groups_by_user(
                $userId,
                GROUP_USER_PERMISSION_PENDING_INVITATION,
                false
            );
            if (!empty($group_pending_invitations)) {
                $group_pending_invitations = count($group_pending_invitations);
            } else {
                $group_pending_invitations = 0;
            }
            $total_invitations = intval($number_of_new_messages_of_friend) + $group_pending_invitations + intval($count_unread_message);
        }
        echo $total_invitations;
        break;
    case 'send_message':
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
        $subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : null;
        $invitationContent = isset($_REQUEST['content']) ? trim($_REQUEST['content']) : null;

        SocialManager::sendInvitationToUser($_REQUEST['user_id'], $subject, $invitationContent);
        break;
    case 'find_users':
        if (api_is_anonymous()) {
            echo '';
            break;
        }

        /** @var UserRepository $repo */
        $repo = Database::getManager()->getRepository('ChamiloUserBundle:User');

        $users = $repo->findUsersToSendMessage(
            api_get_user_id(),
            $_REQUEST['q'],
            $_REQUEST['page_limit']
        );

        $showEmail = api_get_setting('show_email_addresses') === 'true';
        $return = ['items' => []];

        /** @var User $user */
        foreach ($users as $user) {
            $userName = $user->getCompleteNameWithUsername();

            if ($showEmail) {
                $userName .= " ({$user->getEmail()})";
            }

            $return['items'][] = [
                'text' => $userName,
                'id' => $user->getId()
            ];
        }

        echo json_encode($return);
        break;
    default:
        echo '';

}
exit;
