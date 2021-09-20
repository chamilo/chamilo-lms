<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;

/**
 * Include file with functions for the announcements module.
 *
 * @author jmontoya
 *
 * @todo use OOP
 */
class AnnouncementManager
{
    /**
     * Constructor.
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
            '((user_email))',
            '((user_firstname))',
            '((user_lastname))',
            '((user_official_code))',
            '((course_title))',
            '((course_link))',
        ];

        $tags[] = '((teachers))';

        $extraField = new ExtraField('user');
        $extraFields = $extraField->get_all(['filter = ?' => 1]);
        if (!empty($extraFields)) {
            foreach ($extraFields as $extra) {
                $tags[] = "((extra_".$extra['variable']."))";
            }
        }
        $sessionId = api_get_session_id();
        if (!empty($sessionId)) {
            $tags[] = '((coaches))';
            $tags[] = '((general_coach))';
            $tags[] = '((general_coach_email))';
        }

        return $tags;
    }

    /**
     * @param int    $userId
     * @param string $content
     * @param string $courseCode
     * @param int    $sessionId
     *
     * @return string
     */
    public static function parseContent(
        $userId,
        $content,
        $courseCode,
        $sessionId = 0
    ) {
        $readerInfo = api_get_user_info($userId, false, false, true, true);
        $courseInfo = api_get_course_info($courseCode);
        $teacherList = CourseManager::getTeacherListFromCourseCodeToString($courseInfo['code']);

        $generalCoachName = '';
        $generalCoachEmail = '';
        $coaches = '';
        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);
            $coaches = CourseManager::get_coachs_from_course_to_string(
                $sessionId,
                $courseInfo['real_id']
            );

            $generalCoach = api_get_user_info($sessionInfo['id_coach']);
            $generalCoachName = $generalCoach['complete_name'];
            $generalCoachEmail = $generalCoach['email'];
        }

        $data = [];
        $data['user_name'] = '';
        $data['user_firstname'] = '';
        $data['user_lastname'] = '';
        $data['user_official_code'] = '';
        $data['user_email'] = '';
        if (!empty($readerInfo)) {
            $data['user_name'] = $readerInfo['username'];
            $data['user_email'] = $readerInfo['email'];
            $data['user_firstname'] = $readerInfo['firstname'];
            $data['user_lastname'] = $readerInfo['lastname'];
            $data['user_official_code'] = $readerInfo['official_code'];
        }

        $data['course_title'] = $courseInfo['name'];
        $courseLink = api_get_course_url($courseInfo['real_id'], $sessionId);
        $data['course_link'] = Display::url($courseLink, $courseLink);
        $data['teachers'] = $teacherList;

        if (!empty($readerInfo)) {
            $extraField = new ExtraField('user');
            $extraFields = $extraField->get_all(['filter = ?' => 1]);
            if (!empty($extraFields)) {
                foreach ($extraFields as $extra) {
                    $data['extra_'.$extra['variable']] = '';
                }
            }

            if (!empty($readerInfo['extra'])) {
                foreach ($readerInfo['extra'] as $extra) {
                    if (isset($extra['value'])) {
                        /** @var \Chamilo\CoreBundle\Entity\ExtraFieldValues $value */
                        $value = $extra['value'];
                        if ($value instanceof ExtraFieldValues) {
                            $field = $value->getField();
                            if ($field instanceof ExtraFieldEntity) {
                                $data['extra_'.$field->getVariable()] = $value->getValue();
                            }
                        }
                    }
                }
            }
        }

        if (!empty($sessionId)) {
            $data['coaches'] = $coaches;
            $data['general_coach'] = $generalCoachName;
            $data['general_coach_email'] = $generalCoachEmail;
        }

        $tags = self::getTags();
        foreach ($tags as $tag) {
            $simpleTag = str_replace(['((', '))'], '', $tag);
            $value = isset($data[$simpleTag]) ? $data[$simpleTag] : '';
            $content = str_replace($tag, $value, $content);
        }

        return $content;
    }

    /**
     * Gets all announcements from a course.
     *
     * @param array $course_info
     * @param int   $session_id
     *
     * @return array html with the content and count of announcements or false otherwise
     */
    public static function get_all_annoucement_by_course($course_info, $session_id = 0)
    {
        $session_id = (int) $session_id;
        $courseId = $course_info['real_id'];

        $repo = Container::getAnnouncementRepository();
        $criteria = [
            'cId' => $courseId,
        ];

        return $repo->findBy($criteria);
        /*
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
                    announcement.c_id = $courseId AND
                    i.c_id = $courseId
                ORDER BY display_order DESC";
        $rs = Database::query($sql);
        $num_rows = Database::num_rows($rs);
        if ($num_rows > 0) {
            $list = [];
            while ($row = Database::fetch_array($rs)) {
                $list[] = $row;
            }

            return $list;
        }

        return false;*/
    }

    /**
     * This functions switches the visibility a course resource
     * using the visibility field in 'item_property'.
     *
     * @param array  $courseInfo
     * @param int    $id
     * @param string $status
     *
     * @return bool False on failure, True on success
     */
    public static function change_visibility_announcement($courseInfo, $id, $status)
    {
        $repo = Container::getAnnouncementRepository();
        $announcement = $repo->find($id);
        if ($announcement) {
            switch ($status) {
                case 'invisible':
                    $repo->setVisibilityDraft($announcement);
                    break;
                case 'visible':
                    $repo->setVisibilityPublished($announcement);
                    break;
            }
        }

        /*$session_id = api_get_session_id();
        $item_visibility = api_get_item_visibility(
            $courseInfo,
            TOOL_ANNOUNCEMENT,
            $id,
            $session_id
        );
        if ('1' == $item_visibility) {
            api_item_property_update(
                $courseInfo,
                TOOL_ANNOUNCEMENT,
                $id,
                'invisible',
                api_get_user_id()
            );
        } else {
            api_item_property_update(
                $courseInfo,
                TOOL_ANNOUNCEMENT,
                $id,
                'visible',
                api_get_user_id()
            );
        }*/

        return true;
    }

    /**
     * Deletes an announcement.
     *
     * @param array $courseInfo the course array
     * @param int   $id         the announcement id
     */
    public static function delete_announcement($courseInfo, $id)
    {
        $repo = Container::getAnnouncementRepository();
        $announcement = $repo->find($id);
        if ($announcement) {
            $em = Database::getManager();
            $em->remove($announcement);
            $em->flush();
        }

        /*
        api_item_property_update(
            $courseInfo,
            TOOL_ANNOUNCEMENT,
            $id,
            'delete',
            api_get_user_id()
        );*/
    }

    /**
     * Deletes all announcements by course.
     *
     * @param array $courseInfo the course array
     */
    public static function delete_all_announcements($courseInfo)
    {
        $repo = Container::getAnnouncementRepository();
        $announcements = self::get_all_annoucement_by_course(
            $courseInfo,
            api_get_session_id()
        );
        $em = Database::getManager();
        if (!empty($announcements)) {
            foreach ($announcements as $announcement) {
                $em->remove($announcement);
                /*api_item_property_update(
                    $courseInfo,
                    TOOL_ANNOUNCEMENT,
                    $annon['id'],
                    'delete',
                    api_get_user_id()
                );*/
            }
        }
        $em->flush();
    }

    /**
     * @param string $title
     * @param int    $courseId
     * @param int    $sessionId
     * @param int    $visibility 1 or 0
     *
     * @return mixed
     */
    public static function getAnnouncementsByTitle(
        $title,
        $courseId,
        $sessionId = 0,
        $visibility = 1
    ) {
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
     * @param int $groupId
     *
     * @return CAnnouncement
     */
    public static function getAnnouncementInfoById(
        $announcementId,
        $courseId,
        $userId,
        $groupId = 0
    ) {
        $announcementId = (int) $announcementId;

        $repo = Container::getAnnouncementRepository();

        return $repo->find($announcementId);

        $courseId = (int) $courseId;
        $userId = (int) $userId;
        $groupId = (int) $groupId;

        if (api_is_allowed_to_edit(false, true) ||
            (1 === (int) api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
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
            $groupList[] = $groupId;

            if (0 != api_get_user_id()) {
                $extraGroupCondition = '';
                if (!empty($groupId)) {
                    $groupProperties = GroupManager::get_group_properties($groupId);
                    if (GroupManager::TOOL_PRIVATE_BETWEEN_USERS == $groupProperties['announcements_state']) {
                        $extraGroupCondition = " AND (
                            ip.toUser = $userId AND ip.group = $groupId OR
                            (ip.group IN ('0') OR ip.group IS NULL) OR
                            (ip.group = $groupId AND (ip.toUser IS NULL OR ip.toUser = 0))
                        )";
                    }
                }

                $dql = "SELECT a, ip
                        FROM ChamiloCourseBundle:CAnnouncement a
                        JOIN ChamiloCourseBundle:CItemProperty ip
                        WITH a.id = ip.ref AND a.cId = ip.course
                        WHERE
                            a.id = :announcement AND
                            ip.tool='announcement' AND
                            (
                                ip.toUser = $userId OR
                                ip.group IN ('0', '".$groupId."') OR
                                ip.group IS NULL
                            ) AND
                            ip.visibility = '1' AND
                            ip.course = :course
                            $extraGroupCondition
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

        if (!empty($result)) {
            return [
                'announcement' => $result[0],
                'item_property' => $result[1],
            ];
        }

        return [];
    }

    /**
     * Displays one specific announcement.
     *
     * @return string
     */
    public static function displayAnnouncement($id)
    {
        $id = (int) $id;

        if (empty($id)) {
            return '';
        }

        $stok = null;
        $html = '';
        $announcement = self::getAnnouncementInfoById(
            $id,
            api_get_course_int_id(),
            api_get_user_id(),
            api_get_group_id()
        );

        if (null === $announcement) {
            return '';
        }

        $title = $announcement->getTitle();
        $content = $announcement->getContent();

        $html .= "<table height=\"100\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" class=\"data_table\">";
        $html .= "<tr><td><h2>".$title."</h2></td></tr>";

        $repo = Container::getAnnouncementRepository();
        $isVisible = $repo->isGranted(ResourceNodeVoter::VIEW, $announcement);

        $url = api_get_self()."?".api_get_cidreq();
        if (api_is_allowed_to_edit(false, true) ||
            (1 === (int) api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
        ) {
            $modify_icons = "<a href=\"".$url."&action=modify&id=".$id."\">".
                Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL)."</a>";

            $image_visibility = 'invisible';
            $alt_visibility = get_lang('Visible');
            $setNewStatus = 'visible';
            if ($isVisible) {
                $image_visibility = 'visible';
                $alt_visibility = get_lang('Hide');
                $setNewStatus = 'invisible';
            }
            $modify_icons .= "<a
                href=\"".$url."&action=set_visibility&status=".$setNewStatus."&id=".$id."&sec_token=".$stok."\">".
                Display::return_icon($image_visibility.'.png', $alt_visibility, '', ICON_SIZE_SMALL)."</a>";

            if (api_is_allowed_to_edit(false, true)) {
                $modify_icons .= "<a
                    href=\"".$url."&action=delete&id=".$id."&sec_token=".$stok."\"
                    onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES))."')) return false;\">".
                    Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).
                    "</a>";
            }
            $html .= "<tr><th style='text-align:right'>$modify_icons</th></tr>";
        } else {
            if (false === $isVisible) {
                api_not_allowed(true);
            }
        }

        // The user id is always the current one.
        $toUserId = api_get_user_id();
        $content = self::parseContent(
            $toUserId,
            $content,
            api_get_course_id(),
            api_get_session_id()
        );

        $html .= "<tr><td>$content</td></tr>";
        $html .= "<tr>";
        $html .= "<td class=\"announcements_datum\">".get_lang('Latest update')." : ";
        $lastEdit = $announcement->getResourceNode()->getUpdatedAt();
        $html .= Display::dateToStringAgoAndLongDate($lastEdit);
        $html .= "</td></tr>";

        $allow = !api_get_configuration_value('hide_announcement_sent_to_users_info');
        if ($allow && api_is_allowed_to_edit(false, true)) {
            $sentTo = $announcement->getUsersAndGroupSubscribedToResource();
            $sentToForm = self::sent_to_form($sentTo);
            $createdBy = '<br />'.get_lang('Created by').': '.
                UserManager::formatUserFullName($announcement->getResourceNode()->getCreator());
            $html .= Display::tag(
                'td',
                get_lang('Visible to').': '.$sentToForm.$createdBy,
                ['class' => 'announcements_datum']
            );
        }

        $attachments = $announcement->getAttachments();
        if (count($attachments) > 0) {
            $repo = Container::getAnnouncementAttachmentRepository();
            $deleteUrl = api_get_self()."?".api_get_cidreq()."&action=delete_attachment";
            /** @var CAnnouncementAttachment $attachment */
            foreach ($attachments as $attachment) {
                $attachmentId = $attachment->getIid();
                $url = $repo->getResourceFileDownloadUrl($attachment).'?'.api_get_cidreq();
                $html .= '<tr><td>';
                $html .= '<br/>';
                $html .= Display::returnFontAwesomeIcon('paperclip');
                $html .= '<a href="'.$url.' "> '.$attachment->getFilename().' </a>';
                $html .= ' - <span class="forum_attach_comment" >'.$attachment->getComment().'</span>';
                if (api_is_allowed_to_edit(false, true)) {
                    $url = $deleteUrl."&id_attach=".$attachmentId."&sec_token=".$stok;
                    $html .= Display::url(
                        Display::return_icon(
                            'delete.png',
                            get_lang('Delete'),
                            '',
                            16
                        ),
                        $url
                    );
                }
                $html .= '</td></tr>';
            }
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * @param array $courseInfo
     *
     * @return int
     */
    public static function getLastAnnouncementOrder($courseInfo)
    {
        if (empty($courseInfo)) {
            return 0;
        }

        if (!isset($courseInfo['real_id'])) {
            return 0;
        }

        return 0;

        $courseId = $courseInfo['real_id'];
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $sql = "SELECT MAX(display_order)
                FROM $table
                WHERE c_id = $courseId ";
        $result = Database::query($sql);

        $order = 0;
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            $order = (int) $row[0] + 1;
        }

        return $order;
    }

    /**
     * Store an announcement in the database (including its attached file if any).
     *
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param string $title                Announcement title (pure text)
     * @param string $content              Content of the announcement (can be HTML)
     * @param array  $sentTo               Array of users and groups to send the announcement to
     * @param array  $file                 uploaded file $_FILES
     * @param string $file_comment         Comment describing the attachment
     * @param string $end_date
     * @param bool   $sendToUsersInSession Send announcements to users inside a session.
     * @param int    $authorId
     *
     * @return int|bool false on failure, ID of the announcement on success
     */
    public static function add_announcement(
        $courseInfo,
        $sessionId,
        $title,
        $content,
        $sentTo,
        $file = [],
        $file_comment = null,
        $end_date = null,
        $sendToUsersInSession = false,
        $authorId = 0
    ) {
        if (empty($courseInfo)) {
            return false;
        }

        if (!isset($courseInfo['real_id'])) {
            return false;
        }

        $courseId = $courseInfo['real_id'];
        if (empty($end_date)) {
            $end_date = api_get_utc_datetime();
        }

        $order = self::getLastAnnouncementOrder($courseInfo);

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);
        $group = api_get_group_entity();

        $em = Database::getManager();

        $announcement = new CAnnouncement();
        $announcement
            ->setContent($content)
            ->setTitle($title)
            ->setEndDate(new DateTime($end_date))
            ->setDisplayOrder($order)
            ->setParent($course)
        ;

        if (empty($sentTo) || (isset($sentTo[0]) && 'everyone' === $sentTo[0])) {
            $announcement->addCourseLink(
                $course,
                $session,
                $group
            );
        } else {
            $send_to = AbstractResource::separateUsersGroups($sentTo);
            // Storing the selected groups
            if (is_array($send_to['groups']) && !empty($send_to['groups'])) {
                foreach ($send_to['groups'] as $group) {
                    $group = api_get_group_entity($group);
                    if ($group) {
                        $announcement->addGroupLink($course, $group, $session);
                    }
                }
            }

            // Storing the selected users
            if (is_array($send_to['users'])) {
                foreach ($send_to['users'] as $user) {
                    $user = api_get_user_entity($user);
                    $announcement->addUserLink($user, $course, $session, $group);
                }
            }
        }

        if ($sendToUsersInSession) {
            self::addAnnouncementToAllUsersInSessions($announcement);
        }

        $em->persist($announcement);
        $em->flush();

        if (!empty($file)) {
            self::add_announcement_attachment_file(
                $announcement,
                $file_comment,
                $_FILES['user_upload']
            );
        }

        return $announcement;
    }

    /**
     * @param string $title
     * @param string $newContent
     * @param int    $groupId
     * @param array  $to_users
     * @param array  $file
     * @param string $file_comment
     * @param bool   $sendToUsersInSession
     *
     * @return bool|CAnnouncement
     */
    public static function addGroupAnnouncement(
        $title,
        $newContent,
        $groupId,
        $to_users,
        $file = [],
        $file_comment = '',
        $sendToUsersInSession = false
    ) {
        $courseInfo = api_get_course_info();
        $order = self::getLastAnnouncementOrder($courseInfo);
        $em = Database::getManager();
        $now = api_get_utc_datetime();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);
        $group = api_get_group_entity($groupId);

        $announcement = new CAnnouncement();
        $announcement
            ->setContent($newContent)
            ->setTitle($title)
            ->setEndDate(new DateTime($now))
            ->setDisplayOrder($order)
            ->setParent($course)
            ;

        $em->persist($announcement);

        $sendToUsers = AbstractResource::separateUsersGroups($to_users);
        // if nothing was selected in the menu then send to all the group
        $sentToAllGroup = false;
        if (empty($sendToUsers['groups']) && empty($sendToUsers['users'])) {
            $announcement->addCourseLink(
                $course,
                $session,
                $group
            );
            $sentToAllGroup = true;
        }

        if (false === $sentToAllGroup) {
            if (!empty($sendToUsers['groups'])) {
                foreach ($sendToUsers['groups'] as $groupItemId) {
                    $groupItem = api_get_group_entity($groupItemId);
                    if (null !== $groupItem) {
                        $announcement->addCourseLink(
                            $course,
                            $session,
                            $groupItem
                        );
                    }
                }
            }

            if (!empty($sendToUsers['users'])) {
                foreach ($sendToUsers['users'] as $user) {
                    $userToAdd = api_get_user_entity($user);
                    if (null !== $userToAdd) {
                        $announcement->addUserLink(
                            $userToAdd,
                            $course,
                            $session,
                            $group
                        );
                    }
                }
            }
        }

        $em->persist($announcement);
        $em->flush();

        $id = $announcement->getIid();
        if ($id) {
            if (!empty($file)) {
                self::add_announcement_attachment_file(
                    $announcement,
                    $file_comment,
                    $file
                );
            }

            if ($sendToUsersInSession) {
                self::addAnnouncementToAllUsersInSessions($announcement);
            }

            return $announcement;
        }

        return null;
    }

    /**
     * @param int    $id                   id of the announcement
     * @param string $title
     * @param string $newContent
     * @param array  $to                   users that will receive the announcement
     * @param mixed  $file                 attachment
     * @param string $file_comment         file comment
     * @param bool   $sendToUsersInSession
     */
    public static function edit_announcement(
        $id,
        $title,
        $newContent,
        $to,
        $file = [],
        $file_comment = '',
        $sendToUsersInSession = false
    ) {
        $id = (int) $id;

        $repo = Container::getAnnouncementRepository();
        /** @var CAnnouncement $announcement */
        $announcement = $repo->find($id);

        if (null === $announcement) {
            return false;
        }

        $course = api_get_course_entity();
        $group = api_get_group_entity();
        $session = api_get_session_entity();

        $announcement
            ->setTitle($title)
            ->setContent($newContent)
        ;

        if (!empty($file)) {
            self::add_announcement_attachment_file(
                $announcement,
                $file_comment,
                $file
            );
            /*if (empty($id_attach)) {
            } else {
                self::edit_announcement_attachment_file(
                    $id_attach,
                    $file,
                    $file_comment
                );
            }*/
        }

        if ($sendToUsersInSession) {
            self::addAnnouncementToAllUsersInSessions($announcement);
        }

        // store, first the groups, then the users
        if (!empty($to)) {
            $send_to = AbstractResource::separateUsersGroups($to);

            // storing the selected groups
            if (is_array($send_to['groups'])) {
                foreach ($send_to['groups'] as $groupId) {
                    $announcement->addGroupLink($course, api_get_group_entity($groupId), $session);
                }
            }

            // storing the selected users
            if (is_array($send_to['users'])) {
                foreach ($send_to['users'] as $user) {
                    $user = api_get_user_entity($user);
                    $announcement->addUserLink($user, $course, $session, $group);
                }
            }

            // Send to everyone
            if (isset($to[0]) && 'everyone' === $to[0]) {
                $announcement->setParent($course);
                $announcement->addCourseLink($course, $session, $group);
            }
        } else {
            $announcement->setParent($course);
            $announcement->addCourseLink($course, $session);
        }

        $repo->update($announcement);

        return $announcement;
    }

    /**
     * Requires persist + flush after the function is called.
     *
     * @param CAnnouncement $announcement
     */
    public static function addAnnouncementToAllUsersInSessions($announcement)
    {
        $courseCode = api_get_course_id();
        $sessionList = SessionManager::get_session_by_course(api_get_course_int_id());

        $courseEntity = api_get_course_entity();
        $sessionEntity = api_get_session_entity();
        $groupEntity = api_get_group_entity();

        if (!empty($sessionList)) {
            foreach ($sessionList as $sessionInfo) {
                $sessionId = $sessionInfo['id'];
                $userList = CourseManager::get_user_list_from_course_code(
                    $courseCode,
                    $sessionId
                );

                if (!empty($userList)) {
                    foreach ($userList as $user) {
                        $user = api_get_user_entity($user);
                        $announcement->addUserLink($user, $courseEntity, $sessionEntity, $groupEntity);
                    }
                }
            }
        }
    }

    /**
     * @param int $insert_id
     *
     * @return bool
     */
    public static function update_mail_sent($insert_id)
    {
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $insert_id = intval($insert_id);
        // store the modifications in the table tbl_annoucement
        $sql = "UPDATE $table SET email_sent='1'
                WHERE iid = $insert_id";
        Database::query($sql);
    }

    /**
     * @param int $user_id
     *
     * @return CAnnouncement[]
     */
    public static function getAnnouncementCourseTotalByUser($user_id)
    {
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return false;
        }

        $user = api_get_user_entity($user_id);
        $repo = Container::getAnnouncementRepository();

        $qb = $repo->getResourcesByLinkedUser($user);

        return $qb->getQuery()->getResult();

        /*
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $sql = "SELECT DISTINCT
                    announcement.c_id,
                    count(announcement.id) count
                FROM $tbl_announcement announcement
                INNER JOIN $tbl_item_property ip
                ON (announcement.id = ip.ref AND announcement.c_id = ip.c_id)
                WHERE
                    ip.tool='announcement' AND
                    (
                      ip.to_user_id = '$user_id' AND
                      (ip.to_group_id='0' OR ip.to_group_id IS NULL)
                    )
                    AND ip.visibility='1'
                    AND announcement.session_id  = 0
                GROUP BY announcement.c_id";
        $rs = Database::query($sql);
        $num_rows = Database::num_rows($rs);
        $result = [];
        if ($num_rows > 0) {
            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                if (empty($row['c_id'])) {
                    continue;
                }
                $result[] = ['course' => api_get_course_info_by_id($row['c_id']), 'count' => $row['count']];
            }
        }

        return $result;*/
    }

    /**
     * This tools loads all the users and all the groups who have received
     * a specific item (in this case an announcement item).
     *
     * @param CAnnouncement $announcement
     * @param bool          $includeGroupWhenLoadingUser
     *
     * @return array
     */
    public static function loadEditUsers($announcement, $includeGroupWhenLoadingUser = false)
    {
        $result = $announcement->getUsersAndGroupSubscribedToResource();
        $to = [];

        foreach ($result['users'] as $itemId) {
            $to[] = 'USER:'.$itemId;
        }

        foreach ($result['groups'] as $itemId) {
            $to[] = 'GROUP:'.$itemId;
        }

        return $to;
    }

    /**
     * constructs the form to display all the groups and users the message has been sent to.
     *
     * @param array $sent_to_array
     *                             input:
     *                             $sent_to_array is a 2 dimensional array containing the groups and the users
     *                             the first level is a distinction between groups and users:
     *                             $sent_to_array['groups'] * and $sent_to_array['users']
     *                             $sent_to_array['groups'] (resp. $sent_to_array['users']) is also an array
     *                             containing all the id's of the groups (resp. users) who have received this message.
     *
     * @return string
     *
     * @author Patrick Cool <patrick.cool@>
     */
    public static function sent_to_form($sent_to_array)
    {
        // we find all the names of the groups
        $groupList = CourseManager::getCourseGroups();

        // we count the number of users and the number of groups
        $number_users = 0;
        if (isset($sent_to_array['users'])) {
            $number_users = count($sent_to_array['users']);
        }
        $number_groups = 0;
        if (isset($sent_to_array['groups'])) {
            $number_groups = count($sent_to_array['groups']);
        }

        $total_numbers = $number_users + $number_groups;

        // starting the form if there is more than one user/group
        $output = [];
        if ($total_numbers > 1) {
            // outputting the name of the groups
            if (is_array($sent_to_array['groups'])) {
                foreach ($sent_to_array['groups'] as $group_id) {
                    $users = GroupManager::getStudents($group_id, true);
                    $userToArray = [];
                    foreach ($users as $student) {
                        $userToArray[] = $student['complete_name_with_username'];
                    }
                    $output[] =
                        '<br />'.
                        Display::label($groupList[$group_id]->getName(), 'info').
                        '&nbsp;'.implode(', ', $userToArray);
                }
            }

            if (isset($sent_to_array['users'])) {
                if (is_array($sent_to_array['users'])) {
                    $usersToArray = [];
                    foreach ($sent_to_array['users'] as $user_id) {
                        $user_info = api_get_user_info($user_id);
                        $usersToArray[] = $user_info['complete_name_with_username'];
                    }
                    $output[] = '<br />'.Display::label(get_lang('Users')).'&nbsp;'.implode(', ', $usersToArray);
                }
            }
        } else {
            // there is only one user/group
            if (isset($sent_to_array['users']) && !empty($sent_to_array['users'])) {
                $user_info = api_get_user_info($sent_to_array['users'][0]);
                $output[] = api_get_person_name($user_info['firstname'], $user_info['lastname']);
            }
            if (isset($sent_to_array['groups']) &&
                is_array($sent_to_array['groups']) &&
                isset($sent_to_array['groups'][0]) &&
                0 !== $sent_to_array['groups'][0]
            ) {
                $group_id = $sent_to_array['groups'][0];

                $users = GroupManager::getStudents($group_id, true);
                $userToArray = [];
                foreach ($users as $student) {
                    $userToArray[] = $student['complete_name_with_username'];
                }
                $output[] =
                    '<br />'.
                    Display::label($groupList[$group_id]->getName(), 'info').
                    '&nbsp;'.implode(', ', $userToArray);
            }
            if (empty($sent_to_array['groups']) && empty($sent_to_array['users'])) {
                $output[] = "&nbsp;".get_lang('All');
            }
        }
        if (!empty($output)) {
            $output = array_filter($output);
            if (count($output) > 0) {
                $output = implode('<br />', $output);
            }

            return $output;
        }
    }

    /**
     * Show a list with all the attachments according to the post's id.
     *
     * @param int $announcementId
     *
     * @return array with the post info
     *
     * @author Arthur Portugal
     *
     * @version November 2009, dokeos 1.8.6.2
     */
    public static function get_attachment($announcementId)
    {
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $announcementId = (int) $announcementId;
        $row = [];
        $sql = 'SELECT iid, path, filename, comment
                FROM '.$table.'
				WHERE announcement_id = '.$announcementId;
        $result = Database::query($sql);
        $repo = Container::getAnnouncementAttachmentRepository();
        if (0 != Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
        }

        return $row;
    }

    /**
     * This function add a attachment file into announcement.
     *
     * @param string file comment
     * @param array  uploaded file $_FILES
     *
     * @return int -1 if failed, 0 if unknown (should not happen), 1 if success
     */
    public static function add_announcement_attachment_file(CAnnouncement $announcement, $file_comment, $file)
    {
        $return = 0;
        $courseId = api_get_course_int_id();
        $em = Database::getManager();
        if (is_array($file) && 0 == $file['error']) {
            // Try to add an extension to the file if it hasn't one
            $new_file_name = add_ext_on_mime(stripslashes($file['name']), $file['type']);
            // user's file name
            $file_name = $file['name'];

            if (!filter_extension($new_file_name)) {
                $return = -1;
                Display::addFlash(
                    Display::return_message(
                        get_lang('File upload failed: this file extension or file type is prohibited'),
                        'error'
                    )
                );
            } else {
                $repo = Container::getAnnouncementAttachmentRepository();
                $attachment = (new CAnnouncementAttachment())
                    ->setFilename($file_name)
                    ->setPath(uniqid('announce_', true))
                    ->setComment($file_comment)
                    ->setAnnouncement($announcement)
                    ->setSize((int) $file['size'])
                    ->setParent($announcement)
                    ->addCourseLink(
                        api_get_course_entity($courseId),
                        api_get_session_entity(api_get_session_id()),
                        api_get_group_entity()
                    )
                ;
                $em->persist($attachment);
                $em->flush();

                $repo->addFileFromFileRequest($attachment, 'user_upload');

                $return = 1;
            }
        }

        return $return;
    }

    /**
     * This function edit a attachment file into announcement.
     *
     * @param int attach id
     * @param array uploaded file $_FILES
     * @param string file comment
     *
     * @return int
     */
    public static function edit_announcement_attachment_file(
        $id_attach,
        $file,
        $file_comment
    ) {
        // @todo fix edition
        exit;
        /*
        $courseInfo = api_get_course_info();
        $table = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
        $return = 0;
        $courseId = api_get_course_int_id();

        if (is_array($file) && 0 == $file['error']) {
            // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
            $courseDir = $courseInfo['path'].'/upload/announcements';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = add_ext_on_mime(
                stripslashes($file['name']),
                $file['type']
            );
            // user's file name
            $file_name = $file['name'];

            if (!filter_extension($new_file_name)) {
                $return = -1;
                echo Display::return_message(
                    get_lang('File upload failed: this file extension or file type is prohibited'),
                    'error'
                );
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir.'/'.$new_file_name;
                copy($file['tmp_name'], $new_path);
                $safe_file_comment = Database::escape_string($file_comment);
                $safe_file_name = Database::escape_string($file_name);
                $safe_new_file_name = Database::escape_string($new_file_name);
                $id_attach = intval($id_attach);
                $sql = "UPDATE $table SET
                            filename = '$safe_file_name',
                            comment = '$safe_file_comment',
                            path = '$safe_new_file_name',
                            size ='".intval($file['size'])."'
                         WHERE iid = '$id_attach'";
                $result = Database::query($sql);
                if (false === $result) {
                    $return = -1;
                    echo Display::return_message(
                        get_lang('The uploaded file could not be saved (perhaps a permission problem?)'),
                        'error'
                    );
                } else {
                    $return = 1;
                }
            }
        }

        return $return;*/
    }

    /**
     * This function delete a attachment file by id.
     *
     * @param int $id attachment file Id
     *
     * @return bool
     */
    public static function delete_announcement_attachment_file($id)
    {
        $id = (int) $id;
        $repo = Container::getAnnouncementAttachmentRepository();
        $em = Database::getManager();
        $attachment = $repo->find($id);
        $em->remove($attachment);
        $em->flush();

        return true;
    }

    /**
     * @param array         $courseInfo
     * @param int           $sessionId
     * @param CAnnouncement $announcement
     * @param bool          $sendToUsersInSession
     * @param bool          $sendToDrhUsers
     * @param Monolog\Handler\HandlerInterface logger
     * @param int  $senderId
     * @param bool $directMessage
     *
     * @return array
     */
    public static function sendEmail(
        $courseInfo,
        $sessionId,
        $announcement,
        $sendToUsersInSession = false,
        $sendToDrhUsers = false,
        $logger = null,
        $senderId = 0,
        $directMessage = false
    ) {
        $email = new AnnouncementEmail($courseInfo, $sessionId, $announcement, $logger);

        return $email->send($sendToUsersInSession, $sendToDrhUsers, $senderId, $directMessage);
    }

    /**
     * @param $stok
     * @param $announcement_number
     * @param bool   $getCount
     * @param null   $start
     * @param null   $limit
     * @param string $sidx
     * @param string $sord
     * @param string $titleToSearch
     * @param int    $userIdToSearch
     * @param int    $userId
     * @param int    $courseId
     * @param int    $sessionId
     *
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
        $group_id = api_get_group_id();
        $session_id = $sessionId ?: api_get_session_id();
        if (empty($courseId)) {
            $courseInfo = api_get_course_info();
            $courseId = $courseInfo['real_id'];
        } else {
            $courseId = (int) $courseId;
            $courseInfo = api_get_course_info_by_id($courseId);
        }

        if (empty($courseInfo)) {
            return [];
        }

        $repo = Container::getAnnouncementRepository();
        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($session_id);
        $group = api_get_group_entity(api_get_group_id());

        if (api_is_allowed_to_edit(false, true)) {
            $qb = $repo->getResourcesByCourse($course, $session, $group);
        } else {
            $user = api_get_user_entity();
            if (null === $user) {
                return [];
            }
            $qb = $repo->getResourcesByCourseLinkedToUser($user, $course, $session, $group);
        }

        $announcements = $qb->getQuery()->getResult();

        $iterator = 1;
        $bottomAnnouncement = $announcement_number;
        $displayed = [];

        $actionUrl = api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq();
        $emailIcon = '<i class="fa fa-envelope-o" title="'.get_lang('Announcement sent by e-mail').'"></i>';
        $attachmentIcon = '<i class="fa fa-paperclip" title="'.get_lang('Attachment').'"></i>';

        $editIcon = Display::return_icon(
            'edit.png',
            get_lang('Edit'),
            '',
            ICON_SIZE_SMALL
        );

        $editIconDisable = Display::return_icon(
            'edit_na.png',
            get_lang('Edit'),
            '',
            ICON_SIZE_SMALL
        );
        $deleteIcon = Display::return_icon(
            'delete.png',
            get_lang('Delete'),
            '',
            ICON_SIZE_SMALL
        );

        $deleteIconDisable = Display::return_icon(
            'delete_na.png',
            get_lang('Delete'),
            '',
            ICON_SIZE_SMALL
        );

        /*$isTutor = false;
        if (!empty($group_id)) {
            $groupInfo = GroupManager::get_group_properties(api_get_group_id());
            //User has access in the group?
            $isTutor = GroupManager::is_tutor_of_group(
                api_get_user_id(),
                $groupInfo
            );
        }*/

        $results = [];
        /** @var CAnnouncement $announcement */
        foreach ($announcements as $announcement) {
            $announcementId = $announcement->getIid();
            if (!in_array($announcementId, $displayed)) {
                $sent_to_icon = '';
                // the email icon
                if ('1' == $announcement->getEmailSent()) {
                    $sent_to_icon = ' '.$emailIcon;
                }

                $groupReference = '';
                $disableEdit = false;
                $to = [];
                $separated = AbstractResource::separateUsersGroups($to);
                if (!empty($group_id)) {
                    // If the announcement was sent to many groups, disable edition inside a group
                    if (isset($separated['groups']) && count($separated['groups']) > 1) {
                        $disableEdit = true;
                    }

                    // If the announcement was sent only to the course disable edition
                    if (empty($separated['groups']) && empty($separated['users'])) {
                        $disableEdit = true;
                    }

                    // Announcement sent to only a user
                    if ($separated['groups'] > 1 && !in_array($group_id, $separated['groups'])) {
                        $disableEdit = true;
                    }
                } else {
                    if (isset($separated['groups']) && count($separated['groups']) > 1) {
                        $groupReference = '';
                    }
                }

                $title = $announcement->getTitle().$groupReference.$sent_to_icon;
                /*$item_visibility = api_get_item_visibility(
                    $courseInfo,
                    TOOL_ANNOUNCEMENT,
                    $row['id'],
                    $session_id
                );*/
                $visibility = $announcement->isVisible($course, $session);

                // show attachment list
                $attachment_list = self::get_attachment($announcementId);
                $attachment_icon = '';
                if (count($attachment_list) > 0) {
                    $attachment_icon = ' '.$attachmentIcon;
                }

                /* TITLE */
                $username = $announcement->getResourceNode()->getCreator()->getUsername();

                $username_span = Display::tag(
                    'span',
                    $username,
                    ['title' => $username]
                );

                $title = Display::url(
                    $title.$attachment_icon,
                    $actionUrl.'&action=view&id='.$announcementId
                );

                // we can edit if : we are the teacher OR the element belongs to
                // the session we are coaching OR the option to allow users to edit is on
                /*if api_is_allowed_to_edit(false, true) ||
                     (api_is_session_general_coach() && api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $announcementId)) ||
                     (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous()) ||
                     ($isTutor)
                     //$row['to_group_id'] == $group_id &&
                 ) {*/
                if ($repo->isGranted(ResourceNodeVoter::EDIT, $announcement)) {
                    if (true === $disableEdit) {
                        $modify_icons = "<a href='#'>".$editIconDisable."</a>";
                    } else {
                        $modify_icons = "<a
                            href=\"".$actionUrl."&action=modify&id=".$announcementId."\">".$editIcon."</a>";
                    }

                    $image_visibility = 'invisible';
                    $setNewStatus = 'visible';
                    $alt_visibility = get_lang('Visible');
                    if ($visibility) {
                        $image_visibility = 'visible';
                        $setNewStatus = 'invisible';
                        $alt_visibility = get_lang('Hide');
                    }

                    $modify_icons .= "<a
                        href=\"".$actionUrl."&action=set_visibility&status=".$setNewStatus."&id=".$announcementId."&sec_token=".$stok."\">".
                        Display::return_icon($image_visibility.'.png', $alt_visibility, '', ICON_SIZE_SMALL)."</a>";

                    // DISPLAY MOVE UP COMMAND only if it is not the top announcement
                    if (1 != $iterator) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=move&up=".$announcementId."&sec_token=".$stok."\">".
                            Display::return_icon('up.gif', get_lang('Up'))."</a>";
                    } else {
                        $modify_icons .= Display::return_icon('up_na.gif', get_lang('Up'));
                    }
                    if ($iterator < $bottomAnnouncement) {
                        $modify_icons .= "<a href=\"".$actionUrl."&action=move&down=".$announcementId."&sec_token=".$stok."\">".
                            Display::return_icon('down.gif', get_lang('down'))."</a>";
                    } else {
                        $modify_icons .= Display::return_icon('down_na.gif', get_lang('down'));
                    }
                    if (api_is_allowed_to_edit(false, true)) {
                        if (true === $disableEdit) {
                            $modify_icons .= Display::url($deleteIconDisable, '#');
                        } else {
                            $modify_icons .= "<a
                                href=\"".$actionUrl."&action=delete&id=".$announcementId."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(
                                    api_htmlentities(
                                        get_lang('Please confirm your choice'),
                                        ENT_QUOTES,
                                        api_get_system_encoding()
                                    )
                                )."')) return false;\">".
                                $deleteIcon."</a>";
                        }
                    }
                    $iterator++;
                } else {
                    $modify_icons = Display::url(
                        Display::return_icon('default.png'),
                        $actionUrl.'&action=view&id='.$announcementId
                    );
                }

                $results[] = [
                    'id' => $announcementId,
                    'title' => $title,
                    'username' => $username_span,
                    'insert_date' => api_convert_and_format_date(
                        $announcement->getResourceNode()->getCreatedAt(),
                        DATE_TIME_FORMAT_LONG
                    ),
                    'lastedit_date' => api_convert_and_format_date(
                        $announcement->getResourceNode()->getUpdatedAt(),
                        DATE_TIME_FORMAT_LONG
                    ),
                    'actions' => $modify_icons,
                ];
            }
            $displayed[] = $announcementId;
        }

        return $results;
    }

    /**
     * @return int
     */
    public static function getNumberAnnouncements()
    {
        $session_id = api_get_session_id();
        $courseInfo = api_get_course_info();
        $courseId = $courseInfo['real_id'];
        $userId = api_get_user_id();

        $repo = Container::getAnnouncementRepository();
        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($session_id);
        $group = api_get_group_entity(api_get_group_id());
        if (api_is_allowed_to_edit(false, true)) {
            // check teacher status
            if (empty($_GET['origin']) || 'learnpath' !== $_GET['origin']) {
                /*if (0 == api_get_group_id()) {
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
                            announcement.c_id = $courseId AND
                            ip.c_id = $courseId AND
                            ip.tool = 'announcement' AND
                            ip.visibility <> '2'
                            $group_condition
                            $condition_session
                        GROUP BY ip.ref
                        ORDER BY display_order DESC
                        LIMIT 0, $maximum";*/

                $qb = $repo->getResourcesByCourse($course, $session, $group);
                $qb->select('count(resource)');

                return $qb->getQuery()->getSingleScalarResult();
            }
        } else {
            $user = api_get_user_entity($userId);

            if (null === $user) {
                return 0;
            }

            $qb = $repo->getResourcesByCourseLinkedToUser($user, $course, $session, $group);
            $qb->select('count(resource)');

            return $qb->getQuery()->getSingleScalarResult();
        }
    }
}
