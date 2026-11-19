<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tracking;

/**
 * Wave 3 characterization tests (third part) for the Tracking library: show_user_progress, getLpStats and the
 * user data move helpers, pinned before their raw SQL is rewritten with the QueryBuilder.
 */
final class TrackingTier3C5CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private Connection $conn;
    private User $alice;
    private User $bob;
    private Course $courseA;
    private Course $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setManager($this->getEntityManager());
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/tracking.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
        $this->conn = $this->getEntityManager()->getConnection();
        $this->alice = $this->createUser('c5_alice');
        $this->bob = $this->createUser('c5_bob');
        // Titles chosen so that the alphabetical order differs from the creation order.
        $this->courseB = $this->createCourse('c5_zz_course');
        $this->courseA = $this->createCourse('c5_aa_course');
    }

    // ------------------------------------------------------------------
    // show_user_progress
    // ------------------------------------------------------------------

    public function testShowUserProgressListsTheCoursesByTitleWithoutHrRelationsAndTheSessionsOfTheStudent(): void
    {
        $alice = (int) $this->alice->getId();
        $this->courseB->addUserAsStudent($this->alice);
        $this->courseA->addUserAsStudent($this->alice);
        $hr = $this->createCourse('c5_hr_course');
        $hr->addUserAsStudent($this->alice);
        $this->courseB->addUserAsStudent($this->bob);
        $this->getEntityManager()->flush();
        $this->conn->update(
            'course_rel_user',
            ['relation_type' => COURSE_RELATION_TYPE_RRHH],
            ['user_id' => $alice, 'c_id' => $hr->getId()]
        );

        $s1 = $this->createSession('c5_session_1');
        $s2 = $this->createSession('c5_session_2');
        $this->subscribeInSession($alice, (int) $s1->getId(), (int) $this->courseB->getId());
        $this->subscribeInSession($alice, (int) $s1->getId(), (int) $this->courseA->getId());
        $this->subscribeInSession($alice, (int) $s2->getId(), (int) $this->courseA->getId());
        $this->subscribeInSession((int) $this->bob->getId(), (int) $s2->getId(), (int) $this->courseB->getId());

        $result = Tracking::show_user_progress($alice, 0, '', true, true, true);

        // Index 0 holds the ids of the courses of the user (ordered by title, HR relation excluded).
        $this->assertEquals([(int) $this->courseA->getId(), (int) $this->courseB->getId()], $result[0]);
        $this->assertEqualsCanonicalizing([(int) $s1->getId(), (int) $s2->getId(), 0], array_keys($result));
        $this->assertSame('c5_session_1', $result[(int) $s1->getId()]['title']);
        // Within a session the courses are sorted by title (utf8_sort) when no session is requested.
        $this->assertSame(
            [(int) $this->courseA->getId(), (int) $this->courseB->getId()],
            array_map('intval', array_keys($result[(int) $s1->getId()]['course_list']))
        );
        // Bob's subscription is not Alice's.
        $this->assertSame(
            [(int) $this->courseA->getId()],
            array_map('intval', array_keys($result[(int) $s2->getId()]['course_list']))
        );

        // Restricted to one session.
        $only = Tracking::show_user_progress($alice, (int) $s2->getId(), '', true, true, true);
        $this->assertEqualsCanonicalizing([(int) $s2->getId(), 0], array_keys($only));
    }

    public function testShowUserProgressOfAUserWithoutSubscriptionIsEmpty(): void
    {
        $result = Tracking::show_user_progress((int) $this->bob->getId(), 0, '', true, true, true);

        $this->assertSame([0 => []], $result);
    }

    // ------------------------------------------------------------------
    // getLpStats
    // ------------------------------------------------------------------

    public function testLpStatsNotExtendedUsesTheLatestQuizViewAndSumsTheExerciseDurations(): void
    {
        $f = $this->lpFixture((int) $this->createSession('c5_lp_s')->getId());

        $out = $this->lpStats($f);

        // Document row: no score; the quiz row shows the score of the LATEST item view (5, not 3) over the
        // maximum of the last completed attempt (3 + 4 = 7), displayed over 20: 5/7*20 = 14.29 (3/7*20 would be 8.57).
        $text = $this->flat($out);
        $this->assertStringContainsString('c5_doc', $text);
        $this->assertStringContainsString('c5_quiz', $text);
        $this->assertStringContainsString('c5_quiz Completed 14.29 / 20', $text);
        // The duration of the completed attempts of the base context (40 + 70) replaces the item views time (150) and is
        // saved on the latest item view only.
        $this->assertSame('110', (string) $this->conn->fetchOne('SELECT total_time FROM c_lp_item_view WHERE iid = ?', [$f['quizView2']]));
        $this->assertSame('100', (string) $this->conn->fetchOne('SELECT total_time FROM c_lp_item_view WHERE iid = ?', [$f['quizView1']]));
        // The quiz rows link to the attempts list of the exercise because it has attempts.
        $this->assertStringContainsString('lp_item_id='.$f['quizItem'], $out);
        $this->assertStringNotContainsString('lp_item_id='.$f['docItem'], $out);
    }

    public function testLpStatsHidesTheQuizScoreWhenTheExerciseResultsAreDisabled(): void
    {
        $f = $this->lpFixture((int) $this->createSession('c5_lp_s')->getId());
        $this->conn->update('c_quiz', ['results_disabled' => 1], ['iid' => $f['quiz']]);

        $out = $this->lpStats($f);
        $this->assertStringContainsString('Results hidden by the exercise setting', $out);
        $this->assertStringNotContainsString('14.29', $this->flat($out));

        $outExtended = $this->lpStats($f, ['extendedAll' => 1]);
        $this->assertStringContainsString('Results hidden by the exercise setting', $outExtended);
    }

    public function testLpStatsExtendAllListsTheAttemptsOldestFirstOfTheLatestViewOnly(): void
    {
        $f = $this->lpFixture((int) $this->createSession('c5_lp_s')->getId());

        $out = $this->lpStats($f, ['extendedAll' => 1]);
        $text = $this->flat($out);

        $this->assertStringContainsString('Attempt 1', $text);
        $this->assertStringContainsString('Attempt 2', $text);
        // Oldest item view first (score 3, then 5).
        $first = strpos($text, 'Attempt 1');
        $second = strpos($text, 'Attempt 2');
        $this->assertMatchesRegularExpression('/Attempt 1.*3\s*\/\s*20.*Attempt 2.*5\s*\/\s*20/', $text);
        $this->assertLessThan($second, $first);
        $this->assertStringNotContainsString('Attempt 3', $text);
        // Visible results: not hidden.
        $this->assertStringNotContainsString('Results hidden by the exercise setting', $out);
    }

    public function testLpStatsOnlyShowsTheLastViewOfTheLearningPath(): void
    {
        $f = $this->lpFixture((int) $this->createSession('c5_lp_s')->getId());
        // A newer learning path view (view_count 2) holding only the document, with another status.
        $view2 = $this->insertLpView((int) $this->alice->getId(), $f['lp'], (int) $this->courseA->getId(), 10, $f['session'], 2);
        $this->insertLpItemView($f['docItem'], $view2, 77, 'incomplete');

        $text = $this->flat($this->lpStats($f));

        // The quiz item has no view in the newest learning path view: it is not listed with data.
        $this->assertStringNotContainsString('14.29', $text);
        $this->assertStringContainsString('c5_doc', $text);
        $this->assertStringContainsString('Incomplete', $text);
    }

    public function testLpStatsExtendedAttemptsOfAQuizListTheCompletedAttemptsOfTheBaseContext(): void
    {
        $f = $this->lpFixture((int) $this->createSession('c5_lp_s')->getId());

        $out = $this->lpStats($f, ['lp_item_id' => $f['quizItem'], 'extendedAttempt' => 1]);
        $text = $this->flat($out);

        // The two completed attempts, oldest first; neither the incomplete one nor the other session's one.
        $this->assertMatchesRegularExpression('/Attempt 1 completed 10 \/ 20.*Attempt 2 completed 20 \/ 20/', $text);
        $this->assertStringNotContainsString('Attempt 3', $text);
        // The attempt duration comes from exe_duration (40 s then 70 s).
        $this->assertMatchesRegularExpression('/Attempt 1.*00 : 00 : 40.*Attempt 2.*00 : 01 : 10/', $text);
    }

    public function testLpStatsIgnoresTheViewsOfOtherUsersAndOtherSessions(): void
    {
        $f = $this->lpFixture();
        $bob = (int) $this->bob->getId();
        $bobView = $this->insertLpView($bob, $f['lp'], (int) $this->courseA->getId(), 99, null, 1);
        $this->insertLpItemView($f['docItem'], $bobView, 5, 'passed');
        $session = $this->createSession('c5_lp_session');
        $sessionView = $this->insertLpView((int) $this->alice->getId(), $f['lp'], (int) $this->courseA->getId(), 99, (int) $session->getId(), 5);
        $this->insertLpItemView($f['docItem'], $sessionView, 5, 'failed');

        $base = $this->flat($this->lpStats($f));
        // The higher view count of the other session does not hide the base context rows.
        $this->assertStringContainsString('c5_doc Completed', $base);
        $this->assertStringNotContainsString('Passed', $base);
        $this->assertStringNotContainsString('Failed', $base);

        $inSession = $this->flat($this->lpStats($f, ['session' => $session]));
        $this->assertStringContainsString('Failed', $inSession);
        $this->assertStringNotContainsString('Passed', $inSession);
    }

    public function testLpStatsKeepsTheItemViewTimeWhenItEqualsTheSumOfTheExerciseDurations(): void
    {
        $f = $this->lpFixture((int) $this->createSession('c5_lp_eq')->getId());
        // 40 + 110 = 150, the sum of the item views (100 + 50): nothing to rewrite.
        $this->conn->update('track_e_exercises', ['exe_duration' => 110], ['exe_id' => $f['exe2']]);

        $text = $this->flat($this->lpStats($f));

        $this->assertStringContainsString('c5_quiz Completed 14.29 / 20 00 : 02 : 30', $text);
        $this->assertSame('50', (string) $this->conn->fetchOne('SELECT total_time FROM c_lp_item_view WHERE iid = ?', [$f['quizView2']]));
    }

    public function testLpStatsInTheBaseContextNeverMatchesTheStrictSessionZeroQueries(): void
    {
        // The base context stores NULL: the queries comparing session_id = 0 find no attempt (kept as is).
        $f = $this->lpFixture(null);

        $out = $this->lpStats($f);
        $this->assertStringContainsString('c5_quiz Completed 14.29 / 20', $this->flat($out));
        // The exercise duration is not found, so the item view time is not rewritten and no attempts link is shown.
        $this->assertSame('50', (string) $this->conn->fetchOne('SELECT total_time FROM c_lp_item_view WHERE iid = ?', [$f['quizView2']]));
        $this->assertStringNotContainsString('extend_attempt=1', $out);

        $attempts = $this->flat($this->lpStats($f, ['lp_item_id' => $f['quizItem'], 'extendedAttempt' => 1]));
        $this->assertStringNotContainsString('Attempt 1', $attempts);
    }

    /**
     * @return array<string,int>
     */
    private function lpFixture(?int $sessionId = null): array
    {
        $cid = (int) $this->courseA->getId();
        $alice = (int) $this->alice->getId();
        $lp = $this->insertLp('c5_lp');
        $quiz = $this->insertQuiz('c5_quiz_test');
        $root = $this->insertLpItem($lp, 'dir', 0, 0, 'root', 'root');
        $docItem = $this->insertLpItem($lp, 'document', 0, 1, '', 'c5_doc', $root);
        $quizItem = $this->insertLpItem($lp, 'quiz', 20, 2, (string) $quiz, 'c5_quiz', $root);
        $view = $this->insertLpView($alice, $lp, $cid, 10, $sessionId, 1);
        $this->insertLpItemView($docItem, $view, 30, 'completed');
        $quizView1 = $this->insertLpItemView($quizItem, $view, 100, 'completed', 3, 1);
        $quizView2 = $this->insertLpItemView($quizItem, $view, 50, 'completed', 5, 2);

        $q1 = $this->insertQuestion(3);
        $q2 = $this->insertQuestion(4);
        $exe1 = $this->insertExercise($alice, $cid, $quiz, $lp, $quizItem, 40, 'completed', '2001-01-01 10:00:00', $sessionId, 1);
        $exe2 = $this->insertExercise($alice, $cid, $quiz, $lp, $quizItem, 70, 'completed', '2001-01-02 10:00:00', $sessionId, 2);
        $exeIncomplete = $this->insertExercise($alice, $cid, $quiz, $lp, $quizItem, 900, 'incomplete', '2001-01-03 10:00:00', $sessionId, 0);
        $otherSession = (int) $this->createSession('c5_other_session')->getId();
        $exeNullSession = $this->insertExercise($alice, $cid, $quiz, $lp, $quizItem, 1000, 'completed', '2001-02-01 10:00:00', $otherSession, 0);
        // The first attempt only answered the first question (maximum 3), the last one both (maximum 7); a question
        // answered twice (several rows) is counted once.
        $attempts = [[$exe1, $q1], [$exe2, $q1], [$exe2, $q1], [$exe2, $q2]];
        foreach ($attempts as [$exe, $q]) {
            $this->conn->insert('track_e_attempt', [
                'exe_id' => $exe,
                'question_id' => $q,
                'answer' => '',
                'teacher_comment' => '',
                'marks' => 1,
                'tms' => '2001-01-01 10:00:00',
                'seconds_spent' => 0,
            ]);
        }

        return [
            'lp' => $lp,
            'quiz' => $quiz,
            'docItem' => $docItem,
            'quizItem' => $quizItem,
            'quizView1' => $quizView1,
            'quizView2' => $quizView2,
            'exe1' => $exe1,
            'exe2' => $exe2,
            'exeIncomplete' => $exeIncomplete,
            'exeNullSession' => $exeNullSession,
            'session' => $sessionId,
        ];
    }

    private function lpStats(array $f, array $opts = []): string
    {
        return Tracking::getLpStats(
            (int) $this->alice->getId(),
            $this->courseA,
            $opts['session'] ?? ($f['session'] ? $this->getEntityManager()->getRepository(\Chamilo\CoreBundle\Entity\Session::class)->find($f['session']) : null),
            'tracking',
            false,
            $f['lp'],
            $opts['lp_item_id'] ?? null,
            null,
            null,
            $opts['extendedAttempt'] ?? null,
            $opts['extendedAll'] ?? null
        );
    }

    private function flat(string $html): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($html));
    }

    private function insertQuestion(float $ponderation): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => 'q',
            'ponderation' => $ponderation,
            'position' => 1,
            'type' => 1,
            'level' => 1,
            'mandatory' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertQuiz(string $title): int
    {
        $this->conn->insert('c_quiz', [
            'title' => $title,
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
            'page_result_configuration' => '{}',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLp(string $title): int
    {
        $this->conn->insert('c_lp', [
            'lp_type' => 1,
            'title' => $title,
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
            'created_on' => '2001-01-01 00:00:00',
            'modified_on' => '2001-01-01 00:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpItem(int $lpId, string $type, float $maxScore, int $order, string $path, string $title, ?int $parent = null): int
    {
        $this->conn->insert('c_lp_item', [
            'lp_id' => $lpId,
            'title' => $title,
            'item_type' => $type,
            'ref' => '',
            'path' => $path,
            'parent_item_id' => $parent,
            'min_score' => 0,
            'max_score' => $maxScore,
            'display_order' => $order,
            'launch_data' => '',
            'lvl' => 1,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpView(int $userId, int $lpId, int $courseId, ?int $progress, ?int $sessionId, int $viewCount = 1): int
    {
        $this->conn->insert('c_lp_view', [
            'user_id' => $userId,
            'lp_id' => $lpId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'view_count' => $viewCount,
            'last_item' => 0,
            'progress' => $progress,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpItemView(int $itemId, int $viewId, int $total, string $status, float $score = 0, int $viewCount = 1): int
    {
        $this->conn->insert('c_lp_item_view', [
            'lp_item_id' => $itemId,
            'lp_view_id' => $viewId,
            'view_count' => $viewCount,
            'start_time' => 1000000,
            'total_time' => $total,
            'score' => $score,
            'status' => $status,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertExercise(
        int $userId,
        int $courseId,
        int $quizId,
        int $lpId,
        int $lpItemId,
        int $duration,
        string $status,
        string $date,
        ?int $sessionId,
        float $score
    ): int {
        $this->conn->insert('track_e_exercises', [
            'exe_user_id' => $userId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'exe_exo_id' => $quizId,
            'exe_date' => $date,
            'score' => $score,
            'max_score' => 2,
            'user_ip' => '127.0.0.1',
            'status' => $status,
            'data_tracking' => '',
            'start_date' => $date,
            'steps_counter' => 0,
            'orig_lp_id' => $lpId,
            'orig_lp_item_id' => $lpItemId,
            'exe_duration' => $duration,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function subscribeInSession(int $userId, int $sessionId, int $courseId): void
    {
        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'c_id' => $courseId,
            'status' => 0,
            'visibility' => 1,
            'legal_agreement' => 0,
            'progress' => 0,
        ]);
    }
}
