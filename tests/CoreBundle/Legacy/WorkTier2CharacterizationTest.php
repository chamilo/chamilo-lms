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
 * Extra pins for work.lib.php that WorkCharacterizationTest leaves loose: the student view of get_count_work()
 * (own accepted submissions only), its group scope, the newest copy of getDocumentToWorkPerUser(), and the
 * hidden folders / group scope of generateMoveForm().
 *
 * Also pins the INTENDED result of three functions that selected columns c_student_publication no longer has
 * (url, c_id, session_id): the session branch of get_list_users_without_publication(),
 * get_work_user_list_from_documents() and get_work_path(). Course and session now live on the resource link.
 */
final class WorkTier2CharacterizationTest extends KernelTestCase
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
        ChamiloSession::erase('gid');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('login_as');
        ChamiloSession::erase('groupId');
        ChamiloSession::erase('gid');
        parent::tearDown();
    }

    public function testCountWorkAsStudentCountsAllHisOwnAcceptedSubmissionsAndNothingElse(): void
    {
        $this->setContext($this->student(), $this->course(1));
        $folder = $this->folder();
        $this->publish($this->work(['parent_id' => $folder, 'accepted' => 1]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'accepted' => 1]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'accepted' => 0]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'accepted' => 1, 'user_id' => $this->other()->getId()]), 1);

        $this->assertSame(1, (int) api_get_course_info()['show_score']);
        $this->assertEquals(2, get_count_work($folder));
    }

    public function testCountWorkOfAGroupCountsOnlyTheWorkPostedByThatGroup(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $group = $this->group();
        ChamiloSession::write('gid', $group);
        $folder = $this->folder();
        for ($i = 0; $i < 3; ++$i) {
            $this->publish($this->work(['parent_id' => $folder, 'post_group_id' => $group]), 1);
        }
        $this->publish($this->work(['parent_id' => $folder, 'post_group_id' => 0]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'post_group_id' => $group + 1000]), 1);

        $this->assertEquals(3, get_count_work($folder));
    }

    public function testDocumentToWorkPerUserReturnsTheNewestCopy(): void
    {
        $folder = $this->folder();
        $doc = $this->document();
        $me = $this->student()->getId();
        addDocumentToWork($doc, $folder, 1);
        $older = $this->work(['parent_id' => $folder, 'user_id' => $me, 'document_id' => $doc]);
        $newer = $this->work(['parent_id' => $folder, 'user_id' => $me, 'document_id' => $doc]);
        $this->publish($older, 1);
        $this->publish($newer, 1);

        $this->assertSame($newer, (int) getDocumentToWorkPerUser($doc, $folder, 1, 0, $me)['iid']);
    }

    public function testGenerateMoveFormListsHiddenFoldersAndFiltersOnTheGroup(): void
    {
        $this->setContext($this->admin(), $this->course(1));
        $group = $this->group();
        $moving = $this->work(['title' => 'wk2_moving']);
        $hidden = $this->folder(['title' => 'wk2_hidden_folder', 'active' => 0]);
        $inGroup = $this->folder(['title' => 'wk2_group_folder', 'post_group_id' => $group]);
        $plain = $this->folder(['title' => 'wk2_plain_folder']);
        foreach ([$moving, $hidden, $inGroup, $plain] as $id) {
            $this->publish($id, 1);
        }

        $html = generateMoveForm($moving, '/', ['real_id' => 1], 0, 0);
        $this->assertStringContainsString('wk2_hidden_folder', $html, 'a hidden folder (active 0) is a valid target');
        $this->assertStringContainsString('wk2_plain_folder', $html);
        $this->assertStringNotContainsString('wk2_group_folder', $html);

        $groupHtml = generateMoveForm($moving, '/', ['real_id' => 1], $group, 0);
        $this->assertStringContainsString('wk2_group_folder', $groupHtml);
        $this->assertStringNotContainsString('wk2_plain_folder', $groupHtml);
        $this->assertStringNotContainsString('wk2_hidden_folder', $groupHtml);
    }

    public function testUsersWithoutPublicationInASessionLookAtTheSubmissionsLinkedToThatSession(): void
    {
        $sessionId = $this->session();
        $this->setContext($this->admin(), $this->course(1), $sessionId);
        $this->enrol($sessionId, $this->student());
        $this->enrol($sessionId, $this->other());
        $this->enrol($sessionId, $this->admin());
        $task = $this->folder();
        $this->publish($task, 1, 2, $sessionId);
        $this->publish($this->work(['parent_id' => $task, 'user_id' => $this->student()->getId()]), 1, 2, $sessionId);

        $ids = array_map(static fn (array $r): int => (int) $r[3], get_list_users_without_publication($task));

        $this->assertSame([$this->other()->getId()], $ids, 'the author of a submission of the session is not listed; staff never is');
        $row = get_list_users_without_publication($task)[0];
        $this->assertSame($this->other()->getLastname(), $row[0]);
        $this->assertSame($this->other()->getFirstname(), $row[1]);
        $this->assertSame([], get_list_users_without_publication($task, $this->student()->getId()));
        $this->assertCount(1, get_list_users_without_publication($task, $this->other()->getId()));
    }

    public function testUsersWithoutPublicationInASessionIgnoreSubmissionsOfOtherSessionsAndDeletedOnes(): void
    {
        $sessionId = $this->session();
        $otherSession = $this->session();
        $this->setContext($this->admin(), $this->course(1), $sessionId);
        $this->enrol($sessionId, $this->student());
        $this->enrol($sessionId, $this->other());
        $task = $this->folder();
        $this->publish($task, 1, 2, $sessionId);
        // The student only submitted in another session; the other learner's submission was deleted (active 2).
        $this->publish($this->work(['parent_id' => $task, 'user_id' => $this->student()->getId()]), 1, 2, $otherSession);
        $this->publish($this->work(['parent_id' => $task, 'user_id' => $this->other()->getId(), 'active' => 2]), 1, 2, $sessionId);

        $ids = array_map(static fn (array $r): int => (int) $r[3], get_list_users_without_publication($task));
        sort($ids);
        $expected = [$this->student()->getId(), $this->other()->getId()];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function testWorkUserListFromDocumentsListsSubmissionsAndTheDocumentsStillToDo(): void
    {
        $this->setContext($this->student(), $this->course(1));
        $folder = $this->folder(['title' => 'wk2_task']);
        $this->publish($folder, 1);
        $done = $this->document('wk2_b_done');
        $todo = $this->document('wk2_a_todo');
        addDocumentToWork($done, $folder, 1);
        addDocumentToWork($todo, $folder, 1);
        $me = $this->student()->getId();
        $mine = $this->work(['parent_id' => $folder, 'user_id' => $me, 'document_id' => $done, 'title' => 'wk2_b_mine', 'qualification' => 5, 'contains_file' => 1]);
        $this->publish($mine, 1);
        // Not listed: someone else's submission, a deleted one, a folder, and one in another session.
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $this->other()->getId(), 'document_id' => $todo]), 1);
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $me, 'active' => 2, 'title' => 'wk2_deleted']), 1);
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $me, 'filetype' => 'folder', 'title' => 'wk2_sub_folder']), 1);
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $me, 'title' => 'wk2_other_session']), 1, 2, $this->session());

        $rows = get_work_user_list_from_documents(0, 10, 'title', 'asc', $folder, $me);

        $this->assertSame(2, (int) get_work_user_list_from_documents(0, 10, 'title', 'asc', $folder, $me, '', true));
        $this->assertCount(2, $rows);
        // The document of the work that was not submitted yet shows as a row to add; the submitted one is the work.
        $this->assertSame($todo, (int) $rows[0]['document_id']);
        $this->assertSame('wk2_a_todo', $rows[0]['title']);
        $this->assertSame(0, (int) $rows[0]['iid']);
        $this->assertSame($me, (int) $rows[0]['user_id']);
        $this->assertSame($this->student()->getFirstname(), $rows[0]['firstname']);
        $this->assertSame($this->student()->getLastname(), $rows[0]['lastname']);
        $this->assertStringContainsString('document_id='.$todo, $rows[0]['actions']);
        $this->assertSame($mine, (int) $rows[1]['iid']);
        $this->assertSame($done, (int) $rows[1]['document_id']);
        $this->assertSame($folder, (int) $rows[1]['parent_id']);
        $this->assertSame('wk2_b_mine', $rows[1]['title']);
        $this->assertStringContainsString('download.php', $rows[1]['actions'], 'a submission with a file can be downloaded');
        $this->assertStringContainsString('view.php?', $rows[1]['actions']);

        $desc = get_work_user_list_from_documents(0, 10, 'title', 'desc', $folder, $me);
        $this->assertSame([$mine, 0], array_map(static fn (array $r): int => (int) $r['iid'], $desc));
        $page = get_work_user_list_from_documents(1, 1, 'title', 'asc', $folder, $me);
        $this->assertSame([$mine], array_map(static fn (array $r): int => (int) $r['iid'], $page));
    }

    public function testWorkUserListFromDocumentsInASessionOnlySeesWhatIsLinkedToIt(): void
    {
        $sessionId = $this->session();
        $this->setContext($this->student(), $this->course(1), $sessionId);
        $folder = $this->folder();
        $this->publish($folder, 1, 2, $sessionId);
        $doc = $this->document('wk2_session_doc');
        addDocumentToWork($doc, $folder, 1);
        $me = $this->student()->getId();
        $this->publish($this->work(['parent_id' => $folder, 'user_id' => $me, 'document_id' => $doc]), 1);

        $rows = get_work_user_list_from_documents(0, 10, 'title', 'asc', $folder, $me);

        $this->assertCount(1, $rows, 'the base-course submission does not count in the session: the document is still to do');
        $this->assertSame(0, (int) $rows[0]['iid']);
        $this->assertSame($doc, (int) $rows[0]['document_id']);

        // An assignment that only exists in the base course offers its documents nowhere in the session.
        $baseFolder = $this->folder();
        $this->publish($baseFolder, 1);
        addDocumentToWork($this->document('wk2_base_doc'), $baseFolder, 1);
        $this->assertSame([], get_work_user_list_from_documents(0, 10, 'title', 'asc', $baseFolder, $me));
    }

    public function testWorkPathIsTheStoredFileOfTheWorkInTheCurrentCourse(): void
    {
        $this->setContext($this->student(), $this->course(1));
        $work = $this->work(['title' => 'wk2_path']);
        $this->publish($work, 1);
        $nodeId = (int) $this->field($work, 'resource_node_id');
        $this->conn->executeStatement(
            'INSERT INTO resource_file (resource_node_id, title, original_name, size, created_at, updated_at)
             VALUES (?, ?, ?, 3, NOW(), NOW())',
            [$nodeId, 'wk2_path.txt', 'wk2_path.txt']
        );
        $other = $this->work(['title' => 'wk2_path_other_course']);
        $this->publish($other, 2);
        $textOnly = $this->work(['title' => 'wk2_path_text']);
        $this->publish($textOnly, 1);

        $path = get_work_path($work);

        $this->assertIsString($path);
        $this->assertNotSame('', $path);
        $this->assertStringContainsString('wk2_path.txt', $path);
        $this->assertSame('', get_work_path($textOnly), 'a work without a file has no path');
        $this->assertSame(-1, get_work_path($other), 'a work of another course is not reachable');
        $this->assertSame(-1, get_work_path(2000000000));
    }

    private function group(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_group_info (title, status, max_student, doc_state, calendar_state, work_state,
                announcements_state, forum_state, wiki_state, chat_state, self_registration_allowed,
                self_unregistration_allowed, document_access)
             VALUES (?, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)',
            ['wk2_group_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

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

    private function enrol(int $sessionId, User $user, int $courseId = 1): void
    {
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, 0, 1, 0, 0)',
            [$user->getId(), $sessionId, $courseId]
        );
    }

    private function document(?string $title = null): int
    {
        $this->conn->insert('c_document', ['title' => $title ?? 'wk_doc_'.(++$this->seq), 'filetype' => 'file', 'readonly' => 0, 'template' => 0]);

        return (int) $this->conn->lastInsertId();
    }
}
