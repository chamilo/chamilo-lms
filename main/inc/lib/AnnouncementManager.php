<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CItemProperty;

/**
 * Include file with functions for the announcements module.
 * @author jmontoya
 * @package chamilo.announcements
 * @todo use OOP
 */
class AnnouncementManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getTags()
    {
        $tags = [
            '((user_name))',
            '((user_firstname))',
            '((user_lastname))',
            '((user_official_code))',
            '((course_title))',
            '((course_link))'
        ];

        $tags[] = '((teachers))';

        if (!empty(api_get_session_id())) {
            $tags[] = '((coaches))';
            $tags[] = '((general_coach))';
        }

        return $tags;
    }

    /**
     * @param int $userId
     * @param string $content
     * @param string $courseCode
     * @param int $sessionId
     *
     * @return string
     */
    public static function parse_content(
        $userId,
        $content,
        $courseCode,
        $sessionId = 0
    ) {
        $readerInfo = api_get_user_info($userId);
        $courseInfo = api_get_course_info($courseCode);
        $teacherList = CourseManager::get_teacher_list_from_course_code_to_string(
            $courseInfo['code']
        );

        $generalCoach = '';
        $coaches = '';
        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);

            $coaches = CourseManager::get_coachs_from_course_to_string(
                $sessionId,
                $courseInfo['real_id']
            );

            $generalCoach = api_get_user_info($sessionInfo['id_coach']);
            $generalCoach = $generalCoach['complete_name'];
        }

        $data = [];
        $data['user_name'] = '';
        $data['user_firstname'] = '';
        $data['user_lastname'] = '';
        $data['user_official_code'] = '';
        if (!empty($readerInfo)) {
            $data['user_name'] = $readerInfo['username'];
            $data['user_firstname'] = $readerInfo['firstname'];
            $data['user_lastname'] = $readerInfo['lastname'];
            $data['user_official_code'] = $readerInfo['official_code'];
        }

        $data['course_title'] = $courseInfo['name'];
        $courseLink = api_get_course_url($courseCode, $sessionId);
        $data['course_link'] = Display::url($courseLink, $courseLink);
        $data['teachers'] = $teacherList;

        if (!empty(api_get_session_id())) {
            $data['coaches'] = $coaches;
            $data['general_coach'] = $generalCoach;
        }

        $content = str_replace(self::getTags(), $data, $content);

        return $content;
    }

    /**
     * Gets all announcements from a course
     * @param array $course_info
     * @param int $session_id
     * @return array html with the content and count of announcements or false otherwise
     */
    public static function get_all_annoucement_by_course($course_info, $session_id = 0)
    {
        $session_id = intval($session_id);
        $course_id = $course_info['real_id'];

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT DISTINCT 
                    announcement.id, 
                    announcement.title, 
                    announcement.content
				FROM $tbl_announcement announcement 
				INNER JOIN $tbl_item_property i
				ON (announcement.id = i.ref AND announcement.c_id = i.c_id)
				WHERE
                    i.tool='announcement' AND
                    announcement.session_id  = '$session_id' AND
                    announcement.c_id = $course_id AND
                    i.c_id = $course_id
				ORDER BY display_order DESC";
        $rs = Database::query($sql);
        $num_rows = Database::num_rows($rs);
        if ($num_rows > 0) {
            $list = array();
            while ($row = Database::fetch_array($rs)) {
                $list[] = $row;
            }

            return $list;
        }

        return false;
    }

    /**
     * This functions switches the visibility a course resource
     * using the visibility field in 'item_property'
     * @param    array $_course
     * @param    int     $id ID of the element of the corresponding type
     * @return   bool    False on failure, True on success
     */
    public static function change_visibility_announcement($_course, $id)
    {
        $session_id = api_get_session_id();
        $item_visibility = api_get_item_visibility(
            $_course,
            TOOL_ANNOUNCEMENT,
            $id,
            $session_id
        );
        if ($item_visibility == '1') {
            api_item_property_update(
                $_course,
                TOOL_ANNOUNCEMENT,
                $id,
                'invisible',
                api_get_user_id()
            );
        } else {
            api_item_property_update(
                $_course,
                TOOL_ANNOUNCEMENT,
                $id,
                'visible',
                api_get_user_id()
            );
        }

        return true;
    }

    /**
     * Deletes an announcement
     * @param array $_course the course array
     * @param int $id the announcement id
     */
    public static function delete_announcement($_course, $id)
    {
        api_item_property_update(
            $_course,
            TOOL_ANNOUNCEMENT,
            $id,
            'delete',
            api_get_user_id()
        );
    }

    /**
     * Deletes all announcements by course
     * @param array $_course the course array
     */
    public static function delete_all_announcements($_course)
    {
        $announcements = self::get_all_annoucement_by_course($_course, api_get_session_id());
        if (!empty($announcements)) {
            foreach ($announcements as $annon) {
                api_item_property_update(
                    $_course,
                    TOOL_ANNOUNCEMENT,
                    $annon['id'],
                    'delete',
                    api_get_user_id()
                );
            }
        }
    }

    /**
     * @param string $title
     * @param int $courseId
     * @param int $sessionId
     * @param int $visibility 1 or 0
     *
     * @return mixed
     */
    public static function getAnnouncementsByTitle($title, $courseId, $sessionId = 0, $visibility = 1)
    {
        $dql = "SELECT a
                FROM ChamiloCourseBundle:CAnnouncement a 
                JOIN ChamiloCourseBundle:CItemProperty ip
                WITH a.id = ip.ref AND a.cId = ip.course
                WHERE
                    ip.tool = 'announcement' AND                        
                    a.cId = :course AND
                    a.sessionId = :session AND
                    a.title like :title AND
                    ip.visibility = :visibility
                ORDER BY a.displayOrder DESC";

        $qb = Database::getManager()->createQuery($dql);
        $result = $qb->execute(
            [
                'course' => $courseId,
                'session' => $sessionId,
                'visibility' => $visibility,
                'title' => "%$title%",
            ]
        );

        return $result;
    }

    /**
     * @param int $announcementId
     * @param int $courseId
     * @param int $userId
     *
     * @return array
     */
    public static function getAnnouncementInfoById($announcementId, $courseId, $userId)
    {
        if (api_is_allowed_to_edit(false, true) ||
            (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
        ) {
            $dql = "SELECT a, ip
                    FROM ChamiloCourseBundle:CAnnouncement a 
                    JOIN ChamiloCourseBundle:CItemProperty ip
                    WITH a.id = ip.ref AND a.cId = ip.course
                    WHERE                        
                        a.id = :announcement AND
                        ip.tool = 'announcement' AND                        
                        a.cId = :course
                    ORDER BY a.displayOrder DESC";
        } else {
            $group_list = GroupManager::get_group_ids($courseId, api_get_user_id());

            if (empty($group_list)) {
                $group_list[] = 0;
            }

            if (api_get_user_id() != 0) {
                $dql = "SELECT a, ip
                        FROM ChamiloCourseBundle:CAnnouncement a 
                        JOIN ChamiloCourseBundle:CItemProperty ip
                        WITH a.id = ip.ref AND a.cId = ip.course
                        WHERE                      
                            a.id = :announcement AND
                            ip.tool='announcement' AND
                            (
                                ip.toUser = $userId OR
                                ip.group IN ('0', '".implode("', '", $group_list)."') OR
                                ip.group IS NULL
                            ) AND
                            ip.visibility = '1' AND                       
                            ip.course = :course
                        ORDER BY a.displayOrder DESC";
            } else {
                $dql = "SELECT a, ip
                        FROM ChamiloCourseBundle:CAnnouncement a 
                        JOIN ChamiloCourseBundle:CItemProperty ip
                        WITH a.id = ip.ref AND a.cId = ip.course 
                        WHERE                            
                            a.id = :announcement AND
                            ip.tool = 'announcement' AND
                            (ip.group = '0' OR ip.group IS NULL) AND
                            ip.visibility = '1' AND                            
                            ip.course = :course";
            }
        }

        $qb = Database::getManager()->createQuery($dql);
        $result = $qb->execute(
            [
                'announcement' => $announcementId,
                'course' => $courseId,
            ]
        );

        return [
            'announcement' => $result[0],
            'item_property' => $result[1]
        ];
    }

    /**
     * Displays one specific announcement
     * @param int $id, the id of the announcement you want to display
     *
     * @return string
     */
    public static function displayAnnouncement($id)
    {
        if ($id != strval(intval($id))) {
            return null;
        }

        global $charset;

        $html = '';
        $result = self::getAnnouncementInfoById(
            $id,
            api_get_course_int_id(),
            api_get_user_id()
        );
        /** @var CAnnouncement $announcement */
        $announcement = $result['announcement'];
        /** @var CItemProperty $itemProperty */
        $itemProperty = $result['item_property'];

        if (empty($announcement) || empty($itemProperty)) {
            return '';
        }

        $title = $announcement->getTitle();
        $content = $announcement->getContent();

        $html .= "<table height=\"100\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" class=\"data_table\">";
        $html .= "<tr><td><h2>".$title."</h2></td></tr>";

        if (api_is_allowed_to_edit(false, true) ||
            (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
        ) {
            $modify_icons = "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=modify&id=".$id."\">".
                Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL)."</a>";

            if ($itemProperty->getVisibility() === 1) {
                $image_visibility = 'visible';
                $alt_visibility = get_lang('Hide');
            } else {
                $image_visibility = 'invisible';
                $alt_visibility = get_lang('Visible');
            }
            global $stok;

            $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=showhide&id=".$id."&sec_token=".$stok."\">".
                Display::return_icon($image_visibility.'.png', $alt_visibility, '', ICON_SIZE_SMALL)."</a>";

            if (api_is_allowed_to_edit(false, true)) {
                $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=delete&id=".$id."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset))."')) return false;\">".
                    Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).
                    "</a>";
            }
            $html .= "<tr><th style='text-align:right'>$modify_icons</th></tr>";
        }

        $toUser = $itemProperty->getToUser();
        $toUserId = !empty($toUser) ? $toUser->getId() : 0;

        $content = self::parse_content(
            $toUserId,
            $content,
            api_get_course_id(),
            api_get_session_id()
        );

        $lastEdit = $itemProperty->getLasteditDate();

        $html .= "<tr><td>$content</td></tr>";
        $html .= "<tr><td class=\"announcements_datum\">".get_lang('LastUpdateDate')." : ".
            Display::dateToStringAgoAndLongDate(
                !empty($lastEdit) ? $lastEdit->format('Y-m-d h:i:s') : ''
            )."</td></tr>";

        if ($itemProperty->getGroup() !== null) {
            $sent_to_icon = Display::return_icon('group.gif', get_lang('AnnounceSentToUserSelection'));
        }

        if (api_is_allowed_to_edit(false, true)) {
            $sent_to = self::sent_to('announcement', $id);
            $sent_to_form = self::sent_to_form($sent_to);
            $html .= Display::tag(
                'td',
                get_lang('SentTo').': '.$sent_to_form,
                array('class' => 'announcements_datum')
            );
        }
        $attachment_list = self::get_attachment($id);

        if (count($attachment_list) > 0) {
            $html .= "<tr><td>";
            $realname = $attachment_list['path'];
            $user_filename = $attachment_list['filename'];
            $full_file_name = 'download.php?'.api_get_cidreq().'&file='.$realname;
            $html .= '<br/>';
            $html .= Display::return_icon('attachment.gif', get_lang('Attachment'));
            $html .= '<a href="'.$full_file_name.' "> '.$user_filename.' </a>';
            $html .= ' - <span class="forum_attach_comment" >'.$attachment_list['comment'].'</span>';
            if (api_is_allowed_to_edit(false, true)) {
                $html .= Display::url(
                    Display::return_icon('delete.png', get_lang('Delete'), '', 16),
                    api_get_self()."?".api_get_cidreq()."&action=delete_attachment&id_attach=".$attachment_list['id']."&sec_token=".$stok
                );
            }
            $html .= '</td></tr>';
        }
        $html .= "</table>";

        return $html;
    }

    /**
     * @param array $courseInfo
     *
     * @return int
     */
    public static function get_last_announcement_order($courseInfo)
    {
        if (empty($courseInfo)) {
            return 0;
        }
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $course_id = $courseInfo['real_id'];
        $sql = "SELECT MAX(display_order)
                FROM $tbl_announcement
                WHERE c_id = $course_id ";
        $res_max = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res_max)) {
            $row_max = Database::fetch_array($res_max);
            $order = intval($row_max[0]) + 1;
        }

        return $order;
    }

    /**
     * Store an announcement in the database (including its attached file if any)
     * @param array $courseInfo
     * @param int $sessionId
     * @param string $title   Announcement title (pure text)
     * @param string $newContent   Content of the announcement (can be HTML)
     * @param array  $sentTo      Array of users and groups to send the announcement to
     * @param array   $file     uploaded file $_FILES
     * @param string  $file_comment  Comment describing the attachment
     * @param string $end_date
     * @param bool $sendToUsersInSession
     * @param int $authorId
     *
     * @return int      false on failure, ID of the announcement on success
     */
    public static function add_announcement(
        $courseInfo,
        $sessionId,
        $title,
        $newContent,
        $sentTo,
        $file = array(),
        $file_comment = null,
        $end_date = null,
        $sendToUsersInSession = false,
        $authorId = 0
    ) {
        if (empty($courseInfo)) {
            return false;
        }

        $course_id = $courseInfo['real_id'];
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $authorId = empty($authorId) ? api_get_user_id() : $authorId;

        if (empty($end_date)) {
            $end_date = api_get_utc_datetime();
        }

        $order = self::get_last_announcement_order($courseInfo);

        // store in the table announcement
        $params = array(
            'c_id' => $course_id,
            'content' => $newContent,
            'title' => $title,
            'end_date' => $end_date,
            'display_order' => $order,
            'session_id' => (int) $sessionId
        );

        $last_id = Database::insert($tbl_announcement, $params);

        if (empty($last_id)) {
            return false;
        } else {
            $sql = "UPDATE $tbl_announcement SET id = iid WHERE iid = $last_id";
            Database::query($sql);

            if (!empty($file)) {
                self::add_announcement_attachment_file(
                    $last_id,
                    $file_comment,
                    $_FILES['user_upload']
                );
            }

            // store in item_property (first the groups, then the users
            if (empty($sentTo) || (!empty($sentTo) && isset($sentTo[0]) && $sentTo[0] == 'everyone')) {
                // The message is sent to EVERYONE, so we set the group to 0
                api_item_property_update(
                    $courseInfo,
                    TOOL_ANNOUNCEMENT,
                    $last_id,
                    'AnnouncementAdded',
                    $authorId,
                    '0',
                    null,
                    null,
                    null,
                    $sessionId
                );
            } else {
                $send_to = CourseManager::separateUsersGroups($sentTo);
                $batchSize = 20;
                $em = Database::getManager();
                // Storing the selected groups
                if (is_array($send_to['groups']) && !empty($send_to['groups'])) {
                    $counter = 1;
                    foreach ($send_to['groups'] as $group) {
                        $groupInfo = GroupManager::get_group_properties($group);
                        api_item_property_update(
                            $courseInfo,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            'AnnouncementAdded',
                            $authorId,
                            $groupInfo
                        );

                        if (($counter % $batchSize) === 0) {
                             $em->flush();
                             $em->clear();
                        }
                        $counter++;
                    }
                }

                // Storing the selected users
                if (is_array($send_to['users'])) {
                    $counter = 1;
                    foreach ($send_to['users'] as $user) {
                        api_item_property_update(
                            $courseInfo,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            'AnnouncementAdded',
                            $authorId,
                            '',
                            $user
                        );

                        if (($counter % $batchSize) === 0) {
                             $em->flush();
                             $em->clear();
                        }
                        $counter++;
                    }
                }
            }

            if ($sendToUsersInSession) {
                self::addAnnouncementToAllUsersInSessions($last_id);
            }

            return $last_id;
        }
    }

    /**
     * @param $title
     * @param $newContent
     * @param $to
     * @param $to_users
     * @param array $file
     * @param string $file_comment
     * @param bool $sendToUsersInSession
     *
     * @return bool|int
     */
    public static function add_group_announcement(
        $title,
        $newContent,
        $to,
        $to_users,
        $file = array(),
        $file_comment = '',
        $sendToUsersInSession = false
    ) {
        $_course = api_get_course_info();

        // Database definitions
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $order = self::get_last_announcement_order($_course);

        $now = api_get_utc_datetime();
        $course_id = api_get_course_int_id();

        // store in the table announcement
        $params = [
            'c_id' => $course_id,
            'content' => $newContent,
            'title' => $title,
            'end_date' => $now,
            'display_order' => $order,
            'session_id' => api_get_session_id()
        ];

        $last_id = Database::insert($tbl_announcement, $params);

        // Store the attach file
        if ($last_id) {
            $sql = "UPDATE $tbl_announcement SET id = iid WHERE iid = $last_id";
            Database::query($sql);

            if (!empty($file)) {
                self::add_announcement_attachment_file(
                    $last_id,
                    $file_comment,
                    $file
                );
            }

            // Store in item_property (first the groups, then the users)
            //if (!isset($to_users)) {
            if (isset($to_users[0]) && $to_users[0] === 'everyone') {
                // when no user is selected we send it to everyone
                $send_to = CourseManager::separateUsersGroups($to);
                // storing the selected groups
                if (is_array($send_to['groups'])) {
                    foreach ($send_to['groups'] as $group) {
                        $groupInfo = GroupManager::get_group_properties($group);
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $last_id,
                            'AnnouncementAdded',
                            api_get_user_id(),
                            $groupInfo
                        );
                    }
                }
            } else {
                $send_to_groups = CourseManager::separateUsersGroups($to);
                $send_to_users = CourseManager::separateUsersGroups($to_users);
                $to_groups = $send_to_groups['groups'];
                $to_users = $send_to_users['users'];
                // storing the selected users
                if (is_array($to_users) && is_array($to_groups)) {
                    foreach ($to_groups as $group) {
                        $groupInfo = GroupManager::get_group_properties($group);
                        foreach ($to_users as $user) {
                            api_item_property_update(
                                $_course,
                                TOOL_ANNOUNCEMENT,
                                $last_id,
                                'AnnouncementAdded',
                                api_get_user_id(),
                                $groupInfo,
                                $user
                            );
                        }
                    }
                }
            }

            if ($sendToUsersInSession) {
                self::addAnnouncementToAllUsersInSessions($last_id);
            }
        }

        return $last_id;
    }

    /**
     * This function stores the announcement item in the announcement table
     * and updates the item_property table
     *
     * @param int   $id id of the announcement
     * @param string $title
     * @param string $newContent
     * @param array $to users that will receive the announcement
     * @param mixed $file attachment
     * @param string $file_comment file comment
     * @param bool $sendToUsersInSession
     */
    public static function edit_announcement(
        $id,
        $title,
        $newContent,
        $to,
        $file = array(),
        $file_comment = '',
        $sendToUsersInSession = false
    ) {
        $_course = api_get_course_info();
        $course_id = api_get_course_int_id();
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);

        $id = intval($id);

        $params = [
            'title' => $title,
            'content' => $newContent
        ];

        Database::update(
            $tbl_announcement,
            $params,
            ['c_id = ? AND id = ?' => [$course_id, $id]]
        );

        // save attachment file
        $row_attach = self::get_attachment($id);

        $id_attach = 0;
        if ($row_attach) {
            $id_attach = intval($row_attach['id']);
        }

        if (!empty($file)) {
            if (empty($id_attach)) {
                self::add_announcement_attachment_file($id, $file_comment, $file);
            } else {
                self::edit_announcement_attachment_file($id_attach, $file, $file_comment);
            }
        }

        // we remove everything from item_property for this
        $sql = "DELETE FROM $tbl_item_property
                WHERE c_id = $course_id AND ref='$id' AND tool='announcement'";
        Database::query($sql);

        if ($sendToUsersInSession) {
            self::addAnnouncementToAllUsersInSessions($id);
        }

        // store in item_property (first the groups, then the users
        if (!is_null($to)) {
            // !is_null($to): when no user is selected we send it to everyone
            $send_to = CourseManager::separateUsersGroups($to);

            // storing the selected groups
            if (is_array($send_to['groups'])) {
                foreach ($send_to['groups'] as $group) {
                    $groupInfo = GroupManager::get_group_properties($group);
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $id,
                        'AnnouncementUpdated',
                        api_get_user_id(),
                        $groupInfo
                    );
                }
            }

            // storing the selected users
            if (is_array($send_to['users'])) {
                foreach ($send_to['users'] as $user) {
                    api_item_property_update(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $id,
                        'AnnouncementUpdated',
                        api_get_user_id(),
                        0,
                        $user
                    );
                }
            }

            // Send to everyone
            if (isset($to[0]) && $to[0] === 'everyone') {
                api_item_property_update(
                    $_course,
                    TOOL_ANNOUNCEMENT,
                    $id,
                    'AnnouncementUpdated',
                    api_get_user_id(),
                    0
                );
            }
        } else {
            // the message is sent to everyone, so we set the group to 0
            api_item_property_update(
                $_course,
                TOOL_ANNOUNCEMENT,
                $id,
                'AnnouncementUpdated',
                api_get_user_id(),
                0
            );
        }
    }

    /**
     * @param int $announcementId
     */
    public static function addAnnouncementToAllUsersInSessions($announcementId)
    {
        $courseCode = api_get_course_id();
        $_course = api_get_course_info();
        $sessionList = SessionManager::get_session_by_course(api_get_course_int_id());

        if (!empty($sessionList)) {
            foreach ($sessionList as $sessionInfo) {
                $sessionId = $sessionInfo['id'];
                $userList = CourseManager::get_user_list_from_course_code(
                    $courseCode,
                    $sessionId
                );

                if (!empty($userList)) {
                    foreach ($userList as $user) {
                        api_item_property_update(
                            $_course,
                            TOOL_ANNOUNCEMENT,
                            $announcementId,
                            "AnnouncementUpdated",
                            api_get_user_id(),
                            0,
                            $user['user_id'],
                            0,
                            0,
                            $sessionId
                        );
                    }
                }
            }
        }
    }

    /**
     * @param int $insert_id
     * @return bool
     */
    public static function update_mail_sent($insert_id)
    {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        if ($insert_id != strval(intval($insert_id))) {
            return false;
        }
        $insert_id = intval($insert_id);
        $course_id = api_get_course_int_id();
        // store the modifications in the table tbl_annoucement
        $sql = "UPDATE $tbl_announcement SET email_sent='1'
                WHERE c_id = $course_id AND id = $insert_id";
        Database::query($sql);
    }

    /**
     * Gets all announcements from a user by course
     * @param string course db
     * @param int user id
     * @return array html with the content and count of announcements or false otherwise
     */
    public static function get_all_annoucement_by_user_course($course_code, $user_id)
    {
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        if (empty($user_id)) {
            return false;
        }
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        if (!empty($user_id) && is_numeric($user_id)) {
            $user_id = (int) $user_id;
            $sql = "SELECT DISTINCT 
                        announcement.title, 
                        announcement.content, 
                        display_order
					FROM $tbl_announcement announcement 
					INNER JOIN $tbl_item_property ip
					ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
					WHERE
						announcement.c_id = $course_id AND
						ip.c_id = $course_id AND						
						ip.tool='announcement' AND
						(
						  ip.insert_user_id='$user_id' AND
						  (ip.to_group_id='0' OR ip.to_group_id IS NULL)
						)
						AND ip.visibility='1'
						AND announcement.session_id  = 0
					ORDER BY display_order DESC";
            $rs = Database::query($sql);
            $num_rows = Database::num_rows($rs);
            $content = '';
            $i = 0;
            $result = array();
            if ($num_rows > 0) {
                while ($myrow = Database::fetch_array($rs)) {
                    $content .= '<strong>'.$myrow['title'].'</strong><br /><br />';
                    $content .= $myrow['content'];
                    $i++;
                }
                $result['content'] = $content;
                $result['count'] = $i;

                return $result;
            }

            return false;
        }

        return false;
    }

    /**
     * Returns announcement info from its id
     *
     * @param int $course_id
     * @param int $annoucement_id
     * @return array
     */
    public static function get_by_id($course_id, $annoucement_id)
    {
        $annoucement_id = intval($annoucement_id);
        $course_id = $course_id ? intval($course_id) : api_get_course_int_id();

        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT DISTINCT 
                    announcement.id, 
                    announcement.title, 
                    announcement.content
               FROM $tbl_announcement announcement
               INNER JOIN $tbl_item_property ip
               ON
                    announcement.id = ip.ref AND
                    announcement.c_id = ip.c_id
               WHERE
                    announcement.c_id = $course_id AND
                    ip.tool='announcement' AND
                    announcement.id = $annoucement_id
                ";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::fetch_array($result);
        }
        return [];
    }

    /**
     * this function gets all the groups of the course,
     * not including linked courses
     */
    public static function get_course_groups()
    {
        $session_id = api_get_session_id();
        if ($session_id != 0) {
            $new_group_list = CourseManager::get_group_list_of_course(
                api_get_course_id(),
                $session_id,
                1
            );
        } else {
            $new_group_list = CourseManager::get_group_list_of_course(
                api_get_course_id(),
                0,
                1
            );
        }
        return $new_group_list;
    }

    /**
     * This tools loads all the users and all the groups who have received
     * a specific item (in this case an announcement item)
     * @param string $tool
     * @param int $id
     * @return array
     */
    public static function load_edit_users($tool, $id)
    {
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tool = Database::escape_string($tool);
        $id = intval($id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $tbl_item_property
                WHERE c_id = $course_id AND tool='$tool' AND ref = $id";
        $result = Database::query($sql);
        $to = array();
        while ($row = Database::fetch_array($result)) {
            $to_group = $row['to_group_id'];
            switch ($to_group) {
                // it was send to one specific user
                case null:
                    $to[] = "USER:".$row['to_user_id'];
                    break;
                // it was sent to everyone
                case 0:
                    return "everyone";
                    break;
                default:
                    $to[] = "GROUP:".$row['to_group_id'];
            }
        }

        return $to;
    }

    /**
     * constructs the form to display all the groups and users the message has been sent to
     * input:    $sent_to_array is a 2 dimensional array containing the groups and the users
     *            the first level is a distinction between groups and users:
     *            $sent_to_array['groups'] * and $sent_to_array['users']
     *            $sent_to_array['groups'] (resp. $sent_to_array['users']) is also an array
     *            containing all the id's of the groups (resp. users) who have received this message.
     * @author Patrick Cool <patrick.cool@>
     */
    public static function sent_to_form($sent_to_array)
    {
        // we find all the names of the groups
        $group_names = self::get_course_groups();

        // we count the number of users and the number of groups
        if (isset($sent_to_array['users'])) {
            $number_users = count($sent_to_array['users']);
        } else {
            $number_users = 0;
        }
        if (isset($sent_to_array['groups'])) {
            $number_groups = count($sent_to_array['groups']);
        } else {
            $number_groups = 0;
        }
        $total_numbers = $number_users + $number_groups;

        // starting the form if there is more than one user/group
        $output = array();
        if ($total_numbers > 1) {
            // outputting the name of the groups
            if (is_array($sent_to_array['groups'])) {
                foreach ($sent_to_array['groups'] as $group_id) {
                    $output[] = $group_names[$group_id]['name'];
                }
            }

            if (isset($sent_to_array['users'])) {
                if (is_array($sent_to_array['users'])) {
                    foreach ($sent_to_array['users'] as $user_id) {
                        $user_info = api_get_user_info($user_id);
                        $output[] = $user_info['complete_name_with_username'];
                    }
                }
            }
        } else {
            // there is only one user/group
            if (isset($sent_to_array['users']) and is_array($sent_to_array['users'])) {
                $user_info = api_get_user_info($sent_to_array['users'][0]);
                $output[] = api_get_person_name($user_info['firstname'], $user_info['lastname']);
            }
            if (isset($sent_to_array['groups']) and
                is_array($sent_to_array['groups']) and
                isset($sent_to_array['groups'][0]) and
                $sent_to_array['groups'][0] !== 0
            ) {
                $group_id = $sent_to_array['groups'][0];
                $output[] = "&nbsp;".$group_names[$group_id]['name'];
            }
            if (empty($sent_to_array['groups']) and empty($sent_to_array['users'])) {
                $output[] = "&nbsp;".get_lang('Everybody');
            }
        }

        if (!empty($output)) {
            $output = array_filter($output);
            if (count($output) > 0) {
                $output = implode(', ', $output);
            }
            return $output;
        }
    }

    /**
     * Returns all the users and all the groups a specific announcement item
     * has been sent to
     * @param    string  The tool (announcement, agenda, ...)
     * @param    int     ID of the element of the corresponding type
     * @return   array   Array of users and groups to whom the element has been sent
     */
    public static function sent_to($tool, $id)
    {
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $tool = Database::escape_string($tool);
        $id = (int) $id;

        $sent_to_group = array();
        $sent_to = array();
        $course_id = api_get_course_int_id();

        $sql = "SELECT to_group_id, to_user_id
                FROM $tbl_item_property
                WHERE c_id = $course_id AND tool = '$tool' AND ref=".$id;
        $result = Database::query($sql);

        while ($row = Database::fetch_array($result)) {
            // if to_user_id <> 0 then it is sent to a specific user
            if ($row['to_user_id'] <> 0) {
                $sent_to_user[] = $row['to_user_id'];
                continue;
            }

            // if to_group_id is null then it is sent to a specific user
            // if to_group_id = 0 then it is sent to everybody
            if ($row['to_group_id'] != 0) {
                $sent_to_group[] = $row['to_group_id'];
            }
        }

        if (isset($sent_to_group)) {
            $sent_to['groups'] = $sent_to_group;
        }

        if (isset($sent_to_user)) {
            $sent_to['users'] = $sent_to_user;
        }

        return $sent_to;
    }

    /**
     * Show a list with all the attachments according to the post's id
     * @param int $announcementId
     * @return array with the post info
     * @author Arthur Portugal
     * @version November 2009, dokeos 1.8.6.2
     */
    public static function get_attachment($announcementId)
    {
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $announcementId = intval($announcementId);
        $course_id = api_get_course_int_id();
        $row = array();
        $sql = 'SELECT id, path, filename, comment 
                FROM ' . $tbl_announcement_attachment.'
				WHERE c_id = ' . $course_id.' AND announcement_id = '.$announcementId;
        $result = Database::query($sql);
        if (Database::num_rows($result) != 0) {
            $row = Database::fetch_array($result, 'ASSOC');
        }
        return $row;
    }

    /**
     * This function add a attachment file into announcement
     * @param int  announcement id
     * @param string file comment
     * @param array  uploaded file $_FILES
     * @return int  -1 if failed, 0 if unknown (should not happen), 1 if success
     */
    public static function add_announcement_attachment_file($announcement_id, $file_comment, $file)
    {
        $_course = api_get_course_info();
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $return = 0;
        $announcement_id = intval($announcement_id);
        $course_id = api_get_course_int_id();

        if (is_array($file) && $file['error'] == 0) {
            // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
            $courseDir = $_course['path'].'/upload/announcements';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = add_ext_on_mime(stripslashes($file['name']), $file['type']);
            // user's file name
            $file_name = $file['name'];

            if (!filter_extension($new_file_name)) {
                $return = -1;
                echo Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error');
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir.'/'.$new_file_name;

                // This file is copy here but its cleaned in api_mail_html in api.lib.php
                copy($file['tmp_name'], $new_path);

                $params = [
                    'c_id' => $course_id,
                    'filename' => $file_name,
                    'comment' => $file_comment,
                    'path' => $new_file_name,
                    'announcement_id' => $announcement_id,
                    'size' => intval($file['size']),
                ];

                $insertId = Database::insert($tbl_announcement_attachment, $params);
                if ($insertId) {
                    $sql = "UPDATE $tbl_announcement_attachment SET id = iid WHERE iid = $insertId";
                    Database::query($sql);
                }

                $return = 1;
            }
        }

        return $return;
    }

    /**
     * This function edit a attachment file into announcement
     * @param int attach id
     * @param array uploaded file $_FILES
     * @param string file comment
     * @return int
     */
    public static function edit_announcement_attachment_file($id_attach, $file, $file_comment)
    {
        $_course = api_get_course_info();
        $tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $return = 0;
        $course_id = api_get_course_int_id();

        if (is_array($file) && $file['error'] == 0) {
            // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
            $courseDir = $_course['path'].'/upload/announcements';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = add_ext_on_mime(stripslashes($file['name']), $file['type']);
            // user's file name
            $file_name = $file ['name'];

            if (!filter_extension($new_file_name)) {
                $return = -1;
                echo Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error');
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir.'/'.$new_file_name;
                copy($file['tmp_name'], $new_path);
                $safe_file_comment = Database::escape_string($file_comment);
                $safe_file_name = Database::escape_string($file_name);
                $safe_new_file_name = Database::escape_string($new_file_name);
                $id_attach = intval($id_attach);
                $sql = "UPDATE $tbl_announcement_attachment SET 
                            filename = '$safe_file_name', 
                            comment = '$safe_file_comment', 
                            path = '$safe_new_file_name', 
                            size ='".intval($file['size'])."'
					 	WHERE c_id = $course_id AND id = '$id_attach'";
                $result = Database::query($sql);
                if ($result === false) {
                    $return = -1;
                    echo Display::return_message(get_lang('UplUnableToSaveFile'), 'error');
                } else {
                    $return = 1;
                }
            }
        }
        return $return;
    }

    /**
     * This function delete a attachment file by id
     * @param integer $id attachment file Id
     * @return bool
     */
    public static function delete_announcement_attachment_file($id)
    {
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $id = intval($id);
        $course_id = api_get_course_int_id();
        if (empty($course_id) || empty($id)) {
            return false;
        }
        $sql = "DELETE FROM $table
                WHERE c_id = $course_id AND id = $id";
        Database::query($sql);

        return true;
    }

    /**
     * @param array $courseInfo
     * @param int $sessionId
     * @param int $id
     * @param bool $sendToUsersInSession
     * @param bool $sendToDrhUsers
     */
    public static function sendEmail(
        $courseInfo,
        $sessionId,
        $id,
        $sendToUsersInSession = false,
        $sendToDrhUsers = false
    ) {
        $email = AnnouncementEmail::create($courseInfo, $sessionId, $id);
        $email->send($sendToUsersInSession, $sendToDrhUsers);
    }

    /**
     * @param $stok
     * @param $announcement_number
     * @param bool $getCount
     * @param null $start
     * @param null $limit
     * @param string $sidx
     * @param string $sord
     * @param string $titleToSearch
     * @param int $userIdToSearch
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     * @return array
     */
    public static function getAnnouncements(
        $stok,
        $announcement_number,
        $getCount = false,
        $start = null,
        $limit = null,
        $sidx = '',
        $sord = '',
        $titleToSearch = '',
        $userIdToSearch = 0,
        $userId = 0,
        $courseId = 0,
        $sessionId = 0
    ) {
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $user_id = $userId ?: api_get_user_id();
        $group_id = api_get_group_id();
        $session_id = $sessionId ?: api_get_session_id();
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'announcement.session_id'
        );
        $course_id = $courseId ?: api_get_course_int_id();
        $_course = api_get_course_info();

        $group_memberships = GroupManager::get_group_ids($course_id, api_get_user_id());
        $allowUserEditSetting = api_get_course_setting('allow_user_edit_announcement');

        $select = ' DISTINCT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date';
        if ($getCount) {
            $select = ' COUNT(DISTINCT announcement.iid) count';
        }

        $searchCondition = '';
        if (!empty($titleToSearch)) {
            $titleToSearch = Database::escape_string($titleToSearch);
            $searchCondition .= " AND (title LIKE '%$titleToSearch%')";
        }

        if (!empty($userIdToSearch)) {
            $userIdToSearch = intval($userIdToSearch);
            $searchCondition .= " AND (ip.insert_user_id = $userIdToSearch)";
        }

        if (api_is_allowed_to_edit(false, true) ||
            ($allowUserEditSetting && !api_is_anonymous())
        ) {
            // A.1. you are a course admin with a USER filter
            // => see only the messages of this specific user + the messages of the group (s)he is member of.

            //if (!empty($user_id)) {
            if (0) {
                if (is_array($group_memberships) && count($group_memberships) > 0) {
                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement 
                            INNER JOIN $tbl_item_property ip
                            ON (announcement.id = ip.ref AND ip.c_id = announcement.c_id)
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND                                
                                ip.tool = 'announcement' AND
                                (
                                    ip.to_user_id = $user_id OR
                                    ip.to_group_id IS NULL OR
                                    ip.to_group_id IN (0, ".implode(", ", $group_memberships).")
                                ) AND
                                ip.visibility IN ('1', '0')
                                $condition_session
                                $searchCondition
                            ORDER BY display_order DESC";
                } else {
                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement 
                            INNER JOIN $tbl_item_property ip
                            ON (announcement.id = ip.ref AND ip.c_id = announcement.c_id)
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND
                                ip.tool ='announcement' AND
                                (ip.to_user_id = $user_id OR ip.to_group_id='0' OR ip.to_group_id IS NULL) AND
                                ip.visibility IN ('1', '0')
                            $condition_session
                            $searchCondition
                            ORDER BY display_order DESC";
                }
            } elseif ($group_id != 0) {
                // A.2. you are a course admin with a GROUP filter
                // => see only the messages of this specific group
                $sql = "SELECT $select
                        FROM $tbl_announcement announcement 
                        INNER JOIN $tbl_item_property ip
                        ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                        WHERE
                            ip.tool='announcement' AND
                            announcement.c_id = $course_id AND
                            ip.c_id = $course_id AND
                            ip.visibility<>'2' AND
                            (ip.to_group_id = $group_id OR ip.to_group_id='0' OR ip.to_group_id IS NULL)
                            $condition_session
                            $searchCondition
                        ORDER BY display_order DESC";
                //GROUP BY ip.ref
            } else {
                // A.3 you are a course admin without any group or user filter
                // A.3.a you are a course admin without user or group filter but WITH studentview
                // => see all the messages of all the users and groups without editing possibilities
                if (isset($isStudentView) && $isStudentView == "true") {
                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement 
                            INNER JOIN $tbl_item_property ip
                            ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                            WHERE
                                ip.tool='announcement' AND
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND                                
                                ip.visibility='1'
                                $condition_session
                                $searchCondition
                            ORDER BY display_order DESC";

                    //GROUP BY ip.ref
                } else {
                    // A.3.a you are a course admin without user or group filter and WTIHOUT studentview (= the normal course admin view)
                    // => see all the messages of all the users and groups with editing possibilities
                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement 
                            INNER JOIN $tbl_item_property ip
                            ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                            WHERE
                                ip.tool = 'announcement' AND
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id  AND
                                (ip.visibility='0' OR ip.visibility='1')
                                $condition_session
                                $searchCondition
                            ORDER BY display_order DESC";
                }
            }
        } else {
            // STUDENT
            if (is_array($group_memberships) && count($group_memberships) > 0) {
                if ($allowUserEditSetting && !api_is_anonymous()) {
                    if ($group_id == 0) {
                        // No group
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$user_id."' OR (
                                (ip.to_user_id='$user_id' OR ip.to_user_id IS NULL) OR
                                (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))
                            )
                        ) ";
                    } else {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$user_id."' OR ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".$group_id.")
                        )";
                    }
                } else {
                    if ($group_id == 0) {
                        $cond_user_id = " AND (
                            (ip.to_user_id='$user_id' OR ip.to_user_id IS NULL) AND (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))
                        ) ";
                    } else {
                       $cond_user_id = " AND (
                            (ip.to_user_id='$user_id' OR ip.to_user_id IS NULL) AND (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".$group_id."))
                        )";
                    }
                }

                $sql = "SELECT $select
                        FROM $tbl_announcement announcement INNER JOIN
                        $tbl_item_property ip
                        ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                        WHERE
                            announcement.c_id = $course_id AND
                            ip.c_id = $course_id AND                            
                            ip.tool='announcement' 
                            $cond_user_id
                            $condition_session
                            $searchCondition
                            AND ip.visibility='1'
                        ORDER BY display_order DESC";
            } else {
                if ($user_id) {
                    if ($allowUserEditSetting && !api_is_anonymous()) {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".api_get_user_id()."' OR
                            ((ip.to_user_id='$user_id' OR ip.to_user_id IS NULL) AND (ip.to_group_id='0' OR ip.to_group_id IS NULL))
                        ) ";
                    } else {
                        $cond_user_id = " AND ((ip.to_user_id='$user_id' OR ip.to_user_id IS NULL) AND (ip.to_group_id='0' OR ip.to_group_id IS NULL) ) ";
                    }

                    $sql = "SELECT $select
						FROM $tbl_announcement announcement 
						INNER JOIN $tbl_item_property ip
						ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
						WHERE
    						announcement.c_id = $course_id AND
							ip.c_id = $course_id AND    						
    						ip.tool='announcement'
    						$cond_user_id
    						$condition_session
    						$searchCondition
    						AND ip.visibility='1'
    						AND announcement.session_id IN(0, ".$session_id.")
						ORDER BY display_order DESC";
                } else {
                    if (($allowUserEditSetting && !api_is_anonymous())) {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$user_id."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL
                        )";
                    } else {
                        $cond_user_id = " AND ip.to_group_id='0' OR ip.to_group_id IS NULL ";
                    }

                    $sql = "SELECT $select
                            FROM $tbl_announcement announcement 
                            INNER JOIN $tbl_item_property ip
                            ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND                            
                                ip.tool='announcement'
                                $cond_user_id
                                $condition_session
                                $searchCondition  AND
                                ip.visibility='1' AND
                                announcement.session_id IN ( 0,".api_get_session_id().")";
                }
            }
        }

        if (!is_null($start) && !is_null($limit)) {
            $start = intval($start);
            $limit = intval($limit);
            $sql .= " LIMIT $start, $limit";
        }

        $result = Database::query($sql);
        if ($getCount) {
            $result = Database::fetch_array($result, 'ASSOC');

            return $result['count'];
        }

        $iterator = 1;
        $bottomAnnouncement = $announcement_number;
        $displayed = [];
        $results = [];
        $actionUrl = api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq();
        while ($myrow = Database::fetch_array($result, 'ASSOC')) {
            if (!in_array($myrow['id'], $displayed)) {
                $sent_to_icon = '';
                // the email icon
                if ($myrow['email_sent'] == '1') {
                    $sent_to_icon = ' '.Display::return_icon('email.gif', get_lang('AnnounceSentByEmail'));
                }
                $groupReference = ($myrow['to_group_id'] > 0) ? ' <span class="label label-info">'.get_lang('Group').'</span> ' : '';
                $title = $myrow['title'].$groupReference.$sent_to_icon;
                $item_visibility = api_get_item_visibility($_course, TOOL_ANNOUNCEMENT, $myrow['id'], $session_id);
                $myrow['visibility'] = $item_visibility;

                // show attachment list
                $attachment_list = self::get_attachment($myrow['id']);

                $attachment_icon = '';
                if (count($attachment_list) > 0) {
                    $attachment_icon = ' '.Display::return_icon('attachment.gif', get_lang('Attachment'));
                }

                /* TITLE */
                $user_info = api_get_user_info($myrow['insert_user_id']);
                $username = sprintf(get_lang("LoginX"), $user_info['username']);
                $username_span = Display::tag('span', api_get_person_name($user_info['firstName'], $user_info['lastName']), array('title'=>$username));
                $title = Display::url($title.$attachment_icon, $actionUrl.'&action=view&id='.$myrow['id']);
                //$html .= Display::tag('td', $username_span, array('class' => 'announcements-list-line-by-user'));
                //$html .= Display::tag('td', api_convert_and_format_date($myrow['insert_date'], DATE_TIME_FORMAT_LONG), array('class' => 'announcements-list-line-datetime'));

                $modify_icons = '';
                // we can edit if : we are the teacher OR the element belongs to
                // the session we are coaching OR the option to allow users to edit is on
                if (api_is_allowed_to_edit(false, true) ||
                    (api_is_session_general_coach() && api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $myrow['id']))
                    || (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
                ) {
                    $modify_icons = "<a href=\"".$actionUrl."&action=modify&id=".$myrow['id']."\">".
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL)."</a>";
                    if ($myrow['visibility'] == 1) {
                        $image_visibility = "visible";
                        $alt_visibility = get_lang('Hide');
                    } else {
                        $image_visibility = "invisible";
                        $alt_visibility = get_lang('Visible');
                    }
                    $modify_icons .= "<a href=\"".$actionUrl."&action=showhide&id=".$myrow['id']."&sec_token=".$stok."\">".
                        Display::return_icon($image_visibility.'.png', $alt_visibility, '', ICON_SIZE_SMALL)."</a>";

                    // DISPLAY MOVE UP COMMAND only if it is not the top announcement
                    if ($iterator != 1) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=move&up=".$myrow["id"]."&sec_token=".$stok."\">".
                            Display::return_icon('up.gif', get_lang('Up'))."</a>";
                    } else {
                        $modify_icons .= Display::return_icon('up_na.gif', get_lang('Up'));
                    }
                    if ($iterator < $bottomAnnouncement) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=move&down=".$myrow["id"]."&sec_token=".$stok."\">".
                            Display::return_icon('down.gif', get_lang('Down'))."</a>";
                    } else {
                        $modify_icons .= Display::return_icon('down_na.gif', get_lang('Down'));
                    }
                    if (api_is_allowed_to_edit(false, true)) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=delete&id=".$myrow['id']."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, api_get_system_encoding()))."')) return false;\">".
                            Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).
                            "</a>";
                    }
                    $iterator++;
                } else {
                    $modify_icons = Display::url(
                        Display::return_icon('default.png'),
                        $actionUrl.'&action=view&id='.$myrow['id']
                    );
                }

                $announcement = [
                    'id' => $myrow["id"],
                    'title' => $title,
                    'username' => $username_span,
                    'insert_date' => api_convert_and_format_date($myrow['insert_date'], DATE_TIME_FORMAT_LONG),
                    'actions' => $modify_icons
                ];

                $results[] = $announcement;
            }
            $displayed[] = $myrow['id'];
        }

        return $results;
    }

    /**
     * @return int
     */
    public static function getNumberAnnouncements()
    {
        // Maximum title messages to display
        $maximum = '12';
        // Database Table Definitions
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $session_id = api_get_session_id();
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $userId = api_get_user_id();
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'announcement.session_id'
        );

        if (api_is_allowed_to_edit(false, true)) {
            // check teacher status
            if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {

                if (api_get_group_id() == 0) {
                    $group_condition = '';
                } else {
                    $group_condition = " AND (ip.to_group_id='".api_get_group_id()."' OR ip.to_group_id = 0 OR ip.to_group_id IS NULL)";
                }

                $sql = "SELECT 
                            announcement.*, 
                            ip.visibility, 
                            ip.to_group_id, 
                            ip.insert_user_id
                        FROM $tbl_announcement announcement 
                        INNER JOIN $tbl_item_property ip
                        ON (announcement.c_id = ip.c_id AND announcement.id = ip.ref)
                        WHERE
                            announcement.c_id = $course_id AND
                            ip.c_id = $course_id AND                    
                            ip.tool = 'announcement' AND
                            ip.visibility <> '2'
                            $group_condition
                            $condition_session
                        GROUP BY ip.ref
                        ORDER BY display_order DESC
                        LIMIT 0, $maximum";
            }
        } else {
            // students only get to see the visible announcements
            if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
                $group_memberships = GroupManager::get_group_ids($_course['real_id'], $userId);

                if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
                    if (api_get_group_id() == 0) {
                        $cond_user_id = " AND (
                        ip.lastedit_user_id = '".$userId."' OR (
                            ip.to_user_id='".$userId."' OR
                            ip.to_group_id IN (0, ".implode(", ", $group_memberships).") OR
                            ip.to_group_id IS NULL
                            )
                        )
                        ";
                    } else {
                        $cond_user_id = " AND (
                            ip.lastedit_user_id = '".$userId."'OR
                            ip.to_group_id IN (0, ".api_get_group_id().") OR
                            ip.to_group_id IS NULL
                        )";
                    }
                } else {
                    if (api_get_group_id() == 0) {
                        $cond_user_id = " AND (
                            ip.to_user_id='".$userId."' OR
                            ip.to_group_id IN (0, ".implode(", ", $group_memberships).") OR
                            ip.to_group_id IS NULL
                        ) ";
                    } else {
                        $cond_user_id = " AND (
                            ip.to_user_id='".$userId."' OR
                            ip.to_group_id IN (0, ".api_get_group_id().") OR
                            ip.to_group_id IS NULL
                        ) ";
                    }
                }

                // the user is member of several groups => display personal announcements AND
                // his group announcements AND the general announcements
                if (is_array($group_memberships) && count($group_memberships) > 0) {
                    $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                            FROM $tbl_announcement announcement 
                            INNER JOIN $tbl_item_property ip
                            ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                            WHERE
                                announcement.c_id = $course_id AND
                                ip.c_id = $course_id AND                                
                                ip.tool='announcement' AND 
                                ip.visibility='1'
                                $cond_user_id
                                $condition_session
                            GROUP BY ip.ref
                            ORDER BY display_order DESC
                            LIMIT 0, $maximum";
                } else {
                    // the user is not member of any group
                    // this is an identified user => show the general announcements AND his personal announcements
                    if ($userId) {
                        if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
                            $cond_user_id = " AND (
                                ip.lastedit_user_id = '".$userId."' OR
                                ( ip.to_user_id='".$userId."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL)
                            ) ";
                        } else {
                            $cond_user_id = " AND ( ip.to_user_id='".$userId."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL) ";
                        }
                        $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                                FROM $tbl_announcement announcement 
                                INNER JOIN $tbl_item_property ip
                                ON (announcement.c_id = ip.c_id AND announcement.id = ip.ref)
                                WHERE
                                    announcement.c_id = $course_id AND
                                    ip.c_id = $course_id AND 
                                    ip.tool='announcement' AND 
                                    ip.visibility='1'
                                    $cond_user_id
                                    $condition_session
                                GROUP BY ip.ref
                                ORDER BY display_order DESC
                                LIMIT 0, $maximum";
                    } else {

                        if (api_get_course_setting('allow_user_edit_announcement')) {
                            $cond_user_id = " AND (
                                ip.lastedit_user_id = '".api_get_user_id()."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL
                            ) ";
                        } else {
                            $cond_user_id = " AND ip.to_group_id='0' ";
                        }

                        // the user is not identiefied => show only the general announcements
                        $sql = "SELECT 
                                    announcement.*, 
                                    ip.visibility, 
                                    ip.to_group_id, 
                                    ip.insert_user_id
                                FROM $tbl_announcement announcement 
                                INNER JOIN $tbl_item_property ip
                                ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                                WHERE
                                    announcement.c_id = $course_id AND
                                    ip.c_id = $course_id AND 
                                    ip.tool='announcement' AND 
                                    ip.visibility='1' AND 
                                    ip.to_group_id='0'
                                    $condition_session
                                GROUP BY ip.ref
                                ORDER BY display_order DESC
                                LIMIT 0, $maximum";
                    }
                }
            }
        }

        $result = Database::query($sql);

        return Database::num_rows($result);
    }
}
