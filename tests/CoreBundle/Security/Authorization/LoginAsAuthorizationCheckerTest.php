<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authorization;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Security\Authorization\LoginAsAuthorizationChecker;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Authorization policy coverage for "login as" (switch_user).
 *
 * Every decision below is taken with the legacy Container forced to null, reproducing the
 * request ordering in production: the Symfony firewall fires switch_user on kernel.request
 * (priority 8) before LegacyListener (priority 7) wires the legacy Container. The checker
 * must therefore resolve every path natively (User entity + injected repositories), never
 * through legacy helpers that would fatal with "Call to a member function get() on null".
 */
class LoginAsAuthorizationCheckerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    // --- Platform admin paths ---------------------------------------------------------

    public function testGlobalAdminCanImpersonateAdmin(): void
    {
        $impersonator = $this->createUser('login_as_global_admin', '', '', 'ROLE_GLOBAL_ADMIN');
        $target = $this->createUser('login_as_target_admin', '', '', 'ROLE_ADMIN');

        $this->assertTrue($this->decide($impersonator, $target));
    }

    public function testPlainAdminCanImpersonateAdmin(): void
    {
        $impersonator = $this->createUser('login_as_plain_admin', '', '', 'ROLE_ADMIN');
        $target = $this->createUser('login_as_other_admin', '', '', 'ROLE_ADMIN');

        $this->assertTrue($this->decide($impersonator, $target));
    }

    public function testPlainAdminCannotImpersonateGlobalAdmin(): void
    {
        $impersonator = $this->createUser('login_as_plain_admin_2', '', '', 'ROLE_ADMIN');
        $target = $this->createUser('login_as_global_admin_target', '', '', 'ROLE_GLOBAL_ADMIN');

        $this->assertFalse($this->decide($impersonator, $target));
    }

    // --- HR manager (DRH) paths -------------------------------------------------------

    public function testHrManagerCanImpersonateRrhhFollowedStudent(): void
    {
        $drh = $this->createUser('login_as_drh', '', '', 'ROLE_HR');
        $student = $this->createUser('login_as_drh_student');
        $this->linkRrhh($student, $drh);

        $this->assertTrue($this->decide($drh, $student));
    }

    public function testHrManagerCannotImpersonateUnfollowedStudent(): void
    {
        $drh = $this->createUser('login_as_drh_2', '', '', 'ROLE_HR');
        $student = $this->createUser('login_as_unrelated_student');

        $this->assertFalse($this->decide($drh, $student));
    }

    /**
     * Legacy parity: belonging to a session the DRH coaches must NOT grant impersonation
     * rights — only the direct RRHH relation does.
     */
    public function testHrManagerCannotImpersonateUserInCoachedSession(): void
    {
        $drh = $this->createUser('login_as_drh_3', '', '', 'ROLE_HR');
        $student = $this->createUser('login_as_session_student');
        // DRH coaches the session and the student is enrolled, but there is no RRHH relation.
        $this->enrolStudentInDrhSession($student, $drh);

        $this->assertFalse($this->decide($drh, $student));
    }

    /**
     * Privilege-escalation guard: a followed user holding a role the DRH lacks
     * (here ROLE_SESSION_MANAGER) must never be impersonable.
     */
    public function testHrManagerCannotImpersonateFollowedSessionAdmin(): void
    {
        $drh = $this->createUser('login_as_drh_4', '', '', 'ROLE_HR');
        $sessionAdmin = $this->createUser('login_as_followed_session_admin', '', '', 'ROLE_SESSION_MANAGER');
        $this->linkRrhh($sessionAdmin, $drh);

        $this->assertFalse($this->decide($drh, $sessionAdmin));
    }

    public function testHrManagerCannotImpersonateFollowedAdmin(): void
    {
        $drh = $this->createUser('login_as_drh_5', '', '', 'ROLE_HR');
        $admin = $this->createUser('login_as_followed_admin', '', '', 'ROLE_ADMIN');
        $this->linkRrhh($admin, $drh);

        $this->assertFalse($this->decide($drh, $admin));
    }

    /**
     * A teacher is not "higher" than a DRH: ROLE_HR already reaches ROLE_TEACHER through the
     * role hierarchy, so impersonating a followed teacher is not an escalation.
     */
    public function testHrManagerCanImpersonateFollowedTeacher(): void
    {
        $drh = $this->createUser('login_as_drh_6', '', '', 'ROLE_HR');
        $teacher = $this->createUser('login_as_followed_teacher', '', '', 'ROLE_TEACHER');
        $this->linkRrhh($teacher, $drh);

        $this->assertTrue($this->decide($drh, $teacher));
    }

    // --- Helpers ----------------------------------------------------------------------

    /**
     * Runs canLoginAs() with the legacy Container forced to null, proving the decision is
     * free of any legacy-Container dependency.
     */
    private function decide(User $impersonator, User $target): bool
    {
        /** @var LoginAsAuthorizationChecker $checker */
        $checker = self::getContainer()->get(LoginAsAuthorizationChecker::class);

        $previous = Container::$container;
        Container::$container = null;

        try {
            return $checker->canLoginAs($impersonator, $target);
        } finally {
            Container::$container = $previous;
        }
    }

    private function linkRrhh(User $student, User $drh): void
    {
        $em = $this->getEntityManager();
        $em->persist(
            (new UserRelUser())
                ->setUser($student)
                ->setFriend($drh)
                ->setRelationType(UserRelUser::USER_RELATION_TYPE_RRHH)
        );
        $em->flush();
    }

    private function enrolStudentInDrhSession(User $student, User $drh): void
    {
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $course = $this->createCourse('LoginAs DRH course');
        $session = $this->createSession('LoginAs DRH session');
        $session
            ->addCourse($course)
            ->addUserInSession(Session::DRH, $drh)
        ;
        $sessionRepo->update($session);

        $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $session);
        $sessionRepo->update($session);
    }
}
