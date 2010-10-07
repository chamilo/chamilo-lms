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
        $sql = sprintf('SELECT COUNT(id) AS number FROM %s WHERE visual_code = "%s"', $table_course_request, $wanted_course_code);
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
        $category_code = Database::escape_string($category_code);
        $course_language = Database::escape_string($course_language);
        $objetives = Database::escape_string($objetives);
        $target_audience = Database::escape_string($target_audience);

        $user_id = api_get_user_id();
        if ($user_id <= 0) {
            return false;
        }

        $user_info = api_get_user_info($user_id);
        if (!is_array($user_info)) {
            return false;
        }
        $tutor_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $course_language);

        $request_date = date('Y-m-d H:i:s'); // TODO: Use the time-zones way.
        $status = COURSE_REQUEST_PENDING;

        $keys = define_course_keys($wanted_code, '', $_configuration['db_prefix']);
        if (!count($keys)) {
            return false;
        }
        $visual_code = $keys['currentCourseCode'];
        $code = $keys['currentCourseId'];
        $db_name = $keys['currentCourseDbName'];
        $directory = $keys['currentCourseRepository'];

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
        $last_insert_id = Database::get_last_insert_id();

        // TODO: Prepare and send notification e-mail messages.

        return $last_insert_id;

    }

    /**
     * Deletes a given course request.
     * @param int/string $id              The id (an integer number) of the corresponding database record.
     * @return array/bool                 Returns TRUE on success or FALSE on failure.
     */
    public static function delete_course_request($id) {
        $id = (int)$id;
        $sql = "DELETE FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE id = ".$id;
        $result = Database::query($sql);
        return $result !== false;
    }

    public static function count_course_requests($status = null) {
        $course_table = Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST);
        if (is_null($status)) {
            $sql = "SELECT COUNT(id) AS number FROM ".$course_table;
        } else {
            $status = (int)$status;
            $sql = "SELECT COUNT(id) AS number FROM ".$course_table." WHERE status = ".$status;
        }
        $result = Database::fetch_array(Database::query($sql));
        if (is_array($result)) {
            return $result['number'];
        }
        return false;
    }

    /**
     * Gets all the information about a course request using its database id as access key.
     * @param int/string $id              The id (an integer number) of the corresponding database record.
     * @return array/bool                 Returns the requested data as an array or FALSE on failure.
     */
    public static function get_course_request_info($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE id = ".$id;
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return Database::fetch_array($result);
        }
        return false;
    }

    /**
     * Accepts a given by its id course request. The requested course gets created immediately after the request acceptance.
     * @param int/string $id              The id (an integer number) of the corresponding database record.
     * @return array/bool                 Returns the code of the newly created course or FALSE on failure.
     */
    public static function accept_course_request($id) {

        $id = (int)$id;

        // Retrieve request's data
        $course_request_info = CourseRequestManager::get_course_request_info($id);
        if (!is_array($course_request_info)) {
            return false;
        }

        // Make all the checks again before the new course creation.

        $wanted_code = $course_request_info['code'];
        if (CourseManager::course_code_exists($wanted_code)) {
            return false;
        }

        $title = $course_request_info['title'];
        $category_code = $course_request_info['category_code'];
        $course_language = $course_request_info['course_language'];

        $user_id = (int)$course_request_info['user_id'];
        if ($user_id <= 0) {
            return false;
        }
        $user_info = api_get_user_info($user_id);
        if (!is_array($user_info)) {
            return false;
        }
        $tutor_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $course_language);

        // Create the requested course.

        $keys = define_course_keys($wanted_code, '', $_configuration['db_prefix']);
        if (!count($keys)) {
            return false;
        }
        $visual_code = $keys['currentCourseCode'];
        $code = $keys['currentCourseId'];
        $db_name = $keys['currentCourseDbName'];
        $directory = $keys['currentCourseRepository'];
        $expiration_date = time() + $firstExpirationDelay;
        prepare_course_repository($directory, $code);
        update_Db_course($db_name);
        $pictures_array = fill_course_repository($directory);
        fill_Db_course($db_name, $directory, $course_language, $pictures_array);
        register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, $user_id, $expiration_date);

        // Mark the request as accepted.
        $sql = "UPDATE ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." SET status = ".COURSE_REQUEST_ACCEPTED." WHERE id = ".$id;
        Database::query($sql);

        // TODO: Prepare and send notification e-mail messages.

        return $code;

    }

    /**
     * Rejects a given course request.
     * @param int/string $id              The id (an integer number) of the corresponding database record.
     * @return array/bool                 Returns TRUE on success or FALSE on failure.
     */
    public static function reject_course_request($id) {

        $id = (int)$id;
        $sql = "UPDATE ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." SET status = ".COURSE_REQUEST_REJECTED." WHERE id = ".$id;
        $result = Database::query($sql) !== false;

        // TODO: Prepare and send notification e-mail messages.

        return $result;
    }

    /**
     * Asks the author (through e-mail) for additional info about the given course request.
     * @param int/string $id              The database primary id of the given request.
     * @return array/bool                 Returns TRUE on success or FALSE on failure.
     */
    public static function ask_for_additional_info($id) {

        $id = (int)$id;
        $sql = "UPDATE ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." SET info = 1 WHERE id = ".$id;
        $result = Database::query($sql) !== false;

        // TODO: Send the e-mail.

        return $result;
    }

}
