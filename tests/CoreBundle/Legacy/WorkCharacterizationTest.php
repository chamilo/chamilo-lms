<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * Characterization of the legacy assignments library (public/main/work/work.lib.php, plain functions).
 *
 * They pin what the raw SQL currently returns or writes (counts, row shapes, active/accepted filtering, group
 * scoping, ordering, NULL handling) so that a rewrite to DQL that changes any of it fails here. Where the current
 * behaviour looks like a bug it is pinned as-is and flagged with a "PINNED QUIRK" comment: do not "fix" the
 * expectation without deciding that the behaviour change is intended.
 *
 * Fixtures are inserted with prepared DBAL statements (no resource_node, no createCourse(): they deadlock when
 * sibling suites share the database) and reuse the fixture users (admin, anon, fallback_user) and courses 1 and 2.
 * Every title starts with "wk_".
 */
final class WorkCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../../public/main/work/work.lib.php';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/work/work.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        ChamiloSession::erase('login_as');
        ChamiloSession::erase('studentview');
        ChamiloSession::erase('groupId');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('login_as');
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return $this->getUser('admin');
    }

    private function student(): User
    {
        return $this->getUser('anon');
    }

    private function other(): User
    {
        return $this->getUser('fallback_user');
    }

    private function course(int $id = 1): Course
    {
        return $this->getCourse($id);
    }

    private function setContext(?User $user, ?Course $course, int $sessionId = 0): void
    {
        $this->getEntityManager()->clear();
        $tokenStorage = self::getContainer()->get('security.token_storage');
        $tokenStorage->setToken(null !== $user ? new UsernamePasswordToken($user, 'main', $user->getRoles()) : null);
        ChamiloSession::write('_user', null !== $user ? ['id' => $user->getId()] : null);
        if (null !== $course) {
            ChamiloSession::write('_real_cid', $course->getId());
            ChamiloSession::write('_cid', $course->getCode());
            ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        } else {
            ChamiloSession::erase('_real_cid');
            ChamiloSession::erase('_cid');
            ChamiloSession::erase('_course');
        }
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::erase('login_as');
    }

    /**
     * Inserts a bare c_student_publication row (no resource node) and returns its iid.
     */
    private function work(array $o = []): int
    {
        $this->conn->insert('c_student_publication', $o + [
            'parent_id' => null,
            'user_id' => $this->student()->getId(),
            'title' => 'wk_work_'.(++$this->seq),
            'description' => null,
            'active' => 1,
            'accepted' => 1,
            'post_group_id' => 0,
            'sent_date' => '2020-01-01 10:00:00',
            'filetype' => 'file',
            'has_properties' => 0,
            'qualification' => 0,
            'qualificator_id' => 0,
            'weight' => 0,
            'allow_text_assignment' => 0,
            'contains_file' => 0,
            'document_id' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function folder(array $o = []): int
    {
        return $this->work($o + ['filetype' => 'folder', 'user_id' => $this->admin()->getId(), 'title' => 'wk_folder_'.(++$this->seq)]);
    }

    private function relUser(int $workId, int $userId): void
    {
        $this->conn->insert('c_student_publication_rel_user', ['work_id' => $workId, 'user_id' => $userId]);
    }

    private function subscribe(User $user, int $courseId, int $status = 5): void
    {
        $this->conn->insert('course_rel_user', [
            'user_id' => $user->getId(),
            'c_id' => $courseId,
            'relation_type' => 0,
            'status' => $status,
            'is_tutor' => 0,
            'sort' => 0,
            'user_course_cat' => 0,
            'progress' => 0,
        ]);
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            ['wk_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function field(int $iid, string $column): mixed
    {
        return $this->conn->fetchOne("SELECT $column FROM c_student_publication WHERE iid = ?", [$iid]);
    }

    // ------------------------------------------------- per-student counters

    public function testWorkCountByStudentCountsOnlyActiveZeroOrOneOfThatUserInThatFolder(): void
    {
        $folder = $this->folder();
        $otherFolder = $this->folder();
        $me = $this->student()->getId();
        $this->work(['parent_id' => $folder, 'user_id' => $me, 'active' => 1]);
        $this->work(['parent_id' => $folder, 'user_id' => $me, 'active' => 0]);
        $this->work(['parent_id' => $folder, 'user_id' => $me, 'active' => 2]);
        $this->work(['parent_id' => $folder, 'user_id' => $this->other()->getId(), 'active' => 1]);
        $this->work(['parent_id' => $otherFolder, 'user_id' => $me, 'active' => 1]);

        $count = get_work_count_by_student($me, $folder);

        $this->assertSame(2, $count, 'active=2 (deleted), other users and other folders are not counted');
        $this->assertSame(0, get_work_count_by_student($this->other()->getId(), $otherFolder));
    }

    public function testWorkCountByStudentTreatsNullActiveAsNotCounted(): void
    {
        $folder = $this->folder();
        $this->work(['parent_id' => $folder, 'active' => null]);

        // Kept on purpose: the column is nullable, but the whole library filters "active IN (0, 1)", so a NULL row is
        // consistently invisible everywhere; counting it here alone would make the counters disagree with the lists.
        $this->assertSame(0, get_work_count_by_student($this->student()->getId(), $folder));
    }

    public function testUniqueStudentAttemptsTotalCountsDistinctUsersNotSubmissions(): void
    {
        $folder = $this->folder();
        $a = $this->student()->getId();
        $b = $this->other()->getId();
        foreach ([
            $this->work(['parent_id' => $folder, 'user_id' => $a]),
            $this->work(['parent_id' => $folder, 'user_id' => $a]),
            $this->work(['parent_id' => $folder, 'user_id' => $b, 'active' => 0]),
            $this->work(['parent_id' => $folder, 'user_id' => $this->admin()->getId(), 'active' => 2]),
        ] as $id) {
            $this->publish($id, 1);
        }

        $total = getUniqueStudentAttemptsTotal($folder, 0, 1, 0);

        $this->assertEquals(2, $total, 'two distinct authors with active 0/1; the deleted one is ignored');
    }

    public function testUniqueStudentAttemptsTotalFiltersOnPostGroupId(): void
    {
        $folder = $this->folder();
        $this->publish($this->work(['parent_id' => $folder, 'post_group_id' => 0]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $this->other()->getId(), 'post_group_id' => 987654]), 1);

        $this->assertEquals(1, getUniqueStudentAttemptsTotal($folder, 0, 1, 0));
    }

    public function testUniqueStudentAttemptsTotalHonoursTheSessionAndCourseOfTheWorkLinks(): void
    {
        $folder = $this->folder();
        $sessionId = $this->session();
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $this->student()->getId()]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $this->other()->getId()]), 1, 2, $sessionId);
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $this->admin()->getId()]), 2);

        // Fixed: the session condition used to be computed and dropped, so every session saw every submission.
        $this->assertEquals(1, getUniqueStudentAttemptsTotal($folder, 0, 1, 0), 'base course only');
        $this->assertEquals(1, getUniqueStudentAttemptsTotal($folder, 0, 1, $sessionId), 'that session only');
        $this->assertEquals(0, getUniqueStudentAttemptsTotal($folder, 0, 1, $sessionId + 1000), 'another session: none');
        $this->assertEquals(1, getUniqueStudentAttemptsTotal($folder, 0, 2, 0), 'the course id is honoured too');
    }

    public function testUniqueStudentAttemptsWithoutUserNorListIsZero(): void
    {
        $folder = $this->folder();
        $this->work(['parent_id' => $folder]);

        $this->assertSame(0, getUniqueStudentAttempts($folder, 0, 1, 0));
    }

    public function testUniqueStudentAttemptsCountsOnlyFileTypedRowsPerUserAndFolder(): void
    {
        $f1 = $this->folder();
        $f2 = $this->folder();
        $me = $this->student()->getId();
        $this->work(['parent_id' => $f1, 'user_id' => $me]);
        $this->work(['parent_id' => $f1, 'user_id' => $me]);
        $this->work(['parent_id' => $f2, 'user_id' => $me]);
        $this->work(['parent_id' => $f2, 'user_id' => $me, 'filetype' => 'folder']);
        $this->work(['parent_id' => $f2, 'user_id' => $me, 'active' => 2]);

        $this->assertEquals(1, getUniqueStudentAttempts($f1, 0, 1, 0, $me), 'two files in one folder = one attempt pair');
        $this->assertEquals(1, getUniqueStudentAttempts($f2, 0, 1, 0, $me), 'folder-typed and deleted rows are ignored');
        $this->assertEquals(2, getUniqueStudentAttempts([$f1, $f2], 0, 1, 0, $me), 'array of folders: one per (user, folder)');
    }

    public function testUniqueStudentAttemptsWithOnlyUserListFiltersUsers(): void
    {
        $f = $this->folder();
        $a = $this->student()->getId();
        $b = $this->other()->getId();
        $this->work(['parent_id' => $f, 'user_id' => $a]);
        $this->work(['parent_id' => $f, 'user_id' => $b]);

        $this->assertEquals(2, getUniqueStudentAttempts($f, 0, 1, 0, null, [$a, $b]));
        $this->assertEquals(1, getUniqueStudentAttempts($f, 0, 1, 0, null, [$a]));
        $this->assertEquals(0, getUniqueStudentAttempts($f, 0, 1, 0, $b, [$a]), 'user filter AND list filter are combined');
    }

    // ------------------------------------------------------------ assignment

    public function testWorkAssignmentByIdReturnsRowOrEmptyArray(): void
    {
        $folder = $this->folder();
        $this->conn->insert('c_student_publication_assignment', [
            'publication_id' => $folder,
            'expires_on' => '2030-05-06 07:08:09',
            'ends_on' => null,
            'add_to_calendar' => 0,
            'enable_qualification' => 1,
        ]);

        $row = get_work_assignment_by_id($folder);

        $this->assertSame($folder, (int) $row['publication_id']);
        $this->assertSame('2030-05-06 07:08:09', $row['expires_on']);
        $this->assertNull($row['ends_on']);
        $this->assertSame(1, (int) $row['enable_qualification']);
        $this->assertSame([], get_work_assignment_by_id($this->folder()));
    }

    // ----------------------------------------------------------- file names

    public function testCheckExistingWorkFileNameOnlyMatchesActiveOneInSameFolder(): void
    {
        $folder = $this->folder();
        $other = $this->folder();
        $this->work(['parent_id' => $folder, 'title' => 'wk_dup.txt', 'active' => 1]);
        $this->work(['parent_id' => $folder, 'title' => 'wk_hidden.txt', 'active' => 0]);
        $this->work(['parent_id' => $other, 'title' => 'wk_elsewhere.txt', 'active' => 1]);

        $this->assertSame(['title' => 'wk_dup.txt'], checkExistingWorkFileName('wk_dup.txt', $folder));
        $this->assertFalse(checkExistingWorkFileName('wk_hidden.txt', $folder), 'active=0 is not a duplicate');
        $this->assertFalse(checkExistingWorkFileName('wk_elsewhere.txt', $folder), 'scoped to the folder');
    }

    public function testCheckExistingWorkFileNameEscapesQuotes(): void
    {
        $folder = $this->folder();
        $this->work(['parent_id' => $folder, 'title' => "wk_o'brien.txt"]);

        $this->assertSame(['title' => "wk_o'brien.txt"], checkExistingWorkFileName("wk_o'brien.txt", $folder));
    }

    public function testUpdateDirNameRewritesTitleOnly(): void
    {
        $folder = $this->folder(['title' => 'wk_old']);

        $result = updateDirName(['iid' => $folder, 'url' => '/wk_old'], 'wk_new');

        // Fixed: it fell off the end and returned null although documented as bool.
        $this->assertTrue($result);
        $this->assertSame('wk_new', $this->field($folder, 'title'));
    }

    public function testUpdateDirNameWithAnEmptyNameChangesNothingAndReturnsFalse(): void
    {
        $folder = $this->folder(['title' => 'wk_untouched']);

        $this->assertFalse(updateDirName(['iid' => $folder, 'url' => '/x'], ''));
        $this->assertSame('wk_untouched', $this->field($folder, 'title'));
    }

    public function testUpdateDirNameWithSameNameAsUrlIsANoOpReturningTrue(): void
    {
        $folder = $this->folder(['title' => 'wk_keep']);

        $this->assertTrue(updateDirName(['iid' => $folder, 'url' => '/wksame'], 'wksame'));
        $this->assertSame('wk_keep', $this->field($folder, 'title'));
    }

    public function testUpdateDirNameStoresRawTitleButEscapesForTheQuery(): void
    {
        $folder = $this->folder(['title' => 'wk_before']);

        updateDirName(['iid' => $folder, 'url' => '/x'], "wk_l'a b");

        $this->assertSame("wk_l'a b", $this->field($folder, 'title'), 'title is stored as typed (not the sanitised file name)');
    }

    // ----------------------------------------------------- visibility flags

    public function testMakeInvisibleOfWorkWithoutResourceNodeFailsAndLeavesFlag(): void
    {
        $work = $this->work(['accepted' => 1]);

        // By design, not a bug: the flag is flipped through the resource repository, which refuses a work that has
        // no resource node (our bare fixture). In 2.x every work is created as a resource node, so a node-less
        // row is corrupt data and failing loudly is preferable to silently hiding nothing.
        try {
            makeInvisible($work, ['real_id' => 1]);
            $this->fail('Expected the repository to refuse a work without resource node');
        } catch (Throwable $e) {
            $this->assertStringContainsString('resource node', $e->getMessage());
        }
        $this->assertSame(1, (int) $this->field($work, 'accepted'));
    }

    public function testMakeVisibleRejectsEmptyArgumentsWithoutTouchingTheDatabase(): void
    {
        $work = $this->work(['accepted' => 0]);

        $this->assertFalse(makeVisible($work, []));
        $this->assertFalse(makeVisible(0, ['real_id' => 1]));
        $this->assertFalse(makeInvisible($work, []));
        $this->assertSame(0, (int) $this->field($work, 'accepted'));
    }

    public function testMakeVisibleAndInvisibleOfUnknownWorkReturnFalse(): void
    {
        // Fixed: an id that matches no work used to be reported as success.
        $this->assertFalse(makeVisible(2147483000, ['real_id' => 1]));
        $this->assertFalse(makeInvisible(2147483000, ['real_id' => 1]));
    }

    // ------------------------------------------------- subscribed users

    public function testAllUserToWorkListsRowsAndCountsByWorkOnly(): void
    {
        $w1 = $this->folder();
        $w2 = $this->folder();
        $this->relUser($w1, $this->student()->getId());
        $this->relUser($w1, $this->other()->getId());
        $this->relUser($w2, $this->student()->getId());

        $rows = getAllUserToWork($w1, 1);

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [$this->student()->getId(), $this->other()->getId()],
            array_map('intval', array_column($rows, 'user_id'))
        );
        $this->assertSame(2, getAllUserToWork($w1, 1, true));
        // Not a bug: c_student_publication_rel_user lost its c_id column and a work iid is unique platform-wide,
        // so the course id argument is accepted for compatibility but cannot narrow anything.
        $this->assertSame(2, getAllUserToWork($w1, 999, true));
        $this->assertSame(0, getAllUserToWork($this->folder(), 1, true));
        $this->assertSame([], getAllUserToWork($this->folder(), 1));
    }

    public function testUserIsSubscribedToWorkIsTrueForEveryoneWhenNobodyIsListed(): void
    {
        $w = $this->folder();

        $this->assertTrue(userIsSubscribedToWork($this->student()->getId(), $w, 1));
    }

    public function testUserIsSubscribedToWorkRestrictsToListedUsers(): void
    {
        $w = $this->folder();
        $this->relUser($w, $this->student()->getId());

        $this->assertTrue(userIsSubscribedToWork($this->student()->getId(), $w, 1));
        $this->assertFalse(userIsSubscribedToWork($this->other()->getId(), $w, 1));
    }

    public function testAllowOnlySubscribedUserLetsAdminsThroughAndFiltersStudents(): void
    {
        $w = $this->folder();
        $this->relUser($w, $this->student()->getId());

        $this->setContext($this->other(), $this->course());
        $this->assertFalse(allowOnlySubscribedUser($this->other()->getId(), $w, 1));
        $this->assertTrue(allowOnlySubscribedUser($this->student()->getId(), $w, 1));

        $this->setContext($this->admin(), $this->course());
        $this->assertTrue(allowOnlySubscribedUser($this->other()->getId(), $w, 1), 'platform admin is never filtered');
    }

    public function testStudentSubscribedToWorkPrefersWorkSubscriptionsOverCourseUsers(): void
    {
        $w = $this->folder();
        $this->relUser($w, $this->student()->getId());

        $list = getStudentSubscribedToWork($w, 1);

        $this->assertCount(1, $list);
        $this->assertSame($this->student()->getId(), (int) $list[0]['user_id']);
        $this->assertSame(1, getStudentSubscribedToWork($w, 1, null, null, true));
    }

    public function testStudentSubscribedToWorkFallsBackToCourseStudents(): void
    {
        $w = $this->folder();
        $this->subscribe($this->student(), 1, 5);
        $this->subscribe($this->other(), 1, 1);

        $list = getStudentSubscribedToWork($w, 1);

        $this->assertArrayHasKey($this->student()->getId(), $list, 'a course student is returned');
        $this->assertArrayNotHasKey($this->other()->getId(), $list, 'a course teacher (status 1) is not');
    }

    public function testAllDocumentToWorkListsRelationsOfThatWorkOnly(): void
    {
        $w1 = $this->folder();
        $w2 = $this->folder();
        $this->conn->insert('c_student_publication_rel_document', ['work_id' => $w1, 'document_id' => null]);
        $this->conn->insert('c_student_publication_rel_document', ['work_id' => $w2, 'document_id' => null]);

        $rows = getAllDocumentToWork($w1, 1);

        $this->assertCount(1, $rows);
        $this->assertSame($w1, (int) $rows[0]['work_id']);
        // Not a bug: c_student_publication_rel_document has no c_id column any more (see getAllUserToWork).
        $this->assertCount(1, getAllDocumentToWork($w1, 999));
        $this->assertSame([], getAllDocumentToWork($this->folder(), 1));
    }

    private function document(): int
    {
        $this->conn->insert('c_document', ['title' => 'wk_doc_'.(++$this->seq), 'filetype' => 'file', 'readonly' => 0, 'template' => 0]);

        return (int) $this->conn->lastInsertId();
    }

    public function testAddGetAndDeleteUserToWorkRoundTripPerWorkAndUser(): void
    {
        $w1 = $this->folder();
        $w2 = $this->folder();
        $me = $this->student()->getId();
        $other = $this->other()->getId();

        addUserToWork($me, $w1, 1);
        addUserToWork($other, $w1, 1);
        addUserToWork($me, $w2, 1);

        $row = getUserToWork($me, $w1, 1);
        $this->assertCount(1, $row);
        $this->assertSame($w1, (int) $row[0]['work_id']);
        $this->assertSame([], getUserToWork($other, $w2, 1), 'the pair (user, work) is what is looked up');

        deleteUserToWork($me, $w1, 1);

        $this->assertSame([], getUserToWork($me, $w1, 1));
        $this->assertCount(1, getUserToWork($other, $w1, 1), 'the other user of the same work stays');
        $this->assertCount(1, getUserToWork($me, $w2, 1), 'the same user in another work stays');
    }

    public function testAddGetAndDeleteDocumentToWorkRoundTripPerWorkAndDocument(): void
    {
        $w1 = $this->folder();
        $w2 = $this->folder();
        $d1 = $this->document();
        $d2 = $this->document();

        addDocumentToWork($d1, $w1, 1);
        addDocumentToWork($d2, $w1, 1);
        addDocumentToWork($d1, $w2, 1);

        $row = getDocumentToWork($d1, $w1, 1);
        $this->assertCount(1, $row);
        $this->assertSame($w1, (int) $row[0]['work_id']);
        $this->assertSame([], getDocumentToWork($d2, $w2, 1));

        deleteDocumentToWork($d1, $w1, 1);

        $this->assertSame([], getDocumentToWork($d1, $w1, 1));
        $this->assertCount(1, getDocumentToWork($d2, $w1, 1));
        $this->assertCount(1, getDocumentToWork($d1, $w2, 1));
    }

    // ---------------------------------------- users without a publication

    public function testListUsersWithoutPublicationExcludesAuthorsAndTeachers(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->subscribe($this->student(), 1, 5);
        $this->subscribe($this->other(), 1, 5);
        $this->subscribe($this->admin(), 1, 5);
        $task = $this->folder();
        $this->work(['parent_id' => $task, 'user_id' => $this->student()->getId()]);

        $rows = get_list_users_without_publication($task);

        $ids = array_map(static fn (array $r): int => (int) $r[3], $rows);
        $this->assertContains($this->other()->getId(), $ids, 'a subscribed user who did not submit is listed');
        $this->assertNotContains($this->student()->getId(), $ids, 'the author of an active work is not listed');
        $this->assertNotContains($this->admin()->getId(), $ids, 'by design, not a bug: platform users with status 1 (teachers) are subscribed here but are not "learners without a publication", the legacy query excludes them on purpose');
        $row = $rows[array_search($this->other()->getId(), $ids, true)];
        $this->assertSame($this->other()->getLastname(), $row[0]);
        $this->assertSame($this->other()->getFirstname(), $row[1]);
    }

    public function testListUsersWithoutPublicationCountsDeletedSubmissionAsNoSubmission(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->subscribe($this->student(), 1, 5);
        $task = $this->folder();
        $this->work(['parent_id' => $task, 'user_id' => $this->student()->getId(), 'active' => 2]);

        $ids = array_map(static fn (array $r): int => (int) $r[3], get_list_users_without_publication($task));

        $this->assertContains($this->student()->getId(), $ids);
    }

    public function testListUsersWithoutPublicationRestrictsToSubscribedUsersAndStudentFilter(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->subscribe($this->student(), 1, 5);
        $this->subscribe($this->other(), 1, 5);
        $task = $this->folder();
        $this->relUser($task, $this->other()->getId());

        $ids = array_map(static fn (array $r): int => (int) $r[3], get_list_users_without_publication($task));
        $this->assertSame([$this->other()->getId()], $ids, 'only users subscribed to the work are expected');

        $this->assertSame([], get_list_users_without_publication($task, $this->student()->getId()));
        $this->assertCount(1, get_list_users_without_publication($task, $this->other()->getId()));
    }

    // ------------------------------------------------------------ user lists

    public function testWorkUserListReturnsCourseStudentIdsOnly(): void
    {
        $this->subscribe($this->student(), 1, 5);
        $this->subscribe($this->other(), 1, 1);
        $course = $this->course(1);

        $ids = getWorkUserList($course->getCode(), 0, 0, 0, 0, null, null);
        $count = getWorkUserList($course->getCode(), 0, 0, 0, 0, null, null, true);

        $this->assertContains($this->student()->getId(), array_map('intval', $ids));
        $this->assertNotContains($this->other()->getId(), array_map('intval', $ids));
        $this->assertSame(\count($ids), (int) $count);
    }

    // ------------------------------------------------------ published count

    /**
     * Gives a bare work a minimal resource node and a published link in the course, which is what
     * get_count_work() joins on. Raw prepared inserts, no repository (see class comment).
     */
    private function publish(int $workId, int $courseId, int $visibility = 2, ?int $sessionId = null): void
    {
        $typeId = (int) $this->conn->fetchOne("SELECT id FROM resource_type WHERE title = 'student_publications'");
        $now = '2020-01-01 00:00:00';
        $this->conn->executeStatement(
            'INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?, 0, ?)',
            [$typeId, $this->admin()->getId(), 'wk_node_'.$workId, 'wk-node-'.$workId, 'wk-node-'.$workId.'/', $now, $now, Uuid::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn->lastInsertId();
        $this->conn->update('c_student_publication', ['resource_node_id' => $nodeId], ['iid' => $workId]);
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'visibility' => $visibility,
            'display_order' => 0,
            'resource_type_group' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function testCountWorkAsTeacherCountsActiveZeroAndOneIncludingUnaccepted(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $mine = $this->work(['parent_id' => $folder, 'accepted' => 1]);
        $pending = $this->work(['parent_id' => $folder, 'accepted' => 0, 'user_id' => $this->other()->getId()]);
        $hidden = $this->work(['parent_id' => $folder, 'active' => 0]);
        $deleted = $this->work(['parent_id' => $folder, 'active' => 2]);
        foreach ([$mine, $pending, $hidden, $deleted] as $id) {
            $this->publish($id, 1);
        }

        $this->assertEquals(3, get_count_work($folder), 'active=2 is excluded, accepted=0 is kept for editors');
        $this->assertEquals(1, get_count_work($folder, $this->other()->getId()), 'onlyMeUserId');
        $this->assertEquals(2, get_count_work($folder, null, $this->other()->getId()), 'notMeUserId');
    }

    public function testCountWorkAsStudentOnlyCountsAcceptedWork(): void
    {
        $this->setContext($this->student(), $this->course(1));
        $folder = $this->folder();
        $this->publish($this->work(['parent_id' => $folder, 'accepted' => 1]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'accepted' => 0]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'accepted' => 1, 'user_id' => $this->other()->getId()]), 1);

        // The course's show_score flag also narrows non-editors to their own work (the third work belongs to
        // another user), so only the student's accepted one is counted.
        $this->assertSame(1, (int) api_get_course_info()['show_score']);
        $this->assertEquals(1, get_count_work($folder), 'unaccepted and other users\' submissions are hidden from non-editors');
    }

    public function testCountWorkIgnoresLinksOfAnotherCourseAndUnpublishedLinks(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->publish($this->work(['parent_id' => $folder]), 1);
        $this->publish($this->work(['parent_id' => $folder]), 2);
        $this->publish($this->work(['parent_id' => $folder]), 1, 0);
        $this->work(['parent_id' => $folder]); // no node at all

        $this->assertEquals(1, get_count_work($folder));
    }

    public function testCountWorkRestrictsToPostGroupZeroOrNull(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->publish($this->work(['parent_id' => $folder, 'post_group_id' => 0]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'post_group_id' => 987654]), 1);

        $this->assertEquals(1, get_count_work($folder), 'works posted by a group are not counted outside that group');
    }

    // ------------------------------------------- comments and last works

    private function comment(int $workId): void
    {
        $this->conn->insert('c_student_publication_comment', [
            'work_id' => $workId,
            'user_id' => $this->admin()->getId(),
            'comment' => 'wk_comment',
            'sent_at' => '2020-01-02 00:00:00',
        ]);
    }

    public function testWorkCommentCountCountsTheCommentsOfThatWorkOnly(): void
    {
        $folder = $this->folder();
        $w1 = $this->work(['parent_id' => $folder]);
        $w2 = $this->work(['parent_id' => $folder]);
        $this->comment($w1);
        $this->comment($w1);
        $this->comment($w2);

        // Fixed: it filtered on comment.c_id, a column that no longer exists, and always threw.
        $this->assertSame(2, getWorkCommentCount($w1, ['real_id' => 1]));
        $this->assertSame(1, getWorkCommentCount($w2, ['real_id' => 1]));
        $this->assertSame(0, getWorkCommentCount($folder, ['real_id' => 1]));
    }

    public function testWorkCommentCountFromParentHonoursCourseAndSessionOfTheLinks(): void
    {
        $folder = $this->folder();
        $sessionId = $this->session();
        $base = $this->work(['parent_id' => $folder]);
        $inSession = $this->work(['parent_id' => $folder]);
        $otherCourse = $this->work(['parent_id' => $folder]);
        $this->publish($base, 1);
        $this->publish($inSession, 1, 2, $sessionId);
        $this->publish($otherCourse, 2);
        $this->comment($base);
        $this->comment($base);
        $this->comment($inSession);
        $this->comment($otherCourse);

        $this->assertSame(2, (int) getWorkCommentCountFromParent($folder, ['real_id' => 1], 0));
        $this->assertSame(1, (int) getWorkCommentCountFromParent($folder, ['real_id' => 1], $sessionId));
        $this->assertSame(1, (int) getWorkCommentCountFromParent($folder, ['real_id' => 2], 0));
    }

    public function testLastWorkStudentFromParentReturnsAWorkThatHasCommentsInThatCourse(): void
    {
        $folder = $this->folder();
        $commented = $this->work(['parent_id' => $folder]);
        $silent = $this->work(['parent_id' => $folder]);
        $elsewhere = $this->work(['parent_id' => $folder]);
        $this->publish($commented, 1);
        $this->publish($silent, 1);
        $this->publish($elsewhere, 2);
        $this->comment($commented);
        $this->comment($elsewhere);

        // DEAD: no caller; "last" is really the oldest commented work (ascending sent_date), see the @deprecated note.
        $this->assertSame($commented, (int) getLastWorkStudentFromParent($folder, ['real_id' => 1], 0)['iid']);
        $this->assertSame($elsewhere, (int) getLastWorkStudentFromParent($folder, ['real_id' => 2], 0)['iid']);
        $this->assertSame([], getLastWorkStudentFromParent($this->folder(), ['real_id' => 1], 0));
    }

    public function testLastWorkStudentFromParentByUserReturnsTheNewestOfThatUserWithRoundedScore(): void
    {
        $folder = $this->folder(['qualification' => 10]);
        $me = $this->student()->getId();
        $old = $this->work(['parent_id' => $folder, 'user_id' => $me, 'sent_date' => '2020-01-01 10:00:00', 'qualification' => 2]);
        $new = $this->work(['parent_id' => $folder, 'user_id' => $me, 'sent_date' => '2021-01-01 10:00:00', 'qualification' => 8]);
        $theirs = $this->work(['parent_id' => $folder, 'user_id' => $this->other()->getId(), 'sent_date' => '2022-01-01 10:00:00']);
        $elsewhere = $this->work(['parent_id' => $folder, 'user_id' => $me, 'sent_date' => '2023-01-01 10:00:00']);
        foreach ([$old, $new, $theirs] as $id) {
            $this->publish($id, 1);
        }
        $this->publish($elsewhere, 2);
        $parent = ['iid' => $folder, 'qualification' => 10];

        $last = getLastWorkStudentFromParentByUser($me, $parent, ['real_id' => 1], 0);

        $this->assertSame($new, (int) $last['iid'], 'newest work of that user in that course, not other users\' nor other courses\'');
        $this->assertArrayHasKey('qualification_rounded', $last);
        $this->assertSame([], getLastWorkStudentFromParentByUser($this->admin()->getId(), $parent, ['real_id' => 1], 0));
        $this->assertFalse(getLastWorkStudentFromParentByUser($me, [], ['real_id' => 1], 0));
    }

    public function testAgendaExistsForWorkReturnsTheCalendarEventIdOrZero(): void
    {
        $with = $this->folder();
        $without = $this->folder();
        $this->conn->insert('c_student_publication_assignment', ['publication_id' => $with, 'add_to_calendar' => 77, 'enable_qualification' => 0]);
        $this->conn->insert('c_student_publication_assignment', ['publication_id' => $without, 'add_to_calendar' => 0, 'enable_qualification' => 0]);

        // Fixed: it filtered on assignment.c_id, a column that no longer exists, and always threw.
        $this->assertSame(77, (int) agendaExistsForWork($with, ['real_id' => 1]));
        $this->assertSame(0, agendaExistsForWork($without, ['real_id' => 1]));
        $this->assertSame(0, agendaExistsForWork($this->folder(), ['real_id' => 1]), 'no assignment row at all');
    }

    public function testDocumentToWorkPerUserFindsTheCopyOfTheDocumentOfThatUser(): void
    {
        $folder = $this->folder();
        $doc = $this->document();
        $me = $this->student()->getId();
        addDocumentToWork($doc, $folder, 1);
        $mine = $this->work(['parent_id' => $folder, 'user_id' => $me, 'document_id' => $doc]);
        $theirs = $this->work(['parent_id' => $folder, 'user_id' => $this->other()->getId(), 'document_id' => $doc]);
        $this->publish($mine, 1);
        $this->publish($theirs, 1);

        $found = getDocumentToWorkPerUser($doc, $folder, 1, 0, $me);

        $this->assertSame($mine, (int) $found['iid']);
        $this->assertSame([], getDocumentToWorkPerUser($doc, $folder, 2, 0, $me), 'another course');
        $this->assertSame([], getDocumentToWorkPerUser($doc, $folder, 1, $this->session(), $me), 'another session');
        $this->assertSame([], getDocumentToWorkPerUser($doc, $folder, 1, 0, $me, 0), 'inactive filter');
    }

    public function testGenerateMoveFormListsTheActiveRootFoldersOfThatCourseAndSession(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $sessionId = $this->session();
        $moving = $this->work(['title' => 'wk_moving']);
        $target = $this->folder(['title' => 'wk_target_folder']);
        $nested = $this->folder(['title' => 'wk_nested_folder', 'parent_id' => $target]);
        $hidden = $this->folder(['title' => 'wk_deleted_folder', 'active' => 2]);
        $inSession = $this->folder(['title' => 'wk_session_folder']);
        $elsewhere = $this->folder(['title' => 'wk_other_course_folder']);
        $this->publish($moving, 1);
        $this->publish($target, 1);
        $this->publish($nested, 1);
        $this->publish($hidden, 1);
        $this->publish($inSession, 1, 2, $sessionId);
        $this->publish($elsewhere, 2);

        // Fixed: it selected the dropped url/c_id/session_id columns, and so did the selector it feeds.
        $html = generateMoveForm($moving, '/', ['real_id' => 1], 0, 0);

        $this->assertStringContainsString('wk_moving', $html, 'the header names the work being moved');
        $this->assertStringContainsString('wk_target_folder', $html);
        $this->assertStringNotContainsString('wk_nested_folder', $html, 'only root folders');
        $this->assertStringNotContainsString('wk_deleted_folder', $html);
        $this->assertStringNotContainsString('wk_session_folder', $html, 'base course session only');
        $this->assertStringNotContainsString('wk_other_course_folder', $html);

        $inSessionHtml = generateMoveForm($moving, '/', ['real_id' => 1], 0, $sessionId);
        $this->assertStringContainsString('wk_session_folder', $inSessionHtml);
        $this->assertStringNotContainsString('wk_target_folder', $inSessionHtml);
    }

    // ------------------------------------------------------- folder deletion

    public function testDeleteDirWorkOfUnknownFolderReturnsFalse(): void
    {
        $this->setContext($this->admin(), $this->course(1));

        $this->assertFalse(deleteDirWork(2147483000));
    }

    public function testDeleteDirWorkSoftDeletesFolderAndChildrenAndDropsAssignment(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->publish($folder, 1); // deleteDirWork() refuses a folder that is not published in the course
        $child = $this->work(['parent_id' => $folder]);
        $unrelated = $this->work(['parent_id' => $this->folder()]);
        $this->conn->insert('c_student_publication_assignment', [
            'publication_id' => $folder,
            'add_to_calendar' => 0,
            'enable_qualification' => 0,
        ]);

        // Fixed: the work.considered_working_time branch read $work_data['id'] (the key is now "iid") and aborted
        // before any of the soft-delete SQL ran.
        $this->assertTrue(deleteDirWork($folder));

        $this->assertSame(2, (int) $this->field($folder, 'active'), 'the folder is soft-deleted');
        $this->assertSame(2, (int) $this->field($child, 'active'), 'its children are soft-deleted');
        $this->assertSame(1, (int) $this->field($unrelated, 'active'), 'works of other folders are untouched');
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_student_publication_assignment WHERE publication_id = ?', [$folder])
        );
    }

    public function testDeleteDirWorkAlsoRemovesTheCalendarEventOfTheAssignment(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->publish($folder, 1); // deleteDirWork() refuses a folder that is not published in the course
        $this->conn->insert('c_calendar_event', ['title' => 'wk_event', 'content' => '', 'all_day' => 0, 'collective' => 0, 'subscription_visibility' => 0, 'max_attendees' => 0]);
        $eventId = (int) $this->conn->lastInsertId();
        $this->conn->insert('c_student_publication_assignment', [
            'publication_id' => $folder,
            'add_to_calendar' => $eventId,
            'enable_qualification' => 0,
        ]);

        $this->assertTrue(deleteDirWork($folder));

        $this->assertSame(
            0,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_calendar_event WHERE iid = ?', [$eventId]),
            'the agenda entry linked by add_to_calendar is deleted (c_calendar_event has no "id" column)'
        );
    }

    public function testUpdateWorkUrlReturnsMinusOneForEmptyOrUnknownId(): void
    {
        $this->setContext($this->admin(), $this->course(1));

        $this->assertSame(-1, updateWorkUrl(0, '/wk/', 0));
        $this->assertSame(-1, updateWorkUrl(2147483000, '/wk/', 0));
    }

    public function testUpdateWorkUrlOfExistingWorkFailsOnMissingUrlColumn(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $work = $this->work();

        // Deprecated, not repaired: it is tied to the removed file-url model and nothing calls it, so it keeps
        // failing on the missing "url" column (undefined array key) until it is removed.
        try {
            updateWorkUrl($work, '/wk/', 0);
            $this->fail('Expected an undefined array key "url"');
        } catch (Throwable $e) {
            $this->assertStringContainsString('Undefined array key "url"', $e->getMessage());
        }
    }

    /**
     * @dataProvider brokenAgainstCurrentSchema
     */
    public function testQueryThatReferencesDroppedColumnsIsAnSqlError(callable $call): void
    {
        $this->setContext($this->admin(), $this->course(1));

        // These four are @deprecated and left as is: they belong to the removed file-url model (url / id columns),
        // nothing calls them but get_work_path() in the compilatio plugin, and there is no schema-faithful meaning to
        // repair them to. They fail until removed.
        $this->expectException(Throwable::class);
        $call();
    }

    // ------------------------------------------------ queries broken by schema

    /**
     * @return array<string, array{0: callable}>
     */
    public static function brokenAgainstCurrentSchema(): array
    {
        return [
            // c_student_publication has no "url" / "id" column any more.
            'get_work_data_by_path' => [static fn () => get_work_data_by_path('/wk_x', 1)],
            'is_work_exist_by_url' => [static fn () => is_work_exist_by_url('/wk_x')],
            'get_work_path' => [static fn () => get_work_path(1)],
        ];
    }
}
