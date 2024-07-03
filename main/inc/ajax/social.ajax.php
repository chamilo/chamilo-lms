<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageFeedback;
use ChamiloSession as Session;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : null;

$current_user_id = api_get_user_id();
switch ($action) {
    case 'add_friend':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $relation_type = USER_RELATION_TYPE_UNKNOWN; //Unknown contact
        if (isset($_GET['is_my_friend'])) {
            $relation_type = USER_RELATION_TYPE_FRIEND; //My friend
        }

        if (isset($_GET['friend_id'])) {
            $my_current_friend = $_GET['friend_id'];
            UserManager::relate_users($current_user_id, $my_current_friend, $relation_type);
            UserManager::relate_users($my_current_friend, $current_user_id, $relation_type);
            SocialManager::invitation_accepted($my_current_friend, $current_user_id);
            Display::addFlash(
                Display::return_message(get_lang('AddedContactToList'), 'success')
            );

            header('Location: '.api_get_path(WEB_CODE_PATH).'social/invitations.php');
            exit;
        }
        break;
    case 'deny_friend':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $relation_type = USER_RELATION_TYPE_UNKNOWN; //Contact unknown
        if (isset($_GET['is_my_friend'])) {
            $relation_type = USER_RELATION_TYPE_FRIEND; //my friend
        }
        if (isset($_GET['denied_friend_id'])) {
            SocialManager::invitation_denied($_GET['denied_friend_id'], $current_user_id);
            Display::addFlash(
                Display::return_message(get_lang('InvitationDenied'), 'success')
            );

            header('Location: '.api_get_path(WEB_CODE_PATH).'social/invitations.php');
            exit;
        }
        break;
    case 'delete_friend':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $my_delete_friend = (int) $_POST['delete_friend_id'];
        if (isset($_POST['delete_friend_id'])) {
            SocialManager::remove_user_rel_user($my_delete_friend);
        }
        break;
    case 'show_my_friends':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $user_id = api_get_user_id();
        $name_search = Security::remove_XSS($_POST['search_name_q']);

        if (isset($name_search) && $name_search != 'undefined') {
            $friends = SocialManager::get_friends($user_id, null, $name_search);
        } else {
            $friends = SocialManager::get_friends($user_id);
        }

        $friend_html = '';
        $number_of_images = 8;
        $number_friends = count($friends);
        if ($number_friends != 0) {
            $number_loop = $number_friends / $number_of_images;
            $loop_friends = ceil($number_loop);
            $j = 0;
            for ($k = 0; $k < $loop_friends; $k++) {
                if ($j == $number_of_images) {
                    $number_of_images = $number_of_images * 2;
                }
                while ($j < $number_of_images) {
                    if (isset($friends[$j])) {
                        $friend = $friends[$j];
                        $user_name = api_xml_http_response_encode($friend['firstName'].' '.$friend['lastName']);
                        $userPicture = UserManager::getUserPicture($friend['friend_user_id']);

                        $friend_html .= '
                            <div class="col-md-3">
                                <div class="thumbnail text-center" id="div_'.$friends[$j]['friend_user_id'].'">
                                    <img src="'.$userPicture.'" class="img-responsive" id="imgfriend_'.$friend['friend_user_id'].'" title="$user_name">
                                    <div class="caption">
                                        <h3>
                                            <a href="profile.php?u='.$friend['friend_user_id'].'">'.$user_name.'</a>
                                        </h3>
                                        <p>
                                            <button class="btn btn-danger" onclick="delete_friend(this)" id=img_'.$friend['friend_user_id'].'>
                                                '.get_lang('Delete').'
                                            </button>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ';
                    }
                    $j++;
                }
            }
        }
        echo $friend_html;
        break;
    case 'toogle_course':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $user_id = Session::read('social_user_id');

        if ($_POST['action']) {
            $action = $_POST['action'];
        }

        switch ($action) {
            case 'load_course':
                $course_id = intval($_POST['course_code']); // the int course id
                $course_info = api_get_course_info_by_id($course_id);
                $course_code = $course_info['code'];

                if (api_is_user_of_course($course_id, api_get_user_id())) {
                    //------Forum messages
                    $forum_result = get_all_post_from_user($user_id, $course_code);
                    $all_result_data = 0;
                    if ($forum_result != '') {
                        echo '<div id="social-forum-main-title">';
                        echo api_xml_http_response_encode(get_lang('Forum'));
                        echo '</div>';

                        echo '<div style="background:#FAF9F6; padding:0px;" >';
                        echo api_xml_http_response_encode($forum_result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }

                    //------Blog posts
                    $result = Blog::getBlogPostFromUser($course_id, $user_id, $course_code);

                    if (!empty($result)) {
                        api_display_tool_title(api_xml_http_response_encode(get_lang('Blog')));
                        echo '<div style="background:#FAF9F6; padding:0px;">';
                        echo api_xml_http_response_encode($result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }

                    //------Blog comments
                    $result = Blog::getBlogCommentsFromUser($course_id, $user_id, $course_code);
                    if (!empty($result)) {
                        echo '<div  style="background:#FAF9F6; padding-left:10px;">';
                        api_display_tool_title(api_xml_http_response_encode(get_lang('BlogComments')));
                        echo api_xml_http_response_encode($result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }
                    if ($all_result_data == 0) {
                        echo api_xml_http_response_encode(get_lang('NoDataAvailable'));
                    }
                } else {
                    echo '<div class="clear"></div><br />';
                    api_display_tool_title(api_xml_http_response_encode(get_lang('Details')));
                    echo '<div style="background:#FAF9F6; padding:0px;">';
                    echo api_xml_http_response_encode(get_lang('UserNonRegisteredAtTheCourse'));
                    echo '<div class="clear"></div><br />';
                    echo '</div>';
                    echo '<div class="clear"></div><br />';
                }
                break;
            case 'unload_course':
            default:
                break;
        }
        break;
    case 'send_comment':
        if (api_is_anonymous()) {
            exit;
        }

        if (api_get_setting('allow_social_tool') !== 'true') {
            exit;
        }

        $messageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (empty($messageId)) {
            exit;
        }

        $userId = api_get_user_id();
        $messageInfo = MessageManager::get_message_by_id($messageId);
        if (!empty($messageInfo)) {
            $comment = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
            if (!empty($comment)) {
                $messageId = SocialManager::sendWallMessage(
                    $userId,
                    $messageInfo['user_receiver_id'],
                    $comment,
                    $messageId,
                    MESSAGE_STATUS_WALL
                );
                if ($messageId) {
                    $messageInfo = MessageManager::get_message_by_id($messageId);
                    echo SocialManager::processPostComment($messageInfo);
                }
            }
        }
        break;
    case 'delete_message':
        if (api_is_anonymous()) {
            exit;
        }

        if (api_get_setting('allow_social_tool') !== 'true') {
            exit;
        }

        $messageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (empty($messageId)) {
            exit;
        }

        if (!Security::check_token('get', null, 'social')) {
            exit;
        }

        $userId = api_get_user_id();
        $messageInfo = MessageManager::get_message_by_id($messageId);
        if (!empty($messageInfo)) {
            $canDelete = ($messageInfo['user_receiver_id'] == $userId || $messageInfo['user_sender_id'] == $userId) &&
                empty($messageInfo['group_id']);
            if ($canDelete || api_is_platform_admin()) {
                SocialManager::deleteMessage($messageId);
                echo json_encode([
                    'message' => Display::return_message(get_lang('MessageDeleted')),
                    'secToken' => Security::get_token('social'),
                ]);
                break;
            }
        }
        break;
    case 'list_wall_message':
        if (api_is_anonymous()) {
            break;
        }
        $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
        $userId = isset($_REQUEST['u']) ? (int) $_REQUEST['u'] : api_get_user_id();

        $html = '';
        if ($userId == api_get_user_id()) {
            $threadList = SocialManager::getThreadList($userId);
            $threadIdList = [];
            if (!empty($threadList)) {
                $threadIdList = array_column($threadList, 'id');
            }

            $html = SocialManager::getMyWallMessages(
                $userId,
                $start,
                SocialManager::DEFAULT_SCROLL_NEW_POST,
                $threadIdList
            );
            $html = $html['posts'];
        } else {
            $messages = SocialManager::getWallMessages(
                $userId,
                null,
                0,
                0,
                '',
                $start,
                SocialManager::DEFAULT_SCROLL_NEW_POST
            );
            $messages = SocialManager::formatWallMessages($messages);

            if (!empty($messages)) {
                ksort($messages);
                foreach ($messages as $message) {
                    $post = $message['html'];
                    $comments = SocialManager::getWallPostComments($userId, $message);
                    $html .= SocialManager::wrapPost($message, $post.$comments);
                }
            }
        }

        if (!empty($html)) {
            $html .= Display::div(
                Display::url(
                    get_lang('SeeMore'),
                    api_get_self().'?u='.$userId.'&a=list_wall_message&start='.
                    ($start + SocialManager::DEFAULT_SCROLL_NEW_POST).'&length='.SocialManager::DEFAULT_SCROLL_NEW_POST,
                    [
                        'class' => 'nextPage',
                    ]
                ),
                [
                    'class' => 'next',
                ]
            );
        }
        echo $html;
        break;
        // Read the Url using OpenGraph and returns the hyperlinks content
    case 'read_url_with_open_graph':
        api_block_anonymous_users(false);

        $url = $_POST['social_wall_new_msg_main'] ?? '';
        $url = trim($url);
        $html = '';
        if (SocialManager::verifyUrl($url)) {
            $html = Security::remove_XSS(
                SocialManager::readContentWithOpenGraph($url)
            );
        }
        echo $html;
        break;
    case 'like_message':
        header('Content-Type: application/json');

        if (
            api_is_anonymous() ||
            !api_get_configuration_value('social_enable_messages_feedback')
        ) {
            echo json_encode(false);
            exit;
        }

        $messageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $groupId = isset($_GET['group']) ? (int) $_GET['group'] : 0;

        if (empty($messageId) || !in_array($status, ['like', 'dislike'])) {
            echo json_encode(false);
            exit;
        }

        $em = Database::getManager();
        $messageRepo = $em->getRepository('ChamiloCoreBundle:Message');
        $messageLikesRepo = $em->getRepository('ChamiloCoreBundle:MessageFeedback');

        /** @var Message $message */
        $message = $messageRepo->find($messageId);

        if (empty($message)) {
            echo json_encode(false);
            exit;
        }

        if ((int) $message->getGroupId() !== $groupId) {
            echo json_encode(false);
            exit;
        }

        if (!empty($message->getGroupId())) {
            $usergroup = new UserGroup();
            $groupInfo = $usergroup->get($groupId);

            if (empty($groupInfo)) {
                echo json_encode(false);
                exit;
            }

            $isMember = $usergroup->is_group_member($groupId, $current_user_id);

            if (GROUP_PERMISSION_CLOSED == $groupInfo['visibility'] && !$isMember) {
                echo json_encode(false);
                exit;
            }
        }

        $user = api_get_user_entity($current_user_id);

        $userLike = $messageLikesRepo->findOneBy(['message' => $message, 'user' => $user]);

        if (empty($userLike)) {
            $userLike = new MessageFeedback();
            $userLike
                ->setMessage($message)
                ->setUser($user);
        }

        if ('like' === $status) {
            if ($userLike->isLiked()) {
                echo json_encode(false);
                exit;
            }

            $userLike
                ->setLiked(true)
                ->setDisliked(false);
        } elseif ('dislike' === $status) {
            if ($userLike->isDisliked()) {
                echo json_encode(false);
                exit;
            }

            $userLike
                ->setLiked(false)
                ->setDisliked(true);
        }

        $userLike
            ->setUpdatedAt(
                api_get_utc_datetime(null, false, true)
            );

        $em->persist($userLike);
        $em->flush();

        echo json_encode(true);
        break;
    default:
        echo '';
}
exit;
