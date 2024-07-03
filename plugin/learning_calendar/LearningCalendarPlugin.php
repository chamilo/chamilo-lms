<?php
/* For license terms, see /license.txt */

/**
 * Class LearningCalendarPlugin.
 */
class LearningCalendarPlugin extends Plugin
{
    public const EVENT_TYPE_TAKEN = 1;
    public const EVENT_TYPE_EXAM = 2;
    public const EVENT_TYPE_FREE = 3;

    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $version = '0.1';
        $author = 'Julio Montoya';
        parent::__construct($version, $author, ['enabled' => 'boolean']);
        $this->setHasPersonalEvents(true);
    }

    /**
     * Event definition.
     *
     * @return array
     */
    public function getEventTypeList()
    {
        return [
            self::EVENT_TYPE_TAKEN => ['color' => 'red', 'name' => self::get_lang('EventTypeTaken')],
            self::EVENT_TYPE_EXAM => ['color' => 'yellow', 'name' => self::get_lang('EventTypeExam')],
            self::EVENT_TYPE_FREE => ['color' => 'green', 'name' => self::get_lang('EventTypeFree')],
        ];
    }

    /**
     * @return array
     */
    public function getEventTypeColorList()
    {
        $list = $this->getEventTypeList();
        $newList = [];
        foreach ($list as $eventId => $event) {
            $newList[$eventId] = $event['color'];
        }

        return $newList;
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
        return 'learning_calendar';
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
              disabled int default 0,
              author_id int(11) not null
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

        $sql = "
            CREATE TABLE IF NOT EXISTS learning_calendar_control_point(
              id int not null AUTO_INCREMENT primary key,
              user_id int(11) not null,
              control_date date not null,
              control_value int not null,
              created_at datetime not null,
              updated_at datetime not null
            )
        ";
        Database::query($sql);

        $extraField = new ExtraField('lp_item');
        $params = [
            'display_text' => $this->get_lang('LearningCalendarOneDayMarker'),
            'variable' => 'calendar',
            'visible_to_self' => 1,
            'changeable' => 1,
            'visible_to_others' => 1,
            'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
        ];

        $extraField->save($params);

        $extraField = new ExtraField('course');
        $params = [
            'display_text' => $this->get_lang('CourseHoursDuration'),
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
     * @return array
     */
    public function getCalendars(
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

        if (api_is_platform_admin()) {
            $sql = 'SELECT * FROM learning_calendar';
        } else {
            $userId = api_get_user_id();
            $sql = "SELECT * FROM learning_calendar WHERE author_id = $userId";
        }

        $sql .= " LIMIT $from, $numberOfItems ";

        $result = Database::query($sql);
        $list = [];
        $link = api_get_path(WEB_PLUGIN_PATH).'learning_calendar/start.php';
        while ($row = Database::fetch_array($result)) {
            $id = $row['id'];
            $row['title'] = Display::url(
                $row['title'],
                api_get_path(WEB_PLUGIN_PATH).'learning_calendar/calendar.php?id='.$id
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
    public function getCalendarsEventsByDate($calendarInfo, $start, $end, $type = 0, $getCount = false)
    {
        if (empty($calendarInfo)) {
            if ($getCount) {
                return 0;
            }

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
                WHERE calendar_id = $calendarId $startCondition $endCondition $typeCondition";
        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result, 'ASSOC');

            return $row['count'];
        }

        $list = [];
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
    public function getFirstCalendarDate($calendarInfo)
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
    public function getCalendarCount()
    {
        if (api_is_platform_admin()) {
            $sql = 'select count(*) as count FROM learning_calendar';
        } else {
            $userId = api_get_user_id();
            $sql = "select count(*) as count FROM learning_calendar WHERE author_id = $userId";
        }
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return (int) $result['count'];
    }

    /**
     * @param int $calendarId
     *
     * @return array
     */
    public function getUsersPerCalendar($calendarId)
    {
        $calendarId = (int) $calendarId;
        $sql = "SELECT * FROM learning_calendar_user 
                WHERE calendar_id = $calendarId";
        $result = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $userInfo = api_get_user_info($row['user_id']);
            $userInfo['exam'] = 'exam';
            $list[] = $userInfo;
        }

        return $list;
    }

    /**
     * @param int $calendarId
     *
     * @return int
     */
    public function getUsersPerCalendarCount($calendarId)
    {
        $calendarId = (int) $calendarId;
        $sql = "SELECT count(id) as count FROM learning_calendar_user 
                WHERE calendar_id = $calendarId";
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');

        return (int) $row['count'];
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

        if (empty($values)) {
            return [];
        }

        return $values;
    }

    /**
     * @param int $calendarId
     *
     * @return array|mixed
     */
    public function getCalendar($calendarId)
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
    public function getUserCalendar($userId)
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
    public function getUserEvents($userId, $start, $end, $type = 0, $getCount = false)
    {
        $calendarRelUser = $this->getUserCalendar($userId);
        if (!empty($calendarRelUser)) {
            $calendar = $this->getCalendar($calendarRelUser['calendar_id']);

            return $this->getCalendarsEventsByDate($calendar, $start, $end, $type, $getCount);
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
    public function getUserCalendarToString($userId)
    {
        $calendar = $this->getUserCalendar($userId);
        if ($calendar) {
            $calendarInfo = $this->getCalendar($calendar['calendar_id']);

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
    public function addUserToCalendar($calendarId, $userId)
    {
        $calendar = $this->getUserCalendar($userId);
        if (empty($calendar)) {
            $params = [
                'calendar_id' => $calendarId,
                'user_id' => $userId,
            ];

            Database::insert('learning_calendar_user', $params);

            return true;
        }

        return false;
    }

    /**
     * @param int $calendarId
     * @param int $userId
     *
     * @return bool
     */
    public function updateUserToCalendar($calendarId, $userId)
    {
        $calendar = $this->getUserCalendar($userId);
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
    public function deleteAllCalendarFromUser($calendarId, $userId)
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

    public function getForm(FormValidator &$form)
    {
        $form->addText('title', get_lang('Title'));
        $form->addText('total_hours', get_lang('TotalHours'));
        $form->addText('minutes_per_day', get_lang('MinutesPerDay'));
        $form->addHtmlEditor('description', get_lang('Description'), false);
    }

    /**
     * @param Agenda $agenda
     * @param int    $start
     * @param int    $end
     *
     * @return array
     */
    public function getPersonalEvents($agenda, $start, $end)
    {
        $userId = api_get_user_id();
        $events = $this->getUserEvents($userId, $start, $end);

        if (empty($events)) {
            return [];
        }

        $calendarInfo = $events['calendar'];
        $events = $events['events'];

        $list = [];
        $typeList = $this->getEventTypeColorList();
        foreach ($events as $row) {
            $event = [];
            $event['id'] = 'personal_'.$row['id'];
            $event['title'] = $calendarInfo['title'];
            $event['className'] = 'personal';
            $color = isset($typeList[$row['type']]) ? $typeList[$row['type']] : $typeList[self::EVENT_TYPE_FREE];
            $event['borderColor'] = $color;
            $event['backgroundColor'] = $color;
            $event['editable'] = false;
            $event['sent_to'] = get_lang('Me');
            $event['type'] = 'personal';

            if (!empty($row['start_date'])) {
                $event['start'] = $agenda->formatEventDate($row['start_date']);
                $event['start_date_localtime'] = api_get_local_time($row['start_date']);
            }

            if (!empty($row['end_date'])) {
                $event['end'] = $agenda->formatEventDate($row['end_date']);
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
     * @param int   $userId
     * @param array $coursesAndSessions
     *
     * @return string
     */
    public function getGradebookEvaluationListToString($userId, $coursesAndSessions)
    {
        $list = $this->getGradebookEvaluationList($userId, $coursesAndSessions);

        $html = '';
        if (!empty($list)) {
            $html = implode('&nbsp;', array_column($list, 'name'));
        }

        return $html;
    }

    /**
     * @param int   $userId
     * @param array $coursesAndSessions
     *
     * @return array
     */
    public function getGradebookEvaluationList($userId, $coursesAndSessions)
    {
        $userId = (int) $userId;

        if (empty($coursesAndSessions)) {
            return 0;
        }

        $courseSessionConditionToString = '';
        foreach ($coursesAndSessions as $sessionId => $courseList) {
            if (isset($courseList['course_list'])) {
                $courseList = array_keys($courseList['course_list']);
            }
            if (empty($courseList)) {
                continue;
            }
            //$courseListToString = implode("','", $courseList);
            /*if (empty($sessionId)) {
                $courseAndSessionCondition[] =
                    " c.id IN ('$courseListToString') ";
            } else {
                $courseAndSessionCondition[] = "
                    (
                        c.id IN ('$courseListToString')
                    )";
            }*/
            $courseSessionConditionToString = " AND c.id IN ('".implode("','", $courseList)."') ";
        }

        if (empty($courseSessionConditionToString)) {
            return 0;
        }

        $tableEvaluation = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tableResult = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = "SELECT DISTINCT e.name, e.id
                FROM $tableEvaluation e 
                INNER JOIN $tableCourse c
                ON (course_code = c.code)
                INNER JOIN $tableResult r
                ON (r.evaluation_id = e.id)
                WHERE 
                  e.type = 'evaluation' AND
                  r.score >= 2 AND
                  r.user_id = $userId   
                  $courseSessionConditionToString                  
        ";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $list[$row['id']] = $row;
            }
        }

        return $list;
    }

    /**
     * @param int   $userId
     * @param array $coursesAndSessions
     *
     * @return int
     */
    public function getItemCountChecked($userId, $coursesAndSessions)
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
                WHERE                 
                    v.user_id = $userId AND 
                    status = 'completed' 
                    $courseSessionConditionToString
                GROUP BY iv.view_count
               ";

        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');

            return $row['count'];
        }

        return 0;
    }

    /**
     * @param array $htmlHeadXtra
     */
    public function setJavaScript(&$htmlHeadXtra)
    {
        $htmlHeadXtra[] = api_get_js('jqplot/jquery.jqplot.js');
        $htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.dateAxisRenderer.js');
        $htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.canvasOverlay.js');
        $htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.pointLabels.js');
        $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.css');
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
        $stats = $this->getUserStats($userId, $courseAndSessionList);
        $html = $this->get_lang('NumberDaysAccumulatedInCalendar').$stats['user_event_count'];
        if (!empty($courseAndSessionList)) {
            $html .= '<br />';
            $html .= $this->get_lang('NumberDaysAccumulatedInLp').$stats['completed'];
            $html .= '<br />';
            $html .= $this->get_lang('NumberDaysInRetard').' '.($stats['completed'] - $stats['user_event_count']);
        }

        $controlList = $this->getControlPointsToPlot($userId);

        if (!empty($controlList)) {
            $listToString = json_encode($controlList);
            $date = $this->get_lang('Date');
            $controlPoint = $this->get_lang('NumberOfDays');

            $html .= '<div id="control_point_chart"></div>';
            $html .= '<script>
                $(document).ready(function(){
                    var cosPoints = '.$listToString.';
                    var plot1 = $.jqplot(\'control_point_chart\', [cosPoints], {  
                        //animate: !$.jqplot.use_excanvas,                      
                        series:[{
                            showMarker:true,
                            pointLabels: { show:true },
                        }],
                        axes:{
                            xaxis:{
                                label: "'.$date.'",
                                renderer: $.jqplot.DateAxisRenderer,
                                tickOptions:{formatString: "%Y-%m-%d"},
                                tickInterval: \'30 day\',                                
                            },
                            yaxis:{
                                label: "'.$controlPoint.'",
                                max: 20,
                                min: -20,    
                            }
                        },
                        canvasOverlay: {
                            show: true,
                            objects: [{
                                horizontalLine: {
                                    name: \'0 mark\',
                                    y: 0,
                                    lineWidth: 2,
                                    color: \'rgb(f, f, f)\',
                                    shadow: false
                                }
                            }]
                        },                     
                  });
                });
            </script>';
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
    public function getUserStats($userId, $courseAndSessionList)
    {
        // Get events from this year to today
        $takenCount = $this->getUserEvents(
            $userId,
            strtotime(date('Y-01-01')),
            time(),
            self::EVENT_TYPE_TAKEN,
            true
        );

        $completed = 0;
        $diff = 0;
        if (!empty($courseAndSessionList)) {
            $completed = $this->getItemCountChecked($userId, $courseAndSessionList);
            $diff = $takenCount - $completed;
        }

        return [
            'user_event_count' => $takenCount,
            'completed' => $completed,
            'diff' => $diff,
        ];
    }

    /**
     * @param int $calendarId
     *
     * @return bool
     */
    public function copyCalendar($calendarId)
    {
        $item = $this->getCalendar($calendarId);
        $this->protectCalendar($item);
        $item['author_id'] = api_get_user_id();

        if (empty($item)) {
            return false;
        }

        $calendarId = (int) $calendarId;

        unset($item['id']);
        //$item['title'] = $item['title'];

        $newCalendarId = Database::insert('learning_calendar', $item);
        if (!empty($newCalendarId)) {
            $sql = "SELECT * FROM learning_calendar_events WHERE calendar_id = $calendarId";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                unset($row['id']);
                $row['calendar_id'] = $newCalendarId;
                Database::insert('learning_calendar_events', $row);
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $calendarId
     *
     * @return bool
     */
    public function deleteCalendar($calendarId)
    {
        $item = $this->getCalendar($calendarId);
        $this->protectCalendar($item);

        if (empty($item)) {
            return false;
        }

        $calendarId = (int) $calendarId;

        $sql = "DELETE FROM learning_calendar WHERE id = $calendarId";
        Database::query($sql);

        // Delete events
        $sql = "DELETE FROM learning_calendar_events WHERE calendar_id = $calendarId";
        Database::query($sql);

        return true;
    }

    /**
     * @param int    $calendarId
     * @param string $startDate
     */
    public function toogleDayType($calendarId, $startDate)
    {
        $startDate = Database::escape_string($startDate);
        $calendarId = (int) $calendarId;

        $eventTypeList = $this->getEventTypeColorList();
        // Remove the free type to loop correctly when toogle days.
        unset($eventTypeList[self::EVENT_TYPE_FREE]);

        $sql = "SELECT * FROM learning_calendar_events 
                WHERE start_date = '$startDate' AND calendar_id = $calendarId ";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            $currentType = $row['type'];
            $currentType++;
            if ($currentType > count($eventTypeList)) {
                Database::delete(
                    'learning_calendar_events',
                    [' calendar_id = ? AND start_date = ?' => [$calendarId, $startDate]]
                );
            } else {
                $params = [
                    'type' => $currentType,
                ];
                Database::update(
                    'learning_calendar_events',
                    $params,
                    [' calendar_id = ? AND start_date = ?' => [$calendarId, $startDate]]
                );
            }
        } else {
            $params = [
                'name' => '',
                'calendar_id' => $calendarId,
                'start_date' => $startDate,
                'end_date' => $startDate,
                'type' => self::EVENT_TYPE_TAKEN,
            ];
            Database::insert('learning_calendar_events', $params);
        }
    }

    /**
     * @param int $calendarId
     *
     * @return array
     */
    public function getEvents($calendarId)
    {
        $calendarId = (int) $calendarId;
        $eventTypeList = $this->getEventTypeColorList();

        $sql = "SELECT * FROM learning_calendar_events 
                WHERE calendar_id = $calendarId ";
        $result = Database::query($sql);

        $list = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $list[] = [
                'start_date' => $row['start_date'],
                'end_date' => $row['start_date'],
                'color' => $eventTypeList[$row['type']],
            ];
        }

        return $list;
    }

    public function protectCalendar(array $calendarInfo)
    {
        $allow = api_is_platform_admin() || api_is_teacher();

        if (!$allow) {
            api_not_allowed(true);
        }

        if (!empty($calendarInfo)) {
            if (!api_is_platform_admin() && api_is_teacher()) {
                if ($calendarInfo['author_id'] != api_get_user_id()) {
                    api_not_allowed(true);
                }
            }
        }
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getControlPoints($userId)
    {
        $userId = (int) $userId;
        $sql = "SELECT control_date, control_value 
                FROM learning_calendar_control_point 
                WHERE user_id = $userId 
                ORDER BY control_date";
        $result = Database::query($sql);
        $list = Database::store_result($result, 'ASSOC');

        return $list;
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getControlPointsToPlot($userId)
    {
        $list = $this->getControlPoints($userId);
        $points = [];
        foreach ($list as $item) {
            $points[] = [$item['control_date'], $item['control_value']];
        }

        return $points;
    }

    /**
     * @param int $userId
     * @param int $value
     */
    public function addControlPoint($userId, $value)
    {
        $userId = (int) $userId;
        $value = (int) $value;
        $local = api_get_local_time();
        $date = substr($local, 0, 10);

        $sql = "SELECT id 
                FROM learning_calendar_control_point 
                WHERE user_id = $userId AND control_date = '$date'";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            $params = [
                'control_value' => $value,
                'updated_at' => api_get_utc_datetime(),
            ];
            $data = Database::fetch_array($result);
            $id = $data['id'];
            Database::update('learning_calendar_control_point', $params, ['id = ?' => $id]);
        } else {
            $params = [
                'user_id' => $userId,
                'control_date' => $date,
                'control_value' => $value,
                'created_at' => api_get_utc_datetime(),
                'updated_at' => api_get_utc_datetime(),
            ];
            Database::insert('learning_calendar_control_point', $params);
        }
    }

    public function getAddUserToCalendarForm(FormValidator &$form)
    {
        $calendars = $this->getCalendars(0, 1000, '');

        if (empty($calendars)) {
            echo Display::return_message(get_lang('NoData'), 'warning');
            exit;
        }
        $calendars = array_column($calendars, 'title', 'id');
        $calendars = array_map('strip_tags', $calendars);

        $form->addSelect('calendar_id', get_lang('Calendar'), $calendars, ['disable_js' => true]);
    }
}
