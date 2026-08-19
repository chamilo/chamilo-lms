<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Regression tests for the escalation this feature closes: nothing previously stopped any
 * ROLE_ADMIN from granting ROLE_GLOBAL_ADMIN to themselves or anyone else via
 * PATCH /api/users/{id}. Only a global admin registered in the topmost URL of a tree
 * ("unrestricted") may now do this — see UserRolesProcessor and
 * AccessUrlScopeHelper::canGrantGlobalAdminRole().
 */
final class UserRolesApiSecurityTest extends AbstractApiTest
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
            ->setUrl('https://user-roles-security-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        return $child;
    }

    public function testPlainAdminCannotGrantGlobalAdminRoleToSelf(): void
    {
        $plainAdmin = $this->createUserOnUrl('roles_sec_plain_admin', $this->getAccessUrl(), 'ROLE_ADMIN');

        $token = $this->getUserTokenFromUser($plainAdmin);
        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/users/'.$plainAdmin->getId(),
            [
                'headers' => ['content-type' => ['application/merge-patch+json']],
                'json' => ['roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN']],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSubtreeScopedGlobalAdminCannotGrantGlobalAdminRoleToAnotherUser(): void
    {
        $child = $this->createChildUrl();
        $subtreeAdmin = $this->createUserOnUrl('roles_sec_subtree_admin', $child, 'ROLE_GLOBAL_ADMIN');
        $target = $this->createUserOnUrl('roles_sec_subtree_target', $child, 'ROLE_ADMIN');

        $token = $this->getUserTokenFromUser($subtreeAdmin);
        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/users/'.$target->getId(),
            [
                'headers' => ['content-type' => ['application/merge-patch+json']],
                'json' => ['roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN']],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnrestrictedGlobalAdminCanGrantGlobalAdminRole(): void
    {
        $rootAdmin = $this->createUserOnUrl('roles_sec_root_admin', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');
        $target = $this->createUserOnUrl('roles_sec_root_target', $this->getAccessUrl(), 'ROLE_ADMIN');

        $token = $this->getUserTokenFromUser($rootAdmin);
        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/users/'.$target->getId(),
            [
                'headers' => ['content-type' => ['application/merge-patch+json']],
                'json' => ['roles' => ['ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN']],
            ]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUnrelatedEditToAnExistingGlobalAdminIsNotBlocked(): void
    {
        // The guard must only trigger on newly ADDING the role -- an admin who already has it
        // must still be freely editable on unrelated fields, including by a plain ROLE_ADMIN.
        $plainAdmin = $this->createUserOnUrl('roles_sec_editor', $this->getAccessUrl(), 'ROLE_ADMIN');
        $existingGlobalAdmin = $this->createUserOnUrl('roles_sec_existing_ga', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');

        $token = $this->getUserTokenFromUser($plainAdmin);
        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/users/'.$existingGlobalAdmin->getId(),
            [
                'headers' => ['content-type' => ['application/merge-patch+json']],
                'json' => ['firstname' => 'RenamedByTest'],
            ]
        );

        $this->assertResponseIsSuccessful();
    }
}
