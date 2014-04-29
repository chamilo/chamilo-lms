<?php

/**
 * Returns tool notifications for a specific user. I.e. course activity for courses to 
 * which the user is registered.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CourseNoticeQuery implements IteratorAggregate
{

    /**
     *
     * @param int $user_id
     * @param int $limit
     * @return CourseNotices 
     */
    public static function create($user_id = null, $limit = 20)
    {
        return new self($user_id, $limit);
    }

    protected $user_id;
    protected $limit;

    function __construct($user_id = null, $limit = 20)
    {
        if (empty($user_id))
        {
            global $_user;
            $user_id = $_user['user_id'];
        }
        $this->user_id = (int) $user_id;
        $this->limit = $limit;
    }

    public function get_limit()
    {
        return $this->limit;
    }

    public function get_user_id()
    {
        return $this->user_id;
    }

    function get_tools()
    {
        return array(
            array('name' => TOOL_ANNOUNCEMENT, 'table' => TABLE_ANNOUNCEMENT, 'filter' => ''),
            array('name' => TOOL_DOCUMENT, 'table' => TABLE_DOCUMENT, 'filter' => "tool.filetype = 'file'"),
            array('name' => TOOL_CALENDAR_EVENT, 'table' => TABLE_AGENDA, 'filter' => ''),
            array('name' => TOOL_LINK, 'table' => TABLE_LINK, 'filter' => '')
        );
    }

    public function getIterator()
    {
        return new ArrayIterator($this->get_items());
    }

    private $_get_user_courses = null;

    function get_user_courses()
    {
        if (!is_null($this->_get_user_courses))
        {
            return $this->_get_user_courses;
        }

        $user_id = $this->user_id;
        return $this->_get_user_courses = CourseManager::get_courses_list_by_user_id($user_id);
    }

    function get_user_groups()
    {
        $result = array();

        $user_id = $this->user_id;
        $tbl_group = Database::get_course_table(TABLE_GROUP_USER);
        $tbl_group_tutor = Database::get_course_table(TABLE_GROUP_TUTOR);

        $sql = "(SELECT c_id, group_id FROM $tbl_group WHERE user_id = $user_id)
                UNION DISTINCT
                (SELECT c_id, group_id FROM $tbl_group_tutor WHERE user_id = $user_id)";

        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs))
        {
            $result[] = $row;
        }

        return $result;
    }

    function get_items()
    {
        $result = array();
        $tools = $this->get_tools();
        foreach ($tools as $tool)
        {
            $tool_name = $tool['name'];
            $tool_table = $tool['table'];
            $tool_filter = $tool['filter'];
            $items = $this->get_tool_items($tool_name, $tool_table, $tool_filter);
            $result = array_merge($result, $items);
        }
        usort($result, array($this, 'sort_item'));
        return $result;
    }

    protected function sort_item($left, $right)
    {
        if ($left->lastedit_date == $right->lastedit_date)
        {
            return 0;
        }
        return ($left->lastedit_date <= $right->lastedit_date) ? 1 : -1;
    }

    function get_tool_items($tool_name, $tool_table, $tool_filter = '')
    {
        $item_property_table = Database :: get_course_table(TABLE_ITEM_PROPERTY);
        $course_description = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $tool_table = Database :: get_course_table($tool_table);
        $user_id = $this->user_id;

        //courses
        $course_ids = array();
        $user_courses = $this->get_user_courses();
        foreach ($user_courses as $course)
        {
            $course_ids[] = $course['real_id'];
        }
        $course_ids = implode(',', $course_ids);

        //groups
        $group_filter = array();
        $user_groups = $this->get_user_groups();
        foreach ($user_groups as $group)
        {
            $group_id = $group['group_id'];
            $course_id = $group['c_id'];
            $group_filter[] = "(prop.to_group_id = $group_id AND prop.c_id = $course_id)";
        }
        $group_filter = implode(' OR ', $user_groups);
        $group_filter = $group_filter ? ' OR ' . $group_filter : '';

        //AND prop.lastedit_date > '" . $access_date . "'
        //doc.filetype = 'file'AND         
        //$access_date = $this->get_last_access_date($course->code, TOOL_DOCUMENT);

        $sql = "SELECT  tool.*, 
                        prop.tool, prop.insert_user_id, prop.insert_date, prop.lastedit_date, 
                        prop.ref, prop.lastedit_type, prop.lastedit_user_id, prop.to_group_id, 
                        prop.to_user_id, prop.visibility, prop.start_visible, prop.end_visible, prop.id_session,
                        course.code, course.title AS course_title, des.content AS course_description
                FROM $item_property_table prop, $tool_table tool, $course_table course, $course_description des
                WHERE (
                        course.id = prop.c_id AND
                        des.c_id = course.id AND
                        des.id = 1 AND
                        prop.tool = '$tool_name' AND 
                        tool.id = prop.ref AND 
                        tool.c_id = prop.c_id AND 
                        prop.c_id IN ($course_ids) AND
                        prop.visibility != 2 AND 
                        ((prop.to_user_id IS NULL AND prop.to_group_id = 0) OR (prop.to_user_id = $user_id) $group_filter)
                       )";

        $sql = $tool_filter ? "$sql AND ($tool_filter)" : $sql;
        $sql .= 'ORDER BY lastedit_date DESC';
        $sql .= ' LIMIT ' . $this->limit;
        $rs = Database::query($sql);
        $result = array();
        while ($data = Database::fetch_array($rs, 'ASSOC'))
        {
            $result[] = $this->format_item($data);
        }
        return $result;
    }

    protected function format($items)
    {
        $result = array();
        foreach ($items as $item)
        {
            $result[] = $this->format_item($item);
        }
        return $result;
    }

    protected function format_item($item)
    {
        $result = (object) $item;
        $item = (object) $item;


        if (!isset($result->title))
        {
            if (isset($item->name))
            {
                $result->title = $item->name;
            }
        }

        if (!isset($result->description))
        {
            if (isset($item->content))
            {
                $result->description = $item->content;
            }
            else if (isset($item->comment))
            {
                $result->description = $item->comment;
            }
        }

        $result->course_code = $item->code;
        $result->course_id = $item->c_id;
        return $result;
    }

}