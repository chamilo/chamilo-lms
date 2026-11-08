<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use learnpath;
use learnpathItem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Throwable;

/**
 * Characterization tests for the raw-SQL methods of learnpathItem (public/main/lp/learnpathItem.class.php).
 *
 * They pin what the legacy queries do TODAY (rows written, values read, NULL handling, side effects) so a later
 * rewrite to Doctrine that changes any of it fails here. Behaviour that looks wrong is pinned as-is and flagged
 * with a "PINNED QUIRK" comment: do not change the expectation without deciding the change is intended.
 *
 * Fixtures: learning paths, views and item views are inserted with prepared statements in fixture course 1
 * (resource_node_id stays NULL, no resource_node row is written); every title starts with "li_". The learnpath
 * class builds the items, then learnpathItem objects are built straight from their rows and bound to an lp view
 * with set_lp_view(), exactly as learnpath::__construct() does. All times are fixed timestamps.
 */
final class LearnpathItemCharacterizationTest extends KernelTestCase
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
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

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

    private function rootId(int $lpId): int
    {
        return (int) $this->conn()->fetchOne("SELECT iid FROM c_lp_item WHERE lp_id = ? AND path = 'root'", [$lpId]);
    }

    private function addItem(int $lpId, string $title, string $type = 'document', int $order = 1, ?int $parentIid = null, int|string $path = 0): int
    {
        $this->getEntityManager()->clear();
        $em = $this->getEntityManager();
        $lp = new learnpath($em->find(CLp::class, $lpId), api_get_course_info_by_id(self::COURSE_ID), self::USER_ID);
        $parent = $em->find(CLpItem::class, $parentIid ?? $this->rootId($lpId));
        // add_item() for tool types (quiz, sco...) would create real resources: build a document row, retype it in SQL.
        $id = (int) $lp->add_item($parent, 0, \in_array($type, ['dir', 'document'], true) ? $type : 'document', 0, $title);
        $this->conn()->executeStatement(
            'UPDATE c_lp_item SET display_order = ?, item_type = ?, path = ? WHERE iid = ?',
            [$order, $type, (string) $path, $id]
        );
        $this->getEntityManager()->clear();

        return $id;
    }

    // ---------------------------------------------------------------- fixtures

    private function newView(int $lpId, int $userId = self::USER_ID, ?int $sessionId = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (c_id, lp_id, user_id, view_count, session_id, last_item, progress) VALUES (?, ?, ?, 1, ?, 0, 0)',
            [self::COURSE_ID, $lpId, $userId, $sessionId]
        );

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $o column overrides
     */
    private function newItemView(int $viewId, int $itemId, int $attempt = 1, array $o = []): int
    {
        $row = array_merge([
            'lp_item_id' => $itemId,
            'lp_view_id' => $viewId,
            'view_count' => $attempt,
            'start_time' => 0,
            'total_time' => 0,
            'score' => 0,
            'status' => 'not attempted',
            'suspend_data' => null,
            'lesson_location' => null,
            'core_exit' => 'none',
            'max_score' => null,
        ], $o);
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, status,
                suspend_data, lesson_location, core_exit, max_score)
             VALUES (:lp_item_id, :lp_view_id, :view_count, :start_time, :total_time, :score, :status,
                :suspend_data, :lesson_location, :core_exit, :max_score)',
            $row
        );

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * @return array<string, mixed>|false
     */
    private function ivRow(int $viewId, int $itemId, int $attempt = 1): array|false
    {
        return $this->conn()->fetchAssociative(
            'SELECT * FROM c_lp_item_view WHERE lp_view_id = ? AND lp_item_id = ? AND view_count = ?',
            [$viewId, $itemId, $attempt]
        );
    }

    private function item(int $itemId, ?int $viewId = null): learnpathItem
    {
        $this->getEntityManager()->clear();
        $item = new learnpathItem($itemId);
        if (null !== $viewId) {
            $item->set_lp_view($viewId);
        }

        return $item;
    }

    /**
     * Gives the item a fixed viewing window of $seconds, in the past, so get_total_time() is deterministic.
     */
    private function window(learnpathItem $item, int $seconds = 120): void
    {
        $item->current_start_time = 1700000000;
        $item->current_stop_time = 1700000000 + $seconds;
    }

    /**
     * One lp (type 1 = Chamilo, 2 = SCORM) with a single item of $type, a view and one item view (attempt 1).
     *
     * @return array{lp: int, item: int, view: int, iv: int}
     */
    private function scene(string $type = 'document', int $lpType = 1, array $iv = [], int|string $path = 0): array
    {
        $lp = $this->newLpRow('li_lp_'.$type.$lpType, $lpType);
        $item = $this->addItem($lp, 'li_item_'.$type, $type, 1, null, $path);
        $view = $this->newView($lp);
        $iv = $this->newItemView($view, $item, 1, $iv);

        return ['lp' => $lp, 'item' => $item, 'view' => $view, 'iv' => $iv];
    }

    private function newQuiz(int $maxAttempt = 0): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_quiz (title, type, random, random_answers, results_disabled, max_attempt, feedback_type, expired_time,
                propagate_neg, review_answers, random_by_category, display_category_name, hide_question_title, page_result_configuration)
             VALUES ('li_quiz', 1, 0, 0, 0, ?, 0, 0, 0, 0, 0, 0, 0, '[]')",
            [$maxAttempt]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function setPrerequisiteSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting('lp.'.$name, $value);
    }

    protected function tearDown(): void
    {
        foreach (['validate_lp_prerequisite_from_other_session', 'lp_prerequisite_use_last_attempt_only', 'lp_prerequisite_on_quiz_unblock_if_max_attempt_reached'] as $name) {
            try {
                $this->setPrerequisiteSetting($name, 'false');
            } catch (Throwable) {
            }
        }
        $_GET = [];
        unset($_REQUEST['exeId']);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- constructor

    public function testConstructorLoadsTheRowAndTheChildrenOfTheItem(): void
    {
        $lp = $this->newLpRow('li_ctor');
        $dir = $this->addItem($lp, 'li_dir', 'dir', 1);
        $a = $this->addItem($lp, 'li_a', 'document', 1, $dir, 11);
        $b = $this->addItem($lp, 'li_b', 'quiz', 2, $dir, 22);
        $this->conn()->executeStatement(
            "UPDATE c_lp_item SET min_score = 2, max_score = 50, mastery_score = 8, prerequisite = '99', description = 'li_desc',
                ref = 'li_ref', max_time_allowed = '00:10:00', launch_data = 'li_launch', audio = 'li_audio.mp3',
                prerequisite_min_score = 3, prerequisite_max_score = 9, display_order = 4 WHERE iid = ?",
            [$b]
        );

        $item = $this->item($b);
        $this->assertSame($b, (int) $item->get_id());
        $this->assertSame($lp, (int) $item->lp_id);
        $this->assertSame('li_b', $item->get_title());
        $this->assertSame('quiz', $item->get_type());
        $this->assertSame('22', $item->get_path());
        $this->assertSame('li_desc', $item->get_description());
        $this->assertSame('li_ref', $item->get_ref());
        $this->assertSame($dir, (int) $item->get_parent());
        $this->assertSame('99', $item->get_prereq_string());
        $this->assertEquals(2, $item->get_min());
        $this->assertEquals(50, $item->get_max());
        $this->assertEquals(8, $item->get_mastery_score());
        $this->assertSame('00:10:00', $item->get_max_time_allowed());
        $this->assertSame('li_launch', $item->get_launch_data());
        $this->assertSame('li_audio.mp3', $item->audio);
        $this->assertEquals(3, $item->getPrerequisiteMinScore());
        $this->assertEquals(9, $item->getPrerequisiteMaxScore());
        $this->assertSame(4, (int) $item->display_order);
        $this->assertSame([], $item->get_children(), 'a leaf has no children');

        // The parent lists the iids of the items whose parent_item_id points at it.
        $children = array_map('intval', $this->item($dir)->get_children());
        sort($children);
        $this->assertSame([$a, $b], $children);
        // The root is the parent of the first-level items.
        $this->assertSame([$dir], array_map('intval', $this->item($this->rootId($lp))->get_children()));
    }

    public function testConstructorFromAnEntityMapsTheSameFieldsAndLoadsNoChildren(): void
    {
        $lp = $this->newLpRow('li_ctor_entity');
        $dir = $this->addItem($lp, 'li_dir', 'dir', 1);
        $a = $this->addItem($lp, 'li_a', 'document', 1, $dir, 11);

        $this->getEntityManager()->clear();
        $dirEntity = $this->getEntityManager()->find(CLpItem::class, $dir);
        $viaRow = new learnpathItem($dir);
        $viaEntity = new learnpathItem(0, $dirEntity);

        $this->assertSame($dir, (int) $viaEntity->get_id());
        $this->assertSame($viaRow->get_title(), $viaEntity->get_title());
        $this->assertSame($viaRow->get_type(), $viaEntity->get_type());
        $this->assertSame((int) $viaRow->lp_id, (int) $viaEntity->lp_id);
        $this->assertSame([$a], array_map('intval', $viaRow->get_children()));
        // PINNED QUIRK: children are looked up with parent_item_id = $id, and $id is 0 when the item is passed as
        // an entity, so an item built that way never knows its children.
        $this->assertSame([], $viaEntity->get_children());
    }

    public function testGettersFallBackToDefaultsWhenTheColumnsAreEmpty(): void
    {
        $lp = $this->newLpRow('li_defaults');
        $a = $this->addItem($lp, 'li_a');
        $this->conn()->executeStatement(
            'UPDATE c_lp_item SET min_score = 0, max_score = NULL, mastery_score = NULL, description = NULL, parent_item_id = NULL, prerequisite = NULL, max_time_allowed = NULL WHERE iid = ?',
            [$a]
        );
        $item = $this->item($a);

        $this->assertSame(100, $item->get_max(), 'no max score means 100');
        $this->assertSame(0, $item->get_min());
        $this->assertSame(-1, $item->get_mastery_score(), 'a NULL mastery score reads as -1');
        $this->assertNull($item->get_parent());
        $this->assertSame('', $item->get_prereq_string());
        $this->assertSame('', $item->get_max_time_allowed());
        $this->assertSame(0, $item->get_score());
        $this->assertSame(0, $item->get_level());
        $this->assertSame(1, $item->get_attempt_id());
        $this->assertSame(0, $item->get_view_count(), 'view count is 0 while no attempt is loaded, attempt id is 1');
        $this->assertSame('', $item->get_suspend_data());
    }

    // ---------------------------------------------------------------- set_lp_view

    public function testSetLpViewLoadsTheHighestAttemptAndCountsInteractionsAndObjectives(): void
    {
        $s = $this->scene('document', 1, ['status' => 'completed', 'score' => 3, 'total_time' => 40, 'start_time' => 1000,
            'suspend_data' => 'li_old', 'lesson_location' => 'li_loc_old', 'max_score' => '10']);
        $iv2 = $this->newItemView($s['view'], $s['item'], 2, ['status' => 'incomplete', 'score' => 6.5, 'total_time' => 25,
            'start_time' => 2000, 'suspend_data' => 'li_new', 'lesson_location' => 'li_loc_new', 'core_exit' => 'suspend', 'max_score' => '20']);
        foreach ([0, 1] as $i) {
            $this->conn()->executeStatement(
                "INSERT INTO c_lp_iv_interaction (c_id, order_id, lp_iv_id, interaction_id, interaction_type, weighting, completion_time, correct_responses, student_response, result, latency)
                 VALUES (1, ?, ?, ?, 'choice', 1, '10:00:00', 'a', 'a', 'correct', '5')",
                [$i, $iv2, 'li_i'.$i]
            );
        }
        $this->conn()->executeStatement(
            "INSERT INTO c_lp_iv_objective (c_id, lp_iv_id, order_id, objective_id, score_raw, score_max, score_min, status)
             VALUES (1, ?, 0, 'li_o0', 1, 2, 0, 'completed')",
            [$iv2]
        );

        $item = $this->item($s['item']);
        $this->assertTrue($item->set_lp_view($s['view']));

        $this->assertSame($iv2, $item->db_item_view_id, 'the attempt with the highest view_count wins');
        $this->assertSame(2, $item->get_view_count());
        $this->assertSame('incomplete', $item->get_status(false));
        $this->assertEquals(6.5, $item->get_score());
        $this->assertSame('li_new', $item->current_data);
        $this->assertSame('li_loc_new', $item->lesson_location);
        $this->assertSame('suspend', $item->get_core_exit());
        $this->assertSame('20', (string) $item->view_max_score);
        $this->assertSame(2000, (int) $item->current_start_time);
        $this->assertSame(2025, (int) $item->current_stop_time, 'stop time is start time + stored total time');
        $this->assertSame(2, $item->interactions_count);
        $this->assertSame(1, $item->objectives_count);
    }

    public function testSetLpViewWithoutViewIdOrWithAnUnknownViewLeavesTheItemUnbound(): void
    {
        $s = $this->scene();
        $item = $this->item($s['item']);

        $this->assertFalse($item->set_lp_view(null));
        $this->assertSame(0, $item->db_item_view_id);
        $this->assertSame(0, $item->view_id);

        // An id with no item view row still counts as success but binds nothing.
        $this->assertTrue($item->set_lp_view(987654321));
        $this->assertSame(0, $item->db_item_view_id);
        $this->assertSame(987654321, $item->view_id);
        $this->assertSame('not attempted', $item->get_status(false));
    }

    // ---------------------------------------------------------------- status

    public function testSetStatusAcceptsKnownStatusesCaseInsensitivelyAndResetsOnUnknownOnes(): void
    {
        $s = $this->scene();
        $item = $this->item($s['item'], $s['view']);

        foreach (['not attempted', 'incomplete', 'completed', 'passed', 'failed', 'browsed'] as $status) {
            $this->assertTrue($item->set_status($status));
            $this->assertSame($status, $item->get_status(false));
        }
        $this->assertTrue($item->set_status('PASSED'));
        $this->assertSame('PASSED', $item->get_status(false), 'the value is stored as given, not normalised');

        // PINNED QUIRK: "succeeded" (listed by is_done()'s docblock) is not a known status: an unknown status does
        // not keep the previous one, it resets the item to "not attempted".
        $this->assertFalse($item->set_status('succeeded'));
        $this->assertSame('not attempted', $item->get_status(false));
    }

    public function testGetStatusReadsTheDatabaseRowOfTheCurrentAttempt(): void
    {
        $s = $this->scene('document', 1, ['status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $this->assertSame('incomplete', $item->get_status());

        // A change made behind the object's back is seen only when the database is asked.
        $this->conn()->executeStatement('UPDATE c_lp_item_view SET status = ? WHERE iid = ?', ['passed', $s['iv']]);
        $this->assertSame('passed', $item->get_status(true));
        $this->assertSame('incomplete', $item->get_status(false), 'the local copy is untouched by default');
        $this->assertSame('passed', $item->get_status(true, true));
        $this->assertSame('passed', $item->get_status(false), 'update_local copies the database value into the object');

        // The row of another attempt is not the current one: no row for the attempt means "not attempted".
        $item->set_attempt_id(5);
        $this->assertSame('not attempted', $item->get_status(true));
    }

    public function testGetStatusWithoutAnItemViewIsNotAttempted(): void
    {
        $s = $this->scene();
        $item = $this->item($s['item']);

        $this->assertSame('not attempted', $item->get_status(true));
        $this->assertSame('not attempted', $item->get_status(false));
    }

    public function testIsDoneCountsFailedAsDoneButNotBrowsedOrIncomplete(): void
    {
        $s = $this->scene();
        $item = $this->item($s['item'], $s['view']);
        $done = [];
        foreach (['not attempted', 'incomplete', 'completed', 'passed', 'failed', 'browsed'] as $status) {
            $this->conn()->executeStatement('UPDATE c_lp_item_view SET status = ? WHERE iid = ?', [$status, $s['iv']]);
            $done[$status] = $item->is_done();
        }

        // The docblock says completed/passed/succeeded; the code lists failed too ("done" = finished, not "successful").
        $this->assertSame([
            'not attempted' => false,
            'incomplete' => false,
            'completed' => true,
            'passed' => true,
            'failed' => true,
            'browsed' => false,
        ], $done);
    }

    public function testStatusIsMatchesTheDatabaseStatusAgainstAListOfPatterns(): void
    {
        $s = $this->scene('document', 1, ['status' => 'Completed']);
        $item = $this->item($s['item'], $s['view']);

        $this->assertTrue($item->status_is(['passed', 'completed']), 'matching is case-insensitive');
        $this->assertFalse($item->status_is(['passed', 'failed']));
        $this->assertFalse($item->status_is([]));
        $this->assertTrue($item->status_is(['comp.*']), 'entries are used as regular-expression fragments');
    }

    /**
     * @dataProvider restartAllowedProvider
     */
    public function testIsRestartAllowedDependsOnPreventReinitAndTheDatabaseStatus(int $prevent, string $status, int $expected): void
    {
        $s = $this->scene('document', 1, ['status' => $status]);
        $item = $this->item($s['item'], $s['view']);
        $item->set_prevent_reinit($prevent);

        $this->assertSame($expected, $item->isRestartAllowed());
    }

    /**
     * @return array<string, array{int, string, int}>
     */
    public static function restartAllowedProvider(): array
    {
        return [
            // prevent_reinit, status, expected
            'single attempt, not attempted' => [1, 'not attempted', 0],
            'single attempt, incomplete' => [1, 'incomplete', 0],
            'single attempt, completed' => [1, 'completed', -1],
            'single attempt, browsed' => [1, 'browsed', -1],
            'multiple attempts, not attempted' => [0, 'not attempted', -1],
            'multiple attempts, incomplete' => [0, 'incomplete', -1],
            'multiple attempts, passed' => [0, 'passed', 1],
        ];
    }

    public function testCreditAndLessonModeFollowTheStatusWhenReinitIsPrevented(): void
    {
        $s = $this->scene('document', 1, ['status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $this->assertSame('credit', $item->get_credit());
        $this->assertSame('normal', $item->get_lesson_mode());

        $this->conn()->executeStatement("UPDATE c_lp_item_view SET status = 'completed' WHERE iid = ?", [$s['iv']]);
        $this->assertSame('no-credit', $item->get_credit());
        $this->assertSame('review', $item->get_lesson_mode());

        $item->set_prevent_reinit(0);
        $this->assertSame('credit', $item->get_credit(), 'with several attempts allowed a finished item is still credited');
        $this->assertSame('normal', $item->get_lesson_mode());
    }

    public function testPreventReinitLazyLookupIsDeadBecauseThePropertyDefaultsToOne(): void
    {
        $lp = $this->newLpRow('li_prevent');
        $this->conn()->executeStatement('UPDATE c_lp SET prevent_reinit = 0 WHERE iid = ?', [$lp]);
        $a = $this->addItem($lp, 'li_a');
        $item = $this->item($a);

        // PINNED QUIRK: get_prevent_reinit() only reads c_lp.prevent_reinit when the property is unset, but the
        // property is declared with a default of 1 so the lookup never runs; learnpath pushes the value with
        // set_prevent_reinit() instead. Same for get_seriousgame_mode() (always 0 from the constructor).
        $this->assertSame(1, $item->get_prevent_reinit());
        $this->assertSame(0, $item->get_seriousgame_mode());
        $item->set_prevent_reinit(0);
        $this->assertSame(0, $item->get_prevent_reinit());
        $item->set_prevent_reinit('yes');
        $this->assertSame(1, $item->get_prevent_reinit());
    }

    // ---------------------------------------------------------------- write_to_db / save / close / restart

    public function testWriteToDbUpdatesTheChamiloItemViewAndAddsTheViewingTimeToTheTotal(): void
    {
        $s = $this->scene('document', 1, ['total_time' => 100, 'status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 120);
        $item->set_status('completed');
        $item->current_score = 7.5;
        $item->current_data = 'li_suspend';
        $item->lesson_location = 'li_page_3';

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status']);
        $this->assertSame(220, (int) $row['total_time'], 'total_time = stored total_time + this viewing (120 s)');
        $this->assertSame(1700000000, (int) $row['start_time']);
        $this->assertEquals(7.5, $row['score']);
        $this->assertEquals(100, $row['max_score'], 'no max score on the item means 100');
        $this->assertSame('li_suspend', $row['suspend_data']);
        $this->assertSame('li_page_3', $row['lesson_location']);
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item_view WHERE lp_view_id = ?', [$s['view']]), 'no row added');
        $this->assertGreaterThan(1700000000, $item->current_start_time, 'the start time is reset to now after a save');
    }

    public function testWriteToDbOnlyTouchesTheRowOfTheCurrentViewItemAndAttempt(): void
    {
        $s = $this->scene('document', 1, ['status' => 'incomplete', 'score' => 1]);
        $otherView = $this->newView($s['lp'], 3);
        $this->newItemView($otherView, $s['item'], 1, ['score' => 2, 'status' => 'incomplete']);
        $this->newItemView($s['view'], $s['item'], 2, ['score' => 3, 'status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $this->assertSame(2, $item->get_view_count());
        $item->set_status('passed');
        $item->current_score = 9;

        $this->assertTrue($item->write_to_db());

        $this->assertEquals(9, $this->ivRow($s['view'], $s['item'], 2)['score']);
        $this->assertSame('passed', $this->ivRow($s['view'], $s['item'], 2)['status']);
        $this->assertEquals(1, $this->ivRow($s['view'], $s['item'], 1)['score'], 'older attempt untouched');
        $this->assertEquals(2, $this->ivRow($otherView, $s['item'], 1)['score'], 'other user view untouched');
    }

    public function testWriteToDbWithoutAnLpViewWritesNothing(): void
    {
        $s = $this->scene('document', 1, ['score' => 1]);
        $item = $this->item($s['item']);
        $item->current_score = 5;

        $this->assertFalse($item->write_to_db());
        $this->assertEquals(1, $this->ivRow($s['view'], $s['item'])['score']);
    }

    public function testWriteToDbOnAScoItemOfAScormLpUpdatesScoreAndStatusButLeavesTheTimeToScormUpdateTime(): void
    {
        $s = $this->scene('sco', 2, ['total_time' => 50, 'status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 120);
        $item->set_status('completed');
        $item->current_score = 4;

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status']);
        $this->assertEquals(4, $row['score']);
        $this->assertSame(50, (int) $row['total_time'], 'a sco total_time is only written by scorm_update_time()');
        $this->assertSame(0, (int) $row['start_time'], 'and so is not its start_time');
    }

    public function testWriteToDbSkipsAScoItemThatIsAlreadyFinishedWhenReinitIsPrevented(): void
    {
        $s = $this->scene('sco', 2, ['status' => 'completed', 'score' => 4]);
        $item = $this->item($s['item'], $s['view']);
        $item->set_status('incomplete');
        $item->current_score = 1;

        // "no-credit" / "review": the SCORM runtime cannot overwrite a finished attempt.
        $this->assertTrue($item->write_to_db());
        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status']);
        $this->assertEquals(4, $row['score']);
    }

    public function testWriteToDbOfAQuizLeavesStatusAndTimeToTheExerciseTracking(): void
    {
        $s = $this->scene('quiz', 1, ['status' => 'incomplete', 'total_time' => 70], 4242);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 120);
        $item->set_status('completed');
        $item->current_score = 6;

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('incomplete', $row['status'], 'the quiz status comes from track_e_exercises, not from write_to_db');
        $this->assertSame(70, (int) $row['total_time']);
        $this->assertEquals(6, $row['score']);
        $this->assertSame(1700000000, (int) $row['start_time']);
    }

    public function testWriteToDbOfAQuizTakesItsDurationFromTheExerciseResultOfTheRequest(): void
    {
        $quizId = $this->newQuiz();
        $s = $this->scene('quiz', 1, ['status' => 'incomplete', 'total_time' => 70], $quizId);
        $this->conn()->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, exe_date, score, max_score, user_ip, status, data_tracking, start_date,
                steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check, exe_exo_id)
             VALUES (1, 1, '2030-01-01 10:00:00', 5, 10, '127.0.0.1', '', '', '2030-01-01 09:55:00', 1, ?, ?, 300, 0, '', ?)",
            [$s['lp'], $s['item'], $quizId]
        );
        $exeId = (int) $this->conn()->lastInsertId();
        $_REQUEST['exeId'] = (string) $exeId;
        $item = $this->item($s['item'], $s['view']);

        $this->assertTrue($item->write_to_db());

        $this->assertSame(300, (int) $this->ivRow($s['view'], $s['item'])['total_time'], 'total_time is replaced (not added to) by exe_duration');
    }

    public function testWriteToDbInsertsTheRowOfAnAttemptThatDoesNotExistYet(): void
    {
        $s = $this->scene('document', 1);
        $item = $this->item($s['item'], $s['view']);
        $item->set_attempt_id(2);
        $this->window($item, 120);
        $item->set_status('incomplete');
        $item->current_score = 3;
        $item->current_data = 'li_sd';

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item'], 2);
        $this->assertNotFalse($row, 'the missing attempt row is inserted (c_lp_item_view has no c_id column)');
        $this->assertSame((int) $row['iid'], $item->db_item_view_id);
        $this->assertSame('incomplete', $row['status']);
        $this->assertSame(120, (int) $row['total_time']);
        $this->assertSame(1700000000, (int) $row['start_time']);
        $this->assertEquals(3, $row['score']);
        $this->assertSame('li_sd', $row['suspend_data']);
        $this->assertSame('not attempted', $this->ivRow($s['view'], $s['item'], 1)['status'], 'attempt 1 untouched');
    }

    public function testWriteToDbOfAHotpotatoesItemReplacesTheTimeAndUpdatesItsRowByViewItemAndAttempt(): void
    {
        $s = $this->scene('hotpotatoes', 1, ['status' => 'incomplete', 'total_time' => 70]);
        $this->newItemView($s['view'], $s['item'], 2, ['status' => 'incomplete', 'total_time' => 5]);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 90);
        $item->set_status('completed');
        $item->current_score = 8;

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item'], 2);
        $this->assertSame('completed', $row['status']);
        $this->assertSame(90, (int) $row['total_time'], 'hotpotatoes replaces the total instead of adding to it');
        $this->assertEquals(8, $row['score']);
        $this->assertSame('incomplete', $this->ivRow($s['view'], $s['item'], 1)['status'], 'other attempt untouched');
    }

    public function testWriteToDbPersistsInteractionsOnceAndUpdatesThemInPlace(): void
    {
        $s = $this->scene('sco', 2, ['status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $item->add_interaction(0, ['li_q1', 'choice', '10:00:00', 1.5, ['a', 'b'], 'a', 'correct', '5']);
        $item->add_interaction(1, ['li_q2', 'fill-in', '10:01:00', '', 'x', 'y', 'wrong', '7']);

        $this->assertTrue($item->write_to_db());
        $rows = $this->conn()->fetchAllAssociative('SELECT * FROM c_lp_iv_interaction WHERE lp_iv_id = ? ORDER BY order_id', [$s['iv']]);
        $this->assertCount(2, $rows);
        $this->assertSame(['li_q1', 'choice', '10:00:00', 'a,b', 'a', 'correct', '5', 0, 1], [
            $rows[0]['interaction_id'], $rows[0]['interaction_type'], $rows[0]['completion_time'], $rows[0]['correct_responses'],
            $rows[0]['student_response'], $rows[0]['result'], $rows[0]['latency'], (int) $rows[0]['order_id'], (int) $rows[0]['c_id'],
        ]);
        $this->assertEquals(1.5, $rows[0]['weighting']);
        $this->assertEquals(0, $rows[1]['weighting'], 'an empty weighting is stored as 0');
        // PINNED QUIRK: only an array of correct responses is stored (joined with commas); a plain string, as sent
        // for interaction 1 above, is silently replaced by ''.
        $this->assertSame('', $rows[1]['correct_responses']);

        // Same interaction again, with a new answer: updated, not duplicated.
        $item->add_interaction(0, ['li_q1', 'choice', '10:00:00', 1.5, ['a', 'b'], 'b', 'wrong', '9']);
        $this->assertTrue($item->write_to_db());
        $rows = $this->conn()->fetchAllAssociative('SELECT * FROM c_lp_iv_interaction WHERE lp_iv_id = ? ORDER BY order_id', [$s['iv']]);
        $this->assertCount(2, $rows);
        $this->assertSame(['b', 'wrong', '9'], [$rows[0]['student_response'], $rows[0]['result'], $rows[0]['latency']]);
    }

    public function testSaveOfAnAssetOrToolItemMarksItCompletedOnlyWhenThePrerequisitesAreComplete(): void
    {
        foreach (['asset', 'document', 'link'] as $type) {
            $s = $this->scene($type, 1, ['status' => 'incomplete']);

            $item = $this->item($s['item'], $s['view']);
            $this->assertTrue($item->save(false, false));
            $this->assertSame('incomplete', $this->ivRow($s['view'], $s['item'])['status'], "$type: prerequisites not complete");

            $item = $this->item($s['item'], $s['view']);
            $this->assertTrue($item->save(false, true));
            $this->assertSame('completed', $this->ivRow($s['view'], $s['item'])['status'], "$type: prerequisites complete");
        }
    }

    public function testSaveOfAQuizReturnsFalseAndWritesNothing(): void
    {
        $s = $this->scene('quiz', 1, ['status' => 'incomplete', 'score' => 1], 4242);
        $item = $this->item($s['item'], $s['view']);
        $item->current_score = 9;

        $this->assertFalse($item->save(false, true));
        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('incomplete', $row['status']);
        $this->assertEquals(1, $row['score']);
    }

    public function testSaveOfAScoReadsTheRuntimeValuesFromTheQueryString(): void
    {
        $s = $this->scene('sco', 2, ['status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $_GET = [
            'score' => '42',
            'lesson_status' => 'passed',
            'suspend_data' => 'li_bookmark',
            'lesson_location' => 'li_slide_9',
            'core_exit' => 'whatever',
            'time' => '0',
        ];

        $this->assertTrue($item->save(true));

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertEquals(42, $row['score']);
        $this->assertSame('passed', $row['status']);
        $this->assertSame('li_bookmark', $row['suspend_data']);
        $this->assertSame('li_slide_9', $row['lesson_location']);
        // core_exit is kept on the object (any value but '' and 'suspend' becomes 'none') but is not a stored column here.
        $this->assertSame('none', $item->get_core_exit());
    }

    public function testSaveOfAScoIgnoresTheQueryStringWhenNotSavingFromOutside(): void
    {
        $s = $this->scene('sco', 2, ['status' => 'incomplete', 'score' => 1]);
        $item = $this->item($s['item'], $s['view']);
        $_GET = ['score' => '99', 'lesson_status' => 'completed'];

        $this->assertTrue($item->save(false));

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertEquals(1, $row['score']);
        $this->assertSame('incomplete', $row['status']);
    }

    public function testCloseSavesANonScoItemAndStopsTheClock(): void
    {
        $s = $this->scene('document', 1, ['status' => 'incomplete', 'total_time' => 10]);
        $item = $this->item($s['item'], $s['view']);
        $item->current_start_time = time() - 5;
        $item->set_status('completed');

        $before = time();
        $this->assertTrue($item->close());

        $this->assertGreaterThanOrEqual($before, $item->current_stop_time);
        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status']);
        $this->assertGreaterThanOrEqual(15, (int) $row['total_time']);
        $this->assertLessThan(25, (int) $row['total_time']);
    }

    public function testCloseDoesNotSaveWhenSaveOnCloseIsOff(): void
    {
        $s = $this->scene('document', 1, ['status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $item->save_on_close = false;
        $item->set_status('completed');

        $this->assertTrue($item->close());
        $this->assertSame('incomplete', $this->ivRow($s['view'], $s['item'])['status']);
    }

    public function testRestartOfAFinishedItemWhenReinitIsPreventedChangesNothing(): void
    {
        $s = $this->scene('document', 1, ['status' => 'completed', 'score' => 5]);
        $item = $this->item($s['item'], $s['view']);
        $before = $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item_view WHERE lp_view_id = ?', [$s['view']]);

        $this->assertTrue($item->restart());

        $this->assertSame('completed', $item->get_status(false));
        $this->assertSame(1, $item->get_attempt_id());
        $this->assertSame($before, $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item_view WHERE lp_view_id = ?', [$s['view']]));
    }

    public function testRestartOfAnUnfinishedItemKeepsTheAttemptAndResetsTheClock(): void
    {
        $s = $this->scene('document', 1, ['status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 30);

        $this->assertTrue($item->restart());

        $this->assertSame(0, $item->current_start_time);
        $this->assertSame(0, $item->current_stop_time);
        $this->assertSame(1, $item->get_attempt_id());
    }

    public function testRestartOfAFinishedItemWithSeveralAttemptsOpensANewAttempt(): void
    {
        $s = $this->scene('document', 1, ['status' => 'passed', 'score' => 5]);
        $item = $this->item($s['item'], $s['view']);
        $item->set_prevent_reinit(0);
        $item->current_score = 5;

        $this->assertTrue($item->restart());

        $this->assertSame(2, $item->get_attempt_id());
        $this->assertSame('not attempted', $item->get_status(false));
        $new = $this->ivRow($s['view'], $s['item'], 2);
        $this->assertNotFalse($new, 'the new attempt is recorded');
        $this->assertSame('not attempted', $new['status']);
        $this->assertEquals(0, $new['score']);
        $old = $this->ivRow($s['view'], $s['item'], 1);
        $this->assertSame('passed', $old['status'], 'the finished attempt is kept');
        $this->assertEquals(5, $old['score']);
    }

    public function testGetInteractionsCountFromTheDatabaseCountsTheRowsOfTheCurrentAttempt(): void
    {
        $s = $this->scene('sco', 2);
        $iv2 = $this->newItemView($s['view'], $s['item'], 2);
        foreach ([[$s['iv'], 'li_a0'], [$iv2, 'li_b0'], [$iv2, 'li_b1']] as $i => [$iv, $id]) {
            $this->conn()->executeStatement(
                "INSERT INTO c_lp_iv_interaction (c_id, order_id, lp_iv_id, interaction_id, interaction_type, weighting, completion_time, correct_responses, student_response, result, latency)
                 VALUES (1, ?, ?, ?, 'choice', 1, '', '', '', '', '')",
                [$i, $iv, $id]
            );
        }
        $item = $this->item($s['item'], $s['view']);
        $item->set_attempt_id(1);
        $this->assertSame(1, $item->get_interactions_count(true));
        $item->set_attempt_id(2);
        $this->assertSame(2, $item->get_interactions_count(true));
        $item->set_attempt_id(3);
        $this->assertSame(0, $item->get_interactions_count(true));

        $this->assertSame(2, $this->item($s['item'], $s['view'])->get_interactions_count(false), 'set_lp_view counted the latest attempt');
    }

    public function testLoadInteractionsFillsTheInteractionsOfTheCurrentAttemptKeyedByInteractionId(): void
    {
        $s = $this->scene('sco', 2);
        $iv2 = $this->newItemView($s['view'], $s['item'], 2);
        $ins = fn (int $iv, int $order, string $id, string $time, float $weight) => $this->conn()->executeStatement(
            "INSERT INTO c_lp_iv_interaction (c_id, order_id, lp_iv_id, interaction_id, interaction_type, weighting, completion_time, correct_responses, student_response, result, latency)
             VALUES (1, ?, ?, ?, 'choice', ?, ?, 'a,b', 'a', 'wrong', '9')",
            [$order, $iv, $id, $weight, $time]
        );
        $ins($s['iv'], 0, 'li_old', '01:00:00', 9);
        $ins($iv2, 0, 'li_q1', '10:00:00', 1.5);
        $ins($iv2, 1, 'li_q2', '10:01:00', 2);
        $item = $this->item($s['item'], $s['view']);
        $item->interactions = ['stale' => ['x']];

        $item->load_interactions();

        $this->assertSame(['li_q1', 'li_q2'], array_keys($item->interactions), 'the previous content is replaced, attempt 1 is not loaded');
        // Same layout as add_interaction(): id(0), type(1), time(2), weighting(3), correct(4), student(5), result(6), latency(7)
        $this->assertSame('li_q1', $item->interactions['li_q1'][0]);
        $this->assertSame('choice', $item->interactions['li_q1'][1]);
        $this->assertSame('10:00:00', $item->interactions['li_q1'][2]);
        $this->assertEquals(1.5, $item->interactions['li_q1'][3]);
        $this->assertSame(['a,b', 'a', 'wrong', '9'], \array_slice($item->interactions['li_q1'], 4));
        $this->assertStringContainsString("'li_q2'", $item->get_interactions_js_array(true));
    }

    public function testWriteObjectivesToDbUpdatesAnExistingObjectiveRow(): void
    {
        $s = $this->scene('sco', 2);
        $this->conn()->executeStatement(
            "INSERT INTO c_lp_iv_objective (c_id, lp_iv_id, order_id, objective_id, score_raw, score_max, score_min, status)
             VALUES (1, ?, 0, 'li_obj', 0, 0, 0, 'incomplete')",
            [$s['iv']]
        );
        $item = $this->item($s['item'], $s['view']);
        // id(0), status(1), score_raw(2), score_max(3), score_min(4)
        $item->add_objective(0, ['li_obj', 'completed', 7, 10, 2]);

        $item->write_objectives_to_db();

        $rows = $this->conn()->fetchAllAssociative('SELECT * FROM c_lp_iv_objective WHERE lp_iv_id = ?', [$s['iv']]);
        $this->assertCount(1, $rows);
        $this->assertSame('completed', $rows[0]['status']);
        $this->assertEquals(7, $rows[0]['score_raw']);
        $this->assertEquals(10, $rows[0]['score_max']);
        $this->assertEquals(2, $rows[0]['score_min']);
    }

    public function testWriteObjectivesToDbInsertsNewObjectivesAndKeepsTheirOrder(): void
    {
        $s = $this->scene('sco', 2);
        $item = $this->item($s['item'], $s['view']);
        $item->add_objective(0, ['li_new_obj', 'passed', 1, 2, 0.5]);
        $item->add_objective(1, ['li_new_obj2', 'failed', 3, 4, 1]);

        $item->write_objectives_to_db();
        $item->write_objectives_to_db(); // a second write updates, it does not duplicate

        $rows = $this->conn()->fetchAllAssociative('SELECT * FROM c_lp_iv_objective WHERE lp_iv_id = ? ORDER BY order_id', [$s['iv']]);
        $this->assertCount(2, $rows);
        $this->assertSame(['li_new_obj', 'passed', 0, 1], [$rows[0]['objective_id'], $rows[0]['status'], (int) $rows[0]['order_id'], (int) $rows[0]['c_id']]);
        $this->assertEquals(1, $rows[0]['score_raw']);
        $this->assertEquals(2, $rows[0]['score_max']);
        $this->assertEquals(0.5, $rows[0]['score_min']);
        $this->assertSame(['li_new_obj2', 'failed', 1], [$rows[1]['objective_id'], $rows[1]['status'], (int) $rows[1]['order_id']]);
    }

    public function testWriteObjectivesToDbWorksForAnItemWithoutAttemptCounterLikeTheOtherWriters(): void
    {
        $s = $this->scene('sco', 2);
        $item = $this->item($s['item'], $s['view']);
        $item->attempt_id = null;
        $item->add_objective(0, ['li_obj_default', 'passed', 1, 2, 0]);

        // get_attempt_id() defaults to 1, which is what the other methods query; a bare "view_count = " would be a syntax error.
        $item->write_objectives_to_db();

        $this->assertSame(1, (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM c_lp_iv_objective WHERE lp_iv_id = ? AND objective_id = 'li_obj_default'", [$s['iv']]));
    }

    public function testAddObjectiveIgnoresAnObjectiveWithoutId(): void
    {
        $s = $this->scene('sco', 2);
        $item = $this->item($s['item'], $s['view']);

        $this->assertNull($item->add_objective(0, ['', 'passed']));
        $this->assertSame([], $item->objectives);
        $item->add_objective(0, ['li_o', 'passed', 1, 2, 0]);
        $this->assertCount(1, $item->objectives);
        // PINNED QUIRK: the counter is count()+1 (one more than the number of objectives).
        $this->assertSame(2, $item->get_objectives_count());
    }

    // ---------------------------------------------------------------- time

    public function testSetTimeInScormFormatAddsToTheStoredTotalOfAScoItem(): void
    {
        $s = $this->scene('sco', 2, ['total_time' => 100]);
        $item = $this->item($s['item'], $s['view']);

        $item->set_time('01:02:03.45');
        $this->assertSame(100 + 3723, (int) $this->ivRow($s['view'], $s['item'])['total_time'], 'accumulate_scorm_time is on by default');
        $item->set_time('00:00:10');
        $this->assertSame(100 + 3723 + 10, (int) $this->ivRow($s['view'], $s['item'])['total_time']);
        $item->set_time('not a time');
        $this->assertSame(100 + 3723 + 10, (int) $this->ivRow($s['view'], $s['item'])['total_time'], 'unparsable values are ignored');
    }

    public function testSetTimeOnlyTouchesTheRowOfTheCurrentAttempt(): void
    {
        $s = $this->scene('sco', 2, ['total_time' => 100]);
        $this->newItemView($s['view'], $s['item'], 2, ['total_time' => 7]);
        $item = $this->item($s['item'], $s['view']);

        $item->set_time('00:00:10');

        $this->assertSame(17, (int) $this->ivRow($s['view'], $s['item'], 2)['total_time']);
        $this->assertSame(100, (int) $this->ivRow($s['view'], $s['item'], 1)['total_time']);
    }

    public function testSaveOfAScoKeepsAFinishedStatusAgainstAnUnfinishedOneButAcceptsAnotherFinishedOne(): void
    {
        $s = $this->scene('sco', 2, ['status' => 'completed']);
        $item = $this->item($s['item'], $s['view']);

        $_GET = ['lesson_status' => 'incomplete'];
        $item->save(true);
        $this->assertSame('completed', $item->get_status(false), 'completed is not reset to incomplete');

        $_GET = ['lesson_status' => 'passed'];
        $item->save(true);
        $this->assertSame('passed', $item->get_status(false), 'but a finished status may replace another');
    }

    public function testSetTimeOfAScoItemReplacesTheTotalWhenTheLpDoesNotAccumulateScormTime(): void
    {
        $s = $this->scene('sco', 2, ['total_time' => 100]);
        $this->conn()->executeStatement('UPDATE c_lp SET accumulate_scorm_time = 0 WHERE iid = ?', [$s['lp']]);
        $item = $this->item($s['item'], $s['view']);

        $item->set_time('00:00:30');
        $this->assertSame(130, (int) $this->ivRow($s['view'], $s['item'])['total_time']);
        $item->set_time('00:00:50');
        $this->assertSame(150, (int) $this->ivRow($s['view'], $s['item'])['total_time'], 'the previous session time (30) is replaced by the new one (50)');
        $this->assertSame(50, $item->getLastScormSessionTime());
    }

    public function testSetTimeIntFormatOfANonScoItemReplacesTheLastSessionTime(): void
    {
        $s = $this->scene('document', 1, ['total_time' => 100]);
        $item = $this->item($s['item'], $s['view']);

        $item->set_time(40, 'int');
        $this->assertSame(140, (int) $this->ivRow($s['view'], $s['item'])['total_time']);
        $item->set_time(60, 'int');
        $this->assertSame(160, (int) $this->ivRow($s['view'], $s['item'])['total_time']);
    }

    public function testSetTimeZeroOnANonScoItemTurnsTheElapsedTimeSinceStartIntoTheWindow(): void
    {
        $s = $this->scene('document');
        $item = $this->item($s['item'], $s['view']);
        $item->current_start_time = time() - 50;

        $item->set_time('0');

        $elapsed = $item->current_stop_time - $item->current_start_time;
        $this->assertGreaterThanOrEqual(50, $elapsed);
        $this->assertLessThan(60, $elapsed);
        $this->assertSame(0, (int) $this->ivRow($s['view'], $s['item'])['total_time'], 'nothing is stored until a save');
    }

    public function testGetScormTimeFormatsTheMemoryWindowOrTheStoredTotal(): void
    {
        $s = $this->scene('document', 1, ['start_time' => 5000, 'total_time' => 3661]);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 61);

        $this->assertSame('01 : 01 : 01', $item->get_scorm_time('js', 3661));
        $this->assertSame('00 : 01 : 01', $item->get_scorm_time('js'), 'memory window: 61 s');
        $this->assertSame('01 : 01 : 01', $item->get_scorm_time('js', null, true), 'database: total_time');
        $this->assertSame('00 : 00 : 00', $this->item($s['item'])->get_scorm_time('js'), 'no start time = no time');
    }

    public function testGetTotalTimeIsTheWindowAndNeverNegative(): void
    {
        $s = $this->scene();
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 120);
        $this->assertSame(120, $item->get_total_time());

        $item->current_start_time = 1700000100;
        $item->current_stop_time = 1700000000;
        $this->assertSame(0, $item->get_total_time());
    }

    // ---------------------------------------------------------------- write_to_db: update fragments per lp type

    public function testWriteToDbOfAnAssetInAScormLpAddsTheTimeWhileTheStoredStatusIsNotFinished(): void
    {
        $s = $this->scene('asset', 2, ['total_time' => 100, 'status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 120);
        $item->set_status('completed');

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status']);
        $this->assertSame(220, (int) $row['total_time']);
    }

    public function testWriteToDbOfAnAssetInAScormLpKeepsAFinishedStoredStatusAndTime(): void
    {
        $s = $this->scene('asset', 2, ['total_time' => 100, 'status' => 'completed', 'score' => 1]);
        $item = $this->item($s['item'], $s['view']);
        $this->window($item, 120);
        $item->set_status('incomplete');
        $item->current_score = 6;

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status'], 'a finished item is not reset to incomplete');
        $this->assertSame(100, (int) $row['total_time']);
        $this->assertEquals(6, $row['score'], 'the other columns are still written');
    }

    public function testWriteToDbWithSeveralAttemptsInAChamiloLpAddsTimeAndWritesTheStatus(): void
    {
        $s = $this->scene('document', 1, ['total_time' => 100, 'status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $item->set_prevent_reinit(0);
        $this->window($item, 60);
        $item->set_status('passed');

        $this->assertTrue($item->write_to_db());

        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('passed', $row['status']);
        $this->assertSame(160, (int) $row['total_time']);
    }

    public function testWriteToDbWithSeveralAttemptsInAScormLpWritesTheTimeAndStatusUnlessAlreadyFinished(): void
    {
        $s = $this->scene('asset', 2, ['total_time' => 100, 'status' => 'incomplete']);
        $item = $this->item($s['item'], $s['view']);
        $item->set_prevent_reinit(0);
        $this->window($item, 120);
        $item->set_status('completed');

        $this->assertTrue($item->write_to_db());
        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status']);
        $this->assertSame(220, (int) $row['total_time']);

        // The stored attempt is finished now: neither its status nor its time are overwritten any more.
        $item->set_status('incomplete');
        $this->window($item, 500);
        $item->current_score = 9;
        $this->assertTrue($item->write_to_db());
        $row = $this->ivRow($s['view'], $s['item']);
        $this->assertSame('completed', $row['status']);
        $this->assertSame(220, (int) $row['total_time']);
        $this->assertEquals(9, $row['score']);
    }

    public function testWriteToDbOfAFinishedQuizWithSeveralAttemptsOpensANewAttemptRow(): void
    {
        $quizId = $this->newQuiz();
        $s = $this->scene('quiz', 1, ['status' => 'completed', 'score' => 7], $quizId);
        $item = $this->item($s['item'], $s['view']);
        $item->set_prevent_reinit(0);

        $this->assertTrue($item->write_to_db());

        $this->assertSame(2, $item->get_attempt_id());
        $new = $this->ivRow($s['view'], $s['item'], 2);
        $this->assertNotFalse($new);
        $this->assertSame('not attempted', $new['status']);
        $this->assertEquals(0, $new['score']);
        $old = $this->ivRow($s['view'], $s['item'], 1);
        $this->assertSame('completed', $old['status']);
        $this->assertEquals(7, $old['score']);
    }

    public function testScormUpdateTimeInSeriousGameModeFreezesTheTimeOfAFinishedItem(): void
    {
        $s = $this->scene('sco', 2, ['total_time' => 100, 'status' => 'completed']);
        $item = $this->item($s['item'], $s['view']);
        $item->seriousgame_mode = 1;

        $item->scorm_update_time(30);
        $this->assertSame(100, (int) $this->ivRow($s['view'], $s['item'])['total_time'], 'a finished item keeps its time');

        $this->conn()->executeStatement("UPDATE c_lp_item_view SET status = 'incomplete' WHERE iid = ?", [$s['iv']]);
        $item->scorm_update_time(30);
        $this->assertSame(130, (int) $this->ivRow($s['view'], $s['item'])['total_time'], 'an unfinished one still accumulates');
    }

    public function testSetTermsMergesTheNewTermsWithTheStoredOnes(): void
    {
        $lp = $this->newLpRow('li_terms');
        $a = $this->addItem($lp, 'li_a');
        $item = $this->item($a);
        $this->assertNull($item->get_terms());

        $this->assertTrue($item->set_terms('b,a'));
        $this->assertSame('b,a', $item->get_terms(), 'a never-set (NULL) column adds no empty term');

        $item->set_terms('c,b');
        $this->assertSame('c,b,a', $item->get_terms(), 'new terms first, then the stored ones that are not in the new list');

        $item->set_terms("o'k");
        $this->assertSame('o&#039;k,c,b,a', $item->get_terms(), 'the string is stored HTML-encoded (quotes included)');
    }

    public function testDeleteRemovesTheItemAndEveryAttemptOfItsViews(): void
    {
        $s = $this->scene();
        $this->newItemView($s['view'], $s['item'], 2);
        $other = $this->addItem($s['lp'], 'li_other');
        $this->newItemView($s['view'], $other, 1);
        $item = $this->item($s['item'], $s['view']);

        $this->assertTrue($item->delete());

        $this->assertFalse($this->conn()->fetchOne('SELECT iid FROM c_lp_item WHERE iid = ?', [$s['item']]));
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item_view WHERE lp_item_id = ?', [$s['item']]));
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_lp_item_view WHERE lp_item_id = ?', [$other]), 'other items keep their views');
        $this->assertFalse($item->delete(), 'a second delete finds no row');
    }

    public function testGetFilePathOfNonNumericPathsIsPrefixedByTheScormDirectory(): void
    {
        $lp = $this->newLpRow('li_path');
        $a = $this->addItem($lp, 'li_a', 'sco', 1, null, 'sub/index.html');
        $dir = $this->addItem($lp, 'li_dir', 'dir', 2);
        $quiz = $this->addItem($lp, 'li_quiz', 'quiz', 3, null, 77);

        $this->assertSame('sub/index.html', $this->item($a)->get_file_path());
        $this->assertSame('/tmp/scorm/sub/index.html', $this->item($a)->get_file_path('/tmp/scorm/'));
        $this->assertSame('', $this->item($dir)->get_file_path(), 'a dir has no file');
        $this->assertSame('-1', $this->item($quiz)->get_file_path(), 'numeric path of a tool item other than document');
    }

    public function testGetFilePathOfADocumentReadsAPathColumnThatNoLongerExists(): void
    {
        $lp = $this->newLpRow('li_path_doc');
        $doc = $this->addItem($lp, 'li_doc', 'document', 1, null, 123456);

        // PINNED QUIRK: a numeric document path is resolved with "SELECT path FROM c_document WHERE iid = ...";
        // c_document has no path column any more (the file lives in the resource node), so this always fails.
        $this->expectException(NotAllowedException::class);
        $this->item($doc)->get_file_path();
    }

    public function testHumanizeStatusReturnsTheTranslatedLabelOrABadge(): void
    {
        $this->assertSame('Completed', learnpathItem::humanize_status('completed', false));
        $this->assertSame('Not attempted', learnpathItem::humanize_status('not attempted', false));
        $this->assertSame('Incomplete', learnpathItem::humanize_status('passed', false, 'simple'), 'simple mode hides finished-but-not-completed statuses');
        $this->assertSame('Passed', learnpathItem::humanize_status('passed', false));
        $this->assertStringContainsString('badge--success', learnpathItem::humanize_status('passed'));
        $this->assertStringContainsString('badge--error', learnpathItem::humanize_status('failed'));
    }

    // ---------------------------------------------------------------- prerequisites

    /**
     * Three "document" items in one lp, each with an item view in $statuses order.
     *
     * @param array<int, string> $statuses
     *
     * @return array{items: array<int, learnpathItem>, refs: array<int, int>, ids: array<int, int>, lp: int, view: int, iv: array<int, int>}
     */
    private function prereqScene(array $statuses, string $type = 'document', int|string $path = 0): array
    {
        $lp = $this->newLpRow('li_prereq');
        $view = $this->newView($lp);
        $ids = [];
        $iv = [];
        foreach ($statuses as $i => $status) {
            $ids[$i] = $this->addItem($lp, 'li_p'.$i, $type, $i + 1, null, $path);
            $iv[$i] = $this->newItemView($view, $ids[$i], 1, ['status' => $status]);
        }

        return $this->prereqItems($lp, $view, $ids, $iv);
    }

    /**
     * @param array<int, int> $ids
     * @param array<int, int> $iv
     *
     * @return array{items: array<int, learnpathItem>, refs: array<int, int>, ids: array<int, int>, lp: int, view: int, iv: array<int, int>}
     */
    private function prereqItems(int $lp, int $view, array $ids, array $iv): array
    {
        $items = [];
        $refs = [];
        foreach ($ids as $id) {
            $items[$id] = $this->item($id, $view);
            $refs[$id] = $id;
        }

        return ['items' => $items, 'refs' => $refs, 'ids' => $ids, 'lp' => $lp, 'view' => $view, 'iv' => $iv];
    }

    /**
     * Evaluates $expression on the last item of the scene.
     */
    private function check(array $p, string $expression): bool
    {
        $target = $p['items'][end($p['ids'])];

        return $target->parse_prereq($expression, $p['items'], $p['refs'], self::USER_ID);
    }

    private function alert(array $p): string
    {
        return $p['items'][end($p['ids'])]->prereq_alert;
    }

    public function testParsePrereqLiteralsAndASingleItemReference(): void
    {
        $p = $this->prereqScene(['completed', 'incomplete', 'passed', 'not attempted']);
        [$done, $open, $passed] = $p['ids'];

        $this->assertTrue($this->check($p, '_true_'));
        $this->assertFalse($this->check($p, '_false_'));
        $this->assertNotSame('', $this->alert($p));

        $this->assertTrue($this->check($p, (string) $done));
        $this->assertSame('', $this->alert($p));
        $this->assertTrue($this->check($p, (string) $passed));

        $this->assertFalse($this->check($p, (string) $open));
        $this->assertStringContainsString('li_p1', $this->alert($p), 'the alert names the blocking item');

        $this->assertFalse($this->check($p, '987654'), 'a reference to an unknown item blocks');
    }

    public function testParsePrereqOfAnItemCountsOnlyCompletedAndPassedNotFailedOrBrowsed(): void
    {
        $p = $this->prereqScene(['failed', 'browsed', 'completed']);

        $this->assertFalse($this->check($p, (string) $p['ids'][0]));
        $this->assertFalse($this->check($p, (string) $p['ids'][1]));
    }

    public function testParsePrereqAndNeedsEveryConditionAndStopsAtTheFirstFailure(): void
    {
        $p = $this->prereqScene(['completed', 'passed', 'incomplete', 'not attempted']);
        [$a, $b, $c] = $p['ids'];

        $this->assertTrue($this->check($p, $a.'&'.$b));
        $this->assertFalse($this->check($p, $a.'&'.$c));
        $this->assertFalse($this->check($p, $c.'&'.$a));
        $this->assertNotSame('', $this->alert($p));
    }

    public function testParsePrereqOrNeedsOneConditionAndStopsAtTheFirstTrueOne(): void
    {
        $p = $this->prereqScene(['completed', 'incomplete', 'passed', 'not attempted']);
        [$done, $open, $passed] = $p['ids'];

        $this->assertTrue($this->check($p, $done.'|'.$open));
        $this->assertTrue($this->check($p, $open.'|'.$done));
        $this->assertTrue($this->check($p, $open.'|'.$open.'|'.$passed));
        $this->assertFalse($this->check($p, $open.'|'.$open));
        $this->assertNotSame('', $this->alert($p));
        $this->assertTrue($this->check($p, $open.'|'.$done.'&'.$passed), 'OR has the lowest priority: A | (B & C)');
    }

    public function testParsePrereqEqualityAndInequalityCompareTheDatabaseStatusLiterally(): void
    {
        $p = $this->prereqScene(['incomplete', 'passed', 'not attempted']);
        [$open, $passed] = $p['ids'];

        $this->assertTrue($this->check($p, $open.'=incomplete'));
        $this->assertFalse($this->check($p, $open.'=completed'));
        $this->assertTrue($this->check($p, $passed.'=passed'));
        $this->assertTrue($this->check($p, $open.'<>completed'));
        $this->assertFalse($this->check($p, $open.'<>incomplete'));
        $this->assertFalse(@$this->check($p, '987654=completed'), 'unknown item');
    }

    public function testParsePrereqNegationInvertsANumericItemReference(): void
    {
        $p = $this->prereqScene(['completed', 'incomplete', 'not attempted']);
        [$done, $open] = $p['ids'];

        $this->assertFalse($this->check($p, '~'.$done));
        $this->assertTrue($this->check($p, '~'.$open));
    }

    public function testParsePrereqGroupsNeedAllOrTheMultiplierMinimumOfTheirMembers(): void
    {
        $p = $this->prereqScene(['completed', 'passed', 'incomplete', 'not attempted']);
        [$a, $b, $c] = $p['ids'];

        $this->assertTrue($this->check($p, '{'.$a.','.$b.'}'));
        $this->assertFalse($this->check($p, '{'.$a.','.$c.'}'));
        $this->assertFalse(@$this->check($p, '{'.$a.',987654}'), 'an unknown member blocks a plain group');
        $this->assertTrue($this->check($p, '2*{'.$a.','.$b.','.$c.'}'));
        $this->assertFalse($this->check($p, '3*{'.$a.','.$b.','.$c.'}'));
        $this->assertTrue($this->check($p, '1*{'.$c.','.$a.'}'));
        $this->assertFalse(@$this->check($p, '1*{'.$c.',987654}'), 'an unknown member does not count');
    }

    public function testParsePrereqEvaluatesParenthesesInnermostFirst(): void
    {
        $p = $this->prereqScene(['completed', 'passed', 'incomplete', 'not attempted']);
        [$a, $b, $c] = $p['ids'];

        $this->assertTrue($this->check($p, '('.$a.'&'.$b.')'));
        $this->assertFalse($this->check($p, '('.$a.'&'.$c.')'));
        $this->assertTrue($this->check($p, '(('.$a.'&'.$b.')&'.$b.')'));
        $this->assertFalse($this->check($p, '('.$a.'&'.$c.')&'.$b));
        $this->assertTrue($this->check($p, '('.$c.'|'.$a.')'));
    }

    public function testParsePrereqOfAQuizUsesTheLatestFinishedResultAndTheMasteryScore(): void
    {
        $quiz = $this->newQuiz();
        $p = $this->prereqScene(['completed', 'not attempted'], 'quiz', $quiz);
        [$q, $self] = $p['ids'];
        $this->conn()->executeStatement('UPDATE c_lp_item SET mastery_score = 5 WHERE iid = ?', [$q]);
        $p = $this->prereqItems($p['lp'], $p['view'], $p['ids'], $p['iv']);

        // No result at all although the item is marked completed.
        $this->assertFalse($this->check($p, (string) $q));
        $this->assertNotSame('', $this->alert($p));

        $this->newExe($p['lp'], $q, $quiz, 3, '2030-01-01 10:00:00');
        $this->assertFalse($this->check($p, (string) $q), 'score 3 < mastery 5');
        $this->assertStringContainsString('li_p0', $this->alert($p));

        $this->newExe($p['lp'], $q, $quiz, 6, '2030-01-02 10:00:00');
        $this->assertTrue($this->check($p, (string) $q), 'the latest result (6) counts');

        $this->newExe($p['lp'], $q, $quiz, 1, '2030-01-03 10:00:00', 'incomplete');
        $this->assertTrue($this->check($p, (string) $q), 'an incomplete attempt is ignored');

        $this->newExe($p['lp'], $q, $quiz, 2, '2030-01-04 10:00:00');
        $this->assertFalse($this->check($p, (string) $q), 'a newer finished result of 2 replaces the previous one');
        $this->assertNotSame($self, $q);
    }

    public function testParsePrereqOfAQuizUsesTheMinAndMaxOfTheCheckingItemWhenSet(): void
    {
        $quiz = $this->newQuiz();
        $p = $this->prereqScene(['completed', 'not attempted'], 'quiz', $quiz);
        [$q, $self] = $p['ids'];
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite_min_score = 2, prerequisite_max_score = 4 WHERE iid = ?', [$self]);
        $p = $this->prereqItems($p['lp'], $p['view'], $p['ids'], $p['iv']);

        $this->newExe($p['lp'], $q, $quiz, 3, '2030-01-01 10:00:00');
        $this->assertTrue($this->check($p, (string) $q), '2 <= 3 <= 4');

        $this->newExe($p['lp'], $q, $quiz, 5, '2030-01-02 10:00:00');
        $this->assertFalse($this->check($p, (string) $q), '5 is above the maximum');
        $this->assertStringContainsString('li_p0', $this->alert($p));

        // Both bounds are inclusive.
        $this->newExe($p['lp'], $q, $quiz, 2, '2030-01-03 10:00:00');
        $this->assertTrue($this->check($p, (string) $q), 'score = minimum');
        $this->newExe($p['lp'], $q, $quiz, 4, '2030-01-04 10:00:00');
        $this->assertTrue($this->check($p, (string) $q), 'score = maximum');
    }

    public function testParsePrereqOfAQuizWithSeveralAttemptsAcceptsAnyAttemptInRangeEvenIfTheStatusIsNotFinished(): void
    {
        $quiz = $this->newQuiz();
        $p = $this->prereqScene(['incomplete', 'not attempted'], 'quiz', $quiz);
        [$q, $self] = $p['ids'];
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite_min_score = 5, prerequisite_max_score = 10 WHERE iid = ?', [$self]);
        $p = $this->prereqItems($p['lp'], $p['view'], $p['ids'], $p['iv']);
        $p['items'][$self]->set_prevent_reinit(0);

        $this->assertFalse($this->check($p, (string) $q), 'no result yet');
        $this->newExe($p['lp'], $q, $quiz, 1, '2030-01-01 10:00:00');
        $this->assertFalse($this->check($p, (string) $q), 'only an attempt below the minimum');
        $this->newExe($p['lp'], $q, $quiz, 12, '2030-01-01 11:00:00');
        $this->assertFalse($this->check($p, (string) $q), 'an attempt above the maximum does not count either');
        $this->newExe($p['lp'], $q, $quiz, 8, '2030-01-02 10:00:00');
        $this->newExe($p['lp'], $q, $quiz, 2, '2030-01-03 10:00:00');
        $this->assertTrue($this->check($p, (string) $q), 'one attempt in range is enough, whatever the later ones and the item status');
    }

    public function testParsePrereqOfAQuizWithSeveralAttemptsCanBeLimitedToTheLastAttempt(): void
    {
        $quiz = $this->newQuiz();
        $p = $this->prereqScene(['completed', 'not attempted'], 'quiz', $quiz);
        [$q, $self] = $p['ids'];
        $this->conn()->executeStatement('UPDATE c_lp_item SET prerequisite_min_score = 5, prerequisite_max_score = 10 WHERE iid = ?', [$self]);
        $p = $this->prereqItems($p['lp'], $p['view'], $p['ids'], $p['iv']);
        $p['items'][$self]->set_prevent_reinit(0);
        $this->newExe($p['lp'], $q, $quiz, 8, '2030-01-01 10:00:00');
        $this->newExe($p['lp'], $q, $quiz, 2, '2030-01-02 10:00:00');

        $this->assertTrue($this->check($p, (string) $q));
        $this->setPrerequisiteSetting('lp_prerequisite_use_last_attempt_only', 'true');
        $this->assertFalse($this->check($p, (string) $q), 'only the newest attempt (2) is looked at');
    }

    public function testParsePrereqOfAQuizIsUnblockedOnceTheMaximumNumberOfAttemptsIsReached(): void
    {
        $quiz = $this->newQuiz(2);
        $p = $this->prereqScene(['completed', 'not attempted'], 'quiz', $quiz);
        [$q] = $p['ids'];
        $this->conn()->executeStatement('UPDATE c_lp_item SET mastery_score = 9 WHERE iid = ?', [$q]);
        $p = $this->prereqItems($p['lp'], $p['view'], $p['ids'], $p['iv']);
        $this->newExe($p['lp'], $q, $quiz, 1, '2030-01-01 10:00:00');

        $this->setPrerequisiteSetting('lp_prerequisite_on_quiz_unblock_if_max_attempt_reached', 'true');
        $this->assertFalse($this->check($p, (string) $q), 'one attempt out of 2, still below mastery');

        $this->newExe($p['lp'], $q, $quiz, 2, '2030-01-02 10:00:00');
        $this->assertTrue($this->check($p, (string) $q), 'two attempts out of 2: unblocked');
        $this->assertSame('', $this->alert($p));

        $this->setPrerequisiteSetting('lp_prerequisite_on_quiz_unblock_if_max_attempt_reached', 'false');
        $this->assertFalse($this->check($p, (string) $q), 'the setting is off');
    }

    public function testGetStatusFromOtherSessionsLooksAtTheSameLpInEveryRealSessionOfTheUser(): void
    {
        $p = $this->prereqScene(['not attempted', 'not attempted']);
        [$a] = $p['ids'];
        $item = $p['items'][$p['ids'][1]];

        // PINNED QUIRK: with the platform setting off the method has no return statement and yields null.
        $this->assertNull($item->getStatusFromOtherSessions(self::USER_ID, (string) $a, $p['refs']));

        $this->setPrerequisiteSetting('validate_lp_prerequisite_from_other_session', 'true');
        $this->assertFalse($item->getStatusFromOtherSessions(self::USER_ID, (string) $a, $p['refs']), 'no view in a session');

        $sessionId = $this->newSessionRow('li_session');
        $sessionView = $this->newView($p['lp'], self::USER_ID, $sessionId);
        $sessionIv = $this->newItemView($sessionView, $a, 1, ['status' => 'incomplete']);
        $this->assertFalse($item->getStatusFromOtherSessions(self::USER_ID, (string) $a, $p['refs']));

        $this->conn()->executeStatement("UPDATE c_lp_item_view SET status = 'passed' WHERE iid = ?", [$sessionIv]);
        $this->assertTrue($item->getStatusFromOtherSessions(self::USER_ID, (string) $a, $p['refs']));
        $this->assertFalse($item->getStatusFromOtherSessions(3, (string) $a, $p['refs']), 'another user');
    }

    public function testParsePrereqCanBeSatisfiedByAnotherSessionWhenThePlatformAllowsIt(): void
    {
        $p = $this->prereqScene(['incomplete', 'not attempted']);
        [$a] = $p['ids'];
        $sessionId = $this->newSessionRow('li_session2');
        $sessionView = $this->newView($p['lp'], self::USER_ID, $sessionId);
        $this->newItemView($sessionView, $a, 1, ['status' => 'completed']);

        $this->assertFalse($this->check($p, (string) $a));
        $this->setPrerequisiteSetting('validate_lp_prerequisite_from_other_session', 'true');
        $this->assertTrue($this->check($p, (string) $a));
    }

    private function newExe(int $lp, int $lpItem, int $quizId, float $score, string $date, string $status = 'completed'): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, exe_date, score, max_score, user_ip, status, data_tracking, start_date,
                steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check, exe_exo_id)
             VALUES (?, 1, ?, ?, 10, '127.0.0.1', ?, '', ?, 1, ?, ?, 0, 0, '', ?)",
            [self::USER_ID, $date, $score, $status, $date, $lp, $lpItem, $quizId]
        );
    }

    private function newSessionRow(string $title): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            [$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testGetTitleToDisplayReturnsTheTitleOfAnItemWithoutAiMarker(): void
    {
        $s = $this->scene();

        $this->assertSame('li_item_document', $this->item($s['item'])->get_title_to_display());
    }
}
