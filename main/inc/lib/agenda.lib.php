<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AgendaEventInvitation;
use Chamilo\CoreBundle\Entity\AgendaEventInvitee;
use Chamilo\CoreBundle\Entity\AgendaEventSubscriber;
use Chamilo\CoreBundle\Entity\AgendaEventSubscription;
use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\PersonalAgenda;
use Chamilo\UserBundle\Entity\User;

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
                if (api_get_course_setting('allow_user_edit_agenda') == '1'
                    && api_is_allowed_in_course()
                ) {
                    $isAllowToEdit = true;
                }

                $groupId = api_get_group_id();
                if (!empty($groupId)) {
                    $groupInfo = GroupManager::get_group_properties($groupId);
                    $userHasAccess = GroupManager::user_has_access(
                        api_get_user_id(),
                        $groupInfo['iid'],
                        GroupManager::GROUP_TOOL_CALENDAR
                    );
                    $isTutor = GroupManager::is_tutor_of_group(
                        api_get_user_id(),
                        $groupInfo
                    );

                    $isGroupAccess = $userHasAccess || $isTutor;
                    $isAllowToEdit = false;
                    if ($isGroupAccess) {
                        $isAllowToEdit = true;
                    }
                }

                if (false === $isAllowToEdit && !empty($sessionId)) {
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
        $type = (string) trim($type);
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
     * Adds an event to the calendar.
     *
     * @param string $start                 datetime format: 2012-06-14 09:00:00 in local time
     * @param string $end                   datetime format: 2012-06-14 09:00:00 in local time
     * @param string $allDay                (true, false)
     * @param string $title
     * @param string $content
     * @param array  $usersToSend           array('everyone') or a list of user/group ids
     * @param bool   $addAsAnnouncement     event as a *course* announcement
     * @param int    $parentEventId
     * @param array  $attachmentArray       array of $_FILES['']
     * @param array  $attachmentCommentList
     * @param string $eventComment
     * @param string $color
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
        $parentEventId = null,
        $attachmentArray = [],
        $attachmentCommentList = [],
        $eventComment = null,
        $color = '',
        array $inviteesList = [],
        bool $isCollective = false,
        array $reminders = [],
        int $careerId = 0,
        int $promotionId = 0,
        int $subscriptionVisibility = 0,
        ?int $subscriptionItemId = null,
        int $maxSubscriptions = 0
    ) {
        $start = api_get_utc_datetime($start);
        $end = api_get_utc_datetime($end);
        $allDay = isset($allDay) && ($allDay === 'true' || $allDay == 1) ? 1 : 0;
        $id = null;

        $em = Database::getManager();

        switch ($this->type) {
            case 'personal':
                $attributes = [
                    'user' => api_get_user_id(),
                    'title' => $title,
                    'text' => $content,
                    'date' => $start,
                    'enddate' => $end,
                    'all_day' => $allDay,
                    'color' => $color,
                ];

                $id = Database::insert(
                    $this->tbl_personal_agenda,
                    $attributes
                );

                if (api_get_configuration_value('agenda_collective_invitations')) {
                    Agenda::saveCollectiveProperties($inviteesList, $isCollective, $id);
                }

                if (api_get_configuration_value('agenda_event_subscriptions') && api_is_platform_admin()) {
                    $personalEvent = $em->find(PersonalAgenda::class, $id);

                    if ($personalEvent->hasInvitation()
                        && !($personalEvent->getInvitation() instanceof AgendaEventSubscription)
                    ) {
                        break;
                    }

                    $personalEvent
                        ->setSubscriptionVisibility($subscriptionVisibility)
                        ->setSubscriptionItemId($subscriptionItemId ?: null)
                    ;

                    $subscription = (new AgendaEventSubscription())
                        ->setCreator(api_get_user_entity(api_get_user_id()))
                        ->setMaxAttendees($subscriptionVisibility > 0 ? $maxSubscriptions : 0)
                    ;

                    $personalEvent
                        ->setCollective(false)
                        ->setInvitation($subscription)
                    ;

                    $em->flush();
                }
                break;
            case 'course':
                $attributes = [
                    'title' => $title,
                    'content' => $content,
                    'start_date' => $start,
                    'end_date' => $end,
                    'all_day' => $allDay,
                    'session_id' => $this->getSessionId(),
                    'c_id' => $this->course['real_id'],
                    'comment' => $eventComment,
                    'color' => $color,
                ];

                if (!empty($parentEventId)) {
                    $attributes['parent_event_id'] = $parentEventId;
                }
                $this->deleteEventIfAlreadyExists($start, $end, $allDay, $title);
                $senderId = $this->getSenderId();
                $sessionId = $this->getSessionId();

                // Simple course event.
                $id = Database::insert($this->tbl_course_agenda, $attributes);

                if ($id) {
                    $sql = "UPDATE ".$this->tbl_course_agenda." SET id = iid WHERE iid = $id";
                    Database::query($sql);

                    $groupId = api_get_group_id();
                    $groupInfo = [];
                    if ($groupId) {
                        $groupInfo = GroupManager::get_group_properties(
                            $groupId
                        );
                    }

                    if (!empty($usersToSend)) {
                        $sendTo = $this->parseSendToArray($usersToSend);
                        if ($sendTo['everyone']) {
                            api_item_property_update(
                                $this->course,
                                TOOL_CALENDAR_EVENT,
                                $id,
                                'AgendaAdded',
                                $senderId,
                                $groupInfo,
                                '',
                                $start,
                                $end,
                                $sessionId
                            );
                            api_item_property_update(
                                $this->course,
                                TOOL_CALENDAR_EVENT,
                                $id,
                                'visible',
                                $senderId,
                                $groupInfo,
                                '',
                                $start,
                                $end,
                                $sessionId
                            );
                        } else {
                            // Storing the selected groups
                            if (!empty($sendTo['groups'])) {
                                foreach ($sendTo['groups'] as $group) {
                                    $groupInfoItem = [];
                                    if ($group) {
                                        $groupInfoItem = GroupManager::get_group_properties($group);
                                    }

                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        'AgendaAdded',
                                        $senderId,
                                        $groupInfoItem,
                                        0,
                                        $start,
                                        $end,
                                        $sessionId
                                    );

                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        'visible',
                                        $senderId,
                                        $groupInfoItem,
                                        0,
                                        $start,
                                        $end,
                                        $sessionId
                                    );
                                }
                            }

                            // storing the selected users
                            if (!empty($sendTo['users'])) {
                                foreach ($sendTo['users'] as $userId) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        'AgendaAdded',
                                        $senderId,
                                        $groupInfo,
                                        $userId,
                                        $start,
                                        $end,
                                        $sessionId
                                    );

                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        'visible',
                                        $senderId,
                                        $groupInfo,
                                        $userId,
                                        $start,
                                        $end,
                                        $sessionId
                                    );
                                }
                            }
                        }
                    }

                    // Add announcement.
                    if ($addAsAnnouncement) {
                        $this->storeAgendaEventAsAnnouncement(
                            $id,
                            $usersToSend
                        );
                    }

                    // Add attachment.
                    if (isset($attachmentArray) && !empty($attachmentArray)) {
                        $counter = 0;
                        foreach ($attachmentArray as $attachmentItem) {
                            $this->addAttachment(
                                $id,
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
                    $attributes = [
                        'title' => $title,
                        'content' => $content,
                        'start_date' => $start,
                        'end_date' => $end,
                        'all_day' => $allDay,
                        'access_url_id' => api_get_current_access_url_id(),
                    ];

                    if (api_get_configuration_value('allow_careers_in_global_agenda')) {
                        $attributes['career_id'] = $careerId;
                        $attributes['promotion_id'] = $promotionId;
                    }

                    $id = Database::insert(
                        $this->tbl_global_agenda,
                        $attributes
                    );
                }
                break;
        }

        if (api_get_configuration_value('agenda_reminders')) {
            foreach ($reminders as $reminder) {
                $this->addReminder($id, $reminder[0], $reminder[1]);
            }
        }

        return $id;
    }

    /**
     * Checks if an event exists and delete it (right before inserting a modified version in addEvent()).
     *
     * @param string $start  datetime format: 2012-06-14 09:00:00 in local time
     * @param string $end    datetime format: 2012-06-14 09:00:00 in local time
     * @param int    $allDay (true = 1, false = 0)
     *
     * @throws Exception
     */
    public function deleteEventIfAlreadyExists(
        string $start,
        string $end,
        int $allDay,
        string $title
    ): bool {
        $courseId = $this->course['real_id'];
        $start = Database::escape_string($start);
        $end = Database::escape_string($end);
        $title = Database::escape_string($title);
        $sql = "SELECT id FROM ".$this->tbl_course_agenda."
                WHERE c_id = $courseId
                AND session_id = ".$this->sessionId."
                AND start_date = '$start'
                AND end_date = '$end'
                AND all_day = $allDay
                AND title = '$title'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res, 'ASSOC');
            $id = $row['id'];
            $this->deleteEvent($id);

            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function addReminder($eventId, $count, $period)
    {
        switch ($period) {
            case 'i':
                $dateInterval = DateInterval::createFromDateString("$count minutes");
                break;
            case 'h':
                $dateInterval = DateInterval::createFromDateString("$count hours");
                break;
            case 'd':
                $dateInterval = DateInterval::createFromDateString("$count days");
                break;
            default:
                return null;
        }

        $agendaReminder = new AgendaReminder();
        $agendaReminder
            ->setType($this->type)
            ->setEventId($eventId)
            ->setDateInterval($dateInterval)
        ;

        $em = Database::getManager();
        $em->persist($agendaReminder);
        $em->flush();
    }

    public function removeReminders(int $eventId, int $count, string $period)
    {
        switch ($period) {
            case 'i':
                $dateInterval = DateInterval::createFromDateString("$count minutes");
                break;
            case 'h':
                $dateInterval = DateInterval::createFromDateString("$count hours");
                break;
            case 'd':
                $dateInterval = DateInterval::createFromDateString("$count days");
                break;
            default:
                return null;
        }

        Database::getManager()
            ->createQuery(
                'DELETE FROM ChamiloCoreBundle:AgendaReminder ar
                WHERE ar.eventId = :eventId AND ar.type = :type AND ar.dateInterval = :dateInterval'
            )
            ->setParameters(
                [
                    'eventId' => $eventId,
                    'type' => $this->type,
                    'dateInterval' => $dateInterval,
                ]
            )
            ->execute();
    }

    public function getReminder(int $eventId, int $count, string $period)
    {
        switch ($period) {
            case 'i':
                $dateInterval = DateInterval::createFromDateString("$count minutes");
                break;
            case 'h':
                $dateInterval = DateInterval::createFromDateString("$count hours");
                break;
            case 'd':
                $dateInterval = DateInterval::createFromDateString("$count days");
                break;
            default:
                return null;
        }

        $em = Database::getManager();
        $remindersRepo = $em->getRepository('ChamiloCoreBundle:AgendaReminder');

        return $remindersRepo->findOneBy(
            [
                'type' => $this->type,
                'dateInterval' => $dateInterval,
                'eventId' => $eventId,
            ]
        );
    }

    /**
     * @param int $eventId
     * @param int $courseId
     *
     * @return array
     */
    public function getRepeatedInfoByEvent($eventId, $courseId)
    {
        $repeatTable = Database::get_course_table(TABLE_AGENDA_REPEAT);
        $eventId = (int) $eventId;
        $courseId = (int) $courseId;
        $sql = "SELECT * FROM $repeatTable
                WHERE c_id = $courseId AND cal_id = $eventId";
        $res = Database::query($sql);
        $repeatInfo = [];
        if (Database::num_rows($res) > 0) {
            $repeatInfo = Database::fetch_array($res, 'ASSOC');
        }

        return $repeatInfo;
    }

    /**
     * @param string $type
     * @param string $startEvent      in UTC
     * @param string $endEvent        in UTC
     * @param string $repeatUntilDate in UTC
     *
     * @throws Exception
     *
     * @return array with local times
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
                // not yet implemented
                break;
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
            // The code below was not adpating to saving light time but was doubling the difference with UTC time.
            // Might be necessary to adapt to update saving light time difference.
            /*            $startDateInLocal = new DateTime($newStartDate, new DateTimeZone($timeZone));
                        if ($startDateInLocal->format('I') == 0) {
                            // Is saving time? Then fix UTC time to add time
                            $seconds = $startDateInLocal->getOffset();
                            $startDate->add(new DateInterval("PT".$seconds."S"));
                            //$startDateFixed = $startDate->format('Y-m-d H:i:s');
                            //$startDateInLocalFixed = new DateTime($startDateFixed, new DateTimeZone($timeZone));
                            //$newStartDate = $startDateInLocalFixed->format('Y-m-d H:i:s');
                            //$newStartDate = $startDate->setTimezone(new DateTimeZone($timeZone))->format('Y-m-d H:i:s');
                        }

                        $endDateInLocal = new DateTime($newEndDate, new DateTimeZone($timeZone));
                        if ($endDateInLocal->format('I') == 0) {
                            // Is saving time? Then fix UTC time to add time
                            $seconds = $endDateInLocal->getOffset();
                            $endDate->add(new DateInterval("PT".$seconds."S"));
                            //$endDateFixed = $endDate->format('Y-m-d H:i:s');
                            //$endDateInLocalFixed = new DateTime($endDateFixed, new DateTimeZone($timeZone));
                            //$newEndDate = $endDateInLocalFixed->format('Y-m-d H:i:s');
                    }
            */
            $newStartDate = $startDate->setTimezone(new DateTimeZone($timeZone))->format('Y-m-d H:i:s');
            $newEndDate = $endDate->setTimezone(new DateTimeZone($timeZone))->format('Y-m-d H:i:s');
            $list[] = ['start' => $newStartDate, 'end' => $newEndDate];
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

        $courseId = $this->course['real_id'];
        $eventId = (int) $eventId;

        $sql = "SELECT title, content, start_date, end_date, all_day
                FROM $t_agenda
                WHERE c_id = $courseId AND id = $eventId";
        $res = Database::query($sql);

        if (Database::num_rows($res) !== 1) {
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

        if (!in_array($type, $typeList)) {
            return false;
        }

        $now = time();

        // The event has to repeat *in the future*. We don't allow repeated
        // events in the past.
        $endTimeStamp = api_strtotime($end, 'UTC');

        if ($endTimeStamp < $now) {
            return false;
        }

        $row = Database::fetch_array($res);

        $title = $row['title'];
        $content = $row['content'];
        $allDay = $row['all_day'];

        $type = Database::escape_string($type);
        $end = Database::escape_string($end);

        $sql = "INSERT INTO $t_agenda_r (c_id, cal_id, cal_type, cal_end)
                VALUES ($courseId, '$eventId', '$type', '$endTimeStamp')";
        Database::query($sql);

        $generatedDates = $this->generateDatesByType($type, $row['start_date'], $row['end_date'], $end);

        if (empty($generatedDates)) {
            return false;
        }

        foreach ($generatedDates as $dateInfo) {
//            $start = api_get_local_time($dateInfo['start']);
//            $end = api_get_local_time($dateInfo['end']);
            // On line 529 in function generateDatesByType there is a @todo remove comment code
            // just before the part updating the date in local time so keep both synchronised
            $start = $dateInfo['start'];
            $end = $dateInfo['end'];

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

    /**
     * @param int   $item_id
     * @param array $sentTo
     *
     * @return int
     */
    public function storeAgendaEventAsAnnouncement($item_id, $sentTo = [])
    {
        $table_agenda = Database::get_course_table(TABLE_AGENDA);
        $courseId = api_get_course_int_id();

        // Check params
        if (empty($item_id) || $item_id != strval(intval($item_id))) {
            return -1;
        }

        // Get the agenda item.
        $item_id = intval($item_id);
        $sql = "SELECT * FROM $table_agenda
                WHERE c_id = $courseId AND id = ".$item_id;
        $res = Database::query($sql);

        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res, 'ASSOC');

            // Sending announcement
            if (!empty($sentTo)) {
                $id = AnnouncementManager::add_announcement(
                    api_get_course_info(),
                    api_get_session_id(),
                    $row['title'],
                    $row['content'],
                    $sentTo,
                    null,
                    null,
                    $row['end_date']
                );

                AnnouncementManager::sendEmail(
                    api_get_course_info(),
                    api_get_session_id(),
                    $id
                );

                return $id;
            }
        }

        return -1;
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
        $comment = null,
        $color = '',
        $addAnnouncement = false,
        $updateContent = true,
        $authorId = 0,
        array $inviteesList = [],
        bool $isCollective = false,
        array $remindersList = [],
        int $careerId = 0,
        int $promotionId = 0,
        int $subscriptionVisibility = 0,
        ?int $subscriptionItemId = null,
        int $maxSubscriptions = 0,
        array $subscribers = []
    ) {
        $id = (int) $id;
        $start = api_get_utc_datetime($start);
        $end = api_get_utc_datetime($end);
        $allDay = isset($allDay) && $allDay == 'true' ? 1 : 0;
        $currentUserId = api_get_user_id();
        $authorId = empty($authorId) ? $currentUserId : (int) $authorId;

        $em = Database::getManager();

        switch ($this->type) {
            case 'personal':
                $eventInfo = $this->get_event($id);
                if ($eventInfo['user'] != $currentUserId
                    && (
                        api_get_configuration_value('agenda_collective_invitations')
                            && !self::isUserInvitedInEvent($id, $currentUserId)
                    )
                ) {
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

                if (api_get_configuration_value('agenda_collective_invitations')) {
                    Agenda::saveCollectiveProperties($inviteesList, $isCollective, $id);
                }

                if (api_get_configuration_value('agenda_event_subscriptions') && api_is_platform_admin()) {
                    $personalEvent = $em->find(PersonalAgenda::class, $id);

                    if ($personalEvent->hasInvitation()
                        && !($personalEvent->getInvitation() instanceof AgendaEventSubscription)
                    ) {
                        break;
                    }

                    $personalEvent->setSubscriptionVisibility($subscriptionVisibility);

                    /** @var AgendaEventSubscription $subscription */
                    $subscription = $personalEvent->getInvitation();
                    $subscription->setMaxAttendees($subscriptionVisibility > 0 ? $maxSubscriptions : 0);

                    if ($personalEvent->getSubscriptionItemId() != $subscriptionItemId) {
                        $personalEvent->setSubscriptionItemId($subscriptionItemId ?: null);
                        $subscription->removeInvitees();
                    } else {
                        $subscription->removeInviteesNotInIdList($subscribers);
                    }

                    $em->flush();
                }
                break;
            case 'course':
                $eventInfo = $this->get_event($id);

                if (empty($eventInfo)) {
                    return false;
                }

                $groupId = api_get_group_id();
                $groupIid = 0;
                $groupInfo = [];
                if ($groupId) {
                    $groupInfo = GroupManager::get_group_properties($groupId);
                    if ($groupInfo) {
                        $groupIid = $groupInfo['iid'];
                    }
                }

                $courseId = $this->course['real_id'];

                if (empty($courseId)) {
                    return false;
                }

                if (!$this->getIsAllowedToEdit()) {
                    return false;
                }

                $attributes = [
                    'title' => $title,
                    'start_date' => $start,
                    'end_date' => $end,
                    'all_day' => $allDay,
                    'comment' => $comment,
                ];

                if ($updateContent) {
                    $attributes['content'] = $content;
                }

                if (!empty($color)) {
                    $attributes['color'] = $color;
                }

                Database::update(
                    $this->tbl_course_agenda,
                    $attributes,
                    [
                        'id = ? AND c_id = ? AND session_id = ? ' => [
                            $id,
                            $courseId,
                            $this->sessionId,
                        ],
                    ]
                );

                if (!empty($usersToSend)) {
                    $sendTo = $this->parseSendToArray($usersToSend);

                    $usersToDelete = array_diff(
                        $eventInfo['send_to']['users'],
                        $sendTo['users']
                    );
                    $usersToAdd = array_diff(
                        $sendTo['users'],
                        $eventInfo['send_to']['users']
                    );

                    $groupsToDelete = array_diff(
                        $eventInfo['send_to']['groups'],
                        $sendTo['groups']
                    );
                    $groupToAdd = array_diff(
                        $sendTo['groups'],
                        $eventInfo['send_to']['groups']
                    );

                    if ($sendTo['everyone']) {
                        // Delete all from group
                        if (isset($eventInfo['send_to']['groups']) &&
                            !empty($eventInfo['send_to']['groups'])
                        ) {
                            foreach ($eventInfo['send_to']['groups'] as $group) {
                                $groupIidItem = 0;
                                if ($group) {
                                    $groupInfoItem = GroupManager::get_group_properties(
                                        $group
                                    );
                                    if ($groupInfoItem) {
                                        $groupIidItem = $groupInfoItem['iid'];
                                    }
                                }

                                api_item_property_delete(
                                    $this->course,
                                    TOOL_CALENDAR_EVENT,
                                    $id,
                                    0,
                                    $groupIidItem,
                                    $this->sessionId
                                );
                            }
                        }

                        // Storing the selected users.
                        if (isset($eventInfo['send_to']['users']) &&
                            !empty($eventInfo['send_to']['users'])
                        ) {
                            foreach ($eventInfo['send_to']['users'] as $userId) {
                                api_item_property_delete(
                                    $this->course,
                                    TOOL_CALENDAR_EVENT,
                                    $id,
                                    $userId,
                                    $groupIid,
                                    $this->sessionId
                                );
                            }
                        }

                        // Add to everyone only.
                        api_item_property_update(
                            $this->course,
                            TOOL_CALENDAR_EVENT,
                            $id,
                            'visible',
                            $authorId,
                            $groupInfo,
                            null,
                            $start,
                            $end,
                            $this->sessionId
                        );
                    } else {
                        // Delete "everyone".
                        api_item_property_delete(
                            $this->course,
                            TOOL_CALENDAR_EVENT,
                            $id,
                            0,
                            0,
                            $this->sessionId
                        );

                        // Add groups
                        if (!empty($groupToAdd)) {
                            foreach ($groupToAdd as $group) {
                                $groupInfoItem = [];
                                if ($group) {
                                    $groupInfoItem = GroupManager::get_group_properties(
                                        $group
                                    );
                                }

                                api_item_property_update(
                                    $this->course,
                                    TOOL_CALENDAR_EVENT,
                                    $id,
                                    'visible',
                                    $authorId,
                                    $groupInfoItem,
                                    0,
                                    $start,
                                    $end,
                                    $this->sessionId
                                );
                            }
                        }

                        // Delete groups.
                        if (!empty($groupsToDelete)) {
                            foreach ($groupsToDelete as $group) {
                                $groupIidItem = 0;
                                $groupInfoItem = [];
                                if ($group) {
                                    $groupInfoItem = GroupManager::get_group_properties(
                                        $group
                                    );
                                    if ($groupInfoItem) {
                                        $groupIidItem = $groupInfoItem['iid'];
                                    }
                                }

                                api_item_property_delete(
                                    $this->course,
                                    TOOL_CALENDAR_EVENT,
                                    $id,
                                    0,
                                    $groupIidItem,
                                    $this->sessionId
                                );
                            }
                        }

                        // Add users.
                        if (!empty($usersToAdd)) {
                            foreach ($usersToAdd as $userId) {
                                api_item_property_update(
                                    $this->course,
                                    TOOL_CALENDAR_EVENT,
                                    $id,
                                    'visible',
                                    $authorId,
                                    $groupInfo,
                                    $userId,
                                    $start,
                                    $end,
                                    $this->sessionId
                                );
                            }
                        }

                        // Delete users.
                        if (!empty($usersToDelete)) {
                            foreach ($usersToDelete as $userId) {
                                api_item_property_delete(
                                    $this->course,
                                    TOOL_CALENDAR_EVENT,
                                    $id,
                                    $userId,
                                    $groupInfo,
                                    $this->sessionId
                                );
                            }
                        }
                    }
                }

                // Add announcement.
                if (isset($addAnnouncement) && !empty($addAnnouncement)) {
                    $this->storeAgendaEventAsAnnouncement(
                        $id,
                        $usersToSend
                    );
                }

                // Add attachment.
                if (isset($attachmentArray) && !empty($attachmentArray)) {
                    $counter = 0;
                    foreach ($attachmentArray as $attachmentItem) {
                        if (empty($attachmentItem['id'])) {
                            $this->addAttachment(
                                $id,
                                $attachmentItem,
                                $attachmentCommentList[$counter],
                                $this->course
                            );
                        } else {
                            $this->updateAttachment(
                                $attachmentItem['id'],
                                $id,
                                $attachmentItem,
                                $attachmentCommentList[$counter],
                                $this->course
                            );
                        }
                        $counter++;
                    }
                }
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

                    if (api_get_configuration_value('allow_careers_in_global_agenda')) {
                        $attributes['career_id'] = $careerId;
                        $attributes['promotion_id'] = $promotionId;
                    }

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

        $this->editReminders($id, $remindersList);

        return true;
    }

    /**
     * @param int  $id
     * @param bool $deleteAllItemsFromSerie
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteEvent($id, $deleteAllItemsFromSerie = false)
    {
        $em = Database::getManager();

        switch ($this->type) {
            case 'personal':
                $eventInfo = $this->get_event($id);
                if ($eventInfo['user'] == api_get_user_id()) {
                    Database::delete(
                        $this->tbl_personal_agenda,
                        ['id = ?' => $id]
                    );
                } elseif (api_get_configuration_value('agenda_collective_invitations')) {
                    $currentUser = api_get_user_entity(api_get_user_id());

                    $eventRepo = $em->getRepository('ChamiloCoreBundle:PersonalAgenda');
                    $event = $eventRepo->findOneByIdAndInvitee($id, $currentUser);
                    $invitation = $event ? $event->getInvitation() : null;

                    if ($invitation) {
                        $invitation->removeInviteeUser($currentUser);

                        $em->persist($invitation);
                        $em->flush();
                    }
                }
                break;
            case 'course':
                $courseId = api_get_course_int_id();
                $isAllowToEdit = $this->getIsAllowedToEdit();

                if (!empty($courseId) && $isAllowToEdit) {
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

                    // Removing from events.
                    Database::delete(
                        $this->tbl_course_agenda,
                        ['id = ? AND c_id = ?' => [$id, $courseId]]
                    );

                    api_item_property_update(
                        $this->course,
                        TOOL_CALENDAR_EVENT,
                        $id,
                        'delete',
                        api_get_user_id()
                    );

                    // Removing from series.
                    Database::delete(
                        $this->table_repeat,
                        [
                            'cal_id = ? AND c_id = ?' => [
                                $id,
                                $courseId,
                            ],
                        ]
                    );

                    if (isset($eventInfo['attachment']) && !empty($eventInfo['attachment'])) {
                        foreach ($eventInfo['attachment'] as $attachment) {
                            self::deleteAttachmentFile(
                                $attachment['id'],
                                $this->course
                            );
                        }
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

    public function exportEventMembersToCsv(int $id, $type = "Invitee")
    {
        if (false === api_get_configuration_value('agenda_event_subscriptions') && false === api_get_configuration_value('agenda_collective_invitations')) {
            return;
        }
        if ('personal' !== $this->type) {
            return;
        }
        if ($type === "Invitee") {
            $members = self::getInviteesForPersonalEvent($id, AgendaEventInvitee::class);
        } elseif ($type === "Subscriber") {
            $members = self::getInviteesForPersonalEvent($id, AgendaEventSubscriber::class);
        }
        $data = [];
        $data[] = [
            'OfficialCode',
            'Lastname',
            'Firsname',
            'Email',
        ];
        $count = 1;
        foreach ($members as $member) {
            $user = api_get_user_info($member['id']);
            $data[$count][] = $user['official_code'];
            $data[$count][] = $user['lastname'];
            $data[$count][] = $user['firstname'];
            $data[$count][] = $user['email'];
            $count++;
        }

        return $data;
    }

    public function subscribeCurrentUserToEvent(int $id)
    {
        if (false === api_get_configuration_value('agenda_event_subscriptions')) {
            return;
        }

        if ('personal' !== $this->type) {
            return;
        }

        $em = Database::getManager();

        $currentUser = api_get_user_entity(api_get_user_id());
        $personalEvent = $em->find(PersonalAgenda::class, $id);

        /** @var AgendaEventSubscription $subscription */
        $subscription = $personalEvent ? $personalEvent->getInvitation() : null;

        if (!$subscription) {
            return;
        }

        if ($subscription->getInvitees()->count() >= $subscription->getMaxAttendees()
            && $subscription->getMaxAttendees() > 0
        ) {
            return;
        }

        if (AgendaEventSubscription::SUBSCRIPTION_CLASS === $personalEvent->getSubscriptionVisibility()) {
            $objGroup = new UserGroup();
            $groupList = $objGroup->getUserGroupListByUser($currentUser->getId(), UserGroup::NORMAL_CLASS);
            $groupIdList = array_column($groupList, 'id');

            if (!in_array($personalEvent->getSubscriptionItemId(), $groupIdList)) {
                return;
            }
        }

        $subscriber = (new AgendaEventSubscriber())
            ->setUser($currentUser)
        ;

        $subscription->addInvitee($subscriber);

        $em->flush();
    }

    public function unsubscribeCurrentUserToEvent(int $id)
    {
        if (false === api_get_configuration_value('agenda_event_subscriptions')) {
            return;
        }

        if ('personal' !== $this->type) {
            return;
        }

        $em = Database::getManager();

        $currentUser = api_get_user_entity(api_get_user_id());
        $personalEvent = $em->find(PersonalAgenda::class, $id);

        /** @var AgendaEventSubscription $subscription */
        $subscription = $personalEvent ? $personalEvent->getInvitation() : null;

        if (!$subscription) {
            return;
        }

        $subscription->removeInviteeUser($currentUser);

        $em->flush();
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
                $courseInfo = api_get_course_info_by_id($courseId);

                // Session coach can see all events inside a session.
                if (api_is_coach()) {
                    // Own course
                    $this->getCourseEvents(
                        $start,
                        $end,
                        $courseInfo,
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
                        $courseInfo,
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

                if ($sessionFilterActive == false) {
                    // Getting personal events
                    $this->getPersonalEvents($start, $end);

                    // Getting platform/admin events
                    $this->getPlatformEvents($start, $end);
                }

                $ignoreVisibility = api_get_configuration_value('personal_agenda_show_all_session_events');

                $session_list = [];
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

                if (api_is_drh()) {
                    if (api_drh_can_access_all_session_content()) {
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
                                $courses = SessionManager::get_course_list_by_session_id($sessionId);
                                $sessionInfo = [
                                    'session_id' => $sessionId,
                                    'courses' => $courses,
                                ];
                                $session_list[] = $sessionInfo;
                            }
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
                                $courseInfo = api_get_course_info_by_id(
                                    $course_item['real_id']
                                );
                                $this->getCourseEvents(
                                    $start,
                                    $end,
                                    $courseInfo,
                                    0,
                                    $my_session_id
                                );
                            }
                        }
                    }
                }

                if (!empty($my_course_list) && $sessionFilterActive == false) {
                    foreach ($my_course_list as $courseInfoItem) {
                        $courseInfo = api_get_course_info_by_id(
                            $courseInfoItem['real_id']
                        );
                        if (isset($courseId) && !empty($courseId)) {
                            if ($courseInfo['real_id'] == $courseId) {
                                $this->getCourseEvents(
                                    $start,
                                    $end,
                                    $courseInfo,
                                    0,
                                    0,
                                    $user_id
                                );
                            }
                        } else {
                            $this->getCourseEvents(
                                $start,
                                $end,
                                $courseInfo,
                                0,
                                0,
                                $user_id
                            );
                        }
                    }
                }

                if ($start && $end) {
                    $this->loadSessionsAsEvents($start, $end);
                }

                break;
        }

        if (api_get_configuration_value('agenda_reminders')) {
            $this->events = array_map(
                function (array $eventInfo) {
                    $id = str_replace(['personal_', 'course_', 'session_'], '', $eventInfo['id']);

                    $eventInfo['reminders'] = $this->parseEventReminders(
                        $this->getEventReminders(
                            $id,
                            'session' === $eventInfo['type'] ? 'course' : $eventInfo['type']
                        )
                    );

                    return $eventInfo;
                },
                $this->events
            );
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
        $agendaCollectiveInvitations = api_get_configuration_value('agenda_collective_invitations');

        switch ($this->type) {
            case 'personal':
                $user = api_get_user_entity(api_get_user_id());
                $sql = "SELECT * FROM ".$this->tbl_personal_agenda."
                        WHERE id = $id AND user = ".$user->getId();
                $result = Database::query($sql);
                if (Database::num_rows($result)) {
                    $event = Database::fetch_array($result, 'ASSOC');
                    $event['description'] = $event['text'];
                    $event['content'] = $event['text'];
                    $event['start_date'] = $event['date'];
                    $event['end_date'] = $event['enddate'];
                }

                if (null !== $event) {
                    return $event;
                }

                if ($agendaCollectiveInvitations) {
                    $eventRepo = Database::getManager()->getRepository('ChamiloCoreBundle:PersonalAgenda');
                    $event = $eventRepo->findOneByIdAndInvitee($id, $user);

                    if ($event && $event->isCollective()) {
                        return [
                            'id' => $event->getId(),
                            'user' => $event->getUser(),
                            'title' => $event->getTitle(),
                            'text' => $event->getText(),
                            'date' => $event->getDate()->format('Y-m-d H:i:s'),
                            'enddate' => $event->getEndDate()->format('Y-m-d H:i:s'),
                            'course' => null,
                            'parent_event_id' => $event->getParentEventId(),
                            'all_day' => $event->getAllDay(),
                            'color' => $event->getColor(),
                            'agenda_event_invitation_id' => $event->getInvitation()->getId(),
                            'collective' => $event->isCollective(),
                            'description' => $event->getText(),
                            'content' => $event->getText(),
                            'start_date' => $event->getDate()->format('Y-m-d H:i:s'),
                            'end_date' => $event->getEndDate()->format('Y-m-d H:i:s'),
                        ];
                    }
                }

                return null;
            case 'course':
                if (!empty($this->course['real_id'])) {
                    $sql = "SELECT * FROM ".$this->tbl_course_agenda."
                            WHERE c_id = ".$this->course['real_id']." AND id = ".$id;
                    $result = Database::query($sql);
                    if (Database::num_rows($result)) {
                        $event = Database::fetch_array($result, 'ASSOC');
                        $event['description'] = $event['content'];

                        // Getting send to array
                        $event['send_to'] = $this->getUsersAndGroupSubscribedToEvent(
                            $id,
                            $this->course['real_id'],
                            $this->sessionId
                        );

                        // Getting repeat info
                        $event['repeat_info'] = $this->getRepeatedInfoByEvent(
                            $id,
                            $this->course['real_id']
                        );

                        if (!empty($event['parent_event_id'])) {
                            $event['parent_info'] = $this->get_event($event['parent_event_id']);
                        }

                        $event['attachment'] = $this->getAttachmentList(
                            $id,
                            $this->course
                        );
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
        $startDate = null;
        $endDate = null;
        $startCondition = '';
        $endCondition = '';

        $agendaCollectiveInvitations = api_get_configuration_value('agenda_collective_invitations');
        $agendaEventSubscriptions = api_get_configuration_value('agenda_event_subscriptions');
        $userIsAdmin = api_is_platform_admin();

        $queryParams = [];

        if ($start !== 0) {
            $queryParams['start_date'] = api_get_utc_datetime($start, true, true);
            $startCondition = "AND pa.date >= :start_date";
        }
        if ($end !== 0) {
            $queryParams['end_date'] = api_get_utc_datetime($end, false, true);
            $endCondition = "AND (pa.enddate <= :end_date OR pa.enddate IS NULL)";
        }
        $user_id = api_get_user_id();

        $queryParams['user_id'] = $user_id;
        $userCondition = "pa.user = :user_id";

        $objGroup = new UserGroup();

        if ($agendaEventSubscriptions) {
            $groupList = $objGroup->getUserGroupListByUser($user_id, UserGroup::NORMAL_CLASS);

            $userCondition = "(
                    $userCondition
                    OR (
                        pa.subscriptionVisibility = ".AgendaEventSubscription::SUBSCRIPTION_ALL;

            if ($groupList) {
                $userCondition .= "
                        OR (
                            pa.subscriptionVisibility = ".AgendaEventSubscription::SUBSCRIPTION_CLASS."
                            AND pa.subscriptionItemId IN (".implode(', ', array_column($groupList, 'id')).")
                        )
                ";
            }

            $userCondition .= "
                    )
                )
            ";
        }

        $sql = "SELECT pa FROM ChamiloCoreBundle:PersonalAgenda AS pa WHERE $userCondition $startCondition $endCondition";

        $result = Database::getManager()
            ->createQuery($sql)
            ->setParameters($queryParams)
            ->getResult();

        $my_events = [];

        /** @var PersonalAgenda $row */
        foreach ($result as $row) {
            $event = [];
            $event['id'] = 'personal_'.$row->getId();
            $event['title'] = $row->getTitle();
            $event['className'] = 'personal';
            $event['borderColor'] = $event['backgroundColor'] = $this->event_personal_color;
            $event['editable'] = $user_id === (int) $row->getUser();
            $event['sent_to'] = get_lang('Me');
            $event['type'] = 'personal';

            if (!empty($row->getDate())) {
                $event['start'] = $this->formatEventDate($row->getDate());
                $event['start_date_localtime'] = api_get_local_time($row->getDate());
            }

            if (!empty($row->getEnddate())) {
                $event['end'] = $this->formatEventDate($row->getEnddate());
                $event['end_date_localtime'] = api_get_local_time($row->getEnddate());
            }

            $event['description'] = $row->getText();
            $event['allDay'] = $row->getAllDay();
            $event['parent_event_id'] = 0;
            $event['has_children'] = 0;

            if ($agendaCollectiveInvitations || $agendaEventSubscriptions) {
                $subscription = $row->getInvitation();

                if ($subscription instanceof AgendaEventSubscription) {
                    $subscribers = $subscription->getInvitees();

                    $event['subscription_visibility'] = $row->getSubscriptionVisibility();
                    $event['max_subscriptions'] = $subscription->getMaxAttendees();
                    $event['can_subscribe'] = $subscribers->count() < $subscription->getMaxAttendees()
                        || $subscription->getMaxAttendees() === 0;
                    $event['user_is_subscribed'] = $subscription->hasUserAsInvitee(api_get_user_entity($user_id));
                    $event['count_subscribers'] = $subscribers->count();

                    if ($userIsAdmin) {
                        $event['subscribers'] = self::getInviteesForPersonalEvent($row->getId(), AgendaEventSubscriber::class);
                    }

                    if (AgendaEventSubscription::SUBSCRIPTION_CLASS === $row->getSubscriptionVisibility()) {
                        $groupInfo = $objGroup->get($row->getSubscriptionItemId());
                        $event['usergroup'] = $groupInfo['name'];
                    }
                } else {
                    $event['collective'] = $row->isCollective();
                    $event['invitees'] = self::getInviteesForPersonalEvent($row->getId());
                }
            }

            $my_events[] = $event;
            $this->events[] = $event;
        }

        if ($agendaCollectiveInvitations) {
            $this->loadEventsAsInvitee(
                api_get_user_entity($user_id),
                $startDate,
                $endDate
            );
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

    public static function getInviteesForPersonalEvent($eventId, $type = AgendaEventInvitee::class): array
    {
        $em = Database::getManager();
        $event = $em->find('ChamiloCoreBundle:PersonalAgenda', $eventId);

        $invitation = $event->getInvitation();

        if ($invitation instanceof AgendaEventSubscription
            && AgendaEventInvitee::class === $type
        ) {
            return [];
        }

        $inviteeRepo = $em->getRepository($type);
        $invitees = $inviteeRepo->findByInvitation($invitation);

        $inviteeList = [];

        foreach ($invitees as $invitee) {
            $inviteeUser = $invitee->getUser();

            $inviteeList[] = [
                'id' => $inviteeUser->getId(),
                'name' => $inviteeUser->getCompleteNameWithUsername(),
            ];
        }

        return $inviteeList;
    }

    /**
     * Get user/group list per event.
     *
     * @param int $eventId
     * @param int $courseId
     * @param int $sessionId
     * @paraù int $sessionId
     *
     * @return array
     */
    public function getUsersAndGroupSubscribedToEvent(
        $eventId,
        $courseId,
        $sessionId
    ) {
        $eventId = (int) $eventId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $sessionCondition = "ip.session_id = $sessionId";
        if (empty($sessionId)) {
            $sessionCondition = " (ip.session_id = 0 OR ip.session_id IS NULL) ";
        }

        $tlb_course_agenda = Database::get_course_table(TABLE_AGENDA);
        $tbl_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        // Get sent_tos
        $sql = "SELECT DISTINCT to_user_id, to_group_id
                FROM $tbl_property ip
                INNER JOIN $tlb_course_agenda agenda
                ON (
                  ip.ref = agenda.id AND
                  ip.c_id = agenda.c_id AND
                  ip.tool = '".TOOL_CALENDAR_EVENT."'
                )
                WHERE
                    ref = $eventId AND
                    ip.visibility = '1' AND
                    ip.c_id = $courseId AND
                    $sessionCondition
                ";

        $result = Database::query($sql);
        $users = [];
        $groups = [];
        $everyone = false;

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if (!empty($row['to_group_id'])) {
                $groups[] = $row['to_group_id'];
            }
            if (!empty($row['to_user_id'])) {
                $users[] = $row['to_user_id'];
            }

            if (empty($groups) && empty($users)) {
                if ($row['to_group_id'] == 0) {
                    $everyone = true;
                }
            }
        }

        return [
            'everyone' => $everyone,
            'users' => $users,
            'groups' => $groups,
        ];
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
     * @param array  $courseInfo
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
        $courseInfo,
        $groupId = 0,
        $sessionId = 0,
        $user_id = 0,
        $color = ''
    ) {
        $start = isset($start) && !empty($start) ? api_get_utc_datetime(intval($start)) : null;
        $end = isset($end) && !empty($end) ? api_get_utc_datetime(intval($end)) : null;

        if (empty($courseInfo)) {
            return [];
        }
        $courseId = $courseInfo['real_id'];

        if (empty($courseId)) {
            return [];
        }

        $sessionId = (int) $sessionId;
        $user_id = (int) $user_id;

        $groupList = GroupManager::get_group_list(
            null,
            $courseInfo,
            null,
            $sessionId
        );

        $groupNameList = [];
        if (!empty($groupList)) {
            foreach ($groupList as $group) {
                $groupNameList[$group['iid']] = $group['name'];
            }
        }

        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
            $isAllowToEdit = true;
        } else {
            $isAllowToEdit = CourseManager::is_course_teacher(
                api_get_user_id(),
                $courseInfo['code']
            );
        }

        $isAllowToEditByHrm = false;
        if (!empty($sessionId)) {
            $allowDhrToEdit = api_get_configuration_value('allow_agenda_edit_for_hrm');
            if ($allowDhrToEdit) {
                $isHrm = SessionManager::isUserSubscribedAsHRM($sessionId, api_get_user_id());
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
                    // c_item_property.to_group_id field was migrated to use
                    // c_group_info.iid
                    $groupMemberships = array_column($groupList, 'iid');
                }
            } else {
                // get only related groups from user
                $groupMemberships = GroupManager::get_group_ids(
                    $courseId,
                    api_get_user_id()
                );
            }
        }

        $tlb_course_agenda = Database::get_course_table(TABLE_AGENDA);
        $tbl_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $shareEventsInSessions = 1 == api_get_course_setting('agenda_share_events_in_sessions', $courseInfo);

        $agendaSessionCondition = str_replace(
            ' AND ',
            '',
            api_get_session_condition($sessionId, true, $shareEventsInSessions, 'agenda.session_id')
        );
        $ipSessionCondition = api_get_session_condition($sessionId, true, $shareEventsInSessions, 'ip.session_id');

        $sessionCondition = "($agendaSessionCondition $ipSessionCondition)";

        if ($isAllowToEdit) {
            // No group filter was asked
            if (empty($groupId)) {
                if (empty($user_id)) {
                    // Show all events not added in group
                    $userCondition = ' (ip.to_group_id IS NULL OR ip.to_group_id = 0) ';
                    // admin see only his stuff
                    if ($this->type === 'personal') {
                        $userCondition = " (ip.to_user_id = ".api_get_user_id()." AND (ip.to_group_id IS NULL OR ip.to_group_id = 0) ) ";
                        $userCondition .= " OR ( (ip.to_user_id = 0 OR ip.to_user_id is NULL)  AND (ip.to_group_id IS NULL OR ip.to_group_id = 0) ) ";
                    }

                    if (!empty($groupMemberships)) {
                        // Show events sent to selected groups
                        $userCondition .= " OR (ip.to_user_id = 0 OR ip.to_user_id is NULL) AND (ip.to_group_id IN (".implode(", ", $groupMemberships).")) ";
                    }
                } else {
                    // Show events of requested user in no group
                    $userCondition = " (ip.to_user_id = $user_id AND (ip.to_group_id IS NULL OR ip.to_group_id = 0)) ";
                    // Show events sent to selected groups
                    if (!empty($groupMemberships)) {
                        $userCondition .= " OR (ip.to_user_id = $user_id) AND (ip.to_group_id IN (".implode(", ", $groupMemberships).")) ";
                    }
                }
            } else {
                // Show only selected groups (depending of user status)
                $userCondition = " (ip.to_user_id = 0 OR ip.to_user_id is NULL) AND (ip.to_group_id IN (".implode(", ", $groupMemberships).")) ";

                if (!empty($groupMemberships)) {
                    // Show send to $user_id in selected groups
                    $userCondition .= " OR (ip.to_user_id = $user_id) AND (ip.to_group_id IN (".implode(", ", $groupMemberships).")) ";
                }
            }
        } else {
            // No group filter was asked
            if (empty($groupId)) {
                // Show events sent to everyone and no group
                $userCondition = ' ( (ip.to_user_id = 0 OR ip.to_user_id is NULL) AND (ip.to_group_id IS NULL OR ip.to_group_id = 0) ';

                // Show events sent to selected groups
                if (!empty($groupMemberships)) {
                    $userCondition .= " OR (ip.to_user_id = 0 OR ip.to_user_id is NULL) AND (ip.to_group_id IN (".implode(", ", $groupMemberships)."))) ";
                } else {
                    $userCondition .= " ) ";
                }
                $userCondition .= " OR (ip.to_user_id = ".api_get_user_id()." AND (ip.to_group_id IS NULL OR ip.to_group_id = 0)) ";
            } else {
                if (!empty($groupMemberships)) {
                    // Show send to everyone - and only selected groups
                    $userCondition = " (ip.to_user_id = 0 OR ip.to_user_id is NULL) AND (ip.to_group_id IN (".implode(", ", $groupMemberships).")) ";
                }
            }

            // Show sent to only me and no group
            if (!empty($groupMemberships)) {
                $userCondition .= " OR (ip.to_user_id = ".api_get_user_id().") AND (ip.to_group_id IN (".implode(", ", $groupMemberships).")) ";
            } else {
                // Show sent to only me and selected groups
            }
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
        $result = Database::query($sql);

        $coachCanEdit = false;
        if (!empty($sessionId)) {
            $coachCanEdit = api_is_coach($sessionId, $courseId) || api_is_platform_admin();
        }

        if (Database::num_rows($result)) {
            $eventsAdded = array_column($this->events, 'unique_id');
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $event = [];
                $event['id'] = 'course_'.$row['id'];
                $event['unique_id'] = $row['iid'];
                // To avoid doubles
                if (in_array($event['unique_id'], $eventsAdded)) {
                    continue;
                }

                $eventsAdded[] = $event['unique_id'];
                $eventId = $row['ref'];
                $items = $this->getUsersAndGroupSubscribedToEvent(
                    $eventId,
                    $courseId,
                    $this->sessionId
                );
                $group_to_array = $items['groups'];
                $user_to_array = $items['users'];
                $attachmentList = $this->getAttachmentList(
                    $row['id'],
                    $courseInfo
                );
                $event['attachment'] = '';
                if (!empty($attachmentList)) {
                    foreach ($attachmentList as $attachment) {
                        $has_attachment = Display::return_icon(
                            'attachment.gif',
                            get_lang('Attachment')
                        );
                        $user_filename = $attachment['filename'];
                        $url = api_get_path(WEB_CODE_PATH).'calendar/download.php?file='.$attachment['path'].'&course_id='.$courseId.'&'.api_get_cidreq();
                        $event['attachment'] .= $has_attachment.
                            Display::url(
                                $user_filename,
                                $url
                            ).'<br />';
                    }
                }

                $event['title'] = $row['title'];
                $event['className'] = 'course';
                $event['allDay'] = 'false';
                $event['course_id'] = $courseId;
                $event['borderColor'] = $event['backgroundColor'] = $this->event_course_color;

                $sessionInfo = [];
                if (isset($row['session_id']) && !empty($row['session_id'])) {
                    $sessionInfo = api_get_session_info($sessionId);
                    $event['borderColor'] = $event['backgroundColor'] = $this->event_session_color;
                }

                $event['session_name'] = isset($sessionInfo['name']) ? $sessionInfo['name'] : '';
                $event['course_name'] = isset($courseInfo['title']) ? $courseInfo['title'] : '';

                if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
                    $event['borderColor'] = $event['backgroundColor'] = $this->event_group_color;
                }

                if (!empty($color)) {
                    $event['borderColor'] = $event['backgroundColor'] = $color;
                }

                if (isset($row['color']) && !empty($row['color'])) {
                    $event['borderColor'] = $event['backgroundColor'] = $row['color'];
                }

                $event['editable'] = false;
                if ($this->getIsAllowedToEdit() && $this->type == 'course') {
                    $event['editable'] = true;
                    if (!empty($sessionId)) {
                        if ($coachCanEdit == false) {
                            $event['editable'] = false;
                        }
                        if ($isAllowToEditByHrm) {
                            $event['editable'] = true;
                        }
                        if ($sessionId != $row['session_id']) {
                            $event['editable'] = false;
                        }
                    }
                    // if user is author then he can edit the item
                    if (api_get_user_id() == $row['insert_user_id']) {
                        $event['editable'] = true;
                    }
                }

                if (!empty($row['start_date'])) {
                    $event['start'] = $this->formatEventDate($row['start_date']);
                    $event['start_date_localtime'] = api_get_local_time($row['start_date']);
                }
                if (!empty($row['end_date'])) {
                    $event['end'] = $this->formatEventDate($row['end_date']);
                    $event['end_date_localtime'] = api_get_local_time($row['end_date']);
                }

                $event['sent_to'] = '';
                $event['type'] = 'course';
                if ($row['session_id'] != 0) {
                    $event['type'] = 'session';
                }

                // Event Sent to a group?
                if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
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
                    $event['group_id'] = $row['to_group_id'];
                }

                // Event sent to a user?
                if (isset($row['to_user_id'])) {
                    $sent_to = [];
                    if (!empty($user_to_array)) {
                        foreach ($user_to_array as $item) {
                            $user_info = api_get_user_info($item);
                            // Add username as tooltip for $event['sent_to'] - ref #4226
                            $username = api_htmlentities(
                                sprintf(
                                    get_lang('LoginX'),
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
                }

                //Event sent to everyone!
                if (empty($event['sent_to'])) {
                    $event['sent_to'] = '<div class="label_tag notice">'.get_lang('Everyone').'</div>';
                }

                $event['description'] = Security::remove_XSS($row['content']);
                $event['visibility'] = $row['visibility'];
                $event['real_id'] = $row['id'];
                $event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;
                $event['parent_event_id'] = $row['parent_event_id'];
                $event['has_children'] = $this->hasChildren($row['id'], $courseId) ? 1 : 0;
                $event['comment'] = Security::remove_XSS($row['comment']);
                $this->events[] = $event;
            }
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

        if (!Database::num_rows($result)) {
            return [];
        }

        $my_events = [];
        $allowCareersInGlobalAgenda = api_get_configuration_value('allow_careers_in_global_agenda');
        $userId = api_get_user_id();
        $userVisibility = SystemAnnouncementManager::getCurrentUserVisibility();

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $event = [];
            $event['id'] = 'platform_'.$row['id'];
            $event['title'] = $row['title'];
            $event['className'] = 'platform';
            $event['allDay'] = 'false';
            $event['borderColor'] = $event['backgroundColor'] = $this->event_platform_color;
            $event['editable'] = false;
            $event['type'] = 'admin';

            if (api_is_platform_admin() && $this->type === 'admin') {
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
            $event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;
            $event['parent_event_id'] = 0;
            $event['has_children'] = 0;
            $event['description'] = $row['content'];

            if ($allowCareersInGlobalAgenda) {
                $event['career'] = null;
                $event['promotion'] = null;

                $careerId = (int) $row['career_id'];
                $promotionId = (int) $row['promotion_id'];

                $careerPromotionEventIsVisibleForUser = true;

                if (($careerId || $promotionId) && 'admin' !== $this->type) {
                    $careerPromotionEventIsVisibleForUser = SystemAnnouncementManager::isVisibleAnnouncementForUser(
                        $userId,
                        $userVisibility,
                        $careerId,
                        $promotionId
                    );
                }

                if (false === $careerPromotionEventIsVisibleForUser) {
                    continue;
                }

                if (0 !== $careerId) {
                    $careerInfo = (new Career())->get($row['career_id']);

                    unset($careerInfo['status'], $careerInfo['created_at'], $careerInfo['updated_at']);

                    $event['career'] = $careerInfo;
                }

                if (0 !== $promotionId) {
                    $promotionInfo = (new Promotion())->get($row['promotion_id']);

                    unset(
                        $promotionInfo['career_id'],
                        $promotionInfo['status'],
                        $promotionInfo['created_at'],
                        $promotionInfo['updated_at']
                    );

                    $event['promotion'] = $promotionInfo;
                }
            }

            $my_events[] = $event;
            $this->events[] = $event;
        }

        return $my_events;
    }

    /**
     * @param FormValidator $form
     * @param array         $groupList
     * @param array         $userList
     * @param array         $sendTo               array('users' => [1, 2], 'groups' => [3, 4])
     * @param array         $attributes
     * @param bool          $addOnlyItemsInSendTo
     * @param bool          $required
     */
    public function setSendToSelect(
        $form,
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

        /** @var HTML_QuickForm_select $select */
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
                $count_users = isset($group['count_users']) ? $group['count_users'] : $group['userNb'];
                $count_users = " &ndash; $count_users ".get_lang('Users');
                $option = [
                    'text' => $group['name'].$count_users,
                    'value' => "GROUP:".$group['id'],
                ];
                $selected = in_array(
                    $group['id'],
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
                if ($user['status'] == ANONYMOUS) {
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
                if ($item == 'everyone') {
                    $sendTo['everyone'] = true;
                } else {
                    list($type, $id) = explode(':', $item);
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
     * @param int    $eventId
     * @param string $type
     *
     * @return array<int, AgendaReminder>
     */
    public function getEventReminders($eventId, $type = null): array
    {
        $em = Database::getManager();
        $remindersRepo = $em->getRepository('ChamiloCoreBundle:AgendaReminder');

        return $remindersRepo->findBy(
            [
                'eventId' => $eventId,
                'type' => $type ?: $this->type,
            ]
        );
    }

    public function parseEventReminders(array $eventReminders): array
    {
        return array_map(
            function (AgendaReminder $reminder) {
                $interval = $reminder->getDateInterval();

                $reminderInfo = [
                    'id' => $reminder->getId(),
                    'type' => $reminder->getType(),
                    'sent' => $reminder->isSent(),
                    'date_interval' => [$interval->format('%a'), 'd'],
                ];

                if ($interval->i) {
                    $reminderInfo['date_interval'] = [$interval->i, 'i'];
                } elseif ($interval->h) {
                    $reminderInfo['date_interval'] = [$interval->h, 'h'];
                } elseif ($interval->d) {
                    $reminderInfo['date_interval'] = [$interval->d, 'd'];
                }

                return $reminderInfo;
            },
            $eventReminders
        );
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

        $em = Database::getManager();
        $personalEvent = 'personal' === $this->type && $id ? $em->find('ChamiloCoreBundle:PersonalAgenda', $id) : null;

        $url = api_get_self().'?action='.$action.'&id='.$id.'&type='.$this->type;
        if ($this->type == 'course') {
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
        $form_Title = get_lang('AddCalendarItem');
        if (!empty($id)) {
            $form_Title = get_lang('ModifyCalendarItem');
        }

        $form->addHeader($form_Title);
        $form->addElement('hidden', 'id', $id);
        $form->addElement('hidden', 'action', $action);
        $form->addElement('hidden', 'id_attach', $idAttach);

        $isSubEventEdition = false;
        $isParentFromSerie = false;
        $showAttachmentForm = true;

        if ($this->type == 'course') {
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
                    get_lang('EditingThisEventWillRemoveItFromTheSerie'),
                    'warning'
                )
            );
        }

        $form->addElement('text', 'title', get_lang('ItemTitle'));

        if (isset($groupId) && !empty($groupId)) {
            $form->addElement(
                'hidden',
                'users_to_send[]',
                "GROUP:$groupId"
            );
            $form->addElement('hidden', 'to', 'true');
        } else {
            $sendTo = isset($params['send_to']) ? $params['send_to'] : ['everyone' => true];
            if ($this->type == 'course') {
                $this->showToForm($form, $sendTo, [], false, true);
            }
        }

        $form->addDateRangePicker(
            'date_range',
            get_lang('DateRange'),
            false,
            ['id' => 'date_range']
        );
        $form->addElement('checkbox', 'all_day', null, get_lang('AllDay'));

        if ($this->type == 'course') {
            $repeat = $form->addElement(
                'checkbox',
                'repeat',
                null,
                get_lang('RepeatEvent'),
                ['onclick' => 'return plus_repeated_event();']
            );
            $form->addElement(
                'html',
                '<div id="options2" style="display:none">'
            );
            $form->addElement(
                'select',
                'repeat_type',
                get_lang('RepeatType'),
                self::getRepeatTypes()
            );
            $form->addElement(
                'date_picker',
                'repeat_end_day',
                get_lang('RepeatEnd'),
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

        $form->addElement(
            'html_editor',
            'content',
            get_lang('Description'),
            null,
            [
                'ToolbarSet' => $toolbar,
                'Width' => '100%',
                'Height' => '200',
            ]
        );

        if ($this->type == 'course') {
            $form->addElement('textarea', 'comment', get_lang('Comment'));
            $form->addLabel(
                get_lang('FilesAttachment'),
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
                get_lang('AddOneMoreFile').'</a>
                 </span>&nbsp;('.sprintf(
                    get_lang('MaximunFileSizeX'),
                    format_file_size(
                        api_get_setting('message_max_upload_filesize')
                    )
                ).')'
            );

            if (isset($params['attachment']) && !empty($params['attachment'])) {
                $attachmentList = $params['attachment'];
                foreach ($attachmentList as $attachment) {
                    $params['file_comment'] = $attachment['comment'];
                    if (!empty($attachment['path'])) {
                        $form->addElement(
                            'checkbox',
                            'delete_attachment['.$attachment['id'].']',
                            null,
                            get_lang(
                                'DeleteAttachment'
                            ).': '.$attachment['filename']
                        );
                    }
                }
            }

            $form->addElement(
                'textarea',
                'file_comment',
                get_lang('FileComment')
            );
        }

        if (empty($id) && 'course' === $this->type) {
            $form->addElement(
                'checkbox',
                'add_announcement',
                null,
                get_lang('AddAnnouncement').'&nbsp('.get_lang('SendMail').')'
            );
        }

        $agendaCollectiveInvitations = api_get_configuration_value('agenda_collective_invitations');
        $agendaEventSubscriptions = api_is_platform_admin()
            && true === api_get_configuration_value('agenda_event_subscriptions');

        $allowCollectiveInvitations = $agendaCollectiveInvitations && 'personal' === $this->type;
        $allowEventSubscriptions = 'personal' === $this->type && $agendaEventSubscriptions;

        if ($allowCollectiveInvitations && $allowEventSubscriptions && !$personalEvent) {
            $form->addRadio(
                'invitation_type',
                get_lang('Allowed'),
                [
                    'invitations' => get_lang('Invitations'),
                    'subscriptions' => get_lang('Subscriptions'),
                ],
                [
                    'onchange' => "$('#invitations-block, #subscriptions-block').hide(); $('#' + this.value + '-block').show();",
                ]
            );
        }

        if ($allowCollectiveInvitations) {
            $this->addCollectiveInvitationsFields($form, $personalEvent);
        }

        if ($agendaEventSubscriptions) {
            $this->addSubscriptionFields($form, $personalEvent);
        }

        if (api_get_configuration_value('agenda_reminders')) {
            $form->addHtml('<div id="notification_list">');

            if ($id) {
                $this->addFieldsForRemindersToForm($id, $form);
            }

            $form->addHtml('</div>');
            $form->addButton('add_notification', get_lang('AddNotification'), 'bell-o')->setType('button');
            $form->addHtml('<hr>');
        }

        if (api_get_configuration_value('allow_careers_in_global_agenda') && 'admin' === $this->type) {
            Career::addCareerFieldsToForm($form);
            $form->addHtml('<hr>');
        }

        if ($id) {
            $form->addButtonUpdate(get_lang('ModifyEvent'));
        } else {
            $form->addButtonSave(get_lang('AgendaAdd'));
        }

        $form->setDefaults($params);
        $form->addRule(
            'date_range',
            get_lang('ThisFieldIsRequired'),
            'required'
        );
        $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    public function addFieldsForRemindersToForm(int $eventId, FormValidator $form)
    {
        $remindersList = $this->parseEventReminders(
            $this->getEventReminders($eventId)
        );

        foreach ($remindersList as $reminderInfo) {
            $form->addHtml('<div class="form-group">');
            $form
                ->addNumeric('notification_count[]', '', ['step' => 1, 'min' => 0])
                ->setValue($reminderInfo['date_interval'][0])
            ;
            $form
                ->addSelect(
                'notification_period[]',
                '',
                    [
                        'i' => get_lang('Minutes'),
                        'h' => get_lang('Hours'),
                        'd' => get_lang('Days'),
                    ]
                )
                ->setValue($reminderInfo['date_interval'][1])
            ;
            $form->addHtml('<div class="col-sm-2"><p class="form-control-static">'.get_lang('Before').'</p></div>');
            $form->addHtml(
                '<div class="text-right col-sm-2">'
                .'<button class="btn btn-default delete-notification" type="button" aria-label="'.get_lang('Delete').'"><em class="fa fa-times"></em></button>'
                .'</div>'
            );
            $form->addHtml('</div>');
        }

        $renderer = $form->defaultRenderer();
        $renderer->setElementTemplate(
            '<div class="col-sm-offset-2 col-sm-3">{element}</div>',
            'notification_count[]'
        );
        $renderer->setElementTemplate(
            '<div class="col-sm-3">{element}</div>',
            'notification_period[]'
        );
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
        if ($this->type != 'course') {
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
        $id = intval($id);
        if (empty($userId)) {
            $userId = api_get_user_id();
        } else {
            $userId = intval($userId);
        }

        if ($visibility == 0) {
            api_item_property_update(
                $courseInfo,
                TOOL_CALENDAR_EVENT,
                $id,
                'invisible',
                $userId
            );
        } else {
            api_item_property_update(
                $courseInfo,
                TOOL_CALENDAR_EVENT,
                $id,
                'visible',
                $userId
            );
        }
    }

    /**
     * Get repeat types.
     *
     * @return array
     */
    public static function getRepeatTypes()
    {
        return [
            'daily' => get_lang('RepeatDaily'),
            'weekly' => get_lang('RepeatWeekly'),
            'monthlyByDate' => get_lang('RepeatMonthlyByDate'),
            //monthlyByDay"> get_lang('RepeatMonthlyByDay');
            //monthlyByDayR' => get_lang('RepeatMonthlyByDayR'),
            'yearly' => get_lang('RepeatYearly'),
        ];
    }

    /**
     * Show a list with all the attachments according to the post's id.
     *
     * @param int   $eventId
     * @param array $courseInfo
     *
     * @return array with the post info
     */
    public function getAttachmentList($eventId, $courseInfo)
    {
        $tableAttachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
        $courseId = (int) $courseInfo['real_id'];
        $eventId = (int) $eventId;

        $sql = "SELECT id, path, filename, comment
                FROM $tableAttachment
                WHERE
                    c_id = $courseId AND
                    agenda_id = $eventId";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) != 0) {
            $list = Database::store_result($result, 'ASSOC');
        }

        return $list;
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
        $sql = "SELECT id, path, filename, comment
                FROM $tableAttachment
                WHERE
                    c_id = $courseId AND
                    agenda_id = $eventId AND
                    id = $attachmentId
                ";
        $result = Database::query($sql);
        if (Database::num_rows($result) != 0) {
            $row = Database::fetch_array($result, 'ASSOC');
        }

        return $row;
    }

    /**
     * Add an attachment file into agenda.
     *
     * @param int    $eventId
     * @param array  $fileUserUpload ($_FILES['user_upload'])
     * @param string $comment        about file
     * @param array  $courseInfo
     *
     * @return string
     */
    public function addAttachment(
        $eventId,
        $fileUserUpload,
        $comment,
        $courseInfo
    ) {
        $agenda_table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
        $eventId = (int) $eventId;

        // Storing the attachments
        $upload_ok = false;
        if (!empty($fileUserUpload['name'])) {
            $upload_ok = process_uploaded_file($fileUserUpload);
        }

        if (!empty($upload_ok)) {
            $courseDir = $courseInfo['directory'].'/upload/calendar';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $uploadDir = $sys_course_path.$courseDir;

            // Try to add an extension to the file if it hasn't one
            $new_file_name = add_ext_on_mime(
                stripslashes($fileUserUpload['name']),
                $fileUserUpload['type']
            );

            // user's file name
            $file_name = $fileUserUpload['name'];

            if (!filter_extension($new_file_name)) {
                return Display::return_message(
                    get_lang('UplUnableToSaveFileFilteredExtension'),
                    'error'
                );
            } else {
                $new_file_name = uniqid('');
                $new_path = $uploadDir.'/'.$new_file_name;
                $result = @move_uploaded_file(
                    $fileUserUpload['tmp_name'],
                    $new_path
                );
                $courseId = api_get_course_int_id();
                $size = intval($fileUserUpload['size']);
                // Storing the attachments if any
                if ($result) {
                    $params = [
                        'c_id' => $courseId,
                        'filename' => $file_name,
                        'comment' => $comment,
                        'path' => $new_file_name,
                        'agenda_id' => $eventId,
                        'size' => $size,
                    ];
                    $id = Database::insert($agenda_table_attachment, $params);
                    if ($id) {
                        $sql = "UPDATE $agenda_table_attachment
                                SET id = iid WHERE iid = $id";
                        Database::query($sql);

                        api_item_property_update(
                            $courseInfo,
                            'calendar_event_attachment',
                            $id,
                            'AgendaAttachmentAdded',
                            api_get_user_id()
                        );
                    }
                }
            }
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
            $this->deleteAttachmentFile($attachmentId, $courseInfo);
        }
        $this->addAttachment($eventId, $fileUserUpload, $comment, $courseInfo);
    }

    /**
     * This function delete a attachment file by id.
     *
     * @param int   $attachmentId
     * @param array $courseInfo
     *
     * @return string
     */
    public function deleteAttachmentFile($attachmentId, $courseInfo)
    {
        $table = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
        $attachmentId = (int) $attachmentId;
        $courseId = $courseInfo['real_id'];

        if (empty($courseId) || empty($attachmentId)) {
            return false;
        }

        $sql = "DELETE FROM $table
                WHERE c_id = $courseId AND id = ".$attachmentId;
        $result = Database::query($sql);

        // update item_property
        api_item_property_update(
            $courseInfo,
            'calendar_event_attachment',
            $attachmentId,
            'AgendaAttachmentDeleted',
            api_get_user_id()
        );

        if (!empty($result)) {
            return Display::return_message(
                get_lang("AttachmentFileDeleteSuccess"),
                'confirmation'
            );
        }
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
     * @param int $eventId
     * @param int $courseId
     *
     * @return bool
     */
    public function hasChildren($eventId, $courseId)
    {
        $eventId = (int) $eventId;
        $courseId = (int) $courseId;

        $sql = "SELECT count(DISTINCT(id)) as count
                FROM ".$this->tbl_course_agenda."
                WHERE
                    c_id = $courseId AND
                    parent_event_id = $eventId";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');

            return $row['count'] > 0;
        }

        return false;
    }

    public function displayActions(string $view, ?string $filter = ''): string
    {
        $groupInfo = GroupManager::get_group_properties(api_get_group_id());
        $groupIid = $groupInfo['iid'] ?? 0;

        $codePath = api_get_path(WEB_CODE_PATH);

        $currentUserId = api_get_user_id();
        $cidReq = api_get_cidreq();

        $actionsLeft = Display::url(
            Display::return_icon('calendar.png', get_lang('Calendar'), [], ICON_SIZE_MEDIUM),
            $codePath."calendar/agenda_js.php?type={$this->type}&$cidReq"
        );
        $actionsLeft .= Display::url(
            Display::return_icon('week.png', get_lang('AgendaList'), [], ICON_SIZE_MEDIUM),
            $codePath."calendar/agenda_list.php?type={$this->type}&$cidReq"
        );

        $isAllowedToEdit = api_is_allowed_to_edit(false, true);

        $form = '';
        if ($isAllowedToEdit
            || ('personal' === $this->type && !api_is_anonymous() && 'true' === api_get_setting('allow_personal_agenda'))
            || (
                '1' === api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() &&
                api_is_allowed_to_session_edit(false, true))
            || (
                GroupManager::user_has_access($currentUserId, $groupIid, GroupManager::GROUP_TOOL_CALENDAR)
                && GroupManager::is_tutor_of_group($currentUserId, $groupInfo)
            )
        ) {
            $actionsLeft .= Display::url(
                Display::return_icon('new_event.png', get_lang('AgendaAdd'), [], ICON_SIZE_MEDIUM),
                $codePath."calendar/agenda.php?action=add&type={$this->type}&$cidReq"
            );

            $actionsLeft .= Display::url(
                Display::return_icon('import_calendar.png', get_lang('ICalFileImport'), [], ICON_SIZE_MEDIUM),
                $codePath."calendar/agenda.php?action=importical&type={$this->type}&$cidReq"
            );

            if ($this->type === 'course') {
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

                if (true === api_get_configuration_value('agenda_reminders') && $isAllowedToEdit) {
                    $actionsLeft .= Display::url(
                        Display::return_icon(
                            'course_request_pending.png',
                            get_lang('ImportCourseEvents'),
                            [],
                            ICON_SIZE_MEDIUM
                        ),
                        $codePath."calendar/agenda.php?action=import_course_agenda_reminders&type={$this->type}&$cidReq"
                    );
                }
            }
        }

        if ($this->type === 'personal' && !api_is_anonymous()) {
            $actionsLeft .= Display::url(
                Display::return_icon('1day.png', get_lang('SessionsPlanCalendar'), [], ICON_SIZE_MEDIUM),
                $codePath.'calendar/planification.php'
            );

            if (api_is_student_boss() || api_is_platform_admin()) {
                $actionsLeft .= Display::url(
                    Display::return_icon('calendar-user.png', get_lang('MyStudentsSchedule'), [], ICON_SIZE_MEDIUM),
                    $codePath.'mySpace/calendar_plan.php'
                );
            }
        }

        if (api_is_platform_admin()
            || api_is_teacher()
            || api_is_student_boss()
            || api_is_drh()
            || api_is_session_admin()
            || api_is_coach()
        ) {
            if ($this->type == 'personal') {
                $form = '';
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
                    $sessions = ['0' => get_lang('SelectAnOption')] + $sessions;

                    $form->addSelect(
                        'session_id',
                        get_lang('Session'),
                        $sessions,
                        ['id' => 'session_id', 'onchange' => 'submit();']
                    );

                    $form->addButton('reset', get_lang('Reset'), 'eraser');
                    $form = $form->returnForm();
                }
            }
        }

        $actionsRight = '';
        if ($view == 'calendar') {
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
        $form->addHeader(get_lang('ICalFileImport'));
        $form->addElement('file', 'ical_import', get_lang('ICalFileImport'));
        $form->addRule(
            'ical_import',
            get_lang('ThisFieldIsRequired'),
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
            /** @var Sabre\VObject\Component\VEvent $event */
            foreach ($calendar->VEVENT as $event) {
                $tempDate = $event->DTSTART->getValue();
                if ('Z' == substr($tempDate, -1) && 'UTC' != date('e', strtotime($tempDate))) {
                    $event->DTSTART->setValue(gmdate('Ymd\THis\Z', strtotime($tempDate)));
                }
                $tempDate = $event->DTEND->getValue();
                if ('Z' == substr($tempDate, -1) && 'UTC' != date('e', strtotime($tempDate))) {
                    $event->DTEND->setValue(gmdate('Ymd\THis\Z', strtotime($tempDate)));
                }
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
                        if (strlen($repeat['UNTIL']) == 8) {
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

                    if (!empty($repeat['COUNT'])) {
                        /*$count = $repeat['COUNT'];
                        $interval = $repeat['INTERVAL'];
                        $endDate = null;
                        switch($freq) {
                            case 'daily':
                                $start = api_strtotime($startDateTime);
                                $date = new DateTime($startDateTime);
                                $days = $count * $interval;
                                var_dump($days);
                                $date->add(new DateInterval("P".$days."D"));
                                $endDate = $date->format('Y-m-d H:i');
                                //$endDate = $count *
                                for ($i = 0; $i < $count; $i++) {
                                    $days = 86400 * 7
                                }
                            }
                        }*/
                        //$res = agenda_add_repeat_item($courseInfo, $id, $freq, $count, $attendee);
                        /*$this->addRepeatedItem(
                            $id,
                            $freq,
                            $endDate,
                            $sentTo
                        );*/
                    }
                }
            }
        }

        if (!empty($messages)) {
            $messages = implode('<br /> ', $messages);
        } else {
            $messages = get_lang('NoAgendaItems');
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

        if ($filter == 'everyone') {
            $everyone = true;
        } else {
            if (substr($filter, 0, 1) == 'G') {
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
        $user_id = intval($user_id);

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
            if ($course_user_status == '1') {
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

        if ($type == "month_view" || $type == "") {
            // We are in month view
            $sql = "SELECT * FROM ".$tbl_global_agenda."
                    WHERE
                        MONTH(start_date) = ".$month." AND
                        YEAR(start_date) = ".$year."  AND
                        access_url_id = $current_access_url_id
                    ORDER BY start_date ASC";
        }
        // 2. creating the SQL statement for getting the personal agenda items in WEEK view
        if ($type == "week_view") { // we are in week view
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
        if ($type == "day_view") { // we are in day view
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

            if ($type == 'month_view') {
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

            // if the student has specified a course we a add a link to that course
            if ($item['course'] != "") {
                $url = api_get_path(
                        WEB_CODE_PATH
                    )."admin/agenda.php?cidReq=".urlencode(
                        $item['course']
                    )."&day=$day&month=$month&year=$year#$day"; // RH  //Patrick Cool: to highlight the relevant agenda item
                $course_link = "<a href=\"$url\" title=\"".$item['course']."\">".$item['course']."</a>";
            } else {
                $course_link = "";
            }
            // Creating the array that will be returned. If we have week or month view we have an array with the date as the key
            // if we have a day_view we use a half hour as index => key 33 = 16h30
            if ($type !== "day_view") {
                // This is the array construction for the WEEK or MONTH view
                //Display the Agenda global in the tab agenda (administrator)
                $agendaitems[$day] .= "<i>$start_time $end_time</i>&nbsp;-&nbsp;";
                $agendaitems[$day] .= "<b>".get_lang('GlobalEvent')."</b>";
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
                        'GlobalEvent'
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
        $user_id = intval($user_id);

        // 1. creating the SQL statement for getting the personal agenda items in MONTH view
        if ($type === "month_view" || $type === "") {
            // we are in month view
            $sql = "SELECT * FROM $tbl_personal_agenda
                    WHERE
                        user='".$user_id."' AND
                        MONTH(date)='".$month."' AND
                        YEAR(date) = '".$year."'
                     ORDER BY date ASC";
        }

        // 2. creating the SQL statement for getting the personal agenda items in WEEK view
        // we are in week view
        if ($type == "week_view") {
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
            $sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$user_id."' AND date>='".$start_filter."' AND date<='".$end_filter."'";
        }
        // 3. creating the SQL statement for getting the personal agenda items in DAY view
        if ($type == "day_view") {
            // we are in day view
            // we could use mysql date() function but this is only available from 4.1 and higher
            $start_filter = $year."-".$month."-".$day." 00:00:00";
            $start_filter = api_get_utc_datetime($start_filter);
            $end_filter = $year."-".$month."-".$day." 23:59:59";
            $end_filter = api_get_utc_datetime($end_filter);
            $sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$user_id."' AND date>='".$start_filter."' AND date<='".$end_filter."'";
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

            if ($type == 'month_view') {
                $item['calendar_type'] = 'personal';
                $item['start_date'] = $item['date'];
                $agendaitems[$day][] = $item;
                continue;
            }

            // if the student has specified a course we a add a link to that course
            if ($item['course'] != "") {
                $url = api_get_path(
                        WEB_CODE_PATH
                    )."calendar/agenda.php?cidReq=".urlencode(
                        $item['course']
                    )."&day=$day&month=$month&year=$year#$day"; // RH  //Patrick Cool: to highlight the relevant agenda item
                $course_link = "<a href=\"$url\" title=\"".$item['course']."\">".$item['course']."</a>";
            } else {
                $course_link = "";
            }
            // Creating the array that will be returned. If we have week or month view we have an array with the date as the key
            // if we have a day_view we use a half hour as index => key 33 = 16h30
            if ($type !== "day_view") {
                // This is the array construction for the WEEK or MONTH view

                //Display events in agenda
                $agendaitems[$day] .= "<div><i>$time_minute</i> $course_link <a href=\"myagenda.php?action=view&view=personal&day=$day&month=$month&year=$year&id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".$item['title']."</a></div><br />";
            } else {
                // this is the array construction for the DAY view
                $halfhour = 2 * $agendatime['0'];
                if ($agendatime['1'] >= '30') {
                    $halfhour = $halfhour + 1;
                }

                //Display events by list
                $agendaitems[$halfhour] .= "<div><i>$time_minute</i> $course_link <a href=\"myagenda.php?action=view&view=personal&day=$day&month=$month&year=$year&id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".$item['title']."</a></div>";
            }
        }

        return $agendaitems;
    }

    /**
     * Show the monthcalender of the given month.
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
        if (($year % 400 == 0) or ($year % 4 == 0 and $year % 100 != 0)) {
            $numberofdays[2] = 29;
        }
        //Get the first day of the month
        $dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
        //Start the week on monday
        $startdayofweek = $dayone['wday'] != 0 ? ($dayone['wday'] - 1) : 6;
        $g_cc = (isset($_GET['courseCode']) ? $_GET['courseCode'] : '');

        $next_month = ($month == 1 ? 12 : $month - 1);
        $prev_month = ($month == 12 ? 1 : $month + 1);

        $next_year = ($month == 1 ? $year - 1 : $year);
        $prev_year = ($month == 12 ? $year + 1 : $year);

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
                if (($curday == -1) && ($ii == $startdayofweek)) {
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
                                        get_lang('MyAgenda'),
                                        [],
                                        ICON_SIZE_SMALL
                                    );
                                    break;
                                case 'global':
                                    $bg_color = '#FFBC89';
                                    $icon = Display::return_icon(
                                        'view_remove.png',
                                        get_lang('GlobalEvent'),
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
    public static function get_personal_agenda_items_between_dates(
        $user_id,
        $date_start = '',
        $date_end = ''
    ) {
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
        $courses = api_get_user_courses($user_id, false);
        foreach ($courses as $id => $course) {
            $c = api_get_course_info_by_id($course['real_id']);
            //databases of the courses
            $t_a = Database::get_course_table(TABLE_AGENDA, $course['db']);
            $t_ip = Database::get_course_table(
                TABLE_ITEM_PROPERTY,
                $course['db']
            );
            // get the groups to which the user belong
            $group_memberships = GroupManager::get_group_ids(
                $course['db'],
                $user_id
            );
            // if the user is administrator of that course we show all the agenda items
            if ($course['status'] == '1') {
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
                list($year, $month, $day, $hour, $min, $sec) = explode(
                    '[-: ]',
                    $item['start_date']
                );
                $start_date = $year.$month.$day.$hour.$min;
                list($year, $month, $day, $hour, $min, $sec) = explode(
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

        return $items;
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
        if ($utcTime instanceof DateTime) {
            $eventDate = $utcTime;
        } else {
            $utcTimeZone = new DateTimeZone('UTC');
            $eventDate = new DateTime($utcTime, $utcTimeZone);
        }

        $platformTimeZone = new DateTimeZone(api_get_timezone());

        $eventDate->setTimezone($platformTimeZone);

        return $eventDate->format(DateTime::ISO8601);
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public static function saveCollectiveProperties(array $inviteeUserList, bool $isCollective, int $eventId)
    {
        if (empty($inviteeUserList)) {
            return;
        }

        $em = Database::getManager();

        $event = $em->find('ChamiloCoreBundle:PersonalAgenda', $eventId);

        $invitation = $event->getInvitation();

        if ($invitation instanceof AgendaEventSubscription) {
            return;
        }

        if (!$invitation) {
            $invitation = new AgendaEventInvitation();
            $invitation->setCreator(api_get_user_entity(api_get_user_id()));

            $event->setInvitation($invitation);
        }

        $event->setCollective($isCollective);

        foreach ($inviteeUserList as $inviteeId) {
            $userInvitee = api_get_user_entity($inviteeId);

            if (!$invitation->hasUserAsInvitee($userInvitee)) {
                $invitee = new AgendaEventInvitee();
                $invitee
                    ->setUser($userInvitee)
                    ->setInvitation($invitation)
                ;

                $em->persist($invitee);
            }
        }

        $inviteesToRemove = $invitation->getInvitees()
            ->filter(function (AgendaEventInvitee $invitee) use ($inviteeUserList): bool {
                $userInvitee = $invitee->getUser();

                return !in_array($userInvitee->getUserId(), $inviteeUserList);
            });

        foreach ($inviteesToRemove as $invitee) {
            $em->remove($invitee);
        }

        $em->flush();
    }

    public static function getJsForReminders(string $cssSelectorBtnAdd): string
    {
        return '
            var template = \'<div class="form-group">\' +
                \'<div class="col-sm-offset-2 col-sm-3">\' +
                \'<input min="0" step="1" id="notification_count[]" type="number" class=" form-control" name="notification_count[]">\' +
                \'</div>\' +
                \'<div class="col-sm-3">\' +
                \'<select class="form-control" name="notification_period[]" id="form_notification_period[]">\' +
                \'<option value="i">'.get_lang('Minutes').'</option>\' +
                \'<option value="h">'.get_lang('Hours').'</option>\' +
                \'<option value="d">'.get_lang('Days').'</option>\' +
                \'</select>\' +
                \'</div>\' +
                \'<div class="col-sm-2"><p class="form-control-static">'.get_lang('Before').'</p></div>\' +
                \'<div class="text-right col-sm-2">\' +
                \'<button class="btn btn-default delete-notification" type="button" aria-label="'.get_lang('Delete').'"><em class="fa fa-times"></em></button>\' +
                \'</div>\' +
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

    public static function returnGoogleCalendarUrl(int $userId): ?string
    {
        $extraFieldInfo = UserManager::get_extra_user_data_by_field($userId, 'google_calendar_url');

        if (empty($extraFieldInfo) || empty($extraFieldInfo['google_calendar_url'])) {
            return null;
        }

        return $extraFieldInfo['google_calendar_url'];
    }

    public static function returnFullCalendarExtraSettings(): ?string
    {
        $settings = api_get_configuration_value('fullcalendar_settings');

        if (empty($settings) || empty($settings['settings'])) {
            return null;
        }

        $encoded = json_encode($settings['settings']);

        return substr($encoded, 1, -1).',';
    }

    public static function returnOnHoverInfo()
    {
        $onHoverInfo = api_get_configuration_value('agenda_on_hover_info');

        if (!empty($onHoverInfo)) {
            return $onHoverInfo['options'];
        }

        return [
            'comment' => true,
            'description' => true,
        ];
    }

    private function addCollectiveInvitationsFields(FormValidator $form, ?PersonalAgenda $personalEvent)
    {
        $invitees = [];
        $isCollective = false;
        $withInvitation = false;

        if ($personalEvent) {
            $eventInvitation = $personalEvent->getInvitation();
            $withInvitation = !($eventInvitation instanceof AgendaEventSubscription);

            if ($withInvitation) {
                foreach ($eventInvitation->getInvitees() as $invitee) {
                    $inviteeUser = $invitee->getUser();

                    $invitees[$inviteeUser->getId()] = $inviteeUser->getCompleteNameWithUsername();
                }
            }

            $isCollective = $personalEvent->isCollective();
        }

        $form->addHtml(
            '<div id="invitations-block" style="display: '.($withInvitation ? 'block;' : 'none;').'">'
        );
        $form->addHeader(get_lang('Invitations'));
        $form->addSelectAjax(
            'invitees',
            get_lang('Invitees'),
            $invitees,
            [
                'multiple' => 'multiple',
                'url' => api_get_path(WEB_AJAX_PATH).'message.ajax.php?a=find_users',
            ]
        );
        $form->addCheckBox('collective', '', get_lang('IsItEditableByTheInvitees'));
        $form->addHtml('<hr>');
        $form->addHtml('</div>');

        $form->setDefaults([
            'invitees' => array_keys($invitees),
            'collective' => $isCollective,
        ]);
    }

    private function addSubscriptionFields(FormValidator $form, ?PersonalAgenda $personalEvent)
    {
        $subscribers = [];
        $withSubscription = false;
        $maxSubscriptions = 0;
        $groupId = null;

        if ($personalEvent) {
            $eventInvitation = $personalEvent->getInvitation();
            $withSubscription = $eventInvitation instanceof AgendaEventSubscription;
            $maxSubscriptions = $withSubscription ? $eventInvitation->getMaxAttendees() : 0;
            $groupId = $personalEvent->getSubscriptionItemId();

            $subscribers = self::getInviteesForPersonalEvent($personalEvent->getId(), AgendaEventSubscriber::class);
            $subscribers = array_combine(
                array_column($subscribers, 'id'),
                array_column($subscribers, 'name')
            );
        }

        $form->addHtml(
            '<div id="subscriptions-block" style="display: '.($withSubscription ? 'block;' : 'none;').'">'
        );
        $form->addHeader(get_lang('Subscriptions'));
        $form->addSelect(
            'subscription_visibility',
            get_lang('AllowSubscriptions'),
            [
                AgendaEventSubscription::SUBSCRIPTION_NO => get_lang('No'),
                AgendaEventSubscription::SUBSCRIPTION_ALL => get_lang('AllUsersOfThePlatform'),
                AgendaEventSubscription::SUBSCRIPTION_CLASS => get_lang('UsersInsideClass'),
            ]
        );
        $slctItem = $form->addSelectAjax(
            'subscription_item',
            get_lang('SocialGroup').' / '.get_lang('Class'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH).'usergroup.ajax.php?a=get_class_by_keyword',
                'disabled' => 'disabled',
            ]
        );

        $form->addNumeric(
            'max_subscriptions',
            ['', get_lang('MaxSubscriptionsLeaveEmptyToNotLimit')],
            [
                'disabled' => 'disabled',
                'step' => 1,
                'min' => 0,
                'value' => 0,
            ]
        );
        $form->addHtml("<script>
            $(function () {
                $('#add_event_subscription_visibility')
                    .on('change', function () {
                        $('#max_subscriptions').prop('disabled', this.value == 0);
                        $('#add_event_subscription_item').prop('disabled', this.value != 2);
                    })
                    .trigger('change');
            })
            </script>
        ");

        $form->addSelect(
            'subscribers',
            get_lang('Subscribers'),
            $subscribers,
            ['multiple' => 'multiple']
        );

        $form->setDefaults([
            'subscribers' => array_keys($subscribers),
            'max_subscriptions' => $maxSubscriptions,
        ]);

        if ($groupId) {
            $objUserGroup = new UserGroup();

            $groupInfo = $objUserGroup->get($groupId);

            $slctItem->addOption($groupInfo['name'], $groupId);
        }

        $form->addHtml('</div>');
    }

    private function editReminders(int $eventId, array $reminderList = [])
    {
        if (false === api_get_configuration_value('agenda_reminders')) {
            return;
        }

        $eventReminders = $this->parseEventReminders(
            $this->getEventReminders($eventId)
        );
        $eventIntervalList = array_column($eventReminders, 'date_interval');

        foreach ($eventIntervalList as $eventIntervalInfo) {
            if (!in_array($eventIntervalInfo, $reminderList)) {
                $this->removeReminders($eventId, $eventIntervalInfo[0], $eventIntervalInfo[1]);
            }
        }

        foreach ($reminderList as $reminderInfo) {
            if (!in_array($reminderInfo, $eventIntervalList)) {
                $this->addReminder($eventId, $reminderInfo[0], $reminderInfo[1]);
            }
        }
    }

    private static function isUserInvitedInEvent(int $id, int $userId): bool
    {
        $user = api_get_user_entity($userId);

        $event = Database::getManager()
            ->getRepository('ChamiloCoreBundle:PersonalAgenda')
            ->findOneByIdAndInvitee($id, $user)
        ;

        return null !== $event;
    }

    private function loadEventsAsInvitee(User $user, ?DateTime $startDate, ?DateTime $endDate)
    {
        $em = Database::getManager();
        $eventRepo = $em->getRepository('ChamiloCoreBundle:PersonalAgenda');
        $events = $eventRepo->getEventsForInvitee($user, $startDate, $endDate);

        foreach ($events as $event) {
            $eventInfo = [];
            $eventInfo['id'] = 'personal_'.$event->getId();
            $eventInfo['title'] = $event->getTitle();
            $eventInfo['className'] = 'personal';
            $eventInfo['borderColor'] = $eventInfo['backgroundColor'] = $this->event_personal_color;
            $eventInfo['editable'] = $event->isCollective();
            $eventInfo['sent_to'] = get_lang('Me');
            $eventInfo['type'] = 'personal';

            if ($event->getDate()) {
                $eventInfo['start'] = $this->formatEventDate($event->getDate()->format('Y-m-d H:i:s'));
                $eventInfo['start_date_localtime'] = api_get_local_time($event->getDate());
            }

            if ($event->getEnddate()) {
                $eventInfo['end'] = $this->formatEventDate($event->getEnddate()->format('Y-m-d H:i:s'));
                $eventInfo['end_date_localtime'] = api_get_local_time($event->getEnddate());
            }

            $eventInfo['description'] = $event->getText();
            $eventInfo['allDay'] = $event->getAllDay();
            $eventInfo['parent_event_id'] = 0;
            $eventInfo['has_children'] = 0;
            $eventInfo['collective'] = $event->isCollective();
            $eventInfo['invitees'] = [];

            $invitation = $event->getInvitation();

            if ($invitation) {
                foreach ($invitation->getInvitees() as $invitee) {
                    $inviteeUser = $invitee->getUser();

                    $eventInfo['invitees'][] = [
                        'id' => $inviteeUser->getId(),
                        'name' => $inviteeUser->getCompleteNameWithUsername(),
                    ];
                }
            }

            $this->events[] = $eventInfo;
        }
    }

    /**
     * @throws Exception
     */
    private function convertSessionWithDuration(int $userId, array $sessionInfo, DateTime $start, DateTime $end): array
    {
        $courseAccess = CourseManager::getFirstCourseAccessPerSessionAndUser(
            $sessionInfo['session_id'],
            $userId
        );

        if (empty($courseAccess)) {
            throw new Exception();
        }

        $firstAccessDate = new DateTime($courseAccess['login_course_date'], new DateTimeZone('UTC'));
        $lastAccessDate = clone $firstAccessDate;
        $lastAccessDate->modify('+'.$sessionInfo['duration'].' days');

        if ($firstAccessDate->format('Y-m-d H:i:s') > $start
            && $lastAccessDate->format('Y-m-d H:i:s') < $end
        ) {
            throw new Exception();
        }

        $courseList = SessionManager::get_course_list_by_session_id($sessionInfo['id']);
        $firstCourse = current($courseList);

        return [
            'id' => 'session_'.$sessionInfo['id'],
            'session_id' => $sessionInfo['id'],
            'title' => $sessionInfo['name'],
            'description' => $sessionInfo['show_description'] ? $sessionInfo['description'] : '',
            'className' => 'personal',
            'borderColor' => $this->event_personal_color,
            'backgroundColor' => $this->event_personal_color,
            'editable' => false,
            'sent_to' => get_lang('Me'),
            'type' => 'session',
            'start' => $firstAccessDate->format(DateTime::ISO8601),
            'start_date_localtime' => api_get_local_time($firstAccessDate),
            'end' => $lastAccessDate->format(DateTime::ISO8601),
            'end_date_localtime' => api_get_local_time($lastAccessDate),
            'allDay' => 0,
            'parent_event_id' => 0,
            'has_children' => 0,
            'course_url' => api_get_course_url($firstCourse['code'], $sessionInfo['id']),
        ];
    }

    /**
     * @throws Exception
     */
    private function convertSessionWithDates(array $sessionInfo, DateTime $start, DateTime $end): array
    {
        if ($sessionInfo['display_start_date'] < $start
            && $sessionInfo['display_end_date'] > $end
        ) {
            throw new Exception();
        }

        $courseList = SessionManager::get_course_list_by_session_id($sessionInfo['id']);
        $firstCourse = current($courseList);

        return [
            'id' => 'session_'.$sessionInfo['id'],
            'session_id' => $sessionInfo['id'],
            'title' => $sessionInfo['name'],
            'description' => $sessionInfo['show_description'] ? $sessionInfo['description'] : '',
            'className' => 'personal',
            'borderColor' => $this->event_personal_color,
            'backgroundColor' => $this->event_personal_color,
            'editable' => false,
            'sent_to' => get_lang('Me'),
            'type' => 'session_subscription',
            'start' => $sessionInfo['display_start_date'],
            'start_date_localtime' => $sessionInfo['display_start_date']
                ? $this->formatEventDate($sessionInfo['display_start_date'])
                : '',
            'end' => $sessionInfo['display_end_date'],
            'end_date_localtime' => $sessionInfo['display_end_date']
                ? $this->formatEventDate($sessionInfo['display_end_date'])
                : '',
            'allDay' => 0,
            'parent_event_id' => 0,
            'has_children' => 0,
            'course_url' => api_get_course_url($firstCourse['code'], $sessionInfo['id']),
        ];
    }

    private function loadSessionsAsEvents(int $start, int $end)
    {
        if (false === api_get_configuration_value('personal_calendar_show_sessions_occupation')) {
            return;
        }

        $start = api_get_utc_datetime($start, false, true);
        $end = api_get_utc_datetime($end, false, true);
        $userInfo = api_get_user_info();
        $sessionList = SessionManager::getSessionsFollowedByUser($userInfo['id'], $userInfo['status']);

        foreach ($sessionList as $sessionInfo) {
            if (!empty($sessionInfo['duration'])) {
                try {
                    $this->events[] = $this->convertSessionWithDuration($userInfo['id'], $sessionInfo, $start, $end);
                } catch (Exception $e) {
                    continue;
                }

                continue;
            }

            if (empty($sessionInfo['display_start_date']) || empty($sessionInfo['display_end_date'])) {
                continue;
            }

            try {
                $this->events[] = $this->convertSessionWithDates($sessionInfo, $start, $end);
            } catch (Exception $e) {
                continue;
            }
        }
    }
}
