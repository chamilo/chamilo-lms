<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use ChamiloSession as Session;
use Laminas\Feed\Reader\Entry\Rss;
use Laminas\Feed\Reader\Reader;

/**
 * Class SocialManager.
 *
 * This class provides methods for the social network management.
 * Include/require it in your code to use its features.
 */
class SocialManager extends UserManager
{
    const DEFAULT_WALL_POSTS = 10;
    const DEFAULT_SCROLL_NEW_POST = 5;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Allow to see contacts list.
     *
     * @author isaac flores paz
     *
     * @return array
     */
    public static function show_list_type_friends()
    {
        $table = Database::get_main_table(TABLE_MAIN_USER_FRIEND_RELATION_TYPE);
        $sql = 'SELECT id, title FROM '.$table.'
                WHERE id<>6
                ORDER BY id ASC';
        $result = Database::query($sql);
        $friend_relation_list = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $friend_relation_list[] = $row;
        }
        $count_list = count($friend_relation_list);
        if (0 == $count_list) {
            $friend_relation_list[] = get_lang('Unknown');
        } else {
            return $friend_relation_list;
        }
    }

    /**
     * Get the kind of relation between contacts.
     *
     * @param int  $user_id     user id
     * @param int  $user_friend user friend id
     * @param bool $includeRH   include the RH relationship
     *
     * @return int
     *
     * @author isaac flores paz
     */
    public static function get_relation_between_contacts($user_id, $user_friend, $includeRH = false)
    {
        $table = Database::get_main_table(TABLE_MAIN_USER_FRIEND_RELATION_TYPE);
        $userRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        if (false == $includeRH) {
            $sql = 'SELECT rt.id as id
                FROM '.$table.' rt
                WHERE rt.id = (
                    SELECT uf.relation_type
                    FROM '.$userRelUserTable.' uf
                    WHERE
                        user_id='.((int) $user_id).' AND
                        friend_user_id='.((int) $user_friend).' AND
                        uf.relation_type <> '.UserRelUser::USER_RELATION_TYPE_RRHH.'
                    LIMIT 1
                )';
        } else {
            $sql = 'SELECT rt.id as id
                FROM '.$table.' rt
                WHERE rt.id = (
                    SELECT uf.relation_type
                    FROM '.$userRelUserTable.' uf
                    WHERE
                        user_id='.((int) $user_id).' AND
                        friend_user_id='.((int) $user_friend).'
                    LIMIT 1
                )';
        }
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res, 'ASSOC');

            return (int) $row['id'];
        } else {
            if (api_get_configuration_value('social_make_teachers_friend_all')) {
                $adminsList = UserManager::get_all_administrators();
                foreach ($adminsList as $admin) {
                    if (api_get_user_id() == $admin['user_id']) {
                        return UserRelUser::USER_RELATION_TYPE_GOODFRIEND;
                    }
                }
                $targetUserCoursesList = CourseManager::get_courses_list_by_user_id(
                    $user_id,
                    true,
                    false
                );
                $currentUserId = api_get_user_id();
                foreach ($targetUserCoursesList as $course) {
                    $teachersList = CourseManager::get_teacher_list_from_course_code($course['code']);
                    foreach ($teachersList as $teacher) {
                        if ($currentUserId == $teacher['user_id']) {
                            return UserRelUser::USER_RELATION_TYPE_GOODFRIEND;
                        }
                    }
                }
            } else {
                return UserRelUser::USER_UNKNOWN;
            }
        }
    }

    /**
     * Gets friends id list.
     *
     * @param int  user id
     * @param int group id
     * @param string name to search
     * @param bool true will load firstname, lastname, and image name
     *
     * @return array
     *
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code, function renamed, $load_extra_info option added
     * @author isaac flores paz
     */
    public static function get_friends(
        $user_id,
        $id_group = null,
        $search_name = null,
        $load_extra_info = true
    ) {
        $user_id = (int) $user_id;

        $tbl_my_friend = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_my_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = 'SELECT friend_user_id FROM '.$tbl_my_friend.'
                WHERE
                    relation_type NOT IN ('.UserRelUser::USER_RELATION_TYPE_DELETED.', '.UserRelUser::USER_RELATION_TYPE_RRHH.') AND
                    friend_user_id<>'.$user_id.' AND
                    user_id='.$user_id;
        if (isset($id_group) && $id_group > 0) {
            $sql .= ' AND relation_type='.$id_group;
        }
        if (isset($search_name)) {
            $search_name = trim($search_name);
            $search_name = str_replace(' ', '', $search_name);
            $sql .= ' AND friend_user_id IN (
                SELECT user_id FROM '.$tbl_my_user.'
                WHERE
                    firstName LIKE "%'.Database::escape_string($search_name).'%" OR
                    lastName LIKE "%'.Database::escape_string($search_name).'%" OR
                    '.(api_is_western_name_order() ? 'concat(firstName, lastName)' : 'concat(lastName, firstName)').' LIKE concat("%","'.Database::escape_string($search_name).'","%")
                ) ';
        }

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            if ($load_extra_info) {
                $userInfo = api_get_user_info($row['friend_user_id']);
                $list[] = [
                    'friend_user_id' => $row['friend_user_id'],
                    'firstName' => $userInfo['firstName'],
                    'lastName' => $userInfo['lastName'],
                    'username' => $userInfo['username'],
                    'image' => $userInfo['avatar'],
                    'user_info' => $userInfo,
                ];
            } else {
                $list[] = $row;
            }
        }

        return $list;
    }

    /**
     * Get number of messages sent to other users.
     *
     * @param int $userId
     *
     * @return int
     */
    public static function getCountMessagesSent($userId)
    {
        $userId = (int) $userId;
        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT COUNT(*) FROM '.$table.'
                WHERE
                    user_sender_id='.$userId.' AND
                    msg_status < 5';
        $res = Database::query($sql);
        $row = Database::fetch_row($res);

        return $row[0];
    }

    /**
     * Get number of messages received from other users.
     *
     * @param int $receiver_id
     *
     * @return int
     */
    public static function getCountMessagesReceived($receiver_id)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT COUNT(*) FROM '.$table.'
                WHERE
                    user_receiver_id='.intval($receiver_id).' AND
                    msg_status < 4';
        $res = Database::query($sql);
        $row = Database::fetch_row($res);

        return $row[0];
    }

    /**
     * Get number of messages posted on own wall.
     *
     * @param int $userId
     *
     * @return int
     */
    public static function getCountWallPostedMessages($userId)
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return 0;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT COUNT(*)
                FROM '.$table.'
                WHERE
                    user_sender_id='.$userId.' AND
                    (msg_status = '.MESSAGE_STATUS_WALL.' OR
                    msg_status = '.MESSAGE_STATUS_WALL_POST.') AND
                    parent_id = 0';
        $res = Database::query($sql);
        $row = Database::fetch_row($res);

        return $row[0];
    }

    /**
     * Get invitation list received by user.
     *
     * @author isaac flores paz
     *
     * @param int $userId
     * @param int $limit
     *
     * @return array
     */
    public static function get_list_invitation_of_friends_by_user_id($userId, $limit = 0)
    {
        $userId = (int) $userId;
        $limit = (int) $limit;

        if (empty($userId)) {
            return [];
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT user_sender_id, send_date, title, content
                FROM '.$table.'
                WHERE
                    user_receiver_id = '.$userId.' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        if (null != $limit && $limit > 0) {
            $sql .= ' LIMIT '.$limit;
        }
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Get invitation list sent by user.
     *
     * @author Julio Montoya <gugli100@gmail.com>
     *
     * @param int $userId
     *
     * @return array
     */
    public static function get_list_invitation_sent_by_user_id($userId)
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return [];
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT user_receiver_id, send_date,title,content
                FROM '.$table.'
                WHERE
                    user_sender_id = '.$userId.' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[$row['user_receiver_id']] = $row;
        }

        return $list;
    }

    /**
     * Get count invitation sent by user.
     *
     * @author Julio Montoya <gugli100@gmail.com>
     *
     * @param int $userId
     *
     * @return int
     */
    public static function getCountInvitationSent($userId)
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return 0;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT count(user_receiver_id) count
                FROM '.$table.'
                WHERE
                    user_sender_id = '.$userId.' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        $res = Database::query($sql);
        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res, 'ASSOC');

            return (int) $row['count'];
        }

        return 0;
    }

    /**
     * Accepts invitation.
     *
     * @param int $user_send_id
     * @param int $user_receiver_id
     *
     * @return bool
     *
     * @author isaac flores paz
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function invitation_accepted($user_send_id, $user_receiver_id)
    {
        if (empty($user_send_id) || empty($user_receiver_id)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = "UPDATE $table
                SET msg_status = ".MESSAGE_STATUS_INVITATION_ACCEPTED."
                WHERE
                    user_sender_id = ".((int) $user_send_id)." AND
                    user_receiver_id=".((int) $user_receiver_id)." AND
                    msg_status = ".MESSAGE_STATUS_INVITATION_PENDING;
        Database::query($sql);

        return true;
    }

    /**
     * Denies invitation.
     *
     * @param int user sender id
     * @param int user receiver id
     *
     * @return bool
     *
     * @author isaac flores paz
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function invitation_denied($user_send_id, $user_receiver_id)
    {
        if (empty($user_send_id) || empty($user_receiver_id)) {
            return false;
        }
        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'DELETE FROM '.$table.'
                WHERE
                    user_sender_id =  '.((int) $user_send_id).' AND
                    user_receiver_id='.((int) $user_receiver_id).' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        Database::query($sql);

        return true;
    }

    /**
     * Get user's feeds.
     *
     * @param int $user  User ID
     * @param int $limit Limit of posts per feed
     *
     * @return string HTML section with all feeds included
     *
     * @author  Yannick Warnier
     *
     * @since   Dokeos 1.8.6.1
     */
    public static function getUserRssFeed($user, $limit = 5)
    {
        $feed = UserManager::get_extra_user_data_by_field($user, 'rssfeeds');

        if (empty($feed)) {
            return '';
        }
        $feeds = explode(';', $feed['rssfeeds']);
        if (0 == count($feeds)) {
            return '';
        }
        $res = '';
        foreach ($feeds as $url) {
            if (empty($url)) {
                continue;
            }
            try {
                $channel = Reader::import($url);
                $i = 1;
                if (!empty($channel)) {
                    $iconRss = '';
                    if (!empty($feed)) {
                        $iconRss = Display::url(
                            Display::return_icon('social_rss.png', '', [], 22),
                            Security::remove_XSS($feed['rssfeeds']),
                            ['target' => '_blank']
                        );
                    }

                    $res .= '<h3 class="title-rss">'.$iconRss.' '.$channel->getTitle().'</h3>';
                    $res .= '<div class="rss-items">';
                    /** @var Rss $item */
                    foreach ($channel as $item) {
                        if ($limit >= 0 and $i > $limit) {
                            break;
                        }
                        $res .= '<h4 class="rss-title"><a href="'.$item->getLink().'">'.$item->getTitle().'</a></h4>';
                        $res .= '<div class="rss-date">'.api_get_local_time($item->getDateCreated()).'</div>';
                        $res .= '<div class="rss-content"><p>'.$item->getDescription().'</p></div>';
                        $i++;
                    }
                    $res .= '</div>';
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        return $res;
    }

    /**
     * Shows the avatar block in social pages.
     *
     * @param string $show     highlight link possible values:
     *                         group_add,
     *                         home,
     *                         messages,
     *                         messages_inbox,
     *                         messages_compose,
     *                         messages_outbox,
     *                         invitations,
     *                         shared_profile,
     *                         friends,
     *                         groups search
     * @param int    $group_id
     * @param int    $user_id
     */
    public static function show_social_avatar_block($show = '', $group_id = 0, $user_id = 0)
    {
        $user_id = (int) $user_id;
        $group_id = (int) $group_id;

        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }

        $show_groups = [
            'groups',
            'group_messages',
            'messages_list',
            'group_add',
            'mygroups',
            'group_edit',
            'member_list',
            'invite_friends',
            'waiting_list',
            'browse_groups',
        ];

        $template = new Template(null, false, false, false, false, false);

        if (in_array($show, $show_groups) && !empty($group_id)) {
            // Group image
            $userGroup = new UserGroupModel();
            $group_info = $userGroup->get($group_id);
            $userGroupImage = $userGroup->get_picture_group(
                $group_id,
                $group_info['picture'],
                128,
                GROUP_IMAGE_SIZE_BIG
            );
            $template->assign('show_group', true);
            $template->assign('group_id', $group_id);
            $template->assign('user_group_image', $userGroupImage);
            $template->assign(
                'user_is_group_admin',
                $userGroup->is_group_admin(
                    $group_id,
                    api_get_user_id()
                )
            );
        } else {
            $template->assign('show_group', false);
            $template->assign('show_user', true);
            $template->assign(
                'user_image',
                [
                    'big' => UserManager::getUserPicture(
                        $user_id,
                        USER_IMAGE_SIZE_BIG
                    ),
                    'normal' => UserManager::getUserPicture(
                        $user_id,
                        USER_IMAGE_SIZE_MEDIUM
                    ),
                ]
            );
        }

        return $template->fetch($template->get_template('social/avatar_block.tpl'));
    }

    /**
     * Displays a sortable table with the list of online users.
     *
     * @param array $user_list The list of users to be shown
     * @param bool  $wrap      Whether we want the function to wrap the spans list in a div or not
     *
     * @return string HTML block or null if and ID was defined
     * @assert (null) === false
     */
    public static function display_user_list($user_list, $wrap = true)
    {
        $html = '';

        if (isset($_GET['id']) || count($user_list) < 1) {
            return false;
        }

        $course_url = '';
        if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
            $course_url = '&amp;cidReq='.Security::remove_XSS($_GET['cidReq']);
        }

        $hide = api_get_configuration_value('hide_complete_name_in_whoisonline');
        foreach ($user_list as $uid) {
            $user_info = api_get_user_info($uid, true);
            $lastname = $user_info['lastname'];
            $firstname = $user_info['firstname'];
            $completeName = $firstname.', '.$lastname;
            $user_rol = 1 == $user_info['status'] ? Display::return_icon('teacher.png', get_lang('Trainer'), null, ICON_SIZE_TINY) : Display::return_icon('user.png', get_lang('Learner'), null, ICON_SIZE_TINY);
            $status_icon_chat = null;
            if (isset($user_info['user_is_online_in_chat']) && 1 == $user_info['user_is_online_in_chat']) {
                $status_icon_chat = Display::return_icon('online.png', get_lang('Online'));
            } else {
                $status_icon_chat = Display::return_icon('offline.png', get_lang('Offline'));
            }

            $userPicture = $user_info['avatar'];
            $officialCode = '';
            if ('true' === api_get_setting('show_official_code_whoisonline')) {
                $officialCode .= '<div class="items-user-official-code">
                    <p style="min-height: 30px;" title="'.get_lang('Code').'">'.$user_info['official_code'].'</p></div>';
            }

            if (true === $hide) {
                $completeName = '';
                $firstname = '';
                $lastname = '';
            }

            $img = '<img class="img-responsive img-circle" title="'.$completeName.'" alt="'.$completeName.'" src="'.$userPicture.'">';

            $url = null;
            // Anonymous users can't have access to the profile
            if (!api_is_anonymous()) {
                if ('true' === api_get_setting('allow_social_tool')) {
                    $url = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$uid.$course_url;
                } else {
                    $url = '?id='.$uid.$course_url;
                }
            } else {
                $url = null;
            }
            $name = '<a href="'.$url.'">'.$firstname.'<br>'.$lastname.'</a>';

            $html .= '<div class="col-xs-6 col-md-2">
                        <div class="items-user">
                            <div class="items-user-avatar"><a href="'.$url.'">'.$img.'</a></div>
                            <div class="items-user-name">
                            '.$name.'
                            </div>
                            '.$officialCode.'
                            <div class="items-user-status">'.$status_icon_chat.' '.$user_rol.'</div>
                        </div>
                      </div>';
        }

        return $html;
    }

    /**
     * @param string $content
     * @param string $span_count
     *
     * @return string
     */
    public static function social_wrapper_div($content, $span_count)
    {
        $span_count = (int) $span_count;
        $html = '<div class="span'.$span_count.'">';
        $html .= '<div class="well_border">';
        $html .= $content;
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Dummy function.
     */
    public static function get_plugins($place = SOCIAL_CENTER_PLUGIN)
    {
        $content = '';
        switch ($place) {
            case SOCIAL_CENTER_PLUGIN:
                $social_plugins = [1, 2];
                if (is_array($social_plugins) && count($social_plugins) > 0) {
                    $content .= '<div id="social-plugins">';
                    foreach ($social_plugins as $plugin) {
                        $content .= '<div class="social-plugin-item">';
                        $content .= $plugin;
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                }
                break;
            case SOCIAL_LEFT_PLUGIN:
                break;
            case SOCIAL_RIGHT_PLUGIN:
                break;
        }

        return $content;
    }

    /**
     * Gets all messages from someone's wall (within specific limits).
     *
     * @param int        $userId     id of wall shown
     * @param int|string $parentId   id message (Post main)
     * @param int|array  $groupId
     * @param int|array  $friendId
     * @param string     $startDate  Date from which we want to show the messages, in UTC time
     * @param int        $start      Limit for the number of parent messages we want to show
     * @param int        $length     Wall message query offset
     * @param bool       $getCount
     * @param array      $threadList
     *
     * @return array|int
     *
     * @author Yannick Warnier
     */
    public static function getWallMessages(
        $userId,
        $parentId = 0,
        $groupId = 0,
        $friendId = 0,
        $startDate = '',
        $start = 0,
        $length = 10,
        $getCount = false,
        $threadList = []
    ) {
        $tblMessage = Database::get_main_table(TABLE_MESSAGE);

        $parentId = (int) $parentId;
        $userId = (int) $userId;
        $start = (int) $start;
        $length = (int) $length;

        $select = " SELECT
                    id,
                    user_sender_id,
                    user_receiver_id,
                    send_date,
                    content,
                    parent_id,
                    msg_status,
                    group_id,
                    '' as forum_id,
                    '' as thread_id,
                    '' as c_id
                  ";

        if ($getCount) {
            $select = ' SELECT count(id) as count_items ';
        }

        $sqlBase = "$select FROM $tblMessage m WHERE ";
        $sql = [];
        $sql[1] = $sqlBase."msg_status <> ".MESSAGE_STATUS_WALL_DELETE.' AND ';

        // Get my own posts
        $userReceiverCondition = ' (
            user_receiver_id = '.$userId.' AND
            msg_status IN ('.MESSAGE_STATUS_WALL_POST.', '.MESSAGE_STATUS_WALL.') AND
            parent_id = '.$parentId.'
        )';

        $sql[1] .= $userReceiverCondition;

        $sql[2] = $sqlBase.' msg_status = '.MESSAGE_STATUS_PROMOTED.' ';

        // Get my group posts
        $groupCondition = '';
        if (!empty($groupId)) {
            if (is_array($groupId)) {
                $groupId = array_map('intval', $groupId);
                $groupId = implode(",", $groupId);
                $groupCondition = " ( group_id IN ($groupId) ";
            } else {
                $groupId = (int) $groupId;
                $groupCondition = " ( group_id = $groupId ";
            }
            $groupCondition .= ' AND (msg_type = '.Message::MESSAGE_TYPE_GROUP.') ';
        }
        if (!empty($groupCondition)) {
            $sql[3] = $sqlBase.$groupCondition;
        }

        // Get my friend posts
        $friendCondition = '';
        if (!empty($friendId)) {
            if (is_array($friendId)) {
                $friendId = array_map('intval', $friendId);
                $friendId = implode(",", $friendId);
                $friendCondition = " ( user_receiver_id IN ($friendId) ";
            } else {
                $friendId = (int) $friendId;
                $friendCondition = " ( user_receiver_id = $friendId ";
            }
            $friendCondition .= ' AND msg_status = '.MESSAGE_STATUS_WALL_POST.' AND parent_id = 0) ';
        }
        if (!empty($friendCondition)) {
            $sql[4] = $sqlBase.$friendCondition;
        }

        if (!empty($threadList)) {
            if ($getCount) {
                $select = ' SELECT count(iid) count_items ';
            } else {
                $select = " SELECT
                                iid as id,
                                poster_id as user_sender_id,
                                '' as user_receiver_id,
                                post_date as send_date,
                                post_text as content,
                                '' as parent_id,
                                ".MESSAGE_STATUS_FORUM." as msg_status,
                                '' as group_id,
                                forum_id,
                                thread_id,
                                c_id
                            ";
            }

            $threadList = array_map('intval', $threadList);
            $threadList = implode("','", $threadList);
            $condition = " thread_id IN ('$threadList') ";
            $sql[5] = "$select
                    FROM c_forum_post
                    WHERE $condition
                ";
        }

        if ($getCount) {
            $count = 0;
            foreach ($sql as $oneQuery) {
                if (!empty($oneQuery)) {
                    $res = Database::query($oneQuery);
                    $row = Database::fetch_array($res);
                    $count += (int) $row['count_items'];
                }
            }

            return $count;
        }

        $sqlOrder = ' ORDER BY send_date DESC ';
        $sqlLimit = " LIMIT $start, $length ";
        $messages = [];
        foreach ($sql as $index => $oneQuery) {
            if (5 === $index) {
                // Exception only for the forum query above (field name change)
                $oneQuery .= ' ORDER BY post_date DESC '.$sqlLimit;
            } else {
                $oneQuery .= $sqlOrder.$sqlLimit;
            }
            $res = Database::query($oneQuery);
            $em = Database::getManager();
            if (Database::num_rows($res) > 0) {
                $repo = $em->getRepository(CForumPost::class);
                $repoThread = $em->getRepository(CForumThread::class);
                $groups = [];
                $userGroup = new UserGroupModel();
                $urlGroup = api_get_path(WEB_CODE_PATH).'social/group_view.php?id=';
                while ($row = Database::fetch_array($res, 'ASSOC')) {
                    $row['group_info'] = [];
                    if (!empty($row['group_id'])) {
                        if (!in_array($row['group_id'], $groups)) {
                            $group = $userGroup->get($row['group_id']);
                            $group['url'] = $urlGroup.$group['id'];
                            $groups[$row['group_id']] = $group;
                            $row['group_info'] = $group;
                        } else {
                            $row['group_info'] = $groups[$row['group_id']];
                        }
                    }

                    // Forums
                    $row['post_title'] = '';
                    $row['forum_title'] = '';
                    $row['thread_url'] = '';
                    if (MESSAGE_STATUS_FORUM === (int) $row['msg_status']) {
                        // @todo use repositories to get post and threads.
                        /** @var CForumPost $post */
                        $post = $repo->find($row['id']);
                        /** @var CForumThread $thread */
                        $thread = $repoThread->find($row['thread_id']);
                        if ($post && $thread) {
                            //$courseInfo = api_get_course_info_by_id($post->getCId());
                            $row['post_title'] = $post->getForum()->getForumTitle();
                            $row['forum_title'] = $thread->getThreadTitle();
                            $row['thread_url'] = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.http_build_query([
                                    //'cid' => $courseInfo['real_id'],
                                    'forum' => $post->getForum()->getIid(),
                                    'thread' => $post->getThread()->getIid(),
                                    'post_id' => $post->getIid(),
                                ]).'#post_id_'.$post->getIid();
                        }
                    }

                    $messages[$row['id']] = $row;
                }
            }
        }
        // Reordering messages by ID (reverse order) is enough to have the
        // latest first, as there is currently no option to edit messages
        // afterwards
        krsort($messages);

        return $messages;
    }

    /**
     * @return array
     */
    public static function getAttachmentPreviewList(Message $message)
    {
        $list = [];
        //if (empty($message['group_id'])) {
        $files = $message->getAttachments();
        if ($files) {
            $repo = Container::getMessageAttachmentRepository();
            /** @var MessageAttachment $file */
            foreach ($files as $file) {
                $url = $repo->getResourceFileUrl($file);
                $display = Display::fileHtmlGuesser($file->getFilename(), $url);
                $list[] = $display;
            }
        }
        /*} else {
            $list = MessageManager::getAttachmentLinkList($messageId, 0);
        }*/

        return $list;
    }

    /**
     * @param array $message
     *
     * @return string
     */
    public static function getPostAttachment($message)
    {
        $previews = self::getAttachmentPreviewList($message);

        if (empty($previews)) {
            return '';
        }

        return implode('', $previews);
    }

    /**
     * @param array $messages
     *
     * @return array
     */
    public static function formatWallMessages($messages)
    {
        $data = [];
        $users = [];
        foreach ($messages as $key => $message) {
            $userIdLoop = $message['user_sender_id'];
            $userFriendIdLoop = $message['user_receiver_id'];
            if (!isset($users[$userIdLoop])) {
                $users[$userIdLoop] = api_get_user_info($userIdLoop);
            }

            if (!isset($users[$userFriendIdLoop])) {
                $users[$userFriendIdLoop] = api_get_user_info($userFriendIdLoop);
            }

            $html = self::headerMessagePost(
                $users[$userIdLoop],
                $users[$userFriendIdLoop],
                $message
            );

            $data[$key] = $message;
            $data[$key]['html'] = $html;
        }

        return $data;
    }

    /**
     * get html data with OpenGrap passing the URL.
     *
     * @param $link url
     *
     * @return string data html
     */
    public static function readContentWithOpenGraph($link)
    {
        if (false === strpos($link, "://") && "/" != substr($link, 0, 1)) {
            $link = "http://".$link;
        }
        $graph = OpenGraph::fetch($link);
        $link = parse_url($link);
        $host = $link['host'] ? strtoupper($link['host']) : $link['path'];
        if (!$graph) {
            return false;
        }
        $url = $graph->url;
        $image = $graph->image;
        $description = $graph->description;
        $title = $graph->title;
        $html = '<div class="thumbnail social-thumbnail">';
        $html .= empty($image) ? '' : '<a target="_blank" href="'.$url.'">
                <img class="img-responsive social-image" src="'.$image.'" /></a>';
        $html .= '<div class="social-description">';
        $html .= '<a target="_blank" href="'.$url.'"><h5 class="social-title"><b>'.$title.'</b></h5></a>';
        $html .= empty($description) ? '' : '<span>'.$description.'</span>';
        $html .= empty($host) ? '' : '<p>'.$host.'</p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * verify if Url Exist - Using Curl.
     *
     * @param $uri url
     *
     * @return bool
     */
    public static function verifyUrl($uri)
    {
        $curl = curl_init($uri);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $response = curl_exec($curl);
        curl_close($curl);
        if (!empty($response)) {
            return true;
        }

        return false;
    }

    /**
     * Generate the social block for a user.
     *
     * @param int    $userId            The user id
     * @param string $groupBlock        Optional. Highlight link possible values:
     *                                  group_add, home, messages, messages_inbox, messages_compose,
     *                                  messages_outbox, invitations, shared_profile, friends, groups, search
     * @param int    $groupId           Optional. Group ID
     * @param bool   $show_full_profile
     *
     * @return string The HTML code with the social block
     */
    public static function setSocialUserBlock(
        Template $template,
        $userId,
        $groupBlock = '',
        $groupId = 0,
        $show_full_profile = true
    ) {
        if ('true' !== api_get_setting('allow_social_tool')) {
            return '';
        }

        $currentUserId = api_get_user_id();
        $userId = (int) $userId;
        $userRelationType = 0;

        $socialAvatarBlock = self::show_social_avatar_block(
            $groupBlock,
            $groupId,
            $userId
        );

        $profileEditionLink = null;
        if ($currentUserId === $userId) {
            $profileEditionLink = Display::getProfileEditionLink($userId);
        } else {
            $userRelationType = self::get_relation_between_contacts($currentUserId, $userId);
        }

        $options = api_get_configuration_value('profile_fields_visibility');
        if (isset($options['options'])) {
            $options = $options['options'];
        }

        $vCardUserLink = Display::getVCardUserLink($userId);
        if (isset($options['vcard']) && false === $options['vcard']) {
            $vCardUserLink = '';
        }

        $userInfo = api_get_user_info($userId, true, false, true, true);

        if (isset($options['firstname']) && false === $options['firstname']) {
            $userInfo['firstname'] = '';
        }
        if (isset($options['lastname']) && false === $options['lastname']) {
            $userInfo['lastname'] = '';
        }

        if (isset($options['email']) && false === $options['email']) {
            $userInfo['email'] = '';
        }

        // Ofaj
        $hasCertificates = Certificate::getCertificateByUser($userId);
        $userInfo['has_certificates'] = 0;
        if (!empty($hasCertificates)) {
            $userInfo['has_certificates'] = 1;
        }

        $userInfo['is_admin'] = UserManager::is_admin($userId);
        $languageId = api_get_language_from_iso($userInfo['language']);
        $languageInfo = api_get_language_info($languageId);
        if ($languageInfo) {
            $userInfo['language'] = [
                'label' => $languageInfo['original_name'],
                'value' => $languageInfo['english_name'],
                'code' => $languageInfo['isocode'],
            ];
        }

        if (isset($options['language']) && false === $options['language']) {
            $userInfo['language'] = '';
        }

        if (isset($options['photo']) && false === $options['photo']) {
            $socialAvatarBlock = '';
        }

        $extraFieldBlock = self::getExtraFieldBlock($userId, true);
        $showLanguageFlag = api_get_configuration_value('social_show_language_flag_in_profile');

        $template->assign('user', $userInfo);
        $template->assign('show_language_flag', $showLanguageFlag);
        $template->assign('extra_info', $extraFieldBlock);
        $template->assign('social_avatar_block', $socialAvatarBlock);
        $template->assign('profile_edition_link', $profileEditionLink);
        //Added the link to export the vCard to the Template

        //If not friend $show_full_profile is False and the user can't see Email Address and Vcard Download Link
        if ($show_full_profile) {
            $template->assign('vcard_user_link', $vCardUserLink);
        }

        if ('1' === api_get_setting('gamification_mode')) {
            $gamificationPoints = GamificationUtils::getTotalUserPoints(
                $userId,
                $userInfo['status']
            );

            $template->assign('gamification_points', $gamificationPoints);
        }
        $chatEnabled = api_is_global_chat_enabled();

        if (isset($options['chat']) && false === $options['chat']) {
            $chatEnabled = '';
        }

        $template->assign('chat_enabled', $chatEnabled);
        $template->assign('user_relation', $userRelationType);
        $template->assign('user_relation_type_friend', UserRelUser::USER_RELATION_TYPE_FRIEND);
        $template->assign('show_full_profile', $show_full_profile);

        $templateName = $template->get_template('social/user_block.tpl');

        if (in_array($groupBlock, ['groups', 'group_edit', 'member_list'])) {
            $templateName = $template->get_template('social/group_block.tpl');
        }

        $template->assign('social_avatar_block', $template->fetch($templateName));
    }

    /**
     * @param int $user_id
     * @param $link_shared
     * @param bool $showLinkToChat
     *
     * @return string
     */
    public static function listMyFriendsBlock($user_id, $link_shared = '', $showLinkToChat = false)
    {
        //SOCIALGOODFRIEND , USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_PARENT
        $friends = self::get_friends($user_id, UserRelUser::USER_RELATION_TYPE_FRIEND);
        $numberFriends = count($friends);
        $friendHtml = '';

        if (!empty($numberFriends)) {
            $friendHtml .= '<div class="list-group contact-list">';
            $j = 1;

            usort(
                $friends,
                function ($a, $b) {
                    return strcmp($b['user_info']['user_is_online_in_chat'], $a['user_info']['user_is_online_in_chat']);
                }
            );

            foreach ($friends as $friend) {
                if ($j > $numberFriends) {
                    break;
                }
                $name_user = api_get_person_name($friend['firstName'], $friend['lastName']);
                $user_info_friend = api_get_user_info($friend['friend_user_id'], true);

                $statusIcon = Display::return_icon('statusoffline.png', get_lang('Offline'));
                $status = 0;
                if (!empty($user_info_friend['user_is_online_in_chat'])) {
                    $statusIcon = Display::return_icon('statusonline.png', get_lang('Online'));
                    $status = 1;
                }

                $friendAvatarMedium = UserManager::getUserPicture(
                    $friend['friend_user_id'],
                    USER_IMAGE_SIZE_MEDIUM
                );
                $friendAvatarSmall = UserManager::getUserPicture(
                    $friend['friend_user_id'],
                    USER_IMAGE_SIZE_SMALL
                );
                $friend_avatar = '<img src="'.$friendAvatarMedium.'" id="imgfriend_'.$friend['friend_user_id'].'" title="'.$name_user.'" class="user-image"/>';

                $relation = self::get_relation_between_contacts(
                    $friend['friend_user_id'],
                    api_get_user_id()
                );

                if ($showLinkToChat) {
                    $friendHtml .= '<a onclick="javascript:chatWith(\''.$friend['friend_user_id'].'\', \''.$name_user.'\', \''.$status.'\',\''.$friendAvatarSmall.'\')" href="javascript:void(0);" class="list-group-item">';
                    $friendHtml .= $friend_avatar.' <span class="username">'.$name_user.'</span>';
                    $friendHtml .= '<span class="status">'.$statusIcon.'</span>';
                } else {
                    $link_shared = empty($link_shared) ? '' : '&'.$link_shared;
                    $friendHtml .= '<a href="profile.php?'.'u='.$friend['friend_user_id'].$link_shared.'" class="list-group-item">';
                    $friendHtml .= $friend_avatar.' <span class="username">'.$name_user.'</span>';
                    $friendHtml .= '<span class="status">'.$statusIcon.'</span>';
                }

                $friendHtml .= '</a>';

                $j++;
            }
            $friendHtml .= '</div>';
        } else {
            $friendHtml = Display::return_message(get_lang('No friends in your contact list'), 'warning');
        }

        return $friendHtml;
    }

    /**
     * @return string Get the JS code necessary for social wall to load open graph from URLs.
     */
    public static function getScriptToGetOpenGraph()
    {
        return '<script>
            $(function() {
                $("[name=\'social_wall_new_msg_main\']").on("paste", function(e) {
                    $.ajax({
                        contentType: "application/x-www-form-urlencoded",
                        beforeSend: function() {
                            $("[name=\'wall_post_button\']").prop( "disabled", true );
                            $(".panel-preview").hide();
                            $(".spinner").html("'
                                .'<div class=\'text-center\'>'
                                .'<em class=\'fa fa-spinner fa-pulse fa-1x\'></em>'
                                .'<p>'.get_lang('Loading').' '.get_lang('Preview').'</p>'
                                .'</div>'
                            .'");
                        },
                        type: "POST",
                        url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=read_url_with_open_graph",
                        data: "social_wall_new_msg_main=" + e.originalEvent.clipboardData.getData("text"),
                        success: function(response) {
                            $("[name=\'wall_post_button\']").prop("disabled", false);
                            if (!response == false) {
                                $(".spinner").html("");
                                $(".panel-preview").show();
                                $(".url_preview").html(response);
                                $("[name=\'url_content\']").val(response);
                                $(".url_preview img").addClass("img-responsive");
                            } else {
                                $(".spinner").html("");
                            }
                        }
                    });
                });
            });
        </script>';
    }

    /**
     * @param string $urlForm
     *
     * @return string
     */
    public static function getWallForm($urlForm)
    {
        $userId = isset($_GET['u']) ? '?u='.intval($_GET['u']) : '';
        $form = new FormValidator(
            'social_wall_main',
            'post',
            $urlForm.$userId,
            null,
            ['enctype' => 'multipart/form-data'],
            FormValidator::LAYOUT_HORIZONTAL
        );

        $socialWallPlaceholder = isset($_GET['u']) ? get_lang('Write something on your friend\'s wall') : get_lang(
            'Social wallWhatAreYouThinkingAbout'
        );

        $form->addTextarea(
            'social_wall_new_msg_main',
            null,
            [
                'placeholder' => $socialWallPlaceholder,
                'cols-size' => [1, 12, 1],
                'aria-label' => $socialWallPlaceholder,
            ]
        );
        $form->addHtml('<div class="form-group">');
        $form->addHtml('<div class="col-sm-6">');
        $form->addFile('picture', get_lang('File upload'), ['custom' => true]);
        $form->addHtml('</div>');
        $form->addHtml('<div class="col-sm-6 "><div class="pull-right">');
        $form->addButtonSend(
            get_lang('Post'),
            'wall_post_button',
            false,
            [
                'cols-size' => [1, 10, 1],
                'custom' => true,
            ]
        );
        $form->addHtml('</div></div>');
        $form->addHtml('</div>');
        $form->addHidden('url_content', '');
        $html = Display::panel($form->returnForm(), get_lang('Social wall'));

        return $html;
    }

    /**
     * @param string $message
     * @param string $content
     *
     * @return string
     */
    public static function wrapPost($message, $content)
    {
        $class = '';
        if (MESSAGE_STATUS_PROMOTED === (int) $message['msg_status']) {
            $class = 'promoted_post';
        }

        return Display::panel($content, '',
            '',
            'default',
            '',
            'post_'.$message['id'],
            null,
            $class
        );
    }

    /**
     * Get HTML code block for user skills.
     *
     * @param int    $userId      The user ID
     * @param string $orientation
     *
     * @return string
     */
    public static function getSkillBlock($userId, $orientation = 'horizontal')
    {
        if (false === SkillModel::isAllowed($userId, false)) {
            return '';
        }

        $skill = new SkillModel();
        $ranking = $skill->getUserSkillRanking($userId);

        $template = new Template(null, false, false, false, false, false);
        $template->assign('ranking', $ranking);
        $template->assign('orientation', $orientation);
        $template->assign('skills', $skill->getUserSkillsTable($userId, 0, 0, false)['skills']);
        $template->assign('user_id', $userId);
        $template->assign('show_skills_report_link', api_is_student() || api_is_student_boss() || api_is_drh());

        $skillBlock = $template->get_template('social/skills_block.tpl');

        return $template->fetch($skillBlock);
    }

    /**
     * @param int  $user_id
     * @param bool $isArray
     *
     * @return string|array
     */
    public static function getExtraFieldBlock($user_id, $isArray = false)
    {
        $fieldVisibility = api_get_configuration_value('profile_fields_visibility');
        $fieldVisibilityKeys = [];
        if (isset($fieldVisibility['options'])) {
            $fieldVisibility = $fieldVisibility['options'];
            $fieldVisibilityKeys = array_keys($fieldVisibility);
        }

        $t_ufo = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $extra_user_data = UserManager::get_extra_user_data($user_id);

        $extra_information = '';
        if (is_array($extra_user_data) && count($extra_user_data) > 0) {
            $extra_information_value = '';
            $extraField = new ExtraField('user');
            $listType = [];
            $extraFieldItem = [];
            foreach ($extra_user_data as $key => $data) {
                if (empty($data)) {
                    continue;
                }
                if (in_array($key, $fieldVisibilityKeys) && false === $fieldVisibility[$key]) {
                    continue;
                }

                // Avoiding parameters
                if (in_array(
                    $key,
                    [
                        'mail_notify_invitation',
                        'mail_notify_message',
                        'mail_notify_group_message',
                    ]
                )) {
                    continue;
                }
                // get display text, visibility and type from user_field table
                $field_variable = str_replace('extra_', '', $key);

                $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(
                    $field_variable
                );

                if (in_array($extraFieldInfo['variable'], ['skype', 'linkedin_url'])) {
                    continue;
                }

                // if is not visible skip
                if (1 != $extraFieldInfo['visible_to_self']) {
                    continue;
                }

                // if is not visible to others skip also
                if (1 != $extraFieldInfo['visible_to_others']) {
                    continue;
                }

                if (is_array($data)) {
                    switch ($extraFieldInfo['field_type']) {
                        case ExtraField::FIELD_TYPE_RADIO:
                            $objEfOption = new ExtraFieldOption('user');
                            $value = $data['extra_'.$extraFieldInfo['variable']];
                            $optionInfo = $objEfOption->get_field_option_by_field_and_option(
                                $extraFieldInfo['id'],
                                $value
                            );

                            if ($optionInfo && isset($optionInfo[0])) {
                                $optionInfo = $optionInfo[0];
                                $extraFieldItem = [
                                    'variable' => $extraFieldInfo['variable'],
                                    'label' => ucfirst($extraFieldInfo['display_text']),
                                    'value' => $optionInfo['display_text'],
                                ];
                            } else {
                                $extraFieldItem = [
                                    'variable' => $extraFieldInfo['variable'],
                                    'label' => ucfirst($extraFieldInfo['display_text']),
                                    'value' => implode(',', $data),
                                ];
                            }
                            break;
                        default:
                            $extra_information_value .=
                                '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).' '
                                .' '.implode(',', $data).'</li>';
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => implode(',', $data),
                            ];
                            break;
                    }
                } else {
                    switch ($extraFieldInfo['field_type']) {
                        case ExtraField::FIELD_TYPE_RADIO:
                            $objEfOption = new ExtraFieldOption('user');
                            $optionInfo = $objEfOption->get_field_option_by_field_and_option($extraFieldInfo['id'], $extraFieldInfo['value']);
                            break;
                        case ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                        case ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                            $data = explode('::', $data);
                            $data = $data[0];
                            $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).': '.$data.'</li>';
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => $data,
                            ];
                            break;
                        case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                            $id_options = explode('::', $data);
                            $value_options = [];
                            // get option display text from user_field_options table
                            foreach ($id_options as $id_option) {
                                $sql = "SELECT display_text
                                    FROM $t_ufo
                                    WHERE id = '$id_option'";
                                $res_options = Database::query($sql);
                                $row_options = Database::fetch_row($res_options);
                                $value_options[] = $row_options[0];
                            }
                            $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).': '
                                .' '.implode(' ', $value_options).'</li>';
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => $value_options,
                            ];
                            break;
                        case ExtraField::FIELD_TYPE_TAG:
                            $user_tags = UserManager::get_user_tags($user_id, $extraFieldInfo['id']);

                            $tag_tmp = '';
                            foreach ($user_tags as $tags) {
                                $tag_tmp .= '<a class="label label_tag"'
                                    .' href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tags['tag'].'">'
                                    .$tags['tag']
                                    .'</a>';
                            }
                            if (is_array($user_tags) && count($user_tags) > 0) {
                                $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).': '
                                    .' '.$tag_tmp.'</li>';
                            }
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => $tag_tmp,
                            ];
                            break;
                        case ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                            $icon_path = UserManager::get_favicon_from_url($data);
                            if (false == self::verifyUrl($icon_path)) {
                                break;
                            }
                            $bottom = '0.2';
                            //quick hack for hi5
                            $domain = parse_url($icon_path, PHP_URL_HOST);
                            if ('www.hi5.com' == $domain || 'hi5.com' == $domain) {
                                $bottom = '-0.8';
                            }
                            $data = '<a href="'.$data.'">'
                                .'<img src="'.$icon_path.'" alt="icon"'
                                .' style="margin-right:0.5em;margin-bottom:'.$bottom.'em;" />'
                                .$extraFieldInfo['display_text']
                                .'</a>';
                            $extra_information_value .= '<li class="list-group-item">'.$data.'</li>';
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => $data,
                            ];
                            break;
                        case ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                            $parsedData = explode('::', $data);

                            if (!$parsedData) {
                                break;
                            }

                            $objEfOption = new ExtraFieldOption('user');
                            $optionInfo = $objEfOption->get($parsedData[0]);

                            $extra_information_value .= '<li class="list-group-item">'
                                .$optionInfo['display_text'].': '
                                .$parsedData[1].'</li>';
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => $parsedData[1],
                            ];
                            break;
                        case ExtraField::FIELD_TYPE_TRIPLE_SELECT:
                            $optionIds = explode(';', $data);
                            $optionValues = [];

                            foreach ($optionIds as $optionId) {
                                $objEfOption = new ExtraFieldOption('user');
                                $optionInfo = $objEfOption->get($optionId);

                                $optionValues[] = $optionInfo['display_text'];
                            }
                            $extra_information_value .= '<li class="list-group-item">'
                                .ucfirst($extraFieldInfo['display_text']).': '
                                .implode(' ', $optionValues).'</li>';
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => implode(' ', $optionValues),
                            ];
                            break;
                        default:
                            // Ofaj
                            // Converts "Date of birth" into "age"
                            if ('terms_datedenaissance' === $key) {
                                $dataArray = date_to_str_ago($data, 'UTC', true);
                                $dataToString = isset($dataArray['years']) && !empty($dataArray['years']) ? $dataArray['years'] : 0;
                                if (!empty($dataToString)) {
                                    $data = $dataToString;
                                    $extraFieldInfo['display_text'] = get_lang('Age');
                                }
                            }

                            $extra_information_value .= '<li class="list-group-item">'.ucfirst($extraFieldInfo['display_text']).': '.$data.'</li>';
                            $extraFieldItem = [
                                'variable' => $extraFieldInfo['variable'],
                                'label' => ucfirst($extraFieldInfo['display_text']),
                                'value' => $data,
                            ];
                            break;
                    }
                }

                $listType[] = $extraFieldItem;
            }

            if ($isArray) {
                return $listType;
            } else {
                // if there are information to show
                if (!empty($extra_information_value)) {
                    $extra_information_value = '<ul class="list-group">'.$extra_information_value.'</ul>';
                    $extra_information .= Display::panelCollapse(
                        get_lang('Extra information'),
                        $extra_information_value,
                        'sn-extra-information',
                        null,
                        'sn-extra-accordion',
                        'sn-extra-collapse'
                    );
                }
            }
        }

        return $extra_information;
    }

    /**
     * @param int   $countPost
     * @param array $htmlHeadXtra
     */
    public static function getScrollJs($countPost, &$htmlHeadXtra)
    {
        // $ajax_url = api_get_path(WEB_AJAX_PATH).'message.ajax.php';
        $socialAjaxUrl = api_get_path(WEB_AJAX_PATH).'social.ajax.php';
        $javascriptDir = api_get_path(LIBRARY_PATH).'javascript/';
        $locale = api_get_language_isocode();

        // Add Jquery scroll pagination plugin
        //$htmlHeadXtra[] = api_get_js('jscroll/jquery.jscroll.js');
        // Add Jquery Time ago plugin
        //$htmlHeadXtra[] = api_get_asset('jquery-timeago/jquery.timeago.js');
        $timeAgoLocaleDir = $javascriptDir.'jquery-timeago/locales/jquery.timeago.'.$locale.'.js';
        if (file_exists($timeAgoLocaleDir)) {
            $htmlHeadXtra[] = api_get_js('jquery-timeago/locales/jquery.timeago.'.$locale.'.js');
        }

        if ($countPost > self::DEFAULT_WALL_POSTS) {
            $htmlHeadXtra[] = '<script>
            $(function() {
                var container = $("#wallMessages");
                container.jscroll({
                    loadingHtml: "<div class=\"well_border\">'.get_lang('Loading').' </div>",
                    nextSelector: "a.nextPage:last",
                    contentSelector: "",
                    callback: timeAgo
                });
            });
            </script>';
        }

        $htmlHeadXtra[] = '<script>
            function deleteMessage(id)
            {
                $.ajax({
                    url: "'.$socialAjaxUrl.'?a=delete_message" + "&id=" + id,
                    success: function (result) {
                        if (result) {
                            $("#message_" + id).parent().parent().parent().parent().html(result);
                        }
                    }
                });
            }

            function deleteComment(id)
            {
                $.ajax({
                    url: "'.$socialAjaxUrl.'?a=delete_message" + "&id=" + id,
                    success: function (result) {
                        if (result) {
                            $("#message_" + id).parent().parent().parent().html(result);
                        }
                    }
                });
            }

            function submitComment(messageId)
            {
                var data = $("#form_comment_"+messageId).serializeArray();
                $.ajax({
                    type : "POST",
                    url: "'.$socialAjaxUrl.'?a=send_comment" + "&id=" + messageId,
                    data: data,
                    success: function (result) {
                        if (result) {
                            $("#post_" + messageId + " textarea").val("");
                            $("#post_" + messageId + " .sub-mediapost").prepend(result);
                            $("#post_" + messageId + " .sub-mediapost").append(
                                $(\'<div id=result_\' + messageId +\'>'.addslashes(get_lang('Saved.')).'</div>\')
                            );

                            $("#result_" + messageId + "").fadeIn("fast", function() {
                                $("#result_" + messageId + "").delay(1000).fadeOut("fast", function() {
                                    $(this).remove();
                                });
                            });
                        }
                    }
                });
            }

            $(function() {
                timeAgo();

                /*$(".delete_message").on("click", function() {
                    var id = $(this).attr("id");
                    id = id.split("_")[1];
                    $.ajax({
                        url: "'.$socialAjaxUrl.'?a=delete_message" + "&id=" + id,
                        success: function (result) {
                            if (result) {
                                $("#message_" + id).parent().parent().parent().parent().html(result);
                            }
                        }
                    });
                });


                $(".delete_comment").on("click", function() {
                    var id = $(this).attr("id");
                    id = id.split("_")[1];
                    $.ajax({
                        url: "'.$socialAjaxUrl.'?a=delete_message" + "&id=" + id,
                        success: function (result) {
                            if (result) {
                                $("#message_" + id).parent().parent().parent().html(result);
                            }
                        }
                    });
                });
                */
            });

            function timeAgo() {
                $(".timeago").timeago();
            }
            </script>';
    }

    /**
     * @param int $userId
     * @param int $countPost
     *
     * @return string
     */
    public static function getAutoExtendLink($userId, $countPost)
    {
        $userId = (int) $userId;
        $socialAjaxUrl = api_get_path(WEB_AJAX_PATH).'social.ajax.php';
        $socialAutoExtendLink = '';
        if ($countPost > self::DEFAULT_WALL_POSTS) {
            $socialAutoExtendLink = Display::url(
                get_lang('See more'),
                $socialAjaxUrl.'?u='.$userId.'&a=list_wall_message&start='.
                self::DEFAULT_WALL_POSTS.'&length='.self::DEFAULT_SCROLL_NEW_POST,
                [
                    'class' => 'nextPage next',
                ]
            );
        }

        return $socialAutoExtendLink;
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public static function getThreadList($userId)
    {
        return [];
        $forumCourseId = api_get_configuration_value('global_forums_course_id');

        $threads = [];
        if (!empty($forumCourseId)) {
            $courseInfo = api_get_course_info_by_id($forumCourseId);
            /*getNotificationsPerUser($userId, true, $forumCourseId);
            $notification = Session::read('forum_notification');
            Session::erase('forum_notification');*/

            $threadUrlBase = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.http_build_query([
                'cid' => $courseInfo['real_id'],
            ]).'&';
            if (isset($notification['thread']) && !empty($notification['thread'])) {
                $threadList = array_filter(array_unique($notification['thread']));
                $repo = Container::getForumThreadRepository();
                foreach ($threadList as $threadId) {
                    /** @var CForumThread $thread */
                    $thread = $repo->find($threadId);
                    if ($thread) {
                        $threadUrl = $threadUrlBase.http_build_query([
                            'forum' => $thread->getForum()->getIid(),
                            'thread' => $thread->getIid(),
                        ]);
                        $threads[] = [
                            'id' => $threadId,
                            'url' => Display::url(
                                $thread->getThreadTitle(),
                                $threadUrl
                            ),
                            'name' => Display::url(
                                $thread->getThreadTitle(),
                                $threadUrl
                            ),
                            'description' => '',
                        ];
                    }
                }
            }
        }

        return $threads;
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public static function getGroupBlock($userId)
    {
        $threadList = self::getThreadList($userId);
        $userGroup = new UserGroupModel();

        $forumCourseId = api_get_configuration_value('global_forums_course_id');
        $courseInfo = null;
        if (!empty($forumCourseId)) {
            $courseInfo = api_get_course_info_by_id($forumCourseId);
        }

        $social_group_block = '';
        if (!empty($courseInfo)) {
            if (!empty($threadList)) {
                $social_group_block .= '<div class="list-group">';
                foreach ($threadList as $group) {
                    $social_group_block .= ' <li class="list-group-item">';
                    $social_group_block .= $group['name'];
                    $social_group_block .= '</li>';
                }
                $social_group_block .= '</div>';
            }

            $social_group_block .= Display::url(
                get_lang('See all communities'),
                api_get_path(WEB_CODE_PATH).'forum/index.php?cid='.$courseInfo['real_id']
            );

            if (!empty($social_group_block)) {
                $social_group_block = Display::panelCollapse(
                    get_lang('My communities'),
                    $social_group_block,
                    'sm-groups',
                    null,
                    'grups-acordion',
                    'groups-collapse'
                );
            }
        } else {
            // Load my groups
            $results = $userGroup->get_groups_by_user(
                $userId,
                [
                    GROUP_USER_PERMISSION_ADMIN,
                    GROUP_USER_PERMISSION_READER,
                    GROUP_USER_PERMISSION_MODERATOR,
                    GROUP_USER_PERMISSION_HRM,
                ]
            );

            $myGroups = [];
            if (!empty($results)) {
                foreach ($results as $result) {
                    $id = $result['id'];
                    $result['description'] = Security::remove_XSS($result['description'], STUDENT, true);
                    $result['name'] = Security::remove_XSS($result['name'], STUDENT, true);

                    $group_url = "group_view.php?id=$id";

                    $link = Display::url(
                        api_ucwords(cut($result['name'], 40, true)),
                        $group_url
                    );

                    $result['name'] = $link;

                    $picture = $userGroup->get_picture_group(
                        $id,
                        $result['picture'],
                        null,
                        GROUP_IMAGE_SIZE_BIG
                    );

                    $result['picture'] = '<img class="img-responsive" src="'.$picture.'" />';
                    $group_actions = '<div class="group-more"><a class="btn btn-default" href="groups.php?#tab_browse-2">'.
                        get_lang('See more').'</a></div>';
                    $group_info = '<div class="description"><p>'.cut($result['description'], 120, true)."</p></div>";
                    $myGroups[] = [
                        'url' => Display::url(
                            $result['picture'],
                            $group_url
                        ),
                        'name' => $result['name'],
                        'description' => $group_info.$group_actions,
                    ];
                }

                $social_group_block .= '<div class="list-group">';
                foreach ($myGroups as $group) {
                    $social_group_block .= ' <li class="list-group-item">';
                    $social_group_block .= $group['name'];
                    $social_group_block .= '</li>';
                }
                $social_group_block .= '</div>';

                $form = new FormValidator(
                    'find_groups_form',
                    'get',
                    api_get_path(WEB_CODE_PATH).'social/search.php?search_type=2',
                    null,
                    null,
                    FormValidator::LAYOUT_BOX_NO_LABEL
                );
                $form->addHidden('search_type', 2);

                $form->addText(
                    'q',
                    get_lang('Search'),
                    false,
                    [
                        'aria-label' => get_lang('Search'),
                        'custom' => true,
                        'placeholder' => get_lang('Search'),
                    ]
                );

                $social_group_block .= $form->returnForm();

                if (!empty($social_group_block)) {
                    $social_group_block = Display::panelCollapse(
                        get_lang('My groups'),
                        $social_group_block,
                        'sm-groups',
                        null,
                        'grups-acordion',
                        'groups-collapse'
                    );
                }
            }
        }

        return $social_group_block;
    }

    /**
     * @param string $selected
     *
     * @return string
     */
    public static function getHomeProfileTabs($selected = 'home')
    {
        $headers = [
            [
                'url' => api_get_path(WEB_CODE_PATH).'auth/profile.php',
                'content' => get_lang('Profile'),
            ],
        ];
        $allowJustification = 'true' === api_get_plugin_setting('justification', 'tool_enable');
        if ($allowJustification) {
            $plugin = Justification::create();
            $headers[] = [
                'url' => api_get_path(WEB_CODE_PATH).'auth/justification.php',
                'content' => $plugin->get_lang('Justification'),
            ];
        }

        $allowPauseTraining = 'true' === api_get_plugin_setting('pausetraining', 'tool_enable');
        $allowEdit = 'true' === api_get_plugin_setting('pausetraining', 'allow_users_to_edit_pause_formation');
        if ($allowPauseTraining && $allowEdit) {
            $plugin = PauseTraining::create();
            $headers[] = [
                'url' => api_get_path(WEB_CODE_PATH).'auth/pausetraining.php',
                'content' => $plugin->get_lang('PauseTraining'),
            ];
        }

        $selectedItem = 1;
        foreach ($headers as $header) {
            $info = pathinfo($header['url']);
            if ($selected === $info['filename']) {
                break;
            }
            $selectedItem++;
        }

        $tabs = '';
        if (count($headers) > 1) {
            $tabs = Display::tabsOnlyLink($headers, $selectedItem);
        }

        return $tabs;
    }

    /**
     * Returns the formatted header message post.
     *
     * @param int   $authorInfo
     * @param int   $receiverInfo
     * @param array $message      Message data
     *
     * @return string $html       The formatted header message post
     */
    private static function headerMessagePost($authorInfo, $receiverInfo, $message)
    {
        $currentUserId = api_get_user_id();
        $authorId = (int) $authorInfo['user_id'];
        $receiverId = (int) $receiverInfo['user_id'];
        $iconStatus = $authorInfo['icon_status'];

        $date = Display::dateToStringAgoAndLongDate($message['send_date']);
        $avatarAuthor = $authorInfo['avatar'];
        $urlAuthor = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$authorId;
        $nameCompleteAuthor = $authorInfo['complete_name'];

        $urlReceiver = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$receiverId;
        $nameCompleteReceiver = $receiverInfo['complete_name'];

        $htmlReceiver = '';
        if ($authorId !== $receiverId) {
            $htmlReceiver = ' > <a href="'.$urlReceiver.'">'.$nameCompleteReceiver.'</a> ';
        }

        if (!empty($message['group_info'])) {
            $htmlReceiver = ' > <a href="'.$message['group_info']['url'].'">'.$message['group_info']['name'].'</a> ';
        }
        $canEdit = ($currentUserId == $authorInfo['user_id'] || $currentUserId == $receiverInfo['user_id']) && empty($message['group_info']);

        if (!empty($message['thread_id'])) {
            $htmlReceiver = ' > <a href="'.$message['thread_url'].'">'.$message['forum_title'].'</a> ';
            $canEdit = false;
        }

        $postAttachment = self::getPostAttachment($message);

        $html = '<div class="top-mediapost" >';
        $html .= '<div class="pull-right btn-group btn-group-sm">';

        $html .= MessageManager::getLikesButton(
            $message['id'],
            $currentUserId,
            !empty($message['group_info']['id']) ? (int) $message['group_info']['id'] : 0
        );

        if ($canEdit) {
            $htmlDelete = Display::url(
                Display::returnFontAwesomeIcon('trash', '', true),
                'javascript:void(0)',
                [
                    'id' => 'message_'.$message['id'],
                    'title' => get_lang('Delete comment'),
                    'onclick' => 'deleteMessage('.$message['id'].')',
                    'class' => 'btn btn-default',
                ]
            );

            $html .= $htmlDelete;
        }
        $html .= '</div>';

        $html .= '<div class="user-image" >';
        $html .= '<a href="'.$urlAuthor.'">
                    <img class="avatar-thumb" src="'.$avatarAuthor.'" alt="'.$nameCompleteAuthor.'"></a>';
        $html .= '</div>';
        $html .= '<div class="user-data">';
        $html .= $iconStatus;
        $html .= '<div class="username"><a href="'.$urlAuthor.'">'.$nameCompleteAuthor.'</a>'.$htmlReceiver.'</div>';
        $html .= '<div class="post-date">'.$date.'</div>';
        $html .= '</div>';
        $html .= '<div class="msg-content">';
        if (!empty($postAttachment)) {
            $html .= '<div class="post-attachment thumbnail">';
            $html .= $postAttachment;
            $html .= '</div>';
        }
        $html .= '<div>'.Security::remove_XSS($message['content']).'</div>';
        $html .= '</div>';
        $html .= '</div>'; // end mediaPost

        // Popularity post functionality
        $html .= '<div class="popularity-mediapost"></div>';

        return $html;
    }
}
