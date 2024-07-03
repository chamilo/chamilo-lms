<?php

/* For licensing terms, see /license.txt */

/**
 * Class CourseCategory.
 */
class CourseCategory
{
    /**
     * Returns the category fields from the database from an int ID.
     *
     * @param int $categoryId The category ID
     *
     * @return array
     */
    public static function getCategoryById($categoryId)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $categoryId = (int) $categoryId;
        $sql = "SELECT * FROM $table WHERE id = $categoryId";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::fetch_array($result, 'ASSOC');
        }

        return [];
    }

    /**
     * Get category details from a simple category code.
     *
     * @param string $categoryCode The literal category code
     *
     * @return array
     */
    public static function getCategory($categoryCode)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $categoryCode = Database::escape_string($categoryCode);
        $sql = "SELECT * FROM $table WHERE code ='$categoryCode'";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $category = Database::fetch_array($result, 'ASSOC');
            if ($category) {
                // Get access url id
                $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
                $sql = "SELECT * FROM $table WHERE course_category_id = ".$category['id'];
                $result = Database::query($sql);
                $result = Database::fetch_array($result);
                if ($result) {
                    $category['access_url_id'] = $result['access_url_id'];
                }

                return $category;
            }
        }

        return [];
    }

    /**
     * @param string $category Optional. Parent category code
     *
     * @return array
     */
    public static function getCategories($category = '')
    {
        $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $category = Database::escape_string($category);

        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (t1.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
        $allowBaseCategories = api_get_configuration_value('allow_base_course_category');
        if ($allowBaseCategories) {
            $whereCondition = " AND (a.access_url_id = ".api_get_current_access_url_id()." OR a.access_url_id = 1) ";
        }

        $parentIdCondition = " AND (t1.parent_id IS NULL OR t1.parent_id = '' )";
        if (!empty($category)) {
            $parentIdCondition = " AND t1.parent_id = '$category' ";
        }

        $sql = "SELECT
                t1.id,
                t1.name,
                t1.code,
                t1.parent_id,
                t1.tree_pos,
                t1.children_count,
                COUNT(DISTINCT t3.code) AS nbr_courses,
                a.access_url_id
                FROM $tbl_category t1
                $conditions
                LEFT JOIN $tbl_category t2
                ON t1.code = t2.parent_id
                LEFT JOIN $tbl_course t3
                ON t3.category_code=t1.code
                WHERE
                    1 = 1
                    $parentIdCondition
                    $whereCondition
                GROUP BY t1.name,
                         t1.code,
                         t1.parent_id,
                         t1.tree_pos,
                         t1.children_count
                ORDER BY t1.tree_pos";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * Returns a flat list of all course categories in this URL. If the
     * allow_base_course_category option is true, then also show the
     * course categories of the base URL.
     *
     * @return array [id, name, code, parent_id, tree_pos, children_count, number_courses]
     */
    public static function getAllCategories()
    {
        $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (t1.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
        $allowBaseCategories = api_get_configuration_value('allow_base_course_category');
        if ($allowBaseCategories) {
            $whereCondition = " AND (a.access_url_id = ".api_get_current_access_url_id()." OR a.access_url_id = 1) ";
        }

        $sql = "SELECT
                t1.id,
                t1.name,
                t1.code,
                t1.parent_id,
                t1.tree_pos,
                t1.children_count,
                COUNT(DISTINCT t3.code) AS number_courses
                FROM $tbl_category t1
                $conditions
                LEFT JOIN $tbl_course t3
                ON t3.category_code=t1.code
                WHERE 1=1
                    $whereCondition
                GROUP BY
                    t1.name,
                    t1.code,
                    t1.parent_id,
                    t1.tree_pos,
                    t1.children_count
                ORDER BY t1.parent_id, t1.tree_pos";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param string $code
     * @param string $name
     * @param string $canHaveCourses
     * @param int    $parent_id
     *
     * @return bool
     */
    public static function addNode($code, $name, $canHaveCourses, $parent_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $code = trim($code);
        $name = trim($name);
        $parent_id = trim($parent_id);

        $code = CourseManager::generate_course_code($code);
        $sql = "SELECT 1 FROM $table
                WHERE code = '".Database::escape_string($code)."'";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return false;
        }
        $result = Database::query("SELECT MAX(tree_pos) AS maxTreePos FROM $table");
        $row = Database::fetch_array($result);
        $tree_pos = $row['maxTreePos'] + 1;

        $params = [
            'name' => html_filter($name),
            'code' => $code,
            'parent_id' => empty($parent_id) ? null : $parent_id,
            'tree_pos' => $tree_pos,
            'children_count' => 0,
            'auth_course_child' => $canHaveCourses,
            'auth_cat_child' => 'TRUE',
        ];

        $categoryId = Database::insert($table, $params);
        if ($categoryId) {
            self::updateParentCategoryChildrenCount($parent_id, 1);
            UrlManager::addCourseCategoryListToUrl(
                [$categoryId],
                [api_get_current_access_url_id()]
            );

            return $categoryId;
        }

        return false;
    }

    /**
     * Recursive function that updates the count of children in the parent.
     *
     * @param string $categoryId Category ID
     * @param int    $delta      The number to add or delete (1 to add one, -1 to remove one)
     */
    public static function updateParentCategoryChildrenCount($categoryId, $delta = 1)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $categoryId = Database::escape_string($categoryId);
        $delta = (int) $delta;
        // First get to the highest level possible in the tree
        $result = Database::query("SELECT parent_id FROM $table WHERE code = '$categoryId'");
        $row = Database::fetch_array($result);
        if ($row !== false and $row['parent_id'] != 0) {
            // if a parent was found, enter there to see if he's got one more parent
            self::updateParentCategoryChildrenCount($row['parent_id'], $delta);
        }
        // Now we're at the top, get back down to update each child
        $sql = "UPDATE $table SET children_count = (children_count - ".abs($delta).") WHERE code = '$categoryId'";
        if ($delta >= 0) {
            $sql = "UPDATE $table SET children_count = (children_count + $delta) WHERE code = '$categoryId'";
        }
        Database::query($sql);
    }

    /**
     * @param string $node
     *
     * @return bool
     */
    public static function deleteNode($node)
    {
        $category = self::getCategory($node);

        if (empty($category)) {
            return false;
        }

        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $node = Database::escape_string($node);
        $result = Database::query("SELECT parent_id, tree_pos FROM $tbl_category WHERE code='$node'");

        if ($row = Database::fetch_array($result)) {
            if (!empty($row['parent_id'])) {
                Database::query(
                    "UPDATE $tbl_course SET category_code = '".$row['parent_id']."' WHERE category_code='$node'"
                );
                Database::query("UPDATE $tbl_category SET parent_id='".$row['parent_id']."' WHERE parent_id='$node'");
            } else {
                Database::query("UPDATE $tbl_course SET category_code='' WHERE category_code='$node'");
                Database::query("UPDATE $tbl_category SET parent_id=NULL WHERE parent_id='$node'");
            }

            $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
            $sql = "DELETE FROM $table WHERE course_category_id = ".$category['id'];

            Database::query($sql);
            Database::query("UPDATE $tbl_category SET tree_pos=tree_pos-1 WHERE tree_pos > '".$row['tree_pos']."'");
            Database::query("DELETE FROM $tbl_category WHERE code='$node'");

            if (!empty($row['parent_id'])) {
                self::updateParentCategoryChildrenCount($row['parent_id'], -1);
            }

            return true;
        }
    }

    /**
     * @param string $code
     * @param string $name
     * @param string $canHaveCourses
     * @param string $old_code
     *
     * @return bool
     */
    public static function editNode(
        $code,
        $name,
        $canHaveCourses,
        $old_code,
        ?string $newParentCode = null,
        ?string $oldParentCode = null
    ) {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);

        $code = CourseManager::generate_course_code($code);
        $name = html_filter($name);

        $code = CourseManager::generate_course_code($code);
        // Updating category
        Database::update(
            $tbl_category,
            [
                'name' => $name,
                'code' => $code,
                'auth_course_child' => $canHaveCourses,
            ],
            ['code = ?' => $old_code]
        );

        // Updating children
        Database::update(
            $tbl_category,
            ['parent_id' => $code],
            ['parent_id = ?' => $old_code]
        );

        // Updating course category
        Database::update(
            $tbl_course,
            ['category_code' => $code],
            ['category_code = ?' => $old_code]
        );

        Database::update(
            $tbl_category,
            ['parent_id' => $newParentCode ?: null],
            ['code = ?' => $code]
        );

        self::updateParentCategoryChildrenCount($oldParentCode, -1);
        self::updateParentCategoryChildrenCount($newParentCode, 1);

        return true;
    }

    /**
     * Move a node up on display.
     *
     * @param string $code
     * @param int    $tree_pos
     * @param string $parent_id
     *
     * @return bool
     */
    public static function moveNodeUp($code, $tree_pos, $parent_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $code = Database::escape_string($code);
        $tree_pos = (int) $tree_pos;
        $parent_id = Database::escape_string($parent_id);

        $parentIdCondition = " AND (parent_id IS NULL OR parent_id = '' )";
        if (!empty($parent_id)) {
            $parentIdCondition = " AND parent_id = '$parent_id' ";
        }

        $sql = "SELECT code,tree_pos
                FROM $table
                WHERE
                    tree_pos < $tree_pos
                    $parentIdCondition
                ORDER BY tree_pos DESC
                LIMIT 0,1";

        $result = Database::query($sql);
        if (!$row = Database::fetch_array($result)) {
            $sql = "SELECT code, tree_pos
                    FROM $table
                    WHERE
                        tree_pos > $tree_pos
                        $parentIdCondition
                    ORDER BY tree_pos DESC
                    LIMIT 0,1";
            $result2 = Database::query($sql);
            if (!$row = Database::fetch_array($result2)) {
                return false;
            }
        }

        $sql = "UPDATE $table
                SET tree_pos ='".$row['tree_pos']."'
                WHERE code='$code'";
        Database::query($sql);

        $sql = "UPDATE $table
                SET tree_pos = '$tree_pos'
                WHERE code= '".$row['code']."'";
        Database::query($sql);

        return true;
    }

    /**
     * @param string $categoryCode
     *
     * @return array
     */
    public static function getChildren($categoryCode)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $categoryCode = Database::escape_string($categoryCode);
        $sql = "SELECT code, id FROM $table
                WHERE parent_id = '$categoryCode'";
        $result = Database::query($sql);
        $children = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $children[] = $row;
            $subChildren = self::getChildren($row['code']);
            $children = array_merge($children, $subChildren);
        }

        return $children;
    }

    /**
     * @param string $categoryCode
     *
     * @return array
     */
    public static function getParents($categoryCode)
    {
        if (empty($categoryCode)) {
            return [];
        }

        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $categoryCode = Database::escape_string($categoryCode);
        $sql = "SELECT code, parent_id
                FROM $table
                WHERE code = '$categoryCode'";

        $result = Database::query($sql);
        $children = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $parent = self::getCategory($row['parent_id']);
            $children[] = $row;
            $subChildren = self::getParents($parent ? $parent['code'] : null);
            $children = array_merge($children, $subChildren);
        }

        return $children;
    }

    /**
     * @param string $categoryCode
     *
     * @return string|null
     */
    public static function getParentsToString($categoryCode)
    {
        $parents = self::getParents($categoryCode);

        if (!empty($parents)) {
            $parents = array_reverse($parents);
            $categories = [];
            foreach ($parents as $category) {
                $categories[] = $category['code'];
            }

            return implode(' > ', $categories).' > ';
        }

        return null;
    }

    /**
     * @param string $categorySource
     *
     * @return string
     */
    public static function listCategories($categorySource)
    {
        $categories = self::getCategories($categorySource);
        $categorySource = Security::remove_XSS($categorySource);

        if (count($categories) > 0) {
            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
            $column = 0;
            $row = 0;
            $headers = [
                get_lang('Category'),
                get_lang('SubCat'),
                get_lang('Courses'),
                get_lang('Actions'),
            ];
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;
            $mainUrl = api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$categorySource;
            $ajaxUrl = api_get_path(WEB_AJAX_PATH).'course_category.ajax.php';
            $editIcon = Display::return_icon(
                'edit.png',
                get_lang('EditNode'),
                null,
                ICON_SIZE_SMALL
            );
            $exportIcon = Display::return_icon('export_csv.png', get_lang('ExportAsCSV'));

            $deleteIcon = Display::return_icon(
                'delete.png',
                get_lang('DeleteNode'),
                null,
                ICON_SIZE_SMALL
            );
            $moveIcon = Display::return_icon(
                'up.png',
                get_lang('UpInSameLevel'),
                null,
                ICON_SIZE_SMALL
            );

            $showCoursesIcon = Display::return_icon(
                'course.png',
                get_lang('Courses'),
                null,
                ICON_SIZE_SMALL
            );

            $urlId = api_get_current_access_url_id();
            foreach ($categories as $category) {
                $categoryId = $category['id'];
                $editUrl = $mainUrl.'&id='.$category['code'].'&action=edit';
                $moveUrl = $mainUrl.'&id='.$category['code'].'&action=moveUp&tree_pos='.$category['tree_pos'];
                $deleteUrl = $mainUrl.'&id='.$category['code'].'&action=delete';
                $exportUrl = $mainUrl.'&id='.$categoryId.'&action=export';
                $showCoursesUrl = $ajaxUrl.'?id='.$categoryId.'&a=show_courses';

                $actions = [];
                if ($urlId == $category['access_url_id']) {
                    $actions[] = Display::url(
                        $showCoursesIcon,
                        $showCoursesUrl,
                        ['onclick' => 'showCourses(this, '.$categoryId.')']
                    );
                    $actions[] = Display::url($editIcon, $editUrl);
                    $actions[] = Display::url($moveIcon, $moveUrl);
                    $actions[] = Display::url($exportIcon, $exportUrl);
                    $actions[] = Display::url(
                        $deleteIcon,
                        $deleteUrl,
                        ['onclick' => 'javascript: if (!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('ConfirmYourChoice')), ENT_QUOTES)).'\')) return false;']
                    );
                }

                $url = api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$category['code'];
                $title = Display::url(
                    Display::return_icon(
                        'folder_document.gif',
                        get_lang('OpenNode'),
                        null,
                        ICON_SIZE_SMALL
                    ).' '.$category['name'].' ('.$category['code'].')',
                    $url
                );

                $countCourses = self::countCoursesInCategory($category['code'], null, false, false);

                $content = [
                    $title,
                    $category['children_count'],
                    $countCourses,
                    implode('', $actions),
                ];
                $column = 0;
                foreach ($content as $value) {
                    $table->setCellContents($row, $column, $value);
                    $column++;
                }
                $row++;
            }

            return $table->toHtml();
        }

        return Display::return_message(get_lang('NoCategories'), 'warning');
    }

    /**
     * @return array
     */
    public static function getCategoriesToDisplayInHomePage()
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT name FROM $table
                WHERE parent_id IS NULL
                ORDER BY tree_pos";

        return Database::store_result(Database::query($sql));
    }

    /**
     * @param string $categoryCode
     *
     * @return array
     */
    public static function getCategoriesCanBeAddedInCourse($categoryCode)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (c.id = a.course_category_id)";
        $whereCondition = ' AND a.access_url_id = '.api_get_current_access_url_id();

        $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT code, name
                FROM $tbl_category c
                $conditions
                WHERE (auth_course_child = 'TRUE' OR code = '".Database::escape_string($categoryCode)."')
                $whereCondition
                ORDER BY tree_pos";
        $res = Database::query($sql);

        $categoryToAvoid = '';
        if (!api_is_platform_admin()) {
            $categoryToAvoid = api_get_configuration_value('course_category_code_to_use_as_model');
        }

        $categories[''] = '-';
        while ($cat = Database::fetch_array($res)) {
            $categoryCode = $cat['code'];
            if (!empty($categoryToAvoid) && $categoryToAvoid == $categoryCode) {
                continue;
            }
            $categories[$categoryCode] = '('.$categoryCode.') '.$cat['name'];
            ksort($categories);
        }

        return $categories;
    }

    /**
     * @param string $category_code
     * @param string $keyword
     * @param bool   $avoidCourses
     * @param bool   $checkHidePrivate
     * @param array  $conditions
     * @param string $courseLanguageFilter
     *
     * @return int
     */
    public static function countCoursesInCategory(
        $category_code = '',
        $keyword = '',
        $avoidCourses = true,
        $checkHidePrivate = true,
        $conditions = [],
        $courseLanguageFilter = null,
        $filterShowInCatalogue = false
    ) {
        return self::getCoursesInCategory(
            $category_code,
            $keyword,
            $avoidCourses,
            $checkHidePrivate,
            $conditions,
            true,
            $courseLanguageFilter,
            $filterShowInCatalogue
        );
    }

    public static function getCoursesInCategory(
        $category_code = '',
        $keyword = '',
        $avoidCourses = true,
        $checkHidePrivate = true,
        $conditions = [],
        $getCount = false,
        $courseLanguageFilter = null,
        $filterShowInCatalogue = false
    ) {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $categoryCode = Database::escape_string($category_code);
        $keyword = Database::escape_string($keyword);

        $avoidCoursesCondition = '';
        if ($avoidCourses) {
            $avoidCoursesCondition = CoursesAndSessionsCatalog::getAvoidCourseCondition();
        }
        $visibilityCondition = CourseManager::getCourseVisibilitySQLCondition('course', true, $checkHidePrivate);

        $showInCatalogueCondition = '';
        if ($filterShowInCatalogue) {
            $showInCatalogueCondition = CoursesAndSessionsCatalog::getCoursesToShowInCatalogueCondition();
        }

        $sqlInjectJoins = '';
        $courseLanguageWhere = '';
        $where = ' AND 1 = 1 ';
        $sqlInjectWhere = '';
        if (!empty($conditions)) {
            $sqlInjectJoins = $conditions['inject_joins'];
            $where = $conditions['where'];
            $sqlInjectWhere = $conditions['inject_where'];
        }

        // If have courseLanguageFilter, search for it
        if (!empty($courseLanguageFilter)) {
            $courseLanguageFilter = Database::escape_string($courseLanguageFilter);
            $courseLanguageWhere = "AND course.course_language = '$courseLanguageFilter'";
        }

        $categoryFilter = '';
        if ($categoryCode === 'ALL' || empty($categoryCode)) {
            // Nothing to do
        } elseif ($categoryCode === 'NONE') {
            $categoryFilter = ' AND category_code = "" ';
        } else {
            $categoryFilter = ' AND category_code = "'.$categoryCode.'" ';
        }

        $searchFilter = '';
        if (!empty($keyword)) {
            $searchFilter = ' AND (
                code LIKE "%'.$keyword.'%" OR
                title LIKE "%'.$keyword.'%" OR
                tutor_name LIKE "%'.$keyword.'%"
            ) ';
        }

        $urlCondition = ' access_url_id = '.api_get_current_access_url_id().' AND';
        $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $select = " DISTINCT course.id, course.code, course.title, course.category_code ";
        if ($getCount) {
            $select = "count(DISTINCT course.id) as count";
        }
        $sql = "SELECT $select
                FROM $tbl_course as course
                INNER JOIN $tbl_url_rel_course as url_rel_course
                ON (url_rel_course.c_id = course.id)
                $sqlInjectJoins
                WHERE
                    $urlCondition
                    course.visibility != '0' AND
                    course.visibility != '4'
                    $courseLanguageWhere
                    $categoryFilter
                    $searchFilter
                    $avoidCoursesCondition
                    $showInCatalogueCondition
                    $visibilityCondition
                    $where
                    $sqlInjectWhere
            ";

        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result);

            return (int) $row['count'];
        }

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param array $list
     *
     * @return array
     */
    public static function getCourseCategoryNotInList($list)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);

        if (empty($list)) {
            $sql = "SELECT * FROM $table
                    WHERE (parent_id IS NULL) ";
            $result = Database::query($sql);

            return Database::store_result($result, 'ASSOC');
        }

        $list = array_map('intval', $list);
        $listToString = implode("','", $list);

        $sql = "SELECT * FROM $table
                WHERE id NOT IN ('$listToString') AND (parent_id IS NULL) ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param string $keyword
     *
     * @return array|null
     */
    public static function searchCategoryByKeyword($keyword)
    {
        if (empty($keyword)) {
            return null;
        }

        $tableCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (c.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();

        $allowBaseCategories = api_get_configuration_value('allow_base_course_category');
        if ($allowBaseCategories) {
            $whereCondition = " AND (a.access_url_id = ".api_get_current_access_url_id()." OR a.access_url_id = 1) ";
        }

        $keyword = Database::escape_string($keyword);

        $sql = "SELECT c.*, c.name as text
                FROM $tableCategory c $conditions
                WHERE
                (
                    c.code LIKE '%$keyword%' OR name LIKE '%$keyword%'
                ) AND auth_course_child = 'TRUE'
                $whereCondition ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * Return the name tool by action.
     *
     * @param string $action
     *
     * @return string
     */
    public static function getCourseCatalogNameTools($action)
    {
        $nameTools = get_lang('MyCourses');
        if (empty($action)) {
            return $nameTools; //should never happen
        }

        switch ($action) {
            case 'subscribe':
            case 'subscribe_user_with_password':
            case 'display_random_courses':
            case 'display_courses':
                $nameTools = get_lang('CourseManagement');
                break;
            case 'display_sessions':
                $nameTools = get_lang('Sessions');
                break;
            default:
                // Nothing to do
                break;
        }

        return $nameTools;
    }

    /**
     * Save image for a course category.
     *
     * @param int   $categoryId Course category ID
     * @param array $fileData   File data from $_FILES
     */
    public static function saveImage($categoryId, $fileData)
    {
        $categoryInfo = self::getCategoryById($categoryId);
        if (empty($categoryInfo)) {
            return;
        }

        if (!empty($fileData['error'])) {
            return;
        }

        $extension = getextension($fileData['name']);
        $dirName = 'course_category/';
        $fileDir = api_get_path(SYS_UPLOAD_PATH).$dirName;
        $fileName = "cc_$categoryId.{$extension[0]}";

        if (!file_exists($fileDir)) {
            mkdir($fileDir, api_get_permissions_for_new_directories(), true);
        }

        $image = new Image($fileData['tmp_name']);
        $image->send_image($fileDir.$fileName);

        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        Database::update(
            $table,
            ['image' => $dirName.$fileName],
            ['id = ?' => $categoryId]
        );
    }

    /**
     * @param $categoryId
     * @param string $description
     *
     * @return string
     */
    public static function saveDescription($categoryId, $description)
    {
        $categoryInfo = self::getCategoryById($categoryId);
        if (empty($categoryInfo)) {
            return false;
        }
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        Database::update(
            $table,
            ['description' => $description],
            ['id = ?' => $categoryId]
        );

        return true;
    }
}
