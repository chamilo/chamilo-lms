<?php
/* For licensing terms, see /license.txt */

function isMultipleUrlSupport()
{
    global $_configuration;
    if (isset($_configuration['enable_multiple_url_support_for_course_category'])) {
        return $_configuration['enable_multiple_url_support_for_course_category'];
    }
    return false;
}

/**
 * @param int $categoryId
 * @return array
 */
function getCategoryById($categoryId)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $categoryId = Database::escape_string($categoryId);
    $sql = "SELECT * FROM $tbl_category WHERE id = '$categoryId'";
    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        return Database::fetch_array($result, 'ASSOC');
    }
    return array();
}

/**
 * @param string $category
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
 * @return array
 */
function getCategories($category)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $category = Database::escape_string($category);
    $conditions = null;
    $whereCondition = null;
    if (isMultipleUrlSupport()) {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (t1.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
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
			 	LEFT JOIN $tbl_category t2 ON t1.code=t2.parent_id
			 	LEFT JOIN $tbl_course t3 ON t3.category_code=t1.code
				WHERE
				    t1.parent_id " . (empty($category) ? "IS NULL" : "='$category'") . "
				    $whereCondition
				GROUP BY t1.name,
                         t1.code,
                         t1.parent_id,
                         t1.tree_pos,
                         t1.children_count
				ORDER BY t1.tree_pos";
    $result = Database::query($sql);
    return Database::store_result($result);
}


/**
 * @param string $code
 * @param string $name
 * @param string $canHaveCourses
 * @param int $parent_id
 * @return bool
 */
function addNode($code, $name, $canHaveCourses, $parent_id)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $code = trim(Database::escape_string($code));
    $name = trim(Database::escape_string($name));
    $parent_id = Database::escape_string($parent_id);
    $canHaveCourses = Database::escape_string($canHaveCourses);

    $code = generate_course_code($code);

    $result = Database::query("SELECT 1 FROM $tbl_category WHERE code='$code'");
    if (Database::num_rows($result)) {
        return false;
    }

    $result = Database::query("SELECT MAX(tree_pos) AS maxTreePos FROM $tbl_category");
    $row = Database::fetch_array($result);
    $tree_pos = $row['maxTreePos'] + 1;

    $sql = "INSERT INTO $tbl_category(name, code, parent_id, tree_pos, children_count, auth_course_child)
            VALUES('$name','$code'," .(empty($parent_id) ? "NULL" : "'$parent_id'") . ",'$tree_pos','0','$canHaveCourses')";
    Database::query($sql);
    $categoryId = Database::insert_id();

    updateCategoryChildren($parent_id);

    if (isMultipleUrlSupport()) {
        addToUrl($categoryId);
    }
    return $categoryId;
}

/**
 * @param string $category
 */
function updateCategoryChildren($category)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $category = Database::escape_string($category);
    $result = Database::query("SELECT parent_id FROM $tbl_category WHERE code='$category'");

    if ($row = Database::fetch_array($result)) {
        updateCategoryChildren($row['parent_id']);
    }

    $children_count = compterFils($category, 0) - 1;
    Database::query("UPDATE $tbl_category SET children_count='$children_count' WHERE code='$category'");
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
            updateCategoryChildren($row['parent_id']);
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

    $code = generate_course_code($code);
    // Updating category
    $sql = "UPDATE $tbl_category SET name='$name', code='$code', auth_course_child = '$canHaveCourses'
            WHERE code = '$old_code'";
    Database::query($sql);

    // Updating children
    $sql = "UPDATE $tbl_category SET parent_id = '$code'
            WHERE parent_id = '$old_code'";
    Database::query($sql);

    // Updating course category
    $sql = "UPDATE $tbl_course SET category_code = '$code' WHERE category_code = '$old_code' ";
    Database::query($sql);
    return true;
}

/**
 * @param string $code
 * @param string $tree_pos
 * @param int $parent_id
 * @return bool
 */
function moveNodeUp($code, $tree_pos, $parent_id)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $code = Database::escape_string($code);
    $tree_pos = Database::escape_string($tree_pos);
    $parent_id = Database::escape_string($parent_id);
    $sql = "SELECT code,tree_pos
            FROM $tbl_category
            WHERE parent_id " . (empty($parent_id) ? "IS NULL" : "='$parent_id'") . " AND tree_pos<'$tree_pos'
            ORDER BY tree_pos DESC LIMIT 0,1";
    $result = Database::query($sql);
    if (!$row = Database::fetch_array($result)) {

        $sql = "SELECT code,tree_pos FROM $tbl_category
                WHERE parent_id " . (empty($parent_id) ? "IS NULL" : "='$parent_id'") . " AND tree_pos>'$tree_pos'
                ORDER BY tree_pos DESC LIMIT 0,1";
        $result2 = Database::query($sql);
        if (!$row2 = Database::fetch_array($result2)) {
            return false;
        }
    }

    Database::query("UPDATE $tbl_category SET tree_pos='" . $row['tree_pos'] . "' WHERE code='$code'");
    Database::query("UPDATE $tbl_category SET tree_pos='$tree_pos' WHERE code='$row[code]'");
}

/**
 * @param $pere
 * @param $cpt
 * @return mixed
 */
function compterFils($pere, $cpt)
{
    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $pere = Database::escape_string($pere);
    $result = Database::query("SELECT code FROM $tbl_category WHERE parent_id='$pere'");

    while ($row = Database::fetch_array($result)) {
        $cpt = compterFils($row['code'], $cpt);
    }
    return ($cpt + 1);
}

/**
 * @param string $categoryCode
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

function getParents($categoryCode)
{
    if (empty($categoryCode)) {
        return array();
    }

    $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $categoryCode = Database::escape_string($categoryCode);
    $sql = "SELECT code, parent_id FROM $tbl_category WHERE code = '$categoryCode'";

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
 * @return int
 */
function countCoursesInCategory($category_code="")
{
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $TABLE_COURSE_FIELD = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
    $TABLE_COURSE_FIELD_VALUE = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

    // get course list auto-register
    $sql = "SELECT course_code
            FROM $TABLE_COURSE_FIELD_VALUE tcfv
            INNER JOIN $TABLE_COURSE_FIELD tcf ON tcfv.field_id = tcf.id
            WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";

    $special_course_result = Database::query($sql);
    if (Database::num_rows($special_course_result) > 0) {
        $special_course_list = array();
        while ($result_row = Database::fetch_array($special_course_result)) {
            $special_course_list[] = '"' . $result_row['course_code'] . '"';
        }
    }

    $without_special_courses = '';
    if (!empty($special_course_list)) {
        $without_special_courses = ' AND course.code NOT IN (' . implode(',', $special_course_list) . ')';
    }

    $sql = "SELECT * FROM $tbl_course
            WHERE visibility != '0' AND visibility != '4' AND category_code" . "='" . $category_code . "'" . $without_special_courses;
    // Showing only the courses of the current portal access_url_id.

    if (api_is_multiple_url_enabled()) {
        $url_access_id = api_get_current_access_url_id();
        if ($url_access_id != -1) {
            $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $sql = "SELECT * FROM $tbl_course as course
                    INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.course_code=course.code)
                    WHERE access_url_id = $url_access_id AND course.visibility != '0' AND course.visibility != '4' AND category_code" . "='" . $category_code . "'" . $without_special_courses;
        }
    }
    return Database::num_rows(Database::query($sql));
}

/**
 * @param string $category_code
 * @param string $random_value
 * @return array
 */
function browseCoursesInCategory($category_code, $random_value = null)
{
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $TABLE_COURSE_FIELD = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
    $TABLE_COURSE_FIELD_VALUE = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

    // Get course list auto-register
    $sql = "SELECT course_code
            FROM $TABLE_COURSE_FIELD_VALUE tcfv
            INNER JOIN $TABLE_COURSE_FIELD tcf ON tcfv.field_id = tcf.id
            WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";

    $special_course_result = Database::query($sql);
    if (Database::num_rows($special_course_result) > 0) {
        $special_course_list = array();
        while ($result_row = Database::fetch_array($special_course_result)) {
            $special_course_list[] = '"' . $result_row['course_code'] . '"';
        }
    }

    $without_special_courses = '';
    if (!empty($special_course_list)) {
        $without_special_courses = ' AND course.code NOT IN (' . implode(',', $special_course_list) . ')';
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
                    INNER JOIN $tbl_url_rel_course as url_rel_course ON (url_rel_course.course_code=course.code)
                    WHERE access_url_id = $url_access_id ";
            $result = Database::query($sql);
            list($num_records) = Database::fetch_row($result);

            $sql = "SELECT course.id FROM $tbl_course course INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.course_code=course.code)
                        WHERE   access_url_id = $url_access_id AND
                                RAND()*$num_records< $random_value
                                $without_special_courses
                     ORDER BY RAND() LIMIT 0, $random_value";
        } else {
            $sql = "SELECT id FROM $tbl_course course
                    WHERE RAND()*$num_records< $random_value $without_special_courses
                    ORDER BY RAND() LIMIT 0, $random_value";
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
        $sql = "SELECT * FROM $tbl_course WHERE id IN($id_in)";
    } else {
        $category_code = Database::escape_string($category_code);
        if (empty($category_code) || $category_code == "ALL") {
            $sql = "SELECT * FROM $tbl_course WHERE 1=1 $without_special_courses ORDER BY title ";
        } else {
            if ($category_code == 'NONE') {
                $category_code = '';
            }
            $sql = "SELECT * FROM $tbl_course WHERE category_code='$category_code' $without_special_courses ORDER BY title ";
        }

        //showing only the courses of the current Chamilo access_url_id
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            if ($category_code != "ALL") {
                $sql = "SELECT * FROM $tbl_course as course INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.course_code=course.code)
                    WHERE access_url_id = $url_access_id AND category_code='$category_code' $without_special_courses
                    ORDER BY title";    
            } else{
                $sql = "SELECT * FROM $tbl_course as course INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.course_code=course.code)
                    WHERE access_url_id = $url_access_id $without_special_courses
                    ORDER BY title";
            }
            
        }
    }

    $result = Database::query($sql);
    $courses = array();
    while ($row = Database::fetch_array($result)) {
        $row['registration_code'] = !empty($row['registration_code']);
        $count_users = CourseManager::get_users_count_in_course($row['code']);
        $count_connections_last_month = Tracking::get_course_connections_count($row['code'], 0, api_get_utc_datetime(time() - (30 * 86400)));

        if ($row['tutor_name'] == '0') {
            $row['tutor_name'] = get_lang('NoManager');
        }
        $point_info = CourseManager::get_course_ranking($row['id'], 0);
        $courses[] = array(
            'real_id' => $row['id'],
            'point_info' => $point_info,
            'code' => $row['code'],
            'directory' => $row['directory'],
            'db' => $row['db_name'],
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

    $sql = "SELECT c.*, c.name as text FROM $tableCategory c $conditions
            WHERE (c.code LIKE '%$keyword%' or name LIKE '%$keyword%') AND auth_course_child = 'TRUE' $whereCondition ";
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
 CREATE TABLE IF NOT EXISTS access_url_rel_course_category (access_url_id int unsigned NOT NULL, course_category_id int unsigned NOT NULL, PRIMARY KEY (access_url_id, course_category_id));
 */
