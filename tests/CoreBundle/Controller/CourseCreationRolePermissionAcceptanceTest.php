<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Permission;
use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Chamilo\CoreBundle\Entity\Role;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

/**
 * Transaction-isolated acceptance tests for role-controlled course creation.
 * A blank title reaches request validation only when authorization passes.
 * No course is written or external email is sent by these tests.
 */
final class CourseCreationRolePermissionAcceptanceTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testHrPostRequiresGrant(): void
    {
        $client = self::createClient();
        $this->enableCourseCreation();
        $user = $this->createUser('qa2540_hr_'.uniqid(), '', '', 'ROLE_HR');
        $this->setGrant('HR', false);
        $client->loginUser($user);

        self::assertSame('role', $this->getCapability($client)['limitSource']);
        self::assertSame(403, $this->postEmptyTitle($client));

        $this->setGrant('HR', true);
        $capability = $this->getCapability($client);
        self::assertTrue($capability['canCreate']);
        self::assertNotSame('role', $capability['limitSource']);
        self::assertSame(400, $this->postEmptyTitle($client));
    }

    public function testStudentBossPostRequiresGrant(): void
    {
        $client = self::createClient();
        $this->enableCourseCreation();
        $user = $this->createUser('qa2540_boss_'.uniqid(), '', '', 'ROLE_STUDENT_BOSS');
        $this->setGrant('STUDENT_BOSS', false);
        $client->loginUser($user);

        self::assertSame('role', $this->getCapability($client)['limitSource']);
        self::assertSame(403, $this->postEmptyTitle($client));

        $this->setGrant('STUDENT_BOSS', true);
        self::assertTrue($this->getCapability($client)['canCreate']);
        self::assertSame(400, $this->postEmptyTitle($client));
    }

    public function testGrantedRoleStillRespectsGlobalSetting(): void
    {
        $client = self::createClient();
        $this->enableCourseCreation();
        $user = $this->createUser('qa2540_setting_'.uniqid(), '', '', 'ROLE_HR');
        $this->setGrant('HR', true);
        $client->loginUser($user);

        self::assertTrue($this->getCapability($client)['canCreate']);
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('workflows.allow_users_to_create_courses', 'false')
        ;
        $capability = $this->getCapability($client);
        self::assertFalse($capability['canCreate']);
        self::assertSame('setting', $capability['limitSource']);
        self::assertSame(403, $this->postEmptyTitle($client));
    }

    public function testSessionManagerPermissionIsNotImplicit(): void
    {
        $client = self::createClient();
        $this->enableCourseCreation();
        $user = $this->createUser('qa2540_manager_'.uniqid(), '', '', 'ROLE_SESSION_MANAGER');
        $this->setGrant('SESSION_MANAGER', false);
        $client->loginUser($user);

        self::assertSame('role', $this->getCapability($client)['limitSource']);
        $this->setGrant('SESSION_MANAGER', true);
        self::assertTrue($this->getCapability($client)['canCreate']);
    }

    public function testStudentIsDeniedWithoutGrant(): void
    {
        $client = self::createClient();
        $this->enableCourseCreation();
        $user = $this->createUser('qa2540_student_'.uniqid(), '', '', 'ROLE_STUDENT');
        $this->setGrant('STUDENT', false);
        $client->loginUser($user);

        self::assertSame('role', $this->getCapability($client)['limitSource']);
        self::assertSame(403, $this->postEmptyTitle($client));
    }

    public function testTeacherAndAdminKeepPriorAccess(): void
    {
        $client = self::createClient();
        $this->enableCourseCreation();
        $teacher = $this->createUser('qa2540_teacher_'.uniqid(), '', '', 'ROLE_TEACHER');
        $this->setGrant('TEACHER', false);
        $client->loginUser($teacher);

        self::assertTrue($this->getCapability($client)['canCreate']);
        self::assertSame(400, $this->postEmptyTitle($client));

        $client->loginUser($this->getUser('admin'));
        self::assertSame('admin', $this->getCapability($client)['limitSource']);
    }

    public function testNonAdminCannotEditPermissions(): void
    {
        $client = self::createClient();
        $user = $this->createUser('qa2540_no_admin_'.uniqid(), '', '', 'ROLE_HR');
        $client->loginUser($user);
        $client->request('POST', '/permissions', [
            'permissions' => ['course:create' => ['HR' => 'on']],
        ], [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    private function enableCourseCreation(): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('workflows.allow_users_to_create_courses', 'true')
        ;
    }

    private function getCapability(KernelBrowser $client): array
    {
        $client->request('GET', '/course/create-capability');
        self::assertSame(200, $client->getResponse()->getStatusCode());

        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function postEmptyTitle(KernelBrowser $client): int
    {
        $client->request('POST', '/course/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ], json_encode(['name' => ''], JSON_THROW_ON_ERROR));

        return $client->getResponse()->getStatusCode();
    }

    private function setGrant(string $roleCode, bool $enabled): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $role = $em->getRepository(Role::class)->findOneBy(['code' => $roleCode]);
        $permission = $em->getRepository(Permission::class)->findOneBy(['slug' => 'course:create']);
        self::assertNotNull($role);
        self::assertNotNull($permission);

        $grant = $em->getRepository(PermissionRelRole::class)->findOneBy([
            'role' => $role,
            'permission' => $permission,
        ]);

        if ($enabled) {
            if (null === $grant) {
                $grant = (new PermissionRelRole())
                    ->setRole($role)
                    ->setPermission($permission)
                ;
            }
            $grant->setChangeable(true)->setUpdatedAt(new DateTime());
            $em->persist($grant);
        } elseif (null !== $grant) {
            $em->remove($grant);
        }
        $em->flush();
    }
}
