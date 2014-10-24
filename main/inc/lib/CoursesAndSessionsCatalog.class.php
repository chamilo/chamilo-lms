<?php

class CoursesAndSessionsCatalog
{

    /**
     * Check the configuration for the courses and sessions catalog
     * @global array $_configuration Configuration
     * @param int $value The value to check
     * @return boolean Whether the configuration is $value
     */
    public static function is($value = CATALOG_COURSES)
    {
        global $_configuration;

        if (isset($_configuration['catalog_show_courses_sessions'])) {
            if ($_configuration['catalog_show_courses_sessions'] == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether to display the sessions list
     * @global array $_configuration Configuration
     * @return boolean whether to display
     */
    public static function showSessions()
    {
        global $_configuration;

        $catalogShow = CATALOG_COURSES;

        if (isset($_configuration['catalog_show_courses_sessions'])) {
            $catalogShow = $_configuration['catalog_show_courses_sessions'];
        }

        if ($catalogShow == CATALOG_SESSIONS || $catalogShow == CATALOG_COURSES_SESSIONS) {
            return true;
        }

        return false;
    }

    /**
     * Check whether to display the courses list
     * @global array $_configuration Configuration
     * @return boolean whether to display
     */
    public static function showCourses()
    {
        global $_configuration;

        $catalogShow = CATALOG_COURSES;

        if (isset($_configuration['catalog_show_courses_sessions'])) {
            $catalogShow = $_configuration['catalog_show_courses_sessions'];
        }

        if ($catalogShow == CATALOG_COURSES || $catalogShow == CATALOG_COURSES_SESSIONS) {
            return true;
        }

        return false;
    }

}
