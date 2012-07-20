<?php

namespace Glossary;

use Database;

/**
 * Glossary entry repository. Interface with the database.
 *
 * @author Laurent Opprecht <laurent@opprecht.info> for the University of Geneva
 * @licence /license.txt
 */
class GlossaryRepository
{

    /**
     * Return the instance of the repository.
     * 
     * @return \Glossary\GlossaryRepository
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
     * 
     * @param string $where Where filter to apply
     * @return array 
     */
    public function find($where, $orderby = '', $limit = null)
    {
        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $tool = TOOL_GLOSSARY;

        $sql = "SELECT g.*,         
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
                    $table AS g, 
                    $table_item_property AS prop
                WHERE 
                    (g.glossary_id = prop.ref AND
                     g.c_id = prop.c_id AND
                     prop.tool = '$tool')";
                
        $sql .= $where ? "AND ($where)" : '';
        $sql .= ' ORDER BY ';
        $sql .= $orderby ? $orderby : 'name ASC';
        if($count){
            $from = (int)$limit->from;
            $count = (int)$limit->count;
            $sql .= " LIMIT $from, $count";
        }

        $rs = Database :: query($sql);
        while ($data = Database::fetch_object($rs)) {
            $result[] = Glossary::create($data);
        }
        return $result;
    }
    
    public function count($where)
    {
        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $tool = TOOL_GLOSSARY;

        $sql = "SELECT count(*) AS val
                FROM 
                    $table AS g, 
                    $table_item_property AS prop
                WHERE 
                    (g.glossary_id = prop.ref AND
                     g.c_id = prop.c_id AND
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
     * @return \Glossary\Glossary 
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
     * @return \Glossary\Glossary 
     */
    public function find_one_by_id($c_id, $id)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        return $this->find_one("g.c_id = $c_id AND g.glossary_id = $id");
    }

    /**
     * Retrieve one course description from its ids.
     * 
     * @param int|Course $c_id 
     * @param string name
     * @return \Glossary\Glossary 
     */
    public function find_one_by_course_and_name($c_id, $name)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        $name = Database::escape_string($name);
        return $this->find_one("g.c_id = $c_id AND g.name = '$name'");
    }

    /**
     * Returns the list of course descriptions belonging to a specific course and
     * session.
     * 
     * @param object $course
     * @return Array 
     */
    public function find_by_course($course, $orderby = '', $limit = null)
    {
        $c_id = (int)$course->c_id;
        $session_id = isset($course->session_id) ? (int)$course->session_id : 0;
        if (empty($c_id)) {
            return array();
        }
        $condition_session = api_get_session_condition($session_id, true, true);
        $where = "g.c_id = $c_id $condition_session";
        return $this->find($where, $orderby, $limit);
    }
    
    public function count_by_course($course)
    {
        $c_id = (int)$course->c_id;
        $session_id = isset($course->session_id) ? (int)$course->session_id : 0;
        if (empty($c_id)) {
            return 0;
        }
        $condition_session = api_get_session_condition($session_id, true, true);
        $where = "g.c_id = $c_id $condition_session";
        return $this->count($where);
    }

    /**
     *
     * @param object $glossary
     * @return bool
     */
    public function save($glossary)
    {
        $id = $glossary->id;
        if (empty($id)) {
            return $this->insert($glossary);
        } else {
            return $this->update($glossary);
        }
    }


    function next_display_order($c_id)
    {
        $table = Database :: get_course_table(TABLE_GLOSSARY);
        $sql = "SELECT MAX(display_order) FROM  $table WHERE c_id = $c_id ";
        $rs = Database :: query($sql);
        list ($result) = Database :: fetch_row($rs);
        $result = intval($result) + 1;
        return $result;
    }
    
    /**
     *
     * @param \Glossary\Glossary $glossary
     * @return bool 
     */
    public function insert($glossary)
    {
        $c_id = (int) $glossary->c_id;

        $name = trim($glossary->name);
        $name = Database::escape_string($name);

        $description = trim($glossary->description);
        $description = Database::escape_string($description);

        $session_id = (int) $glossary->session_id;
        $session_id = $session_id ? $session_id : '0';

        $display_order = $this->next_display_order($c_id);

        $table = Database :: get_course_table(TABLE_GLOSSARY);
        $sql = "INSERT INTO $table 
                    (c_id, name, description, display_order, session_id)
			    VALUES 
                    ($c_id , '$name', '$description', $display_order, $session_id)";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $id = Database::insert_id();
            $glossary->id = $id;

            $_course = api_get_course_info_by_id($c_id);
            $tool = TOOL_GLOSSARY;
            $user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'GlossaryAdded', $user_id);
        }
        return $result;
    }

    /**
     *
     * @param \Glossary\Glossary $glossary
     * @return bool 
     */
    function update($glossary)
    {
        $c_id = (int) $glossary->c_id;
        $id = (int) $glossary->id;

        $name = trim($glossary->name);
        $name = Database::escape_string($name);

        $description = trim($glossary->description);
        $description = Database::escape_string($description);

        $session_id = (int) $glossary->session_id;
        $session_id = $session_id ? $session_id : '0';
        
        $display_order = (int) $glossary->display_order;

        $table = Database :: get_course_table(TABLE_GLOSSARY);
        $sql = "UPDATE $table SET
                    name = '$name',
                    description = '$description',
                    display_order = $display_order,
                    session_id = $session_id
			    WHERE
                    c_id = $c_id AND
                    glossary_id = $id";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $_course = api_get_course_info_by_id($c_id);
            $tool = TOOL_GLOSSARY;
            $user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'GlossaryUpdated', $user_id);
        }
        return $result;
    }

    /**
     * 
     * @param object $glossary
     * @return boolean 
     */
    public function remove($glossary)
    {
        $table = Database :: get_course_table(TABLE_GLOSSARY);
        $c_id = (int) $glossary->c_id;
        $id = (int) $glossary->id;

        if (empty($c_id) || empty($id)) {
            return false;
        }

        $sql = "DELETE FROM $table WHERE c_id=$c_id AND glossary_id=$id";
        $result = Database :: query($sql);
        if ($result) {
            $tool = TOOL_GLOSSARY;
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