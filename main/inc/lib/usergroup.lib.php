<?php
/* For licensing terms, see /license.txt */

/**
 * Class UserGroup
 *
 * This class provides methods for the UserGroup management.
 * Include/require it in your code to use its features.
 * @package chamilo.library
 *
 */
class UserGroup extends Model
{
    public $columns = array(
        'id',
        'name',
        'description',
        'group_type',
        'picture',
        'url',
        'allow_members_leave_group',
        'visibility',
        'updated_at',
        'created_at'
    );

    public $useMultipleUrl = false;

    const SOCIAL_CLASS = 1;
    const NORMAL_CLASS = 0;
    public $groupType = 0;
    public $showGroupTypeSetting = false;

    /**
     * Set ups DB tables
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_USERGROUP);
        $this->usergroup_rel_user_table = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $this->usergroup_rel_course_table = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
        $this->usergroup_rel_session_table = Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
        $this->access_url_rel_usergroup = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);
        $this->table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $this->table_user = Database::get_main_table(TABLE_MAIN_USER);
        $this->useMultipleUrl = api_get_configuration_value('multiple_access_urls');
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
        $row = Database::select('count(*) as count', $this->table, array(), 'first');

        return $row['count'];
    }

    /**
     * @param int $type
     *
     * @return int
     */
    public function get_count($type = -1)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT count(u.id) as count FROM ".$this->table." u
                    INNER JOIN ".$this->access_url_rel_usergroup." a
                        ON (u.id = a.usergroup_id)
                    WHERE access_url_id = $urlId
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
                $type = intval($type);
                $typeCondition = " WHERE group_type = $type ";
            }

            $sql = "SELECT count(a.id) as count
                    FROM {$this->table} a
                    $typeCondition
            ";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);
                return $row['count'];
            }
        }
    }

    /**
     * @param int $course_id
     * @param int $type
     *
     * @return mixed
     */
    public function getUserGroupByCourseWithDataCount($course_id, $type = -1)
    {
        if ($this->useMultipleUrl) {
            $course_id = intval($course_id);
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
                $type = intval($type);
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
     * @return mixed
     */
    public function get_id_by_name($name)
    {
        $row = Database::select('id', $this->table, array('where' => array('name = ?' => $name)), 'first');

        return $row['id'];
    }

    /**
     * Displays the title + grid
     */
    public function display()
    {
        // action links
        echo '<div class="actions">';
        echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', '32').'</a>';
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('new_class.png', get_lang('AddClasses'), '', '32').'</a>';
        echo Display::url(Display::return_icon('import_csv.png', get_lang('Import'), array(), ICON_SIZE_MEDIUM), 'usergroup_import.php');
        echo Display::url(Display::return_icon('export_csv.png', get_lang('Export'), array(), ICON_SIZE_MEDIUM), 'usergroup_export.php');
        echo '</div>';
        echo Display::grid_html('usergroups');
    }

    /**
     * Get HTML grid
     */
    public function display_teacher_view()
    {
        echo Display::grid_html('usergroups');
    }

    /**
     * Gets a list of course ids by user group
     * @param int $id user group id
     * @param array $loadCourseData
     *
     * @return  array
     */
    public function get_courses_by_usergroup($id, $loadCourseData = false)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $from = $this->usergroup_rel_course_table." c
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = c.usergroup_id) ";
            $whereConditionSql = 'a.usergroup_id = ? AND access_url_id = ? ';
            $whereConditionValues = array($id, $urlId);
        } else {
            $whereConditionSql = 'usergroup_id = ?';
            $whereConditionValues = array($id);
            $from = $this->usergroup_rel_course_table." c ";
        }

        if ($loadCourseData) {
            $from .= " INNER JOIN {$this->table_course} as course ON c.course_id = course.id";
        }

        /*
        if (!empty($conditionsLike)) {
            $from .= " INNER JOIN {$this->table_course} as course ON c.course_id = course.id";
            $conditionSql = array();
            foreach ($conditionsLike as $field => $value) {
                $conditionSql[] = $field.' LIKE %?%';
                $whereConditionValues[] = $value;
            }
            $whereConditionSql .= ' AND '.implode(' AND ', $conditionSql);
        }*/

        $where = array('where' => array($whereConditionSql => $whereConditionValues));

        if ($loadCourseData) {
            $select = 'course.*';
        } else {
            $select = 'course_id';
        }

        $results = Database::select(
            $select,
            $from,
            $where
        );

        $array = array();
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
     * @param array $options
     *
     * @return array
     */
    public function getUserGroupInCourse($options = array(), $type = -1)
    {
        if ($this->useMultipleUrl) {
            $sql = "SELECT u.* FROM {$this->usergroup_rel_course_table} usergroup
                    INNER JOIN  {$this->table} u
                    ON (u.id = usergroup.usergroup_id)
                    INNER JOIN {$this->table_course} c
                    ON (usergroup.course_id = c.id)
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = u.id)
                   ";
        } else {
            $sql = "SELECT u.* FROM {$this->usergroup_rel_course_table} usergroup
                    INNER JOIN  {$this->table} u
                    ON (u.id = usergroup.usergroup_id)
                    INNER JOIN {$this->table_course} c
                    ON (usergroup.course_id = c.id)
                   ";
        }

        $conditions = Database::parse_conditions($options);

        $typeCondition = '';
        if ($type != -1) {
            $type = intval($type);
            $typeCondition = " AND group_type = $type ";
        }

        if (empty($conditions)) {
            $conditions .= "WHERE 1 = 1 $typeCondition ";
        } else {
            $conditions .= " $typeCondition ";
        }

        $sql .= $conditions;

        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $sql .= " AND access_url_id = $urlId ";
        }


        if (isset($options['LIMIT'])) {
            $limits = explode(',', $options['LIMIT']);
            $limits = array_map('intval', $limits);
            if (isset($limits[0]) && isset($limits[1])) {
                $sql .= " LIMIT ".$limits[0].', '.$limits[1];
            }
        }

        $result = Database::query($sql);
        $array = Database::store_result($result, 'ASSOC');

        return $array;
    }

    /**
     * @param array $options
     * @param int   $type
     *
     * @return array|bool
     */
    public function getUserGroupNotInCourse($options = array(), $type = -1)
    {
        $course_id = null;
        if (isset($options['course_id'])) {
            $course_id = intval($options['course_id']);
            unset($options['course_id']);
        }

        if (empty($course_id)) {
            return false;
        }

        $typeCondition = '';
        if ($type != -1) {
            $type = intval($type);
            $typeCondition = " AND group_type = $type ";
        }

        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT DISTINCT u.*
                    FROM {$this->table} u
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = u.id)
                    LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                    ON (u.id = urc.usergroup_id AND course_id = $course_id)
            ";
        } else {
            $sql = "SELECT DISTINCT u.*
                    FROM {$this->table} u
                    LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                    ON (u.id = urc.usergroup_id AND course_id = $course_id)
            ";
        }
        $conditions = Database::parse_conditions($options);

        if (empty($conditions)) {
            $conditions .= "WHERE 1 = 1 $typeCondition ";
        } else {
            $conditions .= " $typeCondition ";
        }

        $sql .= $conditions;

        if ($this->useMultipleUrl) {
            $sql .= " AND access_url_id = $urlId";
        }

        if (isset($options['LIMIT'])) {
            $limits = explode(',', $options['LIMIT']);
            $limits = array_map('intval', $limits);
            if (isset($limits[0]) && isset($limits[1])) {
                $sql .= " LIMIT ".$limits[0].', '.$limits[1];
            }
        }

        $result = Database::query($sql);
        $array = Database::store_result($result, 'ASSOC');

        return $array;
    }

    /**
     * @param int $course_id
     * @return array
     */
    public function get_usergroup_by_course($course_id)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $options = array(
                'where' => array(
                    'c.course_id = ? AND access_url_id = ?' => array(
                        $course_id,
                        $urlId,
                    ),
                ),
            );
            $from = $this->usergroup_rel_course_table." as c INNER JOIN ".$this->access_url_rel_usergroup." a
                    ON c.usergroup_id = a.usergroup_id";
        } else {
            $options = array('where' => array('c.course_id = ?' => $course_id));
            $from = $this->usergroup_rel_course_table." c";
        }

        $results = Database::select('c.usergroup_id', $from, $options);
        $array = array();
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
     * @return bool
     */
    public function usergroup_was_added_in_course($usergroup_id, $course_id)
    {
        $results = Database::select(
            'usergroup_id',
            $this->usergroup_rel_course_table,
            array('where' => array('course_id = ? AND usergroup_id = ?' => array($course_id, $usergroup_id)))
        );

        if (empty($results)) {
            return false;
        }

        return true;
    }

    /**
     * Gets a list of session ids by user group
     * @param   int  $id   user group id
     * @return  array
     */
    public function get_sessions_by_usergroup($id)
    {
        $results = Database::select(
            'session_id',
            $this->usergroup_rel_session_table,
            array('where' => array('usergroup_id = ?' => $id))
        );

        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['session_id'];
            }
        }

        return $array;
    }

    /**
     * Gets a list of user ids by user group
     * @param   int    $id user group id
     * @return  array   with a list of user ids
     */
    public function get_users_by_usergroup($id = null, $relationList = [])
    {
        $relationCondition = '';
        if (!empty($relationList)) {
            $relationListToString = implode("', '", $relationList);
            $relationCondition = " AND relation_type IN('$relationListToString')";
        }

        if (empty($id)) {
            $conditions = array();
        } else {
            $conditions = array('where' => array("usergroup_id = ? $relationCondition "=> $id));
        }
        $results = Database::select(
            'user_id',
            $this->usergroup_rel_user_table,
            $conditions
        );
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['user_id'];
            }
        }

        return $array;
    }

    /**
     * Gets a list of user ids by user group
     * @param   int    $id user group id
     * @return  array   with a list of user ids
     */
    public function getUsersByUsergroupAndRelation($id, $relation = '')
    {
        $conditions = array('where' => array('usergroup_id = ? AND relation_type = ?' => [$id, $relation]));
        $results = Database::select(
            'user_id',
            $this->usergroup_rel_user_table,
            $conditions
        );

        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['user_id'];
            }
        }

        return $array;
    }

    /**
     * Get the group list for a user
     * @param int $userId The user ID
     * @param int $filterByType Optional. The type of group
     * @return array
     */
    public function getUserGroupListByUser($userId, $filterByType = null)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $from = $this->usergroup_rel_user_table." u
                INNER JOIN {$this->access_url_rel_usergroup} a
                ON (a.usergroup_id AND u.usergroup_id)
                INNER JOIN {$this->table} g
                ON (u.usergroup_id = g.id)
                ";
            $where = array('where' => array('user_id = ? AND access_url_id = ? ' => array($userId, $urlId)));
        } else {
            $from = $this->usergroup_rel_user_table." u
                INNER JOIN {$this->table} g
                ON (u.usergroup_id = g.id)
                ";
            $where = array('where' => array('user_id = ?' => $userId));
        }

        if ($filterByType !== null) {
            $where['where'][' AND g.group_type = ?'] = intval($filterByType);
        }

        $results = Database::select(
            'g.*',
            $from,
            $where
        );
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row;
            }
        }

        return $array;
    }

    /**
     * Gets the usergroup id list by user id
     * @param   int $userId user id
     * @return array
     */
    public function get_usergroup_by_user($userId)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $from = $this->usergroup_rel_user_table." u
                    INNER JOIN {$this->access_url_rel_usergroup} a ON (a.usergroup_id AND u.usergroup_id)";
            $where = array('where' => array('user_id = ? AND access_url_id = ? ' => array($userId, $urlId)));
        } else {
            $from = $this->usergroup_rel_user_table." u ";
            $where = array('where' => array('user_id = ?' => $userId));
        }

        $results = Database::select(
            'u.usergroup_id',
            $from,
            $where
        );
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['usergroup_id'];
            }
        }

        return $array;
    }

    /**
     * Subscribes sessions to a group  (also adding the members of the group in the session and course)
     * @param   int   $usergroup_id  usergroup id
     * @param   array  $list list of session ids
     */
    public function subscribe_sessions_to_usergroup($usergroup_id, $list)
    {
        $current_list = self::get_sessions_by_usergroup($usergroup_id);
        $user_list = self::get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = array();
        if (!empty($list)) {
            foreach ($list as $session_id) {
                if (!in_array($session_id, $current_list)) {
                    $new_items[] = $session_id;
                }
            }
        }
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
                    array('usergroup_id = ? AND session_id = ?' => array($usergroup_id, $session_id))
                );
            }
        }

        // Adding new relationships.
        if (!empty($new_items)) {
            foreach ($new_items as $session_id) {
                $params = array('session_id' => $session_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_session_table, $params);

                if (!empty($user_list)) {
                    SessionManager::subscribe_users_to_session($session_id, $user_list, null, false);
                }
            }
        }
    }

    /**
     * Subscribes courses to a group (also adding the members of the group in the course)
     * @param int   $usergroup_id  usergroup id
     * @param array $list  list of course ids (integers)
     * @param bool $delete_groups
     */
    public function subscribe_courses_to_usergroup($usergroup_id, $list, $delete_groups = true)
    {
        $current_list = self::get_courses_by_usergroup($usergroup_id);
        $user_list = self::get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = array();
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
            self::unsubscribe_courses_from_usergroup($usergroup_id, $delete_items);
        }

        // Adding new relationships
        if (!empty($new_items)) {
            foreach ($new_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if ($course_info) {
                    if (!empty($user_list)) {
                        foreach ($user_list as $user_id) {
                            CourseManager::subscribe_user(
                                $user_id,
                                $course_info['code']
                            );
                        }
                    }
                    $params = array(
                        'course_id' => $course_id,
                        'usergroup_id' => $usergroup_id,
                    );
                    Database::insert(
                        $this->usergroup_rel_course_table,
                        $params
                    );
                }
            }
        }
    }

    /**
     * @param int $usergroup_id
     * @param array $delete_items
     */
    public function unsubscribe_courses_from_usergroup($usergroup_id, $delete_items)
    {
        // Deleting items.
        if (!empty($delete_items)) {
            $user_list = self::get_users_by_usergroup($usergroup_id);
            if (!empty($user_list)) {
                foreach ($delete_items as $course_id) {
                    $course_info = api_get_course_info_by_id($course_id);
                    if ($course_info) {
                        foreach ($user_list as $user_id) {
                            CourseManager::unsubscribe_user(
                                $user_id,
                                $course_info['code']
                            );
                        }
                        Database::delete(
                            $this->usergroup_rel_course_table,
                            array(
                                'usergroup_id = ? AND course_id = ?' => array(
                                    $usergroup_id,
                                    $course_id
                                )
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Subscribe users to a group
     * @param int     $usergroup_id usergroup id
     * @param array   $list list of user ids     *
     * @param bool $delete_users_not_present_in_list
     * @param array $relationType
     */
    public function subscribe_users_to_usergroup($usergroup_id, $list, $delete_users_not_present_in_list = true, $relationType = '')
    {
        $current_list = self::get_users_by_usergroup($usergroup_id);
        $course_list = self::get_courses_by_usergroup($usergroup_id);
        $session_list = self::get_sessions_by_usergroup($usergroup_id);

        $delete_items = array();
        $new_items = array();

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
                Database::delete(
                    $this->usergroup_rel_user_table,
                    array('usergroup_id = ? AND user_id = ? AND relation_type = ?' => array($usergroup_id, $user_id, $relationType))
                );
            }
        }

        // Adding new relationships
        if (!empty($new_items)) {
            // Adding sessions
            if (!empty($session_list)) {
                foreach ($session_list as $session_id) {
                    SessionManager::subscribe_users_to_session($session_id, $new_items, null, false);
                }
            }

            foreach ($new_items as $user_id) {
                // Adding courses
                if (!empty($course_list)) {
                    foreach ($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }
                $params = array('user_id' => $user_id, 'usergroup_id' => $usergroup_id, 'relation_type' => $relationType);
                Database::insert($this->usergroup_rel_user_table, $params);
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function usergroup_exists($name)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT * FROM $this->table u
                    INNER JOIN {$this->access_url_rel_usergroup} a 
                    ON (a.usergroup_id = u.id)
                    WHERE name = '".Database::escape_string($name)."' AND access_url_id = $urlId";
        } else {
            $sql = "SELECT * FROM $this->table WHERE name = '".Database::escape_string($name)."'";
        }
        $res = Database::query($sql);

        return Database::num_rows($res) != 0;
    }

    /**
     * @param int $sidx
     * @param int $sord
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getUsergroupsPagination($sidx, $sord, $start, $limit)
    {
        $sord = in_array(strtolower($sord), array('asc', 'desc')) ? $sord : 'desc';

        $start = intval($start);
        $limit = intval($limit);
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $from = $this->table." u INNER JOIN {$this->access_url_rel_usergroup} a ON (u.id = a.usergroup_id)";
            $where = array(' access_url_id = ?' => $urlId);
        } else {
            $from = $this->table." u ";
            $where = array();
        }

        $result = Database::select(
            'u.*',
            $from,
            array(
                'where' => $where,
                'order' => "name $sord",
                'LIMIT' => "$start , $limit"
            )
        );

        $new_result = array();
        if (!empty($result)) {
            foreach ($result as $group) {
                $group['sessions'] = count($this->get_sessions_by_usergroup($group['id']));
                $group['courses'] = count($this->get_courses_by_usergroup($group['id']));

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
                            GROUP_USER_PERMISSION_HRM
                        ];
                        break;
                }
                $group['users'] = count($this->get_users_by_usergroup($group['id'], $roles));
                $new_result[] = $group;
            }
            $result = $new_result;
        }
        $columns = array('name', 'users', 'courses', 'sessions', 'group_type');

        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }

        // Multidimensional sort
        $result = msort($result, $sidx, $sord);

        return $result;
    }

    /**
     * @param array $options
     * @return array
     */
    public function getDataToExport($options = array())
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $from = $this->table." u INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (u.id = a.usergroup_id)";
            $options = array('where' => array('access_url_id = ? ' => $urlId));
            $classes = Database::select('a.id, name, description', $from, $options);
        } else {
            $classes = Database::select('id, name, description', $this->table, $options);
        }

        $result = array();
        if (!empty($classes)) {
            foreach ($classes as $data) {
                $users = self::getUserListByUserGroup($data['id']);
                $userToString = null;
                if (!empty($users)) {
                    $userNameList = array();
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
     * @return array
     */
    public function filterByFirstLetter($firstLetter)
    {
        $firstLetter = Database::escape_string($firstLetter);
        $sql = "SELECT id, name FROM $this->table
		        WHERE
		            name LIKE '".$firstLetter."%' OR
		            name LIKE '".api_strtolower($firstLetter)."%'
		        ORDER BY name DESC ";

        $result = Database::query($sql);
        return Database::store_result($result);
    }

    /**
     * Select user group not in list
     * @param array $list
     * @return array
     */
    public function getUserGroupNotInList($list)
    {
        if (empty($list)) {
            return array();
        }

        $list = array_map('intval', $list);
        $listToString = implode("','", $list);

        $sql = "SELECT * FROM {$this->table} WHERE id NOT IN ('$listToString')";
        $result = Database::query($sql);
        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param $params
     * @param bool $show_query
     * @return bool|int
     */
    public function save($params, $show_query = false)
    {
        $params['updated_at'] = $params['created_at'] = api_get_utc_datetime();
        $params['group_type'] = isset($params['group_type']) ? self::SOCIAL_CLASS : self::NORMAL_CLASS;
        $params['allow_members_leave_group'] = isset($params['allow_members_leave_group']) ? 1 : 0;

        $groupExists = $this->usergroup_exists(trim($params['name']));
        if ($groupExists == false) {
            $id = parent::save($params, $show_query);
            if ($id) {
                if ($this->useMultipleUrl) {
                    $this->subscribeToUrl($id, api_get_current_access_url_id());
                }

                if ($params['group_type'] == self::SOCIAL_CLASS) {
                    $this->add_user_to_group(
                        api_get_user_id(),
                        $id,
                        $params['group_type']
                    );
                }
                $picture = isset($_FILES['picture']) ? $_FILES['picture'] : null;
                $picture = $this->manageFileUpload($id, $picture);
                if ($picture) {
                    $params = array(
                        'id' => $id,
                        'picture' => $picture,
                        'group_type' => $params['group_type']
                    );
                    $this->update($params);
                }
            }

            return $id;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function update($values)
    {
        $values['updated_on'] = api_get_utc_datetime();
        $values['group_type'] = isset($values['group_type']) ? self::SOCIAL_CLASS : self::NORMAL_CLASS;
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

        parent::update($values);

        if (isset($values['delete_picture'])) {
            $this->delete_group_picture($values['id']);
        }

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
            return $this->update_group_picture($groupId, $picture['name'], $picture['tmp_name']);
        }
        return false;
    }

    /**
     * @param $group_id
     * @return string
     */
    public function delete_group_picture($group_id)
    {
        return self::update_group_picture($group_id);
    }

    /**
     * Creates new group pictures in various sizes of a user, or deletes user pfotos.
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php
     * @param	int	The group id
     * @param	string $file			The common file name for the newly created photos.
     * It will be checked and modified for compatibility with the file system.
     * If full name is provided, path component is ignored.
     * If an empty name is provided, then old user photos are deleted only,
     * @see UserManager::delete_user_picture() as the prefered way for deletion.
     * @param	string		$source_file	The full system name of the image from which user photos will be created.
     * @return	string/bool	Returns the resulting common file name of created images which usually should be stored in database.
     * When an image is removed the function returns an empty string. In case of internal error or negative validation it returns FALSE.
     */
    public function update_group_picture($group_id, $file = null, $source_file = null)
    {
        // Validation 1.
        if (empty($group_id)) {
            return false;
        }
        $delete = empty($file);
        if (empty($source_file)) {
            $source_file = $file;
        }

        // User-reserved directory where photos have to be placed.
        $path_info = self::get_group_picture_path_by_id($group_id, 'system', true);

        $path = $path_info['dir'];

        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            @mkdir($path, api_get_permissions_for_new_directories(), true);
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
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
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

        $options = array(
            'quality' => 90,
        );

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
     * @return bool|void
     */
    public function delete($id)
    {
        if ($this->useMultipleUrl) {
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

        /*$sql = "DELETE FROM $this->usergroup_rel_
                WHERE usergroup_id = $id";
        Database::query($sql);*/

        $result = parent::delete($id);
    }

    /**
     * @param int $id
     * @param int $urlId
     */
    public function subscribeToUrl($id, $urlId)
    {
        Database::insert(
            $this->access_url_rel_usergroup,
            array(
                'access_url_id' => $urlId,
                'usergroup_id' =>$id
            )
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
            array(
                'access_url_id = ? AND usergroup_id = ? ' => array($urlId, $id)
            )
        );
    }

    /**
     * @param $needle
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
            // search courses where username or firstname or lastname begins likes $needle
            $sql = 'SELECT id, name FROM '.Database::get_main_table(TABLE_USERGROUP).' u
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
     * Get user list by usergroup
     * @param $id
     * @return array
     */
    public function getUserListByUserGroup($id)
    {
        $id = intval($id);
        $sql = "SELECT u.* FROM ".$this->table_user." u
                INNER JOIN ".$this->usergroup_rel_user_table." c
                ON c.user_id = u.id
                WHERE c.usergroup_id = $id"
                ;
        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * @param FormValidator $form
     * @param string        $type
     * @param array         $data
     */
    public function setForm($form, $type = 'add', $data = array())
    {
        switch ($type) {
            case 'add':
                $header = get_lang('Add');
                break;
            case 'edit':
                $header = get_lang('Edit');
                break;
        }

        $form->addElement('header', $header);

        //Name
        $form->addElement('text', 'name', get_lang('Name'), array('maxlength'=>255));
        $form->applyFilter('name', 'trim');

        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('name', '', 'maxlength', 255);

        // Description
        $form->addTextarea('description', get_lang('Description'), array('cols' => 58));
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
        $form->addElement('text', 'url', get_lang('Url'));
        $form->applyFilter('url', 'trim');

        // Picture
        $allowed_picture_types = $this->getAllowedPictureExtensions();

        $form->addElement('file', 'picture', get_lang('AddPicture'));
        $form->addRule(
            'picture',
            get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
            'filetype',
            $allowed_picture_types
        );

        if (isset($data['picture']) && strlen($data['picture']) > 0) {
            $picture = $this->get_picture_group($data['id'], $data['picture'], 80);
            $img = '<img src="'.$picture['file'].'" />';
            $form->addElement('label', null, $img);
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
     * Gets the current group image
     * @param string group id
     * @param string picture group name
     * @param string height
     * @param string picture size it can be small_,  medium_  or  big_
     * @param string style css
     * @return array with the file and the style of an image i.e $array['file'] $array['style']
     */
    public function get_picture_group($id, $picture_file, $height, $size_picture = GROUP_IMAGE_SIZE_MEDIUM, $style = '')
    {
        $picture = array();
        //$picture['style'] = $style;
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
            //$picture['style'] = '';
            if ($height > 0) {
                $dimension = api_getimagesize($picture['file']);
                $margin = (($height - $dimension['width']) / 2);
                //@ todo the padding-top should not be here
                //$picture['style'] = ' style="padding-top:'.$margin.'px; width:'.$dimension['width'].'px; height:'.$dimension['height'].';" ';
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
     * @param	integer	User ID
     * @param	string	Type of path to return (can be 'none', 'system', 'rel', 'web')
     * @param	bool	Whether we want to have the directory name returned 'as if' there was a file or not (in the case we want to know which directory to create - otherwise no file means no split subdir)
     * @param	bool	If we want that the function returns the /main/img/unknown.jpg image set it at true
     * @return	array 	Array of 2 elements: 'dir' and 'file' which contain the dir and file as the name implies if image does not exist it will return the unknow image if anonymous parameter is true if not it returns an empty er's
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

        if (empty($id) || empty($type)) {
            return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
        }

        $id = intval($id);
        $group_table = Database::get_main_table(TABLE_USERGROUP);
        $sql = "SELECT picture FROM $group_table WHERE id = ".$id;
        $res = Database::query($sql);

        if (!Database::num_rows($res)) {
            return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
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

        return array('dir' => $dir, 'file' => $picture_filename);
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
            GROUP_PERMISSION_CLOSED => get_lang('Closed')
        ];

        return $status;
    }

    /**
     * @param int $type
     */
    public function setGroupType($type)
    {
        $this->groupType = intval($type);
    }

    /**
     * @param int $group_id
     * @param int $user_id
     * @return bool
     */
    public function is_group_admin($group_id, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role = $this->get_user_group_role($user_id, $group_id);
        if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $group_id
     * @param int $user_id
     * @return bool
     */
    public function is_group_moderator($group_id, $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role = $this->get_user_group_role($user_id, $group_id);
        if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $group_id
     * @param int $user_id
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
        $roles = array(
            GROUP_USER_PERMISSION_ADMIN,
            GROUP_USER_PERMISSION_MODERATOR,
            GROUP_USER_PERMISSION_READER,
            GROUP_USER_PERMISSION_HRM,
        );
        $user_role = self::get_user_group_role($user_id, $group_id);
        if (in_array($user_role, $roles)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the relationship between a group and a User
     * @author Julio Montoya
     * @param int $user_id
     * @param int $group_id
     * @return int 0 if there are not relationship otherwise returns the user group
     * */
    public function get_user_group_role($user_id, $group_id)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $return_value = 0;
        if (!empty($user_id) && !empty($group_id)) {
            $sql = "SELECT relation_type FROM $table_group_rel_user
                    WHERE
                        usergroup_id = ".intval($group_id)." AND
                        user_id = ".intval($user_id)." ";
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
     * @return string
     */
    public function getUserRoleToString($userId, $groupId)
    {
        $role = self::get_user_group_role($userId, $groupId);
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
     * Add a group of users into a group of URLs
     * @author Julio Montoya
     * @param array $user_list
     * @param array $group_list
     * @param int   $relation_type
     * */
    public function add_users_to_groups($user_list, $group_list, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $table_url_rel_group = $this->usergroup_rel_user_table;
        $result_array = array();
        $relation_type = intval($relation_type);

        if (is_array($user_list) && is_array($group_list)) {
            foreach ($group_list as $group_id) {
                foreach ($user_list as $user_id) {
                    $role = self::get_user_group_role($user_id, $group_id);
                    if ($role == 0) {
                        $sql = "INSERT INTO $table_url_rel_group
		               			SET
		               			    user_id = ".intval($user_id).",
		               			    usergroup_id = ".intval($group_id).",
		               			    relation_type = ".intval($relation_type);

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
        return 	$result_array;
    }

    /**
     * Deletes an url and session relationship
     * @author Julio Montoya
     * @param  int  $user_id
     * @param  int $group_id
     * @return boolean true if success
     * */
    public function delete_user_rel_group($user_id, $group_id)
    {
        $table = $this->usergroup_rel_user_table;
        $sql = "DELETE FROM $table
               WHERE
                user_id = ".intval($user_id)." AND
                usergroup_id = ".intval($group_id)."  ";
        $result = Database::query($sql);

        return $result;
    }

    /**
     * Add a user into a group
     * @author Julio Montoya
     * @param  int $user_id
     * @param  int $group_id
     * @param  int $relation_type
     *
     * @return boolean true if success
     * */
    public function add_user_to_group($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $table_url_rel_group = $this->usergroup_rel_user_table;
        if (!empty($user_id) && !empty($group_id)) {
            $role = self::get_user_group_role($user_id, $group_id);

            if ($role == 0) {
                $sql = "INSERT INTO $table_url_rel_group
           				SET
           				    user_id = ".intval($user_id).",
           				    usergroup_id = ".intval($group_id).",
           				    relation_type = ".intval($relation_type);
                Database::query($sql);
            } elseif ($role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
                //if somebody already invited me I can be added
                self::update_user_role($user_id, $group_id, GROUP_USER_PERMISSION_READER);
            }
        }

        return true;
    }

    /**
     * Updates the group_rel_user table  with a given user and group ids
     * @author Julio Montoya
     * @param int $user_id
     * @param int $group_id
     * @param int $relation_type
     *
     **/
    public function update_user_role($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $group_id = intval($group_id);
        $user_id = intval($user_id);

        $sql = "UPDATE $table_group_rel_user
   				SET relation_type = ".intval($relation_type)."
                WHERE user_id = $user_id AND usergroup_id = $group_id";
        Database::query($sql);
    }

    /**
     * Gets the inner join from users and group table
     *
     * @return array   Database::store_result of the result
     *
     * @author Julio Montoya
     * */
    public function get_groups_by_user($user_id = '', $relation_type = GROUP_USER_PERMISSION_READER, $with_image = false)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;

        if ($relation_type == 0) {
            $relationCondition = '';
        } else {
            $relation_type = intval($relation_type);
            $relationCondition = " AND gu.relation_type = $relation_type ";
        }

        $sql = "SELECT
                    g.picture,
                    g.name,
                    g.description,
                    g.id ,
                    gu.relation_type
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.usergroup_id = g.id
				WHERE
				    g.group_type = ".self::SOCIAL_CLASS." AND
                    gu.user_id = $user_id
                    $relationCondition
                ORDER BY created_at DESC ";
        $result = Database::query($sql);
        $array = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($with_image) {
                    $picture = self::get_picture_group($row['id'], $row['picture'], 80);
                    $img = '<img src="'.$picture['file'].'" />';
                    $row['picture'] = $img;
                }
                $array[$row['id']] = $row;
            }
        }
        return $array;
    }

    /** Gets the inner join of users and group table
     * @param int  quantity of records
     * @param bool show groups with image or not
     * @return array  with group content
     * @author Julio Montoya
     * */
    public function get_groups_by_popularity($num = 6, $with_image = true)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;
        if (empty($num)) {
            $num = 6;
        } else {
            $num = intval($num);
        }
        // only show admins and readers
        $where_relation_condition = " WHERE g.group_type = ".self::SOCIAL_CLASS." AND
                                      gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."', '".GROUP_USER_PERMISSION_HRM."') ";
        $sql = "SELECT DISTINCT count(user_id) as count, g.picture, g.name, g.description, g.id
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.usergroup_id = g.id $where_relation_condition
				GROUP BY g.id
				ORDER BY count DESC
				LIMIT $num";

        $result = Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = self::get_picture_group($row['id'], $row['picture'], 80);
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

    /** Gets the last groups created
     * @param int  $num quantity of records
     * @param bool $with_image show groups with image or not
     * @return array  with group content
     * @author Julio Montoya
     * */
    public function get_groups_by_age($num = 6, $with_image = true)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_group = $this->table;

        if (empty($num)) {
            $num = 6;
        } else {
            $num = intval($num);
        }
        $where_relation_condition = " WHERE g.group_type = ".self::SOCIAL_CLASS." AND
                                      gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."', '".GROUP_USER_PERMISSION_HRM."') ";
        $sql = "SELECT DISTINCT
                  count(user_id) as count,
                  g.picture,
                  g.name,
                  g.description,
                  g.id
                FROM $tbl_group g
                INNER JOIN $table_group_rel_user gu
                ON gu.usergroup_id = g.id
                $where_relation_condition
                GROUP BY g.id
                ORDER BY created_at DESC
                LIMIT $num ";

        $result = Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = self::get_picture_group($row['id'], $row['picture'], 80);
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
     * Gets the group's members
     * @param int group id
     * @param bool show image or not of the group
     * @param array list of relation type use constants
     * @param int from value
     * @param int limit
     * @param array image configuration, i.e array('height'=>'20px', 'size'=> '20px')
     * @return array list of users in a group
     */
    public function get_users_by_group(
        $group_id,
        $with_image = false,
        $relation_type = array(),
        $from = null,
        $limit = null,
        $image_conf = array('size' => USER_IMAGE_SIZE_MEDIUM, 'height' => 80)
    ) {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $group_id = intval($group_id);

        if (empty($group_id)) {
            return array();
        }

        $limit_text = '';
        if (isset($from) && isset($limit)) {
            $from = intval($from);
            $limit = intval($limit);
            $limit_text = "LIMIT $from, $limit";
        }

        if (count($relation_type) == 0) {
            $where_relation_condition = '';
        } else {
            $new_relation_type = array();
            foreach ($relation_type as $rel) {
                $rel = intval($rel);
                $new_relation_type[] = "'$rel'";
            }
            $relation_type = implode(',', $new_relation_type);
            if (!empty($relation_type))
                $where_relation_condition = "AND gu.relation_type IN ($relation_type) ";
        }

        $sql = "SELECT picture_uri as image, u.id, CONCAT (u.firstname,' ', u.lastname) as fullname, relation_type
    		    FROM $tbl_user u
    		    INNER JOIN $table_group_rel_user gu
    			ON (gu.user_id = u.id)
    			WHERE
    			    gu.usergroup_id= $group_id
    			    $where_relation_condition
    			ORDER BY relation_type, firstname
    			$limit_text";

        $result = Database::query($sql);
        $array  = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $userInfo = api_get_user_info($row['id']);
                $userPicture = UserManager::getUserPicture($row['id']);

                $row['image'] = '<img src="'.$userPicture.'"  />';
                $row['user_info'] = $userInfo;
            }
            $array[$row['id']] = $row;
        }
        return $array;
    }

    /**
     * Gets all the members of a group no matter the relationship for
     * more specifications use get_users_by_group
     * @param int group id
     * @return array
     */
    public function get_all_users_by_group($group_id)
    {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $group_id = intval($group_id);

        if (empty($group_id)) {
            return array();
        }

        $sql = "SELECT u.id, u.firstname, u.lastname, relation_type
                FROM $tbl_user u
			    INNER JOIN $table_group_rel_user gu
			    ON (gu.user_id = u.id)
			    WHERE gu.usergroup_id= $group_id
			    ORDER BY relation_type, firstname";

        $result = Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $array[$row['id']] = $row;
        }
        return $array;
    }

    /**
     * Shows the left column of the group page
     * @param int group id
     * @param int user id
     *
     */
    public function show_group_column_information($group_id, $user_id, $show = '')
    {
        $html = '';
        $group_info = $this->get($group_id);

        //my relation with the group is set here
        $my_group_role = self::get_user_group_role($user_id, $group_id);

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
            $html .= Display::panelCollapse(get_lang('SocialGroups'), $list, 'sm-groups', array(), 'groups-acordeon', 'groups-collapse');
        }
        return $html;
    }

    public function delete_topic($group_id, $topic_id)
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $topic_id = intval($topic_id);
        $group_id = intval($group_id);
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
     * @param bool $with_image
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
    public function get_all_group_tags($tag, $from = 0, $number_of_items = 10, $getCount = false)
    {
        $group_table = $this->table;
        $tag = Database::escape_string($tag);
        $from = intval($from);
        $number_of_items = intval($number_of_items);
        $return = array();

        $keyword = $tag;
        $sql = "SELECT  g.id, g.name, g.description, g.url, g.picture
                FROM $group_table g";
        if (isset ($keyword)) {
            $sql .= " WHERE (
                        g.name LIKE '%".$keyword."%' OR
                        g.description LIKE '%".$keyword."%' OR
                        g.url LIKE '%".$keyword."%'
                     )";
        }

        $direction = 'ASC';
        if (!in_array($direction, array('ASC', 'DESC'))) {
            $direction = 'ASC';
        }

        $from = intval($from);
        $number_of_items = intval($number_of_items);

        //$sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

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
     * @return array
     */
    public static function get_parent_groups($group_id)
    {
        $t_rel_group = Database::get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $max_level = 10;
        $select_part = "SELECT ";
        $cond_part = '';
        for ($i = 1; $i <= $max_level; $i++) {
            $g_number = $i;
            $rg_number = $i - 1;
            if ($i == $max_level) {
                $select_part .= "rg$rg_number.group_id as id_$rg_number ";
            } else {
                $select_part .= "rg$rg_number.group_id as id_$rg_number, ";
            }
            if ($i == 1) {
                $cond_part .= "FROM $t_rel_group rg0 LEFT JOIN $t_rel_group rg$i on rg$rg_number.group_id = rg$i.subgroup_id ";
            } else {
                $cond_part .= " LEFT JOIN $t_rel_group rg$i on rg$rg_number.group_id = rg$i.subgroup_id ";
            }
        }
        $sql = $select_part.' '.$cond_part."WHERE rg0.subgroup_id='$group_id'";
        $res = Database::query($sql);
        $temp_arr = Database::fetch_array($res, 'NUM');
        $toReturn = array();
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
     * Get the group member list by a user and his group role
     * @param int $userId The user ID
     * @param int $relationType Optional. The relation type. GROUP_USER_PERMISSION_ADMIN by default
     * @param boolean $includeSubgroupsUsers Optional. Whether include the users from subgroups
     * @return array
     */
    public function getGroupUsersByUser(
        $userId,
        $relationType = GROUP_USER_PERMISSION_ADMIN,
        $includeSubgroupsUsers = true
    ) {
        $userId = intval($userId);
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
            $groupUsers = self::get_users_by_group($groupId);

            if (empty($groupUsers)) {
                continue;
            }

            foreach ($groupUsers as $member) {
                if ($member['user_id'] == $userId) {
                    continue;
                }

                $userIdList[] = intval($member['user_id']);
            }
        }

        return array_unique($userIdList);
    }

    /**
     * Get the subgroups ID from a group.
     * The default $levels value is 10 considering it as a extensive level of depth
     * @param int $groupId The parent group ID
     * @param int $levels The depth levels
     * @return array The list of ID
     */
    public static function getGroupsByDepthLevel($groupId, $levels = 10)
    {
        $groups = array();
        $groupId = intval($groupId);

        $groupTable = Database::get_main_table(TABLE_USERGROUP);
        $groupRelGroupTable = Database::get_main_table(TABLE_USERGROUP_REL_USERGROUP);

        $select = "SELECT ";
        $from = "FROM $groupTable g1 ";

        for ($i = 1; $i <= $levels; $i++) {
            $tableIndexNumber = $i;
            $tableIndexJoinNumber = $i - 1;
            $select .= "g$i.id as id_$i ";
            $select .= ($i != $levels ? ", " : null);

            if ($i == 1) {
                $from .= "INNER JOIN $groupRelGroupTable gg0 ON g1.id = gg0.subgroup_id and gg0.group_id = $groupId ";
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
     * Set a parent group
     * @param int $group_id
     * @param int $parent_group_id if 0, we delete the parent_group association
     * @param int $relation_type
     * @return resource
     **/
    public static function set_parent_group($group_id, $parent_group_id, $relation_type = 1)
    {
        $table = Database::get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $group_id = intval($group_id);
        $parent_group_id = intval($parent_group_id);
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
     * Filter the groups/classes info to get a name list only
     * @param int $userId The user ID
     * @param int $filterByType Optional. The type of group
     * @return array
     */
    public function getNameListByUser($userId, $filterByType = null)
    {
        $userClasses = $this->getUserGroupListByUser($userId, $filterByType);

        return array_column($userClasses, 'name');
    }

    /**
     * Get the HTML necessary for display the groups/classes name list
     * @param int $userId The user ID
     * @param int $filterByType Optional. The type of group
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
     * @return bool
     */
    public static function canLeave($groupInfo)
    {
        return $groupInfo['allow_members_leave_group'] == 1 ? true : false;
    }
}
