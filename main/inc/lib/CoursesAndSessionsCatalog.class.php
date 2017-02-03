<?php
/* For licensing terms, see /license.txt */

/**
 * @todo change class name
 * Class CoursesAndSessionsCatalog
 */
class CoursesAndSessionsCatalog
{
    /**
     * Check the configuration for the courses and sessions catalog
     * @global array $_configuration Configuration
     * @param int $value The value to check
     *
     * @return boolean Whether the configuration is $value
     */
    public static function is($value = CATALOG_COURSES)
    {
        $showCoursesSessions = intval(api_get_setting('catalog_show_courses_sessions'));
        if ($showCoursesSessions == $value) {
            return true;
        }

        return false;
    }

    /**
     * Check whether to display the sessions list
     * @global array $_configuration Configuration
     *
     * @return boolean whether to display
     */
    public static function showSessions()
    {
        $catalogShow = intval(api_get_setting('catalog_show_courses_sessions'));

        if ($catalogShow == CATALOG_SESSIONS || $catalogShow == CATALOG_COURSES_SESSIONS) {
            return true;
        }

        return false;
    }

    /**
     * Check whether to display the courses list
     * @global array $_configuration Configuration
     *
     * @return boolean whether to display
     */
    public static function showCourses()
    {
        $catalogShow = intval(api_get_setting('catalog_show_courses_sessions'));

        if ($catalogShow == CATALOG_COURSES || $catalogShow == CATALOG_COURSES_SESSIONS) {
            return true;
        }

        return false;
    }
}
