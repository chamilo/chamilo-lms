<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Evaluation;
use GradebookUtils;
use Result;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\UuidV4;

/**
 * Third characterization pass over the raw SQL of the gradebook back end: the parts of GradebookUtils
 * (public/main/gradebook/lib/GradebookUtils.php), Result (be/result.class.php) and Evaluation
 * (be/evaluation.class.php) that GradebookUtilsCharacterizationTest and the Category tests leave unpinned: the
 * exact row shapes the legacy callers read, the create-default-gradebook path, the attendance and forum
 * thread weight propagation, and the Result / Evaluation persistence and lookups.
 *
 * Fixtures (prefix g3_): raw INSERTs, users 1 (admin) and 2 (anonymous), courses 1 and 2.
 */
final class GradebookTier3CharacterizationTest extends KernelTestCase
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
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/gradebook/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        $_GET = [];
        $_POST = [];
        $this->context(1, 1);
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        ChamiloSession::erase('sid');
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('_course');
        $_GET = [];
        $_POST = [];
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function context(int $userId, ?int $courseId, int $sessionId = 0): void
    {
        ChamiloSession::write('_user', ['id' => $userId, 'user_id' => $userId]);
        if (null !== $courseId) {
            $code = 1 === $courseId ? 'AIACT' : 'USINGCHAMILO';
            ChamiloSession::write('_real_cid', $courseId);
            ChamiloSession::write('_cid', $code);
            ChamiloSession::write('_course', api_get_course_info($code));
        } else {
            ChamiloSession::erase('_real_cid');
            ChamiloSession::erase('_cid');
            ChamiloSession::erase('_course');
        }
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::write('gid', 0);
        ChamiloSession::erase('studentview');
        Container::getEntityManager()->clear();
        $user = $this->getUser(1 === $userId ? 'admin' : 'anon');
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }

    private function user(string $last, string $first): int
    {
        $name = 'g3_u_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname, official_code)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, ?, ?)",
            [$name, $name, $name.'@example.com', $name.'@example.com', UuidV4::v4()->toBinary(), $first, $last, 'OC'.$this->seq]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['g3_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function category(int $courseId, ?int $sessionId = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_category (c_id, session_id, user_id, title, weight, visible, locked, generate_certificates,
                is_requirement, calculation_mode)
             VALUES (?, ?, 1, ?, 100, 1, 0, 0, 0, 'weighted_average')",
            [$courseId, $sessionId, 'g3_cat_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $courseId, ?int $categoryId, int $type, int $refNodeId, float $weight = 10.0): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_link (ref_id, c_id, category_id, type, created_at, weight, visible, locked)
             VALUES (?, ?, ?, ?, '2001-01-01 00:00:00', ?, 1, 0)",
            [$refNodeId, $courseId, $categoryId, $type, $weight]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function freeNode(int $offset): int
    {
        return (int) $this->conn->fetchOne('SELECT id FROM resource_node ORDER BY id LIMIT 1 OFFSET '.$offset);
    }

    private function certificate(int $catId, int $userId): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, path_certificate, publish)
             VALUES (?, ?, 80, '2010-03-04 05:06:07', ?, 1)",
            [$catId, $userId, 'g3_'.(++$this->seq).'.html']
        );

        return (int) $this->conn->lastInsertId();
    }

    // ---------------------------------------------------------------- GradebookUtils

    public function testIsResourceInCourseGradebookReturnsTheLinkRowOfThatCourseAndType(): void
    {
        // c_quiz iid 1 is the demo quiz whose resource node is the gradebook_link.ref_id.
        $quizNode = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz WHERE iid = 1');
        $cat = $this->category(1);
        $link = $this->link(1, $cat, 1, $quizNode, 12.0);

        $row = GradebookUtils::isResourceInCourseGradebook(1, 1, 1, null);

        $this->assertSame(
            ['id', 'ref_id', 'c_id', 'category_id', 'type', 'created_at', 'weight', 'visible', 'locked', 'best_score',
                'average_score', 'score_weight', 'user_score_list', 'min_score', 'points_one', 'points_many'],
            array_keys($row)
        );
        $this->assertEquals($link, $row['id']);
        $this->assertEquals($quizNode, $row['ref_id']);
        $this->assertEquals(1, $row['c_id']);
        $this->assertEquals($cat, $row['category_id']);
        $this->assertEquals(12.0, $row['weight']);
        $this->assertEquals(1, $row['visible']);
        $this->assertNull($row['best_score']);
        $this->assertSame('2001-01-01 00:00:00', $row['created_at']);
        $this->assertSame(
            $this->conn->fetchAssociative('SELECT * FROM gradebook_link WHERE id = ?', [$link]),
            $row
        );
        // Another course, or another type: not in that gradebook.
        $this->assertSame([], GradebookUtils::isResourceInCourseGradebook(2, 1, 1, null));
        $this->assertSame([], GradebookUtils::isResourceInCourseGradebook(1, 2, 1, null));
    }

    public function testGetCourseIdByLinkIdIsNullWhenNothingMatches(): void
    {
        $this->assertNull(@GradebookUtils::get_course_id_by_link_id(999999));
    }

    public function testCreateDefaultCourseGradebookCreatesTheCategoryOnceAndThenReusesTheFirstOne(): void
    {
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_category WHERE c_id = 2'));

        $this->context(1, 2);
        $first = GradebookUtils::create_default_course_gradebook(2);

        $this->assertGreaterThan(0, $first);
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_category WHERE c_id = 2'));
        $this->assertSame('2', $this->conn->fetchOne('SELECT title FROM gradebook_category WHERE id = ?', [$first]));
        $this->assertSame($first, GradebookUtils::create_default_course_gradebook(2));

        // A second category of the same course: the lowest id is the one returned (ORDER BY id).
        $second = $this->category(2);
        $this->assertGreaterThan($first, $second);
        $this->assertSame($first, GradebookUtils::create_default_course_gradebook(2));
        $this->assertSame(2, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_category WHERE c_id = 2'));
    }

    public function testCreateDefaultCourseGradebookIgnoresTheCategoriesOfOtherCoursesAndSessions(): void
    {
        $session = $this->session();
        // A session category of course 2 and a base category of course 1: neither answers for the base course 2.
        $inSession = $this->category(2, $session);
        $this->category(1);

        $this->context(1, 2);
        $id = GradebookUtils::create_default_course_gradebook(2);

        $this->assertNotSame($inSession, $id);
        $this->assertNull($this->conn->fetchOne('SELECT session_id FROM gradebook_category WHERE id = ?', [$id]) ?: null);

        // In the session: the session category is the one found, the base one is not.
        $this->context(1, 2, $session);
        $this->assertSame($inSession, GradebookUtils::create_default_course_gradebook(2));
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_category');
        $this->assertSame($inSession, GradebookUtils::create_default_course_gradebook(2));
        $this->assertSame($before, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_category'));
    }

    public function testCreateDefaultCourseGradebookNamesTheSessionCategoryAfterTheSession(): void
    {
        $session = $this->session();
        $this->context(1, 2, $session);

        $id = GradebookUtils::create_default_course_gradebook(2);

        $row = $this->conn->fetchAssociative('SELECT title, session_id FROM gradebook_category WHERE id = ?', [$id]);
        $this->assertSame($session, (int) $row['session_id']);
        $this->assertStringStartsWith('2 - ', $row['title']);
    }

    public function testUpdateLinkWeightPropagatesToTheAttendanceOfTheLink(): void
    {
        $node = $this->freeNode(30);
        $this->conn->executeStatement(
            "INSERT INTO c_attendance (title, active, attendance_qualify_max, attendance_weight, locked, require_unique, resource_node_id)
             VALUES ('g3_att', 1, 0, 1.5, 0, 0, ?)",
            [$node]
        );
        $iid = (int) $this->conn->lastInsertId();
        $otherNode = $this->freeNode(31);
        $this->conn->executeStatement(
            "INSERT INTO c_attendance (title, active, attendance_qualify_max, attendance_weight, locked, require_unique, resource_node_id)
             VALUES ('g3_att2', 1, 0, 1.5, 0, 0, ?)",
            [$otherNode]
        );
        $otherIid = (int) $this->conn->lastInsertId();
        $link = $this->link(1, $this->category(1), LINK_ATTENDANCE, $node, 10.0);

        GradebookUtils::updateLinkWeight($link, 'g3_att', '7.5');

        $this->assertSame(7.5, (float) $this->conn->fetchOne('SELECT weight FROM gradebook_link WHERE id = ?', [$link]));
        $this->assertSame(7.5, (float) $this->conn->fetchOne('SELECT attendance_weight FROM c_attendance WHERE iid = ?', [$iid]));
        $this->assertSame(1.5, (float) $this->conn->fetchOne('SELECT attendance_weight FROM c_attendance WHERE iid = ?', [$otherIid]));
    }

    public function testUpdateLinkWeightPropagatesToTheForumThreadOfTheLink(): void
    {
        $node = $this->freeNode(32);
        $this->conn->executeStatement(
            "INSERT INTO c_forum_thread (title, thread_date, thread_replies, thread_views, thread_sticky, locked,
                thread_qualify_max, thread_weight, thread_peer_qualify, resource_node_id)
             VALUES ('g3_thr', '2001-01-01 00:00:00', 0, 0, 0, 0, 10, 2.5, 0, ?)",
            [$node]
        );
        $iid = (int) $this->conn->lastInsertId();
        $otherNode = $this->freeNode(33);
        $this->conn->executeStatement(
            "INSERT INTO c_forum_thread (title, thread_date, thread_replies, thread_views, thread_sticky, locked,
                thread_qualify_max, thread_weight, thread_peer_qualify, resource_node_id)
             VALUES ('g3_thr2', '2001-01-01 00:00:00', 0, 0, 0, 0, 10, 2.5, 0, ?)",
            [$otherNode]
        );
        $otherIid = (int) $this->conn->lastInsertId();
        $link = $this->link(1, $this->category(1), LINK_FORUM_THREAD, $node, 10.0);

        GradebookUtils::updateLinkWeight($link, 'g3_thr', '6.25');

        $this->assertSame(6.25, (float) $this->conn->fetchOne('SELECT weight FROM gradebook_link WHERE id = ?', [$link]));
        $this->assertSame(6.25, (float) $this->conn->fetchOne('SELECT thread_weight FROM c_forum_thread WHERE iid = ?', [$iid]));
        $this->assertSame(2.5, (float) $this->conn->fetchOne('SELECT thread_weight FROM c_forum_thread WHERE iid = ?', [$otherIid]));
    }

    public function testCertificateListsKeepTheRowShapeOfFetchArray(): void
    {
        $cat = $this->category(1);
        $u = $this->user('G3last', 'G3first');
        $cert = $this->certificate($cat, $u);

        $byCategory = GradebookUtils::get_list_users_certificates($cat);
        $this->assertCount(1, $byCategory);
        $row = $byCategory[0];
        // fetch_array(): the named keys followed by the same values under numeric keys.
        $this->assertSame(['user_id', 'lastname', 'firstname', 'username', 'created_at', 0, 1, 2, 3, 4], array_keys($row));
        $this->assertEquals($u, $row['user_id']);
        $this->assertSame('G3last', $row['lastname']);
        $this->assertSame('G3first', $row['firstname']);
        $this->assertSame('2010-03-04 05:06:07', $row['created_at']);
        $this->assertSame($row['username'], $row[3]);
        $this->assertSame($row['created_at'], $row[4]);
        $this->assertSame($row['user_id'], $row[0]);

        $byUser = GradebookUtils::get_list_gradebook_certificates_by_user_id($u);
        $this->assertCount(1, $byUser);
        $row = $byUser[0];
        $this->assertSame(
            ['score_certificate', 'created_at', 'path_certificate', 'cat_id', 'user_id', 'id', 'publish', 0, 1, 2, 3, 4, 5, 6],
            array_keys($row)
        );
        $this->assertEquals(80, $row['score_certificate']);
        $this->assertSame('2010-03-04 05:06:07', $row['created_at']);
        $this->assertSame($cat.'', (string) $row['cat_id']);
        $this->assertEquals($cert, $row['id']);
        $this->assertEquals(1, $row['publish']);
        $this->assertSame($row['id'], $row[5]);
        $this->assertSame($row['publish'], $row[6]);
    }

    public function testGetCommentReturnsTheRowWithBothKeyKinds(): void
    {
        $cat = $this->category(1);
        GradebookUtils::saveComment($cat, 1, 'g3 comment');

        $row = GradebookUtils::getComment($cat, 1);

        $this->assertSame('g3 comment', $row['comment']);
        $this->assertSame(['id', 'user_id', 'gradebook_id', 'comment', 'created_at', 'updated_at'], \array_slice(array_keys($row), 0, 6));
        $this->assertSame(12, \count($row));
        $this->assertSame($row['id'], $row[0]);
        $this->assertSame($row['comment'], $row[3]);
        $this->assertSame([], GradebookUtils::getComment($cat, 2));
    }

    // ---------------------------------------------------------------- Result

    private function evaluation(?int $courseId = 1, ?int $categoryId = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_evaluation (c_id, category_id, title, created_at, weight, max, visible, type, locked)
             VALUES (?, ?, ?, '2001-01-01 00:00:00', 10, 20, 1, 'evaluation', 0)",
            [$courseId, $categoryId, 'g3_eval_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function subscribe(int $userId, int $courseId, int $status = 5): void
    {
        $this->conn->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, status, relation_type, sort, user_course_cat, is_tutor, progress)
             VALUES (?, ?, ?, 0, 0, 0, 0, 0)',
            [$userId, $courseId, $status]
        );
    }

    private function resultRow(int $evaluationId, int $userId, ?float $score, string $createdAt = '2005-06-07 08:09:10'): int
    {
        $this->conn->executeStatement(
            'INSERT INTO gradebook_result (evaluation_id, user_id, score, created_at) VALUES (?, ?, ?, ?)',
            [$evaluationId, $userId, $score, $createdAt]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function resultsOf(int $evaluationId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT user_id, score, created_at FROM gradebook_result WHERE evaluation_id = ? ORDER BY user_id',
            [$evaluationId]
        );
    }

    public function testResultLoadReturnsTheResultsOrderedByLastNameThenFirstName(): void
    {
        $eval = $this->evaluation();
        $zed = $this->user('Zzz', 'Amy');
        $abe = $this->user('Aaa', 'Bob');
        $abc = $this->user('Aaa', 'Abe');
        $zId = $this->resultRow($eval, $zed, 3.5);
        $this->resultRow($eval, $abe, null);
        $this->resultRow($eval, $abc, 7.0);

        $results = Result::load(null, null, $eval);

        $this->assertSame([$abc, $abe, $zed], array_map(static fn (Result $r) => $r->get_user_id(), $results));
        $last = $results[2];
        $this->assertSame($zId, $last->get_id());
        $this->assertSame($eval, $last->get_evaluation_id());
        $this->assertSame(3.5, $last->get_score());
        $this->assertSame(api_get_local_time('2005-06-07 08:09:10'), $last->get_date());
        $this->assertNull($results[1]->get_score());
        $this->assertSame(7.0, $results[0]->get_score());
    }

    public function testResultLoadFiltersByIdUserAndEvaluation(): void
    {
        $e1 = $this->evaluation();
        $e2 = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');
        $r1 = $this->resultRow($e1, $a, 1.0);
        $r2 = $this->resultRow($e1, $b, 2.0);
        $r3 = $this->resultRow($e2, $a, 3.0);

        $ids = static fn (array $l): array => array_map(static fn (Result $r) => $r->get_id(), $l);
        $this->assertSame([$r2], $ids(Result::load($r2)));
        $this->assertSame([$r1, $r3], $ids(Result::load(null, $a)));
        $this->assertSame([$r3], $ids(Result::load(null, $a, $e2)));
        $this->assertSame([], $ids(Result::load($r1, $b)));
        $this->assertSame([], $ids(Result::load($r1, $a, $e2)));
        $this->assertSame([$r1], $ids(Result::load($r1, $a, $e1)));
    }

    public function testResultLoadByEvaluationOnlyCreatesTheMissingRowsForTheStudentsOfTheCourse(): void
    {
        $eval = $this->evaluation();
        $has = $this->user('Has', 'Has');
        $missing = $this->user('Missing', 'Missing');
        $teacher = $this->user('Teach', 'Teach');
        $otherCourse = $this->user('Other', 'Other');
        foreach ([$has, $missing] as $u) {
            $this->subscribe($u, 1);
        }
        $this->subscribe($teacher, 1, 1);
        $this->subscribe($otherCourse, 2);
        $this->resultRow($eval, $has, 4.0);

        $results = Result::load(null, null, $eval);

        $rows = $this->resultsOf($eval);
        $this->assertSame([$has, $missing], array_map(static fn (array $r): int => (int) $r['user_id'], $rows));
        $this->assertNull($rows[1]['score']);
        $this->assertSame(4.0, (float) $rows[0]['score']);
        $this->assertEqualsWithDelta(time(), strtotime($rows[1]['created_at'].' UTC'), 120);
        $this->assertSame([$has, $missing], array_map(static fn (Result $r) => $r->get_user_id(), $results));

        // A second call adds nothing.
        Result::load(null, null, $eval);
        $this->assertCount(2, $this->resultsOf($eval));
    }

    public function testResultLoadWithoutAnyResultOnlyCreatesRowsWhenAskedTo(): void
    {
        $eval = $this->evaluation();
        $student = $this->user('Stud', 'Stud');
        $this->subscribe($student, 1);

        $this->assertSame([], Result::load(null, null, $eval));
        $this->assertSame([], $this->resultsOf($eval));

        $results = Result::load(null, null, $eval, true);

        $this->assertCount(1, $results);
        $this->assertSame([$student], array_map(static fn (array $r): int => (int) $r['user_id'], $this->resultsOf($eval)));
    }

    public function testResultLoadInASessionCreatesRowsForTheSessionStudentsOnly(): void
    {
        $eval = $this->evaluation();
        $inSession = $this->user('Sess', 'Sess');
        $coach = $this->user('Coach', 'Coach');
        $baseOnly = $this->user('Base', 'Base');
        $session = $this->session();
        foreach ([[$inSession, 0], [$coach, 2]] as [$u, $status]) {
            $this->conn->executeStatement(
                'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
                 VALUES (?, 1, ?, ?, 1, 0, 0)',
                [$session, $u, $status]
            );
        }
        $this->subscribe($baseOnly, 1);
        $this->context(1, 1, $session);

        Result::load(null, null, $eval, true);

        $this->assertSame([$inSession], array_map(static fn (array $r): int => (int) $r['user_id'], $this->resultsOf($eval)));
    }

    public function testResultAddStoresTheResultWithOrWithoutAScore(): void
    {
        $eval = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');

        $withScore = new Result();
        $withScore->set_user_id($a);
        $withScore->set_evaluation_id($eval);
        $withScore->set_date('2011-02-03 04:05:06');
        $withScore->set_score(12.5);
        $withScore->add();
        $noScore = new Result();
        $noScore->set_user_id($b);
        $noScore->set_evaluation_id($eval);
        $noScore->set_date('2012-03-04 05:06:07');
        $noScore->add();

        $rows = $this->resultsOf($eval);
        $this->assertSame([$a, $b], array_map(static fn (array $r): int => (int) $r['user_id'], $rows));
        $this->assertSame(12.5, (float) $rows[0]['score']);
        $this->assertSame('2011-02-03 04:05:06', $rows[0]['created_at']);
        $this->assertNull($rows[1]['score']);
        $this->assertSame('2012-03-04 05:06:07', $rows[1]['created_at']);
    }

    public function testResultAddResultLogCopiesTheCurrentResultIntoTheLog(): void
    {
        $eval = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');
        $ra = $this->resultRow($eval, $a, 9.5);
        $rb = $this->resultRow($eval, $b, null);

        (new Result())->addResultLog($a, $eval);
        (new Result())->addResultLog($b, $eval);

        $logs = $this->conn->fetchAllAssociative(
            'SELECT result_id, user_id, evaluation_id, score, created_at FROM gradebook_result_log WHERE evaluation_id = ? ORDER BY user_id',
            [$eval]
        );
        $this->assertCount(2, $logs);
        $this->assertSame([$ra, $a, $eval], [(int) $logs[0]['result_id'], (int) $logs[0]['user_id'], (int) $logs[0]['evaluation_id']]);
        $this->assertSame(9.5, (float) $logs[0]['score']);
        $this->assertSame([$rb, $b], [(int) $logs[1]['result_id'], (int) $logs[1]['user_id']]);
        $this->assertNull($logs[1]['score']);
        $this->assertEqualsWithDelta(time(), strtotime($logs[0]['created_at'].' UTC'), 120);
    }

    public function testResultSaveUpdatesByIdAndByUserAndEvaluationWhenThereIsNoId(): void
    {
        $eval = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');
        $ra = $this->resultRow($eval, $a, 1.0, '2005-06-07 08:09:10');
        $this->resultRow($eval, $b, 2.0);

        $byId = new Result();
        $byId->set_id($ra);
        $byId->set_user_id($a);
        $byId->set_evaluation_id($eval);
        $byId->set_score(15.25);
        $byId->save();

        $byPair = new Result();
        $byPair->set_user_id($b);
        $byPair->set_evaluation_id($eval);
        $byPair->save();

        $rows = $this->resultsOf($eval);
        $this->assertSame(15.25, (float) $rows[0]['score']);
        // The creation date is not rewritten.
        $this->assertSame('2005-06-07 08:09:10', $rows[0]['created_at']);
        // No score set: the stored score becomes NULL.
        $this->assertNull($rows[1]['score']);
    }

    public function testResultSaveWithAnIdMovesTheRowToTheGivenUserAndEvaluation(): void
    {
        $e1 = $this->evaluation();
        $e2 = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');
        $ra = $this->resultRow($e1, $a, 1.0);

        $result = new Result();
        $result->set_id($ra);
        $result->set_user_id($b);
        $result->set_evaluation_id($e2);
        $result->set_score(0.0);
        $result->save();

        $this->assertSame([], $this->resultsOf($e1));
        $rows = $this->resultsOf($e2);
        $this->assertSame([$b], array_map(static fn (array $r): int => (int) $r['user_id'], $rows));
        $this->assertSame(0.0, (float) $rows[0]['score']);
    }

    public function testResultDeleteRemovesOnlyThatResult(): void
    {
        $eval = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');
        $ra = $this->resultRow($eval, $a, 1.0);
        $this->resultRow($eval, $b, 2.0);
        $result = new Result();
        $result->set_id($ra);
        $result->set_evaluation_id($eval);

        $result->delete();

        $this->assertSame([$b], array_map(static fn (array $r): int => (int) $r['user_id'], $this->resultsOf($eval)));
    }

    public function testResultDeleteAlsoRemovesTheAttemptsWhenMultipleAttemptsAreAllowed(): void
    {
        $eval = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');
        $ra = $this->resultRow($eval, $a, 1.0);
        $rb = $this->resultRow($eval, $b, 2.0);
        foreach ([$ra, $rb] as $rid) {
            $this->conn->executeStatement(
                "INSERT INTO gradebook_result_attempt (result_id, comment, score, created_at, updated_at)
                 VALUES (?, 'g3', 1, '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
                [$rid]
            );
        }
        $manager = self::getContainer()->get(SettingsManager::class);
        $manager->updateSetting('gradebook.gradebook_multiple_evaluation_attempts', 'true');
        $result = new Result();
        $result->set_id($ra);
        $result->set_evaluation_id($eval);

        $result->delete();

        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_result_attempt WHERE result_id = ?', [$ra]));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_result_attempt WHERE result_id = ?', [$rb]));
    }

    public function testResultExistsLooksForTheUserAndEvaluationPair(): void
    {
        $e1 = $this->evaluation();
        $e2 = $this->evaluation();
        $a = $this->user('A', 'A');
        $this->resultRow($e1, $a, 1.0);

        $yes = new Result();
        $yes->set_user_id($a);
        $yes->set_evaluation_id($e1);
        $no = new Result();
        $no->set_user_id($a);
        $no->set_evaluation_id($e2);

        $this->assertTrue($yes->exists());
        $this->assertFalse($no->exists());
    }

    // ---------------------------------------------------------------- Evaluation

    private function evalRow(int $id): array
    {
        return $this->conn->fetchAssociative(
            'SELECT title, description, c_id, category_id, weight, max, visible, locked FROM gradebook_evaluation WHERE id = ?',
            [$id]
        );
    }

    public function testEvaluationSaveRewritesTheEditableColumnsAndLogsTheOldState(): void
    {
        $cat = $this->category(1);
        $other = $this->evaluation(1, $cat);
        $id = $this->evaluation(1, $cat);
        $this->conn->executeStatement(
            "UPDATE gradebook_evaluation SET title = 'g3 old', description = 'old description', weight = 5, max = 20, visible = 1 WHERE id = ?",
            [$id]
        );
        $eval = Evaluation::load($id)[0];
        $eval->set_name("g3 new 'quoted'");
        $eval->set_description('new description');
        $eval->set_weight('37.5');
        $eval->set_max(8.9);
        $eval->set_visible(0);

        $eval->save();

        $row = $this->evalRow($id);
        $this->assertSame("g3 new 'quoted'", $row['title']);
        $this->assertSame('new description', $row['description']);
        $this->assertSame(1, (int) $row['c_id']);
        $this->assertSame($cat, (int) $row['category_id']);
        $this->assertSame(37.5, (float) $row['weight']);
        // intval(): the maximum is truncated.
        $this->assertSame(8.0, (float) $row['max']);
        $this->assertSame(0, (int) $row['visible']);
        $this->assertSame('g3_eval_', substr($this->evalRow($other)['title'], 0, 8));
        $this->assertSame(10.0, (float) $this->evalRow($other)['weight']);
        // The history keeps the state before the update.
        $log = $this->conn->fetchAssociative(
            "SELECT title, description, weight, visible, type, user_id_log FROM gradebook_linkeval_log WHERE id_linkeval_log = ? AND type = 'evaluation'",
            [$id]
        );
        $this->assertSame('g3 old', $log['title']);
        $this->assertSame('old description', $log['description']);
        $this->assertSame(5, (int) $log['weight']);
        $this->assertSame(1, (int) $log['visible']);
        $this->assertSame(1, (int) $log['user_id_log']);
    }

    public function testEvaluationHasResultsOnlyCountsScoredResults(): void
    {
        $withScore = $this->evaluation();
        $onlyEmpty = $this->evaluation();
        $none = $this->evaluation();
        $a = $this->user('A', 'A');
        $b = $this->user('B', 'B');
        $this->resultRow($withScore, $a, 0.0);
        $this->resultRow($onlyEmpty, $b, null);
        $load = static fn (int $id): Evaluation => Evaluation::load($id)[0];

        $this->assertTrue($load($withScore)->has_results());
        $this->assertFalse($load($onlyEmpty)->has_results());
        $this->assertFalse($load($none)->has_results());
        $this->assertSame('evalnotempty', $load($withScore)->get_icon_name());
        $this->assertSame('evalempty', $load($onlyEmpty)->get_icon_name());
    }

    public function testEvaluationDeleteResultsAndDeleteTouchOnlyThatEvaluation(): void
    {
        $e1 = $this->evaluation();
        $e2 = $this->evaluation();
        $a = $this->user('A', 'A');
        $this->resultRow($e1, $a, 1.0);
        $this->resultRow($e2, $a, 2.0);

        Evaluation::load($e1)[0]->delete_results();

        $this->assertSame([], $this->resultsOf($e1));
        $this->assertCount(1, $this->resultsOf($e2));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_evaluation WHERE id = ?', [$e1]));

        Evaluation::load($e1)[0]->delete();
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_evaluation WHERE id = ?', [$e1]));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_evaluation WHERE id = ?', [$e2]));

        Evaluation::load($e2)[0]->delete_with_results();
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_evaluation WHERE id = ?', [$e2]));
        $this->assertSame([], $this->resultsOf($e2));
    }

    public function testEvaluationLockLocksAndUnlocksOnlyThatEvaluation(): void
    {
        $e1 = $this->evaluation();
        $e2 = $this->evaluation();

        Evaluation::load($e1)[0]->lock(1);
        $this->assertSame(1, (int) $this->evalRow($e1)['locked']);
        $this->assertSame(0, (int) $this->evalRow($e2)['locked']);

        Evaluation::load($e1)[0]->lock(0);
        $this->assertSame(0, (int) $this->evalRow($e1)['locked']);
    }

    public function testEvaluationAddEvaluationLogStoresTheCurrentWeight(): void
    {
        $id = $this->evaluation();
        $this->conn->executeStatement("UPDATE gradebook_evaluation SET weight = 42, description = 'g3 d', visible = 0 WHERE id = ?", [$id]);

        (new Evaluation())->addEvaluationLog($id);

        $rows = $this->conn->fetchAllAssociative(
            'SELECT title, description, weight, visible, type, user_id_log FROM gradebook_linkeval_log WHERE id_linkeval_log = ?',
            [$id]
        );
        $this->assertCount(1, $rows);
        $this->assertSame('g3 d', $rows[0]['description']);
        $this->assertSame(42, (int) $rows[0]['weight']);
        $this->assertSame(0, (int) $rows[0]['visible']);
        $this->assertSame('evaluation', $rows[0]['type']);
    }

    public function testEvaluationsWithResultForAStudentFilterByScoreVisibilityAndCategory(): void
    {
        $cat1 = $this->category(1);
        $cat2 = $this->category(1);
        $student = $this->user('S', 'S');
        $other = $this->user('O', 'O');
        $visible1 = $this->evaluation(1, $cat1);
        $visible2 = $this->evaluation(1, $cat2);
        $hidden = $this->evaluation(1, $cat1);
        $noScore = $this->evaluation(1, $cat1);
        $notMine = $this->evaluation(1, $cat1);
        $uncategorised = $this->evaluation(1, null);
        $this->conn->executeStatement('UPDATE gradebook_evaluation SET visible = 0 WHERE id = ?', [$hidden]);
        foreach ([$visible1, $visible2, $hidden, $uncategorised] as $e) {
            $this->resultRow($e, $student, 3.0);
        }
        $this->resultRow($noScore, $student, null);
        $this->resultRow($notMine, $other, 3.0);
        $ids = static fn (array $l): array => array_map(static fn (Evaluation $e): int => $e->get_id(), $l);

        // Editing rights: hidden evaluations are listed too. With no category given, uncategorised ones are left out.
        $asAdmin = $ids(Evaluation::get_evaluations_with_result_for_student($student));
        sort($asAdmin);
        $this->assertSame([$visible1, $visible2, $hidden], $asAdmin);
        $this->assertSame([$visible2], $ids(Evaluation::get_evaluations_with_result_for_student($student, $cat2)));
        $this->assertSame([], $ids(Evaluation::get_evaluations_with_result_for_student($other, $cat2)));

        // A user without rights does not see the hidden one.
        $this->context(2, 1);
        $asStudent = $ids(Evaluation::get_evaluations_with_result_for_student($student));
        sort($asStudent);
        $this->assertSame([$visible1, $visible2], $asStudent);
    }
}
