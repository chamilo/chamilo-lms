<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

/**
 * Functional coverage for the subtree-scoped global admin feature across the Multi URLs
 * dashboard (AccessUrlListController) and the URL-management controllers
 * (AccessUrlManageController, AccessUrlUsersController, ...): a ROLE_GLOBAL_ADMIN registered
 * only in a non-root URL must be confined to that URL's subtree, and creating a URL is
 * reserved to an unrestricted admin specifically (registered in the topmost URL).
 */
class AccessUrlScopeFunctionalTest extends WebTestCase
{
    use ChamiloTestTrait;

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $admin = $this->getAdmin();

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword($username)
            ->setEmail($username.'@example.com')
            ->setCreator($admin)
            ->setCurrentUrl($url)
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $url)
        ;

        if ('' !== $role) {
            $user->addRole($role);
        }

        $repo->updateUser($user);

        return $user;
    }

    private function createChildUrl(): AccessUrl
    {
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();
        $root = $this->getAccessUrl();

        $child = (new AccessUrl())
            ->setUrl('https://scope-functional-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        return $child;
    }

    private function postJson(KernelBrowser $client, string $method, string $uri, array $body): void
    {
        $client->request(
            $method,
            $uri,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
            ],
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }

    public function testScopedAdminCannotAssignUsersToAUrlOutsideTheirSubtree(): void
    {
        // createClient() boots the kernel; it must run before any getContainer() call, so
        // the fixture setup below (which needs the container) comes after it.
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('scope_fn_assign_admin', $child, 'ROLE_GLOBAL_ADMIN');
        $root = $this->getAccessUrl();

        $client->loginUser($scopedAdmin);
        $this->postJson($client, 'POST', '/admin/access-urls-users-data', [
            'access_url_id' => $root->getId(),
            'user_ids' => [$scopedAdmin->getId()],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testScopedAdminsUrlListOnlyContainsTheirOwnSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('scope_fn_list_admin', $child, 'ROLE_GLOBAL_ADMIN');
        $root = $this->getAccessUrl();

        $client->loginUser($scopedAdmin);
        $client->request('GET', '/admin/access-urls-users-data');

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $urlIds = array_column($data['urls'], 'id');

        $this->assertContains($child->getId(), $urlIds);
        $this->assertNotContains($root->getId(), $urlIds);
    }

    public function testScopedAdminCannotCreateAUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('scope_fn_create_scoped', $child, 'ROLE_GLOBAL_ADMIN');

        $client->loginUser($scopedAdmin);
        $this->postJson($client, 'POST', '/admin/access-urls-manage-data', [
            'url' => 'https://scope-fn-should-not-exist-'.uniqid().'.example.org/',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnrestrictedAdminCanCreateAUrl(): void
    {
        $client = static::createClient();
        $rootAdmin = $this->createUserOnUrl('scope_fn_create_root', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');

        $client->loginUser($rootAdmin);
        $this->postJson($client, 'POST', '/admin/access-urls-manage-data', [
            'url' => 'https://scope-fn-should-exist-'.uniqid().'.example.org/',
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testScopedAdminCannotSeeAUserOutsideTheirSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('scope_fn_user_admin', $child, 'ROLE_GLOBAL_ADMIN');
        $rootOnlyUser = $this->createUserOnUrl('scope_fn_root_only_user', $this->getAccessUrl());

        $client->loginUser($scopedAdmin);
        $client->request('GET', '/admin/urls-data/users/'.$rootOnlyUser->getId().'/urls');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnrestrictedAdminCanSeeAnyUser(): void
    {
        $client = static::createClient();
        $rootAdmin = $this->createUserOnUrl('scope_fn_root_view_admin', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');
        $anyUser = $this->getAdmin();

        $client->loginUser($rootAdmin);
        $client->request('GET', '/admin/urls-data/users/'.$anyUser->getId().'/urls');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Editing, locking/unlocking and deleting a URL are reserved to unrestricted admins even
     * for a URL the caller otherwise manages content on -- unlike assign()/bulk() on the
     * sibling controllers, isUrlManaged() is deliberately NOT enough here.
     */
    public function testScopedAdminCannotEditAUrlWithinTheirOwnSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('scope_fn_edit_own_admin', $child, 'ROLE_GLOBAL_ADMIN');

        $client->loginUser($scopedAdmin);
        $this->postJson($client, 'PUT', '/admin/access-urls-manage-data/'.$child->getId(), [
            'url' => $child->getUrl(),
            'description' => 'attempted edit by a scoped admin',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testScopedAdminCannotLockAUrlWithinTheirOwnSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('scope_fn_lock_own_admin', $child, 'ROLE_GLOBAL_ADMIN');

        $client->loginUser($scopedAdmin);
        $client->request(
            'POST',
            '/admin/access-urls-manage-data/'.$child->getId().'/lock',
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testScopedAdminCannotDeleteAUrlWithinTheirOwnSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('scope_fn_delete_own_admin', $child, 'ROLE_GLOBAL_ADMIN');

        $client->loginUser($scopedAdmin);
        $client->request(
            'DELETE',
            '/api/access_urls/'.$child->getId(),
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnrestrictedAdminCanEditLockAndDeleteAUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $rootAdmin = $this->createUserOnUrl('scope_fn_edit_root_admin', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');

        $client->loginUser($rootAdmin);

        $this->postJson($client, 'PUT', '/admin/access-urls-manage-data/'.$child->getId(), [
            'url' => $child->getUrl(),
            'description' => 'edited by an unrestricted admin',
        ]);
        $this->assertResponseIsSuccessful();

        $client->request(
            'POST',
            '/admin/access-urls-manage-data/'.$child->getId().'/lock',
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );
        $this->assertResponseIsSuccessful();

        $client->request(
            'DELETE',
            '/api/access_urls/'.$child->getId(),
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );
        $this->assertResponseStatusCodeSame(204);
    }
}
