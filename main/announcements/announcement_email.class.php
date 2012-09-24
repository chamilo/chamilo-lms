<?php

/**
 * Announcement Email
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 * @author Julio Montoya <gugli100@gmail.com> Adding session support
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
    public $session_id = null;

    function __construct($course, $announcement)
    {
        if (empty($course)) {
            $course = api_get_course_int_id();
            $course = CourseManager::get_course_information_by_id($course);
        } else if (is_numeric($course)) {
            $course = CourseManager::get_course_information_by_id(intval($course));
        }
        $this->course = $course;
        $this->session_id = api_get_session_id();
        
        if (is_numeric($announcement)) {            
            $announcement = AnnouncementManager::get_by_id($course['real_id'], $announcement);            
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
        $course_code = $this->course('code');
        if (empty($this->session_id)) {
            $group_id = api_get_group_id();
            if (empty($group_id)) {
                $user_list = CourseManager::get_user_list_from_course_code($course_code);
            } else {
                $user_list = GroupManager::get_users($group_id);
                $new_user_list = array();
                foreach ($user_list as $user) {
                    $new_user_list[] = array('user_id' => $user);
                }
                $user_list = $new_user_list;
            }
        } else {
            $user_list = CourseManager::get_user_list_from_course_code($course_code, $this->session_id);            
        }
        return $user_list;
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

        $sql = "SELECT to_group_id, to_user_id FROM $tbl_item_property WHERE c_id = $course_id AND tool = '$tool' AND ref = $id AND id_session = {$this->session_id} ";
        $rs = Database::query($sql);

        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            // if to_group_id is null then it is sent to a specific user
            // if to_group_id = 0 then it is sent to everybody
            $group_id = $row['to_group_id'];
            if (!empty($group_id)) {
                $result['groups'][] = (int) $group_id;
            }
            // if to_user_id <> 0 then it is sent to a specific user
            $user_id = $row['to_user_id'];
            if (!empty($user_id)) {
                $result['users'][] = (int) $user_id;
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
        $users = $users ? $users : array();
        $groups = $sent_to['groups'];
        
        if ($users) {
            $users = UserManager::get_user_list_by_ids($users, true);
        }       

        if (!empty($groups)) {
            $group_users = GroupManager::get_groups_users($groups);
            $group_users = UserManager::get_user_list_by_ids($group_users, true);
            
            if (!empty($group_users)) {
                $users = array_merge($users, $group_users);            
            }
        }        
              
        if (empty($users)) {
            $users = self::all_users();
        }
        
        //Clean users just in case
        $new_list_users = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                $new_list_users[$user['user_id']] = array('user_id' => $user['user_id']);
            }
        }
        return $new_list_users;
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
        $result = $this->course('title') . ' - ' . $this->announcement('title');
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
        $content = AnnouncementManager::parse_content($content, $this->course('code'));

        $user_email   = $this->sender('mail');
        $course_param = api_get_cidreq();
        $course_name  = $this->course('title');
        
        $result = "<div>$content</div>";
        
        //Adding attachment
        $attachment = $this->attachement();        
        if (!empty($attachment)) {
            $result .= '<br />';            
            $result .= Display::url($attachment['filename'], api_get_path(WEB_CODE_PATH).'announcements/download.php?file='.basename($attachment['path']).'&'.$course_param).'<br />';            
        }
        
        $result .= '<hr />';
        $sender_name = api_get_person_name($this->sender('firstName'), $this->sender('lastName'), PERSON_NAME_EMAIL_ADDRESS);
        $result .= '<a href="mailto:'.$user_email.'">'.$sender_name.'</a><br/>';
        $result .= '<a href="'.api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.$course_param.'">'.$course_name.'</a><br/>';
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
        while ($row = Database::fetch_array($rs)) {
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
        $sender  = $this->sender();                
        $subject = $this->subject();
        $message = $this->message();
    
        // Send email one by one to avoid antispam
        $users = $this->sent_to();
        
        foreach ($users as $user) {
            MessageManager::send_message_simple($user['user_id'], $subject, $message, $sender['user_id']);
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
        $sql = "UPDATE $tbl_announcement SET email_sent=1 WHERE c_id = $course_id AND id=$id AND session_id = {$this->session_id} ";
        Database::query($sql);
    }

}