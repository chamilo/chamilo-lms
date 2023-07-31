<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use ChamiloSession as Session;
use GuzzleHttp\Client;
use Zend\Feed\Reader\Entry\Rss;
use Zend\Feed\Reader\Reader;

/**
 * Class SocialManager.
 *
 * This class provides methods for the social network management.
 * Include/require it in your code to use its features.
 */
class SocialManager extends UserManager
{
    public const DEFAULT_WALL_POSTS = 10;
    public const DEFAULT_SCROLL_NEW_POST = 5;

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
        if ($count_list == 0) {
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
        if ($includeRH == false) {
            $sql = 'SELECT rt.id as id
                FROM '.$table.' rt
                WHERE rt.id = (
                    SELECT uf.relation_type
                    FROM '.$userRelUserTable.' uf
                    WHERE
                        user_id='.((int) $user_id).' AND
                        friend_user_id='.((int) $user_friend).' AND
                        uf.relation_type <> '.USER_RELATION_TYPE_RRHH.'
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
                        return USER_RELATION_TYPE_GOODFRIEND;
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
                            return USER_RELATION_TYPE_GOODFRIEND;
                        }
                    }
                }
            } else {
                return USER_UNKNOWN;
            }
        }
    }

    /**
     * Get count of friends from user.
     *
     * @param int $userId
     *
     * @return int
     */
    public static function getCountFriends($userId)
    {
        $table = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $userId = (int) $userId;
        if (empty($userId)) {
            return 0;
        }

        $sql = 'SELECT count(friend_user_id) count
                FROM '.$table.'
                WHERE
                    relation_type NOT IN ('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND
                    friend_user_id<>'.$userId.' AND
                    user_id='.$userId;
        $res = Database::query($sql);
        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res, 'ASSOC');

            return (int) $row['count'];
        }

        return 0;
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
                    relation_type NOT IN ('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND
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
     * get web path of user invitate.
     *
     * @author isaac flores paz
     * @author Julio Montoya setting variable array
     *
     * @param int user id
     *
     * @return array
     */
    public static function get_list_web_path_user_invitation_by_user_id($user_id)
    {
        $list_ids = self::get_list_invitation_of_friends_by_user_id($user_id);
        $list = [];
        foreach ($list_ids as $values_ids) {
            $list[] = UserManager::get_user_picture_path_by_id(
                $values_ids['user_sender_id'],
                'web'
            );
        }

        return $list;
    }

    /**
     * Sends an invitation to contacts.
     *
     * @param int user id
     * @param int user friend id
     * @param string title of the message
     * @param string content of the message
     *
     * @return bool
     *
     * @author isaac flores paz
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function send_invitation_friend(
        $user_id,
        $friend_id,
        $message_title,
        $message_content
    ) {
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $user_id = (int) $user_id;
        $friend_id = (int) $friend_id;

        //Just in case we replace the and \n and \n\r while saving in the DB
        $message_content = str_replace(["\n", "\n\r"], '<br />', $message_content);

        $clean_message_content = Database::escape_string($message_content);
        $now = api_get_utc_datetime();
        $sql = 'SELECT COUNT(*) AS count FROM '.$tbl_message.'
                WHERE
                    user_sender_id='.$user_id.' AND
                    user_receiver_id='.$friend_id.' AND
                    msg_status IN('.MESSAGE_STATUS_INVITATION_PENDING.', '.MESSAGE_STATUS_INVITATION_ACCEPTED.', '.MESSAGE_STATUS_INVITATION_DENIED.');
                ';
        $res_exist = Database::query($sql);
        $row_exist = Database::fetch_array($res_exist, 'ASSOC');

        if ($row_exist['count'] == 0) {
            $params = [
                'user_sender_id' => $user_id,
                'user_receiver_id' => $friend_id,
                'msg_status' => MESSAGE_STATUS_INVITATION_PENDING,
                'send_date' => $now,
                'title' => $message_title,
                'content' => $message_content,
                'group_id' => 0,
                'parent_id' => 0,
                'update_date' => $now,
            ];
            $messageId = Database::insert($tbl_message, $params);

            $senderInfo = api_get_user_info($user_id);
            $notification = new Notification();
            $notification->saveNotification(
                $messageId,
                Notification::NOTIFICATION_TYPE_INVITATION,
                [$friend_id],
                $message_title,
                $message_content,
                $senderInfo
            );

            return true;
        } else {
            // invitation already exist
            $sql = 'SELECT COUNT(*) AS count, id FROM '.$tbl_message.'
                    WHERE
                        user_sender_id='.$user_id.' AND
                        user_receiver_id='.$friend_id.' AND
                        msg_status = 7';
            $res_if_exist = Database::query($sql);
            $row_if_exist = Database::fetch_array($res_if_exist, 'ASSOC');
            if ($row_if_exist['count'] == 1) {
                $sql = 'UPDATE '.$tbl_message.' SET
                            msg_status = 5, content = "'.$clean_message_content.'"
                        WHERE
                            user_sender_id='.$user_id.' AND
                            user_receiver_id='.$friend_id.' AND
                            msg_status = 7 ';
                Database::query($sql);

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Get number messages of the inbox.
     *
     * @author isaac flores paz
     *
     * @param int $userId user receiver id
     *
     * @return int
     */
    public static function get_message_number_invitation_by_user_id($userId)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);
        $userId = (int) $userId;
        $sql = 'SELECT COUNT(*) as count_message_in_box FROM '.$table.'
                WHERE
                    user_receiver_id='.$userId.' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        $res = Database::query($sql);
        $row = Database::fetch_array($res, 'ASSOC');
        if ($row) {
            return (int) $row['count_message_in_box'];
        }

        return 0;
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
        if ($limit != null && $limit > 0) {
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
     * Sends invitations to friends.
     *
     * @param int    $userId
     * @param string $subject
     * @param string $content
     *
     * @return bool
     */
    public static function sendInvitationToUser($userId, $subject = '', $content = '')
    {
        $user_info = api_get_user_info($userId);
        $success = get_lang('MessageSentTo');
        $success .= ' : '.api_get_person_name($user_info['firstName'], $user_info['lastName']);
        $content = strip_tags($content);

        if (isset($subject) && isset($content) && isset($userId)) {
            $result = MessageManager::send_message($userId, $subject, $content);

            if ($result) {
                Display::addFlash(
                    Display::return_message($success, 'normal', false)
                );
            } else {
                Display::addFlash(
                    Display::return_message(get_lang('ErrorSendingMessage'), 'error', false)
                );
            }

            return false;
        } elseif (isset($userId) && !isset($subject)) {
            if (isset($userId) && $userId > 0) {
                $count = self::send_invitation_friend(
                    api_get_user_id(),
                    $userId,
                    get_lang('Invitation'),
                    $content
                );

                if ($count) {
                    Display::addFlash(
                        Display::return_message(
                            api_htmlentities(get_lang('InvitationHasBeenSent')),
                            'normal',
                            false
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(
                            api_htmlentities(get_lang('YouAlreadySentAnInvitation')),
                            'warning',
                            false
                        )
                    );
                }
            }
        }
    }

    /**
     * Helper functions definition.
     */
    public static function get_logged_user_course_html($my_course, $count)
    {
        $result = '';
        $count = (int) $count;

        // Table definitions
        $main_user_table = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $course_directory = $my_course['course_info']['directory'];
        $course_title = $my_course['course_info']['title'];
        $course_visibility = $my_course['course_info']['visibility'];

        $user_in_course_status = CourseManager::getUserInCourseStatus(
            api_get_user_id(),
            $my_course['course_info']['real_id']
        );

        $course_path = api_get_path(SYS_COURSE_PATH).$course_directory; // course path
        if (api_get_setting('course_images_in_courses_list') === 'true') {
            if (file_exists($course_path.'/course-pic85x85.png')) {
                $image = $my_course['course_info']['course_image'];
                $imageCourse = Display::img($image, $course_title, ['class' => 'img-course']);
            } else {
                $imageCourse = Display::return_icon(
                    'session_default_small.png',
                    $course_title,
                    ['class' => 'img-course']
                );
            }
        } else {
            $imageCourse = Display::return_icon(
                'course.png',
                get_lang('Course'),
                ['class' => 'img-default']
            );
        }

        //display course entry
        if (api_get_setting('course_images_in_courses_list') === 'true') {
            $result .= '<li id="course_'.$count.'" class="list-group-item" style="min-height:65px;">';
        } else {
            $result .= '<li id="course_'.$count.'" class="list-group-item" style="min-height:44px;">';
        }
        $result .= $imageCourse;

        //show a hyperlink to the course, unless the course is closed and user is not course admin
        if ($course_visibility != COURSE_VISIBILITY_HIDDEN &&
            ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER)
        ) {
            $result .= '<span class="title">'.$course_title.'<span>';
        } else {
            $result .= $course_title.' '.get_lang('CourseClosed');
        }

        $result .= '</li>';
        $session = '';
        if (!empty($my_course['session_name']) && !empty($my_course['id_session'])) {
            // Request for the name of the general coach
            $sql = 'SELECT lastname, firstname
                    FROM '.$tbl_session.' ts
                    LEFT JOIN '.$main_user_table.' tu
                    ON ts.id_coach = tu.user_id
                    WHERE ts.id='.(int) $my_course['id_session'].' LIMIT 1';
            $rs = Database::query($sql);
            $sessioncoach = Database::store_result($rs);
            $sessioncoach = $sessioncoach[0];

            $session = [];
            $session['title'] = $my_course['session_name'];
            if ($my_course['access_start_date'] == '0000-00-00') {
                $session['dates'] = get_lang('WithoutTimeLimits');
                if (api_get_setting('show_session_coach') === 'true') {
                    $session['coach'] = get_lang('GeneralCoach').': '.
                        api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                }
            } else {
                $session['dates'] = ' - '.get_lang('From').' '.$my_course['access_start_date'].' '.get_lang('To').' '.$my_course['access_end_date'];
                if (api_get_setting('show_session_coach') === 'true') {
                    $session['coach'] = get_lang('GeneralCoach').': '.
                        api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                }
            }
        }

        $my_course['id_session'] = isset($my_course['id_session']) ? $my_course['id_session'] : 0;
        $output = [
            $my_course['user_course_cat'],
            $result,
            $my_course['id_session'],
            $session,
        ];

        return $output;
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
            $userGroup = new UserGroup();
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
     * Shows the right menu of the Social Network tool.
     *
     * @param string $show                       highlight link possible values:
     *                                           group_add,
     *                                           home,
     *                                           messages,
     *                                           messages_inbox,
     *                                           messages_compose ,
     *                                           messages_outbox,
     *                                           invitations,
     *                                           shared_profile,
     *                                           friends,
     *                                           groups search
     * @param int    $group_id                   group id
     * @param int    $user_id                    user id
     * @param bool   $show_full_profile          show profile or not (show or hide the user image/information)
     * @param bool   $show_delete_account_button
     */
    public static function show_social_menu(
        $show = '',
        $group_id = 0,
        $user_id = 0,
        $show_full_profile = false,
        $show_delete_account_button = false
    ) {
        $user_id = (int) $user_id;
        $group_id = (int) $group_id;
        $settingExtendedProfileEnabled = api_get_setting('extended_profile');

        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }

        $myExtendedProfileEdit = '';
        if ($user_id == api_get_user_id()) {
            $myExtendedProfileEdit .= '<a href="/main/auth/profile.php?type=extended#openarea" style="display:initial">'.
                Display::return_icon('edit.png', get_lang('EditExtendProfile'), '', 16).'</a>';
        }
        $usergroup = new UserGroup();
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

        // get count unread message and total invitations
        $count_unread_message = MessageManager::getCountNewMessagesFromDB(api_get_user_id());
        $count_unread_message = !empty($count_unread_message) ? Display::badge($count_unread_message) : null;

        $number_of_new_messages_of_friend = self::get_message_number_invitation_by_user_id(api_get_user_id());
        $group_pending_invitations = $usergroup->get_groups_by_user(
            api_get_user_id(),
            GROUP_USER_PERMISSION_PENDING_INVITATION,
            false
        );
        $group_pending_invitations = count($group_pending_invitations);
        $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
        $total_invitations = (!empty($total_invitations) ? Display::badge($total_invitations) : '');

        $filesIcon = Display::return_icon('sn-files.png', get_lang('MyFiles'), null, ICON_SIZE_SMALL);
        $friendsIcon = Display::return_icon('sn-friends.png', get_lang('Friends'), null, ICON_SIZE_SMALL);
        $groupsIcon = Display::return_icon('sn-groups.png', get_lang('SocialGroups'), null, ICON_SIZE_SMALL);
        $homeIcon = Display::return_icon('sn-home.png', get_lang('Home'), null, ICON_SIZE_SMALL);
        $invitationsIcon = Display::return_icon('sn-invitations.png', get_lang('Invitations'), null, ICON_SIZE_SMALL);
        $messagesIcon = Display::return_icon('sn-message.png', get_lang('Messages'), null, ICON_SIZE_SMALL);
        $sharedProfileIcon = Display::return_icon('sn-profile.png', get_lang('ViewMySharedProfile'));
        $searchIcon = Display::return_icon('sn-search.png', get_lang('Search'), null, ICON_SIZE_SMALL);
        $portfolioIcon = Display::return_icon('wiki_task.png', get_lang('Portfolio'));
        $personalDataIcon = Display::return_icon('database.png', get_lang('PersonalDataReport'));
        $messageSocialIcon = Display::return_icon('promoted_message.png', get_lang('PromotedMessages'));
        $portfolio = Display::return_icon('portfolio.png', get_lang('Portfolio '));

        $allowPortfolioTool = api_get_configuration_value('allow_portfolio_tool');

        $forumCourseId = api_get_configuration_value('global_forums_course_id');
        $groupUrl = api_get_path(WEB_CODE_PATH).'social/groups.php';
        if (!empty($forumCourseId)) {
            $courseInfo = api_get_course_info_by_id($forumCourseId);
            if (!empty($courseInfo)) {
                $groupUrl = api_get_path(WEB_CODE_PATH).'forum/index.php?cidReq='.$courseInfo['code'];
            }
        }

        $html = '';
        $active = null;
        if (!in_array(
            $show,
            ['shared_profile', 'groups', 'group_edit', 'member_list', 'waiting_list', 'invite_friends']
        )) {
            $links = '<ul class="nav nav-pills nav-stacked">';
            $active = $show === 'home' ? 'active' : null;
            $links .= '
                <li class="home-icon '.$active.'">
                    <a href="'.api_get_path(WEB_CODE_PATH).'social/home.php">
                        '.$homeIcon.' '.get_lang('Home').'
                    </a>
                </li>';
            $active = $show === 'messages' ? 'active' : null;
            $links .= '
                <li class="messages-icon '.$active.'">
                    <a href="'.api_get_path(WEB_CODE_PATH).'messages/inbox.php">
                        '.$messagesIcon.' '.get_lang('Messages').$count_unread_message.'
                    </a>
                </li>';
            if ($allowPortfolioTool) {
                $links .= '
                    <li class="portoflio-icon '.($show === 'portfolio' ? 'active' : '').'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'portfolio/index.php">
                            '.$portfolioIcon.' '.get_lang('Portfolio').'
                        </a>
                    </li>
                ';
            } else {
                if ($settingExtendedProfileEnabled == true) {
                    $active = $show === 'portfolio' ? 'active' : null;
                    $links .= '
                <li class="portfolio-icon '.$active.'">
                      <a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_id.'&p=1">
                        '.$portfolio.' '.get_lang('Portfolio').'
                    </a>
                </li>';
                }
            }

            // Invitations
            $active = $show === 'invitations' ? 'active' : null;
            $links .= '
                <li class="invitations-icon '.$active.'">
                    <a href="'.api_get_path(WEB_CODE_PATH).'social/invitations.php">
                        '.$invitationsIcon.' '.get_lang('Invitations').$total_invitations.'
                    </a>
                </li>';

            // Shared profile and groups
            $active = $show === 'shared_profile' ? 'active' : null;
            $links .= '
                <li class="shared-profile-icon'.$active.'">
                    <a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php">
                        '.$sharedProfileIcon.' '.get_lang('ViewMySharedProfile').'
                    </a>
                </li>';
            $active = $show === 'friends' ? 'active' : null;
            $links .= '
                <li class="friends-icon '.$active.'">
                    <a href="'.api_get_path(WEB_CODE_PATH).'social/friends.php">
                        '.$friendsIcon.' '.get_lang('Friends').'
                    </a>
                </li>';
            $active = $show === 'browse_groups' ? 'active' : null;
            $links .= '
                <li class="browse-groups-icon '.$active.'">
                    <a href="'.$groupUrl.'">
                        '.$groupsIcon.' '.get_lang('SocialGroups').'
                    </a>
                </li>';

            // Search users
            $active = $show === 'search' ? 'active' : null;
            $links .= '
                <li class="search-icon '.$active.'">
                    <a href="'.api_get_path(WEB_CODE_PATH).'social/search.php">
                        '.$searchIcon.' '.get_lang('Search').'
                    </a>
                </li>';

            // My files
            $active = $show === 'myfiles' ? 'active' : null;

            $myFiles = '
                <li class="myfiles-icon '.$active.'">
                    <a href="'.api_get_path(WEB_CODE_PATH).'social/myfiles.php">
                        '.$filesIcon.' '.get_lang('MyFiles').'
                    </a>
                </li>';

            if (api_get_setting('allow_my_files') === 'false') {
                $myFiles = '';
            }
            $links .= $myFiles;

            if (!api_get_configuration_value('disable_gdpr')) {
                $active = $show === 'personal-data' ? 'active' : null;
                $personalData = '
                    <li class="personal-data-icon '.$active.'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/personal_data.php">
                            '.$personalDataIcon.' '.get_lang('PersonalDataReport').'
                        </a>
                    </li>';
                $links .= $personalData;
            }

            if (api_is_platform_admin()) {
                $active = $show === 'promoted_messages' ? 'active' : null;
                $personalData = '
                    <li class="personal-data-icon '.$active.'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/promoted_messages.php">
                            '.$messageSocialIcon.' '.get_lang('PromotedMessages').'
                        </a>
                    </li>';
                $links .= $personalData;
            }
            $links .= '</ul>';
            $html .= Display::panelCollapse(
                get_lang('SocialNetwork'),
                $links,
                'social-network-menu',
                null,
                'sn-sidebar',
                'sn-sidebar-collapse'
            );
        }

        if (!empty($group_id) && in_array($show, $show_groups)) {
            $html .= $usergroup->show_group_column_information(
                $group_id,
                api_get_user_id(),
                $show
            );
        }

        if ($show === 'shared_profile') {
            $links = '<ul class="nav nav-pills nav-stacked">';
            // My own profile
            if ($show_full_profile && $user_id == api_get_user_id()) {
                $links .= '
                    <li class="home-icon '.$active.'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/home.php">
                            '.$homeIcon.' '.get_lang('Home').'
                        </a>
                    </li>
                    <li class="messages-icon '.$active.'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'messages/inbox.php">
                            '.$messagesIcon.' '.get_lang('Messages').$count_unread_message.'
                        </a>
                    </li>';
                if ($allowPortfolioTool) {
                    $links .= '
                        <li class="portoflio-icon '.($show == 'portfolio' ? 'active' : '').'">
                            <a href="'.api_get_path(WEB_CODE_PATH).'portfolio/index.php">
                                '.$portfolioIcon.' '.get_lang('Portfolio').'
                            </a>
                        </li>
                    ';
                } else {
                    if ($settingExtendedProfileEnabled == true) {
                        $active = $show === 'portfolio' ? 'active' : null;
                        $links .= '
                <li class="portfolio-icon '.$active.'">
                      <a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_id.'&p=1">
                      '.$portfolio.' '.get_lang('Portfolio').'
                    </a>
                </li>';
                    }
                }
                $active = $show === 'invitations' ? 'active' : null;
                $links .= '
                    <li class="invitations-icon'.$active.'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/invitations.php">
                            '.$invitationsIcon.' '.get_lang('Invitations').$total_invitations.'
                        </a>
                    </li>';

                $links .= '
                    <li class="shared-profile-icon active">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php">
                            '.$sharedProfileIcon.' '.get_lang('ViewMySharedProfile').'
                        </a>
                    </li>
                    <li class="friends-icon">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/friends.php">
                            '.$friendsIcon.' '.get_lang('Friends').'
                        </a>
                    </li>';

                $links .= '<li class="browse-groups-icon">
                        <a href="'.$groupUrl.'">
                            '.$groupsIcon.' '.get_lang('SocialGroups').'
                        </a>
                        </li>';

                $active = $show == 'search' ? 'active' : null;
                $links .= '
                    <li class="search-icon '.$active.'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/search.php">
                            '.$searchIcon.' '.get_lang('Search').'
                        </a>
                    </li>';
                $active = $show == 'myfiles' ? 'active' : null;

                $myFiles = '
                    <li class="myfiles-icon '.$active.'">
                     <a href="'.api_get_path(WEB_CODE_PATH).'social/myfiles.php">
                            '.$filesIcon.' '.get_lang('MyFiles').'
                        </a>
                    </li>';

                if (api_get_setting('allow_my_files') === 'false') {
                    $myFiles = '';
                }
                $links .= $myFiles;

                if (!api_get_configuration_value('disable_gdpr')) {
                    $active = $show == 'personal-data' ? 'active' : null;
                    $personalData = '
                    <li class="personal-data-icon '.$active.'">
                        <a href="'.api_get_path(WEB_CODE_PATH).'social/personal_data.php">
                            '.$personalDataIcon.' '.get_lang('PersonalDataReport').'
                        </a>
                    </li>';
                    $links .= $personalData;
                    $links .= '</ul>';
                }
            }

            // My friend profile.
            if ($user_id != api_get_user_id()) {
                $sendMessageText = get_lang('SendMessage');
                $sendMessageIcon = Display::return_icon(
                    'new-message.png',
                    $sendMessageText
                );
                $sendMessageUrl = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?'.http_build_query([
                    'a' => 'get_user_popup',
                    'user_id' => $user_id,
                ]);

                $links .= '<li>';
                $links .= Display::url(
                    "$sendMessageIcon $sendMessageText",
                    $sendMessageUrl,
                    [
                        'class' => 'ajax',
                        'title' => $sendMessageText,
                        'data-title' => $sendMessageText,
                    ]
                );
                if ($allowPortfolioTool) {
                    $links .= '
                        <li class="portoflio-icon '.($show == 'portfolio' ? 'active' : '').'">
                            <a href="'.api_get_path(WEB_CODE_PATH).'portfolio/index.php?user='.$user_id.'">
                                '.$portfolioIcon.' '.get_lang('Portfolio').'
                            </a>
                        </li>
                    ';
                } else {
                    if ($settingExtendedProfileEnabled == true) {
                        $active = $show === 'portfolio' ? 'active' : null;
                        $links .= '
                <li class="portfolio-icon '.$active.'">
                      <a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_id.'&p=1">
                        '.$portfolio.' '.get_lang('Portfolio').'
                    </a>
                </li>';
                    }
                }
            }

            // Check if I already sent an invitation message
            $invitationSentList = self::get_list_invitation_sent_by_user_id(api_get_user_id());

            if (isset($invitationSentList[$user_id]) && is_array($invitationSentList[$user_id]) &&
                count($invitationSentList[$user_id]) > 0
            ) {
                $links .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/invitations.php">'.
                    Display::return_icon('invitation.png', get_lang('YouAlreadySentAnInvitation'))
                    .'&nbsp;&nbsp;'.get_lang('YouAlreadySentAnInvitation').'</a></li>';
            } else {
                if (!$show_full_profile) {
                    $links .= '<li>
                        <a class="btn-to-send-invitation" href="#" data-send-to="'.$user_id.'" title="'.get_lang('SendInvitation').'">'.
                        Display::return_icon('invitation.png', get_lang('SocialInvitationToFriends')).'&nbsp;'.get_lang('SendInvitation').
                        '</a></li>';
                }
            }

            $links .= '</ul>';
            $html .= Display::panelCollapse(
                get_lang('SocialNetwork'),
                $links,
                'social-network-menu',
                null,
                'sn-sidebar',
                'sn-sidebar-collapse'
            );

            if ($show_full_profile && $user_id == api_get_user_id()) {
                // Announcements
                $announcements = [];
                $announcementsByCourse = AnnouncementManager::getAnnoucementCourseTotalByUser($user_id);
                if (!empty($announcementsByCourse)) {
                    foreach ($announcementsByCourse as $announcement) {
                        $url = Display::url(
                            Display::return_icon(
                                'announcement.png',
                                get_lang('Announcements')
                            ).$announcement['course']['name'].' ('.$announcement['count'].')',
                            api_get_path(WEB_CODE_PATH).'announcements/announcements.php?cidReq='.$announcement['course']['code']
                        );
                        $announcements[] = Display::tag('li', $url);
                    }
                }

                if (!empty($announcements)) {
                    $html .= '<div class="social_menu_items">';
                    $html .= '<ul>';
                    foreach ($announcements as $announcement) {
                        $html .= $announcement;
                    }
                    $html .= '</ul>';
                    $html .= '</div>';
                }
            }
        }

        if ($show_delete_account_button) {
            $html .= '<div class="panel panel-default"><div class="panel-body">';
            $html .= '<ul class="nav nav-pills nav-stacked"><li>';
            $url = api_get_path(WEB_CODE_PATH).'auth/unsubscribe_account.php';
            $html .= Display::url(
                Display::return_icon(
                    'delete.png',
                    get_lang('Unsubscribe'),
                    [],
                    ICON_SIZE_TINY
                ).get_lang('Unsubscribe'),
                $url
            );
            $html .= '</li></ul>';
            $html .= '</div></div>';
        }
        $html .= '';

        return $html;
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
            $user_rol = $user_info['status'] == 1 ? Display::return_icon('teacher.png', get_lang('Teacher'), null, ICON_SIZE_TINY) : Display::return_icon('user.png', get_lang('Student'), null, ICON_SIZE_TINY);
            $status_icon_chat = null;
            if (isset($user_info['user_is_online_in_chat']) && $user_info['user_is_online_in_chat'] == 1) {
                $status_icon_chat = Display::return_icon('online.png', get_lang('Online'));
            } else {
                $status_icon_chat = Display::return_icon('offline.png', get_lang('Offline'));
            }

            $userPicture = $user_info['avatar'];
            $officialCode = '';
            if (api_get_setting('show_official_code_whoisonline') == 'true') {
                $officialCode .= '<div class="items-user-official-code"><p style="min-height: 30px;" title="'.get_lang('OfficialCode').'">'.$user_info['official_code'].'</p></div>';
            }

            if ($hide === true) {
                $completeName = '';
                $firstname = '';
                $lastname = '';
            }

            $img = '<img class="img-responsive img-circle" title="'.$completeName.'" alt="'.$completeName.'" src="'.$userPicture.'">';

            $url = null;
            // Anonymous users can't have access to the profile
            if (!api_is_anonymous()) {
                if (api_get_setting('allow_social_tool') === 'true') {
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
     * Displays the information of an individual user.
     *
     * @param int $user_id
     *
     * @return string
     */
    public static function display_individual_user($user_id)
    {
        global $interbreadcrumb;
        $safe_user_id = (int) $user_id;
        $currentUserId = api_get_user_id();

        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT * FROM $user_table WHERE user_id = ".$safe_user_id;
        $result = Database::query($sql);
        $html = null;
        if (Database::num_rows($result) == 1) {
            $user_object = Database::fetch_object($result);
            $userInfo = api_get_user_info($user_id);
            $alt = $userInfo['complete_name'].($currentUserId == $user_id ? '&nbsp;('.get_lang('Me').')' : '');
            $status = get_status_from_code($user_object->status);
            $interbreadcrumb[] = ['url' => 'whoisonline.php', 'name' => get_lang('UsersOnLineList')];

            $html .= '<div class ="thumbnail">';
            $fullurl = $userInfo['avatar'];

            $html .= '<img src="'.$fullurl.'" alt="'.$alt.'" />';

            if (!empty($status)) {
                $html .= '<div class="caption">'.$status.'</div>';
            }
            $html .= '</div>';

            if (api_get_setting('show_email_addresses') == 'true') {
                $html .= Display::encrypted_mailto_link($user_object->email, $user_object->email).'<br />';
            }
            //    MY PERSONAL OPEN AREA
            if ($user_object->openarea) {
                $html .= Display::page_subheader(get_lang('MyPersonalOpenArea'));
                $html .= '<p>'.$user_object->openarea.'</p>';
            }
            //    MY COMPETENCES
            if ($user_object->competences) {
                $html .= Display::page_subheader(get_lang('MyCompetences'));
                $html .= '<p>'.$user_object->competences.'</p>';
            }
            //    MY DIPLOMAS
            if ($user_object->diplomas) {
                $html .= Display::page_subheader(get_lang('MyDiplomas'));
                $html .= '<p>'.$user_object->diplomas.'</p>';
            }
            // WHAT I AM ABLE TO TEACH
            if ($user_object->teach) {
                $html .= Display::page_subheader(get_lang('MyTeach'));
                $html .= '<p>'.$user_object->teach.'</p>';
            }
            //    MY PRODUCTIONS
            self::display_productions($user_object->user_id);
        } else {
            $html .= '<div class="actions-title">';
            $html .= get_lang('UsersOnLineList');
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Display productions in who is online.
     *
     * @param int $user_id User id
     */
    public static function display_productions($user_id)
    {
        $webdir_array = UserManager::get_user_picture_path_by_id($user_id, 'web');
        $sysdir = UserManager::getUserPathById($user_id, 'system');
        $webdir = UserManager::getUserPathById($user_id, 'web');

        if (!is_dir($sysdir)) {
            mkdir($sysdir, api_get_permissions_for_new_directories(), true);
        }

        $productions = UserManager::get_user_productions($user_id);

        if (count($productions) > 0) {
            echo '<dt><strong>'.get_lang('Productions').'</strong></dt>';
            echo '<dd><ul>';
            foreach ($productions as $file) {
                // Only display direct file links to avoid browsing an empty directory
                if (is_file($sysdir.$file) && $file != $webdir_array['file']) {
                    echo '<li><a href="'.$webdir.urlencode($file).'" target=_blank>'.$file.'</a></li>';
                }
                // Real productions are under a subdirectory by the User's id
                if (is_dir($sysdir.$file)) {
                    $subs = scandir($sysdir.$file);
                    foreach ($subs as $my => $sub) {
                        if (substr($sub, 0, 1) != '.' && is_file($sysdir.$file.'/'.$sub)) {
                            echo '<li><a href="'.$webdir.urlencode($file).'/'.urlencode($sub).'" target=_blank>'.$sub.'</a></li>';
                        }
                    }
                }
            }
            echo '</ul></dd>';
        }
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
     * Sends a message to someone's wall.
     *
     * @param int    $userId         id of author
     * @param int    $friendId       id where we send the message
     * @param string $messageContent of the message
     * @param int    $messageId      id parent
     * @param string $messageStatus  status type of message
     *
     * @return int
     *
     * @author Yannick Warnier
     */
    public static function sendWallMessage(
        $userId,
        $friendId,
        $messageContent,
        $messageId = 0,
        $messageStatus = ''
    ) {
        $tblMessage = Database::get_main_table(TABLE_MESSAGE);
        $userId = (int) $userId;
        $friendId = (int) $friendId;
        $messageId = (int) $messageId;

        if (empty($userId) || empty($friendId)) {
            return 0;
        }

        // Just in case we replace the and \n and \n\r while saving in the DB
        $messageContent = str_replace(["\n", "\n\r"], '<br />', $messageContent);
        $now = api_get_utc_datetime();

        $attributes = [
            'user_sender_id' => $userId,
            'user_receiver_id' => $friendId,
            'msg_status' => $messageStatus,
            'send_date' => $now,
            'title' => '',
            'content' => $messageContent,
            'parent_id' => $messageId,
            'group_id' => 0,
            'update_date' => $now,
        ];

        return Database::insert($tblMessage, $attributes);
    }

    /**
     * Send File attachment (jpg,png).
     *
     * @author Anibal Copitan
     *
     * @param int    $userId      id user
     * @param array  $fileAttach
     * @param int    $messageId   id message (relation with main message)
     * @param string $fileComment description attachment file
     *
     * @return bool|int
     */
    public static function sendWallMessageAttachmentFile(
        $userId,
        $fileAttach,
        $messageId,
        $fileComment = ''
    ) {
        $safeFileName = Database::escape_string($fileAttach['name']);

        $extension = strtolower(substr(strrchr($safeFileName, '.'), 1));
        $allowedTypes = api_get_supported_image_extensions();

        $allowedTypes[] = 'mp4';
        $allowedTypes[] = 'webm';
        $allowedTypes[] = 'ogg';

        if (in_array($extension, $allowedTypes)) {
            return MessageManager::saveMessageAttachmentFile($fileAttach, $fileComment, $messageId, $userId);
        }

        return false;
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
            $groupCondition .= ' AND (msg_status = '.MESSAGE_STATUS_NEW.' OR msg_status = '.MESSAGE_STATUS_UNREAD.')) ';
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
            if ($index === 5) {
                // Exception only for the forum query above (field name change)
                $oneQuery .= ' ORDER BY post_date DESC '.$sqlLimit;
            } else {
                $oneQuery .= $sqlOrder.$sqlLimit;
            }
            $res = Database::query($oneQuery);
            $em = Database::getManager();
            if (Database::num_rows($res) > 0) {
                $repo = $em->getRepository('ChamiloCourseBundle:CForumPost');
                $repoThread = $em->getRepository('ChamiloCourseBundle:CForumThread');
                $groups = [];
                $userGroup = new UserGroup();
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
                    if ($row['msg_status'] == MESSAGE_STATUS_FORUM) {
                        /** @var CForumPost $post */
                        $post = $repo->find($row['id']);
                        /** @var CForumThread $thread */
                        $thread = $repoThread->find($row['thread_id']);
                        if ($post && $thread) {
                            $courseInfo = api_get_course_info_by_id($post->getCId());
                            $row['post_title'] = $post->getForumId();
                            $row['forum_title'] = $thread->getThreadTitle();
                            $row['thread_url'] = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.http_build_query([
                                    'cidReq' => $courseInfo['code'],
                                    'forum' => $post->getForumId(),
                                    'thread' => $post->getThreadId(),
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
     * Gets all messages from someone's wall (within specific limits), formatted.
     *
     * @param int    $userId      USER ID of the person's wall
     * @param array  $messageInfo
     * @param string $start       Start date (from when we want the messages until today)
     * @param int    $limit       Limit to the number of messages we want
     * @param int    $offset      Wall messages offset
     *
     * @return string HTML formatted string to show messages
     */
    public static function getWallPostComments(
        $userId,
        $messageInfo,
        $start = null,
        $limit = 10,
        $offset = 0
    ) {
        $messageId = $messageInfo['id'];
        $messages = MessageManager::getMessagesByParent($messageInfo['id'], 0, $offset, $limit);
        $formattedList = '<div class="sub-mediapost row">';
        $users = [];

        // The messages are ordered by date descendant, for comments we need ascendant
        krsort($messages);
        foreach ($messages as $message) {
            $userIdLoop = $message['user_sender_id'];
            if (!isset($users[$userIdLoop])) {
                $users[$userIdLoop] = api_get_user_info($userIdLoop);
            }
            $media = self::processPostComment($message, $users);
            $formattedList .= $media;
        }

        $formattedList .= '</div>';
        $formattedList .= '<div class="mediapost-form row">';
        $formattedList .= '<form class="form-horizontal" id="form_comment_'.$messageId.'" name="post_comment" method="POST">
                <div class="col-sm-9">
                <label for="comment" class="hide">'.get_lang('SocialWriteNewComment').'</label>
                <input type="hidden" name = "messageId" value="'.$messageId.'" />
                <textarea rows="3" class="form-control" placeholder="'.get_lang('SocialWriteNewComment').'" name="comment" rows="1" ></textarea>
                </div>
                <div class="col-sm-3 pull-right">
                <a onclick="submitComment('.$messageId.');" href="javascript:void(0);" name="social_wall_new_msg_submit" class="btn btn-default btn-post">
                    <em class="fa fa-pencil"></em> '.get_lang('Post').'
                </a>
                </div>
                </form>';
        $formattedList .= '</div>';

        return $formattedList;
    }

    /**
     * @param array $message
     * @param array $users
     *
     * @return string
     */
    public static function processPostComment($message, $users = [])
    {
        if (empty($message)) {
            return false;
        }

        $date = Display::dateToStringAgoAndLongDate($message['send_date']);
        $currentUserId = api_get_user_id();
        $userIdLoop = $message['user_sender_id'];
        $receiverId = $message['user_receiver_id'];

        if (!isset($users[$userIdLoop])) {
            $users[$userIdLoop] = api_get_user_info($userIdLoop);
        }

        $iconStatus = $users[$userIdLoop]['icon_status'];
        $nameComplete = $users[$userIdLoop]['complete_name'];
        $url = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$userIdLoop;

        $comment = '<div class="rep-post col-md-12">';
        $comment .= '<div class="col-md-2 col-xs-2 social-post-answers">';
        $comment .= '<div class="user-image pull-right">';
        $comment .= '<a href="'.$url.'">
                        <img src="'.$users[$userIdLoop]['avatar'].'"
                        alt="'.$users[$userIdLoop]['complete_name'].'"
                        class="avatar-thumb">
                     </a>';
        $comment .= '</div>';
        $comment .= '</div>';
        $comment .= '<div class="col-md-7 col-xs-7 social-post-answers">';
        $comment .= '<div class="user-data">';
        $comment .= $iconStatus;
        $comment .= '<div class="username"><a href="'.$url.'">'.$nameComplete.'</a>
                        <span>'.Security::remove_XSS($message['content']).'</span>
                       </div>';
        $comment .= '<div>'.$date.'</div>';
        $comment .= '<br />';
        $comment .= '</div>';
        $comment .= '</div>';

        $comment .= '<div class="col-md-3 col-xs-3 social-post-answers">';
        $comment .= '<div class="pull-right btn-group btn-group-sm">';

        $comment .= MessageManager::getLikesButton(
            $message['id'],
            $currentUserId
        );

        $isOwnWall = $currentUserId == $userIdLoop || $currentUserId == $receiverId;
        if ($isOwnWall) {
            $comment .= Display::button(
                '',
                Display::returnFontAwesomeIcon('trash', '', true),
                [
                    'id' => 'message_'.$message['id'],
                    'title' => get_lang('SocialMessageDelete'),
                    'type' => 'button',
                    'class' => 'btn btn-default btn-delete-social-comment',
                    'data-id' => $message['id'],
                    'data-sectoken' => Security::get_existing_token('social'),
                ]
            );
        }
        $comment .= '</div>';
        $comment .= '</div>';
        $comment .= '</div>';

        return $comment;
    }

    /**
     * @param array $message
     *
     * @return array
     */
    public static function getAttachmentPreviewList($message)
    {
        $messageId = $message['id'];

        $list = [];

        if (empty($message['group_id'])) {
            $files = MessageManager::getAttachmentList($messageId);
            if ($files) {
                $downloadUrl = api_get_path(WEB_CODE_PATH).'social/download.php?message_id='.$messageId;
                foreach ($files as $row_file) {
                    $url = $downloadUrl.'&attachment_id='.$row_file['id'];
                    $display = Display::fileHtmlGuesser($row_file['filename'], $url);
                    $list[] = $display;
                }
            }
        } else {
            $list = MessageManager::getAttachmentLinkList($messageId, 0);
        }

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
     */
    public static function readContentWithOpenGraph(string $link): string
    {
        if (strpos($link, "://") === false && substr($link, 0, 1) != "/") {
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
     */
    public static function verifyUrl(string $uri): bool
    {
        $client = new Client();

        try {
            $response = $client->request('GET', $uri, [
                'timeout' => 15,
                'verify' => false,
                'headers' => [
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Soft delete a message and his chidren.
     *
     * @param int $id id message to delete
     *
     * @throws Exception if file cannot be deleted in delete_message_attachment_file()
     *
     * @return bool status query
     */
    public static function deleteMessage($id)
    {
        $id = (int) $id;
        $messageInfo = MessageManager::get_message_by_id($id);
        if (!empty($messageInfo)) {
            // Delete comments too
            $messages = MessageManager::getMessagesByParent($id);
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    self::deleteMessage($message['id']);
                }
            }

            // Soft delete message
            $tblMessage = Database::get_main_table(TABLE_MESSAGE);
            $statusMessage = MESSAGE_STATUS_WALL_DELETE;
            $sql = "UPDATE $tblMessage SET msg_status = '$statusMessage' WHERE id = '{$id}' ";
            Database::query($sql);

            MessageManager::delete_message_attachment_file($id, $messageInfo['user_sender_id']);
            MessageManager::delete_message_attachment_file($id, $messageInfo['user_receiver_id']);

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
        if (api_get_setting('allow_social_tool') !== 'true') {
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
        if (isset($options['vcard']) && $options['vcard'] === false) {
            $vCardUserLink = '';
        }

        $userInfo = api_get_user_info($userId, true, false, true, true);

        if (isset($options['firstname']) && $options['firstname'] === false) {
            $userInfo['firstname'] = '';
        }
        if (isset($options['lastname']) && $options['lastname'] === false) {
            $userInfo['lastname'] = '';
        }

        if (isset($options['email']) && $options['email'] === false) {
            $userInfo['email'] = '';
        }

        // Ofaj
        $hasCertificates = Certificate::getCertificateByUser($userId);
        $userInfo['has_certificates'] = 0;
        if (!empty($hasCertificates)) {
            $userInfo['has_certificates'] = 1;
        }

        $userInfo['is_admin'] = UserManager::is_admin($userId);

        $languageId = api_get_language_id($userInfo['language']);
        $languageInfo = api_get_language_info($languageId);
        if ($languageInfo) {
            $userInfo['language'] = [
                'label' => $languageInfo['original_name'],
                'value' => $languageInfo['english_name'],
                'code' => $languageInfo['isocode'],
            ];
        }

        if (isset($options['language']) && $options['language'] === false) {
            $userInfo['language'] = '';
        }

        if (isset($options['photo']) && $options['photo'] === false) {
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

        if (api_get_setting('gamification_mode') === '1') {
            $gamificationPoints = GamificationUtils::getTotalUserPoints(
                $userId,
                $userInfo['status']
            );

            $template->assign('gamification_points', $gamificationPoints);
        }
        $chatEnabled = api_is_global_chat_enabled();

        if (isset($options['chat']) && $options['chat'] === false) {
            $chatEnabled = '';
        }

        $template->assign('chat_enabled', $chatEnabled);
        $template->assign('user_relation', $userRelationType);
        $template->assign('user_relation_type_friend', USER_RELATION_TYPE_FRIEND);
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
        $friends = self::get_friends($user_id, USER_RELATION_TYPE_FRIEND);
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
            $friendHtml = Display::return_message(get_lang('NoFriendsInYourContactList'), 'warning');
        }

        return $friendHtml;
    }

    /**
     * @return string Get the JS code necessary for social wall to load open graph from URLs.
     */
    public static function getScriptToGetOpenGraph(): string
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

    public static function displayWallForm(string $urlForm): string
    {
        $form = self::getWallForm($urlForm);
        $form->protect();

        return Display::panel($form->returnForm(), get_lang('SocialWall'));
    }

    /**
     * Show middle section for Portfolio extended.
     * Must be active on main/admin/settings.php?category=User into extended_profile.
     *
     * @param string $urlForm
     *
     * @return string
     */
    public static function getWallFormPortfolio($urlForm)
    {
        $userId = isset($_GET['u']) ? (int) $_GET['u'] : 0;
        $userId = $userId !== 0 ? $userId : api_get_user_id();
        $user_info = api_get_user_info($userId);
        $friend = true;
        $editPorfolioLink = '';
        if ($userId != api_get_user_id()) {
            $friend = self::get_relation_between_contacts(api_get_user_id(), $userId);
        } else {
            $editPorfolioLink .= "<div class=\"pull-right\" style='margin-top: -5px'>".
                '<a href="/main/auth/profile.php?type=extended#openarea" class="btn btn-default btn-sm btn-social-edit">'.
                "<i class=\"fa fa-pencil\" aria-hidden=\"true\"></i>".
                '</a>'.
                "</div>";
        }
        if ($friend == 0) {
            /* if has not relation, get current user */
            $userId = api_get_user_id();
            $user_info = api_get_user_info($userId);
        }
        // Images uploaded by course
        $more_info = '';

        // Productions
        $production_list = UserManager::build_production_list($userId);

        $form = new FormValidator(
            'social_wall_main',
            'post',
            $urlForm.$userId,
            null,
            ['enctype' => 'multipart/form-data'],
            FormValidator::LAYOUT_HORIZONTAL
        );

        $socialWallPlaceholder = isset($_GET['u']) ? get_lang('SocialWallWriteNewPostToFriend') : get_lang(
            'SocialWallWhatAreYouThinkingAbout'
        );

        if (!empty($user_info['competences']) || !empty($user_info['diplomas'])
            || !empty($user_info['openarea']) || !empty($user_info['teach'])) {
            // $more_info .= '<div><h3>'.get_lang('MoreInformation').'</h3></div>';
            //    MY PERSONAL OPEN AREA
            if (!empty($user_info['openarea'])) {
                $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div>';
                $more_info .= '<div class="social-profile-extended">'.$user_info['openarea'].'</div>';
                $more_info .= '<br />';
            }
            //    MY COMPETENCES
            if (!empty($user_info['competences'])) {
                $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyCompetences').'</strong></div>';
                $more_info .= '<div class="social-profile-extended">'.$user_info['competences'].'</div>';
                $more_info .= '<br />';
            }
            //    MY DIPLOMAS
            if (!empty($user_info['diplomas'])) {
                $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div>';
                $more_info .= '<div class="social-profile-extended">'.$user_info['diplomas'].'</div>';
                $more_info .= '<br />';
            }
            //    MY PRODUCTIONS
            if (!empty($production_list)) {
                $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyProductions').'</strong></div>';
                $more_info .= '<div class="social-profile-extended">'.$production_list.'</div>';
                $more_info .= '<br />';
            }
            // WHAT I AM ABLE TO TEACH
            if (!empty($user_info['teach'])) {
                $more_info .= '<div class="social-actions-message"><strong>'.get_lang('MyTeach').'</strong></div>';
                $more_info .= '<div class="social-profile-extended">'.$user_info['teach'].'</div>';
                $more_info .= '<br />';
            }
        }

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
        $form->addFile('picture', get_lang('UploadFile'), ['custom' => true]);
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

        return Display::panel($more_info, get_lang('Portfolio').$editPorfolioLink);
    }

    /**
     * @param int   $userId
     * @param int   $start
     * @param int   $length
     * @param array $threadList
     *
     * @return array
     */
    public static function getMyWallMessages($userId, $start = 0, $length = 10, $threadList = [])
    {
        $userGroup = new UserGroup();
        $groups = $userGroup->get_groups_by_user($userId, [GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_ADMIN]);
        $groupList = [];
        if (!empty($groups)) {
            $groupList = array_column($groups, 'id');
        }

        $friends = self::get_friends($userId, USER_RELATION_TYPE_FRIEND);
        $friendList = [];
        if (!empty($friends)) {
            $friendList = array_column($friends, 'friend_user_id');
        }

        $messages = self::getWallMessages(
            $userId,
            0,
            $groupList,
            $friendList,
            '',
            $start,
            $length,
            false,
            $threadList
        );

        $countPost = self::getCountWallMessagesByUser($userId, $groupList, $friendList, $threadList);
        $messages = self::formatWallMessages($messages);

        $html = '';
        foreach ($messages as $message) {
            $post = $message['html'];
            $comments = '';
            if (in_array($message['msg_status'], [MESSAGE_STATUS_WALL_POST, MESSAGE_STATUS_PROMOTED])) {
                $comments = self::getWallPostComments($userId, $message);
            }

            $html .= self::wrapPost($message, $post.$comments);
        }

        return [
            'posts' => $html,
            'count' => $countPost,
        ];
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
        if ($message['msg_status'] === MESSAGE_STATUS_PROMOTED) {
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
     * @param int   $userId
     * @param array $groupList
     * @param array $friendList
     * @param array $threadList
     *
     * @return int
     */
    public static function getCountWallMessagesByUser($userId, $groupList = [], $friendList = [], $threadList = [])
    {
        return self::getWallMessages(
            $userId,
            0,
            $groupList,
            $friendList,
            '',
            0,
            0,
            true,
            $threadList
        );
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public static function getWallMessagesByUser($userId)
    {
        $messages = self::getWallMessages($userId);
        $messages = self::formatWallMessages($messages);

        $html = '';
        foreach ($messages as $message) {
            $post = $message['html'];
            $comments = self::getWallPostComments($userId, $message);
            $html .= self::wrapPost($message, $post.$comments);
        }

        return $html;
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
        if (Skill::isAllowed($userId, false) === false) {
            return '';
        }

        $skill = new Skill();
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
                if (in_array($key, $fieldVisibilityKeys) && $fieldVisibility[$key] === false) {
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
                if ($extraFieldInfo['visible_to_self'] != 1) {
                    continue;
                }

                // if is not visible to others skip also
                if ($extraFieldInfo['visible_to_others'] != 1) {
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
                            if (!self::verifyUrl($icon_path)) {
                                break;
                            }
                            $bottom = '0.2';
                            //quick hack for hi5
                            $domain = parse_url($icon_path, PHP_URL_HOST);
                            if ($domain == 'www.hi5.com' || $domain == 'hi5.com') {
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
                            if ($key === 'terms_datedenaissance') {
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
                        get_lang('ExtraInformation'),
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
     * @param string $url
     */
    public static function handlePosts($url)
    {
        $friendId = isset($_GET['u']) ? (int) $_GET['u'] : api_get_user_id();
        $url = Security::remove_XSS($url);
        $wallSocialAddPost = SocialManager::getWallForm(api_get_self());

        if (!$wallSocialAddPost->validate()) {
            return;
        }

        $values = $wallSocialAddPost->exportValues();

        // Main post
        if (!empty($values['social_wall_new_msg_main']) || !empty($_FILES['picture']['tmp_name'])) {
            $messageContent = $values['social_wall_new_msg_main'];
            if (!empty($_POST['url_content'])) {
                $messageContent = $values['social_wall_new_msg_main'].'<br /><br />'.$values['url_content'];
            }

            $messageId = self::sendWallMessage(
                api_get_user_id(),
                $friendId,
                $messageContent,
                0,
                MESSAGE_STATUS_WALL_POST
            );

            if ($messageId && !empty($_FILES['picture']['tmp_name'])) {
                self::sendWallMessageAttachmentFile(
                    api_get_user_id(),
                    $_FILES['picture'],
                    $messageId
                );
            }

            Display::addFlash(Display::return_message(get_lang('MessageSent')));
            header('Location: '.$url);
            exit;
        }
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
        $htmlHeadXtra[] = api_get_js('jscroll/jquery.jscroll.js');
        // Add Jquery Time ago plugin
        $htmlHeadXtra[] = api_get_asset('jquery-timeago/jquery.timeago.js');
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
                                $(\'<div id=result_\' + messageId +\'>'.addslashes(get_lang('Saved')).'</div>\')
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

                $("body").on("click", ".btn-delete-social-message", function () {
                    var id = $(this).data("id");
                    var secToken = $(this).data("sectoken");

                    $.getJSON(
                        "'.$socialAjaxUrl.'",
                        { a: "delete_message", id: id, social_sec_token: secToken },
                        function (result) {
                            if (result) {
                                $("#message_" + id).parent().parent().parent().parent().html(result.message);

                                $(".btn-delete-social-message").data("sectoken", result.secToken);
                            }
                        }
                    );
                });

                $("body").on("click", ".btn-delete-social-comment", function () {
                    var id = $(this).data("id");
                    var secToken = $(this).data("sectoken");

                    $.getJSON(
                        "'.$socialAjaxUrl.'",
                        { a: "delete_message", id: id, social_sec_token: secToken },
                        function (result) {
                            if (result) {
                                $("#message_" + id).parent().parent().parent().html(result.message);

                                $(".btn-delete-social-comment").data("sectoken", result.secToken);
                            }
                        }
                    );
                });
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
                get_lang('SeeMore'),
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
        $forumCourseId = api_get_configuration_value('global_forums_course_id');

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        $threads = [];
        if (!empty($forumCourseId)) {
            $courseInfo = api_get_course_info_by_id($forumCourseId);
            getNotificationsPerUser($userId, true, $forumCourseId);
            $notification = Session::read('forum_notification');
            Session::erase('forum_notification');

            $threadUrlBase = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.http_build_query([
                'cidReq' => $courseInfo['code'],
            ]).'&';
            if (isset($notification['thread']) && !empty($notification['thread'])) {
                $threadList = array_filter(array_unique($notification['thread']));
                $em = Database::getManager();
                $repo = $em->getRepository('ChamiloCourseBundle:CForumThread');
                foreach ($threadList as $threadId) {
                    /** @var \Chamilo\CourseBundle\Entity\CForumThread $thread */
                    $thread = $repo->find($threadId);
                    if ($thread) {
                        $threadUrl = $threadUrlBase.http_build_query([
                            'forum' => $thread->getForumId(),
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
        $userGroup = new UserGroup();

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
                get_lang('SeeAllCommunities'),
                api_get_path(WEB_CODE_PATH).'forum/index.php?cidReq='.$courseInfo['code']
            );

            if (!empty($social_group_block)) {
                $social_group_block = Display::panelCollapse(
                    get_lang('MyCommunities'),
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

                    $result['picture'] = '<img class="img-responsive" src="'.$picture['file'].'" />';
                    $group_actions = '<div class="group-more"><a class="btn btn-default" href="groups.php?#tab_browse-2">'.
                        get_lang('SeeMore').'</a></div>';
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
                        get_lang('MyGroups'),
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
        $allowJustification = api_get_plugin_setting('justification', 'tool_enable') === 'true';
        if ($allowJustification) {
            $plugin = Justification::create();
            $headers[] = [
                'url' => api_get_path(WEB_CODE_PATH).'auth/justification.php',
                'content' => $plugin->get_lang('Justification'),
            ];
        }

        $allowPauseTraining = api_get_plugin_setting('pausetraining', 'tool_enable') === 'true';
        $allowEdit = api_get_plugin_setting('pausetraining', 'allow_users_to_edit_pause_formation') === 'true';
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

    private static function getWallForm(string $urlForm): FormValidator
    {
        $userId = isset($_GET['u']) ? '?u='.((int) $_GET['u']) : '';
        $form = new FormValidator(
            'social_wall_main',
            'post',
            $urlForm.$userId,
            null,
            ['enctype' => 'multipart/form-data'],
            FormValidator::LAYOUT_HORIZONTAL
        );

        $socialWallPlaceholder = isset($_GET['u'])
            ? get_lang('SocialWallWriteNewPostToFriend')
            : get_lang('SocialWallWhatAreYouThinkingAbout');

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
        $form->addFile('picture', get_lang('UploadFile'), ['custom' => true]);
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

        return $form;
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
            $htmlDelete = Display::button(
                '',
                Display::returnFontAwesomeIcon('trash', '', true),
                [
                    'id' => 'message_'.$message['id'],
                    'title' => get_lang('SocialMessageDelete'),
                    'type' => 'button',
                    'class' => 'btn btn-default btn-delete-social-message',
                    'data-id' => $message['id'],
                    'data-sectoken' => Security::get_existing_token('social'),
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
