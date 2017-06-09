<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;

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

        $extraFieldType = ExtraField::COURSE_FIELD_TYPE;
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
                $special_course_list[] = '"'.$result_row['item_id'].'"';
            }
        }
        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.id NOT IN ('.implode(',', $special_course_list).')';
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
                    course.category_code cat,
                    course.directory dir,
                    course_rel_user.status status,
                    course_rel_user.sort sort,
                    course_rel_user.user_course_cat user_course_cat
                FROM $TABLECOURS course, $TABLECOURSUSER  course_rel_user
                WHERE
                    course.id = course_rel_user.c_id AND
                    course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH." AND
                    course_rel_user.user_id = '" . $user_id."' $without_special_courses
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
                'category' => $row['cat'],
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
        return CourseManager::get_user_course_categories(api_get_user_id());
    }

    /**
     * This function get all the courses in the particular user category;
     * @return string The name of the user defined course category
     */
    public function get_courses_in_category()
    {
        $user_id = api_get_user_id();

        // table definitions
        $TABLECOURS = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_COURSE_FIELD = Database::get_main_table(TABLE_EXTRA_FIELD);
        $TABLE_COURSE_FIELD_VALUE = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $extraFieldType = ExtraField::COURSE_FIELD_TYPE;

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
                $special_course_list[] = '"'.$result_row['item_id'].'"';
            }
        }

        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.id NOT IN ('.implode(',', $special_course_list).')';
        }

        $sql = "SELECT
                    course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
                    course.title title, course.tutor_name tutor, course.directory, course_rel_user.status status,
                    course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                FROM $TABLECOURS course,
                $TABLECOURSUSER  course_rel_user
                WHERE
                    course.id = course_rel_user.c_id AND
                    course_rel_user.user_id = '".$user_id."' AND
                    course_rel_user.relation_type <> " . COURSE_RELATION_TYPE_RRHH."
                    $without_special_courses
                ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";
        $result = Database::query($sql);
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
                    user_course_cat='".$newcategory."',
                    sort='" . ($max_sort_value + 1)."'
                WHERE
                    c_id ='" . $courseId."' AND
                    user_id='" . $current_user."' AND
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
        $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $current_user_id = api_get_user_id();
        $all_user_courses = $this->get_courses_of_user($current_user_id);

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

        $result = false;
        if (count($target_course) > 0 && count($source_course) > 0) {
            $courseInfo = api_get_course_info($source_course['code']);
            $courseId = $courseInfo['real_id'];

            $targetCourseInfo = api_get_course_info($target_course['code']);
            $targetCourseId = $targetCourseInfo['real_id'];

            $sql = "UPDATE $table
                    SET sort='".$target_course['sort']."'
                    WHERE
                        c_id = '" . $courseId."' AND
                        user_id = '" . $current_user_id."' AND
                        relation_type<>" . COURSE_RELATION_TYPE_RRHH;

            $result1 = Database::query($sql);

            $sql = "UPDATE $table SET sort='".$source_course['sort']."'
                    WHERE
                        c_id ='" . $targetCourseId."' AND
                        user_id='" . $current_user_id."' AND
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
     * @param string    $direction Direction up/down
     * @param string    $category2move Category id
     * @return bool     True If it success
     */
    public function move_category($direction, $category2move)
    {
        $userId = api_get_user_id();
        $userCategories = $this->get_user_course_categories();
        $categories = array_values($userCategories);

        $previous = null;
        $target_category = [];
        foreach ($categories as $key => $category) {
            $category_id = $category['id'];
            if ($category2move == $category_id) {
                // source_course is the course where we clicked the up or down icon
                $source_category = $userCategories[$category2move];
                // target_course is the course before/after the source_course (depending on the up/down icon)
                if ($direction == 'up') {
                    if (isset($categories[$key - 1])) {
                        $target_category = $userCategories[$categories[$key - 1]['id']];
                    }
                } else {
                    if (isset($categories[$key + 1])) {
                        $target_category = $userCategories[$categories[$key + 1]['id']];
                    }
                }
            }
        }

        $result = false;
        if (count($target_category) > 0 && count($source_category) > 0) {
            $table = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
            $sql = "UPDATE $table SET 
                    sort = '".Database::escape_string($target_category['sort'])."'
                    WHERE id='" . intval($source_category['id'])."' AND user_id='".$userId."'";
            $resultFirst = Database::query($sql);
            $sql = "UPDATE $table SET 
                    sort = '".Database::escape_string($source_category['sort'])."'
                    WHERE id='" . intval($target_category['id'])."' AND user_id='".$userId."'";
            $resultSecond = Database::query($sql);
            if (Database::affected_rows($resultFirst) && Database::affected_rows($resultSecond)) {
                $result = true;
            }
        }

        return $result;
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
                SET title='".api_htmlentities($title, ENT_QUOTES, api_get_system_encoding())."'
                WHERE id='" . $category_id."'";
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
        $sql = "DELETE FROM $tucc
                WHERE 
                    id='".$category_id."' AND 
                    user_id='" . $current_user_id."'";
        $resultQuery = Database::query($sql);
        if (Database::affected_rows($resultQuery)) {
            $result = true;
        }
        $sql = "UPDATE $TABLECOURSUSER
                SET user_course_cat='0'
                WHERE
                    user_course_cat='".$category_id."' AND
                    user_id='" . $current_user_id."' AND
                    relation_type<>" . COURSE_RELATION_TYPE_RRHH." ";
        Database::query($sql);

        return $result;
    }

    /**
     * Search the courses database for a course that matches the search term.
     * The search is done on the code, title and tutor field of the course table.
     * @param string $search_term The string that the user submitted, what we are looking for
     * @param array $limit
     * @param boolean $justVisible search only on visible courses in the catalogue
     * @return array An array containing a list of all the courses matching the the search term.
     */
    public function search_courses($search_term, $limit, $justVisible = false)
    {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $limitFilter = CourseCategory::getLimitFilterFromArray($limit);

        // get course list auto-register
        $sql = "SELECT item_id
                FROM $extraFieldValuesTable tcfv
                INNER JOIN $extraFieldTable tcf ON tcfv.field_id =  tcf.id
                WHERE
                    tcf.variable = 'special_course' AND
                    tcfv.value = 1 ";

        $special_course_result = Database::query($sql);
        if (Database::num_rows($special_course_result) > 0) {
            $special_course_list = array();
            while ($result_row = Database::fetch_array($special_course_result)) {
                $special_course_list[] = '"'.$result_row['item_id'].'"';
            }
        }
        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.code NOT IN ('.implode(',', $special_course_list).')';
        }

        $visibilityCondition = $justVisible ? CourseManager::getCourseVisibilitySQLCondition('course', true) : '';

        $search_term_safe = Database::escape_string($search_term);
        $sql_find = "SELECT * FROM $courseTable
                    WHERE (
                            code LIKE '%".$search_term_safe."%' OR
                            title LIKE '%" . $search_term_safe."%' OR
                            tutor_name LIKE '%" . $search_term_safe."%'
                        )
                        $without_special_courses
                        $visibilityCondition
                    ORDER BY title, visual_code ASC
                    $limitFilter
                    ";

        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sql_find = "SELECT *
                            FROM $courseTable as course
                            INNER JOIN $tbl_url_rel_course as url_rel_course
                            ON (url_rel_course.c_id = course.id)
                            WHERE
                                access_url_id = $url_access_id AND (
                                    code LIKE '%".$search_term_safe."%' OR
                                    title LIKE '%" . $search_term_safe."%' OR
                                    tutor_name LIKE '%" . $search_term_safe."%'
                                )
                                $without_special_courses
                                $visibilityCondition
                            ORDER BY title, visual_code ASC
                            $limitFilter
                            ";
            }
        }
        $result_find = Database::query($sql_find);
        $courses = array();
        while ($row = Database::fetch_array($result_find)) {
            $row['registration_code'] = !empty($row['registration_code']);
            $count_users = count(CourseManager::get_user_list_from_course_code($row['code']));
            $count_connections_last_month = Tracking::get_course_connections_count(
                    $row['id'], 0, api_get_utc_datetime(time() - (30 * 86400))
            );

            $point_info = CourseManager::get_course_ranking($row['id'], 0);

            $courses[] = array(
                'real_id' => $row['id'],
                'point_info' => $point_info,
                'code' => $row['code'],
                'directory' => $row['directory'],
                'visual_code' => $row['visual_code'],
                'title' => $row['title'],
                'tutor' => $row['tutor_name'],
                'subscribe' => $row['subscribe'],
                'unsubscribe' => $row['unsubscribe'],
                'registration_code' => $row['registration_code'],
                'creation_date' => $row['creation_date'],
                'visibility' => $row['visibility'],
                'count_users' => $count_users,
                'count_connections' => $count_connections_last_month
            );
        }
        return $courses;
    }

    /**
     * unsubscribe the user from a given course
     * @param   string  $course_code
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
                    user_id='".$current_user_id."' AND
                    c_id ='" . $courseId."' AND
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
        $sql = "SELECT sort FROM $tucc 
                WHERE user_id='".$current_user_id."' 
                ORDER BY sort DESC";
        $rs_sort = Database::query($sql);
        $maxsort = Database::fetch_array($rs_sort);
        $nextsort = $maxsort['sort'] + 1;

        // step 2: we check if there is already a category with this name, if not we store it, else we give an error.
        $sql = "SELECT * FROM $tucc 
                WHERE 
                    user_id='".$current_user_id."' AND 
                    title='" . $category_title."'
                ORDER BY sort DESC";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) == 0) {
            $sql = "INSERT INTO $tucc (user_id, title,sort)
                    VALUES ('".$current_user_id."', '".api_htmlentities($category_title, ENT_QUOTES, api_get_system_encoding())."', '".$nextsort."')";
            $resultQuery = Database::query($sql);
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
        return CourseCategory::countCoursesInCategory($categoryCode, $searchTerm);
    }

    /**
     * get the browsing of the course categories (faculties)
     * @return array    array containing a list with all the categories and subcategories(if needed)
     */
    public function browse_course_categories()
    {
        return CourseCategory::browseCourseCategories();
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
        return CourseCategory::browseCoursesInCategory($categoryCode, $randomValue, $limit);
    }

    /**
     * Subscribe the user to a given course
     * @param string $course_code Course code
     * @return string  Message about results
     */
    public function subscribe_user($course_code)
    {
        $user_id = api_get_user_id();
        $all_course_information = CourseManager::get_course_information($course_code);

        if (
            $all_course_information['registration_code'] == '' ||
            (
                isset($_POST['course_registration_code']) &&
                $_POST['course_registration_code'] == $all_course_information['registration_code']
            )
        ) {
            if (api_is_platform_admin()) {
                $status_user_in_new_course = COURSEMANAGER;
            } else {
                $status_user_in_new_course = null;
            }
            if (CourseManager::add_user_to_course($user_id, $course_code, $status_user_in_new_course)) {
                $send = api_get_course_setting('email_alert_to_teacher_on_new_user_in_course', $course_code);
                if ($send == 1) {
                    CourseManager::email_to_tutor(
                        $user_id,
                        $all_course_information['real_id'],
                        $send_to_tutor_also = false
                    );
                } else if ($send == 2) {
                    CourseManager::email_to_tutor(
                        $user_id,
                        $all_course_information['real_id'],
                        $send_to_tutor_also = true
                    );
                }
                $url = Display::url($all_course_information['title'], api_get_course_url($course_code));
                $message = sprintf(get_lang('EnrollToCourseXSuccessful'), $url);
            } else {
                $message = get_lang('ErrorContactPlatformAdmin');
            }
            return array('message' => $message);
        } else {
            if (isset($_POST['course_registration_code']) &&
                $_POST['course_registration_code'] != $all_course_information['registration_code']
            ) {
                return false;
            }
            $message = get_lang('CourseRequiresPassword').'<br />';
            $message .= $all_course_information['title'].' ('.$all_course_information['visual_code'].') ';

            $action  = api_get_path(WEB_CODE_PATH)."auth/courses.php?action=subscribe_user_with_password&sec_token=".$_SESSION['sec_token'];
            $form = new FormValidator('subscribe_user_with_password', 'post', $action);
            $form->addElement('hidden', 'sec_token', $_SESSION['sec_token']);
            $form->addElement('hidden', 'subscribe_user_with_password', $all_course_information['code']);
            $form->addElement('text', 'course_registration_code');
            $form->addButton('submit', get_lang('SubmitRegistrationCode'));
            $content = $form->returnForm();

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
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();
        $urlId = api_get_current_access_url_id();

        $query = $qb->select('s')->from('ChamiloCoreBundle:Session', 's');

        $qb->innerJoin(
            'ChamiloCoreBundle:AccessUrlRelSession',
            'ars',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            's = ars.sessionId'
        );

        if (!empty($limit)) {
            $query->setFirstResult($limit['start'])
                ->setMaxResults($limit['length']);
        }

        $query
            ->where($qb->expr()->gt('s.nbrCourses', 0))
            ->andWhere($qb->expr()->eq('ars.accessUrlId', $urlId))
        ;

        if (!is_null($date)) {
            $query
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->between(':date', 's.accessStartDate', 's.accessEndDate'),
                        $qb->expr()->isNull('s.accessEndDate'),
                        $qb->expr()->andX(
                            $qb->expr()->isNull('s.accessStartDate'),
                            $qb->expr()->isNotNull('s.accessEndDate'),
                            $qb->expr()->gt('s.accessEndDate', ':date')
                        )
                    )
                )
                ->setParameter('date', $date);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Return a COUNT from Session table
     * @param string $date in Y-m-d format
     * @return int
     */
    public function countSessions($date = null)
    {
        $count = 0;
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $date = Database::escape_string($date);
        $urlId = api_get_current_access_url_id();
        $dateFilter = '';
        if (!empty($date)) {
            $dateFilter = <<<SQL
                AND ('$date' BETWEEN s.access_start_date AND s.access_end_date)
                OR (s.access_end_date IS NULL)
                OR (s.access_start_date IS NULL AND
                s.access_end_date IS NOT NULL AND s.access_end_date > '$date')
SQL;
        }
        $sql = "SELECT COUNT(*) 
                FROM $sessionTable s
                INNER JOIN $url u
                ON (s.id = u.session_id)
                WHERE u.access_url_id = $urlId $dateFilter";
        $res = Database::query($sql);
        if ($res !== false && Database::num_rows($res) > 0) {
            $count = current(Database::fetch_row($res));
        }

        return $count;
    }

    /**
     * Search sessions by the tags in their courses
     * @param string $termTag Term for search in tags
     * @param array $limit Limit info
     * @return array The sessions
     */
    public function browseSessionsByTags($termTag, array $limit)
    {
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();

        $sessions = $qb->select('s')
            ->distinct(true)
            ->from('ChamiloCoreBundle:Session', 's')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'src',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                's.id = src.session'
            )
            ->innerJoin(
                'ChamiloCoreBundle:ExtraFieldRelTag',
                'frt',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'src.course = frt.itemId'
            )
            ->innerJoin(
                'ChamiloCoreBundle:Tag',
                't',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'frt.tagId = t.id'
            )
            ->innerJoin(
                'ChamiloCoreBundle:ExtraField',
                'f',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'frt.fieldId = f.id'
            )
            ->where(
                $qb->expr()->like('t.tag', ":tag")
            )
            ->andWhere(
                $qb->expr()->eq('f.extraFieldType', ExtraField::COURSE_FIELD_TYPE)
            )
            ->setFirstResult($limit['start'])
            ->setMaxResults($limit['length'])
            ->setParameter('tag', "$termTag%")
            ->getQuery()
            ->getResult();

        $sessionsToBrowse = [];
        foreach ($sessions as $session) {
            if ($session->getNbrCourses() === 0) {
                continue;
            }
            $sessionsToBrowse[] = $session;
        }

        return $sessionsToBrowse;
    }

    /**
     * Search sessions by searched term by session name
     * @param string $queryTerm Term for search
     * @param array $limit Limit info
     * @return array The sessions
     */
    public function browseSessionsBySearch($queryTerm, array $limit)
    {
        $sessionsToBrowse = [];

        $criteria = Doctrine\Common\Collections\Criteria::create()
            ->where(
                Doctrine\Common\Collections\Criteria::expr()->contains('name', $queryTerm)
            )
            ->setFirstResult($limit['start'])
            ->setMaxResults($limit['length']);

        $sessions = Database::getManager()
                ->getRepository('ChamiloCoreBundle:Session')
                ->matching($criteria);

        foreach ($sessions as $session) {
            if ($session->getNbrCourses() === 0) {
                continue;
            }

            $sessionsToBrowse[] = $session;
        }

        return $sessionsToBrowse;
    }
}
