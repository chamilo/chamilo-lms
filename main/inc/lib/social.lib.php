<?php
/* For licensing terms, see /license.txt */

use Zend\Feed\Reader\Reader;
use Zend\Feed\Reader\Entry\Rss;

/**
 * Class SocialManager
 *
 * This class provides methods for the social network management.
 * Include/require it in your code to use its features.
 *
 * @package chamilo.social
 */
class SocialManager extends UserManager
{
    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Allow to see contacts list
     * @author isaac flores paz
     * @return array
     */
    public static function show_list_type_friends()
    {
        $friend_relation_list = array();
        $tbl_my_friend_relation_type = Database :: get_main_table(TABLE_MAIN_USER_FRIEND_RELATION_TYPE);
        $sql = 'SELECT id,title FROM '.$tbl_my_friend_relation_type.'
                WHERE id<>6 ORDER BY id ASC';
        $result = Database::query($sql);
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
     * Get relation type contact by name
     * @param string names of the kind of relation
     * @return int
     * @author isaac flores paz
     */
    public static function get_relation_type_by_name($relation_type_name)
    {
        $list_type_friend = self::show_list_type_friends();
        foreach ($list_type_friend as $value_type_friend) {
            if (strtolower($value_type_friend['title']) == $relation_type_name) {

                return $value_type_friend['id'];
            }
        }
    }

    /**
     * Get the kind of relation between contacts
     * @param int user id
     * @param int user friend id
     * @param string
     * @author isaac flores paz
     */
    public static function get_relation_between_contacts($user_id, $user_friend)
    {
        $tbl_my_friend_relation_type = Database :: get_main_table(TABLE_MAIN_USER_FRIEND_RELATION_TYPE);
        $tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);
        $sql = 'SELECT rt.id as id FROM '.$tbl_my_friend_relation_type.' rt
                WHERE rt.id = (
                    SELECT uf.relation_type FROM '.$tbl_my_friend.' uf
                    WHERE
                        user_id='.((int) $user_id).' AND
                        friend_user_id='.((int) $user_friend).' AND
                        uf.relation_type <> '.USER_RELATION_TYPE_RRHH.'
                    LIMIT 1
                )';
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res, 'ASSOC');

            return $row['id'];
        } else {
            return USER_UNKNOW;
        }
    }

    /**
     * Gets friends id list
     * @param int  user id
     * @param int group id
     * @param string name to search
     * @param bool true will load firstname, lastname, and image name
     * @return array
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code, function renamed, $load_extra_info option added
     * @author isaac flores paz
     */
    public static function get_friends($user_id, $id_group = null, $search_name = null, $load_extra_info = true)
    {
        $list_ids_friends = array();
        $tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_my_user = Database :: get_main_table(TABLE_MAIN_USER);
        $sql = 'SELECT friend_user_id FROM '.$tbl_my_friend.'
                WHERE
                    relation_type NOT IN ('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND
                    friend_user_id<>'.((int) $user_id).' AND
                    user_id='.((int) $user_id);
        if (isset($id_group) && $id_group > 0) {
            $sql.=' AND relation_type='.$id_group;
        }
        if (isset($search_name)) {
            $search_name = trim($search_name);
            $search_name = str_replace(' ', '', $search_name);
            $sql.=' AND friend_user_id IN (
                SELECT user_id FROM '.$tbl_my_user.'
                WHERE
                    firstName LIKE "%'.Database::escape_string($search_name).'%" OR
                    lastName LIKE "%'.Database::escape_string($search_name).'%" OR
                    '.(api_is_western_name_order() ? 'concat(firstName, lastName)' : 'concat(lastName, firstName)').' LIKE concat("%","'.Database::escape_string($search_name).'","%")
                ) ';
        }

        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            if ($load_extra_info) {
                $my_user_info = api_get_user_info($row['friend_user_id']);
                $list_ids_friends[] = array(
                    'friend_user_id' => $row['friend_user_id'],
                    'firstName' => $my_user_info['firstName'],
                    'lastName' => $my_user_info['lastName'],
                    'username' => $my_user_info['username'],
                    'image' => $my_user_info['avatar'],
                );
            } else {
                $list_ids_friends[] = $row;
            }
        }

        return $list_ids_friends;
    }

    /**
     * get web path of user invitate
     * @author isaac flores paz
     * @author Julio Montoya setting variable array
     * @param int user id
     *
     * @return array
     */
    public static function get_list_web_path_user_invitation_by_user_id($user_id)
    {
        $list_ids = self::get_list_invitation_of_friends_by_user_id($user_id);
        $list = array();
        foreach ($list_ids as $values_ids) {
            $list[] = UserManager::get_user_picture_path_by_id(
                $values_ids['user_sender_id'],
                'web'
            );
        }

        return $list;
    }

    /**
     * Sends an invitation to contacts
     * @param int user id
     * @param int user friend id
     * @param string title of the message
     * @param string content of the message
     * @return boolean
     * @author isaac flores paz
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function send_invitation_friend($user_id, $friend_id, $message_title, $message_content)
    {
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $user_id = intval($user_id);
        $friend_id = intval($friend_id);

        //Just in case we replace the and \n and \n\r while saving in the DB
        $message_content = str_replace(array("\n", "\n\r"), '<br />', $message_content);

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
                'content'  => $message_content,
                'group_id' => 0,
                'parent_id' => 0,
                'update_date' => $now
            ];
            Database::insert($tbl_message, $params);

            $sender_info = api_get_user_info($user_id);
            $notification = new Notification();
            $notification->save_notification(
                Notification::NOTIFICATION_TYPE_INVITATION,
                array($friend_id),
                $message_title,
                $message_content,
                $sender_info
            );

            return true;
        } else {
            // invitation already exist
            $sql = 'SELECT COUNT(*) AS count, id FROM '.$tbl_message.'
                    WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status = 7';
            $res_if_exist = Database::query($sql);
            $row_if_exist = Database::fetch_array($res_if_exist, 'ASSOC');
            if ($row_if_exist['count'] == 1) {
                $sql = 'UPDATE '.$tbl_message.' SET
                        msg_status=5, content = "'.$clean_message_content.'"
                        WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status = 7 ';
                Database::query($sql);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Get number messages of the inbox
     * @author isaac flores paz
     * @param int user receiver id
     * @return int
     */
    public static function get_message_number_invitation_by_user_id($user_receiver_id)
    {
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT COUNT(*) as count_message_in_box FROM '.$tbl_message.'
                WHERE
                    user_receiver_id='.intval($user_receiver_id).' AND
                    msg_status='.MESSAGE_STATUS_INVITATION_PENDING;
        $res = Database::query($sql);
        $row = Database::fetch_array($res, 'ASSOC');

        return $row['count_message_in_box'];
    }

    /**
     * Get invitation list received by user
     * @author isaac flores paz
     * @param int user id
     * @return array
     */
    public static function get_list_invitation_of_friends_by_user_id($user_id)
    {
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT user_sender_id, send_date, title, content
                FROM '.$tbl_message.'
                WHERE
                    user_receiver_id = '.intval($user_id).' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        $res = Database::query($sql);
        $list_friend_invitation = array();
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list_friend_invitation[] = $row;
        }

        return $list_friend_invitation;
    }

    /**
     * Get invitation list sent by user
     * @author Julio Montoya <gugli100@gmail.com>
     * @param int user id
     * @return array()
     */
    public static function get_list_invitation_sent_by_user_id($user_id)
    {
        $list_friend_invitation = array();
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'SELECT user_receiver_id, send_date,title,content
                FROM '.$tbl_message.'
                WHERE
                    user_sender_id = '.intval($user_id).' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list_friend_invitation[$row['user_receiver_id']] = $row;
        }

        return $list_friend_invitation;
    }

    /**
     * Accepts invitation
     * @param int $user_send_id
     * @param int $user_receiver_id
     * @author isaac flores paz
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function invitation_accepted($user_send_id, $user_receiver_id)
    {
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = "UPDATE $tbl_message
                SET msg_status = ".MESSAGE_STATUS_INVITATION_ACCEPTED."
                WHERE
                    user_sender_id = ".((int) $user_send_id)." AND
                    user_receiver_id=".((int) $user_receiver_id)." AND
                    msg_status = ".MESSAGE_STATUS_INVITATION_PENDING;
        Database::query($sql);
    }

    /**
     * Denies invitation
     * @param int user sender id
     * @param int user receiver id
     * @author isaac flores paz
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function invitation_denied($user_send_id, $user_receiver_id)
    {
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = 'DELETE FROM '.$tbl_message.'
                WHERE
                    user_sender_id =  '.((int) $user_send_id).' AND
                    user_receiver_id='.((int) $user_receiver_id).' AND
                    msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
        Database::query($sql);
    }

    /**
     * allow attach to group
     * @author isaac flores paz
     * @param int user to qualify
     * @param int kind of rating
     * @return void()
     */
    public static function qualify_friend($id_friend_qualify, $type_qualify)
    {
        $tbl_user_friend = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $user_id = api_get_user_id();
        $sql = 'UPDATE '.$tbl_user_friend.' SET relation_type='.((int) $type_qualify).'
                WHERE user_id = '.((int) $user_id).' AND friend_user_id='.(int) $id_friend_qualify;
        Database::query($sql);
    }

    /**
     * Get user's feeds
     * @param   int User ID
     * @param   int Limit of posts per feed
     * @return  string  HTML section with all feeds included
     * @author  Yannick Warnier
     * @since   Dokeos 1.8.6.1
     */
    public static function get_user_feeds($user, $limit = 5)
    {
        $feed = UserManager::get_extra_user_data_by_field($user, 'rssfeeds');

        if (empty($feed)) {
            return '';
        }
        $feeds = explode(';', $feed['rssfeeds']);
        if (count($feeds) == 0) {
            return '';
        }
        $res = '';

        foreach ($feeds as $url) {
            if (empty($url)) {
                continue;
            }
            $channel = Reader::import($url);

            $i = 1;
            if (!empty($channel)) {
                $icon_rss = '';
                if (!empty($feed)) {
                    $icon_rss = Display::url(
                        Display::return_icon('social_rss.png', '', array(), 22),
                        Security::remove_XSS($feed['rssfeeds']),
                        array('target' => '_blank')
                    );
                }

                $res .= '<h3 class="title-rss">'.$icon_rss.' '.$channel->getTitle().'</h3>';
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
        }

        return $res;
    }

    /**
     * Sends invitations to friends
     *
     * @param int  $userId
     * @param string $subject
     * @param string $content
     *
     * @return string message invitation
     */
    public static function sendInvitationToUser($userId, $subject = '', $content = '')
    {
        $user_info = api_get_user_info($userId);
        $success = get_lang('MessageSentTo');
        $success.= ' : '.api_get_person_name($user_info['firstName'], $user_info['lastName']);

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
     * Helper functions definition
     */
    public static function get_logged_user_course_html($my_course, $count)
    {
        $result = '';
        // Table definitions
        $main_user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

        $course_code = $my_course['code'];
        $course_directory = $my_course['course_info']['directory'];
        $course_title = $my_course['course_info']['title'];
        $course_access_settings = CourseManager :: get_access_settings($course_code);

        $course_visibility = $course_access_settings['visibility'];

        $user_in_course_status = CourseManager :: get_user_in_course_status(api_get_user_id(), $course_code);

        //$valor = api_get_settings_params();
        $course_path = api_get_path(SYS_COURSE_PATH).$course_directory;   // course path
        if (api_get_setting('course_images_in_courses_list') === 'true') {
            if (file_exists($course_path.'/course-pic85x85.png')) {
                $image = $my_course['course_info']['course_image'];
                $imageCourse = Display::img($image, $course_title, array('class'=>'img-course'));
            } else {
                $imageCourse = Display::return_icon(
                    'session_default_small.png',
                    $course_title,
                    array('class' => 'img-course')
                );
            }
        } else {
            $imageCourse = Display::return_icon('course.png', get_lang('Course'), array('class' => 'img-default'));
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
           $result .= '<span class="title">' . $course_title . '<span>';
        } else {
            $result .= $course_title." ".get_lang('CourseClosed');
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

            $session = array();
            $session['title'] = $my_course['session_name'];
            if ($my_course['access_start_date'] == '0000-00-00') {
                $session['dates'] = get_lang('WithoutTimeLimits');
                if (api_get_setting('show_session_coach') === 'true') {
                    $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                }
            } else {
                $session ['dates'] = ' - '.get_lang('From').' '.$my_course['access_start_date'].' '.get_lang('To').' '.$my_course['access_end_date'];
                if (api_get_setting('show_session_coach') === 'true') {
                    $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                }
            }
        }
        $my_course['id_session'] = isset($my_course['id_session']) ? $my_course['id_session'] : 0;
        $output = array(
            $my_course['user_course_cat'],
            $result,
            $my_course['id_session'],
            $session
        );

        return $output;
    }

    /**
     * Shows the avatar block in social pages
     *
     * @param string $show highlight link possible values:
     * group_add,
     * home,
     * messages,
     * messages_inbox,
     * messages_compose,
     * messages_outbox,
     * invitations,
     * shared_profile,
     * friends,
     * groups search
     * @param int $group_id
     * @param int $user_id
     *
     */
    public static function show_social_avatar_block($show = '', $group_id = 0, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }

        $show_groups = array(
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
        );

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
            //$template->assign('user_group', $group_info);
            $template->assign(
                'user_is_group_admin',
                $userGroup->is_group_admin(
                    $group_id,
                    api_get_user_id()
                )
            );
        } else {
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
                    )
                ]
            );
        }

        $skillBlock = $template->get_template('social/avatar_block.tpl');

        return $template->fetch($skillBlock);
    }

    /**
     * Shows the right menu of the Social Network tool
     *
     * @param string $show highlight link possible values:
     * group_add,
     * home,
     * messages,
     * messages_inbox,
     * messages_compose ,
     * messages_outbox,
     * invitations,
     * shared_profile,
     * friends,
     * groups search
     * @param int $group_id group id
     * @param int $user_id user id
     * @param bool $show_full_profile show profile or not (show or hide the user image/information)
     * @param bool $show_delete_account_button
     *
     */
    public static function show_social_menu(
        $show = '',
        $group_id = 0,
        $user_id = 0,
        $show_full_profile = false,
        $show_delete_account_button = false
    ) {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }

        $usergroup = new UserGroup();
        $show_groups = array(
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
        );

        // get count unread message and total invitations
        $count_unread_message = MessageManager::get_number_of_messages(true);
        $count_unread_message = !empty($count_unread_message) ? Display::badge($count_unread_message) : null;

        $number_of_new_messages_of_friend = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
        $group_pending_invitations = $usergroup->get_groups_by_user(
            api_get_user_id(),
            GROUP_USER_PERMISSION_PENDING_INVITATION,
            false
        );
        $group_pending_invitations = count($group_pending_invitations);
        $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
        $total_invitations = (!empty($total_invitations) ? Display::badge($total_invitations) : '');

        $filesIcon = Display::return_icon('sn-files.png', get_lang('MyFiles'), '', ICON_SIZE_SMALL);
        $friendsIcon = Display::return_icon('sn-friends.png', get_lang('Friends'), '', ICON_SIZE_SMALL);
        $groupsIcon = Display::return_icon('sn-groups.png', get_lang('SocialGroups'), '', ICON_SIZE_SMALL);
        $homeIcon = Display::return_icon('sn-home.png', get_lang('Home'), '', ICON_SIZE_SMALL);
        $invitationsIcon = Display::return_icon('sn-invitations.png', get_lang('Invitations'), '', ICON_SIZE_SMALL);
        $messagesIcon = Display::return_icon('sn-message.png', get_lang('Messages'), '', ICON_SIZE_SMALL);
        $sharedProfileIcon = Display::return_icon('sn-profile.png', get_lang('ViewMySharedProfile'));
        $searchIcon = Display::return_icon('sn-search.png', get_lang('Search'), '', ICON_SIZE_SMALL);

        $html = '';
        $active = null;
        if (!in_array(
            $show,
            array('shared_profile', 'groups', 'group_edit', 'member_list', 'waiting_list', 'invite_friends')
        )) {
            $links = '<ul class="nav nav-pills nav-stacked">';
            $active = $show == 'home' ? 'active' : null;
            $links .= '
                <li class="home-icon ' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'social/home.php">
                        ' . $homeIcon . ' ' . get_lang('Home') . '
                    </a>
                </li>';
            $active = $show == 'messages' ? 'active' : null;
            $links .= '
                <li class="messages-icon ' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'messages/inbox.php?f=social">
                        ' . $messagesIcon . ' ' . get_lang('Messages') . $count_unread_message . '
                    </a>
                </li>';

            //Invitations
            $active = $show == 'invitations' ? 'active' : null;
            $links .= '
                <li class="invitations-icon ' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'social/invitations.php">
                        ' . $invitationsIcon . ' ' . get_lang('Invitations') . $total_invitations . '
                    </a>
                </li>';

            //Shared profile and groups
            $active = $show == 'shared_profile' ? 'active' : null;
            $links .= '
                <li class="shared-profile-icon' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'social/profile.php">
                        ' . $sharedProfileIcon . ' ' . get_lang('ViewMySharedProfile') . '
                    </a>
                </li>';
            $active = $show == 'friends' ? 'active' : null;
            $links .= '
                <li class="friends-icon ' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'social/friends.php">
                        ' . $friendsIcon . ' ' . get_lang('Friends') . '
                    </a>
                </li>';
            $active = $show == 'browse_groups' ? 'active' : null;
            $links .= '
                <li class="browse-groups-icon ' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'social/groups.php">
                        ' . $groupsIcon . ' ' . get_lang('SocialGroups') . '
                    </a>
                </li>';

            //Search users
            $active = $show == 'search' ? 'active' : null;
            $links .= '
                <li class="search-icon ' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'social/search.php">
                        ' . $searchIcon . ' ' . get_lang('Search') . '
                    </a>
                </li>';

            //My files
            $active = $show == 'myfiles' ? 'active' : null;

            $myFiles = '
                <li class="myfiles-icon ' . $active . '">
                    <a href="' . api_get_path(WEB_CODE_PATH) . 'social/myfiles.php">
                        ' . $filesIcon . ' ' . get_lang('MyFiles') . '
                    </a>
                </li>';

            if (api_get_setting('allow_my_files') === 'false') {
                $myFiles = '';
            }
            $links .= $myFiles;
            $links .='</ul>';

            $html .= Display::panelCollapse(
                get_lang('SocialNetwork'),
                $links,
                'social-network-menu',
                null,
                'sn-sidebar',
                'sn-sidebar-collapse'
            );
        }

        if (in_array($show, $show_groups) && !empty($group_id)) {
            $html .= $usergroup->show_group_column_information(
                $group_id,
                api_get_user_id(),
                $show
            );
        }

        if ($show == 'shared_profile') {
            $links =  '<ul class="nav nav-pills nav-stacked">';
            // My own profile
            if ($show_full_profile && $user_id == intval(api_get_user_id())) {
                $links .= '
                    <li class="home-icon ' . $active . '">
                        <a href="' . api_get_path(WEB_CODE_PATH) . 'social/home.php">
                            ' . $homeIcon . ' ' . get_lang('Home') . '
                        </a>
                    </li>
                    <li class="messages-icon ' . $active . '">
                        <a href="' . api_get_path(WEB_CODE_PATH) . 'messages/inbox.php?f=social">
                            ' . $messagesIcon . ' ' . get_lang('Messages') . $count_unread_message . '
                        </a>
                    </li>';
                $active = $show == 'invitations' ? 'active' : null;
                $links .= '
                    <li class="invitations-icon' . $active . '">
                        <a href="' . api_get_path(WEB_CODE_PATH) . 'social/invitations.php">
                            ' . $invitationsIcon . ' ' . get_lang('Invitations') . $total_invitations . '
                        </a>
                    </li>';

                $links .= '
                    <li class="shared-profile-icon active">
                        <a href="' . api_get_path(WEB_CODE_PATH) . 'social/profile.php">
                            ' . $sharedProfileIcon . ' ' . get_lang('ViewMySharedProfile') . '
                        </a>
                    </li>
                    <li class="friends-icon">
                        <a href="' . api_get_path(WEB_CODE_PATH) . 'social/friends.php">
                            ' . $friendsIcon . ' ' . get_lang('Friends') . '
                        </a>
                    </li>
                    <li class="browse-groups-icon">
                        <a href="' . api_get_path(WEB_CODE_PATH) . 'social/groups.php">
                            ' . $groupsIcon . ' ' . get_lang('SocialGroups') . '
                        </a>
                    </li>';
                $active = $show == 'search' ? 'active' : null;
                $links .= '
                    <li class="search-icon ' . $active . '">
                        <a href="' . api_get_path(WEB_CODE_PATH) . 'social/search.php">
                            ' . $searchIcon . ' ' . get_lang('Search') . '
                        </a>
                    </li>';
                $active = $show == 'myfiles' ? 'active' : null;

                $myFiles = '
                    <li class="myfiles-icon ' . $active . '">
                     <a href="' . api_get_path(WEB_CODE_PATH) . 'social/myfiles.php">
                            ' . $filesIcon . ' ' . get_lang('MyFiles') . '
                        </a>
                    </li>';

                if (api_get_setting('allow_my_files') === 'false') {
                    $myFiles = '';
                }
                $links .= $myFiles;
            }

            // My friend profile.
            if ($user_id != api_get_user_id()) {
                $sendMessageText = get_lang('SendMessage');
                $sendMessageIcon = Display::return_icon(
                    'new-message.png',
                    $sendMessageText
                );
                $sendMesssageUrl = api_get_path(WEB_AJAX_PATH)
                    . 'user_manager.ajax.php?'
                    . http_build_query([
                        'a' => 'get_user_popup',
                        'user_id' => $user_id,
                    ]);

                $links .= '<li>';
                $links .= Display::url(
                    "$sendMessageIcon $sendMessageText",
                    $sendMesssageUrl,
                    [
                        'class' => 'ajax',
                        'title' => $sendMessageText,
                        'data-title' => $sendMessageText,
                    ]
                );
                $links .= '</li>';
            }

            // Check if I already sent an invitation message
            $invitation_sent_list = SocialManager::get_list_invitation_sent_by_user_id(
                api_get_user_id()
            );

            if (isset($invitation_sent_list[$user_id]) && is_array($invitation_sent_list[$user_id]) && count($invitation_sent_list[$user_id]) > 0) {
                $links .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/invitations.php">'.Display::return_icon('invitation.png', get_lang('YouAlreadySentAnInvitation')).'&nbsp;&nbsp;'.get_lang('YouAlreadySentAnInvitation').'</a></li>';
            } else {
                if (!$show_full_profile) {
                    $links .= '<li><a class="btn-to-send-invitation" href="#" data-send-to="' . $user_id . '" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('invitation.png', get_lang('SocialInvitationToFriends')).'&nbsp;'.get_lang('SendInvitation').'</a></li>';
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

            if ($show_full_profile && $user_id == intval(api_get_user_id())) {
                $personal_course_list = UserManager::get_personal_session_course_list($user_id);
                $course_list_code = array();
                $i = 1;
                if (is_array($personal_course_list)) {
                    foreach ($personal_course_list as $my_course) {
                        if ($i <= 10) {
                            $course_list_code[] = array('code' => $my_course['code']);
                        } else {
                            break;
                        }
                        $i++;
                    }
                    // To avoid repeated courses
                    $course_list_code = array_unique_dimensional($course_list_code);
                }

                // Announcements
                $my_announcement_by_user_id = intval($user_id);
                $announcements = array();
                foreach ($course_list_code as $course) {
                    $course_info = api_get_course_info($course['code']);
                    if (!empty($course_info)) {
                        $content = AnnouncementManager::get_all_annoucement_by_user_course(
                            $course_info['code'],
                            $my_announcement_by_user_id
                        );

                        if (!empty($content)) {
                            $url = Display::url(
                                Display::return_icon('announcement.png', get_lang('Announcements')).$course_info['name'].' ('.$content['count'].')',
                                api_get_path(WEB_CODE_PATH).'announcements/announcements.php?cidReq='.$course['code']
                            );
                            $announcements[] = Display::tag('li', $url);
                        }
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
                    array(),
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
     * @param array $user_list The list of users to be shown
     * @param bool $wrap Whether we want the function to wrap the spans list in a div or not
     * @return string HTML block or null if and ID was defined
     * @assert (null) === false
     */
    public static function display_user_list($user_list, $wrap = true)
    {
        $html = null;

        if (isset($_GET['id']) or count($user_list) < 1) {
            return false;
        }

        $course_url = '';
        if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
            $course_url = '&amp;cidReq='.Security::remove_XSS($_GET['cidReq']);
        }

        foreach ($user_list as $uid) {
            $user_info = api_get_user_info($uid, $checkIfUserOnline = true);
            $lastname = $user_info['lastname'];
            $firstname =  $user_info['firstname'];
            $completeName = $firstname.', '.$lastname;

            $user_rol = $user_info['status'] == 1 ? Display::return_icon('teacher.png', get_lang('Teacher'), null, ICON_SIZE_TINY) : Display::return_icon('user.png', get_lang('Student'), null, ICON_SIZE_TINY);
            $status_icon_chat = null;
            if ($user_info['user_is_online_in_chat'] == 1) {
                $status_icon_chat = Display::return_icon('online.png', get_lang('Online'));
            } else {
                $status_icon_chat = Display::return_icon('offline.png', get_lang('Offline'));
            }

            $userPicture = $user_info['avatar'];
            $officialCode = '';
            if (api_get_setting('show_official_code_whoisonline') == 'true') {
                $officialCode .= '<div class="items-user-official-code"><p style="min-height: 30px;" title="'.get_lang('OfficialCode').'">'.$user_info['official_code'].'</p></div>';
            }
            $img = '<img class="img-responsive img-circle" title="'.$completeName.'" alt="'.$completeName.'" src="'.$userPicture.'">';

            $url =  null;
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
     * Displays the information of an individual user
     * @param int $user_id
     */
    public static function display_individual_user($user_id)
    {
        global $interbreadcrumb;
        $safe_user_id = intval($user_id);
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
            $interbreadcrumb[] = array('url' => 'whoisonline.php', 'name' => get_lang('UsersOnLineList'));

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

            if ($user_object->competences) {
                $html .= Display::page_subheader(get_lang('MyCompetences'));
                $html .= '<p>'.$user_object->competences.'</p>';
            }
            if ($user_object->diplomas) {
                $html .= Display::page_subheader(get_lang('MyDiplomas'));
                $html .= '<p>'.$user_object->diplomas.'</p>';
            }
            if ($user_object->teach) {
                $html .= Display::page_subheader(get_lang('MyTeach'));
                $html .= '<p>'.$user_object->teach.'</p>';
            }
            SocialManager::display_productions($user_object->user_id);
            if ($user_object->openarea) {
                $html .= Display::page_subheader(get_lang('MyPersonalOpenArea'));
                $html .= '<p>'.$user_object->openarea.'</p>';
            }
        } else {
            $html .= '<div class="actions-title">';
            $html .= get_lang('UsersOnLineList');
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Display productions in who is online
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
     * @return string
     */
    public static function social_wrapper_div($content, $span_count)
    {
        $span_count = intval($span_count);
        $html = '<div class="span'.$span_count.'">';
        $html .= '<div class="well_border">';
        $html .= $content;
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Dummy function
     *
     */
    public static function get_plugins($place = SOCIAL_CENTER_PLUGIN)
    {
        $content = '';
        switch ($place) {
            case SOCIAL_CENTER_PLUGIN:
                $social_plugins = array(1, 2);
                if (is_array($social_plugins) && count($social_plugins) > 0) {
                    $content.= '<div id="social-plugins">';
                    foreach ($social_plugins as $plugin) {
                        $content.= '<div class="social-plugin-item">';
                        $content.= $plugin;
                        $content.= '</div>';
                    }
                    $content.= '</div>';
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
     * Sends a message to someone's wall
     * @param int $userId id of author
     * @param int $friendId id where we send the message
     * @param string $messageContent of the message
     * @param int $messageId id parent
     * @param string $messageStatus status type of message
     * @return boolean
     * @author Yannick Warnier
     */
    public static function sendWallMessage($userId, $friendId, $messageContent, $messageId = 0, $messageStatus = '')
    {
        $tblMessage = Database::get_main_table(TABLE_MESSAGE);
        $userId = intval($userId);
        $friendId = intval($friendId);
        $messageId = intval($messageId);

        // Just in case we replace the and \n and \n\r while saving in the DB
        $messageContent = str_replace(array("\n", "\n\r"), '<br />', $messageContent);
        $now = api_get_utc_datetime();

        $attributes = array(
            'user_sender_id' => $userId,
            'user_receiver_id' => $friendId,
            'msg_status' => $messageStatus,
            'send_date' => $now,
            'title' => '',
            'content' => $messageContent,
            'parent_id' => $messageId,
            'group_id' => 0,
            'update_date' => $now
        );

        return Database::insert($tblMessage, $attributes);
    }

    /**
     * Send File attachment (jpg,png)
     * @author Anibal Copitan
     * @param int $userId id user
     * @param array $fileAttach
     * @param int $messageId id message (relation with main message)
     * @param string $fileComment description attachment file
     * @return bool
     */
    public static function sendWallMessageAttachmentFile($userId, $fileAttach, $messageId, $fileComment = '')
    {
        $tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        // create directory
        $social = '/social/';
        $pathMessageAttach = UserManager::getUserPathById($userId, 'system').'message_attachments'.$social;
        $safeFileComment = Database::escape_string($fileComment);
        $safeFileName = Database::escape_string($fileAttach['name']);

        $extension = strtolower(substr(strrchr($safeFileName, '.'), 1));
        $allowedTypes = api_get_supported_image_extensions();
        if (!in_array($extension, $allowedTypes)) {
            $flag = false;
        } else {
            $newFileName = uniqid('') . '.' . $extension;
            if (!file_exists($pathMessageAttach)) {
                @mkdir($pathMessageAttach, api_get_permissions_for_new_directories(), true);
            }

            $newPath = $pathMessageAttach . $newFileName;
            if (is_uploaded_file($fileAttach['tmp_name'])) {
                @copy($fileAttach['tmp_name'], $newPath);
            }

            $small = self::resize_picture($newPath, IMAGE_WALL_SMALL_SIZE);
            $medium = self::resize_picture($newPath, IMAGE_WALL_MEDIUM_SIZE);

            $big = new Image($newPath);
            $ok = $small && $small->send_image($pathMessageAttach . IMAGE_WALL_SMALL . '_' . $newFileName) &&
                $medium && $medium->send_image($pathMessageAttach . IMAGE_WALL_MEDIUM .'_' . $newFileName) &&
                $big && $big->send_image($pathMessageAttach . IMAGE_WALL_BIG . '_' . $newFileName);

            // Insert
            $newFileName = $social.$newFileName;

            $params = [
                'filename' => $safeFileName,
                'comment' => $safeFileComment,
                'path' => $newFileName,
                'message_id' => $messageId,
                'size' => $fileAttach['size'],
            ];
            Database::insert($tbl_message_attach, $params);
            $flag = true;
        }

        return $flag;
    }

    /**
     * Gets all messages from someone's wall (within specific limits)
     * @param int $userId id of wall shown
     * @param string $messageStatus status wall message
     * @param int|string $parentId id message (Post main)
     * @param date $start Date from which we want to show the messages, in UTC time
     * @param int $limit Limit for the number of parent messages we want to show
     * @param int $offset Wall message query offset
     * @return boolean
     * @author Yannick Warnier
     */
    public static function getWallMessages($userId, $messageStatus, $parentId = '', $start = null, $limit = 10, $offset = 0)
    {
        if (empty($start)) {
            $start = '0000-00-00';
        }

        $tblMessage = Database::get_main_table(TABLE_MESSAGE);
        $tblMessageAttachment = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        $userId = intval($userId);
        $start = Database::escape_string($start);
        $limit = intval($limit);

        $sql = "SELECT
                    id,
                    user_sender_id,
                    user_receiver_id,
                    send_date,
                    content,
                    parent_id,
                    (
                        SELECT ma.path FROM $tblMessageAttachment ma
                        WHERE  ma.message_id = tm.id 
                    ) as path,
                    (
                        SELECT ma.filename FROM $tblMessageAttachment ma 
                        WHERE ma.message_id = tm.id 
                    ) as filename
                    FROM $tblMessage tm
                WHERE
                    user_receiver_id = $userId AND 
                    send_date > '$start'
        ";

        $sql .= (empty($messageStatus) || is_null($messageStatus)) ? '' : " AND msg_status = '$messageStatus' ";
        $sql .= (empty($parentId) || is_null($parentId)) ? '' : " AND parent_id = '$parentId' ";
        $sql .= " ORDER BY send_date DESC LIMIT $offset, $limit ";
        $messages = array();
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $messages[] = $row;
            }
        }

        return $messages;
    }

    /**
     * Gets all messages from someone's wall (within specific limits), formatted
     * @param int       $userId     USER ID of the person's wall
     * @param int       $friendId   id person
     * @param int       $idMessage  id message
     * @param string    $start      Start date (from when we want the messages until today)
     * @param int       $limit      Limit to the number of messages we want
     * @param int       $offset     Wall messages offset
     * @return string  HTML formatted string to show messages
     */
    public static function getWallMessagesHTML($userId, $friendId, $idMessage, $start = null, $limit = 10, $offset = 0)
    {
        if (empty($start)) {
            $start = '0000-00-00';
        }

        $isOwnWall = (api_get_user_id() == $userId  && $userId == $friendId);
        $messages = self::getWallMessages($userId, MESSAGE_STATUS_WALL, $idMessage, $start, $limit, $offset);
        $formattedList = '<div class="sub-mediapost">';
        $users = array();

        // The messages are ordered by date descendant, for comments we need ascendant
        krsort($messages);
        foreach ($messages as $message) {
            $date = api_get_local_time($message['send_date']);
            $userIdLoop = $message['user_sender_id'];
            if (!isset($users[$userIdLoop])) {
                $users[$userIdLoop] = api_get_user_info($userIdLoop);
            }

            $nameComplete = api_is_western_name_order()
                ? $users[$userIdLoop]['firstname'] .' ' . $users[$userIdLoop]['lastname']
                : $users[$userIdLoop]['lastname'] . ' ' . $users[$userIdLoop]['firstname'];
            $url = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$userIdLoop;
            $media = '';
            $media .= '<div class="rep-post">';
            $media .= '<div class="col-md-2 col-xs-2 social-post-answers">';
            $media .= '<div class="user-image pull-right">';
            $media .= '<a href="'.$url.'" ><img src="'. $users[$userIdLoop]['avatar'] .
                       '" alt="'.$users[$userIdLoop]['complete_name'].'" class="avatar-thumb"></a>';
            $media .= '</div>';
            $media .= '</div>';
            $media .= '<div class="col-md-9 col-xs-9 social-post-answers">';
            $media .= '<div class="user-data">';
            $media .= '<div class="username">' . '<a href="'.$url.'">'.$nameComplete.'</a> <span>'.Security::remove_XSS($message['content']).'</span></div>';
            $media .= '<div class="time timeago" title="'.$date.'">'.$date.'</div>';
            $media .= '<br />';
            $media .= '</div>';
            $media .= '</div>';
            $media .= '</div>';
            if ($isOwnWall) {
                $media .= '<div class="col-md-1 col-xs-1 social-post-answers">';
                $media .= '<div class="pull-right deleted-mgs">';
                $media .= '<a title="'.get_lang("SocialMessageDelete").'" href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?messageId='.
                    $message['id'].'">x</a>';
                $media .= '</div>';
                $media .= '</div>';
            }

            $formattedList .= $media;
        }

        $formattedList .= '</div>';

        $formattedList .= '<div class="mediapost-form">';
            $formattedList .= '<form name="social_wall_message" method="POST">
                <label for="social_wall_new_msg" class="hide">'.get_lang('SocialWriteNewComment').'</label>
                <input type="hidden" name = "messageId" value="'.$idMessage.'" />
                <textarea placeholder="'.get_lang('SocialWriteNewComment').
                '" name="social_wall_new_msg" rows="1" style="width:80%;" ></textarea>
                <button type="submit" name="social_wall_new_msg_submit"
                class="pull-right btn btn-default" /><em class="fa fa-pencil"></em> '.get_lang('Post').'</button>
                </form>';
        $formattedList .= '</div>';

        return $formattedList;
    }

    /**
     * Gets all user's starting wall messages (within specific limits)
     * @param   int     $userId     User's id
     * @param   int     $friendId   Friend's id
     * @param   date    $start      Start date (from when we want the messages until today)
     * @param   int     $limit      Limit to the number of messages we want
     * @param   int     $offset     Wall messages offset
     * @return  array   $data       return user's starting wall messages along with message extra data
     */
    public static function getWallMessagesPostHTML($userId, $friendId = 0, $start = null, $limit = 10, $offset= 0)
    {
        if (empty($start)) {
            $start = '0000-00-00';
        }
        $isOwnWall = (api_get_user_id() == $userId  && $userId == $friendId);
        $messages = self::getWallMessages($userId, MESSAGE_STATUS_WALL_POST, null, $start, $limit, $offset);
        $users = array();
        $data = array();
        foreach ($messages as $key => $message) {
            $userIdLoop = $message['user_sender_id'];
            $userFriendIdLoop = $message['user_receiver_id'];

            if (!isset($users[$userIdLoop])) {
                $users[$userIdLoop] = api_get_user_info($userIdLoop);
            }

            if (!isset($users[$userFriendIdLoop])) {
                $users[$userFriendIdLoop] = api_get_user_info($userFriendIdLoop);
            }

            $html = '';
            $html .= self::headerMessagePost(
                $message['user_sender_id'],
                $message['user_receiver_id'],
                $users,
                $message,
                $isOwnWall
            );

            $data[$key]['id'] = $message['id'];
            $data[$key]['html'] = $html;
        }

        return $data;
    }

    /**
     * Returns the formatted header message post
     * @param   int     $authorId   Author's id
     * @param   int     $receiverId Receiver's id
     * @param   array   $users      Author's and receiver's data
     * @param   array   $message    Message data
     * @param   boolean $isOwnWall  Determines if the author is in its own social wall or not
     * @return  string  $html       The formatted header message post
     */
    private static function headerMessagePost($authorId, $receiverId, $users, $message, $isOwnWall = false)
    {
        $date = api_get_local_time($message['send_date']);
        $avatarAuthor = $users[$authorId]['avatar'];
        $urlAuthor = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$authorId;
        $nameCompleteAuthor = api_get_person_name(
            $users[$authorId]['firstname'],
            $users[$authorId]['lastname']
        );

        $urlReceiver = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$receiverId;
        $nameCompleteReceiver = api_get_person_name(
            $users[$receiverId]['firstname'],
            $users[$receiverId]['lastname']
        );

        $htmlReceiver = '';
        if ($authorId != $receiverId) {
            $htmlReceiver = ' > <a href="'.$urlReceiver.'">' . $nameCompleteReceiver . '</a> ';
        }

        $wallImage = '';
        if (!empty($message['path'])) {
            $imageBig = UserManager::getUserPicture($authorId, USER_IMAGE_SIZE_BIG);
            $imageSmall = UserManager::getUserPicture($authorId, USER_IMAGE_SIZE_SMALL);

            $wallImage = '<a class="thumbnail ajax" href="'.$imageBig.'"><img src="'.$imageSmall.'"></a>';
        }

        $htmlDelete = '';
        if ($isOwnWall) {
            $htmlDelete .= '<a title="'.get_lang("SocialMessageDelete").'" href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?messageId='.
            $message['id'].'">x</a>';
        }

        $html = '';
        $html .= '<div class="top-mediapost" >';
        if ($isOwnWall) {
            $html .= '<div class="pull-right deleted-mgs">';
            $html .= $htmlDelete;
            $html .= '</div>';
        }
        $html .= '<div class="user-image" >';
        $html .= '<a href="'.$urlAuthor.'">'.'<img class="avatar-thumb" src="'.$avatarAuthor.'" alt="'.$nameCompleteAuthor.'"></a>';
        $html .= '</div>';
        $html .= '<div class="user-data">';
        $html .= '<div class="username"><a href="'.$urlAuthor.'">'.$nameCompleteAuthor.'</a>'.$htmlReceiver.'</div>';
        $html .= '<div class="time timeago" title="'.$date.'">'.$date.'</div>';
        $html .= '</div>';
        $html .= '<div class="msg-content">';
        $html .= '<div class="img-post">';
        $html .= $wallImage;
        $html .= '</div>';
        $html .= '<p>'. Security::remove_XSS($message['content']).'</p>';
        $html .= '</div>';
        $html .= '</div>'; // end mediaPost

        // Popularity post functionality
        $html .= '<div class="popularity-mediapost"></div>';

        return $html;
    }

    /**
     * get html data with OpenGrap passing the Url
     * @param $link url
     * @return string data html
     */
    public static function readContentWithOpenGraph($link)
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
        $html  = '<div class="thumbnail social-thumbnail">';
        $html .= empty($image) ? '' : '<a target="_blank" href="'.$url.'"><img class="img-responsive social-image" src="'.$image.'" /></a>';
        $html .= '<div class="social-description">';
        $html .= '<a target="_blank" href="'.$url.'"><h5 class="social-title"><b>'.$title.'</b></h5></a>';
        $html .= empty($description) ? '' : '<span>'.$description.'</span>';
        $html .= empty($host) ? '' : '<p>'.$host.'</p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * verify if Url Exist - Using Curl
     * @param $uri url
     *
     * @return boolean
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
        } else {
            return false;
        }
    }

    /**
    * Delete messages delete logic
    * @param int $id id message to delete.
    * @return bool status query
    */
    public static function deleteMessage($id)
    {
        $id = intval($id);
        $tblMessage = Database::get_main_table(TABLE_MESSAGE);
        $statusMessage = MESSAGE_STATUS_WALL_DELETE;
        $sql = "UPDATE $tblMessage SET msg_status = '$statusMessage' WHERE id = '{$id}' ";

        return Database::query($sql);
    }

    /**
     * Generate the social block for a user
     * @param Template $template
     * @param int $userId The user id
     * @param string $groupBlock Optional. Highlight link possible values:
     * group_add, home, messages, messages_inbox, messages_compose,
     * messages_outbox, invitations, shared_profile, friends, groups, search
     * @param int $groupId Optional. Group ID
     * @return string The HTML code with the social block
     */
    public static function setSocialUserBlock(
        Template $template,
        $userId,
        $groupBlock = '',
        $groupId = 0,
        $show_full_profile = true
    ) {
        if (api_get_setting('allow_social_tool') != 'true') {
            return '';
        }

        $currentUserId = api_get_user_id();
        $userId = intval($userId);
        $userRelationType = 0;

        $socialAvatarBlock = SocialManager::show_social_avatar_block(
            $groupBlock,
            $groupId,
            $userId
        );

        $profileEditionLink = null;

        if ($currentUserId === $userId) {
            $profileEditionLink = Display::getProfileEditionLink($userId);
        } else {
            $userRelationType = SocialManager::get_relation_between_contacts(
                $currentUserId,
                $userId
            );
        }

        $vCardUserLink = Display::getVCardUserLink($userId);

        $userInfo = api_get_user_info($userId, true, false, true, true);

        $template->assign('user', $userInfo);
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
     * @param $show_full_profile
     * @return string
     */
    public static function listMyFriends($user_id, $link_shared, $show_full_profile)
    {
        //SOCIALGOODFRIEND , USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_PARENT
        $friends = SocialManager::get_friends($user_id, USER_RELATION_TYPE_FRIEND);
        $number_of_images = 30;
        $number_friends = count($friends);
        $friendHtml = '';
        if ($number_friends != 0) {
            if ($number_friends > $number_of_images) {
                if (api_get_user_id() == $user_id) {
                    $friendHtml.= ' <span><a href="friends.php">'.get_lang('SeeAll').'</a></span>';
                } else {
                    $friendHtml.= ' <span>'
                        .'<a href="'.api_get_path(WEB_CODE_PATH).'social/profile_friends_and_groups.inc.php'
                        .'?view=friends&height=390&width=610&user_id='.$user_id.'"'
                        .'class="ajax" data-title="'.get_lang('SeeAll').'" title="'.get_lang('SeeAll').'" >'.get_lang('SeeAll').'</a></span>';
                }
            }

            $friendHtml.= '<ul class="nav nav-list">';
            $j = 1;
            for ($k=0; $k < $number_friends; $k++) {
                if ($j > $number_of_images) break;

                if (isset($friends[$k])) {
                    $friend = $friends[$k];
                    $name_user = api_get_person_name($friend['firstName'], $friend['lastName']);
                    $user_info_friend = api_get_user_info($friend['friend_user_id'], true);

                    if ($user_info_friend['user_is_online']) {
                        $statusIcon = Display::span('', array('class' => 'online_user_in_text'));
                    } else {
                        $statusIcon = Display::span('', array('class' => 'offline_user_in_text'));
                    }

                    $friendHtml.= '<li>';
                    $friendHtml.= '<div>';

                    // the height = 92 must be the same in the image_friend_network span style in default.css
                    $friends_profile = UserManager::getUserPicture($friend['friend_user_id'], USER_IMAGE_SIZE_SMALL);
                    $friendHtml.= '<img src="'.$friends_profile.'" id="imgfriend_'.$friend['friend_user_id'].'" title="'.$name_user.'"/>';
                    $link_shared = (empty($link_shared)) ? '' : '&'.$link_shared;
                    $friendHtml.= $statusIcon .'<a href="profile.php?' .'u=' . $friend['friend_user_id'] . $link_shared . '">' . $name_user .'</a>';
                    $friendHtml.= '</div>';
                    $friendHtml.= '</li>';
                }
                $j++;
            }
            $friendHtml.='</ul>';
        } else {
            $friendHtml.= '<div class="">'.get_lang('NoFriendsInYourContactList').'<br />'
                .'<a class="btn btn-primary" href="'.api_get_path(WEB_PATH).'whoisonline.php"><em class="fa fa-search"></em> '. get_lang('TryAndFindSomeFriends').'</a></div>';
        }

        $friendHtml = Display::panel($friendHtml, get_lang('SocialFriend').' (' . $number_friends . ')' );

        return $friendHtml;
    }

    /**
     * @param int $user_id
     * @param $link_shared
     * @param $show_full_profile
     * @return string
     */
    public static function listMyFriendsBlock($user_id, $link_shared = '', $show_full_profile = '')
    {
        //SOCIALGOODFRIEND , USER_RELATION_TYPE_FRIEND, USER_RELATION_TYPE_PARENT
        $friends = SocialManager::get_friends($user_id, USER_RELATION_TYPE_FRIEND);
        $number_of_images = 30;
        $number_friends = count($friends);
        $friendHtml = '';

        if ($number_friends != 0) {

            $friendHtml.= '<div class="list-group">';
            $j = 1;
            for ($k=0; $k < $number_friends; $k++) {
                if ($j > $number_of_images) {
                    break;
                }
                if (isset($friends[$k])) {
                    $friend = $friends[$k];
                    $name_user = api_get_person_name($friend['firstName'], $friend['lastName']);
                    $user_info_friend = api_get_user_info($friend['friend_user_id'], true);

                    if ($user_info_friend['user_is_online']) {
                        $statusIcon = Display::return_icon('statusonline.png',get_lang('Online'));
                        $status=1;
                    } else {
                        $statusIcon = Display::return_icon('statusoffline.png',get_lang('Offline'));
                        $status=0;
                    }

                    $friendAvatarMedium = UserManager::getUserPicture($friend['friend_user_id'], USER_IMAGE_SIZE_MEDIUM);
                    $friendAvatarSmall = UserManager::getUserPicture($friend['friend_user_id'], USER_IMAGE_SIZE_SMALL);
                    $friend_avatar = '<img src="'.$friendAvatarMedium.'" id="imgfriend_'.$friend['friend_user_id'].'" title="'.$name_user.'" class="user-image"/>';
                    $showLinkToChat = api_is_global_chat_enabled() &&
                        $friend['friend_user_id'] != api_get_user_id();

                    if ($showLinkToChat){
                        $friendHtml .= '<a onclick="javascript:chatWith(\''.$friend['friend_user_id'].'\', \''.$name_user.'\', \''.$status.'\',\''.$friendAvatarSmall.'\')" href="javascript:void(0);" class="list-group-item">';
                        $friendHtml .=  $friend_avatar.' <span class="username">' . $name_user . '</span>';
                        $friendHtml .= '<span class="status">' . $statusIcon . '</span>';
                    } else {
                        $link_shared = empty($link_shared) ? '' : '&'.$link_shared;
                        $friendHtml .= '<a href="profile.php?' .'u=' . $friend['friend_user_id'] . $link_shared . '" class="list-group-item">';
                        $friendHtml .=  $friend_avatar.' <span class="username-all">' . $name_user . '</span>';
                    }

                    $friendHtml .= '</a>';
                }
                $j++;
            }
            $friendHtml.='</div>';
        } else {
            $friendHtml.= '<div class="help">'.get_lang('NoFriendsInYourContactList').' '
                .'<a href="'.api_get_path(WEB_PATH).'whoisonline.php"><em class="fa fa-search"></em> '. get_lang('TryAndFindSomeFriends').'</a></div>';
        }

        return $friendHtml;
    }

    /**
     * @return string
     */
    public static function getWallForm($show_full_profile = true)
    {
        if ($show_full_profile) {
            $userId = isset($_GET['u']) ? '?u='.intval($_GET['u']) : '';
            $form = new FormValidator(
                'social_wall_main',
                'post',
                api_get_path(WEB_CODE_PATH).'social/profile.php'.$userId,
                null,
                array('enctype' => 'multipart/form-data') ,
                FormValidator::LAYOUT_HORIZONTAL
            );

            $socialWallPlaceholder = isset($_GET['u']) ? get_lang('SocialWallWriteNewPostToFriend') : get_lang('SocialWallWhatAreYouThinkingAbout');

            $form->addTextarea(
                'social_wall_new_msg_main',
                null,
                [
                    'placeholder' => $socialWallPlaceholder,
                    'cols-size' => [1, 10, 1],
                ]
            );
            $form->addHidden('url_content', '');
            $form->addButtonSend(get_lang('Post'), 'wall_post_button', false, ['cols-size' => [1, 10, 1]]);
            $html = Display::panel($form->returnForm(), get_lang('SocialWall'));

            return $html;
        }
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @return string
     */
    public static function getWallMessagesByUser($userId, $friendId)
    {
        $messages = SocialManager::getWallMessagesPostHTML($userId, $friendId);
        $html = '';

        foreach ($messages as $message) {
            $post = $message['html'];
            $comment = SocialManager::getWallMessagesHTML($userId, $friendId, $message['id']);
            $html .= Display::panel($post.$comment, '');
        }

        return $html;
    }

    /**
     * Get HTML code block for user skills
     * @param int $userId The user ID
     * @return string
     */
    public static function getSkillBlock($userId)
    {
        if (api_get_setting('allow_skills_tool') !== 'true') {
            return null;
        }

        $skill = new Skill();
        $ranking = $skill->get_user_skill_ranking($userId);
        $skills = $skill->get_user_skills($userId, true);

        $template = new Template(null, false, false, false, false, false);
        $template->assign('ranking', $ranking);
        $template->assign('skills', $skills);
        $template->assign('user_id', $userId);
        $template->assign(
            'show_skills_report_link',
            api_is_student() || api_is_student_boss() || api_is_drh()
        );

        $skillBlock = $template->get_template('social/skills_block.tpl');

        return $template->fetch($skillBlock);
    }
}
