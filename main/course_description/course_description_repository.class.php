<?php

namespace CourseDescription;

use Database;

/**
 * Description of course_description_controller
 *
 * @author Laurent Opprecht <laurent@opprecht.info> for the University of Geneva
 * @licence /license.txt
 */
class CourseDescriptionRepository
{

    /**
     * Return the instance of the repository.
     * 
     * @return \CourseDescription\CourseDescriptionRepository
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
    public function find($where)
    {
        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $tool = TOOL_COURSE_DESCRIPTION;

        $sql = "SELECT des.*,         
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
                    $table AS des, 
                    $table_item_property AS prop
                WHERE 
                    (des.id = prop.ref AND
                     des.c_id = prop.c_id AND
                     prop.tool = '$tool')";

        $sql .= $where ? "AND ($where)" : '';

        $rs = Database :: query($sql);
        while ($data = Database::fetch_object($rs)) {
            $result[] = CourseDescription::create($data);
        }
        return $result;

        //$result = new ResultSet($sql);
        //return $result->return_type(__CLASS__);
    }

    /**
     *
     * @param string $where
     * @return \CourseDescription\CourseDescription 
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
     * @return \CourseDescription\CourseDescription 
     */
    public function find_one_by_id($c_id, $id)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        return $this->find_one("des.c_id = $c_id AND des.id = $id");
    }

    /**
     * Returns the list of course descriptions belonging to a specific course and
     * session.
     * 
     * @param object $course
     * @return Array 
     */
    public function find_by_course($course)
    {
        $c_id = (int)$course->c_id;
        $session_id = isset($course->session_id) ? (int)$course->session_id : 0;
        if (empty($c_id)) {
            return array();
        }
        $condition_session = api_get_session_condition($session_id, true, true);
        $where = "des.c_id = $c_id $condition_session";
        return $this->find($where);
    }

    /**
     *
     * @param object $description
     * @return bool
     */
    public function save($description)
    {
        $id = $description->id;
        if (empty($id)) {
            return $this->insert($description);
        } else {
            return $this->update($description);
        }
    }

    /**
     *
     * @param \CourseDescription\CourseDescription $description
     * @return bool 
     */
    public function insert($description)
    {
        $c_id = (int) $description->c_id;

        $session_id = (int) $description->session_id;
        $session_id = $session_id ? $session_id : '0';

        $title = trim($description->title);
        $title = Database::escape_string($title);

        $content = trim($description->content);
        $content = Database::escape_string($content);

        $description_type = (int) $description->description_type;

        $progress = (int) $description->progress;

        $table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
        $sql = "INSERT INTO $table 
                    (c_id, title, content, session_id, description_type, progress)
			    VALUES 
                    ($c_id , '$title', '$content', $session_id, $description_type, $progress)";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $id = Database::insert_id();
            $description->id = $id;

            $_course = api_get_course_info_by_id($c_id);
            $tool = TOOL_COURSE_DESCRIPTION;
            $user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'CourseDescriptionAdded', $user_id);
        }
        return $result;
    }

    /**
     *
     * @param \CourseDescription\CourseDescription $description
     * @return bool 
     */
    function update($description)
    {
        $c_id = (int) $description->c_id;
        $id = (int) $description->id;

        $session_id = (int) $description->session_id;
        $session_id = $session_id ? $session_id : '0';

        $title = trim($description->title);
        $title = Database::escape_string($title);

        $content = trim($description->content);
        $content = Database::escape_string($content);

        $description_type = (int) $description->description_type;

        $progress = (int) $description->progress;

        $table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
        $sql = "UPDATE $table SET
                    title = '$title',
                    content = '$content',
                    session_id = $session_id,
                    description_type = $description_type, 
                    progress = $progress
			    WHERE
                    c_id = $c_id AND
                    id = $id";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $_course = api_get_course_info_by_id($c_id);
            $tool = TOOL_COURSE_DESCRIPTION;
            $user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'CourseDescriptionUpdated', $user_id);
        }
        return $result;
    }

    /**
     * 
     * @param object $description
     * @return boolean 
     */
    public function remove($description)
    {
        $table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
        $c_id = (int) $description->c_id;
        $id = (int) $description->id;

        if (empty($c_id) || empty($id)) {
            return false;
        }

        $sql = "DELETE FROM $table WHERE c_id=$c_id AND id=$id";
        $result = Database :: query($sql);
        if ($result) {
            $tool = TOOL_COURSE_DESCRIPTION;
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