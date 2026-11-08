<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Evaluation;
use GradebookUtils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;

/**
 * Characterization of the raw-SQL helpers of GradebookUtils (public/main/gradebook/lib/GradebookUtils.php):
 * link/evaluation weights, the resources registered in a course gradebook, the students of a course or
 * session, the certificate listings and the per-user gradebook comment.
 *
 * Complements (does not duplicate) tests/CoreBundle/Api/GradebookLinkSecurityTest, which covers the API
 * Platform side; nothing under tests/ exercised this legacy class.
 *
 * LIVE: updateResourceFromCourseGradebook, remove_resource_from_course_gradebook,
 * isResourceInCourseGradebook, get_course_id_by_link_id, get_list_users_certificates,
 * get_list_gradebook_certificates_by_user_id, get_users_in_course, get_user_array_from_sql_result,
 * get_all_users, updateLinkWeight, updateEvaluationWeight, getComment/saveComment,
 * create_default_course_gradebook. DEAD (no caller, marked @deprecated in the source): find_students,
 * character_data. Doctrine-only / UI-only methods (icons, tables, PDF export, certificates in courses and
 * sessions) are out of scope: they hold no raw SQL of their own.
 *
 * The page-level access control (block_students() + api_protect_course_script()) cannot be driven from a
 * test; what is pinned here is which rows each helper touches, because that is what a Doctrine rewrite can
 * silently change. Fixtures (prefix gu_): raw INSERTs, users 1 (admin) and 2 (anonymous), courses 1 and 2.
 */
final class GradebookUtilsCharacterizationTest extends KernelTestCase
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
        $request = Request::create('/main/gradebook/gradebook_edit_all.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        \ChamiloSession::erase('_user');
        \ChamiloSession::erase('sid');
        \ChamiloSession::erase('_real_cid');
        $_GET = [];
    }

    protected function tearDown(): void
    {
        \ChamiloSession::erase('_user');
        \ChamiloSession::erase('sid');
        \ChamiloSession::erase('_real_cid');
        $_GET = [];
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- fixtures

    private function user(string $name, string $last, string $first, int $status = 5, int $active = 1): int
    {
        $name = 'gu_'.$name.'_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname, official_code)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', ?, ?, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, ?, ?)",
            [$name, $name, $name.'@example.com', $name.'@example.com', $status, $active, UuidV4::v4()->toBinary(), $first, $last, 'OC'.$this->seq]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function subscribe(int $userId, int $courseId, int $status = 5, int $relationType = 0): void
    {
        $this->conn->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, status, relation_type, sort, user_course_cat, is_tutor, progress)
             VALUES (?, ?, ?, ?, 0, 0, 0, 0)',
            [$userId, $courseId, $status, $relationType]
        );
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['gu_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function sessionSubscribe(int $sessionId, int $courseId, int $userId, int $status = 0): void
    {
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$sessionId, $courseId, $userId, $status]
        );
    }

    private function category(int $courseId, ?int $sessionId = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_category (c_id, session_id, title, weight, visible, locked, generate_certificates,
                is_requirement, calculation_mode)
             VALUES (?, ?, ?, 100, 1, 0, 0, 0, 'weighted')",
            [$courseId, $sessionId, 'gu_cat_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    /** gradebook_link.ref_id is a foreign key to resource_node: borrow an existing node (nothing is written to it). */
    private function resourceNode(): int
    {
        return (int) $this->conn->fetchOne('SELECT id FROM resource_node ORDER BY id LIMIT 1 OFFSET 5');
    }

    private function link(int $courseId, ?int $categoryId, float $weight = 10.0, int $type = 1): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_link (ref_id, c_id, category_id, type, created_at, weight, visible, locked)
             VALUES (?, ?, ?, ?, '2001-01-01 00:00:00', ?, 1, 0)",
            [$this->resourceNode(), $courseId, $categoryId, $type, $weight]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function evaluation(?int $courseId, ?int $categoryId, float $weight = 10.0): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_evaluation (c_id, category_id, title, created_at, weight, max, visible, type, locked)
             VALUES (?, ?, ?, '2001-01-01 00:00:00', ?, 20, 1, 'evaluation', 0)",
            [$courseId, $categoryId, 'gu_eval_'.(++$this->seq), $weight]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function certificate(int $catId, int $userId, string $createdAt = '2010-01-01 00:00:00'): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, path_certificate, publish)
             VALUES (?, ?, 80, ?, ?, 0)",
            [$catId, $userId, $createdAt, 'gu_'.(++$this->seq).'.html']
        );

        return (int) $this->conn->lastInsertId();
    }

    private function weight(string $table, int $id): float
    {
        return (float) $this->conn->fetchOne("SELECT weight FROM $table WHERE id = ?", [$id]);
    }

    // ---------------------------------------------------------------- resources in the course gradebook

    public function testUpdateResourceFromCourseGradebookChangesOnlyTheLinkOfThatCourse(): void
    {
        $cat = $this->category(1);
        $mine = $this->link(1, $cat, 10.0);
        $otherCourse = $this->link(2, $this->category(2), 10.0);

        $this->assertTrue(GradebookUtils::updateResourceFromCourseGradebook($mine, 1, 33.5));
        // The course id is part of the WHERE: a link of another course is not touched through course 1.
        $this->assertTrue(GradebookUtils::updateResourceFromCourseGradebook($otherCourse, 1, 77.0));

        $this->assertSame(33.5, $this->weight('gradebook_link', $mine));
        $this->assertSame(10.0, $this->weight('gradebook_link', $otherCourse));
    }

    public function testUpdateResourceFromCourseGradebookIgnoresEmptyIdsButStillReturnsTrue(): void
    {
        $link = $this->link(1, $this->category(1), 10.0);

        // PINNED: "false on error" in the docblock, but empty ids silently do nothing and return true.
        $this->assertTrue(GradebookUtils::updateResourceFromCourseGradebook(0, 1, 50.0));
        $this->assertTrue(GradebookUtils::updateResourceFromCourseGradebook($link, 0, 50.0));
        $this->assertSame(10.0, $this->weight('gradebook_link', $link));
    }

    public function testRemoveResourceFromCourseGradebookDeletesTheLinkById(): void
    {
        $cat = $this->category(1);
        $a = $this->link(1, $cat);
        $b = $this->link(1, $cat);

        $this->assertTrue(GradebookUtils::remove_resource_from_course_gradebook($a));

        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_link WHERE id = ?', [$a]));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_link WHERE id = ?', [$b]));
        $this->assertFalse(GradebookUtils::remove_resource_from_course_gradebook(0));
        $this->assertFalse(GradebookUtils::remove_resource_from_course_gradebook(null));
    }

    public function testRemoveResourceFromCourseGradebookDoesNotCheckTheCourse(): void
    {
        $other = $this->link(2, $this->category(2));

        // PINNED: only the link id is in the WHERE clause (no course/category scoping): the callers
        // (work, survey, learning path, attendance libs) are the only gate.
        $this->assertTrue(GradebookUtils::remove_resource_from_course_gradebook($other));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_link WHERE id = ?', [$other]));
    }

    public function testIsResourceInCourseGradebookReturnsAnEmptyArrayForMissingArguments(): void
    {
        $this->assertSame([], GradebookUtils::isResourceInCourseGradebook(0, 1, 1, 0));
        $this->assertSame([], GradebookUtils::isResourceInCourseGradebook(1, 0, 1, 0));
        $this->assertSame([], GradebookUtils::isResourceInCourseGradebook(1, 1, 0, 0));
        // A resource that does not exist has no resource node: not in any gradebook.
        $this->assertSame([], GradebookUtils::isResourceInCourseGradebook(1, 1, 999999, 0));
    }

    public function testGetCourseIdByLinkIdMatchesLinkIdsAndCategoryIds(): void
    {
        $cat = $this->category(2);
        $link = $this->link(2, $cat);

        $this->assertSame(2, (int) GradebookUtils::get_course_id_by_link_id($link));
    }

    public function testGetCourseIdByLinkIdAlsoMatchesTheCategoryIdColumn(): void
    {
        $cat = $this->category(1);
        $a = $this->link(1, $cat);
        // The same number is looked up in both columns (WHERE l.id = N OR l.category_id = N).
        // PINNED QUIRK: passing a category id answers with the course of the links in that category.
        $this->assertSame(1, (int) GradebookUtils::get_course_id_by_link_id($cat));
        unset($a);
    }

    // ---------------------------------------------------------------- weights

    public function testUpdateEvaluationWeightStoresTheWeightOfThatEvaluation(): void
    {
        $cat = $this->category(1);
        $a = $this->evaluation(1, $cat, 10.0);
        $b = $this->evaluation(1, $cat, 10.0);
        \ChamiloSession::write('_user', ['id' => 1]);

        GradebookUtils::updateEvaluationWeight($a, '42.5');

        $this->assertSame(42.5, $this->weight('gradebook_evaluation', $a));
        $this->assertSame(10.0, $this->weight('gradebook_evaluation', $b));
        $this->assertSame(
            1,
            (int) $this->conn->fetchOne("SELECT COUNT(*) FROM gradebook_linkeval_log WHERE id_linkeval_log = ? AND type = 'evaluation'", [$a])
        );
    }

    public function testUpdateLinkWeightStoresTheWeightAndKeepsALogRow(): void
    {
        $cat = $this->category(1);
        $a = $this->link(1, $cat, 10.0);
        $b = $this->link(1, $cat, 10.0);
        \ChamiloSession::write('_user', ['id' => 1]);

        GradebookUtils::updateLinkWeight($a, 'gu_renamed', '12.25');

        $this->assertSame(12.25, $this->weight('gradebook_link', $a));
        $this->assertSame(10.0, $this->weight('gradebook_link', $b));
        $log = $this->conn->fetchAssociative('SELECT title, type FROM gradebook_linkeval_log WHERE id_linkeval_log = ?', [$a]);
        $this->assertSame('gu_renamed', $log['title']);
    }

    // ---------------------------------------------------------------- comments

    public function testGetCommentReturnsFalseWhenThereIsNone(): void
    {
        $this->assertEmpty(GradebookUtils::getComment($this->category(1), 1));
    }

    public function testSaveCommentInsertsThenUpdatesTheSameRow(): void
    {
        $cat = $this->category(1);

        GradebookUtils::saveComment($cat, 1, 'first');
        $first = GradebookUtils::getComment($cat, 1);
        GradebookUtils::saveComment($cat, 1, 'second');
        $second = GradebookUtils::getComment($cat, 1);

        $this->assertSame('first', $first['comment']);
        $this->assertSame('second', $second['comment']);
        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(
            1,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_comment WHERE gradebook_id = ? AND user_id = 1', [$cat])
        );
    }

    public function testCommentsAreKeptPerUserAndPerGradebook(): void
    {
        $cat1 = $this->category(1);
        $cat2 = $this->category(1);

        GradebookUtils::saveComment($cat1, 1, 'admin on one');
        GradebookUtils::saveComment($cat1, 2, 'anon on one');
        GradebookUtils::saveComment($cat2, 1, 'admin on two');

        $this->assertSame('admin on one', GradebookUtils::getComment($cat1, 1)['comment']);
        $this->assertSame('anon on one', GradebookUtils::getComment($cat1, 2)['comment']);
        $this->assertSame('admin on two', GradebookUtils::getComment($cat2, 1)['comment']);
        $this->assertEmpty(GradebookUtils::getComment($cat2, 2));
    }

    // ---------------------------------------------------------------- students of a course

    public function testGetUsersInCourseListsOnlyStudentsOfTheCourse(): void
    {
        $student = $this->user('student', 'Zed', 'Amy');
        $teacher = $this->user('teacher', 'Teach', 'Tom', 1);
        $other = $this->user('other', 'Other', 'Olga');
        $this->subscribe($student, 1, 5);
        $this->subscribe($teacher, 1, 1);
        $this->subscribe($other, 2, 5);

        $users = GradebookUtils::get_users_in_course(1);

        $this->assertArrayHasKey('STUD'.$student, $users);
        $this->assertArrayNotHasKey('STUD'.$teacher, $users);
        $this->assertArrayNotHasKey('STUD'.$other, $users);
        $row = $users['STUD'.$student];
        $this->assertSame($student, (int) $row[0]);
        $this->assertSame('Zed', $row[2]);
        $this->assertSame('Amy', $row[3]);
        $this->assertStringStartsWith('OC', $row[4]);
    }

    public function testGetUsersInCourseIsOrderedByTheNameConvention(): void
    {
        $a = $this->user('a', 'Zulu', 'Alpha');
        $b = $this->user('b', 'Alpha', 'Zulu');
        $this->subscribe($a, 1);
        $this->subscribe($b, 1);

        $keys = array_keys(array_filter(
            GradebookUtils::get_users_in_course(1),
            static fn (array $r): bool => \in_array((int) $r[0], [$a, $b], true)
        ));

        $expected = api_sort_by_first_name() ? ['STUD'.$a, 'STUD'.$b] : ['STUD'.$b, 'STUD'.$a];
        $this->assertSame($expected, $keys);
    }

    public function testGetUsersInCourseDoesNotHideSoftDeletedStudents(): void
    {
        $gone = $this->user('gone', 'Gone', 'Gina', 5, -2);
        $this->subscribe($gone, 1);

        // PINNED QUIRK: no u.active filter, a soft-deleted (-2) student still shows in the gradebook lists.
        $this->assertArrayHasKey('STUD'.$gone, GradebookUtils::get_users_in_course(1));
    }

    public function testGetUsersInCourseInASessionListsTheSessionStudentsOfThatCourse(): void
    {
        $inSession = $this->user('insess', 'Sess', 'Sue');
        $coach = $this->user('coach', 'Coach', 'Carl');
        $baseOnly = $this->user('base', 'Base', 'Bob');
        $session = $this->session();
        $this->sessionSubscribe($session, 1, $inSession, 0);
        $this->sessionSubscribe($session, 1, $coach, 2);
        $this->subscribe($baseOnly, 1);
        \ChamiloSession::write('sid', $session);

        $users = GradebookUtils::get_users_in_course(1);

        $this->assertSame(['STUD'.$inSession], array_keys($users));
    }

    public function testGetUserArrayFromSqlResultKeysRowsByUserAndKeepsFiveColumns(): void
    {
        $u = $this->user('x', 'Last', 'First');
        $result = \Database::query("SELECT id AS user_id, username, lastname, firstname, official_code FROM user WHERE id = $u");

        $rows = GradebookUtils::get_user_array_from_sql_result($result);

        $this->assertSame(['STUD'.$u], array_keys($rows));
        $this->assertSame([(string) $u, $rows['STUD'.$u][1], 'Last', 'First', $rows['STUD'.$u][4]], array_map('strval', $rows['STUD'.$u]));
    }

    // ---------------------------------------------------------------- get_all_users

    public function testGetAllUsersStartsFromTheStudentsOfTheCurrentCourse(): void
    {
        $student = $this->user('s', 'S', 'S');
        $this->subscribe($student, 1);
        \ChamiloSession::write('_real_cid', 1);

        $this->assertArrayHasKey('STUD'.$student, GradebookUtils::get_all_users());
    }

    public function testGetAllUsersAddsTheStudentsOfEveryCourseOfTheEvaluations(): void
    {
        $s1 = $this->user('s1', 'One', 'One');
        $s2 = $this->user('s2', 'Two', 'Two');
        $this->subscribe($s1, 1);
        $this->subscribe($s2, 2);
        \ChamiloSession::write('_real_cid', 1);
        $eval = (new Evaluation())->setCourseId(2);

        $users = GradebookUtils::get_all_users([$eval]);

        $this->assertArrayHasKey('STUD'.$s1, $users);
        $this->assertArrayHasKey('STUD'.$s2, $users);
    }

    public function testGetAllUsersForACourseIndependentEvaluationListsThePeopleWithAResult(): void
    {
        $withResult = $this->user('res', 'Zulu', 'Alpha');
        $second = $this->user('res2', 'Alpha', 'Zulu');
        $noResult = $this->user('nores', 'No', 'No');
        $this->subscribe($noResult, 2);
        $evalId = $this->evaluation(null, null);
        foreach ([$withResult, $second] as $uid) {
            $this->conn->executeStatement(
                "INSERT INTO gradebook_result (evaluation_id, user_id, score, created_at) VALUES (?, ?, 5, '2001-01-01 00:00:00')",
                [$evalId, $uid]
            );
        }
        \ChamiloSession::write('_real_cid', 1);
        $eval = new Evaluation();
        $eval->set_id($evalId);

        // The query used to get a second "ORDER BY" appended for first-name-first languages, a syntax
        // error: the flat view of a course-independent evaluation broke for them.
        $users = GradebookUtils::get_all_users([$eval]);

        $this->assertArrayNotHasKey('STUD'.$noResult, $users);
        $keys = array_values(array_intersect(array_keys($users), ['STUD'.$withResult, 'STUD'.$second]));
        $expected = api_is_western_name_order() ? ['STUD'.$withResult, 'STUD'.$second] : ['STUD'.$second, 'STUD'.$withResult];
        $this->assertSame($expected, $keys);
    }

    // ---------------------------------------------------------------- certificates

    public function testGetListUsersCertificatesFiltersByCategoryAndUsers(): void
    {
        $cat1 = $this->category(1);
        $cat2 = $this->category(1);
        $a = $this->user('a', 'Aaa', 'Zed');
        $b = $this->user('b', 'Bbb', 'Yan');
        $this->certificate($cat1, $a);
        $this->certificate($cat1, $b);
        $this->certificate($cat2, $a);

        $inCat1 = GradebookUtils::get_list_users_certificates($cat1);
        $this->assertEqualsCanonicalizing([$a, $b], array_map(static fn (array $r): int => (int) $r['user_id'], $inCat1));
        // Ordered by last name unless the language sorts by first name.
        $expectedFirst = api_sort_by_first_name() ? $b : $a;
        $this->assertSame($expectedFirst, (int) $inCat1[0]['user_id']);

        $onlyB = GradebookUtils::get_list_users_certificates($cat1, [$b]);
        $this->assertSame([$b], array_map(static fn (array $r): int => (int) $r['user_id'], $onlyB));

        // DISTINCT collapses the two certificates of user a when no category is given.
        $all = GradebookUtils::get_list_users_certificates(null, [$a]);
        $this->assertCount(1, $all);
    }

    public function testGetListUsersCertificatesIgnoresANonPositiveCategory(): void
    {
        $cat = $this->category(1);
        $a = $this->user('a', 'A', 'A');
        $this->certificate($cat, $a);

        $ids = array_map(static fn (array $r): int => (int) $r['user_id'], GradebookUtils::get_list_users_certificates(0, [$a]));

        $this->assertSame([$a], $ids);
    }

    public function testGetListGradebookCertificatesByUserIdReturnsOneRowPerCertificate(): void
    {
        $cat1 = $this->category(1);
        $cat2 = $this->category(1);
        $a = $this->user('a', 'A', 'A');
        $other = $this->user('o', 'O', 'O');
        $c1 = $this->certificate($cat1, $a);
        $c2 = $this->certificate($cat2, $a);
        $this->certificate($cat1, $other);

        $all = GradebookUtils::get_list_gradebook_certificates_by_user_id($a);
        $this->assertEqualsCanonicalizing([$c1, $c2], array_map(static fn (array $r): int => (int) $r['id'], $all));

        $one = GradebookUtils::get_list_gradebook_certificates_by_user_id($a, $cat2);
        $this->assertSame([$c2], array_map(static fn (array $r): int => (int) $r['id'], $one));
        $this->assertSame(80.0, (float) $one[0]['score_certificate']);
        $this->assertSame([], GradebookUtils::get_list_gradebook_certificates_by_user_id(999999));
    }

    // ---------------------------------------------------------------- guarded / dead entry points

    public function testCreateDefaultCourseGradebookDoesNothingWithoutEditRights(): void
    {
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_category');

        // No logged-in user: api_is_allowed_to_edit() is false, so nothing is created, 0 is returned.
        $this->assertSame(0, GradebookUtils::create_default_course_gradebook(1));
        $this->assertSame($before, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_category'));
    }

    public function testFindStudentsNeedsEditRightsAndAMask(): void
    {
        $this->user('findme', 'Findme', 'Fiona');

        // Dead helper: refuses without edit rights (no user here) and for an empty mask.
        $this->assertNull(GradebookUtils::find_students('Findme'));
        $this->assertNull(GradebookUtils::find_students(''));
    }

    public function testGetTableTypeCourseRejectsUnknownTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        GradebookUtils::get_table_type_course('gu_unknown_type');
    }
}
