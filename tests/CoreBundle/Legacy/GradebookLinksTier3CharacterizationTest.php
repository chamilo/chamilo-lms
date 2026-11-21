<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use AttendanceLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use ForumParticipationLink;
use ForumThreadLink;
use LearnpathLink;
use Doctrine\DBAL\Connection;
use ExerciseLink;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization of the gradebook link classes under public/main/gradebook/lib/be/ (attendance, forum thread,
 * exercise ...) before their Database::query() calls move to DQL.
 */
final class GradebookLinksTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    /** @var int[] */
    private array $users = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/gradebook/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        $_GET = [];
        $_POST = [];
        ChamiloSession::write('_user', ['id' => 1, 'user_id' => 1]);
        ChamiloSession::write('_real_cid', 1);
        ChamiloSession::write('_cid', 'AIACT');
        ChamiloSession::write('_course', api_get_course_info('AIACT'));
        ChamiloSession::write('sid', 0);
        ChamiloSession::write('gid', 0);
        ChamiloSession::erase('studentview');
        Container::getEntityManager()->clear();
        $user = $this->getUser('admin');
        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
        $this->users = array_map('intval', $this->conn->fetchFirstColumn('SELECT id FROM user ORDER BY id LIMIT 4'));
    }

    protected function tearDown(): void
    {
        foreach (['_user', 'sid', '_real_cid', '_cid', '_course'] as $key) {
            ChamiloSession::erase($key);
        }
        $_GET = [];
        $_POST = [];
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ------------------------------------------------------------ attendance

    private function attendance(array $o = []): int
    {
        $this->conn->insert('c_attendance', $o + [
            'title' => 'gl_att_'.(++$this->seq),
            'description' => '',
            'active' => 1,
            'attendance_qualify_title' => null,
            'attendance_qualify_max' => 0,
            'attendance_weight' => 0,
            'locked' => 0,
            'require_unique' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function attendanceDate(int $attendanceId): int
    {
        $this->conn->insert('c_attendance_calendar', [
            'attendance_id' => $attendanceId,
            'date_time' => '2020-01-0'.(1 + $this->seq++ % 9).' 10:00:00',
            'done_attendance' => 1,
            'blocked' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function sheet(int $calendarId, int $userId, ?int $presence): void
    {
        $this->conn->insert('c_attendance_sheet', [
            'attendance_calendar_id' => $calendarId,
            'user_id' => $userId,
            'presence' => $presence,
        ]);
    }

    private function attResult(int $attendanceId, int $userId, int $score): void
    {
        $this->conn->insert('c_attendance_result', [
            'attendance_id' => $attendanceId,
            'user_id' => $userId,
            'score' => $score,
        ]);
    }

    private function attLink(int $attendanceId): AttendanceLink
    {
        $link = new AttendanceLink();
        $link->set_ref_id($attendanceId);

        return $link;
    }

    public function testAttendanceHasResultsIsValidLinkGetLinkAndName(): void
    {
        $att = $this->attendance(['attendance_qualify_title' => 'Qualify title']);
        $link = $this->attLink($att);
        $this->assertFalse($link->has_results());
        $this->attResult($att, $this->users[1], 5);
        $this->assertTrue($link->has_results());
        $this->assertFalse($this->attLink($this->attendance())->has_results());

        $this->assertTrue($link->is_valid_link());
        $this->assertFalse($this->attLink(999999999)->is_valid_link());

        $this->assertStringContainsString('attendance_id='.$att.'&', $link->get_link());
        $this->assertStringContainsString('attendance/index.php?action=attendance_sheet_list', $link->get_link());

        $this->assertSame('Qualify title', $link->get_name());
        // The legacy code reads a "name" column that does not exist: without a qualify title the name is ''.
        $this->assertSame('', $this->attLink($this->attendance())->get_name());
        $this->assertSame('', $this->attLink(999999999)->get_name());
    }

    public function testAttendanceCalcScoreFromResultsTable(): void
    {
        [$a, $b, $c] = $this->users;
        $att = $this->attendance(['attendance_qualify_max' => 10]);
        $this->attResult($att, $a, 10);
        $this->attResult($att, $b, 5);
        $other = $this->attendance(['attendance_qualify_max' => 10]);
        $this->attResult($other, $c, 9);
        $link = $this->attLink($att);

        $this->assertSame([10.0, 10.0], $link->calc_score($a));
        $this->assertSame([5.0, 10.0], $link->calc_score($b));
        $this->assertSame([0.0, 10.0], $link->calc_score($c));
        $this->assertSame([1.0, 1], $link->calc_score($a, 'default'));
        $this->assertSame([0.5, 1], $link->calc_score($b, 'default'));
        $this->assertSame([0.0, 1], $link->calc_score($c, 'default'));

        $this->assertSame([10.0, 10.0], $link->calc_score(null, 'best'));
        $this->assertSame([7.5, 10.0], $link->calc_score(null, 'average'));
        $this->assertSame([1.5, 2], $link->calc_score(null));
        $this->assertSame([null, null], $this->attLink($this->attendance(['attendance_qualify_max' => 10]))->calc_score());
    }

    public function testAttendanceCalcScoreMaxFallsBackToNumberOfDates(): void
    {
        [$a] = $this->users;
        $att = $this->attendance(['attendance_qualify_max' => 0]);
        $this->attendanceDate($att);
        $this->attendanceDate($att);
        $this->attendanceDate($this->attendance());
        $this->attResult($att, $a, 1);

        $this->assertSame([1.0, 2.0], $this->attLink($att)->calc_score($a));
        $this->assertSame([0.5, 1], $this->attLink($att)->calc_score($a, 'default'));
        // No dates, no max: no division by zero, nothing counted.
        $empty = $this->attendance(['attendance_qualify_max' => 0]);
        $this->attResult($empty, $a, 3);
        $this->assertSame([null, null], $this->attLink($empty)->calc_score());
        $this->assertSame([0.0, 0.0], $this->attLink($empty)->calc_score($this->users[1]));
    }

    public function testAttendanceCalcScoreRequireUniqueUsesSheets(): void
    {
        [$a, $b, $c] = $this->users;
        $att = $this->attendance(['attendance_qualify_max' => 0, 'require_unique' => 1]);
        $d1 = $this->attendanceDate($att);
        $d2 = $this->attendanceDate($att);
        $foreign = $this->attendanceDate($this->attendance(['require_unique' => 1]));
        // a: late once (3) -> present; b: absent only (0 and null); c: only on a foreign attendance.
        $this->sheet($d1, $a, 0);
        $this->sheet($d2, $a, 3);
        $this->sheet($d1, $b, 0);
        $this->sheet($d2, $b, null);
        $this->sheet($foreign, $c, 1);
        $link = $this->attLink($att);

        // qualify max 0 in unique mode becomes 100.
        $this->assertSame([100.0, 100.0], $link->calc_score($a));
        $this->assertSame([0.0, 100.0], $link->calc_score($b));
        $this->assertSame([0.0, 100.0], $link->calc_score($c));
        $this->assertSame([1.0, 1], $link->calc_score($a, 'default'));
        $this->assertSame([0.0, 1], $link->calc_score($b, 'default'));

        // users a and b count (c has no sheet on this attendance): only a is present.
        $this->assertSame([100.0, 100.0], $link->calc_score(null, 'best'));
        $this->assertEqualsWithDelta(50.0, $link->calc_score(null, 'average')[0], 0.0001);
        $res = $link->calc_score(null);
        $this->assertEqualsWithDelta(1.0, $res[0], 0.0001);
        $this->assertSame(2, $res[1]);
        $this->assertSame([null, null], $this->attLink($this->attendance(['require_unique' => 1]))->calc_score());
    }

    // ---------------------------------------------------------- forum thread

    private function forum(): int
    {
        $this->conn->insert('c_forum_forum', ['title' => 'gl_forum_'.(++$this->seq), 'locked' => 0, 'forum_image' => '']);

        return (int) $this->conn->lastInsertId();
    }

    private function thread(array $o = []): int
    {
        $this->conn->insert('c_forum_thread', $o + [
            'title' => 'gl_thread_'.(++$this->seq),
            'thread_date' => '2020-01-01 00:00:00',
            'thread_sticky' => 0,
            'locked' => 0,
            'thread_title_qualify' => null,
            'thread_qualify_max' => 10,
            'thread_weight' => 0,
            'thread_peer_qualify' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * The qualify table is read by ITS OWN iid (not by thread_id): a quirk the migration must keep.
     */
    private function qualify(int $iid, int $userId, float $qualify, string $time, ?int $threadId = null): void
    {
        $this->conn->insert('c_forum_thread_qualify', [
            'iid' => $iid,
            'user_id' => $userId,
            'thread_id' => $threadId,
            'qualify_user_id' => 1,
            'c_id' => 1,
            'qualify' => $qualify,
            'qualify_time' => $time,
        ]);
    }

    private function nextQualifyIid(): int
    {
        return 1 + (int) $this->conn->fetchOne('SELECT COALESCE(MAX(iid), 0) FROM c_forum_thread_qualify');
    }

    private function threadLink(int $threadId): ForumThreadLink
    {
        $link = new ForumThreadLink();
        $link->set_ref_id($threadId);

        return $link;
    }

    public function testForumThreadIsValidLinkNameAndLink(): void
    {
        $forum = $this->forum();
        $plain = $this->thread(['title' => 'Plain thread', 'forum_id' => $forum]);
        $qualified = $this->thread(['title' => 'Plain', 'thread_title_qualify' => 'Qualify name']);
        $this->assertTrue($this->threadLink($plain)->is_valid_link());
        $this->assertFalse($this->threadLink(999999999)->is_valid_link());

        $this->assertSame('Plain thread', $this->threadLink($plain)->get_name());
        $this->assertSame('Qualify name', $this->threadLink($qualified)->get_name());

        $url = $this->threadLink($plain)->get_link();
        $this->assertStringContainsString('/forum/'.$forum.'/thread/'.$plain.'?', $url);
        $this->assertStringContainsString('cid=1&sid=0&gid=0&gradebook=view', $url);
        $this->assertSame('', $this->threadLink(999999999)->get_link());
    }

    public function testForumThreadCalcScoreWithoutRowsAndPeerQualify(): void
    {
        [$a, $b] = $this->users;
        $thread = $this->thread(['thread_qualify_max' => 8]);
        // Nothing in the qualify table has iid = $thread: no row for a student, nothing to average.
        $this->assertSame([0, 8.0], $this->threadLink($thread)->calc_score($a));
        $this->assertSame([null, null], $this->threadLink($thread)->calc_score());

        // Peer qualify averages the rows of the student, but only from 3 rows on (the iid filter allows one).
        $peer = $this->thread(['thread_qualify_max' => 4, 'thread_peer_qualify' => 1]);
        $this->qualify($peer, $a, 3.0, '2020-01-01 10:00:00');
        $this->assertSame([0, 4.0], $this->threadLink($peer)->calc_score($a));
        $this->assertSame([0, 4.0], $this->threadLink($peer)->calc_score($b));
    }

    public function testForumThreadCalcScoreReadsRowsOfItsOwnIid(): void
    {
        [$a, $b] = $this->users;
        $iid = $this->nextQualifyIid() + 500;
        $this->conn->insert('c_forum_thread', [
            'iid' => $iid,
            'title' => 'gl_thread_same_iid',
            'thread_date' => '2020-01-01 00:00:00',
            'thread_sticky' => 0,
            'locked' => 0,
            'thread_qualify_max' => 8,
            'thread_weight' => 0,
            'thread_peer_qualify' => 0,
        ]);
        $this->qualify($iid, $a, 6.0, '2020-01-01 10:00:00', $iid);
        $link = $this->threadLink($iid);

        $this->assertSame([6.0, 8.0], $link->calc_score($a));
        $this->assertSame([0, 8.0], $link->calc_score($b));
        $this->assertSame([0.75, 1], $link->calc_score());
        $this->assertSame([6.0, 8.0], $link->calc_score(null, 'best'));
        $this->assertSame([6.0, 8.0], $link->calc_score(null, 'average'));
    }

    public function testForumThreadCalcScoreSaveAndDeleteLinkedData(): void
    {
        $thread = $this->thread(['thread_qualify_max' => 8, 'thread_weight' => 2, 'thread_title_qualify' => 'T']);
        $other = $this->thread(['thread_qualify_max' => 5, 'thread_weight' => 3, 'thread_title_qualify' => 'O']);
        $link = $this->threadLink($thread);
        $link->set_weight(6.25);
        $link->save_linked_data();
        $row = $this->conn->fetchAssociative('SELECT thread_weight, thread_qualify_max FROM c_forum_thread WHERE iid = ?', [$thread]);
        $this->assertEquals(6.25, $row['thread_weight']);
        $this->assertEquals(8, $row['thread_qualify_max']);
        $this->assertEquals(3, $this->conn->fetchOne('SELECT thread_weight FROM c_forum_thread WHERE iid = ?', [$other]));

        $link->delete_linked_data();
        $row = $this->conn->fetchAssociative('SELECT thread_weight, thread_qualify_max, thread_title_qualify FROM c_forum_thread WHERE iid = ?', [$thread]);
        $this->assertEquals(0, $row['thread_weight']);
        $this->assertEquals(0, $row['thread_qualify_max']);
        $this->assertSame('', $row['thread_title_qualify']);
        $row = $this->conn->fetchAssociative('SELECT thread_weight, thread_qualify_max, thread_title_qualify FROM c_forum_thread WHERE iid = ?', [$other]);
        $this->assertEquals(3, $row['thread_weight']);
        $this->assertEquals(5, $row['thread_qualify_max']);
        $this->assertSame('O', $row['thread_title_qualify']);

        // Without a ref id nothing is written.
        $none = new ForumThreadLink();
        $none->set_ref_id(0);
        $none->delete_linked_data();
        $this->assertEquals(5, $this->conn->fetchOne('SELECT thread_qualify_max FROM c_forum_thread WHERE iid = ?', [$other]));
    }

    // -------------------------------------------------------------- exercise

    private function quiz(array $o = []): int
    {
        $this->conn->insert('c_quiz', $o + [
            'title' => 'gl_quiz_'.(++$this->seq),
            'description' => 'quiz description',
            'type' => 1,
            'random' => 0,
            'random_answers' => 0,
            'results_disabled' => 0,
            'max_attempt' => 0,
            'feedback_type' => 0,
            'expired_time' => 0,
            'propagate_neg' => 0,
            'review_answers' => 0,
            'random_by_category' => 0,
            'display_category_name' => 0,
            'hide_question_title' => 0,
            'page_result_configuration' => '[]',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function exerciseLink(int $quizId, int $courseId = 1): ExerciseLink
    {
        $link = new ExerciseLink();
        $link->set_ref_id($quizId);
        $link->course_id = $courseId;

        return $link;
    }

    private function resetCompletionCache(): void
    {
        $prop = new \ReflectionProperty(ExerciseLink::class, 'completionTrackingIdsByCourse');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testExerciseGetExerciseDataAndNameDescription(): void
    {
        $quiz = $this->quiz(['title' => 'Quiz data title', 'description' => 'Quiz data description']);
        $link = $this->exerciseLink($quiz);
        $data = $link->get_exercise_data();
        $this->assertSame($quiz, (int) $data['iid']);
        $this->assertSame('Quiz data title', $data['title']);
        $this->assertSame('Quiz data description', $data['description']);
        $this->assertSame('Quiz data title', $link->get_name());
        $this->assertSame('Quiz data description', $link->get_description());

        $this->assertFalse($this->exerciseLink(999999999)->get_exercise_data());
    }

    public function testExerciseGetAllLinksAddsTheQuizzesUsedAsLearningPathItems(): void
    {
        $quiz = $this->quiz(['title' => 'gl_quiz_in_lp']);
        $plain = $this->quiz(['title' => 'gl_quiz_not_in_lp']);
        foreach ([(string) $quiz, 'not-a-quiz-id'] as $path) {
            $this->conn->insert('c_lp_item', [
                'title' => 'gl_lp_item', 'item_type' => 'quiz', 'ref' => '', 'path' => $path, 'min_score' => 0,
                'display_order' => 1, 'launch_data' => '', 'lvl' => 0,
            ]);
        }
        $this->conn->insert('c_lp_item', [
            'title' => 'gl_lp_item_doc', 'item_type' => 'document', 'ref' => '', 'path' => (string) $plain, 'min_score' => 0,
            'display_order' => 1, 'launch_data' => '', 'lvl' => 0,
        ]);

        $cats = $this->exerciseLink($plain)->get_all_links();
        $inLp = array_values(array_filter($cats, static fn (array $c): bool => str_starts_with((string) $c[1], 'gl_quiz_in_lp')));
        $this->assertCount(1, array_filter($inLp, static fn (array $c): bool => str_contains((string) $c[1], '(')));
        $lpEntry = array_values(array_filter($inLp, static fn (array $c): bool => str_contains((string) $c[1], '(')))[0];
        $this->assertSame($quiz, (int) $lpEntry[0]);
        $this->assertMatchesRegularExpression('/^gl_quiz_in_lp \(.+\)$/', $lpEntry[1]);
        // A quiz listed only through a non-quiz LP item does not get the LP entry.
        $this->assertSame([], array_filter($cats, static fn (array $c): bool => str_starts_with((string) $c[1], 'gl_quiz_not_in_lp ')));
        $this->assertSame([], $this->exerciseLinkWithoutCourse()->get_all_links());
    }

    private function exerciseLinkWithoutCourse(): ExerciseLink
    {
        $link = new ExerciseLink();
        $link->course_id = 0;

        return $link;
    }

    public function testExerciseCompletionTrackingIdsComeFromTheCourseCompletionRule(): void
    {
        $fieldId = $this->conn->fetchOne("SELECT id FROM extra_field WHERE item_type = 2 AND variable = 'course_completion_rule'");
        if (false === $fieldId) {
            $this->conn->insert('extra_field', [
                'item_type' => 2, 'value_type' => 1, 'variable' => 'course_completion_rule', 'created_at' => '2020-01-01 00:00:00',
            ]);
            $fieldId = (int) $this->conn->lastInsertId();
        }
        $course = 987650 + $this->seq++;
        $quiz = $this->quiz();
        $other = $this->quiz();
        $rule = json_encode([
            'version' => 1,
            'migration_complete' => true,
            'components' => [['type' => 'exercise', 'resource_id' => $quiz, 'tracking_resource_ids' => [$quiz + 1000]]],
        ]);
        $value = fn (string $v) => $this->conn->insert('extra_field_values', [
            'field_id' => $fieldId, 'item_id' => $course, 'field_value' => $v,
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00',
        ]);

        // No value for the course: nothing configured.
        $this->resetCompletionCache();
        $this->assertFalse($this->exerciseLink($quiz, $course)->hasConfiguredCompletionTrackingIds());

        $value($rule);
        $this->resetCompletionCache();
        $this->assertTrue($this->exerciseLink($quiz, $course)->hasConfiguredCompletionTrackingIds());
        $this->assertFalse($this->exerciseLink($other, $course)->hasConfiguredCompletionTrackingIds());
        // Same rule on another course id does not leak.
        $this->resetCompletionCache();
        $this->assertFalse($this->exerciseLink($quiz, $course + 1)->hasConfiguredCompletionTrackingIds());

        // An incomplete migration is ignored.
        $this->resetCompletionCache();
        $this->conn->update('extra_field_values', ['field_value' => str_replace('true', 'false', $rule)], ['item_id' => $course]);
        $this->assertFalse($this->exerciseLink($quiz, $course)->hasConfiguredCompletionTrackingIds());

        // Two values for the same course and field: ambiguous, ignored.
        $this->resetCompletionCache();
        $this->conn->update('extra_field_values', ['field_value' => $rule], ['item_id' => $course]);
        $value($rule);
        $this->assertFalse($this->exerciseLink($quiz, $course)->hasConfiguredCompletionTrackingIds());
        $this->resetCompletionCache();
    }

    // ------------------------------------------------------------ learnpath

    private function lp(array $o = []): int
    {
        $this->conn->insert('c_lp', $o + [
            'title' => 'gl_lp_'.(++$this->seq),
            'description' => 'lp description',
            'lp_type' => 1,
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

    private function lpView(int $lpId, int $userId, int $courseId, ?int $sessionId, int $progress = 0): void
    {
        $this->conn->insert('c_lp_view', [
            'lp_id' => $lpId,
            'user_id' => $userId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'view_count' => 1,
            'last_item' => 0,
            'progress' => $progress,
        ]);
    }

    private function lpLink(int $lpId, int $courseId = 1, int $sessionId = 0): LearnpathLink
    {
        $link = new LearnpathLink();
        $link->set_ref_id($lpId);
        $link->course_id = $courseId;
        $link->set_session_id($sessionId);

        return $link;
    }

    public function testLearnpathIsValidLinkNameAndDescription(): void
    {
        $lp = $this->lp(['title' => 'LP title', 'description' => 'LP desc']);
        $this->assertTrue($this->lpLink($lp)->is_valid_link());
        $this->assertFalse($this->lpLink(999999999)->is_valid_link());
        $this->assertSame('LP title', $this->lpLink($lp)->get_name());
        $this->assertSame('LP desc', $this->lpLink($lp)->get_description());
    }

    public function testLearnpathHasResultsIsScopedByCourseLpAndSession(): void
    {
        $lp = $this->lp();
        $other = $this->lp();
        $session = (int) $this->conn->fetchOne('SELECT id FROM session ORDER BY id LIMIT 1');
        if (0 === $session) {
            $this->conn->executeStatement(
                'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position) VALUES (?, 0, 0, 0, 1, 0, 0)',
                ['gl_lp_session']
            );
            $session = (int) $this->conn->lastInsertId();
        }
        $this->assertFalse($this->lpLink($lp)->has_results());

        $this->lpView($lp, $this->users[0], 1, null);
        $this->assertFalse($this->lpLink($lp)->has_results(), 'a base-course view (NULL session) is not session 0');
        $this->lpView($lp, $this->users[0], 1, $session);
        $this->assertTrue($this->lpLink($lp, 1, $session)->has_results());
        $this->assertFalse($this->lpLink($other, 1, $session)->has_results());
        $this->assertFalse($this->lpLink($lp, 2, $session)->has_results());
        $this->assertFalse($this->lpLink($lp)->has_results());
    }

    // --------------------------------------------------- forum participation

    private function post(int $threadId, int $userId, int $visible = 1): void
    {
        $this->conn->insert('c_forum_post', [
            'thread_id' => $threadId,
            'poster_id' => $userId,
            'title' => 'gl_post_'.(++$this->seq),
            'post_date' => '2020-01-01 00:00:00',
            'visible' => $visible,
        ]);
    }

    private function participationLink(int $threadId): ForumParticipationLink
    {
        $link = new ForumParticipationLink();
        $link->set_ref_id($threadId);
        $link->set_points_one(2.0);
        $link->set_points_many(5.0);

        return $link;
    }

    public function testForumParticipationCountsVisiblePostsOfTheThreadPerStudent(): void
    {
        [$a, $b, $c] = $this->users;
        $thread = $this->thread();
        $other = $this->thread();
        $this->post($thread, $a);
        $this->post($thread, $b);
        $this->post($thread, $b);
        $this->post($thread, $b, 0);
        $this->post($thread, $c, 0);
        $this->post($other, $a);
        $this->post($other, $a);
        $this->post($other, $c);
        $link = $this->participationLink($thread);

        $this->assertSame([2.0, 5.0], $link->calc_score($a));
        $this->assertSame([5.0, 5.0], $link->calc_score($b));
        $this->assertSame([0.0, 5.0], $link->calc_score($c), 'only a hidden post in the thread');
        $this->assertSame([null, null], $link->calc_score());
        $this->assertTrue($link->has_results());
        $this->assertFalse($this->participationLink($this->thread())->has_results());
        $hidden = $this->thread();
        $this->post($hidden, $a, 0);
        $this->assertFalse($this->participationLink($hidden)->has_results());
    }
}
