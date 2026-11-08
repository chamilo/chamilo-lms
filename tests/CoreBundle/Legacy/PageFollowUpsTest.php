<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;

/**
 * Follow-ups left by earlier agents on several legacy pages. Fixtures are raw prepared inserts, tags start
 * with "fu_" (see WorkAccessControlTest for the rationale).
 */
final class PageFollowUpsTest extends KernelTestCase
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
    }

    private function work(int $creatorId, ?int $parent, string $type, int $courseId): int
    {
        $this->conn->insert('c_student_publication', [
            'parent_id' => $parent,
            'user_id' => $creatorId,
            'title' => 'fu_work_'.(++$this->seq),
            'active' => 1,
            'accepted' => 1,
            'post_group_id' => 0,
            'sent_date' => '2020-01-01 10:00:00',
            'filetype' => $type,
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
            [$typeId, $creatorId, 'fu_node_'.$workId, 'fu-node-'.$workId, 'fu-node-'.$workId.'/', $now, $now, hex2bin(str_replace('-', '', (string) Uuid::v4()))]
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

    // Item 1: student_work.php listed every learner's submissions because the repository ignored $user.
    public function testStudentAssignmentsAreScopedToTheRequestedLearner(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);
        $adminId = (int) $this->getUser('admin')->getId();
        $folderId = $this->work($adminId, null, 'folder', 1);
        $mine = $this->work($adminId, $folderId, 'file', 1);
        $other = $this->work(2, $folderId, 'file', 1);
        $em->clear();

        $course = $em->getRepository(Course::class)->find(1);
        $folder = $em->getRepository(CStudentPublication::class)->find($folderId);
        $admin = $em->getRepository(User::class)->find($adminId);

        $all = array_map(static fn ($w) => $w->getIid(), $repo->getStudentAssignments($folder, $course)->getQuery()->getResult());
        sort($all);
        $this->assertSame([$mine, $other], $all, 'without a learner the folder keeps listing all submissions');

        $scoped = array_map(static fn ($w) => $w->getIid(), $repo->getStudentAssignments($folder, $course, null, null, $admin)->getQuery()->getResult());
        $this->assertSame([$mine], $scoped, 'a learner filter must hide the submissions of others');
    }

    // Item 2: work.ajax.php passed the course info array where deleteWorkItem() wants the Course entity (a
    // TypeError), and the id was never scoped: a submission of another course must survive.
    public function testDeleteWorkItemWithTheCourseEntityIgnoresASubmissionOfAnotherCourse(): void
    {
        $adminId = (int) $this->getUser('admin')->getId();
        $folderId = $this->work($adminId, null, 'folder', 2);
        $itemId = $this->work($adminId, $folderId, 'file', 2);
        $this->getEntityManager()->clear();
        $course1 = $this->getEntityManager()->getRepository(Course::class)->find(1);

        $this->assertFalse(deleteWorkItem($itemId, $course1));
        $this->assertSame(
            '1',
            (string) $this->conn->fetchOne('SELECT active FROM c_student_publication WHERE iid = ?', [$itemId]),
            'the submission of course 2 must not be deleted from course 1'
        );
    }

    // Item 5: the banner passed the numeric course id where the course code is expected, so the counter was
    // always false.
    public function testBannerOnlineCounterOfTheCourseUsesTheCourseCode(): void
    {
        require_once __DIR__.'/../../../public/main/inc/lib/banner.lib.php';
        $this->conn->executeStatement('DELETE FROM track_e_online');
        $adminId = (int) $this->getUser('admin')->getId();
        $this->conn->executeStatement(
            'INSERT INTO track_e_online (login_user_id, login_date, user_ip, c_id, session_id, access_url_id)
             VALUES (?, ?, ?, 1, 0, 1)',
            [$adminId, gmdate('Y-m-d H:i:s', time() - 60), '127.0.0.1']
        );
        $code = (string) $this->conn->fetchOne('SELECT code FROM course WHERE id = 1');

        $this->assertSame(1, (int) getOnlineUsersInCourseCount($adminId, ['id' => 1, 'code' => $code]));
    }
}
