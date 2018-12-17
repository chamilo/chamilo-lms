<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @todo change class name
 * Class CoursesAndSessionsCatalog
 */
class CoursesAndSessionsCatalog
{
    const PAGE_LENGTH = 12;

    /**
     * Check the configuration for the courses and sessions catalog.
     *
     * @global array $_configuration Configuration
     *
     * @param int $value The value to check
     *
     * @return bool Whether the configuration is $value
     */
    public static function is($value = CATALOG_COURSES)
    {
        $showCoursesSessions = (int) api_get_setting('catalog_show_courses_sessions');
        if ($showCoursesSessions == $value) {
            return true;
        }

        return false;
    }

    /**
     * Check whether to display the sessions list.
     *
     * @global array $_configuration Configuration
     *
     * @return bool whether to display
     */
    public static function showSessions()
    {
        $catalogShow = (int) api_get_setting('catalog_show_courses_sessions');

        if ($catalogShow == CATALOG_SESSIONS || $catalogShow == CATALOG_COURSES_SESSIONS) {
            return true;
        }

        return false;
    }

    /**
     * Check whether to display the courses list.
     *
     * @global array $_configuration Configuration
     *
     * @return bool whether to display
     */
    public static function showCourses()
    {
        $catalogShow = (int) api_get_setting('catalog_show_courses_sessions');

        if ($catalogShow == CATALOG_COURSES || $catalogShow == CATALOG_COURSES_SESSIONS) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getCoursesToAvoid()
    {
        $TABLE_COURSE_FIELD = Database::get_main_table(TABLE_EXTRA_FIELD);
        $TABLE_COURSE_FIELD_VALUE = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        // Check special courses
        $courseListToAvoid = CourseManager::get_special_course_list();

        // Checks "hide_from_catalog" extra field
        $extraFieldType = ExtraField::COURSE_FIELD_TYPE;

        $sql = "SELECT item_id FROM $TABLE_COURSE_FIELD_VALUE tcfv
                INNER JOIN $TABLE_COURSE_FIELD tcf
                ON tcfv.field_id =  tcf.id
                WHERE
                    tcf.extra_field_type = $extraFieldType AND
                    tcf.variable = 'hide_from_catalog' AND
                    tcfv.value = 1
                ";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $courseListToAvoid[] = $row['item_id'];
            }
        }

        return $courseListToAvoid;
    }

    /**
     * @return string
     */
    public static function getAvoidCourseCondition()
    {
        $courseListToAvoid = self::getCoursesToAvoid();
        $condition = '';
        if (!empty($courseListToAvoid)) {
            $courses = [];
            foreach ($courseListToAvoid as $courseId) {
                $courses[] = '"'.$courseId.'"';
            }
            $condition = ' AND course.id NOT IN ('.implode(',', $courses).')';
        }

        return $condition;
    }

    /**
     * Get available le courses count.
     *
     * @param int $accessUrlId (optional)
     *
     * @return int Number of courses
     */
    public static function countAvailableCoursesToShowInCatalog($accessUrlId = 1)
    {
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tableCourseRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $courseToAvoidCondition = self::getAvoidCourseCondition();
        $visibilityCondition = CourseManager::getCourseVisibilitySQLCondition('course', true);

        $accessUrlId = (int) $accessUrlId;
        if (empty($accessUrlId)) {
            $accessUrlId = 1;
        }

        $sql = "SELECT count(course.id) 
                FROM $tableCourse course
                INNER JOIN $tableCourseRelAccessUrl u
                ON (course.id = u.c_id)
                WHERE
                    u.access_url_id = $accessUrlId AND
                    course.visibility != 0 AND
                    course.visibility != 4
                    $courseToAvoidCondition
                    $visibilityCondition
                ";

        $res = Database::query($sql);
        $row = Database::fetch_row($res);

        return $row[0];
    }

    /**
     * @return array
     */
    public static function getCourseCategories()
    {
        $urlId = 1;
        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
        }

        $countCourses = self::countAvailableCoursesToShowInCatalog($urlId);

        $categories = [];
        $categories[0][0] = [
            'id' => 0,
            'name' => get_lang('DisplayAll'),
            'code' => 'ALL',
            'parent_id' => null,
            'tree_pos' => 0,
            'count_courses' => $countCourses,
        ];

        $categoriesFromDatabase = CourseCategory::getCategories();
        foreach ($categoriesFromDatabase as $row) {
            $count_courses = CourseCategory::countCoursesInCategory($row['code']);
            $row['count_courses'] = $count_courses;
            if (empty($row['parent_id'])) {
                $categories[0][$row['tree_pos']] = $row;
            } else {
                $categories[$row['parent_id']][$row['tree_pos']] = $row;
            }
        }
        $count_courses = CourseCategory::countCoursesInCategory();
        $categories[0][count($categories[0]) + 1] = [
            'id' => 0,
            'name' => get_lang('None'),
            'code' => 'NONE',
            'parent_id' => null,
            'tree_pos' => $row['tree_pos'] + 1,
            'children_count' => 0,
            'auth_course_child' => true,
            'auth_cat_child' => true,
            'count_courses' => $count_courses,
        ];

        return $categories;
    }

    /**
     * Return LIMIT to filter SQL query.
     *
     * @param array $limit
     *
     * @return string
     */
    public static function getLimitFilterFromArray($limit)
    {
        $limitFilter = '';
        if (!empty($limit) && is_array($limit)) {
            $limitStart = isset($limit['start']) ? (int) $limit['start'] : 0;
            $limitLength = isset($limit['length']) ? (int) $limit['length'] : 12;
            $limitFilter = 'LIMIT '.$limitStart.', '.$limitLength;
        }

        return $limitFilter;
    }

    /**
     * @param string $category_code
     * @param int    $random_value
     * @param array  $limit         will be used if $random_value is not set.
     *                              This array should contains 'start' and 'length' keys
     *
     * @return array
     */
    public static function getCoursesInCategory($category_code, $random_value = null, $limit = [])
    {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $avoidCoursesCondition = self::getAvoidCourseCondition();
        $visibilityCondition = CourseManager::getCourseVisibilitySQLCondition(
            'course',
            true
        );

        if (!empty($random_value)) {
            $random_value = intval($random_value);

            $sql = "SELECT COUNT(*) FROM $tbl_course";
            $result = Database::query($sql);
            list($num_records) = Database::fetch_row($result);

            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

                $sql = "SELECT COUNT(*) FROM $tbl_course course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE access_url_id = $url_access_id ";
                $result = Database::query($sql);
                list($num_records) = Database::fetch_row($result);

                $sql = "SELECT course.id, course.id as real_id 
                        FROM $tbl_course course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            access_url_id = $url_access_id AND
                            RAND()*$num_records< $random_value
                            $avoidCoursesCondition 
                            $visibilityCondition
                        ORDER BY RAND()
                        LIMIT 0, $random_value";
            } else {
                $sql = "SELECT id, id as real_id FROM $tbl_course course
                        WHERE 
                            RAND()*$num_records< $random_value 
                            $avoidCoursesCondition 
                            $visibilityCondition
                        ORDER BY RAND()
                        LIMIT 0, $random_value";
            }

            $result = Database::query($sql);
            $id_in = null;
            while (list($id) = Database::fetch_row($result)) {
                if ($id_in) {
                    $id_in .= ",$id";
                } else {
                    $id_in = "$id";
                }
            }
            if ($id_in === null) {
                return [];
            }
            $sql = "SELECT *, id as real_id FROM $tbl_course WHERE id IN($id_in)";
        } else {
            $limitFilter = self::getLimitFilterFromArray($limit);
            $category_code = Database::escape_string($category_code);
            if (empty($category_code) || $category_code == "ALL") {
                $sql = "SELECT *, id as real_id 
                        FROM $tbl_course course
                        WHERE
                          1=1
                          $avoidCoursesCondition
                          $visibilityCondition
                    ORDER BY title $limitFilter ";
            } else {
                if ($category_code == 'NONE') {
                    $category_code = '';
                }
                $sql = "SELECT *, id as real_id FROM $tbl_course course
                        WHERE
                            category_code='$category_code'
                            $avoidCoursesCondition
                            $visibilityCondition
                        ORDER BY title $limitFilter ";
            }

            // Showing only the courses of the current Chamilo access_url_id
            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                if ($category_code != "ALL") {
                    $sql = "SELECT *, course.id real_id FROM $tbl_course as course
                            INNER JOIN $tbl_url_rel_course as url_rel_course
                            ON (url_rel_course.c_id = course.id)
                            WHERE
                                access_url_id = $url_access_id AND
                                category_code='$category_code'
                                $avoidCoursesCondition
                                $visibilityCondition
                            ORDER BY title $limitFilter";
                } else {
                    $sql = "SELECT *, course.id real_id FROM $tbl_course as course
                            INNER JOIN $tbl_url_rel_course as url_rel_course
                            ON (url_rel_course.c_id = course.id)
                            WHERE
                                access_url_id = $url_access_id
                                $avoidCoursesCondition
                                $visibilityCondition
                            ORDER BY title $limitFilter";
                }
            }
        }

        $result = Database::query($sql);
        $courses = [];
        while ($row = Database::fetch_array($result)) {
            $row['registration_code'] = !empty($row['registration_code']);
            $count_users = CourseManager::get_users_count_in_course($row['code']);
            $count_connections_last_month = Tracking::get_course_connections_count(
                $row['id'],
                0,
                api_get_utc_datetime(time() - (30 * 86400))
            );

            if ($row['tutor_name'] == '0') {
                $row['tutor_name'] = get_lang('NoManager');
            }
            $point_info = CourseManager::get_course_ranking($row['id'], 0);
            $courses[] = [
                'real_id' => $row['real_id'],
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
                'category' => $row['category_code'],
                'count_users' => $count_users,
                'count_connections' => $count_connections_last_month,
            ];
        }

        return $courses;
    }

    /**
     * Search the courses database for a course that matches the search term.
     * The search is done on the code, title and tutor field of the course table.
     *
     * @param string $search_term The string that the user submitted, what we are looking for
     * @param array  $limit
     * @param bool   $justVisible search only on visible courses in the catalogue
     *
     * @return array an array containing a list of all the courses matching the the search term
     */
    public static function search_courses($search_term, $limit, $justVisible = false)
    {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $limitFilter = self::getLimitFilterFromArray($limit);
        $avoidCoursesCondition = self::getAvoidCourseCondition();
        $visibilityCondition = $justVisible ? CourseManager::getCourseVisibilitySQLCondition('course', true) : '';
        $search_term_safe = Database::escape_string($search_term);
        $sql_find = "SELECT * FROM $courseTable course
                      WHERE (
                            course.code LIKE '%".$search_term_safe."%' OR
                            course.title LIKE '%".$search_term_safe."%' OR
                            course.tutor_name LIKE '%".$search_term_safe."%'
                        )
                        $avoidCoursesCondition
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
                                    title LIKE '%".$search_term_safe."%' OR
                                    tutor_name LIKE '%".$search_term_safe."%'
                                )
                                $avoidCoursesCondition
                                $visibilityCondition
                            ORDER BY title, visual_code ASC
                            $limitFilter
                            ";
            }
        }
        $result_find = Database::query($sql_find);
        $courses = [];
        while ($row = Database::fetch_array($result_find)) {
            $row['registration_code'] = !empty($row['registration_code']);
            $count_users = count(CourseManager::get_user_list_from_course_code($row['code']));
            $count_connections_last_month = Tracking::get_course_connections_count(
                $row['id'],
                0,
                api_get_utc_datetime(time() - (30 * 86400))
            );

            $point_info = CourseManager::get_course_ranking($row['id'], 0);

            $courses[] = [
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
                'count_connections' => $count_connections_last_month,
            ];
        }

        return $courses;
    }

    /**
     * List the sessions.
     *
     * @param string $date  (optional) The date of sessions
     * @param array  $limit
     *
     * @return array The session list
     * @throws Exception
     */
    public static function browseSessions($date = null, $limit = [])
    {
        $em = Database::getManager();
        $urlId = api_get_current_access_url_id();
        $date = Database::escape_string($date);
        $sql = "SELECT s.id FROM session s ";
        $sql .= "
            INNER JOIN access_url_rel_session ars
            ON s.id = ars.session_id
        ";

        $sql .= "
            WHERE s.nbr_courses > 0
                AND ars.access_url_id = $urlId
        ";

        if (!is_null($date)) {
            $sql .= "
                AND (
                    ('$date' BETWEEN DATE(s.access_start_date) AND DATE(s.access_end_date))
                    OR (s.access_end_date IS NULL)
                    OR (
                        s.access_start_date IS NULL
                        AND s.access_end_date IS NOT NULL
                        AND DATE(s.access_end_date) >= '$date'
                    )
                )
            ";
        }

        if (!empty($limit)) {
            $limit['start'] = (int) $limit['start'];
            $limit['length'] = (int) $limit['length'];
            $sql .= "LIMIT {$limit['start']}, {$limit['length']} ";
        }

        $list = Database::store_result(Database::query($sql), 'ASSOC');
        $sessions = [];
        foreach ($list as $sessionData) {
            $id = $sessionData['id'];
            $sessions[] = $em->find('ChamiloCoreBundle:Session', $id);
        }

        return $sessions;
    }

    /**
     * Search sessions by searched term by session name.
     *
     * @param string $queryTerm Term for search
     * @param array  $limit     Limit info
     *
     * @return array The sessions
     */
    public static function browseSessionsBySearch($queryTerm, array $limit)
    {
        $sessionsToBrowse = [];

        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->contains('name', $queryTerm)
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

    /**
     * Search sessions by the tags in their courses.
     *
     * @param string $termTag Term for search in tags
     * @param array  $limit   Limit info
     *
     * @return array The sessions
     */
    public static function browseSessionsByTags($termTag, array $limit)
    {
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();

        $sessions = $qb->select('s')
            ->distinct(true)
            ->from('ChamiloCoreBundle:Session', 's')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'src',
                Join::WITH,
                's.id = src.session'
            )
            ->innerJoin(
                'ChamiloCoreBundle:ExtraFieldRelTag',
                'frt',
                Join::WITH,
                'src.course = frt.itemId'
            )
            ->innerJoin(
                'ChamiloCoreBundle:Tag',
                't',
                Join::WITH,
                'frt.tagId = t.id'
            )
            ->innerJoin(
                'ChamiloCoreBundle:ExtraField',
                'f',
                Join::WITH,
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
}
