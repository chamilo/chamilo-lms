<?php
/* For licensing terms, see /license.txt */

/**
 * Class Auth
 * Auth can be used to instantiate objects or as a library to manage courses
 * This file contains a class used like library provides functions for auth tool.
 * It's also used like model to courses_controller (MVC pattern)
 * @author Christian Fasanando <christian1827@gmail.com>
 *
 * @package chamilo.auth
 */
class Auth
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * retrieves all the courses that the user has already subscribed to
     * @param   int $user_id
     * @return  array an array containing all the information of the courses of the given user
     */
    public function get_courses_of_user($user_id)
    {
        $TABLECOURS = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_COURSE_FIELD = Database::get_main_table(TABLE_EXTRA_FIELD);
        $TABLE_COURSE_FIELD_VALUE = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $extraFieldType = \Chamilo\CoreBundle\Entity\ExtraField::COURSE_FIELD_TYPE;
        // get course list auto-register
        $sql = "SELECT item_id FROM $TABLE_COURSE_FIELD_VALUE tcfv
                INNER JOIN $TABLE_COURSE_FIELD tcf
                ON tcfv.field_id =  tcf.id
                WHERE
                    tcf.extra_field_type = $extraFieldType AND
                    tcf.variable = 'special_course' AND
                    tcfv.value = 1
                ";

        $result = Database::query($sql);
        $special_course_list = array();
        if (Database::num_rows($result) > 0) {
            while ($result_row = Database::fetch_array($result)) {
                $special_course_list[] = '"' . $result_row['item_id'] . '"';
            }
        }
        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.id NOT IN (' . implode(',', $special_course_list) . ')';
        }

        // Secondly we select the courses that are in a category (user_course_cat<>0) and sort these according to the sort of the category
        $user_id = intval($user_id);
        $sql = "SELECT
                    course.code k,
                    course.visual_code vc,
                    course.subscribe subscr,
                    course.unsubscribe unsubscr,
                    course.title i,
                    course.tutor_name t,
                    course.directory dir,
                    course_rel_user.status status,
                    course_rel_user.sort sort,
                    course_rel_user.user_course_cat user_course_cat
                FROM $TABLECOURS course, $TABLECOURSUSER  course_rel_user
                WHERE
                    course.id = course_rel_user.c_id AND
                    course_rel_user.relation_type<>" . COURSE_RELATION_TYPE_RRHH . " AND
                    course_rel_user.user_id = '" . $user_id . "' $without_special_courses
                ORDER BY course_rel_user.sort ASC";
        $result = Database::query($sql);
        $courses = array();
        while ($row = Database::fetch_array($result)) {
            //we only need the database name of the course
            $courses[] = array(
                'code' => $row['k'],
                'visual_code' => $row['vc'],
                'title' => $row['i'],
                'directory' => $row['dir'],
                'status' => $row['status'],
                'tutor' => $row['t'],
                'subscribe' => $row['subscr'],
                'unsubscribe' => $row['unsubscr'],
                'sort' => $row['sort'],
                'user_course_category' => $row['user_course_cat']
            );
        }

        return $courses;
    }

    /**
     * retrieves the user defined course categories
     * @return array containing all the IDs of the user defined courses categories, sorted by the "sort" field
     */
    public function get_user_course_categories()
    {
        $user_id = api_get_user_id();
        $table_category = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT * FROM " . $table_category . " WHERE user_id=$user_id ORDER BY sort ASC";
        $result = Database::query($sql);
        $output = array();
        while ($row = Database::fetch_array($result)) {
            $output[] = $row;
        }

        return $output;
    }

    /**
     * This function get all the courses in the particular user category;
     * @return string: the name of the user defined course category
     */
    public function get_courses_in_category()
    {
        $user_id = api_get_user_id();

        // table definitions
        $TABLECOURS = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_COURSE_FIELD = Database::get_main_table(TABLE_EXTRA_FIELD);
        $TABLE_COURSE_FIELD_VALUE = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $extraFieldType = \Chamilo\CoreBundle\Entity\ExtraField::COURSE_FIELD_TYPE;

        // get course list auto-register
        $sql = "SELECT item_id
                FROM $TABLE_COURSE_FIELD_VALUE tcfv
                INNER JOIN $TABLE_COURSE_FIELD tcf
                ON tcfv.field_id =  tcf.id
                WHERE
                    tcf.extra_field_type = $extraFieldType AND
                    tcf.variable = 'special_course' AND
                    tcfv.value = 1 ";

        $result = Database::query($sql);
        $special_course_list = array();
        if (Database::num_rows($result) > 0) {
            while ($result_row = Database::fetch_array($result)) {
                $special_course_list[] = '"' . $result_row['item_id'] . '"';
            }
        }

        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.id NOT IN (' . implode(',', $special_course_list) . ')';
        }

        $sql = "SELECT
                    course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
                    course.title title, course.tutor_name tutor, course.directory, course_rel_user.status status,
                    course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                FROM $TABLECOURS course,
                $TABLECOURSUSER  course_rel_user
                WHERE
                    course.id = course_rel_user.c_id AND
                    course_rel_user.user_id = '" . $user_id . "' AND
                    course_rel_user.relation_type <> " . COURSE_RELATION_TYPE_RRHH . "
                    $without_special_courses
                ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";
        $result = Database::query($sql);
        $number_of_courses = Database::num_rows($result);
        $data = array();
        while ($course = Database::fetch_array($result)) {
            $data[$course['user_course_cat']][] = $course;
        }

        return $data;
    }

    /**
     * stores  the changes in a course category
     * (moving a course to a different course category)
     * @param  int    $courseId
     * @param  int       Category id
     * @return bool      True if it success
     */
    public function updateCourseCategory($courseId, $newcategory)
    {
        $courseId = intval($courseId);
        $newcategory = intval($newcategory);
        $current_user = api_get_user_id();

        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $max_sort_value = api_max_sort_value($newcategory, $current_user);
        $sql = "UPDATE $TABLECOURSUSER SET
                    user_course_cat='" . $newcategory . "',
                    sort='" . ($max_sort_value + 1) . "'
                WHERE
                    c_id ='" . $courseId . "' AND
                    user_id='" . $current_user . "' AND
                    relation_type<>" . COURSE_RELATION_TYPE_RRHH;
        $resultQuery = Database::query($sql);

        $result = false;
        if (Database::affected_rows($resultQuery)) {
            $result = true;
        }

        return $result;
    }

    /**
     * moves the course one place up or down
     * @param   string    Direction (up/down)
     * @param   string    Course code
     * @param   int       Category id
     * @return  bool      True if it success
     */
    public function move_course($direction, $course2move, $category)
    {
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

        $target_course = array();
        foreach ($user_courses as $count => $course) {
            if ($course2move == $course['code']) {
                // source_course is the course where we clicked the up or down icon
                $source_course = $course;
                // target_course is the course before/after the source_course (depending on the up/down icon)
                if ($direction == 'up') {
                    $target_course = $user_courses[$count - 1];
                } else {
                    $target_course = $user_courses[$count + 1];
                }
                break;
            }
        }

        if (count($target_course) > 0 && count($source_course) > 0) {
            $courseInfo = api_get_course_info($source_course['code']);
            $courseId = $courseInfo['real_id'];

            $sql = "UPDATE $TABLECOURSUSER
                    SET sort='" . $target_course['sort'] . "'
                    WHERE
                        c_id = '" . $courseId . "' AND
                        user_id = '" . $current_user_id . "' AND
                        relation_type<>" . COURSE_RELATION_TYPE_RRHH;
            $result1 = Database::query($sql);

            $sql = "UPDATE $TABLECOURSUSER SET sort='" . $source_course['sort'] . "'
                    WHERE
                        c_id ='" . $courseId . "' AND
                        user_id='" . $current_user_id . "' AND
                        relation_type<>" . COURSE_RELATION_TYPE_RRHH;
            $result2 = Database::query($sql);

            if (Database::affected_rows($result1) && Database::affected_rows($result2)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Moves the course one place up or down
     * @param string    Direction up/down
     * @param string    Category id
     * @return bool     True If it success
     */
    public function move_category($direction, $category2move)
    {
        // the database definition of the table that stores the user defined course categories
        $table_user_defined_category = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);

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
            $sql_update1 = "UPDATE $table_user_defined_category SET sort='" . Database::escape_string($target_category['sort']) . "'
                            WHERE id='" . intval($source_category['id']) . "' AND user_id='" . $current_user_id . "'";
            $sql_update2 = "UPDATE $table_user_defined_category SET sort='" . Database::escape_string($source_category['sort']) . "'
                            WHERE id='" . intval($target_category['id']) . "' AND user_id='" . $current_user_id . "'";

            $result1 = Database::query($sql_update2);
            $result2 = Database::query($sql_update1);
            if (Database::affected_rows($result1) && Database::affected_rows($result2)) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Retrieves the user defined course categories and all the info that goes with it
     * @return array containing all the info of the user defined courses categories with the id as key of the array
     */
    public function get_user_course_categories_info()
    {
        $current_user_id = api_get_user_id();
        $table_category = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT * FROM " . $table_category . "
                WHERE user_id='" . $current_user_id . "'
                ORDER BY sort ASC";
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
    public function store_edit_course_category($title, $category_id)
    {
        // protect data
        $title = Database::escape_string($title);
        $category_id = intval($category_id);
        $result = false;
        $tucc = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "UPDATE $tucc
                SET title='" . api_htmlentities($title, ENT_QUOTES, api_get_system_encoding()) . "'
                WHERE id='" . $category_id . "'";
        $resultQuery = Database::query($sql);
        if (Database::affected_rows($resultQuery)) {
            $result = true;
        }
        return $result;
    }

    /**
     * deletes a course category and moves all the courses that were in this category to main category
     * @param   int     Category id
     * @return  bool    True if it success
     */
    public function delete_course_category($category_id)
    {
        $current_user_id = api_get_user_id();
        $tucc = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $category_id = intval($category_id);
        $result = false;
        $sql_delete = "DELETE FROM $tucc
                       WHERE id='" . $category_id . "' and user_id='" . $current_user_id . "'";
        $resultQuery = Database::query($sql_delete);
        if (Database::affected_rows($resultQuery)) {
            $result = true;
        }
        $sql = "UPDATE $TABLECOURSUSER
                SET user_course_cat='0'
                WHERE
                    user_course_cat='" . $category_id . "' AND
                    user_id='" . $current_user_id . "' AND
                    relation_type<>" . COURSE_RELATION_TYPE_RRHH . " ";
        Database::query($sql);

        return $result;
    }

    /**
     * unsubscribe the user from a given course
     * @param   string  Course code
     * @return  bool    True if it success
     */
    public function remove_user_from_course($course_code)
    {
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        // protect variables
        $current_user_id = api_get_user_id();
        $course_code = Database::escape_string($course_code);
        $result = true;

        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        // we check (once again) if the user is not course administrator
        // because the course administrator cannot unsubscribe himself
        // (s)he can only delete the course
        $sql = "SELECT * FROM $tbl_course_user
                WHERE
                    user_id='" . $current_user_id . "' AND
                    c_id ='" . $courseId . "' AND
                    status='1' ";
        $result_check = Database::query($sql);
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
    public function store_course_category($category_title)
    {
        $tucc = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);

        // protect data
        $current_user_id = api_get_user_id();
        $category_title = Database::escape_string($category_title);
        $result = false;

        // step 1: we determine the max value of the user defined course categories
        $sql = "SELECT sort FROM $tucc WHERE user_id='" . $current_user_id . "' ORDER BY sort DESC";
        $rs_sort = Database::query($sql);
        $maxsort = Database::fetch_array($rs_sort);
        $nextsort = $maxsort['sort'] + 1;

        // step 2: we check if there is already a category with this name, if not we store it, else we give an error.
        $sql = "SELECT * FROM $tucc WHERE user_id='" . $current_user_id . "' AND title='" . $category_title . "'ORDER BY sort DESC";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) == 0) {
            $sql_insert = "INSERT INTO $tucc (user_id, title,sort)
                           VALUES ('" . $current_user_id . "', '" . api_htmlentities($category_title, ENT_QUOTES, api_get_system_encoding()) . "', '" . $nextsort . "')";
            $resultQuery = Database::query($sql_insert);
            if (Database::affected_rows($resultQuery)) {
                $result = true;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Counts the number of courses in a given course category
     * @param   string $categoryCode Category code
     * @param $searchTerm
     * @return  int     Count of courses
     */
    public function count_courses_in_category($categoryCode, $searchTerm = '')
    {
        return countCoursesInCategory($categoryCode, $searchTerm);
    }

    /**
     * get the browsing of the course categories (faculties)
     * @return array    array containing a list with all the categories and subcategories(if needed)
     */
    public function browse_course_categories()
    {
        return browseCourseCategories();
    }

    /**
     * Display all the courses in the given course category. I could have used a parameter here
     * @param string $categoryCode Category code
     * @param int $randomValue
     * @param array $limit will be used if $random_value is not set.
     * This array should contains 'start' and 'length' keys
     * @return array Courses data
     */
    public function browse_courses_in_category($categoryCode, $randomValue = null, $limit = array())
    {
        return browseCoursesInCategory($categoryCode, $randomValue, $limit);
    }

    /**
     * Subscribe the user to a given course
     * @param string Course code
     * @return string  Message about results
     */
    public function subscribe_user($course_code)
    {
        $user_id = api_get_user_id();
        $all_course_information = CourseManager::get_course_information($course_code);

        if ($all_course_information['registration_code'] == '' || $_POST['course_registration_code'] == $all_course_information['registration_code']) {
            if (api_is_platform_admin()) {
                $status_user_in_new_course = COURSEMANAGER;
            } else {
                $status_user_in_new_course = null;
            }
            if (CourseManager::add_user_to_course($user_id, $course_code, $status_user_in_new_course)) {
                $send = api_get_course_setting('email_alert_to_teacher_on_new_user_in_course', $course_code);
                if ($send == 1) {
                    CourseManager::email_to_tutor($user_id, $course_code, $send_to_tutor_also = false);
                } else if ($send == 2) {
                    CourseManager::email_to_tutor($user_id, $course_code, $send_to_tutor_also = true);
                }
                $url = Display::url($all_course_information['title'], api_get_course_url($course_code));
                $message = sprintf(get_lang('EnrollToCourseXSuccessful'), $url);
            } else {
                $message = get_lang('ErrorContactPlatformAdmin');
            }
            return array('message' => $message);
        } else {
            if (isset($_POST['course_registration_code']) && $_POST['course_registration_code'] != $all_course_information['registration_code']) {
                return false;
            }
            $message = get_lang('CourseRequiresPassword') . '<br />';
            $message .= $all_course_information['title'].' ('.$all_course_information['visual_code'].') ';

            $action  = api_get_path(WEB_CODE_PATH) . "auth/courses.php?action=subscribe_user_with_password&sec_token=" . $_SESSION['sec_token'];
            $form = new FormValidator('subscribe_user_with_password', 'post', $action);
            $form->addElement('hidden', 'sec_token', $_SESSION['sec_token']);
            $form->addElement('hidden', 'subscribe_user_with_password', $all_course_information['code']);
            $form->addElement('text', 'course_registration_code');
            $form->addElement('button', 'submit', get_lang('SubmitRegistrationCode'));
            $content = $form->return_form();
            return array('message' => $message, 'content' => $content);
        }
    }

    /**
     * List the sessions
     * @param string $date (optional) The date of sessions
     * @param array $limit
     * @return array The session list
     */
    public function browseSessions($date = null, $limit = array())
    {
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $sessionsToBrowse = array();
        $userId = api_get_user_id();
        $limitFilter = getLimitFilterFromArray($limit);

        $sql = "SELECT s.id, s.name, s.nbr_courses, s.nbr_users, s.date_start, s.date_end, u.lastname, u.firstname, u.username, description, show_description "
            . "FROM $sessionTable AS s "
            . "INNER JOIN $userTable AS u "
            . "ON s.id_coach = u.user_id "
            . "WHERE 1 = 1 ";

        if (!is_null($date)) {
            $date = Database::escape_string($date);

            $sql .= "AND ('$date' BETWEEN s.date_start AND s.date_end) "
                . "OR (s.date_end = '0000-00-00') "
                . "OR (s.date_start = '0000-00-00' AND s.date_end != '0000-00-00' AND s.date_end > '$date')";
        }

        // Add limit filter to do pagination
        $sql .= $limitFilter;

        $sessionResult = Database::query($sql);

        if ($sessionResult != false) {
            while ($session = Database::fetch_assoc($sessionResult)) {
                if ($session['nbr_courses'] > 0) {
                    $session['coach_name'] = api_get_person_name($session['firstname'], $session['lastname']);
                    $session['coach_name'] .= " ({$session['username']})";
                    $session['is_subscribed'] = SessionManager::isUserSubscribedAsStudent($session['id'], $userId);

                    $sessionsToBrowse[] = $session;
                }
            }
        }

        return $sessionsToBrowse;
    }

    /**
     * Return a COUNT from Session table
     * @param string $date in Y-m-d format
     * @return int
     */
    function countSessions($date = null)
    {
        $count = 0;
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $date = Database::escape_string($date);
        $dateFilter = '';
        if (!empty($date)) {
            $dateFilter = ' AND ("' . $date . '" BETWEEN s.date_start AND s.date_end) ' .
                'OR (s.date_end = "0000-00-00") ' .
                'OR (s.date_start = "0000-00-00" AND ' .
                's.date_end != "0000-00-00" AND s.date_end > "' . $date . '") ';
        }
        $sql = "SELECT COUNT(*) FROM $sessionTable s WHERE 1 = 1 $dateFilter";
        $res = Database::query($sql);
        if ($res !== false && Database::num_rows($res) > 0) {
            $count = current(Database::fetch_row($res));
        }

        return $count;
    }
}
