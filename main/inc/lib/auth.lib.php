<?php

/* For licensing terms, see /license.txt */

/**
 * Class Auth
 * Auth can be used to instantiate objects or as a library to manage courses
 * This file contains a class used like library provides functions for auth tool.
 * It's also used like model to courses_controller (MVC pattern).
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */
class Auth
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * This function get all the courses in the particular user category.
     *
     * @param bool $hidePrivate
     *
     * @return array
     */
    public function getCoursesInCategory($hidePrivate = true)
    {
        $user_id = api_get_user_id();

        $TABLECOURS = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $avoidCoursesCondition = CoursesAndSessionsCatalog::getAvoidCourseCondition();
        $showCoursesCondition = CoursesAndSessionsCatalog::getCoursesToShowInCatalogueCondition();
        $visibilityCondition = CourseManager::getCourseVisibilitySQLCondition('course', true, $hidePrivate);

        $sql = "SELECT
                    course.id as real_id,
                    course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
                    course.title title, course.tutor_name tutor, course.directory, course_rel_user.status status,
                    course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                FROM $TABLECOURS course,
                $TABLECOURSUSER  course_rel_user
                WHERE
                    course.id = course_rel_user.c_id AND
                    course_rel_user.user_id = '".$user_id."' AND
                    course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                    $avoidCoursesCondition
                    $showCoursesCondition
                    $visibilityCondition
                ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";
        $result = Database::query($sql);
        $data = [];
        while ($course = Database::fetch_array($result)) {
            $data[$course['user_course_cat']][] = $course;
        }

        return $data;
    }

    /**
     * stores  the changes in a course category
     * (moving a course to a different course category).
     *
     * @param int $courseId
     * @param int       Category id
     *
     * @return bool True if it success
     */
    public function updateCourseCategory($courseId, $newcategory)
    {
        $courseId = (int) $courseId;
        $newcategory = (int) $newcategory;
        $current_user = api_get_user_id();

        $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $max_sort_value = api_max_sort_value($newcategory, $current_user);
        $sql = "UPDATE $table SET
                    user_course_cat='".$newcategory."',
                    sort='".($max_sort_value + 1)."'
                WHERE
                    c_id ='".$courseId."' AND
                    user_id='".$current_user."' AND
                    relation_type<>".COURSE_RELATION_TYPE_RRHH;
        $resultQuery = Database::query($sql);

        $result = false;
        if (Database::affected_rows($resultQuery)) {
            $result = true;
        }

        return $result;
    }

    /**
     * moves the course one place up or down.
     *
     * @param string    Direction (up/down)
     * @param string    Course code
     * @param int       Category id
     *
     * @return bool True if it success
     */
    public function move_course($direction, $course2move, $category)
    {
        $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $current_user_id = api_get_user_id();
        $all_user_courses = CourseManager::getCoursesByUserCourseCategory($current_user_id);

        // we need only the courses of the category we are moving in
        $user_courses = [];
        foreach ($all_user_courses as $key => $course) {
            if ($course['user_course_category'] == $category) {
                $user_courses[] = $course;
            }
        }

        $target_course = [];
        foreach ($user_courses as $count => $course) {
            if ($course2move == $course['code']) {
                // source_course is the course where we clicked the up or down icon
                $source_course = $course;
                // target_course is the course before/after the source_course (depending on the up/down icon)
                if ('up' == $direction) {
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
                        c_id = '".$courseId."' AND
                        user_id = '".$current_user_id."' AND
                        relation_type<>".COURSE_RELATION_TYPE_RRHH;

            $result1 = Database::query($sql);

            $sql = "UPDATE $table SET sort='".$source_course['sort']."'
                    WHERE
                        c_id ='".$targetCourseId."' AND
                        user_id='".$current_user_id."' AND
                        relation_type<>".COURSE_RELATION_TYPE_RRHH;

            $result2 = Database::query($sql);

            if (Database::affected_rows($result1) && Database::affected_rows($result2)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Moves the course one place up or down.
     *
     * @param string $direction     Direction up/down
     * @param string $category2move Category id
     *
     * @return bool True If it success
     */
    public function move_category($direction, $category2move)
    {
        $userId = api_get_user_id();
        $userCategories = CourseManager::get_user_course_categories($userId);
        $categories = array_values($userCategories);

        $previous = null;
        $target_category = [];
        foreach ($categories as $key => $category) {
            $category_id = $category['id'];
            if ($category2move == $category_id) {
                // source_course is the course where we clicked the up or down icon
                $source_category = $userCategories[$category2move];
                // target_course is the course before/after the source_course (depending on the up/down icon)
                if ('up' == $direction) {
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
                    WHERE id='".intval($source_category['id'])."' AND user_id='".$userId."'";
            $resultFirst = Database::query($sql);
            $sql = "UPDATE $table SET
                    sort = '".Database::escape_string($source_category['sort'])."'
                    WHERE id='".intval($target_category['id'])."' AND user_id='".$userId."'";
            $resultSecond = Database::query($sql);
            if (Database::affected_rows($resultFirst) && Database::affected_rows($resultSecond)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Updates the user course category in the chamilo_user database.
     *
     * @param string  Category title
     * @param int     Category id
     *
     * @return bool True if it success
     */
    public function store_edit_course_category($title, $category_id)
    {
        $title = Database::escape_string($title);
        $category_id = (int) $category_id;
        $result = false;
        $table = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "UPDATE $table
                SET title='".api_htmlentities($title, ENT_QUOTES, api_get_system_encoding())."'
                WHERE id='".$category_id."'";
        $resultQuery = Database::query($sql);
        if (Database::affected_rows($resultQuery)) {
            $result = true;
        }

        return $result;
    }

    /**
     * deletes a course category and moves all the courses that were in this category to main category.
     *
     * @param int     Category id
     *
     * @return bool True if it success
     */
    public function delete_course_category($category_id)
    {
        $current_user_id = api_get_user_id();
        $tucc = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $category_id = (int) $category_id;
        $result = false;
        $sql = "DELETE FROM $tucc
                WHERE
                    id='".$category_id."' AND
                    user_id='".$current_user_id."'";
        $resultQuery = Database::query($sql);
        if (Database::affected_rows($resultQuery)) {
            $result = true;
        }
        $sql = "UPDATE $TABLECOURSUSER
                SET user_course_cat='0'
                WHERE
                    user_course_cat='".$category_id."' AND
                    user_id='".$current_user_id."' AND
                    relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
        Database::query($sql);

        return $result;
    }

    /**
     * @param int $categoryId
     *
     * @return array|mixed
     */
    public function getUserCourseCategory($categoryId)
    {
        $userId = api_get_user_id();
        $tucc = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $categoryId = (int) $categoryId;

        $sql = "SELECT * FROM $tucc
                WHERE
                    id= $categoryId AND
                    user_id= $userId";
        $resultQuery = Database::query($sql);

        return Database::fetch_array($resultQuery, 'ASSOC');
    }

    /**
     * unsubscribe the user from a given course.
     *
     * @param string $course_code
     *
     * @return bool True if it success
     */
    public function remove_user_from_course($course_code, $sessionId = 0)
    {
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        // protect variables
        $current_user_id = api_get_user_id();
        $course_code = Database::escape_string($course_code);

        $courseInfo = api_get_course_info($course_code);

        if (empty($courseInfo) || empty($current_user_id)) {
            return false;
        }

        // Check if course can be unsubscribe.
        if ('1' !== $courseInfo['unsubscribe']) {
            return false;
        }

        $courseId = $courseInfo['real_id'];

        // we check (once again) if the user is not course administrator
        // because the course administrator cannot unsubscribe himself
        // (s)he can only delete the course
        $sql = "SELECT * FROM $tbl_course_user
                WHERE
                    user_id='".$current_user_id."' AND
                    c_id ='".$courseId."' AND
                    status='1' ";
        $result_check = Database::query($sql);
        $number_of_rows = Database::num_rows($result_check);

        $result = true;
        if ($number_of_rows > 0) {
            $result = false;
        }

        if ($result) {
            CourseManager::unsubscribe_user($current_user_id, $course_code, $sessionId);
        }

        return $result;
    }

    /**
     * stores the user course category in the chamilo_user database.
     *
     * @param string  Category title
     *
     * @return bool True if it success
     */
    public function store_course_category($category_title)
    {
        $table = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);

        // protect data
        $current_user_id = api_get_user_id();
        $category_title = Database::escape_string($category_title);

        // step 1: we determine the max value of the user defined course categories
        $sql = "SELECT sort FROM $table
                WHERE user_id='".$current_user_id."'
                ORDER BY sort DESC";
        $rs_sort = Database::query($sql);
        $maxsort = Database::fetch_array($rs_sort);
        $nextsort = $maxsort['sort'] + 1;

        // step 2: we check if there is already a category with this name,
        // if not we store it, else we give an error.
        $sql = "SELECT * FROM $table
                WHERE
                    user_id='".$current_user_id."' AND
                    title='".$category_title."'
                ORDER BY sort DESC";
        $rs = Database::query($sql);

        $result = false;
        if (0 == Database::num_rows($rs)) {
            $sql = "INSERT INTO $table (user_id, title,sort)
                    VALUES ('".$current_user_id."', '".api_htmlentities(
                    $category_title,
                    ENT_QUOTES,
                    api_get_system_encoding()
                )."', '".$nextsort."')";
            $resultQuery = Database::query($sql);
            if (Database::affected_rows($resultQuery)) {
                $result = true;
            }
        }

        return $result;
    }
}
