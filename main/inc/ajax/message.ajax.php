<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

use Chamilo\UserBundle\Entity\User;
use Chamilo\UserBundle\Entity\Repository\UserRepository;

require_once '../global.inc.php';

$action = $_GET['a'];

switch ($action) {
    case 'send_message':
        $subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : null;
        $messageContent = isset($_REQUEST['content']) ? trim($_REQUEST['content']) : null;

        if (empty($subject) || empty($messageContent)) {
            echo Display::display_error_message(get_lang('ErrorSendingMessage'));
            exit;
        }

        $result = MessageManager::send_message($_REQUEST['user_id'], $subject, $messageContent);
        if ($result) {
            echo Display::display_confirmation_message(get_lang('MessageHasBeenSent'));
        } else {
            echo Display::display_error_message(get_lang('ErrorSendingMessage'));
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
        $repo = Database::getManager()
            ->getRepository('ChamiloUserBundle:User');

        $users = $repo->findUsersToSendMessage(
            api_get_user_id(),
            $_REQUEST['q'],
            $_REQUEST['page_limit']
        );

        $showEmail = api_get_setting('show_email_addresses') === 'true';
        $return = ['items' => []];

        /** @var User $user */
        foreach ($users as $user) {
            $userName = $user->getCompleteName();

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
