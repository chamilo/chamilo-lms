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
        $result = true;

        $courseInfo = api_get_course_info($course_code);
        // Check if course can be unsubscribe
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
        if ($number_of_rows > 0) {
            $result = false;
        }

        if ($result) {
            CourseManager::unsubscribe_user($current_user_id, $course_code, $sessionId);
        }

        return $result;
    }
}
