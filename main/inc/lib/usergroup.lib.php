<?php
/* For licensing terms, see /license.txt */
/**
 * This class provides methods for the UserGroup management.
 * Include/require it in your code to use its features.
 * @package chamilo.library
 */
/**
 * Code
 */
require_once 'model.lib.php';

/**
 * Class UserGroup
 */
class UserGroup extends Model
{
    public $columns = array('id', 'name', 'description');
    public $useMultipleUrl = false;

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

        global $_configuration;
        if (isset($_configuration['enable_multiple_url_support_for_classes'])) {
            $this->useMultipleUrl = $_configuration['enable_multiple_url_support_for_classes'];
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
        $row = Database::select('count(*) as count', $this->table, array(), 'first');

        return $row['count'];
    }

    /**
     * @return int
     */
    public function get_count()
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
                $row  = Database::fetch_array($result);

                return $row['count'];
            }

            return 0;
        } else {
            return $this->getTotalCount();
        }
    }

    /**
     * @param int $course_id
     *
     * @return mixed
     */
    public function get_usergroup_by_course_with_data_count($course_id)
    {
        if ($this->useMultipleUrl) {
            $course_id = intval($course_id);
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT count(c.usergroup_id) as count FROM {$this->usergroup_rel_course_table} c
                    INNER JOIN {$this->access_url_rel_usergroup} a ON (c.usergroup_id = a.usergroup_id)
                    WHERE access_url_id = $urlId AND course_id = $course_id
            ";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row  = Database::fetch_array($result);
                return $row['count'];
            }

            return 0;
        } else {
            $row = Database::select(
                'count(*) as count',
                $this->usergroup_rel_course_table,
                array('where' => array('course_id = ?' => $course_id)),
                'first'
            );

            return $row['count'];
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
     * @param int user group id
     * @param array $loadCourseData
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
    public function get_usergroup_in_course($options = array())
    {
        if ($this->useMultipleUrl) {
            $sql = "SELECT u.* FROM {$this->usergroup_rel_course_table} usergroup
                    INNER JOIN  {$this->table} u
                    ON (u.id = usergroup.usergroup_id)
                    INNER JOIN {$this->table_course} c
                    ON (usergroup.course_id = c.id)
                    INNER JOIN {$this->access_url_rel_usergroup} a ON (a.usergroup_id = u.id)
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
     *
     * @return array|bool
     */
    public function get_usergroup_not_in_course($options = array())
    {
        $course_id = null;
        if (isset($options['course_id'])) {
            $course_id = intval($options['course_id']);
            unset($options['course_id']);
        }

        if (empty($course_id)) {
            return false;
        }

        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $sql = "SELECT DISTINCT u.id, name
                    FROM {$this->table} u
                    INNER JOIN {$this->access_url_rel_usergroup} a
                    ON (a.usergroup_id = u.id)
                    LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                    ON (u.id = urc.usergroup_id AND course_id = $course_id)
            ";
        } else {
            $sql = "SELECT DISTINCT u.id, name
                    FROM {$this->table} u
                    LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                    ON (u.id = urc.usergroup_id AND course_id = $course_id)
            ";
        }
        $conditions = Database::parse_conditions($options);
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
            $options = array('where' => array('c.course_id = ? AND access_url_id = ?' => array($course_id, $urlId)));
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
     * @param   int     user group id
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
     * @param   int     user group id
     * @return  array   with a list of user ids
     */
    public function get_users_by_usergroup($id = null)
    {
        if (empty($id)) {
            $conditions = array();
        } else {
            $conditions = array('where' => array('usergroup_id = ?' => $id));
        }
        $results = Database::select('user_id', $this->usergroup_rel_user_table, $conditions, true);
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['user_id'];
            }
        }
        return $array;
    }

    /**
     * Gets the usergroup id list by user id
     * @param   int user id
     * @return array
     */
    public function get_usergroup_by_user($userId)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $from = $this->usergroup_rel_user_table." u
                    INNER JOIN {$this->access_url_rel_usergroup} a ON (a.usergroup_id AND u.usergroup_id)";
            $where =  array('where' => array('user_id = ? AND access_url_id = ? ' => array($userId, $urlId)));
        } else {
            $from = $this->usergroup_rel_user_table." u ";
            $where =  array('where' => array('user_id = ?' => $userId));
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
     * @param   int     usergroup id
     * @param   array   list of session ids
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
                    SessionManager::suscribe_users_to_session($session_id, $user_list, null, false);
                }
            }
        }
    }

    /**
     * Subscribes courses to a group (also adding the members of the group in the course)
     * @param   int     usergroup id
     * @param   array   list of course ids (integers)
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
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }
                $params = array('course_id' => $course_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_course_table, $params);
            }
        }
    }

    /**
     * @param int $usergroup_id
     * @param bool $delete_items
     */
    public function unsubscribe_courses_from_usergroup($usergroup_id, $delete_items)
    {
        // Deleting items.
        if (!empty($delete_items)) {
            $user_list = self::get_users_by_usergroup($usergroup_id);
            foreach ($delete_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);
                    }
                }
                Database::delete($this->usergroup_rel_course_table, array('usergroup_id = ? AND course_id = ?' => array($usergroup_id, $course_id)));
            }
        }
    }

    /**
     * Subscribe users to a group
     * @param int     usergroup id
     * @param array   list of user ids
     * @param bool $delete_users_not_present_in_list
     */
    public function subscribe_users_to_usergroup($usergroup_id, $list, $delete_users_not_present_in_list = true)
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
                    array('usergroup_id = ? AND user_id = ?' => array($usergroup_id, $user_id))
                );
            }
        }

        // Adding new relationships
        if (!empty($new_items)) {
            //Adding sessions
            if (!empty($session_list)) {
                foreach ($session_list as $session_id) {
                    SessionManager::suscribe_users_to_session($session_id, $new_items, null, false);
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
                $params = array('user_id' => $user_id, 'usergroup_id' => $usergroup_id);
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
                    INNER JOIN {$this->access_url_rel_usergroup} a ON (a.usergroup_id = u.id)
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
            array('where' => $where, 'order'=> "name $sord", 'LIMIT'=> "$start , $limit")
        );

        $new_result = array();
        if (!empty($result)) {
            foreach ($result as $group) {
                $group['sessions']   = count($this->get_sessions_by_usergroup($group['id']));
                $group['courses']    = count($this->get_courses_by_usergroup($group['id']));
                $group['users']      = count($this->get_users_by_usergroup($group['id']));
                $new_result[]        = $group;
            }
            $result = $new_result;
        }
        $columns = array('id', 'name', 'users', 'courses','sessions');

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
    public function get_all_for_export($options = null)
    {
        if ($this->useMultipleUrl) {
            $urlId = api_get_current_access_url_id();
            $from = $this->table." u INNER JOIN {$this->access_url_rel_usergroup} a ON (u.id = a.usergroup_id)";
            $options = array('where' => array('access_url_id = ? ' => $urlId));
            return Database::select('a.id, name, description', $from, $options);
        } else {
            return Database::select('id, name, description', $this->table, $options);
        }
    }

    /**
     * @param string $firstLetter
     * @return array
     */
    public function filterByFirstLetter($firstLetter)
    {
        $firstLetter = Database::escape_string($firstLetter);
        $sql = "SELECT id, name FROM $this->table
		        WHERE name LIKE '".$firstLetter."%' OR name LIKE '".api_strtolower($firstLetter)."%'
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
     * @return bool|void
     */
    public function save($params, $show_query = false)
    {
        $id = parent::save($params, $show_query);
        if ($this->useMultipleUrl) {
            $this->subscribeToUrl($id, api_get_current_access_url_id());
        }
        return $id;
    }

    /**
     * @param int $id
     * @return bool|void
     */
    public function delete($id)
    {
        $result = parent::delete($id);
        if ($this->useMultipleUrl) {
            if ($result) {
                $this->unsubscribeToUrl($id, api_get_current_access_url_id());
            }
        }
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

    public static function searchUserGroupAjax($needle)
    {
        $response = new XajaxResponse();
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
        $response->addAssign('ajax_list_courses','innerHTML', api_utf8_encode($return));

        return $response;
    }
}
/* CREATE TABLE IF NOT EXISTS access_url_rel_usergroup (access_url_id int unsigned NOT NULL, usergroup_id int unsigned NOT NULL, PRIMARY KEY (access_url_id, usergroup_id));*/
