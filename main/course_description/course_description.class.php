<?php

namespace CourseDescription;

/**
 * Object Model for the "Course Description" database table. Allows to 
 * 
 *      - query database
 *      - create/insert new course descriptions
 *      - update/edit course descriptions
 *      - delete course descriptions
 * 
 * Course descriptions used to provide descriptions for course/sessions. 
 * A course/session can have several descriptions associated to it from various types.
 * Course descriptions are primarily made primarily of
 * 
 *      - a title
 *      - some content
 *      - a type (for ex: info, objectives, etc)
 * 
 * Usage:
 *      
 *      Create
 * 
 *      $des = new CourseDescription();
 *      $des->set_title('...');
 *      $des->set_content('...');
 *      $des->set_description_type(...);
 *      $des->insert();
 * 
 *      Update
 * 
 *      $des = CourseDescription::get_by_id(..., ...);
 *      $des->set_title('...');
 *      $des->update();
 * 
 *      Delete
 * 
 *      $des = CourseDescription::get_by_id(..., ...);
 *      $des->delete();
 * 
 * @package chamilo.course_description
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Laurent Opprecht <laurent@opprecht.info> for the University of Geneva
 * @licence /license.txt
 */
class CourseDescription
{

    /**
     * Return the repository.
     * 
     * @return \CourseDescription\CourseDescriptionRepository 
     */
    public static function repository()
    {
        return CourseDescriptionRepository::instance();
    }

    /**
     * Returns the list of all available types
     * 
     * @return array
     */
    public static function get_types()
    {
        return CourseDescriptionType::all();
    }

//    /**
//     * Deprecated (still used by web services)
//     * 
//     * @param int Course id
//     * @deprecated use get_descriptions_by_course
//     * @return array Array of CourseDescriptions
//     */
//    public static function get_descriptions($course_id)
//    {
//        // Get course code
//        $course_info = api_get_course_info_by_id($course_id);
//        if (!empty($course_info)) {
//            $course_id = $course_info['real_id'];
//        } else {
//            return array();
//        }
//        $t_course_desc = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
//        $sql = "SELECT * FROM $t_course_desc WHERE c_id = $course_id AND session_id = '0';";
//        $sql_result = Database::query($sql);
//        $results = array();
//        while ($row = Database::fetch_array($sql_result)) {
//            $desc_tmp = new CourseDescription();
//            $desc_tmp->set_id($row['id']);
//            $desc_tmp->set_title($row['title']);
//            $desc_tmp->set_content($row['content']);
//            $desc_tmp->set_session_id($row['session_id']);
//            $desc_tmp->set_description_type($row['description_type']);
//            $desc_tmp->set_progress($row['progress']);
//            $results[] = $desc_tmp;
//        }
//        return $results;
//    }

    /**
     *
     * @param object $data
     * @return \CourseDescription\CourseDescription
     */
    public static function create($data = null)
    {
        return new self($data);
    }

    protected $c_id;
    protected $id;
    protected $title;
    protected $content;
    protected $session_id;
    protected $description_type;
    protected $progress;
    protected $type = null;

    function __construct($data = null)
    {
        if ($data) {
            foreach ($this as $key => $value) {
                if (isset($data->{$key})) {
                    $this->{$key} = $data->{$key};
                }
            }
        }
    }

    function __get($name)
    {
        $f = array($this, "get_$name");
        return call_user_func($f);
    }

    function __isset($name)
    {
        $f = array($this, "get_$name");
        return is_callable($f);
    }

    function __set($name, $value)
    {
        $f = array($this, "set_$name");
        if (!is_callable($f)) {
            return;
        }
        call_user_func($f, $value);
    }

//    /**
//     * Insert the course description object into the course_description table.
//     * 
//     * @return bool True on success, false on failure
//     */
//    public function insert()
//    {
//        $course_id = $this->get_c_id();
//        $description_type = $this->get_description_type();
//        $title = Database::escape_string($this->get_title());
//        $content = Database::escape_string($this->get_content());
//        $progress = $this->get_progress();
//        $progress = $progress ? $progress : '0';
//        $session_id = $this->get_session_id();
//
//        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
//        $sql = "INSERT IGNORE INTO $table SET
//				c_id 				= $course_id, 
//				description_type	= $description_type, 
//				title 				= '$title', 
//				content 			= '$content', 
//				progress 			= $progress, 
//				session_id          = $session_id";
//
//        Database::query($sql);
//
//        $id = Database::insert_id();
//        if (empty($id)) {
//            return false;
//        }
//        $this->id = $id;
//
//        /**
//         * @todo: course info should come from c_id 
//         */
//        api_item_property_update(api_get_course_info(), TOOL_COURSE_DESCRIPTION, $id, 'CourseDescriptionAdded', api_get_user_id());
//
//        return true;
//    }
//    /**
//     * Insert a row like history inside track_e_item_property table
//     * 
//     * @param 	int 	description type
//     * @return  int		affected rows
//     */
//    public function insert_stats($description_type)
//    {
//        $tbl_stats_item_property = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
//        $description_id = $this->get_id_by_description_type($description_type);
//        $course_id = api_get_real_course_id();
//        $course_code = api_get_course_id();
//        $item_property_id = api_get_item_property_id($course_code, TOOL_COURSE_DESCRIPTION, $description_id);
//        $sql = "INSERT IGNORE INTO $tbl_stats_item_property SET
//				c_id				= " . api_get_course_int_id() . ",
//				course_id 			= '$course_id',
//			 	item_property_id 	= '$item_property_id',
//			 	title 				= '" . Database::escape_string($this->title) . "',
//			 	content 			= '" . Database::escape_string($this->content) . "',
//			 	progress 			= '" . intval($this->progress) . "',
//			 	lastedit_date 		= '" . date('Y-m-d H:i:s') . "',
//			 	lastedit_user_id 	= '" . api_get_user_id() . "',
//			 	session_id			= '" . intval($this->session_id) . "'";
//        Database::query($sql);
//        $affected_rows = Database::affected_rows();
//        return $affected_rows;
//    }
//    /**
//     * Update a course description object to the database.
//     * 
//     * @return bool True on success, false on failure.
//     */
//    public function update()
//    {
//        $course_id = $this->get_c_id();
//        $id = $this->get_id();
//        $description_type = $this->get_description_type();
//        $title = Database::escape_string($this->get_title());
//        $content = Database::escape_string($this->get_content());
//        $progress = $this->get_progress();
//        $progress = $progress ? $progress : '0';
//        $session_id = $this->get_session_id();
//
//        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
//        $sql = "UPDATE $table SET  
//						title       = '$title', 
//						content     = '$content', 
//						progress    = $progress 
//				WHERE 	id          = $id AND 
//						c_id = $course_id ";
//
//        Database::query($sql);
//        $result = (bool) Database::affected_rows();
//
//        if ($result) {
//            //insert into item_property
//            /**
//             * @todo: course info should come from c_id 
//             */
//            api_item_property_update(api_get_course_info(), TOOL_COURSE_DESCRIPTION, $this->id, 'CourseDescriptionUpdated', api_get_user_id());
//        }
//        return $result;
//    }
//    /**
//     * Delete a course description object from the database.
//     * 
//     * @return bool True on success false on failure
//     */
//    public function delete()
//    {
//        $table = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
//        $course_id = $this->get_c_id();
//        $id = $this->get_id();
//
//        $sql = "DELETE FROM $table WHERE c_id = $course_id AND id = $id";
//        Database::query($sql);
//        $result = (bool) Database::affected_rows();
//        if ($result) {
//            /**
//             * @todo: should get course info from $this->c_id 
//             */
//            api_item_property_update(api_get_course_info(), TOOL_COURSE_DESCRIPTION, $this->id, 'CourseDescriptionDeleted', api_get_user_id());
//        }
//        return $result;
//    }
//    /**
//     * Get description id by description type
//     * @param int description type
//     * @return int description id
//     */
//    public function get_id_by_description_type($description_type)
//    {
//        $tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
//        $course_id = api_get_course_int_id();
//
//        $sql = "SELECT id FROM $tbl_course_description WHERE c_id = $course_id AND description_type = '" . intval($description_type) . "'";
//        $rs = Database::query($sql);
//        $row = Database::fetch_array($rs);
//        $description_id = $row['id'];
//        return $description_id;
//    }
//    /**
//     * get thematic progress in porcent for a course,
//     * first you must set session_id property with the object CourseDescription
//     * @param bool		true for showing a icon about the progress, false otherwise (optional)
//     * @param int		Description type (optional)
//     * @return string   img html
//     */
//    public function get_progress_porcent($with_icon = false, $description_type = THEMATIC_ADVANCE)
//    {
//        $tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
//        $session_id = intval($session_id);
//        $course_id = api_get_course_int_id();
//
//        $sql = "SELECT progress FROM $tbl_course_description WHERE c_id = $course_id AND description_type = '" . intval($description_type) . "' AND session_id = '" . intval($this->session_id) . "' ";
//        $rs = Database::query($sql);
//        $progress = '';
//        $img = '';
//        $title = '0%';
//        $image = 'level_0.png';
//        if (Database::num_rows($rs) > 0) {
//            $row = Database::fetch_array($rs);
//            $progress = $row['progress'] . '%';
//            $image = 'level_' . $row['progress'] . '.png';
//        }
//        if ($with_icon) {
//            $img = Display::return_icon($image, get_lang('ThematicAdvance'), array('style' => 'vertical-align:middle'));
//        }
//        $progress = $img . $progress;
//        return $progress;
//    }
//    /**
//     * Get description titles by default
//     * @return array
//     */
//    public function get_default_description_title()
//    {
//        $default_description_titles = array();
//        $default_description_titles[1] = get_lang('GeneralDescription');
//        $default_description_titles[2] = get_lang('Objectives');
//        $default_description_titles[3] = get_lang('Topics');
//        $default_description_titles[4] = get_lang('Methodology');
//        $default_description_titles[5] = get_lang('CourseMaterial');
//        $default_description_titles[6] = get_lang('HumanAndTechnicalResources');
//        $default_description_titles[7] = get_lang('Assessment');
//
//        $default_description_titles[8] = get_lang('Other');
//        return $default_description_titles;
//    }

    /**
     * The course id. 
     * 
     * @see get_course() property to get access to the course object
     * @return int
     */
    public function get_c_id()
    {
        return $this->c_id;
    }

    /**
     * @return void
     */
    public function set_c_id($value)
    {
        $this->c_id = intval($value);
    }

    /**
     * The id of the object.
     * 
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * 
     * @return void
     */
    public function set_id($value)
    {
        $this->id = intval($value);
    }

    /**
     * The title of the course description.
     * 
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * 
     * @return void
     */
    public function set_title($title)
    {
        $this->title = $title;
    }

    /**
     * The content/description of the course description.
     * @return string
     */
    public function get_content()
    {
        return $this->content;
    }

    /**
     * 
     * @return void
     */
    public function set_content($content)
    {
        $this->content = $content;
    }

    /**
     * The session id the object is associated with.
     * 
     * @return int
     */
    public function get_session_id()
    {
        return $this->session_id;
    }

    /**
     * 
     * @return void
     */
    public function set_session_id($value)
    {
        $this->session_id = intval($value);
    }

    /**
     * The type of the course description. Should match one of the id returns
     * by CourseDescription::get_types().
     * 
     * @return int
     */
    public function get_description_type()
    {
        return $this->description_type;
    }

    /**
     * 
     * @return void
     */
    public function set_description_type($value)
    {
        $this->description_type = intval($value);
    }

    /**
     * ???
     * @return int
     */
    public function get_progress()
    {
        return $this->progress;
    }

    /**
     * 
     * @return void
     */
    public function set_progress($value)
    {
        $this->progress = intval($value);
    }

    /**
     * Return one type from its id
     * 
     * @return \CourseDescription\CourseDescriptionType 
     */
    public function get_type()
    {
        $type_id = $this->get_description_type();
        if ($this->type && $this->type->id == $type_id) {
            return $this->type;
        }
        $this->type = CourseDescriptionType::repository()->find_one_by_id($type_id);
        return $this->type;
    }

    /**
     * Returns the course object this object is associated with.
     * Lazy loaded from the value returned by get_c_id().
     * @return \Model\Course
     */
    public function get_course()
    {
        if ($this->course && $this->course->get_id() == $this->c_id) {
            return $this->course;
        }

        $this->course = Course::get_by_id($this->c_id);
        return $this->course;
    }

    /**
     * The item property this object is associated with.
     * 
     * @return \Model\ItemProperty
     */
    public function get_item_property()
    {
        if ($this->item_property && $this->item_property->get_c_id() == $this->c_id && $this->item_property->get_ref() == $this->id) {
            return $this->item_property;
        }

        $this->item_property = ItemProperty::get_by_ref($this->id, TOOL_COURSE_DESCRIPTION);
        return $this->item_property;
    }

}

//
///**
// * The common routes (urls) for course description objects: 
// * 
// *      - create new course description
// *      - edit course description
// *      - delete course description 
// * 
// * @author Laurent Opprecht <laurent@opprecht.info> for the University of Geneva
// * @licence /license.txt
// */
//class CourseDescriptionRoutes
//{
//
//    /**
//     *
//     * @return CourseDescriptionRoutes 
//     */
//    public static function instance()
//    {
//        static $result = null;
//        if (empty($result)) {
//            $result = new self();
//        }
//        return $result;
//    }
//
//    protected function __construct()
//    {
//        ;
//    }
//
//    /**
//     * Returns the url used to create a new course description from a specific type.
//     * 
//     * @param CourseDescriptionType $type
//     * @param bool $html True to html escape the url, false otherwise.
//     * @return string
//     */
//    function create($type = null, $html = true)
//    {
//        $type = is_object($type) ? $type->get_id() : (int) $type;
//
//        $params = Uri::course_params();
//        $params['action'] = 'add';
//        if ($type) {
//            $params['description_type'] = $type;
//        }
//        $result = Chamilo::url('/main/course_description/index.php', $params, $html);
//        return $result;
//    }
//
//    /**
//     * The url to edit a course description object
//     * 
//     * @param CourseDescription $description
//     * @param bool $html True to html escape the url, false otherwise.
//     * @return string  
//     */
//    function edit($description, $html = true)
//    {
//        $params = array();
//        $params['id'] = $description->get_id();
//        $params['cidReq'] = api_get_course_id();
//        $params['id_session'] = $description->get_session_id();
//        $params['description_type'] = $description->get_description_type();
//        $params['action'] = 'edit';
//        $result = Chamilo::url('/main/course_description/index.php', $params, $html);
//        return $result;
//    }
//
//    /**
//     * The index route to list all course descriptions for the current course/session
//     * 
//     * @param bool $html True to html escape the url, false otherwise.
//     * @return type 
//     */
//    function index($html = true)
//    {
//        $params = Uri::course_params();
//        $result = Chamilo::url('/main/course_description/index.php', $params, $html);
//        return $result;
//    }
//
//    /**
//     * Url to delete a course description object.
//     * 
//     * @param CourseDescription $description
//     * @param bool $html True to html escape the url, false otherwise.
//     * @return string  
//     */
//    function delete($description, $html = true)
//    {
//        $params = array();
//        $params['id'] = $description->get_id();
//        $params['cidReq'] = api_get_course_id();
//        $params['id_session'] = $description->get_session_id();
//        $params['description_type'] = $description->get_description_type();
//        $params['action'] = 'delete';
//        $result = Chamilo::url('/main/course_description/index.php', $params, $html);
//        return $result;
//    }
//
//}
//
