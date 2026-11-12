<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Exception\NotAllowedException;
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
 * Second pass over the assignments pages: every helper that writes through a work, folder, comment or
 * submission id taken from the request must refuse an id that is not in the course the request is made in,
 * and the state-changing links of the legacy pages carry their own token (CsrfProtectionListener does not
 * cover public/main). Fixtures are raw prepared inserts, tags start with "wp_".
 */
final class WorkAccessControlPagesTest extends KernelTestCase
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
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    private function publishedWork(int $courseId, ?int $parent = null, int $accepted = 1, int $active = 1): int
    {
        $admin = $this->getUser('admin')->getId();
        $this->conn->insert('c_student_publication', [
            'parent_id' => $parent,
            'user_id' => $admin,
            'title' => 'wp_work_'.(++$this->seq),
            'active' => $active,
            'accepted' => $accepted,
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
            [$typeId, $admin, 'wp_node_'.$workId, 'wp-node-'.$workId, 'wp-node-'.$workId.'/', $now, $now, Uuid::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn->lastInsertId();
        $this->conn->update('c_student_publication', ['resource_node_id' => $nodeId], ['iid' => $workId]);
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => null,
            'visibility' => 2,
            'display_order' => 0,
            'resource_type_group' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $workId;
    }

    private function accepted(int $workId): int
    {
        return (int) $this->conn->fetchOne('SELECT accepted FROM c_student_publication WHERE iid = ?', [$workId]);
    }

    private function active(int $workId): int
    {
        return (int) $this->conn->fetchOne('SELECT active FROM c_student_publication WHERE iid = ?', [$workId]);
    }

    public function testWorkIsInCourseIgnoresTheSessionButNotTheCourse(): void
    {
        $id = $this->publishedWork(2);

        $this->assertTrue(workIsInCourse($id, 2));
        $this->assertFalse(workIsInCourse($id, 1));
        $this->assertFalse(workIsInCourse(0, 2));
        $this->assertFalse(workIsInCourse(999999999, 2));
    }

    public function testMakeVisibleAndInvisibleRefuseAWorkOfAnotherCourse(): void
    {
        $foreignHidden = $this->publishedWork(2, null, 0);
        $foreignShown = $this->publishedWork(2, null, 1);

        $this->assertFalse(makeVisible($foreignHidden, ['real_id' => 1]));
        $this->assertSame(0, $this->accepted($foreignHidden), 'a teacher of course 1 must not publish a course 2 submission');

        $this->assertFalse(makeInvisible($foreignShown, ['real_id' => 1]));
        $this->assertSame(1, $this->accepted($foreignShown), 'a teacher of course 1 must not hide a course 2 submission');
    }

    public function testMakeVisibleAndInvisibleStillWorkInTheOwnCourse(): void
    {
        $id = $this->publishedWork(1, null, 0);

        $this->assertTrue(makeVisible($id, ['real_id' => 1]));
        $this->assertSame(1, $this->accepted($id));
        $this->assertTrue(makeInvisible($id, ['real_id' => 1]));
        $this->assertSame(0, $this->accepted($id));
    }

    public function testDeleteWorkItemRefusesAWorkOfAnotherCourse(): void
    {
        $id = $this->publishedWork(2);
        $course = Container::getCourseRepository()->find(1);
        $this->assertNotNull($course, 'fixture course 1 expected');
        // Be a platform administrator in course 1: the only thing left to refuse the delete is the course scope
        // (without a role the delete is refused for the wrong reason and the scope check could be removed unnoticed).
        $admin = $this->getUser('admin');
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()));
        ChamiloSession::write('_user', ['id' => $admin->getId()]);
        ChamiloSession::write('_real_cid', 1);

        $this->assertFalse(deleteWorkItem($id, $course));
        $this->assertSame(1, $this->active($id), 'the foreign submission must not be soft-deleted');
    }

    public function testProtectWorkRefusesAWorkOfAnotherCourseEvenToAnAdministrator(): void
    {
        // The role checks of protectWork() are about the current course: an administrator sitting in course 1 is
        // let through for a course 1 work, but a work id of course 2 taken from the request is refused first.
        $admin = $this->getUser('admin');
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()));
        ChamiloSession::write('_user', ['id' => $admin->getId()]);
        ChamiloSession::write('_real_cid', 1);
        $courseInfo = ['real_id' => 1];
        $mine = $this->publishedWork(1);
        $theirs = $this->publishedWork(2);

        $this->assertTrue(protectWork($courseInfo, $mine));

        $this->expectException(NotAllowedException::class);
        protectWork($courseInfo, $theirs);
    }

    public function testGetFileContentsRefusesASubmissionOfAnotherCourseEvenToAnAdministrator(): void
    {
        $admin = $this->getUser('admin');
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()));
        ChamiloSession::write('_user', ['id' => $admin->getId()]);
        ChamiloSession::write('_real_cid', 1);

        $withFile = function (int $courseId): int {
            $folder = $this->publishedWork($courseId);
            $work = $this->publishedWork($courseId, $folder);
            $nodeId = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_student_publication WHERE iid = ?', [$work]);
            $this->conn->insert('resource_file', [
                'resource_node_id' => $nodeId,
                'title' => 'wp_file.txt',
                'original_name' => 'wp_file.txt',
                'size' => 3,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

            return $work;
        };
        $mine = $withFile(1);
        $theirs = $withFile(2);
        Container::getEntityManager()->clear();

        $this->assertNotFalse(getFileContents($mine, ['real_id' => 1, 'show_score' => 0], 0, false, true), 'a file of the current course is readable');
        $this->assertFalse(getFileContents($theirs, ['real_id' => 1, 'show_score' => 0], 0, false, true), 'a submission id of course 2 is not');
    }

    public function testDeleteDirWorkRefusesAFolderOfAnotherCourse(): void
    {
        $id = $this->publishedWork(2);
        $this->conn->update('c_student_publication', ['filetype' => 'folder'], ['iid' => $id]);

        $this->assertFalse(deleteDirWork($id, 1));
        $this->assertSame(1, $this->active($id), 'the foreign folder must not be deleted');
    }

    public function testProcessWorkFormRefusesAFolderOfAnotherCourse(): void
    {
        $id = $this->publishedWork(2);
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_student_publication');

        $result = processWorkForm(
            ['iid' => $id],
            ['title' => 'wp_upload', 'description' => '', 'contains_file' => 0],
            ['real_id' => 1],
            0,
            0,
            (int) $this->getUser('admin')->getId()
        );

        $this->assertNull($result);
        $this->assertSame($before, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_student_publication'));
    }

    public function testItemMustBeAChildOfTheFolderItIsOpenedFrom(): void
    {
        $folder = $this->publishedWork(1);
        $child = $this->publishedWork(1, $folder);
        $other = $this->publishedWork(1);

        $this->assertTrue(workItemIsInFolder($child, $folder));
        $this->assertFalse(workItemIsInFolder($child, $other));
        $this->assertFalse(workItemIsInFolder($folder, $folder));
        $this->assertFalse(workItemIsInFolder(0, $folder));
    }

    private function comment(int $workId, int $userId): int
    {
        $this->conn->insert('c_student_publication_comment', [
            'work_id' => $workId,
            'user_id' => $userId,
            'comment' => 'wp_comment',
            'sent_at' => '2020-01-01 10:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function testCommentAttachmentCanOnlyBeRemovedByItsAuthorOrAManagerOfThatWork(): void
    {
        $adminId = (int) $this->getUser('admin')->getId();
        $work = $this->publishedWork(1);
        $otherWork = $this->publishedWork(1);
        $comment = $this->comment($work, $adminId);

        $this->assertTrue(workCommentFileIsDeletable($comment, $work, $adminId, false), 'author');
        $this->assertTrue(workCommentFileIsDeletable($comment, $work, $adminId + 1000, true), 'manager');
        $this->assertFalse(workCommentFileIsDeletable($comment, $work, $adminId + 1000, false), 'another learner');
        $this->assertFalse(workCommentFileIsDeletable($comment, $otherWork, $adminId, true), 'comment of another work');
        $this->assertFalse(workCommentFileIsDeletable(999999999, $work, $adminId, true), 'unknown comment');
    }

    public function testGetFileNeverDumpsAnEntityAndRefusesForeignSubmissions(): void
    {
        $foreign = $this->publishedWork(2);

        ob_start();
        $result = getFile($foreign, ['real_id' => 1, 'show_score' => 0]);
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertSame('', $output, 'getFile() used to var_dump() the submission entity and exit');
    }

    public function testOnlyStudentsOfTheCourseCanBeAddedToAnAssignment(): void
    {
        $adminId = (int) $this->getUser('admin')->getId();
        $userId = (int) $this->conn->fetchOne(
            'SELECT id FROM user WHERE id <> ? AND id NOT IN (SELECT user_id FROM course_rel_user WHERE user_id IS NOT NULL) LIMIT 1',
            [$adminId]
        );
        $this->assertGreaterThan(0, $userId, 'fixture: a user without any course');
        $this->conn->insert('course_rel_user', [
            'user_id' => $userId,
            'c_id' => 1,
            'relation_type' => 0,
            'status' => 5,
            'is_tutor' => 0,
            'sort' => 0,
            'user_course_cat' => 0,
            'legal_agreement' => 0,
            'progress' => 0,
        ]);

        $this->assertTrue(workUserIsStudentOfCourse($userId, 1, 0));
        $this->assertFalse(workUserIsStudentOfCourse($userId, 2, 0), 'subscribed to another course only');
        $this->assertFalse(workUserIsStudentOfCourse($userId, 1, 987003), 'not in that session');
        $this->assertFalse(workUserIsStudentOfCourse(0, 1, 0));
    }

    public function testActionTokenIsStableAndChecked(): void
    {
        $token = workActionToken();
        $this->assertNotSame('', $token);
        $this->assertSame($token, workActionToken(), 'opening a second tab must not invalidate the links of the first');

        $_GET = [];
        $this->assertFalse(workActionTokenIsValid(), 'no token');
        $_GET['work_sec_token'] = 'forged';
        $this->assertFalse(workActionTokenIsValid(), 'wrong token');
        $_GET['work_sec_token'] = $token;
        $this->assertTrue(workActionTokenIsValid());
    }

    public function testActionUrlCarriesTheToken(): void
    {
        $url = workActionUrl('http://x.test/main/work/work_list_all.php?cid=1&action=delete');
        $this->assertStringContainsString('&work_sec_token='.workActionToken(), $url);
    }
}
