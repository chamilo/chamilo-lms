<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * api_global_admin_can_edit_admin() decides who may manage another user's account from the
 * admin pages (user edit form, CSV user update, enable/disable, delete, anonymize). With
 * $allow_session_admin, session administrators may manage regular users, but administrator
 * accounts stay reserved to administrators.
 */
final class AdminAccountEditPermissionTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));
    }

    public function testSessionAdminCanManageARegularUser(): void
    {
        $sessionAdmin = $this->createUser('aaep_sm_regular', '', '', 'ROLE_SESSION_MANAGER');
        $student = $this->createUser('aaep_student_regular', '', '', 'ROLE_STUDENT');
        $teacher = $this->createUser('aaep_teacher_regular', '', '', 'ROLE_TEACHER');

        $this->assertTrue(api_global_admin_can_edit_admin($student->getId(), $sessionAdmin->getId(), true));
        $this->assertTrue(api_global_admin_can_edit_admin($teacher->getId(), $sessionAdmin->getId(), true));
    }

    public function testSessionAdminCannotManageAnAdministrator(): void
    {
        $sessionAdmin = $this->createUser('aaep_sm_admin', '', '', 'ROLE_SESSION_MANAGER');
        $admin = $this->createUser('aaep_admin_target', '', '', 'ROLE_ADMIN');

        $this->assertFalse(api_global_admin_can_edit_admin($admin->getId(), $sessionAdmin->getId(), true));
    }

    public function testSessionAdminCannotManageAGlobalAdministrator(): void
    {
        $sessionAdmin = $this->createUser('aaep_sm_global', '', '', 'ROLE_SESSION_MANAGER');
        $globalAdmin = $this->createUser('aaep_global_target', '', '', 'ROLE_GLOBAL_ADMIN');

        $this->assertFalse(api_global_admin_can_edit_admin($globalAdmin->getId(), $sessionAdmin->getId(), true));
    }

    public function testSessionAdminIsNotAnAdministratorWithoutTheFlag(): void
    {
        $sessionAdmin = $this->createUser('aaep_sm_noflag', '', '', 'ROLE_SESSION_MANAGER');
        $student = $this->createUser('aaep_student_noflag', '', '', 'ROLE_STUDENT');

        $this->assertFalse(api_global_admin_can_edit_admin($student->getId(), $sessionAdmin->getId()));
    }

    public function testAdministratorCanManageAnotherAdministratorButNotAGlobalAdministrator(): void
    {
        $admin = $this->createUser('aaep_admin_actor', '', '', 'ROLE_ADMIN');
        $otherAdmin = $this->createUser('aaep_admin_other', '', '', 'ROLE_ADMIN');
        $globalAdmin = $this->createUser('aaep_global_other', '', '', 'ROLE_GLOBAL_ADMIN');

        $this->assertTrue(api_global_admin_can_edit_admin($otherAdmin->getId(), $admin->getId(), true));
        $this->assertFalse(api_global_admin_can_edit_admin($globalAdmin->getId(), $admin->getId(), true));
    }

    public function testRegularUserCannotManageAnyone(): void
    {
        $student = $this->createUser('aaep_student_actor', '', '', 'ROLE_STUDENT');
        $other = $this->createUser('aaep_student_other', '', '', 'ROLE_STUDENT');

        $this->assertFalse(api_global_admin_can_edit_admin($other->getId(), $student->getId(), true));
    }
}
