<?php
/* For licensing terms, see /license.txt */

$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);

function isMultipleUrlSupport()
{
    global $_configuration;
    if (isset($_configuration['allow_multiple_url_for_course_category'])) {
        return $_configuration['allow_multiple_url_for_course_category'];
    }
    return false;
}

function getCategory($category)
{
    global $tbl_category;
    $category = Database::escape_string($category);
    $sql = "SELECT * FROM $tbl_category WHERE code ='$category'";
    $result = Database::query($sql);
    if (Database::num_rows($result)) {
        return Database::fetch_array($result, 'ASSOC');
    }
    return array();
}

/**
 * @param $category
 * @return array
 */
function getCategories($category)
{
    global $tbl_category, $tbl_course;
    $category = Database::escape_string($category);
    $sql = "SELECT t1.name,t1.code,t1.parent_id,t1.tree_pos,t1.children_count,COUNT(DISTINCT t3.code) AS nbr_courses
			 	FROM $tbl_category t1 LEFT JOIN $tbl_category t2 ON t1.code=t2.parent_id LEFT JOIN $tbl_course t3 ON t3.category_code=t1.code
				WHERE t1.parent_id " . (empty($category) ? "IS NULL" : "='$category'") . "
				GROUP BY t1.name,t1.code,t1.parent_id,t1.tree_pos,t1.children_count ORDER BY t1.tree_pos";
    $result = Database::query($sql);
    return Database::store_result($result);
}

function deleteNode($node)
{
    global $tbl_category, $tbl_course;
    $node = Database::escape_string($node);

    $result = Database::query("SELECT parent_id,tree_pos FROM $tbl_category WHERE code='$node'");

    if ($row = Database::fetch_array($result)) {
        if (!empty($row['parent_id'])) {
            Database::query("UPDATE $tbl_course SET category_code='" . $row['parent_id'] . "' WHERE category_code='$node'");
            Database::query("UPDATE $tbl_category SET parent_id='" . $row['parent_id'] . "' WHERE parent_id='$node'");
        } else {
            Database::query("UPDATE $tbl_course SET category_code='' WHERE category_code='$node'");
            Database::query("UPDATE $tbl_category SET parent_id=NULL WHERE parent_id='$node'");
        }

        Database::query("UPDATE $tbl_category SET tree_pos=tree_pos-1 WHERE tree_pos > '" . $row['tree_pos'] . "'");
        Database::query("DELETE FROM $tbl_category WHERE code='$node'");

        if (!empty($row['parent_id'])) {
            updateFils($row['parent_id']);
        }
    }
}

function addNode($code, $name, $canHaveCourses, $parent_id)
{
    global $tbl_category;
    $code = trim(Database::escape_string($code));
    $name = trim(Database::escape_string($name));
    $parent_id = Database::escape_string($parent_id);
    $canHaveCourses = Database::escape_string($canHaveCourses);

    $result = Database::query("SELECT 1 FROM $tbl_category WHERE code='$code'");
    if (Database::num_rows($result)) {
        return false;
    }

    $result = Database::query("SELECT MAX(tree_pos) AS maxTreePos FROM $tbl_category");
    $row = Database::fetch_array($result);
    $tree_pos = $row['maxTreePos'] + 1;
    $code = generate_course_code($code);
    $sql = "INSERT INTO $tbl_category(name,code,parent_id,tree_pos,children_count,auth_course_child)
            VALUES('$name','$code'," . (empty($parent_id) ? "NULL" : "'$parent_id'") . ",'$tree_pos','0','$canHaveCourses')";
    Database::query($sql);
    updateFils($parent_id);
    return true;
}

function editNode($code, $name, $canHaveCourses, $old_code)
{
    global $tbl_category, $tbl_course;

    $code = trim(Database::escape_string($code));
    $name = trim(Database::escape_string($name));
    $old_code = Database::escape_string($old_code);
    $canHaveCourses = Database::escape_string($canHaveCourses);

    if ($code != $old_code) {
        $result = Database::query("SELECT 1 FROM $tbl_category WHERE code='$code'");
        if (Database::num_rows($result)) {
            return false;
        }
    }
    $code = generate_course_code($code);
    $sql = "UPDATE $tbl_category SET name='$name', code='$code', auth_course_child = '$canHaveCourses'
    WHERE code='$old_code'";
    Database::query($sql);
    var_dump($sql);

    $sql = "UPDATE $tbl_course SET category_code = '$code' WHERE category_code = '$old_code' ";
    Database::query($sql);
    return true;
}

function moveNodeUp($code, $tree_pos, $parent_id)
{
    global $tbl_category;
    $code = Database::escape_string($code);
    $tree_pos = Database::escape_string($tree_pos);
    $parent_id = Database::escape_string($parent_id);

    $result = Database::query("SELECT code,tree_pos FROM $tbl_category WHERE parent_id " . (empty($parent_id) ? "IS NULL" : "='$parent_id'") . " AND tree_pos<'$tree_pos' ORDER BY tree_pos DESC LIMIT 0,1");

    if (!$row = Database::fetch_array($result)) {
        $result = Database::query("SELECT code,tree_pos FROM $tbl_category WHERE parent_id " . (empty($parent_id) ? "IS NULL" : "='$parent_id'") . " AND tree_pos>'$tree_pos' ORDER BY tree_pos DESC LIMIT 0,1");

        if (!$row = Database::fetch_array($result)) {
            return false;
        }
    }

    Database::query("UPDATE $tbl_category SET tree_pos='" . $row['tree_pos'] . "' WHERE code='$code'");
    Database::query("UPDATE $tbl_category SET tree_pos='$tree_pos' WHERE code='$row[code]'");
}

function updateFils($category)
{
    global $tbl_category;
    $category = Database::escape_string($category);
    $result = Database::query("SELECT parent_id FROM $tbl_category WHERE code='$category'");

    if ($row = Database::fetch_array($result)) {
        updateFils($row['parent_id']);
    }

    $children_count = compterFils($category, 0) - 1;
    Database::query("UPDATE $tbl_category SET children_count='$children_count' WHERE code='$category'");
}

function compterFils($pere, $cpt)
{
    global $tbl_category;
    $pere = Database::escape_string($pere);
    $result = Database::query("SELECT code FROM $tbl_category WHERE parent_id='$pere'");

    while ($row = Database::fetch_array($result)) {
        $cpt = compterFils($row['code'], $cpt);
    }
    return ($cpt + 1);
}


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
            $deleteUrl = $mainUrl.'&id='.$category['code'].'&action=delete';
            $moveUrl  = $mainUrl.'&id='.$category['code'].'&action=moveUp&tree_pos='.$category['tree_pos'];

            $actions = Display::url($editIcon, $editUrl).Display::url($deleteIcon, $deleteUrl).Display::url($moveIcon, $moveUrl);
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
