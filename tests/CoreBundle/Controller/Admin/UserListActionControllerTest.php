<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The user list actions (single and bulk) only reach accounts the current administrator
 * is allowed to manage: global administrator accounts are left to global administrators.
 */
class UserListActionControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testAdminBulkActionsSkipGlobalAdministrators(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ula_admin_bulk', '', '', 'ROLE_ADMIN'));
        $globalAdmin = $this->createUser('ula_global_bulk_target', '', '', 'ROLE_GLOBAL_ADMIN');
        $student = $this->createUser('ula_student_bulk_target', '', '', 'ROLE_STUDENT');

        $data = $this->post($client, ['action' => 'disable_users', 'user_ids' => [$globalAdmin->getId(), $student->getId()]]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $data['affected']);
        $this->assertSame([$globalAdmin->getId()], $data['deniedIds']);
        $this->assertSame(User::ACTIVE, $this->reload($globalAdmin)->getActive());
        $this->assertSame(User::INACTIVE, $this->reload($student)->getActive());

        $this->post($client, ['action' => 'delete_users', 'user_ids' => [$globalAdmin->getId()]]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(User::ACTIVE, $this->reload($globalAdmin)->getActive());
    }

    public function testAdminBulkDestroySkipsGlobalAdministrators(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ula_admin_destroy', '', '', 'ROLE_ADMIN'));
        $globalAdmin = $this->createUser('ula_global_destroy_target', '', '', 'ROLE_GLOBAL_ADMIN');

        $this->post($client, ['action' => 'destroy_users', 'user_ids' => [$globalAdmin->getId()]]);

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->reload($globalAdmin));
    }

    /**
     * @dataProvider singleActionProvider
     */
    public function testAdminSingleActionsAreDeniedOnGlobalAdministrators(string $action): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ula_admin_single_'.$action, '', '', 'ROLE_ADMIN'));
        $globalAdmin = $this->createUser('ula_global_single_'.$action, '', '', 'ROLE_GLOBAL_ADMIN');
        if ('restore' === $action) {
            $globalAdmin->setActive(User::SOFT_DELETED);
            $this->getEntityManager()->flush();
        }
        $statusBefore = $globalAdmin->getActive();

        $this->post($client, ['action' => $action, 'user_id' => $globalAdmin->getId()]);

        $this->assertResponseStatusCodeSame(403);
        $refreshed = $this->reload($globalAdmin);
        $this->assertNotNull($refreshed);
        $this->assertSame($statusBefore, $refreshed->getActive());
        $this->assertSame('ula_global_single_'.$action, $refreshed->getUsername());
    }

    public static function singleActionProvider(): array
    {
        return [['delete_user'], ['restore'], ['destroy'], ['anonymize']];
    }

    public function testAdminCanStillManageRegularUsersAndOtherAdministrators(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser('ula_admin_regular', '', '', 'ROLE_ADMIN'));
        $otherAdmin = $this->createUser('ula_admin_regular_other', '', '', 'ROLE_ADMIN');
        $student = $this->createUser('ula_student_regular', '', '', 'ROLE_STUDENT');

        $data = $this->post($client, ['action' => 'disable_users', 'user_ids' => [$otherAdmin->getId(), $student->getId()]]);
        $this->assertSame(2, $data['affected']);
        $this->assertArrayNotHasKey('deniedIds', $data);

        $this->post($client, ['action' => 'delete_user', 'user_id' => $student->getId()]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(User::SOFT_DELETED, $this->reload($student)->getActive());
    }

    public function testGlobalAdminCanStillManageAnotherGlobalAdministrator(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $otherGlobalAdmin = $this->createUser('ula_global_by_global', '', '', 'ROLE_GLOBAL_ADMIN');

        $data = $this->post($client, ['action' => 'disable_users', 'user_ids' => [$otherGlobalAdmin->getId()]]);

        $this->assertSame(1, $data['affected']);
        $this->assertSame(User::INACTIVE, $this->reload($otherGlobalAdmin)->getActive());
    }

    public function testBulkActionsNeverTouchTheCurrentAccount(): void
    {
        $client = static::createClient();
        $self = $this->createUser('ula_admin_self', '', '', 'ROLE_ADMIN');
        $client->loginUser($self);

        $data = $this->post($client, ['action' => 'disable_users', 'user_ids' => [$self->getId()]]);

        $this->assertSame(0, $data['affected']);
        $this->assertSame(User::ACTIVE, $this->reload($self)->getActive());
    }

    private function post(KernelBrowser $client, array $parameters): ?array
    {
        $client->request('POST', '/admin/user-list-action', $parameters, [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        return json_decode((string) $client->getResponse()->getContent(), true);
    }

    private function reload(User $user): ?User
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(User::class)->find($user->getId());
    }
}
