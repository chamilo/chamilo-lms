<?php
/* For licensing terms, see /license.txt */

/**
 * Announcement Email.
 *
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 * @author Julio Montoya <gugli100@gmail.com> Adding session support
 */
class AnnouncementEmail
{
    public $session_id = null;
    public $logger;
    protected $course = null;
    protected $announcement = null;

    /**
     * @param array           $courseInfo
     * @param int             $sessionId
     * @param int             $announcementId
     * @param \Monolog\Logger $logger
     */
    public function __construct($courseInfo, $sessionId, $announcementId, $logger = null)
    {
        if (empty($courseInfo)) {
            $courseInfo = api_get_course_info();
        }

        $this->course = $courseInfo;
        $this->session_id = empty($sessionId) ? api_get_session_id() : (int) $sessionId;

        if (is_numeric($announcementId)) {
            $this->announcement = AnnouncementManager::get_by_id($courseInfo['real_id'], $announcementId);
        }
        $this->logger = $logger;
    }

    /**
     * Course info.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function course($key = '')
    {
        $result = $key ? $this->course[$key] : $this->course;
        $result = $key == 'id' ? intval($result) : $result;

        return $result;
    }

    /**
     * Announcement info.
     *
     * @param string $key
     *
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
     * session is turned on or not.
     *
     * @return array
     */
    public function all_users()
    {
        $courseCode = $this->course('code');
        if (empty($this->session_id)) {
            $group_id = api_get_group_id();
            if (empty($group_id)) {
                $userList = CourseManager::get_user_list_from_course_code($courseCode);
            } else {
                $userList = GroupManager::get_users($group_id);
                $new_user_list = [];
                foreach ($userList as $user) {
                    $new_user_list[] = ['user_id' => $user];
                }
                $userList = $new_user_list;
            }
        } else {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $this->session_id
            );
        }

        return $userList;
    }

    /**
     * Returns users and groups an announcement item has been sent to.
     *
     * @return array Array of users and groups to whom the element has been sent
     */
    public function sent_to_info()
    {
        $result = [];
        $result['groups'] = [];
        $result['users'] = [];

        $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tool = TOOL_ANNOUNCEMENT;
        $id = $this->announcement('id');
        $course_id = $this->course('real_id');
        $sessionCondition = api_get_session_condition($this->session_id);

        $sql = "SELECT to_group_id, to_user_id
                FROM $table
                WHERE
                    c_id = $course_id AND
                    tool = '$tool' AND
                    ref = $id
                    $sessionCondition";

        $rs = Database::query($sql);

        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            // if to_user_id <> 0 then it is sent to a specific user
            $user_id = $row['to_user_id'];
            if (!empty($user_id)) {
                $result['users'][] = (int) $user_id;
                // If user is set then skip the group
                continue;
            }

            // if to_group_id is null then it is sent to a specific user
            // if to_group_id = 0 then it is sent to everybody
            $group_id = $row['to_group_id'];
            if (!empty($group_id)) {
                $result['groups'][] = (int) $group_id;
            }
        }

        return $result;
    }

    /**
     * Returns the list of user info to which an announcement was sent.
     * This function returns a list of actual users even when recipient
     * are groups.
     *
     * @return array
     */
    public function sent_to()
    {
        $sent_to = $this->sent_to_info();
        $users = $sent_to['users'];
        $users = $users ? $users : [];
        $groups = $sent_to['groups'];

        if ($users) {
            $users = UserManager::get_user_list_by_ids($users, true);
        }

        if (!empty($groups)) {
            $groupUsers = GroupManager::get_groups_users($groups);
            $groupUsers = UserManager::get_user_list_by_ids($groupUsers, true);

            if (!empty($groupUsers)) {
                $users = array_merge($users, $groupUsers);
            }
        }

        if (empty($users)) {
            if (!empty($this->logger)) {
                $this->logger->addInfo('User list is empty. No users found. Trying all_users()');
            }
            $users = self::all_users();
        }

        // Clean users just in case
        $newListUsers = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $newListUsers[$user['user_id']] = ['user_id' => $user['user_id']];
            }
        }

        return $newListUsers;
    }

    /**
     * Email subject.
     *
     * @param bool $directMessage
     *
     * @return string
     */
    public function subject($directMessage = false)
    {
        if ($directMessage) {
            $result = $this->announcement('title');
        } else {
            $result = $this->course('title').' - '.$this->announcement('title');
        }

        $result = stripslashes($result);

        return $result;
    }

    /**
     * Email message.
     *
     * @param int  $receiverUserId
     * @param bool $checkUrls      It checks access url of user when multiple_access_urls = true
     *
     * @return string
     */
    public function message($receiverUserId, $checkUrls = false)
    {
        $content = $this->announcement('content');
        $session_id = $this->session_id;
        $courseCode = $this->course('code');
        $courseId = $this->course('real_id');
        $content = AnnouncementManager::parseContent(
            $receiverUserId,
            $content,
            $courseCode,
            $session_id
        );

        $accessConfig = [];
        $useMultipleUrl = api_get_configuration_value('multiple_access_urls');
        if ($useMultipleUrl && $checkUrls) {
            $accessUrls = api_get_access_url_from_user($receiverUserId, $courseId);
            if (!empty($accessUrls)) {
                $accessConfig['multiple_access_urls'] = true;
                $accessConfig['access_url'] = (int) $accessUrls[0];
            }
        }
        // Build the link by hand because api_get_cidreq() doesn't accept course params
        $course_param = 'cidReq='.$courseCode.'&id_session='.$session_id.'&gidReq='.api_get_group_id();
        $course_name = $this->course('title');
        $result = "<div>$content</div>";

        // Adding attachment
        $attachment = $this->attachment();
        if (!empty($attachment)) {
            $result .= '<br />';
            $result .= Display::url(
                $attachment['filename'],
                api_get_path(WEB_CODE_PATH, $accessConfig).
                'announcements/download.php?file='.basename($attachment['path']).'&'.$course_param
            );
            $result .= '<br />';
        }

        $result .= '<hr />';

        $userInfo = api_get_user_info();
        if (!empty($userInfo)) {
            if ('true' === api_get_setting('show_email_addresses')) {
                $result .= '<a href="mailto:'.$userInfo['mail'].'">'.$userInfo['complete_name'].'</a><br/>';
            } else {
                $result .= '<p>'.$userInfo['complete_name'].'</p><br/>';
            }
        }

        $result .= '<a href="'.api_get_path(WEB_CODE_PATH, $accessConfig).'announcements/announcements.php?'.$course_param.'">'.
            $course_name.'</a><br/>';

        return $result;
    }

    /**
     * Returns the one file that can be attached to an announcement.
     *
     * @return array
     */
    public function attachment()
    {
        $result = [];
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $id = $this->announcement('id');
        $course_id = $this->course('real_id');
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id AND announcement_id = $id ";
        $rs = Database::query($sql);
        $course_path = $this->course('directory');
        while ($row = Database::fetch_array($rs)) {
            $path = api_get_path(SYS_COURSE_PATH).$course_path.'/upload/announcements/'.$row['path'];
            $filename = $row['filename'];
            $result[] = ['path' => $path, 'filename' => $filename];
        }

        $result = $result ? reset($result) : [];

        return $result;
    }

    /**
     * Send announcement by email to myself.
     */
    public function sendAnnouncementEmailToMySelf()
    {
        $userId = api_get_user_id();
        $subject = $this->subject();
        $message = $this->message($userId);
        MessageManager::send_message_simple(
            $userId,
            $subject,
            $message,
            api_get_user_id(),
            false,
            true
        );
    }

    /**
     * Send emails to users.
     *
     * @param bool $sendToUsersInSession
     * @param bool $sendToDrhUsers       send a copy of the message to the DRH users
     * @param int  $senderId             related to the main user
     * @param bool $directMessage
     * @param bool $checkUrls            It checks access url of user when multiple_access_urls = true
     *
     * @return array
     */
    public function send($sendToUsersInSession = false, $sendToDrhUsers = false, $senderId = 0, $directMessage = false, $checkUrls = false)
    {
        $senderId = empty($senderId) ? api_get_user_id() : (int) $senderId;
        $subject = $this->subject($directMessage);
        $courseId = $this->course('real_id');
        // Send email one by one to avoid antispam
        $users = $this->sent_to();

        $batchSize = 20;
        $counter = 1;
        $em = Database::getManager();

        if (empty($users) && !empty($this->logger)) {
            $this->logger->addInfo('User list is empty. No emails will be sent.');
        }
        $messageSentTo = [];
        foreach ($users as $user) {
            $message = $this->message($user['user_id'], $checkUrls);
            $wasSent = MessageManager::messageWasAlreadySent($senderId, $user['user_id'], $subject, $message);
            if ($wasSent === false) {
                if (!empty($this->logger)) {
                    $this->logger->addInfo(
                        'Announcement: #'.$this->announcement('id').'. Send email to user: #'.$user['user_id']
                    );
                }

                $messageSentTo[] = $user['user_id'];
                MessageManager::send_message_simple(
                    $user['user_id'],
                    $subject,
                    $message,
                    $senderId,
                    $sendToDrhUsers,
                    true,
                    [],
                    true,
                    [],
                    $checkUrls,
                    $courseId
                );
            } else {
                if (!empty($this->logger)) {
                    $this->logger->addInfo(
                        'Message "'.$subject.'" was already sent. Announcement: #'.$this->announcement('id').'.
                        User: #'.$user['user_id']
                    );
                }
            }

            if (($counter % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            $counter++;
        }

        if ($sendToUsersInSession) {
            $sessionList = SessionManager::get_session_by_course($this->course['real_id']);
            if (!empty($sessionList)) {
                foreach ($sessionList as $sessionInfo) {
                    $sessionId = $sessionInfo['id'];
                    $message = $this->message(null);
                    $userList = CourseManager::get_user_list_from_course_code(
                        $this->course['code'],
                        $sessionId
                    );
                    if (!empty($userList)) {
                        foreach ($userList as $user) {
                            $messageSentTo[] = $user['user_id'];
                            MessageManager::send_message_simple(
                                $user['user_id'],
                                $subject,
                                $message,
                                $senderId,
                                false,
                                true,
                                [],
                                true,
                                [],
                                $checkUrls,
                                $courseId
                            );
                        }
                    }
                }
            }
        }

        $this->logMailSent();
        $messageSentTo = array_unique($messageSentTo);

        return $messageSentTo;
    }

    /**
     * Store that emails where sent.
     */
    public function logMailSent()
    {
        $id = $this->announcement('id');
        $courseId = $this->course('real_id');
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $sql = "UPDATE $table SET
                email_sent = 1
                WHERE
                    c_id = $courseId AND
                    id = $id AND
                    session_id = {$this->session_id}
                ";
        Database::query($sql);
    }
}
