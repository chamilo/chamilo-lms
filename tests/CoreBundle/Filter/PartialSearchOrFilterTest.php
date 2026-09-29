<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Filter;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * GET /api/users applies PartialSearchOrFilter's portal scoping. A ROLE_GLOBAL_ADMIN registered
 * in the topmost URL of a tree ("unrestricted") must still see every user, unchanged. One
 * registered only in a non-root URL must be scoped to that URL's subtree specifically -- not
 * merely to whichever URL the request happens to arrive on, which is how a non-admin user is
 * scoped (their own portal IS the URL they're browsing; an admin's authority is a property of
 * their own registration, not of the request host).
 */
class PartialSearchOrFilterTest extends WebTestCase
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
            ->setUrl('https://partial-search-filter-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        return $child;
    }

    /**
     * @return string[] usernames present in the hydra:member collection
     */
    private function requestUsernames(KernelBrowser $client): array
    {
        $client->request('GET', '/api/users', ['itemsPerPage' => 1000], [], ['HTTP_ACCEPT' => 'application/ld+json']);
        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        return array_column($data['hydra:member'] ?? [], 'username');
    }

    public function testScopedGlobalAdminOnlySeesUsersInTheirOwnSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('psof_scoped_admin', $child, 'ROLE_GLOBAL_ADMIN');
        $childUser = $this->createUserOnUrl('psof_child_user', $child);
        $rootOnlyUser = $this->createUserOnUrl('psof_root_only_user', $this->getAccessUrl());

        $client->loginUser($scopedAdmin);
        $usernames = $this->requestUsernames($client);

        $this->assertContains($childUser->getUsername(), $usernames);
        $this->assertNotContains($rootOnlyUser->getUsername(), $usernames);
    }

    public function testUnrestrictedGlobalAdminSeesUsersFromEveryUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $rootAdmin = $this->createUserOnUrl('psof_root_admin', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');
        $childUser = $this->createUserOnUrl('psof_child_user_2', $child);
        $rootOnlyUser = $this->createUserOnUrl('psof_root_only_user_2', $this->getAccessUrl());

        $client->loginUser($rootAdmin);
        $usernames = $this->requestUsernames($client);

        $this->assertContains($childUser->getUsername(), $usernames);
        $this->assertContains($rootOnlyUser->getUsername(), $usernames);
    }
}
