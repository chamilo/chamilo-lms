<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Event;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Second characterization pass over public/main/inc/lib/events.lib.php: the branches the first pass
 * (EventCharacterizationTest) never reached - the learning path view tree removal and the minimum-time log
 * clean-up of delete_student_lp_events(), get_all_exercises_from_lp(), and the track_e_access_complete clean-up
 * of eventRemoveVirtualCourseTime().
 */
final class EventTier2CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/events.lib.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        unset($_SERVER['HTTP_REFERER']);
        ChamiloSession::erase('login_as');
        $_SERVER['HTTP_USER_AGENT'] = 'ev2_agent';
        $_SERVER['REQUEST_URI'] = '/ev2_uri';
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('login_as');
        parent::tearDown();
    }

    private function setContext(User $user, Course $course, int $sessionId = 0): void
    {
        ChamiloSession::write('_user', ['id' => $user->getId()]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::erase('login_as');
    }

    private function enableMinimumTime(): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting('lp.lp_minimum_time', 'true');
        $this->getEntityManager()->clear();
    }

    private function enableNewTrackingSystem(int $courseId): void
    {
        $this->enableMinimumTime();
        $fieldId = $this->conn->fetchOne("SELECT id FROM extra_field WHERE variable = 'new_tracking_system' AND item_type = 2");
        if (false === $fieldId) {
            $this->conn->executeStatement(
                'INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self,
                    visible_to_others, changeable, filter, created_at, auto_remove)
                 VALUES (2, 1, \'new_tracking_system\', \'ev2\', 0, 1, 1, 1, 0, \'2020-01-01 00:00:00\', 0)'
            );
            $fieldId = (int) $this->conn->lastInsertId();
        }
        $this->conn->executeStatement('DELETE FROM extra_field_values WHERE field_id = ? AND item_id = ?', [$fieldId, $courseId]);
        $this->conn->executeStatement(
            'INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at)
             VALUES (?, \'1\', ?, \'2020-01-01 00:00:00\', \'2020-01-01 00:00:00\')',
            [$fieldId, $courseId]
        );
    }

    private function rows(string $table, string $where, array $params = [], string $order = ''): array
    {
        return $this->conn->fetchAllAssociative(
            "SELECT * FROM $table WHERE $where".('' !== $order ? " ORDER BY $order" : ''),
            $params
        );
    }

    private function makeLp(int $courseId): int
    {
        $this->conn->insert('c_lp', [
            'lp_type' => 1,
            'title' => 'ev2_lp_'.(++$this->seq),
            'path' => '',
            'force_commit' => 0,
            'content_maker' => '',
            'content_license' => '',
            'js_lib' => '',
            'debug' => 0,
            'theme' => '',
            'author' => '',
            'prerequisite' => 0,
            'hide_toc_frame' => 0,
            'seriousgame_mode' => 0,
            'autolaunch' => 0,
            'max_attempts' => 0,
            'subscribe_users' => 0,
            'created_on' => '2020-01-01 00:00:00',
            'modified_on' => '2020-01-01 00:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function makeLpItem(int $lpId, string $type, int $order, ?int $parent = null, string $title = ''): int
    {
        $this->conn->insert('c_lp_item', [
            'lp_id' => $lpId,
            'title' => '' !== $title ? $title : 'ev2_item_'.(++$this->seq),
            'item_type' => $type,
            'ref' => '12',
            'path' => '34',
            'min_score' => 0,
            'max_score' => 100,
            'display_order' => $order,
            'launch_data' => '',
            'lvl' => 0,
            'parent_item_id' => $parent,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function makeLpView(int $uid, int $lpId, int $cid, ?int $sid): int
    {
        $this->conn->insert('c_lp_view', [
            'user_id' => $uid,
            'lp_id' => $lpId,
            'c_id' => $cid,
            'session_id' => $sid,
            'view_count' => 1,
            'last_item' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * An item view, one interaction and one objective hanging off the given lp view.
     */
    private function hangOffView(int $viewId, int $itemId, int $cid): void
    {
        $this->conn->insert('c_lp_item_view', [
            'lp_item_id' => $itemId,
            'lp_view_id' => $viewId,
            'view_count' => 1,
            'start_time' => 0,
            'total_time' => 0,
            'score' => 0,
        ]);
        $this->conn->insert('c_lp_iv_interaction', [
            'c_id' => $cid,
            'order_id' => 0,
            'lp_iv_id' => $viewId,
            'interaction_id' => 'i',
            'interaction_type' => 't',
            'weighting' => 0,
            'completion_time' => '',
            'correct_responses' => '',
            'student_response' => '',
            'result' => '',
            'latency' => '',
        ]);
        $this->conn->insert('c_lp_iv_objective', [
            'c_id' => $cid,
            'lp_iv_id' => $viewId,
            'order_id' => 0,
            'objective_id' => 'o',
            'score_raw' => 0,
            'score_max' => 0,
            'score_min' => 0,
            'status' => 's',
        ]);
    }

    private function seedAccessComplete(int $uid, int $cid, int $sid, string $tool, int $toolId, string $action = 'a', string $details = ''): int
    {
        $this->conn->insert('track_e_access_complete', [
            'user_id' => $uid,
            'date_reg' => '2020-01-01 10:00:00',
            'tool' => $tool,
            'tool_id' => $toolId,
            'tool_id_detail' => 0,
            'action' => $action,
            'action_details' => $details,
            'current_id' => 1,
            'ip_user' => '10.0.0.1',
            'user_agent' => 'ua',
            'session_id' => $sid,
            'c_id' => $cid,
            'ch_sid' => 'sid',
            'login_as' => 0,
            'info' => '',
            'url' => '/u',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function countRows(string $table, string $where, array $params): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    // ---------------------------------------------------------------- tests

    public function testDeleteStudentLpEventsRemovesTheViewTreeOfTheMatchingSessionOnly(): void
    {
        $user = $this->getUser('admin');
        $other = $this->getUser('anon');
        $course = $this->getCourse(1);
        $this->setContext($user, $course);
        $cid = $course->getId();
        $uid = $user->getId();
        $sid = (int) $this->createSession('ev2_s_dlp')->getId();
        $otherSid = (int) $this->createSession('ev2_s_dlp_other')->getId();

        $lp = $this->makeLp($cid);
        $otherLp = $this->makeLp($cid);
        $item = $this->makeLpItem($lp, 'document', 1);
        $otherItem = $this->makeLpItem($otherLp, 'document', 1);

        $view = $this->makeLpView($uid, $lp, $cid, $sid);
        $this->hangOffView($view, $item, $cid);
        $otherSessionView = $this->makeLpView($uid, $lp, $cid, $otherSid);
        $this->hangOffView($otherSessionView, $item, $cid);
        $otherLpView = $this->makeLpView($uid, $otherLp, $cid, $sid);
        $this->hangOffView($otherLpView, $otherItem, $cid);
        $otherUserView = $this->makeLpView($other->getId(), $lp, $cid, $sid);
        $this->hangOffView($otherUserView, $item, $cid);

        $this->assertTrue(Event::delete_student_lp_events($uid, $lp, ['real_id' => $cid], $sid));

        $this->assertCount(0, $this->rows('c_lp_view', 'iid = ?', [$view]));
        $this->assertCount(0, $this->rows('c_lp_item_view', 'lp_view_id = ?', [$view]));
        $this->assertCount(0, $this->rows('c_lp_iv_interaction', 'lp_iv_id = ?', [$view]));
        $this->assertCount(0, $this->rows('c_lp_iv_objective', 'lp_iv_id = ?', [$view]));

        foreach ([$otherSessionView, $otherLpView, $otherUserView] as $kept) {
            $this->assertCount(1, $this->rows('c_lp_view', 'iid = ?', [$kept]), 'view '.$kept.' survives');
            $this->assertCount(1, $this->rows('c_lp_item_view', 'lp_view_id = ?', [$kept]));
            $this->assertCount(1, $this->rows('c_lp_iv_interaction', 'lp_iv_id = ?', [$kept]));
            $this->assertCount(1, $this->rows('c_lp_iv_objective', 'lp_iv_id = ?', [$kept]));
        }
    }

    public function testDeleteStudentLpEventsWithoutViewStillCleansResultsAndLogs(): void
    {
        $user = $this->getUser('admin');
        $course = $this->getCourse(1);
        $this->setContext($user, $course);
        $lp = $this->makeLp($course->getId());

        $this->assertTrue(Event::delete_student_lp_events($user->getId(), $lp, ['real_id' => $course->getId()], 0));
        $this->assertSame(
            1,
            $this->countRows('track_e_default', 'default_event_type = ? AND default_value = ?', [LOG_LP_ATTEMPT_DELETE, (string) $lp])
        );
    }

    public function testDeleteStudentLpEventsViewWithNullSessionIsFoundByTheCleanupButKeptByTheFinalDelete(): void
    {
        $user = $this->getUser('admin');
        $course = $this->getCourse(1);
        $this->setContext($user, $course);
        $cid = $course->getId();
        $lp = $this->makeLp($cid);
        $item = $this->makeLpItem($lp, 'document', 1);
        $view = $this->makeLpView($user->getId(), $lp, $cid, null);
        $this->hangOffView($view, $item, $cid);

        $this->assertTrue(Event::delete_student_lp_events($user->getId(), $lp, ['real_id' => $cid], 0));

        // Session 0 reaches the NULL-session view (the lookup accepts NULL) so its tree is emptied ...
        $this->assertCount(0, $this->rows('c_lp_item_view', 'lp_view_id = ?', [$view]));
        $this->assertCount(0, $this->rows('c_lp_iv_interaction', 'lp_iv_id = ?', [$view]));
        $this->assertCount(0, $this->rows('c_lp_iv_objective', 'lp_iv_id = ?', [$view]));
        // ... but the closing DELETE only matches session_id = 0 literally, so the view row itself stays.
        $this->assertCount(1, $this->rows('c_lp_view', 'iid = ?', [$view]));
    }

    public function testDeleteStudentLpEventsRemovesMinimumTimeLogsOfThatLpUserAndSessionOnly(): void
    {
        $user = $this->getUser('admin');
        $other = $this->getUser('anon');
        $course = $this->getCourse(1);
        $this->setContext($user, $course);
        $cid = $course->getId();
        $uid = $user->getId();
        $sid = (int) $this->createSession('ev2_s_min')->getId();
        $lp = $this->makeLp($cid);

        $mine = $this->seedAccessComplete($uid, $cid, $sid, 'learnpath', $lp);
        $otherSession = $this->seedAccessComplete($uid, $cid, 0, 'learnpath', $lp);
        $otherLp = $this->seedAccessComplete($uid, $cid, $sid, 'learnpath', $lp + 1);
        $otherUser = $this->seedAccessComplete($other->getId(), $cid, $sid, 'learnpath', $lp);
        $otherTool = $this->seedAccessComplete($uid, $cid, $sid, 'document', $lp);

        // Setting off: the logs are not touched.
        $this->assertTrue(Event::delete_student_lp_events($uid, $lp, ['real_id' => $cid], $sid));
        $this->assertCount(1, $this->rows('track_e_access_complete', 'id = ?', [$mine]));

        $this->enableMinimumTime();
        $this->assertTrue(Event::delete_student_lp_events($uid, $lp, ['real_id' => $cid], $sid));
        $this->assertCount(0, $this->rows('track_e_access_complete', 'id = ?', [$mine]));
        foreach ([$otherSession, $otherLp, $otherUser, $otherTool] as $kept) {
            $this->assertCount(1, $this->rows('track_e_access_complete', 'id = ?', [$kept]), 'log '.$kept.' survives');
        }
    }

    public function testGetAllExercisesFromLpReturnsQuizItemsOrderedByParentThenPosition(): void
    {
        $course = $this->getCourse(1);
        $lp = $this->makeLp($course->getId());
        $otherLp = $this->makeLp($course->getId());

        $folderA = $this->makeLpItem($lp, 'dir', 1, null, 'ev2 folder a');
        $folderB = $this->makeLpItem($lp, 'dir', 2, null, 'ev2 folder b');
        $rootLate = $this->makeLpItem($lp, 'quiz', 5, null, 'ev2 root late');
        $rootEarly = $this->makeLpItem($lp, 'quiz', 1, null, 'ev2 root early');
        $inB = $this->makeLpItem($lp, 'quiz', 1, $folderB, 'ev2 in b');
        $inA2 = $this->makeLpItem($lp, 'quiz', 2, $folderA, 'ev2 in a 2');
        $inA1 = $this->makeLpItem($lp, 'quiz', 1, $folderA, 'ev2 in a 1');
        $this->makeLpItem($lp, 'document', 3, $folderA, 'ev2 not a quiz');
        $this->makeLpItem($otherLp, 'quiz', 1, null, 'ev2 other lp');

        $list = Event::get_all_exercises_from_lp($lp);

        // NULL parents sort first, then by parent id, then by display order.
        $this->assertSame(
            [$rootEarly, $rootLate, $inA1, $inA2, $inB],
            array_map(static fn (array $row): int => (int) $row['iid'], $list)
        );
        $this->assertSame([0, 1, 2, 3, 4], array_keys($list));

        $first = $list[0];
        $this->assertSame(
            [
                'iid', 'lp_id', 'item_root', 'parent_item_id', 'title', 'item_type', 'ref', 'description', 'path',
                'min_score', 'max_score', 'mastery_score', 'display_order', 'prerequisite', 'parameters',
                'launch_data', 'max_time_allowed', 'terms', 'search_did', 'audio', 'prerequisite_min_score',
                'prerequisite_max_score', 'previous_item_id', 'next_item_id', 'lvl', 'duration', 'export_allowed',
            ],
            array_keys($first)
        );
        $this->assertSame($rootEarly, $first['iid']);
        $this->assertSame($lp, $first['lp_id']);
        $this->assertNull($first['parent_item_id']);
        $this->assertSame('ev2 root early', $first['title']);
        $this->assertSame('quiz', $first['item_type']);
        $this->assertSame('12', $first['ref']);
        $this->assertSame('34', $first['path']);
        $this->assertEquals(0, $first['min_score']);
        $this->assertEquals(100, $first['max_score']);
        $this->assertSame(1, $first['display_order']);
        $this->assertSame(0, $first['lvl']);
        $this->assertSame($folderA, $list[2]['parent_item_id']);

        $this->assertSame([], Event::get_all_exercises_from_lp($lp + 100000));
    }

    public function testRemoveVirtualCourseTimeAlsoRemovesTheMinimumTimeWorkLogs(): void
    {
        $user = $this->getUser('admin');
        $other = $this->getUser('anon');
        $course = $this->getCourse(1);
        $this->setContext($user, $course);
        $cid = $course->getId();
        $uid = $user->getId();

        $this->enableNewTrackingSystem($cid);

        // (Event::registerLog() is not used to create these: it stores '' in the integer login_as column.)
        foreach ([77, 78] as $work) {
            $this->seedAccessComplete($uid, $cid, 0, TOOL_STUDENTPUBLICATION, 0, 'add_work_start_'.$work, '01:30:00');
            $this->seedAccessComplete($uid, $cid, 0, TOOL_STUDENTPUBLICATION, 0, 'add_work_end_'.$work, '01:30:00');
        }

        // Same duration but another user's or another duration's logs must stay.
        $foreign = $this->seedAccessComplete($other->getId(), $cid, 0, TOOL_STUDENTPUBLICATION, 0, 'add_work_start_77', '01:30:00');
        $otherDuration = $this->seedAccessComplete($uid, $cid, 0, TOOL_STUDENTPUBLICATION, 0, 'add_work_start_77', '02:00:00');

        $this->assertTrue(Event::eventRemoveVirtualCourseTime($cid, $uid, 0, '01:30:00', 77));

        $left = array_column(
            $this->rows('track_e_access_complete', 'user_id = ? AND tool = ?', [$uid, TOOL_STUDENTPUBLICATION], 'id'),
            'action'
        );
        $this->assertSame(['add_work_start_78', 'add_work_end_78', 'add_work_start_77'], $left, 'only work 77 start/end of that duration are removed');
        $this->assertCount(1, $this->rows('track_e_access_complete', 'id = ?', [$foreign]));
        $this->assertCount(1, $this->rows('track_e_access_complete', 'id = ?', [$otherDuration]));
    }

    public function testRemoveVirtualCourseTimeReportsRemovalOfTheEndLogEvenWithoutConnectionRows(): void
    {
        $user = $this->getUser('admin');
        $course = $this->getCourse(1);
        $this->setContext($user, $course);
        $cid = $course->getId();
        $uid = $user->getId();

        $this->enableNewTrackingSystem($cid);

        // Only a start log: no connection rows and no end log, so nothing counts as removed (the start log is
        // deleted but does not flip the return value).
        $start = $this->seedAccessComplete($uid, $cid, 0, TOOL_STUDENTPUBLICATION, 0, 'add_work_start_5', '00:10:00');
        $this->assertFalse(Event::eventRemoveVirtualCourseTime($cid, $uid, 0, '00:10:00', 5));
        $this->assertCount(0, $this->rows('track_e_access_complete', 'id = ?', [$start]));

        $end = $this->seedAccessComplete($uid, $cid, 0, TOOL_STUDENTPUBLICATION, 0, 'add_work_end_5', '00:10:00');
        $this->assertTrue(Event::eventRemoveVirtualCourseTime($cid, $uid, 0, '00:10:00', 5));
        $this->assertCount(0, $this->rows('track_e_access_complete', 'id = ?', [$end]));
    }
}
