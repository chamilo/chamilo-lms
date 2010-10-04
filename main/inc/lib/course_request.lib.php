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

}