<?php
/* For licensing terms, see /license.txt */

// PLUGIN PLACES
define('SOCIAL_LEFT_PLUGIN', 1);
define('SOCIAL_CENTER_PLUGIN', 2);
define('SOCIAL_RIGHT_PLUGIN', 3);
define('CUT_GROUP_NAME', 50);

//This require is necessary because we use constants that need to be loaded before the SocialManager class
require_once api_get_path(LIBRARY_PATH).'message.lib.php';

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
        $sql = 'SELECT id,title FROM '.$tbl_my_friend_relation_type.' WHERE id<>6 ORDER BY id ASC';
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
                WHERE relation_type NOT IN ('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND friend_user_id<>'.((int) $user_id).' AND user_id='.((int) $user_id);
        if (isset($id_group) && $id_group > 0) {
            $sql.=' AND relation_type='.$id_group;
        }
        if (isset($search_name)) {
            $search_name = trim($search_name);
            $search_name = str_replace(' ', '', $search_name);
            $sql.=' AND friend_user_id IN (SELECT user_id FROM '.$tbl_my_user.' WHERE firstName LIKE "%'.Database::escape_string($search_name).'%" OR lastName LIKE "%'.Database::escape_string($search_name).'%"   OR    '.(api_is_western_name_order() ? 'concat(firstName, lastName)' : 'concat(lastName, firstName)').' like concat("%","'.Database::escape_string($search_name).'","%")    ) ';
        }

        $res = Database::query($sql);
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            if ($load_extra_info) {
                $path = UserManager::get_user_picture_path_by_id($row['friend_user_id'], 'web', false, true);
                $my_user_info = api_get_user_info($row['friend_user_id']);
                $list_ids_friends[] = array(
                    'friend_user_id' => $row['friend_user_id'],
                    'firstName' => $my_user_info['firstName'],
                    'lastName' => $my_user_info['lastName'],
                    'username' => $my_user_info['username'],
                    'image' => $path['file']
                );
            } else {
                $list_ids_friends[] = $row;
            }
        }

        return $list_ids_friends;
    }

    /**
     * get list web path of contacts by user id
     * @param int user id
     * @param int group id
     * @param string name to search
     * @param array
     * @author isaac flores paz
     */
    public static function get_list_path_web_by_user_id($user_id, $id_group = null, $search_name = null)
    {
        $combine_friend = array();
        $list_ids = self::get_friends($user_id, $id_group, $search_name);
        if (is_array($list_ids)) {
            foreach ($list_ids as $values_ids) {
                $list_path_image_friend[] = UserManager::get_user_picture_path_by_id($values_ids['friend_user_id'], 'web', false, true);
                $combine_friend = array(
                    'id_friend' => $list_ids,
                    'path_friend' => $list_path_image_friend
                );
            }
        }

        return $combine_friend;
    }

    /**
     * get web path of user invitate
     * @author isaac flores paz
     * @author Julio Montoya setting variable array
     * @param int user id
     * @return array
     */
    public static function get_list_web_path_user_invitation_by_user_id($user_id)
    {
        $list_ids = self::get_list_invitation_of_friends_by_user_id((int) $user_id);
        $list_path_image_friend = array();
        foreach ($list_ids as $values_ids) {
            $list_path_image_friend[] = UserManager::get_user_picture_path_by_id(
                $values_ids['user_sender_id'],
                'web',
                false,
                true
            );
        }
        return $list_path_image_friend;
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
        $tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
        $user_id = intval($user_id);
        $friend_id = intval($friend_id);

        //Just in case we replace the and \n and \n\r while saving in the DB
        $message_content = str_replace(array("\n", "\n\r"), '<br />', $message_content);

        $clean_message_title = Database::escape_string($message_title);
        $clean_message_content = Database::escape_string($message_content);

        $now = api_get_utc_datetime();

        $sql_exist = 'SELECT COUNT(*) AS count FROM '.$tbl_message.'
                      WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status IN(5,6,7);';

        $res_exist = Database::query($sql_exist);
        $row_exist = Database::fetch_array($res_exist, 'ASSOC');

        if ($row_exist['count'] == 0) {

            $sql = ' INSERT INTO '.$tbl_message.'(user_sender_id,user_receiver_id,msg_status,send_date,title,content)
                   VALUES('.$user_id.','.$friend_id.','.MESSAGE_STATUS_INVITATION_PENDING.',"'.$now.'","'.$clean_message_title.'","'.$clean_message_content.'") ';
            Database::query($sql);

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
            //invitation already exist
            $sql_if_exist = 'SELECT COUNT(*) AS count, id FROM '.$tbl_message.'
                             WHERE user_sender_id='.$user_id.' AND user_receiver_id='.$friend_id.' AND msg_status = 7';
            $res_if_exist = Database::query($sql_if_exist);
            $row_if_exist = Database::fetch_array($res_if_exist, 'ASSOC');
            if ($row_if_exist['count'] == 1) {
                $sql = 'UPDATE '.$tbl_message.'SET msg_status=5, content = "'.$clean_message_content.'"
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
        $tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
        $sql = 'SELECT COUNT(*) as count_message_in_box FROM '.$tbl_message.'
                WHERE user_receiver_id='.intval($user_receiver_id).' AND msg_status='.MESSAGE_STATUS_INVITATION_PENDING;
        $res = Database::query($sql);
        $row = Database::fetch_array($res, 'ASSOC');
        return $row['count_message_in_box'];
    }

    /**
     * Get invitation list received by user
     * @author isaac flores paz
     * @param int user id
     * @return array()
     */
    public static function get_list_invitation_of_friends_by_user_id($user_id)
    {
        $tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
        $sql = 'SELECT user_sender_id,send_date,title,content
                FROM '.$tbl_message.'
                WHERE user_receiver_id='.intval($user_id).' AND msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
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
        $tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
        $sql = 'SELECT user_receiver_id, send_date,title,content
                FROM '.$tbl_message.'
                WHERE user_sender_id = '.intval($user_id).' AND msg_status = '.MESSAGE_STATUS_INVITATION_PENDING;
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
        $tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
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
        $tbl_message = Database::get_main_table(TABLE_MAIN_MESSAGE);
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
     * Sends invitations to friends
     * @author Isaac Flores Paz <isaac.flores.paz@gmail.com>
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     * @param void
     * @return string message invitation
     */
    public static function send_invitation_friend_user($userfriend_id, $subject_message = '', $content_message = '')
    {
        global $charset;

        $user_info = api_get_user_info($userfriend_id);
        $succes = get_lang('MessageSentTo');
        $succes.= ' : '.api_get_person_name($user_info['firstName'], $user_info['lastName']);

        if (isset($subject_message) && isset($content_message) && isset($userfriend_id)) {
            $send_message = MessageManager::send_message($userfriend_id, $subject_message, $content_message);

            if ($send_message) {
                echo Display::display_confirmation_message($succes, true);
            } else {
                echo Display::display_error_message(get_lang('ErrorSendingMessage'), true);
            }
            return false;
        } elseif (isset($userfriend_id) && !isset($subject_message)) {
            $count_is_true = false;
            if (isset($userfriend_id) && $userfriend_id > 0) {
                $message_title = get_lang('Invitation');
                $count_is_true = self::send_invitation_friend(api_get_user_id(), $userfriend_id, $message_title, $content_message);

                if ($count_is_true) {
                    echo Display::display_confirmation_message(api_htmlentities(get_lang('InvitationHasBeenSent'), ENT_QUOTES, $charset), false);
                } else {
                    echo Display::display_warning_message(api_htmlentities(get_lang('YouAlreadySentAnInvitation'), ENT_QUOTES, $charset), false);
                }
            }
        }
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
        if (!function_exists('fetch_rss')) {
            return '';
        }
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
            $rss = @fetch_rss($url);
            $i = 1;
            if (!empty($rss->items)) {
                $icon_rss = '';
                if (!empty($feed)) {
                    $icon_rss = Display::url(Display::return_icon('rss.png', '', array(), 32), Security::remove_XSS($feed['rssfeeds']), array('target' => '_blank'));
                }
                $res .= '<h2>'.$rss->channel['title'].''.$icon_rss.'</h2>';
                $res .= '<div class="social-rss-channel-items">';
                foreach ($rss->items as $item) {
                    if ($limit >= 0 and $i > $limit) {
                        break;
                    }
                    $res .= '<h3><a href="'.$item['link'].'">'.$item['title'].'</a></h3>';
                    $res .= '<div class="social-rss-item-date">'.api_get_local_time($item['date_timestamp']).'</div>';
                    $res .= '<div class="social-rss-item-content">'.$item['description'].'</div><br />';
                    $i++;
                }
                $res .= '</div>';
            }
        }
        return $res;
    }

    /**
     * Helper functions definition
     */
    public static function get_logged_user_course_html($my_course, $count)
    {
        global $nosession, $nbDigestEntries, $orderKey, $digest, $thisCourseSysCode;
        if (!$nosession) {
            global $now, $date_start, $date_end;
        }
        //initialise
        $result = '';
        // Table definitions
        $main_user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

        $course_code = $my_course['code'];
        $course_visual_code = $my_course['course_info']['official_code'];
        $course_title = $my_course['course_info']['title'];

        $course_info = Database :: get_course_info($course_code);

        $course_id = $course_info['real_id'];

        $course_access_settings = CourseManager :: get_access_settings($course_code);

        $course_visibility = $course_access_settings['visibility'];

        $user_in_course_status = CourseManager :: get_user_in_course_status(api_get_user_id(), $course_code);
        //function logic - act on the data
        $is_virtual_course = CourseManager :: is_virtual_course_from_system_code($course_code);
        if ($is_virtual_course) {
            // If the current user is also subscribed in the real course to which this
            // virtual course is linked, we don't need to display the virtual course entry in
            // the course list - it is combined with the real course entry.
            $target_course_code = CourseManager :: get_target_of_linked_course($course_code);
            $is_subscribed_in_target_course = CourseManager :: is_user_subscribed_in_course(api_get_user_id(), $target_course_code);
            if ($is_subscribed_in_target_course) {
                return; //do not display this course entry
            }
        }

        $s_htlm_status_icon = Display::return_icon('course.gif', get_lang('Course'));

        //display course entry
        $result .= '<div id="div_'.$count.'">';
        //$result .= '<h3><img src="../img/nolines_plus.gif" id="btn_'.$count.'" onclick="toogle_course(this,\''.$course_id.'\' )">';
        $result .= $s_htlm_status_icon;

        //show a hyperlink to the course, unless the course is closed and user is not course admin
        if ($course_visibility != COURSE_VISIBILITY_HIDDEN && ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER)) {
           //$result .= '<a href="javascript:void(0)" id="ln_'.$count.'"  onclick=toogle_course(this,\''.$course_id.'\');>&nbsp;'.$course_title.'</a>';
           $result .= $course_title;
        } else {
            $result .= $course_title." "." ".get_lang('CourseClosed')."";
        }
        $result .= '</h3>';
        //$current_course_settings = CourseManager :: get_access_settings($my_course['k']);
        // display the what's new icons
        /*if ($nbDigestEntries > 0) {
            reset($digest);
            $result .= '<ul>';
            while (list ($key2) = each($digest[$thisCourseSysCode])) {
                $result .= '<li>';
                if ($orderKey[1] == 'keyTools') {
                    $result .= "<a href=\"$toolsList[$key2] [\"path\"] $thisCourseSysCode \">";
                    $result .= "$toolsList[$key2][\"name\"]</a>";
                } else {
                    $result .= api_convert_and_format_date($key2, DATE_FORMAT_LONG, date_default_timezone_get());
                }
                $result .= '</li>';
                $result .= '<ul>';
                reset($digest[$thisCourseSysCode][$key2]);
                while (list ($key3, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2])) {
                    $result .= '<li>';
                    if ($orderKey[2] == 'keyTools') {
                        $result .= "<a href=\"$toolsList[$key3] [\"path\"] $thisCourseSysCode \">";
                        $result .= "$toolsList[$key3][\"name\"]</a>";
                    } else {
                        $result .= api_convert_and_format_date($key3, DATE_FORMAT_LONG, date_default_timezone_get());
                    }
                    $result .= '<ul compact="compact">';
                    reset($digest[$thisCourseSysCode][$key2][$key3]);
                    while (list ($key4, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2][$key3])) {
                        $result .= '<li>';
                        $result .= htmlspecialchars(substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT));
                        $result .= '</li>';
                    }
                    $result .= '</ul>';
                    $result .= '</li>';
                }
                $result .= '</ul>';
                $result .= '</li>';
            }
            $result .= '</ul>';
        }*/
        $result .= '</li>';
        $result .= '</div>';

        if (!$nosession) {
            $session = '';
            $active = false;
            if (!empty($my_course['session_name'])) {

                // Request for the name of the general coach
                $sql = 'SELECT lastname, firstname
                        FROM '.$tbl_session.' ts  LEFT JOIN '.$main_user_table.' tu
                        ON ts.id_coach = tu.user_id
                        WHERE ts.id='.(int) $my_course['id_session'].' LIMIT 1';
                $rs = Database::query($sql);
                $sessioncoach = Database::store_result($rs);
                $sessioncoach = $sessioncoach[0];

                $session = array();
                $session['title'] = $my_course['session_name'];
                if ($my_course['date_start'] == '0000-00-00') {
                    $session['dates'] = get_lang('WithoutTimeLimits');
                    if (api_get_setting('show_session_coach') === 'true') {
                        $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                    }
                    $active = true;
                } else {
                    $session ['dates'] = ' - '.get_lang('From').' '.$my_course['date_start'].' '.get_lang('To').' '.$my_course['date_end'];
                    if (api_get_setting('show_session_coach') === 'true') {
                        $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                    }
                    $active = ($date_start <= $now && $date_end >= $now) ? true : false;
                }
            }
            $my_course['id_session'] = isset($my_course['id_session']) ? $my_course['id_session'] : 0;
            $output = array($my_course['user_course_cat'], $result, $my_course['id_session'], $session, 'active' => $active);
        } else {
            $output = array($my_course['user_course_cat'], $result);
        }
        //$my_course['creation_date'];
        return $output;
    }

    /**
     * Shows the avatar block in social pages
     *
     * @param string highlight link possible values: group_add, home, messages, messages_inbox, messages_compose ,messages_outbox ,invitations, shared_profile, friends, groups search
     * @param int group id
     * @param int user id
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
            'browse_groups'
        );

        // get count unread message and total invitations
        $count_unread_message = MessageManager::get_number_of_messages(true);
        $count_unread_message = !empty($count_unread_message) ? Display::badge($count_unread_message) : null;

        $number_of_new_messages_of_friend = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
        $group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION, false);
        $group_pending_invitations = count($group_pending_invitations);
        $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
        $total_invitations = (!empty($total_invitations) ? Display::badge($total_invitations) : '');
        $showUserImage = user_is_online($user_id) || api_is_platform_admin();

        $html = '<div>';
        if (in_array($show, $show_groups) && !empty($group_id)) {
            //--- Group image
            $group_info = GroupPortalManager::get_group_data($group_id);
            $big = GroupPortalManager::get_picture_group($group_id, $group_info['picture_uri'], 160, GROUP_IMAGE_SIZE_BIG);

            $html .= '<div class="social-content-image">';
            $html .= '<div class="well social-background-content">';
            $html .= Display::url('<img src='.$big['file'].' class="social-groups-image" /> </a><br /><br />', api_get_path(WEB_PATH).'main/social/groups.php?id='.$group_id);
            if (GroupPortalManager::is_group_admin($group_id, api_get_user_id())) {
                $html .= '<div id="edit_image" class="hidden_message" style="display:none">
                            <a href="'.api_get_path(WEB_PATH).'main/social/group_edit.php?id='.$group_id.'">'.
                    get_lang('EditGroup').'</a></div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        } else {
            if ($showUserImage) {
                $img_array = UserManager::get_user_picture_path_by_id($user_id, 'web', true, true);
            } else {
                $img_array = UserManager::get_user_picture_path_by_id(null, 'web', true, true);
            }
            $big_image = UserManager::get_picture_user($user_id, $img_array['file'], '', USER_IMAGE_SIZE_BIG);
            $big_image = $big_image['file'].'?'.uniqid();
            $normal_image = $img_array['dir'].$img_array['file'].'?'.uniqid();

            //--- User image

            $html .= '<div class="well social-background-content">';
            if ($img_array['file'] != 'unknown.jpg') {
                $html .= '<a class="thumbnail thickbox" href="'.$big_image.'"><img src='.$normal_image.' /> </a>';
            } else {
                $html .= '<img src='.$normal_image.' width="110px" />';
            }
            if (api_get_user_id() == $user_id) {
                $html .= '<div id="edit_image" class="hidden_message" style="display:none">';
                $html .= '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php">'.get_lang('EditProfile').'</a></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
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
        $user_info = api_get_user_info($user_id, true);
        $current_user_id = api_get_user_id();
        $current_user_info = api_get_user_info($current_user_id, true);

        if ($current_user_id == $user_id) {
            $user_friend_relation = null;
        } else {
            $user_friend_relation = SocialManager::get_relation_between_contacts($current_user_id, $user_id);
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
            'browse_groups'
        );

        // get count unread message and total invitations
        $count_unread_message = MessageManager::get_number_of_messages(true);
        $count_unread_message = !empty($count_unread_message) ? Display::badge($count_unread_message) : null;

        $number_of_new_messages_of_friend = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
        $group_pending_invitations = GroupPortalManager::get_groups_by_user(
            api_get_user_id(),
            GROUP_USER_PERMISSION_PENDING_INVITATION,
            false
        );
        $group_pending_invitations = count($group_pending_invitations);
        $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
        $total_invitations = (!empty($total_invitations) ? Display::badge($total_invitations) : '');

        $html = '';

        if (!in_array($show, array('shared_profile', 'groups', 'group_edit', 'member_list', 'waiting_list', 'invite_friends'))) {

            $html .= '<div class="well sidebar-nav"><ul class="nav nav-list">';
            $active = $show == 'home' ? 'active' : null;
            $html .= '<li class="home-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.get_lang('Home').'</a></li>';
            $active = $show == 'messages' ? 'active' : null;
            $html .= '<li class="messages-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.get_lang('Messages').$count_unread_message.'</a></li>';

            //Invitations
            $active = $show == 'invitations' ? 'active' : null;
            $html .= '<li class="invitations-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.get_lang('Invitations').$total_invitations.'</a></li>';

            //Shared profile and groups
            $active = $show == 'shared_profile' ? 'active' : null;
            $html .= '<li class="shared-profile-icon'.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.get_lang('ViewMySharedProfile').'</a></li>';
            $active = $show == 'friends' ? 'active' : null;
            $html .= '<li class="friends-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/friends.php">'.get_lang('Friends').'</a></li>';
            $active = $show == 'browse_groups' ? 'active' : null;
            $html .= '<li class="browse-groups-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/groups.php">'.get_lang('SocialGroups').'</a></li>';

            //Search users
            $active = $show == 'search' ? 'active' : null;
            $html .= '<li class="search-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/search.php">'.get_lang('Search').'</a></li>';

            //My files
            $active = $show == 'myfiles' ? 'active' : null;
            $html .= '<li class="myfiles-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/myfiles.php">'.get_lang('MyFiles').'</span></a></li>';
            $html .='</ul>
                  </div>';
        }

        if (in_array($show, $show_groups) && !empty($group_id)) {
            $html .= GroupPortalManager::show_group_column_information(
                $group_id,
                api_get_user_id(),
                $show
            );
        }

        if ($show == 'shared_profile') {
            $html .= '<div class="well sidebar-nav">
                    <ul class="nav nav-list">';

            // My own profile
            if ($show_full_profile && $user_id == intval(api_get_user_id())) {
                $html .= '<li class="home-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/home.php">'.get_lang('Home').'</a></li>
                          <li class="messages-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.get_lang('Messages').$count_unread_message.'</a></li>';
                $active = $show == 'invitations' ? 'active' : null;
                $html .= '<li class="invitations-icon'.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.get_lang('Invitations').$total_invitations.'</a></li>';

                $html .= '<li class="shared-profile-icon active"><a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.get_lang('ViewMySharedProfile').'</a></li>
                          <li class="friends-icon"><a href="'.api_get_path(WEB_PATH).'main/social/friends.php">'.get_lang('Friends').'</a></li>
                          <li class="browse-groups-icon"><a href="'.api_get_path(WEB_PATH).'main/social/groups.php">'.get_lang('SocialGroups').'</a></li>';
                $active = $show == 'search' ? 'active' : null;
                $html .= '<li class="search-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/search.php">'.get_lang('Search').'</a></li>';
                $active = $show == 'myfiles' ? 'active' : null;
                $html .= '<li class="myfiles-icon '.$active.'"><a href="'.api_get_path(WEB_PATH).'main/social/myfiles.php">'.get_lang('MyFiles').'</a></li>';
            }

            // My friend profile.
            if ($user_id != api_get_user_id()) {
                $html .= '<li><a href="javascript:void(0);" onclick="javascript:send_message_to_user(\''.$user_id.'\');" title="'.get_lang('SendMessage').'">';
                $html .= Display::return_icon('compose_message.png', get_lang('SendMessage')).'&nbsp;&nbsp;'.get_lang('SendMessage').'</a></li>';
            }

            // Check if I already sent an invitation message
            $invitation_sent_list = SocialManager::get_list_invitation_sent_by_user_id(api_get_user_id());

            if (isset($invitation_sent_list[$user_id]) && is_array($invitation_sent_list[$user_id]) && count($invitation_sent_list[$user_id]) > 0) {
                $html .= '<li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('invitation.png', get_lang('YouAlreadySentAnInvitation')).'&nbsp;&nbsp;'.get_lang('YouAlreadySentAnInvitation').'</a></li>';
            } else {
                if (!$show_full_profile) {
                    $html .= '<li><a  href="javascript:void(0);" onclick="javascript:send_invitation_to_user(\''.$user_id.'\');" title="'.get_lang('SendInvitation').'">'.Display :: return_icon('invitation.png', get_lang('SocialInvitationToFriends')).'&nbsp;'.get_lang('SendInvitation').'</a></li>';
                }
            }

            // Chat
            //@todo check if user is online and if it's a friend to show the chat link
            if (api_is_global_chat_enabled()) {
                $user_name = $user_info['complete_name'];

                if ($user_friend_relation == USER_RELATION_TYPE_FRIEND) {
                    if ($user_id != api_get_user_id()) {
                        //Only show chat if I'm available to talk
                        if ($current_user_info['user_is_online_in_chat'] == 1) {
                            $options = array('onclick' => "javascript:chatWith('".$user_id."', '".Security::remove_XSS($user_name)."', '".$user_info['user_is_online_in_chat']."')");
                            $chat_icon = $user_info['user_is_online_in_chat'] ? Display::return_icon('online.png', get_lang('Online')) : Display::return_icon('offline.png', get_lang('Offline'));
                            $html .= Display::tag('li',
                                Display::url(
                                    $chat_icon.'&nbsp;&nbsp;'.get_lang('Chat'),
                                    'javascript:void(0);',
                                    $options
                                )
                            );
                        }
                    }
                } else {
                    if ($user_id != api_get_user_id()) {
                        if ($current_user_info['user_is_online_in_chat'] == 1) {
                            $message = Security::remove_XSS(sprintf(get_lang("YouHaveToAddXAsAFriendFirst"), $user_name));
                            $options = array('onclick' => "javascript:chatNotYetWith('".$message."')");
                            $chat_icon = $user_info['user_is_online_in_chat'] ? Display::return_icon('online.png', get_lang('Online')) : Display::return_icon('offline.png', get_lang('Offline'));
                            $html .= Display::tag('li',
                                Display::url(
                                    $chat_icon.'&nbsp;&nbsp;'.get_lang('Chat'),
                                    'javascript:void(0);',
                                    $options
                                )
                            );
                        }
                    }
                }
            }
            $html .= '</ul></div>';

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
                    //to avoid repeted courses
                    $course_list_code = array_unique_dimensional($course_list_code);
                }

                //-----Announcements
                $my_announcement_by_user_id = intval($user_id);
                $announcements = array();
                foreach ($course_list_code as $course) {
                    $course_info = api_get_course_info($course['code']);
                    if (!empty($course_info)) {
                        $content = AnnouncementManager::get_all_annoucement_by_user_course($course_info['code'], $my_announcement_by_user_id);

                        if (!empty($content)) {
                            $url = Display::url(Display::return_icon('announcement.png', get_lang('Announcements')).$course_info['name'].' ('.$content['count'].')', api_get_path(WEB_CODE_PATH).'announcements/announcements.php?cidReq='.$course['code']);
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
            $html .= '<div class="sidebar-nav"><ul><li>';
            $url = api_get_path(WEB_CODE_PATH).'auth/unsubscribe_account.php';
            $html .= Display::url(Display::return_icon('delete.png', get_lang('Unsubscribe'), array(), ICON_SIZE_TINY).get_lang('Unsubscribe'), $url);
            $html .= '</li></ul></div>';
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
        $column_size = '9';
        $add_row = false;
        if (api_is_anonymous()) {
            $column_size = '12';
            $add_row = true;
        }

        $extra_params = array();
        $course_url = '';
        if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
            $extra_params['cidReq'] = Security::remove_XSS($_GET['cidReq']);
            $course_url = '&amp;cidReq='.Security::remove_XSS($_GET['cidReq']);
        }

        if ($wrap) {
            if ($add_row) {
                $html .='<div class="row">';
            }

            $html .= '<div class="span'.$column_size.'">';

            $html .= '<ul id="online_grid_container" class="thumbnails">';
        }

        foreach ($user_list as $uid) {
            $user_info = api_get_user_info($uid);
            // Anonymous users can't have access to the profile
            if (!api_is_anonymous()) {
                if (api_get_setting('allow_social_tool') == 'true') {
                    $url = api_get_path(WEB_PATH).'main/social/profile.php?u='.$uid.$course_url;
                } else {
                    $url = '?id='.$uid.$course_url;
                }
            } else {
                $url = '#';
            }
            $image_array = UserManager::get_user_picture_path_by_id($uid, 'system', false, true);

            // reduce image
            $name = $user_info['complete_name'];
            $status_icon = Display::span('', array('class' => 'online_user_in_text'));
            $user_status = $user_info['status'] == 1 ? Display::span('', array('class' => 'teacher_online')) : Display::span('', array('class' => 'student_online'));

            if ($image_array['file'] == 'unknown.jpg' || !file_exists($image_array['dir'].$image_array['file'])) {
                $friends_profile['file'] = api_get_path(WEB_CODE_PATH).'img/unknown_180_100.jpg';
                $img = '<img title = "'.$name.'" alt="'.$name.'" src="'.$friends_profile['file'].'">';
            } else {
                $friends_profile = UserManager::get_picture_user($uid, $image_array['file'], 80, USER_IMAGE_SIZE_ORIGINAL);
                $img = '<img title = "'.$name.'" alt="'.$name.'" src="'.$friends_profile['file'].'">';
            }
            $name = '<a href="'.$url.'">'.$status_icon.$user_status.$name.'</a><br>';
            $html .= '<li class="span'.($column_size / 3).'"><div class="thumbnail">'.$img.'<div class="caption">'.$name.'</div</div></li>';
        }
        $counter = $_SESSION['who_is_online_counter'];

        if ($wrap) {
            $html .= '</ul></div>';
        }
        if (count($user_list) >= 9) {
            $html .= '<div class="span'.$column_size.'"><a class="btn btn-large" id="link_load_more_items" data_link="'.$counter.'" >'.get_lang('More').'</a></div>';
        }
        if ($wrap && $add_row) {
            $html .= '</div>';
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

        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT * FROM $user_table WHERE user_id = ".$safe_user_id;
        $result = Database::query($sql);
        $html = null;
        if (Database::num_rows($result) == 1) {
            $user_object = Database::fetch_object($result);
            $alt = GetFullUserName($user_id).($_SESSION['_uid'] == $user_id ? '&nbsp;('.get_lang('Me').')' : '');
            $status = get_status_from_code($user_object->status);
            $interbreadcrumb[] = array('url' => 'whoisonline.php', 'name' => get_lang('UsersOnLineList'));

            $html .= '<div class ="thumbnail">';
            if (strlen(trim($user_object->picture_uri)) > 0) {
                $sysdir_array = UserManager::get_user_picture_path_by_id($safe_user_id, 'system');
                $sysdir = $sysdir_array['dir'];
                $webdir_array = UserManager::get_user_picture_path_by_id($safe_user_id, 'web');
                $webdir = $webdir_array['dir'];
                $fullurl = $webdir.$user_object->picture_uri;
                $system_image_path = $sysdir.$user_object->picture_uri;
                list($width, $height, $type, $attr) = @getimagesize($system_image_path);
                $height += 30;
                $width += 30;
                // get the path,width and height from original picture
                $big_image = $webdir.'big_'.$user_object->picture_uri;
                $big_image_size = api_getimagesize($big_image);
                $big_image_width = $big_image_size['width'];
                $big_image_height = $big_image_size['height'];
                $url_big_image = $big_image.'?rnd='.time();
                //echo '<a href="javascript:void()" onclick="javascript: return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');" >';
                $html .= '<img src="'.$fullurl.'" alt="'.$alt.'" />';
            } else {
                $html .= Display::return_icon('unknown.jpg', get_lang('Unknown'));
            }
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
        $sysdir_array = UserManager::get_user_picture_path_by_id($user_id, 'system', true);
        $sysdir = $sysdir_array['dir'];
        $webdir_array = UserManager::get_user_picture_path_by_id($user_id, 'web', true);
        $webdir = $webdir_array['dir'];

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
    public static function sendWallMessage($userId, $friendId, $messageContent, $messageId = 0 ,$messageStatus)
    {
        $tblMessage = Database::get_main_table(TABLE_MAIN_MESSAGE);
        $userId = intval($userId);
        $friendId = intval($friendId);
        $messageId = intval($messageId);

        //Just in case we replace the and \n and \n\r while saving in the DB
        $messageContent = str_replace(array("\n", "\n\r"), '<br />', $messageContent);
        $cleanMessageContent = Database::escape_string($messageContent);

        $attributes = array(
            'user_sender_id' => $userId,
            'user_receiver_id' => $friendId,
            'msg_status' => $messageStatus,
            'send_date' => api_get_utc_datetime(),
            'title' => '',
            'content' => $cleanMessageContent,
            'parent_id' => $messageId
        );
        return Database::insert($tblMessage, $attributes);

        /* Deprecated since 2014-10-29
        $senderInfo = api_get_user_info($userId);
        $notification = new Notification();
        $notification->save_notification(Notification::NOTIFICATION_TYPE_WALL_MESSAGE, array($friendId), '', $messageContent, $senderInfo);
        */

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
        $flag = false;
        $tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        // create directory
        $pathUserInfo = UserManager::get_user_picture_path_by_id($userId, 'system', true);
        $social = '/social/';
        $pathMessageAttach = $pathUserInfo['dir'] . 'message_attachments'. $social;
        $safeFileComment = Database::escape_string($fileComment);
        $safeFileName = Database::escape_string($fileAttach['name']);

        $extension = strtolower(substr(strrchr($safeFileName, '.'), 1));
        $allowedTypes = getSupportedImageExtensions();
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
            $sql = "INSERT INTO $tbl_message_attach(filename, comment, path, message_id, size)
				  VALUES ( '$safeFileName', '$safeFileComment', '$newFileName' , '$messageId', '".$fileAttach['size']."' )";
            Database::query($sql);
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
        $tblMessage = Database::get_main_table(TABLE_MAIN_MESSAGE);
        $userId = intval($userId);
        $start = Database::escape_string($start);
        // TODO: set a maximum of 3 months for messages
        //if ($start == '0000-00-00') {
        //
        //}
        $limit = intval($limit);
        $messages = array();
        $sql = "SELECT id, user_sender_id,user_receiver_id, send_date, content, parent_id,
          (SELECT ma.path from message_attachment ma WHERE  ma.message_id = tm.id ) as path,
          (SELECT ma.filename from message_attachment ma WHERE  ma.message_id = tm.id ) as filename
            FROM $tblMessage tm
            WHERE user_receiver_id = $userId
                AND send_date > '$start' ";
        $sql .= (empty($messageStatus) || is_null($messageStatus)) ? '' : " AND msg_status = '$messageStatus' ";
        $sql .= (empty($parentId) || is_null($parentId)) ? '' : " AND parent_id = '$parentId' ";
        $sql .= " ORDER BY send_date DESC LIMIT $offset, $limit ";
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
        $formattedList = '<div class="mediaPost" style="width:calc(100%-14px);
        display:block;padding-left:14px">';
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
            $url = api_get_path(WEB_PATH).'main/social/profile.php?u='.$userIdLoop;
            $media = '';
            $media .= '<div class="media" style="width:100%; display:inline-block; margin-bottom:5px;">';
            $media .= '<div class="media-body" style="width: 100%; height: 32px; margin-bottom:5px;">';
            $media .= '<div class="pull-left" style="width: 32px; height: 100%;">';
            $media .= '<a href="'.$url.'" >'
            . '<img class="" src="'. $users[$userIdLoop]['avatar'] .'" '
            . 'alt="'.$users[$userIdLoop]['complete_name'].'" style="width: 32px; height: 32px;"> '
            . '</a>';
            $media .= '</div>';
            $media .= '<div class="pull-left" style="padding-left:4px;width: calc(100% - 36px);height: 100%;">';
            $media .= '<div style="width: 100%; height: 50%;">';
            $media .= '<h4 class="media-heading" style="width: inherit;">'
            . '<a href="'.$url.'">'.$nameComplete.'</a></h4>';
            $media .= '</div>';
            $media .= '<div style="width: 100%; height: 50%;">';
            $media .= '<div class="pull-left" style="height: 100%;">';
            $media .= '<small><span class="time timeago" title="'.$date.'">'.$date.'</span></small>';
            $media .= '</div>';
            $media .= '</div>';
            $media .= '</div>';
            $media .= '</div>';
            if ($isOwnWall) {
                $media .= '<div style="width: 100%;height:20px">';
                $media .= '<div><a href="'.api_get_path(WEB_PATH).'main/social/profile.php?messageId='.
                $message['id'].'">'.get_lang('SocialMessageDelete').'</a></div>';
                $media .= '</div>';
            }
            $media .= '<div style="width:100%;text-align:justify;">';
            $media .= '<span class="content">'.Security::remove_XSS($message['content']).'</span>';
            $media .= '</div>';
            $media .= '</div>'; // end media
            $formattedList .= $media;
        }

        $formattedList .= '</div>';

        $formattedList .= '<div class="mediaPost" style="display:inline-block;">';
            $formattedList .= '<form name="social_wall_message" method="POST">
                <label for="social_wall_new_msg" class="hide">'.get_lang('SocialWriteNewComment').'</label>
                <input type="hidden" name = "messageId" value="'.$idMessage.'" />
                <textarea placeholder="'.get_lang('SocialWriteNewComment').
                '" name="social_wall_new_msg" rows="1" cols="80" style="width: 98%"></textarea>
                <br />
                <input type="submit" name="social_wall_new_msg_submit"
                value="'.get_lang('Post').'" class="float right btn" />
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
        $messages = self::getWallMessages($userId, MESSAGE_STATUS_WALL_POST , null, $start, $limit, $offset);
        $users = array();
        $data = array();
        foreach ($messages as $key => $message) {
            $date = api_get_local_time($message['send_date']);
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


    private  static function headerMessagePost($authorId, $reciverId, $users, $message, $isOwnWall = false)
    {
        $date = api_get_local_time($message['send_date']);
        $avatarAuthor = $users[$authorId]['avatar'];
        $urlAuthor = api_get_path(WEB_PATH).'main/social/profile.php?u='.$authorId;
        $nameCompleteAuthor = api_is_western_name_order()
            ? $users[$authorId]['firstname'] .' ' . $users[$authorId]['lastname']
            : $users[$authorId]['lastname'] . ' ' . $users[$authorId]['firstname'];

        $avatarReciver = $users[$reciverId]['avatar'];
        $urlReciber = api_get_path(WEB_PATH).'main/social/profile.php?u='.$reciverId;
        $nameCompleteReciver = api_is_western_name_order()
            ? $users[$reciverId]['firstname'] .' ' . $users[$reciverId]['lastname']
            : $users[$reciverId]['lastname'] . ' ' . $users[$reciverId]['firstname'];

        $htmlReciber = '';
        if ($authorId != $reciverId) {
            $htmlReciber = ' > <a href="'.$urlReciber.'">' . $nameCompleteReciver . '</a> ';
        }

        $wallImage = '';
        if (!empty($message['path'])) {
            $pathUserInfo = UserManager::get_user_picture_path_by_id($authorId, 'web', true);
            $pathImg = $pathUserInfo['dir'] . 'message_attachments';
            $imageBig = $pathImg .self::getImage($message['path'], IMAGE_WALL_BIG);
            $imageSmall =  $pathImg. self::getImage($message['path'], IMAGE_WALL_SMALL);
            $wallImage = '<a class="thumbnail thickbox" href="'.$imageBig.'"><img src="'.$imageSmall.'"></a>';
        }


        $htmlDelete = '';
        if ($isOwnWall) {
            $htmlDelete .= '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?messageId='.
            $message['id'].'">'.get_lang('SocialMessageDelete').'</a>';
        }

        $html = '';
        $html .= '<div class="mediaPost" style="width: 100%; display:inline-block; margin-bottom:5px;">';
        $html .= '<div class="media-body" style="width: 100%; height: 40px; margin-bottom:5px;">';
        $html .= '<div class="pull-left" style="width: 40px; height: 100%;">';
        $html .= '<a href="'.$urlAuthor.'">'.'<img class="" src="'.$avatarAuthor.
        '" alt="'.$nameCompleteAuthor.'" style="width: 40px; height: 40px;"></a>';
        $html .= '</div>';
        $html .= '<div class="pull-left" style="padding-left:4px; width: calc(100% - 44px);height: 100%;">';
        $html .= '<div style="width: 100%; height: 50%;">';
        $html .= '<h4 class="media-heading" style="width: inherit;">';
        $html .= '<a href="'.$urlAuthor.'">'.$nameCompleteAuthor.'</a>'.$htmlReciber.'</h4>';
        $html .= '</div>';
        $html .= '<div style="width: 100%; height: 50%;">';
        $html .= '<div class="pull-left" style="height: 100%;">';
        $html .= '<small><span class="time timeago" title="'.$date.'">'.$date.'</span></small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        if ($isOwnWall) {
            $html .= '<div style="width: 100%;height:20px">';
            $html .= $htmlDelete;
            $html .= '</div>';
        }
        $html .= '<div style="width: 100%;">';
        $html .= $wallImage;
        $html .= '</div>';
        $html .= '<div style="width:100%;text-align:justify;">';
        $html .= '<span class="content">'.
            Security::remove_XSS(self::readContentWithOpenGraph($message['content'])).'</span>';
        $html .= '</div>';
        $html .= '</div>'; // end mediaPost

        return $html;
    }

    /**
     * Get schedule html (with data openGrap)
     * @param $text content text
     */
    public function readContentWithOpenGraph($text)
    {
        // search link in first line
        $regExUrl = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        $newText = '';
        $count = 0;
        if(preg_match($regExUrl, $text, $url)) {
            // make the urls hyper links
            $newText .= preg_replace($regExUrl, "<a target=\"_blank\" href=" . $url[0] . ">".$url[0]."</a> ", $text);
            if ($count == 0) {
                // Comment this line to disable OpenGraph
                //$newText .= self::getHtmlByLink($url[0]);
            }
            $count++;
        } else {
            $newText .= $text;
        }

        return $newText;
    }

    /**
     * html with data OpenGrap
     * @param $link url
     * @return string data html
     */
    public function getHtmlByLink($link)
    {
        $graph = OpenGraph::fetch($link);
        $title = $graph->title;
        $html = '<div>';
        $html .= '<a target="_blank" href="'.$link.'"><h3>'.$title.'</h3>';
        $html .= empty($graph->image) ? '' : '<img alt="" src="'.$graph->image.'" height="160" ></a>';
        $html .= empty($graph->description) ? '' : '<div>'.$graph->description.'</div>';
        $html .= "</div>";

        return $html;
    }


    /**
     * Get name img by sizes
     * @param string$path
     * @return string
     */
    private static function getImage($path, $size = '')
    {
        $name = '';
        $array = preg_split('#\/#', $path);
        if (isset($array[2]) && !empty($array[2])) {

            if ($size == IMAGE_WALL_SMALL) {
                $name = IMAGE_WALL_SMALL. '_' . $array[2];
            }else if($size == IMAGE_WALL_MEDIUM){
                $name = IMAGE_WALL_MEDIUM. '_' . $array[2];
            }else if($size == IMAGE_WALL_BIG){
                $name = IMAGE_WALL_BIG. '_' . $array[2];
            }else {
                $name = IMAGE_WALL_SMALL. '_' . $array[2];
            }
            $lessImage = str_replace($array[2], '', $path);
            $name = $lessImage . $name;
        }

        return $name;
    }
    /**
    * Delete messages delete logic
    * @param int $id indice message to delete.
    * @return status query
    */
    public static function deleteMessage($id)
    {
        $id = intval($id);
        $tblMessage = Database::get_main_table(TABLE_MESSAGE);
        $statusMessage = MESSAGE_STATUS_WALL_DELETE;
        $sql = "UPDATE $tblMessage SET msg_status = '$statusMessage' WHERE id = '{$id}' ";
        return Database::query($sql);
    }

}
