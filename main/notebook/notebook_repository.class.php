<?php

namespace Notebook;

use Database;

/**
 * Notebook repository. Interface with the database.
 *
 * @author Laurent Opprecht <laurent@opprecht.info> for the University of Geneva
 * @licence /license.txt
 */
class NotebookRepository
{

    /**
     * Return the instance of the repository.
     * 
     * @return \Notebook\NotebookRepository
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    /**
     *
     * @return string
     */
    public function get_table()
    {
        return Database :: get_course_table(TABLE_NOTEBOOK);
    }

    /**
     * 
     * @return string
     */
    public function get_tool()
    {
        return TOOL_NOTEBOOK;
    }

    /**
     * 
     * 
     * @param string $where Where filter to apply
     * @return array 
     */
    public function find($where, $orderby = '', $limit = null)
    {
        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table = $this->get_table();
        $tool = $this->get_tool();

        $sql = "SELECT n.*,         
                       prop.id AS property_id, 
                       prop.tool, 
                       prop.insert_user_id,
                       prop.insert_date,
                       prop.lastedit_date,
                       prop.ref,
                       prop.lastedit_type,
                       prop.lastedit_user_id,
                       prop.to_group_id, 
                       prop.to_user_id, 
                       prop.visibility, 
                       prop.start_visible, 
                       prop.end_visible, 
                       prop.id_session
                FROM 
                    $table AS n, 
                    $table_item_property AS prop
                WHERE 
                    (n.notebook_id = prop.ref AND
                     n.c_id = prop.c_id AND
                     prop.tool = '$tool')";

        $sql .= $where ? "AND ($where)" : '';
        $sql .= ' ORDER BY ';
        $sql .= $orderby ? $orderby : 'creation_date ASC';
        if ($limit) {
            $from = (int) $limit->from;
            $count = (int) $limit->count;
            $sql .= " LIMIT $from, $count";
        }

        $rs = Database :: query($sql);
        while ($data = Database::fetch_object($rs)) {
            $result[] = Notebook::create($data);
        }
        return $result;
    }

    public function count($where)
    {
        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table = $this->get_table();
        $tool = $this->get_tool();

        $sql = "SELECT count(*) AS val
                FROM 
                    $table AS n, 
                    $table_item_property AS prop
                WHERE 
                    (n.notebook_id = prop.ref AND
                     n.c_id = prop.c_id AND
                     prop.tool = '$tool')";

        $sql .= $where ? "AND ($where)" : '';
        $sql .= " ORDER BY name ASC";

        $rs = Database :: query($sql);
        while ($data = Database::fetch_object($rs)) {
            return $data->val;
        }
        return 0;
    }

    /**
     *
     * @param string $where
     * @return \Notebook\notebook_id
     */
    public function find_one($where)
    {
        $items = $this->find($where);
        foreach ($items as $item) {
            return $item;
        }
        return null;
    }

    /**
     * Retrieve one course description from its ids.
     * 
     * @param int|Course $c_id 
     * @param int $id           
     * @return \notebook_id\notebook_id 
     */
    public function find_one_by_id($c_id, $id)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        return $this->find_one("n.c_id = $c_id AND n.notebook_id = $id");
    }

    /**
     * Retrieve one course description from its ids.
     * 
     * @param int|Course $c_id 
     * @param string name
     * @return \Notebook\Notebook
     */
    public function find_one_by_course_and_title($c_id, $title)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        $name = Database::escape_string($name);
        return $this->find_one("n.c_id = $c_id AND n.title = '$title'");
    }

    /**
     * Returns the list of notes belonging to a specific course and
     * session.
     * 
     * @param object $course
     * @return Array 
     */
    public function find_by_course($course, $orderby = '', $limit = null)
    {
        $c_id = (int) $course->c_id;
        $session_id = isset($course->session_id) ? (int) $course->session_id : 0;
        if (empty($c_id)) {
            return array();
        }
        $condition_session = api_get_session_condition($session_id);
        $where = "n.c_id = $c_id $condition_session";
        return $this->find($where, $orderby, $limit);
    }
    
    /**
     * Returns the list of notes belonging to a specific course and
     * session.
     * 
     * @param object $course
     * @return Array 
     */
    public function find_by_course_and_user($course, $user, $orderby = '', $limit = null)
    {
        $user_id = is_object($user) ? (int)$user->user_id : (int)$user;
        $c_id = (int) $course->c_id;
        $session_id = isset($course->session_id) ? (int) $course->session_id : 0;
        if (empty($c_id)) {
            return array();
        }
        $condition_session = api_get_session_condition($session_id);
        $where = "user_id = $user_id AND n.c_id = $c_id $condition_session";
        return $this->find($where, $orderby, $limit);
    }

    public function count_by_course($course)
    {
        $c_id = (int) $course->c_id;
        $session_id = isset($course->session_id) ? (int) $course->session_id : 0;
        if (empty($c_id)) {
            return 0;
        }
        $condition_session = api_get_session_condition($session_id);
        $where = "n.c_id = $c_id $condition_session";
        return $this->count($where);
    }

    /**
     *
     * @param object \Notebook\Notebook
     * @return bool
     */
    public function save($item)
    {
        $id = $item->id;
        if (empty($id)) {
            return $this->insert($item);
        } else {
            return $this->update($item);
        }
    }

    /**
     *
     * @param \Notebook\Notebook $item
     * @return bool 
     */
    public function insert($item)
    {
        $c_id = (int) $item->c_id;
        
        $user_id = (int)$item->user_id;
        $user_id = $user_id ? $user_id : api_get_user_id();
       
        $_course = api_get_course_info_by_id($c_id);
        $course = $_course['code'];

        $session_id = (int) $item->session_id;
        $session_id = $session_id ? $session_id : api_get_session_id();

        $title = trim($item->title);
        $title = Database::escape_string($title);

        $description = trim($item->description);
        $description = Database::escape_string($description);
        
        $now = time();
        $creation_date = date('Y-m-d H:i:s', $now);
        $update_date = $creation_date;
        
        $status = (int)$item->status;
        $status = $status ? $status : '0';

        $table = $this->get_table();
        $sql = "INSERT INTO $table 
                    (c_id, user_id, course, session_id, title, description, creation_date, update_date, status)
			    VALUES 
                    ($c_id, $user_id, '$course', $session_id, '$title', '$description', '$creation_date', '$update_date', $status)";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $id = Database::insert_id();
            $item->id = $id;
            $item->creation_date = $creation_date;
            $item->update_date = $update_date;
            $item->status = (int)$status;
            $item->course = $course;
            $item->session_id = $session_id;
            $item->user_id = $user_id;

            //$_course = api_get_course_info_by_id($c_id);
            $tool = $this->get_tool();
           //$user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'NotebookAdded', $user_id);
        }
        return $result;
    }

    /**
     *
     * @param \Notebook\Notebook $item
     * @return bool 
     */
    function update($item)
    {
        $c_id = (int) $item->c_id;
        $id = (int) $item->id;
        
        $user_id = (int)$item->user_id;
        $user_id = $user_id ? $user_id : api_get_user_id();

        $title = trim($item->title);
        $title = Database::escape_string($title);

        $description = trim($item->description);
        $description = Database::escape_string($description);

        $session_id = (int) $item->session_id;
        $session_id = $session_id ? $session_id : '0';

        $creation_date = $item->creation_date;
        $creation_date = is_string($creation_date) ? strtotime($creation_date) : $creation_date;
        $creation_date = date('Y-m-d H:i:s', $creation_date);
        
        $now = time();
        $creation_date = date('Y-m-d H:i:s', $now);
        $update_date = $creation_date;
        
        $status = (int)$item->status;
        $status = $status ? $status : '0';
        
        
        $table = $this->get_table();
        $sql = "UPDATE $table SET
                    user_id = $user_id,
                    session_id = $session_id,
                    title = '$title',
                    description = '$description',
                    update_date = '$update_date',
                    status = $status
			    WHERE
                    c_id = $c_id AND
                    notebook_id = $id";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $item->update_date = $update_date;
            $_course = api_get_course_info_by_id($c_id);
            $tool = $this->get_tool();
            //$user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'NotebookUpdated', $user_id);
        }
        return $result;
    }

    /**
     * 
     * @param object $item
     * @return boolean 
     */
    public function remove($item)
    {
        $table = $this->get_table();
        $c_id = (int) $item->c_id;
        $id = (int) $item->id;

        if (empty($c_id) || empty($id)) {
            return false;
        }

        $sql = "DELETE FROM $table WHERE c_id=$c_id AND notebook_id=$id";
        $result = Database :: query($sql);
        if ($result) {
            $tool = $this->get_tool();
            $tbl_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
            $sql = "DELETE FROM $tbl_property WHERE c_id=$c_id AND ref=$id AND tool='$tool'";
            Database :: query($sql);
        }
        return (bool) $result;
    }

    /**
     *
     * @param object $course
     * @return int 
     */
    public function remove_by_course($course)
    {
        $items = $this->find_by_course($course);
        foreach ($items as $item) {
            $success = $this->remove($item);
            if ($success) {
                $result++;
            }
        }
        return $result;
    }

}