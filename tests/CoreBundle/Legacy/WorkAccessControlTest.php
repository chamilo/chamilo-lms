<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The relation helpers of work.lib.php (assigned users and documents, move form) no longer scope by course, so
 * the pages calling them must first prove that the work id taken from the request belongs to the current course
 * (and session). These tests pin the guards doing that, and the "no move into itself/descendant" check.
 * Fixtures are raw prepared inserts, tags start with "wa_" (see WorkCharacterizationTest for the rationale).
 */
final class WorkAccessControlTest extends KernelTestCase
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

    private function work(?int $parent = null, string $type = 'file'): int
    {
        $admin = $this->getUser('admin')->getId();
        $this->conn->insert('c_student_publication', [
            'parent_id' => $parent,
            'user_id' => $admin,
            'title' => 'wa_work_'.(++$this->seq),
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

        return (int) $this->conn->lastInsertId();
    }

    private function publish(int $workId, int $courseId, ?int $sessionId = null): void
    {
        $typeId = (int) $this->conn->fetchOne("SELECT id FROM resource_type WHERE title = 'student_publications'");
        $now = '2020-01-01 00:00:00';
        $this->conn->executeStatement(
            'INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?, 0, UNHEX(REPLACE(UUID(), \'-\', \'\')))',
            [$typeId, $this->getUser('admin')->getId(), 'wa_node_'.$workId, 'wa-node-'.$workId, 'wa-node-'.$workId.'/', $now, $now]
        );
        $nodeId = (int) $this->conn->lastInsertId();
        $this->conn->update('c_student_publication', ['resource_node_id' => $nodeId], ['iid' => $workId]);
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'visibility' => 2,
            'display_order' => 0,
            'resource_type_group' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function session(): int
    {
        $this->conn->insert('session', [
            'title' => 'wa_session_'.(++$this->seq).'_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function testWorkOfTheSameCourseIsAccepted(): void
    {
        $id = $this->work(null, 'folder');
        $this->publish($id, 1);

        $this->assertTrue(workBelongsToCourse($id, 1, 0));
    }

    public function testWorkOfAnotherCourseIsRejected(): void
    {
        $id = $this->work(null, 'folder');
        $this->publish($id, 2);

        $this->assertFalse(workBelongsToCourse($id, 1, 0), 'a teacher of course 1 must not reach a course 2 work');
        $this->assertTrue(workBelongsToCourse($id, 2, 0));
    }

    public function testUnknownOrEmptyIdIsRejected(): void
    {
        $this->assertFalse(workBelongsToCourse(999999999, 1, 0));
        $this->assertFalse(workBelongsToCourse(0, 1, 0));
    }

    public function testWorkWithoutResourceLinkIsRejected(): void
    {
        $id = $this->work(null, 'folder');

        $this->assertFalse(workBelongsToCourse($id, 1, 0));
    }

    public function testSessionScoping(): void
    {
        $s1 = $this->session();
        $s2 = $this->session();
        $base = $this->work(null, 'folder');
        $this->publish($base, 1);
        $inSession = $this->work(null, 'folder');
        $this->publish($inSession, 1, $s1);

        $this->assertTrue(workBelongsToCourse($base, 1, $s1), 'base course content is visible inside a session');
        $this->assertTrue(workBelongsToCourse($inSession, 1, $s1));
        $this->assertFalse(workBelongsToCourse($inSession, 1, $s2), 'another session');
        $this->assertFalse(workBelongsToCourse($inSession, 1, 0), 'session content is not base course content');
    }

    public function testDescendantCheck(): void
    {
        $root = $this->work(null, 'folder');
        $child = $this->work($root, 'folder');
        $grandChild = $this->work($child, 'folder');
        $other = $this->work(null, 'folder');

        $this->assertTrue(workIsSameOrDescendantOf($root, $root));
        $this->assertTrue(workIsSameOrDescendantOf($child, $root));
        $this->assertTrue(workIsSameOrDescendantOf($grandChild, $root));
        $this->assertFalse(workIsSameOrDescendantOf($root, $grandChild));
        $this->assertFalse(workIsSameOrDescendantOf($other, $root));
    }

    public function testDescendantCheckSurvivesParentCycle(): void
    {
        $a = $this->work(null, 'folder');
        $b = $this->work($a, 'folder');
        $this->conn->update('c_student_publication', ['parent_id' => $b], ['iid' => $a]);
        $other = $this->work(null, 'folder');

        $this->assertFalse(workIsSameOrDescendantOf($a, $other));
    }

    public function testDocumentGuardRejectsUnknownAndForeignDocuments(): void
    {
        $this->assertFalse(workDocumentBelongsToCourse(0, 1, 0));
        $this->assertFalse(workDocumentBelongsToCourse(999999999, 1, 0));

        $docId = (int) $this->conn->fetchOne('SELECT d.iid FROM c_document d INNER JOIN resource_link l ON l.resource_node_id = d.resource_node_id WHERE l.c_id IS NOT NULL LIMIT 1');
        if ($docId > 0) {
            $courseId = (int) $this->conn->fetchOne('SELECT l.c_id FROM c_document d INNER JOIN resource_link l ON l.resource_node_id = d.resource_node_id WHERE d.iid = ? LIMIT 1', [$docId]);
            $this->assertFalse(workDocumentBelongsToCourse($docId, 987654, 0));
        }
    }
}
