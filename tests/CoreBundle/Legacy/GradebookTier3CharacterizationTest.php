<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use GradebookUtils;
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
        $this->assertSame(['id', 'user_id', 'gradebook_id', 'comment', 'created_at', 'updated_at'], array_slice(array_keys($row), 0, 6));
        $this->assertSame(12, \count($row));
        $this->assertSame($row['id'], $row[0]);
        $this->assertSame($row['comment'], $row[3]);
        $this->assertSame([], GradebookUtils::getComment($cat, 2));
    }
}
