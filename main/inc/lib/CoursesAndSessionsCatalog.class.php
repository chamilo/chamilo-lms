<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\Tag;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @todo change class name
 */
class CoursesAndSessionsCatalog
{
    public const PAGE_LENGTH = 12;

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

        $categoryToAvoid = api_get_configuration_value('course_category_code_to_use_as_model');
        if (!empty($categoryToAvoid) && api_is_student()) {
            $coursesInCategoryToAvoid = CourseCategory::getCoursesInCategory($categoryToAvoid, '', false);
            if (!empty($coursesInCategoryToAvoid)) {
                foreach ($coursesInCategoryToAvoid as $courseToAvoid) {
                    $courseListToAvoid[] = $courseToAvoid['id'];
                }
            }
        }

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

    public static function getCourseCategoriesTree()
    {
        $urlId = 1;
        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
        }

        $countCourses = self::countAvailableCoursesToShowInCatalog($urlId);
        $categories = [];
        $list = [];

        $categories['ALL'] = [
            'id' => 0,
            'name' => get_lang('DisplayAll'),
            'code' => 'ALL',
            'parent_id' => null,
            'tree_pos' => 0,
            'number_courses' => $countCourses,
            'level' => 0,
        ];

        $allCategories = CourseCategory::getAllCategories();
        $categoryToAvoid = '';
        if (api_is_student()) {
            $categoryToAvoid = api_get_configuration_value('course_category_code_to_use_as_model');
        }
        foreach ($allCategories as $category) {
            $categoryCode = $category['code'];
            if (!empty($categoryToAvoid) && $categoryToAvoid == $categoryCode) {
                continue;
            }

            if (empty($category['parent_id'])) {
                $list[$categoryCode] = $category;
                $list[$categoryCode]['level'] = 0;
                list($subList, $childrenCount) = self::buildCourseCategoryTree($allCategories, $categoryCode, 0);
                foreach ($subList as $item) {
                    $list[$item['code']] = $item;
                }
                // Real course count
                $countCourses = CourseCategory::countCoursesInCategory($categoryCode);
                $list[$categoryCode]['number_courses'] = $childrenCount + $countCourses;
            }
        }

        // count courses that are in no category
        $countCourses = CourseCategory::countCoursesInCategory('NONE');
        $categories['NONE'] = [
            'id' => 0,
            'name' => get_lang('WithoutCategory'),
            'code' => 'NONE',
            'parent_id' => null,
            'tree_pos' => 0,
            'children_count' => 0,
            'auth_course_child' => true,
            'auth_cat_child' => true,
            'number_courses' => $countCourses,
            'level' => 0,
        ];

        return array_merge($list, $categories);
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
     * @param string $categoryCode
     * @param int    $randomValue
     * @param array  $limit        will be used if $randomValue is not set.
     *                             This array should contains 'start' and 'length' keys
     *
     * @return array
     */
    public static function getCoursesInCategory($categoryCode, $randomValue = null, $limit = [])
    {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $avoidCoursesCondition = self::getAvoidCourseCondition();
        $visibilityCondition = CourseManager::getCourseVisibilitySQLCondition('course', true);

        if (!empty($randomValue)) {
            $randomValue = (int) $randomValue;

            $sql = "SELECT COUNT(*) FROM $tbl_course";
            $result = Database::query($sql);
            list($num_records) = Database::fetch_row($result);

            if (api_is_multiple_url_enabled()) {
                $urlId = api_get_current_access_url_id();
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

                $urlCondition = ' access_url_id = '.$urlId.' ';
                $allowBaseCategories = api_get_configuration_value('allow_base_course_category');
                if ($allowBaseCategories) {
                    $urlCondition = ' (access_url_id = '.$urlId.' OR access_url_id = 1)  ';
                }

                $sql = "SELECT COUNT(*)
                        FROM $tbl_course course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE access_url_id = $urlId";
                $result = Database::query($sql);
                list($num_records) = Database::fetch_row($result);

                $sql = "SELECT course.id, course.id as real_id
                        FROM $tbl_course course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            $urlCondition AND
                            RAND()*$num_records< $randomValue
                            $avoidCoursesCondition
                            $visibilityCondition
                        ORDER BY RAND()
                        LIMIT 0, $randomValue";
            } else {
                $sql = "SELECT id, id as real_id
                        FROM $tbl_course course
                        WHERE
                            RAND()*$num_records< $randomValue
                            $avoidCoursesCondition
                            $visibilityCondition
                        ORDER BY RAND()
                        LIMIT 0, $randomValue";
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
            if (null === $id_in) {
                return [];
            }
            $sql = "SELECT *, id as real_id FROM $tbl_course WHERE id IN($id_in)";
        } else {
            $limitFilter = self::getLimitFilterFromArray($limit);
            $categoryCode = Database::escape_string($categoryCode);
            $listCode = self::childrenCategories($categoryCode);
            $conditionCode = ' ';

            if (empty($listCode)) {
                if ($categoryCode === 'NONE') {
                    $conditionCode .= " category_code='' ";
                } else {
                    $conditionCode .= " category_code='$categoryCode' ";
                }
            } else {
                foreach ($listCode as $code) {
                    $conditionCode .= " category_code='$code' OR ";
                }
                $conditionCode .= " category_code='$categoryCode' ";
            }

            if (empty($categoryCode) || $categoryCode === 'ALL') {
                $sql = "SELECT *, id as real_id
                        FROM $tbl_course course
                        WHERE
                          1=1
                          $avoidCoursesCondition
                          $visibilityCondition
                        ORDER BY title $limitFilter ";
            } else {
                $sql = "SELECT *, id as real_id FROM $tbl_course course
                        WHERE
                            $conditionCode
                            $avoidCoursesCondition
                            $visibilityCondition
                        ORDER BY title $limitFilter ";
            }

            // Showing only the courses of the current Chamilo access_url_id
            if (api_is_multiple_url_enabled()) {
                $urlId = api_get_current_access_url_id();
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

                $urlCondition = ' access_url_id = '.$urlId.' ';
                if ($categoryCode !== 'ALL') {
                    $sql = "SELECT *, course.id real_id
                            FROM $tbl_course as course
                            INNER JOIN $tbl_url_rel_course as url_rel_course
                            ON (url_rel_course.c_id = course.id)
                            WHERE
                                $urlCondition AND
                                $conditionCode
                                $avoidCoursesCondition
                                $visibilityCondition
                            ORDER BY title $limitFilter";
                } else {
                    $sql = "SELECT *, course.id real_id FROM $tbl_course as course
                            INNER JOIN $tbl_url_rel_course as url_rel_course
                            ON (url_rel_course.c_id = course.id)
                            WHERE
                                $urlCondition
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
            $connectionsLastMonth = Tracking::get_course_connections_count(
                $row['id'],
                0,
                api_get_utc_datetime(time() - (30 * 86400))
            );

            if ($row['tutor_name'] == '0') {
                $row['tutor_name'] = get_lang('NoManager');
            }

            $courses[] = [
                'real_id' => $row['real_id'],
                'point_info' => CourseManager::get_course_ranking($row['id'], 0),
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
                'count_connections' => $connectionsLastMonth,
            ];
        }

        return $courses;
    }

    /**
     * Search the courses database for a course that matches the search term.
     * The search is done on the code, title and tutor field of the course table.
     *
     * @param string $categoryCode
     * @param string $keyword      The string that the user submitted
     * @param array  $limit
     * @param bool   $justVisible  search only on visible courses in the catalogue
     * @param array  $conditions
     *
     * @return array an array containing a list of all the courses matching the the search term
     */
    public static function searchCourses($categoryCode, $keyword, $limit, $justVisible = false, $conditions = [])
    {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $limitFilter = self::getLimitFilterFromArray($limit);
        $avoidCoursesCondition = self::getAvoidCourseCondition();
        $visibilityCondition = $justVisible ? CourseManager::getCourseVisibilitySQLCondition('course', true) : '';

        $keyword = Database::escape_string($keyword);
        $categoryCode = Database::escape_string($categoryCode);

        $sqlInjectJoins = '';
        $where = 'AND 1 = 1 ';
        $sqlInjectWhere = '';
        $injectExtraFields = '1';
        if (!empty($conditions)) {
            $sqlInjectJoins = $conditions['inject_joins'];
            $where = $conditions['where'];
            $sqlInjectWhere = $conditions['inject_where'];
            $injectExtraFields = !empty($conditions['inject_extra_fields']) ? $conditions['inject_extra_fields'] : 1;
            $injectExtraFields = rtrim($injectExtraFields, ', ');
        }

        $categoryFilter = '';
        if ($categoryCode === 'ALL' || empty($categoryCode)) {
            // Nothing to do
        } elseif ($categoryCode === 'NONE') {
            $categoryFilter = ' AND category_code = "" ';
        } else {
            $categoryFilter = ' AND category_code = "'.$categoryCode.'" ';
        }

        //$sql = "SELECT DISTINCT course.*, $injectExtraFields
        $sql = "SELECT DISTINCT(course.id)
                FROM $courseTable course
                $sqlInjectJoins
                WHERE (
                        course.code LIKE '%".$keyword."%' OR
                        course.title LIKE '%".$keyword."%' OR
                        course.tutor_name LIKE '%".$keyword."%'
                    )
                    $where
                    $categoryFilter
                    $sqlInjectWhere
                    $avoidCoursesCondition
                    $visibilityCondition
                ORDER BY title, visual_code ASC
                $limitFilter
                ";

        if (api_is_multiple_url_enabled()) {
            $urlId = api_get_current_access_url_id();
            if (-1 != $urlId) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $urlCondition = ' access_url_id = '.$urlId.' AND';
                $allowBaseCategories = api_get_configuration_value('allow_base_course_category');
                if ($allowBaseCategories) {
                    $urlCondition = ' (access_url_id = '.$urlId.' OR access_url_id = 1) AND ';
                }
                //SELECT DISTINCT course.*, $injectExtraFields
                $sql = "SELECT DISTINCT(course.id)
                        FROM $courseTable as course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        $sqlInjectJoins
                        WHERE
                            access_url_id = $urlId AND
                            (
                                code LIKE '%".$keyword."%' OR
                                title LIKE '%".$keyword."%' OR
                                tutor_name LIKE '%".$keyword."%'
                            )
                            $where
                            $categoryFilter
                            $sqlInjectWhere
                            $avoidCoursesCondition
                            $visibilityCondition
                        ORDER BY title, visual_code ASC
                        $limitFilter
                       ";
            }
        }

        $result = Database::query($sql);
        $courses = [];
        while ($row = Database::fetch_array($result)) {
            $courseId = $row['id'];
            $courseInfo = api_get_course_info_by_id($courseId);
            if (empty($courseInfo)) {
                continue;
            }
            $courseCode = $courseInfo['code'];

            $countUsers = CourseManager::get_user_list_from_course_code(
                $courseCode,
                0,
                null,
                null,
                null,
                true
            );
            $connectionsLastMonth = Tracking::get_course_connections_count(
                $courseId,
                0,
                api_get_utc_datetime(time() - (30 * 86400))
            );

            $courseInfo['point_info'] = CourseManager::get_course_ranking($courseId, 0);
            $courseInfo['tutor'] = $courseInfo['tutor_name'];
            $courseInfo['registration_code'] = !empty($courseInfo['registration_code']);
            $courseInfo['count_users'] = $countUsers;
            $courseInfo['count_connections'] = $connectionsLastMonth;

            $courses[] = $courseInfo;
        }

        return $courses;
    }

    /**
     * Gets extra fields listed in configuration option course_catalog_settings/extra_field_sort_options sorting order.
     *
     * @return array "extra_field_$id" => order (1 = ascending, -1 = descending)
     */
    public static function courseExtraFieldSortingOrder()
    {
        $order = [];
        $variableOrder = api_get_configuration_sub_value('course_catalog_settings/extra_field_sort_options', []);
        foreach (self::getCourseExtraFieldsAvailableForSorting() as $extraField) {
            $order['extra_field_'.$extraField->getId()] = $variableOrder[$extraField->getVariable()];
        }

        return $order;
    }

    /**
     * Gets the extra fields listed in configuration option course_catalog_settings/extra_field_sort_options.
     *
     * @return ExtraField[]
     */
    public static function getCourseExtraFieldsAvailableForSorting()
    {
        $variables = array_keys(
            api_get_configuration_sub_value('course_catalog_settings/extra_field_sort_options', [])
        );
        if (is_array($variables) && !empty($variables)) {
            return ExtraField::getExtraFieldsFromVariablesOrdered($variables, ExtraField::COURSE_FIELD_TYPE);
        }

        return [];
    }

    /**
     * Builds the list of possible course standard sort criteria.
     *
     * @return array option name => order (1 = ascending, -1 = descending)
     */
    public static function courseStandardSortOrder()
    {
        return api_get_configuration_sub_value(
            'course_catalog_settings/standard_sort_options',
            [
                'title' => 1,
                'creation_date' => -1,
                'count_users' => -1, // subscription count
                'point_info/point_average' => -1, // average score
                'point_info/total_score' => -1, // score sum
                'point_info/users' => -1, // vote count
            ]
        );
    }

    /**
     * Builds the list of possible course sort criteria to be used in an HTML select element.
     *
     * @return array select option name => display text
     */
    public static function courseSortOptions()
    {
        /** @var $extraFields ExtraField[] */
        $standardLabels = [
            'title' => get_lang('Title'),
            'creation_date' => get_lang('CreationDate'),
            'count_users' => get_lang('SubscriptionCount'),
            'point_info/point_average' => get_lang('PointAverage'),
            'point_info/total_score' => get_lang('TotalScore'),
            'point_info/users' => get_lang('VoteCount'),
        ];
        $options = [];
        foreach (array_keys(self::courseStandardSortOrder()) as $name) {
            $options[$name] = $standardLabels[$name] ?: $name;
        }
        foreach (self::getCourseExtraFieldsAvailableForSorting() as $extraField) {
            $options['extra_field_'.$extraField->getId()] = $extraField->getDisplayText();
        }

        return $options;
    }

    public static function courseSortOrder()
    {
        return self::courseStandardSortOrder() + self::courseExtraFieldSortingOrder();
    }

    /**
     * Wrapper for self::searchCourses which locally sorts the results according to $sortKey.
     *
     * @param string   $categoryCode can be 'ALL', 'NONE' or any existing course category code
     * @param string   $keyword      search pattern to be found in course code, title or tutor_name
     * @param array    $limit        associative array generated by \CoursesAndSessionsCatalog::getLimitArray()
     * @param bool     $justVisible  search only on visible courses in the catalogue
     * @param array    $conditions   associative array generated using \ExtraField::parseConditions
     * @param string[] $sortKeys     a subset of the keys of the array returned by courseSortOptions()
     *
     * @return array list of all the courses matching the the search term
     */
    public static function searchAndSortCourses(
        $categoryCode,
        $keyword,
        $limit,
        $justVisible = false,
        $conditions = [],
        $sortKeys = []
    ) {
        // Get ALL matching courses (no limit)
        $courses = self::searchCourses($categoryCode, $keyword, null, $justVisible, $conditions);
        // Do we have extra fields to sort on ?
        $extraFieldsToSortOn = [];
        foreach (self::getCourseExtraFieldsAvailableForSorting() as $extraField) {
            if (in_array('extra_field_'.$extraField->getId(), $sortKeys)) {
                $extraFieldsToSortOn[] = $extraField;
            }
        }
        if (!empty($extraFieldsToSortOn)) {
            // load extra field values and store them in $courses
            $courseIds = [];
            foreach ($courses as $course) {
                $courseIds[] = $course['real_id'];
            }
            $values = ExtraField::getValueForEachExtraFieldForEachItem($extraFieldsToSortOn, $courseIds);
            foreach ($courses as &$course) {
                $courseId = $course['real_id'];
                if (array_key_exists($courseId, $values)) {
                    foreach ($values[$courseId] as $extraFieldId => $value) {
                        $course['extra_field_'.$extraFieldId] = $value;
                    }
                }
            }
            unset($course);
        }

        // do we have $course['groupKey']['subKey'] to sort on, such as 'point_info/users' ?
        foreach ($sortKeys as $key) {
            if (false !== strpos($key, '/')) {
                foreach ($courses as &$course) {
                    $subValue = api_array_sub_value($course, $key);
                    if (!is_null($subValue)) {
                        $course[$key] = $subValue;
                    }
                }
                unset($course);
            }
        }
        $sortOrder = self::courseSortOrder();
        usort($courses, function ($a, $b) use ($sortKeys, $sortOrder) {
            foreach ($sortKeys as $key) {
                $valueA = array_key_exists($key, $a) ? $a[$key] : null;
                $valueB = array_key_exists($key, $b) ? $b[$key] : null;
                if ($valueA !== $valueB) {
                    $aIsLessThanB = (is_string($valueA) && is_string($valueB))
                        ? strtolower($valueA) < strtolower($valueB)
                        : $valueA < $valueB;
                    $reverseOrder = (array_key_exists($key, $sortOrder) && -1 === $sortOrder[$key]);
                    $aIsBeforeB = ($aIsLessThanB xor $reverseOrder);

                    return $aIsBeforeB ? -1 : 1;
                }
            }

            return 0;
        });

        return array_slice($courses, $limit['start'], $limit['length']);
    }

    /**
     * List the sessions.
     *
     * @param string $date
     * @param array  $limit
     * @param bool   $returnQueryBuilder
     * @param bool   $getCount
     *
     * @return array|\Doctrine\ORM\Query The session list
     */
    public static function browseSessions($date = null, $limit = [], $returnQueryBuilder = false, $getCount = false)
    {
        $urlId = api_get_current_access_url_id();
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();
        $qb2 = $em->createQueryBuilder();

        $qb = $qb
            ->select('s')
            ->from('ChamiloCoreBundle:Session', 's')
            ->where(
                $qb->expr()->in(
                    's',
                    $qb2
                        ->select('s2')
                        ->from('ChamiloCoreBundle:AccessUrlRelSession', 'url')
                        ->join('ChamiloCoreBundle:Session', 's2')
                        ->where(
                            $qb->expr()->eq('url.sessionId ', 's2.id')
                        )->andWhere(
                            $qb->expr()->eq('url.accessUrlId ', $urlId))
                        ->getDQL()
                )
            )
            ->andWhere($qb->expr()->gt('s.nbrCourses', 0))
        ;

        if (!empty($date)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.accessEndDate'),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->lte('s.accessStartDate', $date),
                        $qb->expr()->gte('s.accessEndDate', $date)
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->gte('s.accessEndDate', $date)
                    )
                )
            );
        }

        if ($getCount) {
            $qb->select('count(s)');
        }

        $qb = self::hideFromSessionCatalogCondition($qb);

        if (!empty($limit)) {
            $qb
                ->setFirstResult($limit['start'])
                ->setMaxResults($limit['length'])
            ;
        }

        $query = $qb->getQuery();

        if ($returnQueryBuilder) {
            return $query;
        }

        if ($getCount) {
            return $query->getSingleScalarResult();
        }

        return $query->getResult();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     *
     * @return mixed
     */
    public static function hideFromSessionCatalogCondition($qb)
    {
        $em = Database::getManager();
        $qb3 = $em->createQueryBuilder();

        $extraField = new \ExtraField('session');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable('hide_from_catalog');
        if (!empty($extraFieldInfo)) {
            $qb->andWhere(
                $qb->expr()->notIn(
                    's',
                    $qb3
                        ->select('s3')
                        ->from('ChamiloCoreBundle:ExtraFieldValues', 'fv')
                        ->innerJoin('ChamiloCoreBundle:Session', 's3', Join::WITH, 'fv.itemId = s3.id')
                        ->where(
                            $qb->expr()->eq('fv.field', $extraFieldInfo['id'])
                        )->andWhere(
                            $qb->expr()->eq('fv.value ', 1)
                        )
                        ->getDQL()
                )
            );
        }

        return $qb;
    }

    /**
     * Search sessions by the tags in their courses.
     *
     * @param string $termTag Term for search in tags
     * @param array  $limit   Limit info
     *
     * @return array The sessions
     */
    public static function browseSessionsByTags($termTag, array $limit, $getCount = false)
    {
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();

        $urlId = api_get_current_access_url_id();

        $qb->select('s')
            ->distinct()
            ->from('ChamiloCoreBundle:Session', 's')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'src',
                Join::WITH,
                's.id = src.session'
            )
            ->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelSession',
                'url',
                Join::WITH,
                'url.sessionId = s.id'
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
                $qb->expr()->like('t.tag', ':tag')
            )
            ->andWhere(
                $qb->expr()->eq('f.extraFieldType', ExtraField::COURSE_FIELD_TYPE)
            )
            ->andWhere($qb->expr()->gt('s.nbrCourses', 0))
            ->andWhere($qb->expr()->eq('url.accessUrlId', $urlId))
            ->setParameter('tag', "$termTag%")
        ;

        if (!empty($limit)) {
            $qb
                ->setFirstResult($limit['start'])
                ->setMaxResults($limit['length'])
            ;
        }

        $qb = self::hideFromSessionCatalogCondition($qb);

        if ($getCount) {
            $qb->select('count(s)');

            return $qb->getQuery()->getSingleScalarResult();
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Search sessions by the title.
     *
     * @param string $keyword
     * @param array  $limit   Limit info
     *
     * @return array The sessions
     */
    public static function getSessionsByName($keyword, array $limit, $getCount = false)
    {
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();
        $urlId = api_get_current_access_url_id();

        $qb->select('s')
            ->distinct()
            ->from('ChamiloCoreBundle:Session', 's')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'src',
                Join::WITH,
                's.id = src.session'
            )
            ->innerJoin(
                'ChamiloCoreBundle:AccessUrlRelSession',
                'url',
                Join::WITH,
                'url.sessionId = s.id'
            )
            ->andWhere($qb->expr()->eq('url.accessUrlId', $urlId))
            ->andWhere('s.name LIKE :keyword')
            ->andWhere($qb->expr()->gt('s.nbrCourses', 0))
            ->setParameter('keyword', "%$keyword%")
        ;

        if (!empty($limit)) {
            $qb
                ->setFirstResult($limit['start'])
                ->setMaxResults($limit['length']);
        }

        $qb = self::hideFromSessionCatalogCondition($qb);

        if ($getCount) {
            $qb->select('count(s)');

            return $qb->getQuery()->getSingleScalarResult();
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Build a recursive tree of course categories.
     *
     * @param array $categories
     * @param int   $parentId
     * @param int   $level
     *
     * @return array
     */
    public static function buildCourseCategoryTree($categories, $parentId = 0, $level = 0)
    {
        $list = [];
        $count = 0;
        $level++;
        foreach ($categories as $category) {
            if (empty($category['parent_id'])) {
                continue;
            }
            if ($category['parent_id'] == $parentId) {
                $list[$category['code']] = $category;
                $count += $category['number_courses'];
                $list[$category['code']]['level'] = $level;
                list($subList, $childrenCount) = self::buildCourseCategoryTree(
                    $categories,
                    $category['code'],
                    $level
                );
                $list[$category['code']]['number_courses'] += $childrenCount;
                foreach ($subList as $item) {
                    $list[$item['code']] = $item;
                }
                $count += $childrenCount;
            }
        }

        return [$list, $count];
    }

    /**
     * List Code Search Category.
     *
     * @param string $code
     *
     * @return array
     */
    public static function childrenCategories($code)
    {
        $allCategories = CourseCategory::getAllCategories();
        $list = [];
        $row = [];

        if ($code !== 'ALL' && $code !== 'NONE') {
            foreach ($allCategories as $category) {
                if ($category['code'] === $code) {
                    $list = self::buildCourseCategoryTree($allCategories, $category['code'], 0);
                }
            }
            foreach ($list[0] as $item) {
                $row[] = $item['code'];
            }
        }

        return $row;
    }

    /**
     * Display the course catalog image of a course.
     *
     * @param array $course
     *
     * @return string HTML string
     */
    public static function returnThumbnail($course)
    {
        $course_path = api_get_path(SYS_COURSE_PATH).$course['directory'];

        if (file_exists($course_path.'/course-pic.png')) {
            // redimensioned image 85x85
            $courseMediumImage = api_get_path(WEB_COURSE_PATH).$course['directory'].'/course-pic.png';
        } else {
            // without picture
            $courseMediumImage = Display::return_icon(
                'session_default.png',
                null,
                null,
                null,
                null,
                true
            );
        }

        return $courseMediumImage;
    }

    /**
     * @param array $courseInfo
     *
     * @return string
     */
    public static function return_teacher($courseInfo)
    {
        $teachers = CourseManager::getTeachersFromCourse($courseInfo['real_id']);
        $length = count($teachers);

        if (!$length) {
            return '';
        }

        $html = '<div class="block-author">';
        if ($length > 6) {
            $html .= '<a
            id="plist"
            data-trigger="focus"
            tabindex="0" role="button"
            class="btn btn-default panel_popover"
            data-toggle="popover"
            title="'.addslashes(get_lang('CourseTeachers')).'"
            data-html="true"
        >
            <i class="fa fa-graduation-cap" aria-hidden="true"></i>
        </a>';
            $html .= '<div id="popover-content-plist" class="hide">';
            foreach ($teachers as $value) {
                $name = $value['firstname'].' '.$value['lastname'];
                $html .= '<div class="popover-teacher">';
                $html .= '<a href="'.$value['url'].'" class="ajax" data-title="'.$name.'" title="'.$name.'">
                        <img src="'.$value['avatar'].'" title="'.$name.'" alt="'.get_lang('UserPicture').'"/></a>';
                $html .= '<div class="teachers-details"><h5>
                        <a href="'.$value['url'].'" class="ajax" data-title="'.$name.'" title="'.$name.'">'
                    .$name.'</a></h5></div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            foreach ($teachers as $value) {
                $name = $value['firstname'].' '.$value['lastname'];
                if ($length > 2) {
                    $html .= '<a href="'.$value['url'].'" class="ajax" data-title="'.$name.'" title="'.$name.'">
                        <img src="'.$value['avatar'].'" title="'.$name.'" alt="'.get_lang('UserPicture').'"/></a>';
                } else {
                    $html .= '<a href="'.$value['url'].'" class="ajax" data-title="'.$name.'" title="'.$name.'">
                        <img src="'.$value['avatar'].'" title="'.$name.'" alt="'.get_lang('UserPicture').'"/></a>';
                    $html .= '<div class="teachers-details"><h5>
                        <a href="'.$value['url'].'" class="ajax" data-title="'.$name.'">'
                        .$name.'</a></h5><p>'.get_lang('Teacher').'</p></div>';
                }
            }
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Display the already registerd text in a course in the course catalog.
     *
     * @param $status
     *
     * @return string HTML string
     */
    public static function return_already_registered_label($status)
    {
        $icon = '<em class="fa fa-check"></em>';
        $title = get_lang('YouAreATeacherOfThisCourse');
        if ($status === 'student') {
            $icon = '<em class="fa fa-check"></em>';
            $title = get_lang('AlreadySubscribed');
        }

        $html = Display::tag(
            'span',
            $icon.' '.$title,
            [
                'id' => 'register',
                'class' => 'label-subscribed text-success',
                'title' => $title,
                'aria-label' => $title,
            ]
        );

        return $html.PHP_EOL;
    }

    /**
     * Display the register button of a course in the course catalog.
     *
     * @param $course
     * @param $stok
     * @param $categoryCode
     * @param $search_term
     *
     * @return string
     */
    public static function return_register_button($course, $stok, $categoryCode, $search_term)
    {
        $title = get_lang('Subscribe');
        $action = 'subscribe_course';
        if (!empty($course['registration_code'])) {
            $action = 'subscribe_course_validation';
        }

        return Display::url(
            Display::returnFontAwesomeIcon('check').' '.$title,
            api_get_self().'?action='.$action.'&sec_token='.$stok.
            '&course_code='.$course['code'].'&search_term='.$search_term.'&category_code='.$categoryCode,
            ['class' => 'btn btn-success btn-sm', 'title' => $title, 'aria-label' => $title]
        );
    }

    /**
     * Display the unregister button of a course in the course catalog.
     *
     * @param array  $course
     * @param string $stok
     * @param string $search_term
     * @param string $categoryCode
     * @param int    $sessionId
     *
     * @return string
     */
    public static function return_unregister_button($course, $stok, $search_term, $categoryCode, $sessionId = 0)
    {
        $title = get_lang('Unsubscription');
        $search_term = Security::remove_XSS($search_term);
        $categoryCode = Security::remove_XSS($categoryCode);
        $sessionId = (int) $sessionId;

        $url = api_get_self().'?action=unsubscribe&sec_token='.$stok.'&sid='.$sessionId.'&course_code='.$course['code'].
            '&search_term='.$search_term.'&category_code='.$categoryCode;

        return Display::url(
            Display::returnFontAwesomeIcon('sign-in').'&nbsp;'.$title,
            $url,
            ['class' => 'btn btn-danger', 'title' => $title, 'aria-label' => $title]
        );
    }

    /**
     * Get a HTML button for subscribe to session.
     *
     * @param int    $sessionId         The session ID
     * @param string $sessionName       The session name
     * @param bool   $checkRequirements Optional.
     *                                  Whether the session has requirement. Default is false
     * @param bool   $includeText       Optional. Whether show the text in button
     * @param bool   $btnBing
     *
     * @return string The button HTML
     */
    public static function getRegisteredInSessionButton(
        $sessionId,
        $sessionName,
        $checkRequirements = false,
        $includeText = false,
        $btnBing = false
    ) {
        $sessionId = (int) $sessionId;
        $class = 'btn-sm';
        if ($btnBing) {
            $class = 'btn-lg btn-block';
        }

        if ($checkRequirements) {
            return self::getRequirements($sessionId, SequenceResource::SESSION_TYPE, $includeText, $class);
        }

        $catalogSessionAutoSubscriptionAllowed = false;
        if (api_get_setting('catalog_allow_session_auto_subscription') === 'true') {
            $catalogSessionAutoSubscriptionAllowed = true;
        }

        $url = api_get_path(WEB_CODE_PATH);

        if ($catalogSessionAutoSubscriptionAllowed) {
            $url .= 'auth/courses.php?';
            $url .= http_build_query([
                'action' => 'subscribe_to_session',
                'session_id' => $sessionId,
            ]);

            $result = Display::toolbarButton(
                get_lang('Subscribe'),
                $url,
                'pencil',
                'primary',
                [
                    'class' => $class.' ajax',
                    'data-title' => get_lang('AreYouSureToSubscribe'),
                    'data-size' => 'md',
                    'title' => get_lang('Subscribe'),
                ],
                $includeText
            );
        } else {
            $url .= 'inc/email_editor.php?';
            $url .= http_build_query([
                'action' => 'subscribe_me_to_session',
                'session' => Security::remove_XSS($sessionName),
            ]);

            $result = Display::toolbarButton(
                get_lang('SubscribeToSessionRequest'),
                $url,
                'pencil',
                'primary',
                ['class' => $class],
                $includeText
            );
        }

        $hook = HookResubscribe::create();
        if (!empty($hook)) {
            $hook->setEventData([
                'session_id' => $sessionId,
            ]);
            try {
                $hook->notifyResubscribe(HOOK_EVENT_TYPE_PRE);
            } catch (Exception $exception) {
                $result = $exception->getMessage();
            }
        }

        return $result;
    }

    public static function getRequirements($id, $type, $includeText, $class, $sessionId = 0)
    {
        $id = (int) $id;
        $type = (int) $type;
        $url = api_get_path(WEB_AJAX_PATH).'sequence.ajax.php?';
        $url .= http_build_query(
            [
                'a' => 'get_requirements',
                'id' => $id,
                'type' => $type,
                'sid' => $sessionId,
            ]
        );

        return Display::toolbarButton(
            get_lang('CheckRequirements'),
            $url,
            'shield',
            'info',
            [
                'class' => $class.' ajax',
                'data-title' => get_lang('CheckRequirements'),
                'data-size' => 'md',
                'title' => get_lang('CheckRequirements'),
            ],
            $includeText
        );
    }

    /**
     * Generate a label if the user has been  registered in session.
     *
     * @return string The label
     */
    public static function getAlreadyRegisteredInSessionLabel()
    {
        $icon = '<em class="fa fa-graduation-cap"></em>';

        return Display::div(
            $icon,
            [
                'class' => 'btn btn-default btn-sm registered',
                'title' => get_lang("AlreadyRegisteredToSession"),
            ]
        );
    }

    /**
     * Get a icon for a session.
     *
     * @param string $sessionName The session name
     *
     * @return string The icon
     */
    public static function getSessionIcon($sessionName)
    {
        return Display::return_icon(
            'window_list.png',
            $sessionName,
            null,
            ICON_SIZE_MEDIUM
        );
    }

    public static function getSessionPagination($action, $countSessions, $limit)
    {
        $pageTotal = ceil($countSessions / $limit['length']);
        $pagination = '';
        // Do NOT show pagination if only one page or less
        if ($pageTotal > 1) {
            $pagination = self::getCatalogPagination(
                $limit['current'],
                $limit['length'],
                $pageTotal,
                null,
                $action
            );
        }

        return $pagination;
    }

    /**
     * Return Session catalog rendered view.
     *
     * @param array $limit
     */
    public static function sessionList($limit = [])
    {
        $date = isset($_POST['date']) ? $_POST['date'] : '';
        $limit = isset($limit) ? $limit : self::getLimitArray();

        $countSessions = self::browseSessions($date, [], false, true);
        $sessions = self::browseSessions($date, $limit);

        $pagination = self::getSessionPagination('display_sessions', $countSessions, $limit);
        $sessionsBlocks = self::getFormattedSessionsBlock($sessions);

        // Get session search catalogue URL
        $courseUrl = self::getCatalogUrl(
            1,
            $limit['length'],
            null,
            'subscribe'
        );

        $tpl = new Template();
        $tpl->assign('actions', self::getTabList(2));
        $tpl->assign('show_courses', self::showCourses());
        $tpl->assign('show_sessions', self::showSessions());
        $tpl->assign('show_tutor', api_get_setting('show_session_coach') === 'true');
        $tpl->assign('course_url', $courseUrl);
        $tpl->assign('catalog_pagination', $pagination);
        $tpl->assign('search_token', Security::get_token());
        $tpl->assign('search_date', $date);
        $tpl->assign('web_session_courses_ajax_url', api_get_path(WEB_AJAX_PATH).'course.ajax.php');
        $tpl->assign('sessions', $sessionsBlocks);
        $tpl->assign('already_subscribed_label', self::getAlreadyRegisteredInSessionLabel());
        $tpl->assign('catalog_settings', self::getCatalogSearchSettings());

        $contentTemplate = $tpl->get_template('catalog/session_catalog.tpl');

        $tpl->display($contentTemplate);
    }

    /**
     * Show the Session Catalogue with filtered session by course tags.
     *
     * @param array $limit Limit info
     */
    public static function sessionsListByName(array $limit)
    {
        $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : null;
        $courseUrl = self::getCatalogUrl(
            1,
            $limit['length'],
            null,
            'subscribe'
        );

        $count = self::getSessionsByName($keyword, [], true);
        $sessions = self::getSessionsByName($keyword, $limit);
        $sessionsBlocks = self::getFormattedSessionsBlock($sessions);
        $pagination = self::getSessionPagination('search_session_title', $count, $limit);

        $tpl = new Template();
        $tpl->assign('catalog_pagination', $pagination);
        $tpl->assign('actions', self::getTabList(2));
        $tpl->assign('show_courses', self::showCourses());
        $tpl->assign('show_sessions', self::showSessions());
        $tpl->assign('show_tutor', api_get_setting('show_session_coach') === 'true');
        $tpl->assign('course_url', $courseUrl);
        $tpl->assign('already_subscribed_label', self::getAlreadyRegisteredInSessionLabel());
        $tpl->assign('search_token', Security::get_token());
        $tpl->assign('keyword', Security::remove_XSS($keyword));
        $tpl->assign('sessions', $sessionsBlocks);
        $tpl->assign('catalog_settings', self::getCatalogSearchSettings());

        $contentTemplate = $tpl->get_template('catalog/session_catalog.tpl');

        $tpl->display($contentTemplate);
    }

    public static function getCatalogSearchSettings()
    {
        $settings = api_get_configuration_value('catalog_settings');
        if (empty($settings)) {
            // Default everything is visible
            $settings = [
                'sessions' => [
                    'by_title' => true,
                    'by_date' => true,
                    'by_tag' => true,
                    'show_session_info' => true,
                    'show_session_date' => true,
                ],
                'courses' => [
                    'by_title' => true,
                ],
            ];
        }

        return $settings;
    }

    /**
     * @param int $active
     *
     * @return string
     */
    public static function getTabList($active = 1)
    {
        $pageLength = isset($_GET['pageLength']) ? (int) $_GET['pageLength'] : self::PAGE_LENGTH;

        $url = self::getCatalogUrl(1, $pageLength, null, 'display_sessions');
        $headers = [];
        if (self::showCourses()) {
            $headers[] = [
                'url' => api_get_self(),
                'content' => get_lang('CourseManagement'),
            ];
        }

        if (self::showSessions()) {
            $headers[] = [
                'url' => $url,
                'content' => get_lang('SessionList'),
            ];
        }

        // If only one option hide menu.
        if (1 === count($headers)) {
            return '';
        }

        return Display::tabsOnlyLink($headers, $active);
    }

    /**
     * Show the Session Catalogue with filtered session by course tags.
     *
     * @param array $limit Limit info
     */
    public static function sessionsListByCoursesTag(array $limit)
    {
        $searchTag = isset($_REQUEST['search_tag']) ? $_REQUEST['search_tag'] : '';
        $searchDate = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d');
        $courseUrl = self::getCatalogUrl(
            1,
            $limit['length'],
            null,
            'subscribe'
        );

        $sessions = self::browseSessionsByTags($searchTag, $limit);
        $sessionsBlocks = self::getFormattedSessionsBlock($sessions);

        $count = self::browseSessionsByTags($searchTag, [], true);
        $pagination = self::getSessionPagination('search_tag', $count, $limit);

        $tpl = new Template();
        $tpl->assign('catalog_pagination', $pagination);
        $tpl->assign('show_courses', self::showCourses());
        $tpl->assign('show_sessions', self::showSessions());
        $tpl->assign('show_tutor', api_get_setting('show_session_coach') === 'true');
        $tpl->assign('course_url', $courseUrl);
        $tpl->assign('already_subscribed_label', self::getAlreadyRegisteredInSessionLabel());
        $tpl->assign('search_token', Security::get_token());
        $tpl->assign('search_date', Security::remove_XSS($searchDate));
        $tpl->assign('search_tag', Security::remove_XSS($searchTag));
        $tpl->assign('sessions', $sessionsBlocks);

        $contentTemplate = $tpl->get_template('catalog/session_catalog.tpl');

        $tpl->display($contentTemplate);
    }

    /**
     * @return array
     */
    public static function getLimitArray()
    {
        $pageCurrent = isset($_REQUEST['pageCurrent']) ? (int) $_GET['pageCurrent'] : 1;
        $pageLength = isset($_REQUEST['pageLength']) ? (int) $_GET['pageLength'] : self::PAGE_LENGTH;

        return [
            'start' => ($pageCurrent - 1) * $pageLength,
            'current' => $pageCurrent,
            'length' => $pageLength,
        ];
    }

    /**
     * Get the formatted data for sessions block to be displayed on Session Catalog page.
     *
     * @param array $sessions The session list
     *
     * @return array
     */
    public static function getFormattedSessionsBlock(array $sessions)
    {
        $extraFieldValue = new ExtraFieldValue('session');
        $userId = api_get_user_id();
        $sessionsBlocks = [];
        $entityManager = Database::getManager();
        $sessionRelCourseRepo = $entityManager->getRepository('ChamiloCoreBundle:SessionRelCourse');
        $extraFieldRepo = $entityManager->getRepository('ChamiloCoreBundle:ExtraField');
        $extraFieldRelTagRepo = $entityManager->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');

        $tagsField = $extraFieldRepo->findOneBy([
            'extraFieldType' => Chamilo\CoreBundle\Entity\ExtraField::COURSE_FIELD_TYPE,
            'variable' => 'tags',
        ]);

        /** @var \Chamilo\CoreBundle\Entity\Session $session */
        foreach ($sessions as $session) {
            $sessionDates = SessionManager::parseSessionDates([
                'display_start_date' => $session->getDisplayStartDate(),
                'display_end_date' => $session->getDisplayEndDate(),
                'access_start_date' => $session->getAccessStartDate(),
                'access_end_date' => $session->getAccessEndDate(),
                'coach_access_start_date' => $session->getCoachAccessStartDate(),
                'coach_access_end_date' => $session->getCoachAccessEndDate(),
            ]);

            $imageField = $extraFieldValue->get_values_by_handler_and_field_variable(
                $session->getId(),
                'image'
            );
            $sessionCourseTags = [];
            if (!is_null($tagsField)) {
                $sessionRelCourses = $sessionRelCourseRepo->findBy([
                    'session' => $session,
                ]);
                /** @var SessionRelCourse $sessionRelCourse */
                foreach ($sessionRelCourses as $sessionRelCourse) {
                    $courseTags = $extraFieldRelTagRepo->getTags(
                        $tagsField,
                        $sessionRelCourse->getCourse()->getId()
                    );
                    /** @var Tag $tag */
                    foreach ($courseTags as $tag) {
                        $sessionCourseTags[] = $tag->getTag();
                    }
                }
            }

            if (!empty($sessionCourseTags)) {
                $sessionCourseTags = array_unique($sessionCourseTags);
            }

            /** @var SequenceResourceRepository $repo */
            $repo = $entityManager->getRepository('ChamiloCoreBundle:SequenceResource');
            $sequences = $repo->getRequirementsAndDependenciesWithinSequences(
                $session->getId(),
                SequenceResource::SESSION_TYPE
            );

            $hasRequirements = false;
            foreach ($sequences as $sequence) {
                if (count($sequence['requirements']) === 0) {
                    continue;
                }
                $hasRequirements = true;
                break;
            }
            $cat = $session->getCategory();
            if (empty($cat)) {
                $cat = null;
                $catName = '';
            } else {
                $catName = $cat->getName();
            }

            $generalCoach = $session->getGeneralCoach();
            $coachId = $generalCoach ? $generalCoach->getId() : 0;
            $coachName = $generalCoach ? UserManager::formatUserFullName($session->getGeneralCoach()) : '';

            $actions = null;
            if (api_is_platform_admin()) {
                $actions = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$session->getId();
            }

            $plugin = \BuyCoursesPlugin::create();
            $isThisSessionOnSale = $plugin->getBuyCoursePluginPrice($session);

            $sessionsBlock = [
                'id' => $session->getId(),
                'name' => $session->getName(),
                'image' => isset($imageField['value']) ? $imageField['value'] : null,
                'nbr_courses' => $session->getNbrCourses(),
                'nbr_users' => $session->getNbrUsers(),
                'coach_id' => $coachId,
                'coach_url' => $generalCoach
                    ? api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&user_id='.$coachId
                    : '',
                'coach_name' => $coachName,
                'coach_avatar' => UserManager::getUserPicture($coachId, USER_IMAGE_SIZE_SMALL),
                'is_subscribed' => SessionManager::isUserSubscribedAsStudent($session->getId(), $userId),
                'icon' => self::getSessionIcon($session->getName()),
                'date' => $sessionDates['display'],
                'price' => !empty($isThisSessionOnSale['html']) ? $isThisSessionOnSale['html'] : '',
                'subscribe_button' => isset($isThisSessionOnSale['buy_button']) ? $isThisSessionOnSale['buy_button'] : self::getRegisteredInSessionButton(
                    $session->getId(),
                    $session->getName(),
                    $hasRequirements
                ),
                'show_description' => $session->getShowDescription(),
                'description' => $session->getDescription(),
                'category' => $catName,
                'tags' => $sessionCourseTags,
                'edit_actions' => $actions,
                'duration' => SessionManager::getDayLeftInSession(
                    ['id' => $session->getId(), 'duration' => $session->getDuration()],
                    $userId
                ),
            ];

            $sessionsBlocks[] = array_merge($sessionsBlock, $sequences);
        }

        return $sessionsBlocks;
    }

    /**
     * Get Pagination HTML div.
     *
     * @param int    $pageCurrent
     * @param int    $pageLength
     * @param int    $pageTotal
     * @param string $categoryCode
     * @param string $action
     * @param array  $fields
     * @param array  $sortKeys
     *
     * @return string
     */
    public static function getCatalogPagination(
        $pageCurrent,
        $pageLength,
        $pageTotal,
        $categoryCode = '',
        $action = '',
        $fields = [],
        $sortKeys = []
    ) {
        // Start empty html
        $pageDiv = '';
        $html = '';
        $pageBottom = max(1, $pageCurrent - 3);
        $pageTop = min($pageTotal, $pageCurrent + 3);

        if ($pageBottom > 1) {
            $pageDiv .= self::getPageNumberItem(1, $pageLength);
            if ($pageBottom > 2) {
                $pageDiv .= self::getPageNumberItem(
                    $pageBottom - 1,
                    $pageLength,
                    null,
                    '...',
                    $categoryCode,
                    $action,
                    $fields,
                    $sortKeys
                );
            }
        }

        // For each page add its page button to html
        for ($i = $pageBottom; $i <= $pageTop; $i++) {
            if ($i === $pageCurrent) {
                $pageItemAttributes = ['class' => 'active'];
            } else {
                $pageItemAttributes = [];
            }
            $pageDiv .= self::getPageNumberItem(
                $i,
                $pageLength,
                $pageItemAttributes,
                '',
                $categoryCode,
                $action,
                $fields,
                $sortKeys
            );
        }

        // Check if current page is the last page
        if ($pageTop < $pageTotal) {
            if ($pageTop < ($pageTotal - 1)) {
                $pageDiv .= self::getPageNumberItem(
                    $pageTop + 1,
                    $pageLength,
                    null,
                    '...',
                    $categoryCode,
                    $action,
                    $fields,
                    $sortKeys
                );
            }
            $pageDiv .= self::getPageNumberItem(
                $pageTotal,
                $pageLength,
                [],
                '',
                $categoryCode,
                $action,
                $fields,
                $sortKeys
            );
        }

        // Complete pagination html
        $pageDiv = Display::tag('ul', $pageDiv, ['class' => 'pagination']);
        $html .= '<nav>'.$pageDiv.'</nav>';

        return $html;
    }

    /**
     * Get li HTML of page number.
     *
     * @param $pageNumber
     * @param $pageLength
     * @param array  $liAttributes
     * @param string $content
     * @param string $categoryCode
     * @param string $action
     * @param array  $fields
     * @param array  $sortKeys
     *
     * @return string
     */
    public static function getPageNumberItem(
        $pageNumber,
        $pageLength,
        $liAttributes = [],
        $content = '',
        $categoryCode = '',
        $action = '',
        $fields = [],
        $sortKeys = []
    ) {
        // Get page URL
        $url = self::getCatalogUrl($pageNumber, $pageLength, $categoryCode, $action, $fields, $sortKeys);

        // If is current page ('active' class) clear URL
        if (isset($liAttributes) && is_array($liAttributes) && isset($liAttributes['class'])) {
            if (strpos('active', $liAttributes['class']) !== false) {
                $url = '';
            }
        }

        $content = !empty($content) ? $content : $pageNumber;

        return Display::tag(
            'li',
            Display::url(
                $content,
                $url
            ),
            $liAttributes
        );
    }

    /**
     * Return URL to course catalog.
     *
     * @param int    $pageCurrent
     * @param int    $pageLength
     * @param string $categoryCode
     * @param string $action
     * @param array  $extraFields
     * @param array  $sortKeys
     *
     * @return string
     */
    public static function getCatalogUrl(
        $pageCurrent,
        $pageLength,
        $categoryCode = null,
        $action = null,
        $extraFields = [],
        $sortKeys = []
    ) {
        $requestAction = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : '';
        $action = isset($action) ? Security::remove_XSS($action) : $requestAction;
        $searchTerm = isset($_REQUEST['search_term']) ? Security::remove_XSS($_REQUEST['search_term']) : '';
        $keyword = isset($_REQUEST['keyword']) ? Security::remove_XSS($_REQUEST['keyword']) : '';
        $searchTag = isset($_REQUEST['search_tag']) ? $_REQUEST['search_tag'] : '';

        if ($action === 'subscribe_user_with_password') {
            $action = 'subscribe';
        }

        $categoryCode = !empty($categoryCode) ? Security::remove_XSS($categoryCode) : 'ALL';

        // Start URL with params
        $pageUrl = api_get_self().
            '?action='.$action.
            '&search_term='.$searchTerm.
            '&keyword='.$keyword.
            '&search_tag='.$searchTag.
            '&category_code='.$categoryCode.
            '&pageCurrent='.$pageCurrent.
            '&pageLength='.$pageLength;

        if (!empty($extraFields)) {
            $params = [];
            foreach ($extraFields as $variable => $value) {
                $params[Security::remove_XSS($variable)] = Security::remove_XSS($value);
            }
            if (!empty($params)) {
                $pageUrl .= '&'.http_build_query($params);
            }
        }

        if (!empty($sortKeys)) {
            foreach ($sortKeys as $sortKey) {
                $pageUrl .= '&sortKeys%5B%5D='.Security::remove_XSS($sortKey);
            }
        }

        switch ($action) {
            case 'subscribe':
                // for search
                $pageUrl .=
                    '&sec_token='.Security::getTokenFromSession();
                break;
            case 'display_courses':
            default:
                break;
        }

        return $pageUrl;
    }
}
