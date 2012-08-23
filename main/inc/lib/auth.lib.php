<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains a class used like library provides functions for auth tool. It's also used like model to courses_controller (MVC pattern)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.auth
 */
/**
 * Code
 */
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';

/**
 * Auth can be used to instanciate objects or as a library to manage courses
 * @package chamilo.auth
 */
class Auth
{
    /**
     * Constructor
     */
    public function __construct() {        
    }
        
    /**
     * retrieves all the courses that the user has already subscribed to
     * @param   int User id
     * @return  array an array containing all the information of the courses of the given user
    */
    public function get_courses_of_user($user_id) {
        $TABLECOURS                 = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER             = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_COURSE_FIELD         = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $TABLE_COURSE_FIELD_VALUE	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

        // get course list auto-register
        $sql = "SELECT course_code FROM $TABLE_COURSE_FIELD_VALUE tcfv INNER JOIN $TABLE_COURSE_FIELD tcf ON " .
              " tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";

        $special_course_result = Database::query($sql);
        if (Database::num_rows($special_course_result)>0) {
            $special_course_list = array();
            while ($result_row = Database::fetch_array($special_course_result)) {
                    $special_course_list[] = '"'.$result_row['course_code'].'"';
            }
        }
        $without_special_courses = '';
        if (!empty($special_course_list)) {
                $without_special_courses = ' AND course.code NOT IN ('.implode(',',$special_course_list).')';
        }

        // Secondly we select the courses that are in a category (user_course_cat<>0) and sort these according to the sort of the category
        $user_id = intval($user_id);
        $sql_select_courses = "SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
                                      course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
                                      course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                                FROM $TABLECOURS course, $TABLECOURSUSER  course_rel_user
                                WHERE course.code = course_rel_user.course_code
                                AND   course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                                AND   course_rel_user.user_id = '".$user_id."' $without_special_courses
                                ORDER BY course_rel_user.sort ASC";
        $result = Database::query($sql_select_courses);
        while ($row = Database::fetch_array($result)) {
            //we only need the database name of the course
            $courses[] = array('db' => $row['db'], 'code' => $row['k'], 'visual_code' => $row['vc'], 'title' => $row['i'], 'directory' => $row['dir'], 'status' => $row['status'], 'tutor' => $row['t'], 'subscribe' => $row['subscr'], 'unsubscribe' => $row['unsubscr'], 'sort' => $row['sort'], 'user_course_category' => $row['user_course_cat']);
        }
        return $courses;
    }

    /**
     * retrieves the user defined course categories
     * @return array containing all the IDs of the user defined courses categories, sorted by the "sort" field
    */
    public function get_user_course_categories() {
        $user_id = api_get_user_id();
        $table_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT * FROM ".$table_category." WHERE user_id=$user_id ORDER BY sort ASC";
        $result = Database::query($sql);
        $output = array();
        while ($row = Database::fetch_array($result)) {
                $output[] = $row;
        }
        return $output;
    }

    /**
    * This function get all the courses in the particular user category;
    * @param  int   User category id
    * @return string: the name of the user defined course category
    */
    public function get_courses_in_category() {

        $user_id = api_get_user_id();

        // table definitions
        $TABLECOURS     = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_USER_COURSE_CATEGORY = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $TABLE_COURSE_FIELD 	= Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
        $TABLE_COURSE_FIELD_VALUE	= Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

        // get course list auto-register
        $sql = "SELECT course_code FROM $TABLE_COURSE_FIELD_VALUE tcfv INNER JOIN $TABLE_COURSE_FIELD tcf ON " .
                        " tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";

        $special_course_result = Database::query($sql);
        if(Database::num_rows($special_course_result)>0) {
                $special_course_list = array();
                while ($result_row = Database::fetch_array($special_course_result)) {
                        $special_course_list[] = '"'.$result_row['course_code'].'"';
                }
        }
        $without_special_courses = '';
        if (!empty($special_course_list)) {
                $without_special_courses = ' AND course.code NOT IN ('.implode(',',$special_course_list).')';
        }

        $sql_select_courses = "SELECT course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
                                                                course.title title, course.tutor_name tutor, course.db_name, course.directory, course_rel_user.status status,
                                                                course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                                        FROM    $TABLECOURS       course,
                                                                                $TABLECOURSUSER  course_rel_user
                                        WHERE course.code = course_rel_user.course_code
                                        AND  course_rel_user.user_id = '".$user_id."'
                                        AND  course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                                            $without_special_courses
                                        ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";
        $result = Database::query($sql_select_courses);
        $number_of_courses = Database::num_rows($result);
        $data = array();
        while ($course = Database::fetch_array($result)) {
            $data[$course['user_course_cat']][] = $course;
        }
        return $data;

    }

    /**
     * stores  the changes in a course category (moving a course to a different course category)
     * @param  string    Course code
     * @param  int       Category id
     * @return bool      True if it success
    */
    public function store_changecoursecategory($course_code, $newcategory) {
        $course_code = Database::escape_string($course_code);
        $newcategory = intval($newcategory);
        $current_user = api_get_user_id();
        $result = false;

        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $max_sort_value = api_max_sort_value($newcategory, $current_user); // max_sort_value($newcategory);
        Database::query("UPDATE $TABLECOURSUSER SET user_course_cat='".$newcategory."', sort='".($max_sort_value + 1)."' WHERE course_code='".$course_code."' AND user_id='".$current_user."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." ");

        if (Database::affected_rows()) { $result = true; }
        return $result;
    }

    /**
     * moves the course one place up or down
     * @param   string    Direction (up/down)
     * @param   string    Course code
     * @param   int       Category id
     * @return  bool      True if it success
    */
    public function move_course($direction, $course2move, $category) {

        // definition of tables
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $current_user_id = api_get_user_id();
        $all_user_courses = $this->get_courses_of_user($current_user_id);
        $result = false;

        // we need only the courses of the category we are moving in
        $user_courses = array();
        foreach ($all_user_courses as $key => $course) {
                if ($course['user_course_category'] == $category) {
                        $user_courses[] = $course;
                }
        }

        foreach ($user_courses as $key => $course) {
                if ($course2move == $course['code']) {
                        // source_course is the course where we clicked the up or down icon
                        $source_course = $course;
                        // target_course is the course before/after the source_course (depending on the up/down icon)
                        if ($direction == 'up') {
                                $target_course = $user_courses[$key - 1];
                        } else {
                                $target_course = $user_courses[$key + 1];
                        }
                } 
        }

        if (count($target_course) > 0 && count($source_course) > 0) {
                $sql_update1 = "UPDATE $TABLECOURSUSER SET sort='".$target_course['sort']."' WHERE course_code='".$source_course['code']."' AND user_id='".$current_user_id."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
                $sql_update2 = "UPDATE $TABLECOURSUSER SET sort='".$source_course['sort']."' WHERE course_code='".$target_course['code']."' AND user_id='".$current_user_id."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." ";                    
                Database::query($sql_update2);
                Database::query($sql_update1);
                if (Database::affected_rows()) { $result = true; }
        }
        return $result;
    }

    /**
     * Moves the course one place up or down
     * @param string    Direction up/down
     * @param string    Category id
     * @return bool     True If it success
     */
    public function move_category($direction, $category2move) {

        // the database definition of the table that stores the user defined course categories
        $table_user_defined_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

        $current_user_id = api_get_user_id();
        $user_coursecategories = $this->get_user_course_categories();
        $user_course_categories_info = $this->get_user_course_categories_info();
        $result = false;

        foreach ($user_coursecategories as $key => $category) {
                $category_id = $category['id'];
                if ($category2move == $category_id) {
                        // source_course is the course where we clicked the up or down icon
                        $source_category = $user_course_categories_info[$category2move];
                        // target_course is the course before/after the source_course (depending on the up/down icon)
                        if ($direction == 'up') {
                                $target_category = $user_course_categories_info[$user_coursecategories[$key - 1]['id']];
                        } else {
                                $target_category = $user_course_categories_info[$user_coursecategories[$key + 1]['id']];
                        }
                }
        }

        if (count($target_category) > 0 && count($source_category) > 0) {
                $sql_update1="UPDATE $table_user_defined_category SET sort='".Database::escape_string($target_category['sort'])."' WHERE id='".intval($source_category['id'])."' AND user_id='".$current_user_id."'";
                $sql_update2="UPDATE $table_user_defined_category SET sort='".Database::escape_string($source_category['sort'])."' WHERE id='".intval($target_category['id'])."' AND user_id='".$current_user_id."'";
                Database::query($sql_update2);
                Database::query($sql_update1);
                if (Database::affected_rows()) {
                    $result = true;
                }
        }
        return $result;
    }

    /**
     * Retrieves the user defined course categories and all the info that goes with it
     * @return array containing all the info of the user defined courses categories with the id as key of the array
    */
    public function get_user_course_categories_info() {
        $current_user_id = api_get_user_id();
        $table_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT * FROM ".$table_category." WHERE user_id='".$current_user_id."' ORDER BY sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
                $output[$row['id']] = $row;
        }
        return $output;
    }

    /**
     * Updates the user course category in the chamilo_user database
     * @param   string  Category title
     * @param   int     Category id
     * @return  bool    True if it success
    */
    public function store_edit_course_category($title, $category_id) {
        // protect data
        $title = Database::escape_string($title);
        $category_id = intval($category_id);
        $result = false;
        $tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql_update = "UPDATE $tucc SET title='".api_htmlentities($title, ENT_QUOTES, api_get_system_encoding())."' WHERE id='".$category_id."'";
        Database::query($sql_update);
        if (Database::affected_rows()) { $result = true; }
        return $result;
    }

    /**
     * deletes a course category and moves all the courses that were in this category to main category
     * @param   int     Category id
     * @return  bool    True if it success
    */
    public function delete_course_category($category_id) {
        $current_user_id = api_get_user_id();
        $tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $category_id = intval($category_id);
        $result = false;
        $sql_delete = "DELETE FROM $tucc WHERE id='".$category_id."' and user_id='".$current_user_id."'";
        Database::query($sql_delete);
        if (Database::affected_rows()) { $result = true; }
        $sql_update = "UPDATE $TABLECOURSUSER SET user_course_cat='0' WHERE user_course_cat='".$category_id."' AND user_id='".$current_user_id."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." ";            
        Database::query($sql_update);            
        return $result;
    }

    /**
     * unsubscribe the user from a given course
     * @param   string  Course code
     * @return  bool    True if it success
    */
    public function remove_user_from_course($course_code) {

        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        // protect variables
        $current_user_id = api_get_user_id();
        $course_code = Database::escape_string($course_code);
        $result = true;

        // we check (once again) if the user is not course administrator
        // because the course administrator cannot unsubscribe himself
        // (s)he can only delete the course
        $sql_check = "SELECT * FROM $tbl_course_user WHERE user_id='".$current_user_id."' AND course_code='".$course_code."' AND status='1' ";
        $result_check = Database::query($sql_check);
        $number_of_rows = Database::num_rows($result_check);
        if ($number_of_rows > 0) {
                $result = false;
        }

        CourseManager::unsubscribe_user($current_user_id, $course_code);
            return $result;
    }

    /**
     * stores the user course category in the chamilo_user database
     * @param   string  Category title
     * @return  bool    True if it success
    */
    public function store_course_category($category_title) {

        $tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

        // protect data
        $current_user_id = api_get_user_id();
        $category_title = Database::escape_string($category_title);
        $result = false;

        // step 1: we determine the max value of the user defined course categories
        $sql = "SELECT sort FROM $tucc WHERE user_id='".$current_user_id."' ORDER BY sort DESC";
        $rs_sort = Database::query($sql);
        $maxsort = Database::fetch_array($rs_sort);
        $nextsort = $maxsort['sort'] + 1;

        // step 2: we check if there is already a category with this name, if not we store it, else we give an error.
        $sql = "SELECT * FROM $tucc WHERE user_id='".$current_user_id."' AND title='".$category_title."'ORDER BY sort DESC";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) == 0) {
                $sql_insert = "INSERT INTO $tucc (user_id, title,sort) VALUES ('".$current_user_id."', '".api_htmlentities($category_title, ENT_QUOTES, api_get_system_encoding())."', '".$nextsort."')";
                Database::query($sql_insert);
                if (Database::affected_rows()) { $result = true; }
        } else {
                $result = false;
        }
        return $result;
    }

    /**
     * Counts the number of courses in a given course category
     * @param   string  Category code
     * @return  int     Count of courses
    */
    public function count_courses_in_category($category_code) {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLE_COURSE_FIELD 			= Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
        $TABLE_COURSE_FIELD_VALUE		= Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

        // get course list auto-register
        $sql = "SELECT course_code FROM $TABLE_COURSE_FIELD_VALUE tcfv INNER JOIN $TABLE_COURSE_FIELD tcf ON " .
                        " tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";

        $special_course_result = Database::query($sql);
        if(Database::num_rows($special_course_result)>0) {
                $special_course_list = array();
                while ($result_row = Database::fetch_array($special_course_result)) {
                        $special_course_list[] = '"'.$result_row['course_code'].'"';
                }
        }
        $without_special_courses = '';
        if (!empty($special_course_list)) {
                $without_special_courses = ' AND course.code NOT IN ('.implode(',',$special_course_list).')';
        }

        $sql = "SELECT * FROM $tbl_course WHERE category_code".(empty($category_code) ? " IS NULL" : "='".$category_code."'").$without_special_courses;
        // Showing only the courses of the current Dokeos access_url_id.
        global $_configuration;
        if ($_configuration['multiple_access_urls']) {
                $url_access_id = api_get_current_access_url_id();
                if ($url_access_id != -1) {
                        $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                        $sql = "SELECT * FROM $tbl_course as course INNER JOIN $tbl_url_rel_course as url_rel_course
                                        ON (url_rel_course.course_code=course.code)
                                        WHERE access_url_id = $url_access_id AND category_code".(empty($category_code) ? " IS NULL" : "='".$category_code."'").$without_special_courses;
                }
        }
        return Database::num_rows(Database::query($sql));
    }

    /**
     * get the browsing of the course categories (faculties)
     * @return array    array containing a list with all the categories and subcategories(if needed)
     */
    public function browse_course_categories() {
        $tbl_courses_nodes = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT * FROM $tbl_courses_nodes ORDER BY tree_pos ASC";
        $result = Database::query($sql);
        $categories = array();
        while ($row = Database::fetch_array($result)) {
            $count_courses = $this->count_courses_in_category($row['code']);
            $row['count_courses'] = $count_courses;
            if (!isset($row['parent_id'])) {
                $categories[0][$row['tree_pos']] = $row;
            } else {
                $categories[$row['parent_id']][$row['tree_pos']] = $row;
            }
        }
        return $categories;
    }   

    /**
     * Display all the courses in the given course category. I could have used a parameter here
     * @param   string  Category code
     * @return  array   Courses data
    */
    public function browse_courses_in_category($category_code, $random_value = null) {
        global $_configuration;        
        $tbl_course               = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLE_COURSE_FIELD       = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $TABLE_COURSE_FIELD_VALUE = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
                

        // Get course list auto-register
        $sql = "SELECT course_code FROM $TABLE_COURSE_FIELD_VALUE tcfv INNER JOIN $TABLE_COURSE_FIELD tcf ON tcfv.field_id = tcf.id 
                WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";

        $special_course_result = Database::query($sql);
        if (Database::num_rows($special_course_result)>0) {
            $special_course_list = array();
            while ($result_row = Database::fetch_array($special_course_result)) {
                $special_course_list[] = '"'.$result_row['course_code'].'"';
            }
        }
        
        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.code NOT IN ('.implode(',',$special_course_list).')';
        }        
        
        if (!empty($random_value)) {
            $random_value = intval($random_value);
                  
            $sql = "SELECT COUNT(*) FROM $tbl_course";
            $result = Database::query($sql);
            list($num_records) = Database::fetch_row($result);
            
            if ($_configuration['multiple_access_urls']) {
            
                $url_access_id = api_get_current_access_url_id();                
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            
                $sql = "SELECT COUNT(*) FROM $tbl_course course INNER JOIN $tbl_url_rel_course as url_rel_course ON (url_rel_course.course_code=course.code) 
                        WHERE access_url_id = $url_access_id ";
                $result = Database::query($sql);
                list($num_records) = Database::fetch_row($result);
                
                $sql = "SELECT course.id FROM $tbl_course course INNER JOIN $tbl_url_rel_course as url_rel_course 
                        ON (url_rel_course.course_code=course.code) 
                        WHERE   access_url_id = $url_access_id AND 
                                RAND()*$num_records< $random_value
                                $without_special_courses ORDER BY RAND() LIMIT 0, $random_value";                                
            } else {
                $sql = "SELECT id FROM $tbl_course course WHERE RAND()*$num_records< $random_value $without_special_courses ORDER BY RAND() LIMIT 0, $random_value";
            }
            
            $result = Database::query($sql);
            $id_in = null;
            while (list($id) = Database::fetch_row($result)){
                if ($id_in) {
                    $id_in.=",$id"; 
                } else { 
                    $id_in="$id";
                }
            }            
            $sql = "SELECT * FROM $tbl_course WHERE id IN($id_in)";
            
        } else {
            $category_code = Database::escape_string($category_code);
            $sql = "SELECT * FROM $tbl_course WHERE category_code='$category_code' $without_special_courses ORDER BY title ";
            
            //showing only the courses of the current Chamilo access_url_id
            if ($_configuration['multiple_access_urls']) {
                $url_access_id = api_get_current_access_url_id();                
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sql = "SELECT * FROM $tbl_course as course INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.course_code=course.code)
                        WHERE access_url_id = $url_access_id AND category_code='$category_code' $without_special_courses ORDER BY title";                
            }            
        }

        $result = Database::query($sql);
        $courses = array();
        while ($row = Database::fetch_array($result)) {            
            $row['registration_code'] = !empty($row['registration_code']);
            $count_users = CourseManager::get_users_count_in_course($row['code']);                
            $count_connections_last_month = Tracking::get_course_connections_count($row['code'], 0, api_get_utc_datetime(time()-(30*86400)));

            if ($row['tutor_name'] == '0') {
                $row['tutor_name'] = get_lang('NoManager');
            }                
            $point_info = CourseManager::get_course_ranking($row['id'], 0);
            $courses[] = array(
                                'real_id'           => $row['id'],
                                'point_info'		=> $point_info,                                    
                                'code'              => $row['code'],
                                'directory'         => $row['directory'],
                                'db'                => $row['db_name'],
                                'visual_code'       => $row['visual_code'],
                                'title'             => $row['title'],
                                'tutor'             => $row['tutor_name'],
                                'subscribe'         => $row['subscribe'],
                                'unsubscribe'       => $row['unsubscribe'],
                                'registration_code' => $row['registration_code'],
                                'creation_date'     => $row['creation_date'],
                                'visibility'        => $row['visibility'],
                                'count_users'       => $count_users,
                                'count_connections' => $count_connections_last_month
                                );
        }

        return $courses;
    }

    /**
     * Search the courses database for a course that matches the search term.
     * The search is done on the code, title and tutor field of the course table.
     * @param string $search_term: the string that the user submitted, what we are looking for
     * @return array an array containing a list of all the courses (the code, directory, dabase, visual_code, title, ... ) matching the the search term.
    */
    public function search_courses($search_term) {
        $TABLECOURS                 = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLE_COURSE_FIELD         = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
        $TABLE_COURSE_FIELD_VALUE	= Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

        // get course list auto-register
        $sql = "SELECT course_code FROM $TABLE_COURSE_FIELD_VALUE tcfv INNER JOIN $TABLE_COURSE_FIELD tcf ON tcfv.field_id =  tcf.id 
                WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";

        $special_course_result = Database::query($sql);
        if (Database::num_rows($special_course_result)>0) {
            $special_course_list = array();
            while ($result_row = Database::fetch_array($special_course_result)) {
                    $special_course_list[] = '"'.$result_row['course_code'].'"';
            }
        }
        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.code NOT IN ('.implode(',',$special_course_list).')';
        }

        $search_term_safe = Database::escape_string($search_term);
        $sql_find = "SELECT * FROM $TABLECOURS WHERE (code LIKE '%".$search_term_safe."%' OR title LIKE '%".$search_term_safe."%' OR tutor_name LIKE '%".$search_term_safe."%') $without_special_courses ORDER BY title, visual_code ASC";

        global $_configuration;
        if ($_configuration['multiple_access_urls']) {
                $url_access_id = api_get_current_access_url_id();
                if ($url_access_id != -1) {
                        $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                        $sql_find = "SELECT * FROM $TABLECOURS as course INNER JOIN $tbl_url_rel_course as url_rel_course
                                        ON (url_rel_course.course_code=course.code)
                                        WHERE access_url_id = $url_access_id AND  (code LIKE '%".$search_term_safe."%' OR title LIKE '%".$search_term_safe."%' OR tutor_name LIKE '%".$search_term_safe."%' ) $without_special_courses ORDER BY title, visual_code ASC ";
                }
        }
        $result_find = Database::query($sql_find);
        $courses = array();
        while ($row = Database::fetch_array($result_find)) {
            $row['registration_code'] = !empty($row['registration_code']);
            $count_users = count(CourseManager::get_user_list_from_course_code($row['code']));
            $count_connections_last_month = Tracking::get_course_connections_count($row['code'], 0, api_get_utc_datetime(time()-(30*86400)));
            $courses[] = array(
                                'code'              => $row['code'],
                                'directory'         => $row['directory'],
                                'db'                => $row['db_name'],
                                'visual_code'       => $row['visual_code'],
                                'title'             => $row['title'],
                                'tutor'             => $row['tutor_name'],
                                'subscribe'         => $row['subscribe'],
                                'unsubscribe'       => $row['unsubscribe'],
                                'registration_code' => $row['registration_code'],
                                'creation_date'     => $row['creation_date'],
                                'visibility'        => $row['visibility'],
                                'count_users'       => $count_users,
                                'count_connections' => $count_connections_last_month
                                );
        }
        return $courses;
    }

     /**
      * Subscribe the user to a given course
      * @param string Course code
      * @return string  Message about results
     */
    public function subscribe_user($course_code) {
        global $_user;

        $all_course_information = CourseManager::get_course_information($course_code);                        
        if ($all_course_information['registration_code'] == '' || $_POST['course_registration_code'] == $all_course_information['registration_code']) {
                if (api_is_platform_admin()) {
                        $status_user_in_new_course = COURSEMANAGER;
                } else {
                        $status_user_in_new_course=null;
                }
                if (CourseManager::add_user_to_course($_user['user_id'], $course_code, $status_user_in_new_course)) {
                        $send = api_get_course_setting('email_alert_to_teacher_on_new_user_in_course', $course_code);
                        if ($send == 1) {
                                CourseManager::email_to_tutor($_user['user_id'], $course_code, $send_to_tutor_also = false);
                        } else if ($send == 2){
                                CourseManager::email_to_tutor($_user['user_id'], $course_code, $send_to_tutor_also = true);
                        }
                        return get_lang('EnrollToCourseSuccessful');
                } else {
                        return get_lang('ErrorContactPlatformAdmin');
                }
        } else {

                $return = '';
                if (isset($_POST['course_registration_code']) && $_POST['course_registration_code'] != $all_course_information['registration_code']) {
                        return false;
                }
                $return .= get_lang('CourseRequiresPassword').'<br />';
                $return .= $all_course_information['visual_code'].' - '.$all_course_information['title'];

                $return .= "<form action=\"".api_get_path(WEB_CODE_PATH)."auth/courses.php?action=subscribe_course&sec_token=".$_SESSION['sec_token']."&subscribe_course=".$all_course_information['code']."&category_code=".$all_course_information['category_code']."   \" method=\"post\">";
                $return .= '<input type="hidden" name="token" value="'.$_SESSION['sec_token'].'" />';
                //$return .= "<input type=\"hidden\" name=\"subscribe\" value=\"".$all_course_information['code']."\" />";
                //$return .= "<input type=\"hidden\" name=\"category_code\" value=\"".$all_course_information['category_code']."\" />";
                $return .= "<input type=\"text\" name=\"course_registration_code\" value=\"".$_POST['course_registration_code']."\" />";
                $return .= "<input type=\"submit\" name=\"submit_course_registration_code\" value=\"OK\" alt=\"".get_lang('SubmitRegistrationCode')."\" /></form>";
                return $return;
        }
    }
}
