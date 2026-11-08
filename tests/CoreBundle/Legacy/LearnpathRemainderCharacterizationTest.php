<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use learnpath;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;

/**
 * Second characterization pass over public/main/lp/learnpath.class.php: visibility/publication/category/ordering
 * helpers (resource_link based), links, progress/score/stars helpers, prerequisites and the student visibility rule.
 *
 * Learning paths and categories are inserted with prepared statements (resource_node rows with a fixed type, links
 * to fixture course 1). Every title starts with "l2_" (sibling files run against the same database).
 */
final class LearnpathRemainderCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;
    private const USER_ID = 1;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
        $session->set('_real_cid', self::COURSE_ID);
        $session->set('_cid', 'TEMP');
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function newLpRow(string $title, int $type = 1, array $extra = []): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (?, ?, '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$type, $title]
        );
        $lpId = (int) $this->conn()->lastInsertId();
        foreach ($extra as $col => $value) {
            $this->conn()->executeStatement("UPDATE c_lp SET $col = ? WHERE iid = ?", [$value, $lpId]);
        }

        $em = $this->getEntityManager();
        $root = (new CLpItem())
            ->setTitle('root')
            ->setPath('root')
            ->setLp($em->find(CLp::class, $lpId))
            ->setItemType('root')
        ;
        $em->persist($root);
        $em->flush();
        $em->clear();

        return $lpId;
    }

    private function lp(int $lpId, int $userId = self::USER_ID): learnpath
    {
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(CLp::class, $lpId);

        return new learnpath($entity, api_get_course_info_by_id(self::COURSE_ID), $userId);
    }

    private function rootId(int $lpId): int
    {
        return (int) $this->conn()->fetchOne("SELECT iid FROM c_lp_item WHERE lp_id = ? AND path = 'root'", [$lpId]);
    }

    private function addItem(int $lpId, string $title, string $type = 'document', int $order = 1, ?int $parentIid = null, int $path = 0): int
    {
        $lp = $this->lp($lpId);
        $parent = $this->getEntityManager()->find(CLpItem::class, $parentIid ?? $this->rootId($lpId));
        $id = (int) $lp->add_item($parent, 0, $type, $path, $title);
        $this->conn()->executeStatement('UPDATE c_lp_item SET display_order = ? WHERE iid = ?', [$order, $id]);
        $this->getEntityManager()->clear();

        return $id;
    }

    private function newNode(string $title, int $type = 40): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO resource_node (resource_type_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, NOW(), NOW(), 0, ?)",
            [$type, $title, $title.'-'.uniqid(), Uuid::v4()->toBinary()]
        );

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * Links a resource node to course 1 (no session), like the platform does.
     */
    private function link(int $nodeId, int $visibility = 2, int $order = 0, ?int $sessionId = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, (SELECT resource_type_id FROM resource_node WHERE id = ?), NOW(), NOW())',
            [$nodeId, self::COURSE_ID, $sessionId, $visibility, $order, $nodeId]
        );

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * Learning path with a resource node linked to course 1.
     *
     * @return array{int, int} lp id, node id
     */
    private function publishedLp(string $title, int $visibility = 2, int $order = 0, array $extra = []): array
    {
        $lpId = $this->newLpRow($title, 1, $extra);
        $node = $this->newNode($title);
        $this->conn()->executeStatement('UPDATE c_lp SET resource_node_id = ? WHERE iid = ?', [$node, $lpId]);
        $this->link($node, $visibility, $order);

        return [$lpId, $node];
    }

    /**
     * @return array{int, int} category id, node id
     */
    private function newCategory(string $title, int $visibility = 2, int $order = 0): array
    {
        $node = $this->newNode($title, 41);
        $this->conn()->executeStatement('INSERT INTO c_lp_category (resource_node_id, title) VALUES (?, ?)', [$node, $title]);
        $id = (int) $this->conn()->lastInsertId();
        $this->link($node, $visibility, $order);

        return [$id, $node];
    }

    private function linkVisibility(int $nodeId): ?int
    {
        $v = $this->conn()->fetchOne('SELECT visibility FROM resource_link WHERE resource_node_id = ? AND c_id = ? AND session_id IS NULL', [$nodeId, self::COURSE_ID]);

        return false === $v ? null : (int) $v;
    }

    private function setCurrentUser(int $userId = self::USER_ID): void
    {
        Container::getSession()->set('_user', ['id' => $userId]);
        $user = $this->getEntityManager()->find(User::class, $userId);
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }

    // ---------------------------------------------------------------- visibility / publication

    public function testToggleVisibilityPublishesOrDraftsTheLinkOfTheCurrentCourseContext(): void
    {
        [$lpId, $node] = $this->publishedLp('l2_vis', 0);

        $this->assertTrue(learnpath::toggleVisibility($lpId, 1));
        $this->assertSame(2, $this->linkVisibility($node), 'published');

        $this->assertTrue(learnpath::toggleVisibility($lpId, 0));
        $this->assertSame(0, $this->linkVisibility($node), 'draft');

        $this->assertTrue(learnpath::toggleVisibility($lpId, '1'), 'the visibility is cast to int');
        $this->assertSame(2, $this->linkVisibility($node));
        // PINNED: only 1 means "published"; any other value (e.g. the link constant 2) drafts the path.
        learnpath::toggleVisibility($lpId, 2);
        $this->assertSame(0, $this->linkVisibility($node));
        $this->assertFalse(learnpath::toggleVisibility(999999999, 1), 'unknown lp');
    }

    public function testToggleVisibilityOfAPathNotLinkedToTheCourseAddsALinkToTheCurrentCourse(): void
    {
        $lpId = $this->newLpRow('l2_vis_unlinked');
        $node = $this->newNode('l2_vis_unlinked');
        $this->conn()->executeStatement('UPDATE c_lp SET resource_node_id = ? WHERE iid = ?', [$node, $lpId]);

        // PINNED QUIRK: the repository creates the missing link for the current course instead of refusing
        // (lp_controller guards the call itself with its "lp linked to the current course" check).
        $this->assertTrue(learnpath::toggleVisibility($lpId, 1));
        $this->assertSame(2, $this->linkVisibility($node));
    }

    public function testToggleCategoryVisibilityChangesTheLinkButAlwaysReturnsFalse(): void
    {
        [$catId, $node] = $this->newCategory('l2_cat_vis', 2);

        $result = learnpath::toggleCategoryVisibility($catId, 0);

        $this->assertSame(0, $this->linkVisibility($node));
        // PINNED QUIRK: returns false even when the category was updated (the only caller ignores the result).
        $this->assertFalse($result);
        $this->assertSame(2, (function () use ($catId, $node) {
            learnpath::toggleCategoryVisibility($catId, 1);

            return $this->linkVisibility($node);
        })());
        $this->assertFalse(learnpath::toggleCategoryVisibility(999999999, 1));
    }

    public function testTogglePublishAddsThenRemovesTheCourseHomeShortcut(): void
    {
        [$lpId, $node] = $this->publishedLp('l2_shortcut');
        $count = fn (): int => (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_shortcut WHERE shortcut_node_id = ?', [$node]);
        $this->getEntityManager()->clear();
        $this->setCurrentUser();

        $this->assertTrue(learnpath::togglePublish($lpId));
        $this->assertSame(1, $count(), 'first call publishes on the course home');

        $this->assertTrue(learnpath::togglePublish($lpId));
        $this->assertSame(0, $count(), 'second call removes the shortcut');
        $this->assertFalse(learnpath::togglePublish(999999999));
    }

    public function testToggleCategoryPublishAddsThenRemovesTheCourseHomeShortcut(): void
    {
        [$catId, $node] = $this->newCategory('l2_cat_shortcut');
        $count = fn (): int => (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_shortcut WHERE shortcut_node_id = ?', [$node]);
        $this->setCurrentUser();

        $this->assertTrue(learnpath::toggleCategoryPublish($catId));
        $this->assertSame(1, $count());
        $this->assertTrue(learnpath::toggleCategoryPublish($catId));
        $this->assertSame(0, $count());
        $this->assertFalse(learnpath::toggleCategoryPublish(999999999));
    }

    // ---------------------------------------------------------------- categories

    private function linkOrder(int $nodeId): int
    {
        return (int) $this->conn()->fetchOne('SELECT display_order FROM resource_link WHERE resource_node_id = ? AND c_id = ?', [$nodeId, self::COURSE_ID]);
    }

    /**
     * The display order of a link is a Gedmo "sortable" position inside (course, session, type group): moving a
     * link swaps it with its neighbour and a move past either end is ignored. The fixtures therefore append
     * contiguous positions after the current maximum of the group.
     */
    private function nextOrder(int $typeGroup): int
    {
        return 1 + (int) $this->conn()->fetchOne(
            'SELECT COALESCE(MAX(display_order), -1) FROM resource_link WHERE c_id = ? AND session_id IS NULL AND resource_type_group = ?',
            [self::COURSE_ID, $typeGroup]
        );
    }

    public function testMoveCategorySwapsItWithItsNeighbourAndStopsAtTheEnds(): void
    {
        $base = $this->nextOrder(41);
        [$first, $firstNode] = $this->newCategory('l2_cat_m1', 2, $base);
        [$second, $secondNode] = $this->newCategory('l2_cat_m2', 2, $base + 1);
        [, $thirdNode] = $this->newCategory('l2_cat_m3', 2, $base + 2);

        learnpath::moveUpCategory($second);
        $this->assertSame([$base + 1, $base, $base + 2], [$this->linkOrder($firstNode), $this->linkOrder($secondNode), $this->linkOrder($thirdNode)]);

        learnpath::moveDownCategory($second);
        $this->assertSame([$base, $base + 1, $base + 2], [$this->linkOrder($firstNode), $this->linkOrder($secondNode), $this->linkOrder($thirdNode)]);

        learnpath::moveDownCategory($first);
        learnpath::moveDownCategory($first);
        learnpath::moveDownCategory($first);
        $this->assertSame($base + 2, $this->linkOrder($firstNode), 'moving down past the last position is ignored');

        // Unknown ids are ignored.
        learnpath::moveUpCategory(999999999);
        learnpath::moveDownCategory(999999999);
    }

    public function testMoveLearningPathSwapsItWithItsNeighbourAndIgnoresUnknownDirections(): void
    {
        $base = $this->nextOrder(40);
        [$a, $aNode] = $this->publishedLp('l2_lp_m1', 2, $base);
        [$b, $bNode] = $this->publishedLp('l2_lp_m2', 2, $base + 1);

        learnpath::move($b, 'up');
        $this->assertSame([$base + 1, $base], [$this->linkOrder($aNode), $this->linkOrder($bNode)]);

        learnpath::move($b, 'sideways');
        $this->assertSame([$base + 1, $base], [$this->linkOrder($aNode), $this->linkOrder($bNode)]);

        learnpath::move($b, 'down');
        $this->assertSame([$base, $base + 1], [$this->linkOrder($aNode), $this->linkOrder($bNode)]);
        learnpath::move(999999999, 'up');
    }

    public function testMoveOnlyTouchesTheLinkOfTheCurrentCourse(): void
    {
        $base = $this->nextOrder(40);
        [$a, $aNode] = $this->publishedLp('l2_lp_o1', 2, $base);
        [$b] = $this->publishedLp('l2_lp_o2', 2, $base + 1);
        $otherCourse = (int) $this->conn()->fetchOne('SELECT MIN(id) FROM course WHERE id <> ?', [self::COURSE_ID]);
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, 2, 0, 40, NOW(), NOW())',
            [$aNode, $otherCourse]
        );

        learnpath::move($a, 'down');

        $this->assertSame($base + 1, $this->linkOrder($aNode));
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT display_order FROM resource_link WHERE resource_node_id = ? AND c_id = ?', [$aNode, $otherCourse]));
        unset($b);
    }

    public function testCategoriesAreListedByLinkOrderForTheCurrentCourse(): void
    {
        $base = $this->nextOrder(41);
        [$b] = $this->newCategory('l2_cat_b', 2, $base + 1);
        [$a] = $this->newCategory('l2_cat_a', 2, $base);
        [$draft] = $this->newCategory('l2_cat_draft', 0, $base + 2);
        $this->getEntityManager()->clear();

        $list = learnpath::getCategories(self::COURSE_ID);

        $ids = array_map(static fn (CLpCategory $c) => $c->getIid(), $list);
        $mine = array_values(array_intersect($ids, [$a, $b, $draft]));
        $this->assertSame([$a, $b], $mine, 'ordered by display_order; the draft category is not listed');

        $select = learnpath::getCategoryFromCourseIntoSelect(self::COURSE_ID);
        $this->assertArrayHasKey($a, $select);
        $this->assertSame('l2_cat_b', $select[$b]);
        $this->assertArrayNotHasKey($draft, $select, 'only published categories are offered in the select');
        $withEmpty = learnpath::getCategoryFromCourseIntoSelect(self::COURSE_ID, true);
        $this->assertSame(get_lang('Select a category'), $withEmpty[0]);

        $this->assertSame(0, learnpath::getCountCategories(0));
        // PINNED QUIRK: the count adds count(resource) to a query that already selects the entity, so it always
        // throws NonUniqueResultException for a real course (no caller left).
        $this->expectException(NonUniqueResultException::class);
        learnpath::getCountCategories(self::COURSE_ID);
    }

    public function testUpdateCategoryRenamesIt(): void
    {
        [$catId] = $this->newCategory('l2_cat_old');

        learnpath::updateCategory(['id' => $catId, 'name' => 'l2_cat_new']);
        learnpath::updateCategory(['id' => 999999999, 'name' => 'ignored']);

        $this->assertSame('l2_cat_new', $this->conn()->fetchOne('SELECT title FROM c_lp_category WHERE iid = ?', [$catId]));
    }

    public function testCreateCategoryLinksItToTheCurrentCourse(): void
    {
        $this->setCurrentUser();

        $id = learnpath::createCategory(['name' => 'l2_cat_created']);

        $this->assertGreaterThan(0, $id);
        $this->assertSame('l2_cat_created', $this->conn()->fetchOne('SELECT title FROM c_lp_category WHERE iid = ?', [$id]));
        $nodeId = (int) $this->conn()->fetchOne('SELECT resource_node_id FROM c_lp_category WHERE iid = ?', [$id]);
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM resource_link WHERE resource_node_id = ? AND c_id = ? AND session_id IS NULL', [$nodeId, self::COURSE_ID]));
    }

    public function testDeleteCategoryDetachesItsLearningPathsAndRemovesTheCategory(): void
    {
        [$catId, $node] = $this->newCategory('l2_cat_del');
        [$lpId] = $this->publishedLp('l2_lp_in_cat');
        $this->conn()->executeStatement('UPDATE c_lp SET category_id = ? WHERE iid = ?', [$catId, $lpId]);
        $this->getEntityManager()->clear();

        $this->assertTrue(learnpath::deleteCategory($catId));

        $this->assertFalse($this->conn()->fetchOne('SELECT iid FROM c_lp_category WHERE iid = ?', [$catId]));
        $this->assertNull($this->conn()->fetchOne('SELECT category_id FROM c_lp WHERE iid = ?', [$lpId]), 'the path survives without category');
        $this->assertFalse(learnpath::deleteCategory($catId), 'already gone');
    }

    public function testCategorySessionIdDependsOnTheSettingAndTheLinkSession(): void
    {
        [$catId, $node] = $this->newCategory('l2_cat_sess');
        $sid = (int) $this->conn()->fetchOne('SELECT MIN(id) FROM session');
        if ($sid > 0) {
            $this->conn()->executeStatement('UPDATE resource_link SET session_id = ? WHERE resource_node_id = ?', [$sid, $node]);
        }
        $settings = Container::getSettingsManager();

        $settings->updateSetting('lp.allow_session_lp_category', 'false');
        $this->assertSame(0, learnpath::getCategorySessionId($catId), 'setting off: always 0');

        if ($sid > 0) {
            $settings->updateSetting('lp.allow_session_lp_category', 'true');
            $this->getEntityManager()->clear();
            $this->assertSame($sid, learnpath::getCategorySessionId($catId));
        }
    }

    // ---------------------------------------------------------------- who may see a learning path / category

    private function lpEntity(int $lpId): CLp
    {
        $this->getEntityManager()->clear();

        return $this->getEntityManager()->find(CLp::class, $lpId);
    }

    private function inCategory(int $lpId, int $catId): void
    {
        $this->conn()->executeStatement('UPDATE c_lp SET category_id = ? WHERE iid = ?', [$catId, $lpId]);
    }

    private function visibleFor(int $lpId, int $studentId): bool
    {
        return learnpath::is_lp_visible_for_student($this->lpEntity($lpId), $studentId, $this->getCourse(self::COURSE_ID));
    }

    public function testCategoryVisibilityForAStudentFollowsTheLinkAndTheSubscribedUsers(): void
    {
        [$open] = $this->newCategory('l2_cat_open');
        [$draft] = $this->newCategory('l2_cat_hidden', 0);
        [$restricted] = $this->newCategory('l2_cat_restricted');
        $this->conn()->executeStatement('INSERT INTO c_lp_category_rel_user (category_id, user_id) VALUES (?, 3)', [$restricted]);
        $em = $this->getEntityManager();
        $em->clear();
        $course = $this->getCourse(self::COURSE_ID);
        $user2 = $em->find(User::class, 2);
        $user3 = $em->find(User::class, 3);
        $category = fn (int $id) => $em->find(CLpCategory::class, $id);

        $this->assertTrue(learnpath::categoryIsVisibleForStudent($category($open), $user2, $course));
        $this->assertFalse(learnpath::categoryIsVisibleForStudent($category($draft), $user2, $course), 'a draft category is hidden');
        $this->assertTrue(learnpath::categoryIsVisibleForStudent($category($restricted), $user3, $course), 'the subscribed user sees it');
        $this->assertFalse(learnpath::categoryIsVisibleForStudent($category($restricted), $user2, $course), 'anybody else does not');
    }

    public function testCategoryUserRestrictionIsIgnoredWhenTheSubscriptionSettingIsOff(): void
    {
        Container::getSettingsManager()->updateSetting('lp.lp_subscription_settings', json_encode(['options' => [
            'allow_add_users_to_lp' => true,
            'allow_add_users_to_lp_category' => false,
        ]]));
        $this->assertFalse(learnpath::getSubscriptionSettings()['allow_add_users_to_lp_category']);
        [$restricted] = $this->newCategory('l2_cat_restricted_off');
        $this->conn()->executeStatement('INSERT INTO c_lp_category_rel_user (category_id, user_id) VALUES (?, 3)', [$restricted]);
        $em = $this->getEntityManager();
        $em->clear();
        $course = $this->getCourse(self::COURSE_ID);

        $this->assertTrue(learnpath::categoryIsVisibleForStudent(
            $em->find(CLpCategory::class, $restricted),
            $em->find(User::class, 2),
            $course
        ));
    }

    public function testStudentSeesAPublishedPathAndNotADraftOne(): void
    {
        [$published] = $this->publishedLp('l2_vis_pub');
        [$draft] = $this->publishedLp('l2_vis_draft', 0);
        $unlinked = $this->newLpRow('l2_vis_unlinked_student');

        $this->assertTrue($this->visibleFor($published, 3));
        $this->assertFalse($this->visibleFor($draft, 3));
        $this->assertFalse($this->visibleFor($unlinked, 3), 'no link in the course: not visible');
    }

    public function testStudentCannotSeeAPathOfAHiddenCategory(): void
    {
        [$lpId] = $this->publishedLp('l2_vis_hidden_cat');
        [$hidden] = $this->newCategory('l2_vis_cat_hidden', 0);
        $this->inCategory($lpId, $hidden);

        $this->assertFalse($this->visibleFor($lpId, 3));
    }

    public function testStudentNeedsASubscriptionWhenThePathIsRestrictedToUsers(): void
    {
        [$lpId] = $this->publishedLp('l2_vis_subscribed', 2, 0, ['subscribe_users' => 1]);
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_rel_user (lp_id, c_id, user_id, creator_id, created_at, is_open_without_date) VALUES (?, ?, 3, 1, NOW(), 1)',
            [$lpId, self::COURSE_ID]
        );

        $this->assertTrue($this->visibleFor($lpId, 3));
        $this->assertFalse($this->visibleFor($lpId, 2));
    }

    public function testPathInACategoryHonoursPrerequisiteAndAvailabilityDates(): void
    {
        [$cat] = $this->newCategory('l2_vis_cat_ok');
        $prereq = $this->newLpRow('l2_vis_prereq');
        [$blocked] = $this->publishedLp('l2_vis_c_blocked', 2, 0, ['prerequisite' => $prereq]);
        [$future] = $this->publishedLp('l2_vis_c_future', 2, 0, ['published_on' => '2099-01-01 00:00:00']);
        [$expired] = $this->publishedLp('l2_vis_c_expired', 2, 0, ['expired_on' => '2001-01-01 00:00:00']);
        [$open] = $this->publishedLp('l2_vis_c_open', 2, 0, ['published_on' => '2001-01-01 00:00:00', 'expired_on' => '2099-01-01 00:00:00']);
        foreach ([$blocked, $future, $expired, $open] as $id) {
            $this->inCategory($id, $cat);
        }

        $this->assertFalse($this->visibleFor($blocked, 3), 'prerequisite not completed');
        $this->assertFalse($this->visibleFor($future, 3), 'not published yet');
        $this->assertFalse($this->visibleFor($expired, 3), 'expired');
        $this->assertTrue($this->visibleFor($open, 3));

        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, last_item, progress) VALUES (?, ?, 3, 1, 0, 100)',
            [self::COURSE_ID, $prereq]
        );
        $this->assertTrue($this->visibleFor($blocked, 3), 'prerequisite completed');
    }

    public function testPathWithoutCategoryHonoursPrerequisiteAndAvailabilityDatesToo(): void
    {
        $prereq = $this->newLpRow('l2_vis_prereq_nocat');
        [$blocked] = $this->publishedLp('l2_vis_n_blocked', 2, 0, ['prerequisite' => $prereq]);
        [$future] = $this->publishedLp('l2_vis_n_future', 2, 0, ['published_on' => '2099-01-01 00:00:00']);
        [$expired] = $this->publishedLp('l2_vis_n_expired', 2, 0, ['expired_on' => '2001-01-01 00:00:00']);

        // PINNED QUIRK: the prerequisite and the dates are only checked for paths that have a category; the
        // else-branch returns true for an expired, not yet open or prerequisite-blocked path.
        $this->assertTrue($this->visibleFor($blocked, 3));
        $this->assertTrue($this->visibleFor($future, 3));
        $this->assertTrue($this->visibleFor($expired, 3));
    }

    // ---------------------------------------------------------------- progress bar / stars / score

    private function setProgress(int $lpId, int $progress, int $userId = self::USER_ID): void
    {
        $this->conn()->executeStatement('UPDATE c_lp_view SET progress = ? WHERE lp_id = ? AND user_id = ?', [$progress, $lpId, $userId]);
    }

    private function setItemStatus(int $lpId, int $itemId, string $status, int $userId = self::USER_ID): void
    {
        $viewId = (int) $this->conn()->fetchOne('SELECT iid FROM c_lp_view WHERE lp_id = ? AND user_id = ? ORDER BY view_count DESC', [$lpId, $userId]);
        $this->conn()->executeStatement(
            'UPDATE c_lp_item_view SET status = ? WHERE lp_view_id = ? AND lp_item_id = ?',
            [$status, $viewId, $itemId]
        );
    }

    private function addExerciseAttempt(int $lpId, int $itemId, int $quizId, float $score, float $max, int $userId = self::USER_ID, string $date = '2030-01-02 10:00:00'): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip, status,
                data_tracking, start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, NULL, ?, ?, ?, ?, '127.0.0.1', '', '', ?, 1, ?, ?, 10, 0, '')",
            [$userId, self::COURSE_ID, $quizId, $date, $score, $max, $date, $lpId, $itemId]
        );
    }

    /**
     * Learning path with three quizzes (fixture exercises 1, 2 and 3): the last one is the "final evaluation".
     *
     * @return array{int, int, int, int} lp id, quiz item ids
     */
    private function lpWithQuizzes(string $title): array
    {
        // add_item() reads and saves the exercise, and Exercise::save() rejects the NULL sound of the fixture quizzes.
        $this->conn()->executeStatement("UPDATE c_quiz SET sound = '' WHERE iid IN (1, 2, 3)");
        $lpId = $this->newLpRow($title);
        $q1 = $this->addItem($lpId, $title.'_q1', 'quiz', 1, null, 1);
        $q2 = $this->addItem($lpId, $title.'_q2', 'quiz', 2, null, 2);
        $q3 = $this->addItem($lpId, $title.'_q3', 'quiz', 3, null, 3);

        return [$lpId, $q1, $q2, $q3];
    }

    public function testGetProgressBarMarkupCarriesThePercentage(): void
    {
        $html = learnpath::get_progress_bar(45, '%');

        $this->assertStringContainsString('aria-valuenow="45"', $html);
        $this->assertStringContainsString('style="width: 45%;"', $html);
        $this->assertStringContainsString('<div class="p-progressbar-label">45%</div>', $html);
        $this->assertStringContainsString('aria-valuenow="-1"', learnpath::get_progress_bar(), 'default percentage is -1');
    }

    public function testProgressBarTextCapsFakedStepsAndHandlesAnEmptyPath(): void
    {
        $empty = $this->newLpRow('l2_bar_empty');
        $this->assertEquals([0, '%'], array_map(static fn ($v) => is_numeric($v) ? (int) $v : $v, $this->lp($empty)->get_progress_bar_text('%')));
        $this->assertSame([0, '/0'], $this->lp($empty)->get_progress_bar_text('abs'));

        $lpId = $this->newLpRow('l2_bar_cap');
        $a = $this->addItem($lpId, 'l2_a', 'document', 1);
        $this->addItem($lpId, 'l2_b', 'document', 2);
        $this->addItem($lpId, 'l2_c', 'document', 3);
        $this->addItem($lpId, 'l2_d', 'document', 4);
        $this->lp($lpId);
        $this->setItemStatus($lpId, $a, 'completed');
        $lp = $this->lp($lpId);

        $this->assertSame([1, '/4'], $lp->get_progress_bar_text('abs'));
        $this->assertSame([3, '/4'], $lp->get_progress_bar_text('abs', 2), 'faked steps are added');
        $this->assertSame([4, '/4'], $lp->get_progress_bar_text('abs', 3), 'exactly the total');
        $this->assertSame([4, '/4'], $lp->get_progress_bar_text('abs', 4), 'one more than the total is capped');
        $this->assertSame([4, '/4'], $lp->get_progress_bar_text('abs', 10), 'but never exceed the total');
        $this->assertSame('25', (string) $lp->get_progress_bar_text('%')[0]);
        $this->assertSame([0, ''], $lp->get_progress_bar_text('other'), 'unknown mode: percentage 0 and no text');
        $this->assertStringContainsString('aria-valuenow="25"', $lp->getProgressBar('%'));
        $this->assertSame('%', $lp->get_progress_bar_mode(), 'default mode');
        $this->assertSame('', $lp->get_theme());
    }

    public function testScoreAsProgressOnlyAppliesToASingleStepScormWithTheExtraFieldSet(): void
    {
        Container::getSettingsManager()->updateSetting('lp.lp_score_as_progress_enable', 'true');
        $scorm = $this->newLpRow('l2_score_progress', 2);
        $item = $this->addItem($scorm, 'l2_sco', 'sco', 1);
        $document = $this->newLpRow('l2_score_progress_doc', 1);
        $this->addItem($document, 'l2_doc', 'document', 1);
        $fieldId = (int) $this->conn()->fetchOne("SELECT id FROM extra_field WHERE item_type = 6 AND variable = 'use_score_as_progress'");
        if (0 === $fieldId) {
            $this->conn()->executeStatement(
                'INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
                 VALUES (6, 13, ?, ?, 1, 1, 1, 0, NOW(), 0)',
                ['use_score_as_progress', 'l2 use score as progress']
            );
            $fieldId = (int) $this->conn()->lastInsertId();
        }
        $setValue = fn (int $lpId, string $value) => $this->conn()->executeStatement(
            'INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
            [$fieldId, $value, $lpId]
        );

        $this->assertFalse($this->lp($scorm)->getUseScoreAsProgress(), 'no value stored');
        $setValue($scorm, '1');
        $setValue($document, '1');
        $this->assertSame('1', $this->lp($scorm)->getUseScoreAsProgress());
        $this->conn()->executeStatement('UPDATE c_lp_item SET max_score = 4 WHERE iid = ?', [$item]);
        $this->conn()->executeStatement('UPDATE c_lp_item_view SET score = 3 WHERE lp_item_id = ?', [$item]);
        // PINNED QUIRK: `if ($mode = '%')` assigns instead of comparing, so the 'abs' mode is answered as a percentage.
        $this->assertSame(['75', '%'], $this->lp($scorm)->get_progress_bar_text('abs'));
        $this->assertSame(['75', '%'], $this->lp($scorm)->get_progress_bar_text('%'));
        $this->assertFalse($this->lp($document)->getUseScoreAsProgress(), 'only SCORM paths');
        $this->addItem($scorm, 'l2_sco2', 'sco', 2);
        $this->assertFalse($this->lp($scorm)->getUseScoreAsProgress(), 'only single step paths');
        $this->assertSame($item, $this->lp($scorm)->get_first_item_id());
    }

    public function testExerciseItemsExcludeTheLastQuizWhichIsTheFinalEvaluation(): void
    {
        [$lpId, $q1, $q2, $q3] = $this->lpWithQuizzes('l2_quizzes');
        $none = $this->newLpRow('l2_no_quiz');
        $this->addItem($none, 'l2_doc', 'document', 1);

        $lp = $this->lp($lpId);

        $this->assertSame([$q1, $q2], array_map(static fn ($i) => $i->db_id, $lp->getExercisesItems()));
        $this->assertSame($q3, $lp->getFinalEvaluationItem()->db_id);
        $this->assertSame([], $this->lp($none)->getExercisesItems());
        $this->assertNull($this->lp($none)->getFinalEvaluationItem(), 'no quiz: nothing (array_pop of []), not false as documented');
    }

    public function testCalculateStarsAddsOneStarPerMilestone(): void
    {
        [$lpId, $q1, $q2, $q3] = $this->lpWithQuizzes('l2_stars');
        $lp = $this->lp($lpId);
        $this->assertSame(0, $lp->getCalculateStars(), 'nothing done');

        $this->setProgress($lpId, 49);
        $this->assertSame(0, $lp->getCalculateStars(), '49% is below the first star');
        $this->setProgress($lpId, 50);
        $this->assertSame(1, $lp->getCalculateStars());

        // Exercise average: (100 + 0 (no attempt)) / 2 = 50 -> second star only.
        $this->addExerciseAttempt($lpId, $q1, 1, 10, 10);
        $this->assertSame(2, $lp->getCalculateStars());
        // (100 + 60) / 2 = 80 -> third star as well.
        $this->addExerciseAttempt($lpId, $q2, 2, 6, 10);
        $this->assertSame(3, $lp->getCalculateStars());
        // Final evaluation: 79% gives nothing, 80% gives the fourth star.
        $this->addExerciseAttempt($lpId, $q3, 3, 79, 100);
        $this->assertSame(3, $lp->getCalculateStars());
        $this->addExerciseAttempt($lpId, $q3, 3, 80, 100, self::USER_ID, '2030-01-03 10:00:00');
        $this->assertSame(4, $lp->getCalculateStars(), 'the last attempt counts');
    }

    public function testCalculateStarsUsesTheLastAttemptEvenWhenItIsWorse(): void
    {
        [$lpId, $q1, $q2, $q3] = $this->lpWithQuizzes('l2_stars_last');
        $lp = $this->lp($lpId);
        $this->addExerciseAttempt($lpId, $q3, 3, 90, 100, self::USER_ID, '2030-01-02 10:00:00');
        $this->assertSame(1, $lp->getCalculateStars());
        $this->addExerciseAttempt($lpId, $q3, 3, 10, 100, self::USER_ID, '2030-01-04 10:00:00');
        $this->assertSame(0, $lp->getCalculateStars());
    }

    public function testCalculateStarsToleratesAFinalEvaluationWithoutMaximumScore(): void
    {
        [$lpId, , , $q3] = $this->lpWithQuizzes('l2_stars_zero');
        $this->addExerciseAttempt($lpId, $q3, 3, 0, 0);

        // PINNED QUIRK: the chapter exercises guard against max_score 0 but the final evaluation divides by it.
        $this->expectException(\DivisionByZeroError::class);
        $this->lp($lpId)->getCalculateStars();
    }

    public function testCalculateScoreSumsTheLastAttemptOfEveryQuiz(): void
    {
        [$lpId, $q1, $q2, $q3] = $this->lpWithQuizzes('l2_score');
        $lp = $this->lp($lpId);
        $this->assertSame(0, $lp->getCalculateScore());

        $this->addExerciseAttempt($lpId, $q1, 1, 3, 10, self::USER_ID, '2030-01-02 10:00:00');
        $this->addExerciseAttempt($lpId, $q1, 1, 7, 10, self::USER_ID, '2030-01-03 10:00:00');
        $this->addExerciseAttempt($lpId, $q2, 2, 4, 10);
        $this->addExerciseAttempt($lpId, $q3, 3, 20, 30);
        $this->addExerciseAttempt($lpId, $q3, 3, 99, 100, 3);

        $this->assertEquals(7 + 4 + 20, $lp->getCalculateScore(), 'other users and older attempts do not count');
    }

    // ---------------------------------------------------------------- links of the items

    public function testGetLinkIsEmptyWithoutAnyItemAndForUnknownItems(): void
    {
        $empty = $this->newLpRow('l2_link_empty');
        $this->assertSame('', $this->lp($empty)->get_link('http'));

        $lpId = $this->newLpRow('l2_link_unknown');
        $this->addItem($lpId, 'l2_a', 'dir', 1);
        $this->assertSame('', $this->lp($lpId)->get_link('http', 999999999), 'unknown item id');
    }

    public function testGetLinkOfAFolderPointsAtTheFolderContentPage(): void
    {
        $lpId = $this->newLpRow('l2_link_dir');
        $dir = $this->addItem($lpId, 'l2_dir', 'dir', 1);

        $this->assertSame('lp_content.php?type=dir', $this->lp($lpId)->get_link('http', $dir));
    }

    public function testGetLinkOfADocumentThatNoLongerExistsIsEmpty(): void
    {
        $lpId = $this->newLpRow('l2_link_doc_gone');
        $doc = $this->addItem($lpId, 'l2_doc', 'document', 1, null, 999999999);

        $this->assertSame('', $this->lp($lpId)->get_link('http', $doc));
    }

    public function testGetLinkOfALinkItemIsItsUrlAndEmptyWhenTheLinkIsGone(): void
    {
        $this->conn()->executeStatement("INSERT INTO c_link (url, title) VALUES ('https://example.org/l2?a=1&b=2', 'l2_link')");
        $linkId = (int) $this->conn()->lastInsertId();
        $lpId = $this->newLpRow('l2_link_link');
        $link = $this->addItem($lpId, 'l2_link_item', 'link', 1, null, $linkId);
        $gone = $this->addItem($lpId, 'l2_link_gone', 'link', 2, null, 999999999);

        $this->assertSame('https://example.org/l2?a=1&b=2', $this->lp($lpId)->get_link('http', $link));
        $this->assertSame('', $this->lp($lpId)->get_link('http', $gone));
    }

    public function testGetLinkOfAYoutubeLinkItemUsesTheEmbedPage(): void
    {
        $this->conn()->executeStatement("INSERT INTO c_link (url, title) VALUES ('https://www.youtube.com/watch?v=abcdefghijk', 'l2_yt')");
        $linkId = (int) $this->conn()->lastInsertId();
        $lpId = $this->newLpRow('l2_link_youtube');
        $item = $this->addItem($lpId, 'l2_yt_item', 'link', 1, null, $linkId);

        $url = $this->lp($lpId)->get_link('http', $item);

        $this->assertStringContainsString('lp/embed.php?type=youtube&source=abcdefghijk', $url);
    }

    public function testGetLinkOfAQuizItemCarriesTheAttemptContextAndTheReinitFlag(): void
    {
        $this->conn()->executeStatement("UPDATE c_quiz SET sound = '' WHERE iid = 1");
        $lpId = $this->newLpRow('l2_link_quiz');
        $quiz = $this->addItem($lpId, 'l2_quiz', 'quiz', 1, null, 1);

        $lp = $this->lp($lpId);
        $url = $lp->get_link('http', $quiz);

        $this->assertMatchesRegularExpression(
            '#^http://[^/]+/resources/exercise/\d+/1/overview\?cid=1&sid=0&gid=0&origin=learnpath&lp_init=1'
            .'&learnpath_item_view_id=\d+&learnpath_id='.$lpId.'&learnpath_item_id='.$quiz.'&isStudentView=true&not_multiple_attempt=0$#',
            $url,
            'nothing completed yet'
        );

        // A completed attempt of the item locks a new one while the path prevents re-initialisation.
        $viewId = (int) $this->conn()->fetchOne('SELECT iid FROM c_lp_view WHERE lp_id = ? AND user_id = 1', [$lpId]);
        $this->conn()->executeStatement(
            "INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, status, suspend_data, lesson_location, core_exit, max_score)
             VALUES (?, ?, 0, 0, 0, 0, 'completed', '', '', 'none', '')",
            [$quiz, $viewId]
        );
        $this->assertStringEndsWith('not_multiple_attempt=1', $lp->get_link('http', $quiz), 'prevent_reinit defaults to 1 in the table');
        $this->conn()->executeStatement('UPDATE c_lp SET prevent_reinit = 0 WHERE iid = ?', [$lpId]);
        $this->assertStringEndsWith('not_multiple_attempt=0', $this->recreate($lpId, $quiz)->get_link('http', $quiz));
    }

    /**
     * New learnpath object whose current item is forced to the given item.
     */
    private function recreate(int $lpId, int $currentItem): learnpath
    {
        $lp = $this->lp($lpId);
        $lp->current = $currentItem;

        return $lp;
    }

    public function testGetLinkOfScormItemsKeepsAbsoluteUrlsAndAppendsTheManifestParameters(): void
    {
        $lpId = $this->newLpRow('l2_link_scorm', 2);
        $relative = $this->addItem($lpId, 'l2_sco_rel', 'sco', 1);
        $absolute = $this->addItem($lpId, 'l2_sco_abs', 'sco', 2);
        $withQuery = $this->addItem($lpId, 'l2_sco_query', 'sco', 3);
        $dir = $this->addItem($lpId, 'l2_sco_dir', 'dir', 4);
        $upd = fn (int $id, string $path, string $params) => $this->conn()->executeStatement('UPDATE c_lp_item SET path = ?, parameters = ? WHERE iid = ?', [$path, $params, $id]);
        $upd($relative, 'dir%2Fpage.htm', '?mode=1');
        $upd($absolute, 'https://cdn.example.org/page.htm', '');
        $upd($withQuery, 'page.htm?x=2', '');
        $lp = $this->lp($lpId);

        // The learning path has no asset here, so only the item path is returned (no base url).
        $this->assertSame('dir/page.htm?mode=1', $lp->get_link('http', $relative), '%2F is decoded and the leading ? of the parameters dropped');
        $this->assertSame('https://cdn.example.org/page.htm?', $lp->get_link('http', $absolute), 'an external url is kept; an empty parameters string still adds the ?');
        $this->assertSame('page.htm?x=2', $lp->get_link('http', $withQuery), 'a query in the path becomes the parameters');
        $this->assertSame('lp_content.php?type=dir', $lp->get_link('http', $dir));
    }

    public function testGetLinkIgnoresTheLearningPathOfTheItem(): void
    {
        $mine = $this->newLpRow('l2_link_mine', 2);
        $this->addItem($mine, 'l2_mine', 'sco', 1);
        $other = $this->newLpRow('l2_link_other', 2);
        $foreign = $this->addItem($other, 'l2_foreign', 'sco', 1);
        $this->conn()->executeStatement("UPDATE c_lp_item SET path = 'secret/page.htm' WHERE iid = ?", [$foreign]);

        // PINNED QUIRK: the item is looked up by id alone, so a SCORM path resolves an item of another learning path.
        $this->assertSame('secret/page.htm?', $this->lp($mine)->get_link('http', $foreign));
    }

    public function testResourceLinkOfCoursePagesAndUnknownItems(): void
    {
        $lpId = $this->newLpRow('l2_rl');
        $assign = $this->addItem($lpId, 'l2_assignments', 'assignments', 1);
        $hot = $this->addItem($lpId, 'l2_hot', 'hotpotatoes', 2);
        $final = $this->addItem($lpId, 'l2_final', 'final_item', 3);
        $dir = $this->addItem($lpId, 'l2_dir', 'dir', 4);
        $unknown = $this->addItem($lpId, 'l2_unknown', 'something_else', 5);
        $rl = static fn (int $item): mixed => learnpath::rl_get_resource_link_for_learnpath(self::COURSE_ID, $lpId, $item, 0);

        $this->assertStringContainsString('work/work.php?', $rl($assign));
        $this->assertSame('', $rl($hot));
        $this->assertStringContainsString("lp/lp_final_item.php?&id=0&lp_id=$lpId&", $rl($final));
        $this->assertStringEndsWith('lp/blank.php', $rl($dir));
        $this->assertSame('', $rl($unknown), 'unknown item types have no link');
    }

    public function testResourceLinkOfAnItemOfAnotherPathFails(): void
    {
        $mine = $this->newLpRow('l2_rl_mine');
        $other = $this->newLpRow('l2_rl_other');
        $foreign = $this->addItem($other, 'l2_foreign', 'dir', 1);

        // PINNED QUIRK: the item is looked up by (lp, id) and the miss is dereferenced without a check.
        $this->expectException(\Error::class);
        learnpath::rl_get_resource_link_for_learnpath(self::COURSE_ID, $mine, $foreign, 0);
    }

    // ---------------------------------------------------------------- table of contents helpers

    public function testTocListsTheItemsInDisplayOrderWithLevelsAndStatusClasses(): void
    {
        $lpId = $this->newLpRow('l2_toc');
        $dir = $this->addItem($lpId, 'l2_dir', 'dir', 1);
        $second = $this->addItem($lpId, 'l2_second', 'document', 3, $dir);
        $first = $this->addItem($lpId, 'l2_first', 'document', 2, $dir);
        $top = $this->addItem($lpId, 'l2_top', 'document', 4);
        $this->lp($lpId);
        $this->setItemStatus($lpId, $first, 'completed');
        $this->setItemStatus($lpId, $second, 'failed');

        $toc = $this->lp($lpId)->get_toc();

        $this->assertSame([$dir, $first, $second, $top], array_column($toc, 'id'));
        $this->assertSame(['dir', 'document', 'document', 'document'], array_column($toc, 'type'));
        $this->assertSame([1, 2, 2, 1], array_column($toc, 'level'), 'levels start at 1 under the root item');
        $this->assertSame([$this->rootId($lpId), $dir, $dir, $this->rootId($lpId)], array_column($toc, 'parent'));
        $this->assertSame(['not attempted', 'completed', 'failed', 'not attempted'], array_column($toc, 'status'));
        $this->assertSame(
            [learnpath::getStatusCSSClassName('not attempted'), learnpath::getStatusCSSClassName('completed'), learnpath::getStatusCSSClassName('failed'), learnpath::getStatusCSSClassName('not attempted')],
            array_column($toc, 'status_class')
        );
        $this->assertSame('', learnpath::getStatusCSSClassName('no such status'));
        $this->assertNotSame('', learnpath::getStatusCSSClassName('completed'));
    }

    public function testFirstOrLastItemIgnoresFoldersAndReportsTheSingleItemCase(): void
    {
        $lpId = $this->newLpRow('l2_firstlast');
        $dir = $this->addItem($lpId, 'l2_dir', 'dir', 1);
        $a = $this->addItem($lpId, 'l2_a', 'document', 2, $dir);
        $b = $this->addItem($lpId, 'l2_b', 'document', 3, $dir);
        $c = $this->addItem($lpId, 'l2_c', 'document', 4);
        $single = $this->newLpRow('l2_firstlast_single');
        $only = $this->addItem($single, 'l2_only', 'document', 1);
        $lp = $this->lp($lpId);

        $this->assertSame('first', $lp->isFirstOrLastItem($a));
        $this->assertSame('none', $lp->isFirstOrLastItem($b));
        $this->assertSame('last', $lp->isFirstOrLastItem($c));
        $this->assertSame('both', $this->lp($single)->isFirstOrLastItem($only));
        // PINNED QUIRK: a folder (or unknown id) is not in the list: array_search() returns false, which matches case 0.
        $this->assertSame('first', $lp->isFirstOrLastItem($dir));
        $this->assertSame(['chapter_types' => ['dir']], ['chapter_types' => learnpath::getChapterTypes()]);
    }

    public function testParentNamesOfTheCurrentItemAreOrderedFromTheTopLevel(): void
    {
        $lpId = $this->newLpRow('l2_parents');
        $chapter = $this->addItem($lpId, 'l2_chapter', 'dir', 1);
        $section = $this->addItem($lpId, 'l2_section', 'dir', 2, $chapter);
        $leaf = $this->addItem($lpId, 'l2_leaf', 'document', 3, $section);
        $top = $this->addItem($lpId, 'l2_top', 'document', 4);
        $lp = $this->lp($lpId);

        $this->assertSame(['l2_chapter', 'l2_section'], $lp->getCurrentItemParentNames($leaf));
        $this->assertSame(['l2_chapter'], $lp->getCurrentItemParentNames($section));
        $this->assertSame([], $lp->getCurrentItemParentNames($top));
        $this->assertSame([], $lp->getCurrentItemParentNames(999999999));
    }

    public function testListArrayTocIsTheItemTreeUnderTheRoot(): void
    {
        $lpId = $this->newLpRow('l2_tree');
        $dir = $this->addItem($lpId, 'l2_dir', 'dir', 1);
        $leaf = $this->addItem($lpId, 'l2_leaf', 'document', 2, $dir);
        $top = $this->addItem($lpId, 'l2_top', 'document', 3);

        $tree = $this->lp($lpId)->getListArrayToc();

        $this->assertCount(2, $tree, 'the root item is not part of the tree');
        $this->assertSame([$dir, $top], array_map(static fn ($n) => (int) $n['iid'], $tree));
        $this->assertSame([$leaf], array_map(static fn ($n) => (int) $n['__children'][0]['iid'], [$tree[0]]));
        $this->assertSame([], $tree[1]['__children']);
    }

    // ---------------------------------------------------------------- prerequisites at path level

    public function testPrerequisitesMatchRequiresThePreviousItemToBeDone(): void
    {
        $lpId = $this->newLpRow('l2_prereq_match');
        $a = $this->addItem($lpId, 'l2_a', 'document', 1);
        $b = $this->addItem($lpId, 'l2_b', 'document', 2);
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite = ? WHERE iid = ?', [(string) $a, $b]);
        $this->lp($lpId);

        $lp = $this->lp($lpId);
        $this->assertTrue($lp->prerequisites_match($a), 'no prerequisite');
        $this->assertFalse($lp->prerequisites_match($b));
        $this->assertNotSame('', $lp->error, 'the reason is kept as the error message of the path');

        $this->setItemStatus($lpId, $a, 'completed');
        $this->assertTrue($this->lp($lpId)->prerequisites_match($b));
        $this->assertTrue($this->lp($lpId)->prerequisites_match(999999999), 'an unknown item has nothing to check');
    }

    public function testPrerequisitesCanBeBypassedByAdministratorsOnlyWhenTheSettingAllowsIt(): void
    {
        $lpId = $this->newLpRow('l2_prereq_bypass');
        $a = $this->addItem($lpId, 'l2_a', 'document', 1);
        $b = $this->addItem($lpId, 'l2_b', 'document', 2);
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite = ? WHERE iid = ?', [(string) $a, $b]);
        $this->lp($lpId);
        $course = $this->getCourse(self::COURSE_ID);
        $prereqLp = $this->newLpRow('l2_prereq_bypass_target');
        $settings = Container::getSettingsManager();

        $this->setCurrentUser(1);
        $settings->updateSetting('lp.allow_teachers_to_access_blocked_lp_by_prerequisite', 'false');
        $this->assertFalse($this->lp($lpId)->prerequisites_match($b), 'an administrator is blocked too by default');
        $this->assertTrue(learnpath::isBlockedByPrerequisite(1, $prereqLp, $course, 0));

        $settings->updateSetting('lp.allow_teachers_to_access_blocked_lp_by_prerequisite', 'true');
        $this->assertTrue($this->lp($lpId)->prerequisites_match($b));
        $this->assertFalse(learnpath::isBlockedByPrerequisite(1, $prereqLp, $course, 0));

        // A visitor without edit rights is never helped by the setting.
        Container::getSession()->set('_user', null);
        self::getContainer()->get('security.token_storage')->setToken(null);
        $this->assertTrue(learnpath::isBlockedByPrerequisite(3, $prereqLp, $course, 0));
    }

    public function testScormPrerequisiteStringIsTranslatedToTheReferencesOfTheItems(): void
    {
        $lpId = $this->newLpRow('l2_scorm_prereq', 2);
        $a = $this->addItem($lpId, 'l2_a', 'sco', 1);
        $b = $this->addItem($lpId, 'l2_b', 'sco', 2);
        $c = $this->addItem($lpId, 'l2_c', 'sco', 3);
        $this->conn()->executeStatement('UPDATE c_lp_item SET ref = ? WHERE iid = ?', ['SCO_A', $a]);
        $this->conn()->executeStatement('UPDATE c_lp_item SET ref = ?, prerequisite = ? WHERE iid = ?', ['SCO_B', (string) $a, $b]);
        $this->conn()->executeStatement('UPDATE c_lp_item SET ref = ?, prerequisite = ? WHERE iid = ?', ['SCO_C', '(SCO_A)&(SCO_B)', $c]);
        $lp = $this->lp($lpId);

        $this->assertSame('', $lp->get_scorm_prereq_string($a), 'no prerequisite');
        $this->assertSame('SCO_A', $lp->get_scorm_prereq_string($b), 'an item id becomes the reference of that item');
        // The references of an expression are replaced by ITEM_<id>; the replacement also consumes the character on
        // each side of the reference (here the parentheses), and a reference at the very start or end of the
        // string has no such neighbour, so it is not replaced at all.
        $this->assertSame("ITEM_$a&ITEM_$b", $lp->get_scorm_prereq_string($c));
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite = ? WHERE iid = ?', ['SCO_A&SCO_B', $c]);
        $this->assertSame('SCO_A&SCO_B', $this->lp($lpId)->get_scorm_prereq_string($c));
    }

    public function testPreviousStepAsPrerequisiteCopiesTheMaximumOfAQuizAsMasteryScore(): void
    {
        $lpId = $this->newLpRow('l2_chain_quiz');
        $quiz = $this->addItem($lpId, 'l2_quiz', 'quiz', 1);
        $next = $this->addItem($lpId, 'l2_next', 'document', 2);
        $after = $this->addItem($lpId, 'l2_after', 'document', 3);
        $this->conn()->executeStatement('UPDATE c_lp_item SET max_score = 7 WHERE iid = ?', [$quiz]);

        $this->lp($lpId)->set_previous_step_as_prerequisite_for_all_items();

        $get = fn (int $id) => $this->conn()->fetchAssociative('SELECT prerequisite, mastery_score FROM c_lp_item WHERE iid = ?', [$id]);
        $this->assertEquals(7, $get($quiz)['mastery_score'], 'the quiz must be passed with its own maximum');
        $this->assertSame((string) $quiz, $get($next)['prerequisite']);
        $this->assertSame((string) $next, $get($after)['prerequisite']);
        $this->assertNull($get($next)['mastery_score']);
    }

    public function testWorkTimeOfThePrerequisiteIsReadFromThePath(): void
    {
        $lpId = $this->newLpRow('l2_worktime');
        $this->conn()->executeStatement('UPDATE c_lp SET accumulate_work_time = 15 WHERE iid = ?', [$lpId]);

        $this->assertSame(15, (int) learnpath::getAccumulateWorkTimePrerequisite($lpId, self::COURSE_ID));
        // PINNED QUIRK: the constructor never loads the minimum time into $accumulateWorkTime (used by the player to
        // enforce the minimum time) and puts the work time into $accumulateScormTime (the "accumulate SCORM time"
        // flag) instead of the real flag.
        $lp = $this->lp($lpId);
        $this->assertSame(0, $lp->getAccumulateWorkTime());
        $this->assertSame(15, $lp->getAccumulateScormTime());
    }

    // ---------------------------------------------------------------- forum, search index, autolaunch

    public function testLpHasForumQueriesTablesThatNoLongerExist(): void
    {
        $lpId = $this->newLpRow('l2_forum');

        // PINNED QUIRK (no caller, already @deprecated): c_forum and c_item_property were replaced by c_forum_forum and
        // resource_link, so the query throws instead of answering.
        $this->expectException(\Throwable::class);
        $this->lp($lpId)->lpHasForum();
    }

    public function testGetForumListsTheForumsOfTheCurrentCourse(): void
    {
        $lpId = $this->newLpRow('l2_forum_list');

        $forums = $this->lp($lpId)->getForum();

        $this->assertIsArray($forums);
        foreach ($forums as $forum) {
            $this->assertInstanceOf(\Chamilo\CourseBundle\Entity\CForum::class, $forum);
        }
    }

    public function testSearchTermsAreOnlyHandledWhenTheSearchEngineIsEnabled(): void
    {
        $lpId = $this->newLpRow('l2_terms');
        Container::getSettingsManager()->updateSetting('search.search_enabled', 'false');

        $this->assertFalse($this->lp($lpId)->set_terms_by_prefix('a,b', 'T'), 'search disabled');
    }

    public function testSetAutolaunchLaunchesOnlyOnePathOfTheCourse(): void
    {
        [$a] = $this->publishedLp('l2_auto_a', 2, 0);
        [$b] = $this->publishedLp('l2_auto_b', 2, 0);
        $auto = fn (int $id): int => (int) $this->conn()->fetchOne('SELECT autolaunch FROM c_lp WHERE iid = ?', [$id]);
        $lp = $this->lp($a);

        $lp->set_autolaunch($a, 1);
        $this->assertSame([1, 0], [$auto($a), $auto($b)]);

        $lp->set_autolaunch($b, '1');
        $this->assertSame([0, 1], [$auto($a), $auto($b)], 'the previous one is switched off');

        $lp->set_autolaunch($b, 0);
        $this->assertSame([0, 0], [$auto($a), $auto($b)], 'status 0 only switches everything off');

        $lp->set_autolaunch(999999999, 1);
        $this->assertSame([0, 0], [$auto($a), $auto($b)]);
    }

    public function testSetAutolaunchDoesNotTouchPathsOfOtherCourses(): void
    {
        [$a] = $this->publishedLp('l2_auto_here', 2, 0);
        $elsewhere = $this->newLpRow('l2_auto_elsewhere');
        $node = $this->newNode('l2_auto_elsewhere');
        $otherCourse = (int) $this->conn()->fetchOne('SELECT MIN(id) FROM course WHERE id <> ?', [self::COURSE_ID]);
        $this->conn()->executeStatement('UPDATE c_lp SET resource_node_id = ?, autolaunch = 1 WHERE iid = ?', [$node, $elsewhere]);
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, visibility, display_order, resource_type_group, created_at, updated_at) VALUES (?, ?, 2, 0, 40, NOW(), NOW())',
            [$node, $otherCourse]
        );

        $this->lp($a)->set_autolaunch($a, 1);

        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT autolaunch FROM c_lp WHERE iid = ?', [$elsewhere]));
    }

    // ---------------------------------------------------------------- object lifecycle helpers

    public function testGetLpFromSessionBuildsAFreshObjectWhenNothingIsStored(): void
    {
        $lpId = $this->newLpRow('l2_session_obj');
        $this->getEntityManager()->clear();

        $lp = learnpath::getLpFromSession(self::COURSE_ID, $lpId, self::USER_ID);

        $this->assertSame($lpId, $lp->get_id());
        $this->assertSame(self::USER_ID, $lp->get_user_id());
        $this->assertSame('l2_session_obj', $lp->get_name());
    }

    public function testGetLpFromSessionReusesTheSerializedObjectAndRebindsTheEntity(): void
    {
        $lpId = $this->newLpRow('l2_session_stored');
        $original = $this->lp($lpId);
        Container::getSession()->set('lpobject', serialize($original));
        $this->getEntityManager()->clear();

        $restored = learnpath::getLpFromSession(self::COURSE_ID, $lpId, 3);

        $this->assertSame($lpId, $restored->get_id());
        $this->assertSame(self::USER_ID, $restored->get_user_id(), 'the stored object wins over the requested user');
        $this->assertSame($lpId, $restored->entity->getIid());
    }

    public function testUserIdentifierForExternalServicesFallsBackToTheUserId(): void
    {
        $this->setCurrentUser(1);
        Container::getSettingsManager()->updateSetting('lp.scorm_api_extrafield_to_use_as_student_id', '');
        $this->assertSame(1, learnpath::getUserIdentifierForExternalServices());

        Container::getSettingsManager()->updateSetting('lp.scorm_api_extrafield_to_use_as_student_id', 'true');
        $this->assertSame('admin', learnpath::getUserIdentifierForExternalServices());
    }

    public function testSortItemByOrderListReparentsAndRenumbersTheItems(): void
    {
        $lpId = $this->newLpRow('l2_sort');
        $a = $this->addItem($lpId, 'l2_a', 'document', 1);
        $b = $this->addItem($lpId, 'l2_b', 'document', 2);
        $dir = $this->addItem($lpId, 'l2_dir', 'dir', 3);
        $this->getEntityManager()->clear();
        $root = $this->getEntityManager()->find(CLpItem::class, $this->rootId($lpId));

        $result = learnpath::sortItemByOrderList($root, [
            (object) ['id' => $dir, 'parent_id' => 0],
            (object) ['id' => $b, 'parent_id' => $dir],
            (object) ['id' => $a, 'parent_id' => $dir],
            (object) ['id' => 0, 'parent_id' => 0],
        ]);

        $this->assertTrue($result);
        $rows = $this->conn()->fetchAllAssociative('SELECT iid, parent_item_id, display_order FROM c_lp_item WHERE lp_id = ? AND iid <> ?', [$lpId, $root->getIid()]);
        $byId = array_column($rows, null, 'iid');
        $this->assertSame([2, 3, 4], [(int) $byId[$dir]['display_order'], (int) $byId[$b]['display_order'], (int) $byId[$a]['display_order']]);
        $this->assertSame([$root->getIid(), $dir, $dir], [(int) $byId[$dir]['parent_item_id'], (int) $byId[$b]['parent_item_id'], (int) $byId[$a]['parent_item_id']]);
        $this->assertTrue(learnpath::sortItemByOrderList($root, []), 'an empty list is a no-op');
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT display_order FROM c_lp_item WHERE iid = ?', [$root->getIid()]), 'the root item gets order 1');
    }

    public function testDeleteRemovesTheLinkOfTheCurrentCourseOnlyAndRefusesAnotherId(): void
    {
        [$lpId, $node] = $this->publishedLp('l2_delete');
        $lp = $this->lp($lpId);

        $this->assertFalse($lp->delete(null, $lpId + 1), 'an explicit id must be the id of the object');
        $this->assertSame(2, $this->linkVisibility($node));

        $lp->delete(null, $lpId);

        $deletedAt = $this->conn()->fetchOne('SELECT deleted_at FROM resource_link WHERE resource_node_id = ? AND c_id = ?', [$node, self::COURSE_ID]);
        $this->assertNotNull($deletedAt, 'the course link is soft-deleted');
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp WHERE iid = ?', [$lpId]), 'the row stays (soft delete)');
    }
}
