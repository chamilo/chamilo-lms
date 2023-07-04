<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\SocialPostFeedback;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : null;

$current_user_id = api_get_user_id();
switch ($action) {
    case 'show_my_friends':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $user_id = api_get_user_id();
        $name_search = Security::remove_XSS($_POST['search_name_q']);

        if (isset($name_search) && 'undefined' != $name_search) {
            $friends = SocialManager::get_friends($user_id, null, $name_search);
        } else {
            $friends = SocialManager::get_friends($user_id);
        }

        $friend_html = '';
        $number_of_images = 8;
        $number_friends = count($friends);
        if (0 != $number_friends) {
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
                                            <button class="btn btn--danger" onclick="delete_friend(this)" id=img_'.$friend['friend_user_id'].'>
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
        $user_id = Session::read('social_user_id');

        if ($_POST['action']) {
            $action = $_POST['action'];
        }

        switch ($action) {
            case 'load_course':
                $courseId = intval($_POST['course_code']); // the int course id
                $course = api_get_course_entity($courseId);
                $course_code = $course->getCode();
                $user = api_get_user_entity();

                if ($course->hasSubscriptionByUser($user)) {
                    //------Forum messages
                    $forum_result = Container::getForumPostRepository()->countUserForumPosts($user, $course);
                    $all_result_data = 0;
                    if ('' != $forum_result) {
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
                    $result = Blog::getBlogPostFromUser($courseId, $user_id, $courseCode);

                    if (!empty($result)) {
                        Display::page_subheader2(api_xml_http_response_encode(get_lang('Blog')));
                        echo '<div style="background:#FAF9F6; padding:0px;">';
                        echo api_xml_http_response_encode($result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }

                    //------Blog comments
                    $result = Blog::getBlogCommentsFromUser($courseId, $user_id, $course_code);
                    if (!empty($result)) {
                        echo '<div  style="background:#FAF9F6; padding-left:10px;">';
                        Display::page_subheader2(api_xml_http_response_encode(get_lang('Blog comments')));
                        echo api_xml_http_response_encode($result);
                        echo '</div>';
                        echo '<br />';
                        $all_result_data++;
                    }
                    if (0 == $all_result_data) {
                        echo api_xml_http_response_encode(get_lang('No data available'));
                    }
                } else {
                    echo '<div class="clear"></div><br />';
                    Display::page_subheader2(api_xml_http_response_encode(get_lang('Details')));
                    echo '<div style="background:#FAF9F6; padding:0px;">';
                    echo api_xml_http_response_encode(get_lang('User not registered in course'));
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
                    get_lang('See more'),
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
        $messageRepo = $em->getRepository(SocialPost::class);
        $messageLikesRepo = $em->getRepository(SocialPostFeedback::class);

        $message = $messageRepo->find($messageId);

        if (empty($message)) {
            echo json_encode(false);
            exit;
        }

        if (!empty($message->getGroupReceiver())) {
            if ($message->getGroupReceiver()->getId() !== $groupId) {
                echo json_encode(false);
                exit;
            }

            $usergroup = new UserGroupModel();
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

        $userLike = $messageLikesRepo->findOneBy(['post' => $message, 'user' => $user]);

        if (empty($userLike)) {
            $userLike = new SocialPostFeedback();
            $userLike
                ->setSocialPost($message)
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
