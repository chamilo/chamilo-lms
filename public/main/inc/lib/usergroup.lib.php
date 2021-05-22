<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Usergroup as UserGroupEntity;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Class UserGroup.
 *
 * This class provides methods for the UserGroup management.
 * Include/require it in your code to use its features.
 */
class UserGroup extends Model
{
    public const SOCIAL_CLASS = 1;
    public const NORMAL_CLASS = 0;
    public $columns = [
        'id',
        'name',
        'description',
        'group_type',
        'picture',
        'url',
        'allow_members_leave_group',
        'visibility',
        'updated_at',
        'created_at',
    ];

    public $useMultipleUrl = false;
    public $groupType = 0;
    public $showGroupTypeSetting = false;
    public $usergroup_rel_user_table;
    public $usergroup_rel_course_table;
    public $usergroup;
    public $usergroup_rel_session_table;
    public $session_table;
    public $access_url_rel_usergroup;
    public $session_rel_course_table;
    public $access_url_rel_user;
    public $table_course;
    public $table_user;

    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_USERGROUP);
        $this->usergroup_rel_user_table = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $this->usergroup_rel_course_table = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
        $this->usergroup_rel_session_table = Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
        $this->session_table = Database::get_main_table(TABLE_MAIN_SESSION);
        $this->usergroup_table = Database::get_main_table(TABLE_USERGROUP);
        $this->access_url_rel_usergroup = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);
        $this->session_rel_course_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $this->access_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $this->table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $this->table_user = Database::get_main_table(TABLE_MAIN_USER);
        $this->useMultipleUrl = api_get_configuration_value('multiple_access_urls');
        if ($this->allowTeachers()) {
            $this->columns[] = 'author_id';
        }
    }

    /**
     * @return bool
     */
    public function getUseMultipleUrl()
    {
        return $this->useMultipleUrl;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $options = [];
        $from = $this->table;

        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $options = [
                'where' => [
                    'access_url_id = ?' => [
                        $urlId,
                    ],
                ],
            ];
            $from = " $this->table u
                      INNER JOIN $this->access_url_rel_usergroup a
                      ON (u.id = a.usergroup_id) ";
        }
        $row = Database::select('count(*) as count', $from, $options, 'first');

        return $row['count'];
    }

    /**
     * @param int  $id       user group id
     * @param bool $getCount
     *
     * @return array|int
     */
    public function getUserGroupUsers($id, $getCount = false, $start = 0, $limit = 0)
    {
        $id = (int) $id;
        $start = (int) $start;
        $limit = (int) $limit;

        $select = ' u.* ';
        if ($getCount) {
            $select = 'COUNT(u.id) count ';
        }

        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT $select
                    FROM $this->usergroup_rel_user_table u
                    INNER JOIN $this->access_url_rel_user a
                    ON (u.user_id = a.user_id)
                    WHERE u.usergroup_id = $id AND access_url_id = $urlId ";
        } else {
            $sql = "SELECT $select
                    FROM $this->usergroup_rel_user_table u
                    WHERE u.usergroup_id = $id";
        }
        $limitCondition = '';
        if (!empty($start) && !empty($limit)) {
            $limitCondition = " LIMIT $start, $limit";
        }

        $sql .= $limitCondition;

        $result = Database::query($sql);

        if ($getCount) {
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return $row['count'];
            }

            return 0;
        } else {
            $list = [];
            $showCalendar = 'true' === api_get_plugin_setting('learning_calendar', 'enabled');
            $calendarPlugin = null;
            if ($showCalendar) {
                $calendarPlugin = LearningCalendarPlugin::create();
            }
            $url = api_get_path(WEB_PLUGIN_PATH).'learning_calendar/calendar.php?';
            while ($data = Database::fetch_array($result)) {
                $userId = $data['user_id'];
                $userInfo = api_get_user_info($userId);
                $data['name'] = $userInfo['complete_name_with_username'];

                if ($showCalendar) {
                    $calendar = $calendarPlugin->getUserCalendar($userId);
                    $data['calendar_id'] = 0;
                    $data['calendar'] = '';
                    if (!empty($calendar)) {
                        $calendarInfo = $calendarPlugin->getCalendar($calendar['calendar_id']);
                        if ($calendarInfo) {
                            $data['calendar_id'] = $calendar['calendar_id'];
                            $data['calendar'] = Display::url(
                                $calendarInfo['title'],
                                $url.'&id='.$calendar['calendar_id']
                            );
                        }
                    }

                    $courseAndSessionList = Tracking::show_user_progress(
                        $userId,
                        0,
                        '',
                        true,
                        true,
                        true
                    );

                    $stats = $calendarPlugin->getUserStats($userId, $courseAndSessionList);
                    $evaluations = $calendarPlugin->getGradebookEvaluationListToString($userId, $courseAndSessionList);
                    $data['gradebook_items'] = $evaluations;
                    $totalTime = 0;
                    foreach ($courseAndSessionList as $sessionId => $course) {
                        foreach ($course as $courseId) {
                            $totalTime += Tracking::get_time_spent_on_the_course($userId, $courseId, $sessionId);
                        }
                    }

                    $data['time_spent'] = api_time_to_hms($totalTime);
                    $data['lp_day_completed'] = $stats['completed'];
                    $data['days_diff'] = $stats['completed'] - $stats['user_event_count'];
                }
                $data['id'] = $data['user_id'];
                $list[] = $data;
            }

            return $list;
        }
    }

    /**
     * @param string $extraWhereCondition
     *
     * @return int
     */
    public function get_count($extraWhereCondition = '')
    {
        $authorCondition = '';

        if ($this->allowTeachers()) {
            if (!api_is_platform_admin()) {
                $userId = api_get_user_id();
                $authorCondition = " AND author_id = $userId";
            }
        }

        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT count(u.id) as count
                    FROM $this->table u
                    INNER JOIN $this->access_url_rel_usergroup a
                    ON (u.id = a.usergroup_id)
                    WHERE access_url_id = $urlId $authorCondition
                    AND $extraWhereCondition
            ";

            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return $row['count'];
            }
        } else {
            $sql = "SELECT count(a.id) as count
                    FROM {$this->table} a
                    WHERE 1 = 1
                    $authorCondition
                    AND $extraWhereCondition
            ";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return $row['count'];
            }
        }

        return 0;
    }

    /**
     * @param int $course_id
     * @param int $type
     *
     * @return mixed
     */
    public function getUserGroupByCourseWithDataCount($course_id, $type = -1)
    {
        if ($this->getUseMultipleUrl()) {
            $course_id = (int) $course_id;
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT count(c.usergroup_id) as count
                    FROM {$this->usergroup_rel_course_table} c
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (c.usergroup_id = a.usergroup_id)
                    WHERE access_url_id = $urlId AND course_id = $course_id
            ";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return $row['count'];
            }

            return 0;
        } else {
            $typeCondition = '';
            if (-1 != $type) {
                $type = (int) $type;
                $typeCondition = " AND group_type = $type ";
            }
            $sql = "SELECT count(c.usergroup_id) as count
                    FROM {$this->usergroup_rel_course_table} c
                    INNER JOIN {$this->table} a
                    ON (c.usergroup_id = a.id)
                    WHERE
                        course_id = $course_id
                        $typeCondition
            ";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return $row['count'];
            }

            return 0;
        }
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function getIdByName($name)
    {
        $row = Database::select(
            'id',
            $this->table,
            ['where' => ['name = ?' => $name]],
            'first'
        );

        if ($row) {
            return (int) $row['id'];
        }

        return 0;
    }

    /**
     * Displays the title + grid.
     */
    public function returnGrid()
    {
        $html = '';
        $actions = '';
        if (api_is_platform_admin()) {
            $actions .= '<a href="../admin/index.php">'.
                Display::return_icon(
                    'back.png',
                    get_lang('Back to').' '.get_lang('Administration'),
                    '',
                    ICON_SIZE_MEDIUM
                ).
                '</a>';
        }

        $actions .= '<a href="'.api_get_self().'?action=add">'.
            Display::return_icon('new_class.png', get_lang('Add classes'), '', ICON_SIZE_MEDIUM).
            '</a>';
        $actions .= Display::url(
            Display::return_icon('import_csv.png', get_lang('Import'), [], ICON_SIZE_MEDIUM),
            'usergroup_import.php'
        );
        $actions .= Display::url(
            Display::return_icon('export_csv.png', get_lang('Export'), [], ICON_SIZE_MEDIUM),
            'usergroup_export.php'
        );
        $html .= Display::toolbarAction('toolbar', [$actions]);
        $html .= Display::grid_html('usergroups');

        return $html;
    }

    /**
     * Displays the title + grid.
     */
    public function displayToolBarUserGroupUsers()
    {
        // action links
        echo '<div class="actions">';
        $courseInfo = api_get_course_info();
        if (empty($courseInfo)) {
            echo '<a href="../admin/usergroups.php">'.
                Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Administration'), '', '32').
                '</a>';
        } else {
            echo Display::url(
                Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Administration'), '', '32'),
                api_get_path(WEB_CODE_PATH).'user/class.php?'.api_get_cidreq()
            );
        }

        echo '</div>';
        echo Display::grid_html('usergroups');
    }

    /**
     * Get HTML grid.
     */
    public function display_teacher_view()
    {
        echo Display::grid_html('usergroups');
    }

    /**
     * Gets a list of course ids by user group.
     *
     * @param int  $id             user group id
     * @param bool $loadCourseData
     *
     * @return array
     */
    public function get_courses_by_usergroup($id, $loadCourseData = false)
    {
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $from = $this->usergroup_rel_course_table." c
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = c.usergroup_id) ";
            $whereConditionSql = 'a.usergroup_id = ? AND access_url_id = ? ';
            $whereConditionValues = [$id, $urlId];
        } else {
            $whereConditionSql = 'usergroup_id = ?';
            $whereConditionValues = [$id];
            $from = $this->usergroup_rel_course_table.' c ';
        }

        if ($loadCourseData) {
            $from .= " INNER JOIN {$this->table_course} as course ON c.course_id = course.id";
        }

        $where = ['where' => [$whereConditionSql => $whereConditionValues]];

        $select = 'course_id';
        if ($loadCourseData) {
            $select = 'course.*';
        }

        $results = Database::select(
            $select,
            $from,
            $where
        );

        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                if ($loadCourseData) {
                    $array[$row['id']] = $row;
                } else {
                    $array[] = $row['course_id'];
                }
            }
        }

        return $array;
    }

    /**
     * Gets all users that are part of a group or class.
     *
     * Example to obtain the number of registered:
     * <code>
     * <?php
     *
     * $options['where'] = [' usergroup.course_id = ? ' => $course_id];
     * $obj = new UserGroup();
     * $count = $obj->getUserGroupInCourse(
     * $options,
     * -1,
     * true,
     * true
     * );
     * echo "<pre>".var_export($count,true)."</pre>";
     * ?>
     * </code>
     *
     *
     * Example to obtain the list of classes or groups registered:
     * <code>
     * <?php
     *
     * $options['where'] = [' usergroup.course_id = ? ' => $course_id];
     * $obj = new UserGroup();
     * $students = $obj->getUserGroupInCourse(
     * $options,
     * -1,
     * false,
     * true
     * );
     * echo "<pre>".var_export($students,true)."</pre>";
     * ?>
     * </code>
     *
     * @param array $options
     * @param int   $type        0 = classes / 1 = social groups
     * @param bool  $withClasses Return with classes.
     *
     * @return array
     */
    public function getUserGroupInCourse(
        $options = [],
        $type = -1,
        $getCount = false,
        $withClasses = false
    ) {
        $data = [];
        $sqlClasses = '';
        $whereClasess = '';
        $resultClasess = null;
        $counts = 0;
        $select = 'DISTINCT u.*';
        if ($getCount) {
            $select = 'count(u.id) as count';
        }

        if (
            true == $withClasses &&
            isset($options['session_id']) &&
            0 != (int) $options['session_id']
        ) {
            $sessionId = (int) $options['session_id'];
            $courseId = (int) $options['course_id'];
            unset($options['session_id']);
            $whereClasess = " WHERE ur.session_id = $sessionId AND sc.c_id = $courseId ";
        } else {
            $withClasses = false;
        }

        if ($this->getUseMultipleUrl()) {
            if (true != $withClasses) {
                $sql = "SELECT $select
                    FROM {$this->usergroup_rel_course_table} usergroup
                    INNER JOIN {$this->table} u
                    ON (u.id = usergroup.usergroup_id)
                    INNER JOIN {$this->table_course} c
                    ON (usergroup.course_id = c.id)
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = u.id)
                   ";
            } else {
                $sqlClasses = "SELECT".
                    " $select ".
                    " FROM".
                    " {$this->usergroup_rel_session_table} ur".
                    " INNER JOIN {$this->usergroup_table} u ON  u.id = ur.usergroup_id ".
                    " INNER JOIN `{$this->session_table}` s ON  s.id = ur.session_id".
                    " INNER JOIN {$this->access_url_rel_usergroup} a ON (a.usergroup_id = u.id) ".
                    " INNER JOIN {$this->session_rel_course_table} sc ON s.id = sc.session_id ".
                    " $whereClasess ";
            }
        } else {
            if (true != $withClasses) {
                $sql = "SELECT $select
                    FROM {$this->usergroup_rel_course_table} usergroup
                    INNER JOIN {$this->table} u
                    ON (u.id = usergroup.usergroup_id)
                    INNER JOIN {$this->table_course} c
                    ON (usergroup.course_id = c.id)
                   ";
            } else {
                $sqlClasses = "SELECT".
                    " $select ".
                    " FROM".
                    " {$this->usergroup_rel_session_table} ur".
                    " INNER JOIN {$this->usergroup_table} u ON  u.id = ur.usergroup_id ".
                    " INNER JOIN `{$this->session_table}` s ON  s.id = ur.session_id".
                    " INNER JOIN {$this->session_rel_course_table} sc ON s.id = sc.session_id ".
                    " $whereClasess ";
            }
        }
        if (-1 != $type) {
            $type = (int) $type;
            $options['where']['AND group_type = ? '] = $type;
        }
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $options['where']['AND access_url_id = ? '] = $urlId;
        }

        $conditions = Database::parse_conditions($options);
        if (true == $withClasses) {
            $resultClasess = Database::query($sqlClasses);
        } else {
            $sql .= $conditions;

            $result = Database::query($sql);
        }

        if ($getCount) {
            if (!empty($result)) {
                if (Database::num_rows($result)) {
                    $row = Database::fetch_array($result);
                    $counts += $row['count'];
                }
            }
            if (!empty($sqlClasses)) {
                if (Database::num_rows($resultClasess)) {
                    $row = Database::fetch_array($resultClasess);
                    $counts += $row['count'];
                }
            }

            return $counts;
        }
        if (!empty($result)) {
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $data[] = $row;
                }
            }
        }
        if (!empty($sqlClasses)) {
            if (Database::num_rows($resultClasess) > 0) {
                while ($row = Database::fetch_array($resultClasess, 'ASSOC')) {
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    /**
     * @param array $options
     * @param int   $type
     * @param bool  $getCount
     * @param bool  $withClasses
     *
     * @return array|bool
     */
    public function getUserGroupNotInCourse(
        $options = [],
        $type = -1,
        $getCount = false,
        $withClasses = false
    ) {
        $data = [];
        $sqlClasses = '';
        $whereClasess = '';
        $resultClasess = null;
        $course_id = null;
        if (isset($options['course_id'])) {
            $course_id = (int) $options['course_id'];
            unset($options['course_id']);
        }

        if (empty($course_id)) {
            return false;
        }

        $select = 'DISTINCT u.*';
        if ($getCount) {
            $select = 'count(u.id) as count';
        }

        if (
            true == $withClasses &&
            isset($options['session_id']) &&
            0 != (int) $options['session_id']
        ) {
            $sessionId = (int) $options['session_id'];
            unset($options['session_id']);
            $whereClasess = " WHERE ur.session_id != $sessionId ";
        } else {
            $withClasses = false;
        }

        if ($this->getUseMultipleUrl()) {
            if (false == $withClasses) {
                $sql = "SELECT $select
                    FROM {$this->table} u
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = u.id)
                    LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                    ON (u.id = urc.usergroup_id AND course_id = $course_id)
            ";
            } else {
                $sqlClasses = " SELECT".
                    " $select".
                    " FROM".
                    " {$this->usergroup_rel_session_table} ur".
                    " LEFT OUTER  JOIN {$this->usergroup_table} u ON u.id = ur.usergroup_id".
                    " INNER JOIN {$this->access_url_rel_usergroup} a ON (a.usergroup_id = u.id) ".
                    " LEFT JOIN `{$this->session_table}` s ON s.id = ur.session_id".
                    " LEFT JOIN {$this->session_rel_course_table} sc ON s.id = sc.session_id ".
                    " $whereClasess ";
            }
        } else {
            if (false == $withClasses) {
                $sql = "SELECT $select
                    FROM {$this->table} u
                    LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                    ON (u.id = urc.usergroup_id AND course_id = $course_id)
            ";
            } else {
                $sqlClasses = " SELECT".
                    " $select".
                    " FROM".
                    " {$this->usergroup_rel_session_table} ur".
                    " LEFT OUTER  JOIN {$this->usergroup_table} u ON u.id = ur.usergroup_id".
                    " LEFT JOIN `{$this->session_table}` s ON s.id = ur.session_id".
                    " LEFT JOIN {$this->session_rel_course_table} sc ON s.id = sc.session_id ".
                    " $whereClasess ";
            }
        }

        if (-1 != $type) {
            $type = (int) $type;
            $options['where']['AND group_type = ? '] = $type;
        }
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $options['where']['AND access_url_id = ? '] = $urlId;
        }

        /*if ($this->allowTeachers()) {
            if (!api_is_platform_admin()) {
                $userId = api_get_user_id();
                $options['where']['AND author_id = ? '] = $userId;
            }
        }*/

        $conditions = Database::parse_conditions($options);

        if (true == $withClasses) {
            $resultClasess = Database::query($sqlClasses);
        } else {
            $sql .= $conditions;
            $result = Database::query($sql);
        }

        if ($getCount) {
            if (!empty($result)) {
                $result = Database::query($sql);
                $array = Database::fetch_array($result, 'ASSOC');

                return $array['count'];
            }
            if (!empty($sqlClasses)) {
                if (Database::num_rows($resultClasess)) {
                    $row = Database::fetch_array($resultClasess);

                    return $row['count'];
                }
            }
        }
        if (!empty($result)) {
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $data[] = $row;
                }
            }
        }
        if (!empty($sqlClasses)) {
            if (Database::num_rows($resultClasess) > 0) {
                while ($row = Database::fetch_array($resultClasess, 'ASSOC')) {
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    /**
     * @param int $course_id
     *
     * @deprecated  ?
     *
     * @return array
     */
    public function get_usergroup_by_course($course_id)
    {
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $options = [
                'where' => [
                    'c.course_id = ? AND access_url_id = ?' => [
                        $course_id,
                        $urlId,
                    ],
                ],
            ];
            $from = " $this->usergroup_rel_course_table as c
                    INNER JOIN $this->access_url_rel_usergroup a
                    ON c.usergroup_id = a.usergroup_id ";
        } else {
            $options = ['where' => ['c.course_id = ?' => $course_id]];
            $from = $this->usergroup_rel_course_table." c";
        }

        $results = Database::select('c.usergroup_id', $from, $options);
        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['usergroup_id'];
            }
        }

        return $array;
    }

    /**
     * @param int $usergroup_id
     * @param int $course_id
     *
     * @return bool
     */
    public function usergroup_was_added_in_course(
        $usergroup_id,
        $course_id,
        $Session = 0
    ) {
        $Session = (int) $Session;
        $results = Database::select(
            'usergroup_id',
            $this->usergroup_rel_course_table,
            ['where' => ['course_id = ? AND usergroup_id = ?' => [$course_id, $usergroup_id]]]
        );

        $resultSession = Database::select(
            'usergroup_id',
            $this->usergroup_rel_session_table,
            ['where' => ['session_id = ? AND usergroup_id = ?' => [$Session, $usergroup_id]]]
        );

        if (empty($results) && 0 == $Session) {
            return false;
        }
        if ((empty($resultSession)) && 0 != $Session) {
            return false;
        }

        return true;
    }

    /**
     * Gets a list of session ids by user group.
     *
     * @param int $id group id
     *
     * @return array
     */
    public function get_sessions_by_usergroup($id)
    {
        $results = Database::select(
            'session_id',
            $this->usergroup_rel_session_table,
            ['where' => ['usergroup_id = ?' => $id]]
        );

        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['session_id'];
            }
        }

        return $array;
    }

    /**
     * Gets a list of user ids by user group.
     *
     * @param int   $id    user group id
     * @param array $roles
     *
     * @return array with a list of user ids
     */
    public function get_users_by_usergroup($id = null, $roles = [])
    {
        $relationCondition = '';
        if (!empty($roles)) {
            $relationConditionArray = [];
            foreach ($roles as $relation) {
                $relation = (int) $relation;
                if (empty($relation)) {
                    $relationConditionArray[] = " (relation_type = 0 OR relation_type IS NULL OR relation_type = '') ";
                } else {
                    $relationConditionArray[] = " relation_type = $relation ";
                }
            }
            $relationCondition = ' AND ( ';
            $relationCondition .= implode('OR', $relationConditionArray);
            $relationCondition .= ' ) ';
        }

        if (empty($id)) {
            $conditions = [];
        } else {
            $conditions = ['where' => ["usergroup_id = ? $relationCondition " => $id]];
        }

        $results = Database::select(
            'user_id',
            $this->usergroup_rel_user_table,
            $conditions
        );

        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['user_id'];
            }
        }

        return $array;
    }

    /**
     * Gets a list of user ids by user group.
     *
     * @param int $id       user group id
     * @param int $relation
     *
     * @return array with a list of user ids
     */
    public function getUsersByUsergroupAndRelation($id, $relation = 0)
    {
        $relation = (int) $relation;
        if (empty($relation)) {
            $conditions = ['where' => ['usergroup_id = ? AND (relation_type = 0 OR relation_type IS NULL OR relation_type = "") ' => [$id]]];
        } else {
            $conditions = ['where' => ['usergroup_id = ? AND relation_type = ?' => [$id, $relation]]];
        }

        $results = Database::select(
            'user_id',
            $this->usergroup_rel_user_table,
            $conditions
        );

        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['user_id'];
            }
        }

        return $array;
    }

    /**
     * Get the group list for a user.
     *
     * @param int $userId       The user ID
     * @param int $filterByType Optional. The type of group
     *
     * @return array
     */
    public function getUserGroupListByUser($userId, $filterByType = null)
    {
        $userId = (int) $userId;
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $from = $this->usergroup_rel_user_table." u
                INNER JOIN {$this->access_url_rel_usergroup} a
                ON (a.usergroup_id AND u.usergroup_id)
                INNER JOIN {$this->table} g
                ON (u.usergroup_id = g.id)
                ";
            $where = ['where' => ['user_id = ? AND access_url_id = ? ' => [$userId, $urlId]]];
        } else {
            $from = $this->usergroup_rel_user_table." u
                INNER JOIN {$this->table} g
                ON (u.usergroup_id = g.id)
                ";
            $where = ['where' => ['user_id = ?' => $userId]];
        }

        if (null !== $filterByType) {
            $where['where'][' AND g.group_type = ?'] = (int) $filterByType;
        }

        $results = Database::select(
            'g.*',
            $from,
            $where
        );
        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row;
            }
        }

        return $array;
    }

    /**
     * Gets the usergroup id list by user id.
     *
     * @param int $userId user id
     *
     * @return array
     */
    public function get_usergroup_by_user($userId)
    {
        $userId = (int) $userId;
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $from = $this->usergroup_rel_user_table." u
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = u.usergroup_id) ";
            $where = ['where' => ['user_id = ? AND access_url_id = ? ' => [$userId, $urlId]]];
        } else {
            $from = $this->usergroup_rel_user_table.' u ';
            $where = ['where' => ['user_id = ?' => $userId]];
        }

        $results = Database::select(
            'u.usergroup_id',
            $from,
            $where
        );

        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['usergroup_id'];
            }
        }

        return $array;
    }

    /**
     * Subscribes sessions to a group  (also adding the members of the group in the session and course).
     *
     * @param int   $usergroup_id          usergroup id
     * @param array $list                  list of session ids
     * @param bool  $deleteCurrentSessions Optional. Empty the session list for the usergroup (class)
     */
    public function subscribe_sessions_to_usergroup($usergroup_id, $list, $deleteCurrentSessions = true)
    {
        $current_list = $this->get_sessions_by_usergroup($usergroup_id);
        $user_list = $this->get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = [];
        if (!empty($list)) {
            foreach ($list as $session_id) {
                if (!in_array($session_id, $current_list)) {
                    $new_items[] = $session_id;
                }
            }
        }
        if ($deleteCurrentSessions) {
            if (!empty($current_list)) {
                foreach ($current_list as $session_id) {
                    if (!in_array($session_id, $list)) {
                        $delete_items[] = $session_id;
                    }
                }
            }

            // Deleting items
            if (!empty($delete_items)) {
                foreach ($delete_items as $session_id) {
                    if (!empty($user_list)) {
                        foreach ($user_list as $user_id) {
                            SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                        }
                    }
                    Database::delete(
                        $this->usergroup_rel_session_table,
                        ['usergroup_id = ? AND session_id = ?' => [$usergroup_id, $session_id]]
                    );
                }
            }
        }

        // Adding new relationships.
        if (!empty($new_items)) {
            foreach ($new_items as $session_id) {
                $params = ['session_id' => $session_id, 'usergroup_id' => $usergroup_id];
                Database::insert($this->usergroup_rel_session_table, $params);

                if (!empty($user_list)) {
                    SessionManager::subscribeUsersToSession(
                        $session_id,
                        $user_list,
                        null,
                        false
                    );
                }
            }
        }
    }

    /**
     * Subscribes courses to a group (also adding the members of the group in the course).
     *
     * @param int   $usergroup_id  usergroup id
     * @param array $list          list of course ids (integers)
     * @param bool  $delete_groups
     */
    public function subscribe_courses_to_usergroup($usergroup_id, $list, $delete_groups = true)
    {
        $current_list = $this->get_courses_by_usergroup($usergroup_id);
        $user_list = $this->get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = [];
        if (!empty($list)) {
            foreach ($list as $id) {
                if (!in_array($id, $current_list)) {
                    $new_items[] = $id;
                }
            }
        }

        if (!empty($current_list)) {
            foreach ($current_list as $id) {
                if (!in_array($id, $list)) {
                    $delete_items[] = $id;
                }
            }
        }

        if ($delete_groups) {
            $this->unsubscribe_courses_from_usergroup($usergroup_id, $delete_items);
        }

        // Adding new relationships
        if (!empty($new_items)) {
            foreach ($new_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if ($course_info) {
                    if (!empty($user_list)) {
                        foreach ($user_list as $user_id) {
                            CourseManager::subscribeUser(
                                $user_id,
                                $course_id
                            );
                        }
                    }
                    $params = [
                        'course_id' => $course_id,
                        'usergroup_id' => $usergroup_id,
                    ];
                    Database::insert(
                        $this->usergroup_rel_course_table,
                        $params
                    );
                }
            }
        }
    }

    /**
     * @param int   $usergroup_id
     * @param array $delete_items
     */
    public function unsubscribe_courses_from_usergroup($usergroup_id, $delete_items, $sessionId = 0)
    {
        $sessionId = (int) $sessionId;
        // Deleting items.
        if (!empty($delete_items)) {
            $user_list = $this->get_users_by_usergroup($usergroup_id);

            $groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            foreach ($delete_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if ($course_info) {
                    if (!empty($user_list)) {
                        foreach ($user_list as $user_id) {
                            CourseManager::unsubscribe_user(
                                $user_id,
                                $course_info['code'],
                                $sessionId
                            );
                        }
                    }

                    Database::delete(
                        $this->usergroup_rel_course_table,
                        [
                            'usergroup_id = ? AND course_id = ?' => [
                                $usergroup_id,
                                $course_id,
                            ],
                        ]
                    );
                }
                if (0 != $sessionId && 0 != $groupId) {
                    $this->subscribe_sessions_to_usergroup($groupId, [0]);
                } else {
                    $s = $sessionId;
                }
            }
        }
    }

    /**
     * Subscribe users to a group.
     *
     * @param int   $usergroup_id                     usergroup id
     * @param array $list                             list of user ids
     * @param bool  $delete_users_not_present_in_list
     * @param int   $relationType
     */
    public function subscribe_users_to_usergroup(
        $usergroup_id,
        $list,
        $delete_users_not_present_in_list = true,
        $relationType = 0
    ) {
        $current_list = $this->get_users_by_usergroup($usergroup_id);
        $course_list = $this->get_courses_by_usergroup($usergroup_id);
        $session_list = $this->get_sessions_by_usergroup($usergroup_id);
        $session_list = array_filter($session_list);
        $relationType = (int) $relationType;

        $delete_items = [];
        $new_items = [];

        if (!empty($list)) {
            foreach ($list as $user_id) {
                if (!in_array($user_id, $current_list)) {
                    $new_items[] = $user_id;
                }
            }
        }

        if (!empty($current_list)) {
            foreach ($current_list as $user_id) {
                if (!in_array($user_id, $list)) {
                    $delete_items[] = $user_id;
                }
            }
        }

        // Deleting items
        if (!empty($delete_items) && $delete_users_not_present_in_list) {
            foreach ($delete_items as $user_id) {
                // Removing courses
                if (!empty($course_list)) {
                    foreach ($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);
                    }
                }
                // Removing sessions
                if (!empty($session_list)) {
                    foreach ($session_list as $session_id) {
                        SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                    }
                }

                if (empty($relationType)) {
                    Database::delete(
                        $this->usergroup_rel_user_table,
                        [
                            'usergroup_id = ? AND user_id = ? AND (relation_type = "0" OR relation_type IS NULL OR relation_type = "")' => [
                                $usergroup_id,
                                $user_id,
                            ],
                        ]
                    );
                } else {
                    Database::delete(
                        $this->usergroup_rel_user_table,
                        [
                            'usergroup_id = ? AND user_id = ? AND relation_type = ?' => [
                                $usergroup_id,
                                $user_id,
                                $relationType,
                            ],
                        ]
                    );
                }
            }
        }

        // Adding new relationships
        if (!empty($new_items)) {
            // Adding sessions
            if (!empty($session_list)) {
                foreach ($session_list as $session_id) {
                    SessionManager::subscribeUsersToSession($session_id, $new_items, null, false);
                }
            }

            foreach ($new_items as $user_id) {
                // Adding courses.
                if (!empty($course_list)) {
                    foreach ($course_list as $course_id) {
                        CourseManager::subscribeUser($user_id, $course_id);
                    }
                }
                $params = [
                    'user_id' => $user_id,
                    'usergroup_id' => $usergroup_id,
                    'relation_type' => $relationType,
                ];
                Database::insert($this->usergroup_rel_user_table, $params);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function usergroup_exists($name)
    {
        $name = Database::escape_string($name);
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT * FROM $this->table u
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = u.id)
                    WHERE name = '".$name."' AND access_url_id = $urlId";
        } else {
            $sql = "SELECT * FROM $this->table WHERE name = '".$name."'";
        }

        $res = Database::query($sql);

        return 0 != Database::num_rows($res);
    }

    /**
     * @return bool
     */
    public function allowTeachers()
    {
        return true === api_get_configuration_value('allow_teachers_to_classes');
    }

    /**
     * @param int    $sidx
     * @param int    $sord
     * @param int    $start
     * @param int    $limit
     * @param string $extraWhereCondition
     *
     * @return array
     */
    public function getUsergroupsPagination($sidx, $sord, $start, $limit, $extraWhereCondition = '')
    {
        $sord = in_array(strtolower($sord), ['asc', 'desc']) ? $sord : 'desc';

        $start = (int) $start;
        $limit = (int) $limit;

        $sqlFrom = "{$this->table} u ";
        $sqlWhere = '1 = 1 ';

        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $sqlFrom .= " INNER JOIN {$this->access_url_rel_usergroup} a ON (u.id = a.usergroup_id) ";
            $sqlWhere .= " AND a.access_url_id = $urlId ";
        }

        if ($this->allowTeachers()) {
            if (!api_is_platform_admin()) {
                $userId = api_get_user_id();
                $sqlWhere .= " AND author_id = $userId ";
            }
        }

        if ($extraWhereCondition) {
            $sqlWhere .= " AND $extraWhereCondition ";
        }

        $result = Database::store_result(
            Database::query("SELECT u.* FROM $sqlFrom WHERE $sqlWhere ORDER BY name $sord LIMIT $start, $limit")
        );

        $new_result = [];
        if (!empty($result)) {
            foreach ($result as $group) {
                $group['sessions'] = count($this->get_sessions_by_usergroup($group['id']));
                $group['courses'] = count($this->get_courses_by_usergroup($group['id']));
                $roles = [];
                switch ($group['group_type']) {
                    case 0:
                        $group['group_type'] = Display::label(get_lang('Class'), 'info');
                        $roles = [0];
                        break;
                    case 1:
                        $group['group_type'] = Display::label(get_lang('Social'), 'success');
                        $roles = [
                            GROUP_USER_PERMISSION_ADMIN,
                            GROUP_USER_PERMISSION_READER,
                            GROUP_USER_PERMISSION_MODERATOR,
                            GROUP_USER_PERMISSION_HRM,
                        ];
                        break;
                }
                $group['users'] = Display::url(
                    count($this->get_users_by_usergroup($group['id'], $roles)),
                    api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?id='.$group['id']
                );
                $new_result[] = $group;
            }
            $result = $new_result;
        }
        $columns = ['name', 'users', 'courses', 'sessions', 'group_type'];

        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }

        // Multidimensional sort
        $result = msort($result, $sidx, $sord);

        return $result;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getDataToExport($options = [])
    {
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $from = $this->table." u
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (u.id = a.usergroup_id)";
            $options = ['where' => ['access_url_id = ? ' => $urlId]];
            if ($this->allowTeachers()) {
                $options['where'] = [' author_id = ? ' => api_get_user_id()];
            }
            $classes = Database::select('a.id, name, description', $from, $options);
        } else {
            if ($this->allowTeachers()) {
                $options['where'] = [' author_id = ? ' => api_get_user_id()];
            }
            $classes = Database::select('id, name, description', $this->table, $options);
        }

        $result = [];
        if (!empty($classes)) {
            foreach ($classes as $data) {
                $users = $this->getUserListByUserGroup($data['id']);
                $userToString = null;
                if (!empty($users)) {
                    $userNameList = [];
                    foreach ($users as $userData) {
                        $userNameList[] = $userData['username'];
                    }
                    $userToString = implode(',', $userNameList);
                }
                $data['users'] = $userToString;
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * @param string $firstLetter
     * @param int    $limit
     *
     * @return array
     */
    public function filterByFirstLetter($firstLetter, $limit = 0)
    {
        $firstLetter = Database::escape_string($firstLetter);
        $limit = (int) $limit;

        $sql = ' SELECT g.id, name ';

        $urlCondition = '';
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $sql .= " FROM $this->table g
                    INNER JOIN $this->access_url_rel_usergroup a
                    ON (g.id = a.usergroup_id) ";
            $urlCondition = " AND access_url_id = $urlId ";
        } else {
            $sql = " FROM $this->table g ";
        }
        $sql .= "
		        WHERE
		            name LIKE '".$firstLetter."%' OR
		            name LIKE '".api_strtolower($firstLetter)."%'
		            $urlCondition
		        ORDER BY name DESC ";

        if (!empty($limit)) {
            $sql .= " LIMIT $limit ";
        }

        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * Select user group not in list.
     *
     * @param array $list
     *
     * @return array
     */
    public function getUserGroupNotInList($list)
    {
        if (empty($list)) {
            return [];
        }

        $list = array_map('intval', $list);
        $listToString = implode("','", $list);

        $sql = 'SELECT * ';
        $urlCondition = '';
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $sql .= " FROM $this->table g
                    INNER JOIN $this->access_url_rel_usergroup a
                    ON (g.id = a.usergroup_id)";
            $urlCondition = " AND access_url_id = $urlId ";
        } else {
            $sql = " FROM $this->table g ";
        }

        $sql .= " WHERE g.id NOT IN ('$listToString') $urlCondition ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param $params
     * @param bool $showQuery
     *
     * @return bool|int
     */
    public function save($params, $showQuery = false)
    {
        $params['updated_at'] = $params['created_at'] = api_get_utc_datetime();
        $params['group_type'] = isset($params['group_type']) ? self::SOCIAL_CLASS : self::NORMAL_CLASS;
        $params['allow_members_leave_group'] = isset($params['allow_members_leave_group']) ? 1 : 0;

        $userGroupExists = $this->usergroup_exists(trim($params['name']));
        if (false === $userGroupExists) {
            $userGroup = new UserGroupEntity();
            $repo = Container::getUsergroupRepository();
            $userGroup
                ->setName(trim($params['name']))
                ->setDescription($params['description'])
                ->setUrl($params['url'])
                ->setVisibility($params['visibility'])
                ->setGroupType($params['group_type'])
                ->setAllowMembersToLeaveGroup($params['allow_members_leave_group'])
            ;
            if ($this->allowTeachers()) {
                $userGroup->setAuthorId(api_get_user_id());
            }

            $repo->create($userGroup);

            $id = $userGroup->getId();
            if ($id) {
                if ($this->getUseMultipleUrl()) {
                    $this->subscribeToUrl($id, api_get_current_access_url_id());
                }

                if (self::SOCIAL_CLASS == $params['group_type']) {
                    $this->add_user_to_group(
                        api_get_user_id(),
                        $id,
                        $params['group_type']
                    );
                }
                $request = Container::getRequest();
                $file = $request->files->get('picture');
                $this->manageFileUpload($userGroup, $file);
            }

            return $id;
        }

        return false;
    }

    public function update($params, $showQuery = false)
    {
        $repo = Container::getUsergroupRepository();
        /** @var UserGroupEntity $userGroup */
        $userGroup = $repo->find($params['id']);
        if (null === $userGroup) {
            return false;
        }

        //$params['updated_on'] = api_get_utc_datetime();
        $userGroup
            ->setGroupType(isset($params['group_type']) ? self::SOCIAL_CLASS : self::NORMAL_CLASS)
            ->setAllowMembersToLeaveGroup(isset($params['allow_members_leave_group']) ? 1 : 0)
        ;
        $cropImage = isset($params['picture_crop_result']) ? $params['picture_crop_result'] : null;
        $picture = isset($_FILES['picture']) ? $_FILES['picture'] : null;
        if (!empty($picture)) {
            $request = Container::getRequest();
            $file = $request->files->get('picture');
            $this->manageFileUpload($userGroup, $file, $cropImage);
        }

        //parent::update($params, $showQuery);
        $repo->update($userGroup);

        if (isset($params['delete_picture'])) {
            $this->delete_group_picture($params['id']);
        }

        return true;
    }

    /**
     * @param UserGroupEntity $groupId
     * @param string          $picture
     * @param string          $cropParameters
     *
     * @return bool
     */
    public function manageFileUpload($userGroup, $picture, $cropParameters = '')
    {
        if ($userGroup) {
            $illustrationRepo = Container::getIllustrationRepository();
            $illustrationRepo->addIllustration($userGroup, api_get_user_entity(), $picture, $cropParameters);

            return true;
        }

        return false;
    }

    /**
     * @param int $groupId
     *
     * @return string
     */
    public function delete_group_picture($groupId)
    {
        $repo = Container::getUsergroupRepository();
        $userGroup = $repo->find($groupId);
        if ($userGroup) {
            $illustrationRepo = Container::getIllustrationRepository();
            $illustrationRepo->deleteIllustration($userGroup);
        }
    }

    /**
     * @return mixed
     */
    public function getGroupType()
    {
        return $this->groupType;
    }

    /**
     * @param int $id
     *
     * @return bool|void
     */
    public function delete($id)
    {
        $id = (int) $id;
        if ($this->getUseMultipleUrl()) {
            $this->unsubscribeToUrl($id, api_get_current_access_url_id());
        }

        $sql = "DELETE FROM $this->usergroup_rel_user_table
                WHERE usergroup_id = $id";
        Database::query($sql);

        $sql = "DELETE FROM $this->usergroup_rel_course_table
                WHERE usergroup_id = $id";
        Database::query($sql);

        $sql = "DELETE FROM $this->usergroup_rel_session_table
                WHERE usergroup_id = $id";
        Database::query($sql);

        //parent::delete($id);
        $repo = Container::getUsergroupRepository();
        $userGroup = $repo->find($id);
        $repo->delete($userGroup);
    }

    /**
     * @param int $id
     * @param int $urlId
     */
    public function subscribeToUrl($id, $urlId)
    {
        Database::insert(
            $this->access_url_rel_usergroup,
            [
                'access_url_id' => $urlId,
                'usergroup_id' => $id,
            ]
        );
    }

    /**
     * @param int $id
     * @param int $urlId
     */
    public function unsubscribeToUrl($id, $urlId)
    {
        Database::delete(
            $this->access_url_rel_usergroup,
            [
                'access_url_id = ? AND usergroup_id = ? ' => [$urlId, $id],
            ]
        );
    }

    /**
     * @param $needle
     *
     * @return xajaxResponse
     */
    public static function searchUserGroupAjax($needle)
    {
        $response = new xajaxResponse();
        $return = '';

        if (!empty($needle)) {
            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = api_convert_encoding($needle, $charset, 'utf-8');
            $needle = Database::escape_string($needle);

            $sql = 'SELECT id, name
                    FROM '.Database::get_main_table(TABLE_USERGROUP).' u
                    WHERE name LIKE "'.$needle.'%"
                    ORDER BY name
                    LIMIT 11';
            $result = Database::query($sql);
            $i = 0;
            while ($data = Database::fetch_array($result)) {
                $i++;
                if ($i <= 10) {
                    $return .= '<a
                    href="javascript: void(0);"
                    onclick="javascript: add_user_to_url(\''.addslashes($data['id']).'\',\''.addslashes($data['name']).' \')">'.$data['name'].' </a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
        }
        $response->addAssign('ajax_list_courses', 'innerHTML', api_utf8_encode($return));

        return $response;
    }

    /**
     * Get user list by usergroup.
     *
     * @param int    $id
     * @param string $orderBy
     *
     * @return array
     */
    public function getUserListByUserGroup($id, $orderBy = '')
    {
        $id = (int) $id;
        $sql = "SELECT u.* FROM $this->table_user u
                INNER JOIN $this->usergroup_rel_user_table c
                ON c.user_id = u.id
                WHERE c.usergroup_id = $id"
                ;
        if (!empty($orderBy)) {
            $orderBy = Database::escape_string($orderBy);
            $sql .= " ORDER BY $orderBy ";
        }
        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * @param FormValidator $form
     * @param string        $type
     */
    public function setForm($form, $type = 'add', UserGroupEntity $userGroup = null)
    {
        $header = '';
        switch ($type) {
            case 'add':
                $header = get_lang('Add');
                break;
            case 'edit':
                $header = get_lang('Edit');
                break;
        }

        $form->addHeader($header);

        // Name
        $form->addElement('text', 'name', get_lang('Name'), ['maxlength' => 255]);
        $form->applyFilter('name', 'trim');

        $form->addRule('name', get_lang('Required field'), 'required');
        $form->addRule('name', '', 'maxlength', 255);

        // Description
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            true,
            false,
            [
            'ToolbarSet' => 'Minimal',
            ]
        );
        $form->applyFilter('description', 'trim');

        if ($this->showGroupTypeSetting) {
            $form->addElement(
                'checkbox',
                'group_type',
                null,
                get_lang('Social group')
            );
        }

        // url
        $form->addElement('text', 'url', get_lang('URL'));
        $form->applyFilter('url', 'trim');

        // Picture
        //$allowed_picture_types = $this->getAllowedPictureExtensions();

        // Picture
        $form->addFile(
            'picture',
            get_lang('Add a picture'),
            ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1']
        );

        $repo = Container::getIllustrationRepository();
        if ($userGroup && $repo->hasIllustration($userGroup)) {
            $picture = $repo->getIllustrationUrl($userGroup);
            $img = '<img src="'.$picture.'" />';
            $form->addElement('label', null, $img);
            $form->addElement('checkbox', 'delete_picture', '', get_lang('Remove picture'));
        }

        $form->addElement('select', 'visibility', get_lang('Group Permissions'), $this->getGroupStatusList());
        $form->setRequiredNote('<span class="form_required">*</span> <small>'.get_lang('Required field').'</small>');
        $form->addElement('checkbox', 'allow_members_leave_group', '', get_lang('Allow members to leave group'));

        // Setting the form elements
        if ('add' === $type) {
            $form->addButtonCreate($header);
        } else {
            $form->addButtonUpdate($header);
        }
    }

    /**
     * Gets the current group image.
     *
     * @param string $id group id
     * @param string picture group name
     * @param string height
     * @param int $size_picture picture size it can be small_,  medium_  or  big_
     * @param string style css
     *
     * @return string
     */
    public function get_picture_group(
        $id,
        $picture_file,
        $height,
        $size_picture = GROUP_IMAGE_SIZE_MEDIUM,
        $style = ''
    ) {
        $repoIllustration = Container::getIllustrationRepository();
        $repoUserGroup = Container::getUsergroupRepository();
        $userGroup = $repoUserGroup->find($id);

        return $repoIllustration->getIllustrationUrl($userGroup);

        /*
        $picture = [];
        //$picture['style'] = $style;
        if ('unknown.jpg' === $picture_file) {
            $picture['file'] = Display::returnIconPath($picture_file);

            return $picture;
        }

        switch ($size_picture) {
            case GROUP_IMAGE_SIZE_ORIGINAL:
                $size_picture = '';
                break;
            case GROUP_IMAGE_SIZE_BIG:
                $size_picture = 'big_';
                break;
            case GROUP_IMAGE_SIZE_MEDIUM:
                $size_picture = 'medium_';
                break;
            case GROUP_IMAGE_SIZE_SMALL:
                $size_picture = 'small_';
                break;
            default:
                $size_picture = 'medium_';
        }

        $image_array_sys = $this->get_group_picture_path_by_id($id, 'system', false, true);
        $image_array = $this->get_group_picture_path_by_id($id, 'web', false, true);
        $file = $image_array_sys['dir'].$size_picture.$picture_file;
        if (file_exists($file)) {
            $picture['file'] = $image_array['dir'].$size_picture.$picture_file;
            //$picture['style'] = '';
            if ($height > 0) {
                $dimension = api_getimagesize($picture['file']);
                $margin = ($height - $dimension['width']) / 2;
                //@ todo the padding-top should not be here
            }
        } else {
            $file = $image_array_sys['dir'].$picture_file;
            if (file_exists($file) && !is_dir($file)) {
                $picture['file'] = $image_array['dir'].$picture_file;
            } else {
                $picture['file'] = Display::returnIconPath('group_na.png', 64);
            }
        }

        return $picture;*/
    }

    /**
     * @return array
     */
    public function getAllowedPictureExtensions()
    {
        return ['jpg', 'jpeg', 'png', 'gif'];
    }

    /**
     * @return array
     */
    public function getGroupStatusList()
    {
        $status = [
            GROUP_PERMISSION_OPEN => get_lang('Open'),
            GROUP_PERMISSION_CLOSED => get_lang('Closed'),
        ];

        return $status;
    }

    /**
     * @param int $type
     */
    public function setGroupType($type)
    {
        $this->groupType = (int) $type;
    }

    /**
     * @param int $group_id
     * @param int $user_id
     *
     * @return bool
     */
    public function is_group_admin($group_id, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role = $this->get_user_group_role($user_id, $group_id);
        if (in_array($user_role, [GROUP_USER_PERMISSION_ADMIN])) {
            return true;
        }

        return false;
    }

    /**
     * @param int $group_id
     * @param int $user_id
     *
     * @return bool
     */
    public function isGroupModerator($group_id, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role = $this->get_user_group_role($user_id, $group_id);
        if (in_array($user_role, [GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $group_id
     * @param int $user_id
     *
     * @return bool
     */
    public function is_group_member($group_id, $user_id = 0)
    {
        if (api_is_platform_admin()) {
            return true;
        }
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $roles = [
            GROUP_USER_PERMISSION_ADMIN,
            GROUP_USER_PERMISSION_MODERATOR,
            GROUP_USER_PERMISSION_READER,
            GROUP_USER_PERMISSION_HRM,
        ];
        $user_role = $this->get_user_group_role($user_id, $group_id);
        if (in_array($user_role, $roles)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the relationship between a group and a User.
     *
     * @author Julio Montoya
     *
     * @param int $user_id
     * @param int $group_id
     *
     * @return int 0 if there are not relationship otherwise returns the user group
     * */
    public function get_user_group_role($user_id, $group_id)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $return_value = 0;
        $user_id = (int) $user_id;
        $group_id = (int) $group_id;

        if (!empty($user_id) && !empty($group_id)) {
            $sql = "SELECT relation_type
                    FROM $table_group_rel_user
                    WHERE
                        usergroup_id = $group_id AND
                        user_id = $user_id ";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result, 'ASSOC');
                $return_value = $row['relation_type'];
            }
        }

        return $return_value;
    }

    /**
     * @param int $userId
     * @param int $groupId
     *
     * @return string
     */
    public function getUserRoleToString($userId, $groupId)
    {
        $role = $this->get_user_group_role($userId, $groupId);
        $roleToString = '';

        switch ($role) {
            case GROUP_USER_PERMISSION_ADMIN:
                $roleToString = get_lang('Admin');
                break;
            case GROUP_USER_PERMISSION_READER:
                $roleToString = get_lang('Reader');
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION:
                $roleToString = get_lang('Pending invitation');
                break;
            case GROUP_USER_PERMISSION_MODERATOR:
                $roleToString = get_lang('Moderator');
                break;
            case GROUP_USER_PERMISSION_HRM:
                $roleToString = get_lang('Human Resources Manager');
                break;
        }

        return $roleToString;
    }

    /**
     * Add a group of users into a group of URLs.
     *
     * @author Julio Montoya
     *
     * @param array $user_list
     * @param array $group_list
     * @param int   $relation_type
     *
     * @return array
     */
    public function add_users_to_groups($user_list, $group_list, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $table_url_rel_group = $this->usergroup_rel_user_table;
        $result_array = [];
        $relation_type = (int) $relation_type;

        if (is_array($user_list) && is_array($group_list)) {
            foreach ($group_list as $group_id) {
                foreach ($user_list as $user_id) {
                    $user_id = (int) $user_id;
                    $group_id = (int) $group_id;

                    $role = $this->get_user_group_role($user_id, $group_id);
                    if (0 == $role) {
                        $sql = "INSERT INTO $table_url_rel_group
		               			SET
		               			    user_id = $user_id ,
		               			    usergroup_id = $group_id ,
		               			    relation_type = $relation_type ";

                        $result = Database::query($sql);
                        if ($result) {
                            $result_array[$group_id][$user_id] = 1;
                        } else {
                            $result_array[$group_id][$user_id] = 0;
                        }
                    }
                }
            }
        }

        return $result_array;
    }

    /**
     * Deletes an url and session relationship.
     *
     * @author Julio Montoya
     *
     * @param int $userId
     * @param int $groupId
     *
     * @return bool true if success
     * */
    public function delete_user_rel_group($userId, $groupId)
    {
        $userId = (int) $userId;
        $groupId = (int) $groupId;
        if (empty($userId) || empty($groupId)) {
            return false;
        }

        $table = $this->usergroup_rel_user_table;
        $sql = "DELETE FROM $table
                WHERE
                    user_id = $userId AND
                    usergroup_id = $groupId";

        $result = Database::query($sql);

        return $result;
    }

    /**
     * Add a user into a group.
     *
     * @author Julio Montoya
     *
     * @param int $user_id
     * @param int $group_id
     * @param int $relation_type
     *
     * @return bool true if success
     */
    public function add_user_to_group($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $table_url_rel_group = $this->usergroup_rel_user_table;
        if (!empty($user_id) && !empty($group_id)) {
            $role = $this->get_user_group_role($user_id, $group_id);

            if (0 == $role) {
                $sql = "INSERT INTO $table_url_rel_group
           				SET
           				    user_id = ".intval($user_id).",
           				    usergroup_id = ".intval($group_id).",
           				    relation_type = ".intval($relation_type);
                Database::query($sql);
            } elseif (GROUP_USER_PERMISSION_PENDING_INVITATION == $role) {
                //if somebody already invited me I can be added
                self::update_user_role($user_id, $group_id, GROUP_USER_PERMISSION_READER);
            }
        }

        return true;
    }

    /**
     * Updates the group_rel_user table  with a given user and group ids.
     *
     * @author Julio Montoya
     *
     * @param int $user_id
     * @param int $group_id
     * @param int $relation_type
     */
    public function update_user_role($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $group_id = (int) $group_id;
        $user_id = (int) $user_id;
        $relation_type = (int) $relation_type;

        $sql = "UPDATE $table_group_rel_user
   				SET relation_type = $relation_type
                WHERE user_id = $user_id AND usergroup_id = $group_id";
        Database::query($sql);
    }

    /**
     * Gets the inner join from users and group table.
     *
     * @return array|int Database::store_result of the result
     *
     * @author Julio Montoya
     * */
    public function get_groups_by_user($user_id, $relationType = GROUP_USER_PERMISSION_READER, $with_image = false)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;
        $user_id = (int) $user_id;

        if (0 == $relationType) {
            $relationCondition = '';
        } else {
            if (is_array($relationType)) {
                $relationType = array_map('intval', $relationType);
                $relationType = implode("','", $relationType);
                $relationCondition = " AND ( gu.relation_type IN ('$relationType')) ";
            } else {
                $relationType = (int) $relationType;
                $relationCondition = " AND gu.relation_type = $relationType ";
            }
        }

        $sql = 'SELECT
                    g.picture,
                    g.name,
                    g.description,
                    g.id ,
                    gu.relation_type';

        $urlCondition = '';
        if ($this->getUseMultipleUrl()) {
            $sql .= " FROM $tbl_group g
                    INNER JOIN ".$this->access_url_rel_usergroup." a
                    ON (g.id = a.usergroup_id)
                    INNER JOIN $table_group_rel_user gu
                    ON gu.usergroup_id = g.id";
            $urlId = api_get_current_access_url_id();
            $urlCondition = " AND access_url_id = $urlId ";
        } else {
            $sql .= " FROM $tbl_group g
                    INNER JOIN $table_group_rel_user gu
                    ON gu.usergroup_id = g.id";
        }

        $sql .= " WHERE
				    g.group_type = ".self::SOCIAL_CLASS." AND
                    gu.user_id = $user_id
                    $relationCondition
                    $urlCondition
                ORDER BY created_at DESC ";
        $result = Database::query($sql);
        $array = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($with_image) {
                    $picture = $this->get_picture_group($row['id'], $row['picture'], 80);
                    $img = '<img src="'.$picture['file'].'" />';
                    $row['picture'] = $img;
                }
                $array[$row['id']] = $row;
            }
        }

        return $array;
    }

    /** Gets the inner join of users and group table.
     * @param int  quantity of records
     * @param bool show groups with image or not
     *
     * @return array with group content
     *
     * @author Julio Montoya
     * */
    public function get_groups_by_popularity($num = 6, $with_image = true)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;
        if (empty($num)) {
            $num = 6;
        } else {
            $num = (int) $num;
        }
        // only show admins and readers
        $whereCondition = " WHERE
                              g.group_type = ".self::SOCIAL_CLASS." AND
                              gu.relation_type IN
                              ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."', '".GROUP_USER_PERMISSION_HRM."') ";

        $sql = 'SELECT DISTINCT count(user_id) as count, g.picture, g.name, g.description, g.id ';

        $urlCondition = '';
        if ($this->getUseMultipleUrl()) {
            $sql .= " FROM $tbl_group g
                    INNER JOIN ".$this->access_url_rel_usergroup." a
                    ON (g.id = a.usergroup_id)
                    INNER JOIN $table_group_rel_user gu
                    ON gu.usergroup_id = g.id";
            $urlId = api_get_current_access_url_id();
            $urlCondition = " AND access_url_id = $urlId ";
        } else {
            $sql .= " FROM $tbl_group g
                    INNER JOIN $table_group_rel_user gu
                    ON gu.usergroup_id = g.id";
        }

        $sql .= "
				$whereCondition
				$urlCondition
				GROUP BY g.id
				ORDER BY count DESC
				LIMIT $num";

        $result = Database::query($sql);
        $array = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = $this->get_picture_group($row['id'], $row['picture'], 80);
                $img = '<img src="'.$picture['file'].'" />';
                $row['picture'] = $img;
            }
            if (empty($row['id'])) {
                continue;
            }
            $array[$row['id']] = $row;
        }

        return $array;
    }

    /** Gets the last groups created.
     * @param int  $num       quantity of records
     * @param bool $withImage show groups with image or not
     *
     * @return array with group content
     *
     * @author Julio Montoya
     * */
    public function get_groups_by_age($num = 6, $withImage = true)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;

        if (empty($num)) {
            $num = 6;
        } else {
            $num = (int) $num;
        }

        $where = " WHERE
                        g.group_type = ".self::SOCIAL_CLASS." AND
                        gu.relation_type IN
                        ('".GROUP_USER_PERMISSION_ADMIN."' ,
                        '".GROUP_USER_PERMISSION_READER."',
                        '".GROUP_USER_PERMISSION_MODERATOR."',
                        '".GROUP_USER_PERMISSION_HRM."')
                    ";
        $sql = 'SELECT DISTINCT
                  count(user_id) as count,
                  g.picture,
                  g.name,
                  g.description,
                  g.id ';

        $urlCondition = '';
        if ($this->getUseMultipleUrl()) {
            $sql .= " FROM $tbl_group g
                    INNER JOIN ".$this->access_url_rel_usergroup." a
                    ON (g.id = a.usergroup_id)
                    INNER JOIN $table_group_rel_user gu
                    ON gu.usergroup_id = g.id";
            $urlId = api_get_current_access_url_id();
            $urlCondition = " AND access_url_id = $urlId ";
        } else {
            $sql .= " FROM $tbl_group g
                    INNER JOIN $table_group_rel_user gu
                    ON gu.usergroup_id = g.id";
        }
        $sql .= "
                $where
                $urlCondition
                GROUP BY g.id
                ORDER BY created_at DESC
                LIMIT $num ";

        $result = Database::query($sql);
        $array = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($withImage) {
                $picture = $this->get_picture_group($row['id'], $row['picture'], 80);
                $img = '<img src="'.$picture['file'].'" />';
                $row['picture'] = $img;
            }
            if (empty($row['id'])) {
                continue;
            }
            $array[$row['id']] = $row;
        }

        return $array;
    }

    /**
     * Gets the group's members.
     *
     * @param int group id
     * @param bool show image or not of the group
     * @param array list of relation type use constants
     * @param int from value
     * @param int limit
     * @param array image configuration, i.e array('height'=>'20px', 'size'=> '20px')
     *
     * @return array list of users in a group
     */
    public function get_users_by_group(
        $group_id,
        $withImage = false,
        $relation_type = [],
        $from = null,
        $limit = null,
        $image_conf = ['size' => USER_IMAGE_SIZE_MEDIUM, 'height' => 80]
    ) {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return [];
        }

        $limit_text = '';
        if (isset($from) && isset($limit)) {
            $from = (int) $from;
            $limit = (int) $limit;
            $limit_text = "LIMIT $from, $limit";
        }

        if (0 == count($relation_type)) {
            $where_relation_condition = '';
        } else {
            $new_relation_type = [];
            foreach ($relation_type as $rel) {
                $rel = (int) $rel;
                $new_relation_type[] = "'$rel'";
            }
            $relation_type = implode(',', $new_relation_type);
            if (!empty($relation_type)) {
                $where_relation_condition = "AND gu.relation_type IN ($relation_type) ";
            }
        }

        $sql = "SELECT
                    picture_uri as image,
                    u.id,
                    CONCAT (u.firstname,' ', u.lastname) as fullname,
                    relation_type
    		    FROM $tbl_user u
    		    INNER JOIN $table_group_rel_user gu
    			ON (gu.user_id = u.id)
    			WHERE
    			    gu.usergroup_id= $group_id
    			    $where_relation_condition
    			ORDER BY relation_type, firstname
    			$limit_text";

        $result = Database::query($sql);
        $array = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($withImage) {
                $userInfo = api_get_user_info($row['id']);
                $userPicture = UserManager::getUserPicture($row['id']);
                $row['image'] = '<img src="'.$userPicture.'"  />';
                $row['user_info'] = $userInfo;
            }

            $row['user_id'] = $row['id'];
            $array[$row['id']] = $row;
        }

        return $array;
    }

    /**
     * Gets all the members of a group no matter the relationship for
     * more specifications use get_users_by_group.
     *
     * @param int group id
     *
     * @return array
     */
    public function get_all_users_by_group($group_id)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return [];
        }

        $sql = "SELECT u.id, u.firstname, u.lastname, relation_type
                FROM $tbl_user u
			    INNER JOIN $table_group_rel_user gu
			    ON (gu.user_id = u.id)
			    WHERE gu.usergroup_id= $group_id
			    ORDER BY relation_type, firstname";

        $result = Database::query($sql);
        $array = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $array[$row['id']] = $row;
        }

        return $array;
    }

    /**
     * Shows the left column of the group page.
     *
     * @param int    $group_id
     * @param int    $user_id
     * @param string $show
     *
     * @return string
     */
    public function show_group_column_information($group_id, $user_id, $show = '')
    {
        $html = '';
        $group_info = $this->get($group_id);

        //my relation with the group is set here
        $my_group_role = $this->get_user_group_role($user_id, $group_id);

        // Loading group permission
        $links = '';
        switch ($my_group_role) {
            case GROUP_USER_PERMISSION_READER:
                // I'm just a reader
                $relation_group_title = get_lang('I am a reader');
                $links .= '<li class="'.('invite_friends' == $show ? 'active' : '').'"><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('Invite friends')).get_lang('Invite friends').'</a></li>';
                if (self::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                        Display::return_icon('group_leave.png', get_lang('Leave group')).get_lang('Leave group').'</a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_ADMIN:
                $relation_group_title = get_lang('I am an admin');
                $links .= '<li class="'.('group_edit' == $show ? 'active' : '').'"><a href="group_edit.php?id='.$group_id.'">'.
                            Display::return_icon('group_edit.png', get_lang('Edit this group')).get_lang('Edit this group').'</a></li>';
                $links .= '<li class="'.('member_list' == $show ? 'active' : '').'"><a href="group_waiting_list.php?id='.$group_id.'">'.
                            Display::return_icon('waiting_list.png', get_lang('Waiting list')).get_lang('Waiting list').'</a></li>';
                $links .= '<li class="'.('invite_friends' == $show ? 'active' : '').'"><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('Invite friends')).get_lang('Invite friends').'</a></li>';
                if (self::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                        Display::return_icon('group_leave.png', get_lang('Leave group')).get_lang('Leave group').'</a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION:
//				$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('You have been invited to join now'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('You have been invited to join now').'</span></a></li>';
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
                $relation_group_title = get_lang('Waiting for admin response');
                break;
            case GROUP_USER_PERMISSION_MODERATOR:
                $relation_group_title = get_lang('I am a moderator');
                //$links .=  '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('Compose message').'">'.Display::return_icon('compose_message.png', get_lang('Create thread'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('Create thread').'</span></a></li>';
                //$links .=  '<li><a href="groups.php?id='.$group_id.'">'.				Display::return_icon('message_list.png', get_lang('Messages list'), array('hspace'=>'6')).'<span class="'.($show=='messages_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Messages list').'</span></a></li>';
                //$links .=  '<li><a href="group_members.php?id='.$group_id.'">'.		Display::return_icon('member_list.png', get_lang('Members list'), array('hspace'=>'6')).'<span class="'.($show=='member_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('Members list').'</span></a></li>';
                if (GROUP_PERMISSION_CLOSED == $group_info['visibility']) {
                    $links .= '<li><a href="group_waiting_list.php?id='.$group_id.'">'.
                                Display::return_icon('waiting_list.png', get_lang('Waiting list')).get_lang('Waiting list').'</a></li>';
                }
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('Invite friends')).get_lang('Invite friends').'</a></li>';
                if (self::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                        Display::return_icon('group_leave.png', get_lang('Leave group')).get_lang('Leave group').'</a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_HRM:
                $relation_group_title = get_lang('I am a human resources manager');
                $links .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="ajax" title="'.get_lang('Compose message').'" data-size="lg" data-title="'.get_lang('Compose message').'">'.
                            Display::return_icon('new-message.png', get_lang('Create thread')).get_lang('Create thread').'</a></li>';
                $links .= '<li><a href="group_view.php?id='.$group_id.'">'.
                            Display::return_icon('message_list.png', get_lang('Messages list')).get_lang('Messages list').'</a></li>';
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('Invite friends')).get_lang('Invite friends').'</a></li>';
                $links .= '<li><a href="group_members.php?id='.$group_id.'">'.
                            Display::return_icon('member_list.png', get_lang('Members list')).get_lang('Members list').'</a></li>';
                $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                            Display::return_icon('delete_data.gif', get_lang('Leave group')).get_lang('Leave group').'</a></li>';
                break;
            default:
                //$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('Join group'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('Join group').'</a></span></li>';
                break;
        }
        if (!empty($links)) {
            $list = '<ul class="nav nav-pills">';
            $list .= $links;
            $list .= '</ul>';
            $html .= Display::panelCollapse(
                get_lang('Social groups'),
                $list,
                'sm-groups',
                [],
                'groups-acordeon',
                'groups-collapse'
            );
        }

        return $html;
    }

    /**
     * @param int $group_id
     * @param int $topic_id
     */
    public function delete_topic($group_id, $topic_id)
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $topic_id = (int) $topic_id;
        $group_id = (int) $group_id;

        $sql = "UPDATE $table_message SET
                    msg_status = 3
                WHERE
                    group_id = $group_id AND
                    (id = '$topic_id' OR parent_id = $topic_id)
                ";
        Database::query($sql);
    }

    /**
     * @param string $user_id
     * @param int    $relation_type
     * @param bool   $with_image
     *
     * @deprecated
     *
     * @return int
     */
    public function get_groups_by_user_count(
        $user_id = '',
        $relation_type = GROUP_USER_PERMISSION_READER,
        $with_image = false
    ) {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;
        $user_id = (int) $user_id;

        if (0 == $relation_type) {
            $where_relation_condition = '';
        } else {
            $relation_type = (int) $relation_type;
            $where_relation_condition = "AND gu.relation_type = $relation_type ";
        }

        $sql = "SELECT count(g.id) as count
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.usergroup_id = g.id
				WHERE gu.user_id = $user_id $where_relation_condition ";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result, 'ASSOC');

            return $row['count'];
        }

        return 0;
    }

    /**
     * @param string $tag
     * @param int    $from
     * @param int    $number_of_items
     *
     * @return array
     */
    public function get_all_group_tags($tag, $from = 0, $number_of_items = 10, $getCount = false)
    {
        $group_table = $this->table;
        $tag = Database::escape_string($tag);
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;
        $return = [];

        $keyword = $tag;
        $sql = 'SELECT  g.id, g.name, g.description, g.url, g.picture ';
        $urlCondition = '';
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $sql .= " FROM $this->table g
                    INNER JOIN $this->access_url_rel_usergroup a
                    ON (g.id = a.usergroup_id)";
            $urlCondition = " AND access_url_id = $urlId ";
        } else {
            $sql .= " FROM $group_table g";
        }
        if (isset($keyword)) {
            $sql .= " WHERE (
                        g.name LIKE '%".$keyword."%' OR
                        g.description LIKE '%".$keyword."%' OR
                        g.url LIKE '%".$keyword."%'
                     ) $urlCondition
                     ";
        } else {
            $sql .= " WHERE 1 = 1 $urlCondition ";
        }

        $direction = 'ASC';
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $sql .= " LIMIT $from, $number_of_items";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                if (!in_array($row['id'], $return)) {
                    $return[$row['id']] = $row;
                }
            }
        }

        return $return;
    }

    /**
     * @param int $group_id
     *
     * @return array
     */
    public static function get_parent_groups($group_id)
    {
        $t_rel_group = Database::get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $group_id = (int) $group_id;

        $max_level = 10;
        $select_part = 'SELECT ';
        $cond_part = '';
        for ($i = 1; $i <= $max_level; $i++) {
            $rg_number = $i - 1;
            if ($i == $max_level) {
                $select_part .= "rg$rg_number.group_id as id_$rg_number ";
            } else {
                $select_part .= "rg$rg_number.group_id as id_$rg_number, ";
            }
            if (1 == $i) {
                $cond_part .= "FROM $t_rel_group rg0
                               LEFT JOIN $t_rel_group rg$i
                               ON rg$rg_number.group_id = rg$i.subgroup_id ";
            } else {
                $cond_part .= " LEFT JOIN $t_rel_group rg$i
                                ON rg$rg_number.group_id = rg$i.subgroup_id ";
            }
        }
        $sql = $select_part.' '.$cond_part."WHERE rg0.subgroup_id='$group_id'";
        $res = Database::query($sql);
        $temp_arr = Database::fetch_array($res, 'NUM');
        $toReturn = [];
        if (is_array($temp_arr)) {
            foreach ($temp_arr as $elt) {
                if (isset($elt)) {
                    $toReturn[] = $elt;
                }
            }
        }

        return $toReturn;
    }

    /**
     * Get the group member list by a user and his group role.
     *
     * @param int  $userId                The user ID
     * @param int  $relationType          Optional. The relation type. GROUP_USER_PERMISSION_ADMIN by default
     * @param bool $includeSubgroupsUsers Optional. Whether include the users from subgroups
     *
     * @return array
     */
    public function getGroupUsersByUser(
        $userId,
        $relationType = GROUP_USER_PERMISSION_ADMIN,
        $includeSubgroupsUsers = true
    ) {
        $userId = (int) $userId;
        $groups = $this->get_groups_by_user($userId, $relationType);
        $groupsId = array_keys($groups);
        $subgroupsId = [];
        $userIdList = [];

        if ($includeSubgroupsUsers) {
            foreach ($groupsId as $groupId) {
                $subgroupsId = array_merge($subgroupsId, self::getGroupsByDepthLevel($groupId));
            }

            $groupsId = array_merge($groupsId, $subgroupsId);
        }

        $groupsId = array_unique($groupsId);

        if (empty($groupsId)) {
            return [];
        }

        foreach ($groupsId as $groupId) {
            $groupUsers = $this->get_users_by_group($groupId);

            if (empty($groupUsers)) {
                continue;
            }

            foreach ($groupUsers as $member) {
                if ($member['user_id'] == $userId) {
                    continue;
                }

                $userIdList[] = (int) $member['user_id'];
            }
        }

        return array_unique($userIdList);
    }

    /**
     * Get the subgroups ID from a group.
     * The default $levels value is 10 considering it as a extensive level of depth.
     *
     * @param int $groupId The parent group ID
     * @param int $levels  The depth levels
     *
     * @return array The list of ID
     */
    public static function getGroupsByDepthLevel($groupId, $levels = 10)
    {
        $groups = [];
        $groupId = (int) $groupId;

        $groupTable = Database::get_main_table(TABLE_USERGROUP);
        $groupRelGroupTable = Database::get_main_table(TABLE_USERGROUP_REL_USERGROUP);

        $select = 'SELECT ';
        $from = "FROM $groupTable g1 ";

        for ($i = 1; $i <= $levels; $i++) {
            $tableIndexNumber = $i;
            $tableIndexJoinNumber = $i - 1;
            $select .= "g$i.id as id_$i ";
            $select .= $i != $levels ? ', ' : null;

            if (1 == $i) {
                $from .= " INNER JOIN $groupRelGroupTable gg0
                           ON g1.id = gg0.subgroup_id and gg0.group_id = $groupId ";
            } else {
                $from .= "LEFT JOIN $groupRelGroupTable gg$tableIndexJoinNumber ";
                $from .= " ON g$tableIndexJoinNumber.id = gg$tableIndexJoinNumber.group_id ";
                $from .= "LEFT JOIN $groupTable g$tableIndexNumber ";
                $from .= " ON gg$tableIndexJoinNumber.subgroup_id = g$tableIndexNumber.id ";
            }
        }

        $result = Database::query("$select $from");

        while ($item = Database::fetch_assoc($result)) {
            foreach ($item as $myGroupId) {
                if (!empty($myGroupId)) {
                    $groups[] = $myGroupId;
                }
            }
        }

        return array_map('intval', $groups);
    }

    /**
     * Set a parent group.
     *
     * @param int $group_id
     * @param int $parent_group_id if 0, we delete the parent_group association
     * @param int $relation_type
     *
     * @return \Doctrine\DBAL\Statement
     */
    public function setParentGroup($group_id, $parent_group_id, $relation_type = 1)
    {
        $table = Database::get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $group_id = (int) $group_id;
        $parent_group_id = (int) $parent_group_id;
        if (0 == $parent_group_id) {
            $sql = "DELETE FROM $table WHERE subgroup_id = $group_id";
        } else {
            $sql = "SELECT group_id FROM $table WHERE subgroup_id = $group_id";
            $res = Database::query($sql);
            if (0 == Database::num_rows($res)) {
                $sql = "INSERT INTO $table SET
                        group_id = $parent_group_id,
                        subgroup_id = $group_id,
                        relation_type = $relation_type";
            } else {
                $sql = "UPDATE $table SET
                        group_id = $parent_group_id,
                        relation_type = $relation_type
                        WHERE subgroup_id = $group_id";
            }
        }
        $res = Database::query($sql);

        return $res;
    }

    /**
     * Filter the groups/classes info to get a name list only.
     *
     * @param int $userId       The user ID
     * @param int $filterByType Optional. The type of group
     *
     * @return array
     */
    public function getNameListByUser($userId, $filterByType = null)
    {
        $userClasses = $this->getUserGroupListByUser($userId, $filterByType);

        return array_column($userClasses, 'name');
    }

    /**
     * Get the HTML necessary for display the groups/classes name list.
     *
     * @param int $userId       The user ID
     * @param int $filterByType Optional. The type of group
     *
     * @return string
     */
    public function getLabelsFromNameList($userId, $filterByType = null)
    {
        $groupsNameListParsed = $this->getNameListByUser($userId, $filterByType);

        if (empty($groupsNameListParsed)) {
            return '';
        }

        $nameList = '<ul class="list-unstyled">';
        foreach ($groupsNameListParsed as $name) {
            $nameList .= '<li>'.Display::span($name, ['class' => 'label label-info']).'</li>';
        }

        $nameList .= '</ul>';

        return $nameList;
    }

    /**
     * @param array $groupInfo
     *
     * @return bool
     */
    public static function canLeave($groupInfo)
    {
        return 1 == $groupInfo['allow_members_leave_group'] ? true : false;
    }

    /**
     * Check permissions and blocks the page.
     *
     * @param array $userGroupInfo
     * @param bool  $checkAuthor
     * @param bool  $checkCourseIsAllow
     */
    public function protectScript($userGroupInfo = [], $checkAuthor = true, $checkCourseIsAllow = false)
    {
        api_block_anonymous_users();

        if (api_is_platform_admin()) {
            return true;
        }

        if ($checkCourseIsAllow) {
            if (api_is_allowed_to_edit()) {
                return true;
            }
        }

        if ($this->allowTeachers() && api_is_teacher()) {
            if ($checkAuthor && !empty($userGroupInfo)) {
                if (isset($userGroupInfo['author_id']) && $userGroupInfo['author_id'] != api_get_user_id()) {
                    api_not_allowed(true);
                }
            }

            return true;
        } else {
            api_protect_admin_script(true);
            api_protect_limit_for_session_admin();
        }
    }

    public function getGroupsByLp($lpId, $courseId, $sessionId)
    {
        $lpId = (int) $lpId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $sessionCondition = api_get_session_condition($sessionId, true);
        $table = Database::get_course_table(TABLE_LP_REL_USERGROUP);
        $sql = "SELECT usergroup_id FROM $table
                WHERE
                    c_id = $courseId AND
                    lp_id = $lpId
                    $sessionCondition
                    ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    public function getGroupsByLpCategory($categoryId, $courseId, $sessionId)
    {
        $categoryId = (int) $categoryId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $sessionCondition = api_get_session_condition($sessionId, true);

        $table = Database::get_course_table(TABLE_LP_CATEGORY_REL_USERGROUP);
        $sql = "SELECT usergroup_id FROM $table
                WHERE
                    c_id = $courseId AND
                    lp_category_id = $categoryId
                    $sessionCondition
                ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }
}
