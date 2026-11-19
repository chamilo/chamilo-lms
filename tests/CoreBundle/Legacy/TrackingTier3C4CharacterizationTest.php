<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Tracking;

/**
 * Wave 3 characterization tests (second part) for the Tracking library: they pin what the existing tests
 * left open before the raw SQL of the library is rewritten with the QueryBuilder.
 */
final class TrackingTier3C4CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private Connection $conn;
    private User $alice;
    private User $bob;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setManager($this->getEntityManager());
        $this->conn = $this->getEntityManager()->getConnection();
        $this->alice = $this->createUser('c4_alice');
        $this->bob = $this->createUser('c4_bob');
        $this->course = $this->createCourse('c4_course');
    }

    public function testAverageLpProgressForStudentListKeepsOneProgressPerLearningPathAndSession(): void
    {
        $lp = $this->insertLp('c4_lp');
        $cid = (int) $this->course->getId();
        $alice = (int) $this->alice->getId();
        // The same learning path in the base context (80) and in a session (20): two groups, 80 + 20 averaged = 50.
        $this->insertLpView($alice, $lp, $cid, 80, null);
        $this->insertLpView($alice, $lp, $cid, 20, (int) $this->createSession('c4_session')->getId());

        $this->assertEqualsWithDelta(50.0, Tracking::getAverageLearningPathProgressForStudentList([$alice]), 0.0001);
    }

    private function insertLp(string $title): int
    {
        $this->conn->insert('c_lp', [
            'lp_type' => 1,
            'title' => $title,
            'path' => '',
            'force_commit' => 0,
            'content_maker' => '',
            'content_license' => '',
            'js_lib' => '',
            'debug' => 0,
            'theme' => '',
            'author' => '',
            'prerequisite' => 0,
            'hide_toc_frame' => 0,
            'seriousgame_mode' => 0,
            'autolaunch' => 0,
            'max_attempts' => 0,
            'subscribe_users' => 0,
            'created_on' => '2001-01-01 00:00:00',
            'modified_on' => '2001-01-01 00:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function insertLpView(int $userId, int $lpId, int $courseId, ?int $progress, ?int $sessionId = null): int
    {
        $this->conn->insert('c_lp_view', [
            'user_id' => $userId,
            'lp_id' => $lpId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'view_count' => 1,
            'last_item' => 0,
            'progress' => $progress,
        ]);

        return (int) $this->conn->lastInsertId();
    }
}
