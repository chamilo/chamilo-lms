<?php

/**
 * Announcement Email
 *
 * @license see /license.txt 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class AnnouncementEmail
{

    /**
     *
     * @param int|array $course
     * @param int|array $annoucement
     * 
     * @return AnnouncementEmail 
     */
    public static function create($course, $announcement)
    {
        return new self($course, $announcement);
    }

    protected $course = null;
    protected $announcement = null;

    function __construct($course, $announcement)
    {
        if (empty($course))
        {
            $course = api_get_course_int_id();
            $course = CourseManager::get_course_information_by_id($course);
        }
        else if (is_numeric($course))
        {
            $course = CourseManager::get_course_information_by_id(intval($course));
        }
        $this->course = $course;

        if (is_numeric($announcement))
        {
            $announcement = AnnouncementManager::get_by_id($course['id'], intval($announcement));
        }
        $this->announcement = $announcement;
    }

    /**
     * Course info
     * 
     * @param string $key
     * @return array 
     */
    public function course($key = '')
    {
        $result = $key ? $this->course[$key] : $this->course;
        $result = $key == 'id' ? intval($result) : $result;
        return $result;
    }

    /**
     * Announcement info
     * 
     * @param string $key
     * @return array 
     */
    public function announcement($key = '')
    {
        $result = $key ? $this->announcement[$key] : $this->announcement;
        $result = $key == 'id' ? intval($result) : $result;
        return $result;
    }

    /**
     * Returns either all course users or all session users depending on whether
     * session is turned on or not
     * 
     * @return array
     */
    public function all_users()
    {
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $course_code = $this->course('code');
        $course_code = Database::escape_string($course_code);

        if (empty($_SESSION['id_session']) || api_get_setting('use_session_mode') == 'false')
        {
            $rel_rh = COURSE_RELATION_TYPE_RRHH;
            $sql = "SELECT user.user_id, user.email, user.lastname, user.firstname
                    FROM $tbl_course_user, $tbl_user
                    WHERE active = 1 AND 
                          course_code='$course_code' AND
                          course_rel_user.user_id = user.user_id AND 
                          relation_type <> $rel_rh";
        }
        else
        {
            $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $session_id = api_get_session_id();
            $sql = "SELECT user.user_id, user.email, user.lastname, user.firstname
                    FROM $tbl_user 
                    INNER JOIN $tbl_session_course_user
                          ON $tbl_user.user_id = $tbl_session_course_user.id_user AND
                             $tbl_session_course_user.course_code = $course_code AND
                             $tbl_session_course_user.id_session = $session_id
                    WHERE
                        active = 1";
        }

        $rs = Database::query($sql);

        $result = array();
        while ($data = Database::fetch_array($rs))
        {
            $result[] = $data;
        }
        return $result;
    }

    /**
     * Returns users and groups an announcement item has been sent to.
     * 
     * @return array Array of users and groups to whom the element has been sent
     */
    public function sent_to_info()
    {
        $result = array();
        $result['groups'] = array();
        $result['users'] = array();

        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tool = TOOL_ANNOUNCEMENT;

        $id = $this->announcement('id');
        $course_id = $this->course('id');

        $sql = "SELECT to_group_id, to_user_id FROM $tbl_item_property WHERE c_id = $course_id AND tool = '$tool' AND ref=$id";
        $rs = Database::query($sql);

        $sent_to_group = array();
        $sent_to_user = array();
        while ($row = Database::fetch_array($rs))
        {
            // if to_group_id is null then it is sent to a specific user
            // if to_group_id = 0 then it is sent to everybody
            $group_id = $row['to_group_id'];
            if (!empty($group_id))
            {
                $result['groups'][] = (int)$group_id;
            }

            // if to_user_id <> 0 then it is sent to a specific user
            $user_id = $row['to_user_id'];
            if (!empty($user_id))
            {
                $result['users'][] = (int)$user_id;
            }
        }
        return $result;
    }

    /**
     * Returns the list of user info to which an announcement was sent.
     * This function returns a list of actual users even when recipient 
     * are groups
     * 
     * @return array
     */
    public function sent_to()
    {
        $sent_to = $this->sent_to_info();
        $users = $sent_to['users'];
        $groups = $sent_to['groups'];

        if (!empty($groups))
        {
            $group_users = GroupManager::get_groups_users($groups);
            $group_users = UserManager::get_user_list_by_ids($group_users, true);
            $users = array_merge($users, $group_users);
        }
        if (empty($users))
        {
            $users = self::all_users();
        }

        return $users;
    }

    /**
     * Sender info
     * 
     * @param string $key
     * @return array 
     */
    public function sender($key = '')
    {
        global $_user;
        return $key ? $_user[$key] : $_user;
    }

    /**
     * Email subject
     * 
     * @return string
     */
    public function subject()
    {
        $result = $this->course('title');
        $result = stripslashes($result);
        return $result;
    }

    /**
     * Email message
     * 
     * @return string
     */
    public function message()
    {
        $title = $this->announcement('title');
        $title = stripslashes($title);

        $content = $this->announcement('content');
        $content = stripslashes($content);

        $user_firstname = $this->sender('firstName');
        $user_lastname = $this->sender('lastName');
        $user_email = $this->sender('mail');

        $www = api_get_path(WEB_CODE_PATH);
        $course_param = api_get_cidreq();
        $course_name = $this->course('name');

        $result = '';
        $result .= "<h1>$title</h1>";
        $result .= "<div>$content</div>";
        $result .= '--';
        $result .= "<a href=\"mailto:$user_email\">$user_firstname $user_lastname</a><br/>";
        $result .= "<a href=\"$www/announcements/announcements.php?$course_param\">$course_name</a><br/>";
        return $result;
    }

    /**
     * Returns the one file that can be attached to an announcement.
     * 
     * @return array
     */
    public function attachement()
    {
        $result = array();
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $id = $this->announcement('id');
        $course_id = $this->course('id');
        $sql = "SELECT * FROM $tbl_announcement_attachment WHERE c_id = $course_id AND announcement_id = $id ";
        $rs = Database::query($sql);
        $course_path = $this->course('directory');
        while ($row = Database::fetch_array($rs))
        {
            $path = api_get_path(SYS_COURSE_PATH) . $course_path . '/upload/announcements/' . $row['path'];
            $filename = $row['filename'];
            $result[] = array('path' => $path, 'filename' => $filename);
        }

        $result = $result ? reset($result) : array();
        return $result;
    }

    /**
     * Send emails to users. 
     */
    public function send()
    {
        $sender = $this->sender();
        $sender_name = api_get_person_name($sender['firstName'], $sender['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
        $sender_email = $sender['mail'];

        $subject = $this->subject();
        $message = $this->message();
        $attachement = $this->attachement();

        // Send email one by one to avoid antispam         
        $users = $this->sent_to();
        foreach ($users as $user)
        {
            $recipient_name = api_get_person_name($user['firstname'], $user['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
            $recipient_email = $user['email'];
            @api_mail_html($recipient_name, $recipient_email, $subject, $message, $sender_name, $sender_email, null, $attachement, true);
        }
        $this->log_mail_sent();
    }

    /**
     * Store that emails where sent 
     */
    public function log_mail_sent()
    {
        $id = $this->announcement('id');
        $course_id = $this->course('id');

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $sql = "UPDATE $tbl_announcement SET email_sent=1 WHERE c_id = $course_id AND id=$id";
        Database::query($sql);
    }

}