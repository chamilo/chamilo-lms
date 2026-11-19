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
 * Wave 3 characterization tests (third part) for the Tracking library: show_user_progress, getLpStats and the
 * user data move helpers, pinned before their raw SQL is rewritten with the QueryBuilder.
 */
final class TrackingTier3C5CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private Connection $conn;
    private User $alice;
    private User $bob;
    private Course $courseA;
    private Course $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setManager($this->getEntityManager());
        $this->conn = $this->getEntityManager()->getConnection();
        $this->alice = $this->createUser('c5_alice');
        $this->bob = $this->createUser('c5_bob');
        // Titles chosen so that the alphabetical order differs from the creation order.
        $this->courseB = $this->createCourse('c5_zz_course');
        $this->courseA = $this->createCourse('c5_aa_course');
    }

    // ------------------------------------------------------------------
    // show_user_progress
    // ------------------------------------------------------------------

    public function testShowUserProgressListsTheCoursesByTitleWithoutHrRelationsAndTheSessionsOfTheStudent(): void
    {
        $alice = (int) $this->alice->getId();
        $this->courseB->addUserAsStudent($this->alice);
        $this->courseA->addUserAsStudent($this->alice);
        $hr = $this->createCourse('c5_hr_course');
        $hr->addUserAsStudent($this->alice);
        $this->courseB->addUserAsStudent($this->bob);
        $this->getEntityManager()->flush();
        $this->conn->update(
            'course_rel_user',
            ['relation_type' => COURSE_RELATION_TYPE_RRHH],
            ['user_id' => $alice, 'c_id' => $hr->getId()]
        );

        $s1 = $this->createSession('c5_session_1');
        $s2 = $this->createSession('c5_session_2');
        $this->subscribeInSession($alice, (int) $s1->getId(), (int) $this->courseB->getId());
        $this->subscribeInSession($alice, (int) $s1->getId(), (int) $this->courseA->getId());
        $this->subscribeInSession($alice, (int) $s2->getId(), (int) $this->courseA->getId());
        $this->subscribeInSession((int) $this->bob->getId(), (int) $s2->getId(), (int) $this->courseB->getId());

        $result = Tracking::show_user_progress($alice, 0, '', true, true, true);

        // Index 0 holds the ids of the courses of the user (ordered by title, HR relation excluded).
        $this->assertEquals([(int) $this->courseA->getId(), (int) $this->courseB->getId()], $result[0]);
        $this->assertEqualsCanonicalizing([(int) $s1->getId(), (int) $s2->getId(), 0], array_keys($result));
        $this->assertSame('c5_session_1', $result[(int) $s1->getId()]['title']);
        // Within a session the courses are sorted by title (utf8_sort) when no session is requested.
        $this->assertSame(
            [(int) $this->courseA->getId(), (int) $this->courseB->getId()],
            array_map('intval', array_keys($result[(int) $s1->getId()]['course_list']))
        );
        // Bob's subscription is not Alice's.
        $this->assertSame(
            [(int) $this->courseA->getId()],
            array_map('intval', array_keys($result[(int) $s2->getId()]['course_list']))
        );

        // Restricted to one session.
        $only = Tracking::show_user_progress($alice, (int) $s2->getId(), '', true, true, true);
        $this->assertEqualsCanonicalizing([(int) $s2->getId(), 0], array_keys($only));
    }

    public function testShowUserProgressOfAUserWithoutSubscriptionIsEmpty(): void
    {
        $result = Tracking::show_user_progress((int) $this->bob->getId(), 0, '', true, true, true);

        $this->assertSame([0 => []], $result);
    }

    private function subscribeInSession(int $userId, int $sessionId, int $courseId): void
    {
        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'c_id' => $courseId,
            'status' => 0,
            'visibility' => 1,
            'legal_agreement' => 0,
            'progress' => 0,
        ]);
    }
}
