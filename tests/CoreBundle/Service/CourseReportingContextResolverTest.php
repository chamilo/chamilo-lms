<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContext;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization of CourseReportingContextResolver::resolve(): who may open the course reporting of a course or
 * session (administrators, teachers and coaches of the context, human resources managers linked to it) and which
 * ids of the request are accepted. Users are "rp_" rows; the request is a pushed Request with cid/sid/gid.
 */
final class CourseReportingContextResolverTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;
    use ReportingResourceFixturesTrait;

    private CourseReportingContextResolver $resolver;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->resolver = self::getContainer()->get(CourseReportingContextResolver::class);
    }

    /**
     * @param array<string, int> $query
     */
    private function resolveAs(?int $userId, array $query): CourseReportingContext
    {
        $this->fresh();
        $stack = self::getContainer()->get('request_stack');
        while (null !== $stack->getCurrentRequest()) {
            $stack->pop();
        }
        $stack->push(new Request($query));
        $storage = self::getContainer()->get('security.token_storage');
        if (null === $userId) {
            $storage->setToken(null);
        } else {
            $user = $this->entityUser($userId);
            $storage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        }

        return $this->resolver->resolve();
    }

    private function assertDenied(?int $userId, array $query, string $why): void
    {
        try {
            $this->resolveAs($userId, $query);
            $this->fail($why);
        } catch (AccessDeniedHttpException) {
            $this->addToAssertionCount(1);
        }
    }

    private function userWithRole(string $tag, string $role, int $status = 5): int
    {
        $id = $this->rawUser($tag, $status);
        $this->conn()->executeStatement('UPDATE user SET roles = ? WHERE id = ?', [json_encode([$role]), $id]);

        return $id;
    }

    // ------------------------------------------------------------ request ids

    public function testCourseIdentifierIsMandatoryAndMustExist(): void
    {
        try {
            $this->resolveAs(1, []);
            $this->fail('no cid');
        } catch (BadRequestHttpException) {
            $this->addToAssertionCount(1);
        }
        try {
            $this->resolveAs(1, ['cid' => 999999999]);
            $this->fail('unknown course');
        } catch (NotFoundHttpException) {
            $this->addToAssertionCount(1);
        }
        $this->expectException(NotFoundHttpException::class);
        $this->resolveAs(1, ['cid' => 1, 'sid' => 999999999]);
    }

    public function testASessionMustContainTheCourseAndAGroupMustBelongToTheContext(): void
    {
        $session = $this->rawSession('r3cr1');
        $this->assertDenied(1, ['cid' => 1, 'sid' => $session], 'the session does not contain the course');
        $this->sessionCourse($session, 1);
        $context = $this->resolveAs(1, ['cid' => 1, 'sid' => $session]);
        $this->assertSame($session, $context->sessionId());

        $other = $this->newGroup('r3_cr_group_other', 2);
        $this->assertDenied(1, ['cid' => 1, 'gid' => $other], 'a group of another course');
        $mine = $this->newGroup('r3_cr_group', 1);
        $this->assertSame($mine, $this->resolveAs(1, ['cid' => 1, 'gid' => $mine])->groupId);
        $sessionOnly = $this->newGroup('r3_cr_group_session', 1, $session);
        $this->assertDenied(1, ['cid' => 1, 'gid' => $sessionOnly], 'a session group outside the session');
        $this->assertSame($sessionOnly, $this->resolveAs(1, ['cid' => 1, 'sid' => $session, 'gid' => $sessionOnly])->groupId);
    }

    public function testAnonymousVisitorsAreDenied(): void
    {
        $this->assertDenied(null, ['cid' => 1], 'no user');
    }

    // ---------------------------------------------------------------- roles

    public function testAdministratorsAreAdministratorsOfAnyCourse(): void
    {
        $context = $this->resolveAs(1, ['cid' => 2]);

        $this->assertTrue($context->isAdministrator);
        $this->assertSame(2, $context->courseId());
        $this->assertSame([0, 8, 9, 10, 11], $context->hiddenColumnIndexes);
    }

    public function testStudentsAndTeachersOfOtherCoursesAreDenied(): void
    {
        $student = $this->rawUser('r3cr2a');
        $this->courseUser($student, 1, 5);
        $teacherElsewhere = $this->rawUser('r3cr2b', 1);
        $this->courseUser($teacherElsewhere, 2, 1);

        $this->assertDenied($student, ['cid' => 1], 'a learner');
        $this->assertDenied($teacherElsewhere, ['cid' => 1], 'a teacher of another course');
    }

    public function testTheTeacherOfTheCourseIsATeacherWithoutBeingAdministrator(): void
    {
        $teacher = $this->rawUser('r3cr3', 1);
        $this->courseUser($teacher, 1, 1);

        $context = $this->resolveAs($teacher, ['cid' => 1]);

        $this->assertTrue($context->isTeacher);
        $this->assertFalse($context->isAdministrator);
        $this->assertFalse($context->isHumanResourcesManager);
        $this->assertSame($teacher, (int) $context->currentUser->getId());
    }

    public function testSessionCoachesAreTeachersOfTheirSessionOnly(): void
    {
        $mine = $this->rawSession('r3cr4a');
        $theirs = $this->rawSession('r3cr4b');
        foreach ([$mine, $theirs] as $s) {
            $this->sessionCourse($s, 1);
        }
        $general = $this->rawUser('r3cr4c', 1);
        $this->sessionUser($mine, $general, 3);
        $courseCoach = $this->rawUser('r3cr4d', 1);
        $this->sessionCourseUser($mine, 1, $courseCoach, 2);

        $this->assertTrue($this->resolveAs($general, ['cid' => 1, 'sid' => $mine])->isTeacher);
        $this->assertTrue($this->resolveAs($courseCoach, ['cid' => 1, 'sid' => $mine])->isTeacher);
        $this->assertDenied($general, ['cid' => 1, 'sid' => $theirs], 'a coach of another session');
        $this->assertDenied($courseCoach, ['cid' => 1, 'sid' => $theirs], 'a course coach of another session');
        $this->assertDenied($general, ['cid' => 1], 'a session coach is not a teacher of the base course');
    }

    public function testHumanResourcesManagersNeedTheHrRoleAndALinkToTheCourseOrSession(): void
    {
        $hr = $this->userWithRole('r3cr5a', 'ROLE_HR', 4);
        $this->courseUser($hr, 1, 4, 1);
        $linkedSession = $this->rawSession('r3cr5s');
        $this->sessionCourse($linkedSession, 1);
        $hrSession = $this->userWithRole('r3cr5b', 'ROLE_HR', 4);
        $this->sessionUser($linkedSession, $hrSession, 1);
        $noRole = $this->rawUser('r3cr5c', 4);
        $this->courseUser($noRole, 1, 4, 1);
        $unlinked = $this->userWithRole('r3cr5d', 'ROLE_HR', 4);

        $context = $this->resolveAs($hr, ['cid' => 1]);
        $this->assertTrue($context->isHumanResourcesManager);
        $this->assertFalse($context->isTeacher);
        $this->assertTrue($this->resolveAs($hrSession, ['cid' => 1, 'sid' => $linkedSession])->isHumanResourcesManager);
        $this->assertDenied($noRole, ['cid' => 1], 'the relation alone is not enough without ROLE_HR');
        $this->assertDenied($unlinked, ['cid' => 1], 'an HR manager not linked to the course');
        $this->assertDenied($hrSession, ['cid' => 1], 'a session link does not open the base course');
    }
}
