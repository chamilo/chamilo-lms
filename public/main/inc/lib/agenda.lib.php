<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\PersonalAgenda;
use Chamilo\CoreBundle\Entity\SysCalendar;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CoreBundle\Entity\Course;
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
        $this->tbl_global_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
        $this->tbl_personal_agenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);
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
                    $allowDhrToEdit = api_get_configuration_value('allow_agenda_edit_for_hrm');
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
        $agendaColors = array_merge(
            [
                'platform' => 'red', //red
                'course' => '#458B00', //green
                'group' => '#A0522D', //siena
                'session' => '#00496D', // kind of green
                'other_session' => '#999', // kind of green
                'personal' => 'steel blue', //steel blue
                'student_publication' => '#FF8C00', //DarkOrange
            ],
            api_get_configuration_value('agenda_colors') ?: []
        );

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
        $typeList = $this->getTypes();
        if (in_array($type, $typeList)) {
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
        $start = api_get_utc_datetime($start, false, true);
        $end = api_get_utc_datetime($end, false, true);
        $allDay = isset($allDay) && 'true' === $allDay ? 1 : 0;
        $id = null;

        $em = Database::getManager();
        switch ($this->type) {
            case 'personal':
                $event = new PersonalAgenda();
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
                $id = $event->getId();
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

                if (!empty($usersToSend)) {
                    $sendTo = $this->parseSendToArray($usersToSend);
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
            case 'admin':
                if (api_is_platform_admin()) {
                    $event = new SysCalendar();
                    $event
                        ->setTitle($title)
                        ->setContent($content)
                        ->setStartDate($start)
                        ->setEndDate($end)
                        ->setAllDay($allDay)
                        ->setUrl(api_get_url_entity())
                    ;
                    $em->persist($event);
                    $em->flush();
                    $id = $event->getId();
                }
                break;
        }

        return $id;
    }

    /**
     * @param string $type
     * @param string $startEvent      in UTC
     * @param string $endEvent        in UTC
     * @param string $repeatUntilDate in UTC
     *
     * @throws Exception
     *
     * @return array
     */
    public function generateDatesByType($type, $startEvent, $endEvent, $repeatUntilDate)
    {
        $continue = true;
        $repeatUntilDate = new DateTime($repeatUntilDate, new DateTimeZone('UTC'));
        $loopMax = 365;
        $counter = 0;
        $list = [];

        switch ($type) {
            case 'daily':
                $interval = 'P1D';
                break;
            case 'weekly':
                $interval = 'P1W';
                break;
            case 'monthlyByDate':
                $interval = 'P1M';
                break;
            case 'monthlyByDay':
            case 'monthlyByDayR':
                // not yet implemented
                break;
            case 'yearly':
                $interval = 'P1Y';
                break;
        }

        if (empty($interval)) {
            return [];
        }
        $timeZone = api_get_timezone();

        while ($continue) {
            $startDate = new DateTime($startEvent, new DateTimeZone('UTC'));
            $endDate = new DateTime($endEvent, new DateTimeZone('UTC'));

            $startDate->add(new DateInterval($interval));
            $endDate->add(new DateInterval($interval));

            $newStartDate = $startDate->format('Y-m-d H:i:s');
            $newEndDate = $endDate->format('Y-m-d H:i:s');

            $startEvent = $newStartDate;
            $endEvent = $newEndDate;

            if ($endDate > $repeatUntilDate) {
                break;
            }

            // @todo remove comment code
            $startDateInLocal = new DateTime($newStartDate, new DateTimeZone($timeZone));
            if (0 == $startDateInLocal->format('I')) {
                // Is saving time? Then fix UTC time to add time
                $seconds = $startDateInLocal->getOffset();
                $startDate->add(new DateInterval("PT".$seconds."S"));
                $startDateFixed = $startDate->format('Y-m-d H:i:s');
                $startDateInLocalFixed = new DateTime($startDateFixed, new DateTimeZone($timeZone));
                $newStartDate = $startDateInLocalFixed->format('Y-m-d H:i:s');
            }

            $endDateInLocal = new DateTime($newEndDate, new DateTimeZone($timeZone));
            if (0 == $endDateInLocal->format('I')) {
                // Is saving time? Then fix UTC time to add time
                $seconds = $endDateInLocal->getOffset();
                $endDate->add(new DateInterval("PT".$seconds."S"));
                $endDateFixed = $endDate->format('Y-m-d H:i:s');
                $endDateInLocalFixed = new DateTime($endDateFixed, new DateTimeZone($timeZone));
                $newEndDate = $endDateInLocalFixed->format('Y-m-d H:i:s');
            }
            $list[] = ['start' => $newStartDate, 'end' => $newEndDate, 'i' => $startDateInLocal->format('I')];
            $counter++;

            // just in case stop if more than $loopMax
            if ($counter > $loopMax) {
                break;
            }
        }

        return $list;
    }

    /**
     * @param int    $eventId
     * @param string $type
     * @param string $end     in UTC
     * @param array  $sentTo
     *
     * @return bool
     */
    public function addRepeatedItem($eventId, $type, $end, $sentTo = [])
    {
        $t_agenda = Database::get_course_table(TABLE_AGENDA);
        $t_agenda_r = Database::get_course_table(TABLE_AGENDA_REPEAT);

        if (empty($this->course)) {
            return false;
        }

        $eventId = (int) $eventId;

        $sql = "SELECT title, content, start_date, end_date, all_day
                FROM $t_agenda
                WHERE iid = $eventId";
        $res = Database::query($sql);

        if (1 !== Database::num_rows($res)) {
            return false;
        }

        $typeList = [
            'daily',
            'weekly',
            'monthlyByDate',
            'monthlyByDay',
            'monthlyByDayR',
            'yearly',
        ];

        if (!in_array($type, $typeList, true)) {
            return false;
        }

        $now = time();
        $endTimeStamp = api_strtotime($end, 'UTC');
        // The event has to repeat *in the future*. We don't allow repeated
        // events in the past
        if ($endTimeStamp < $now) {
            return false;
        }

        $row = Database::fetch_array($res);

        $title = $row['title'];
        $content = $row['content'];
        $allDay = $row['all_day'];

        $type = Database::escape_string($type);
        $end = Database::escape_string($end);
        $sql = "INSERT INTO $t_agenda_r (cal_id, cal_type, cal_end)
                VALUES ('$eventId', '$type', '$endTimeStamp')";
        Database::query($sql);

        $generatedDates = $this->generateDatesByType($type, $row['start_date'], $row['end_date'], $end);

        if (empty($generatedDates)) {
            return false;
        }

        foreach ($generatedDates as $dateInfo) {
            $start = api_get_local_time($dateInfo['start']);
            $end = api_get_local_time($dateInfo['end']);
            $this->addEvent(
                $start,
                $end,
                $allDay,
                $title,
                $content,
                $sentTo,
                false,
                $eventId
            );
        }

        return true;
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
                $eventInfo = $this->get_event($id);
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
                );
                break;
            case 'course':
                $repo = Container::getCalendarEventRepository();
                $em = Database::getManager();
                /** @var CCalendarEvent $event */
                $event = $repo->find($id);

                if (empty($event)) {
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

                        //var_dump($sendTo['everyone'], $usersToDelete, $usersToAdd, $groupsToDelete, $groupToAdd);exit;
                        $links = $event->getResourceNode()->getResourceLinks();

                        if ($sendTo['everyone']) {
                            // Delete all from group
                            if (isset($sentToEvent['groups']) && !empty($sentToEvent['groups'])) {
                                foreach ($sentToEvent['groups'] as $group) {
                                    foreach ($links as $link) {
                                        if ($link->hasGroup() && $link->getGroup()->getIid() === $group) {
                                            $em->remove($link);
                                        }
                                    }
                                }
                            }

                            // Storing the selected users.
                            if (isset($sentToEvent['users']) && !empty($sentToEvent['users'])) {
                                foreach ($sentToEvent['users'] as $userId) {
                                    foreach ($links as $link) {
                                        if ($link->hasUser() && $link->getUser()->getId() === $userId) {
                                            $em->remove($link);
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ($links as $link) {
                                $em->remove($link);
                            }

                            // Add groups
                            if (!empty($groupToAdd)) {
                                foreach ($groupToAdd as $group) {
                                    $group = api_get_group_entity($group);
                                    $event->addCourseLink($courseEntity, $sessionEntity, $group);
                                }
                            }

                            // Delete groups.
                            if (!empty($groupsToDelete)) {
                                foreach ($groupsToDelete as $group) {
                                    foreach ($links as $link) {
                                        if ($link->hasGroup() && $link->getGroup()->getIid() === $group) {
                                            $em->remove($link);
                                        }
                                    }
                                }
                            }

                            // Add users.
                            if (!empty($usersToAdd)) {
                                foreach ($usersToAdd as $userId) {
                                    $event = $event->addUserLink(
                                        api_get_user_entity($userId),
                                        $courseEntity,
                                        $sessionEntity,
                                        $groupEntity
                                    );
                                }
                            }

                            // Delete users.
                            if (!empty($usersToDelete)) {
                                foreach ($usersToDelete as $userId) {
                                    foreach ($links as $link) {
                                        if ($link->hasUser() && $link->getUser()->getId() === $userId) {
                                            $em->remove($link);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $em->persist($event);
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
            case 'admin':
            case 'platform':
                if (api_is_platform_admin()) {
                    $attributes = [
                        'title' => $title,
                        'start_date' => $start,
                        'end_date' => $end,
                        'all_day' => $allDay,
                    ];

                    if ($updateContent) {
                        $attributes['content'] = $content;
                    }
                    Database::update(
                        $this->tbl_global_agenda,
                        $attributes,
                        ['id = ?' => $id]
                    );
                }
                break;
        }
    }

    /**
     * @param int  $id
     * @param bool $deleteAllItemsFromSerie
     */
    public function deleteEvent($id, $deleteAllItemsFromSerie = false)
    {
        switch ($this->type) {
            case 'personal':
                $eventInfo = $this->get_event($id);
                if ($eventInfo['user'] == api_get_user_id()) {
                    Database::delete(
                        $this->tbl_personal_agenda,
                        ['id = ?' => $id]
                    );
                }
                break;
            case 'course':
                $courseId = api_get_course_int_id();
                $isAllowToEdit = $this->getIsAllowedToEdit();

                if (!empty($courseId) && $isAllowToEdit) {
                    // Delete
                    $eventInfo = $this->get_event($id);

                    if ($deleteAllItemsFromSerie) {
                        /* This is one of the children.
                           Getting siblings and delete 'Em all + the father! */
                        if (isset($eventInfo['parent_event_id']) && !empty($eventInfo['parent_event_id'])) {
                            // Removing items.
                            $events = $this->getAllRepeatEvents($eventInfo['parent_event_id']);
                            if (!empty($events)) {
                                foreach ($events as $event) {
                                    $this->deleteEvent($event['id']);
                                }
                            }
                            // Removing parent.
                            $this->deleteEvent($eventInfo['parent_event_id']);
                        } else {
                            // This is the father looking for the children.
                            $events = $this->getAllRepeatEvents($id);
                            if (!empty($events)) {
                                foreach ($events as $event) {
                                    $this->deleteEvent($event['id']);
                                }
                            }
                        }
                    }

                    $repo = Container::getCalendarEventRepository();
                    $event = $repo->find($id);

                    if ($event) {
                        Database::getManager()->remove($event);
                        Database::getManager()->flush();

                        // Removing from events.
                        /*Database::delete(
                            $this->tbl_course_agenda,
                            ['id = ? AND c_id = ?' => [$id, $courseId]]
                        );*/

                        /*api_item_property_update(
                            $this->course,
                            TOOL_CALENDAR_EVENT,
                            $id,
                            'delete',
                            api_get_user_id()
                        );*/

                        // Removing from series.
                        Database::delete(
                            $this->table_repeat,
                            [
                                'cal_id = ?' => [
                                    $id,
                                ],
                            ]
                        );
                        // Attachments are already deleted using the doctrine remove() function.
                        /*if (isset($eventInfo['attachment']) && !empty($eventInfo['attachment'])) {
                            foreach ($eventInfo['attachment'] as $attachment) {
                                self::deleteAttachmentFile(
                                    $attachment['id'],
                                    $this->course
                                );
                            }
                        }*/
                        echo 1;
                    }
                }
                break;
            case 'admin':
                if (api_is_platform_admin()) {
                    Database::delete(
                        $this->tbl_global_agenda,
                        ['id = ?' => $id]
                    );
                }
                break;
        }
    }

    /**
     * Get agenda events.
     *
     * @param int    $start
     * @param int    $end
     * @param int    $courseId
     * @param int    $groupId
     * @param int    $user_id
     * @param string $format
     *
     * @return array|string
     */
    public function getEvents(
        $start,
        $end,
        $courseId = null,
        $groupId = null,
        $user_id = 0,
        $format = 'json'
    ) {
        switch ($this->type) {
            case 'admin':
                $this->getPlatformEvents($start, $end);
                break;
            case 'course':
                $course = api_get_course_entity($courseId);

                // Session coach can see all events inside a session.
                if (api_is_coach()) {
                    // Own course
                    $this->getCourseEvents(
                        $start,
                        $end,
                        $course,
                        $groupId,
                        $this->sessionId,
                        $user_id
                    );

                    // Others
                    $this->getSessionEvents(
                        $start,
                        $end,
                        $this->sessionId,
                        $user_id,
                        $this->eventOtherSessionColor
                    );
                } else {
                    $this->getCourseEvents(
                        $start,
                        $end,
                        $course,
                        $groupId,
                        $this->sessionId,
                        $user_id
                    );
                }
                break;
            case 'personal':
            default:
                $sessionFilterActive = false;
                if (!empty($this->sessionId)) {
                    $sessionFilterActive = true;
                }

                if (false == $sessionFilterActive) {
                    // Getting personal events
                    $this->getPersonalEvents($start, $end);

                    // Getting platform/admin events
                    $this->getPlatformEvents($start, $end);
                }

                $ignoreVisibility = api_get_configuration_value('personal_agenda_show_all_session_events');

                // Getting course events
                $my_course_list = [];
                if (!api_is_anonymous()) {
                    $session_list = SessionManager::get_sessions_by_user(
                        api_get_user_id(),
                        $ignoreVisibility
                    );
                    $my_course_list = CourseManager::get_courses_list_by_user_id(
                        api_get_user_id(),
                        false
                    );
                }

                if (api_is_drh() && api_drh_can_access_all_session_content()) {
                    $session_list = [];
                    $sessionList = SessionManager::get_sessions_followed_by_drh(
                        api_get_user_id(),
                        null,
                        null,
                        null,
                        true,
                        false
                    );

                    if (!empty($sessionList)) {
                        foreach ($sessionList as $sessionItem) {
                            $sessionId = $sessionItem['id'];
                            $courses = SessionManager::get_course_list_by_session_id(
                                $sessionId
                            );
                            $sessionInfo = [
                                'session_id' => $sessionId,
                                'courses' => $courses,
                            ];
                            $session_list[] = $sessionInfo;
                        }
                    }
                }

                if (!empty($session_list)) {
                    foreach ($session_list as $session_item) {
                        if ($sessionFilterActive) {
                            if ($this->sessionId != $session_item['session_id']) {
                                continue;
                            }
                        }

                        $my_courses = $session_item['courses'];
                        $my_session_id = $session_item['session_id'];

                        if (!empty($my_courses)) {
                            foreach ($my_courses as $course_item) {
                                $course = api_get_course_entity($course_item['real_id']);
                                $this->getCourseEvents(
                                    $start,
                                    $end,
                                    $course,
                                    0,
                                    $my_session_id
                                );
                            }
                        }
                    }
                }

                if (!empty($my_course_list) && false == $sessionFilterActive) {
                    foreach ($my_course_list as $courseInfoItem) {
                        $course = api_get_course_entity($courseInfoItem['real_id']);
                        if (isset($courseId) && !empty($courseId)) {
                            if ($course->getId() == $courseId) {
                                $this->getCourseEvents(
                                    $start,
                                    $end,
                                    $course,
                                    0,
                                    0,
                                    $user_id
                                );
                            }
                        } else {
                            $this->getCourseEvents(
                                $start,
                                $end,
                                $course,
                                0,
                                0,
                                $user_id
                            );
                        }
                    }
                }
                break;
        }

        $this->cleanEvents();

        switch ($format) {
            case 'json':
                if (empty($this->events)) {
                    return '[]';
                }

                return json_encode($this->events);
                break;
            case 'array':
                if (empty($this->events)) {
                    return [];
                }

                return $this->events;
                break;
        }
    }

    /**
     * Clean events.
     *
     * @return bool
     */
    public function cleanEvents()
    {
        if (empty($this->events)) {
            return false;
        }

        foreach ($this->events as &$event) {
            $event['description'] = Security::remove_XSS($event['description']);
            $event['title'] = Security::remove_XSS($event['title']);
        }

        return true;
    }

    /**
     * @param int $id
     * @param int $minute_delta
     *
     * @return int
     */
    public function resizeEvent($id, $minute_delta)
    {
        $id = (int) $id;
        $delta = (int) $minute_delta;
        $event = $this->get_event($id);
        if (!empty($event)) {
            switch ($this->type) {
                case 'personal':
                    $sql = "UPDATE $this->tbl_personal_agenda SET
                            enddate = DATE_ADD(enddate, INTERVAL $delta MINUTE)
							WHERE id = ".$id;
                    Database::query($sql);
                    break;
                case 'course':
                    $sql = "UPDATE $this->tbl_course_agenda SET
                            end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE
							    c_id = ".$this->course['real_id']." AND
							    id = ".$id;
                    Database::query($sql);
                    break;
                case 'admin':
                    $sql = "UPDATE $this->tbl_global_agenda SET
                            end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id = ".$id;
                    Database::query($sql);
                    break;
            }
        }

        return 1;
    }

    /**
     * @param int $id
     * @param int $minute_delta minutes
     * @param int $allDay
     *
     * @return int
     */
    public function move_event($id, $minute_delta, $allDay)
    {
        $id = (int) $id;
        $event = $this->get_event($id);

        if (empty($event)) {
            return false;
        }

        // we convert the hour delta into minutes and add the minute delta
        $delta = (int) $minute_delta;
        $allDay = (int) $allDay;

        if (!empty($event)) {
            switch ($this->type) {
                case 'personal':
                    $sql = "UPDATE $this->tbl_personal_agenda SET
                            all_day = $allDay, date = DATE_ADD(date, INTERVAL $delta MINUTE),
                            enddate = DATE_ADD(enddate, INTERVAL $delta MINUTE)
							WHERE id=".$id;
                    Database::query($sql);
                    break;
                case 'course':
                    $sql = "UPDATE $this->tbl_course_agenda SET
                            all_day = $allDay,
                            start_date = DATE_ADD(start_date, INTERVAL $delta MINUTE),
                            end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE
							    c_id = ".$this->course['real_id']." AND
							    id=".$id;
                    Database::query($sql);
                    break;
                case 'admin':
                    $sql = "UPDATE $this->tbl_global_agenda SET
                            all_day = $allDay,
                            start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE),
                            end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id=".$id;
                    Database::query($sql);
                    break;
            }
        }

        return 1;
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
                $sql = "SELECT * FROM ".$this->tbl_personal_agenda."
                        WHERE id = $id AND user = ".api_get_user_id();
                $result = Database::query($sql);
                if (Database::num_rows($result)) {
                    $event = Database::fetch_array($result, 'ASSOC');
                    $event['description'] = $event['text'];
                    $event['content'] = $event['text'];
                    $event['start_date'] = $event['date'];
                    $event['end_date'] = $event['enddate'];
                }
                break;
            case 'course':
                $repo = Container::getCalendarEventRepository();
                /** @var CCalendarEvent $eventEntity */
                $eventEntity = $repo->find($id);

                if (!empty($this->course['real_id'])) {
                    if ($eventEntity) {
                        $event = [];
                        $event['iid'] = $eventEntity->getIid();
                        $event['title'] = $eventEntity->getTitle();
                        $event['content'] = $eventEntity->getContent();
                        $event['all_day'] = $eventEntity->getAllDay();
                        $event['start_date'] = $eventEntity->getStartDate()->format('Y-m-d H:i:s');
                        $event['end_date'] = $eventEntity->getEndDate()->format('Y-m-d H:i:s');
                        $event['description'] = $eventEntity->getComment();

                        // Getting send to array
                        $event['send_to'] = $eventEntity->getUsersAndGroupSubscribedToResource();

                        // Getting repeat info
                        $event['repeat_info'] = $eventEntity->getRepeatEvents();

                        if (!empty($event['parent_event_id'])) {
                            $event['parent_info'] = $eventEntity->getParentEvent();
                        }

                        $event['attachment'] = $eventEntity->getAttachments();
                    }
                }
                break;
            case 'admin':
            case 'platform':
                $sql = "SELECT * FROM ".$this->tbl_global_agenda."
                        WHERE id = $id";
                $result = Database::query($sql);
                if (Database::num_rows($result)) {
                    $event = Database::fetch_array($result, 'ASSOC');
                    $event['description'] = $event['content'];
                }
                break;
        }

        return $event;
    }

    /**
     * Gets personal events.
     *
     * @param int $start
     * @param int $end
     *
     * @return array
     */
    public function getPersonalEvents($start, $end)
    {
        $start = (int) $start;
        $end = (int) $end;
        $startCondition = '';
        $endCondition = '';

        if (0 !== $start) {
            $startCondition = "AND date >= '".api_get_utc_datetime($start)."'";
        }
        if (0 !== $start) {
            $endCondition = "AND (enddate <= '".api_get_utc_datetime($end)."' OR enddate IS NULL)";
        }
        $user_id = api_get_user_id();

        $sql = "SELECT * FROM ".$this->tbl_personal_agenda."
                WHERE user = $user_id $startCondition $endCondition";

        $result = Database::query($sql);
        $my_events = [];
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $event = [];
                $event['id'] = 'personal_'.$row['id'];
                $event['title'] = $row['title'];
                $event['className'] = 'personal';
                $event['borderColor'] = $event['backgroundColor'] = $this->event_personal_color;
                $event['editable'] = true;
                $event['sent_to'] = get_lang('Me');
                $event['type'] = 'personal';

                if (!empty($row['date'])) {
                    $event['start'] = $this->formatEventDate($row['date']);
                    $event['start_date_localtime'] = api_get_local_time($row['date']);
                }

                if (!empty($row['enddate'])) {
                    $event['end'] = $this->formatEventDate($row['enddate']);
                    $event['end_date_localtime'] = api_get_local_time($row['enddate']);
                }

                $event['description'] = $row['text'];
                $event['allDay'] = isset($row['all_day']) && 1 == $row['all_day'] ? $row['all_day'] : 0;
                $event['parent_event_id'] = 0;
                $event['has_children'] = 0;

                $my_events[] = $event;
                $this->events[] = $event;
            }
        }

        // Add plugin personal events
        $this->plugin = new AppPlugin();
        $plugins = $this->plugin->getInstalledPluginListObject();
        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            if ($plugin->hasPersonalEvents && method_exists($plugin, 'getPersonalEvents')) {
                $pluginEvents = $plugin->getPersonalEvents($this, $start, $end);

                if (!empty($pluginEvents)) {
                    $this->events = array_merge($this->events, $pluginEvents);
                }
            }
        }

        return $my_events;
    }

    /**
     * @param int    $start
     * @param int    $end
     * @param int    $sessionId
     * @param int    $userId
     * @param string $color
     *
     * @return array
     */
    public function getSessionEvents(
        $start,
        $end,
        $sessionId = 0,
        $userId = 0,
        $color = ''
    ) {
        $courses = SessionManager::get_course_list_by_session_id($sessionId);

        if (!empty($courses)) {
            foreach ($courses as $course) {
                $course = api_get_course_entity($course['real_id']);
                $this->getCourseEvents(
                    $start,
                    $end,
                    $course,
                    0,
                    $sessionId,
                    0,
                    $color
                );
            }
        }
    }

    /**
     * @param int    $start
     * @param int    $end
     * @param Course $course
     * @param int    $groupId
     * @param int    $sessionId
     * @param int    $user_id
     * @param string $color
     *
     * @return array
     */
    public function getCourseEvents(
        $start,
        $end,
        Course $course,
        $groupId = 0,
        $sessionId = 0,
        $user_id = 0,
        $color = ''
    ) {
        $start = (int) $start;
        $end = (int) $end;

        $start = !empty($start) ? api_get_utc_datetime($start) : null;
        $end = !empty($end) ? api_get_utc_datetime($end) : null;

        if (null === $course) {
            return [];
        }

        $courseId = $course->getId();
        $userId = api_get_user_id();
        $sessionId = (int) $sessionId;
        $user_id = (int) $user_id;

        $groupList = GroupManager::get_group_list(
            null,
            $course,
            null,
            $sessionId
        );

        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
            $isAllowToEdit = true;
        } else {
            $isAllowToEdit = CourseManager::isCourseTeacher($userId, $courseId);
        }

        $isAllowToEditByHrm = false;
        if (!empty($sessionId)) {
            $allowDhrToEdit = api_get_configuration_value('allow_agenda_edit_for_hrm');
            if ($allowDhrToEdit) {
                $isHrm = SessionManager::isUserSubscribedAsHRM($sessionId, $userId);
                if ($isHrm) {
                    $isAllowToEdit = $isAllowToEditByHrm = true;
                }
            }
        }

        $groupMemberships = [];
        if (!empty($groupId)) {
            $groupMemberships = [$groupId];
        } else {
            if ($isAllowToEdit) {
                if (!empty($groupList)) {
                    $groupMemberships = array_column($groupList, 'iid');
                }
            } else {
                // get only related groups from user
                $groupMemberships = GroupManager::get_group_ids($courseId, $userId);
            }
        }

        $repo = Container::getCalendarEventRepository();
        $session = api_get_session_entity($sessionId);
        $qb = $repo->getResourcesByCourseOnly($course, $course->getResourceNode());
        $userCondition = '';

        if ($isAllowToEdit) {
            // No group filter was asked
            if (empty($groupId)) {
                if (empty($user_id)) {
                    // Show all events not added in group
                    $userCondition = ' (links.group IS NULL) ';
                    // admin see only his stuff
                    if ('personal' === $this->type) {
                        $userCondition = " (links.user = ".api_get_user_id()." AND (links.group IS NULL) ";
                        $userCondition .= " OR ( (links.user IS NULL)  AND (links.group IS NULL ))) ";
                    }

                    if (!empty($groupMemberships)) {
                        // Show events sent to selected groups
                        $userCondition .= " OR (links.user IS NULL) AND (links.group IN (".implode(", ", $groupMemberships).")) ";
                    }
                } else {
                    // Show events of requested user in no group
                    $userCondition = " (links.user = $user_id AND links.group IS NULL) ";
                    // Show events sent to selected groups
                    if (!empty($groupMemberships)) {
                        $userCondition .= " OR (links.user = $user_id) AND (links.group IN (".implode(", ", $groupMemberships).")) ";
                    }
                }
            } else {
                // Show only selected groups (depending of user status)
                $userCondition = " (links.user is NULL) AND (links.group IN (".implode(", ", $groupMemberships).")) ";

                if (!empty($groupMemberships)) {
                    // Show send to $user_id in selected groups
                    $userCondition .= " OR (links.user = $user_id) AND (links.group IN (".implode(", ", $groupMemberships).")) ";
                }
            }
        } else {
            // No group filter was asked
            if (empty($groupId)) {
                // Show events sent to everyone and no group
                $userCondition = ' ( (links.user is NULL) AND (links.group IS NULL) ';
                // Show events sent to selected groups
                if (!empty($groupMemberships)) {
                    $userCondition .= " OR (links.user is NULL) AND
                                        (links.group IN (".implode(", ", $groupMemberships)."))) ";
                } else {
                    $userCondition .= " ) ";
                }
                $userCondition .= " OR (links.user = ".api_get_user_id()." AND (links.group IS NULL )) ";
            } else {
                if (!empty($groupMemberships)) {
                    // Show send to everyone - and only selected groups
                    $userCondition = " (links.user is NULL) AND
                                       (links.group IN (".implode(", ", $groupMemberships).")) ";
                }
            }

            // Show sent to only me and no group
            if (!empty($groupMemberships)) {
                $userCondition .= " OR (
                                    links.user = ".api_get_user_id().") AND
                                    (links.group IN (".implode(", ", $groupMemberships).")
                                    ) ";
            }
        }

        if (!empty($userCondition)) {
            $qb->andWhere($userCondition);
        }

        if (!empty($start) && !empty($end)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'resource.startDate BETWEEN :start AND :end',
                    'resource.endDate BETWEEN :start AND :end',
                    $qb->expr()->orX(
                        'resource.startDate IS NOT NULL AND resource.endDate IS NOT NULL AND
                            YEAR(resource.startDate) = YEAR(resource.endDate) AND
                            MONTH(:start) BETWEEN MONTH(resource.startDate) AND MONTH(resource.endDate)
                        '
                    )
                )
            )
            ->setParameter('start', $start)
            ->setParameter('end', $end);
        }

        /*
        if (empty($sessionId)) {
            $sessionCondition = "
            (
                agenda.session_id = 0 AND (ip.session_id IS NULL OR ip.session_id = 0)
            ) ";
        } else {
            $sessionCondition = "
            (
                agenda.session_id = $sessionId AND
                ip.session_id = $sessionId
            ) ";
        }

        if (api_is_allowed_to_edit()) {
            $visibilityCondition = " (ip.visibility IN ('1', '0'))  ";
        } else {
            $visibilityCondition = " (ip.visibility = '1') ";
        }

        $sql = "SELECT DISTINCT
                    agenda.*,
                    ip.visibility,
                    ip.to_group_id,
                    ip.insert_user_id,
                    ip.ref,
                    to_user_id
                FROM $tlb_course_agenda agenda
                INNER JOIN $tbl_property ip
                ON (
                    agenda.id = ip.ref AND
                    agenda.c_id = ip.c_id AND
                    ip.tool = '".TOOL_CALENDAR_EVENT."'
                )
                WHERE
                    $sessionCondition AND
                    ($userCondition) AND
                    $visibilityCondition AND
                    agenda.c_id = $courseId
        ";
        $dateCondition = '';
        if (!empty($start) && !empty($end)) {
            $dateCondition .= "AND (
                 agenda.start_date BETWEEN '".$start."' AND '".$end."' OR
                 agenda.end_date BETWEEN '".$start."' AND '".$end."' OR
                 (
                     agenda.start_date IS NOT NULL AND agenda.end_date IS NOT NULL AND
                     YEAR(agenda.start_date) = YEAR(agenda.end_date) AND
                     MONTH('$start') BETWEEN MONTH(agenda.start_date) AND MONTH(agenda.end_date)
                 )
            )";
        }

        $sql .= $dateCondition;
        $result = Database::query($sql);*/
        $coachCanEdit = false;
        if (!empty($sessionId)) {
            $coachCanEdit = api_is_coach($sessionId, $courseId) || api_is_platform_admin();
        }
        $events = $qb->getQuery()->getResult();

        $repo = Container::getCalendarEventAttachmentRepository();

        /** @var CCalendarEvent $row */
        foreach ($events as $row) {
            $eventId = $row->getIid();
            $event = [];
            $event['id'] = 'course_'.$eventId;
            $event['unique_id'] = $eventId;

            $eventsAdded[] = $eventId;
            $attachmentList = $row->getAttachments();
            $event['attachment'] = '';
            if (!empty($attachmentList)) {
                $icon = Display::returnFontAwesomeIcon(
                    'paperclip',
                    '1'
                );
                /** @var CCalendarEventAttachment $attachment */
                foreach ($attachmentList as $attachment) {
                    $url = $repo->getResourceFileDownloadUrl($attachment).'?'.api_get_cidreq();
                    $event['attachment'] .= $icon.
                        Display::url(
                            $attachment->getFilename(),
                            $url
                        ).'<br />';
                }
            }

            $event['title'] = $row->getTitle();
            $event['className'] = 'course';
            $event['allDay'] = 'false';
            $event['course_id'] = $courseId;
            $event['borderColor'] = $event['backgroundColor'] = $this->event_course_color;

            $sessionInfo = [];
            /*if (!empty($row->getSessionId())) {
                $sessionInfo = api_get_session_info($row->getSessionId());
                $event['borderColor'] = $event['backgroundColor'] = $this->event_session_color;
            }*/

            $event['session_name'] = $sessionInfo['name'] ?? '';
            $event['course_name'] = $course->getTitle();

            /*if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
                $event['borderColor'] = $event['backgroundColor'] = $this->event_group_color;
            }*/

            if (!empty($color)) {
                $event['borderColor'] = $event['backgroundColor'] = $color;
            }

            if ($row->getColor()) {
                $event['borderColor'] = $event['backgroundColor'] = $row->getColor();
            }

            $event['resourceEditable'] = false;
            if ($this->getIsAllowedToEdit() && 'course' === $this->type) {
                $event['resourceEditable'] = true;
                if (!empty($sessionId)) {
                    if (false == $coachCanEdit) {
                        $event['resourceEditable'] = false;
                    }
                    if ($isAllowToEditByHrm) {
                        $event['resourceEditable'] = true;
                    }
                }
                // if user is author then he can edit the item
                if (api_get_user_id() == $row->getResourceNode()->getCreator()->getId()) {
                    $event['resourceEditable'] = true;
                }
            }

            if (!empty($row->getStartDate())) {
                $event['start'] = $this->formatEventDate($row->getStartDate()->format('Y-m-d H:i:s'));
                $event['start_date_localtime'] = api_get_local_time($row->getStartDate()->format('Y-m-d H:i:s'));
            }
            if (!empty($row->getEndDate())) {
                $event['end'] = $this->formatEventDate($row->getEndDate()->format('Y-m-d H:i:s'));
                $event['end_date_localtime'] = api_get_local_time($row->getEndDate()->format('Y-m-d H:i:s'));
            }

            $event['sent_to'] = '';
            $event['type'] = 'course';
            /*if (0 != $row->getSessionId()) {
                $event['type'] = 'session';
            }*/

            $everyone = false;
            $links = $row->getResourceNode()->getResourceLinks();
            $sentTo = [];
            foreach ($links as $link) {
                if ($link->getUser()) {
                    $sentTo[] = $link->getUser()->getFirstname();
                }
                if ($link->getCourse()) {
                    $sentTo[] = $link->getCourse()->getName();
                }
                if ($link->getSession()) {
                    $sentTo[] = $link->getSession()->getName();
                }
                if ($link->getGroup()) {
                    $sentTo[] = $link->getGroup()->getName();
                }
            }

            // Event Sent to a group?
            /*if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
                $sent_to = [];
                if (!empty($group_to_array)) {
                    foreach ($group_to_array as $group_item) {
                        $sent_to[] = $groupNameList[$group_item];
                    }
                }
                $sent_to = implode('@@', $sent_to);
                $sent_to = str_replace(
                    '@@',
                    '</div><div class="label_tag notice">',
                    $sent_to
                );
                $event['sent_to'] = '<div class="label_tag notice">'.$sent_to.'</div>';
                $event['type'] = 'group';
            }*/

            // Event sent to a user?
            /*if (isset($row['to_user_id'])) {
                $sent_to = [];
                if (!empty($user_to_array)) {
                    foreach ($user_to_array as $item) {
                        $user_info = api_get_user_info($item);
                        // Add username as tooltip for $event['sent_to'] - ref #4226
                        $username = api_htmlentities(
                            sprintf(
                                get_lang('Login: %s'),
                                $user_info['username']
                            ),
                            ENT_QUOTES
                        );
                        $sent_to[] = "<span title='".$username."'>".$user_info['complete_name']."</span>";
                    }
                }
                $sent_to = implode('@@', $sent_to);
                $sent_to = str_replace(
                    '@@',
                    '</div><div class="label_tag notice">',
                    $sent_to
                );
                $event['sent_to'] = '<div class="label_tag notice">'.$sent_to.'</div>';
            }*/

            //Event sent to everyone!
            /*if (empty($event['sent_to'])) {
                $event['sent_to'] = '<div class="label_tag notice">'.get_lang('Everyone').'</div>';
            }*/
            $event['sent_to'] = implode('<br />', $sentTo);
            $event['description'] = $row->getContent();
            $event['visibility'] = $row->isVisible($course, $session) ? 1 : 0;
            $event['real_id'] = $eventId;
            $event['allDay'] = $row->getAllDay();
            $event['parent_event_id'] = $row->getParentEvent() ? $row->getParentEvent()->getIid() : null;
            $event['has_children'] = $row->getChildren()->count() > 0;
            $event['comment'] = $row->getComment();
            $this->events[] = $event;
        }

        return $this->events;
    }

    /**
     * @param int $start tms
     * @param int $end   tms
     *
     * @return array
     */
    public function getPlatformEvents($start, $end)
    {
        $start = isset($start) && !empty($start) ? api_get_utc_datetime(intval($start)) : null;
        $end = isset($end) && !empty($end) ? api_get_utc_datetime(intval($end)) : null;
        $dateCondition = '';

        if (!empty($start) && !empty($end)) {
            $dateCondition .= "AND (
                 start_date BETWEEN '".$start."' AND '".$end."' OR
                 end_date BETWEEN '".$start."' AND '".$end."' OR
                 (
                     start_date IS NOT NULL AND end_date IS NOT NULL AND
                     YEAR(start_date) = YEAR(end_date) AND
                     MONTH('$start') BETWEEN MONTH(start_date) AND MONTH(end_date)
                 )
            )";
        }

        $access_url_id = api_get_current_access_url_id();

        $sql = "SELECT *
                FROM ".$this->tbl_global_agenda."
                WHERE access_url_id = $access_url_id
                $dateCondition";
        $result = Database::query($sql);
        $my_events = [];
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $event = [];
                $event['id'] = 'platform_'.$row['id'];
                $event['title'] = $row['title'];
                $event['className'] = 'platform';
                $event['allDay'] = 'false';
                $event['borderColor'] = $event['backgroundColor'] = $this->event_platform_color;
                $event['editable'] = false;
                $event['type'] = 'admin';

                if (api_is_platform_admin() && 'admin' === $this->type) {
                    $event['editable'] = true;
                }

                if (!empty($row['start_date'])) {
                    $event['start'] = $this->formatEventDate($row['start_date']);
                    $event['start_date_localtime'] = api_get_local_time($row['start_date']);
                }

                if (!empty($row['end_date'])) {
                    $event['end'] = $this->formatEventDate($row['end_date']);
                    $event['end_date_localtime'] = api_get_local_time($row['end_date']);
                }
                $event['allDay'] = isset($row['all_day']) && 1 == $row['all_day'] ? $row['all_day'] : 0;
                $event['parent_event_id'] = 0;
                $event['has_children'] = 0;
                $event['description'] = $row['content'];

                $my_events[] = $event;
                $this->events[] = $event;
            }
        }

        return $my_events;
    }

    /**
     * @param CGroup[] $groupList
     * @param array    $userList
     * @param array    $sendTo               array('users' => [1, 2], 'groups' => [3, 4])
     * @param array    $attributes
     * @param bool     $addOnlyItemsInSendTo
     * @param bool     $required
     */
    public function setSendToSelect(
        FormValidator $form,
        $groupList = [],
        $userList = [],
        $sendTo = [],
        $attributes = [],
        $addOnlyItemsInSendTo = false,
        $required = false
    ) {
        $params = [
            'id' => 'users_to_send_id',
            'data-placeholder' => get_lang('Select'),
            'multiple' => 'multiple',
            'class' => 'multiple-select',
        ];

        if (!empty($attributes)) {
            $params = array_merge($params, $attributes);
            if (empty($params['multiple'])) {
                unset($params['multiple']);
            }
        }

        $sendToGroups = isset($sendTo['groups']) ? $sendTo['groups'] : [];
        $sendToUsers = isset($sendTo['users']) ? $sendTo['users'] : [];

        $select = $form->addSelect(
            'users_to_send',
            get_lang('To'),
            null,
            $params
        );

        if ($required) {
            $form->setRequired($select);
        }

        $selectedEveryoneOptions = [];
        if (isset($sendTo['everyone']) && $sendTo['everyone']) {
            $selectedEveryoneOptions = ['selected'];
            $sendToUsers = [];
        }

        $select->addOption(
            get_lang('Everyone'),
            'everyone',
            $selectedEveryoneOptions
        );

        $options = [];
        if (is_array($groupList)) {
            foreach ($groupList as $group) {
                $groupId = $group->getIid();
                $count = $group->getMembers()->count();
                $countUsers = " &ndash; $count ".get_lang('Users');
                $option = [
                    'text' => $group->getName().$countUsers,
                    'value' => "GROUP:".$groupId,
                ];

                $selected = in_array(
                    $groupId,
                    $sendToGroups
                ) ? true : false;
                if ($selected) {
                    $option['selected'] = 'selected';
                }

                if ($addOnlyItemsInSendTo) {
                    if ($selected) {
                        $options[] = $option;
                    }
                } else {
                    $options[] = $option;
                }
            }
            $select->addOptGroup($options, get_lang('Groups'));
        }

        // adding the individual users to the select form
        if (is_array($userList)) {
            $options = [];
            foreach ($userList as $user) {
                if (ANONYMOUS == $user['status']) {
                    continue;
                }
                $option = [
                    'text' => api_get_person_name(
                            $user['firstname'],
                            $user['lastname']
                        ).' ('.$user['username'].')',
                    'value' => "USER:".$user['user_id'],
                ];

                $selected = in_array(
                    $user['user_id'],
                    $sendToUsers
                ) ? true : false;

                if ($selected) {
                    $option['selected'] = 'selected';
                }

                if ($addOnlyItemsInSendTo) {
                    if ($selected) {
                        $options[] = $option;
                    }
                } else {
                    $options[] = $option;
                }
            }

            $select->addOptGroup($options, get_lang('Users'));
        }
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
     * @param array $params
     *
     * @return FormValidator
     */
    public function getForm($params = [])
    {
        $action = isset($params['action']) ? Security::remove_XSS($params['action']) : null;
        $id = isset($params['id']) ? (int) $params['id'] : 0;

        $url = api_get_self().'?action='.$action.'&id='.$id.'&type='.$this->type;
        if ('course' === $this->type) {
            $url = api_get_self().'?'.api_get_cidreq().'&action='.$action.'&id='.$id.'&type='.$this->type;
        }

        $form = new FormValidator(
            'add_event',
            'post',
            $url,
            null,
            ['enctype' => 'multipart/form-data']
        );

        $idAttach = isset($params['id_attach']) ? (int) $params['id_attach'] : null;
        $groupId = api_get_group_id();
        $form_Title = get_lang('Add event to agenda');
        if (!empty($id)) {
            $form_Title = get_lang('Edit event');
        }

        $form->addHeader($form_Title);
        $form->addHidden('id', $id);
        $form->addHidden('action', $action);
        $form->addHidden('id_attach', $idAttach);

        $isSubEventEdition = false;
        $isParentFromSerie = false;
        $showAttachmentForm = true;

        if ('course' === $this->type) {
            // Edition mode.
            if (!empty($id)) {
                $showAttachmentForm = false;
                if (isset($params['parent_event_id']) && !empty($params['parent_event_id'])) {
                    $isSubEventEdition = true;
                }
                if (!empty($params['repeat_info'])) {
                    $isParentFromSerie = true;
                }
            }
        }

        if ($isSubEventEdition) {
            $form->addElement(
                'label',
                null,
                Display::return_message(
                    get_lang('Editing this event will remove it from the serie of events it is currently part of'),
                    'warning'
                )
            );
        }

        $form->addElement('text', 'title', get_lang('Event name'));

        if (isset($groupId) && !empty($groupId)) {
            $form->addElement(
                'hidden',
                'users_to_send[]',
                "GROUP:$groupId"
            );
            $form->addElement('hidden', 'to', 'true');
        } else {
            $sendTo = isset($params['send_to']) ? $params['send_to'] : ['everyone' => true];
            if ('course' === $this->type) {
                $this->showToForm($form, $sendTo, [], false, true);
            }
        }

        $form->addDateRangePicker(
            'date_range',
            get_lang('Date range'),
            false,
            ['id' => 'date_range']
        );
        $form->addElement('checkbox', 'all_day', null, get_lang('All day'));

        if ('course' === $this->type) {
            $repeat = $form->addElement(
                'checkbox',
                'repeat',
                null,
                get_lang('Repeat event'),
                ['onclick' => 'return plus_repeated_event();']
            );
            $form->addElement(
                'html',
                '<div id="options2" style="display:none">'
            );
            $form->addElement(
                'select',
                'repeat_type',
                get_lang('Repeat type'),
                self::getRepeatTypes()
            );
            $form->addElement(
                'date_picker',
                'repeat_end_day',
                get_lang('Repeat end date'),
                ['id' => 'repeat_end_date_form']
            );

            if ($isSubEventEdition || $isParentFromSerie) {
                $repeatInfo = $params['repeat_info'];
                if ($isSubEventEdition) {
                    $parentEvent = $params['parent_info'];
                    $repeatInfo = $parentEvent['repeat_info'];
                }
                $params['repeat'] = 1;
                $params['repeat_type'] = $repeatInfo['cal_type'];
                $params['repeat_end_day'] = substr(
                    api_get_local_time($repeatInfo['cal_end']),
                    0,
                    10
                );

                $form->freeze(['repeat_type', 'repeat_end_day']);
                $repeat->_attributes['disabled'] = 'disabled';
            }
            $form->addElement('html', '</div>');
        }

        if (!empty($id)) {
            if (empty($params['end_date'])) {
                $params['date_range'] = $params['end_date'];
            }

            $params['date_range'] =
                substr(api_get_local_time($params['start_date']), 0, 16).' / '.
                substr(api_get_local_time($params['end_date']), 0, 16);
        }

        $toolbar = 'Agenda';
        if (!api_is_allowed_to_edit(null, true)) {
            $toolbar = 'AgendaStudent';
        }

        $form->addHtmlEditor(
            'content',
            get_lang('Description'),
            null,
            [
                'ToolbarSet' => $toolbar,
                'Width' => '100%',
                'Height' => '200',
            ]
        );

        if ('course' === $this->type) {
            $form->addTextarea('comment', get_lang('Comment'));
            $form->addLabel(
                get_lang('Files attachments'),
                '<div id="filepaths" class="file-upload-event">

                        <div id="filepath_1">
                            <input type="file" name="attach_1"/>

                            <label>'.get_lang('Description').'</label>
                            <input class="form-control" type="text" name="legend[]" />
                        </div>

                    </div>'
            );

            $form->addLabel(
                '',
                '<span id="link-more-attach">
                    <a href="javascript://" onclick="return add_image_form()">'.
                get_lang('Add one more file').'</a>
                 </span>&nbsp;('.sprintf(
                    get_lang('Maximun file size: %s'),
                    format_file_size(
                        api_get_setting('message_max_upload_filesize')
                    )
                ).')'
            );

            if (isset($params['attachment']) && !empty($params['attachment'])) {
                $attachmentList = $params['attachment'];
                /** @var CCalendarEventAttachment $attachment */
                foreach ($attachmentList as $attachment) {
                    $form->addElement(
                        'checkbox',
                        'delete_attachment['.$attachment->getIid().']',
                        null,
                        get_lang('Delete attachment').': '.$attachment->getFilename()
                    );
                }
            }

            $form->addTextarea(
                'file_comment',
                get_lang('File comment')
            );
        }

        if (empty($id)) {
            $form->addElement(
                'checkbox',
                'add_announcement',
                null,
                get_lang('Add an announcement').'&nbsp('.get_lang('Send mail').')'
            );
        }

        if ($id) {
            $form->addButtonUpdate(get_lang('Edit event'));
        } else {
            $form->addButtonSave(get_lang('Add event'));
        }

        $form->setDefaults($params);
        $form->addRule(
            'date_range',
            get_lang('Required field'),
            'required'
        );
        $form->addRule('title', get_lang('Required field'), 'required');

        return $form;
    }

    /**
     * @param FormValidator $form
     * @param array         $sendTo               array('everyone' => false, 'users' => [1, 2], 'groups' => [3, 4])
     * @param array         $attributes
     * @param bool          $addOnlyItemsInSendTo
     * @param bool          $required
     *
     * @return bool
     */
    public function showToForm(
        $form,
        $sendTo = [],
        $attributes = [],
        $addOnlyItemsInSendTo = false,
        $required = false
    ) {
        if ('course' !== $this->type) {
            return false;
        }

        $order = 'lastname';
        if (api_is_western_name_order()) {
            $order = 'firstname';
        }

        $userList = CourseManager::get_user_list_from_course_code(
            api_get_course_id(),
            $this->sessionId,
            null,
            $order
        );

        $groupList = CourseManager::get_group_list_of_course(
            api_get_course_id(),
            $this->sessionId
        );

        $this->setSendToSelect(
            $form,
            $groupList,
            $userList,
            $sendTo,
            $attributes,
            $addOnlyItemsInSendTo,
            $required
        );

        return true;
    }

    /**
     * @param int   $id
     * @param int   $visibility 0= invisible, 1 visible
     * @param array $courseInfo
     * @param int   $userId
     */
    public static function changeVisibility(
        $id,
        $visibility,
        $courseInfo,
        $userId = null
    ) {
        $id = (int) $id;

        $repo = Container::getCalendarEventRepository();
        /** @var CCalendarEvent $event */
        $event = $repo->find($id);
        $visibility = (int) $visibility;

        if ($event) {
            if (0 === $visibility) {
                $repo->setVisibilityDraft($event);
            } else {
                $repo->setVisibilityPublished($event);
            }
        }

        return true;
    }

    /**
     * Get repeat types.
     */
    public static function getRepeatTypes(): array
    {
        return [
            'daily' => get_lang('Daily'),
            'weekly' => get_lang('Weekly'),
            'monthlyByDate' => get_lang('Monthly, by date'),
            //monthlyByDay"> get_lang('Monthly, by day');
            //monthlyByDayR' => get_lang('Monthly, by dayR'),
            'yearly' => get_lang('Yearly'),
        ];
    }

    /**
     * Show a list with all the attachments according to the post's id.
     *
     * @param int   $attachmentId
     * @param int   $eventId
     * @param array $courseInfo
     *
     * @return array with the post info
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
            $row = Database::fetch_array($result, 'ASSOC');
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
            $attachment = new CCalendarEventAttachment();
            $attachment
                ->setFilename($fileName)
                ->setComment($comment)
                ->setEvent($event)
                ->setParent($event)
                ->addCourseLink(
                    api_get_course_entity(),
                    api_get_session_entity(),
                    api_get_group_entity()
                );

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
     * @param int $eventId
     *
     * @return array
     */
    public function getAllRepeatEvents($eventId)
    {
        $events = [];
        $eventId = (int) $eventId;

        switch ($this->type) {
            case 'personal':
                break;
            case 'course':
                if (!empty($this->course['real_id'])) {
                    $sql = "SELECT * FROM ".$this->tbl_course_agenda."
                            WHERE
                                c_id = ".$this->course['real_id']." AND
                                parent_event_id = ".$eventId;
                    $result = Database::query($sql);
                    if (Database::num_rows($result)) {
                        while ($row = Database::fetch_array($result, 'ASSOC')) {
                            $events[] = $row;
                        }
                    }
                }
                break;
        }

        return $events;
    }

    /**
     * @param int    $filter
     * @param string $view
     *
     * @return string
     */
    public function displayActions($view, $filter = 0)
    {
        $group = api_get_group_entity();
        $groupIid = null === $group ? 0 : $group->getIid();
        $codePath = api_get_path(WEB_CODE_PATH);

        $currentUserId = api_get_user_id();
        $cidReq = api_get_cidreq();

        $actionsLeft = '';
        $actionsLeft .= Display::url(
            Display::return_icon('calendar.png', get_lang('Calendar'), [], ICON_SIZE_MEDIUM),
            $codePath."calendar/agenda_js.php?type={$this->type}&$cidReq"
        );
        $actionsLeft .= Display::url(
            Display::return_icon('week.png', get_lang('Agenda list'), [], ICON_SIZE_MEDIUM),
            $codePath."calendar/agenda_list.php?type={$this->type}&$cidReq"
        );

        $form = '';
        if (api_is_allowed_to_edit(false, true) ||
            ('1' == api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()) &&
            api_is_allowed_to_session_edit(false, true)
            || (
                GroupManager::userHasAccess($currentUserId, $group, GroupManager::GROUP_TOOL_CALENDAR)
                && GroupManager::isTutorOfGroup($currentUserId, $group)
            )
        ) {
            $actionsLeft .= Display::url(
                Display::return_icon('new_event.png', get_lang('Add event'), [], ICON_SIZE_MEDIUM),
                $codePath."calendar/agenda.php?action=add&type={$this->type}&$cidReq"
            );

            $actionsLeft .= Display::url(
                Display::return_icon('import_calendar.png', get_lang('Outlook import'), [], ICON_SIZE_MEDIUM),
                $codePath."calendar/agenda.php?action=importical&type={$this->type}&$cidReq"
            );

            if ('course' === $this->type) {
                if (!isset($_GET['action'])) {
                    $form = new FormValidator(
                        'form-search',
                        'post',
                        '',
                        '',
                        [],
                        FormValidator::LAYOUT_INLINE
                    );
                    $attributes = [
                        'multiple' => false,
                        'id' => 'select_form_id_search',
                    ];
                    $selectedValues = $this->parseAgendaFilter($filter);
                    $this->showToForm($form, $selectedValues, $attributes);
                    $form = $form->returnForm();
                }
            }
        }

        if ('personal' === $this->type && !api_is_anonymous()) {
            $actionsLeft .= Display::url(
                Display::return_icon('1day.png', get_lang('Sessions plan calendar'), [], ICON_SIZE_MEDIUM),
                $codePath."calendar/planification.php"
            );

            if (api_is_student_boss() || api_is_platform_admin()) {
                $actionsLeft .= Display::url(
                    Display::return_icon('calendar-user.png', get_lang('MyStudentsSchedule'), [], ICON_SIZE_MEDIUM),
                    $codePath.'mySpace/calendar_plan.php'
                );
            }
        }

        if (api_is_platform_admin() ||
            api_is_teacher() ||
            api_is_student_boss() ||
            api_is_drh() ||
            api_is_session_admin() ||
            api_is_coach()
        ) {
            if ('personal' === $this->type) {
                $form = null;
                if (!isset($_GET['action'])) {
                    $form = new FormValidator(
                        'form-search',
                        'get',
                        api_get_self().'?type=personal&',
                        '',
                        [],
                        FormValidator::LAYOUT_INLINE
                    );

                    $sessions = [];

                    if (api_is_drh()) {
                        $sessionList = SessionManager::get_sessions_followed_by_drh($currentUserId);
                        if (!empty($sessionList)) {
                            foreach ($sessionList as $sessionItem) {
                                $sessions[$sessionItem['id']] = strip_tags($sessionItem['name']);
                            }
                        }
                    } else {
                        $sessions = SessionManager::get_sessions_by_user($currentUserId);
                        $sessions = array_column($sessions, 'session_name', 'session_id');
                    }

                    $form->addHidden('type', 'personal');
                    $sessions = ['0' => get_lang('Please select an option')] + $sessions;

                    $form->addSelect(
                        'session_id',
                        get_lang('Session'),
                        $sessions,
                        ['id' => 'session_id', 'onchange' => 'submit();']
                    );

                    $form->addButtonReset(get_lang('Reset'));
                    $form = $form->returnForm();
                }
            }
        }

        $actionsRight = '';
        if ('calendar' === $view) {
            $actionsRight .= $form;
        }

        return Display::toolbarAction(
            'toolbar-agenda',
            [$actionsLeft, $actionsRight]
        );
    }

    /**
     * @return FormValidator
     */
    public function getImportCalendarForm()
    {
        $form = new FormValidator(
            'frm_import_ical',
            'post',
            api_get_self().'?action=importical&type='.$this->type,
            ['enctype' => 'multipart/form-data']
        );
        $form->addHeader(get_lang('Outlook import'));
        $form->addElement('file', 'ical_import', get_lang('Outlook import'));
        $form->addRule(
            'ical_import',
            get_lang('Required field'),
            'required'
        );
        $form->addButtonImport(get_lang('Import'), 'ical_submit');

        return $form;
    }

    /**
     * @param array $courseInfo
     * @param $file
     *
     * @return false|string
     */
    public function importEventFile($courseInfo, $file)
    {
        $charset = api_get_system_encoding();
        $filepath = api_get_path(SYS_ARCHIVE_PATH).$file['name'];
        $messages = [];

        if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log(
                'Problem moving uploaded file: '.$file['error'].' in '.__FILE__.' line '.__LINE__
            );

            return false;
        }

        $data = file_get_contents($filepath);

        $trans = [
            'DAILY' => 'daily',
            'WEEKLY' => 'weekly',
            'MONTHLY' => 'monthlyByDate',
            'YEARLY' => 'yearly',
        ];
        $sentTo = ['everyone' => true];
        $calendar = Sabre\VObject\Reader::read($data);
        $currentTimeZone = api_get_timezone();
        if (!empty($calendar->VEVENT)) {
            foreach ($calendar->VEVENT as $event) {
                $start = $event->DTSTART->getDateTime();
                $end = $event->DTEND->getDateTime();
                //Sabre\VObject\DateTimeParser::parseDateTime(string $dt, \Sabre\VObject\DateTimeZone $tz)

                $startDateTime = api_get_local_time(
                    $start->format('Y-m-d H:i:s'),
                    $currentTimeZone,
                    $start->format('e')
                );
                $endDateTime = api_get_local_time(
                    $end->format('Y-m-d H:i'),
                    $currentTimeZone,
                    $end->format('e')
                );
                $title = api_convert_encoding(
                    (string) $event->summary,
                    $charset,
                    'UTF-8'
                );
                $description = api_convert_encoding(
                    (string) $event->description,
                    $charset,
                    'UTF-8'
                );

                $id = $this->addEvent(
                    $startDateTime,
                    $endDateTime,
                    'false',
                    $title,
                    $description,
                    $sentTo
                );

                $messages[] = " $title - ".$startDateTime." - ".$endDateTime;

                //$attendee = (string)$event->attendee;
                /** @var Sabre\VObject\Property\ICalendar\Recur $repeat */
                $repeat = $event->RRULE;
                if ($id && !empty($repeat)) {
                    $repeat = $repeat->getParts();
                    $freq = $trans[$repeat['FREQ']];

                    if (isset($repeat['UNTIL']) && !empty($repeat['UNTIL'])) {
                        // Check if datetime or just date (strlen == 8)
                        if (8 == strlen($repeat['UNTIL'])) {
                            // Fix the datetime format to avoid exception in the next step
                            $repeat['UNTIL'] .= 'T000000';
                        }
                        $until = Sabre\VObject\DateTimeParser::parseDateTime(
                            $repeat['UNTIL'],
                            new DateTimeZone($currentTimeZone)
                        );
                        $until = $until->format('Y-m-d H:i:s');
                        $this->addRepeatedItem(
                            $id,
                            $freq,
                            $until,
                            $sentTo
                        );
                    }
                }
            }
        }

        if (!empty($messages)) {
            $messages = implode('<br /> ', $messages);
        } else {
            $messages = get_lang('There are no events');
        }

        return $messages;
    }

    /**
     * Parse filter turns USER:12 to ['users' => [12])] or G:1 ['groups' => [1]].
     *
     * @param int $filter
     *
     * @return array
     */
    public function parseAgendaFilter($filter)
    {
        $everyone = false;
        $groupId = null;
        $userId = null;

        if ('everyone' === $filter) {
            $everyone = true;
        } else {
            if ('G' === substr($filter, 0, 1)) {
                $groupId = str_replace('GROUP:', '', $filter);
            } else {
                $userId = str_replace('USER:', '', $filter);
            }
        }
        if (empty($userId) && empty($groupId)) {
            $everyone = true;
        }

        return [
            'everyone' => $everyone,
            'users' => [$userId],
            'groups' => [$groupId],
        ];
    }

    /**
     *    This function retrieves all the agenda items of all the courses the user is subscribed to.
     */
    public static function get_myagendaitems(
        $user_id,
        $courses_dbs,
        $month,
        $year
    ) {
        $user_id = (int) $user_id;

        $items = [];
        $my_list = [];

        // get agenda-items for every course
        foreach ($courses_dbs as $key => $array_course_info) {
            //databases of the courses
            $TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);
            $TABLE_ITEMPROPERTY = Database::get_course_table(
                TABLE_ITEM_PROPERTY
            );

            $group_memberships = GroupManager::get_group_ids(
                $array_course_info['real_id'],
                $user_id
            );
            $course_user_status = CourseManager::getUserInCourseStatus(
                $user_id,
                $array_course_info['real_id']
            );
            // if the user is administrator of that course we show all the agenda items
            if ('1' == $course_user_status) {
                //echo "course admin";
                $sqlquery = "SELECT DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
							FROM ".$TABLEAGENDA." agenda,
								 ".$TABLE_ITEMPROPERTY." ip
							WHERE agenda.id = ip.ref
							AND MONTH(agenda.start_date)='".$month."'
							AND YEAR(agenda.start_date)='".$year."'
							AND ip.tool='".TOOL_CALENDAR_EVENT."'
							AND ip.visibility='1'
							GROUP BY agenda.id
							ORDER BY start_date ";
            } else {
                // if the user is not an administrator of that course
                if (is_array($group_memberships) && count(
                        $group_memberships
                    ) > 0
                ) {
                    $sqlquery = "SELECT	agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
								FROM ".$TABLEAGENDA." agenda,
									".$TABLE_ITEMPROPERTY." ip
								WHERE agenda.id = ip.ref
								AND MONTH(agenda.start_date)='".$month."'
								AND YEAR(agenda.start_date)='".$year."'
								AND ip.tool='".TOOL_CALENDAR_EVENT."'
								AND	( ip.to_user_id='".$user_id."' OR (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".implode(
                            ", ",
                            $group_memberships
                        ).")) )
								AND ip.visibility='1'
								ORDER BY start_date ";
                } else {
                    $sqlquery = "SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
								FROM ".$TABLEAGENDA." agenda,
									".$TABLE_ITEMPROPERTY." ip
								WHERE agenda.id = ip.ref
								AND MONTH(agenda.start_date)='".$month."'
								AND YEAR(agenda.start_date)='".$year."'
								AND ip.tool='".TOOL_CALENDAR_EVENT."'
								AND ( ip.to_user_id='".$user_id."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL)
								AND ip.visibility='1'
								ORDER BY start_date ";
                }
            }
            $result = Database::query($sqlquery);

            while ($item = Database::fetch_array($result, 'ASSOC')) {
                $agendaday = -1;
                if (!empty($item['start_date'])) {
                    $item['start_date'] = api_get_local_time(
                        $item['start_date']
                    );
                    $item['start_date_tms'] = api_strtotime(
                        $item['start_date']
                    );
                    $agendaday = date("j", $item['start_date_tms']);
                }
                if (!empty($item['end_date'])) {
                    $item['end_date'] = api_get_local_time($item['end_date']);
                }

                $url = api_get_path(
                        WEB_CODE_PATH
                    )."calendar/agenda.php?cidReq=".urlencode(
                        $array_course_info["code"]
                    )."&day=$agendaday&month=$month&year=$year#$agendaday";

                $item['url'] = $url;
                $item['course_name'] = $array_course_info['title'];
                $item['calendar_type'] = 'course';
                $item['course_id'] = $array_course_info['course_id'];

                $my_list[$agendaday][] = $item;
            }
        }

        // sorting by hour for every day
        $agendaitems = [];
        foreach ($items as $agendaday => $tmpitems) {
            if (!isset($agendaitems[$agendaday])) {
                $agendaitems[$agendaday] = '';
            }
            sort($tmpitems);
            foreach ($tmpitems as $val) {
                $agendaitems[$agendaday] .= $val;
            }
        }

        return $my_list;
    }

    /**
     * This function retrieves one personal agenda item returns it.
     *
     * @param    array    The array containing existing events. We add to this array.
     * @param    int        Day
     * @param    int        Month
     * @param    int        Year (4 digits)
     * @param    int        Week number
     * @param    string    Type of view (month_view, week_view, day_view)
     *
     * @return array The results of the database query, or null if not found
     */
    public static function get_global_agenda_items(
        $agendaitems,
        $day,
        $month,
        $year,
        $week,
        $type
    ) {
        $tbl_global_agenda = Database::get_main_table(
            TABLE_MAIN_SYSTEM_CALENDAR
        );
        $month = intval($month);
        $year = intval($year);
        $week = intval($week);
        $day = intval($day);
        // 1. creating the SQL statement for getting the personal agenda items in MONTH view

        $current_access_url_id = api_get_current_access_url_id();

        if ("month_view" == $type or "" == $type) {
            // We are in month view
            $sql = "SELECT * FROM ".$tbl_global_agenda." WHERE MONTH(start_date) = ".$month." AND YEAR(start_date) = ".$year."  AND access_url_id = $current_access_url_id ORDER BY start_date ASC";
        }
        // 2. creating the SQL statement for getting the personal agenda items in WEEK view
        if ("week_view" == $type) { // we are in week view
            $start_end_day_of_week = self::calculate_start_end_of_week(
                $week,
                $year
            );
            $start_day = $start_end_day_of_week['start']['day'];
            $start_month = $start_end_day_of_week['start']['month'];
            $start_year = $start_end_day_of_week['start']['year'];
            $end_day = $start_end_day_of_week['end']['day'];
            $end_month = $start_end_day_of_week['end']['month'];
            $end_year = $start_end_day_of_week['end']['year'];
            // in sql statements you have to use year-month-day for date calculations
            $start_filter = $start_year."-".$start_month."-".$start_day." 00:00:00";
            $start_filter = api_get_utc_datetime($start_filter);

            $end_filter = $end_year."-".$end_month."-".$end_day." 23:59:59";
            $end_filter = api_get_utc_datetime($end_filter);
            $sql = " SELECT * FROM ".$tbl_global_agenda." WHERE start_date>='".$start_filter."' AND start_date<='".$end_filter."' AND  access_url_id = $current_access_url_id ";
        }
        // 3. creating the SQL statement for getting the personal agenda items in DAY view
        if ("day_view" == $type) { // we are in day view
            // we could use mysql date() function but this is only available from 4.1 and higher
            $start_filter = $year."-".$month."-".$day." 00:00:00";
            $start_filter = api_get_utc_datetime($start_filter);

            $end_filter = $year."-".$month."-".$day." 23:59:59";
            $end_filter = api_get_utc_datetime($end_filter);
            $sql = " SELECT * FROM ".$tbl_global_agenda." WHERE start_date>='".$start_filter."' AND start_date<='".$end_filter."'  AND  access_url_id = $current_access_url_id";
        }

        $result = Database::query($sql);

        while ($item = Database::fetch_array($result)) {
            if (!empty($item['start_date'])) {
                $item['start_date'] = api_get_local_time($item['start_date']);
                $item['start_date_tms'] = api_strtotime($item['start_date']);
            }
            if (!empty($item['end_date'])) {
                $item['end_date'] = api_get_local_time($item['end_date']);
            }

            // we break the date field in the database into a date and a time part
            $agenda_db_date = explode(" ", $item['start_date']);
            $date = $agenda_db_date[0];
            $time = $agenda_db_date[1];
            // we divide the date part into a day, a month and a year
            $agendadate = explode("-", $date);
            $year = intval($agendadate[0]);
            $month = intval($agendadate[1]);
            $day = intval($agendadate[2]);
            // we divide the time part into hour, minutes, seconds
            $agendatime = explode(":", $time);
            $hour = $agendatime[0];
            $minute = $agendatime[1];
            $second = $agendatime[2];

            if ('month_view' == $type) {
                $item['calendar_type'] = 'global';
                $agendaitems[$day][] = $item;
                continue;
            }

            $start_time = api_format_date(
                $item['start_date'],
                TIME_NO_SEC_FORMAT
            );
            $end_time = '';
            if (!empty($item['end_date'])) {
                $end_time = ' - '.api_format_date(
                        $item['end_date'],
                        DATE_TIME_FORMAT_LONG
                    );
            }

            // Creating the array that will be returned. If we have week or month view we have an array with the date as the key
            // if we have a day_view we use a half hour as index => key 33 = 16h30
            if ("day_view" !== $type) {
                // This is the array construction for the WEEK or MONTH view
                //Display the Agenda global in the tab agenda (administrator)
                $agendaitems[$day] .= "<i>$start_time $end_time</i>&nbsp;-&nbsp;";
                $agendaitems[$day] .= "<b>".get_lang('Platform event')."</b>";
                $agendaitems[$day] .= "<div>".$item['title']."</div><br>";
            } else {
                // this is the array construction for the DAY view
                $halfhour = 2 * $agendatime['0'];
                if ($agendatime['1'] >= '30') {
                    $halfhour = $halfhour + 1;
                }
                if (!is_array($agendaitems[$halfhour])) {
                    $content = $agendaitems[$halfhour];
                }
                $agendaitems[$halfhour] = $content."<div><i>$hour:$minute</i> <b>".get_lang(
                        'Platform event'
                    ).":  </b>".$item['title']."</div>";
            }
        }

        return $agendaitems;
    }

    /**
     * This function retrieves all the personal agenda items and add them to the agenda items found by the other
     * functions.
     */
    public static function get_personal_agenda_items(
        $user_id,
        $agendaitems,
        $day,
        $month,
        $year,
        $week,
        $type
    ) {
        $tbl_personal_agenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);
        $user_id = (int) $user_id;
        $course_link = '';
        // 1. creating the SQL statement for getting the personal agenda items in MONTH view
        if ("month_view" === $type || "" == $type) {
            // we are in month view
            $sql = "SELECT * FROM ".$tbl_personal_agenda."
                    WHERE user='".$user_id."' and MONTH(date)='".$month."' AND YEAR(date) = '".$year."'
                    ORDER BY date ASC";
        }

        // 2. creating the SQL statement for getting the personal agenda items in WEEK view
        // we are in week view
        if ("week_view" === $type) {
            $start_end_day_of_week = self::calculate_start_end_of_week(
                $week,
                $year
            );
            $start_day = $start_end_day_of_week['start']['day'];
            $start_month = $start_end_day_of_week['start']['month'];
            $start_year = $start_end_day_of_week['start']['year'];
            $end_day = $start_end_day_of_week['end']['day'];
            $end_month = $start_end_day_of_week['end']['month'];
            $end_year = $start_end_day_of_week['end']['year'];
            // in sql statements you have to use year-month-day for date calculations
            $start_filter = $start_year."-".$start_month."-".$start_day." 00:00:00";
            $start_filter = api_get_utc_datetime($start_filter);
            $end_filter = $end_year."-".$end_month."-".$end_day." 23:59:59";
            $end_filter = api_get_utc_datetime($end_filter);
            $sql = "SELECT * FROM ".$tbl_personal_agenda."
                    WHERE user='".$user_id."' AND date>='".$start_filter."' AND date<='".$end_filter."'";
        }
        // 3. creating the SQL statement for getting the personal agenda items in DAY view
        if ("day_view" === $type) {
            // we are in day view
            // we could use mysql date() function but this is only available from 4.1 and higher
            $start_filter = $year."-".$month."-".$day." 00:00:00";
            $start_filter = api_get_utc_datetime($start_filter);
            $end_filter = $year."-".$month."-".$day." 23:59:59";
            $end_filter = api_get_utc_datetime($end_filter);
            $sql = "SELECT * FROM ".$tbl_personal_agenda."
                    WHERE user='".$user_id."' AND date>='".$start_filter."' AND date<='".$end_filter."'";
        }

        $result = Database::query($sql);
        while ($item = Database::fetch_array($result, 'ASSOC')) {
            $time_minute = api_convert_and_format_date(
                $item['date'],
                TIME_NO_SEC_FORMAT
            );
            $item['date'] = api_get_local_time($item['date']);
            $item['start_date_tms'] = api_strtotime($item['date']);
            $item['content'] = $item['text'];

            // we break the date field in the database into a date and a time part
            $agenda_db_date = explode(" ", $item['date']);
            $date = $agenda_db_date[0];
            $time = $agenda_db_date[1];
            // we divide the date part into a day, a month and a year
            $agendadate = explode("-", $item['date']);
            $year = intval($agendadate[0]);
            $month = intval($agendadate[1]);
            $day = intval($agendadate[2]);
            // we divide the time part into hour, minutes, seconds
            $agendatime = explode(":", $time);

            $hour = $agendatime[0];
            $minute = $agendatime[1];
            $second = $agendatime[2];

            if ('month_view' === $type) {
                $item['calendar_type'] = 'personal';
                $item['start_date'] = $item['date'];
                $agendaitems[$day][] = $item;
                continue;
            }

            // Creating the array that will be returned.
            // If we have week or month view we have an array with the date as the key
            // if we have a day_view we use a half hour as index => key 33 = 16h30
            if ("day_view" !== $type) {
                // This is the array construction for the WEEK or MONTH view

                //Display events in agenda
                $agendaitems[$day] .= "<div>
                     <i>$time_minute</i> $course_link
                     <a href=\"myagenda.php?action=view&view=personal&day=$day&month=$month&year=$year&id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".
                    $item['title']."</a></div><br />";
            } else {
                // this is the array construction for the DAY view
                $halfhour = 2 * $agendatime['0'];
                if ($agendatime['1'] >= '30') {
                    $halfhour = $halfhour + 1;
                }

                //Display events by list
                $agendaitems[$halfhour] .= "<div>
                    <i>$time_minute</i> $course_link
                    <a href=\"myagenda.php?action=view&view=personal&day=$day&month=$month&year=$year&id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".$item['title']."</a></div>";
            }
        }

        return $agendaitems;
    }

    /**
     * Show the month calendar of the given month.
     *
     * @param    array    Agendaitems
     * @param    int    Month number
     * @param    int    Year number
     * @param    array    Array of strings containing long week day names (deprecated, you can send an empty array
     *                          instead)
     * @param    string    The month name
     */
    public static function display_mymonthcalendar(
        $user_id,
        $agendaitems,
        $month,
        $year,
        $weekdaynames,
        $monthName,
        $show_content = true
    ) {
        global $DaysShort, $course_path;
        //Handle leap year
        $numberofdays = [
            0,
            31,
            28,
            31,
            30,
            31,
            30,
            31,
            31,
            30,
            31,
            30,
            31,
        ];
        if ((0 == $year % 400) or (0 == $year % 4 and 0 != $year % 100)) {
            $numberofdays[2] = 29;
        }
        //Get the first day of the month
        $dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
        //Start the week on monday
        $startdayofweek = 0 != $dayone['wday'] ? ($dayone['wday'] - 1) : 6;
        $g_cc = (isset($_GET['courseCode']) ? $_GET['courseCode'] : '');

        $next_month = (1 == $month ? 12 : $month - 1);
        $prev_month = (12 == $month ? 1 : $month + 1);

        $next_year = (1 == $month ? $year - 1 : $year);
        $prev_year = (12 == $month ? $year + 1 : $year);

        if ($show_content) {
            $back_url = Display::url(
                get_lang('Previous'),
                api_get_self()."?coursePath=".urlencode(
                    $course_path
                )."&courseCode=".Security::remove_XSS(
                    $g_cc
                )."&action=view&view=month&month=".$next_month."&year=".$next_year
            );
            $next_url = Display::url(
                get_lang('Next'),
                api_get_self()."?coursePath=".urlencode(
                    $course_path
                )."&courseCode=".Security::remove_XSS(
                    $g_cc
                )."&action=view&view=month&month=".$prev_month."&year=".$prev_year
            );
        } else {
            $back_url = Display::url(
                get_lang('Previous'),
                '',
                [
                    'onclick' => "load_calendar('".$user_id."','".$next_month."', '".$next_year."'); ",
                    'class' => 'btn ui-button ui-widget ui-state-default',
                ]
            );
            $next_url = Display::url(
                get_lang('Next'),
                '',
                [
                    'onclick' => "load_calendar('".$user_id."','".$prev_month."', '".$prev_year."'); ",
                    'class' => 'pull-right btn ui-button ui-widget ui-state-default',
                ]
            );
        }
        $html = '';
        $html .= '<div class="actions">';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-4">'.$back_url.'</div>';
        $html .= '<div class="col-md-4"><p class="agenda-title text-center">'.$monthName." ".$year.'</p></div>';
        $html .= '<div class="col-md-4">'.$next_url.'</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<table id="agenda_list2" class="table table-bordered">';
        $html .= '<tr>';
        for ($ii = 1; $ii < 8; $ii++) {
            $html .= '<td class="weekdays">'.$DaysShort[$ii % 7].'</td>';
        }
        $html .= '</tr>';

        $curday = -1;
        $today = getdate();
        while ($curday <= $numberofdays[$month]) {
            $html .= "<tr>";
            for ($ii = 0; $ii < 7; $ii++) {
                if ((-1 == $curday) && ($ii == $startdayofweek)) {
                    $curday = 1;
                }
                if (($curday > 0) && ($curday <= $numberofdays[$month])) {
                    $bgcolor = $class = 'class="days_week"';
                    $dayheader = Display::div(
                        $curday,
                        ['class' => 'agenda_day']
                    );
                    if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {
                        $class = "class=\"days_today\" style=\"width:10%;\"";
                    }

                    $html .= "<td ".$class.">".$dayheader;

                    if (!empty($agendaitems[$curday])) {
                        $items = $agendaitems[$curday];
                        $items = msort($items, 'start_date_tms');

                        foreach ($items as $value) {
                            $value['title'] = Security::remove_XSS(
                                $value['title']
                            );
                            $start_time = api_format_date(
                                $value['start_date'],
                                TIME_NO_SEC_FORMAT
                            );
                            $end_time = '';

                            if (!empty($value['end_date'])) {
                                $end_time = '-&nbsp;<i>'.api_format_date(
                                        $value['end_date'],
                                        DATE_TIME_FORMAT_LONG
                                    ).'</i>';
                            }
                            $complete_time = '<i>'.api_format_date(
                                    $value['start_date'],
                                    DATE_TIME_FORMAT_LONG
                                ).'</i>&nbsp;'.$end_time;
                            $time = '<i>'.$start_time.'</i>';

                            switch ($value['calendar_type']) {
                                case 'personal':
                                    $bg_color = '#D0E7F4';
                                    $icon = Display::return_icon(
                                        'user.png',
                                        get_lang('Personal agenda'),
                                        [],
                                        ICON_SIZE_SMALL
                                    );
                                    break;
                                case 'global':
                                    $bg_color = '#FFBC89';
                                    $icon = Display::return_icon(
                                        'view_remove.png',
                                        get_lang('Platform event'),
                                        [],
                                        ICON_SIZE_SMALL
                                    );
                                    break;
                                case 'course':
                                    $bg_color = '#CAFFAA';
                                    $icon_name = 'course.png';
                                    if (!empty($value['session_id'])) {
                                        $icon_name = 'session.png';
                                    }
                                    if ($show_content) {
                                        $icon = Display::url(
                                            Display::return_icon(
                                                $icon_name,
                                                $value['course_name'].' '.get_lang(
                                                    'Course'
                                                ),
                                                [],
                                                ICON_SIZE_SMALL
                                            ),
                                            $value['url']
                                        );
                                    } else {
                                        $icon = Display::return_icon(
                                            $icon_name,
                                            $value['course_name'].' '.get_lang(
                                                'Course'
                                            ),
                                            [],
                                            ICON_SIZE_SMALL
                                        );
                                    }
                                    break;
                                default:
                                    break;
                            }

                            $result = '<div class="rounded_div_agenda" style="background-color:'.$bg_color.';">';

                            if ($show_content) {
                                //Setting a personal event to green
                                $icon = Display::div(
                                    $icon,
                                    ['style' => 'float:right']
                                );

                                $link = $value['calendar_type'].'_'.$value['id'].'_'.$value['course_id'].'_'.$value['session_id'];

                                //Link to bubble
                                $url = Display::url(
                                    cut($value['title'], 40),
                                    '#',
                                    ['id' => $link, 'class' => 'opener']
                                );
                                $result .= $time.' '.$icon.' '.Display::div(
                                        $url
                                    );

                                //Hidden content
                                $content = Display::div(
                                    $icon.Display::tag(
                                        'h2',
                                        $value['course_name']
                                    ).'<hr />'.Display::tag(
                                        'h3',
                                        $value['title']
                                    ).$complete_time.'<hr />'.Security::remove_XSS(
                                        $value['content']
                                    )
                                );

                                //Main div
                                $result .= Display::div(
                                    $content,
                                    [
                                        'id' => 'main_'.$link,
                                        'class' => 'dialog',
                                        'style' => 'display:none',
                                    ]
                                );
                                $result .= '</div>';
                                $html .= $result;
                            } else {
                                $html .= $result .= $icon.'</div>';
                            }
                        }
                    }
                    $html .= "</td>";
                    $curday++;
                } else {
                    $html .= "<td></td>";
                }
            }
            $html .= "</tr>";
        }
        $html .= "</table>";
        echo $html;
    }

    /**
     * Get personal agenda items between two dates (=all events from all registered courses).
     *
     * @param int $user_id user ID of the user
     * @param    string    Optional start date in datetime format (if no start date is given, uses today)
     * @param    string    Optional end date in datetime format (if no date is given, uses one year from now)
     *
     * @return array array of events ordered by start date, in
     *               [0]('datestart','dateend','title'),[1]('datestart','dateend','title','link','coursetitle') format,
     *               where datestart and dateend are in yyyyMMddhhmmss format
     *
     * @deprecated use agenda events
     */
    public static function get_personal_agenda_items_between_dates($user_id, $date_start = '', $date_end = '')
    {
        throw new Exception('fix get_personal_agenda_items_between_dates');
        /*
        $items = [];
        if ($user_id != strval(intval($user_id))) {
            return $items;
        }
        if (empty($date_start)) {
            $date_start = date('Y-m-d H:i:s');
        }
        if (empty($date_end)) {
            $date_end = date(
                'Y-m-d H:i:s',
                mktime(0, 0, 0, date("m"), date("d"), date("Y") + 1)
            );
        }
        $expr = '/\d{4}-\d{2}-\d{2}\ \d{2}:\d{2}:\d{2}/';
        if (!preg_match($expr, $date_start)) {
            return $items;
        }
        if (!preg_match($expr, $date_end)) {
            return $items;
        }

        // get agenda-items for every course
        //$courses = api_get_user_courses($user_id, false);
        $courses = CourseManager::get_courses_list_by_user_id($user_id, false);
        foreach ($courses as $id => $course) {
            $c = api_get_course_info_by_id($course['real_id']);
            $t_a = Database::get_course_table(TABLE_AGENDA, $course['db']);
            $t_ip = Database::get_course_table(
                TABLE_ITEM_PROPERTY,
                $course['db']
            );
            // get the groups to which the user belong
            $group_memberships = GroupManager:: get_group_ids(
                $course['db'],
                $user_id
            );
            // if the user is administrator of that course we show all the agenda items
            if ('1' == $course['status']) {
                //echo "course admin";
                $sqlquery = "SELECT ".
                    " DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref ".
                    " FROM ".$t_a." agenda, ".
                    $t_ip." ip ".
                    " WHERE agenda.id = ip.ref ".
                    " AND agenda.start_date>='$date_start' ".
                    " AND agenda.end_date<='$date_end' ".
                    " AND ip.tool='".TOOL_CALENDAR_EVENT."' ".
                    " AND ip.visibility='1' ".
                    " GROUP BY agenda.id ".
                    " ORDER BY start_date ";
            } else {
                // if the user is not an administrator of that course, then...
                if (is_array($group_memberships) && count(
                        $group_memberships
                    ) > 0
                ) {
                    $sqlquery = "SELECT ".
                        "DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref ".
                        " FROM ".$t_a." agenda, ".
                        $t_ip." ip ".
                        " WHERE agenda.id = ip.ref ".
                        " AND agenda.start_date>='$date_start' ".
                        " AND agenda.end_date<='$date_end' ".
                        " AND ip.tool='".TOOL_CALENDAR_EVENT."' ".
                        " AND	( ip.to_user_id='".$user_id."' OR (ip.to_group_id IS NULL OR ip.to_group_id IN (0, ".implode(
                            ", ",
                            $group_memberships
                        ).")) ) ".
                        " AND ip.visibility='1' ".
                        " ORDER BY start_date ";
                } else {
                    $sqlquery = "SELECT ".
                        "DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref ".
                        " FROM ".$t_a." agenda, ".
                        $t_ip." ip ".
                        " WHERE agenda.id = ip.ref ".
                        " AND agenda.start_date>='$date_start' ".
                        " AND agenda.end_date<='$date_end' ".
                        " AND ip.tool='".TOOL_CALENDAR_EVENT."' ".
                        " AND ( ip.to_user_id='".$user_id."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL) ".
                        " AND ip.visibility='1' ".
                        " ORDER BY start_date ";
                }
            }

            $result = Database::query($sqlquery);
            while ($item = Database::fetch_array($result)) {
                $agendaday = date("j", strtotime($item['start_date']));
                $month = date("n", strtotime($item['start_date']));
                $year = date("Y", strtotime($item['start_date']));
                $URL = api_get_path(
                        WEB_PATH
                    )."main/calendar/agenda.php?cidReq=".urlencode(
                        $course["code"]
                    )."&day=$agendaday&month=$month&year=$year#$agendaday";
                [$year, $month, $day, $hour, $min, $sec] = explode(
                    '[-: ]',
                    $item['start_date']
                );
                $start_date = $year.$month.$day.$hour.$min;
                [$year, $month, $day, $hour, $min, $sec] = explode(
                    '[-: ]',
                    $item['end_date']
                );
                $end_date = $year.$month.$day.$hour.$min;

                $items[] = [
                    'datestart' => $start_date,
                    'dateend' => $end_date,
                    'title' => $item['title'],
                    'link' => $URL,
                    'coursetitle' => $c['name'],
                ];
            }
        }

        return $items;*/
    }

    /**
     * This function calculates the startdate of the week (monday)
     * and the enddate of the week (sunday)
     * and returns it as an array.
     */
    public static function calculate_start_end_of_week($week_number, $year)
    {
        // determine the start and end date
        // step 1: we calculate a timestamp for a day in this week
        $random_day_in_week = mktime(
                0,
                0,
                0,
                1,
                1,
                $year
            ) + ($week_number) * (7 * 24 * 60 * 60); // we calculate a random day in this week
        // step 2: we which day this is (0=sunday, 1=monday, ...)
        $number_day_in_week = date('w', $random_day_in_week);
        // step 3: we calculate the timestamp of the monday of the week we are in
        $start_timestamp = $random_day_in_week - (($number_day_in_week - 1) * 24 * 60 * 60);
        // step 4: we calculate the timestamp of the sunday of the week we are in
        $end_timestamp = $random_day_in_week + ((7 - $number_day_in_week + 1) * 24 * 60 * 60) - 3600;
        // step 5: calculating the start_day, end_day, start_month, end_month, start_year, end_year
        $start_day = date('j', $start_timestamp);
        $start_month = date('n', $start_timestamp);
        $start_year = date('Y', $start_timestamp);
        $end_day = date('j', $end_timestamp);
        $end_month = date('n', $end_timestamp);
        $end_year = date('Y', $end_timestamp);
        $start_end_array['start']['day'] = $start_day;
        $start_end_array['start']['month'] = $start_month;
        $start_end_array['start']['year'] = $start_year;
        $start_end_array['end']['day'] = $end_day;
        $start_end_array['end']['month'] = $end_month;
        $start_end_array['end']['year'] = $end_year;

        return $start_end_array;
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
}
