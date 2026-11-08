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

/**
 * Second pass over work.lib.php: the list functions now built on the repository QueryBuilder (teacher, student,
 * per-submission and pending lists), the "who is the author" predicate, the deletion helpers, the "created by"
 * report and the marks (qualification) update.
 *
 * What matters here is WHO sees WHICH row: a learner only ever his own submissions (or the accepted ones when the
 * course shows the scores), a teacher only the course (and session) of the request, a platform teacher only the
 * courses he teaches. Course and session scoping goes through resource_link, so every fixture row has a minimal
 * resource_node + resource_link inserted with prepared statements (no createCourse()/repository creation: they
 * deadlock when sibling suites share the database). Fixture users: admin (platform admin, editor everywhere),
 * anon (the "learner"), fallback_user (the "other learner"). Every title starts with "w2_".
 */
final class WorkListsCharacterizationTest extends KernelTestCase
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
        $_GET = [];
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('login_as');
        $_GET = [];
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return $this->getUser('admin');
    }

    private function learner(): User
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

    private function setContext(?User $user, ?Course $course, int $sessionId = 0, array $roles = []): void
    {
        $this->getEntityManager()->clear();
        $tokenStorage = self::getContainer()->get('security.token_storage');
        $tokenStorage->setToken(null !== $user ? new UsernamePasswordToken($user, 'main', $roles ?: $user->getRoles()) : null);
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
     * A work row with a published (or not) link in a course: returns its iid.
     *
     * @param array $o row overrides; 'creator' is the resource node creator (default: the row's user)
     */
    private function node(array $o = [], int $courseId = 1, int $visibility = 2, ?int $sessionId = null, ?int $groupId = null): int
    {
        $creator = $o['creator'] ?? ($o['user_id'] ?? $this->admin()->getId());
        unset($o['creator']);
        $this->conn->insert('c_student_publication', $o + [
            'parent_id' => null,
            'user_id' => $this->admin()->getId(),
            'title' => 'w2_work_'.(++$this->seq),
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
        $workId = (int) $this->conn->lastInsertId();

        $typeId = (int) $this->conn->fetchOne("SELECT id FROM resource_type WHERE title = 'student_publications'");
        $now = '2020-01-01 00:00:00';
        $this->conn->executeStatement(
            'INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?, 0, ?)',
            [$typeId, $creator, 'w2_node_'.$workId, 'w2-node-'.$workId, 'w2-node-'.$workId.'/', $now, $now, Uuid::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn->lastInsertId();
        $this->conn->update('c_student_publication', ['resource_node_id' => $nodeId], ['iid' => $workId]);
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'group_id' => $groupId,
            'visibility' => $visibility,
            'display_order' => 0,
            'resource_type_group' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $workId;
    }

    private function folder(array $o = [], int $courseId = 1, int $visibility = 2, ?int $sessionId = null): int
    {
        return $this->node($o + ['filetype' => 'folder', 'title' => 'w2_folder_'.(++$this->seq)], $courseId, $visibility, $sessionId);
    }

    private function submission(int $folder, User $user, array $o = [], int $courseId = 1, ?int $sessionId = null): int
    {
        return $this->node($o + ['parent_id' => $folder, 'user_id' => $user->getId()], $courseId, 2, $sessionId);
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

    private function relUser(int $workId, int $userId): void
    {
        $this->conn->insert('c_student_publication_rel_user', ['work_id' => $workId, 'user_id' => $userId]);
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            ['w2_session_'.(++$this->seq).'_'.uniqid()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function field(int $iid, string $column): mixed
    {
        return $this->conn->fetchOne("SELECT $column FROM c_student_publication WHERE iid = ?", [$iid]);
    }

    private function setShowScore(int $courseId, int $value): void
    {
        $this->conn->update('course', ['show_score' => $value], ['id' => $courseId]);
    }

    /**
     * @return string[] the plain titles of the rows of a user list ('title_clean')
     */
    private function titles(array $rows): array
    {
        $titles = array_column($rows, 'title_clean');
        sort($titles);

        return $titles;
    }

    private function titleOf(int $iid): string
    {
        return (string) $this->field($iid, 'title');
    }

    // ------------------------------------------------- get_work_user_list (rows of one assignment)

    public function testUserListOfATeacherHoldsTheActiveAndHiddenSubmissionsOfThatFolderInThatCourseOnly(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $otherFolder = $this->folder();
        $kept = $this->submission($folder, $this->learner(), ['title' => 'w2_kept']);
        $pending = $this->submission($folder, $this->other(), ['title' => 'w2_pending', 'accepted' => 0]);
        $hiddenFlag = $this->submission($folder, $this->learner(), ['title' => 'w2_active0', 'active' => 0]);
        $this->submission($folder, $this->learner(), ['title' => 'w2_deleted', 'active' => 2]);
        $this->submission($otherFolder, $this->learner(), ['title' => 'w2_other_folder']);
        $this->submission($folder, $this->learner(), ['title' => 'w2_other_course'], 2);
        $draft = $this->node(['parent_id' => $folder, 'title' => 'w2_link_draft'], 1, 0);

        $rows = get_work_user_list(0, 50, null, null, $folder);

        // A teacher/admin also gets the submissions whose link is a draft (visibility 0), to be able to publish them.
        $this->assertSame(['w2_active0', 'w2_kept', 'w2_link_draft', 'w2_pending'], $this->titles($rows));
        $this->assertEqualsCanonicalizing([$kept, $pending, $hiddenFlag, $draft], array_column($rows, 'iid'));
        $this->assertSame(4, (int) get_work_user_list(0, 50, null, null, $folder, '', null, true));
    }

    public function testUserListRowCarriesTheAuthorTheMarkAndTheRawDate(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->submission($folder, $this->learner(), [
            'title' => 'w2_graded',
            'qualification' => 7.5,
            'qualificator_id' => $this->admin()->getId(),
            'sent_date' => '2020-03-04 05:06:07',
        ]);
        $this->submission($folder, $this->other(), ['title' => 'w2_waiting']);

        $rows = array_column(get_work_user_list(0, 50, null, null, $folder), null, 'title_clean');

        $this->assertSame('2020-03-04 05:06:07', $rows['w2_graded']['sent_date_from_db']);
        $this->assertStringContainsString('Reviewed', strip_tags($rows['w2_graded']['qualificator_id']));
        $this->assertStringNotContainsString('Not reviewed', strip_tags($rows['w2_graded']['qualificator_id']));
        $this->assertStringContainsString('Not reviewed', strip_tags($rows['w2_waiting']['qualificator_id']));
        $this->assertSame('', $rows['w2_waiting']['qualification_only'], 'no mark yet');
        $this->assertNotSame('', $rows['w2_graded']['qualification_only']);
        $this->assertStringContainsString('7.5', $rows['w2_graded']['qualification_only']);
    }

    public function testUserListOfAnUnknownFolderIsNullNotAnEmptyList(): void
    {
        $this->setContext($this->admin(), $this->course(1));

        // PINNED QUIRK: the function falls off its end when the folder does not exist: null (callers foreach it).
        $this->assertNull(get_work_user_list(0, 50, null, null, 999999999));
    }

    public function testUserListOfALearnerIsRestrictedToHisOwnSubmissionsWhenTheCourseShowsNoScore(): void
    {
        $this->setShowScore(1, 1);
        $this->setContext($this->learner(), $this->course(1));
        $folder = $this->folder();
        $this->submission($folder, $this->learner(), ['title' => 'w2_mine']);
        $this->submission($folder, $this->learner(), ['title' => 'w2_mine_pending', 'accepted' => 0]);
        $this->submission($folder, $this->other(), ['title' => 'w2_theirs']);
        $this->submission($folder, $this->other(), ['title' => 'w2_theirs_pending', 'accepted' => 0]);

        $rows = get_work_user_list(0, 50, null, null, $folder);

        $this->assertSame(['w2_mine', 'w2_mine_pending'], $this->titles($rows));
        $this->assertSame(2, (int) get_work_user_list(0, 50, null, null, $folder, '', null, true), 'the count must not reveal how many classmates sent work');
    }

    public function testUserListOfALearnerNeverShowsTheHiddenSubmissionsOfOthers(): void
    {
        $this->setShowScore(1, 0);
        $this->setContext($this->learner(), $this->course(1));
        $folder = $this->folder();
        $this->submission($folder, $this->learner(), ['title' => 'w2_mine_pending', 'accepted' => 0]);
        $this->submission($folder, $this->other(), ['title' => 'w2_theirs_visible']);
        $this->submission($folder, $this->other(), ['title' => 'w2_theirs_hidden', 'accepted' => 0]);

        $rows = get_work_user_list(0, 50, null, null, $folder);

        $this->assertSame(
            ['w2_mine_pending', 'w2_theirs_visible'],
            $this->titles($rows),
            'when the course shows the work of the others, only the accepted ones; the teacher\'s "invisible" flag must hold'
        );
        $this->assertSame(2, (int) get_work_user_list(0, 50, null, null, $folder, '', null, true));
    }

    public function testUserListMarksASubmissionSentAfterTheDeadlineAndDoesNotCrashOnIt(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->conn->insert('c_student_publication_assignment', [
            'publication_id' => $folder,
            'expires_on' => '2020-02-01 00:00:00',
            'ends_on' => null,
            'add_to_calendar' => 0,
            'enable_qualification' => 0,
        ]);
        $this->submission($folder, $this->learner(), ['title' => 'w2_in_time', 'sent_date' => '2020-01-15 00:00:00']);
        $this->submission($folder, $this->other(), ['title' => 'w2_late', 'sent_date' => '2020-03-01 00:00:00']);

        $rows = array_column(get_work_user_list(0, 50, null, null, $folder), null, 'title_clean');

        $this->assertSame('', $rows['w2_in_time']['expiry_note']);
        $this->assertStringContainsString('Expired', strip_tags($rows['w2_late']['expiry_note']));
    }

    public function testUserListIgnoresLinksOfAnotherSession(): void
    {
        $sessionId = $this->session();
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->submission($folder, $this->learner(), ['title' => 'w2_base']);
        $this->submission($folder, $this->learner(), ['title' => 'w2_in_session'], 1, $sessionId);

        $baseRows = get_work_user_list(0, 50, null, null, $folder);
        $this->assertNotContains('w2_in_session', array_column($baseRows, 'title_clean'));
        $this->assertContains('w2_base', array_column($baseRows, 'title_clean'));

        $this->setContext($this->admin(), $this->course(1), $sessionId);
        $sessionRows = get_work_user_list(0, 50, null, null, $folder);
        $this->assertContains('w2_in_session', array_column($sessionRows, 'title_clean'));
    }

    public function testUserListCanBeRestrictedToOneStudent(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->submission($folder, $this->learner(), ['title' => 'w2_a']);
        $this->submission($folder, $this->other(), ['title' => 'w2_b']);

        $rows = get_work_user_list(0, 50, null, null, $folder, '', $this->other()->getId());

        $this->assertSame(['w2_b'], $this->titles($rows), 'the $studentId argument selects one learner');
        $this->assertSame(1, (int) get_work_user_list(0, 50, null, null, $folder, '', $this->other()->getId(), true));
    }

    public function testUserListPagingAndNoLimit(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        foreach (['a', 'b', 'c'] as $i => $letter) {
            $this->submission($folder, $this->learner(), ['title' => 'w2_'.$letter, 'sent_date' => '2020-01-0'.($i + 1).' 00:00:00']);
        }

        $page = get_work_user_list(1, 1, 'title', 'asc', $folder);
        $this->assertSame(['w2_b'], array_column($page, 'title_clean'));

        // The legacy SQL only added LIMIT when both start and limit were given: a null limit meant "everything"
        // (upload_corrections.php relies on it).
        $all = get_work_user_list(null, null, null, null, $folder);
        $this->assertCount(3, $all);
    }

    public function testUserListIsSortedByTheRequestedColumn(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        foreach (['b' => '2020-01-02', 'c' => '2020-01-03', 'a' => '2020-01-01'] as $letter => $date) {
            $this->submission($folder, $this->learner(), ['title' => 'w2_'.$letter, 'sent_date' => $date.' 00:00:00']);
        }

        $this->assertSame(['w2_a', 'w2_b', 'w2_c'], array_column(get_work_user_list(0, 50, 'title', 'asc', $folder), 'title_clean'));
        $this->assertSame(['w2_c', 'w2_b', 'w2_a'], array_column(get_work_user_list(0, 50, 'title', 'desc', $folder), 'title_clean'));
        $this->assertSame(['w2_c', 'w2_b', 'w2_a'], array_column(get_work_user_list(0, 50, null, null, $folder), 'title_clean'), 'default: newest first');
        $this->assertSame(['w2_a', 'w2_b', 'w2_c'], array_column(get_work_user_list(0, 50, 'sent_date', 'asc', $folder), 'title_clean'));
        // An unknown column (it comes from the request) falls back to the default instead of reaching the query.
        $this->assertSame(['w2_c', 'w2_b', 'w2_a'], array_column(get_work_user_list(0, 50, 'x; DROP TABLE user', 'asc', $folder), 'title_clean'));
    }

    // ----------------------------------------------------------------- getWorkListTeacher

    public function testTeacherListHoldsTheRootFoldersOfTheCourseThatAreNotDeleted(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $keep = $this->folder(['title' => 'w2_t_keep']);
        $hiddenFolder = $this->folder(['title' => 'w2_t_active0', 'active' => 0]);
        $this->folder(['title' => 'w2_t_deleted', 'active' => 2]);
        $this->folder(['title' => 'w2_t_other_course'], 2);
        $draft = $this->folder(['title' => 'w2_t_draft'], 1, 0);
        $this->submission($keep, $this->learner(), ['title' => 'w2_t_child']);

        $rows = getWorkListTeacher(0, 500, null, null, null);

        $mine = array_values(array_filter($rows, static fn ($r) => str_contains(strip_tags($r['title']), 'w2_t_')));
        $titles = array_map(static fn ($r) => trim(preg_replace('/\s+\d+$/', '', strip_tags($r['title']))), $mine);
        sort($titles);
        // Root folders only (a child is not an assignment), no active=2, other courses never; a draft folder is
        // listed for the teacher.
        $this->assertSame(['w2_t_active0', 'w2_t_draft', 'w2_t_keep'], $titles);
        $this->assertEqualsCanonicalizing([$keep, $hiddenFolder, $draft], array_column($mine, 'iid'));
        $this->assertGreaterThanOrEqual(3, getWorkListTeacher(0, 500, null, null, null, true));
    }

    public function testTeacherListCountMatchesTheRowsAndIsAnInteger(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $before = getWorkListTeacher(0, 500, null, null, null, true);
        $this->folder();
        $this->folder();
        $this->folder([], 2);

        $after = getWorkListTeacher(0, 500, null, null, null, true);

        $this->assertIsInt($after);
        $this->assertSame($before + 2, $after);
        $this->assertCount($after, getWorkListTeacher(0, 500, null, null, null));
    }

    public function testTeacherListIsEmptyForALearner(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->folder();
        $this->setContext($this->learner(), $this->course(1));

        $this->assertSame([], getWorkListTeacher(0, 500, null, null, null));
        // PINNED QUIRK: the count of a non-editor is the empty array, not 0 (model.ajax.php handles both).
        $this->assertSame([], getWorkListTeacher(0, 500, null, null, null, true));
    }

    public function testTeacherListIsPagedAndSorted(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        foreach (['a' => '2020-01-01', 'b' => '2020-01-02', 'c' => '2020-01-03'] as $letter => $date) {
            $this->folder(['title' => 'w2_p_'.$letter, 'sent_date' => $date.' 00:00:00']);
        }
        $strip = static fn (array $rows): array => array_values(array_filter(
            array_map(static fn ($r) => trim(preg_replace('/\s+\d+$/', '', strip_tags($r['title']))), $rows),
            static fn ($t) => str_starts_with($t, 'w2_p_')
        ));

        $this->assertSame(['w2_p_a', 'w2_p_b', 'w2_p_c'], $strip(getWorkListTeacher(0, 500, 'title', 'asc', null)));
        $this->assertSame(['w2_p_c', 'w2_p_b', 'w2_p_a'], $strip(getWorkListTeacher(0, 500, 'title', 'desc', null)));
        $this->assertSame(['w2_p_c', 'w2_p_b', 'w2_p_a'], $strip(getWorkListTeacher(0, 500, null, null, null)), 'default: newest first');
        $this->assertCount(2, getWorkListTeacher(0, 2, 'title', 'asc', null));
    }

    public function testTeacherListOfASessionDoesNotMixTheBaseCourse(): void
    {
        $sessionId = $this->session();
        $this->setContext($this->admin(), $this->course(1), $sessionId);
        $inSession = $this->folder(['title' => 'w2_s_in'], 1, 2, $sessionId);
        $base = $this->folder(['title' => 'w2_s_base']);

        $ids = array_column(getWorkListTeacher(0, 500, null, null, null), 'iid');

        $this->assertContains($inSession, $ids);
        $this->assertNotContains($base, $ids, 'assignments of the base course are not listed in a session');
    }

    // ----------------------------------------------------------------- getWorkListStudent

    public function testStudentListShowsOnlyPublishedFoldersOfThisCourse(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $shown = $this->folder(['title' => 'w2_s_shown']);
        $this->folder(['title' => 'w2_s_draft'], 1, 0);
        $this->folder(['title' => 'w2_s_elsewhere'], 2);
        $this->folder(['title' => 'w2_s_deleted', 'active' => 2]);
        $this->submission($shown, $this->other(), ['title' => 'w2_s_not_a_folder']);

        $rows = getWorkListStudent(0, 500, 'title', 'asc', null);

        $titles = array_values(array_filter(
            array_map(static fn ($r) => strip_tags($r['title']), $rows),
            static fn ($t) => str_starts_with($t, 'w2_s_')
        ));
        $this->assertSame(['w2_s_shown'], $titles, 'no draft link, no other course, no deleted folder, no child');
    }

    public function testStudentListSkipsFoldersAssignedToOtherUsersButCountsThem(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $open = $this->folder(['title' => 'w2_r_open']);
        $mine = $this->folder(['title' => 'w2_r_mine']);
        $theirs = $this->folder(['title' => 'w2_r_theirs']);
        $this->relUser($mine, $this->learner()->getId());
        $this->relUser($theirs, $this->other()->getId());

        $rows = getWorkListStudent(0, 500, 'title', 'asc', null);
        $titles = array_values(array_filter(
            array_map(static fn ($r) => strip_tags($r['title']), $rows),
            static fn ($t) => str_starts_with($t, 'w2_r_')
        ));

        $this->assertSame(['w2_r_mine', 'w2_r_open'], $titles, 'a folder assigned to somebody else is not offered');
        $total = getWorkListStudent(0, 500, 'title', 'asc', null, true);
        // PINNED QUIRK: the count includes the folders the loop skips (the grid pager can promise more than it
        // shows).
        $this->assertSame(count($rows) + 1, $total);
    }

    public function testStudentListLinksToTheDrhViewForAHumanResourcesManager(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $this->folder(['title' => 'w2_d_one']);
        $rows = getWorkListStudent(0, 500, 'title', 'asc', null);
        $row = array_values(array_filter($rows, static fn ($r) => str_contains($r['title'], 'w2_d_one')))[0];

        $this->assertStringContainsString('work/work_list.php', $row['title'], 'a learner opens his own list');
        $this->assertStringNotContainsString('work_list_all.php', $row['title']);
        $this->assertStringContainsString('work_list_others.php', $row['others']);
    }

    // ----------------------------------------------------------------- getWorkList

    public function testWorkListReturnsTheRootFoldersOfTheCourse(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $a = $this->folder(['title' => 'w2_l_a']);
        $b = $this->folder(['title' => 'w2_l_b', 'active' => 0]);
        $this->folder(['title' => 'w2_l_deleted', 'active' => 2]);
        $this->folder(['title' => 'w2_l_elsewhere'], 2);
        $this->submission($a, $this->learner(), ['title' => 'w2_l_child']);

        $list = getWorkList($a, ['qualification' => 5]);

        $ids = array_map(static fn ($w) => $w->getIid(), $list);
        $this->assertContains($a, $ids);
        $this->assertContains($b, $ids);
        $titles = array_map(static fn ($w) => $w->getTitle(), $list);
        $this->assertNotContains('w2_l_deleted', $titles);
        $this->assertNotContains('w2_l_elsewhere', $titles);
        $this->assertNotContains('w2_l_child', $titles);
    }

    public function testWorkListHidesDeletedFoldersFromALearnerToo(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $shown = $this->folder(['title' => 'w2_l_shown']);
        $this->folder(['title' => 'w2_l_gone', 'active' => 2]);
        $this->folder(['title' => 'w2_l_draft'], 1, 0);

        $titles = array_map(static fn ($w) => $w->getTitle(), getWorkList($shown, []));

        $this->assertContains('w2_l_shown', $titles);
        $this->assertNotContains('w2_l_gone', $titles);
        $this->assertNotContains('w2_l_draft', $titles, 'a learner only gets the published folders');
    }

    // ----------------------------------------------------------------- user_is_author

    public function testEditorsAreAuthorsOfEverything(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $work = $this->submission($folder, $this->learner());

        $this->assertTrue(user_is_author($work));
        $this->assertFalse(user_is_author(0));
        $this->assertFalse(user_is_author(null));
    }

    public function testLearnerIsAuthorOfHisOwnSubmissionOnly(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $folder = $this->folder();
        $mine = $this->submission($folder, $this->learner());
        $theirs = $this->submission($folder, $this->other());

        $this->assertTrue(user_is_author($mine));
        $this->assertFalse(user_is_author($theirs));
        $this->assertTrue(user_is_author($theirs, $this->other()->getId()), 'explicit user argument');
        $this->assertFalse(user_is_author($mine, $this->other()->getId()));
    }

    public function testAuthorshipFollowsEitherTheNodeCreatorOrTheRowUser(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $folder = $this->folder();
        // Row of another user created through the learner's own node (teacher uploading in his name): both
        // columns are accepted as proof.
        $viaNode = $this->submission($folder, $this->other(), ['creator' => $this->learner()->getId()]);
        $viaRow = $this->submission($folder, $this->learner(), ['creator' => $this->other()->getId()]);

        $this->assertTrue(user_is_author($viaNode));
        $this->assertTrue(user_is_author($viaRow));
    }

    public function testAuthorOfAnUnknownSubmissionIsFalseForALearner(): void
    {
        $this->setContext($this->learner(), $this->course(1));

        $this->assertFalse(user_is_author(999999999));
    }

    // ----------------------------------------------------------------- getWorkCreatedByUser

    public function testCreatedByUserListsTheWorksTheUserCreatedInThatCourse(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder(['title' => 'w2_c_folder']);
        $this->submission($folder, $this->learner(), ['title' => 'w2_c_mine']);
        $this->submission($folder, $this->other(), ['title' => 'w2_c_theirs']);
        $this->submission($folder, $this->learner(), ['title' => 'w2_c_elsewhere'], 2);

        $list = getWorkCreatedByUser($this->learner()->getId(), 1, 0);
        $titles = array_column($list, 0);

        $this->assertSame(['w2_c_mine'], array_values(array_filter($titles, static fn ($t) => str_starts_with($t, 'w2_c_'))));
        $row = $list[array_search('w2_c_mine', $titles, true)];
        $this->assertCount(3, $row, 'title, creation date, update date');
    }

    public function testCreatedByUserDoesNotMixSessions(): void
    {
        $sessionId = $this->session();
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $this->submission($folder, $this->learner(), ['title' => 'w2_cs_base']);
        $this->submission($folder, $this->learner(), ['title' => 'w2_cs_session'], 1, $sessionId);

        $base = array_column(getWorkCreatedByUser($this->learner()->getId(), 1, 0), 0);
        $inSession = array_column(getWorkCreatedByUser($this->learner()->getId(), 1, $sessionId), 0);

        $this->assertContains('w2_cs_base', $base);
        $this->assertNotContains('w2_cs_session', $base);
        $this->assertContains('w2_cs_session', $inSession);
    }

    // ----------------------------------------------------------------- getPendingWorkList

    /**
     * @return string[] the submission titles of the pending list that belong to this test
     */
    private function pendingTitles(?array $rows): array
    {
        $titles = array_values(array_filter(
            array_column($rows ?? [], 'title'),
            static fn ($t) => str_starts_with((string) $t, 'w2_')
        ));
        sort($titles);

        return $titles;
    }

    private function pendingFixture(): int
    {
        $folder = $this->folder(['title' => 'w2_pf1']);
        $folder2 = $this->folder(['title' => 'w2_pf2'], 2);
        $this->submission($folder, $this->learner(), ['title' => 'w2_pn_open']);
        $this->submission($folder, $this->other(), [
            'title' => 'w2_pn_graded',
            'qualification' => 6,
            'qualificator_id' => $this->admin()->getId(),
        ]);
        $this->submission($folder, $this->learner(), ['title' => 'w2_pn_hidden_flag', 'active' => 0]);
        $this->submission($folder, $this->learner(), ['title' => 'w2_pn_deleted', 'active' => 2]);
        $this->node(['parent_id' => $folder, 'title' => 'w2_pn_draft', 'user_id' => $this->learner()->getId()], 1, 0);
        $this->submission($folder2, $this->learner(), ['title' => 'w2_pn_course2'], 2);

        return $folder;
    }

    public function testPendingListOfAnAdministratorHoldsEverySubmittedWorkOfEveryCourse(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->pendingFixture();

        $rows = getPendingWorkList(null, null, null, null, '', false);

        // Folders are not submissions, deleted and draft ones are not pending.
        $this->assertSame(
            ['w2_pn_course2', 'w2_pn_graded', 'w2_pn_hidden_flag', 'w2_pn_open'],
            $this->pendingTitles($rows)
        );
        $row = array_values(array_filter($rows, static fn ($r) => 'w2_pn_graded' === $r['title']))[0];
        $this->assertSame('w2_pf1', $row['work_name']);
        $this->assertStringContainsString('Reviewed', strip_tags($row['qualificator_id']));
        $this->assertSame('6', $row['qualification']);
    }

    public function testPendingListFiltersByCourseAndByReviewStatus(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->pendingFixture();

        $course1 = getPendingWorkList(null, null, null, null, '', false, 1);
        $this->assertSame(['w2_pn_graded', 'w2_pn_hidden_flag', 'w2_pn_open'], $this->pendingTitles($course1));
        $this->assertSame(['w2_pn_course2'], $this->pendingTitles(getPendingWorkList(null, null, null, null, '', false, 2)));

        $notReviewed = getPendingWorkList(null, null, null, null, '', false, 1, 2);
        $this->assertSame(['w2_pn_hidden_flag', 'w2_pn_open'], $this->pendingTitles($notReviewed));
        $reviewed = getPendingWorkList(null, null, null, null, '', false, 1, 3);
        $this->assertSame(['w2_pn_graded'], $this->pendingTitles($reviewed));
    }

    public function testPendingListCountAgreesWithTheRows(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->pendingFixture();

        $count = getPendingWorkList(null, null, null, null, '', true, 1);

        $this->assertIsInt($count);
        $this->assertCount($count, getPendingWorkList(null, null, null, null, '', false, 1));
        $this->assertSame(3, getPendingWorkList(null, null, null, null, '1 = 1', true, 1) - (getPendingWorkList(null, null, null, null, '', true, 1) - 3));
    }

    public function testPendingListSortAllowlistAndPaging(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder(['title' => 'w2_ps']);
        foreach (['b' => '2020-02-01', 'a' => '2020-03-01', 'c' => '2020-01-01'] as $letter => $date) {
            $this->submission($folder, $this->learner(), ['title' => 'w2_pn_'.$letter, 'sent_date' => $date.' 00:00:00']);
        }

        $byTitle = getPendingWorkList(0, 500, 'title', 'asc', '', false, 1);
        $this->assertSame(['w2_pn_a', 'w2_pn_b', 'w2_pn_c'], $this->pendingTitles($byTitle));
        $this->assertSame(['w2_pn_c', 'w2_pn_b', 'w2_pn_a'], array_values(array_filter(
            array_column(getPendingWorkList(0, 500, 'title', 'desc', '', false, 1), 'title'),
            static fn ($t) => str_starts_with($t, 'w2_pn_')
        )));
        $this->assertSame(['w2_pn_c', 'w2_pn_b', 'w2_pn_a'], array_values(array_filter(
            array_column(getPendingWorkList(0, 500, 'sent_date', 'asc', '', false, 1), 'title'),
            static fn ($t) => str_starts_with($t, 'w2_pn_')
        )));
        // A column name that is not in the allowlist falls back to the sent date (the direction is kept).
        $this->assertSame(['w2_pn_c', 'w2_pn_b', 'w2_pn_a'], array_values(array_filter(
            array_column(getPendingWorkList(0, 500, '1; DROP TABLE user', 'asc', '', false, 1), 'title'),
            static fn ($t) => str_starts_with($t, 'w2_pn_')
        )));
        $this->assertCount(2, getPendingWorkList(1, 2, 'title', 'asc', '', false, 1));
    }

    public function testPendingListLeavesOutWorkOfInvitees(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder(['title' => 'w2_pi']);
        $this->submission($folder, $this->other(), ['title' => 'w2_pn_invited']);
        $this->submission($folder, $this->learner(), ['title' => 'w2_pn_normal']);
        $this->conn->update('user', ['status' => INVITEE], ['id' => $this->other()->getId()]);

        $this->assertSame(['w2_pn_normal'], $this->pendingTitles(getPendingWorkList(null, null, null, null, '', false, 1)));
    }

    public function testPendingListIsEmptyForALearner(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->pendingFixture();
        $this->setContext($this->learner(), $this->course(1));

        $this->assertSame([], getPendingWorkList(null, null, null, null, '', false));
        $this->assertSame(0, getPendingWorkList(null, null, null, null, '', true));
    }

    public function testPendingListOfATeacherIsLimitedToTheCoursesHeTeaches(): void
    {
        $teacher = $this->learner();
        $teacher->setRoles(['ROLE_TEACHER']);
        $this->subscribe($teacher, 1, 1);  // teaches course 1
        $this->subscribe($teacher, 2, 5);  // only a learner in course 2
        $this->setContext($teacher, $this->course(1));
        $this->pendingFixture();

        $titles = $this->pendingTitles(getPendingWorkList(null, null, null, null, '', false));

        $this->assertSame(['w2_pn_graded', 'w2_pn_hidden_flag', 'w2_pn_open'], $titles, 'nothing from the course where he is a learner');
        $this->assertSame([], $this->pendingTitles(getPendingWorkList(null, null, null, null, '', false, 2)), 'asking for that course explicitly changes nothing');
        $this->assertSame(0, getPendingWorkList(null, null, null, null, '', true, 2));
    }

    // ----------------------------------------------------------------- getAllWorkListStudent

    private function assignment(int $folder, ?string $expiresOn): void
    {
        $this->conn->insert('c_student_publication_assignment', [
            'publication_id' => $folder,
            'expires_on' => $expiresOn,
            'ends_on' => null,
            'add_to_calendar' => 0,
            'enable_qualification' => 0,
        ]);
    }

    /**
     * @return string[] the folder titles of an "all my assignments" result that belong to this test
     */
    private function allTitles(array $rows): array
    {
        $titles = [];
        foreach ($rows as $row) {
            $title = trim(strip_tags($row['title']));
            if (str_starts_with($title, 'w2_')) {
                $titles[] = $title;
            }
        }
        sort($titles);

        return $titles;
    }

    private function allFixture(): void
    {
        $this->subscribe($this->learner(), 1);
        $this->subscribe($this->learner(), 2);
        $open = $this->folder(['title' => 'w2_a_open']);
        $sent = $this->folder(['title' => 'w2_a_sent']);
        $closed = $this->folder(['title' => 'w2_a_closed']);
        $closedAndSent = $this->folder(['title' => 'w2_a_closed_sent']);
        $future = $this->folder(['title' => 'w2_a_future']);
        $otherCourse = $this->folder(['title' => 'w2_a_course2'], 2);
        $mine = $this->folder(['title' => 'w2_a_for_me']);
        $theirs = $this->folder(['title' => 'w2_a_for_them']);
        $this->folder(['title' => 'w2_a_gone', 'active' => 2]);
        $this->folder(['title' => 'w2_a_draft'], 1, 0);
        $this->assignment($closed, '2000-01-01 00:00:00');
        $this->assignment($closedAndSent, '2000-01-01 00:00:00');
        $this->assignment($future, '2999-01-01 00:00:00');
        $this->relUser($mine, $this->learner()->getId());
        $this->relUser($theirs, $this->other()->getId());
        $this->submission($sent, $this->learner(), ['title' => 'w2_a_mine']);
        $this->submission($closedAndSent, $this->learner(), ['title' => 'w2_a_mine2']);
        $this->submission($open, $this->other(), ['title' => 'w2_a_theirs_in_open']);
        $this->submission($otherCourse, $this->other(), ['title' => 'w2_a_theirs_in_c2'], 2);
    }

    public function testAllAssignmentsToDoAreTheOpenOnesWithNothingSentOfMyCourses(): void
    {
        $this->setContext($this->learner(), null);
        $this->allFixture();

        $rows = getAllWorkListStudent(0, 500, 'title', 'asc', '1 = 1', false, 0);

        // Not the one I already answered, not the expired one, not the deleted/draft ones, not the one assigned
        // to somebody else; what others sent does not matter.
        $this->assertSame(
            ['w2_a_course2', 'w2_a_for_me', 'w2_a_future', 'w2_a_open'],
            $this->allTitles($rows)
        );
        $this->assertSame(count($rows), getAllWorkListStudent(0, 500, 'title', 'asc', '1 = 1', true, 0));
    }

    public function testAllAssignmentsWithResultsAreTheOnesIAnsweredOrThatClosedWithoutMe(): void
    {
        $this->setContext($this->learner(), null);
        $this->allFixture();

        $rows = getAllWorkListStudent(0, 500, 'title', 'asc', '1 = 1', false, 1);

        $this->assertSame(['w2_a_closed', 'w2_a_closed_sent', 'w2_a_sent'], $this->allTitles($rows));
        $this->assertSame(count($rows), getAllWorkListStudent(0, 500, 'title', 'asc', '1 = 1', true, 1));
        $sent = array_values(array_filter($rows, static fn ($r) => str_contains($r['title'], 'w2_a_sent')))[0];
        $this->assertArrayHasKey('last_upload', $sent);
        $this->assertArrayNotHasKey('feedback', $sent, 'no comment yet');
        $this->assertStringContainsString('2020', $sent['last_upload']);
    }

    public function testAllAssignmentsOnlyCoverTheCoursesOfTheUser(): void
    {
        $this->subscribe($this->learner(), 1);
        $this->setContext($this->learner(), null);
        $this->folder(['title' => 'w2_a_mine']);
        $this->folder(['title' => 'w2_a_not_mine'], 2);

        $titles = $this->allTitles(getAllWorkListStudent(0, 500, 'title', 'asc', '1 = 1', false, 0));

        $this->assertSame(['w2_a_mine'], $titles, 'course 2 is not one of his courses');
    }

    public function testAllAssignmentsSortPageAndDeadlineColumn(): void
    {
        $this->subscribe($this->learner(), 1);
        $this->setContext($this->learner(), null);
        $b = $this->folder(['title' => 'w2_a_b']);
        $a = $this->folder(['title' => 'w2_a_a']);
        $c = $this->folder(['title' => 'w2_a_c']);
        $this->assignment($b, '2998-01-01 00:00:00');
        $this->assignment($a, '2999-01-01 00:00:00');
        $this->assignment($c, '2997-01-01 00:00:00');
        $mine = static fn (array $rows): array => array_map(
            static fn ($r) => trim(strip_tags($r['title'])),
            array_values(array_filter($rows, static fn ($r) => str_contains($r['title'], 'w2_a_')))
        );

        $this->assertSame(['w2_a_a', 'w2_a_b', 'w2_a_c'], $mine(getAllWorkListStudent(0, 500, 'title', 'asc', '', false, 0)));
        $this->assertSame(['w2_a_c', 'w2_a_b', 'w2_a_a'], $mine(getAllWorkListStudent(0, 500, 'title', 'desc', '', false, 0)));
        $this->assertSame(['w2_a_c', 'w2_a_b', 'w2_a_a'], $mine(getAllWorkListStudent(0, 500, 'expires_on', 'asc', '', false, 0)));
        $this->assertCount(2, getAllWorkListStudent(1, 2, 'title', 'asc', '', false, 0));
    }

    public function testAllAssignmentsOfAnAnonymousVisitorAreEmpty(): void
    {
        $this->setContext(null, null);

        $this->assertSame([], getAllWorkListStudent(0, 500, 'title', 'asc', '', false, 0));
    }

    public function testAllAssignmentsShowTheFeedbackCountOfMyOwnWorkOnly(): void
    {
        $this->subscribe($this->learner(), 1);
        $this->setContext($this->learner(), null);
        $folder = $this->folder(['title' => 'w2_a_fb']);
        $mine = $this->submission($folder, $this->learner(), ['title' => 'w2_a_fb_mine']);
        $theirs = $this->submission($folder, $this->other(), ['title' => 'w2_a_fb_theirs']);
        foreach ([$mine, $theirs, $theirs] as $workId) {
            $this->conn->insert('c_student_publication_comment', [
                'work_id' => $workId,
                'user_id' => $this->admin()->getId(),
                'comment' => 'w2_comment',
                'sent_at' => '2020-01-02 00:00:00',
            ]);
        }

        $rows = getAllWorkListStudent(0, 500, 'title', 'asc', '', false, 1);
        $row = array_values(array_filter($rows, static fn ($r) => str_contains($r['title'], 'w2_a_fb')))[0];

        $this->assertStringContainsString('1 ', strip_tags($row['feedback']), 'one comment on my work, the two on the other learner\'s are not mine');
        $this->assertStringNotContainsString('3', strip_tags($row['feedback']));
    }

    // ----------------------------------------------------------------- deleteWorkItem / deleteAllWorkPerUser

    private function setCourseSetting(int $courseId, string $variable, string $value): void
    {
        $this->conn->insert('c_course_setting', [
            'c_id' => $courseId,
            'variable' => $variable,
            'value' => $value,
            'title' => $variable,
        ]);
        // api_get_course_setting() keeps its answers in a static array: force the reload.
        api_get_course_setting($variable, ['real_id' => $courseId], true);
    }

    private function deleteEvents(): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM track_e_default WHERE default_event_type = 'work_file_delete'");
    }

    public function testATeacherDeletesASubmissionBySettingItInactiveAndLogsIt(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folder = $this->folder();
        $work = $this->submission($folder, $this->learner(), ['title' => 'w2_del']);
        $sibling = $this->submission($folder, $this->learner(), ['title' => 'w2_keep']);
        $events = $this->deleteEvents();

        $this->assertTrue(deleteWorkItem($work, $this->course(1)));

        $this->assertSame(2, (int) $this->field($work, 'active'), 'soft delete: the row stays, active = 2');
        $this->assertSame(1, (int) $this->field($sibling, 'active'));
        $this->assertSame($events + 1, $this->deleteEvents(), 'the deletion is logged');
        $this->assertSame([], array_filter(
            array_column(get_work_user_list(0, 50, null, null, $folder), 'title_clean'),
            static fn ($t) => 'w2_del' === $t
        ));
    }

    public function testDeleteWorkItemOfAnUnknownOrEmptyIdIsFalse(): void
    {
        $this->setContext($this->admin(), $this->course(1));

        $this->assertFalse(deleteWorkItem(0, $this->course(1)));
        $this->assertFalse(deleteWorkItem(999999999, $this->course(1)));
    }

    public function testALearnerDeletesHisOwnUnmarkedSubmissionOnlyWhenTheCourseAllowsIt(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $folder = $this->folder();
        $mine = $this->submission($folder, $this->learner());

        $this->setCourseSetting(1, 'student_delete_own_publication', '0');
        $this->assertFalse(deleteWorkItem($mine, $this->course(1)), 'setting off');
        $this->assertSame(1, (int) $this->field($mine, 'active'));

        $this->setCourseSetting(1, 'student_delete_own_publication', '1');
        $this->assertTrue(deleteWorkItem($mine, $this->course(1)), 'setting on');
        $this->assertSame(2, (int) $this->field($mine, 'active'));
    }

    public function testALearnerCannotDeleteAMarkedSubmissionNorSomebodyElsesWork(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $this->setCourseSetting(1, 'student_delete_own_publication', '1');
        $folder = $this->folder();
        $marked = $this->submission($folder, $this->learner(), ['qualificator_id' => $this->admin()->getId(), 'qualification' => 5]);
        $theirs = $this->submission($folder, $this->other());

        $this->assertFalse(deleteWorkItem($marked, $this->course(1)), 'a reviewed submission is frozen');
        $this->assertFalse(deleteWorkItem($theirs, $this->course(1)), 'not the author');
        $this->assertSame(1, (int) $this->field($marked, 'active'));
        $this->assertSame(1, (int) $this->field($theirs, 'active'));
    }

    public function testDeleteAllWorkOfAUserLeavesTheOtherLearnersAlone(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $folderA = $this->folder();
        $folderB = $this->folder();
        $mineA = $this->submission($folderA, $this->learner());
        $mineB = $this->submission($folderB, $this->learner());
        $theirsA = $this->submission($folderA, $this->other());
        $mineElsewhere = $this->submission($folderA, $this->learner(), [], 2);

        $deleted = deleteAllWorkPerUser($this->learner(), $this->course(1));

        $deletedIds = array_map(static fn ($w) => $w->getIid(), $deleted);
        sort($deletedIds);
        $this->assertSame([$mineA, $mineB], $deletedIds);
        $this->assertSame(2, (int) $this->field($mineA, 'active'));
        $this->assertSame(2, (int) $this->field($mineB, 'active'));
        $this->assertSame(1, (int) $this->field($theirsA, 'active'), 'the other learner keeps his work');
        $this->assertSame(1, (int) $this->field($mineElsewhere, 'active'), 'other course untouched');
    }

    public function testDeleteAllWorkOfAUserNeedsTheRightToDelete(): void
    {
        $this->setContext($this->learner(), $this->course(1));
        $folder = $this->folder();
        $mine = $this->submission($folder, $this->learner());

        // A learner running the helper on himself is still bound by the course setting (off by default).
        $this->setCourseSetting(1, 'student_delete_own_publication', '0');
        $this->assertSame([], deleteAllWorkPerUser($this->learner(), $this->course(1)));
        $this->assertSame(1, (int) $this->field($mine, 'active'));
    }

    public function testDeleteAllWorkOfAUserWithoutWorkIsEmpty(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $this->folder();

        $this->assertSame([], deleteAllWorkPerUser($this->other(), $this->course(1)));
    }
}
