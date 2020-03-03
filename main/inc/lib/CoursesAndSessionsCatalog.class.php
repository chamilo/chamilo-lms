<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
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

        foreach ($allCategories as $category) {
            if (empty($category['parent_id'])) {
                $list[$category['code']] = $category;
                $list[$category['code']]['level'] = 0;
                list($subList, $childrenCount) = self::buildCourseCategoryTree($allCategories, $category['code'], 0);
                foreach ($subList as $item) {
                    $list[$item['code']] = $item;
                }
                // Real course count
                $countCourses = CourseCategory::countCoursesInCategory($category['code']);
                $list[$category['code']]['number_courses'] = $childrenCount + $countCourses;
            }
        }

        // count courses that are in no category
        $countCourses = CourseCategory::countCoursesInCategory();
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
            $countCourses = CourseCategory::countCoursesInCategory($row['code']);
            $row['count_courses'] = $countCourses;
            if (empty($row['parent_id'])) {
                $categories[0][$row['tree_pos']] = $row;
            } else {
                $categories[$row['parent_id']][$row['tree_pos']] = $row;
            }
        }

        // count courses that are in no category
        $countCourses = CourseCategory::countCoursesInCategory();
        $categories[0][count($categories[0]) + 1] = [
            'id' => 0,
            'name' => get_lang('None'),
            'code' => 'NONE',
            'parent_id' => null,
            'tree_pos' => $row['tree_pos'] + 1,
            'children_count' => 0,
            'auth_course_child' => true,
            'auth_cat_child' => true,
            'count_courses' => $countCourses,
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
        $visibilityCondition = CourseManager::getCourseVisibilitySQLCondition('course', true);

        if (!empty($random_value)) {
            $random_value = (int) $random_value;

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

                $sql = "SELECT COUNT(*) FROM $tbl_course course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE access_url_id = '.$urlId.' ";
                $result = Database::query($sql);
                list($num_records) = Database::fetch_row($result);

                $sql = "SELECT course.id, course.id as real_id
                        FROM $tbl_course course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            $urlCondition AND
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
            $listCode = self::childrenCategories($category_code);
            $conditionCode = ' ';

            if (empty($listCode)) {
                if ($category_code === 'NONE') {
                    $conditionCode .= " category_code='' ";
                } else {
                    $conditionCode .= " category_code='$category_code' ";
                }
            } else {
                foreach ($listCode as $code) {
                    $conditionCode .= " category_code='$code' OR ";
                }
                $conditionCode .= " category_code='$category_code' ";
            }

            if (empty($category_code) || $category_code == 'ALL') {
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
                if ($category_code != 'ALL') {
                    $sql = "SELECT *, course.id real_id FROM $tbl_course as course
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
        $sql = "SELECT * FROM $courseTable course
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
            $urlId = api_get_current_access_url_id();
            if ($urlId != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

                $urlCondition = ' access_url_id = '.$urlId.' AND';
                $allowBaseCategories = api_get_configuration_value('allow_base_course_category');
                if ($allowBaseCategories) {
                    $urlCondition = ' (access_url_id = '.$urlId.' OR access_url_id = 1) AND ';
                }

                $sql = "SELECT course.*
                        FROM $courseTable as course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            access_url_id = $urlId AND
                            (
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
        $result_find = Database::query($sql);
        $courses = [];
        while ($row = Database::fetch_array($result_find)) {
            $row['registration_code'] = !empty($row['registration_code']);
            $countUsers = CourseManager::get_user_list_from_course_code(
                $row['code'],
                0,
                null,
                null,
                null,
                true
            );
            $connectionsLastMonth = Tracking::get_course_connections_count(
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
                'count_users' => $countUsers,
                'count_connections' => $connectionsLastMonth,
            ];
        }

        return $courses;
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

        /*$dql = "SELECT $select
                FROM ChamiloCoreBundle:Session s
                WHERE EXISTS
                    (
                        SELECT url.sessionId FROM ChamiloCoreBundle:AccessUrlRelSession url
                        WHERE url.sessionId = s.id AND url.accessUrlId = $urlId
                    ) AND
                    s.nbrCourses > 0
                ";
        if (!is_null($date)) {
            $date = Database::escape_string($date);
            $dql .= "
                AND (
                    (s.accessEndDate IS NULL)
                    OR
                    (
                    s.accessStartDate IS NOT NULL AND
                    s.accessEndDate IS NOT NULL AND
                    s.accessStartDate <= '$date' AND s.accessEndDate >= '$date')
                    OR
                    (
                        s.accessStartDate IS NULL AND
                        s.accessEndDate IS NOT NULL AND
                        s.accessEndDate >= '$date'
                    )
                )
            ";
        }*/

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

        if (!is_null($date)) {
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
    public static function browseSessionsByTags($termTag, array $limit)
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
            ->andWhere(
                $qb->expr()->gt('s.nbrCourses', 0)
            )
            ->andWhere(
                $qb->expr()->eq('url.accessUrlId', $urlId)
            )
            ->setFirstResult($limit['start'])
            ->setMaxResults($limit['length'])
            ->setParameter('tag', "$termTag%")
            ;

        $qb = self::hideFromSessionCatalogCondition($qb);

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
    public static function getSessionsByName($keyword, array $limit)
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
            ->andWhere(
                $qb->expr()->eq('url.accessUrlId', $urlId)
            )->andWhere(
                's.name LIKE :keyword'
            )
            ->andWhere(
                $qb->expr()->gt('s.nbrCourses', 0)
            )
            ->setFirstResult($limit['start'])
            ->setMaxResults($limit['length'])
            ->setParameter('keyword', "%$keyword%")
        ;

        $qb = self::hideFromSessionCatalogCondition($qb);

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

        if ($code != 'ALL' and $code != 'NONE') {
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
}
