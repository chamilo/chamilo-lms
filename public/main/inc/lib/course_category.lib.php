<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AccessUrlRelCourseCategory;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\CourseCategory as CourseCategoryEntity;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;

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
            $row = self::categoryRowsQueryBuilder()
                ->where('c.code = :code')
                ->setParameter('code', $categoryCode)
                ->setMaxResults(1)
                ->getQuery()
                ->getArrayResult()[0] ?? null;

            if (null !== $row) {
                return self::withAccessUrlId(self::toLegacyCategoryRow($row));
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
        $row = self::categoryRowsQueryBuilder()
            ->where('c.id = :id')
            ->setParameter('id', (int) $categoryId)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        return null === $row ? [] : self::withAccessUrlId(self::toLegacyCategoryRow($row));
    }

    /**
     * Every column of the course_category table, aliased to the snake_case keys the legacy callers read.
     */
    private static function categoryRowsQueryBuilder(): QueryBuilder
    {
        return Container::getEntityManager()->createQueryBuilder()
            ->select(
                'c.id AS id',
                'IDENTITY(c.parent) AS parent_id',
                'IDENTITY(c.asset) AS asset_id',
                'c.title AS title',
                'c.code AS code',
                'c.treePos AS tree_pos',
                'c.childrenCount AS children_count',
                'c.authCourseChild AS auth_course_child',
                'c.authCatChild AS auth_cat_child',
                'c.description AS description'
            )
            ->from(CourseCategoryEntity::class, 'c')
        ;
    }

    /**
     * Integer columns come back as integers (NULL stays NULL), as they did from the raw table row.
     */
    private static function toLegacyCategoryRow(array $row): array
    {
        foreach (['id', 'parent_id', 'tree_pos', 'children_count'] as $key) {
            if (array_key_exists($key, $row) && null !== $row[$key]) {
                $row[$key] = (int) $row[$key];
            }
        }

        return $row;
    }

    /**
     * Adds the access_url_id of the first URL the category is assigned to, if any.
     */
    private static function withAccessUrlId(array $category): array
    {
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(r.url) AS access_url_id')
            ->from(AccessUrlRelCourseCategory::class, 'r')
            ->where('IDENTITY(r.courseCategory) = :categoryId')
            ->setParameter('categoryId', (int) $category['id'])
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()
        ;
        if ([] !== $rows) {
            $category['access_url_id'] = null === $rows[0]['access_url_id'] ? null : (int) $rows[0]['access_url_id'];
        }

        return $category;
    }

    /**
     * Returns a flat list of all course categories in this URL, including the
     * sub-categories of the categories assigned to it. If the
     * allow_base_course_category option is true, then also show the
     * course categories of the base URL.
     *
     * @return array [id, name, code, parent_id, tree_pos, children_count, number_courses]
     */
    public static function getAllCategories()
    {
        $accessUrlIds = [api_get_current_access_url_id()];
        $allowBaseCategories = ('true' === api_get_setting('course.allow_base_course_category'));
        if ($allowBaseCategories) {
            $accessUrlIds[] = 1;
        }

        // Sub-categories are visible wherever their top-level category is assigned.
        $categoryIds = Container::getCourseCategoryRepository()->findIdsInAccessUrlTree($accessUrlIds);
        if (empty($categoryIds)) {
            return [];
        }

        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select(
                'c.id AS id',
                'c.title AS title',
                'c.code AS code',
                'IDENTITY(c.parent) AS parent_id',
                'c.treePos AS tree_pos',
                'c.childrenCount AS children_count'
            )
            ->from(CourseCategoryEntity::class, 'c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', array_map('intval', $categoryIds))
            ->orderBy('c.parent')
            ->addOrderBy('c.treePos')
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map([self::class, 'toLegacyCategoryRow'], $rows);
    }

    /**
     * @param string   $code
     * @param string   $name
     * @param string   $canHaveCourses
     * @param int|null $parentId
     */
    public static function add($code, $name, $canHaveCourses, $description = '', $parentId = null): ?CourseCategoryEntity
    {
        $code = trim($code);
        $name = trim($name);
        $parentId = (int) $parentId;

        $code = CourseManager::generate_course_code($code);
        $em = Container::getEntityManager();
        $existing = $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(CourseCategoryEntity::class, 'c')
            ->where('c.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getSingleScalarResult()
        ;
        if ((int) $existing > 0) {
            return null;
        }
        $maxTreePos = $em->createQueryBuilder()
            ->select('MAX(c.treePos)')
            ->from(CourseCategoryEntity::class, 'c')
            ->getQuery()
            ->getSingleScalarResult()
        ;
        $tree_pos = (int) $maxTreePos + 1;
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
        $categoryId = (int) $categoryId;
        $delta = (int) $delta;
        $em = Container::getEntityManager();
        // First get to the highest level possible in the tree
        $rows = $em->createQueryBuilder()
            ->select('IDENTITY(c.parent) AS parent_id')
            ->from(CourseCategoryEntity::class, 'c')
            ->where('c.id = :id')
            ->setParameter('id', $categoryId)
            ->getQuery()
            ->getArrayResult()
        ;
        if ([] !== $rows && !empty($rows[0]['parent_id'])) {
            // if a parent was found, enter there to see if he's got one more parent
            self::updateParentCategoryChildrenCount($rows[0]['parent_id'], $delta);
        }
        // Now we're at the top, get back down to update each child
        $em->createQueryBuilder()
            ->update(CourseCategoryEntity::class, 'c')
            ->set('c.childrenCount', 'c.childrenCount + :delta')
            ->where('c.id = :id')
            ->setParameter('delta', $delta)
            ->setParameter('id', $categoryId)
            ->getQuery()
            ->execute()
        ;
    }

    public static function edit($categoryId, $name, $canHaveCourses, $code, $description, $parentId = null): ?CourseCategoryEntity
    {
        $repo = Container::getCourseCategoryRepository();
        $category = $repo->find($categoryId);
        if (null === $category) {
            return null;
        }

        $name = trim($name);
        $code = CourseManager::generate_course_code(trim((string) $code));
        if ('' !== $code) {
            $category->setCode($code);
        }
        $category
            ->setTitle($name)
            ->setDescription($description)
            ->setAuthCourseChild($canHaveCourses)
        ;

        // null: parent not given, leave as is. An empty value (no parent selected in the form) means the root.
        if (null !== $parentId) {
            $category->setParent(empty($parentId) ? null : $repo->find($parentId));
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
        $categoryId = (int) $categoryId;

        self::reorganizeTreePos($parentId);

        // The posted position is stale once the siblings were renumbered: read the real one.
        $treePos = self::getTreePos($categoryId, $parentId);
        if (null === $treePos) {
            return false;
        }

        $previousCategory = self::siblingsQueryBuilder($parentId)
            ->select('c.id AS id', 'c.treePos AS tree_pos')
            ->andWhere('c.treePos < :treePos')
            ->setParameter('treePos', $treePos)
            ->orderBy('c.treePos', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        if (null === $previousCategory) {
            return false;
        }

        self::setTreePos($categoryId, (int) $previousCategory['tree_pos']);
        self::setTreePos((int) $previousCategory['id'], $treePos);

        return true;
    }

    public static function moveNodeDown($categoryId, $treePos, $parentId): bool
    {
        $categoryId = (int) $categoryId;

        self::reorganizeTreePos($parentId);

        // The posted position is stale once the siblings were renumbered: read the real one.
        $treePos = self::getTreePos($categoryId, $parentId);
        if (null === $treePos) {
            return false;
        }

        $nextCategory = self::siblingsQueryBuilder($parentId)
            ->select('c.id AS id', 'c.treePos AS tree_pos')
            ->andWhere('c.treePos > :treePos')
            ->setParameter('treePos', $treePos)
            ->orderBy('c.treePos', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        if (null === $nextCategory) {
            return false;
        }

        self::setTreePos($categoryId, (int) $nextCategory['tree_pos']);
        self::setTreePos((int) $nextCategory['id'], $treePos);

        return true;
    }

    /**
     * The categories having $parentId as parent (the root categories when it is empty).
     */
    private static function siblingsQueryBuilder($parentId): QueryBuilder
    {
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->from(CourseCategoryEntity::class, 'c')
        ;
        if (empty($parentId)) {
            return $qb->where('c.parent IS NULL');
        }

        return $qb->where('IDENTITY(c.parent) = :parentId')->setParameter('parentId', (int) $parentId);
    }

    private static function setTreePos(int $categoryId, int $treePos): void
    {
        Container::getEntityManager()->createQueryBuilder()
            ->update(CourseCategoryEntity::class, 'c')
            ->set('c.treePos', ':treePos')
            ->where('c.id = :id')
            ->setParameter('treePos', $treePos)
            ->setParameter('id', $categoryId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Position of a category among the siblings of $parentId, null if it is not one of them.
     */
    private static function getTreePos(int $categoryId, $parentId): ?int
    {
        $rows = self::siblingsQueryBuilder($parentId)
            ->select('c.treePos AS tree_pos')
            ->andWhere('c.id = :id')
            ->setParameter('id', $categoryId)
            ->getQuery()
            ->getArrayResult()
        ;

        return [] === $rows ? null : (int) $rows[0]['tree_pos'];
    }

    public static function reorganizeTreePos($parentId): void
    {
        $rows = self::siblingsQueryBuilder($parentId)
            ->select('c.id AS id')
            ->orderBy('c.treePos')
            ->getQuery()
            ->getArrayResult()
        ;

        $newTreePos = 1;
        foreach ($rows as $row) {
            self::setTreePos((int) $row['id'], $newTreePos);
            $newTreePos++;
        }
    }

    /**
     * All the descendants of a category (depth first), as [code, id] rows.
     *
     * @param string $categoryCode
     *
     * @return array
     */
    public static function getChildren($categoryCode)
    {
        $category = self::getCategory($categoryCode);
        if (empty($category)) {
            return [];
        }

        return self::getChildrenByParentId((int) $category['id']);
    }

    private static function getChildrenByParentId(int $parentId): array
    {
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('c.code AS code', 'c.id AS id')
            ->from(CourseCategoryEntity::class, 'c')
            ->where('IDENTITY(c.parent) = :parentId')
            ->setParameter('parentId', $parentId)
            ->orderBy('c.treePos')
            ->addOrderBy('c.id')
            ->getQuery()
            ->getArrayResult()
        ;
        $children = [];
        foreach ($rows as $row) {
            $row['id'] = (int) $row['id'];
            $children[] = $row;
            $children = array_merge($children, self::getChildrenByParentId((int) $row['id']));
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

        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('c.code AS code', 'IDENTITY(c.parent) AS parent_id')
            ->from(CourseCategoryEntity::class, 'c')
            ->where('c.code = :code')
            ->setParameter('code', (string) $categoryCode)
            ->getQuery()
            ->getArrayResult()
        ;
        $children = [];
        foreach ($rows as $row) {
            $row = self::toLegacyCategoryRow($row);
            $parent = empty($row['parent_id']) ? [] : self::getCategoryById($row['parent_id']);
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

            $editIcon = Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon-secondary', null, ICON_SIZE_SMALL, get_lang('Edit'));
            $exportIcon = Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('CSV export'));
            $deleteIcon = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon-danger', null, ICON_SIZE_SMALL, get_lang('Delete'));
            $urlId = api_get_current_access_url_id();
            // Sub-categories can be managed wherever their top-level category is assigned.
            $categoryIdsInUrl = Container::getCourseCategoryRepository()->findIdsInAccessUrlTree([$urlId]);
            $isSecondaryUrl = api_get_multiple_access_url() && 1 != $urlId;
            $secToken = Security::get_existing_token();

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
                        'sec_token' => $secToken,
                        'tree_pos' => $treePos,
                    ]));

                $moveDownUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'moveDown',
                        'id' => $categoryId,
                        'sec_token' => $secToken,
                        'tree_pos' => $treePos,
                    ]));

                $deleteUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'delete',
                        'id' => $categoryId,
                        'sec_token' => $secToken,
                    ]));

                $exportUrl = $baseUrl.'?'.http_build_query(array_merge($baseParams, [
                        'action' => 'export',
                        'id' => $categoryId,
                    ]));

                $actions = [];

                if (in_array($categoryId, $categoryIdsInUrl, true)) {
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
                    // Deleting a category removes it from every URL, so it is only allowed in the main portal
                    if (!$isSecondaryUrl) {
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
                }

                $url = api_get_path(WEB_CODE_PATH).'admin/course_category.php?id='.$categoryId;
                $title = Display::url(
                    Display::getMdiIcon(
                        ObjectIcon::FOLDER,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_SMALL,
                        get_lang('Open this category')
                    ).' '.htmlspecialchars($category->getTitle(), ENT_QUOTES, 'UTF-8').' ('.htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8').')',
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
        return Container::getEntityManager()->createQueryBuilder()
            ->select('c.title AS title')
            ->from(CourseCategoryEntity::class, 'c')
            ->where('c.parent IS NULL')
            ->orderBy('c.treePos')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * @param string $categoryCode
     *
     * @return array
     */
    public static function getCategoriesCanBeAddedInCourse($categoryCode)
    {
        $res = Container::getEntityManager()->createQueryBuilder()
            ->select('c.id AS id', 'c.code AS code', 'c.title AS title')
            ->from(CourseCategoryEntity::class, 'c')
            ->innerJoin(AccessUrlRelCourseCategory::class, 'a', 'WITH', 'a.courseCategory = c')
            ->where("c.authCourseChild = 'TRUE' OR c.code = :code")
            ->andWhere('IDENTITY(a.url) = :urlId')
            ->setParameter('code', (string) $categoryCode)
            ->setParameter('urlId', (int) api_get_current_access_url_id())
            ->orderBy('c.treePos')
            ->getQuery()
            ->getArrayResult()
        ;

        $categoryToAvoid = '';
        if (!api_is_platform_admin()) {
            $categoryToAvoid = api_get_setting('course.course_category_code_to_use_as_model');
        }
        $categories[''] = '-';
        foreach ($res as $cat) {
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
     * @param int|string $category_code Category id or code
     * @param string     $keyword
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
     * @param int|string|null $categoryId Category id or category code; an unknown one (or 'NONE') matches nothing
     * @param string          $keyword    Only the courses whose title contains it (case-insensitive)
     * @param bool            $avoidCourses Apply the catalogue visibility/URL/avoid-list filters (pass false for admin use)
     *
     * @return Collection|int The courses, or their number when $getCount is true
     */
    public static function getCoursesInCategory(
        $categoryId,
        $keyword = '',
        $avoidCourses = true,
        $conditions = [],
        $getCount = false
    ) {
        $repo = Container::getCourseCategoryRepository();
        $category = null;
        if (is_int($categoryId) || (is_string($categoryId) && ctype_digit($categoryId))) {
            $category = $repo->find((int) $categoryId);
        } elseif (is_string($categoryId) && '' !== $categoryId) {
            $category = $repo->findOneBy(['code' => $categoryId]);
        }

        $courses = null === $category ? new ArrayCollection() : $category->getCourses();
        if (is_string($keyword) && '' !== $keyword) {
            $courses = $courses->filter(
                static fn ($course): bool => false !== mb_stripos((string) $course->getTitle(), $keyword)
            );
        }

        if ($avoidCourses && !$courses->isEmpty()) {
            // Same filters as the catalogue listing (searchAndSortCourses): never expose closed/hidden courses,
            // the courses to avoid, the catalogue restrictions or the courses of another access URL.
            $ids = array_map(static fn ($course): int => (int) $course->getId(), $courses->toArray());
            $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
            $join = '';
            $urlCondition = '';
            if (api_is_multiple_url_enabled()) {
                $join = ' INNER JOIN '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE).
                    ' url_rel_course ON url_rel_course.c_id = course.id';
                $urlCondition = ' AND url_rel_course.access_url_id = '.(int) api_get_current_access_url_id();
            }
            // TODO(db-neutral): raw SQL fragments from getAvoidCourseCondition()/getCourseVisibilitySQLCondition()
            $sql = "SELECT DISTINCT course.id FROM $tblCourse course $join
                    WHERE course.id IN (".implode(',', $ids).")
                    $urlCondition
                    ".CoursesAndSessionsCatalog::getAvoidCourseCondition().'
                    '.CourseManager::getCourseVisibilitySQLCondition('course', true);
            $allowed = array_map('intval', array_column(Database::store_result(Database::query($sql), 'ASSOC'), 'id'));
            $courses = $courses->filter(static fn ($course): bool => in_array((int) $course->getId(), $allowed, true));
        }

        return $getCount ? count($courses) : $courses;
    }

    /**
     * Row (id, title, code, category_code) of a Course entity, as the course search JSON expects it.
     * category_code is the code of the first category of the course, or null.
     */
    public static function courseEntityToSearchRow(CourseEntity $course): array
    {
        $category = $course->getCategories()->first();

        return [
            'id' => (int) $course->getId(),
            'title' => $course->getTitle(),
            'code' => $course->getCode(),
            'category_code' => $category ? $category->getCode() : null,
        ];
    }

    /**
     * @param array $list
     *
     * @return array
     */
    public static function getCourseCategoryNotInList($list)
    {
        $qb = self::categoryRowsQueryBuilder()->where('c.parent IS NULL');
        if (!empty($list)) {
            $qb
                ->andWhere('c.id NOT IN (:ids)')
                ->setParameter('ids', array_map('intval', $list))
            ;
        }

        return array_map([self::class, 'toLegacyCategoryRow'], $qb->getQuery()->getArrayResult());
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

        $accessUrlIds = [api_get_current_access_url_id()];

        $allowBaseCategories = ('true' === api_get_setting('course.allow_base_course_category'));
        if ($allowBaseCategories) {
            $accessUrlIds[] = 1;
        }

        // Sub-categories are visible wherever their top-level category is assigned.
        $categoryIds = Container::getCourseCategoryRepository()->findIdsInAccessUrlTree($accessUrlIds);
        if (empty($categoryIds)) {
            return [];
        }
        $rows = self::categoryRowsQueryBuilder()
            ->addSelect('c.title AS text')
            ->where('c.code LIKE :keyword OR c.title LIKE :keyword')
            ->andWhere("c.authCourseChild = 'TRUE'")
            ->andWhere('c.id IN (:ids)')
            ->setParameter('keyword', '%'.$keyword.'%')
            ->setParameter('ids', array_map('intval', $categoryIds))
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map([self::class, 'toLegacyCategoryRow'], $rows);
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
                $nameTools = get_lang('Courses catalogue');
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
