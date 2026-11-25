<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use MySpace;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for MySpace::user_available_in_session() and MySpace::get_user_creator()
 * (public/main/inc/lib/myspace.lib.php). Fixtures start with "msp3_".
 */
final class MySpaceTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/myspace.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function subscribe(User $user, int $sessionId, int $courseId, int $status = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$user->getId(), $sessionId, $courseId, $status]
        );
    }

    private function setStatus(User $user, int $status): void
    {
        $this->conn()->executeStatement('UPDATE user SET status = ? WHERE id = ?', [$status, $user->getId()]);
    }

    public function testUserAvailableInSessionReturnsTheUserIdOfASubscribedStudent(): void
    {
        $course = $this->createCourse('msp3_c1');
        $session = $this->createSession('msp3_s1');
        $student = $this->createUser('msp3_student');
        $this->setStatus($student, 5);
        $this->subscribe($student, (int) $session->getId(), (int) $course->getId());

        $id = MySpace::user_available_in_session('msp3_student', [(int) $course->getId()], (int) $session->getId());

        $this->assertSame((int) $student->getId(), (int) $id);
        $this->assertSame(
            \gettype($this->conn()->fetchOne('SELECT id FROM user WHERE id = ?', [$student->getId()])),
            \gettype($id)
        );
    }

    public function testUserAvailableInSessionLooksAtEveryCourseOfTheListAndStopsAtTheFirstHit(): void
    {
        $c1 = $this->createCourse('msp3_c2a');
        $c2 = $this->createCourse('msp3_c2b');
        $session = $this->createSession('msp3_s2');
        $student = $this->createUser('msp3_student2');
        $this->setStatus($student, 5);
        $this->subscribe($student, (int) $session->getId(), (int) $c2->getId());

        $id = MySpace::user_available_in_session(
            'msp3_student2',
            [(int) $c1->getId(), (string) $c2->getId()],
            (int) $session->getId()
        );

        $this->assertSame((int) $student->getId(), (int) $id);
    }

    public function testUserAvailableInSessionIsZeroWhenSessionCourseOrStatusDoNotMatch(): void
    {
        $course = $this->createCourse('msp3_c3');
        $other = $this->createCourse('msp3_c3b');
        $session = $this->createSession('msp3_s3');
        $otherSession = $this->createSession('msp3_s3b');
        $student = $this->createUser('msp3_student3');
        $teacher = $this->createUser('msp3_teacher3');
        $this->setStatus($student, 5);
        $this->subscribe($student, (int) $session->getId(), (int) $course->getId());
        $this->subscribe($teacher, (int) $session->getId(), (int) $course->getId());

        $this->assertSame(0, MySpace::user_available_in_session('msp3_student3', [(int) $other->getId()], (int) $session->getId()));
        $this->assertSame(0, MySpace::user_available_in_session('msp3_student3', [(int) $course->getId()], (int) $otherSession->getId()));
        // users whose status is not 5 (student) are ignored
        $this->assertSame(0, MySpace::user_available_in_session('msp3_teacher3', [(int) $course->getId()], (int) $session->getId()));
        $this->assertSame(0, MySpace::user_available_in_session('msp3_nobody', [(int) $course->getId()], (int) $session->getId()));
        $this->assertSame(0, MySpace::user_available_in_session('msp3_student3', [], (int) $session->getId()));
    }

    public function testUserAvailableInSessionBindsTheUsername(): void
    {
        $course = $this->createCourse('msp3_c4');
        $session = $this->createSession('msp3_s4');
        $student = $this->createUser('msp3_student4');
        $this->setStatus($student, 5);
        $this->subscribe($student, (int) $session->getId(), (int) $course->getId());

        $this->assertSame(
            0,
            MySpace::user_available_in_session("x' OR '1'='1", [(int) $course->getId()], (int) $session->getId())
        );
    }

    public function testGetUserCreatorListsTheUsersCreatedByAnotherUser(): void
    {
        $me = $this->createUser('msp3_me');
        $other = $this->createUser('msp3_other');
        $mine = $this->createUser('msp3_mine');
        $theirs = $this->createUser('msp3_theirs');
        $orphan = $this->createUser('msp3_orphan');
        $this->conn()->executeStatement('UPDATE user SET creator_id = ? WHERE id = ?', [$me->getId(), $mine->getId()]);
        $this->conn()->executeStatement('UPDATE user SET creator_id = ? WHERE id = ?', [$other->getId(), $theirs->getId()]);
        $this->conn()->executeStatement('UPDATE user SET creator_id = NULL WHERE id = ?', [$orphan->getId()]);
        ChamiloSession::write('_user', ['id' => $me->getId()]);

        $errors = MySpace::get_user_creator([
            ['UserName' => 'msp3_mine'],
            ['UserName' => 'msp3_theirs', 'extra' => 'kept'],
            ['UserName' => 'msp3_orphan'],
            ['UserName' => 'msp3_unknown'],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('msp3_theirs', $errors[0]['UserName']);
        $this->assertSame('kept', $errors[0]['extra']);
        $this->assertSame(get_lang('User is already registered by another tutor.'), $errors[0]['error']);
    }

    public function testGetUserCreatorBindsTheUsername(): void
    {
        $me = $this->createUser('msp3_me2');
        $other = $this->createUser('msp3_other2');
        $theirs = $this->createUser('msp3_theirs2');
        $this->conn()->executeStatement('UPDATE user SET creator_id = ? WHERE id = ?', [$other->getId(), $theirs->getId()]);
        ChamiloSession::write('_user', ['id' => $me->getId()]);

        $this->assertSame([], MySpace::get_user_creator([['UserName' => "x' OR '1'='1"]]));
    }
}
