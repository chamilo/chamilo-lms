<?php
/* For licensing terms, see /license.txt*/

/**
 * This is the course library for Chamilo.
 *
 * All main course functions should be placed here.
 *
 * Many functions of this library deal with providing support for
 * virtual/linked/combined courses (this was already used in several universities
 * but not available in standard Chamilo).
 *
 * The implementation changed, initially a course was a real course
 * if target_course_code was 0 , this was changed to NULL.
 * There are probably some places left with the wrong code.
 *
 * @package chamilo.library
 */

/**
    DOCUMENTATION
    (list not up to date, you can auto generate documentation with phpDocumentor)

    CourseManager::get_real_course_code_select_html($element_name, $has_size=true, $only_current_user_courses=true)
    CourseManager::check_parameter($parameter, $error_message)
    CourseManager::check_parameter_or_fail($parameter, $error_message)
    CourseManager::course_code_exists($wanted_course_code)
    CourseManager::get_real_course_list()
    CourseManager::get_virtual_course_list()

    GENERAL COURSE FUNCTIONS
    CourseManager::get_access_settings($course_code)
    CourseManager::set_course_tool_visibility($tool_table_id, $visibility)
    CourseManager::get_user_in_course_status($user_id, $course_code)
    CourseManager::add_user_to_course($user_id, $course_code)
    CourseManager::get_virtual_course_info($real_course_code)
    CourseManager::is_virtual_course_from_visual_code($visual_code)
    CourseManager::is_virtual_course_from_system_code($system_code)
    CourseManager::get_virtual_courses_linked_to_real_course($real_course_code)
    CourseManager::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code)
    CourseManager::has_virtual_courses_from_code($real_course_code, $user_id)
    CourseManager::get_target_of_linked_course($virtual_course_code)

    TITLE AND CODE FUNCTIONS
    CourseManager::determine_course_title_from_course_info($user_id, $course_info)
    CourseManager::create_combined_name($user_is_registered_in_real_course, $real_course_name, $virtual_course_list)
    CourseManager::create_combined_code($user_is_registered_in_real_course, $real_course_code, $virtual_course_list)

    USER FUNCTIONS
    CourseManager::get_real_course_list_of_user_as_course_admin($user_id)
    CourseManager::get_course_list_of_user_as_course_admin($user_id)

    CourseManager::is_user_subscribed_in_course($user_id, $course_code)
    CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_code)
    CourseManager::get_user_list_from_course_code($course_code)
    CourseManager::get_real_and_linked_user_list($course_code);

    GROUP FUNCTIONS
    CourseManager::get_group_list_of_course($course_code)

    CREATION FUNCTIONS
    CourseManager::attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category)
*/

/*	INIT SECTION */

/**
 * Configuration files
 */
require_once api_get_path(CONFIGURATION_PATH).'add_course.conf.php';

/**
 * Libraries (we assume main_api is also included...)
 */

require_once api_get_path(LIBRARY_PATH).'database.lib.php';
require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';

/**
 * Constants definition
 */

//LOGIC: course visibility and registration settings
/*
    COURSE VISIBILITY

    MAPPING OLD SETTINGS TO NEW SETTINGS
    -----------------------

    NOT_VISIBLE_NO_SUBSCRIPTION_ALLOWED
    --> COURSE_VISIBILITY_REGISTERED, SUBSCRIBE_NOT_ALLOWED
    NOT_VISIBLE_SUBSCRIPTION_ALLOWED
    --> COURSE_VISIBILITY_REGISTERED, SUBSCRIBE_ALLOWED
    VISIBLE_SUBSCRIPTION_ALLOWED
    --> COURSE_VISIBILITY_OPEN_PLATFORM, SUBSCRIBE_ALLOWED
    VISIBLE_NO_SUBSCRIPTION_ALLOWED
    --> COURSE_VISIBILITY_OPEN_PLATFORM, SUBSCRIBE_NOT_ALLOWED
*/
//OLD SETTINGS
define('NOT_VISIBLE_NO_SUBSCRIPTION_ALLOWED', 0);
define('NOT_VISIBLE_SUBSCRIPTION_ALLOWED', 1);
define('VISIBLE_SUBSCRIPTION_ALLOWED', 2);
define('VISIBLE_NO_SUBSCRIPTION_ALLOWED', 3);



/**
 * Variables
 */

$TABLECOURSE         = Database::get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSDOMAIN    = Database::get_main_table(TABLE_MAIN_CATEGORY);
$TABLEUSER           = Database::get_main_table(TABLE_MAIN_USER);
$TABLECOURSUSER      = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLEANNOUNCEMENTS  = 'announcement';
$coursesRepositories = $_configuration['root_sys'];

/**
 *	CourseManager Class
 *	@package chamilo.library
 */
class CourseManager {

    /**
     * Returns all the information of a given coursecode
     * @param string $course_code, the course code
     * @return an array with all the fields of the course table
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     */
    public static function get_course_information($course_code) {
        return Database::fetch_array(Database::query(
            "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
            WHERE code='".Database::escape_string($course_code)."'"),'ASSOC'
        );
    }
    
    /**
     * Returns all the information of a given coursecode
     * @param   int     the course id
     * @return an array with all the fields of the course table

     */
    public static function get_course_information_by_id($course_id) {
        return Database::select('*', Database::get_main_table(TABLE_MAIN_COURSE), array('where'=>array('id = ?' =>intval($course_id))),'first');        
    }
    

    /**
     * Returns a list of courses. Should work with quickform syntax
     * @param	integer	Offset (from the 7th = '6'). Optional.
     * @param	integer	Number of results we want. Optional.
     * @param	string	The column we want to order it by. Optional, defaults to first column.
     * @param	string	The direction of the order (ASC or DESC). Optional, defaults to ASC.
     * @param	string	The visibility of the course, or all by default.
     * @param	string	If defined, only return results for which the course *title* begins with this string
     */
    public static function get_courses_list($from = 0, $howmany = 0, $orderby = 1, $orderdirection = 'ASC', $visibility = -1, $startwith = '') {

        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." ";
        if (!empty($startwith)) {
            $sql .= "WHERE title LIKE '".Database::escape_string($startwith)."%' ";
            if ($visibility !== -1 && $visibility == strval(intval($visibility))) {
                $sql .= " AND visibility = $visibility ";
            }
        } else {
            $sql .= "WHERE 1 ";
            if ($visibility !== -1 && $visibility == strval(intval($visibility))) {
                $sql .= " AND visibility = $visibility ";
            }
        }
        if (!empty($orderby)) {
            $sql .= " ORDER BY ".Database::escape_string($orderby)." ";
        } else {
            $sql .= " ORDER BY 1 ";
        }

        if (!in_array($orderdirection, array('ASC', 'DESC'))) {
            $sql .= 'ASC';
        } else {
            $sql .= Database::escape_string($orderdirection);
        }

        if (!empty($howmany) && is_int($howmany) and $howmany > 0) {
            $sql .= ' LIMIT '.Database::escape_string($howmany);
        } else {
            $sql .= ' LIMIT 1000000'; //virtually no limit
        }
        if (!empty($from)) {
            $from = intval($from);
            $sql .= ' OFFSET '.Database::escape_string($from);
        } else {
            $sql .= ' OFFSET 0';
        }
        
        return Database::store_result(Database::query($sql));
    }

    /**
     * Returns the access settings of the course:
     * which visibility;
     * wether subscribing is allowed;
     * wether unsubscribing is allowed.
     *
     * @param string $course_code, the course code
     * @todo for more consistency: use course_info call from database API
     * @return an array with int fields "visibility", "subscribe", "unsubscribe"
     */
    public static function get_access_settings($course_code) {
        return Database::fetch_array(Database::query(
            "SELECT visibility, subscribe, unsubscribe from ".Database::get_main_table(TABLE_MAIN_COURSE)."
            WHERE code = '".Database::escape_string($course_code)."'")
        );
    }

    /**
     * Returns the status of a user in a course, which is COURSEMANAGER or STUDENT.
     * @param   int      User ID
     * @param   string   Course code
     * @return int the status of the user in that course
     */
    public static function get_user_in_course_status($user_id, $course_code) {
        $result = Database::fetch_array(Database::query(
            "SELECT status FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
            WHERE course_code = '".Database::escape_string($course_code)."' AND user_id = ".Database::escape_string($user_id))
        );
        return $result['status'];
    }

    /**
     * Unsubscribe one or more users from a course
     * 
     * @param   mixed   user_id or an array with user ids 
     * @param   int     session id
     * @param   string  course code
     * 
     */
    public static function unsubscribe_user($user_id, $course_code, $session_id = 0) {

        if (!is_array($user_id)) {
            $user_id = array($user_id);
        }
        if (count($user_id) == 0) {
            return;
        }
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
                
        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {        
        	$session_id = intval($_SESSION['id_session']);
        }

        //Cleaning the $user_id variable
        if (is_array($user_id)) {
            $new_user_id_list = array();
            foreach($user_id as $my_user_id) {
                $new_user_id_list[]= intval($my_user_id);
            }
            $new_user_id_list = array_filter($new_user_id_list);
            $user_ids = implode(',', $new_user_id_list);
        } else {
            $user_ids = intval($user_id);
        }

        $course_code = Database::escape_string($course_code);

        $course = Database::fetch_object(Database::query("SELECT db_name FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '".$course_code."'"));

        // Unsubscribe user from all groups in the course.
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_GROUP_USER, $course->db_name)."
                WHERE user_id IN (".$user_ids.")");
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_GROUP_TUTOR, $course->db_name)."
                WHERE user_id IN (".$user_ids.")");

        // Erase user student publications (works) in the course - by André Boivin
        //@todo field student_publication.author should be the user id

        $table_course_user_publication 	= Database :: get_course_table(TABLE_STUDENT_PUBLICATION, $course->db_name);
        $sqlu = "SELECT * FROM $table_user WHERE user_id IN (".$user_ids.")";
        $resu = Database::query($sqlu);
        $username = Database::fetch_array($resu,'ASSOC');
          $userfirstname = $username['firstname'];
        $userlastname = $username['lastname'];
         $publication_name = $userfirstname.' '.$userlastname ;

        $sql = "DELETE FROM $table_course_user_publication WHERE author = '".Database::escape_string($publication_name)."'";
        Database::query($sql);


        // Unsubscribe user from all blogs in the course.
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_BLOGS_REL_USER, $course->db_name)."
                WHERE user_id IN (".$user_ids.")");
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER, $course->db_name)."
                WHERE user_id IN (".$user_ids.")");

        //Deleting users in forum_notification and mailqueue course tables
        $sql_delete_forum_notification = "DELETE FROM  ".Database::get_course_table(TABLE_FORUM_NOTIFICATION, $course->db_name)." WHERE user_id IN (".$user_ids.")";
        Database::query($sql_delete_forum_notification);

        $sql_delete_mail_queue = "DELETE FROM ".Database::get_course_table(TABLE_FORUM_MAIL_QUEUE, $course->db_name)." WHERE user_id IN (".$user_ids.")";
        Database::query($sql_delete_mail_queue);


        // Unsubscribe user from the course.
        if (!empty($session_id)) {
            // Delete in table session_rel_course_rel_user
            Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE id_session ='".$session_id."' AND course_code = '".Database::escape_string($_SESSION['_course']['id'])."' AND id_user IN ($user_ids)");

            foreach ($user_id as $uid) {
                // check if a user is register in the session with other course
                $sql = "SELECT id_user FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session='$session_id' AND id_user='$uid'";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) == 0) {
                    // Delete in table session_rel_user
                    Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                                     WHERE id_session ='".$session_id."' AND id_user='$uid' AND relation_type<>".SESSION_RELATION_TYPE_RRHH."");
                }
            }

            // Update the table session
            $row = Database::fetch_array(Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                    WHERE id_session = '".$session_id."' AND relation_type<>".SESSION_RELATION_TYPE_RRHH."  "));
            $count = $row[0]; // number of users by session
            $result = Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION)." SET nbr_users = '$count'
                    WHERE id = '".$session_id."'");

            // Update the table session_rel_course
            $row = Database::fetch_array(@Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session = '$session_id' AND course_code = '$course_code' AND status<>2" ));
            $count = $row[0]; // number of users by session and course
            $result = @Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE)." SET nbr_users = '$count' WHERE id_session = '$session_id' AND course_code = '$course_code' ");

        } else {
            Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    WHERE user_id IN (".$user_ids.") AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '".$course_code."'");

            // add event to system log            
            $user_id = api_get_user_id();
            event_system(LOG_UNSUBSCRIBE_USER_FROM_COURSE, LOG_COURSE_CODE, $course_code, api_get_utc_datetime(), $user_id);
        }
    }

    /**
     * Subscribe a user to a course. No checks are performed here to see if
     * course subscription is allowed.
     * @param   int     User ID
     * @param   string  Course code
     * @param   int     Status (STUDENT, COURSEMANAGER, COURSE_ADMIN, NORMAL_COURSE_MEMBER)
     * @return  bool    True on success, false on failure
     * @see add_user_to_course
     */
    public static function subscribe_user($user_id, $course_code, $status = STUDENT, $session_id = 0) {

        if ($user_id != strval(intval($user_id))) {
            return false; //detected possible SQL injection
        }

        $course_code = Database::escape_string($course_code);
        if (empty ($user_id) || empty ($course_code)) {
            return false;
        }
        
        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {        
            $session_id = intval($_SESSION['id_session']);
        }

        $status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
        $role_id = ($status == COURSEMANAGER) ? COURSE_ADMIN : NORMAL_COURSE_MEMBER;

        // A preliminary check whether the user has bben already registered on the platform.
        if (Database::num_rows(@Database::query("SELECT status FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                WHERE user_id = '$user_id' ")) == 0) {
            return false; // The user has not been registered to the platform.
        }

        // Check whether the user has not been already subscribed to the course.
        if (empty($session_id)) {
            if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    WHERE user_id = '$user_id' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '$course_code'")) > 0) {
                return false; // The user has been already subscribed to the course.
            }
        }

        if (!empty($session_id)) {

            // Check whether the user has not already been stored in the session_rel_course_user table
            if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE course_code = '".$_SESSION['_course']['id']."'
                    AND id_session ='".$session_id."'
                    AND id_user = '".$user_id."'")) > 0) {
                return false;
            }

            // check if the user is registered in the session with other course
            $sql = "SELECT id_user FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session='".$session_id."' AND id_user='$user_id'";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) == 0) {
                // Check whether the user has not already been stored in the session_rel_user table
                if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                        WHERE id_session ='".$session_id."'
                        AND id_user = '".$user_id."' AND relation_type<>".SESSION_RELATION_TYPE_RRHH." ")) > 0) {
                    return false;
                }
            }

            // Add him/her in the table session_rel_course_rel_user
            @Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    SET id_session ='".$session_id."',
                    course_code = '".$_SESSION['_course']['id']."',
                    id_user = '".$user_id."'");

            // Add him/her in the table session_rel_user
            @Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                    SET id_session ='".$session_id."',
                    id_user = '".$user_id."'");

            // Update the table session
            $row = Database::fetch_array(@Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)." WHERE id_session = '".$session_id."' AND relation_type<>".SESSION_RELATION_TYPE_RRHH.""));
            $count = $row[0]; // number of users by session
            $result = @Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION)." SET nbr_users = '$count' WHERE id = '".$session_id."'");

            // Update the table session_rel_course
            $row = Database::fetch_array(@Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session = '".$session_id."' AND course_code = '$course_code' AND status<>2" ));
            $count = $row[0]; // number of users by session
            $result = @Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE)." SET nbr_users = '$count' WHERE id_session = '".$session_id."' AND course_code = '$course_code' ");

        } else {
            $course_sort = self::userCourseSort($user_id, $course_code);
            $result = @Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    SET course_code = '$course_code',
                        user_id     = '$user_id',
                        status      = '".$status."',
                        sort        = '". ($course_sort)."'");

            // Add event to the system log            
            $user_id = api_get_user_id();
            event_system(LOG_SUBSCRIBE_USER_TO_COURSE, LOG_COURSE_CODE, $course_code, api_get_utc_datetime(), $user_id);
        }
        return (bool)$result;
    }

    /**
     * Get the course id based on the original id and field name in the extra fields. Returns 0 if course was not found
     *
     * @param string Original course id
     * @param string Original field name
     * @return int Course id
     */
    public static function get_course_code_from_original_id($original_course_id_value, $original_course_id_name) {
        $t_cfv = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $table_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $sql_course = "SELECT course_code FROM $table_field cf INNER JOIN $t_cfv cfv ON cfv.field_id=cf.id WHERE field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
        $res = Database::query($sql_course);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->course_code;
        } else {
            return 0;
        }
    }

    /**
     * Gets the course code from the course id. Returns null if course id was not found
     *
     * @param int Course id
     * @return string Course code
     */
    public static function get_course_code_from_course_id($id) {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $id = intval($id);
        $sql = "SELECT code FROM $table WHERE id = '$id' ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->code;
        } else {
            return null;
        }
    }

    /**
     * Subscribe a user $user_id to a course $course_code.
     * @author Hugues Peeters
     * @author Roan Embrechts
     *
     * @param  int $user_id the id of the user
     * @param  string $course_code the course code
     * @param string $status (optional) The user's status in the course
     *
     * @return boolean true if subscription succeeds, boolean false otherwise.
     */
    public static function add_user_to_course($user_id, $course_code, $status = STUDENT) {
        $user_table         = Database::get_main_table(TABLE_MAIN_USER);
        $course_table       = Database::get_main_table(TABLE_MAIN_COURSE);
        $course_user_table  = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
        if (empty($user_id) || empty($course_code) || ($user_id != strval(intval($user_id)))) {
            return false;
        }
        $course_code = Database::escape_string($course_code);

        // Check in advance whether the user has already been registered on the platform.
        if (Database::num_rows(Database::query("SELECT status FROM ".$user_table." WHERE user_id = '$user_id' ")) == 0) {
            return false; // Thehe user has not been registered to the platform.
        }

        // Check whether the user has already been subscribed to this course.
        if (Database::num_rows(Database::query("SELECT * FROM ".$course_user_table." WHERE user_id = '$user_id' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '$course_code'")) > 0) {
            return false; // The user has been subscribed to the course.
        }

        // Check in advance whether subscription is allowed or not for this course.
        if (Database::num_rows(Database::query("SELECT code, visibility FROM ".$course_table." WHERE code = '$course_code' AND subscribe = '".SUBSCRIBE_NOT_ALLOWED."'")) > 0) {
            return false; // Subscription is not allowed for this course.
        }

        // Ok, subscribe the user.
        $max_sort = api_max_sort_value('0', $user_id);
        return (bool)Database::query("INSERT INTO ".$course_user_table."
                SET course_code = '$course_code',
                user_id = '$user_id',
                status = '".$status."',
                sort = '". ($max_sort + 1)."'");
    }

    /**
     *	Checks wether a parameter exists.
     *	If it doesn't, the function displays an error message.
     *
     *	@return true if parameter is set and not empty, false otherwise
     *	@todo move function to better place, main_api ?
     */
    public static function check_parameter($parameter, $error_message) {
        if (empty($parameter)) {
            Display::display_normal_message($error_message);
            return false;
        }
        return true;
    }

    /**
     *	Lets the script die when a parameter check fails.
     *	@todo move function to better place, main_api ?
     */
    public static function check_parameter_or_fail($parameter, $error_message) {
        if (!self::check_parameter($parameter, $error_message)) {
            die();
        }
    }

    /**
     *	@return true if there already are one or more courses
     *	with the same code OR visual_code (visualcode), false otherwise
     */
    public static function course_code_exists($wanted_course_code) {
        $wanted_course_code = Database::escape_string($wanted_course_code);
        $result = Database::fetch_array(Database::query("SELECT COUNT(*) as number FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."WHERE code = '$wanted_course_code' OR visual_code = '$wanted_course_code'"));
        return $result['number'] > 0;
    }

    /**
     *	@return an array with the course info of all real courses on the platform
     */
    public static function get_real_course_list() {
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE target_course_code IS NULL");
        $real_course_list = array();
        while ($result = Database::fetch_array($sql_result)) {
            $real_course_list[$result['code']] = $result;
        }
        return $real_course_list;
    }

    /**
     * Lists all virtual courses
     * @return array   Course info (course code => details) of all virtual courses on the platform
     */
    public static function get_virtual_course_list() {
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE target_course_code IS NOT NULL");
        $virtual_course_list = array();
        while ($result = Database::fetch_array($sql_result)) {
            $virtual_course_list[$result['code']] = $result;
        }
        return $virtual_course_list;
    }

    /**
     * Returns an array with the course info of the real courses of which
     * the current user is course admin
     * @return array   A list of courses details for courses to which the user is subscribed as course admin (status = 1)
     */
    public static function get_real_course_list_of_user_as_course_admin($user_id) {
        $result_array = array();
        if ($user_id != strval(intval($user_id))) {
            return $result_array;
        }
        $sql_result = Database::query("SELECT *
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
                LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
                ON course.code = course_user.course_code
                WHERE course.target_course_code IS NULL
                    AND course_user.user_id = '$user_id'
                    AND course_user.status = '1'");
        if ($sql_result === false) { return $result_array; }
        while ($result = Database::fetch_array($sql_result)) {
            $result_array[] = $result;
        }
        return $result_array;
    }

    /**
     *	@return an array with the course info of all the courses (real and virtual) of which
     *	the current user is course admin
     */
    public static function get_course_list_of_user_as_course_admin($user_id) {        
        if ($user_id != strval(intval($user_id))) {
            return array();
        }

        // Definitions database tables and variables
        $tbl_course      = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $user_id = intval($user_id);
        $data = array();

        $sql_nb_cours = "SELECT course_rel_user.course_code, course.title, course.id, course.db_name
            FROM $tbl_course_user as course_rel_user
            INNER JOIN $tbl_course as course
                ON course.code = course_rel_user.course_code
            WHERE course_rel_user.user_id='$user_id' AND course_rel_user.status='1'
            ORDER BY course.title";

        if (api_get_multiple_access_url()) {
            $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql_nb_cours = "	SELECT course_rel_user.course_code, course.title, course.id, course.db_name
                    FROM $tbl_course_user as course_rel_user
                    INNER JOIN $tbl_course as course
                        ON course.code = course_rel_user.course_code
                      INNER JOIN $tbl_course_rel_access_url course_rel_url
                        ON (course_rel_url.course_code= course.code)
                      WHERE access_url_id =  $access_url_id  AND course_rel_user.user_id='$user_id' AND course_rel_user.status='1'
                      ORDER BY course.title";
            }
        }

        $result_nb_cours = Database::query($sql_nb_cours);
        if (Database::num_rows($result_nb_cours) > 0) {
            while ($row = Database::fetch_array($result_nb_cours,'ASSOC')) {
                $data[$row['course_code']] = $row;
            }
        }

        return $data;
    }

    /**
     * Find out for which courses the user is registered and determine a visual course code and course title from that.
     * Takes virtual courses into account
     *
     * Default case: the name and code stay what they are.
     *
     * Scenarios:
     * - User is registered in real course and virtual courses; name / code become a mix of all
     * - User is registered in real course only: name stays that of real course
     * - User is registered in virtual course only: name becomes that of virtual course
     * - user is not registered to any of the real/virtual courses: name stays that of real course
     * (I'm not sure about the last case, but this seems not too bad)
     *
     * @author Roan Embrechts
     * @param $user_id, the id of the user
     * @param $course_info, an array with course info that you get using Database::get_course_info($course_system_code);
     * @return an array with indices
     *    $return_result['title'] - the course title of the combined courses
     *    $return_result['code']  - the course code of the combined courses
     */
    public static function determine_course_title_from_course_info($user_id, $course_info) {

        if ($user_id != strval(intval($user_id))) {
            return array();
        }

        $real_course_id = $course_info['system_code'];
        $real_course_info = Database::get_course_info($real_course_id);
        $real_course_name = $real_course_info['title'];
        $real_course_visual_code = $real_course_info['visual_code'];
        $real_course_real_code = Database::escape_string($course_info['system_code']);

        //is the user registered in the real course?
        $result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE user_id = '$user_id' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '$real_course_real_code'"));
        $user_is_registered_in_real_course = !empty($result);

        //get a list of virtual courses linked to the current real course and to which the current user is subscribed
        $user_subscribed_virtual_course_list = self::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_id);
        $virtual_courses_exist = count($user_subscribed_virtual_course_list) > 0;

        //now determine course code and name
        if ($user_is_registered_in_real_course && $virtual_courses_exist) {
            $course_info['name'] = self::create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
            $course_info['official_code'] = self::create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
        }
        elseif ($user_is_registered_in_real_course) {
            //course name remains real course name
            $course_info['name'] = $real_course_name;
            $course_info['official_code'] = $real_course_visual_code;
        }
        elseif ($virtual_courses_exist) {
            $course_info['name'] = self::create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
            $course_info['official_code'] = self::create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
        } else {
            //course name remains real course name
            $course_info['name'] = $real_course_name;
            $course_info['official_code'] = $real_course_visual_code;
        }

        $return_result['title'] = $course_info['name'];
        $return_result['code'] = $course_info['official_code'];
        return $return_result;
    }

    /**
     * Create a course title based on all real and virtual courses the user is registered in.
     * @param boolean $user_is_registered_in_real_course
     * @param string $real_course_name, the title of the real course
     * @param array $virtual_course_list, the list of virtual courses
     */
    public static function create_combined_name($user_is_registered_in_real_course, $real_course_name, $virtual_course_list) {

        $complete_course_name = array();

        if ($user_is_registered_in_real_course) {
            // Add the real name to the result.
            $complete_course_name[] = $real_course_name;
        }

        // Add course titles of all virtual courses.
        foreach ($virtual_course_list as $current_course) {
            $complete_course_name[] = $current_course['title'];
        }

        // 'CombinedCourse' is from course_home language file.
        return (($user_is_registered_in_real_course || count($virtual_course_list) > 1) ? get_lang('CombinedCourse').' ' : '').implode(' &amp; ', $complete_course_name);
    }

    /**
     *	Create a course code based on all real and virtual courses the user is registered in.
     */
    public static function create_combined_code($user_is_registered_in_real_course, $real_course_code, $virtual_course_list) {

        $complete_course_code = array();

        if ($user_is_registered_in_real_course) {
            // Add the real code to the result
            $complete_course_code[] = $real_course_code;
        }

        // Add codes of all virtual courses.
        foreach ($virtual_course_list as $current_course) {
            $complete_course_code[] = $current_course['visual_code'];
        }

        return implode(' &amp; ', $complete_course_code);
    }

    /**
     *	Return course info array of virtual course
     *
     *	Note this is different from getting information about a real course!
     *
     *	@param $real_course_code, the id of the real course which the virtual course is linked to
     */
    public static function get_virtual_course_info($real_course_code) {
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE target_course_code = '".Database::escape_string($real_course_code)."'");
        $result = array();
        while ($virtual_course = Database::fetch_array($sql_result)) {
            $result[] = $virtual_course;
        }
        return $result;
    }

    /**
     *	@param string $system_code, the system code of the course
     *	@return true if the course is a virtual course, false otherwise
     */
    public static function is_virtual_course_from_system_code($system_code) {
        $result = Database::fetch_array(Database::query("SELECT target_course_code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '".Database::escape_string($system_code)."'"));
        return !empty($result['target_course_code']);
    }

    /**
     *	Returns whether the course code given is a visual code
     *  @param  string  Visual course code
     *	@return true if the course is a virtual course, false otherwise
     */
    public static function is_virtual_course_from_visual_code($visual_code) {
        $result = Database::fetch_array(Database::query("SELECT target_course_code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE visual_code = '".Database::escape_string($visual_code)."'"));
        return !empty($result['target_course_code']);
    }

    /**
     * @return true if the real course has virtual courses that the user is subscribed to, false otherwise
     */
    public static function has_virtual_courses_from_code($real_course_code, $user_id) {
        return count(self::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code)) > 0;
    }

    /**
     *	Return an array of arrays, listing course info of all virtual course
     *	linked to the real course ID $real_course_code
     *
     *	@param string The id of the real course which the virtual courses are linked to
     *  @return array List of courses details
     */
    public static function get_virtual_courses_linked_to_real_course($real_course_code) {
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE target_course_code = '".Database::get_main_table(TABLE_MAIN_COURSE)."'");
        $result_array = array();
        while ($result = Database::fetch_array($sql_result)) {
            $result_array[] = $result;
        }
        return $result_array;
    }

    /**
     * This function returns the course code of the real course
     * to which a virtual course is linked.
     *
     * @param the course code of the virtual course
     * @return the course code of the real course
     */
    public static function get_target_of_linked_course($virtual_course_code) {
        //get info about the virtual course
        $result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '".Database::escape_string($virtual_course_code)."'"));
        return $result['target_course_code'];
    }

    /*    
        USER FUNCTIONS
    */

    /**
     * Check if user is subscribed inside a course
     * @param 	int		User id
     * @param	string	Course code, if this parameter is null, it'll check for all courses
     * @param	bool	True for checking inside sessions too, by default is not checked
     * @return 	bool 	true if the user is registered in the course, false otherwise
     */
    public static function is_user_subscribed_in_course($user_id, $course_code = null, $in_a_session = false) {

        $user_id = intval($user_id);

        $condition_course = '';
        if (isset($course_code)) {
            $course_code = Database::escape_string($course_code);
            $condition_course = ' AND course_code = "'.$course_code.'" ';
        }

        $result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE user_id = $user_id AND relation_type<>".COURSE_RELATION_TYPE_RRHH." $condition_course "));
        if (!empty($result)) {
            return true; // The user has been registered in this course.
        }

        if (!$in_a_session) {
            return false; // The user has not been registered in this course.
        }

        if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
                ' WHERE id_user = '.$user_id.' '.$condition_course.' ')) > 0) {
            return true;
        }

        if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
                ' WHERE id_user = '.$user_id.' AND status=2 '.$condition_course.' ')) > 0) {
            return true;
        }

        if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION).
                ' WHERE id='.intval($_SESSION['id_session']).' AND id_coach='.$user_id)) > 0) {
            return true;
        }

        return false;
    }

    /**
     *	Is the user a teacher in the given course?
     *
     *	@param $user_id, the id (int) of the user
     *	@param $course_code, the course code
     *
     *	@return true if the user is a teacher in the course, false otherwise
     */
    public static function is_course_teacher($user_id, $course_code) {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        $sql_result = Database::query('SELECT status FROM '.Database::get_main_table(TABLE_MAIN_COURSE_USER).
                ' WHERE course_code="'.Database::escape_string($course_code).'" and user_id="'.$user_id.'"');
        if (Database::num_rows($sql_result) > 0) {
            return Database::result($sql_result, 0, 'status') == 1;
        }
        return false;
    }

    /**
     *	Is the user subscribed in the real course or linked courses?
     *
     *	@param int the id of the user
     *	@param array info about the course (comes from course table, see database lib)
     *
     *	@return true if the user is registered in the real course or linked courses, false otherwise
     */
    public static function is_user_subscribed_in_real_or_linked_course ($user_id, $course_code, $session_id = '') {

        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        $course_code = Database::escape_string($course_code);

        if ($session_id == '') {
            $result = Database::fetch_array(Database::query("SELECT *
                    FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
                    LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
                    ON course.code = course_user.course_code
                    WHERE course_user.user_id = '$user_id' AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH." AND ( course.code = '$course_code' OR target_course_code = '$course_code')"));
            return !empty($result);
        }

        // From here we trust session id.

        // Is he/she subscribed to the session's course?

        // A user?
        if (Database::num_rows(Database::query("SELECT id_user
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE id_session='".$_SESSION['id_session']."'
                    AND id_user='$user_id'"))) {
            return true;
        }

        // A course coach?
        if (Database::num_rows(Database::query("SELECT id_user
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE id_session='".$_SESSION['id_session']."'
                    AND id_user = '$user_id' AND status = 2
                    AND course_code='$course_code'"))) {
            return true;
        }

        // A session coach?
        if (Database::num_rows(Database::query("SELECT id_coach
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION)." AS session
                    WHERE session.id='".$_SESSION['id_session']."'
                    AND id_coach='$user_id'"))) {
            return true;
        }

        return false;
    }

    /**
     *	Return user info array of all users registered in the specified real or virtual course
     *	This only returns the users that are registered in this actual course, not linked courses.
     *
     * @param string $course_code the code of the course
     * @param boolean $with_session determines if the course is used in a session or not
     * @param integer $session_id the id of the session
     * @param string $limit the LIMIT statement of the sql statement
     * @param string $order_by the field to order the users by. Valid values are 'lastname', 'firstname', 'username', 'email', 'official_code' OR a part of a SQL statement that starts with ORDER BY ...
     *  @return array
     */
    public static function get_user_list_from_course_code($course_code, $with_session = true, $session_id = 0, $limit = '', $order_by = '') {        
        // variable initialisation
        $session_id 	= intval($session_id);        
        $course_code 	= Database::escape_string($course_code);
        $where 			= array();

        // if the $order_by does not contain 'ORDER BY' we have to check if it is a valid field that can be sorted on
        if (!strstr($order_by,'ORDER BY')) {
            if (!empty($order_by) AND in_array($order_by, array('lastname', 'firstname', 'username', 'email', 'official_code'))){
                    $order_by = 'ORDER BY user.'.$order_by;
                } else {
                    $order_by = '';
                }
        }

        $sql = $session_id == 0
            ? 'SELECT DISTINCT course_rel_user.status as status_rel, user.user_id, course_rel_user.role, course_rel_user.tutor_id, user.*  '
            : 'SELECT DISTINCT user.user_id, session_course_user.status as status_session, user.*  ';
        $sql .= ' FROM '.Database::get_main_table(TABLE_MAIN_USER).' as user ';

        if (!empty($session_id)) {
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' as session_course_user
                      ON user.user_id = session_course_user.id_user
                      AND session_course_user.course_code="'.$course_code.'"            
                      AND session_course_user.id_session = '.$session_id;            
            $where[] = ' session_course_user.course_code IS NOT NULL ';
        } else {        
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_COURSE_USER).' as course_rel_user
                        ON user.user_id = course_rel_user.user_id AND course_rel_user.relation_type<>'.COURSE_RELATION_TYPE_RRHH.'
                        AND course_rel_user.course_code="'.$course_code.'"';
            $where[] = ' course_rel_user.course_code IS NOT NULL ';
        }
        $multiple_access_url = api_get_multiple_access_url();
        if ($multiple_access_url) {
            $sql  .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).'  au ON (au.user_id = user.user_id) ';
        }

        $sql .= ' WHERE '.implode(' OR ', $where);

        if ($multiple_access_url) {
            $current_access_url_id = api_get_current_access_url_id();
            $sql .= " AND (access_url_id =  $current_access_url_id ) ";
        }        
        $sql .= ' '.$order_by.' '.$limit;
        
        $rs = Database::query($sql);
        $users = array();
        
        if (Database::num_rows($rs)) {
            while ($user = Database::fetch_array($rs)) {
                //$user_info = Database::get_user_info_from_id($user['user_id']);
                $user_info = $user;
                $user_info['status'] = $user['status'];
    
                if (isset($user['role'])) {
                    $user_info['role'] = $user['role'];
                }
                if (isset($user['tutor_id'])) {
                    $user_info['tutor_id'] = $user['tutor_id'];
                }
    
                if (!empty($session_id)) {
                    $user_info['status_session'] = $user['status_session'];
                }
    
                $users[$user['user_id']] = $user_info;
            }
        }        
        return $users;
    }
    
    /**
     * Gets subscribed users in a course or in a course/session 
     * 
     * @param   string    $course_code
     * @param   int       $session_id
     * @return  int
     */
    public static function get_users_count_in_course($course_code, $session_id = 0) {        
        // variable initialisation
        $session_id     = intval($session_id);        
        $course_code    = Database::escape_string($course_code);

        $sql .= 'SELECT DISTINCT count(*) as count  FROM '.Database::get_main_table(TABLE_MAIN_USER).' as user ';
        $where = array();
        if (!empty($session_id)) {
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' as session_course_user
                      ON user.user_id = session_course_user.id_user
                      AND session_course_user.course_code = "'.$course_code.'"
                      AND session_course_user.id_session  = '.$session_id;
            
            $where[] = ' session_course_user.course_code IS NOT NULL ';
        } else {        
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_COURSE_USER).' as course_rel_user
                        ON user.user_id = course_rel_user.user_id AND course_rel_user.relation_type<>'.COURSE_RELATION_TYPE_RRHH.'
                        AND course_rel_user.course_code="'.$course_code.'"';
            $where[] = ' course_rel_user.course_code IS NOT NULL ';
        }
        
        $multiple_access_url = api_get_multiple_access_url();
        if ($multiple_access_url) {
            $sql  .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).'  au ON (au.user_id = user.user_id) ';
        }

        $sql .= ' WHERE '.implode(' OR ', $where);

        if ($multiple_access_url) {
            $current_access_url_id = api_get_current_access_url_id();
            $sql .= " AND (access_url_id =  $current_access_url_id ) ";
        }
        $rs = Database::query($sql);
        $users = array();        
        $count = 0;
        if (Database::num_rows($rs)) {
            $user = Database::fetch_array($rs);
            $count = $user['count'];            
        }        
        return $count;
    }

    /**
     * Get a list of coaches of a course and a session
     * @param   string  Course code
     * @param   int     Session ID
     * @return  array   List of users
     */
    public static function get_coach_list_from_course_code($course_code, $session_id) {

        if ($session_id != strval(intval($session_id))) {
            return array();
        }

        $course_code = Database::escape_string($course_code);

        $users = array();

        // We get the coach for the given course in a given session.
        $rs = Database::query('SELECT id_user FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
                ' WHERE id_session="'.$session_id.'" AND course_code="'.$course_code.'" AND status = 2');
        while ($user = Database::fetch_array($rs)) {
            $user_info = Database::get_user_info_from_id($user['id_user']);
            $user_info['status'] = $user['status'];
            $user_info['role'] = $user['role'];
            $user_info['tutor_id'] = $user['tutor_id'];
            $user_info['email'] = $user['email'];
            $users[$user['id_user']] = $user_info;
        }

        // We get the session coach.
        $rs = Database::query('SELECT id_coach FROM '.Database::get_main_table(TABLE_MAIN_SESSION).
                ' WHERE id="'.$session_id.'"');
        $user_info = array();
        $session_id_coach = Database::result($rs, 0, 'id_coach');
        $user_info = Database::get_user_info_from_id($session_id_coach);
        $user_info['status'] = $user['status'];
        $user_info['role'] = $user['role'];
        $user_info['tutor_id'] = $user['tutor_id'];
        $user_info['email'] = $user['email'];
        $users[$session_id_coach] = $user_info;

        return $users;
    }


    /**
     *	Return user info array of all users registered in the specified real or virtual course
     *	This only returns the users that are registered in this actual course, not linked courses.
     *
     *	@param string $course_code
     *	@param boolean $full list to true if we want sessions students too
     *	@return array with user id
     */
    public static function get_student_list_from_course_code($course_code, $with_session = false, $session_id = 0) {
        $session_id = intval($session_id);
        $course_code = Database::escape_string($course_code);

        $students = array();

        if ($session_id == 0) {
            // students directly subscribed to the course
            $rs = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    WHERE course_code = '$course_code' AND status = 5");
            while ($student = Database::fetch_array($rs)) {
                $students[$student['user_id']] = $student;
            }
        }

        // students subscribed to the course through a session

        if (api_get_setting('use_session_mode') == 'true' && $with_session) {
            $sql_query = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE course_code = '$course_code' AND status<>2";
            if ($session_id != 0) {
                $sql_query .= ' AND id_session = '.$session_id;
            }
            $rs = Database::query($sql_query);
            while($student = Database::fetch_array($rs)) {
                $students[$student['id_user']] = $student;
            }
        }

        return $students;
    }

    /**
     *	Return user info array of all teacher-users registered in the specified real or virtual course
     *	This only returns the users that are registered in this actual course, not linked courses.
     *
     *	@param string $course_code
     *	@return array with user id
     */
    public static function get_teacher_list_from_course_code($course_code) {

        $course_code = Database::escape_string($course_code);

        // teachers directly subscribed to the course
        $teachers = array();
        // TODO: This query is not optimal.
        $rs = Database::query("SELECT u.user_id, u.lastname, u.firstname, u.email, u.username, u.status " .
                "FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." cu, ".Database::get_main_table(TABLE_MAIN_USER)." u ".
                "WHERE cu.course_code = '$course_code' " .
                "AND cu.status = 1 " .
                "AND cu.user_id = u.user_id");
        while ($teacher = Database::fetch_array($rs)) {
            $teachers[$teacher['user_id']] = $teacher;
        }
        return $teachers;
    }

    /**
     *	Return user info array of all users registered in the specified course
     *	this includes the users of the course itsel and the users of all linked courses.
     *
     *	@param array $course_info
     *	@return array with user info
     */
    public static function get_real_and_linked_user_list($course_code, $with_sessions = true, $session_id = 0) {
        //get list of virtual courses
        $virtual_course_list = self::get_virtual_courses_linked_to_real_course($course_code);

        //get users from real course
        $user_list = self::get_user_list_from_course_code($course_code, $with_sessions, $session_id);
        foreach ($user_list as $this_user) {
            $complete_user_list[] = $this_user;
        }

        //get users from linked courses
        foreach ($virtual_course_list as $this_course) {
            $course_code = $this_course['code'];
            $user_list = self::get_user_list_from_course_code($course_code, $with_sessions, $session_id);
            foreach ($user_list as $this_user) {
                $complete_user_list[] = $this_user;
            }
        }

        return $complete_user_list;
    }

    /**
     *	Return an array of arrays, listing course info of all courses in the list
     *	linked to the real course $real_course_code, to which the user $user_id is subscribed.
     *
     *	@param $user_id, the id (int) of the user
     *	@param $real_course_code, the id (char) of the real course
     *
     *	@return array of course info arrays
     */
    public static function get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $course_code) {
        $result_array = array();

        if ($user_id != strval(intval($user_id))) {
            return $result_array;
        }

        $course_code = Database::escape_string($course_code);
		$sql = "SELECT *
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
                LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
                ON course.code = course_user.course_code
                WHERE course.target_course_code = '$course_code' AND course_user.user_id = '$user_id' AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
        $sql_result = Database::query($sql);

        while ($result = Database::fetch_array($sql_result)) {
            $result_array[] = $result;
        }

        return $result_array;
    }

    /*
        GROUP FUNCTIONS
    */

    /**
     * Get the list of groups from the course
     * @param   string  Course code
     * @param   int     Session ID (optional)
     * @return  array   List of groups info
     */
    public static function get_group_list_of_course($course_code, $session_id = 0) {
        $course_info = Database::get_course_info($course_code);
        $database_name = $course_info['db_name'];

        $group_list = array();
        $session_id != 0 ? $session_condition = ' WHERE g.session_id IN(1,'.intval($session_id).')' : $session_condition = ' WHERE g.session_id = 0';
        $sql="SELECT g.id, g.name, COUNT(gu.id) userNb
                FROM ".Database::get_course_table(TABLE_GROUP, $database_name)." AS g
                LEFT JOIN ".Database::get_course_table(TABLE_GROUP_USER, $database_name)." gu
                ON g.id = gu.group_id
                $session_condition
                GROUP BY g.id
                ORDER BY g.name";
        $result = Database::query($sql);

        while ($group_data = Database::fetch_array($result)) {
            $group_list[$group_data['id']] = $group_data;
        }
        return $group_list;
    }

    /**
     * Checks all parameters needed to create a virtual course.
     * If they are all set, the virtual course creation procedure is called.
     *
     * Call this function instead of create_virtual_course
     * @param  string  Course code
     * @param  string  Course title
     * @param  string  Wanted course code
     * @param  string  Course language
     * @param  string  Course category
     * @return bool    True on success, false on error
     */
    public static function attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category) {
        //better: create parameter list, check the entire list, when false display errormessage
        self::check_parameter_or_fail($real_course_code, 'Unspecified parameter: real course id.');
        self::check_parameter_or_fail($course_title, 'Unspecified parameter: course title.');
        self::check_parameter_or_fail($wanted_course_code, 'Unspecified parameter: wanted course code.');
        self::check_parameter_or_fail($course_language, 'Unspecified parameter: course language.');
        self::check_parameter_or_fail($course_category, 'Unspecified parameter: course category.');

        return self::create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);
    }

    /**
     * This function creates a virtual course.
     * It assumes all parameters have been checked and are not empty.
     * It checks wether a course with the $wanted_course_code already exists.
     *
     * Users of this library should consider this function private,
     * please call attempt_create_virtual_course instead of this one.
     *
     * note: The virtual course 'owner' id (the first course admin) is set to the CURRENT user id.
     * @param  string  Course code
     * @param  string  Course title
     * @param  string  Wanted course code
     * @param  string  Course language
     * @param  string  Course category
     * @return true if the course creation succeeded, false otherwise
     * @todo research: expiration date of a course
     */
    public static function create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category) {
        global $firstExpirationDelay;

        $user_id = api_get_user_id();
        $real_course_info = Database::get_course_info($real_course_code);
        $real_course_code = $real_course_info['system_code'];

        //check: virtual course creation fails if another course has the same
        //code, real or fake.
        if (self::course_code_exists($wanted_course_code)) {
            Display::display_error_message($wanted_course_code.' - '.get_lang('CourseCodeAlreadyExists'));
            return false;
        }

        //add data to course table, course_rel_user
        $course_sys_code = $wanted_course_code;
        $course_screen_code = $wanted_course_code;
        $course_repository = $real_course_info['directory'];
        $course_db_name = $real_course_info['db_name'];
        $responsible_teacher = $real_course_info['tutor_name'];
        $faculty_shortname = $course_category;
        // $course_title = $course_title;
        // $course_language = $course_language;
        $teacher_id = $user_id;

        //HACK ----------------------------------------------------------------
        $expiration_date = time() + $firstExpirationDelay;
        //END HACK ------------------------------------------------------------

        register_course($course_sys_code, $course_screen_code, $course_repository, $course_db_name, $responsible_teacher, $faculty_shortname, $course_title, $course_language, $teacher_id, $expiration_date);

        //above was the normal course creation table update call,
        //now one more thing: fill in the target_course_code field
        Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_COURSE)." SET target_course_code = '$real_course_code'
                WHERE code = '".Database::escape_string($course_sys_code)."' LIMIT 1 ");

        return true;
    }

    /**
     * Delete a course
     * This function deletes a whole course-area from the platform. When the
     * given course is a virtual course, the database and directory will not be
     * deleted.
     * When the given course is a real course, also all virtual courses refering
     * to the given course will be deleted.
     * Considering the fact that we remove all traces of the course in the main
     * database, it makes sense to remove all tracking as well (if stats databases exist)
     * so that a new course created with this code would not use the remains of an older
     * course.
     *
     * @param string The code of the course to delete
     * @todo When deleting a virtual course: unsubscribe users from that virtual
     * course from the groups in the real course if they are not subscribed in
     * that real course.
     * @todo Remove globals
     */
    public static function delete_course($code) {
        global $_configuration;

        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_course_class = Database::get_main_table(TABLE_MAIN_COURSE_CLASS);
        $user_role_table = Database::get_main_table(MAIN_USER_ROLE_TABLE);
        $location_table = Database::get_main_table(MAIN_LOCATION_TABLE);
        $role_right_location_table = Database::get_main_table(MAIN_ROLE_RIGHT_LOCATION_TABLE);
        $table_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $table_course_survey = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY);
        $table_course_survey_question = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
        $table_course_survey_question_option = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
        $stats = false;
        if (Database::get_statistic_database() != ''){
            $stats = true;
            $table_stats_hotpots = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
            $table_stats_attempt = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
            $table_stats_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
            $table_stats_access = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
            $table_stats_lastaccess = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
            $table_stats_course_access = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
            $table_stats_online = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
            $table_stats_default = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
            $table_stats_downloads = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
            $table_stats_links = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
            $table_stats_uploads = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
        }
        $code = Database::escape_string($code);
        $sql = "SELECT * FROM $table_course WHERE code='".$code."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) == 0) {
            return;
        }
        $this_course = Database::fetch_array($res);
        $db_name = $this_course['db_name'];
        self::create_database_dump($code);
        if (!self::is_virtual_course_from_system_code($code)) {
            // If this is not a virtual course, look for virtual courses that depend on this one, if any
            $virtual_courses = self::get_virtual_courses_linked_to_real_course($code);
            foreach ($virtual_courses as $index => $virtual_course) {
                // Unsubscribe all classes from the virtual course
                $sql = "DELETE FROM $table_course_class WHERE course_code='".$virtual_course['code']."'";
                Database::query($sql);
                // Unsubscribe all users from the virtual course
                $sql = "DELETE FROM $table_course_user WHERE course_code='".$virtual_course['code']."'";
                Database::query($sql);
                // Delete the course from the sessions tables
                $sql = "DELETE FROM $table_session_course WHERE course_code='".$virtual_course['code']."'";
                Database::query($sql);
                $sql = "DELETE FROM $table_session_course_user WHERE course_code='".$virtual_course['code']."'";
                Database::query($sql);
                // Delete the course from the survey tables
                $sql = "DELETE FROM $table_course_survey WHERE course_code='".$virtual_course['code']."'";
                Database::query($sql);
                $sql = "DELETE FROM $table_course_survey_user WHERE db_name='".$virtual_course['db_name']."'";
                Database::query($sql);
                $sql = "DELETE FROM $table_course_survey_reminder WHERE db_name='".$virtual_course['db_name']."'";
                Database::query($sql);

                // Delete the course from the stats tables
                if ($stats) {
                    $sql = "DELETE FROM $table_stats_hotpots WHERE exe_cours_id = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_attempt WHERE course_code = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_exercises WHERE exe_cours_id = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_access WHERE access_cours_code = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_lastaccess WHERE access_cours_code = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_course_access WHERE course_code = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_online WHERE course = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_default WHERE default_cours_code = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_downloads WHERE down_cours_id = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_links WHERE links_cours_id = '".$virtual_course['code']."'";
                    Database::query($sql);
                    $sql = "DELETE FROM $table_stats_uploads WHERE upload_cours_id = '".$virtual_course['code']."'";
                    Database::query($sql);
                }

                // Delete the course from the course table
                $sql = "DELETE FROM $table_course WHERE code='".$virtual_course['code']."'";
                Database::query($sql);
            }
            $sql = "SELECT * FROM $table_course WHERE code='".$code."'";
            $res = Database::query($sql);
            $course = Database::fetch_array($res);
            if (!$_configuration['single_database']) {
                $sql = "DROP DATABASE IF EXISTS ".$course['db_name'];
                Database::query($sql);
            } else {
                //TODO Clean the following code as currently it would probably delete another course
                //similarly named, by mistake...
                $db_pattern = $_configuration['table_prefix'].$course['db_name'].$_configuration['db_glue'];
                $sql = "SHOW TABLES LIKE '$db_pattern%'";
                $result = Database::query($sql);
                while (list ($courseTable) = Database::fetch_array($result)) {
                    Database::query("DROP TABLE $courseTable");
                }
            }
            $course_dir = api_get_path(SYS_COURSE_PATH).$course['directory'];
            $archive_dir = api_get_path(SYS_ARCHIVE_PATH).$course['directory'].'_'.time();
            if (is_dir($course_dir)) {
                rename($course_dir, $archive_dir);
            }
        }

        // Unsubscribe all classes from the course
        $sql = "DELETE FROM $table_course_class WHERE course_code='".$code."'";
        Database::query($sql);
        // Unsubscribe all users from the course
        $sql = "DELETE FROM $table_course_user WHERE course_code='".$code."'";
        Database::query($sql);
        // Delete the course from the sessions tables
        $sql = "DELETE FROM $table_session_course WHERE course_code='".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_session_course_user WHERE course_code='".$code."'";
        Database::query($sql);

        $sql = 'SELECT survey_id FROM '.$table_course_survey.' WHERE course_code="'.$code.'"';
        $result_surveys = Database::query($sql);
        while($surveys = Database::fetch_array($result_surveys)) {
            $survey_id = $surveys[0];
            $sql = 'DELETE FROM '.$table_course_survey_question.' WHERE survey_id="'.$survey_id.'"';
            Database::query($sql);
            $sql = 'DELETE FROM '.$table_course_survey_question_option.' WHERE survey_id="'.$survey_id.'"';
            Database::query($sql);
            $sql = 'DELETE FROM '.$table_course_survey.' WHERE survey_id="'.$survey_id.'"';
            Database::query($sql);
        }

        // Delete the course from the stats tables
        if ($stats) {
            $sql = "DELETE FROM $table_stats_hotpots WHERE exe_cours_id = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_attempt WHERE course_code = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_exercises WHERE exe_cours_id = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_access WHERE access_cours_code = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_lastaccess WHERE access_cours_code = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_course_access WHERE course_code = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_online WHERE course = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_default WHERE default_cours_code = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_downloads WHERE down_cours_id = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_links WHERE links_cours_id = '".$code."'";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_uploads WHERE upload_cours_id = '".$code."'";
            Database::query($sql);
        }

        global $_configuration;
        if ($_configuration['multiple_access_urls']) {
            require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
            $url_id = 1;
            if (api_get_current_access_url_id() != -1) {
                $url_id = api_get_current_access_url_id();
            }
            UrlManager::delete_url_rel_course($code, $url_id);
        }

        // Delete the course from the database
        $sql = "DELETE FROM $table_course WHERE code='".$code."'";
        Database::query($sql);

        // delete extra course fields
        $t_cf 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $t_cfv 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

        $sql = "SELECT distinct field_id FROM $t_cfv WHERE course_code = '$code'";
        $res_field_ids = @Database::query($sql);

        while($row_field_id = Database::fetch_row($res_field_ids)){
            $field_ids[] = $row_field_id[0];
        }

        //delete from table_course_field_value from a given course_code

        $sql_course_field_value = "DELETE FROM $t_cfv WHERE course_code = '$code'";
        @Database::query($sql_course_field_value);

        $sql = "SELECT distinct field_id FROM $t_cfv";
        $res_field_all_ids = @Database::query($sql);

        while($row_field_all_id = Database::fetch_row($res_field_all_ids)){
            $field_all_ids[] = $row_field_all_id[0];
        }

        if (is_array($field_ids) && count($field_ids) > 0) {
            foreach ($field_ids as $field_id) {
                // check if field id is used into table field value
                if (is_array($field_all_ids)) {
                    if (in_array($field_id, $field_all_ids)) {
                        continue;
                    } else {
                        $sql_course_field = "DELETE FROM $t_cf WHERE id = '$field_id'";
                        Database::query($sql_course_field);
                    }
                }
            }
        }

        // Add event to system log        
        $user_id = api_get_user_id();
        event_system(LOG_COURSE_DELETE, LOG_COURSE_CODE, $code, api_get_utc_datetime(), $user_id, $code);

    }

    /**
     * Creates a file called mysql_dump.sql in the course folder
     * @param $course_code The code of the course
     * @todo Implementation for single database
     */
    public static function create_database_dump($course_code) {
        global $_configuration;

        if ($_configuration['single_database']) {
            return;
        }
        $sql_dump = '';
        $course_code = Database::escape_string($course_code);
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT * FROM $table_course WHERE code = '$course_code'";
        $res = Database::query($sql);
        $course = Database::fetch_array($res);
        $sql = "SHOW TABLES FROM ".$course['db_name'];
        $res = Database::query($sql);
        while ($table = Database::fetch_array($res)) {
            $sql = "SELECT * FROM ".$course['db_name'].".".$table[0]."";
            $res3 = Database::query($sql);
            while ($row = Database::fetch_array($res3)) {
                foreach ($row as $key => $value) {
                    $row[$key] = $key."='".addslashes($row[$key])."'";
                }
                $sql_dump .= "\nINSERT INTO $table[0] SET ".implode(', ', $row).';';
            }
        }
        if (is_dir(api_get_path(SYS_COURSE_PATH).$course['directory'])) {
            $file_name = api_get_path(SYS_COURSE_PATH).$course['directory'].'/mysql_dump.sql';
            $handle = fopen($file_name, 'a+');
            if ($handle !== false) {
                fwrite($handle, $sql_dump);
                fclose($handle);
            } else {
                //TODO trigger exception in a try-catch
            }
        }
    }

    /**
     * Sort courses for a specific user ??
     * @param   int     User ID
     * @param   string  Course code
     * @return  int     Minimum course order
     * @todo Review documentation
     */
    public static function userCourseSort($user_id, $course_code) {

        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        $course_code = Database::escape_string($course_code);
        $TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $course_title = Database::result(Database::query('SELECT title FROM '.$TABLECOURSE.
                ' WHERE code="'.$course_code.'"'), 0, 0);

        $result = Database::query('SELECT course.code as code, course.title as title, cu.sort as sort FROM '.$TABLECOURSUSER.' as cu, '.$TABLECOURSE.' as course
                WHERE course.code = cu.course_code
                AND user_id = "'.$user_id.'"
                AND cu.relation_type<>'.COURSE_RELATION_TYPE_RRHH.'
                AND user_course_cat=0 ORDER BY cu.sort');

        $course_title_precedent = '';
        $counter = 0;
        $course_found = false;
        $course_sort = 1;

        while ($courses = Database::fetch_array($result)){

            if ($course_title_precedent == '') {
                $course_title_precedent = $courses['title'];
            }

            if (api_strcasecmp($course_title_precedent, $course_title) < 0) {

                $course_found = true;
                $course_sort = $courses['sort'];

                if ($counter == 0) {
                    $sql = 'UPDATE '.$TABLECOURSUSER.' SET sort = sort+1 WHERE user_id= "'.$user_id.'" AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND user_course_cat="0" AND sort > "'.$course_sort.'"';
                    $course_sort++;
                } else {
                    $sql = 'UPDATE '.$TABLECOURSUSER.' SET sort = sort+1 WHERE user_id= "'.$user_id.'" AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND user_course_cat="0" AND sort >= "'.$course_sort.'"';
                }

                Database::query($sql);
                break;

            } else {
                $course_title_precedent = $courses['title'];
            }

            $counter++;
        }

        // We must register the course in the beginning of the list
        if (Database::num_rows($result) > 0 && !$course_found) {
            $course_sort = Database::result(Database::query('SELECT min(sort) as min_sort FROM '.$TABLECOURSUSER.
                    ' WHERE user_id="'.$user_id.'" AND user_course_cat="0"'), 0, 0);

            Database::query('UPDATE '.$TABLECOURSUSER.' SET sort = sort+1
                    WHERE user_id= "'.$user_id.'" AND user_course_cat="0"');
        }

        return $course_sort;
    }

    /**
     * create recursively all categories as option of the select passed in paramater.
     *
     * @param object $select_element the quickform select where the options will be added
     * @param string $category_selected_code the option value to select by default (used mainly for edition of courses)
     * @param string $parent_code the parent category of the categories added (default=null for root category)
     * @param string $padding the indent param (you shouldn't indicate something here)
     */
    public static function select_and_sort_categories($select_element, $category_selected_code = '', $parent_code = null , $padding = '') {
        
        $sql = "SELECT code, name, auth_course_child, auth_cat_child
                FROM ".Database::get_main_table(TABLE_MAIN_CATEGORY)."
                WHERE parent_id ".(is_null($parent_code) ? "IS NULL" : "='".Database::escape_string($parent_code)."'")."
                ORDER BY code";
        $res = Database::query($sql);

        while ($cat = Database::fetch_array($res)) {
            $params = $cat['auth_course_child'] == 'TRUE' ? '' : 'disabled';
            $params .= ($cat['code'] == $category_selected_code) ? ' selected' : '';
            $select_element->addOption($padding.'('.$cat['code'].') '.$cat['name'], $cat['code'], $params);
            if ($cat['auth_cat_child']) {
                self::select_and_sort_categories($select_element, $category_selected_code, $cat['code'], $padding.' - ');
            }
        }
    }

    /**
     * check if course exists
     * @param string course_code
     * @param string whether to accept virtual course codes or not
     * @return true if exists, false else
     */
    public static function course_exists($course_code, $accept_virtual = false) {
        if ($accept_virtual === true) {
            $sql = 'SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_COURSE).' WHERE code="'.Database::escape_string($course_code).'" OR visual_code="'.Database::escape_string($course_code).'"';
        } else {
            $sql = 'SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_COURSE).' WHERE code="'.Database::escape_string($course_code).'"';
        }
        return Database::num_rows(Database::query($sql));
    }

    /**
     * Send an email to tutor after the auth-suscription of a student in your course
     * @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
     * @param  int $user_id the id of the user
     * @param  string $course_code the course code
     * @param  string $send_to_tutor_also
     * @return string we return the message that is displayed when the action is succesfull
     */
    public static function email_to_tutor($user_id, $course_code, $send_to_tutor_also = false) {

        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        $course_code = Database::escape_string($course_code);

        $student = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                WHERE user_id='".$user_id."'"));
        $information = self::get_course_information($course_code);
        $name_course = $information['title'];
        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." WHERE course_code='".$course_code."'";

        // TODO: Ivan: This is a mistake, please, have a look at it. Intention here is diffcult to be guessed.
        //if ($send_to_tutor_also = true) {
        // Proposed change:
        if ($send_to_tutor_also) {
        //
            $sql .= " AND tutor_id=1";
        } else {
            $sql .= " AND status=1";
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $tutor = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                    WHERE user_id='".$row['user_id']."'"));
            $emailto		 = $tutor['email'];
            $emailsubject	 = get_lang('NewUserInTheCourse').': '.$name_course;
            $emailbody		 = get_lang('Dear').': '. api_get_person_name($tutor['firstname'], $tutor['lastname'])."\n";
            $emailbody		.= get_lang('MessageNewUserInTheCourse').': '.$name_course."\n";
            $emailbody		.= get_lang('UserName').': '.$student['username']."\n";
            if (api_is_western_name_order()) {
                $emailbody	.= get_lang('FirstName').': '.$student['firstname']."\n";
                $emailbody	.= get_lang('LastName').': '.$student['lastname']."\n";
            } else {
                $emailbody	.= get_lang('LastName').': '.$student['lastname']."\n";
                $emailbody	.= get_lang('FirstName').': '.$student['firstname']."\n";
            }
            $emailbody		.= get_lang('Email').': '.$student['email']."\n\n";
            $recipient_name = api_get_person_name($tutor['firstname'], $tutor['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
            $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
            $email_admin = api_get_setting('emailAdministrator');
            @api_mail($recipient_name, $emailto, $emailsubject, $emailbody, $sender_name,$email_admin);
        }
    }
    
    public static function get_special_course_list() {
        $tbl_course_field           = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
        $tbl_course_field_value     = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        
          //we filter the courses from the URL
        $join_access_url = $where_access_url='';        
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $join_access_url= "LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.course_code= tcfv.course_code ";
                $where_access_url =" AND access_url_id = $access_url_id ";
            }
        }
        
        // get course list auto-register
        $sql = "SELECT DISTINCT(tcfv.course_code) FROM $tbl_course_field_value tcfv INNER JOIN $tbl_course_field tcf 
                ON tcfv.field_id =  tcf.id $join_access_url 
                WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1  $where_access_url";
        $special_course_result = Database::query($sql);
        $special_course_list = array();
        
        if (Database::num_rows($special_course_result)>0) {
            $special_course_list = array();
            while ($result_row = Database::fetch_array($special_course_result)) {
                $special_course_list[] = $result_row['course_code'];
            }
        }
        return $special_course_list;        
    }

    /**
     * Get list of courses for a given user
     * @param int       user ID
     * @param boolean   Whether to include courses from session or not
     * @return array    List of codes and db names
     * @author isaac flores paz
     */
    public static function get_courses_list_by_user_id($user_id, $include_sessions = false) {
        $user_id = intval($user_id);
        $course_list = array();
        $codes = array();
        $tbl_course                 = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user            = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_user_course_category   = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
                
        
        $special_course_list = self::get_special_course_list();
        
        $with_special_courses = $without_special_courses = '';
        if (!empty($special_course_list)) {
            $sc_string = '"'.implode('","',$special_course_list).'"';
            $with_special_courses = ' course.code IN ('.$sc_string.')';
            $without_special_courses = ' AND course.code NOT IN ('.$sc_string.')';
        }
        
        if (!empty($with_special_courses)) {
            $sql = "SELECT DISTINCT(course.code), course.db_name db_name, course.title
                        FROM    ".$tbl_course_user." course_rel_user
                        LEFT JOIN ".$tbl_course." course
                        ON course.code = course_rel_user.course_code
                        LEFT JOIN ".$tbl_user_course_category." user_course_category
                        ON course_rel_user.user_course_cat = user_course_category.id
                        WHERE  $with_special_courses
                        GROUP BY course.code
                        ORDER BY user_course_category.sort,course.title,course_rel_user.sort ASC";
            $rs_special_course = Database::query($sql);
            if (Database::num_rows($rs_special_course) > 0) {
                while ($result_row = Database::fetch_array($rs_special_course)) {
                        $result_row['special_course'] = 1;
                        $course_list[] = $result_row;
                        $codes[] = $result_row['code'];
                }
            }
        }

        // get course list not auto-register. Use Distinct to avoid multiple
        // entries when a course is assigned to a HRD (DRH) as watcher
        $sql = "SELECT DISTINCT(course.code),course.db_name,course.title
                FROM $tbl_course course
                INNER JOIN $tbl_course_user cru
                ON course.code=cru.course_code
                WHERE  cru.user_id='$user_id' $without_special_courses";

        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $course_list[] = $row;
                $codes[] = $row['code'];
            }
        }

        if ($include_sessions === true) {
            $r = Database::query("SELECT DISTINCT(c.code),c.db_name,c.title
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." s, ".Database::get_main_table(TABLE_MAIN_COURSE)." c
                    WHERE id_user = $user_id AND s.course_code=c.code");
            while ($row = Database::fetch_array($r, 'ASSOC')) {
                if (!in_array($row['code'], $codes)) {
                    $course_list[] = $row;
                }
            }
        }
        return $course_list;
    }

    /**
     * Get course ID from a given course directory name
     * @param   string  Course directory (without any slash)
     * @return  string  Course code, or false if not found
     */
    public static function get_course_id_from_path ($path) {
        $path = Database::escape_string(str_replace('.', '', str_replace('/', '', $path)));
        $res = Database::query("SELECT code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE directory LIKE BINARY '$path'");
        if ($res === false) {
            return false;
        }
        if (Database::num_rows($res) != 1) {
            return false;
        }
        $row = Database::fetch_array($res);
        return $row['code'];
    }

    /**
     * Get course code(s) from visual code
     * @param   string  Visual code
     * @return  array   List of codes for the given visual code
     */
    public static function get_courses_info_from_visual_code($code) {
        $result = array();
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE visual_code = '".Database::escape_string($code)."'");
        while ($virtual_course = Database::fetch_array($sql_result)) {
            $result[] = $virtual_course;
        }
        return $result;
    }

    /**
     * Get emails of tutors to course
     * @param string Visual code
     * @return array List of emails of tutors to course
     * @author @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
     * */
    public static function get_emails_of_tutors_to_course($code) {
        $list = array();
        $res = Database::query("SELECT user_id FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE course_code='".Database::escape_string($code)."' AND status=1");
        while ($list_users = Database::fetch_array($res)) {
            $result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                    WHERE user_id=".$list_users['user_id']);
            while ($row_user = Database::fetch_array($result)){
                $name_teacher = api_get_person_name($row_user['firstname'], $row_user['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
                $list[] = array($row_user['email'] => $name_teacher);
            }
        }
        return $list;
    }

    /**
     * Get coachs' emails by session
     * @param int session id
     * @param string course code
     * @return array  array(email => name_tutor)  by coach
     * @author Carlos Vargas <carlos.vargas@dokeos.com>
     */
    public static function get_email_of_tutor_to_session($session_id,$course_code) {

        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $coachs_emails = array();

        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);

        $sql = "SELECT id_user FROM $tbl_session_course_user WHERE id_session='$session_id' AND course_code='$course_code' AND status =2";
        $rs  = Database::query($sql);

        if (Database::num_rows($rs) > 0) {

            $user_ids = array();
            while ($row = Database::fetch_array($rs)) {
                $user_ids[] = $row['id_user'];
            }

            $sql = "SELECT firstname,lastname,email FROM $tbl_user WHERE user_id IN (".implode(",",$user_ids).")";
            $rs_user = Database::query($sql);

            while ($row_emails = Database::fetch_array($rs_user)) {
                $name_tutor = api_get_person_name($row_emails['firstname'], $row_emails['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
                $mail_tutor = array($row_emails['email'] => $name_tutor);
                $coachs_emails[] = $mail_tutor;
            }
        }

        return $coachs_emails;
    }

    /**
     * Creates a new extra field for a given course
      * @param	string	Field's internal variable name
      * @param	int		Field's type
      * @param	string	Field's language var name
      * @return int     new extra field id
      */
    public static function create_course_extra_field($fieldvarname, $fieldtype, $fieldtitle) {
        // database table definition
        $t_cfv			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $t_cf 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $fieldvarname 	= Database::escape_string($fieldvarname);
        $fieldtitle 	= Database::escape_string($fieldtitle);
        $fieldtype = (int)$fieldtype;
        $time = time();
        $sql_field = "SELECT id FROM $t_cf WHERE field_variable = '$fieldvarname'";
        $res_field = Database::query($sql_field);

        $r_field = Database::fetch_row($res_field);

        if (Database::num_rows($res_field) > 0) {
            return $r_field[0];
        }

        // save new fieldlabel into course_field table
        $sql = "SELECT MAX(field_order) FROM $t_cf";
        $res = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_row($res);
            $order = $row[0] + 1;
        }

        $sql = "INSERT INTO $t_cf
                                    SET field_type = '$fieldtype',
                                    field_variable = '$fieldvarname',
                                    field_display_text = '$fieldtitle',
                                    field_order = '$order',
                                    tms = FROM_UNIXTIME($time)";
        Database::query($sql);

        return Database::insert_id();
    }

    /**
     * Updates course attribute. Note that you need to check that your attribute is valid before you use this function
     *
     * @param int Course id
     * @param string Attribute name
     * @param string Attribute value
     * @return bool True if attribute was successfully updated, false if course was not found or attribute name is invalid
     */
    public static function update_attribute($id, $name, $value) {
        $id = (int)$id;
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "UPDATE $table SET $name = '".Database::escape_string($value)."' WHERE id = '$id';";
        return Database::query($sql);
    }

    /**
     * Update course attributes. Will only update attributes with a non-empty value. Note that you NEED to check that your attributes are valid before using this function
     *
     * @param int Course id
     * @param array Associative array with field names as keys and field values as values
     * @return bool True if update was successful, false otherwise
     */
    public static function update_attributes($id, $attributes) {
        $id = (int)$id;
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "UPDATE $table SET ";
        $i = 0;
        foreach($attributes as $name => $value) {
            if($value != '') {
                if($i > 0) {
                    $sql .= ", ";
                }
                $sql .= " $name = '".Database::escape_string($value)."'";
                $i++;
            }
        }
        $sql .= " WHERE id = '$id';";
        return Database::query($sql);
    }


    /**
     * Update an extra field value for a given course
     * @param	integer	Course ID
     * @param	string	Field variable name
     * @param	string	Field value
     * @return	boolean	true if field updated, false otherwise
     */
    public static function update_course_extra_field_value($course_code, $fname, $fvalue = '') {

        $t_cfv			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $t_cf 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $fname = Database::escape_string($fname);
        $course_code = Database::escape_string($course_code);
        $fvalues = '';
        if (is_array($fvalue)) {
            foreach ($fvalue as $val) {
                $fvalues .= Database::escape_string($val).';';
            }
            if (!empty($fvalues)) {
                $fvalues = substr($fvalues, 0, -1);
            }
        } else {
            $fvalues = Database::escape_string($fvalue);
        }

        $sqlcf = "SELECT * FROM $t_cf WHERE field_variable='$fname'";
        $rescf = Database::query($sqlcf);
        if (Database::num_rows($rescf) == 1) {
            // Ok, the field exists
            // Check if enumerated field, if the option is available
            $rowcf = Database::fetch_array($rescf);

            $tms = time();
            $sqlcfv = "SELECT * FROM $t_cfv WHERE course_code = '$course_code' AND field_id = '".$rowcf['id']."' ORDER BY id";
            $rescfv = Database::query($sqlcfv);
            $n = Database::num_rows($rescfv);
            if ($n > 1) {
                //problem, we already have to values for this field and user combination - keep last one
                while ($rowcfv = Database::fetch_array($rescfv)) { // See the TODO note below.
                    if ($n > 1) {
                        $sqld = "DELETE FROM $t_cfv WHERE id = ".$rowcfv['id'];
                        $resd = Database::query($sqld);
                        $n--;
                    }
                    $rowcfv = Database::fetch_array($rescfv);
                    if ($rowcfv['field_value'] != $fvalues) {
                        $sqlu = "UPDATE $t_cfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowcfv['id'];
                        $resu = Database::query($sqlu);
                        return ($resu ? true : false);
                    }
                    return true; // TODO: Sure exit from the function occures in this "while" cycle. Logic should checked. Maybe "if" instead of "while"? It is not clear...
                }
            } elseif ($n == 1) {
                //we need to update the current record
                $rowcfv = Database::fetch_array($rescfv);
                if ($rowcfv['field_value'] != $fvalues) {
                    $sqlu = "UPDATE $t_cfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowcfv['id'];
                    //error_log('UM::update_extra_field_value: '.$sqlu);
                    $resu = Database::query($sqlu);
                    return ($resu ? true : false);
                }
                return true;
            } else {
                $sqli = "INSERT INTO $t_cfv (course_code,field_id,field_value,tms) " .
                    "VALUES ('$course_code',".$rowcf['id'].",'$fvalues',FROM_UNIXTIME($tms))";
                //error_log('UM::update_extra_field_value: '.$sqli);
                $resi = Database::query($sqli);
                return ($resi ? true : false);
            }
        } else {
            return false; //field not found
        }
    }

    /**
     * Get the course id of an course by the database name
     * @param string The database name
     * @return string The course id
     */
    public static function get_course_id_by_database_name($db_name) {
        return Database::result(Database::query('SELECT code FROM '.Database::get_main_table(TABLE_MAIN_COURSE).
                ' WHERE db_name="'.Database::escape_string($db_name).'"'), 0, 'code');
    }

    public static function get_session_category_id_by_session_id($session_id) {
        return Database::result(Database::query('SELECT  sc.id session_category
                FROM '.Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY).' sc
                INNER JOIN '.Database::get_main_table(TABLE_MAIN_SESSION).' s
                ON sc.id=s.session_category_id WHERE s.id="'.Database::escape_string($session_id).'"'),
            0, 'session_category');
    }

    /**
     * Get the course id of an course by the database name
     * @param string The database name
     * @return string The course id
     */
    public static function get_course_extra_field_list($code) {
        $tbl_course_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $tbl_course_field_value	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $sql_field = "SELECT id, field_type, field_variable, field_display_text, field_default_value
            FROM $tbl_course_field  WHERE field_visible = '1' ";
        $res_field = Database::query($sql_field);
        $extra_fields = array();
        while($rowcf = Database::fetch_array($res_field)) {
            $extra_field_id = $rowcf['id'];
            $sql_field_value = "SELECT field_value FROM $tbl_course_field_value WHERE course_code = '$code' AND field_id = '$extra_field_id' ";
            $res_field_value = Database::query($sql_field_value);
            if(Database::num_rows($res_field_value) > 0 ) {
                $r_field_value = Database::fetch_row($res_field_value);
                $rowcf['extra_field_value'] = $r_field_value[0];
            }
            $extra_fields[] = $rowcf;
        }
        return $extra_fields;
    }

    /**
     * Gets the value of a course extra field. Returns null if it was not found
     *
     * @param string Name of the extra field
     * @param string Course code
     * @return string Value
     */
    public static function get_course_extra_field_value($field_name, $code) {
        $tbl_course_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $tbl_course_field_value	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $sql = "SELECT id FROM $tbl_course_field WHERE field_visible = '1' AND field_variable = '$field_name';";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if(!$row) {
            return null;
        } else {
            $sql_field_value = "SELECT field_value FROM $tbl_course_field_value WHERE course_code = '$code' AND field_id = '{$row->id}';";
            $res_field_value = Database::query($sql_field_value);
            $row_field_value = Database::fetch_object($res_field_value);
            if(!$row_field_value) {
                return null;
            } else {
                return $row_field_value['field_value'];
            }
        }
    }


    /**
     * Get the database name of a course by the code
     * @param string The course code
     * @return string The database name
     */
    public static function get_name_database_course($course_code) {
        return Database::result(Database::query('SELECT db_name FROM '.Database::get_main_table(TABLE_MAIN_COURSE).
                ' WHERE code="'.Database::escape_string($course_code).'"'), 0, 'db_name');
    }

    /**
     * Lists details of the course description
     * @param array		The course description
     * @param string	The encoding
     * @param bool		If true is displayed if false is hidden
     * @return string 	The course description in html
     */
    public static function get_details_course_description_html($descriptions, $charset, $action_show = true) {
        if (isset($descriptions) && count($descriptions) > 0) {
            $data = '';
            foreach ($descriptions as $id => $description) {
                $data .= '<div class="sectiontitle">';
                if (api_is_allowed_to_edit() && $action_show) {
                    //delete
                    $data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=delete&amp;description_id='.$description->id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).'\')) return false;">';
                    $data .= Display::return_icon('delete.gif', get_lang('Delete'), array('style' => 'vertical-align:middle;float:right;'));
                    $data .= '</a> ';
                    //edit
                    $data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;description_id='.$description->id.'">';
                    $data .= Display::return_icon('edit.png', get_lang('Edit'), array('style' => 'vertical-align:middle;float:right; padding-right:4px;'), 22);
                    $data .= '</a> ';
                }
                $data .= $description->title;
                $data .= '</div>';
                $data .= '<div class="sectioncomment">';
                $data .= text_filter($description->content);
                $data .= '</div>';
            }
        } else {
            $data .= '<em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
        }

        return $data;
    }

    /**
     * Returns the details of a course category
     *
     * @param string Category code
     * @return array Course category
     */
    public static function get_course_category($code) {
        $table_categories = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT * FROM $table_categories WHERE code = '$code';";
        return Database::fetch_array(Database::query($sql));
    }

    /*
    ==============================================================================
        DEPRECATED METHODS
    ==============================================================================
    */

    /**
     *	This code creates a select form element to let the user
     *	choose a real course to link to.
     *
     *	A good non-display library should not use echo statements, but just return text/html
     *	so users of the library can choose when to display.
     *
     *	We display the course code, but internally store the course id.
     *
     *	@param boolean $has_size, true the select tag gets a size element, false it stays a dropdownmenu
     *	@param boolean $only_current_user_courses, true only the real courses of which the
     *	current user is course admin are displayed, false all real courses are shown.
     *	@param string $element_name the name of the select element
     *	@return a string containing html code for a form select element.
     * @deprecated Function not in use
     */
    public static function get_real_course_code_select_html($element_name, $has_size = true, $only_current_user_courses = true, $user_id) {
        if ($only_current_user_courses) {
            $real_course_list = self::get_real_course_list_of_user_as_course_admin($user_id);
        } else {
            $real_course_list = self::get_real_course_list();
        }

        if ($has_size) {
            $size_element = "size=\"".SELECT_BOX_SIZE."\"";
        } else {
            $size_element = "";
        }
        $html_code = "<select name=\"$element_name\" $size_element >\n";
        foreach ($real_course_list as $real_course) {
            $course_code = $real_course["code"];
            $html_code .= "<option value=\"".$course_code."\">";
            $html_code .= $course_code;
            $html_code .= "</option>\n";
        }
        $html_code .= "</select>\n";

        return $html_code;
    }

    /**
     * 	Get count rows of a table inside a course database
     *  @param  string	The table of which the rows should be counted
     *  @param  int		optionally count rows by session id
     *  @return int 	The number of rows in the given table.
     */
    public static function count_rows_course_table($table, $session_id = '') {
        $condition_session = '';
        if ($session_id !== '') {
            $session_id = intval($session_id);
            $condition_session = " WHERE session_id = '$session_id' ";
        }
        $sql	= "SELECT COUNT(*) AS n FROM $table $condition_session ";
        $rs 	= Database::query($sql);
        $row 	= Database::fetch_row($rs);
        return $row[0];
    }

    /**
      * Subscribes courses to human resource manager (Dashboard feature)
      *	@param	int 		Human Resource Manager id
      * @param	array		Courses code
      * @param	int			Relation type
      **/
    public static function suscribe_courses_to_hr_manager($hr_manager_id,$courses_list) {
        global $_configuration;

        // Database Table Definitions
        $tbl_course             =   Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_rel_user    =   Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course_rel_access_url     =   Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $hr_manager_id = intval($hr_manager_id);
        $affected_rows = 0;

        //Deleting assigned courses to hrm_id
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT s.course_code FROM $tbl_course_rel_user s INNER JOIN $tbl_course_rel_access_url a ON (a.course_code = s.course_code) WHERE user_id = $hr_manager_id AND relation_type=".COURSE_RELATION_TYPE_RRHH." AND access_url_id = ".api_get_current_access_url_id()."";
        } else {
            $sql = "SELECT course_code FROM $tbl_course_rel_user WHERE user_id = $hr_manager_id AND relation_type=".COURSE_RELATION_TYPE_RRHH." ";
        }
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result))   {
                $sql = "DELETE FROM $tbl_course_rel_user WHERE course_code = '{$row['course_code']}' AND user_id = $hr_manager_id AND relation_type=".COURSE_RELATION_TYPE_RRHH." ";
                Database::query($sql);
            }
        }

        // inserting new courses list
        if (is_array($courses_list)) {
            foreach ($courses_list as $course_code) {
                $course_code = Database::escape_string($course_code);
                $insert_sql = "INSERT IGNORE INTO $tbl_course_rel_user(course_code, user_id, status, relation_type) VALUES('$course_code', $hr_manager_id, '".DRH."', '".COURSE_RELATION_TYPE_RRHH."')";
                Database::query($insert_sql);
                if (Database::affected_rows()) {
                    $affected_rows++;
                }
            }
        }
        return $affected_rows;

    }

    /**
     * get courses followed by human resources manager
     * @param int 		human resources manager id
     * @return array	courses
     */
    public static function get_courses_followed_by_drh($hr_dept_id) {        
        // Database Table Definitions
        $tbl_course 			= 	Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_rel_user 	= 	Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course_rel_access_url =   Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $hr_dept_id = intval($hr_dept_id);
        $assigned_courses_to_hrm = array();

        if (api_get_multiple_access_url()) {
           $sql = "SELECT * FROM $tbl_course c
                    INNER JOIN $tbl_course_rel_user cru ON (cru.course_code = c.code) LEFT JOIN $tbl_course_rel_access_url a  ON (a.course_code = c.code) WHERE cru.user_id = '$hr_dept_id' AND status = ".DRH." AND relation_type = '".COURSE_RELATION_TYPE_RRHH."' AND access_url_id = ".api_get_current_access_url_id()."";
        } else {
            $sql = "SELECT * FROM $tbl_course c
                    INNER JOIN $tbl_course_rel_user cru ON cru.course_code = c.code AND cru.user_id = '$hr_dept_id' AND status = ".DRH." AND relation_type = '".COURSE_RELATION_TYPE_RRHH."' ";
        }
        $rs_assigned_courses = Database::query($sql);
        if (Database::num_rows($rs_assigned_courses) > 0) {
            while ($row_assigned_courses = Database::fetch_array($rs_assigned_courses))	{
                $assigned_courses_to_hrm[$row_assigned_courses['code']] = $row_assigned_courses;
            }
        }
        return $assigned_courses_to_hrm;
    }

    /**
     * check if a course is special (autoregister)
     * @param string course code
     */
    public static function is_special_course($course_code){
        $tbl_course_field_value		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $tbl_course_field 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $is_special = false;
        $sql = "SELECT course_code FROM $tbl_course_field_value tcfv INNER JOIN $tbl_course_field tcf ON " .
                " tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 AND course_code='$course_code'";
        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);
        if ($num_rows > 0){
            $is_special = true;

        }
        return $is_special;

    }

    /**
     * Update course picture
     * @param   string  Course code
     * @param   string  File name
     * @param   string  The full system name of the image from which course picture will be created.
     * @return  bool    Returns the resulting. In case of internal error or negative validation returns FALSE.
     */
    public static function update_course_picture($course_code, $filename, $source_file = null) {

        $course_info          = api_get_course_info($course_code);
        $store_path           = api_get_path(SYS_COURSE_PATH).$course_info['path'];   // course path
        $course_image         = $store_path.'/course-pic.png';                      // image name for courses
        $course_medium_image  = $store_path.'/course-pic85x85.png';
        $extension            = strtolower(substr(strrchr($filename, '.'), 1));

        $result = false;        
        if (file_exists($course_image)) {
            @unlink($course_image);
        }
        if (file_exists($course_medium_image)) {
            @unlink($course_medium_image);
        }

        $my_course_image = new Image($source_file);
        $result = $my_course_image->send_image($course_image, -1, 'png');
        //Redimension image to 100x85
        if ($result) {                
            $medium = new Image($course_image);
            $picture_infos = $medium->get_image_size();            
            $medium->resize(100, 85, 0, false);            
            $rs = $medium->send_image($store_path.'/course-pic85x85.png', -1, 'png');
        }
        return $result;
    }

    /**
     *  @deprecated See CourseManager::course_code_exists()
     */
    public static function is_existing_course_code($wanted_course_code) {
        return self::course_code_exists($wanted_course_code);
    }

    /**
     * Display special courses (and only these) as several HTML divs of class userportal-course-item
     *
     * Special courses are courses that stick on top of the list and are "auto-registerable"
     * in the sense that any user clicking them is registered as a student
     * @param int       User id
     * @return void
     */
    function display_special_courses ($user_id) {

        $user_id = intval($user_id);
        $user_info = api_get_user_info($user_id);
        
        $tbl_course                 = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user            = Database::get_main_table(TABLE_MAIN_COURSE_USER);        
        $tbl_user_course_category   = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

        $special_course_list        = self::get_special_course_list();
        
        $with_special_courses = $without_special_courses = '';
        if (!empty($special_course_list)) {
            $with_special_courses = ' course.code IN ("'.implode('","',$special_course_list).'")';
        }

        if (!empty($with_special_courses)) {
            $sql = "SELECT course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
                                course.title title, course.tutor_name tutor, course.db_name, course.directory, course_rel_user.status status,
                                course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat, course_rel_user.user_id, course.visibility
                                FROM $tbl_course course
                                LEFT JOIN $tbl_course_user course_rel_user ON course.code = course_rel_user.course_code AND course_rel_user.user_id = '$user_id'
                                WHERE $with_special_courses group by course.code";

            $rs_special_course = Database::query($sql);
            $number_of_courses = Database::num_rows($rs_special_course);
            $key = 0;
            $status_icon = '';
            if ($number_of_courses > 0) {
                while ($course = Database::fetch_array($rs_special_course)) {

                    // Get notifications.
                    $my_course = array();
                    $my_course['db']         = $course['db_name'];
                    $my_course['k']          = $course['code'];
                    $my_course['id_session'] = null;
                    $my_course['s']          = $course['status'];
                    $show_notification = Display::show_notification($my_course);

                    if (empty($course['user_id'])) {
                        $course['status'] = $user_info['status'];
                    }

                    $status_icon = Display::return_icon('blackboard.png', get_lang('Course'), array('width'=>'48px'));
                    /*
                    if ($course['status'] == 1) {
                        $status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'), array('style' => 'width: 11px; height: 11px;'));
                    }
                    if (($course['status'] == 5 && !api_is_coach()) || empty($course['status'])) {
                        $status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('students.gif', get_lang('Status').': '.get_lang('Student'), array('style' => 'width: 11px; height: 11px;'));
                    }*/

                    echo '<div class="userportal-course-item">';

                    if (api_is_platform_admin()) {
                        echo '<div style="float: right;"><a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course['code'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'), 22).'</a>';
                        if ($course['status'] == COURSEMANAGER) {
                            //echo Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'),array('style'=>'width:11px; height:11px;'));
                        }
                        echo '</div>';
                    }
                    $course_visibility = $course['visibility'];
                    if ($course_visibility != COURSE_VISIBILITY_CLOSED || $course['status'] == COURSEMANAGER) {
                        $course_title = '<a href="'.api_get_path(WEB_COURSE_PATH).$course['directory'].'/?id_session=0&amp;autoreg=1">'.$course['title'].'</a>';
                    } else {
                        $course_title = $course['title']." ".Display::tag('span',get_lang('CourseClosed'), array('class'=>'item_closed'));
                    }

                    echo '<div style="float: left; margin-right: 10px;">'.$status_icon.'</div><span class="userportal-course-item-title">'.$course_title.'</span><br />';
                    if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                        echo $course['visual_code'];
                    }
                    if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
                        echo ' - ';
                    }

                    if (api_get_setting('display_teacher_in_courselist') == 'true') {
                        echo $course['tutor'];
                    }
                    echo '&nbsp;';
                    echo Display::return_icon('klipper.png', get_lang('CourseAutoRegister'));

                    // Show notifications.
                    echo $show_notification;
                    echo '</div>';
                    $key++;
                }
            }
        }
    }
    /**
     * Display courses (without special courses) as several HTML divs
     * of course categories, as class userportal-catalog-item.
     * @uses display_courses_in_category() to display the courses themselves
     * @param int        user id
     * @return void
     */
    function display_courses($user_id) {
    
        global $_user, $_configuration;
    
        // Building an array that contains all the id's of the user defined course categories.
        // Initially this was inside the display_courses_in_category function but when we do it here we have fewer
        // sql executions = performance increase.
        $all_user_categories = self :: get_user_course_categories();
    
        // Step 0: We display the course without a user category.
        self :: display_courses_in_category(0, 'true');
    
        // Step 1: We get all the categories of the user.
        $tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT id, title FROM $tucc WHERE user_id='".$_user['user_id']."' ORDER BY sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // We simply display the title of the category.
            echo '<div class="userportal-catalog-item">';
                echo '<ul class="catalog_box">';
                    echo '<li>';
                        echo Display::return_icon('folder_yellow.png', get_lang('Category'), array('width' => '48px', 'align' => 'absmiddle'));
                        echo '<span>';
                        echo '<a name="category'.$row['id'].'"></a>'; // Display an internal anchor.
                        echo $row['title'];
                        echo '</span>';
                    echo '</li>';
                    self :: display_courses_in_category($row['id']);
            echo '</ul>';
            echo '</div>';
        }
    }    
    /**
     *  Display courses inside a category (without special courses) as HTML dics of
     *  class userportal-course-item.
     *  @param int      User category id
     *  @return void
     */
    function display_courses_in_category($user_category_id) {
    
        global $_user;
        // Table definitions
        $TABLECOURS                  = Database :: get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER              = Database :: get_main_table(TABLE_MAIN_COURSE_USER);                
        $TABLE_ACCESS_URL_REL_COURSE = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $TABLE_USER_COURSE_CATEGORY  = Database :: get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $current_url_id = api_get_current_access_url_id();
    
        // Get course list auto-register
        $special_course_list        = self::get_special_course_list();
        
        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.code NOT IN ("'.implode('","',$special_course_list).'")';
        }
    
        $sql_select_courses = "SELECT course.id, course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
                                        course.title title, course.tutor_name tutor, course.db_name, course.directory, course_rel_user.status status,
                                        course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat, course.visibility
                                        FROM    $TABLECOURS       course,
                                                $TABLECOURSUSER  course_rel_user, ".$TABLE_ACCESS_URL_REL_COURSE." url
                                        WHERE course.code = course_rel_user.course_code AND url.course_code = course.code
                                        AND   course_rel_user.user_id = '".$_user['user_id']."'
                                        AND course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                                        AND course_rel_user.user_course_cat='".$user_category_id."' $without_special_courses ";
        // If multiple URL access mode is enabled, only fetch courses
        // corresponding to the current URL.
        if (api_get_multiple_access_url() && $current_url_id != -1){
            $sql_select_courses .= " AND url.course_code=course.code AND access_url_id='".$current_url_id."'";
        }
        // Use user's classification for courses (if any).
        $sql_select_courses .= " ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";
        $result = Database::query($sql_select_courses);
        $number_of_courses = Database::num_rows($result);
        $key = 0;
        $status_icon = '';
    
        // Browse through all courses.
        while ($course = Database::fetch_array($result)) {
            // Get notifications.
            $my_course = array();
            $my_course['db']         = $course['db_name'];
            $my_course['k']          = $course['code'];
            $my_course['id_session'] = null;
            $my_course['s']          = $course['status'];
            // For each course, get if there is any notification icon to show
            // (something that would have changed since the user's last visit).
            $show_notification = Display :: show_notification($my_course);
            // New code displaying the user's status in respect to this course.
            $status_icon = Display::return_icon('blackboard.png', get_lang('Course'), array('width' => '48px'));
    
            echo '<div class="userportal-course-item">';
    
            if (api_is_platform_admin()) {
                echo '<div style="float:right;">';
                
                echo '<a id="document_preview_'.$course['id'].'" class="document_preview" href="#">'.Display::return_icon('folder.png', get_lang('Documents'), array('align' => 'absmiddle'),22).'</a>';
                echo '<a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course['code'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),22).'</a>';
                echo '<div class="clear"></div>';
                echo Display::div('', array('id' => 'document_result_'.$course['id'], 'class'=>'document_preview_container'));
                
                if ($course['status'] == COURSEMANAGER) {
                    //echo Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'), array('style'=>'width: 11px; height: 11px;'));
                }
                echo '</div>';
            }
    
            // Function logic - act on the data (check if the course is virtual, if yes change the link).
            $is_virtual_course = CourseManager :: is_virtual_course_from_system_code($course['code']);
            if ($is_virtual_course) {
                // If the current user is also subscribed in the real course to which this
                // virtual course is linked, we don't need to display the virtual course entry in
                // the course list - it is combined with the real course entry.
                $target_course_code = CourseManager :: get_target_of_linked_course($course['code']);
                $is_subscribed_in_target_course = CourseManager :: is_user_subscribed_in_course(api_get_user_id(), $target_course_code);
                if ($is_subscribed_in_target_course) {
                    return; // Do not display this course entry.
                }
            }
            // Check if the course has virtual courses attached. If yes change the course title and display code.
            $has_virtual_courses = CourseManager :: has_virtual_courses_from_code($course['code'], api_get_user_id());
            if ($has_virtual_courses) {
                $course_info = api_get_course_info($course['code']);
                $return_result = CourseManager :: determine_course_title_from_course_info(api_get_user_id(), $course_info);
                $course_title = $return_result['title'];
                $course_display_code = $return_result['code'];
            } else {
                $course_title = $course['title'];
                $course_display_code = $course['visual_code'];
            }
    
            // Show a hyperlink to the course, unless the course is closed and user is not course admin.
            $course_visibility = $course['visibility'];
            $course_title_url = '';
            if ($course_visibility != COURSE_VISIBILITY_CLOSED || $course['status'] == COURSEMANAGER) {
                $course_title_url = api_get_path(WEB_COURSE_PATH).$course['directory'].'/?id_session=0';
                $course_title = Display::url($course['title'], $course_title_url);
                
            } else {
                $course_title = $course['title']." ".Display::tag('span',get_lang('CourseClosed'), array('class'=>'item_closed'));
            }
            if (!empty($course_title_url)) {
                $status_icon = Display::url($status_icon, $course_title_url);
            }
            // Start displaying the course block itself.
            echo '<div style="float: left; margin-right: 10px;">'.$status_icon.'</div><span class="userportal-course-item-title">'.$course_title.'</span><br />';
            
            if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                echo $course_display_code;
            }
            if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
                echo ' - ';
            }
            if (api_get_setting('display_teacher_in_courselist') == 'true') {
                if (!empty($course['tutor']))
                    echo $course['tutor'];
            }
            // Show notifications.
            echo $show_notification;
            echo '</div>';
            $key++;
        }
    }
    /**
     * Retrieves the user defined course categories
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @return array containing all the titles of the user defined courses with the id as key of the array
     */
    function get_user_course_categories() {
        global $_user;
        $output = array();
        $table_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT * FROM ".$table_category." WHERE user_id='".Database::escape_string($_user['user_id'])."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $output[$row['id']] = $row['title'];
        }
        return $output;
    }

    /**
	 * Get the course id based on the original id and field name in the extra fields. Returns 0 if course was not found
	 *
	 * @param string Original course id
	 * @param string Original field name
	 * @return int Course id
	 */
	public static function get_course_id_from_original_id($original_course_id_value, $original_course_id_name) {
		$t_cfv = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
		$table_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
		$sql_course = "SELECT id FROM $table_field cf INNER JOIN $t_cfv cfv ON cfv.field_id=cf.id WHERE field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res = Database::query($sql_course);
		$row = Database::fetch_object($res);
		if($row != false) {
			return $row->id;
		} else {
			return 0;
		}
	}

    /**
     * Display code for one specific course a logged in user is subscribed to.
     * Shows a link to the course, what's new icons...
     *
     * $my_course['d'] - course directory
     * $my_course['i'] - course title
     * $my_course['c'] - visual course code
     * $my_course['k']  - system course code
     * $my_course['db'] - course database
     * 
     * @param   array       Course details
     * @param   integer     Session ID
     * @param   string      CSS class to apply to course entry
     * @param   boolean     Whether the session is supposedly accessible now (not in the case it has passed and is in invisible/unaccessible mode)
     * @return  string      The HTML to be printed for the course entry
     *
     * @version 1.0.3
     * @todo refactor into different functions for database calls | logic | display
     * @todo replace single-character $my_course['d'] indices
     * @todo move code for what's new icons to a separate function to clear things up
     * @todo add a parameter user_id so that it is possible to show the courselist of other users (=generalisation). This will prevent having to write a new function for this.
     */
    function get_logged_user_course_html($course, $session_id = 0, $class = 'courses', $session_accessible = true) {
        global $nosession, $nbDigestEntries, $digest, $thisCourseSysCode, $orderKey;
        $charset = api_get_system_encoding();
    
        $current_uid = api_get_user_id();
        $info = api_get_course_info($course['code']);
        $status_course = CourseManager::get_user_in_course_status($current_uid, $course['code']);
    
        if (!is_array($course['code'])) {
            $my_course = api_get_course_info($course['code']);
            $my_course['k'] = $my_course['id'];
            $my_course['db'] = $my_course['dbName'];
            $my_course['c'] = $my_course['official_code'];
            $my_course['i'] = $my_course['name'];
            $my_course['d'] = $my_course['path'];
            $my_course['t'] = $my_course['titular'];
            $my_course['id_session'] = $session_id;
            $my_course['status'] = empty($session_id) ? $status_course : 5;
        }
    
        if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
            global $now, $date_start, $date_end;
        }
    
        // Initialise.
        $result = '';
    
        // Table definitions
        //$statistic_database = Database::get_statistic_database();
        $main_user_table            = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category       = Database :: get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $course_database            = $my_course['db'];
        $course_tool_table          = Database :: get_course_table(TABLE_TOOL_LIST, $course_database);
        $tool_edit_table            = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_database);
        $course_group_user_table    = Database :: get_course_table(TOOL_USER, $course_database);
    
        $user_id                = api_get_user_id();
        $course_system_code     = $my_course['k'];
        $course_visual_code     = $my_course['c'];
        $course_title           = $my_course['i'];
        $course_directory       = $my_course['d'];
        $course_teacher         = $my_course['t'];
        $course_teacher_email   = isset($my_course['email']) ? $my_course['email'] : '';
        $course_info = Database :: get_course_info($course_system_code);
        $course_access_settings = CourseManager :: get_access_settings($course_system_code);
        $course_id = isset($course_info['course_id']) ? $course_info['course_id'] : null;
        $course_visibility = $course_access_settings['visibility'];
    
        $user_in_course_status = CourseManager :: get_user_in_course_status(api_get_user_id(), $course_system_code);
    
        // Function logic - act on the data.
        $is_virtual_course = CourseManager :: is_virtual_course_from_system_code($my_course['k']);
        if ($is_virtual_course) {
            // If the current user is also subscribed in the real course to which this
            // virtual course is linked, we don't need to display the virtual course entry in
            // the course list - it is combined with the real course entry.
            $target_course_code = CourseManager :: get_target_of_linked_course($course_system_code);
            $is_subscribed_in_target_course = CourseManager :: is_user_subscribed_in_course(api_get_user_id(), $target_course_code);
            if ($is_subscribed_in_target_course) {
                return; //do not display this course entry
            }
        }
        $has_virtual_courses = CourseManager :: has_virtual_courses_from_code($course_system_code, api_get_user_id());
        if ($has_virtual_courses) {
            $return_result = CourseManager :: determine_course_title_from_course_info(api_get_user_id(), $course_info);
            $course_display_title = $return_result['title'];
            $course_display_code = $return_result['code'];
        } else {
            $course_display_title = $course_title;
            $course_display_code = $course_visual_code;
        }
    
        $s_course_status = $my_course['status'];
        $is_coach = api_is_coach($my_course['id_session'],$course['code']);
            
        $s_htlm_status_icon = Display::return_icon('blackboard_blue.png', get_lang('Course'), array('width' => '48px'));
        /*
        if ($s_course_status == 1) {
            $s_htlm_status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'), array('style' => 'width: 11px; height: 11px;'));
        }
        if ($s_course_status == 2 || ($is_coach && $s_course_status != 1)) {
            $s_htlm_status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('coachs.gif', get_lang('Status').': '.get_lang('GeneralCoach'), array('style' => 'width: 11px; height: 11px;'));
        }
        if (($s_course_status == 5 && !$is_coach) || empty($s_course_status)) {
            $s_htlm_status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('students.gif', get_lang('Status').': '.get_lang('Student'), array('style' => 'width: 11px; height: 11px;'));
        }
        */
        // Display course entry.
        
        // Show a hyperlink to the course, unless the course is closed and user is not course admin.
        $session_url = '';
        $session_title = '';
        if ($session_accessible) {
            if ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER) {
                if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
                    if (empty($my_course['id_session'])) {
                        $my_course['id_session'] = 0;
                    }
                    if ($user_in_course_status == COURSEMANAGER || ($date_start <= $now && $date_end >= $now) || $date_start == '0000-00-00') {
                        $session_url = api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'];
                        $session_title = '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'].'">'.$course_display_title.'</a>';
                    }
                } else {
                    $session_url   = api_get_path(WEB_COURSE_PATH).$course_directory.'/';
                    $session_title = '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/">'.$course_display_title.'</a>';
                }
            } else {
                $session_title = $course_display_title.' '.Display::tag('span',get_lang('CourseClosed'), array('class'=>'item_closed'));                
            }
        } else {
        	$session_title = $course_display_title;
        }
        
        if (!empty($session_url)) {
            $s_htlm_status_icon = Display::url($s_htlm_status_icon, $session_url);
        }
        
        $result .= '<li class="'.$class.'"><div class="coursestatusicons">'.$s_htlm_status_icon.'</div>';
        $result .= $session_title;
        
        // Show the course_code and teacher if chosen to display this.
        if (api_get_setting('display_coursecode_in_courselist') == 'true' || api_get_setting('display_teacher_in_courselist') == 'true') {
            $result .= '<br />';
        }
    
        if (api_get_setting('display_coursecode_in_courselist') == 'true') {
            $result .= $course_display_code;
        }
    
        if (api_get_setting('display_teacher_in_courselist') == 'true') {
            if (api_get_setting('use_session_mode')=='true' && !$nosession) {
                $coachs_course = api_get_coachs_from_course($my_course['id_session'], $course['code']);
                $course_coachs = array();
                if (is_array($coachs_course)) {
                    foreach ($coachs_course as $coach_course) {
                        $course_coachs[] = api_get_person_name($coach_course['firstname'], $coach_course['lastname']);
                    }
                }
    
                if ($s_course_status == 1 || ($s_course_status == 5 && empty($my_course['id_session'])) || empty($s_course_status)) {
                    $result .= $course_teacher;
                }
    
                if (($s_course_status == 5 && !empty($my_course['id_session'])) || ($is_coach && $s_course_status != 1)) {
                    if (is_array($course_coachs) && count($course_coachs)> 0 ) {
                        if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
                            $result .= ' &ndash; ';
                        }
                        $result .= get_lang('Coachs').': '.implode(', ',$course_coachs);
                    }
                }
    
            } else {
                $result .= $course_teacher;
            }
    
            if(!empty($course_teacher_email)) {
                $result .= ' ('.$course_teacher_email.')';
            }
        }
    
        $result .= isset($course['special_course']) ? ' '.Display::return_icon('klipper.png', get_lang('CourseAutoRegister')) : '';
    
        $current_course_settings = CourseManager :: get_access_settings($my_course['k']);
    
        // Display the "what's new" icons.
        $result .= Display :: show_notification($my_course);
    
        if ((CONFVAL_showExtractInfo == SCRIPTVAL_InCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both) && $nbDigestEntries > 0) {
    
            reset($digest);
            $result .= '
                        <ul>';
            while (list($key2) = each($digest[$thisCourseSysCode])) {
                $result .= '<li>';
                if ($orderKey[1] == 'keyTools') {
                    $result .= "<a href=\"$toolsList[$key2] [\"path\"] $thisCourseSysCode \">";
                    $result .= "$toolsList[$key2][\"name\"]</a>";
                } else {
                    $result .= api_convert_and_format_date($key2, DATE_FORMAT_LONG, date_default_timezone_get());
                }
                $result .= '</li>';
                $result .= '<ul>';
                reset ($digest[$thisCourseSysCode][$key2]);
                while (list ($key3, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2])) {
                    $result .= '<li>';
                    if ($orderKey[2] == 'keyTools') {
                        $result .= "<a href=\"$toolsList[$key3] [\"path\"] $thisCourseSysCode \">";
                        $result .= "$toolsList[$key3][\"name\"]</a>";
                    } else {
                        $result .= api_convert_and_format_date($key3, DATE_FORMAT_LONG, date_default_timezone_get());
                    }
                    $result .= '<ul compact="compact">';
                    reset($digest[$thisCourseSysCode][$key2][$key3]);
                    while (list ($key4, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2][$key3])) {
                        $result .= '<li>';
                        $result .= @htmlspecialchars(api_substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT), ENT_QUOTES, $charset);
                        $result .= '</li>';
                    }
                    $result .= '</ul>';
                    $result .= '</li>';
                }
                $result .= '</ul>';
                $result .= '</li>';
            }
            $result .= '</ul>';
        }
        $result .= '</li>';
        $session_category_id = null;
        if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
            $session = '';
            $active = false;
            if (!empty($my_course['session_name'])) {
    
                // Request for the name of the general coach
                $sql = 'SELECT lastname, firstname,sc.name
                FROM '.$tbl_session.' ts
                LEFT JOIN '.$main_user_table .' tu
                ON ts.id_coach = tu.user_id
                INNER JOIN '.$tbl_session_category.' sc ON ts.session_category_id = sc.id
                WHERE ts.id='.(int) $my_course['id_session']. ' LIMIT 1';
    
                $rs = Database::query($sql);
                $sessioncoach = Database::store_result($rs);
                $sessioncoach = $sessioncoach[0];
    
                $session = array();
                $session['title'] = $my_course['session_name'];
                $session_category_id = CourseManager::get_session_category_id_by_session_id($my_course['id_session']);
                $session['category'] = $sessioncoach['name'];
                if ($my_course['date_start'] == '0000-00-00') {
                    //$session['dates'] = get_lang('WithoutTimeLimits');
                    $session['dates'] = '';
                    if (api_get_setting('show_session_coach') === 'true') {
                        $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                    }
                    $active = true;
                } else {
                    $session ['dates'] = ' - '.get_lang('From').' '.$my_course['date_start'].' '.get_lang('To').' '.$my_course['date_end'];
                    if (api_get_setting('show_session_coach') === 'true') {
                        $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
                    }
                    $active = ($date_start <= $now && $date_end >= $now);
                }
            }
            $user_course_category = '';
            if (isset($my_course['user_course_cat'])) {
                 $user_course_category = $my_course['user_course_cat'];
            }
            $output = array ($user_course_category, $result, $my_course['id_session'], $session, 'active' => $active, 'session_category_id' => $session_category_id);
        } else {
            $output = array ($my_course['user_course_cat'], $result);
        }
    
        return $output;
    }
    
    /**
     *
     
     * @param	string	source course code
     * @param 	int		source session id
     * @param	string	destination course code
     * @param 	int		destination session id
     * @return bool	
     */
    function copy_course($source_course_code, $source_session_id, $destination_course_code, $destination_session_id) {
        require_once api_get_path(SYS_CODE_PATH).'coursecopy/classes/CourseBuilder.class.php';
        require_once api_get_path(SYS_CODE_PATH).'coursecopy/classes/CourseRestorer.class.php';
        require_once api_get_path(SYS_CODE_PATH).'coursecopy/classes/CourseSelectForm.class.php';

        $course_info = api_get_course_info($source_course_code);
        
        if (!empty($course_info)) {
            $cb = new CourseBuilder('', $course_info);
		    $course = $cb->build($source_session_id, $source_course_code, true);		    		
    		$course_restorer = new CourseRestorer($course);
    		$course_restorer->restore($destination_course_code, $destination_session_id, true, true);
    		return true;		
        }
        return false;
    }
    
    
    /**
     * A simpler version of the copy_course, the function creates an empty course
     * 
     * @param	string	course title
     * @param	string	source course code
     * @param 	int		source session id
     * @return 	array
     */
    function copy_course_simple($new_title, $source_course_code, $source_session_id = 0, $destination_session_id = 0) {
        $source_course_info = api_get_course_info($source_course_code);
        if (!empty($source_course_info)) {
            $new_course_code = self::generate_nice_next_course_code($source_course_code); 
            if ($new_course_code) {
                $new_course_info = self::create_course($new_title, $new_course_code, false);
                if (!empty($new_course_info['code'])) {                    
                    $result = self::copy_course($source_course_code, $source_session_id, $new_course_info['code'], $destination_session_id);
                    if ($result) {
                        return $new_course_info;
                    }                   
                }
            }
        }
        return false;
    }
    
    
    
    /**
     * Creates a new course code based in given code
     * 
     * @param string	wanted code
     * <code>	$wanted_code = 'curse' if there are in the DB codes like curse1 curse2 the function will return: course3</code>
     * if the course code doest not exist in the DB the same course code will be returned
     * @return string	wanted unused code
     */
	function generate_nice_next_course_code($wanted_code) {
        require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
        $course_code_ok = !self::course_code_exists($wanted_code);
        if (!$course_code_ok) {           
           $wanted_code = generate_course_code($wanted_code);
           $table = Database::get_main_table(TABLE_MAIN_COURSE);
           $wanted_code = Database::escape_string($wanted_code);
           $sql = "SELECT count(*) as count FROM $table WHERE code LIKE '$wanted_code%'";           
           $result = Database::query($sql);
           if (Database::num_rows($result) > 0 ) {
		       $row = Database::fetch_array($result);
		       $count = $row['count'] + 1;
		       $wanted_code = $wanted_code.'_'.$count;
		       $result = api_get_course_info($wanted_code);
		       if (empty($result)) {
		           return $wanted_code;
		       }
           }
           return false;     
        }     
        return $wanted_code;
    }
        
    /**
     * Creates a course very fast (the function doesn't need a lot of parameters)
     * 
     * @param	string	course title
     * @param	bool	add example content or not
     * @param	mixed	false if the course was not created, array with the course info
     * 
		Coded found in create_course/add_course.php
		
     */
    function create_course($title, $wanted_code  = '', $exemplary_content = false) {
        require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
        global $_configuration, $firstExpirationDelay;
        if (empty($title)) {
            return false;
        }        
        if (empty($wanted_code)) {
            $wanted_code = $title;    
            // Check whether the requested course code has already been occupied.       
            $dbnamelength = strlen($_configuration['db_prefix']);
            // Ensure the database prefix + database name do not get over 40 characters.
            $maxlength = 40 - $dbnamelength;
            $wanted_code = generate_course_code(api_substr($title, 0, $maxlength));            
        }
 
        // Create the course immediately
        $keys = define_course_keys($wanted_code, '', $_configuration['db_prefix']);

        if (count($keys)) {
            $visual_code = $keys['currentCourseCode'];
            $code        = $keys['currentCourseId'];
            $db_name     = $keys['currentCourseDbName'];
            $directory   = $keys['currentCourseRepository'];
                
            $code_info   = api_get_course_info($code);
            if (empty($code_info)) {            
                $expiration_date = time() + $firstExpirationDelay;
                prepare_course_repository($directory, $code);
                update_Db_course($db_name);
                $pictures_array = fill_course_repository($directory,  $exemplary_content);
                fill_Db_course($db_name, $directory, $course_language, $pictures_array, $exemplary_content);
                $course_language = api_get_setting('platformLanguage');
                $result = register_course($code, $visual_code, $directory, $db_name, '', '', $title, $course_language, api_get_user_id(), $expiration_date);
                $course_info = api_get_course_info($code);
                if (!empty($course_info)) {
                    return $course_info;    
                }
            }
        }
        return false;            
    } 

    
} //end class CourseManager
