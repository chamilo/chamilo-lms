<?php
/* For licensing terms, see /license.txt */

/**
 * Course request manager
 * @package chamilo.library
 *
 * @author José Manuel Abuin Mosquera <chema@cesga.es>, 2010
 * @author Bruno Rubio Gayo <brubio@cesga.es>, 2010
 * Centro de Supercomputacion de Galicia (CESGA)
 *
 * @author Ivan Tcholakov <ivantcholakov@gmail.com> (technical adaptation for Chamilo 1.8.8), 2010
 */

define(COURSE_REQUEST_PENDING,  0);
define(COURSE_REQUEST_ACCEPTED, 1);
define(COURSE_REQUEST_REJECTED, 2);

class CourseRequestManager {

    /**
     * Checks whether a given course code has been already occupied.
     * @param string $wanted_course_code    The code to be checked.
     * @return string
     * Returns TRUE if there is created:
     * - a course with the same code OR visual_code (visualcode).
     * - a course request with the same code as the given one, or
     * Othewise returns FALSE.
     */
    public static function course_code_exists($wanted_course_code) {
        if ($code_exists = CourseManager::course_code_exists($wanted_course_code)) {
            return $code_exists;
        }
        $table_course_request = Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST);
        $wanted_course_code = Database::escape_string($wanted_course_code);
        $sql = sprintf('SELECT COUNT(*) as number FROM %s WHERE visual_code = "%s"', $table_course_request, $wanted_course_code);
        $result = Database::fetch_array(Database::query($sql));
        return $result['number'] > 0;
    }

    /**
     * Creates a new course request within the database.
     * @param string $wanted_code       The code for the created in the future course.
     * @param string $title
     * @param string $description
     * @param string $category_code
     * @param string $course_language
     * @param string $objetives
     * @param string $target_audience
     * @return int/bool                 The database id of the newly created course request or FALSE on failure.
     */
    public static function create_course_request($wanted_code, $title, $description, $category_code, $course_language, $objetives, $target_audience) {

        $wanted_code = Database::escape_string($wanted_code);
        $title = Database::escape_string($title);
        $description = Database::escape_string($description);
        $objetives = str_replace('"', '', $objetives);
        $target_audience = str_replace('"', '', $target_audience);

        $keys = define_course_keys($wanted_code, '', $_configuration['db_prefix']);
        if (!count($keys)) {
            return false;
        }
        $visual_code = $keys['currentCourseCode'];
        $code = $keys['currentCourseId'];
        $db_name = $keys['currentCourseDbName'];
        $directory = $keys['currentCourseRepository'];

        $user_id = api_get_user_id();
        if ($user_id <= 0) {
            return false;
        }
        $user_info = api_get_user_info($user_id);
        $tutor_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $course_language);

        $request_date = date('Y-m-d H:i:s'); // TODO: Use the time-zones way.
        $status = COURSE_REQUEST_PENDING;

        $sql = sprintf('INSERT INTO %s (
                code, user_id, directory, db_name,
                course_language, title, description, category_code,
                tutor_name, visual_code, request_date,
                objetives, target_audience, status)
            VALUES (
                "%s","%s","%s","%s",
                "%s","%s","%s","%s",
                "%s","%s","%s",
                "%s","%s","%s");', Database::get_main_table(TABLE_MAIN_COURSE_REQUEST),
                $code, $user_id, $directory, $db_name,
                $course_language, $title, $description, $category_code,
                $tutor_name, $visual_code, $request_date,
                $objetives, $target_audience, $status);
        $result_sql = Database::query($sql);

        if (!$result_sql) {
            return false;
        }
        return Database::get_last_insert_id();

    }

    /**
     * Gets all the information about a course request using its database id as access key.
     * @param int/string $id              The id (an integer number) of the corresponding database record.
     * @return array/bool                 Returns the requested data as an array of FALSE on failure.
     */
    public static function get_course_request_info($id) {
        $sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE id='".Database::escape_string($id)."'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return Database::fetch_array($result);
        }
        return false;
    }

}