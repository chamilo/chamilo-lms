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
 * NOT covered, on purpose: get_work_user_list_from_documents() selects the removed c_id / session_id / url
 * columns of c_student_publication (and uses a UNION), so it is an SQL error today; get_work_path() is pinned as
 * such in WorkCharacterizationTest.
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

    private function document(): int
    {
        $this->conn->insert('c_document', ['title' => 'wk_doc_'.(++$this->seq), 'filetype' => 'file', 'readonly' => 0, 'template' => 0]);

        return (int) $this->conn->lastInsertId();
    }
}
