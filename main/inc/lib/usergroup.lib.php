<?php

/* For licensing terms, see /license.txt */

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

    /**
     * Set ups DB tables.
     */
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

                    $courseAndSessionList = Tracking::showUserProgress(
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
            if ($type != -1) {
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
        // action links
        $html = '<div class="actions">';
        if (api_is_platform_admin()) {
            $html .= '<a href="../admin/index.php">'.
                Display::return_icon(
                    'back.png',
                    get_lang('BackTo').' '.get_lang('PlatformAdmin'),
                    '',
                    ICON_SIZE_MEDIUM
                ).
                '</a>';
        }

        $html .= '<a href="'.api_get_self().'?action=add">'.
            Display::return_icon('new_class.png', get_lang('AddClasses'), '', ICON_SIZE_MEDIUM).
            '</a>';
        $html .= Display::url(
            Display::return_icon('import_csv.png', get_lang('Import'), [], ICON_SIZE_MEDIUM),
            'usergroup_import.php'
        );
        $html .= Display::url(
            Display::return_icon('export_csv.png', get_lang('Export'), [], ICON_SIZE_MEDIUM),
            'usergroup_export.php'
        );
        $html .= '</div>';
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
                Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', '32').
                '</a>';
        } else {
            echo Display::url(
                Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', '32'),
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
     * @param array $options
     * @param int   $type    0 = classes / 1 = social groups
     *
     * @return array
     */
    public function getUserGroupInCourse($options = [], $type = -1, $getCount = false)
    {
        $select = 'DISTINCT u.*';
        if ($getCount) {
            $select = 'count(u.id) as count';
        }

        $sessionCheck = false;
        if (isset($options['session_id']) && !empty($options['session_id'])) {
            $sessionCheck = true;
        }

        if ($this->getUseMultipleUrl()) {
            if (false === $sessionCheck) {
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
                $sql = "SELECT $select
                        FROM {$this->usergroup_rel_session_table} usergroup
                        INNER JOIN {$this->table} u
                        ON (u.id = usergroup.usergroup_id)
                        INNER JOIN {$this->session_table} s
                        ON (usergroup.session_id = s.id)
                        INNER JOIN {$this->access_url_rel_usergroup} a
                        ON (a.usergroup_id = u.id)
                   ";
            }
        } else {
            if (false === $sessionCheck) {
                $sql = "SELECT $select
                        FROM {$this->usergroup_rel_course_table} usergroup
                        INNER JOIN {$this->table} u
                        ON (u.id = usergroup.usergroup_id)
                        INNER JOIN {$this->table_course} c
                        ON (usergroup.course_id = c.id)
                       ";
            } else {
                $sql = "SELECT $select
                        FROM {$this->usergroup_rel_session_table} usergroup
                        INNER JOIN {$this->table} u
                        ON (u.id = usergroup.usergroup_id)
                        INNER JOIN {$this->session_table} s
                        ON (usergroup.session_id = s.id)
                       ";
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
        $sql .= $conditions;
        $result = Database::query($sql);

        if ($getCount) {
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return (int) $row['count'];
            }

            return 0;
        }

        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * @param array $options
     * @param int   $type
     * @param bool  $getCount
     *
     * @return array|bool
     */
    public function getUserGroupNotInCourse($options = [], $type = -1, $getCount = false)
    {
        $courseId = 0;
        if (isset($options['course_id'])) {
            $courseId = (int) $options['course_id'];
            unset($options['course_id']);
        }

        if (empty($courseId)) {
            return false;
        }

        $select = 'DISTINCT u.*';
        if ($getCount) {
            $select = 'count(u.id) as count';
        }

        $sessionCheck = false;
        $sessionId = 0;
        if (isset($options['session_id']) && !empty($options['session_id'])) {
            $sessionCheck = true;
            $sessionId = (int) $options['session_id'];
        }

        if ($this->getUseMultipleUrl()) {
            if (false === $sessionCheck) {
                $sql = "SELECT $select
                        FROM {$this->table} u
                        INNER JOIN {$this->access_url_rel_usergroup} a
                        ON (a.usergroup_id = u.id)
                        LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                        ON (u.id = urc.usergroup_id AND course_id = $courseId)
                ";
            } else {
                $sql = "SELECT $select
                        FROM {$this->table} u
                        INNER JOIN {$this->access_url_rel_usergroup} a
                        ON (a.usergroup_id = u.id)
                        LEFT OUTER JOIN {$this->usergroup_rel_session_table} urs
                        ON (u.id = urs.usergroup_id AND session_id = $sessionId)
                ";
            }
        } else {
            if (false === $sessionCheck) {
                $sql = "SELECT $select
                        FROM {$this->table} u
                        LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                        ON (u.id = urc.usergroup_id AND course_id = $courseId)
                ";
            } else {
                $sql = "SELECT $select
                        FROM {$this->table} u
                        LEFT OUTER JOIN {$this->usergroup_rel_session_table} urc
                        ON (u.id = urc.usergroup_id AND session_id = $sessionId)
                ";
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
        $sql .= $conditions;
        $result = Database::query($sql);

        if ($getCount) {
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $array = Database::fetch_array($result, 'ASSOC');

                return $array['count'];
            }

            return 0;
        }

        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $data[] = $row;
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

        if (empty($results) && $Session == 0) {
            return false;
        }
        if ((empty($resultSession)) && $Session != 0) {
            return false;
        }

        return true;
    }

    /**
     * Gets a list of session ids by user group.
     *
     * @param int  $id                group id
     * @param bool $returnSessionData Whether to return an array with info (true) or just the session ID (false)
     *
     * @return array
     */
    public function get_sessions_by_usergroup($id, $returnSessionData = false)
    {
        if ($returnSessionData) {
            $results = Database::select(
                'g.session_id, s.name, s.description, s.nbr_users, s.nbr_courses',
                $this->usergroup_rel_session_table." g, ".$this->session_table." s",
                ['where' => ['g.session_id = s.id AND g.usergroup_id = ?' => $id]]
            );
        } else {
            $results = Database::select(
                'session_id',
                $this->usergroup_rel_session_table,
                ['where' => ['usergroup_id = ?' => $id]]
            );
        }

        $array = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                if ($returnSessionData) {
                    $array[$row['session_id']] = $row;
                } else {
                    $array[] = $row['session_id'];
                }
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
     *
     * @return array List of IDs of the sessions added to the usergroup
     */
    public function subscribe_sessions_to_usergroup($usergroup_id, $list, $deleteCurrentSessions = true): array
    {
        $current_list = $this->get_sessions_by_usergroup($usergroup_id);
        $user_list = $this->get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = [];
        if (!empty($list)) {
            foreach ($list as $session_id) {
                if (SessionManager::isValidId($session_id)) {
                    // Only if the session IDs given are not bogus
                    if (!in_array($session_id, $current_list)) {
                        $new_items[] = $session_id;
                    }
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
                $sessions = '';
                foreach ($delete_items as $session_id) {
                    if (!api_get_configuration_value('usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe')) {
                        if (!empty($user_list)) {
                            foreach ($user_list as $user_id) {
                                SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                            }
                        }
                    }
                    Database::delete(
                        $this->usergroup_rel_session_table,
                        ['usergroup_id = ? AND session_id = ?' => [$usergroup_id, $session_id]]
                    );
                    $sessions .= $session_id.',';
                }
                // Add event to system log
                Event::addEvent(
                    LOG_GROUP_PORTAL_SESSION_UNSUBSCRIBED,
                    LOG_GROUP_PORTAL_ID,
                    'gid: '.$usergroup_id.' - sids: '.substr($sessions, 0, -1),
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
            }
        }

        $sessions = [];
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
                    $sessions[] = $session_id;
                }
            }
            // Add event to system log
            Event::addEvent(
                LOG_GROUP_PORTAL_SESSION_SUBSCRIBED,
                LOG_GROUP_PORTAL_ID,
                'gid: '.$usergroup_id.' - sids: '.implode(',', $sessions),
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $sessions;
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

        $courses = [];
        // Adding new relationships
        if (!empty($new_items)) {
            foreach ($new_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if ($course_info) {
                    if (!empty($user_list)) {
                        $messageError = [];
                        $messageOk = [];
                        foreach ($user_list as $user_id) {
                            $subscribed = CourseManager::subscribeUser(
                                $user_id,
                                $course_info['code'],
                                STUDENT,
                                0,
                                0,
                                true,
                                false
                            );
                            $userInfo = api_get_user_info($user_id);
                            if (!$subscribed) {
                                $messageError[] = sprintf(
                                    get_lang('UserXNotSubscribedToCourseX'),
                                    $userInfo['complete_name_with_username'],
                                    $course_info['title']
                                );
                            } else {
                                $messageOk[] = sprintf(
                                    get_lang('UserXAddedToCourseX'),
                                    $userInfo['complete_name_with_username'],
                                    $course_info['title']
                                );
                            }
                        }
                        if (!empty($messageError)) {
                            $strMessagesError = implode('<br>', $messageError);
                            Display::addFlash(
                                Display::return_message(
                                    $strMessagesError,
                                    'error',
                                    false
                                )
                            );
                        }
                        if (!empty($messageOk)) {
                            $strMessagesOk = implode('<br>', $messageOk);
                            Display::addFlash(
                                Display::return_message(
                                    $strMessagesOk,
                                    'normal',
                                    false
                                )
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
                $courses[] = $course_id;
            }
            // Add event to system log
            Event::addEvent(
                LOG_GROUP_PORTAL_COURSE_SUBSCRIBED,
                LOG_GROUP_PORTAL_ID,
                'gid: '.$usergroup_id.' - cids: '.implode(',', $courses),
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $courses;
    }

    /**
     * Unsubscribe a usergroup from a list of courses.
     *
     * @param int   $usergroup_id
     * @param array $delete_items
     */
    public function unsubscribe_courses_from_usergroup($usergroup_id, $delete_items)
    {
        $courses = [];
        // Deleting items.
        if (!empty($delete_items)) {
            $user_list = $this->get_users_by_usergroup($usergroup_id);
            foreach ($delete_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if ($course_info) {
                    if (!api_get_configuration_value('usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe')) {
                        if (!empty($user_list)) {
                            foreach ($user_list as $user_id) {
                                CourseManager::unsubscribe_user(
                                    $user_id,
                                    $course_info['code']
                                );
                            }
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
                    $courses[] = $course_id;
                }
            }
            // Add event to system log
            Event::addEvent(
                LOG_GROUP_PORTAL_COURSE_UNSUBSCRIBED,
                LOG_GROUP_PORTAL_ID,
                'gid: '.$usergroup_id.' - cids: '.implode(',', $courses),
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $courses;
    }

    /**
     * Unsubscribe a usergroup from a list of sessions.
     *
     * @param int   $groupId
     * @param array $items   Session IDs to remove from the group
     *
     * @return array The list of session IDs that have been unsubscribed from the group
     */
    public function unsubscribeSessionsFromUserGroup($groupId, $items)
    {
        // Deleting items.
        $sessions = [];
        if (!empty($items)) {
            $users = $this->get_users_by_usergroup($groupId);
            foreach ($items as $sessionId) {
                if (SessionManager::isValidId($sessionId)) {
                    if (!api_get_configuration_value('usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe')) {
                        if (!empty($users)) {
                            foreach ($users as $userId) {
                                SessionManager::unsubscribe_user_from_session(
                                    $sessionId,
                                    $userId
                                );
                            }
                        }
                    }
                    Database::delete(
                        $this->usergroup_rel_session_table,
                        [
                            'usergroup_id = ? AND session_id = ?' => [
                                $groupId,
                                $sessionId,
                            ],
                        ]
                    );
                    $sessions[] = $sessionId;
                }
            }
            // Add event to system log
            Event::addEvent(
                LOG_GROUP_PORTAL_SESSION_UNSUBSCRIBED,
                LOG_GROUP_PORTAL_ID,
                'gid: '.$groupId.' - sids: '.implode(',', $sessions),
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $sessions;
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
                if (!api_get_configuration_value('usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe')) {
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
                // Add event to system log
                Event::addEvent(
                    LOG_GROUP_PORTAL_USER_UNSUBSCRIBED,
                    LOG_GROUP_PORTAL_ID,
                    'gid: '.$usergroup_id.' - uid: '.$user_id,
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
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
                // Adding courses
                if (!empty($course_list)) {
                    $messageError = [];
                    $messageOk = [];
                    foreach ($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        $subscribed = CourseManager::subscribeUser(
                            $user_id,
                            $course_info['code'],
                            STUDENT,
                            0,
                            0,
                            true,
                            false
                        );
                        $userInfo = api_get_user_info($user_id);
                        if (!$subscribed) {
                            $messageError[] = sprintf(
                                get_lang('UserXNotSubscribedToCourseX'),
                                $userInfo['complete_name_with_username'],
                                $course_info['title']
                            );
                        } else {
                            $messageOk[] = sprintf(
                                get_lang('UserXAddedToCourseX'),
                                $userInfo['complete_name_with_username'],
                                $course_info['title']
                            );
                        }
                    }
                    if (!empty($messageError)) {
                        $strMessagesError = implode('<br>', $messageError);
                        Display::addFlash(
                            Display::return_message(
                                $strMessagesError,
                                'error',
                                false
                            )
                        );
                    }
                    if (!empty($messageOk)) {
                        $strMessagesOk = implode('<br>', $messageOk);
                        Display::addFlash(
                            Display::return_message(
                                $strMessagesOk,
                                'normal',
                                false
                            )
                        );
                    }
                }
                $params = [
                    'user_id' => $user_id,
                    'usergroup_id' => $usergroup_id,
                    'relation_type' => $relationType,
                ];
                Database::insert($this->usergroup_rel_user_table, $params);
                // Add event to system log
                Event::addEvent(
                    LOG_GROUP_PORTAL_USER_SUBSCRIBED,
                    LOG_GROUP_PORTAL_ID,
                    'gid: '.$usergroup_id.' - uid: '.$user_id,
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
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
     * Returns whether teachers can access the classes, as per 'allow_teachers_to_classes' setting.
     *
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
        $and = '';
        if (!empty($options) && !empty($options['where'])) {
            $and = ' AND ';
        }
        if ($this->getUseMultipleUrl()) {
            $urlId = api_get_current_access_url_id();
            $from = $this->table." u
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (u.id = a.usergroup_id)";
            $options['where'][$and.' access_url_id = ? '] = $urlId;
            if ($this->allowTeachers()) {
                $options['where'] = [' AND author_id = ? ' => api_get_user_id()];
            }
            $classes = Database::select('u.id, name, description, group_type, visibility', $from, $options);
        } else {
            if ($this->allowTeachers()) {
                $options['where'] = [$and.' author_id = ? ' => api_get_user_id()];
            }
            $classes = Database::select('id, name, description, group_type, visibility', $this->table, $options);
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

                $courses = $this->get_courses_by_usergroup($data['id'], true);
                $coursesToString = '';
                if (!empty($courses)) {
                    $coursesToString = implode(', ', array_column($courses, 'code'));
                }

                $sessions = $this->get_sessions_by_usergroup($data['id']);
                $sessionsToString = '';
                if (!empty($sessions)) {
                    $sessionList = [];
                    foreach ($sessions as $sessionId) {
                        $sessionList[] = api_get_session_info($sessionId)['name'];
                    }
                    $sessionsToString = implode(', ', $sessionList);
                }

                $data['users'] = $userToString;
                $data['courses'] = $coursesToString;
                $data['sessions'] = $sessionsToString;
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
        $sql = "SELECT * FROM $this->table g WHERE g.id NOT IN ('$listToString')";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param $params
     * @param bool $show_query
     *
     * @return bool|int
     */
    public function save($params, $show_query = false)
    {
        $params['updated_at'] = $params['created_at'] = api_get_utc_datetime();
        $params['group_type'] = !empty($params['group_type']) ? self::SOCIAL_CLASS : self::NORMAL_CLASS;
        $params['allow_members_leave_group'] = isset($params['allow_members_leave_group']) ? 1 : 0;

        $groupExists = $this->usergroup_exists(trim($params['name']));
        if (false == $groupExists) {
            if ($this->allowTeachers()) {
                $params['author_id'] = api_get_user_id();
            }
            $id = parent::save($params, $show_query);
            if ($id) {
                if ($this->getUseMultipleUrl()) {
                    $this->subscribeToUrl($id, api_get_current_access_url_id());
                }

                if (self::SOCIAL_CLASS == $params['group_type']) {
                    $this->add_user_to_group(
                        api_get_user_id(),
                        $id,
                        GROUP_USER_PERMISSION_ADMIN
                    );
                }
                $picture = isset($_FILES['picture']) ? $_FILES['picture'] : null;
                $picture = $this->manageFileUpload($id, $picture);
                if ($picture) {
                    $params = [
                        'id' => $id,
                        'picture' => $picture,
                        'group_type' => $params['group_type'],
                    ];
                    $this->update($params);
                }
            }
            // Add event to system log
            Event::addEvent(
                LOG_GROUP_PORTAL_CREATED,
                LOG_GROUP_PORTAL_ID,
                'id: '.$id,
                api_get_utc_datetime(),
                api_get_user_id()
            );

            return $id;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function update($values, $showQuery = false)
    {
        $values['updated_on'] = api_get_utc_datetime();
        $values['group_type'] = !empty($values['group_type']) ? self::SOCIAL_CLASS : self::NORMAL_CLASS;
        $values['allow_members_leave_group'] = isset($values['allow_members_leave_group']) ? 1 : 0;

        if (isset($values['id'])) {
            $picture = isset($_FILES['picture']) ? $_FILES['picture'] : null;
            if (!empty($picture)) {
                $picture = $this->manageFileUpload($values['id'], $picture);
                if ($picture) {
                    $values['picture'] = $picture;
                }
            }

            if (isset($values['delete_picture'])) {
                $values['picture'] = null;
            }
        }

        parent::update($values, $showQuery);

        if (isset($values['delete_picture'])) {
            $this->delete_group_picture($values['id']);
        }
        // Add event to system log
        Event::addEvent(
            LOG_GROUP_PORTAL_UPDATED,
            LOG_GROUP_PORTAL_ID,
            'id: '.$values['id'],
            api_get_utc_datetime(),
            api_get_user_id()
        );

        return true;
    }

    /**
     * @param int    $groupId
     * @param string $picture
     *
     * @return bool|string
     */
    public function manageFileUpload($groupId, $picture)
    {
        if (!empty($picture['name'])) {
            return $this->update_group_picture(
                $groupId,
                $picture['name'],
                $picture['tmp_name']
            );
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
        return $this->update_group_picture($groupId);
    }

    /**
     * Creates new group pictures in various sizes of a user, or deletes user pfotos.
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php.
     *
     * @param    int    The group id
     * @param string $file The common file name for the newly created photos.
     *                     It will be checked and modified for compatibility with the file system.
     *                     If full name is provided, path component is ignored.
     *                     If an empty name is provided, then old user photos are deleted only,
     *
     * @see UserManager::delete_user_picture() as the prefered way for deletion.
     *
     * @param string $source_file the full system name of the image from which user photos will be created
     *
     * @return mixed Returns the resulting common file name of created images which usually should be stored in database.
     *               When an image is removed the function returns an empty string.
     *               In case of internal error or negative validation it returns FALSE.
     */
    public function update_group_picture($group_id, $file = null, $source_file = null)
    {
        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return false;
        }
        $delete = empty($file);
        if (empty($source_file)) {
            $source_file = $file;
        }

        // User-reserved directory where photos have to be placed.
        $path_info = $this->get_group_picture_path_by_id($group_id, 'system', true);

        $path = $path_info['dir'];

        // If this directory does not exist - we create it.
        if (!is_dir($path)) {
            $res = @mkdir($path, api_get_permissions_for_new_directories(), true);
            if ($res === false) {
                // There was an issue creating the directory $path, probably
                // permissions-related
                return false;
            }
        }

        // The old photos (if any).
        $old_file = $path_info['file'];

        // Let us delete them.
        if (!empty($old_file)) {
            if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) {
                $prefix = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_';
                @rename($path.'small_'.$old_file, $path.$prefix.'small_'.$old_file);
                @rename($path.'medium_'.$old_file, $path.$prefix.'medium_'.$old_file);
                @rename($path.'big_'.$old_file, $path.$prefix.'big_'.$old_file);
                @rename($path.$old_file, $path.$prefix.$old_file);
            } else {
                @unlink($path.'small_'.$old_file);
                @unlink($path.'medium_'.$old_file);
                @unlink($path.'big_'.$old_file);
                @unlink($path.$old_file);
            }
        }

        // Exit if only deletion has been requested. Return an empty picture name.
        if ($delete) {
            return '';
        }

        // Validation 2.
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file = str_replace('\\', '/', $file);
        $filename = (($pos = strrpos($file, '/')) !== false) ? substr($file, $pos + 1) : $file;
        $extension = strtolower(substr(strrchr($filename, '.'), 1));
        if (!in_array($extension, $allowed_types)) {
            return false;
        }

        // This is the common name for the new photos.
        if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE && !empty($old_file)) {
            $old_extension = strtolower(substr(strrchr($old_file, '.'), 1));
            $filename = in_array($old_extension, $allowed_types) ? substr($old_file, 0, -strlen($old_extension)) : $old_file;
            $filename = (substr($filename, -1) == '.') ? $filename.$extension : $filename.'.'.$extension;
        } else {
            $filename = api_replace_dangerous_char($filename);
            if (PREFIX_IMAGE_FILENAME_WITH_UID) {
                $filename = uniqid('').'_'.$filename;
            }
            // We always prefix user photos with user ids, so on setting
            // api_get_setting('split_users_upload_directory') === 'true'
            // the correspondent directories to be found successfully.
            $filename = $group_id.'_'.$filename;
        }

        // Storing the new photos in 4 versions with various sizes.

        /*$image->resize(
        // get original size and set width (widen) or height (heighten).
        // width or height will be set maintaining aspect ratio.
            $image->getSize()->widen( 700 )
        );*/

        // Usign the Imagine service
        $imagine = new Imagine\Gd\Imagine();
        $image = $imagine->open($source_file);

        $options = [
            'quality' => 90,
        ];

        //$image->resize(new Imagine\Image\Box(200, 200))->save($path.'big_'.$filename);
        $image->resize($image->getSize()->widen(200))->save($path.'big_'.$filename, $options);

        $image = $imagine->open($source_file);
        $image->resize(new Imagine\Image\Box(85, 85))->save($path.'medium_'.$filename, $options);

        $image = $imagine->open($source_file);
        $image->resize(new Imagine\Image\Box(22, 22))->save($path.'small_'.$filename);

        /*
        $small  = self::resize_picture($source_file, 22);
        $medium = self::resize_picture($source_file, 85);
        $normal = self::resize_picture($source_file, 200);

        $big = new Image($source_file); // This is the original picture.
        $ok = $small && $small->send_image($path.'small_'.$filename)
            && $medium && $medium->send_image($path.'medium_'.$filename)
            && $normal && $normal->send_image($path.'big_'.$filename)
            && $big && $big->send_image($path.$filename);
        return $ok ? $filename : false;*/
        return $filename;
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

        $res = parent::delete($id);
        // Add event to system log
        if ($res) {
            Event::addEvent(
                LOG_GROUP_PORTAL_DELETED,
                LOG_GROUP_PORTAL_ID,
                'id: '.$id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $res;
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
     * @param array         $data
     */
    public function setForm($form, $type = 'add', $data = [])
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
        $form->addText('name', get_lang('Name'), true, ['maxlength' => 255]);
        $form->addRule('name', '', 'maxlength', 255);

        // Description
        $form->addTextarea('description', get_lang('Description'), ['cols' => 58]);
        $form->applyFilter('description', 'trim');

        if ($this->showGroupTypeSetting) {
            $form->addElement(
                'checkbox',
                'group_type',
                null,
                get_lang('SocialGroup')
            );
        }

        // url
        $form->addText('url', get_lang('Url'), false);

        // Picture
        $allowed_picture_types = $this->getAllowedPictureExtensions();

        $form->addFile('picture', get_lang('AddPicture'));
        $form->addRule(
            'picture',
            get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
            'filetype',
            $allowed_picture_types
        );

        if (isset($data['picture']) && strlen($data['picture']) > 0) {
            $picture = $this->get_picture_group($data['id'], $data['picture'], 80);
            $img = '<img src="'.$picture['file'].'" />';
            $form->addLabel(null, $img);
            $form->addElement('checkbox', 'delete_picture', '', get_lang('DelImage'));
        }

        $form->addElement('select', 'visibility', get_lang('GroupPermissions'), $this->getGroupStatusList());
        $form->setRequiredNote('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>');
        $form->addElement('checkbox', 'allow_members_leave_group', '', get_lang('AllowMemberLeaveGroup'));

        // Setting the form elements
        if ($type === 'add') {
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
     * @param string $size_picture picture size it can be small_,  medium_  or  big_
     * @param string style css
     *
     * @return array with the file and the style of an image i.e $array['file'] $array['style']
     */
    public function get_picture_group(
        $id,
        $picture_file,
        $height,
        $size_picture = GROUP_IMAGE_SIZE_MEDIUM,
        $style = ''
    ) {
        $picture = [];
        if ($picture_file === 'unknown.jpg') {
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

        return $picture;
    }

    /**
     * Gets the group picture URL or path from group ID (returns an array).
     * The return format is a complete path, enabling recovery of the directory
     * with dirname() or the file with basename(). This also works for the
     * functions dealing with the user's productions, as they are located in
     * the same directory.
     *
     * @param    int    User ID
     * @param    string    Type of path to return (can be 'none', 'system', 'rel', 'web')
     * @param    bool    Whether we want to have the directory name returned 'as if'
     * there was a file or not (in the case we want to know which directory to create -
     * otherwise no file means no split subdir)
     * @param    bool    If we want that the function returns the /main/img/unknown.jpg image set it at true
     *
     * @return array Array of 2 elements: 'dir' and 'file' which contain the dir
     *               and file as the name implies if image does not exist it will return the unknown
     *               image if anonymous parameter is true if not it returns an empty er's
     */
    public function get_group_picture_path_by_id($id, $type = 'none', $preview = false, $anonymous = false)
    {
        switch ($type) {
            case 'system': // Base: absolute system path.
                $base = api_get_path(SYS_UPLOAD_PATH);
                break;
            case 'rel': // Base: semi-absolute web path (no server base).
                $base = api_get_path(REL_CODE_PATH);
                break;
            case 'web': // Base: absolute web path.
                $base = api_get_path(WEB_UPLOAD_PATH);
                break;
            case 'none':
            default: // Base: empty, the result path below will be relative.
                $base = '';
        }
        $id = (int) $id;

        if (empty($id) || empty($type)) {
            return $anonymous ? ['dir' => $base.'img/', 'file' => 'unknown.jpg'] : ['dir' => '', 'file' => ''];
        }

        $group_table = Database::get_main_table(TABLE_USERGROUP);
        $sql = "SELECT picture FROM $group_table WHERE id = ".$id;
        $res = Database::query($sql);

        if (!Database::num_rows($res)) {
            return $anonymous ? ['dir' => $base.'img/', 'file' => 'unknown.jpg'] : ['dir' => '', 'file' => ''];
        }
        $user = Database::fetch_array($res);
        $picture_filename = trim($user['picture']);

        if (api_get_setting('split_users_upload_directory') === 'true') {
            if (!empty($picture_filename)) {
                $dir = $base.'groups/'.substr($picture_filename, 0, 1).'/'.$id.'/';
            } elseif ($preview) {
                $dir = $base.'groups/'.substr((string) $id, 0, 1).'/'.$id.'/';
            } else {
                $dir = $base.'groups/'.$id.'/';
            }
        } else {
            $dir = $base.'groups/'.$id.'/';
        }

        return ['dir' => $dir, 'file' => $picture_filename];
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
                $roleToString = get_lang('PendingInvitation');
                break;
            case GROUP_USER_PERMISSION_MODERATOR:
                $roleToString = get_lang('Moderator');
                break;
            case GROUP_USER_PERMISSION_HRM:
                $roleToString = get_lang('Drh');
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
                $usersList = '';
                foreach ($user_list as $user_id) {
                    $user_id = (int) $user_id;
                    $group_id = (int) $group_id;

                    $role = $this->get_user_group_role($user_id, $group_id);
                    if ($role == 0) {
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
                    $usersList .= $user_id.',';
                }
                // Add event to system log
                Event::addEvent(
                    LOG_GROUP_PORTAL_USER_SUBSCRIBED,
                    LOG_GROUP_PORTAL_ID,
                    'gid: '.$group_id.' - uids: '.substr($usersList, 0, -1),
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
            }
        }

        return $result_array;
    }

    /**
     * Deletes the subscription of a user to a usergroup.
     *
     * @author Julio Montoya
     *
     * @param int $userId
     * @param int $groupId
     *
     * @return bool true on success
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
        // Add event to system log
        Event::addEvent(
            LOG_GROUP_PORTAL_USER_UNSUBSCRIBED,
            LOG_GROUP_PORTAL_ID,
            'gid: '.$groupId.' - uid: '.$userId,
            api_get_utc_datetime(),
            api_get_user_id()
        );

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
        $user_id = (int) $user_id;
        $group_id = (int) $group_id;
        $relation_type = (int) $relation_type;
        if (!empty($user_id) && !empty($group_id)) {
            $role = $this->get_user_group_role($user_id, $group_id);

            if ($role == 0) {
                $sql = "INSERT INTO $table_url_rel_group
           				SET
           				    user_id = ".$user_id.",
           				    usergroup_id = ".$group_id.",
           				    relation_type = ".$relation_type;
                Database::query($sql);
                // Add event to system log
                Event::addEvent(
                    LOG_GROUP_PORTAL_USER_SUBSCRIBED,
                    LOG_GROUP_PORTAL_ID,
                    'gid: '.$group_id.' - uid: '.$user_id,
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
            } elseif ($role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
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
     * @return array Database::store_result of the result
     *
     * @author Julio Montoya
     * */
    public function get_groups_by_user($user_id, $relationType = GROUP_USER_PERMISSION_READER, $with_image = false)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;
        $user_id = (int) $user_id;

        if ($relationType == 0) {
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

    /**
     * Gets the inner join of users and group table.
     *
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

    /**
     * Gets the last groups created.
     *
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
        $limit = null
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

        if (count($relation_type) == 0) {
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

        $sql = "SELECT u.id, u.firstname, u.lastname, gu.relation_type
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
                $relation_group_title = get_lang('IAmAReader');
                $links .= '<li class="'.($show == 'invite_friends' ? 'active' : '').'"><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('InviteFriends')).get_lang('InviteFriends').'</a></li>';
                if (self::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                        Display::return_icon('group_leave.png', get_lang('LeaveGroup')).get_lang('LeaveGroup').'</a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_ADMIN:
                $relation_group_title = get_lang('IAmAnAdmin');
                $links .= '<li class="'.($show == 'group_edit' ? 'active' : '').'"><a href="group_edit.php?id='.$group_id.'">'.
                            Display::return_icon('group_edit.png', get_lang('EditGroup')).get_lang('EditGroup').'</a></li>';
                $links .= '<li class="'.($show == 'member_list' ? 'active' : '').'"><a href="group_waiting_list.php?id='.$group_id.'">'.
                            Display::return_icon('waiting_list.png', get_lang('WaitingList')).get_lang('WaitingList').'</a></li>';
                $links .= '<li class="'.($show == 'invite_friends' ? 'active' : '').'"><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('InviteFriends')).get_lang('InviteFriends').'</a></li>';
                if (self::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                        Display::return_icon('group_leave.png', get_lang('LeaveGroup')).get_lang('LeaveGroup').'</a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION:
//				$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('YouHaveBeenInvitedJoinNow'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouHaveBeenInvitedJoinNow').'</span></a></li>';
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
                $relation_group_title = get_lang('WaitingForAdminResponse');
                break;
            case GROUP_USER_PERMISSION_MODERATOR:
                $relation_group_title = get_lang('IAmAModerator');
                //$links .=  '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('NewTopic').'</span></a></li>';
                //$links .=  '<li><a href="groups.php?id='.$group_id.'">'.				Display::return_icon('message_list.png', get_lang('MessageList'), array('hspace'=>'6')).'<span class="'.($show=='messages_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MessageList').'</span></a></li>';
                //$links .=  '<li><a href="group_members.php?id='.$group_id.'">'.		Display::return_icon('member_list.png', get_lang('MemberList'), array('hspace'=>'6')).'<span class="'.($show=='member_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MemberList').'</span></a></li>';
                if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED) {
                    $links .= '<li><a href="group_waiting_list.php?id='.$group_id.'">'.
                                Display::return_icon('waiting_list.png', get_lang('WaitingList')).get_lang('WaitingList').'</a></li>';
                }
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('InviteFriends')).get_lang('InviteFriends').'</a></li>';
                if (self::canLeave($group_info)) {
                    $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                        Display::return_icon('group_leave.png', get_lang('LeaveGroup')).get_lang('LeaveGroup').'</a></li>';
                }
                break;
            case GROUP_USER_PERMISSION_HRM:
                $relation_group_title = get_lang('IAmAHRM');
                $links .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="ajax" title="'.get_lang('ComposeMessage').'" data-size="lg" data-title="'.get_lang('ComposeMessage').'">'.
                            Display::return_icon('new-message.png', get_lang('NewTopic')).get_lang('NewTopic').'</a></li>';
                $links .= '<li><a href="group_view.php?id='.$group_id.'">'.
                            Display::return_icon('message_list.png', get_lang('MessageList')).get_lang('MessageList').'</a></li>';
                $links .= '<li><a href="group_invitation.php?id='.$group_id.'">'.
                            Display::return_icon('invitation_friend.png', get_lang('InviteFriends')).get_lang('InviteFriends').'</a></li>';
                $links .= '<li><a href="group_members.php?id='.$group_id.'">'.
                            Display::return_icon('member_list.png', get_lang('MemberList')).get_lang('MemberList').'</a></li>';
                $links .= '<li><a href="group_view.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.
                            Display::return_icon('delete_data.gif', get_lang('LeaveGroup')).get_lang('LeaveGroup').'</a></li>';
                break;
            default:
                //$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('JoinGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('JoinGroup').'</a></span></li>';
                break;
        }
        if (!empty($links)) {
            $list = '<ul class="nav nav-pills">';
            $list .= $links;
            $list .= '</ul>';
            $html .= Display::panelCollapse(
                get_lang('SocialGroups'),
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
     * @param string $relation_type
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
        $user_id = intval($user_id);

        if ($relation_type == 0) {
            $where_relation_condition = '';
        } else {
            $relation_type = intval($relation_type);
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
    public function get_all_group_tags($tag = '', $from = 0, $number_of_items = 10, $getCount = false)
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

        $from = (int) $from;
        $number_of_items = (int) $number_of_items;
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
            if ($i == 1) {
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

            if ($i == 1) {
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
        if ($parent_group_id == 0) {
            $sql = "DELETE FROM $table WHERE subgroup_id = $group_id";
        } else {
            $sql = "SELECT group_id FROM $table WHERE subgroup_id = $group_id";
            $res = Database::query($sql);
            if (Database::num_rows($res) == 0) {
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
        return $groupInfo['allow_members_leave_group'] == 1 ? true : false;
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

    /**
     * Check the given ID matches an existing group
     * @param int $groupId
     * @return bool
     */
    public function groupExists(int $groupId) {
        $sql = "SELECT id FROM ".$this->table. " WHERE id = ".$groupId;
        $result = Database::query($sql);
        if (Database::num_rows($result) === 1) {
            return true;
        }

        return false;
    }
    /**
     * Check the given ID matches an existing user
     * @param int $userId
     * @return bool
     */
    public function userExists(int $userId) {
        $sql = "SELECT id FROM ".$this->table_user. " WHERE id = ".$userId;
        $result = Database::query($sql);
        if (Database::num_rows($result) === 1) {
            return true;
        }

        return false;
    }
}
