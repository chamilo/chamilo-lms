<?php
/* For licensing terms, see /license.txt */

/**
 * Returns whether we are in a mode where multiple URLs are configured to work
 * with course categories
 * @return bool
 */
function isMultipleUrlSupport()
{
    global $_configuration;
    if (isset($_configuration['enable_multiple_url_support_for_course_category'])) {
        return $_configuration['enable_multiple_url_support_for_course_category'];
    }
    return false;
}

/**
 * Returns the category fields from the database from an int ID
 * @param int $categoryId The category ID
 * @return array
 */
function getCategoryById($categoryId)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $categoryId = intval($categoryId);
    $sql = "SELECT * FROM $tbl_category WHERE id = $categoryId";
    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        return Database::fetch_array($result, 'ASSOC');
    }
    return array();
}

/**
 * Get category details from a simple category code
 * @param string $category The literal category code
 * @return array
 */
function getCategory($category)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $category = Database::escape_string($category);
    $sql = "SELECT * FROM $tbl_category WHERE code ='$category'";
    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        return Database::fetch_array($result, 'ASSOC');
    }
    return array();
}

/**
 * @param string $category
 *
 * @return array
 */
function getCategories($category)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $category = Database::escape_string($category);
    $conditions = null;
    $whereCondition = '';

    if (isMultipleUrlSupport()) {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (t1.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
    }

    $parentIdCondition = " AND (t1.parent_id IS NULL OR t1.parent_id = '' )";
    if (!empty($category)) {
        $parentIdCondition =  " AND t1.parent_id  = '$category' ";
    }

    $sql = "SELECT
                t1.name,
                t1.code,
                t1.parent_id,
                t1.tree_pos,
                t1.children_count,
                COUNT(DISTINCT t3.code) AS nbr_courses
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

    $categories = Database::store_result($result);
    foreach ($categories as $category) {
        $category['nbr_courses'] =  1;
    }

    return $categories;
}


/**
 * @param string $code
 * @param string $name
 * @param string $canHaveCourses
 * @param int $parent_id
 *
 * @return bool
 */
function addNode($code, $name, $canHaveCourses, $parent_id)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $code = trim($code);
    $name = trim($name);
    $parent_id = trim($parent_id);

    $code = CourseManager::generate_course_code($code);
    $sql = "SELECT 1 FROM $tbl_category
            WHERE code = '".Database::escape_string($code)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        return false;
    }
    $result = Database::query("SELECT MAX(tree_pos) AS maxTreePos FROM $tbl_category");
    $row = Database::fetch_array($result);
    $tree_pos = $row['maxTreePos'] + 1;

    $params = [
        'name' => $name,
        'code' => $code,
        'parent_id' => empty($parent_id) ? '' : $parent_id,
        'tree_pos' => $tree_pos,
        'children_count' => 0,
        'auth_course_child' => $canHaveCourses,
        'auth_cat_child' => 'TRUE'
    ];

    $categoryId = Database::insert($tbl_category, $params);

    updateParentCategoryChildrenCount($parent_id, 1);

    if (isMultipleUrlSupport()) {
        addToUrl($categoryId);
    }

    return $categoryId;
}

/**
 * Recursive function that updates the count of children in the parent
 * @param string $categoryId Category ID
 * @param    int $delta      The number to add or delete (1 to add one, -1 to remove one)
 */
function updateParentCategoryChildrenCount($categoryId, $delta = 1)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $categoryId = Database::escape_string($categoryId);
    $delta = intval($delta);
    // First get to the highest level possible in the tree
    $result = Database::query("SELECT parent_id FROM $tbl_category WHERE code = '$categoryId'");
    $row = Database::fetch_array($result);
    if ($row !== false and $row['parent_id'] != 0) {
        // if a parent was found, enter there to see if he's got one more parent
        updateParentCategoryChildrenCount($row['parent_id'], $delta);
    }
    // Now we're at the top, get back down to update each child
    //$children_count = courseCategoryChildrenCount($categoryId);
    if ($delta >= 0) {
        $sql = "UPDATE $tbl_category SET children_count = (children_count + $delta)
                WHERE code = '$categoryId'";
    } else {
        $sql = "UPDATE $tbl_category SET children_count = (children_count - ".abs($delta).")
                WHERE code = '$categoryId'";
    }
    Database::query($sql);
}

/**
 * @param string $node
 */
function deleteNode($node)
{
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);

    $node = Database::escape_string($node);

    $result = Database::query("SELECT parent_id, tree_pos FROM $tbl_category WHERE code='$node'");

    if ($row = Database::fetch_array($result)) {
        if (!empty($row['parent_id'])) {
            Database::query("UPDATE $tbl_course SET category_code = '".$row['parent_id']."' WHERE category_code='$node'");
            Database::query("UPDATE $tbl_category SET parent_id='" . $row['parent_id'] . "' WHERE parent_id='$node'");
        } else {
            Database::query("UPDATE $tbl_course SET category_code='' WHERE category_code='$node'");
            Database::query("UPDATE $tbl_category SET parent_id=NULL WHERE parent_id='$node'");
        }

        Database::query("UPDATE $tbl_category SET tree_pos=tree_pos-1 WHERE tree_pos > '" . $row['tree_pos'] . "'");
        Database::query("DELETE FROM $tbl_category WHERE code='$node'");

        if (!empty($row['parent_id'])) {
            updateParentCategoryChildrenCount($row['parent_id'], -1);
        }
    }
}

/**
 * @param string $code
 * @param string $name
 * @param string $canHaveCourses
 * @param string $old_code
 * @return bool
 */
function editNode($code, $name, $canHaveCourses, $old_code)
{
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);

    $code = trim(Database::escape_string($code));
    $name = trim(Database::escape_string($name));
    $old_code = Database::escape_string($old_code);
    $canHaveCourses = Database::escape_string($canHaveCourses);

    $code = CourseManager::generate_course_code($code);
    // Updating category
    $sql = "UPDATE $tbl_category SET name='$name', code='$code', auth_course_child = '$canHaveCourses'
            WHERE code = '$old_code'";
    Database::query($sql);

    // Updating children
    $sql = "UPDATE $tbl_category SET parent_id = '$code'
            WHERE parent_id = '$old_code'";
    Database::query($sql);

    // Updating course category
    $sql = "UPDATE $tbl_course SET category_code = '$code'
            WHERE category_code = '$old_code' ";
    Database::query($sql);
    return true;
}

/**
 * Move a node up on display
 * @param string $code
 * @param int $tree_pos
 * @param string $parent_id
 *
 * @return bool
 */
function moveNodeUp($code, $tree_pos, $parent_id)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $code = Database::escape_string($code);
    $tree_pos = intval($tree_pos);
    $parent_id = Database::escape_string($parent_id);

    $parentIdCondition = " AND (parent_id IS NULL OR parent_id = '' )";
    if (!empty($parent_id)) {
        $parentIdCondition = " AND parent_id = '$parent_id' ";
    }

    $sql = "SELECT code,tree_pos
            FROM $tbl_category
            WHERE
                tree_pos < $tree_pos
                $parentIdCondition
            ORDER BY tree_pos DESC
            LIMIT 0,1";

    $result = Database::query($sql);
    if (!$row = Database::fetch_array($result)) {
        $sql = "SELECT code, tree_pos
                FROM $tbl_category
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

    $sql = "UPDATE $tbl_category
            SET tree_pos ='" . $row['tree_pos'] . "'
            WHERE code='$code'";
    Database::query($sql);

    $sql = "UPDATE $tbl_category
            SET tree_pos = '$tree_pos'
            WHERE code= '" . $row['code'] . "'";
    Database::query($sql);

    return true;
}

/**
 * Counts the number of children categories a category has
 * @param int   $categoryId The ID of the category of which we want to count the children
 * @param int   $count  The number of subcategories we counted this far
 * @return mixed The number of subcategories this category has
 */
function courseCategoryChildrenCount($categoryId)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $categoryId = intval($categoryId);
    $count = 0;
    if (empty($categoryId)) {
        return 0;
    }
    $sql = "SELECT id, code FROM $tbl_category WHERE parent_id = $categoryId";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $count += courseCategoryChildrenCount($row['id']);
    }
    $sql = "UPDATE $tbl_category SET children_count = $count WHERE id = $categoryId";
    Database::query($sql);
    return $count + 1;
}

/**
 * @param string $categoryCode
 *
 * @return array
 */
function getChildren($categoryCode)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $categoryCode = Database::escape_string($categoryCode);
    $result = Database::query("SELECT code, id FROM $tbl_category WHERE parent_id = '$categoryCode'");
    $children = array();
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $children[] = $row;
        $subChildren = getChildren($row['code']);
        $children = array_merge($children, $subChildren);
    }

    return $children;
}

/**
 * @param string $categoryCode
 *
 * @return array
 */
function getParents($categoryCode)
{
    if (empty($categoryCode)) {
        return array();
    }

    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $categoryCode = Database::escape_string($categoryCode);
    $sql = "SELECT code, parent_id FROM $tbl_category
            WHERE code = '$categoryCode'";

    $result = Database::query($sql);
    $children = array();
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $parent = getCategory($row['parent_id']);
        $children[] = $row;
        $subChildren = getParents($parent['code']);
        $children = array_merge($children, $subChildren);
    }
    return $children;
}

/**
 * @param string $categoryCode
 * @return null|string
 */
function getParentsToString($categoryCode)
{
    $parents = getParents($categoryCode);

    if (!empty($parents)) {
        $parents = array_reverse($parents);
        $categories = array();
        foreach ($parents as $category) {
            $categories[] = $category['code'];
        }
        $categoriesInString = implode(' > ', $categories).' > ';

        return $categoriesInString;
    }
    return null;
}

/**
 * @param string $categorySource
 * @return string
 */
function listCategories($categorySource)
{
    $categorySource = isset($categorySource) ? $categorySource : null;
    $categories = getCategories($categorySource);

    if (count($categories) > 0) {
        $table = new HTML_Table(array('class' => 'data_table'));
        $column = 0;
        $row = 0;
        $headers = array(
            get_lang('Category'), get_lang('CategoriesNumber'), get_lang('Courses'), get_lang('Actions')
        );
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }
        $row++;
        $mainUrl = api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$categorySource;

        $editIcon = Display::return_icon('edit.png', get_lang('EditNode'), null, ICON_SIZE_SMALL);
        $deleteIcon = Display::return_icon('delete.png', get_lang('DeleteNode'), null, ICON_SIZE_SMALL);
        $moveIcon =  Display::return_icon('up.png', get_lang('UpInSameLevel'), null, ICON_SIZE_SMALL);

        foreach ($categories as $category) {

            $editUrl = $mainUrl.'&id='.$category['code'].'&action=edit';
            $moveUrl  = $mainUrl.'&id='.$category['code'].'&action=moveUp&tree_pos='.$category['tree_pos'];
            $deleteUrl = $mainUrl.'&id='.$category['code'].'&action=delete';

            $actions = Display::url($editIcon, $editUrl).Display::url($moveIcon, $moveUrl).Display::url($deleteIcon, $deleteUrl);
            $url = api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$category['code'];
            $title = Display::url(
                Display::return_icon('folder_document.gif', get_lang('OpenNode'), null, ICON_SIZE_SMALL).' '.$category['name'],
                $url
            );
            $content = array(
                $title,
                $category['children_count'],
                $category['nbr_courses'],
                $actions
            );
            $column = 0;
            foreach ($content as $value) {
                $table->setCellContents($row, $column, $value);
                $column++;
            }
            $row++;
        }

        return $table->toHtml();
    } else {
        return Display::return_message(get_lang("NoCategories"), 'warning');
    }
}

/**
 * @return array
 */
function getCategoriesToDisplayInHomePage()
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $sql = "SELECT name FROM $tbl_category WHERE parent_id IS NULL ORDER BY tree_pos";
    return Database::store_result(Database::query($sql));
}

/**
 * @param int $id
 * @return bool
 */
function addToUrl($id)
{
    if (!isMultipleUrlSupport()) {
        return false;
    }
    UrlManager::addCourseCategoryListToUrl(array($id), array(api_get_current_access_url_id()));
}

/**
 * @param string $categoryCode
 * @return array
 */
function getCategoriesCanBeAddedInCourse($categoryCode)
{
    $conditions = null;
    $whereCondition = null;
    if (isMultipleUrlSupport()) {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (c.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
    }

    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $sql = "SELECT code, name
            FROM $tbl_category c
            $conditions
            WHERE (auth_course_child = 'TRUE' OR code = '".Database::escape_string($categoryCode)."')
                   $whereCondition
            ORDER BY tree_pos";
    $res = Database::query($sql);

    $categories[''] = '-';
    while ($cat = Database::fetch_array($res)) {
        $categories[$cat['code']] = '('.$cat['code'].') '.$cat['name'];
        ksort($categories);
    }
    return $categories;
}

/**
 * @return array
 */
function browseCourseCategories()
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $conditions = null;
    $whereCondition = null;

    if (isMultipleUrlSupport()) {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (c.id = a.course_category_id)";
        $whereCondition = " WHERE a.access_url_id = ".api_get_current_access_url_id();
    }
    $sql = "SELECT c.* FROM $tbl_category c
            $conditions
            $whereCondition
            ORDER BY tree_pos ASC";
    $result = Database::query($sql);
    $url_access_id = 1;
    if (api_is_multiple_url_enabled()) {
        $url_access_id = api_get_current_access_url_id();
    }
    $countCourses = CourseManager :: countAvailableCourses($url_access_id);

    $categories = array();
    $categories[0][0] = array(
        'id' => 0,
        'name' => get_lang('DisplayAll'),
        'code' => 'ALL',
        'parent_id' => null,
        'tree_pos' => 0,
        'count_courses' => $countCourses

    );
    while ($row = Database::fetch_array($result)) {
        $count_courses = countCoursesInCategory($row['code']);
        $row['count_courses'] = $count_courses;
        if (!isset($row['parent_id'])) {
            $categories[0][$row['tree_pos']] = $row;
        } else {
            $categories[$row['parent_id']][$row['tree_pos']] = $row;
        }
    }

    $count_courses = countCoursesInCategory();

    $categories[0][count($categories[0])+1] = array(
        'id' =>0,
        'name' => get_lang('None'),
        'code' => 'NONE',
        'parent_id' => null,
        'tree_pos' => $row['tree_pos']+1,
        'children_count' => 0,
        'auth_course_child' => true,
        'auth_cat_child' => true,
        'count_courses' => $count_courses
    );

    return $categories;
}

/**
 * @param string $category_code
 * @param string $searchTerm
 * @return int
 */
function countCoursesInCategory($category_code="", $searchTerm = '')
{
    global $_configuration;
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $categoryCode = Database::escape_string($category_code);
    $searchTerm = Database::escape_string($searchTerm);
    $categoryFilter = '';
    $searchFilter = '';

    $specialCourseList = CourseManager::get_special_course_list();

    $without_special_courses = '';
    if (!empty($specialCourseList)) {
        $without_special_courses = ' AND course.code NOT IN (' . implode(',', $specialCourseList) . ')';
    }

    $visibilityCondition = null;
    $hidePrivate = api_get_setting('course_catalog_hide_private');
    if ($hidePrivate === 'true') {
        $courseInfo = api_get_course_info();
        $courseVisibility = $courseInfo['visibility'];
        $visibilityCondition = ' AND course.visibility <> 1';
    }

    if ($categoryCode == 'ALL') {
        // Nothing to do
    } elseif ($categoryCode == 'NONE') {
        $categoryFilter =  ' AND category_code = "" ';
    } else {
        $categoryFilter =  ' AND category_code = "' . $categoryCode . '" ';
    }

    if (!empty($searchTerm)) {
        $searchFilter = ' AND (code LIKE "%' . $searchTerm . '%"
            OR title LIKE "%' . $searchTerm . '%"
            OR tutor_name LIKE "%' . $searchTerm . '%") ';
    }

    $sql = "SELECT * FROM $tbl_course
            WHERE
                visibility != '0' AND
                visibility != '4'
                $categoryFilter
                $searchFilter
                $without_special_courses
                $visibilityCondition
            ";
    // Showing only the courses of the current portal access_url_id.

    if (api_is_multiple_url_enabled()) {
        $url_access_id = api_get_current_access_url_id();
        if ($url_access_id != -1) {
            $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $sql = "SELECT * FROM $tbl_course as course
                    INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.c_id = course.id)
                    WHERE
                        access_url_id = $url_access_id AND
                        course.visibility != '0' AND
                        course.visibility != '4' AND
                        category_code = '$category_code'
                        $searchTerm
                        $without_special_courses
                        $visibilityCondition
                    ";
        }
    }

    return Database::num_rows(Database::query($sql));
}

/**
 * @param string $category_code
 * @param int $random_value
 * @param array $limit will be used if $random_value is not set.
 * This array should contains 'start' and 'length' keys
 * @return array
 */
function browseCoursesInCategory($category_code, $random_value = null, $limit = array())
{
    global $_configuration;
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

    $specialCourseList = CourseManager::get_special_course_list();

    $without_special_courses = '';
    if (!empty($specialCourseList)) {
        $without_special_courses = ' AND course.code NOT IN (' . implode(',', $specialCourseList) . ')';
    }
    $visibilityCondition = null;
    $hidePrivate = api_get_setting('course_catalog_hide_private');
    if ($hidePrivate === 'true') {
        $courseInfo = api_get_course_info();
        $courseVisibility = $courseInfo['visibility'];
        $visibilityCondition = ' AND course.visibility <> 1';
    }
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

            $sql = "SELECT course.id FROM $tbl_course course
                    INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.c_id = course.id)
                    WHERE
                        access_url_id = $url_access_id AND
                        RAND()*$num_records< $random_value
                        $without_special_courses $visibilityCondition
                    ORDER BY RAND()
                    LIMIT 0, $random_value";
        } else {
            $sql = "SELECT id FROM $tbl_course course
                    WHERE RAND()*$num_records< $random_value $without_special_courses $visibilityCondition
                    ORDER BY RAND()
                    LIMIT 0, $random_value";
        }

        $result = Database::query($sql);
        $id_in = null;
        while (list($id) = Database::fetch_row($result)) {
            if ($id_in) {
                $id_in.=",$id";
            } else {
                $id_in = "$id";
            }
        }
        if ($id_in === null) {
            return array();
        }
        $sql = "SELECT * FROM $tbl_course WHERE id IN($id_in)";
    } else {
        $limitFilter = getLimitFilterFromArray($limit);
        $category_code = Database::escape_string($category_code);
        if (empty($category_code) || $category_code == "ALL") {
            $sql = "SELECT * FROM $tbl_course
                    WHERE
                        1=1
                        $without_special_courses
                        $visibilityCondition
                    ORDER BY title $limitFilter ";
        } else {
            if ($category_code == 'NONE') {
                $category_code = '';
            }
            $sql = "SELECT * FROM $tbl_course
                    WHERE
                        category_code='$category_code'
                        $without_special_courses
                        $visibilityCondition
                    ORDER BY title $limitFilter ";
        }

        //showing only the courses of the current Chamilo access_url_id
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            if ($category_code != "ALL") {
                $sql = "SELECT * FROM $tbl_course as course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            access_url_id = $url_access_id AND
                            category_code='$category_code'
                            $without_special_courses
                            $visibilityCondition
                        ORDER BY title $limitFilter";
            } else {
                $sql = "SELECT * FROM $tbl_course as course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            access_url_id = $url_access_id
                            $without_special_courses
                            $visibilityCondition
                        ORDER BY title $limitFilter";
            }
        }
    }

    $result = Database::query($sql);
    $courses = array();
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
        $courses[] = array(
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
            'count_connections' => $count_connections_last_month
        );
    }

    return $courses;
}

/**
 * create recursively all categories as option of the select passed in parameter.
 *
 * @param HTML_QuickForm_Element $element
 * @param string $defaultCode the option value to select by default (used mainly for edition of courses)
 * @param string $parentCode the parent category of the categories added (default=null for root category)
 * @param string $padding the indent param (you shouldn't indicate something here)
 */
function setCategoriesInForm($element, $defaultCode = null, $parentCode = null, $padding = null)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $conditions = null;
    $whereCondition = null;
    if (isMultipleUrlSupport()) {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (c.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
    }

    $sql = "SELECT code, name, auth_course_child, auth_cat_child
            FROM ".$tbl_category." c
            $conditions
            WHERE parent_id ".(empty($parentCode) ? "IS NULL" : "='".Database::escape_string($parentCode)."'")."
            $whereCondition
            ORDER BY name,  code";
    $res = Database::query($sql);

    while ($cat = Database::fetch_array($res, 'ASSOC')) {
        $params = $cat['auth_course_child'] == 'TRUE' ? '' : 'disabled';
        $params .= ($cat['code'] == $defaultCode) ? ' selected' : '';
        $option = $padding.' '.$cat['name'].' ('.$cat['code'].')';

        $element->addOption($option, $cat['code'], $params);
        if ($cat['auth_cat_child'] == 'TRUE') {
            setCategoriesInForm($element, $defaultCode, $cat['code'], $padding.' - ');
        }
    }
}

/**
 * @param array $list
 * @return array
 */
function getCourseCategoryNotInList($list)
{
    $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
    if (empty($list)) {
        return array();
    }

    $list = array_map('intval', $list);
    $listToString = implode("','", $list);

    $sql = "SELECT * FROM $table WHERE id NOT IN ('$listToString') AND (parent_id IS NULL) ";
    $result = Database::query($sql);
    return Database::store_result($result, 'ASSOC');
}

/**
 * @param string $keyword
 * @return array|null
 */
function searchCategoryByKeyword($keyword)
{
    if (empty($keyword)) {
        return null;
    }

    $tableCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $conditions = null;
    $whereCondition = null;
    if (isMultipleUrlSupport()) {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (c.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
    }

    $keyword = Database::escape_string($keyword);

    $sql = "SELECT c.*, c.name as text
            FROM $tableCategory c $conditions
            WHERE
                (
                    c.code LIKE '%$keyword%' OR name LIKE '%$keyword%'
                ) AND
                auth_course_child = 'TRUE'
                $whereCondition ";
    $result = Database::query($sql);
    return Database::store_result($result, 'ASSOC');
}

/**
 * @param array $list
 * @return array
 */
function searchCategoryById($list)
{
    if (empty($list)) {
        return array();
    } else {
        $list = array_map('intval', $list);
        $list = implode("','", $list);
    }

    $tableCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $conditions = null;
    $whereCondition = null;
    if (isMultipleUrlSupport()) {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (c.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
    }

    $sql = "SELECT c.*, c.name as text FROM $tableCategory c $conditions
            WHERE c.id IN $list $whereCondition";
    $result = Database::query($sql);
    return Database::store_result($result, 'ASSOC');
}

/**
 * @return array
 */
function getLimitArray()
{
    $pageCurrent = isset($_REQUEST['pageCurrent']) ?
        intval($_GET['pageCurrent']) :
        1;
    $pageLength = isset($_REQUEST['pageLength']) ?
        intval($_GET['pageLength']) :
        10;
    return array(
        'start' => ($pageCurrent - 1) * $pageLength,
        'current' => $pageCurrent,
        'length' => $pageLength,
    );
}

/**
 * Return LIMIT to filter SQL query
 * @param array $limit
 * @return string
 */
function getLimitFilterFromArray($limit)
{
    $limitFilter = '';
    if (!empty($limit) && is_array($limit)) {
        $limitStart = isset($limit['start']) ? $limit['start'] : 0;
        $limitLength = isset($limit['length']) ? $limit['length'] : 10;
        $limitFilter = 'LIMIT ' . $limitStart . ', ' . $limitLength;
    }

    return $limitFilter;
}

/**
 * Get Pagination HTML div
 * @param $pageCurrent
 * @param $pageLength
 * @param $pageTotal
 * @return string
 */
function getCataloguePagination($pageCurrent, $pageLength, $pageTotal)
{
    // Start empty html
    $pageDiv = '';
    $html='';
    $pageBottom = max(1, $pageCurrent - 3);
    $pageTop = min($pageTotal, $pageCurrent + 3);

    if ($pageBottom > 1) {
        $pageDiv .= getPageNumberItem(1, $pageLength);
        if ($pageBottom > 2) {
            $pageDiv .= getPageNumberItem($pageBottom - 1, $pageLength, null, '...');
        }
    } else {
        // Nothing to do
    }

    // For each page add its page button to html
    for (
        $i = $pageBottom;
        $i <= $pageTop;
        $i++
    ) {
        if ($i === $pageCurrent) {
            $pageItemAttributes = array('class' => 'active');
        } else {
            $pageItemAttributes = array();
        }
        $pageDiv .= getPageNumberItem($i, $pageLength, $pageItemAttributes);

    }
    // Check if current page is the last page

    if ($pageTop < $pageTotal) {
        if ($pageTop < ($pageTotal - 1)) {
            $pageDiv .= getPageNumberItem($pageTop + 1, $pageLength, null, '...');
        }
        $pageDiv .= getPageNumberItem($pageTotal, $pageLength);
    } else {
        // Nothing to do
    }

    // Complete pagination html
    $pageDiv = Display::tag('ul',$pageDiv,array('class' => 'pagination'));


    $html.='<nav>'.$pageDiv.'</nav>';
    return $html;
}

/**
 * Return URL to course catalog
 * @param int $pageCurrent
 * @param int $pageLength
 * @param string $categoryCode
 * @param int $hiddenLinks
 * @param string $action
 * @return string
 */
function getCourseCategoryUrl(
    $pageCurrent,
    $pageLength,
    $categoryCode = null,
    $hiddenLinks = null,
    $action = null
) {
    $requestAction = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : null;
    $action = isset($action) ? Security::remove_XSS($action) : $requestAction;
    $searchTerm = isset($_REQUEST['search_term']) ? Security::remove_XSS($_REQUEST['search_term']) : null;

    $categoryCodeRequest = isset($_REQUEST['category_code']) ? Security::remove_XSS($_REQUEST['category_code']) : null;
    $categoryCode = isset($categoryCode) ? Security::remove_XSS($categoryCode) : $categoryCodeRequest;

    $hiddenLinksRequest = isset($_REQUEST['hidden_links']) ? Security::remove_XSS($_REQUEST['hidden_links']) : null;
    $hiddenLinks = isset($hiddenLinks) ? Security::remove_XSS($hiddenLinksRequest) : $categoryCodeRequest;

        // Start URL with params
    $pageUrl = api_get_self() .
        '?action=' . $action .
        '&category_code=' .$categoryCode.
        '&hidden_links=' .$hiddenLinks.
        '&pageCurrent=' . $pageCurrent .
        '&pageLength=' . $pageLength
    ;

    switch ($action) {
        case 'subscribe' :
            // for search
            $pageUrl .=
                '&search_term=' . $searchTerm .
                '&search_course=1' .
                '&sec_token=' . $_SESSION['sec_token'];
            break;
        case 'display_courses' :
            // No break
        default :
            break;

    }

    return $pageUrl;
}

/**
 * Get li HTML of page number
 * @param $pageNumber
 * @param $pageLength
 * @param array $liAttributes
 * @param string $content
 * @return string
 */
function getPageNumberItem($pageNumber, $pageLength, $liAttributes = array(), $content = '')
{
    // Get page URL
    $url = getCourseCategoryUrl(
        $pageNumber,
        $pageLength
    );

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
 * Return the name tool by action
 * @param string $action
 * @return string
 */
function getCourseCatalogNameTools($action)
{


    $nameTools = get_lang('SortMyCourses');
    if (empty($action)) {
        return $nameTools; //should never happen
    }

    switch ($action) {
        case 'createcoursecategory' :
            $nameTools = get_lang('CreateCourseCategory');
            break;
        case 'subscribe' :
            $nameTools = get_lang('CourseManagement');
            break;
        case 'subscribe_user_with_password' :
            $nameTools = get_lang('CourseManagement');
            break;
        case 'display_random_courses' :
            // No break
        case 'display_courses' :
            $nameTools = get_lang('CourseManagement');
            break;
        case 'display_sessions' :
            $nameTools = get_lang('Sessions');
            break;
        default :
            // Nothing to do
            break;
    }

    return $nameTools;
}

/**
 CREATE TABLE IF NOT EXISTS access_url_rel_course_category (access_url_id int unsigned NOT NULL, course_category_id int unsigned NOT NULL, PRIMARY KEY (access_url_id, course_category_id));
 */
