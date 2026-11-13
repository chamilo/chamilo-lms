<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * Class Agenda.
 *
 * @author: Julio Montoya <gugli100@gmail.com>
 */
class Agenda
{
    public $events = [];
    /** @var string Current type */
    public $type = 'personal';
    public $types = ['personal', 'admin', 'course'];
    public $sessionId = 0;
    public $senderId;
    /** @var array */
    public $course;
    /** @var string */
    public $comment;
    public $eventStudentPublicationColor;
    /** @var array */
    private $sessionInfo;
    /** @var bool */
    private $isAllowedToEdit;

    private $tbl_global_agenda;
    private $tbl_personal_agenda;
    private $tbl_course_agenda;
    private $table_repeat;

    /**
     * Constructor.
     *
     * @param string $type
     * @param int    $senderId  Optional The user sender ID
     * @param int    $courseId  Optional. The course ID
     * @param int    $sessionId Optional The session ID
     */
    public function __construct(
        $type,
        $senderId = 0,
        $courseId = 0,
        $sessionId = 0
    ) {
        // Table definitions
        $this->tbl_course_agenda = Database::get_course_table(TABLE_AGENDA);
        $this->table_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT);

        $this->setType($type);
        $this->setSenderId($senderId ?: api_get_user_id());
        $isAllowToEdit = false;

        switch ($type) {
            case 'course':
                $sessionId = $sessionId ?: api_get_session_id();
                $sessionInfo = api_get_session_info($sessionId);
                $this->setSessionId($sessionId);
                $this->setSessionInfo($sessionInfo);

                // Setting the course object if we are in a course
                $courseInfo = api_get_course_info_by_id($courseId);
                if (!empty($courseInfo)) {
                    $this->set_course($courseInfo);
                }

                // Check if teacher/admin rights.
                $isAllowToEdit = api_is_allowed_to_edit(false, true);
                // Check course setting.
                if ('1' === api_get_course_setting('allow_user_edit_agenda')
                    && api_is_allowed_in_course()
                ) {
                    $isAllowToEdit = true;
                }

                $group = api_get_group_entity();
                if (!empty($group)) {
                    $userHasAccess = GroupManager::userHasAccess(
                        api_get_user_id(),
                        $group,
                        GroupManager::GROUP_TOOL_CALENDAR
                    );
                    $isTutor = GroupManager::isTutorOfGroup(
                        api_get_user_id(),
                        $group
                    );

                    $isGroupAccess = $userHasAccess || $isTutor;
                    $isAllowToEdit = false;
                    if ($isGroupAccess) {
                        $isAllowToEdit = true;
                    }
                }

                if (!empty($sessionId)) {
                    $allowDhrToEdit = ('true' === api_get_setting('agenda.allow_agenda_edit_for_hrm'));
                    if ($allowDhrToEdit) {
                        $isHrm = SessionManager::isUserSubscribedAsHRM($sessionId, api_get_user_id());
                        if ($isHrm) {
                            $isAllowToEdit = true;
                        }
                    }
                }
                break;
            case 'admin':
                $isAllowToEdit = api_is_platform_admin();
                break;
            case 'personal':
                $isAllowToEdit = !api_is_anonymous();
                break;
        }

        $this->setIsAllowedToEdit($isAllowToEdit);
        $this->events = [];
        $agendaColors = [
                'platform' => 'red', //red
                'course' => '#458B00', //green
                'group' => '#A0522D', //siena
                'session' => '#00496D', // kind of green
                'other_session' => '#999', // kind of green
                'personal' => 'steel blue', //steel blue
                'student_publication' => '#FF8C00', //DarkOrange
            ];
        $settingAgendaColors = api_get_setting('agenda.agenda_colors', true);
        if (is_array($settingAgendaColors)) {
            $agendaColors = array_merge($agendaColors, $settingAgendaColors);
        }

        // Event colors
        $this->event_platform_color = $agendaColors['platform'];
        $this->event_course_color = $agendaColors['course'];
        $this->event_group_color = $agendaColors['group'];
        $this->event_session_color = $agendaColors['session'];
        $this->eventOtherSessionColor = $agendaColors['other_session'];
        $this->event_personal_color = $agendaColors['personal'];
        $this->eventStudentPublicationColor = $agendaColors['student_publication'];
    }

    /**
     * @param int $senderId
     */
    public function setSenderId($senderId)
    {
        $this->senderId = (int) $senderId;
    }

    /**
     * @return int
     */
    public function getSenderId()
    {
        return $this->senderId;
    }

    /**
     * @param string $type can be 'personal', 'admin'  or  'course'
     */
    public function setType($type)
    {
        $type = trim($type);
        $typeList = $this->getTypes();
        if (in_array($type, $typeList, true)) {
            $this->type = $type;
        }
    }

    /**
     * Returns the type previously set (and filtered) through setType
     * If setType() was not called, then type defaults to "personal" as
     * set in the class definition.
     */
    public function getType()
    {
        if (isset($this->type)) {
            return $this->type;
        }
    }

    /**
     * @param int $id
     */
    public function setSessionId($id)
    {
        $this->sessionId = (int) $id;
    }

    /**
     * @param array $sessionInfo
     */
    public function setSessionInfo($sessionInfo)
    {
        $this->sessionInfo = $sessionInfo;
    }

    /**
     * @return int $id
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param array $courseInfo
     */
    public function set_course($courseInfo)
    {
        $this->course = $courseInfo;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Whether the event belongs to the course of this agenda: it is a child of the course resource node or has a
     * link to the course. Event ids come from the request, so every read or write by id must check this.
     */
    private function eventBelongsToCourse(CCalendarEvent $event): bool
    {
        $courseId = (int) ($this->course['real_id'] ?? 0);
        $node = $event->getResourceNode();
        if (empty($courseId) || null === $node) {
            return false;
        }

        $courseNodeId = api_get_course_entity($courseId)?->getResourceNode()?->getId();
        if (null !== $courseNodeId && $node->getParent()?->getId() === $courseNodeId) {
            return true;
        }

        foreach ($node->getResourceLinks() as $link) {
            if ($link->getCourse()?->getId() === $courseId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds an event to the calendar.
     *
     * @param string         $start                 datetime format: 2012-06-14 09:00:00 in local time
     * @param string         $end                   datetime format: 2012-06-14 09:00:00 in local time
     * @param string         $allDay                (true, false)
     * @param string         $title
     * @param string         $content
     * @param array          $usersToSend           array('everyone') or a list of user/group ids
     * @param bool           $addAsAnnouncement     event as a *course* announcement
     * @param int            $parentEventId
     * @param UploadedFile[] $attachmentArray       array of $_FILES['']
     * @param array          $attachmentCommentList
     * @param string         $eventComment
     * @param string         $color
     *
     * @return int
     */
    public function addEvent(
        $start,
        $end,
        $allDay,
        $title,
        $content,
        $usersToSend = [],
        $addAsAnnouncement = false,
        $parentEventId = 0,
        $attachmentArray = [],
        $attachmentCommentList = [],
        $eventComment = '',
        $color = ''
    ) {
        if (empty($end)) {
            // Callers without an end date (e.g. the Bbb plugin) get a zero-length event, not one ending "now".
            $end = $start;
        }
        $start = api_get_utc_datetime($start, false, true);
        $end = api_get_utc_datetime($end, false, true);
        // Callers pass 'true', true or 1 indifferently.
        $allDay = (isset($allDay) && ('true' === $allDay || true === $allDay || 1 === $allDay || '1' === $allDay)) ? 1 : 0;
        $id = null;

        $em = Database::getManager();
        switch ($this->type) {
            case 'personal':
                /*$event = new PersonalAgenda();
                $event
                    ->setTitle($title)
                    ->setText($content)
                    ->setDate($start)
                    ->setEndDate($end)
                    ->setAllDay($allDay)
                    ->setColor($color)
                    ->setUser(api_get_user_entity())
                ;
                $em->persist($event);
                $em->flush();
                $id = $event->getId();*/
                break;
            case 'course':
                $sessionId = $this->getSessionId();
                $sessionEntity = api_get_session_entity($sessionId);
                $courseEntity = api_get_course_entity($this->course['real_id']);
                $groupEntity = api_get_group_entity(api_get_group_id());

                $event = new CCalendarEvent();
                $event
                    ->setTitle($title)
                    ->setContent($content)
                    ->setStartDate($start)
                    ->setEndDate($end)
                    ->setAllDay($allDay)
                    ->setColor($color)
                    ->setComment($eventComment)
                ;

                if (!empty($parentEventId)) {
                    $repo = Container::getCalendarEventRepository();
                    $parentEvent = $repo->find($parentEventId);
                    $event->setParentEvent($parentEvent);
                }

                $event->setParent($courseEntity);

                $sendTo = $this->parseSendToArray($usersToSend);
                $hasRecipients = !empty($sendTo['everyone']) || !empty($sendTo['users'])
                    || !empty(array_filter($sendTo['groups'] ?? []));
                if (!$hasRecipients) {
                    // No usable recipient (empty list, or only GROUP:0): publish to the whole course context,
                    // otherwise the event would be linked to nobody and listed nowhere.
                    $event->addCourseLink($courseEntity, $sessionEntity, $groupEntity);
                } else {
                    if ($sendTo['everyone']) {
                        $event->addCourseLink($courseEntity, $sessionEntity, $groupEntity);
                    } else {
                        // Storing the selected groups
                        if (!empty($sendTo['groups'])) {
                            foreach ($sendTo['groups'] as $group) {
                                $groupInfo = null;
                                if ($group) {
                                    $groupInfo = api_get_group_entity($group);
                                    $event->addCourseLink($courseEntity, $sessionEntity, $groupInfo);
                                }
                            }
                        }

                        // storing the selected users
                        if (!empty($sendTo['users'])) {
                            foreach ($sendTo['users'] as $userId) {
                                $event->addUserLink(
                                    api_get_user_entity($userId),
                                    $courseEntity,
                                    $sessionEntity,
                                    $groupEntity
                                );
                            }
                        }
                    }
                }

                $em->persist($event);
                $em->flush();
                $id = $event->getIid();

                if ($id) {
                    // Add announcement.
                    if ($addAsAnnouncement) {
                        $this->storeAgendaEventAsAnnouncement($event, $usersToSend);
                    }

                    // Add attachment.
                    if (!empty($attachmentArray)) {
                        $counter = 0;
                        foreach ($attachmentArray as $attachmentItem) {
                            $this->addAttachment(
                                $event,
                                $attachmentItem,
                                $attachmentCommentList[$counter],
                                $this->course
                            );
                            $counter++;
                        }
                    }
                }
                break;
        }

        return $id;
    }

    public function storeAgendaEventAsAnnouncement(CCalendarEvent $event, array $sentTo = [])
    {
        // Sending announcement
        if (!empty($sentTo)) {
            $id = AnnouncementManager::add_announcement(
                api_get_course_info(),
                api_get_session_id(),
                $event->getTitle(),
                $event->getContent(),
                $sentTo,
                null,
                null,
                $event->getEndDate()->format('Y-m-d H:i:s')
            );

            AnnouncementManager::sendEmail(
                api_get_course_info(),
                api_get_session_id(),
                $id
            );

            return true;
        }

        return false;
    }

    /**
     * Edits an event.
     *
     * @param int    $id
     * @param string $start                 datetime format: 2012-06-14 09:00:00
     * @param string $end                   datetime format: 2012-06-14 09:00:00
     * @param int    $allDay                is all day 'true' or 'false'
     * @param string $title
     * @param string $content
     * @param array  $usersToSend
     * @param array  $attachmentArray
     * @param array  $attachmentCommentList
     * @param string $comment
     * @param string $color
     * @param bool   $addAnnouncement
     * @param bool   $updateContent
     * @param int    $authorId
     *
     * @return bool
     */
    public function editEvent(
        $id,
        $start,
        $end,
        $allDay,
        $title,
        $content,
        $usersToSend = [],
        $attachmentArray = [],
        $attachmentCommentList = [],
        $comment = '',
        $color = '',
        $addAnnouncement = false,
        $updateContent = true,
        $authorId = 0
    ) {
        $startObject = api_get_utc_datetime($start, true, true);
        $endObject = api_get_utc_datetime($end, true, true);
        $start = api_get_utc_datetime($start);
        $end = api_get_utc_datetime($end);
        $allDay = isset($allDay) && 'true' == $allDay ? 1 : 0;
        $authorId = empty($authorId) ? api_get_user_id() : (int) $authorId;

        switch ($this->type) {
            case 'personal':
                /*$eventInfo = $this->get_event($id);
                if ($eventInfo['user'] != api_get_user_id()) {
                    break;
                }
                $attributes = [
                    'title' => $title,
                    'date' => $start,
                    'enddate' => $end,
                    'all_day' => $allDay,
                ];

                if ($updateContent) {
                    $attributes['text'] = $content;
                }

                if (!empty($color)) {
                    $attributes['color'] = $color;
                }

                Database::update(
                    $this->tbl_personal_agenda,
                    $attributes,
                    ['id = ?' => $id]
                );*/
                break;
            case 'course':
                $repo = Container::getCalendarEventRepository();
                $em = Database::getManager();
                /** @var CCalendarEvent $event */
                $event = $repo->find($id);

                if (empty($event) || !$this->eventBelongsToCourse($event)) {
                    return false;
                }

                $sentToEvent = $event->getUsersAndGroupSubscribedToResource();
                $courseId = $this->course['real_id'];

                if (empty($courseId)) {
                    return false;
                }

                $courseEntity = api_get_course_entity($courseId);
                $sessionEntity = api_get_session_entity($this->sessionId);
                $groupEntity = api_get_group_entity(api_get_group_id());

                if ($this->getIsAllowedToEdit()) {
                    $event
                        ->setTitle($title)
                        ->setStartDate($startObject)
                        ->setEndDate($endObject)
                        ->setAllDay($allDay)
                        ->setComment($comment)
                    ;

                    if ($updateContent) {
                        $event->setContent($content);
                    }

                    if (!empty($color)) {
                        $event->setColor($color);
                    }

                    if (!empty($usersToSend)) {
                        $sendTo = $this->parseSendToArray($usersToSend);

                        $usersToDelete = array_diff($sentToEvent['users'], $sendTo['users']);
                        $usersToAdd = array_diff($sendTo['users'], $sentToEvent['users']);
                        $groupsToDelete = array_diff($sentToEvent['groups'], $sendTo['groups']);
                        $groupToAdd = array_diff($sendTo['groups'], $sentToEvent['groups']);

                        $links = $event->getResourceNode()->getResourceLinks();
                        // addCourseLink() needs the parent (the course) on an event loaded from the database.
                        $event->setParent($courseEntity);
                        // Links are removed after persist($event): persisting cascades to the node's links and
                        // would otherwise cancel a removal scheduled before it.
                        // They also leave the collection now, or addCourseLink() would see them as existing.
                        $linksToRemove = [];
                        $removeLink = static function ($link) use (&$linksToRemove, $links): void {
                            $links->removeElement($link);
                            $linksToRemove[] = $link;
                        };

                        if ($sendTo['everyone']) {
                            // Remove the links to groups and users, then make sure the course-wide link exists.
                            $hasCourseLink = false;
                            foreach ($links as $link) {
                                if ($link->hasGroup() || $link->hasUser()) {
                                    $removeLink($link);
                                } else {
                                    $hasCourseLink = true;
                                }
                            }
                            if (!$hasCourseLink) {
                                $event->addCourseLink($courseEntity, $sessionEntity, $groupEntity);
                            }
                        } else {
                            foreach ($links as $link) {
                                if ($link->hasGroup()) {
                                    // Delete groups.
                                    if (in_array($link->getGroup()->getIid(), $groupsToDelete)) {
                                        $removeLink($link);
                                    }
                                } elseif ($link->hasUser()) {
                                    // Delete users.
                                    if (in_array($link->getUser()->getId(), $usersToDelete)) {
                                        $removeLink($link);
                                    }
                                } else {
                                    // The course-wide link: the event is no longer for everyone.
                                    $removeLink($link);
                                }
                            }

                            // Add groups.
                            foreach ($groupToAdd as $group) {
                                $group = api_get_group_entity($group);
                                $event->addCourseLink($courseEntity, $sessionEntity, $group);
                            }

                            // Add users.
                            foreach ($usersToAdd as $userId) {
                                $event = $event->addUserLink(
                                    api_get_user_entity($userId),
                                    $courseEntity,
                                    $sessionEntity,
                                    $groupEntity
                                );
                            }
                        }
                    }

                    $em->persist($event);
                    foreach ($linksToRemove ?? [] as $link) {
                        $em->remove($link);
                    }
                    $em->flush($event);

                    // Add announcement.
                    if (isset($addAnnouncement) && !empty($addAnnouncement)) {
                        $this->storeAgendaEventAsAnnouncement($event, $usersToSend);
                    }

                    // Add attachment.
                    if (isset($attachmentArray) && !empty($attachmentArray)) {
                        $counter = 0;
                        foreach ($attachmentArray as $attachmentItem) {
                            if (isset($attachmentItem['id'])) {
                                $this->updateAttachment(
                                    $attachmentItem['id'],
                                    $id,
                                    $attachmentItem,
                                    $attachmentCommentList[$counter],
                                    $this->course
                                );
                                $counter++;
                            }
                        }
                    }

                    return true;
                }

                return false;
                break;
        }
    }

    /**
     * Gets a single event.
     *
     * @param int $id event id
     *
     * @return array
     */
    public function get_event($id)
    {
        // make sure events of the personal agenda can only be seen by the user himself
        $id = (int) $id;
        $event = null;
        switch ($this->type) {
            case 'personal':
                /*$sql = "SELECT * FROM ".$this->tbl_personal_agenda."
                        WHERE id = $id AND user = ".api_get_user_id();
                $result = Database::query($sql);
                if (Database::num_rows($result)) {
                    $event = Database::fetch_assoc($result);
                    $event['description'] = $event['text'];
                    $event['content'] = $event['text'];
                    $event['start_date'] = $event['date'];
                    $event['end_date'] = $event['enddate'];
                }*/
                break;
            case 'course':
                $repo = Container::getCalendarEventRepository();
                /** @var CCalendarEvent $eventEntity */
                $eventEntity = $repo->find($id);

                if (!empty($this->course['real_id'])) {
                    if ($eventEntity && $this->eventBelongsToCourse($eventEntity)) {
                        $event = [];
                        $event['iid'] = $eventEntity->getIid();
                        $event['title'] = $eventEntity->getTitle();
                        $event['content'] = $eventEntity->getContent();
                        $event['all_day'] = $eventEntity->isAllDay();
                        $event['start_date'] = $eventEntity->getStartDate()->format('Y-m-d H:i:s');
                        $event['end_date'] = $eventEntity->getEndDate()->format('Y-m-d H:i:s');
                        $event['description'] = $eventEntity->getComment();

                        // Getting send to array
                        $event['send_to'] = $eventEntity->getUsersAndGroupSubscribedToResource();

                        // Getting repeat info
                        $event['repeat_info'] = $eventEntity->getRepeatEvents();

                        $parentEvent = $eventEntity->getParentEvent();
                        if (null !== $parentEvent) {
                            $event['parent_event_id'] = $parentEvent->getIid();
                            $event['parent_info'] = $parentEvent;
                        }

                        $event['attachment'] = $eventEntity->getAttachments();
                    }
                }
                break;
        }

        return $event;
    }

    /**
     * Separates the users and groups array
     * users have a value USER:XXX (with XXX the user id
     * groups have a value GROUP:YYY (with YYY the group id)
     * use the 'everyone' key.
     *
     * @author Julio Montoya based in separate_users_groups in agenda.inc.php
     *
     * @param array $to
     *
     * @return array
     */
    public function parseSendToArray($to)
    {
        $groupList = [];
        $userList = [];
        $sendTo = null;

        $sendTo['everyone'] = false;
        if (is_array($to) && count($to) > 0) {
            foreach ($to as $item) {
                if ('everyone' == $item) {
                    $sendTo['everyone'] = true;
                } else {
                    [$type, $id] = explode(':', $item);
                    switch ($type) {
                        case 'GROUP':
                            $groupList[] = $id;
                            break;
                        case 'USER':
                            $userList[] = $id;
                            break;
                    }
                }
            }
            $sendTo['groups'] = $groupList;
            $sendTo['users'] = $userList;
        }

        return $sendTo;
    }

    /**
     * Show a list with all the attachments according to the post's id.
     *
     * @param int   $attachmentId
     * @param int   $eventId
     * @param array $courseInfo
     *
     * @return array with the post info
     *
     * @deprecated only called by updateAttachment() (no caller passes attachments); selects c_id/path columns that c_calendar_event_attachment no longer has.
     */
    public function getAttachment($attachmentId, $eventId, $courseInfo)
    {
        if (empty($courseInfo) || empty($attachmentId) || empty($eventId)) {
            return [];
        }

        $tableAttachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
        $courseId = (int) $courseInfo['real_id'];
        $eventId = (int) $eventId;
        $attachmentId = (int) $attachmentId;

        $row = [];
        $sql = "SELECT iid, path, filename, comment
                FROM $tableAttachment
                WHERE
                    c_id = $courseId AND
                    agenda_id = $eventId AND
                    iid = $attachmentId
                ";
        $result = Database::query($sql);
        if (0 != Database::num_rows($result)) {
            $row = Database::fetch_assoc($result);
        }

        return $row;
    }

    /**
     * Add an attachment file into agenda.
     *
     * @param CCalendarEvent $event
     * @param UploadedFile   $file
     * @param string         $comment
     * @param array          $courseInfo
     *
     * @return string
     */
    public function addAttachment(
        $event,
        $file,
        $comment,
        $courseInfo
    ) {
        // Storing the attachments
        $valid = false;
        if ($file) {
            $valid = process_uploaded_file($file);
        }

        if ($valid) {
            // user's file name
            $fileName = $file->getClientOriginalName();
            $em = Database::getManager();
            $attachment = (new CCalendarEventAttachment())
                ->setFilename($fileName)
                ->setComment($comment)
                ->setEvent($event)
                ->setParent($event)
                ->addCourseLink(
                    api_get_course_entity(),
                    api_get_session_entity(),
                    api_get_group_entity()
                )
            ;

            $repo = Container::getCalendarEventAttachmentRepository();
            $em->persist($attachment);
            $em->flush();

            $repo->addFile($attachment, $file);
            $em->persist($attachment);
            $em->flush();
        }
    }

    /**
     * @param int    $attachmentId
     * @param int    $eventId
     * @param array  $fileUserUpload
     * @param string $comment
     * @param array  $courseInfo
     */
    public function updateAttachment(
        $attachmentId,
        $eventId,
        $fileUserUpload,
        $comment,
        $courseInfo
    ) {
        $attachment = $this->getAttachment(
            $attachmentId,
            $eventId,
            $courseInfo
        );
        if (!empty($attachment)) {
            $this->deleteAttachmentFile($attachmentId);
        }
        $this->addAttachment($eventId, $fileUserUpload, $comment, $courseInfo);
    }

    /**
     * This function delete a attachment file by id.
     *
     * @param int $attachmentId
     *
     * @return string
     */
    public function deleteAttachmentFile($attachmentId)
    {
        $repo = Container::getCalendarEventAttachmentRepository();
        /** @var CCalendarEventAttachment $attachment */
        $attachment = $repo->find($attachmentId);
        $em = Database::getManager();
        if (empty($attachment)) {
            return false;
        }

        $em->remove($attachment);
        $em->flush();

        return Display::return_message(
            get_lang("The attached file has been deleted"),
            'confirmation'
        );
    }

    /**
     * @return bool
     */
    public function getIsAllowedToEdit()
    {
        return $this->isAllowedToEdit;
    }

    /**
     * @param bool $isAllowedToEdit
     */
    public function setIsAllowedToEdit($isAllowedToEdit)
    {
        $this->isAllowedToEdit = $isAllowedToEdit;
    }

    /**
     * Format needed for the Fullcalendar js lib.
     *
     * @param string $utcTime
     *
     * @return bool|string
     */
    public function formatEventDate($utcTime)
    {
        $utcTimeZone = new DateTimeZone('UTC');
        $platformTimeZone = new DateTimeZone(api_get_timezone());

        $eventDate = new DateTime($utcTime, $utcTimeZone);
        $eventDate->setTimezone($platformTimeZone);

        return $eventDate->format(DateTime::ISO8601);
    }

    public static function getJsForReminders(string $cssSelectorBtnAdd): string
    {
        return '
            var template = \'<div class="flex flex-row items-center gap-4">\' +
                \'<input min="0" step="1" id="notification_count[]" type="number" name="notification_count[]">\' +
                \'<select name="notification_period[]" id="form_notification_period[]">\' +
                \'<option value="i">'.get_lang('Minutes').'</option>\' +
                \'<option value="h">'.get_lang('Hours').'</option>\' +
                \'<option value="d">'.get_lang('Days').'</option>\' +
                \'</select>\' +
                \'<p class="form-control-static">'.get_lang('Before').'</p>\' +
                \'<button class="btn btn--danger delete-notification" type="button" aria-label="'.get_lang('Delete').'">\' +
                \'<i class="mdi mdi-close" aria-hidden="true"></i>\' +
                \'</button>\' +
                \'</div>\';

            $("'.$cssSelectorBtnAdd.'").on("click", function (e) {
                e.preventDefault();

                $(template).appendTo("#notification_list");
                $("#notification_list select").selectpicker("refresh");
            });

            $("#notification_list").on("click", ".delete-notification", function (e) {
                e.preventDefault();

                $(this).parents(".form-group").remove();
            });';
    }
}
