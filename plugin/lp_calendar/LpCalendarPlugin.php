<?php
/* For license terms, see /license.txt */

/**
 * Class LpCalendarPlugin.
 */
class LpCalendarPlugin extends Plugin
{
    const EVENT_TYPE_TAKEN = 1;
    const EVENT_TYPE_EXAM = 2;

    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $this->hasPersonalEvents = true;
        $version = '0.1';
        $author = 'Julio Montoya';
        parent::__construct($version, $author, ['enabled' => 'boolean']);
    }

    /**
     * @return array
     */
    public static function getEventTypeList()
    {
        return [
            //self::EVENT_TYPE_FREE => 'green',
            self::EVENT_TYPE_TAKEN => 'red',
            self::EVENT_TYPE_EXAM => 'yellow',
        ];
    }

    /**
     * Get the class instance.
     *
     * @return $this
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Get the plugin directory name.
     */
    public function get_name()
    {
        return 'lp_calendar';
    }

    /**
     * Install the plugin. Setup the database.
     */
    public function install()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS learning_calendar(
              id int not null AUTO_INCREMENT primary key,
              title varchar(255) not null default '',
              description longtext default null,
              total_hours int not null default 0,
              minutes_per_day int not null default 0,
              disabled int default 0
            )
        ";
        Database::query($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS learning_calendar_events(
              id int not null AUTO_INCREMENT primary key,
              name varchar(255) default '',
              calendar_id int not null,
              start_date date not null,
              end_date date not null,
              type int not null
            )
        ";
        Database::query($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS learning_calendar_user(
              id int not null AUTO_INCREMENT primary key,
              user_id int(11) not null,
              calendar_id int not null
            )
        ";
        Database::query($sql);

        $extraField = new ExtraField('lp_item');
        $params = [
            'variable' => 'calendar',
            'visible_to_self' => 1,
            'changeable' => 1,
            'visible_to_others' => 1,
            'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
        ];

        $extraField->save($params);

        $extraField = new ExtraField('course');
        $params = [
            'variable' => 'course_hours_duration',
            'visible_to_self' => 1,
            'changeable' => 1,
            'visible_to_others' => 1,
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
        ];

        $extraField->save($params);

        return true;
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
        $tables = [
            'learning_calendar',
            'learning_calendar_events',
            'learning_calendar_user',
        ];

        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }

        $extraField = new ExtraField('lp_item');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('calendar');

        if ($fieldInfo) {
            $extraField->delete($fieldInfo['id']);
        }

        $extraField = new ExtraField('course');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('course_hours_duration');
        if ($fieldInfo) {
            $extraField->delete($fieldInfo['id']);
        }

        return true;
    }

    /**
     * @param int    $from
     * @param int    $numberOfItems
     * @param int    $column
     * @param string $direction
     *
     * @return array|\Doctrine\DBAL\Driver\Statement
     */
    public static function getCalendars(
        $from,
        $numberOfItems,
        $column,
        $direction = 'DESC'
    ) {
        $column = (int) $column;
        $from = (int) $from;
        $numberOfItems = (int) $numberOfItems;
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'DESC';
        }

        $sql = 'select * FROM learning_calendar';

        $sql .= " LIMIT $from, $numberOfItems ";

        $result = Database::query($sql);
        $list = [];
        $link = api_get_path(WEB_PLUGIN_PATH).'lp_calendar/start.php';
        while ($row = Database::fetch_array($result)) {
            $id = $row['id'];
            $row['title'] = Display::url(
                $row['title'],
                api_get_path(WEB_PLUGIN_PATH).'lp_calendar/calendar.php?id='.$id
            );
            $actions = Display::url(
                Display::return_icon('edit.png', get_lang('Edit')),
                $link.'?action=edit&id='.$id
            );

            $actions .= Display::url(
                Display::return_icon('copy.png', get_lang('Copy')),
                $link.'?action=copy&id='.$id
            );

            $actions .= Display::url(
                Display::return_icon('delete.png', get_lang('Delete')),
                $link.'?action=delete&id='.$id
            );
            $row['actions'] = $actions;
            $list[] = $row;
        }

        return $list;
    }

    /**
     * @param array $calendarInfo
     * @param int   $start
     * @param int   $end
     * @param int   $type
     * @param bool  $getCount
     *
     * @return array
     */
    public static function getCalendarsEventsByDate($calendarInfo, $start, $end, $type = 0, $getCount = false)
    {
        if (empty($calendarInfo)) {
            return [];
        }

        $calendarId = (int) $calendarInfo['id'];
        $start = (int) $start;
        $end = (int) $end;

        $startCondition = '';
        $endCondition = '';
        $typeCondition = '';

        if ($start !== 0) {
            $start = api_get_utc_datetime($start);
            $startCondition = "AND start_date >= '".$start."'";
        }
        if ($end !== 0) {
            $end = api_get_utc_datetime($end);
            $endCondition = "AND (end_date <= '".$end."' OR end_date IS NULL)";
        }

        if (!empty($type)) {
            $type = (int) $type;
            $typeCondition = " AND type = $type ";
        }

        $select = '*';
        if ($getCount) {
            $select = 'count(id) count ';
        }

        $sql = "SELECT $select FROM learning_calendar_events 
                WHERE calendar_id = $calendarId $startCondition $endCondition ";
        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result, 'ASSOC');

            return $row['count'];
        }

        $list = [];
        //$link = api_get_path(WEB_PLUGIN_PATH).'lp_calendar/start.php';
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $list[] = $row;
        }

        return ['calendar' => $calendarInfo, 'events' => $list];
    }

    /**
     * @param array $calendarInfo
     *
     * @return array
     */
    public static function getFirstCalendarDate($calendarInfo)
    {
        if (empty($calendarInfo)) {
            return [];
        }

        $calendarId = (int) $calendarInfo['id'];

        /*if (!empty($type)) {
            $type = (int) $type;
            $typeCondition = " AND type = $type ";
        }*/

        $sql = "SELECT start_date FROM learning_calendar_events 
                WHERE calendar_id = $calendarId ORDER BY start_date LIMIT 1";
        $result = Database::query($sql);

        $row = Database::fetch_array($result, 'ASSOC');

        return $row['start_date'];
    }

    /**
     * @return int
     */
    public static function getCalendarCount()
    {
        $sql = 'select count(*) as count FROM learning_calendar';
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return (int) $result['count'];
    }

    /**
     * @param int $id
     */
    public function toggleVisibility($id)
    {
        $extraField = new ExtraField('lp_item');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('calendar');
        if ($fieldInfo) {
            $itemInfo = $this->getItemVisibility($id);
            if (empty($itemInfo)) {
                $extraField = new ExtraFieldValue('lp_item');
                $value = 1;
                $params = [
                    'field_id' => $fieldInfo['id'],
                    'value' => $value,
                    'item_id' => $id,
                ];
                $extraField->save($params);
            } else {
                $newValue = (int) $itemInfo['value'] === 1 ? 0 : 1;
                $extraField = new ExtraFieldValue('lp_item');
                $params = [
                    'id' => $itemInfo['id'],
                    'value' => $newValue,
                ];
                $extraField->update($params);
            }
        }
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getItemVisibility($id)
    {
        $extraField = new ExtraFieldValue('lp_item');
        $values = $extraField->get_values_by_handler_and_field_variable($id, 'calendar');

        return $values;
    }

    /**
     * @param int $calendarId
     *
     * @return array|mixed
     */
    public static function getCalendar($calendarId)
    {
        $calendarId = (int) $calendarId;
        $sql = "SELECT * FROM learning_calendar WHERE id = $calendarId";
        $result = Database::query($sql);
        $item = Database::fetch_array($result, 'ASSOC');

        return $item;
    }

    /**
     * @param int $userId
     *
     * @return array|mixed
     */
    public static function getUserCalendar($userId)
    {
        $userId = (int) $userId;
        $sql = "SELECT * FROM learning_calendar_user WHERE user_id = $userId";
        $result = Database::query($sql);
        $item = Database::fetch_array($result, 'ASSOC');

        return $item;
    }

    /**
     * @param int  $userId
     * @param int  $start
     * @param int  $end
     * @param int  $type
     * @param bool $getCount
     *
     * @return array|int
     */
    public static function getUserEvents($userId, $start, $end, $type = 0, $getCount = false)
    {
        $calendarRelUser = self::getUserCalendar($userId);
        if (!empty($calendarRelUser)) {
            $calendar = self::getCalendar($calendarRelUser['calendar_id']);

            return self::getCalendarsEventsByDate($calendar, $start, $end, $type, $getCount);
        }

        if ($getCount) {
            return 0;
        }

        return [];
    }

    /**
     * @param int $userId
     *
     * @return mixed|string
     */
    public static function getUserCalendarToString($userId)
    {
        $calendar = self::getUserCalendar($userId);
        if ($calendar) {
            $calendarInfo = self::getCalendar($calendar['calendar_id']);

            return $calendarInfo['title'];
        }

        return '';
    }

    /**
     * @param int $calendarId
     * @param int $userId
     *
     * @return bool
     */
    public static function addUserToCalendar($calendarId, $userId)
    {
        $calendar = self::getUserCalendar($userId);
        if (empty($calendar)) {
            $params = [
                'calendar_id' => $calendarId,
                'user_id' => $userId,
            ];

            Database::insert('learning_calendar_user', $params);
        }

        return true;
    }

    /**
     * @param int $calendarId
     * @param int $userId
     *
     * @return bool
     */
    public static function updateUserToCalendar($calendarId, $userId)
    {
        $calendar = self::getUserCalendar($userId);
        if (!empty($calendar)) {
            $params = [
                'calendar_id' => $calendarId,
                'user_id' => $userId,
            ];

            Database::update('learning_calendar_user', $params, ['id = ?' => $calendar['id']]);
        }

        return true;
    }

    /**
     * @param int $calendarId
     * @param int $userId
     *
     * @return bool
     */
    public static function deleteAllCalendarFromUser($calendarId, $userId)
    {
        $calendarId = (int) $calendarId;
        $userId = (int) $userId;
        $sql = "DELETE FROM learning_calendar_user 
                WHERE user_id = $userId AND calendar_id = $calendarId";
        Database::query($sql);

        return true;
    }

    /*public static function getUserCalendar($calendarId, $userId)
    {
        $params = [
            'calendar_id' => $calendarId,
            'user_id' => $calendarId,
        ];

        Database::insert('learning_calendar_user', $params);

        return true;
    }*/

    /**
     * @param FormValidator $form
     */
    public function getForm(FormValidator &$form)
    {
        $form->addText('title', get_lang('Title'));
        $form->addText('total_hours', get_lang('TotalHours'));
        $form->addText('minutes_per_day', get_lang('MinutesPerDay'));
        $form->addHtmlEditor('description', get_lang('Description'), false);
    }

    /**
     * @param int $start
     * @param int $end
     *
     * @return array
     */
    public function getPersonalEvents($calendar, $start, $end)
    {
        $userId = api_get_user_id();
        $events = self::getUserEvents($userId, $start, $end);

        if (empty($events)) {
            return [];
        }

        $calendarInfo = $events['calendar'];
        $events = $events['events'];

        $list = [];
        $typeList = self::getEventTypeList();
        foreach ($events as $row) {
            $event['id'] = 'personal_'.$row['id'];
            $event['title'] = $calendarInfo['title'];
            $event['className'] = 'personal';
            $color = isset($typeList[$row['type']]) ? $typeList[$row['type']] : 'green';
            $event['borderColor'] = $color;
            $event['backgroundColor'] = $color;

            $event['editable'] = false;
            $event['sent_to'] = get_lang('Me');
            $event['type'] = 'personal';

            if (!empty($row['start_date'])) {
                $event['start'] = $calendar->formatEventDate($row['start_date']);
                $event['start_date_localtime'] = api_get_local_time($row['start_date']);
            }

            if (!empty($row['end_date'])) {
                $event['end'] = $calendar->formatEventDate($row['end_date']);
                $event['end_date_localtime'] = api_get_local_time($row['end_date']);
            }

            $event['description'] = 'plugin';
            $event['allDay'] = 1;
            $event['parent_event_id'] = 0;
            $event['has_children'] = 0;
            $list[] = $event;
        }

        return $list;
    }

    /**
     * @param array $coursesAndSessions
     *
     * @return int
     */
    public static function getItemCountChecked($userId, $coursesAndSessions)
    {
        $userId = (int) $userId;

        if (empty($coursesAndSessions)) {
            return 0;
        }

        $tableItem = Database::get_course_table(TABLE_LP_ITEM);
        $tableLp = Database::get_course_table(TABLE_LP_MAIN);
        $tableLpItemView = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $tableLpView = Database::get_course_table(TABLE_LP_VIEW);
        $extraField = new ExtraField('lp_item');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('calendar');

        if (empty($fieldInfo)) {
            return 0;
        }

        $courseAndSessionCondition = [];
        foreach ($coursesAndSessions as $sessionId => $courseList) {
            if (isset($courseList['course_list'])) {
                $courseList = array_keys($courseList['course_list']);
            }
            if (empty($courseList)) {
                continue;
            }
            $courseListToString = implode("','", $courseList);
            if (empty($sessionId)) {
                $courseAndSessionCondition[] =
                    " ((l.session_id = 0 OR l.session_id is NULL) AND i.c_id IN ('$courseListToString'))";
            } else {
                $courseAndSessionCondition[] = " 
                    (
                        ((l.session_id = 0 OR l.session_id is NULL) OR l.session_id = $sessionId) AND 
                        i.c_id IN ('$courseListToString')
                    )";
            }
        }

        if (empty($courseAndSessionCondition)) {
            return 0;
        }

        $courseSessionConditionToString = 'AND ('.implode(' OR ', $courseAndSessionCondition).') ';

        $sql = "SELECT count(*) as count 
                FROM $tableItem i INNER JOIN $tableLp l
                ON (i.c_id = l.c_id AND i.lp_id = l.iid) 
                INNER JOIN $tableLpItemView iv
                ON (iv.c_id = l.c_id AND i.iid = iv.lp_item_id) 
                INNER JOIN $tableLpView v
                ON (v.c_id = l.c_id AND v.lp_id = l.iid AND iv.lp_view_id = v.iid)
                INNER JOIN extra_field_values e 
                ON (e.item_id = i.iid AND value = 1 AND field_id = ".$fieldInfo['id'].")
                WHERE v.user_id = $userId AND status = 'completed' $courseSessionConditionToString";

        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');

            return $row['count'];
        }

        return 0;
    }

    /**
     * @param int   $userId
     * @param array $courseAndSessionList
     *
     * @return string
     */
    public function getUserStatsPanel($userId, $courseAndSessionList)
    {
        // @todo use translation
        // get events from this year to today
        $stats = self::getUserStats($userId, $courseAndSessionList);
        $html = $this->get_lang('NumberDaysAccumulatedInCalendar').$stats['user_event_count'];
        if (!empty($courseAndSessionList)) {
            $html .= '<br />';
            $html .= $this->get_lang('NumberDaysAccumulatedInLp').$stats['completed'];
            $html .= '<br />';
            $html .= $this->get_lang('NumberDaysInRetard').' '.$stats['diff'];
        }

        $html = Display::panel($html, $this->get_lang('LearningCalendar'));

        return $html;
    }

    /**
     * @param int   $userId
     * @param array $courseAndSessionList
     *
     * @return array
     */
    public static function getUserStats($userId, $courseAndSessionList)
    {
        // Get events from this year to today
        $takenCount = self::getUserEvents(
            $userId,
            strtotime(date('Y-01-01')),
            time(),
            self::EVENT_TYPE_TAKEN,
            true
        );

        $completed = 0;
        $diff = 0;

        if (!empty($courseAndSessionList)) {
            $completed = self::getItemCountChecked($userId, $courseAndSessionList);
            if ($takenCount > $completed) {
                $diff = $takenCount - $completed;
            }
        }

        return [
            'user_event_count' => $takenCount,
            'completed' => $completed,
            'diff' => $diff,
        ];
    }
}
