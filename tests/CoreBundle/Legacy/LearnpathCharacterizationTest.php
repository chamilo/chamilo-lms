<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use learnpath;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the raw-SQL methods of learnpath (public/main/lp/learnpath.class.php).
 *
 * They pin what the legacy queries do TODAY (rows, ordering, NULL handling, DB side effects) so a later rewrite
 * to Doctrine/DQL that changes any of it fails here. Current behaviour that looks like a bug is pinned as-is and
 * flagged with a "PINNED QUIRK" comment: do not "fix" the expectation without deciding the change is intended.
 *
 * Fixtures: learning paths are inserted with a prepared statement (resource_node_id stays NULL, so no resource_node
 * row is written) in fixture course 1, with the fixture users admin (1), anon (2) and fallback_user (3).
 * Every title starts with "lp_" (sibling test files run against the same database).
 */
final class LearnpathCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;
    private const USER_ID = 1;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Legacy code reads its services, session and request through this static bridge.
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
        // Legacy "current course" context.
        $session->set('_real_cid', self::COURSE_ID);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    /**
     * Inserts a bare c_lp row plus its nested-tree root item (what CLpRepository::createLp does, minus the
     * resource node). Returns the lp id.
     */
    private function newLpRow(string $title, int $type = 1): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (?, ?, '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$type, $title]
        );
        $lpId = (int) $this->conn()->lastInsertId();

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

    /**
     * Legacy code runs with an empty identity map and raw SQL is invisible to entities already loaded, so
     * every step goes through a cleared entity manager.
     */
    private function lp(int $lpId, int $userId = self::USER_ID): learnpath
    {
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(CLp::class, $lpId);

        return new learnpath($entity, api_get_course_info_by_id(self::COURSE_ID), $userId);
    }

    private function newSessionRow(string $title): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            [$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function rootId(int $lpId): int
    {
        return (int) $this->conn()->fetchOne("SELECT iid FROM c_lp_item WHERE lp_id = ? AND path = 'root'", [$lpId]);
    }

    /**
     * Adds an item through learnpath::add_item() under the given parent (default: root) and then forces its
     * display_order (add_item() leaves it at 0 for every item).
     */
    private function addItem(int $lpId, string $title, string $type = 'document', int $order = 1, ?int $parentIid = null, int $path = 0): int
    {
        $lp = $this->lp($lpId);
        $parent = $this->getEntityManager()->find(CLpItem::class, $parentIid ?? $this->rootId($lpId));
        $id = (int) $lp->add_item($parent, 0, $type, $path, $title);
        $this->conn()->executeStatement('UPDATE c_lp_item SET display_order = ? WHERE iid = ?', [$order, $id]);
        $this->getEntityManager()->clear();

        return $id;
    }

    /**
     * @return array<string, mixed>|false
     */
    private function view(int $lpId, int $userId = self::USER_ID): array|false
    {
        return $this->conn()->fetchAssociative(
            'SELECT * FROM c_lp_view WHERE lp_id = ? AND user_id = ? ORDER BY view_count DESC',
            [$lpId, $userId]
        );
    }

    private function setItemStatus(int $lpId, int $itemId, string $status, int $viewCount = 1): void
    {
        $viewId = (int) $this->conn()->fetchOne('SELECT iid FROM c_lp_view WHERE lp_id = ? AND user_id = ? ORDER BY view_count DESC', [$lpId, self::USER_ID]);
        $this->conn()->executeStatement(
            'UPDATE c_lp_item_view SET status = ? WHERE lp_view_id = ? AND lp_item_id = ? AND view_count = ?',
            [$status, $viewId, $itemId, $viewCount]
        );
    }

    private function lpCol(int $lpId, string $col): mixed
    {
        return $this->conn()->fetchOne("SELECT $col FROM c_lp WHERE iid = ?", [$lpId]);
    }

    // ---------------------------------------------------------------- constructor / views

    public function testConstructorCreatesViewAndNotAttemptedItemViews(): void
    {
        $lpId = $this->newLpRow('lp_ctor');
        $dir = $this->addItem($lpId, 'lp_dir', 'dir', 1);
        $doc = $this->addItem($lpId, 'lp_doc', 'document', 2, $dir);

        // (addItem() has already built a learnpath for the user, which created the view.)
        $lp = $this->lp($lpId);

        $view = $this->view($lpId);
        $this->assertSame(self::COURSE_ID, (int) $view['c_id']);
        $this->assertSame(1, (int) $view['view_count']);
        $this->assertSame(0, (int) $view['last_item']);
        $this->assertNull($view['session_id'], 'no session id is stored when the context has no session');
        $this->assertNull($view['progress']);
        $this->assertSame((int) $view['iid'], $lp->get_view_id());
        $this->assertSame(1, $lp->attempt);

        $rows = $this->conn()->fetchAllAssociative(
            'SELECT lp_item_id, status, view_count, score, total_time FROM c_lp_item_view WHERE lp_view_id = ? ORDER BY lp_item_id',
            [$view['iid']]
        );
        $this->assertSame([$dir, $doc], array_map(static fn ($r) => (int) $r['lp_item_id'], $rows), 'the root is not given an item view');
        foreach ($rows as $row) {
            $this->assertSame('not attempted', $row['status']);
            $this->assertSame(1, (int) $row['view_count']);
            $this->assertEquals(0, $row['score']);
            $this->assertSame(0, (int) $row['total_time']);
        }

        // A second construction reuses the view and does not add item views again.
        $this->lp($lpId);
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_view WHERE lp_id = ?', [$lpId]));
        $this->assertSame(2, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item_view WHERE lp_view_id = ?', [$view['iid']]));
    }

    public function testConstructorPicksHighestViewCountOfTheUserAndIgnoresOtherUsersAndSessions(): void
    {
        $lpId = $this->newLpRow('lp_views');
        $doc = $this->addItem($lpId, 'lp_doc');
        $ins = fn (int $user, int $count, ?int $session, int $progress, int $last) => (int) $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, session_id, last_item, progress) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [self::COURSE_ID, $lpId, $user, $count, $session, $last, $progress]
        );
        $ins(self::USER_ID, 2, null, 30, 0);
        $ins(self::USER_ID, 5, null, 80, $doc);
        $sid = $this->newSessionRow('lp_session_a');
        $ins(self::USER_ID, 9, $sid, 99, 0); // belongs to a session: excluded from a no-session context
        $ins(3, 11, null, 11, 0); // another user

        $lp = $this->lp($lpId);

        $this->assertSame(5, (int) $lp->attempt);
        $this->assertSame(80, (int) $lp->progress_db);
        $this->assertSame($doc, (int) $lp->last_item_seen);
        $this->assertSame(
            (int) $this->conn()->fetchOne('SELECT iid FROM c_lp_view WHERE lp_id = ? AND view_count = 5', [$lpId]),
            $lp->get_view_id()
        );
        $this->assertSame($doc, $lp->get_current_item_id(), 'the last seen item is reopened');
    }

    public function testConstructorStatusComesFromTheHighestViewCountOfTheItemView(): void
    {
        $lpId = $this->newLpRow('lp_status');
        $a = $this->addItem($lpId, 'lp_a', 'document', 2);
        $b = $this->addItem($lpId, 'lp_b', 'document', 1);
        $this->lp($lpId);
        $viewId = (int) $this->view($lpId)['iid'];
        $this->setItemStatus($lpId, $a, 'completed');
        $this->conn()->executeStatement(
            "INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, status) VALUES (?, ?, 2, 0, 0, 0, 'incomplete')",
            [$a, $viewId]
        );

        $lp = $this->lp($lpId);

        // Ordered by display_order, not by id.
        $this->assertSame([[$b => 'not attempted'], [$a => 'incomplete']], $lp->get_items_status_list());
        // The status of the HIGHEST view_count wins ('incomplete' of view_count 2 over 'completed' of 1): the
        // constructor's own "ORDER BY view_count DESC" loop would keep the lowest one, but set_lp_view() on the
        // item re-reads the latest item view afterwards.
        $this->assertSame('incomplete', $lp->items[$a]->get_status());
    }

    public function testConstructorWithoutUserSetsAnErrorAndCreatesNoView(): void
    {
        $lpId = $this->newLpRow('lp_nouser');

        $lp = $this->lp($lpId, 0);

        $this->assertSame('User ID is empty', $lp->error);
        $this->assertFalse($this->conn()->fetchOne('SELECT iid FROM c_lp_view WHERE lp_id = ?', [$lpId]));
    }

    public function testGetViewCreatesOrReusesTheViewPerUser(): void
    {
        $lpId = $this->newLpRow('lp_getview');
        $lp = $this->lp($lpId);
        $first = $lp->get_view_id();
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, last_item) VALUES (?, ?, ?, 2, 0)',
            [self::COURSE_ID, $lpId, self::USER_ID]
        );

        $latest = $lp->get_view();
        $this->assertGreaterThan($first, $latest, 'without attempt number the highest view_count is returned');
        $this->assertSame($first, $lp->get_view(1), 'an attempt number restricts the lookup to that view_count');

        // Another user has no view yet: get_view() inserts one with view_count 1.
        $other = $lp->get_view(0, 3);
        $row = $this->conn()->fetchAssociative('SELECT user_id, view_count, last_item FROM c_lp_view WHERE iid = ?', [$other]);
        $this->assertSame(1, (int) $row['view_count']);
        $this->assertSame(0, (int) $row['last_item']);
        // PINNED QUIRK: the lookup is done for the $userId argument but the INSERT uses the learnpath's own user
        // (admin, 1), so no view is ever found for the other user and every call inserts yet another row.
        $this->assertSame(self::USER_ID, (int) $row['user_id']);
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_view WHERE lp_id = ? AND user_id = 3', [$lpId]));
        $this->assertNotSame($other, $lp->get_view(0, 3));
    }

    public function testRestartCannotInsertTheNewViewOnThisSchema(): void
    {
        $lpId = $this->newLpRow('lp_restart');
        $this->addItem($lpId, 'lp_doc');
        $lp = $this->lp($lpId);

        // PINNED QUIRK (source bug): restart() inserts into c_lp_view without last_item, a NOT NULL column with
        // no default, and also writes session_id 0 (a foreign key to session). On this schema the INSERT is
        // rejected, so a restart is impossible; Database::handleError() turns it into NotAllowedException.
        $this->expectException(NotAllowedException::class);
        try {
            $lp->restart();
        } finally {
            $this->assertSame(1, $lp->attempt, 'the attempt counter is not incremented when the insert fails');
            $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_view WHERE lp_id = ?', [$lpId]));
        }
    }

    // ---------------------------------------------------------------- items: add / list / delete

    public function testAddItemPersistsDefaultsUnderTheGivenParent(): void
    {
        $lpId = $this->newLpRow('lp_add');
        $lp = $this->lp($lpId);
        $root = $this->getEntityManager()->find(CLpItem::class, $this->rootId($lpId));

        $id = $lp->add_item($root, 0, '', 0, 'lp_untyped', 'desc');
        $secondId = $lp->add_item($root, $id, 'document', 77, 'lp_second', '', 0, 120);

        $row = $this->conn()->fetchAssociative('SELECT * FROM c_lp_item WHERE iid = ?', [$id]);
        $this->assertSame('dir', $row['item_type'], 'an empty type defaults to dir');
        $this->assertSame('lp_untyped', $row['title']);
        $this->assertSame('desc', $row['description']);
        $this->assertSame($this->rootId($lpId), (int) $row['parent_item_id']);
        $this->assertSame($this->rootId($lpId), (int) $row['item_root']);
        $this->assertSame(1, (int) $row['lvl']);
        $this->assertEquals(100, $row['max_score']);
        // PINNED QUIRK: $previousId is ignored, display_order is 0 for every added item, and the prerequisite
        // argument 0 is stored as the string '0'.
        $this->assertSame(0, (int) $row['display_order']);
        $this->assertSame('0', $row['prerequisite']);
        $second = $this->conn()->fetchAssociative('SELECT * FROM c_lp_item WHERE iid = ?', [$secondId]);
        $this->assertSame('77', $second['path']);
        $this->assertSame('120', (string) $second['max_time_allowed']);
        $this->assertSame(0, (int) $second['display_order']);
    }

    public function testGetItemsForFormOrdersByDisplayOrderAndExcludesRoot(): void
    {
        $lpId = $this->newLpRow('lp_form');
        $c = $this->addItem($lpId, 'lp_c', 'document', 3);
        $a = $this->addItem($lpId, 'lp_a', 'dir', 1);
        $b = $this->addItem($lpId, 'lp_b', 'document', 2, $a);
        $other = $this->newLpRow('lp_form_other');
        $this->addItem($other, 'lp_foreign', 'document', 1);

        $rows = $this->lp($lpId)->getItemsForForm();

        $this->assertSame([$a, $b, $c], array_column($rows, 'iid'), 'ordered by display_order across levels, root excluded');
        $this->assertSame(['dir', 'document', 'document'], array_column($rows, 'item_type'));
        $this->assertSame($a, (int) $rows[1]['parent_item_id']);
        $this->assertSame(
            ['iid', 'id', 'item_type', 'title', 'title_raw', 'path', 'description', 'parent_item_id', 'previous_item_id', 'next_item_id',
                'display_order', 'max_score', 'min_score', 'mastery_score', 'prerequisite', 'max_time_allowed', 'prerequisite_min_score',
                'prerequisite_max_score'],
            array_keys($rows[0])
        );
        $this->assertNull($rows[0]['mastery_score']);
        $this->assertSame('0', $rows[0]['prerequisite']);

        // PINNED QUIRK: with $addParentCondition the filter is "parent_item_id IS NULL", but every item hangs
        // from the root item (never NULL), so nothing is returned.
        $this->assertSame([], $this->lp($lpId)->getItemsForForm(true));
    }

    public function testGetFlatOrderedItemsListIsDepthFirstByDisplayOrder(): void
    {
        $lpId = $this->newLpRow('lp_flat');
        $z = $this->addItem($lpId, 'lp_z', 'document', 9);
        $dir = $this->addItem($lpId, 'lp_dir', 'dir', 2);
        $child2 = $this->addItem($lpId, 'lp_child2', 'document', 6, $dir);
        $child1 = $this->addItem($lpId, 'lp_child1', 'document', 5, $dir);
        $first = $this->addItem($lpId, 'lp_first', 'document', 1);

        $lp = $this->lp($lpId);

        $expected = [$first, $dir, $child1, $child2, $z];
        $this->assertSame($expected, $lp->ordered_items);
        $this->assertSame($expected, learnpath::get_flat_ordered_items_list($this->getEntityManager()->find(CLp::class, $lpId), null));
    }

    public function testDeleteItemClearsPrerequisitesPointingAtItAndRejectsForeignItems(): void
    {
        $lpId = $this->newLpRow('lp_del');
        $a = $this->addItem($lpId, 'lp_a', 'document', 1);
        $b = $this->addItem($lpId, 'lp_b', 'document', 2);
        $c = $this->addItem($lpId, 'lp_c', 'document', 3);
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite = ? WHERE iid = ?', [(string) $a, $b]);
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite = ? WHERE iid = ?', [(string) $b, $c]);
        $otherLp = $this->newLpRow('lp_del_other');
        $foreign = $this->addItem($otherLp, 'lp_foreign', 'document', 1);

        $lp = $this->lp($lpId);
        $this->assertFalse($lp->delete_item(0));
        $this->assertFalse($lp->delete_item($foreign), 'an item of another learning path is refused');
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item WHERE iid = ?', [$foreign]));

        $lp->delete_item($a);

        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item WHERE iid = ?', [$a]));
        $this->assertSame('', $this->conn()->fetchOne('SELECT prerequisite FROM c_lp_item WHERE iid = ?', [$b]), 'prerequisite = deleted id is blanked');
        $this->assertSame((string) $b, $this->conn()->fetchOne('SELECT prerequisite FROM c_lp_item WHERE iid = ?', [$c]), 'others stay');
    }

    public function testDeleteItemRepointsFinalItemAtTheRootItem(): void
    {
        $lpId = $this->newLpRow('lp_delfinal');
        $a = $this->addItem($lpId, 'lp_a', 'document', 1);
        $final = $this->addItem($lpId, 'lp_final', TOOL_LP_FINAL_ITEM, 2);

        $this->lp($lpId)->delete_item($a);

        // PINNED QUIRK: getLastInFirstLevel() looks for items WITHOUT parent, which is only the root item, so the
        // final item's previous_item_id (also the nested-tree "left" column) is overwritten with the root's id.
        $this->assertSame($this->rootId($lpId), (int) $this->conn()->fetchOne('SELECT previous_item_id FROM c_lp_item WHERE iid = ?', [$final]));
    }

    public function testGetLastInFirstLevelIsTheRootItem(): void
    {
        $lpId = $this->newLpRow('lp_lastfirst');
        $this->addItem($lpId, 'lp_a', 'document', 1);

        // PINNED QUIRK: see testDeleteItemRepointsFinalItemAtTheRootItem.
        $this->assertSame($this->rootId($lpId), (int) $this->lp($lpId)->getLastInFirstLevel());
    }

    public function testDeleteChildrenItemsDeletesRecursivelyAndCountsRows(): void
    {
        $lpId = $this->newLpRow('lp_children');
        $dir = $this->addItem($lpId, 'lp_dir', 'dir', 1);
        $sub = $this->addItem($lpId, 'lp_sub', 'dir', 1, $dir);
        $leaf = $this->addItem($lpId, 'lp_leaf', 'document', 1, $sub);
        $sibling = $this->addItem($lpId, 'lp_sibling', 'document', 2);

        $lp = $this->lp($lpId);
        $this->assertFalse($lp->delete_children_items(0));
        $this->assertSame(2, $lp->delete_children_items($dir), 'sub and leaf');

        $left = array_map('intval', $this->conn()->fetchFirstColumn('SELECT iid FROM c_lp_item WHERE lp_id = ? AND path <> \'root\' ORDER BY iid', [$lpId]));
        $this->assertSame([$dir, $sibling], $left, 'the dir itself and its sibling are kept');
        $this->assertSame(0, $this->lp($lpId)->delete_children_items($sibling));
    }

    // ---------------------------------------------------------------- counts / current item

    public function testItemCountsAndStatusCounters(): void
    {
        $lpId = $this->newLpRow('lp_counts');
        $dir = $this->addItem($lpId, 'lp_dir', 'dir', 1);
        $a = $this->addItem($lpId, 'lp_a', 'document', 2, $dir);
        $b = $this->addItem($lpId, 'lp_b', 'document', 3, $dir);
        $c = $this->addItem($lpId, 'lp_c', 'document', 4);
        $this->lp($lpId);
        $this->setItemStatus($lpId, $a, 'completed');
        $this->setItemStatus($lpId, $b, 'failed');
        $this->setItemStatus($lpId, $dir, 'completed');

        $lp = $this->lp($lpId);

        $this->assertSame(4, $lp->get_total_items_count(), 'dir included, root excluded');
        $this->assertSame(3, $lp->getTotalItemsCountWithoutDirs());
        $this->assertSame(2, $lp->get_complete_items_count(), 'failed counts as progressed by default, a completed dir never counts');
        $this->assertSame(1, $lp->get_complete_items_count(true));
        $this->assertEquals([67, '%'], array_map(static fn ($v) => is_numeric($v) ? (int) $v : $v, $lp->get_progress_bar_text('%')));
        $this->assertEquals([2, '/3'], $lp->get_progress_bar_text('abs'));
        $this->assertSame($dir, $lp->get_first_item_id());
        $this->assertSame($c, $lp->get_current_item_id(), 'first non-dir, not-done item becomes current');
    }

    // ---------------------------------------------------------------- save_last / progress

    public function testSaveLastStoresLastItemAndProgressOnTheView(): void
    {
        $lpId = $this->newLpRow('lp_save');
        $a = $this->addItem($lpId, 'lp_a', 'document', 1);
        $b = $this->addItem($lpId, 'lp_b', 'document', 2);
        $this->lp($lpId);
        $this->setItemStatus($lpId, $a, 'completed');
        $lp = $this->lp($lpId);
        $other = $this->lp($lpId, 3);
        $this->assertSame($b, $lp->get_current_item_id());

        $lp->save_last();

        $row = $this->view($lpId);
        $this->assertSame($b, (int) $row['last_item']);
        $this->assertSame(50, (int) $row['progress']);
        $this->assertSame(50, $lp->progress_db);
        $this->assertSame(0, (int) $this->view($lpId, 3)['last_item'], 'only the current user view is updated');
        $this->assertNull($this->view($lpId, 3)['progress']);
        $this->assertSame(50, learnpath::getProgress($lpId, self::USER_ID, self::COURSE_ID));
        unset($other);
    }

    public function testSaveLastOnlyTouchesViewsOfTheCurrentSessionContext(): void
    {
        $lpId = $this->newLpRow('lp_savesess');
        $a = $this->addItem($lpId, 'lp_a', 'document', 1);
        $lp = $this->lp($lpId);
        $this->setItemStatus($lpId, $a, 'completed');
        $lp = $this->lp($lpId);
        // A second view of the same user belonging to a session.
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, session_id, last_item, progress) VALUES (?, ?, ?, 4, ?, 0, 5)',
            [self::COURSE_ID, $lpId, self::USER_ID, $this->newSessionRow('lp_session_b')]
        );

        $lp->save_last();

        $this->assertSame(100, (int) $this->conn()->fetchOne('SELECT progress FROM c_lp_view WHERE lp_id = ? AND view_count = 1', [$lpId]));
        $this->assertSame(5, (int) $this->conn()->fetchOne('SELECT progress FROM c_lp_view WHERE lp_id = ? AND view_count = 4', [$lpId]), 'session views are left alone');
    }

    public function testGetProgressIsPerUserCourseAndSessionContext(): void
    {
        $lpId = $this->newLpRow('lp_progress');
        $ins = fn (int $user, ?int $session, ?int $progress, int $count = 1) => $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, session_id, last_item, progress) VALUES (?, ?, ?, ?, ?, 0, ?)',
            [self::COURSE_ID, $lpId, $user, $count, $session, $progress]
        );
        $sid = $this->newSessionRow('lp_session_c');
        $ins(1, null, 40);
        $ins(2, $sid, 60);
        $ins(3, null, null);

        $this->assertSame(40, learnpath::getProgress($lpId, 1, self::COURSE_ID), 'NULL session matches the no-session context');
        $this->assertSame(0, learnpath::getProgress($lpId, 1, self::COURSE_ID, $sid), 'a view of no session is not a view of the session');
        $this->assertSame(60, learnpath::getProgress($lpId, 2, self::COURSE_ID, $sid));
        $this->assertSame(0, learnpath::getProgress($lpId, 2, self::COURSE_ID), 'session view invisible without session');
        $this->assertSame(0, learnpath::getProgress($lpId, 3, self::COURSE_ID), 'NULL progress is cast to 0');
        $this->assertSame(0, learnpath::getProgress($lpId, 1, 2), 'other course');
        $this->assertSame(0, learnpath::getProgress($lpId + 100000, 1, self::COURSE_ID), 'unknown lp');
    }

    public function testGetProgressFromLpListKeysByLpIdAndSkipsLpsWithoutView(): void
    {
        $lp1 = $this->newLpRow('lp_plist1');
        $lp2 = $this->newLpRow('lp_plist2');
        $lp3 = $this->newLpRow('lp_plist3');
        foreach ([[$lp1, 25], [$lp2, 100]] as [$id, $progress]) {
            $this->conn()->executeStatement(
                'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, last_item, progress) VALUES (?, ?, 1, 1, 0, ?)',
                [self::COURSE_ID, $id, $progress]
            );
        }

        $list = learnpath::getProgressFromLpList([$lp1, $lp2, $lp3], 1, self::COURSE_ID);

        ksort($list);
        $this->assertSame([$lp1 => 25, $lp2 => 100], array_map('intval', $list));
        $this->assertSame([], learnpath::getProgressFromLpList([], 1, self::COURSE_ID));
        $this->assertSame([], learnpath::getProgressFromLpList([$lp3], 1, self::COURSE_ID));
        $this->assertSame([], learnpath::getProgressFromLpList([$lp1], 2, self::COURSE_ID), 'other user');
    }

    public function testIsBlockedByPrerequisiteFollowsTheProgressOfThePrerequisite(): void
    {
        $prereq = $this->newLpRow('lp_prereq');
        $course = $this->getCourse(self::COURSE_ID);
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, last_item, progress) VALUES (?, ?, 3, 1, 0, 99)',
            [self::COURSE_ID, $prereq]
        );

        $this->assertFalse(learnpath::isBlockedByPrerequisite(3, 0, $course, 0), 'no prerequisite');
        $this->assertTrue(learnpath::isBlockedByPrerequisite(3, $prereq, $course, 0), '99 < 100');
        $this->assertTrue(learnpath::isBlockedByPrerequisite(2, $prereq, $course, 0), 'no view at all counts as 0');

        $this->conn()->executeStatement('UPDATE c_lp_view SET progress = 100 WHERE lp_id = ? AND user_id = 3', [$prereq]);
        $this->assertFalse(learnpath::isBlockedByPrerequisite(3, $prereq, $course, 0));
    }

    // ---------------------------------------------------------------- lp row toggles

    public function testGetTypeStaticReadsLpTypeAndIsNullForUnknown(): void
    {
        $scorm = $this->newLpRow('lp_type_scorm', 2);
        $chamilo = $this->newLpRow('lp_type_chamilo', 1);

        $this->assertSame(2, (int) learnpath::get_type_static($scorm));
        $this->assertSame(1, (int) learnpath::get_type_static($chamilo));
        $this->assertNull(learnpath::get_type_static($chamilo + 100000));
        $this->assertSame(2, (int) $this->lp($scorm)->get_type());
    }

    public function testUpdateDefaultViewModeCyclesThroughTheFourModes(): void
    {
        $lpId = $this->newLpRow('lp_viewmode');
        $lp = $this->lp($lpId);
        $this->assertSame('embedded', $this->lpCol($lpId, 'default_view_mod'));

        $seen = [];
        for ($i = 0; $i < 5; $i++) {
            $seen[] = $lp->update_default_view_mode();
            $this->assertSame($seen[$i], $this->lpCol($lpId, 'default_view_mod'));
        }

        $this->assertSame(['embedframe', 'impress', 'fullscreen', 'embedded', 'embedframe'], $seen);
        $this->assertSame('embedframe', $lp->mode);
    }

    public function testUpdateDefaultViewModeKeepsAnUnknownModeAndIsMinusOneForUnknownLp(): void
    {
        $lpId = $this->newLpRow('lp_viewmode_odd');
        $lp = $this->lp($lpId);
        $this->conn()->executeStatement("UPDATE c_lp SET default_view_mod = 'weird' WHERE iid = ?", [$lpId]);
        $this->assertSame('weird', $lp->update_default_view_mode());

        $this->conn()->executeStatement('DELETE FROM c_lp WHERE iid = ?', [$lpId]);
        $this->assertSame(-1, $lp->update_default_view_mode());
    }

    public function testUpdateDefaultScormCommitToggles(): void
    {
        $lpId = $this->newLpRow('lp_commit', 2);
        $lp = $this->lp($lpId);

        $this->assertTrue($lp->update_default_scorm_commit());
        $this->assertSame(1, (int) $this->lpCol($lpId, 'force_commit'));
        $this->assertFalse($lp->update_default_scorm_commit());
        $this->assertSame(0, (int) $this->lpCol($lpId, 'force_commit'));
    }

    public function testReinitSeriousGameAndDebugTogglesPersist(): void
    {
        $lpId = $this->newLpRow('lp_toggles');
        $lp = $this->lp($lpId);
        $this->assertSame(1, (int) $this->lpCol($lpId, 'prevent_reinit'), 'column default');

        $this->assertSame(0, $lp->update_reinit());
        $this->assertSame(0, (int) $this->lpCol($lpId, 'prevent_reinit'));
        $this->assertSame(1, $lp->update_reinit());
        $this->assertSame(1, (int) $this->lpCol($lpId, 'prevent_reinit'));

        $this->assertSame(1, $lp->set_seriousgame_mode());
        $this->assertSame(1, (int) $this->lpCol($lpId, 'seriousgame_mode'));
        $this->assertSame(0, $lp->set_seriousgame_mode());
        $this->assertSame(0, (int) $this->lpCol($lpId, 'seriousgame_mode'));

        $this->assertSame(1, $lp->update_scorm_debug());
        $this->assertSame(1, (int) $this->lpCol($lpId, 'debug'));
        $this->assertSame(0, $lp->update_scorm_debug());
        $this->assertSame(0, (int) $this->lpCol($lpId, 'debug'));
    }

    public function testAttemptModeIsStoredAsPreventReinitAndSeriousGameFlags(): void
    {
        $lpId = $this->newLpRow('lp_attempt', 2);
        $lp = $this->lp($lpId);
        $expect = [
            'seriousgame' => [1, 1],
            'single' => [1, 0],
            'multiple' => [0, 0],
            'whatever' => [0, 0],
        ];
        foreach ($expect as $mode => [$reinit, $serious]) {
            $this->assertTrue($lp->set_attempt_mode($mode));
            $this->assertSame($reinit, (int) $this->lpCol($lpId, 'prevent_reinit'), $mode);
            $this->assertSame($serious, (int) $this->lpCol($lpId, 'seriousgame_mode'), $mode);
        }

        $lp->set_attempt_mode('single');
        $this->assertSame('single', $lp->get_attempt_mode());
        $lp->switch_attempt_mode();
        $this->assertSame('multiple', $lp->get_attempt_mode());
        $this->assertSame([0, 0], [(int) $this->lpCol($lpId, 'prevent_reinit'), (int) $this->lpCol($lpId, 'seriousgame_mode')]);
        $lp->switch_attempt_mode();
        $this->assertSame('seriousgame', $lp->get_attempt_mode());
        $lp->switch_attempt_mode();
        $this->assertSame('single', $lp->get_attempt_mode());
    }

    public function testSetModifiedOnWritesCurrentUtcTime(): void
    {
        $lpId = $this->newLpRow('lp_modified');
        $lp = $this->lp($lpId);

        $this->assertTrue($lp->set_modified_on());

        $stored = strtotime((string) $this->lpCol($lpId, 'modified_on').' UTC');
        $this->assertEqualsWithDelta(time(), $stored, 5);
        $this->assertSame((string) $this->lpCol($lpId, 'modified_on'), $lp->modified_on);
        $this->assertSame('2030-01-01 00:00:00', (string) $this->lpCol($lpId, 'created_on'));
    }

    public function testAccumulateWorkTimeAggregates(): void
    {
        $lpId = $this->newLpRow('lp_work');
        $this->conn()->executeStatement('UPDATE c_lp SET accumulate_work_time = 30 WHERE iid = ?', [$lpId]);

        $this->assertSame(30, (int) learnpath::getAccumulateWorkTimePrerequisite($lpId, self::COURSE_ID));
    }

    // ---------------------------------------------------------------- prerequisites

    public function testClearPrerequisitesBlanksPrerequisitesAndQuizMasteryScoresOfThisLpOnly(): void
    {
        $lpId = $this->newLpRow('lp_clear');
        $a = $this->addItem($lpId, 'lp_a', 'document', 1);
        $q = $this->addItem($lpId, 'lp_q', 'quiz', 2);
        $other = $this->newLpRow('lp_clear_other');
        $o = $this->addItem($other, 'lp_o', 'document', 1);
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite = ?, mastery_score = 80 WHERE iid IN (?, ?, ?)', ['12', $a, $q, $o]);

        // PINNED QUIRK (source bug): the second UPDATE sets mastery_score = '' on a DOUBLE column, which strict
        // SQL mode rejects, so clearPrerequisites() throws after having cleared the prerequisites (first UPDATE).
        try {
            $this->lp($lpId)->clearPrerequisites();
            $this->fail('NotAllowedException expected');
        } catch (NotAllowedException) {
        }

        $get = fn (int $id) => $this->conn()->fetchAssociative('SELECT prerequisite, mastery_score FROM c_lp_item WHERE iid = ?', [$id]);
        $this->assertSame('', $get($a)['prerequisite']);
        $this->assertSame('', $get($q)['prerequisite']);
        $this->assertEquals(80, $get($a)['mastery_score']);
        $this->assertEquals(80, $get($q)['mastery_score'], 'the quiz mastery score reset never happens (see above)');
        $this->assertSame('12', $get($o)['prerequisite'], 'other learning paths are untouched');
        $this->assertEquals(80, $get($o)['mastery_score']);
    }

    public function testSetPreviousStepAsPrerequisiteChainsNonDirItems(): void
    {
        $lpId = $this->newLpRow('lp_chain');
        $a = $this->addItem($lpId, 'lp_a', 'document', 1);
        $dir = $this->addItem($lpId, 'lp_dir', 'dir', 2);
        $b = $this->addItem($lpId, 'lp_b', 'document', 3, $dir);
        $c = $this->addItem($lpId, 'lp_c', 'document', 4, $dir);

        $this->lp($lpId)->set_previous_step_as_prerequisite_for_all_items();

        $pre = fn (int $id) => $this->conn()->fetchOne('SELECT prerequisite FROM c_lp_item WHERE iid = ?', [$id]);
        $this->assertSame('0', $pre($a), 'first item keeps what add_item stored');
        $this->assertSame((string) $a, $pre($b), 'a folder is skipped: b requires a');
        $this->assertSame((string) $b, $pre($c));
        $this->assertSame('0', $pre($dir), 'folders get no prerequisite');
    }

    // ---------------------------------------------------------------- interactions / objectives

    public function testInteractionsCountAndArrayAreOrderedByOrderId(): void
    {
        $lpId = $this->newLpRow('lp_inter');
        $doc = $this->addItem($lpId, 'lp_doc');
        $this->lp($lpId);
        $iv = (int) $this->conn()->fetchOne('SELECT iid FROM c_lp_item_view WHERE lp_item_id = ?', [$doc]);
        $ins = fn (int $order, string $id, string $resp) => $this->conn()->executeStatement(
            "INSERT INTO c_lp_iv_interaction (c_id, order_id, lp_iv_id, interaction_id, interaction_type, weighting, completion_time, correct_responses, student_response, result, latency)
             VALUES (?, ?, ?, ?, 'choice', 1, '10:00:00', 'x', ?, 'correct', '0000:00:05')",
            [self::COURSE_ID, $order, $iv, $id, $resp]
        );
        $ins(2, 'q%2F2', 'b');
        $ins(0, 'q%2F0', 'a__|b__|c');

        $this->assertSame(2, (int) learnpath::get_interactions_count_from_db($iv, self::COURSE_ID));
        $this->assertSame(0, (int) learnpath::get_interactions_count_from_db($iv, 2), 'other course');
        $this->assertSame(0, (int) learnpath::get_interactions_count_from_db($iv + 100000, self::COURSE_ID));

        $list = learnpath::get_iv_interactions_array($iv, self::COURSE_ID);

        $this->assertCount(3, $list, 'header row + 2 interactions');
        $this->assertSame(1, $list[1]['order_id'], 'order_id is shown +1');
        $this->assertSame('q/0', $list[1]['id'], 'interaction_id is urldecoded');
        $this->assertSame('a__|b__|c', $list[1]['student_response']);
        // 3 or more parts: the last part is dropped from the formatted response, then joined by commas.
        $this->assertSame('a,b', $list[1]['student_response_formatted']);
        $this->assertSame('', $list[1]['correct_responses'], 'correct answers are hidden');
        $this->assertSame('choice', $list[1]['type']);
        $this->assertSame('10:00:00', $list[1]['time']);
        $this->assertSame('correct', $list[1]['result']);
        $this->assertSame('0000:00:05', $list[1]['latency']);
        $this->assertSame(3, $list[2]['order_id']);
        $this->assertSame('b', $list[2]['student_response_formatted']);
        $this->assertSame([], learnpath::get_iv_interactions_array(0, self::COURSE_ID));
        $this->assertSame([], learnpath::get_iv_interactions_array($iv + 100000, self::COURSE_ID), 'no row: no header either');
    }

    public function testObjectivesCountAndArrayAreOrderedByOrderId(): void
    {
        $lpId = $this->newLpRow('lp_obj');
        $doc = $this->addItem($lpId, 'lp_doc');
        $this->lp($lpId);
        $iv = (int) $this->conn()->fetchOne('SELECT iid FROM c_lp_item_view WHERE lp_item_id = ?', [$doc]);
        $ins = fn (int $order, string $id, float $raw) => $this->conn()->executeStatement(
            "INSERT INTO c_lp_iv_objective (c_id, lp_iv_id, order_id, objective_id, score_raw, score_max, score_min, status) VALUES (?, ?, ?, ?, ?, 10, 1, 'passed')",
            [self::COURSE_ID, $iv, $order, $id, $raw]
        );
        $ins(1, 'o%2F1', 7.5);
        $ins(0, 'o%2F0', 2);

        $this->assertSame(2, (int) learnpath::get_objectives_count_from_db($iv, self::COURSE_ID));
        $this->assertSame(0, (int) learnpath::get_objectives_count_from_db($iv, 2));

        $list = learnpath::get_iv_objectives_array($iv, self::COURSE_ID);

        $this->assertCount(3, $list);
        $this->assertSame(1, $list[1]['order_id']);
        $this->assertSame('o/0', $list[1]['objective_id']);
        $this->assertEquals(2, $list[1]['score_raw']);
        $this->assertEquals(10, $list[1]['score_max']);
        $this->assertEquals(1, $list[1]['score_min']);
        $this->assertSame('passed', $list[1]['status']);
        $this->assertEquals(7.5, $list[2]['score_raw']);
        $this->assertSame([], learnpath::get_iv_objectives_array(0, self::COURSE_ID));
    }

    // ---------------------------------------------------------------- "continue with" learning path (resource_node join)

    private function newNode(string $title, ?int $parent): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_node (resource_type_id, parent_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (32, ?, ?, ?, NOW(), NOW(), 0, UNHEX(REPLACE(UUID(), \'-\', \'\')))',
            [$parent, $title, $title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testFlowNextLpOptionsListsSiblingLpsOfTheSameParentNodeByTitle(): void
    {
        $folder = $this->newNode('lp_folder_a', null);
        $otherFolder = $this->newNode('lp_folder_b', null);
        $current = $this->newLpRow('lp_flow_current');
        $zeta = $this->newLpRow('lp_flow_zeta');
        $alpha = $this->newLpRow('lp_flow_alpha');
        $stranger = $this->newLpRow('lp_flow_stranger');
        $orphan = $this->newLpRow('lp_flow_orphan');
        foreach ([[$current, $folder], [$zeta, $folder], [$alpha, $folder], [$stranger, $otherFolder]] as [$id, $parent]) {
            $this->conn()->executeStatement('UPDATE c_lp SET resource_node_id = ? WHERE iid = ?', [$this->newNode('lp_node_'.$id, $parent), $id]);
        }

        $options = learnpath::getFlowNextLpOptions($current);

        $this->assertSame([$alpha => 'lp_flow_alpha', $zeta => 'lp_flow_zeta'], $options, 'the current lp and other folders are excluded');
        $this->assertSame([$alpha, $zeta], array_keys($options), 'ordered by title ASC');
        $this->assertSame([], learnpath::getFlowNextLpOptions(0));
        $this->assertSame([], learnpath::getFlowNextLpOptions($orphan), 'an lp without resource node has no siblings');
        $this->assertSame([], learnpath::getFlowNextLpOptions($stranger));

        $this->assertTrue(learnpath::isValidFlowNextLp($current, $zeta));
        $this->assertFalse(learnpath::isValidFlowNextLp($current, $stranger));
        $this->assertFalse(learnpath::isValidFlowNextLp($current, $current));
        $this->assertFalse(learnpath::isValidFlowNextLp($current, 0));
        $this->assertSame(['iid' => $alpha, 'title' => 'lp_flow_alpha'], learnpath::getFlowNextLpInfo($current, $alpha));
        $this->assertSame([], learnpath::getFlowNextLpInfo($current, $stranger));
    }

    // ---------------------------------------------------------------- generated JS

    public function testGetJsDropdownArrayListsChildrenOfEveryItemInDisplayOrder(): void
    {
        $lpId = $this->newLpRow('lp_js');
        $dir = $this->addItem($lpId, 'lp_dir', 'dir', 1);
        $second = $this->addItem($lpId, 'lp_second', 'document', 6, $dir);
        $first = $this->addItem($lpId, 'lp_first', 'document', 5, $dir);

        $js = $this->lp($lpId)->get_js_dropdown_array();

        $this->assertStringContainsString('var child_name = new Array();', $js);
        $this->assertStringContainsString("child_name[$dir] = new Array();", $js);
        // PINNED QUIRK: the level-0 list (items without parent) is empty because getItemsForForm(true) never matches.
        $this->assertStringNotContainsString('child_value[0][0]', $js);
        $firstPos = strpos($js, "child_value[$dir][0] = \"$first\";");
        $secondPos = strpos($js, "child_value[$dir][1] = \"$second\";");
        $this->assertNotFalse($firstPos, 'display_order 5 comes first');
        $this->assertNotFalse($secondPos);
        $this->assertStringContainsString('lp_first', $js);
        // Every item (also leaves) gets an (empty) array.
        $this->assertStringContainsString("child_name[$first] = new Array();", $js);
    }

    // ---------------------------------------------------------------- SQL that cannot run on the current schema

    public function testQueriesReferencingMissingColumnsFailTodayExplicitly(): void
    {
        $lpId = $this->newLpRow('lp_badsql');
        $this->addItem($lpId, 'lp_a');
        $lp = $this->lp($lpId);

        // PINNED QUIRK (source bugs, not fixed here): c_lp has no c_id column and c_lp_item neither, so these
        // legacy queries are invalid SQL. Whatever Database::handleError() does with it is today's behaviour.
        foreach ([
            'select_previous_item_id' => static fn () => $lp->select_previous_item_id(),
            'getAccumulateWorkTimeTotal' => static fn () => learnpath::getAccumulateWorkTimeTotal(self::COURSE_ID),
            'getAccumulateWorkTimeTotalCourse' => static fn () => $lp->getAccumulateWorkTimeTotalCourse(),
        ] as $name => $call) {
            $failed = false;
            try {
                $call();
            } catch (NotAllowedException) {
                $failed = true;
            }
            $this->assertTrue($failed, $name.' is expected to fail on the missing c_id column');
        }
    }
}
