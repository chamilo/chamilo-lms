<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use ExerciseLib;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for the raw-SQL helpers of ExerciseLib (public/main/inc/lib/exercise.lib.php): result and
 * attempt listings, counters, per-answer statistics and attempt deletion.
 *
 * They pin what the legacy queries return or write TODAY so a rewrite to Doctrine/DQL that changes any of it fails
 * here. Quirks are pinned as-is and flagged "PINNED QUIRK".
 *
 * Quizzes are bare c_quiz rows linked to the course through a resource_link on a spare resource node (creating
 * nodes deadlocks on the shared database). Extra users are raw INSERTs. Fixtures are tagged "qs_".
 */
final class ExerciseLibCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    /** @var int[]|null */
    private ?array $spare = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/exercise/exercise_report.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $_SERVER['HTTP_USER_AGENT'] = 'qs_agent';
        $_SERVER['REQUEST_URI'] = '/qs_uri';
        unset($_GET['gradebook'], $_GET['filter_by_user'], $_REQUEST['learnpath_id']);
        $this->setSetting('tracking.hide_ip_address', 'true');
        $this->setSetting('exercise.show_exercise_session_attempts_in_base_course', 'false');
    }

    // ---------------------------------------------------------------- helpers

    private function setContext(User $user, Course $course, int $sessionId = 0): void
    {
        ChamiloSession::write('_user', ['id' => $user->getId()]);
        ChamiloSession::write('_real_cid', $course->getId());
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::erase('login_as');
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }

    private function ctx(int $sessionId = 0): void
    {
        $this->setContext($this->getUser('admin'), $this->getCourse(1), $sessionId);
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    private function rawUser(string $suffix, int $status = 5): int
    {
        $name = 'qs_'.$suffix.'_'.getmypid().'_'.(++$this->seq);
        $this->conn->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', \'[]\', ?, 1,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, NOW(), NOW(), \'\')',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$suffix, 'Last'.$suffix, $status]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$id]);

        return $id;
    }

    private function rawSession(string $title): int
    {
        $this->conn->insert('session', [
            'title' => 'qs_'.$title,
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function subscribe(int $userId, int $courseId, int $status = STUDENT, int $relationType = 0): void
    {
        $this->conn->insert('course_rel_user', [
            'user_id' => $userId,
            'c_id' => $courseId,
            'relation_type' => $relationType,
            'status' => $status,
            'is_tutor' => 0,
            'sort' => 0,
            'user_course_cat' => 0,
            'progress' => 0,
        ]);
    }

    private function subscribeInSession(int $userId, int $courseId, int $sessionId, int $status = SessionEntity::STUDENT): void
    {
        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => $userId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'status' => $status,
            'visibility' => 0,
            'legal_agreement' => 0,
            'progress' => 0,
        ]);
    }

    /** A quiz linked to the course through a live (or soft-deleted) resource_link on a spare node. */
    private function quiz(array $o = [], ?int $courseId = 1, ?string $deletedAt = null, int $nodeIdx = 0): int
    {
        $this->spare ??= $this->conn->fetchFirstColumn(
            'SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM c_quiz WHERE resource_node_id IS NOT NULL)'
            .' AND id NOT IN (SELECT resource_node_id FROM resource_link WHERE resource_node_id IS NOT NULL) ORDER BY id DESC LIMIT 12'
        );
        $this->assertGreaterThan($this->nextNode, \count($this->spare), 'fixture needs spare resource nodes');
        $node = (int) $this->spare[$this->nextNode++];
        $this->conn->insert('c_quiz', $o + [
            'title' => 'qs_quiz_'.(++$this->seq),
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
            'resource_node_id' => $node,
        ]);
        $quizId = (int) $this->conn->lastInsertId();
        if (null !== $courseId) {
            $now = '2020-01-01 00:00:00';
            $this->conn->insert('resource_link', [
                'resource_node_id' => $node,
                'c_id' => $courseId,
                'visibility' => 2,
                'display_order' => 0,
                'resource_type_group' => 1,
                'deleted_at' => $deletedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $quizId;
    }

    private int $nextNode = 0;

    private function question(string $title, int $type = 1, float $weight = 1.0): int
    {
        $this->conn->insert('c_quiz_question', [
            'question' => $title,
            'ponderation' => $weight,
            'position' => 0,
            'type' => $type,
            'level' => 0,
            'mandatory' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function exe(array $o): int
    {
        $this->conn->insert('track_e_exercises', $o + [
            'exe_user_id' => 1,
            'c_id' => 1,
            'session_id' => null,
            'exe_exo_id' => null,
            'exe_date' => '2020-01-01 10:00:00',
            'score' => 0,
            'max_score' => 0,
            'user_ip' => '10.0.0.1',
            'status' => '',
            'data_tracking' => '',
            'start_date' => '2020-01-01 09:00:00',
            'steps_counter' => 0,
            'orig_lp_id' => 0,
            'orig_lp_item_id' => 0,
            'exe_duration' => 0,
            'orig_lp_item_view_id' => 0,
            'questions_to_check' => '',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function attempt(int $exeId, int $userId, int $questionId, string $answer, float $marks = 1.0): void
    {
        $this->conn->insert('track_e_attempt', [
            'exe_id' => $exeId,
            'user_id' => $userId,
            'question_id' => $questionId,
            'answer' => $answer,
            'teacher_comment' => '',
            'marks' => $marks,
            'position' => 1,
            'tms' => '2020-01-01 10:00:00',
            'seconds_spent' => 0,
        ]);
    }

    private function hotspot(int $exeId, int $userId, int $questionId, int $answerId, int $correct, int $courseId = 1): void
    {
        $this->conn->insert('track_e_hotspot', [
            'c_id' => $courseId,
            'hotspot_user_id' => $userId,
            'hotspot_exe_id' => $exeId,
            'hotspot_question_id' => $questionId,
            'hotspot_answer_id' => $answerId,
            'hotspot_correct' => $correct,
            'hotspot_coordinate' => '0;0',
        ]);
    }

    private function qualify(int $exeId, int $author, int $final): void
    {
        $this->conn->insert('track_e_attempt_qualify', [
            'exe_id' => $exeId,
            'question_id' => 0,
            'marks' => 1,
            'insert_date' => '2020-01-01 10:00:00',
            'author' => $author,
            'teacher_comment' => '',
            'session_id' => 0,
            'final' => $final,
        ]);
    }

    private function cnt(string $table, string $where, array $params = []): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    /** @return int[] exe ids of the rows (key 'exe_id') returned by get_exam_results_data */
    private function results(int $quizId, string $where = '', ?string $column = 'exe_id', string $dir = 'asc', int $courseId = 1): array
    {
        $rows = ExerciseLib::get_exam_results_data(0, 100, $column, $dir, $quizId, $where, false, $courseId, false, true);
        $this->assertIsArray($rows);

        return array_map(static fn (array $r): int => (int) $r['exe_id'], $rows);
    }

    private function examCount(int $quizId, string $where = '', int $courseId = 1, bool $showSession = false): int
    {
        return (int) ExerciseLib::get_count_exam_results($quizId, $where, $courseId, $showSession);
    }

    // ---------------------------------------------------------------- get_exercise_track_exercise_info

    public function testTrackInfoJoinsTheQuizAndTheAttemptAndFormatsDuration(): void
    {
        $this->ctx();
        $quiz = $this->quiz(['title' => 'qs_info_title']);
        $exe = $this->exe(['exe_exo_id' => $quiz, 'score' => 3, 'max_score' => 4, 'exe_duration' => 125]);
        $noDuration = $this->exe(['exe_exo_id' => $quiz]);

        $info = ExerciseLib::get_exercise_track_exercise_info($exe);

        $this->assertSame('qs_info_title', $info['title']);
        $this->assertSame($exe, (int) $info['exe_id']);
        $this->assertSame($quiz, (int) $info['exe_exo_id']);
        $this->assertEquals(3, $info['score']);
        $this->assertSame('', ExerciseLib::get_exercise_track_exercise_info($noDuration)['duration_formatted']);
        $this->assertNotSame('', $info['duration_formatted']);
        $this->assertSame([], ExerciseLib::get_exercise_track_exercise_info(0));
        $this->assertSame([], ExerciseLib::get_exercise_track_exercise_info(999999999));
    }

    public function testTrackInfoIsEmptyWhenTheAttemptHasNoQuiz(): void
    {
        $this->ctx();
        $exe = $this->exe(['exe_exo_id' => null]);
        $this->assertSame([], ExerciseLib::get_exercise_track_exercise_info($exe), 'INNER JOIN on c_quiz');
    }

    // ---------------------------------------------------------------- get_exam_results_data / get_count_exam_results

    public function testResultsListOnlyAttemptsOfThatQuizAndCourse(): void
    {
        $this->ctx();
        $quiz = $this->quiz(['title' => 'qs_res_quiz']);
        $other = $this->quiz();
        $first = $this->exe(['exe_exo_id' => $quiz, 'score' => 2, 'max_score' => 5]);
        $second = $this->exe(['exe_exo_id' => $quiz, 'score' => 5, 'max_score' => 5, 'exe_date' => '2020-01-02 10:00:00']);
        $this->exe(['exe_exo_id' => $other]);
        $this->exe(['exe_exo_id' => $quiz, 'c_id' => 2]);

        $this->assertSame([$first, $second], $this->results($quiz));
        $this->assertSame([$second, $first], $this->results($quiz, '', 'exe_id', 'desc'));
        $this->assertSame(2, $this->examCount($quiz));
        // PINNED QUIRK: the quiz's resource_link is joined without checking its course, so an attempt row of
        // another course (c_id = 2) on the same quiz is listed when that course is requested.
        $this->assertCount(1, $this->results($quiz, '', 'exe_id', 'asc', 2));
    }

    public function testResultRowsCarryUserQuizAndFormattedScores(): void
    {
        $this->ctx();
        $quiz = $this->quiz(['title' => 'qs_res_row', 'expired_time' => 0]);
        $exe = $this->exe(['exe_exo_id' => $quiz, 'score' => 2, 'max_score' => 5, 'exe_duration' => 180, 'steps_counter' => 3]);

        $rows = ExerciseLib::get_exam_results_data(0, 100, 'exe_id', 'asc', $quiz, '', false, 1, false, true);

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame('qs_res_row', $row['title']);
        $this->assertSame('admin', $row['username']);
        $this->assertSame($exe, (int) $row['id']);
        $this->assertSame(1, (int) $row['exe_user_id']);
        $this->assertSame(3, (int) $row['exe_duration'], 'seconds converted to rounded minutes');
        $this->assertEquals(2, $row['only_score']);
        $this->assertEquals(5, $row['total']);
        $this->assertNotSame('', $row['score_percentage']);
        $this->assertNotSame('', $row['actions']);
        $this->assertSame('', $row['session']);
    }

    public function testResultsHideUnlinkedAndSoftDeletedQuizzes(): void
    {
        $this->ctx();
        $live = $this->quiz();
        $deleted = $this->quiz([], 1, '2020-02-01 00:00:00');
        $unlinked = $this->quiz([], null);
        $exeLive = $this->exe(['exe_exo_id' => $live]);
        $this->exe(['exe_exo_id' => $deleted]);
        $this->exe(['exe_exo_id' => $unlinked]);

        $this->assertSame([$exeLive], $this->results($live));
        $this->assertSame([], $this->results($deleted), 'quiz moved to the recycle bin');
        $this->assertSame([], $this->results($unlinked), 'no resource_link: invisible');
        $this->assertSame(0, $this->examCount($deleted));
    }

    public function testResultsSkipIgnoredStatusesAndSoftDeletedUsers(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $student = $this->rawUser('res_student');
        $invitee = $this->rawUser('res_invitee', INVITEE);
        $gone = $this->rawUser('res_gone');
        $this->conn->executeStatement('UPDATE user SET active = ? WHERE id = ?', [USER_SOFT_DELETED, $gone]);
        $ok = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $student]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $invitee]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 2]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $gone]);

        $this->assertSame([$ok], $this->results($quiz));
        $this->assertSame(1, $this->examCount($quiz));
    }

    public function testResultsAreScopedToTheCurrentSession(): void
    {
        $sid = $this->rawSession('res_sess');
        $quiz = $this->quiz();
        $base = $this->exe(['exe_exo_id' => $quiz]);
                $inSession = $this->exe(['exe_exo_id' => $quiz, 'session_id' => $sid]);

        $this->ctx();
        $this->assertSame([$base], $this->results($quiz), 'base course: NULL session');

        $this->ctx($sid);
        $this->assertSame([$inSession], $this->results($quiz));
        $this->assertSame(1, $this->examCount($quiz));

        // The "show session field" flag lifts the scoping altogether (all sessions at once).
        $this->ctx();
        $this->assertSame(2, $this->examCount($quiz, '', 1, true));

        // PINNED QUIRK: the base-course setting only widens the SELECT-side condition; the track sub-select
        // is lifted too, so the base course shows every session.
        $this->setSetting('exercise.show_exercise_session_attempts_in_base_course', 'true');
        $this->assertSame([$base, $inSession], $this->results($quiz));
    }

    public function testResultsStatusCodeOfIncompleteAndRevisedAttempts(): void
    {
        $this->ctx();
        $limited = $this->quiz(['expired_time' => 30]);
        $unlimited = $this->quiz(['expired_time' => 0]);
        $now = gmdate('Y-m-d H:i:s');
        $longAgo = $this->exe(['exe_exo_id' => $limited, 'status' => 'incomplete', 'start_date' => '2001-01-01 00:00:00']);
        $ongoing = $this->exe(['exe_exo_id' => $limited, 'status' => 'incomplete', 'start_date' => $now]);
        $noLimit = $this->exe(['exe_exo_id' => $unlimited, 'status' => 'incomplete', 'start_date' => $now]);
        $revised = $this->exe(['exe_exo_id' => $unlimited]);
        $plain = $this->exe(['exe_exo_id' => $unlimited]);
        $this->qualify($revised, 1, 1);

        $labels = static function (array $rows): array {
            $out = [];
            foreach ($rows as $r) {
                $out[(int) $r['exe_id']] = trim(strip_tags((string) $r['status']));
            }

            return $out;
        };
        $limitedRows = ExerciseLib::get_exam_results_data(0, 100, 'exe_id', 'asc', $limited, '', false, 1, false, true);
        $unlimitedRows = ExerciseLib::get_exam_results_data(0, 100, 'exe_id', 'asc', $unlimited, '', false, 1, false, true);

        $this->assertSame([$longAgo => get_lang('Unclosed'), $ongoing => get_lang('Ongoing')], $labels($limitedRows));
        $this->assertSame(
            [$noLimit => get_lang('Unclosed'), $revised => get_lang('Validated'), $plain => get_lang('Not validated')],
            $labels($unlimitedRows)
        );
    }

    public function testResultsAcceptAnExtraWhereConditionAndRejectUnknownDirections(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $low = $this->exe(['exe_exo_id' => $quiz, 'score' => 1, 'max_score' => 5]);
        $high = $this->exe(['exe_exo_id' => $quiz, 'score' => 4, 'max_score' => 5]);

        $this->assertSame([$high], $this->results($quiz, ' AND te.score >= 4 '));
        $this->assertSame([$low, $high], $this->results($quiz, '', 'exe_id', 'bogus; DROP TABLE x'), 'direction falls back to asc');
        $this->assertSame(1, $this->examCount($quiz, ' AND te.score >= 4 '));
    }

    public function testResultsPageWithOffsetAndLimit(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $ids = [];
        foreach ([1, 2, 3, 4] as $i) {
            $ids[] = $this->exe(['exe_exo_id' => $quiz, 'score' => $i, 'max_score' => 4]);
        }

        $page = ExerciseLib::get_exam_results_data(1, 2, 'exe_id', 'asc', $quiz, '', false, 1, false, true);
        $this->assertSame([$ids[1], $ids[2]], array_map(static fn ($r) => (int) $r['exe_id'], $page));
    }

    public function testResultsGroupFilterWorksOnGroupMembership(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $member = $this->rawUser('grp_member');
        $outsider = $this->rawUser('grp_out');
        $this->conn->insert('c_group_info', [
            'title' => 'qs_group',
            'status' => 1,
            'description' => '',
            'max_student' => 0,
            'doc_state' => 0,
            'calendar_state' => 0,
            'work_state' => 0,
            'announcements_state' => 0,
            'forum_state' => 0,
            'wiki_state' => 0,
            'chat_state' => 0,
            'self_registration_allowed' => 0,
            'self_unregistration_allowed' => 0,
            'document_access' => 0,
        ]);
        $groupId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_group_rel_user', ['user_id' => $member, 'group_id' => $groupId, 'c_id' => 1, 'status' => 0, 'role' => '']);
        $inGroup = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $member]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $outsider]);

        // PINNED QUIRK: the "group" filter sub-select joins c_group_info on `g.id`/`g.c_id`/`g.name`, columns the
        // table no longer has (iid/title only): picking a group in the results page always throws.
        $this->expectException(NotAllowedException::class);
        $this->results($quiz, " AND group_id = $groupId ");
        $this->assertSame([$inGroup], []);
    }

    public function testResultsGroupNoneFilterThrowsOnTheSameMissingColumns(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $this->exe(['exe_exo_id' => $quiz]);

        // PINNED QUIRK: "No group" uses the same `g.id` / `g.c_id` join: throws.
        $this->expectException(NotAllowedException::class);
        $this->results($quiz, " AND group_id = 'group_none' ");
    }

    public function testResultsGroupAllFilterListsEveryone(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $one = $this->exe(['exe_exo_id' => $quiz]);

        $this->assertSame([$one], $this->results($quiz, " AND group_id = 'group_all' "));
    }

    public function testResultsAreFalseForANonEditorAndEmptyForAnUnknownCourse(): void
    {
        $quiz = $this->quiz();
        $this->exe(['exe_exo_id' => $quiz]);
        // a plain student of the course
        $student = $this->rawUser('res_plain');
        $this->subscribe($student, 1);
        $studentEntity = Container::getEntityManager()->getRepository(User::class)->find($student);
        $this->setContext($studentEntity, $this->getCourse(1));

        $this->assertFalse(ExerciseLib::get_exam_results_data(0, 10, 'exe_id', 'asc', $quiz, '', false, 1, false, true));
        $this->assertSame([], ExerciseLib::get_exam_results_data(0, 10, 'exe_id', 'asc', $quiz, '', false, 999999, false, true));
    }

    // ---------------------------------------------------------------- reviewed attempts

    public function testGetReviewedAttemptsListsOnlyFinalQualificationsByARealAuthor(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $sid = $this->rawSession('rev_sess');
        $reviewed = $this->exe(['exe_exo_id' => $quiz]);
        $draft = $this->exe(['exe_exo_id' => $quiz]);
        $noAuthor = $this->exe(['exe_exo_id' => $quiz]);
        $inSession = $this->exe(['exe_exo_id' => $quiz, 'session_id' => $sid]);
        $otherCourse = $this->exe(['exe_exo_id' => $quiz, 'c_id' => 2]);
        $this->qualify($reviewed, 1, 1);
        $this->qualify($reviewed, 1, 1); // duplicates collapse (DISTINCT)
        $this->qualify($draft, 1, 0);
        $this->qualify($noAuthor, 0, 1);
        $this->qualify($inSession, 1, 1);
        $this->qualify($otherCourse, 1, 1);

        $ids = static fn (array $rows): array => array_map('intval', array_column($rows, 'exe_id'));
        $this->assertSame([$reviewed], $ids(ExerciseLib::getReviewedAttemptsInfo($quiz, 0)));
        $this->assertSame([$inSession], $ids(ExerciseLib::getReviewedAttemptsInfo($quiz, $sid)));
    }

    // ---------------------------------------------------------------- counters

    public function testGetExerciseResultsCountByType(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $sid = $this->rawSession('cnt_sess');
        $u2 = $this->rawUser('cnt_u2');
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1, 'score' => 5, 'max_score' => 5]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1, 'score' => 5, 'max_score' => 5]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u2, 'score' => 2, 'max_score' => 5]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u2, 'score' => 3, 'max_score' => 5]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u2, 'score' => 5, 'max_score' => 5, 'status' => 'incomplete']);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1, 'score' => 1, 'max_score' => 1, 'session_id' => $sid]);

        $this->assertSame(6 - 1, ExerciseLib::getExerciseResultsCount('all', 1, $quiz), 'incomplete excluded, every session without filter');
        $this->assertSame(3, ExerciseLib::getExerciseResultsCount('correct', 1, $quiz));
        $this->assertSame(2, ExerciseLib::getExerciseResultsCount('wrong', 1, $quiz));
        $this->assertSame(1, ExerciseLib::getExerciseResultsCount('correct_student', 1, $quiz), 'distinct users');
        $this->assertSame(1, ExerciseLib::getExerciseResultsCount('wrong_student', 1, $quiz));
        $this->assertSame(1, ExerciseLib::getExerciseResultsCount('correct', 1, $quiz, $sid));
        $this->assertSame(0, ExerciseLib::getExerciseResultsCount('wrong', 1, $quiz, $sid));
        $this->assertSame(0, ExerciseLib::getExerciseResultsCount('all', 2, $quiz));
    }

    public function testGetTotalQuestionAnsweredCountsDistinctCompletedAttempts(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_tqa');
        $student = $this->rawUser('tqa_student');
        $this->subscribe($student, 1);
        $teacherExe = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1]);
        $studentExe = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $student]);
        $incomplete = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $student, 'status' => 'incomplete']);
        foreach ([$teacherExe, $studentExe, $incomplete] as $exe) {
            $this->attempt($exe, 1, $q, 'x');
            $this->attempt($exe, 1, $q, 'y'); // two rows for the question: counted once
        }
        $this->subscribe(1, 1, 1);

        $this->assertSame(2, ExerciseLib::getTotalQuestionAnswered(1, $quiz, $q));
        $this->assertSame(1, ExerciseLib::getTotalQuestionAnswered(1, $quiz, $q, true), 'students only');
        $this->assertSame(0, ExerciseLib::getTotalQuestionAnswered(2, $quiz, $q));
        $this->assertSame(0, ExerciseLib::getTotalQuestionAnswered(1, $quiz, 999999999));
    }

    public function testGetWrongQuestionResultsRanksQuestionsByNumberOfWrongMarks(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $sid = $this->rawSession('wrong_sess');
        $hard = $this->question('qs_hard', 1, 2.0);
        $easy = $this->question('qs_easy', 1, 2.0);
        $fine = $this->question('qs_fine', 1, 2.0);
        $e1 = $this->exe(['exe_exo_id' => $quiz]);
        $e2 = $this->exe(['exe_exo_id' => $quiz]);
        $e3 = $this->exe(['exe_exo_id' => $quiz, 'status' => 'incomplete']);
        $e4 = $this->exe(['exe_exo_id' => $quiz, 'session_id' => $sid]);
        foreach ([$e1, $e2, $e3] as $e) {
            $this->attempt($e, 1, $hard, 'a', 0.0);
        }
        $this->attempt($e1, 1, $easy, 'a', 0.0);
        $this->attempt($e2, 1, $easy, 'a', 0.0);
        $this->attempt($e1, 1, $fine, 'a', 2.0);
        $this->attempt($e2, 1, $fine, 'a', 2.0);
        $this->attempt($e4, 1, $easy, 'a', 1.0);
        $this->attempt($e4, 1, $hard, 'a', 2.0);
        $e5 = $this->exe(['exe_exo_id' => $quiz]);
        $this->attempt($e5, 1, $hard, 'a', 0.5);
        $this->attempt($this->exe(['exe_exo_id' => $quiz]), 1, $hard, 'a', 0.0);

        $rows = ExerciseLib::getWrongQuestionResults(1, $quiz);
        $this->assertSame(['qs_hard', 'qs_easy'], array_column($rows, 'question'), 'incomplete attempts ignored, fully right questions absent');
        $this->assertSame([4, 3], array_map('intval', array_column($rows, 'count')), 'most wrong first; session attempts count without a session filter');
        $this->assertSame([$hard, $easy], array_map('intval', array_column($rows, 'question_id')));

        $this->assertSame(['qs_easy'], array_column(ExerciseLib::getWrongQuestionResults(1, $quiz, $sid), 'question'));
        $this->assertSame(['qs_hard'], array_column(ExerciseLib::getWrongQuestionResults(1, $quiz, 0, 1), 'question'), 'limit keeps the worst');
    }

    // ---------------------------------------------------------------- per-question statistics

    public function testGetStudentStatsByQuestionAggregatesMarksOfCompletedAttempts(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_stats');
        $student = $this->rawUser('stats_student');
        $this->subscribe($student, 1);
        $this->subscribe(1, 1, 1);
        $a = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $student]);
        $b = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1]);
        $c = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $student, 'status' => 'incomplete']);
        $this->attempt($a, $student, $q, 'x', 1.0);
        $this->attempt($b, 1, $q, 'x', 3.0);
        $this->attempt($c, $student, $q, 'x', 100.0);
        $hrm = $this->rawUser('stats_hrm');
        $this->subscribe($hrm, 1, STUDENT, 2); // relation_type 2: not a learner
        $this->attempt($this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $hrm]), $hrm, $q, 'x', 50.0);
        $code = $this->getCourse(1)->getCode();

        $all = ExerciseLib::getStudentStatsByQuestion($q, $quiz, $code, 0);
        $this->assertEquals(50, $all['max']);
        $this->assertEquals(1, $all['min']);
        $this->assertEquals(18, $all['average'], 'every completed attempt, incomplete ones excluded');

        $students = ExerciseLib::getStudentStatsByQuestion($q, $quiz, $code, 0, true);
        $this->assertEquals(1, $students['max'], 'only the subscribed student (status 5, relation_type 0)');

        $none = ExerciseLib::getStudentStatsByQuestion($q + 1000000, $quiz, $code, 0);
        $this->assertNull($none['max']);
    }

    public function testGetCountOfAnswersCountsStudentsChoicesInBaseCourse(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_count_answers');
        $s1 = $this->rawUser('cnt_s1');
        $s2 = $this->rawUser('cnt_s2');
        $tutor = $this->rawUser('cnt_tutor');
        $this->subscribe($s1, 1);
        $this->subscribe($s2, 1);
        $this->subscribe($tutor, 1, STUDENT, 2); // relation_type 2 = HRM-like, excluded
        foreach ([[$s1, '7'], [$s2, '7'], [$s2, '8'], [$tutor, '7']] as [$uid, $ans]) {
            $exe = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $uid]);
            $this->attempt($exe, $uid, $q, $ans);
        }
        $incomplete = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $s1, 'status' => 'incomplete']);
        $this->attempt($incomplete, $s1, $q, '7');
        $code = $this->getCourse(1)->getCode();

        $this->assertSame(2, ExerciseLib::getCountOfAnswers(7, $q, $quiz, $code, 0));
        $this->assertSame(1, ExerciseLib::getCountOfAnswers(8, $q, $quiz, $code, 0));
        $this->assertSame(0, ExerciseLib::getCountOfAnswers(9, $q, $quiz, $code, 0));
        $this->assertSame(3, ExerciseLib::getCountOfAnswers(7, $q, $quiz, $code, 0, FILL_IN_BLANKS), 'fill-in-blanks: every student answer row, whatever the answer id');
    }

    public function testGetCountOfAnswersInASessionIsBrokenByTheMissingAttemptCourseColumn(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_count_answers_sess');
        $sid = $this->rawSession('count_sess');
        $code = $this->getCourse(1)->getCode();

        // PINNED QUIRK: the session branch joins on `a.c_id` (track_e_attempt has no c_id): always throws.
        $this->expectException(NotAllowedException::class);
        ExerciseLib::getCountOfAnswers(7, $q, $quiz, $code, $sid);
    }

    public function testGetNumberStudentsAnswerHotspotCount(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_hot', HOT_SPOT);
        $s1 = $this->rawUser('hot_s1');
        $s2 = $this->rawUser('hot_s2');
        $this->subscribe($s1, 1);
        $this->subscribe($s2, 1);
        $e1 = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $s1]);
        $e1b = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $s1]);
        $e2 = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $s2]);
        $this->hotspot($e1, $s1, $q, 1, 1);
        $this->hotspot($e1b, $s1, $q, 1, 1); // same student twice: counted once (DISTINCT)
        $this->hotspot($e2, $s2, $q, 1, 0);  // not correct
        $this->hotspot($e2, $s2, $q, 2, 1);
        $code = $this->getCourse(1)->getCode();

        $this->assertSame(1, ExerciseLib::getNumberStudentsAnswerHotspotCount(1, $q, $quiz, $code, 0));
        $this->assertSame(1, ExerciseLib::getNumberStudentsAnswerHotspotCount(2, $q, $quiz, $code, 0));
        $this->assertSame(0, ExerciseLib::getNumberStudentsAnswerHotspotCount(3, $q, $quiz, $code, 0));
    }

    public function testLegacyPerAnswerCountersAreBrokenByTheMissingAttemptCourseColumn(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_legacy_counter');
        $code = $this->getCourse(1)->getCode();

        // PINNED QUIRK (no caller anywhere): both join track_e_attempt on `a.c_id`, a column it does not have.
        try {
            ExerciseLib::get_number_students_answer_count(1, $q, $quiz, $code, 0);
            $this->fail('expected unknown column');
        } catch (NotAllowedException) {
            $this->addToAssertionCount(1);
        }
        $this->expectException(NotAllowedException::class);
        ExerciseLib::get_number_students_question_with_answer_count($q, $quiz, $code, 0);
    }

    // ---------------------------------------------------------------- deleting and fixing attempts

    public function testDeleteExerciseAttemptRemovesItsAnswersAndTrackingOnly(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_del_attempt');
        $victim = $this->exe(['exe_exo_id' => $quiz]);
        $keeper = $this->exe(['exe_exo_id' => $quiz]);
        $this->attempt($victim, 1, $q, 'v');
        $this->attempt($keeper, 1, $q, 'k');
        $this->hotspot($victim, 1, $q, 1, 1);
        $this->qualify($victim, 1, 1);

        ExerciseLib::deleteExerciseAttempt($victim);

        $this->assertSame(0, $this->cnt('track_e_exercises', 'exe_id = ?', [$victim]));
        $this->assertSame(0, $this->cnt('track_e_attempt', 'exe_id = ?', [$victim]));
        $this->assertSame(1, $this->cnt('track_e_exercises', 'exe_id = ?', [$keeper]));
        $this->assertSame(1, $this->cnt('track_e_attempt', 'exe_id = ?', [$keeper]));
        // PINNED QUIRK: hotspot answers of the deleted attempt are left orphaned (no FK on track_e_hotspot);
        // the teacher qualifications only vanish through the ON DELETE CASCADE of their foreign key.
        $this->assertSame(1, $this->cnt('track_e_hotspot', 'hotspot_exe_id = ?', [$victim]));
        $this->assertSame(0, $this->cnt('track_e_attempt_qualify', 'exe_id = ?', [$victim]));
        $this->assertSame(2, $this->cnt('track_e_default', "default_event_type = 'exe_attempt_deleted' AND default_value IN (?, ?)", [(string) $victim, $victim.'-1']));
    }

    public function testDeleteExerciseAttemptIgnoresUnknownIds(): void
    {
        $this->ctx();
        $before = $this->cnt('track_e_exercises', '1 = 1');
        ExerciseLib::deleteExerciseAttempt(0);
        ExerciseLib::deleteExerciseAttempt(999999999);
        $this->assertSame($before, $this->cnt('track_e_exercises', '1 = 1'));
    }

    public function testUpdateAttemptDataTrackingRewritesOnlyWhenTheListChanged(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $exe = $this->exe(['exe_exo_id' => $quiz, 'data_tracking' => '5,6,7']);
        $other = $this->exe(['exe_exo_id' => $quiz, 'data_tracking' => '9']);
        $info = ['data_tracking' => '5,6,7'];

        ExerciseLib::updateAttemptDataTrackingIfNeeded($exe, [5, 6, 7], $info);
        $this->assertSame('5,6,7', $this->conn->fetchOne('SELECT data_tracking FROM track_e_exercises WHERE exe_id = ?', [$exe]));

        ExerciseLib::updateAttemptDataTrackingIfNeeded($exe, ['5', '7', 'x'], $info);
        $this->assertSame('5,7,0', $this->conn->fetchOne('SELECT data_tracking FROM track_e_exercises WHERE exe_id = ?', [$exe]), 'ids are int-cast');
        $this->assertSame('5,7,0', $info['data_tracking']);
        $this->assertSame('9', $this->conn->fetchOne('SELECT data_tracking FROM track_e_exercises WHERE exe_id = ?', [$other]));

        ExerciseLib::updateAttemptDataTrackingIfNeeded(0, [1], $info);
        $this->assertSame('5,7,0', $info['data_tracking'], 'exe 0: untouched');
    }

    // ---------------------------------------------------------------- ranking

    public function testExerciseResultsInRankingOrdersByBestScoreThenEarliestDate(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $u2 = $this->rawUser('rank_u2');
        $u3 = $this->rawUser('rank_u3');
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1, 'score' => 5, 'max_score' => 10, 'exe_date' => '2020-03-01 10:00:00']);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1, 'score' => 8, 'max_score' => 10, 'exe_date' => '2020-03-02 10:00:00']);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u2, 'score' => 8, 'max_score' => 10, 'exe_date' => '2020-03-01 09:00:00']);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u3, 'score' => 2, 'max_score' => 10]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u3, 'score' => 10, 'max_score' => 10, 'status' => 'incomplete']);

        $data = ExerciseLib::exerciseResultsInRanking($quiz, 1);

        $this->assertSame([$u2, 1, $u3], array_map(static fn ($r) => $r[1]->getId(), $data));
        $this->assertSame([1, 1, 2], array_column($data, 0), 'ties share a position');
        $this->assertSame([], ExerciseLib::exerciseResultsInRanking($quiz, 2));
    }

    public function testBestAttemptByUserPicksTheHighestRatioAndTheLatestOnTies(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $this->exe(['exe_exo_id' => $quiz, 'score' => 1, 'max_score' => 2]);
        $best = $this->exe(['exe_exo_id' => $quiz, 'score' => 3, 'max_score' => 6]);
        $this->exe(['exe_exo_id' => $quiz, 'score' => 0, 'max_score' => 0]);
        $this->exe(['exe_exo_id' => $quiz, 'score' => 9, 'max_score' => 10, 'orig_lp_id' => 4, 'orig_lp_item_id' => 5]);

        $attempt = ExerciseLib::get_best_attempt_by_user(1, $quiz, 1, 0);

        // PINNED QUIRK: ties keep the LAST attempt (>=), attempts made through a learning path are ignored.
        $this->assertSame($best, (int) $attempt['exe_id']);
        $this->assertSame([], ExerciseLib::get_best_attempt_by_user(2, $quiz, 1, 0));
    }

    // ---------------------------------------------------------------- doctrine-based helpers

    public function testCountAnsweredQuestionsByUserAfterTime(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_after');
        $u = $this->rawUser('after');
        $exe = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u]);
        foreach ([['2020-05-01 10:00:00', 1], ['2020-05-02 10:00:00', 1], ['2020-04-01 10:00:00', 1]] as [$tms, $c]) {
            $this->conn->insert('track_e_attempt', [
                'exe_id' => $exe, 'user_id' => $u, 'question_id' => $q, 'answer' => 'x', 'teacher_comment' => '',
                'marks' => 1, 'position' => 1, 'tms' => $tms, 'seconds_spent' => 0,
            ]);
        }

        // PINNED QUIRK: the DQL filters TrackEAttempt on userId/cId/sessionId, none of which are mapped fields (the
        // entity has user and trackExercise associations): always a QueryException. Its only caller,
        // isQuestionsLimitPerDayReached(), therefore throws whenever a course sets quiz_question_limit_per_day.
        $this->expectException(\Doctrine\ORM\Query\QueryException::class);
        ExerciseLib::countAnsweredQuestionsByUserAfterTime(new \DateTime('2020-04-15 00:00:00', new \DateTimeZone('UTC')), $u, 1, 0);
    }

    public function testGetExerciseTitleById(): void
    {
        $quiz = $this->quiz(['title' => 'qs_title_by_id']);
        $this->assertSame('qs_title_by_id', ExerciseLib::getExerciseTitleById($quiz));
        $this->expectException(\Doctrine\ORM\NoResultException::class);
        ExerciseLib::getExerciseTitleById(999999999);
    }

    public function testRecalculateResultRefusesMismatchedUserQuizOrEmptyTracking(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $noQuestions = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1, 'data_tracking' => '']);

        $this->assertNull(ExerciseLib::recalculateResult($noQuestions, 1, $quiz, 1));
        $this->assertNull(ExerciseLib::recalculateResult($noQuestions, 2, $quiz, 1), 'other user');
        $this->assertNull(ExerciseLib::recalculateResult($noQuestions, 1, $quiz + 1, 1), 'other quiz');
        $this->assertNull(ExerciseLib::recalculateResult(999999999, 1, $quiz, 1));
        $this->assertNull(ExerciseLib::recalculateResult($noQuestions, 0, $quiz, 1));
    }

    public function testFindAttemptExeIdsForExportFiltersCourseQuizSessionAndDates(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $sid = $this->rawSession('export_sess');
        $old = $this->exe(['exe_exo_id' => $quiz, 'exe_date' => '2020-01-01 10:00:00']);
        $mid = $this->exe(['exe_exo_id' => $quiz, 'exe_date' => '2020-02-01 10:00:00']);
        $new = $this->exe(['exe_exo_id' => $quiz, 'exe_date' => '2020-03-01 10:00:00']);
        $inSession = $this->exe(['exe_exo_id' => $quiz, 'exe_date' => '2020-02-15 10:00:00', 'session_id' => $sid]);
        $this->exe(['exe_exo_id' => $quiz, 'c_id' => 2]);
        $em = Container::getEntityManager();
        $course = $em->getRepository(Course::class)->find(1);
        $quizEntity = $em->getRepository(\Chamilo\CourseBundle\Entity\CQuiz::class)->find($quiz);
        $sessionEntity = $em->getRepository(SessionEntity::class)->find($sid);
        $method = new ReflectionMethod(ExerciseLib::class, 'findAttemptExeIdsForExport');

        $this->assertSame([$new, $mid, $old], $method->invoke(null, $course, $quizEntity, null, []), 'base course: newest first');
        $this->assertSame([$inSession], $method->invoke(null, $course, $quizEntity, $sessionEntity, []));
        $this->assertSame([$mid], $method->invoke(null, $course, $quizEntity, null, ['start_date' => '2020-01-15', 'end_date' => '2020-02-15']));
    }

    public function testFindAttemptExeIdsFallbackSqlMatchesTheBaseCourseRows(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $sid = $this->rawSession('fallback_sess');
        $a = $this->exe(['exe_exo_id' => $quiz, 'exe_date' => '2020-01-01 10:00:00']);
        $b = $this->exe(['exe_exo_id' => $quiz, 'exe_date' => '2020-02-01 10:00:00']);
        $this->exe(['exe_exo_id' => $quiz, 'session_id' => $sid]);
        $method = new ReflectionMethod(ExerciseLib::class, 'findAttemptExeIdsFallbackSql');

        $this->assertSame([$b, $a], $method->invoke(null, 1, $quiz, []));
        $this->assertSame([$a], $method->invoke(null, 1, $quiz, ['end_date' => '2020-01-15']));
        $this->assertSame([$b], $method->invoke(null, 1, $quiz, ['start_date' => '2020-01-15']));
        $this->assertSame([], $method->invoke(null, 2, $quiz, []));
    }

    public function testGetUserQuestionScoreGlobalHotspotCombinationReadsTheTrackedHotspots(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs_hot_comb', HOT_SPOT_COMBINATION, 4.0);
        $exe = $this->exe(['exe_exo_id' => $quiz]);
        $this->hotspot($exe, 1, $q, 1, 1);
        $this->hotspot($exe, 1, $q, 2, 1);
        $this->hotspot($exe, 1, $q, 3, 0);

        // Score is all-or-nothing: every option of the question must be correct.
        $this->assertSame(4.0, ExerciseLib::getUserQuestionScoreGlobal(HOT_SPOT_COMBINATION, [], $exe, $q, 4.0, [], 2));
        $this->assertSame(0.0, ExerciseLib::getUserQuestionScoreGlobal(HOT_SPOT_COMBINATION, [], $exe, $q, 4.0, [], 3));
        $this->assertSame(0.0, ExerciseLib::getUserQuestionScoreGlobal(HOT_SPOT_COMBINATION, [], $exe, $q, 4.0, [], 0));
    }

    public function testShowTestsWhereQuestionIsUsedListsOtherQuizzesButNotTheExcludedOne(): void
    {
        $this->ctx();
        $q = $this->question('qs_used');
        $here = $this->quiz(['title' => 'qs_used_here']);
        $there = $this->quiz(['title' => 'qs_used_there']);
        foreach ([$here, $there] as $i => $quizId) {
            $this->conn->insert('c_quiz_rel_question', ['quiz_id' => $quizId, 'question_id' => $q, 'question_order' => $i + 1]);
        }

        ob_start();
        ExerciseLib::showTestsWhereQuestionIsUsed($q, $here);
        $html = (string) ob_get_clean();
        $this->assertStringContainsString('qs_used_there', $html);
        $this->assertStringNotContainsString('qs_used_here', $html);

        ob_start();
        ExerciseLib::showTestsWhereQuestionIsUsed($q, $there);
        ob_end_clean();

        ob_start();
        ExerciseLib::showTestsWhereQuestionIsUsed($this->question('qs_unused'));
        $this->assertSame('', trim((string) ob_get_clean()));
    }

    public function testGetEmailContentForAttemptReportsUnknownAttempts(): void
    {
        $this->ctx();
        $content = ExerciseLib::getEmailContentForAttempt(999999999);
        $this->assertSame('', $content['to']);
        $this->assertSame('No exercise info found', $content['subject']);
    }
}
