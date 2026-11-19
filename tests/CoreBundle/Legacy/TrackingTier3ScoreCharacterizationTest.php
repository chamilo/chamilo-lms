<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;
use Tracking;

/**
 * Wave 3 characterization tests for the exercise average of Tracking::get_avg_student_exercise_score():
 * the variants (several students, base-context filter, learning path name) the other tests leave open.
 */
final class TrackingTier3ScoreCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE = 1;

    private Connection $conn;
    private int $seq = 0;
    private int $creator = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new Session(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/mySpace/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        Database::setManager($container->get('doctrine')->getManager());
        ChamiloSession::erase('system_timezone');
        $this->creator = $this->user('t3s_creator');
    }

    public function testAverageOverSeveralStudentsIsDividedByAttemptsThenStudents(): void
    {
        $s1 = $this->user('t3s_s1');
        $s2 = $this->user('t3s_s2');
        $other = $this->user('t3s_other');
        $quiz = $this->quiz(self::COURSE);
        $code = api_get_course_info_by_id(self::COURSE)['code'];
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10);
        $this->exercise($s2, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 10, 10);
        $this->exercise($other, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 0, 10);

        // sum(50 + 100) = 150 over 2 attempts = 75, then over 2 students = 37.5.
        $this->assertSame(37.5, Tracking::get_avg_student_exercise_score([$s1, $s2], $code, $quiz));
        // The student filter excludes the third student.
        $this->assertSame(50.0, Tracking::get_avg_student_exercise_score($s1, $code, $quiz));

        // An attempt tied to a learning path item (even with no learning path id) is not a plain attempt.
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-02 10:00:00', 10, 10, '', 0, 9);
        $this->assertSame(50.0, Tracking::get_avg_student_exercise_score($s1, $code, $quiz));
    }

    public function testSessionFilterKeepsTheBaseContextOnlyForSessionZero(): void
    {
        $s1 = $this->user('t3s_s1');
        $sessionId = $this->session();
        $quiz = $this->quiz(self::COURSE);
        $code = api_get_course_info_by_id(self::COURSE)['code'];
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10);
        $this->exercise($s1, self::COURSE, $sessionId, $quiz, '2001-02-02 10:00:00', 10, 10);

        // Session 0 keeps the NULL/0 attempt only, the session keeps its own, no filter keeps both.
        $this->assertSame(50.0, Tracking::get_avg_student_exercise_score($s1, $code, $quiz, 0));
        $this->assertSame(100.0, Tracking::get_avg_student_exercise_score($s1, $code, $quiz, $sessionId));
        $this->assertSame(75.0, Tracking::get_avg_student_exercise_score($s1, $code, $quiz, null));
    }

    public function testIntoLpReturnsTheAverageAndTheNameOfTheLearningPath(): void
    {
        $s1 = $this->user('t3s_s1');
        $quiz = $this->quiz(self::COURSE);
        $code = api_get_course_info_by_id(self::COURSE)['code'];
        $lpId = $this->lp(self::COURSE);
        $this->exercise($s1, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10, '', $lpId, 4);

        $this->assertSame([50.0, 't3s_lp'], Tracking::get_avg_student_exercise_score($s1, $code, $quiz, null, 1, 1));

        // No LP attempt: no learning path name.
        $other = $this->user('t3s_other');
        $this->exercise($other, self::COURSE, null, $quiz, '2001-02-01 10:00:00', 5, 10);
        $this->assertSame([50.0, null], Tracking::get_avg_student_exercise_score($other, $code, $quiz, null, 1, 1));
    }

    private function user(string $name): int
    {
        $name .= '_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'tc')",
            [$name, $name, $name.'@example.com', $name.'@example.com', UuidV4::v4()->toBinary(), $name]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status)
             VALUES (?, 0, 0, 0, 1, 0)',
            ['t3s_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function node(int $resourceTypeId, string $title): int
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 0, ?, '2001-01-01 00:00:00', '2001-01-01 00:00:00', 0, ?)",
            [$resourceTypeId, $this->creator, $title, 't3s-'.(++$this->seq), 't3s-'.$this->seq.'/', UuidV4::v4()->toBinary()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $nodeId, int $courseId): void
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, session_id, group_id, visibility, display_order,
                resource_type_group, created_at, updated_at)
             VALUES (?, ?, NULL, NULL, 2, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$nodeId, $courseId]
        );
    }

    private function quiz(int $courseId): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_quiz (title, type, random, random_answers, results_disabled, max_attempt, feedback_type,
                expired_time, propagate_neg, review_answers, random_by_category, display_category_name,
                hide_question_title, page_result_configuration, resource_node_id)
             VALUES ('t3s_quiz', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '{}', ?)",
            [$node = $this->node(19, 't3s_quiz')]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId);

        return $id;
    }

    private function lp(int $courseId): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on, use_max_score, resource_node_id)
             VALUES (1, 't3s_lp', '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', 1, ?)",
            [$node = $this->node(40, 't3s_lp')]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId);

        return $id;
    }

    private function exercise(
        int $userId,
        int $courseId,
        ?int $sessionId,
        int $quizId,
        string $date,
        float $score,
        float $maxScore,
        string $status = '',
        int $lpId = 0,
        int $lpItemId = 0
    ): void {
        $this->conn->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip,
                status, data_tracking, start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration,
                orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, ?, ?, ?, ?, ?, '127.0.0.1', ?, '', ?, 0, ?, ?, 0, 0, '')",
            [$userId, $courseId, $sessionId, $quizId, $date, $score, $maxScore, $status, $date, $lpId, $lpItemId]
        );
    }
}
