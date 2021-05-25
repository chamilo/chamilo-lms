<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\CourseCategory as CourseCategoryEntity;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CourseCategory.
 */
class CourseCategory
{

    /**
     * Get category details from a simple category code.
     *
     * @param string|null $categoryCode The literal category code
     *
     * @return array
     */
    public static function getCategory(string $categoryCode = null)
    {
        if (!empty($categoryCode)) {
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
        }

        return [];
    }

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
                ON t3.category_id=t1.id
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
     * @param null|int    $parentId
     */
    public static function add($code, $name, $canHaveCourses, $description = '', $parentId = null): ?CourseCategoryEntity
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $code = trim($code);
        $name = trim($name);
        $parentId = (int) $parentId;

        $code = CourseManager::generate_course_code($code);
        $sql = "SELECT 1 FROM $table
                WHERE code = '".Database::escape_string($code)."'";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return null;
        }
        $result = Database::query("SELECT MAX(tree_pos) AS maxTreePos FROM $table");
        $row = Database::fetch_array($result);
        $tree_pos = $row['maxTreePos'] + 1;
        $parentId = empty($parentId) ? null : $parentId;

        $repo = Container::getCourseCategoryRepository();
        $category = new CourseCategoryEntity();
        $category
            ->setName($name)
            ->setCode($code)
            ->setDescription($description)
            ->setTreePos($tree_pos)
            ->setAuthCourseChild($canHaveCourses)
            ->setAuthCatChild('TRUE');

        if (!empty($parentId)) {
            $category->setParent($repo->find($parentId));
        }

        $repo->save($category);

        $categoryId = $category->getId();
        if ($categoryId) {
            self::updateParentCategoryChildrenCount($parentId, 1);
            UrlManager::addCourseCategoryListToUrl(
                [$categoryId],
                [api_get_current_access_url_id()]
            );

            return $category;
        }

        return null;
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
        $result = Database::query("SELECT parent_id FROM $table WHERE id = '$categoryId'");
        $row = Database::fetch_array($result);
        if (false !== $row && 0 != $row['parent_id']) {
            // if a parent was found, enter there to see if he's got one more parent
            self::updateParentCategoryChildrenCount($row['parent_id'], $delta);
        }
        // Now we're at the top, get back down to update each child
        $sql = "UPDATE $table SET children_count = (children_count - ".abs($delta).") WHERE id = '$categoryId'";
        if ($delta >= 0) {
            $sql = "UPDATE $table SET children_count = (children_count + $delta) WHERE id = '$categoryId'";
        }
        Database::query($sql);
    }

    public static function delete($categoryId): bool
    {
        $repo = Container::getCourseCategoryRepository();
        $category = $repo->find($categoryId);
        if (null === $category) {
            return false;
        }

        $repo->delete($category);

        return true;

        // @todo check that doctrine deletes all the connections.
        /*$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $node = Database::escape_string($node);
        $result = Database::query("SELECT parent_id, tree_pos FROM $tbl_category WHERE code='$node'");

        if ($row = Database::fetch_array($result)) {
            if (!empty($row['parent_id'])) {
                Database::query(
                    "UPDATE $tbl_course SET category_id = '".$row['parent_id']."' WHERE category_id = {$category['id']}"
                );
                Database::query("UPDATE $tbl_category SET parent_id='".$row['parent_id']."' WHERE parent_id='$node'");
            } else {
                Database::query("UPDATE $tbl_course SET category_id = NULL WHERE category_id = ".$category['id']);
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
        }*/
    }

    public static function edit($categoryId, $name, $canHaveCourses, $code, $description): CourseCategoryEntity
    {
        $name = trim(Database::escape_string($name));
        $canHaveCourses = Database::escape_string($canHaveCourses);

        $repo = Container::getCourseCategoryRepository();
        $category = $repo->find($categoryId);

        $category
            ->setCode($name)
            ->setName($name)
            ->setDescription($description)
            ->setAuthCourseChild($canHaveCourses);

        $repo->save($category);

        // Updating children
        /*$sql = "UPDATE $tbl_category SET parent_id = '$code'
            WHERE parent_id = '$old_code'";
        Database::query($sql);*/

        return $category;
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

    public static function listCategories(array $categorySource = []): string
    {
        $categories = self::getCategories($categorySource ? $categorySource['id'] : null);
        $categoryCode = $categorySource ? Security::remove_XSS($categorySource['code']) : '';

        if (count($categories) > 0) {
            $table = new HTML_Table(['class' => 'data_table']);
            $column = 0;
            $row = 0;
            $headers = [
                get_lang('Category'),
                get_lang('Sub-categories'),
                get_lang('Courses'),
                get_lang('Detail'),
            ];
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;
            $mainUrl = api_get_path(WEB_CODE_PATH).'admin/course_category.php?category='.$categoryCode;

            $editIcon = Display::return_icon(
                'edit.png',
                get_lang('Edit this category'),
                null,
                ICON_SIZE_SMALL
            );
            $deleteIcon = Display::return_icon(
                'delete.png',
                get_lang('Delete this category'),
                null,
                ICON_SIZE_SMALL
            );
            $moveIcon = Display::return_icon(
                'up.png',
                get_lang('Up in same level'),
                null,
                ICON_SIZE_SMALL
            );

            $urlId = api_get_current_access_url_id();
            foreach ($categories as $category) {
                $categoryId = $category->getId();
                $code = $category->getCode();
                $editUrl = $mainUrl.'&id='.$categoryId.'&action=edit';
                $moveUrl = $mainUrl.'&id='.$categoryId.'&action=moveUp&tree_pos='.$category->getTreePos();
                $deleteUrl = $mainUrl.'&id='.$categoryId.'&action=delete';

                $actions = [];
                $criteria = Criteria::create();
                $criteria->where(Criteria::expr()->eq('status', User::STUDENT));

                $inUrl = $category->getUrls()->filter(
                    function ($entry) use ($urlId) {
                        return $entry->getUrl()->getId() === $urlId;
                    }
                );

                if ($inUrl->count() > 0) {
                    $actions[] = Display::url($editIcon, $editUrl);
                    $actions[] = Display::url($moveIcon, $moveUrl);
                    $actions[] = Display::url($deleteIcon, $deleteUrl);
                }

                $url = api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$categoryId;
                $title = Display::url(
                    Display::return_icon(
                        'folder_document.gif',
                        get_lang('Open this category'),
                        null,
                        ICON_SIZE_SMALL
                    ).' '.$category->getName().' ('.$code.')',
                    $url
                );

                $countCourses = $category->getCourses()->count();
                $content = [
                    $title,
                    $category->getChildrenCount(),
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

        return Display::return_message(get_lang('There are no categories here'), 'warning');
    }

    /**
     * @param int|null $category Optional. Parent category ID.
     *
     * @return CourseCategoryEntity[]
     */
    public static function getCategories($category = null)
    {
        $repo = Container::getCourseCategoryRepository();
        $category = (int) $category;

        return $repo->findAllInAccessUrl(
            api_get_current_access_url_id(),
            api_get_configuration_value('allow_base_course_category'),
            $category
        );

        /*
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (t1.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
        $allowBaseCategories = api_get_configuration_value('allow_base_course_category');
        if ($allowBaseCategories) {
            $whereCondition = " AND (a.access_url_id = ".api_get_current_access_url_id()." OR a.access_url_id = 1) ";
        }

        $parentIdCondition = " AND (t1.parent_id IS NULL OR t1.parent_id = '' )";

        if ($category) {
            $parentIdCondition = " AND t1.parent_id = $category ";
        }

        $sql = "SELECT
                t1.name,
                t1.code,
                t1.parent_id,
                t1.tree_pos,
                t1.children_count,
                COUNT(DISTINCT t4.code) AS nbr_courses,
                a.access_url_id
                FROM $tbl_category t1
                $conditions
                LEFT JOIN $tbl_category t2
                ON t1.id = t2.parent_id
                LEFT JOIN $tbl_course_rel_category t3
                ON t1.id = t3.course_category_id
                LEFT JOIN $tbl_course t4
                ON t3.course_id = t4.id
                WHERE
                    1 = 1
                    $parentIdCondition
                    $whereCondition
                GROUP BY t1.name,
                         t1.code,
                         t1.parent_id,
                         t1.tree_pos,
                         t1.children_count
                ORDER BY t1.tree_pos
        ";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');*/
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
        $sql = "SELECT c.id, c.code, name
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
            $categories[$cat['id']] = '('.$cat['code'].') '.$cat['name'];
            ksort($categories);
        }

        return $categories;
    }

    /**
     * @param string $category_code
     * @param string $keyword
     *
     * @paran bool  $avoidCourses
     * @paran array $conditions
     *
     * @return int
     */
    public static function countCoursesInCategory(
        $category_code = '',
        $keyword = '',
        $avoidCourses = true,
        $conditions = []
    ) {
        return self::getCoursesInCategory($category_code, $keyword, $avoidCourses, $conditions, true);
    }

    public static function getCoursesInCategory(
        $category_code = '',
        $keyword = '',
        $avoidCourses = true,
        $conditions = [],
        $getCount = false
    ) {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tblCourseCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $keyword = Database::escape_string($keyword);
        $categoryCode = Database::escape_string($category_code);
        $avoidCoursesCondition = '';
        if ($avoidCourses) {
            $avoidCoursesCondition = CoursesAndSessionsCatalog::getAvoidCourseCondition();
        }

        $visibilityCondition = CourseManager::getCourseVisibilitySQLCondition('course', true);

        $sqlInjectJoins = '';
        $where = ' AND 1 = 1 ';
        $sqlInjectWhere = '';
        if (!empty($conditions)) {
            $sqlInjectJoins = $conditions['inject_joins'];
            $where = $conditions['where'];
            $sqlInjectWhere = $conditions['inject_where'];
        }
        $categoryFilter = '';
        if ('ALL' === $categoryCode || empty($categoryCode)) {
            // Nothing to do
        } elseif ('NONE' === $categoryCode) {
            $categoryFilter = ' AND course.category_id IS NULL ';
        } else {
            $categoryJoin = " INNER JOIN $tblCourseCategory cat ON course.category_id = cat.id ";
            $categoryFilter = ' AND cat.code = "'.$categoryCode.'" ';
        }

        $searchFilter = '';
        if (!empty($keyword)) {
            $searchFilter = ' AND (
                course.code LIKE "%'.$keyword.'%" OR
                course.title LIKE "%'.$keyword.'%" OR
                course.tutor_name LIKE "%'.$keyword.'%"
            ) ';
        }

        $urlCondition = ' url_rel_course.access_url_id = '.api_get_current_access_url_id().' AND';
        $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $select = " DISTINCT course.id, course.code, course.title ";
        if ($getCount) {
            $select = "count(DISTINCT course.id) as count";
        }
        $sql = "SELECT $select
                FROM $tbl_course as course
                INNER JOIN $tbl_url_rel_course as url_rel_course
                ON (url_rel_course.c_id = course.id)
                $sqlInjectJoins
                $categoryJoin
                WHERE
                    $urlCondition
                    course.visibility != '0' AND
                    course.visibility != '4'
                    $categoryFilter
                    $searchFilter
                    $avoidCoursesCondition
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
     * Get Pagination HTML div.
     *
     * @param $pageCurrent
     * @param $pageLength
     * @param $pageTotal
     *
     * @return string
     */
    public static function getCatalogPagination($pageCurrent, $pageLength, $pageTotal)
    {
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
                    '...'
                );
            }
        }

        // For each page add its page button to html
        for ($i = $pageBottom; $i <= $pageTop; $i++) {
            if ($i === $pageCurrent) {
                $pageItemAttributes = ['class' => 'page-item active'];
            } else {
                $pageItemAttributes = ['class' => 'page-item'];
            }
            $pageDiv .= self::getPageNumberItem(
                $i,
                $pageLength,
                $pageItemAttributes
            );
        }

        // Check if current page is the last page
        if ($pageTop < $pageTotal) {
            if ($pageTop < ($pageTotal - 1)) {
                $pageDiv .= self::getPageNumberItem(
                    $pageTop + 1,
                    $pageLength,
                    null,
                    '...'
                );
            }
            $pageDiv .= self::getPageNumberItem($pageTotal, $pageLength);
        }

        // Complete pagination html
        $pageDiv = Display::tag('ul', $pageDiv, ['class' => 'pagination']);
        $html .= '<nav>'.$pageDiv.'</nav>';

        return $html;
    }

    /**
     * Get li HTML of page number.
     *
     * @param        $pageNumber
     * @param        $pageLength
     * @param array  $liAttributes
     * @param string $content
     *
     * @return string
     */
    public static function getPageNumberItem(
        $pageNumber,
        $pageLength,
        $liAttributes = [],
        $content = ''
    ) {
        // Get page URL
        $url = self::getCourseCategoryUrl(
            $pageNumber,
            $pageLength
        );

        // If is current page ('active' class) clear URL
        if (isset($liAttributes) && is_array($liAttributes) && isset($liAttributes['class'])) {
            if (false !== strpos('active', $liAttributes['class'])) {
                $url = '';
            }
        }

        $content = !empty($content) ? $content : $pageNumber;

        return Display::tag(
            'li',
            Display::url(
                $content,
                $url,
                ['class' => 'page-link']
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
     * @param int    $hiddenLinks
     * @param string $action
     *
     * @return string
     */
    public static function getCourseCategoryUrl(
        $pageCurrent,
        $pageLength,
        $categoryCode = null,
        $hiddenLinks = null,
        $action = null
    ) {
        $requestAction = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : null;
        $action = isset($action) ? Security::remove_XSS($action) : $requestAction;
        $searchTerm = isset($_REQUEST['search_term']) ? Security::remove_XSS($_REQUEST['search_term']) : null;

        if ('subscribe_user_with_password' === $action) {
            $action = 'subscribe';
        }

        $categoryCodeRequest = isset($_REQUEST['category_code']) ? Security::remove_XSS(
            $_REQUEST['category_code']
        ) : null;
        $categoryCode = isset($categoryCode) ? Security::remove_XSS($categoryCode) : $categoryCodeRequest;
        $hiddenLinksRequest = isset($_REQUEST['hidden_links']) ? Security::remove_XSS($_REQUEST['hidden_links']) : null;
        $hiddenLinks = isset($hiddenLinks) ? Security::remove_XSS($hiddenLinksRequest) : $categoryCodeRequest;

        // Start URL with params
        $pageUrl = api_get_self().
            '?action='.$action.
            '&category_code='.$categoryCode.
            '&hidden_links='.$hiddenLinks.
            '&pageCurrent='.$pageCurrent.
            '&pageLength='.$pageLength;

        switch ($action) {
            case 'subscribe':
                // for search
                $pageUrl .=
                    '&search_term='.$searchTerm.
                    '&search_course=1'.
                    '&sec_token='.Security::getTokenFromSession();
                break;
            case 'display_courses':
            default:
                break;
        }

        return $pageUrl;
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
        $nameTools = get_lang('My courses');
        if (empty($action)) {
            return $nameTools; //should never happen
        }

        switch ($action) {
            case 'subscribe':
            case 'subscribe_user_with_password':
            case 'display_random_courses':
            case 'display_courses':
                $nameTools = get_lang('Courses catalog');
                break;
            case 'display_sessions':
                $nameTools = get_lang('Course sessions');
                break;
            default:
                // Nothing to do
                break;
        }

        return $nameTools;
    }

    public static function deleteImage(CourseCategoryEntity $category)
    {
        $assetRepo = Container::getAssetRepository();
        $assetId = $category->getImage();
        $asset = $assetRepo->find($assetId);
        $assetRepo->delete($asset);

        $category->setImage('');
        $repo = Container::getCourseCategoryRepository();
        $repo->save($category);
    }

    /**
     * Save image for a course category.
     *
     * @param array $fileData File data from $_FILES
     */
    public static function saveImage(CourseCategoryEntity $category, $fileData)
    {
        if (isset($fileData['tmp_name']) && !empty($fileData['tmp_name'])) {
            $categoryId = $category->getId();
            $extension = getextension($fileData['name']);
            $fileName = "cc_$categoryId.{$extension[0]}";

            $repo = Container::getAssetRepository();
            $asset = new Asset();
            $asset
                ->setCategory(Asset::COURSE_CATEGORY)
                ->setTitle($fileName);
            $asset = $repo->createFromRequest($asset, $fileData);

            $category->setImage($asset->getId());
            $repo = Container::getCourseCategoryRepository();
            $repo->save($category);
        }

        /*
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
        );*/
    }

    /**
     * @param        $category
     * @param string $description
     *
     * @return string
     */
    public static function saveDescription(CourseCategoryEntity $category, $description)
    {
        $repo = Container::getCourseCategoryRepository();
        $category->setDescription($description);
        $repo->save($category);
    }
}
