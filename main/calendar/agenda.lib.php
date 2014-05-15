<?php
/* For licensing terms, see /license.txt */

/**
 * Class Agenda
 *
 * @author: Julio Montoya <gugli100@gmail.com>
 */

class Agenda
{
    public $events = array();
    /** @var string Current type */
    public $type = 'personal';
    public $types = array('personal', 'admin', 'course');
    public $sessionId = 0;
    /** @var array */
    public $course;

    /**
     *
     */
    public function __construct()
    {
        //Table definitions
        $this->tbl_global_agenda = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
        $this->tbl_personal_agenda = Database::get_user_personal_table(TABLE_PERSONAL_AGENDA);
        $this->tbl_course_agenda = Database::get_course_table(TABLE_AGENDA);
        $this->table_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT);

        //Setting the course object if we are in a course
        $this->course = null;
        $courseInfo = api_get_course_info();
        if (!empty($courseInfo)) {
            $this->course = $courseInfo;
        }
        $this->sessionId = api_get_session_id();
        $this->events = array();

        //Event colors
        $this->event_platform_color = 'red'; //red
        $this->event_course_color = '#458B00'; //green
        $this->event_group_color = '#A0522D'; //siena
        $this->event_session_color = '#00496D'; // kind of green
        $this->event_personal_color = 'steel blue'; //steel blue
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
     * Adds an event to the calendar
     * @param string  $start datetime format: 2012-06-14 09:00:00
     * @param string  $end datetime format: 2012-06-14 09:00:00
     * @param string  $allDay (true, false)
     * @param string  $title
     * @param string  $content
     * @param array   $usersToSend array('everyone') or a list of user/group ids
     * @param bool    $addAsAnnouncement event as a *course* announcement
     * @param int $parentEventId
     * @param array $attachmentArray $_FILES['']
     * @param string $attachmentComment
     *
     * @return int
     */
    public function add_event(
        $start,
        $end,
        $allDay,
        $title,
        $content,
        $usersToSend = array(),
        $addAsAnnouncement = false,
        $parentEventId = null,
        $attachmentArray = array(),
        $attachmentComment = null
    ) {
        $start = api_get_utc_datetime($start);
        $end = api_get_utc_datetime($end);
        $allDay = isset($allDay) && $allDay == 'true' ? 1 : 0;

        $id = null;
        switch ($this->type) {
            case 'personal':
                $attributes = array(
                    'user' => api_get_user_id(),
                    'title' => $title,
                    'text' => $content,
                    'date' => $start,
                    'enddate' => $end,
                    'all_day' => $allDay
                );
                $id = Database::insert($this->tbl_personal_agenda, $attributes);
                break;
            case 'course':
                $attributes = array(
                    'title' => $title,
                    'content' => $content,
                    'start_date' => $start,
                    'end_date' => $end,
                    'all_day' => $allDay,
                    'session_id' => api_get_session_id(),
                    'c_id' => $this->course['real_id']
                );

                if (!empty($parentEventId)) {
                    $attributes['parent_event_id'] = $parentEventId;
                }

                // Simple course event.
                $id = Database::insert($this->tbl_course_agenda, $attributes);

                if ($id) {
                    $groupId = api_get_group_id();

                    if (!empty($usersToSend)) {
                        $sendTo = $this->parseSendToArray($usersToSend);

                        if ($sendTo['everyone']) {
                            api_item_property_update(
                                $this->course,
                                TOOL_CALENDAR_EVENT,
                                $id,
                                "AgendaAdded",
                                api_get_user_id(),
                                $groupId,
                                '',
                                $start,
                                $end
                            );
                            api_item_property_update(
                                $this->course,
                                TOOL_CALENDAR_EVENT,
                                $id,
                                "visible",
                                api_get_user_id(),
                                $groupId,
                                '',
                                $start,
                                $end
                            );
                        } else {
                            // Storing the selected groups
                            if (!empty($sendTo['groups'])) {
                                foreach ($sendTo['groups'] as $group) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "AgendaAdded",
                                        api_get_user_id(),
                                        $group,
                                        0,
                                        $start,
                                        $end
                                    );

                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "visible",
                                        api_get_user_id(),
                                        $group,
                                        0,
                                        $start,
                                        $end
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
                                        "AgendaAdded",
                                        api_get_user_id(),
                                        $groupId,
                                        $userId,
                                        $start,
                                        $end
                                    );

                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "visible",
                                        api_get_user_id(),
                                        $groupId,
                                        $userId,
                                        $start,
                                        $end
                                    );
                                }
                            }
                        }
                    }

                    // Add announcement.
                    if ($addAsAnnouncement) {
                        $this->store_agenda_item_as_announcement($id, $usersToSend);
                    }

                    // Add attachment.
                    if (isset($attachmentArray) && !empty($attachmentArray)) {
                        $this->addAttachment(
                            $id,
                            $attachmentArray,
                            $attachmentComment,
                            $this->course
                        );
                    }
                }
                break;
            case 'admin':
                if (api_is_platform_admin()) {
                    $attributes = array(
                        'title' => $title,
                        'content' => $content,
                        'start_date' => $start,
                        'end_date' => $end,
                        'all_day' => $allDay,
                        'access_url_id' => api_get_current_access_url_id()
                    );

                    $id = Database::insert($this->tbl_global_agenda, $attributes);
                }
                break;
        }

        return $id;
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
        $eventId = intval($eventId);
        $courseId = intval($courseId);
        $sql = "SELECT * FROM $repeatTable
                WHERE c_id = $courseId AND cal_id = $eventId";
        $res = Database::query($sql);
        $repeatInfo = array();
        if (Database::num_rows($res) > 0) {
            $repeatInfo = Database::fetch_array($res, 'ASSOC');
        }

        return $repeatInfo;
    }

    /**
    * @param int $eventId
    * @param string $type
    * @param string $end in local time
    * @param array $sentTo
    *
    * @return bool
    */
    public function addRepeatedItem($eventId, $type, $end, $sentTo = array())
    {
        $t_agenda = Database::get_course_table(TABLE_AGENDA);
        $t_agenda_r = Database::get_course_table(TABLE_AGENDA_REPEAT);

        if (empty($this->course)) {
            return false;
        }

        $course_id = $this->course['real_id'];
        $eventId = intval($eventId);

        $sql = "SELECT title, content, start_date, end_date, all_day
                FROM $t_agenda
                WHERE c_id = $course_id AND id = $eventId";
        $res = Database::query($sql);

        if (Database::num_rows($res) !== 1) {
            return false;
        }

        $row = Database::fetch_array($res);
        $origStartDate = api_strtotime($row['start_date'], 'UTC');
        $origEndDate = api_strtotime($row['end_date'], 'UTC');
        $diff = $origEndDate - $origStartDate;

        $title = $row['title'];
        $content = $row['content'];
        $allDay = $row['all_day'];

        $now = time();
        $type = Database::escape_string($type);
        $end = api_strtotime($end);

        if (1 <= $end && $end <= 500) {
            // We assume that, with this type of value, the user actually gives a count of repetitions
            //and that he wants us to calculate the end date with that (particularly in case of imports from ical)
            switch ($type) {
                case 'daily':
                    $end = $origStartDate + (86400 * $end);
                    break;
                case 'weekly':
                    $end = $this->addWeek($origStartDate, $end);
                    break;
                case 'monthlyByDate':
                    $end = $this->addMonth($origStartDate, $end);
                    break;
                case 'monthlyByDay':
                    //TODO
                    break;
                case 'monthlyByDayR':
                    //TODO
                    break;
                case 'yearly':
                    $end = $this->addYear($origStartDate, $end);
                    break;
            }
        }

        $typeList = array('daily', 'weekly', 'monthlyByDate', 'monthlyByDay', 'monthlyByDayR', 'yearly');

        if ($end > $now && in_array($type, $typeList)) {
            $sql = "INSERT INTO $t_agenda_r (c_id, cal_id, cal_type, cal_end)
                    VALUES ($course_id, '$eventId', '$type', '$end')";
            Database::query($sql);

            switch ($type) {
                // @todo improve loop.
                case 'daily':
                    for ($i = $origStartDate + 86400; $i <= $end; $i += 86400) {
                        $start = date('Y-m-d H:i:s', $i);
                        $repeatEnd = date('Y-m-d H:i:s', $i + $diff);
                        $this->add_event(
                            $start,
                            $repeatEnd,
                            $allDay,
                            $title,
                            $content,
                            $sentTo,
                            false,
                            $eventId
                        );
                    }
                    break;
                case 'weekly':
                    for ($i = $origStartDate + 604800; $i <= $end; $i += 604800) {
                        $start = date('Y-m-d H:i:s', $i);
                        $repeatEnd = date('Y-m-d H:i:s', $i + $diff);
                        $this->add_event(
                            $start,
                            $repeatEnd,
                            $allDay,
                            $title,
                            $content,
                            $sentTo,
                            false,
                            $eventId
                        );
                    }
                    break;
                case 'monthlyByDate':
                    $next_start = $this->addMonth($origStartDate);
                    while ($next_start <= $end) {
                        $start = date('Y-m-d H:i:s', $next_start);
                        $repeatEnd = date('Y-m-d H:i:s', $next_start + $diff);
                        $this->add_event(
                            $start,
                            $repeatEnd,
                            $allDay,
                            $title,
                            $content,
                            $sentTo,
                            false,
                            $eventId
                        );
                        $next_start = $this->addMonth($next_start);
                    }
                    break;
                case 'monthlyByDay':
                    //not yet implemented
                    break;
                case 'monthlyByDayR':
                    //not yet implemented
                    break;
                case 'yearly':
                    $next_start = $this->addYear($origStartDate);
                    while ($next_start <= $end) {
                        $start = date('Y-m-d H:i:s', $next_start);
                        $repeatEnd = date('Y-m-d H:i:s', $next_start + $diff);
                        $this->add_event(
                            $start,
                            $repeatEnd,
                            $allDay,
                            $title,
                            $content,
                            $sentTo,
                            false,
                            $eventId
                        );
                        $next_start = $this->addYear($next_start);
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * @param int $item_id
     * @param array $sentTo
     * @return int
     */
    public function store_agenda_item_as_announcement($item_id, $sentTo = array())
    {
        $table_agenda = Database::get_course_table(TABLE_AGENDA);
        $course_id = api_get_course_int_id();

        // Check params
        if (empty($item_id) or $item_id != strval(intval($item_id))) {
            return -1;
        }

        // Get the agenda item.
        $item_id = Database::escape_string($item_id);
        $sql = "SELECT * FROM $table_agenda WHERE c_id = $course_id AND id = ".$item_id;
        $res = Database::query($sql);

        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res, 'ASSOC');
            // Sending announcement
            if (!empty($sentTo)) {
                $id = AnnouncementManager::add_announcement(
                    $row['title'],
                    $row['content'],
                    $sentTo,
                    null,
                    null,
                    $row['end_date']
                );
                AnnouncementManager::send_email($id);
            }
            return $id;
        }
        return -1;
    }

    /**
     * Edits an event
     *
     * @param int $id
     * @param string $start datetime format: 2012-06-14 09:00:00
     * @param string $end datetime format: 2012-06-14 09:00:00
     * @param int $allDay is all day 'true' or 'false'
     * @param string $title
     * @param string $content
     * @param array $usersToSend
     * @param int $editRepeatType
     * @param array $attachmentArray
     * @param string $attachmentComment
     *
     * @return bool
     */
    public function edit_event(
        $id,
        $start,
        $end,
        $allDay,
        $title,
        $content,
        $usersToSend = array(),
        $attachmentArray = array(),
        $attachmentComment = null
    ) {
        $start = api_get_utc_datetime($start);
        $end = api_get_utc_datetime($end);
        $allDay = isset($allDay) && $allDay == 'true' ? 1 : 0;

        switch ($this->type) {
            case 'personal':
                $eventInfo = $this->get_event($id);
                if ($eventInfo['user'] != api_get_user_id()) {
                    break;
                }
                $attributes = array(
                    'title' => $title,
                    'text' => $content,
                    'date' => $start,
                    'enddate' => $end,
                    'all_day' => $allDay
                );
                Database::update(
                    $this->tbl_personal_agenda,
                    $attributes,
                    array('id = ?' => $id)
                );
                break;
            case 'course':
                $eventInfo = $this->get_event($id);

                if (empty($eventInfo)) {
                    return false;
                }

                $groupId = api_get_group_id();
                $course_id = api_get_course_int_id();

                if (empty($course_id)) {
                    return false;
                }

                if (api_is_allowed_to_edit(null, true)) {

                    $attributes = array(
                        'title' => $title,
                        'content' => $content,
                        'start_date' => $start,
                        'end_date' => $end,
                        'all_day' => $allDay
                    );

                    Database::update(
                        $this->tbl_course_agenda,
                        $attributes,
                        array(
                            'id = ? AND c_id = ? AND session_id = ? ' => array($id, $course_id, api_get_session_id())
                        )
                    );

                    if (!empty($usersToSend)) {
                        $sendTo = $this->parseSendToArray($usersToSend);

                        $usersToDelete = array_diff($eventInfo['send_to']['users'], $sendTo['users']);
                        $usersToAdd = array_diff($sendTo['users'], $eventInfo['send_to']['users']);

                        $groupsToDelete = array_diff($eventInfo['send_to']['groups'], $sendTo['groups']);
                        $groupToAdd = array_diff($sendTo['groups'], $eventInfo['send_to']['groups']);

                        if ($sendTo['everyone']) {
                            // Delete all:
                            if (!empty($eventInfo['send_to']['groups']) &&
                                isset($eventInfo['send_to']['groups'])
                            ) {
                                foreach ($eventInfo['send_to']['groups'] as $group) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "delete",
                                        api_get_user_id(),
                                        $group,
                                        0,
                                        $start,
                                        $end,
                                        $this->sessionId
                                    );
                                }
                            }

                            // storing the selected users
                            if (!empty($eventInfo['send_to']['users']) &&
                                isset($eventInfo['send_to']['users'])
                            ) {
                                foreach ($eventInfo['send_to']['users'] as $userId) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "delete",
                                        api_get_user_id(),
                                        $groupId,
                                        $userId,
                                        $start,
                                        $end,
                                        $this->sessionId
                                    );
                                }
                            }

                            // Add to everyone only.
                            api_item_property_update(
                                $this->course,
                                TOOL_CALENDAR_EVENT,
                                $id,
                                "visible",
                                api_get_user_id(),
                                $groupId,
                                '',
                                $start,
                                $end,
                                $this->sessionId
                            );
                        } else {
                            // Groups
                            if (!empty($groupToAdd)) {
                                foreach ($groupToAdd as $group) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "visible",
                                        api_get_user_id(),
                                        $group,
                                        0,
                                        $start,
                                        $end,
                                        $this->sessionId
                                    );
                                }
                            }

                            if (!empty($groupsToDelete)) {
                                foreach ($groupsToDelete as $group) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "delete",
                                        api_get_user_id(),
                                        $group,
                                        0,
                                        $start,
                                        $end,
                                        $this->sessionId
                                    );
                                }
                            }

                            // Users.
                            if (!empty($usersToAdd)) {
                                foreach ($usersToAdd as $userId) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "visible",
                                        api_get_user_id(),
                                        $groupId,
                                        $userId,
                                        $start,
                                        $end,
                                        $this->sessionId
                                    );
                                }
                            }

                            if (!empty($usersToDelete)) {
                                foreach ($usersToDelete as $userId) {
                                    api_item_property_update(
                                        $this->course,
                                        TOOL_CALENDAR_EVENT,
                                        $id,
                                        "delete",
                                        api_get_user_id(),
                                        $groupId,
                                        $userId,
                                        $start,
                                        $end,
                                        $this->sessionId
                                    );
                                }
                            }
                        }
                    }

                    // Add announcement.
                    /*if (isset($addAsAnnouncement) && !empty($addAsAnnouncement)) {
                        $this->store_agenda_item_as_announcement($id);
                    }*/

                    // Add attachment.
                    if (isset($attachmentArray) && !empty($attachmentArray)) {
                        $this->updateAttachment(
                            $id,
                            $attachmentArray,
                            $attachmentComment,
                            $this->course
                        );
                    }
                }
                break;
            case 'admin':
            case 'platform':
                if (api_is_platform_admin()) {
                    $attributes = array(
                        'title' => $title,
                        'content' => $content,
                        'start_date' => $start,
                        'end_date' => $end,
                        'all_day' => $allDay
                    );
                    Database::update(
                        $this->tbl_global_agenda,
                        $attributes,
                        array('id = ?' => $id)
                    );
                }
                break;
        }
    }

    /**
     * @param int $id
     * @param bool $deleteAllItemsFromSerie
     */
    public function delete_event($id, $deleteAllItemsFromSerie = false)
    {
        switch ($this->type) {
            case 'personal':
                $eventInfo = $this->get_event($id);
                if ($eventInfo['user'] == api_get_user_id()) {
                    Database::delete(
                        $this->tbl_personal_agenda,
                        array('id = ?' => $id)
                    );
                }
                break;
            case 'course':
                $course_id = api_get_course_int_id();
                if (!empty($course_id) && api_is_allowed_to_edit(null, true)) {
                    // Delete
                    if ($deleteAllItemsFromSerie) {
                        $eventInfo = $this->get_event($id);
                        /* This is one of the children.
                           Getting siblings and delete 'Em all + the father! */
                        if (isset($eventInfo['parent_event_id']) && !empty($eventInfo['parent_event_id'])) {
                            // Removing items.
                            $events = $this->getAllRepeatEvents($eventInfo['parent_event_id']);
                            if (!empty($events)) {
                                foreach ($events as $event) {
                                    $this->delete_event($event['id']);
                                }
                            }
                            // Removing parent.
                            $this->delete_event($eventInfo['parent_event_id']);
                        } else {
                            // This is the father looking for the children.
                            $events = $this->getAllRepeatEvents($id);
                            if (!empty($events)) {
                                foreach ($events as $event) {
                                    $this->delete_event($event['id']);
                                }
                            }
                        }
                    }


                    // Removing from events.
                    Database::delete(
                        $this->tbl_course_agenda,
                        array('id = ? AND c_id = ?' => array($id, $course_id))
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
                        array('cal_id = ? AND c_id = ?' => array($id, $course_id))
                    );
                }
                break;
            case 'admin':
                if (api_is_platform_admin()) {
                    Database::delete(
                        $this->tbl_global_agenda,
                        array('id = ?' => $id)
                    );
                }
                break;
        }
    }

    /**
     * Get agenda events
     * @param int $start
     * @param int $end
     * @param int $course_id
     * @param int $groupId
     * @param int $user_id
     * @param string $format
     * @return array|string
     */
    public function get_events($start, $end, $course_id = null, $groupId = null, $user_id = 0, $format = 'json')
    {
        switch ($this->type) {
            case 'admin':
                $this->get_platform_events($start, $end);
                break;
            case 'course':
                $session_id = api_get_session_id();
                $courseInfo = api_get_course_info_by_id($course_id);
                $this->get_course_events(
                    $start,
                    $end,
                    $courseInfo,
                    $groupId,
                    $session_id,
                    $user_id
                );
                break;
            case 'personal':
            default:
                // Getting personal events
                $this->get_personal_events($start, $end);

                // Getting platform/admin events
                $this->get_platform_events($start, $end);

                // Getting course events
                $my_course_list = array();

                if (!api_is_anonymous()) {
                    $session_list = SessionManager::get_sessions_by_user(api_get_user_id());
                    $my_course_list = CourseManager::get_courses_list_by_user_id(api_get_user_id(), true);
                }

                if (!empty($session_list)) {
                    foreach ($session_list as $session_item) {
                        $my_courses = $session_item['courses'];
                        $my_session_id = $session_item['session_id'];
                        if (!empty($my_courses)) {
                            foreach ($my_courses as $course_item) {
                                $courseInfo = api_get_course_info($course_item['code']);
                                $this->get_course_events($start, $end, $courseInfo, 0, $my_session_id);
                            }
                        }
                    }
                }

                if (!empty($my_course_list)) {
                    foreach ($my_course_list as $course_info_item) {
                        if (isset($course_id) && !empty($course_id)) {
                            if ($course_info_item['real_id'] == $course_id) {
                                $this->get_course_events($start, $end, $course_info_item);
                            }
                        } else {
                            $this->get_course_events($start, $end, $course_info_item);
                        }
                    }
                }
                break;
        }

        if (!empty($this->events)) {
            switch ($format) {
                case 'json':
                    return json_encode($this->events);
                    break;
                case 'array':
                    return $this->events;
                    break;
            }

        }
        return '';
    }

    /**
     * @param int $id
     * @param int $day_delta
     * @param int $minute_delta
     * @return int
     */
    public function resize_event($id, $day_delta, $minute_delta)
    {
        // we convert the hour delta into minutes and add the minute delta
        $delta = ($day_delta * 60 * 24) + $minute_delta;
        $delta = intval($delta);

        $event = $this->get_event($id);
        if (!empty($event)) {
            switch ($this->type) {
                case 'personal':
                    $sql = "UPDATE $this->tbl_personal_agenda SET all_day = 0, enddate = DATE_ADD(enddate, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
                    Database::query($sql);
                    break;
                case 'course':
                    $sql = "UPDATE $this->tbl_course_agenda SET all_day = 0,  end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE c_id = ".$this->course['real_id']." AND id=".intval($id);
                    Database::query($sql);
                    break;
                case 'admin':
                    $sql = "UPDATE $this->tbl_global_agenda SET all_day = 0, end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
                    Database::query($sql);
                    break;
            }
        }
        return 1;
    }

    /**
     * @param $id
     * @param $day_delta
     * @param $minute_delta
     * @return int
     */
    public function move_event($id, $day_delta, $minute_delta)
    {
        // we convert the hour delta into minutes and add the minute delta
        $delta = ($day_delta * 60 * 24) + $minute_delta;
        $delta = intval($delta);

        $event = $this->get_event($id);

        $allDay = 0;
        if ($day_delta == 0 && $minute_delta == 0) {
            $allDay = 1;
        }

        if (!empty($event)) {
            switch ($this->type) {
                case 'personal':
                    $sql = "UPDATE $this->tbl_personal_agenda SET all_day = $allDay, date = DATE_ADD(date, INTERVAL $delta MINUTE), enddate = DATE_ADD(enddate, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
                    $result = Database::query($sql);
                    break;
                case 'course':
                    $sql = "UPDATE $this->tbl_course_agenda SET all_day = $allDay, start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE c_id = ".$this->course['real_id']." AND id=".intval($id);
                    $result = Database::query($sql);
                    break;
                case 'admin':
                    $sql = "UPDATE $this->tbl_global_agenda SET all_day = $allDay, start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
                    $result = Database::query($sql);
                    break;
            }
        }
        return 1;
    }

    /**
     * Gets a single event
     *
     * @param int event id
     * @return array
     */
    public function get_event($id)
    {
        // make sure events of the personal agenda can only be seen by the user himself
        $id = intval($id);
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

                        $event['attachment'] = $this->getAttachment($id, $this->course);
                    }
                }
                break;
            case 'admin':
            case 'platform':
                $sql = "SELECT * FROM ".$this->tbl_global_agenda."
                        WHERE id = ".$id;
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
     * Gets personal events
     * @param int $start
     * @param int $end
     * @return array
     */
    public function get_personal_events($start, $end)
    {
        $start = api_get_utc_datetime(intval($start));
        $end = api_get_utc_datetime(intval($end));
        $user_id = api_get_user_id();

        $sql = "SELECT * FROM ".$this->tbl_personal_agenda."
                WHERE date >= '".$start."' AND (enddate <='".$end."' OR enddate IS NULL) AND user = $user_id";

        $result = Database::query($sql);
        $my_events = array();
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $event = array();
                $event['id'] = 'personal_'.$row['id'];
                $event['title'] = $row['title'];
                $event['className'] = 'personal';
                $event['borderColor'] = $event['backgroundColor'] = $this->event_personal_color;
                $event['editable'] = true;
                $event['sent_to'] = get_lang('Me');
                $event['type'] = 'personal';

                if (!empty($row['date']) && $row['date'] != '0000-00-00 00:00:00') {
                    $event['start'] = $this->format_event_date($row['date']);
                    $event['start_date_localtime'] = api_get_local_time($row['date']);
                }

                if (!empty($row['enddate']) && $row['enddate'] != '0000-00-00 00:00:00') {
                    $event['end'] = $this->format_event_date($row['enddate']);
                    $event['end_date_localtime'] = api_get_local_time($row['enddate']);
                }
                $event['description'] = $row['text'];
                $event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;

                $event['parent_event_id'] = 0;
                $event['has_children'] = 0;

                $my_events[] = $event;
                $this->events[] = $event;
            }
        }
        return $my_events;
    }

    /**
     * Get user/group list per event.
     *
     * @param int $eventId
     * @param int $courseId
     * @paraù int $sessionId
     *
     * @return array
     */
    public function getUsersAndGroupSubscribedToEvent($eventId, $courseId, $sessionId)
    {
        $eventId = intval($eventId);
        $courseId = intval($courseId);
        $sessionId = intval($sessionId);

        $tlb_course_agenda = Database::get_course_table(TABLE_AGENDA);
        $tbl_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        // Get sent_tos
        $sql = "SELECT DISTINCT to_user_id, to_group_id
                FROM $tbl_property ip
                INNER JOIN $tlb_course_agenda agenda
                ON (ip.ref = agenda.id AND ip.c_id = agenda.c_id)
                WHERE
                    ip.tool         = '".TOOL_CALENDAR_EVENT."' AND
                    ref             = $eventId AND
                    ip.visibility   = '1' AND
                    ip.c_id         = $courseId AND
                    ip.id_session = $sessionId
                ";

        $result = Database::query($sql);
        $users = array();
        $groups = array();
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

        return array(
            'everyone' => $everyone,
            'users' => $users,
            'groups' => $groups
        );
    }

    /**
     * @param int $start
     * @param int $end
     * @param array $courseInfo
     * @param int $groupId
     * @param int $session_id
     * @param int $user_id
     * @return array
     */
    public function get_course_events($start, $end, $courseInfo, $groupId = 0, $session_id = 0, $user_id = 0)
    {
        $start = isset($start) && !empty($start) ? api_get_utc_datetime(intval($start)) : null;
        $end = isset($end) && !empty($end) ? api_get_utc_datetime(intval($end)) : null;

        if (empty($courseInfo)) {
            return array();
        }
        $course_id = $courseInfo['real_id'];
        if (empty($course_id)) {
            return array();
        }
        $user_id = intval($user_id);

        $groupList = GroupManager::get_group_list(null, $courseInfo['code']);

        $group_name_list = array();
        if (!empty($groupList)) {
            foreach ($groupList as $group) {
                $group_name_list[$group['id']] = $group['name'];
            }
        }

        if (!api_is_allowed_to_edit()) {
            $group_memberships = GroupManager::get_group_ids($course_id, api_get_user_id());
            $user_id = api_get_user_id();
        } else {
            $group_memberships = GroupManager::get_group_ids($course_id, $user_id);
        }

        $tlb_course_agenda = Database::get_course_table(TABLE_AGENDA);
        $tbl_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

        if (!empty($groupId)) {
            $group_memberships = array($groupId);
        }

        $session_id = intval($session_id);

        if (is_array($group_memberships) && count($group_memberships) > 0) {
            if (api_is_allowed_to_edit()) {
                if (!empty($groupId)) {
                    $where_condition = "( ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) ";
                } else {
                    if (!empty($user_id)) {
                        $where_condition = "( ip.to_user_id = $user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) ";
                    } else {
                        $where_condition = "( ip.to_group_id is null OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) ";
                    }
                }
            } else {
                $where_condition = "( ip.to_user_id = $user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) ";
            }

            $sql = "SELECT DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref, to_user_id
                    FROM $tlb_course_agenda agenda INNER JOIN $tbl_property ip
                    ON (agenda.id = ip.ref AND agenda.c_id = ip.c_id)
                    WHERE
                        ip.tool         ='".TOOL_CALENDAR_EVENT."' AND
                        $where_condition AND
                        ip.visibility   = '1' AND
                        agenda.c_id     = $course_id AND
                        ip.c_id         = $course_id
                    ";
        } else {
            $visibilityCondition = "ip.visibility='1' AND";
            if (api_is_allowed_to_edit()) {
                if ($user_id == 0) {
                    $where_condition = "";
                } else {
                    $where_condition = "( ip.to_user_id=".$user_id. ") AND ";
                }
                $visibilityCondition = " (ip.visibility IN ('1', '0')) AND ";
            } else {
                $where_condition = "( ip.to_user_id=$user_id OR ip.to_group_id='0') AND ";
            }
            $sql = "SELECT DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref, to_user_id
                    FROM $tlb_course_agenda agenda INNER JOIN $tbl_property ip
                    ON (agenda.id = ip.ref AND agenda.c_id = ip.c_id)
                    WHERE
                        ip.tool='".TOOL_CALENDAR_EVENT."' AND
                        $where_condition
                        $visibilityCondition
                        agenda.c_id = $course_id AND
                        ip.c_id = $course_id AND
                        agenda.session_id = $session_id AND
                        ip.id_session = $session_id
                    ";
        }

        $dateCondition = null;
        if (!empty($start)  && !empty($end)) {
            $dateCondition .= "AND (
                (agenda.start_date >= '".$start."' OR agenda.start_date IS NULL) AND
                (agenda.end_date <= '".$end."' OR agenda.end_date IS NULL)
            )";
        }

        $sql .= $dateCondition;

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $events_added = array();
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $eventId = $row['ref'];
                $items = $this->getUsersAndGroupSubscribedToEvent($eventId, $course_id, $this->sessionId);
                $group_to_array = $items['groups'];
                $user_to_array = $items['users'];
                $event = array();
                $event['id'] = 'course_'.$row['id'];

                // To avoid doubles
                if (in_array($row['id'], $events_added)) {
                    continue;
                }

                $events_added[] = $row['id'];
                $attachment = $this->getAttachment($row['id'], $courseInfo);

                if (!empty($attachment)) {
                    $has_attachment = Display::return_icon('attachment.gif', get_lang('Attachment'));
                    $user_filename = $attachment['filename'];
                    $url = api_get_path(WEB_CODE_PATH).'calendar/download.php?file='.$attachment['path'].'&course_id='.$course_id.'&'.api_get_cidreq();
                    $event['attachment'] = $has_attachment.Display::url($user_filename, $url);
                } else {
                    $event['attachment'] = '';
                }

                $event['title'] = $row['title'];
                $event['className'] = 'course';
                $event['allDay'] = 'false';
                $event['course_id'] = $course_id;

                $event['borderColor'] = $event['backgroundColor'] = $this->event_course_color;
                if (isset($row['session_id']) && !empty($row['session_id'])) {
                    $event['borderColor'] = $event['backgroundColor'] = $this->event_session_color;
                }

                if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
                    $event['borderColor'] = $event['backgroundColor'] = $this->event_group_color;
                }

                $event['editable'] = false;

                if (api_is_allowed_to_edit() && $this->type == 'course') {
                    $event['editable'] = true;
                }

                if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
                    $event['start'] = $this->format_event_date($row['start_date']);
                    $event['start_date_localtime'] = api_get_local_time($row['start_date']);
                }
                if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
                    $event['end'] = $this->format_event_date($row['end_date']);
                    $event['end_date_localtime'] = api_get_local_time($row['end_date']);
                }

                $event['sent_to'] = '';
                $event['type'] = 'course';
                if ($row['session_id'] != 0) {
                    $event['type'] = 'session';
                }

                // Event Sent to a group?
                if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
                    $sent_to = array();
                    if (!empty($group_to_array)) {
                        foreach ($group_to_array as $group_item) {
                            $sent_to[] = $group_name_list[$group_item];
                        }
                    }
                    $sent_to = implode('@@', $sent_to);
                    $sent_to = str_replace('@@', '</div><div class="label_tag notice">', $sent_to);
                    $event['sent_to'] = '<div class="label_tag notice">'.$sent_to.'</div>';
                    $event['type'] = 'group';
                }

                //Event sent to a user?
                if (isset($row['to_user_id'])) {
                    $sent_to = array();
                    if (!empty($user_to_array)) {
                        foreach ($user_to_array as $item) {
                            $user_info = api_get_user_info($item);
                            // Add username as tooltip for $event['sent_to'] - ref #4226
                            $username = api_htmlentities(sprintf(get_lang('LoginX'), $user_info['username']), ENT_QUOTES);
                            $sent_to[] = "<span title='".$username."'>".$user_info['complete_name']."</span>";
                        }
                    }
                    $sent_to = implode('@@', $sent_to);
                    $sent_to = str_replace('@@', '</div><div class="label_tag notice">', $sent_to);
                    $event['sent_to'] = '<div class="label_tag notice">'.$sent_to.'</div>';
                }

                //Event sent to everyone!
                if (empty($event['sent_to'])) {
                    $event['sent_to'] = '<div class="label_tag notice">'.get_lang('Everyone').'</div>';
                }

                $event['description'] = $row['content'];
                $event['visibility'] = $row['visibility'];
                $event['real_id'] = $row['id'];
                $event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;
                $event['parent_event_id'] = $row['parent_event_id'];
                $event['has_children'] = $this->hasChildren($row['id'], $course_id) ? 1 : 0;

                $this->events[] = $event;
            }
        }
        return $this->events;
    }

    /**
     * @param int $start tms
     * @param int $end tms
     * @return array
     */
    public function get_platform_events($start, $end)
    {
        $start = intval($start);
        $end = intval($end);

        $start = api_get_utc_datetime($start);
        $end = api_get_utc_datetime($end);

        $access_url_id = api_get_current_access_url_id();

        $sql = "SELECT * FROM ".$this->tbl_global_agenda."
               WHERE start_date >= '".$start."' AND end_date <= '".$end."' AND access_url_id = $access_url_id ";

        $result = Database::query($sql);
        $my_events = array();
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $event = array();
                $event['id'] = 'platform_'.$row['id'];
                $event['title'] = $row['title'];
                $event['className'] = 'platform';
                $event['allDay'] = 'false';
                $event['borderColor'] = $event['backgroundColor'] = $this->event_platform_color;
                $event['editable'] = false;
                $event['type'] = 'admin';

                if (api_is_platform_admin() && $this->type == 'admin') {
                    $event['editable'] = true;
                }

                if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
                    $event['start'] = $this->format_event_date($row['start_date']);
                    $event['start_date_localtime'] = api_get_local_time($row['start_date']);
                }
                if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
                    $event['end'] = $this->format_event_date($row['end_date']);
                    $event['end_date_localtime'] = api_get_local_time($row['end_date']);
                }

                $event['description'] = $row['content'];
                $event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;

                $event['parent_event_id'] = 0;
                $event['has_children'] = 0;

                $my_events[] = $event;
                $this->events[] = $event;
            }
        }
        return $my_events;
    }

    /**
     * Format needed for the Fullcalendar js lib
     *
     * @param string $utc_time
     * @return bool|string
     */
    function format_event_date($utc_time)
    {
        return date('c', api_strtotime(api_get_local_time($utc_time)));
    }

    /**
     * this function shows the form with the user that were not selected
     * @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @return string code
     */
    public static function construct_not_selected_select_form($group_list = null, $user_list = null, $to_already_selected = array())
    {
        $html = '<select id="users_to_send_id" data-placeholder="'.get_lang('Select').'" name="users_to_send[]" multiple="multiple" style="width:250px" class="chzn-select">';
        if ($to_already_selected == 'everyone') {
            $html .= '<option value="everyone" checked="checked">'.get_lang('Everyone').'</option>';
        } else {
            $html .= '<option value="everyone">'.get_lang('Everyone').'</option>';
        }

        if (is_array($group_list)) {
            $html .= '<optgroup label="'.get_lang('Groups').'">';
            foreach ($group_list as $this_group) {
                if (!is_array($to_already_selected) || !in_array("GROUP:".$this_group['id'], $to_already_selected)) {
                    // $to_already_selected is the array containing the groups (and users) that are already selected
                    $count_users = isset($this_group['count_users']) ? $this_group['count_users'] : $this_group['userNb'];
                    $count_users = " &ndash; $count_users ".get_lang('Users');

                    $html .= '<option value="GROUP:'.$this_group['id'].'"> '.$this_group['name'].$count_users.'</option>';
                }
            }
            $html .= '</optgroup>';
        }

        // adding the individual users to the select form
        if (is_array($group_list)) {
            $html .= '<optgroup label="'.get_lang('Users').'">';
        }
        foreach ($user_list as $this_user) {
            // $to_already_selected is the array containing the users (and groups) that are already selected
            if (!is_array($to_already_selected) || !in_array("USER:".$this_user['user_id'], $to_already_selected)) {
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $this_user['username']), ENT_QUOTES);
                // @todo : add title attribute $username in the jqdialog window. wait for a chosen version to inherit title attribute
                $html .= '<option title="'.$username.'" value="USER:'.$this_user['user_id'].'">'.api_get_person_name($this_user['firstname'], $this_user['lastname']).' ('.$this_user['username'].') </option>';
            }
        }
        if (is_array($group_list)) {
            $html .= '</optgroup>';
            $html .= "</select>";
        }
        return $html;
    }

    /**
     * @param FormValidator $form
     * @param array $groupList
     * @param array $userList
     * @param array $sendTo array('users' => [1, 2], 'groups' => [3, 4])
     * @param array $attributes
     * @param bool $addOnlyItemsInSendTo
     */
    public function setSendToSelect(
        $form,
        $groupList = null,
        $userList = null,
        $sendTo = array(),
        $attributes = array(),
        $addOnlyItemsInSendTo = false
    ) {
        $params = array(
            'id' => 'users_to_send_id',
            'data-placeholder' => get_lang('Select'),
            'multiple' => 'multiple',
            'style' => 'width:250px',
            'class' => 'chzn-select'
        );

        if (!empty($attributes)) {
            $params = array_merge($params, $attributes);
            if (empty($params['multiple'])) {
                unset($params['multiple']);
            }
        }

        $sendToGroups = isset($sendTo['groups']) ? $sendTo['groups'] : array();
        $sendToUsers = isset($sendTo['users']) ? $sendTo['users'] : array();

        /** @var HTML_QuickForm_select $select */
        $select = $form->addElement('select', 'users_to_send', get_lang('To'), null, $params);

        $selectedEveryoneOptions = array();
        if (isset($sendTo['everyone']) && $sendTo['everyone']) {
            $selectedEveryoneOptions = array('selected');
            $sendToUsers = array();
        }

        $select->addOption(get_lang('Everyone'), 'everyone', $selectedEveryoneOptions);

        $options = array();
        if (is_array($groupList)) {
            foreach ($groupList as $group) {
                $count_users = isset($group['count_users']) ? $group['count_users'] : $group['userNb'];
                $count_users = " &ndash; $count_users ".get_lang('Users');
                $option = array(
                    'text' => $group['name'].$count_users,
                    'value' => "GROUP:".$group['id']
                );
                $selected = in_array($group['id'], $sendToGroups) ? true : false;
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
            $options = array();
            foreach ($userList as $user) {
                $option = array(
                    'text' => api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')',
                    'value' => "USER:".$user['user_id']
                );

                $selected = in_array($user['user_id'], $sendToUsers) ? true : false;

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
     * use the 'everyone' key
     * @author Julio Montoya based in separate_users_groups in agenda.inc.php
     * @param array $to
     * @return array
     */
    public function parseSendToArray($to)
    {
        $groupList = array();
        $userList = array();
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
     * @param array $params
     * @return FormValidator
     */
    public function getForm($params = array())
    {
        $action = isset($params['action']) ? Security::remove_XSS($params['action']) : null;
        $id = isset($params['id']) ? intval($params['id']) : null;
        if ($this->type == 'course') {
            $url = api_get_self().'?'.api_get_cidreq().'&action='.$action.'&id='.$id.'&type='.$this->type;
        } else {
            $url = api_get_self().'?action='.$action.'&id='.$id.'&type='.$this->type;
        }

        $form = new FormValidator(
            'add_event',
            'post',
            $url,
            null,
            array('enctype' => 'multipart/form-data')
        );

        $idAttach = isset($params['id_attach']) ? intval($params['id_attach']) : null;
        $groupId = api_get_group_id();

        if ($id) {
            $form_title = get_lang('ModifyCalendarItem');
            $button = get_lang('ModifyEvent');
        } else {
            $form_title = get_lang('AddCalendarItem');
            $button = get_lang('AgendaAdd');
        }

        $form->addElement('header', $form_title);
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
                Display::return_message(get_lang('EditingThisEventWillRemoveItFromTheSerie'), 'warning')
            );
        }

        $form->addElement('text', 'title', get_lang('ItemTitle'));

        if (isset($groupId) && !empty($groupId)) {
            $form->addElement('hidden', 'selected_form[0]', "GROUP:'.$groupId.'");
            $form->addElement('hidden', 'to', 'true');
        } else {
            $sendTo = isset($params['send_to']) ? $params['send_to'] : null;
            if ($this->type == 'course') {
                $this->showToForm($form, $sendTo);
            }
        }

        $form->addDateRangePicker('date_range', get_lang('StartDate'), false, array('id' => 'date_range'));
        $form->addElement('checkbox', 'all_day', null, get_lang('AllDay'));

        if ($this->type == 'course') {
            $repeat = $form->addElement('checkbox', 'repeat', null, get_lang('RepeatEvent'), array('onclick' => 'return plus_repeated_event();'));
            $form->addElement('html', '<div id="options2" style="display:none">');
            $form->addElement('select', 'repeat_type', get_lang('RepeatType'), self::getRepeatTypes());
            $form->addElement('date_picker', 'repeat_end_day', get_lang('RepeatEnd'), array('id' => 'repeat_end_date_form'));

            if ($isSubEventEdition || $isParentFromSerie) {
                if ($isSubEventEdition) {
                    $parentEvent = $params['parent_info'];
                    $repeatInfo = $parentEvent['repeat_info'];
                } else {
                    $repeatInfo = $params['repeat_info'];
                }
                $params['repeat'] = 1;
                $params['repeat_type'] = $repeatInfo['cal_type'];
                $params['repeat_end_day'] = substr(api_get_local_time($repeatInfo['cal_end']), 0, 10);

                $form->freeze(array('repeat_type', 'repeat_end_day'));
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

        if (!api_is_allowed_to_edit(null, true)) {
            $toolbar = 'AgendaStudent';
        } else {
            $toolbar = 'Agenda';
        }

        $form->addElement(
            'html_editor',
            'content',
            get_lang('Description'),
            null,
            array('ToolbarSet' => $toolbar, 'Width' => '100%', 'Height' => '200')
        );

        if ($this->type == 'course') {

            $form->addElement('file', 'user_upload', get_lang('AddAnAttachment'));
            if ($showAttachmentForm) {
                if (isset($params['attachment']) && !empty($params['attachment'])) {
                    $attachment = $params['attachment'];
                    $params['file_comment'] = $attachment['comment'];
                    if (!empty($attachment['path'])) {
                        $form->addElement(
                            'checkbox',
                            'delete_attachment',
                            null,
                            get_lang('DeleteAttachment').' '.$attachment['filename']
                        );
                    }
                }
            }

            $form->addElement('textarea', 'file_comment', get_lang('Comment'));
        }

        if (empty($id)) {
            $form->addElement(
                'checkbox',
                'add_announcement',
                null,
                get_lang('AddAnnouncement').'&nbsp('.get_lang('SendMail').')'
            );
        }

        $form->addElement('button', 'submit', $button);
        $form->setDefaults($params);

        $form->addRule('date_range', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * @param FormValidator $form
     * @param array $sendTo array('everyone' => false, 'users' => [1, 2], 'groups' => [3, 4])
     * @param array $attributes
     * @param bool $addOnlyItemsInSendTo
     * @return bool
     */
    public function showToForm(
        $form,
        $sendTo = array(),
        $attributes = array(),
        $addOnlyItemsInSendTo = false
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
            api_get_session_id(),
            null,
            $order
        );
        $groupList = CourseManager::get_group_list_of_course(
            api_get_course_id(),
            api_get_session_id()
        );

        $this->setSendToSelect(
            $form,
            $groupList,
            $userList,
            $sendTo,
            $attributes,
            $addOnlyItemsInSendTo
        );
        return true;

    }

    /**
     * @param int $id
     * @param int $visibility 0= invisible, 1 visible
     * @param array $courseInfo
     * @param int $userId
     */
    public static function changeVisibility($id, $visibility, $courseInfo, $userId = null)
    {
        $id = Database::escape_string($id);
        if (empty($userId)) {
            $userId = api_get_user_id();
        } else {
            $userId = intval($userId);
        }

        if ($visibility == 0) {
            api_item_property_update($courseInfo, TOOL_CALENDAR_EVENT, $id, "invisible", $userId);
        } else {
            api_item_property_update($courseInfo, TOOL_CALENDAR_EVENT, $id, "visible", $userId);
        }
    }

    /**
     * Get repeat types
     * @return array
     */
    public static function getRepeatTypes()
    {
        return array(
            'daily' => get_lang('RepeatDaily'),
            'weekly'  => get_lang('RepeatWeekly'),
            'monthlyByDate'  => get_lang('RepeatMonthlyByDate'),
            //monthlyByDay"> get_lang('RepeatMonthlyByDay');
            //monthlyByDayR' => get_lang('RepeatMonthlyByDayR'),
            'yearly' => get_lang('RepeatYearly')
        );
    }

    /**
     * Show a list with all the attachments according to the post's id
     * @param int $eventId
     * @param array $courseInfo
     * @return array with the post info
     */
    public function getAttachment($eventId, $courseInfo)
    {
        $tableAttachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
        $courseId = intval($courseInfo['real_id']);
        $eventId = Database::escape_string($eventId);
        $row = array();
        $sql = "SELECT id, path, filename, comment
                FROM $tableAttachment
                WHERE
                    c_id = $courseId AND
                    agenda_id = $eventId";
        $result = Database::query($sql);
        if (Database::num_rows($result) != 0) {
            $row = Database::fetch_array($result, 'ASSOC');
        }

        return $row;
    }

    /**
     * Add an attachment file into agenda
     * @param int $eventId
     * @param array $fileUserUpload ($_FILES['user_upload'])
     * @param string comment about file
     * @param array $courseInfo
     * @return string
     */
    public function addAttachment($eventId, $fileUserUpload, $comment, $courseInfo)
    {
        $agenda_table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
        $eventId = intval($eventId);

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
                $result = @move_uploaded_file($fileUserUpload['tmp_name'], $new_path);
                $comment = Database::escape_string($comment);
                $file_name = Database::escape_string($file_name);
                $course_id = api_get_course_int_id();
                $size = intval($fileUserUpload['size']);
                // Storing the attachments if any
                if ($result) {
                    $sql = 'INSERT INTO '.$agenda_table_attachment.'(c_id, filename, comment, path, agenda_id, size) '.
                            "VALUES ($course_id, '".$file_name."', '".$comment."', '".$new_file_name."' , '".$eventId."', '".$size."' )";
                    Database::query($sql);
                    $id = Database::insert_id();
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

    /**
     * @param int $eventId
     * @param array $fileUserUpload
     * @param string $comment
     * @param array $courseInfo
     */
    public function updateAttachment($eventId, $fileUserUpload, $comment, $courseInfo)
    {
        $attachment = $this->getAttachment($eventId, $courseInfo);
        if (!empty($attachment)) {
            $this->deleteAttachmentFile($attachment['id'], $courseInfo);
        }
        $this->addAttachment($eventId, $fileUserUpload, $comment, $courseInfo);
    }

    /**
     * This function delete a attachment file by id
     * @param int $attachmentId
     * @param array $courseInfo
     * @return string
     */
    public function deleteAttachmentFile($attachmentId, $courseInfo)
    {
        $agenda_table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
        $attachmentId = intval($attachmentId);
        $courseId = $courseInfo['real_id'];

        if (empty($courseId) || empty($attachmentId)) {
            return false;
        }

        $sql = "DELETE FROM $agenda_table_attachment
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
            return Display::return_message(get_lang("AttachmentFileDeleteSuccess"), 'confirmation');
        }
    }

    /**
     * Adds x weeks to a UNIX timestamp
     * @param   int     The timestamp
     * @param   int     The number of weeks to add
     * @return  int     The new timestamp
     */
    function addWeek($timestamp, $num = 1)
    {
        return $timestamp + $num * 604800;
    }

    /**
     * Adds x months to a UNIX timestamp
     * @param   int     The timestamp
     * @param   int     The number of years to add
     * @return  int     The new timestamp
     */
    function addMonth($timestamp, $num = 1)
    {
        list($y, $m, $d, $h, $n, $s) = split('/', date('Y/m/d/h/i/s', $timestamp));
        if ($m + $num > 12) {
            $y += floor($num / 12);
            $m += $num % 12;
        } else {
            $m += $num;
        }
        return mktime($h, $n, $s, $m, $d, $y);
    }

    /**
     * Adds x years to a UNIX timestamp
     * @param   int     The timestamp
     * @param   int     The number of years to add
     * @return  int     The new timestamp
     */
    function addYear($timestamp, $num = 1)
    {
        list($y, $m, $d, $h, $n, $s) = split('/', date('Y/m/d/h/i/s', $timestamp));
        return mktime($h, $n, $s, $m, $d, $y + $num);
    }

    /**
     * @param int $eventId
     * @return array
     */
    public function getAllRepeatEvents($eventId)
    {
        $events = array();
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
        $eventId = intval($eventId);
        $courseId = intval($courseId);

        $sql = "SELECT count(DISTINCT(id)) as count
                FROM ".$this->tbl_course_agenda."
                WHERE
                    c_id = ".$courseId." AND
                    parent_event_id = ".$eventId;
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            return $row['count'] > 0;
        }
        return false;
    }

    /**
     * @param int $filter
     * @param string $view
     * @return string
     */
    public function displayActions($view, $filter = 0)
    {
        $actions = "<a href='".api_get_path(WEB_CODE_PATH)."calendar/agenda_js.php?type={$this->type}'>".
            Display::return_icon('calendar.png', get_lang('Calendar'), '', ICON_SIZE_MEDIUM)."</a>";

        $actions .= "<a href='".api_get_path(WEB_CODE_PATH)."calendar/agenda_list.php?type={$this->type}&".api_get_cidreq()."'>".
            Display::return_icon('week.png', get_lang('AgendaList'), '', ICON_SIZE_MEDIUM)."</a>";

        if (api_is_allowed_to_edit(false, true) OR
            (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()) && api_is_allowed_to_session_edit(false, true) OR
            GroupManager::user_has_access(api_get_user_id(), api_get_group_id(), GroupManager::GROUP_TOOL_CALENDAR) &&
            GroupManager::is_tutor_of_group(api_get_user_id(), api_get_group_id())
        ) {
            if ($this->type == 'course') {
                $form = null;
                if (!isset($_GET['action'])) {
                    $form = new FormValidator('form-search');
                    $attributes = array(
                        'multiple' => false,
                        'id' => 'select_form_id_search'
                    );
                    $selectedValues = $this->parseAgendaFilter($filter);
                    $this->showToForm($form, $selectedValues, $attributes);
                    $form = $form->return_form();

                }
                $actions .= "<a href='".api_get_path(WEB_CODE_PATH)."calendar/agenda.php?".api_get_cidreq()."&action=add&type=course'>".
                    Display::return_icon('new_event.png', get_lang('AgendaAdd'), '', ICON_SIZE_MEDIUM)."</a>";
                $actions .= "<a href='".api_get_path(WEB_CODE_PATH)."calendar/agenda.php?".api_get_cidreq()."&action=importical&type=course'>".
                    Display::return_icon('import_calendar.png', get_lang('ICalFileImport'), '', ICON_SIZE_MEDIUM)."</a>";
                if ($view == 'calendar') {
                    $actions .= $form;
                }
            }
        }
        return $actions;
    }

    /**
     * @return FormValidator
     */
    public function getImportCalendarForm()
    {
        $form = new FormValidator(
            'frm_import_ical',
            'post',
            api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&type='.$this->type,
            array('enctype' => 'multipart/form-data')
        );
        $form->addElement('header', get_lang('ICalFileImport'));
        $form->addElement('file', 'ical_import', get_lang('ICalFileImport'));
        $form->addRule('ical_import', get_lang('ThisFieldIsRequired'), 'required');
        $form->addElement('button', 'ical_submit', get_lang('Import'));

        return $form;
    }

    /**
     * @param array $courseInfo
     * @param $file
     * @return array|bool|string
     */
    public function importEventFile($courseInfo, $file)
    {
        $charset = api_get_system_encoding();
        $filepath = api_get_path(SYS_ARCHIVE_PATH).$file['name'];
        $messages = array();

        if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log('Problem moving uploaded file: '.$file['error'].' in '.__FILE__.' line '.__LINE__);
            return false;
        }

        $data = file_get_contents($filepath);

        require_once api_get_path(SYS_PATH).'vendor/autoload.php';

        $trans = array(
            'DAILY' => 'daily',
            'WEEKLY' => 'weekly',
            'MONTHLY' => 'monthlyByDate',
            'YEARLY' => 'yearly'
        );
        $sentTo = array('everyone' => true);
        $calendar = Sabre\VObject\Reader::read($data);
        $currentTimeZone = _api_get_timezone();
        if (!empty($calendar->VEVENT)) {
            foreach ($calendar->VEVENT as $event) {
                $start = $event->DTSTART->getDateTime();
                $end = $event->DTEND->getDateTime();
                //Sabre\VObject\DateTimeParser::parseDateTime(string $dt, \Sabre\VObject\DateTimeZone $tz)

                $startDateTime = api_get_local_time($start->format('Y-m-d H:i:s'), $currentTimeZone, $start->format('e'));
                $endDateTime = api_get_local_time($end->format('Y-m-d H:i'), $currentTimeZone, $end->format('e'));
                $title = api_convert_encoding((string)$event->summary, $charset, 'UTF-8');
                $description = api_convert_encoding((string)$event->description, $charset, 'UTF-8');

                $id = $this->add_event(
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
                        $until = Sabre\VObject\DateTimeParser::parseDateTime($repeat['UNTIL'], new DateTimeZone($currentTimeZone));
                        $until = $until->format('Y-m-d H:i');
                        //$res = agenda_add_repeat_item($courseInfo, $id, $freq, $until, $attendee);
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
        }

        return $messages;
    }

    /**
     * Parse filter turns USER:12 to ['users' => [12])] or G:1 ['groups' => [1]]
     * @param $filter
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

        return array(
            'everyone' => $everyone,
            'users' => array($userId),
            'groups' => array($groupId)
        );
    }
}
