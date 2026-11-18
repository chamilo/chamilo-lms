<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\NoResultException;
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
final class ExerciseLibTier2CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    /**
     * @var int[]|null
     */
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

    /**
     * A quiz linked to the course through a live (or soft-deleted) resource_link on a spare node.
     */
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

    /**
     * @return int[] exe ids of the rows (key 'exe_id') returned by get_exam_results_data
     */
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

    // ---------------------------------------------------------------- pinned by the mutation pass

    public function testTrackInfoKeepsTheRawTypesOfBothTables(): void
    {
        $this->ctx();
        $quiz = $this->quiz(['title' => 'qs2_info']);
        $exe = $this->exe(['exe_exo_id' => $quiz, 'score' => 3, 'max_score' => 4, 'exe_duration' => 125, 'exe_user_id' => 1]);

        $info = ExerciseLib::get_exercise_track_exercise_info($exe);

        $this->assertSame($exe, $info['exe_id']);
        $this->assertSame($quiz, $info['iid']);
        $this->assertSame($quiz, $info['exe_exo_id']);
        $this->assertSame(1, $info['exe_user_id']);
        $this->assertSame(1, $info['c_id']);
        $this->assertSame(125, $info['exe_duration']);
        $this->assertSame(3.0, $info['score']);
        $this->assertIsString($info['exe_date']);
        $this->assertSame('qs2_info', $info['title']);
        $this->assertIsInt($info['type']);
    }

    public function testStudentStatsInASessionOnlyCountTheStudentsOfThatSession(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs2_stats_sess');
        $sid = $this->rawSession('stats_sess');
        $other = $this->rawSession('stats_sess_other');
        $in = $this->rawUser('ss_in');
        $coach = $this->rawUser('ss_coach');
        $inOther = $this->rawUser('ss_other');
        $this->subscribeInSession($in, 1, $sid);
        $this->subscribeInSession($coach, 1, $sid, SessionEntity::COURSE_COACH);
        $this->subscribeInSession($inOther, 1, $other);
        foreach ([[$in, $sid, 2.0], [$coach, $sid, 40.0], [$inOther, $sid, 60.0]] as [$uid, $sess, $mark]) {
            $this->attempt($this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $uid, 'session_id' => $sess]), $uid, $q, 'x', $mark);
        }
        $code = $this->getCourse(1)->getCode();

        $stats = ExerciseLib::getStudentStatsByQuestion($q, $quiz, $code, $sid, true);

        $this->assertEquals(2, $stats['max'], 'the coach and the student of another session are not counted');
        $this->assertEquals(60, ExerciseLib::getStudentStatsByQuestion($q, $quiz, $code, $sid)['max']);
    }

    public function testHotspotCountInASessionCountsTheSessionStudentsOnly(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $q = $this->question('qs2_hot_sess', HOT_SPOT);
        $sid = $this->rawSession('hot_sess');
        $other = $this->rawSession('hot_sess_other');
        $in = $this->rawUser('hs_in');
        $inOther = $this->rawUser('hs_other');
        $in2 = $this->rawUser('hs_in2');
        $this->subscribeInSession($in, 1, $sid);
        $this->subscribeInSession($in2, 1, $sid);
        $this->subscribeInSession($inOther, 1, $other);
        foreach ([[$in, $sid], [$in2, $sid], [$inOther, $sid]] as [$uid, $sess]) {
            $exe = $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $uid, 'session_id' => $sess]);
            $this->hotspot($exe, $uid, $q, 1, 1);
        }
        $code = $this->getCourse(1)->getCode();

        $this->assertSame(2, ExerciseLib::getNumberStudentsAnswerHotspotCount(1, $q, $quiz, $code, $sid));
        $this->assertSame(0, ExerciseLib::getNumberStudentsAnswerHotspotCount(1, $q, $quiz, $code, 0));
    }

    public function testResultsCountOfWrongStudentsCountsDistinctUsersWithAWrongScore(): void
    {
        $this->ctx();
        $quiz = $this->quiz();
        $u2 = $this->rawUser('wrong_u2');
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u2, 'score' => 1, 'max_score' => 5]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u2, 'score' => 2, 'max_score' => 5]);
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => 1, 'score' => 5, 'max_score' => 5]);
        $u3 = $this->rawUser('wrong_u3');
        $this->exe(['exe_exo_id' => $quiz, 'exe_user_id' => $u3, 'score' => 5, 'max_score' => 5]);

        $this->assertSame(1, ExerciseLib::getExerciseResultsCount('wrong_student', 1, $quiz));
        $this->assertSame(2, ExerciseLib::getExerciseResultsCount('wrong', 1, $quiz));
        $this->assertSame(1, ExerciseLib::getExerciseResultsCount('correct_student', 1, $quiz) - 1);
    }
}
