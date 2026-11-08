<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use CourseCategory;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for CourseCategory (course_category.lib.php).
 *
 * LIVE (called from admin/course_category.php, course.ajax.php, course_import.php, the portal):
 * getCategory, getCategoryById, getAllCategories, add, edit, moveNodeUp/Down, reorganizeTreePos,
 * getParents/getParentsToString, searchCategoryByKeyword, getCoursesInCategory (by id).
 * DEAD (no caller anywhere): getChildren, getCategoriesCanBeAddedInCourse, getCategoriesToDisplayInHomePage,
 * getCourseCategoryNotInList, updateParentCategoryChildrenCount outside add().
 *
 * Course categories are global rows; which of them a portal sees is decided by access_url_rel_course_category
 * (plus the whole subtree below an assigned category), so most assertions are about that scoping. Fixtures are
 * raw INSERTs tagged "cc_"/"CC", courses 1 and 2 are the installed demo courses.
 */
final class CourseCategoryCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/admin/course_category.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ------------------------------------------------------------------ getCategory / getCategoryById

    public function testGetCategoryReturnsTheRowByCodeWithItsAccessUrl(): void
    {
        $id = $this->cat('cc_a', 'CCA', null, 4, 2, 'FALSE');
        $this->assign($id, 1);

        $row = CourseCategory::getCategory('CCA');

        $this->assertSame((string) $id, (string) $row['id']);
        $this->assertSame('cc_a', $row['title']);
        $this->assertSame('FALSE', $row['auth_course_child']);
        $this->assertSame('4', (string) $row['tree_pos']);
        $this->assertSame('1', (string) $row['access_url_id']);
    }

    public function testGetCategoryWithoutUrlAssignmentHasNoAccessUrlKey(): void
    {
        $this->cat('cc_a', 'CCA');

        $row = CourseCategory::getCategory('CCA');

        $this->assertSame('cc_a', $row['title']);
        $this->assertArrayNotHasKey('access_url_id', $row);
    }

    public function testGetCategoryReturnsEmptyArrayForUnknownOrEmptyCode(): void
    {
        $this->assertSame([], CourseCategory::getCategory('CC-NOPE'));
        $this->assertSame([], CourseCategory::getCategory(''));
        $this->assertSame([], CourseCategory::getCategory(null));
        // Quotes are escaped, not injected.
        $this->assertSame([], CourseCategory::getCategory("x' OR '1'='1"));
    }

    public function testGetCategoryByIdReturnsTheRowAndCastsTheId(): void
    {
        $id = $this->cat('cc_a', 'CCA');
        $this->assign($id, 1);

        $row = CourseCategory::getCategoryById((string) $id);
        $this->assertSame('CCA', $row['code']);
        $this->assertSame('1', (string) $row['access_url_id']);

        // (int) cast: trailing junk is dropped, an injection attempt becomes id 0.
        $this->assertSame('CCA', CourseCategory::getCategoryById($id.' OR 1=1')['code']);
        $this->assertSame([], CourseCategory::getCategoryById('abc'));
        $this->assertSame([], CourseCategory::getCategoryById(999999));
    }

    // ------------------------------------------------------------------ getAllCategories

    public function testGetAllCategoriesIncludesTheSubtreeOfAssignedCategoriesOnly(): void
    {
        $root = $this->cat('cc_root', 'CCROOT', null, 50);
        $childB = $this->cat('cc_b', 'CCB', $root, 2);
        $childA = $this->cat('cc_a', 'CCA', $root, 1);
        $grand = $this->cat('cc_g', 'CCG', $childA, 1);
        $this->assign($root, 1);
        $orphan = $this->cat('cc_orphan', 'CCORPHAN', null, 51);
        $otherUrl = $this->url('other');
        $this->assign($orphan, $otherUrl);

        $all = CourseCategory::getAllCategories();
        $ids = array_map(static fn ($r) => (int) $r['id'], $all);

        $this->assertContains($root, $ids);
        $this->assertContains($childA, $ids);
        $this->assertContains($childB, $ids);
        $this->assertContains($grand, $ids, 'Sub-categories are visible wherever their top-level category is assigned.');
        $this->assertNotContains($orphan, $ids, 'A category assigned to another URL only is not visible.');
        $this->assertSame(['id', 'title', 'code', 'parent_id', 'tree_pos', 'children_count'], array_keys($all[0]));

        // ORDER BY parent_id, tree_pos: among the fixture rows siblings come by tree_pos, parents before their
        // children (parent ids are lower than the children's here).
        $mine = array_values(array_filter($ids, static fn ($i) => \in_array($i, [$root, $childA, $childB, $grand], true)));
        $this->assertSame([$root, $childA, $childB, $grand], $mine);
    }

    public function testGetAllCategoriesBaseCategoriesSettingKeepsUrlOneVisible(): void
    {
        $one = $this->cat('cc_one', 'CCONE', null, 60);
        $this->assign($one, 1);
        $this->setSetting('course.allow_base_course_category', 'true');

        $ids = array_map(static fn ($r) => (int) $r['id'], CourseCategory::getAllCategories());

        $this->assertContains($one, $ids);
    }

    // ------------------------------------------------------------------ add / children count

    public function testAddCreatesTheCategoryNormalisesTheCodeAndAssignsItToTheCurrentUrl(): void
    {
        $max = (int) $this->conn()->fetchOne('SELECT MAX(tree_pos) FROM course_category');

        $entity = CourseCategory::add(' cc add-1 ', ' Cc Title ', 'FALSE', 'a description');
        $this->fresh();

        $this->assertNotNull($entity);
        $row = $this->conn()->fetchAssociative('SELECT * FROM course_category WHERE id = ?', [$entity->getId()]);
        // generate_course_code: uppercase, only A-Z0-9.
        $this->assertSame('CCADD1', $row['code']);
        $this->assertSame('Cc Title', $row['title']);
        $this->assertSame('FALSE', $row['auth_course_child']);
        $this->assertSame('TRUE', $row['auth_cat_child']);
        $this->assertSame('a description', $row['description']);
        $this->assertSame($max + 1, (int) $row['tree_pos']);
        $this->assertNull($row['parent_id']);
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne(
                'SELECT COUNT(*) FROM access_url_rel_course_category WHERE course_category_id = ? AND access_url_id = 1',
                [$entity->getId()]
            )
        );
    }

    public function testAddRefusesADuplicateCodeAndCreatesNothing(): void
    {
        $this->assertNotNull(CourseCategory::add('CCDUP', 'one', 'TRUE'));
        $count = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_category');

        $this->assertNull(CourseCategory::add('cc-dup', 'two', 'TRUE'), 'The normalised code collides.');

        $this->assertSame($count, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_category'));
    }

    public function testAddUnderAParentIncrementsTheChildrenCountOfEveryAncestor(): void
    {
        $grand = $this->cat('cc_grand', 'CCGRAND');
        $parent = $this->cat('cc_parent', 'CCPARENT', $grand);

        $entity = CourseCategory::add('CCKID', 'kid', 'TRUE', '', $parent);
        $this->fresh();

        $this->assertSame($parent, (int) $this->conn()->fetchOne('SELECT parent_id FROM course_category WHERE id = ?', [$entity->getId()]));
        $this->assertSame(1, $this->childrenCount($parent));
        $this->assertSame(1, $this->childrenCount($grand), 'The count is propagated up the tree.');
    }

    public function testUpdateParentCategoryChildrenCountAddsAndSubtractsAlongTheChain(): void
    {
        $grand = $this->cat('cc_grand', 'CCGRAND', null, 0, 5);
        $parent = $this->cat('cc_parent', 'CCPARENT', $grand, 0, 3);

        CourseCategory::updateParentCategoryChildrenCount($parent, 2);
        $this->assertSame(7, $this->childrenCount($grand));
        $this->assertSame(5, $this->childrenCount($parent));

        CourseCategory::updateParentCategoryChildrenCount($parent, -1);
        $this->assertSame(6, $this->childrenCount($grand));
        $this->assertSame(4, $this->childrenCount($parent));
    }

    public function testUpdateParentCategoryChildrenCountDoesNotClampAtZero(): void
    {
        // INTENTIONAL: no clamping; the counter is only ever moved by add()/delete in pairs, a negative value would reveal an unbalanced caller.
        $cat = $this->cat('cc_a', 'CCA', null, 0, 0);

        CourseCategory::updateParentCategoryChildrenCount($cat, -1);

        $this->assertSame(-1, $this->childrenCount($cat));
    }

    // ------------------------------------------------------------------ edit

    public function testEditUnknownCategoryReturnsNull(): void
    {
        $this->assertNull(CourseCategory::edit(999999, 'x', 'TRUE', 'X', ''));
    }

    public function testEditChangesTitleDescriptionAndAuthCourseChild(): void
    {
        $id = $this->cat('cc_old', 'CCOLD');

        $entity = CourseCategory::edit($id, ' New title ', 'FALSE', 'CCOLD', 'new description');
        $this->fresh();

        $this->assertSame($id, $entity->getId());
        $row = $this->conn()->fetchAssociative('SELECT * FROM course_category WHERE id = ?', [$id]);
        $this->assertSame('New title', $row['title']);
        $this->assertSame('FALSE', $row['auth_course_child']);
        $this->assertSame('new description', $row['description']);
    }

    public function testEditSetsTheCodeTheFormSentNotTheTitle(): void
    {
        $id = $this->cat('cc_old', 'CCOLD');

        CourseCategory::edit($id, 'Renamed title', 'TRUE', ' cc-keep ', '');
        $this->fresh();

        // The code goes through the same normalisation as add().
        $this->assertSame(
            'CCKEEP',
            $this->conn()->fetchOne('SELECT code FROM course_category WHERE id = ?', [$id])
        );

        // A code that normalises to nothing leaves the stored one alone.
        CourseCategory::edit($id, 'Renamed again', 'TRUE', '--', '');
        $this->fresh();
        $this->assertSame('CCKEEP', $this->conn()->fetchOne('SELECT code FROM course_category WHERE id = ?', [$id]));
    }

    public function testEditCanReparentAndMoveBackToTheRoot(): void
    {
        $parent = $this->cat('cc_p', 'CCP');
        $id = $this->cat('cc_a', 'CCA');

        CourseCategory::edit($id, 'cc_a', 'TRUE', 'CCA', '', $parent);
        $this->fresh();
        $this->assertSame($parent, (int) $this->conn()->fetchOne('SELECT parent_id FROM course_category WHERE id = ?', [$id]));

        // null means "leave as is" (the argument was not given) ...
        CourseCategory::edit($id, 'cc_a', 'TRUE', 'CCA', '', null);
        $this->fresh();
        $this->assertSame($parent, (int) $this->conn()->fetchOne('SELECT parent_id FROM course_category WHERE id = ?', [$id]));

        // ... while an empty value posted by the form (no parent selected) moves it back to the root.
        CourseCategory::edit($id, 'cc_a', 'TRUE', 'CCA', '', '');
        $this->fresh();
        $this->assertNull($this->conn()->fetchOne('SELECT parent_id FROM course_category WHERE id = ?', [$id]));
    }

    // ------------------------------------------------------------------ tree order

    public function testMoveNodeUpSwapsWithThePreviousSiblingOnly(): void
    {
        $p = $this->cat('cc_p', 'CCP');
        $a = $this->cat('cc_a', 'CCA', $p, 1);
        $b = $this->cat('cc_b', 'CCB', $p, 2);
        $c = $this->cat('cc_c', 'CCC', $p, 3);
        $other = $this->cat('cc_other', 'CCOTHER', null, 0);

        $this->assertTrue(CourseCategory::moveNodeUp($c, 3, $p));

        $this->assertSame([$a => 1, $c => 2, $b => 3], $this->positions([$a, $b, $c]));
        $this->assertSame(0, $this->treePos($other), 'A category of another parent is not touched.');
    }

    public function testMoveNodeUpOnTheFirstSiblingReturnsFalseAndChangesNothing(): void
    {
        $p = $this->cat('cc_p', 'CCP');
        $a = $this->cat('cc_a', 'CCA', $p, 1);
        $b = $this->cat('cc_b', 'CCB', $p, 2);

        $this->assertFalse(CourseCategory::moveNodeUp($a, 1, $p));

        $this->assertSame([$a => 1, $b => 2], $this->positions([$a, $b]));
    }

    public function testMoveNodeDownSwapsWithTheNextSiblingAndStopsAtTheEnd(): void
    {
        $p = $this->cat('cc_p', 'CCP');
        $a = $this->cat('cc_a', 'CCA', $p, 1);
        $b = $this->cat('cc_b', 'CCB', $p, 2);
        $c = $this->cat('cc_c', 'CCC', $p, 3);

        $this->assertTrue(CourseCategory::moveNodeDown($a, 1, $p));
        $this->assertSame([$b => 1, $a => 2, $c => 3], $this->positions([$a, $b, $c]));

        $this->assertFalse(CourseCategory::moveNodeDown($c, 3, $p));
        $this->assertSame([$b => 1, $a => 2, $c => 3], $this->positions([$a, $b, $c]));
    }

    public function testMoveNodeRenumbersTheSiblingsFromOneBeforeSwapping(): void
    {
        $p = $this->cat('cc_p', 'CCP');
        $a = $this->cat('cc_a', 'CCA', $p, 10);
        $b = $this->cat('cc_b', 'CCB', $p, 20);
        $c = $this->cat('cc_c', 'CCC', $p, 30);

        // The caller still passes the old position of $b (20); the position is read again after the renumbering to
        // 1,2,3, so "up" swaps $b with $a (it used to pick $c and write 20 into the tree).
        $this->assertTrue(CourseCategory::moveNodeUp($b, 20, $p));

        $this->assertSame([$b => 1, $a => 2, $c => 3], $this->positions([$a, $b, $c]));
    }

    public function testMoveNodeUpAtRootScopesToRootCategoriesAndNullParent(): void
    {
        $x = $this->cat('cc_x', 'CCX', null, 1001);
        $y = $this->cat('cc_y', 'CCY', null, 1002);
        $p = $this->cat('cc_p', 'CCP', null, 1003);
        $child = $this->cat('cc_child', 'CCCHILD', $p, 0);
        // The page passes the tree_pos it listed, i.e. a contiguous one.
        CourseCategory::reorganizeTreePos(null);

        // No parent: the siblings are the root categories, so the child is never a candidate.
        $this->assertTrue(CourseCategory::moveNodeUp($p, $this->treePos($p), null));
        $this->assertSame(0, $this->treePos($child));
        $order = $this->conn()->fetchFirstColumn(
            'SELECT id FROM course_category WHERE parent_id IS NULL AND id IN (?, ?, ?) ORDER BY tree_pos',
            [$x, $y, $p]
        );
        $this->assertSame([(string) $x, (string) $p, (string) $y], array_map('strval', $order));
    }

    public function testReorganizeTreePosRenumbersOneParentFromOneKeepingTheOrder(): void
    {
        $p = $this->cat('cc_p', 'CCP');
        $c = $this->cat('cc_c', 'CCC', $p, 30);
        $a = $this->cat('cc_a', 'CCA', $p, 5);
        $b = $this->cat('cc_b', 'CCB', $p, 5);
        $q = $this->cat('cc_q', 'CCQ');
        $z = $this->cat('cc_z', 'CCZ', $q, 77);

        CourseCategory::reorganizeTreePos($p);

        $pos = $this->positions([$a, $b, $c]);
        $this->assertSame([1, 2, 3], array_values($pos));
        $this->assertSame(3, $pos[$c], 'The highest tree_pos ends last.');
        $this->assertSame(77, $this->treePos($z), 'Another parent is untouched.');
    }

    // ------------------------------------------------------------------ parents / children

    public function testGetParentsReturnsTheCategoryAndEveryAncestor(): void
    {
        $r = $this->cat('cc_r', 'CCR');
        $m = $this->cat('cc_m', 'CCM', $r);
        $this->cat('cc_l', 'CCL', $m);

        $parents = CourseCategory::getParents('CCL');

        $this->assertSame(['CCL', 'CCM', 'CCR'], array_column($parents, 'code'));
    }

    public function testGetParentsToStringBuildsTheBreadcrumbFromTheRoot(): void
    {
        $r = $this->cat('cc_r', 'CCR');
        $m = $this->cat('cc_m', 'CCM', $r);
        $this->cat('cc_l', 'CCL', $m);

        $this->assertSame('CCR > CCM > CCL > ', CourseCategory::getParentsToString('CCL'));
        $this->assertSame('CCR > ', CourseCategory::getParentsToString('CCR'));
        $this->assertNull(CourseCategory::getParentsToString('CC-NOPE'));
        $this->assertNull(CourseCategory::getParentsToString(''));
    }

    public function testGetChildrenReturnsEveryDescendantDepthFirst(): void
    {
        $r = $this->cat('cc_r', 'CCR');
        $a = $this->cat('cc_a', 'CCA', $r, 1);
        $a1 = $this->cat('cc_a1', 'CCA1', $a, 1);
        $b = $this->cat('cc_b', 'CCB', $r, 2);

        $children = CourseCategory::getChildren('CCR');

        $this->assertSame(['CCA', 'CCA1', 'CCB'], array_column($children, 'code'));
        $this->assertSame([$a, $a1, $b], array_map('intval', array_column($children, 'id')));
        $this->assertSame([], CourseCategory::getChildren('CCB'));
        $this->assertSame([], CourseCategory::getChildren('CC-NOPE'));
    }

    // ------------------------------------------------------------------ search / lists

    public function testSearchCategoryByKeywordMatchesCodeOrTitleOfAssignableCategoriesInTheUrlTree(): void
    {
        $root = $this->cat('cc_zeta root', 'CCQROOT', null, 70);
        $this->assign($root, 1);
        $byTitle = $this->cat('cc_zeta kid', 'CCQ1', $root, 1);
        $byCode = $this->cat('cc_kid', 'CCZETA2', $root, 2);
        $noCourses = $this->cat('cc_zeta nocourses', 'CCQ3', $root, 3, 0, 'FALSE');
        $outside = $this->cat('cc_zeta outside', 'CCQ4', null, 71);
        $this->assign($outside, $this->url('other'));

        $rows = CourseCategory::searchCategoryByKeyword('zeta');
        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);

        $this->assertEqualsCanonicalizing([$root, $byTitle, $byCode], $ids);
        $this->assertNotContains($noCourses, $ids, 'auth_course_child must be TRUE.');
        $this->assertNotContains($outside, $ids, 'Not in the URL tree.');
        $this->assertSame($rows[0]['title'], $rows[0]['text'], 'The select2 "text" alias duplicates the title.');
    }

    public function testSearchCategoryByKeywordEmptyKeywordReturnsNullNoMatchReturnsEmptyArray(): void
    {
        $this->assertNull(CourseCategory::searchCategoryByKeyword(''));
        $this->assertNull(CourseCategory::searchCategoryByKeyword('0'));
        $this->assertSame([], CourseCategory::searchCategoryByKeyword('cc-no-such-category-xyz'));
        $this->assertSame([], CourseCategory::searchCategoryByKeyword("x' OR '1'='1"));
    }

    public function testSearchCategoryByKeywordDoesNotEscapeLikeWildcards(): void
    {
        // INTENTIONAL: "%" and "_" typed by the user act as LIKE wildcards (search box semantics). Harmless (read-only and scoped)
        // but "%" matches every assignable category of the URL.
        $a = $this->cat('cc_wild', 'CCWILD', null, 80);
        $this->assign($a, 1);

        $ids = array_map(static fn ($r) => (int) $r['id'], CourseCategory::searchCategoryByKeyword('%'));

        $this->assertContains($a, $ids);
        $this->assertContains(
            $a,
            array_map(static fn ($r) => (int) $r['id'], CourseCategory::searchCategoryByKeyword('CCW_LD'))
        );
    }

    public function testGetCategoriesReturnsTheChildrenOfOneParentInTreePosOrderWithinTheUrlTree(): void
    {
        $root = $this->cat('cc_r', 'CCR', null, 90);
        $this->assign($root, 1);
        $b = $this->cat('cc_b', 'CCB', $root, 2);
        $a = $this->cat('cc_a', 'CCA', $root, 1);
        $this->cat('cc_grand', 'CCGRAND', $a, 1);

        $cats = CourseCategory::getCategories($root);

        $this->assertSame([$a, $b], array_map(static fn ($c) => $c->getId(), $cats));
        $topLevel = array_map(static fn ($c) => $c->getId(), CourseCategory::getCategories());
        $this->assertContains($root, $topLevel);
        $this->assertNotContains($a, $topLevel, 'Only root categories without a parent argument.');
    }

    public function testGetCoursesInCategoryReturnsTheCoursesLinkedToTheCategoryById(): void
    {
        $cat = $this->cat('cc_c', 'CCC');
        $this->conn()->executeStatement('INSERT INTO course_rel_category (course_id, course_category_id) VALUES (1, ?)', [$cat]);

        $courses = CourseCategory::getCoursesInCategory($cat);

        $this->assertCount(1, $courses);
        $this->assertSame(1, $courses->first()->getId());
        // The keyword filters on the title; the count flavour returns an integer; a category code works like an id
        // (the catalogue and auth/courses.php pass codes, which used to fatal) and an unknown code is empty.
        $this->assertCount(0, CourseCategory::getCoursesInCategory($cat, 'no-such-title'));
        $this->assertCount(1, CourseCategory::getCoursesInCategory($cat, (string) $courses->first()->getTitle()));
        $this->assertSame(1, CourseCategory::countCoursesInCategory($cat));
        $this->assertSame(1, CourseCategory::countCoursesInCategory('CCC'));
        $this->assertCount(1, CourseCategory::getCoursesInCategory('CCC'));
        $this->assertSame(0, CourseCategory::countCoursesInCategory('CC-NO-SUCH-CODE'));
        $this->assertCount(0, CourseCategory::getCoursesInCategory('CC-NO-SUCH-CODE'));
    }

    // ------------------------------------------------------------------ dead code, cheap pins

    public function testDeadHelpersStillRunAgainstTheCurrentSchema(): void
    {
        $root = $this->cat('cc_dead', 'CCDEAD', null, 95, 0, 'TRUE');
        $this->assign($root, 1);
        $noKids = $this->cat('cc_dead2', 'CCDEAD2', null, 96, 0, 'FALSE');
        $this->assign($noKids, 1);

        $titles = array_column(CourseCategory::getCategoriesToDisplayInHomePage(), 'title');
        $this->assertContains('cc_dead', $titles);

        $listed = CourseCategory::getCategoriesCanBeAddedInCourse('CCDEAD2');
        $this->assertSame('-', $listed['']);
        $this->assertSame('(CCDEAD) cc_dead', $listed[$root]);
        $this->assertSame('(CCDEAD2) cc_dead2', $listed[$noKids], 'The course own category is always selectable.');

        $notIn = array_column(CourseCategory::getCourseCategoryNotInList([$root]), 'id');
        $this->assertNotContains((string) $root, array_map('strval', $notIn));
        $this->assertContains((string) $noKids, array_map('strval', $notIn));
        $all = array_column(CourseCategory::getCourseCategoryNotInList([]), 'id');
        $this->assertContains((string) $root, array_map('strval', $all));
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    private function cat(
        string $title,
        string $code,
        ?int $parent = null,
        int $treePos = 0,
        int $children = 0,
        string $authCourseChild = 'TRUE'
    ): int {
        $this->conn()->executeStatement(
            'INSERT INTO course_category (parent_id, title, code, tree_pos, children_count, auth_course_child, auth_cat_child)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$parent, $title, $code, $treePos, $children, $authCourseChild, 'TRUE']
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function url(string $suffix): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO access_url (url, description, active, created_by, lft, lvl, rgt, is_login_only)
             VALUES (?, ?, 1, 1, 1, 0, 2, 0)',
            ['http://cc-'.$suffix.'.test/', 'cc']
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function assign(int $categoryId, int $urlId): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (?, ?)',
            [$urlId, $categoryId]
        );
    }

    private function childrenCount(int $id): int
    {
        return (int) $this->conn()->fetchOne('SELECT children_count FROM course_category WHERE id = ?', [$id]);
    }

    private function treePos(int $id): int
    {
        return (int) $this->conn()->fetchOne('SELECT tree_pos FROM course_category WHERE id = ?', [$id]);
    }

    /**
     * @param int[] $ids @return array<int,int> id => tree_pos
     */
    private function positions(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[$id] = $this->treePos($id);
        }
        asort($out);

        return $out;
    }
}
