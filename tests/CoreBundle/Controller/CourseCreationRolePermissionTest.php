<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Permission;
use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Chamilo\CoreBundle\Entity\Role;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Helpers\PermissionHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

/**
 * A configured course:create grant must be effective without granting a platform role.
 * The trainer/administrator behavior and global creation setting must remain intact.
 */
final class CourseCreationRolePermissionTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testHrAndStudentBossRequireExplicitGrant(): void
    {
        $client = self::createClient();
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('workflows.allow_users_to_create_courses', 'true');

        foreach (['HR' => 'ROLE_HR', 'STUDENT_BOSS' => 'ROLE_STUDENT_BOSS', 'SESSION_MANAGER' => 'ROLE_SESSION_MANAGER'] as $roleCode => $roleName) {
            $user = $this->createUser('qa_2540_'.strtolower($roleCode).'_'.uniqid(), '', '', $roleName);
            $this->setCourseCreateGrant($roleCode, false);

            $permissionHelper = self::getContainer()->get(PermissionHelper::class);
            $courseHelper = self::getContainer()->get(CourseHelper::class);
            self::assertFalse($permissionHelper->hasPermissionForUser('course:create', $user));
            self::assertSame('role', $courseHelper->resolveCourseCreationCapabilityForUser($user)['limitSource']);

            $client->loginUser($user);
            $client->request('GET', '/course/create-capability');
            self::assertSame('role', $this->capability($client)['limitSource']);

            $this->setCourseCreateGrant($roleCode, true);
            self::assertTrue($permissionHelper->hasPermissionForUser('course:create', $user));
            self::assertNotSame('role', $courseHelper->resolveCourseCreationCapabilityForUser($user)['limitSource']);

            // Check the setting before another HTTP request reboots the test kernel.
            $settings = self::getContainer()->get(SettingsManager::class);
            $courseHelper = self::getContainer()->get(CourseHelper::class);
            $settings->updateSetting('workflows.allow_users_to_create_courses', 'false');

            try {
                self::assertSame(
                    'false',
                    $settings->getSetting('workflows.allow_users_to_create_courses', true),
                    'The effective setting must reflect the disabled value.'
                );
                self::assertSame('setting', $courseHelper->resolveCourseCreationCapabilityForUser($user)['limitSource']);
            } finally {
                $settings->updateSetting('workflows.allow_users_to_create_courses', 'true');
            }

            $client->request('GET', '/course/create-capability');
            self::assertNotSame('role', $this->capability($client)['limitSource']);
        }
    }

    public function testPermissionsFormRequiresCsrfToken(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request('POST', '/permissions', [
            'permissions' => ['course:create' => ['HR' => 'on']],
        ], [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testTrainerKeepsPreviousBehaviorWhenMatrixGrantIsAbsent(): void
    {
        self::createClient();
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('workflows.allow_users_to_create_courses', 'true')
        ;
        $teacher = $this->createUser('qa_2540_teacher_'.uniqid(), '', '', 'ROLE_TEACHER');
        $this->setCourseCreateGrant('TEACHER', false);

        self::assertNotSame(
            'role',
            self::getContainer()->get(CourseHelper::class)->resolveCourseCreationCapabilityForUser($teacher)['limitSource']
        );
    }

    public function testUnconfiguredRoleCannotPostCourseCreation(): void
    {
        $client = self::createClient();
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('workflows.allow_users_to_create_courses', 'true')
        ;
        $boss = $this->createUser('qa_2540_denied_'.uniqid(), '', '', 'ROLE_STUDENT_BOSS');
        $this->setCourseCreateGrant('STUDENT_BOSS', false);

        $client->loginUser($boss);
        $client->request('POST', '/course/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ], json_encode(['name' => 'Denied 2540 '.uniqid()], JSON_THROW_ON_ERROR));

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    private function capability($client): array
    {
        self::assertSame(200, $client->getResponse()->getStatusCode());

        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function setCourseCreateGrant(string $roleCode, bool $enabled): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $role = $em->getRepository(Role::class)->findOneBy(['code' => $roleCode]);
        $permission = $em->getRepository(Permission::class)->findOneBy(['slug' => 'course:create']);
        self::assertNotNull($role, 'The role fixtures must include '.$roleCode.'.');
        self::assertNotNull($permission, 'The permission fixtures must include course:create.');

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
