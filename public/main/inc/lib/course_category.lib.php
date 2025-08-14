<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\CourseCategory as CourseCategoryEntity;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;

class CourseCategory
{
    /**
     * Get category details from a simple category code.
     *
     * @param string|null $categoryCode The literal category code
     *
     * @return array
     */
    public static function getCategory(string $categoryCode = null): array
    {
        if (!empty($categoryCode)) {
            $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
            $categoryCode = Database::escape_string($categoryCode);
            $sql = "SELECT * FROM $table WHERE code ='$categoryCode'";
            $result = Database::query($sql);

            if (Database::num_rows($result)) {
                $category = Database::fetch_assoc($result);
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
            $category = Database::fetch_assoc($result);
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
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $conditions = " INNER JOIN $table a ON (t1.id = a.course_category_id)";
        $whereCondition = " AND a.access_url_id = ".api_get_current_access_url_id();
        $allowBaseCategories = ('true' === api_get_setting('course.allow_base_course_category'));
        if ($allowBaseCategories) {
            $whereCondition = " AND (a.access_url_id = ".api_get_current_access_url_id()." OR a.access_url_id = 1)";
        }

        $sql = "SELECT
                t1.id,
                t1.title,
                t1.code,
                t1.parent_id,
                t1.tree_pos,
                t1.children_count
            FROM $tbl_category t1
            $conditions
            WHERE 1=1
                $whereCondition
            GROUP BY
                t1.id,
                t1.title,
                t1.code,
                t1.parent_id,
                t1.tree_pos,
                t1.children_count
            ORDER BY t1.parent_id, t1.tree_pos";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param string   $code
     * @param string   $name
     * @param string   $canHaveCourses
     * @param int|null $parentId
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
            ->setTitle($name)
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
        if (false !== $row && !empty($row['parent_id'])) {
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

    public static function edit($categoryId, $name, $canHaveCourses, $code, $description, $parentId = null): ?CourseCategoryEntity
    {
        $repo = Container::getCourseCategoryRepository();
        $category = $repo->find($categoryId);
        if (null === $category) {
            return null;
        }

        $name = trim($name);
        $category
            ->setCode($name)
            ->setTitle($name)
            ->setDescription($description)
            ->setAuthCourseChild($canHaveCourses)
        ;

        if (!empty($parentId)) {
            $category->setParent(Container::getCourseCategoryRepository()->find($parentId));
        }

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
    public static function moveNodeUp($categoryId, $treePos, $parentId): bool
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $categoryId = (int) $categoryId;
        $treePos = (int) $treePos;

        $parentIdCondition = "parent_id IS NULL";
        if (!empty($parentId)) {
            $parentIdCondition = "parent_id = '".Database::escape_string($parentId)."'";
        }

        self::reorganizeTreePos($parentId);

        $sql = "SELECT id, tree_pos
            FROM $table
            WHERE $parentIdCondition AND tree_pos < $treePos
            ORDER BY tree_pos DESC
            LIMIT 1";

        $result = Database::query($sql);
        $previousCategory = Database::fetch_array($result);

        if (!$previousCategory) {
            return false;
        }

        Database::query("UPDATE $table SET tree_pos = {$previousCategory['tree_pos']} WHERE id = $categoryId");
        Database::query("UPDATE $table SET tree_pos = $treePos WHERE id = {$previousCategory['id']}");

        return true;
    }

    public static function moveNodeDown($categoryId, $treePos, $parentId): bool
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $categoryId = (int) $categoryId;
        $treePos = (int) $treePos;

        $parentIdCondition = "parent_id IS NULL";
        if (!empty($parentId)) {
            $parentIdCondition = "parent_id = '".Database::escape_string($parentId)."'";
        }

        self::reorganizeTreePos($parentId);

        $sql = "SELECT id, tree_pos
            FROM $table
            WHERE $parentIdCondition AND tree_pos > $treePos
            ORDER BY tree_pos ASC
            LIMIT 1";

        $result = Database::query($sql);
        $nextCategory = Database::fetch_array($result);

        if (!$nextCategory) {
            return false;
        }

        Database::query("UPDATE $table SET tree_pos = {$nextCategory['tree_pos']} WHERE id = $categoryId");
        Database::query("UPDATE $table SET tree_pos = $treePos WHERE id = {$nextCategory['id']}");

        return true;
    }

    public static function reorganizeTreePos($parentId): void
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);

        $parentIdCondition = "parent_id IS NULL";
        if (!empty($parentId)) {
            $parentIdCondition = "parent_id = '".Database::escape_string($parentId)."'";
        }

        $sql = "SELECT id FROM $table WHERE $parentIdCondition ORDER BY tree_pos";
        $result = Database::query($sql);

        $newTreePos = 1;
        while ($row = Database::fetch_array($result)) {
            Database::query("UPDATE $table SET tree_pos = $newTreePos WHERE id = {$row['id']}");
            $newTreePos++;
        }
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
        while ($row = Database::fetch_assoc($result)) {
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
        while ($row = Database::fetch_assoc($result)) {
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
            $baseUrl = api_get_path(WEB_CODE_PATH).'admin/course_category.php';
            $baseParams = [];
            if (!empty($categorySource['id'])) {
                $baseParams['id'] = (int) $categorySource['id'];
            }

            $editIcon = Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'));
            $exportIcon = Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('CSV export'));
            $deleteIcon = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'));
            $urlId = api_get_current_access_url_id();

            $positions = array_map(fn($c) => $c->getTreePos(), $categories);
            $minTreePos = min($positions);
            $maxTreePos = max($positions);

            foreach ($categories as $category) {
                $categoryId = $category->getId();
                $code = $category->getCode();
                $treePos = $category->getTreePos();
                $editUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'edit',
                        'id' => $categoryId,
                    ]));

                $moveUpUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'moveUp',
                        'id' => $categoryId,
                        'tree_pos' => $treePos,
                    ]));

                $moveDownUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'moveDown',
                        'id' => $categoryId,
                        'tree_pos' => $treePos,
                    ]));

                $deleteUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'delete',
                        'id' => $categoryId,
                    ]));

                $exportUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'export',
                        'id' => $categoryId,
                    ]));

                $actions = [];

                $inUrl = $category->getUrls()->filter(
                    function ($entry) use ($urlId) {
                        return $entry->getUrl()->getId() === $urlId;
                    }
                );

                if ($inUrl->count() > 0) {
                    $actions[] = Display::url($editIcon, $editUrl);

                    if ($treePos > $minTreePos) {
                        $actions[] = Display::url(
                            Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Move up')),
                            $moveUpUrl
                        );
                    } else {
                        $actions[] = Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Move up'));
                    }

                    if ($treePos < $maxTreePos) {
                        $actions[] = Display::url(
                            Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Move down')),
                            $moveDownUrl
                        );
                    } else {
                        $actions[] = Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Move down'));
                    }

                    $actions[] = Display::url($exportIcon, $exportUrl);
                    $actions[] = Display::url(
                        $deleteIcon,
                        $deleteUrl,
                        [
                            'onclick' => 'javascript: if (!confirm(\''.addslashes(
                                    api_htmlentities(sprintf(get_lang('Please confirm your choice')), ENT_QUOTES)
                                ).'\')) return false;',
                        ]
                    );
                }

                $url = api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$categoryId;
                $title = Display::url(
                    Display::getMdiIcon(
                        ObjectIcon::FOLDER,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_SMALL,
                        get_lang('Open this category')
                    ).' '.$category->getTitle().' ('.$code.')',
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
        $allowBaseCourseCategory = ('true' === api_get_setting('course.allow_base_course_category'));

        return $repo->findAllInAccessUrl(
            api_get_current_access_url_id(),
            $allowBaseCourseCategory,
            $category
        );
    }

    /**
     * @return array
     */
    public static function getCategoriesToDisplayInHomePage()
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT title FROM $table
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
        $sql = "SELECT c.id, c.code, c.title
                FROM $tbl_category c
                $conditions
                WHERE (auth_course_child = 'TRUE' OR code = '".Database::escape_string($categoryCode)."')
                $whereCondition
                ORDER BY tree_pos";
        $res = Database::query($sql);

        $categoryToAvoid = '';
        if (!api_is_platform_admin()) {
            $categoryToAvoid = api_get_setting('course.course_category_code_to_use_as_model');
        }
        $categories[''] = '-';
        while ($cat = Database::fetch_array($res)) {
            $categoryCode = $cat['code'];
            if (!empty($categoryToAvoid) && $categoryToAvoid == $categoryCode) {
                continue;
            }
            $categories[$cat['id']] = '('.$cat['code'].') '.$cat['title'];
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

    /**
     * @return \Chamilo\CoreBundle\Entity\Course[]
     */
    public static function getCoursesInCategory(
        $categoryId,
        $keyword = '',
        $avoidCourses = true,
        $conditions = [],
        $getCount = false
    ) {
        $repo = Container::getCourseCategoryRepository();
        /** @var CourseCategoryEntity $category */
        $category = $repo->find($categoryId);

        // @todo add filters

        return $category->getCourses();
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

        $allowBaseCategories = ('true' === api_get_setting('course.allow_base_course_category'));
        if ($allowBaseCategories) {
            $whereCondition = " AND (a.access_url_id = ".api_get_current_access_url_id()." OR a.access_url_id = 1) ";
        }

        $keyword = Database::escape_string($keyword);

        $sql = "SELECT c.*, c.title as text
                FROM $tableCategory c $conditions
                WHERE
                (
                    c.code LIKE '%$keyword%' OR c.title LIKE '%$keyword%'
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

    /**
     * Save image for a course category.
     *
     * @param array $fileData File data from $_FILES
     */
    public static function saveImage(CourseCategoryEntity $category, $fileData, $crop = '')
    {
        if (isset($fileData['tmp_name']) && !empty($fileData['tmp_name'])) {
            $repo = Container::getCourseCategoryRepository();
            $repo->deleteAsset($category);

            $assetRepo = Container::getAssetRepository();
            $asset = (new Asset())
                ->setCategory(Asset::COURSE_CATEGORY)
                ->setTitle($fileData['name'])
                ->setCrop($crop)
            ;
            $asset = $assetRepo->createFromRequest($asset, $fileData);

            $category->setAsset($asset);
            $repo->save($category);
        }
    }
}
