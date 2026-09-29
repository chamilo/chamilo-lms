<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Access-URL scoping of DELETE /api/users/{id} (UserVoter::DELETE), same rule as EDIT
 * (AccessUrlScopeHelper::canEditUser()): an admin may only delete a user within their scope.
 * See UserDeleteApiTest for the pre-existing, unscoped coverage (soft delete, self-delete
 * refusal, non-admin refusal) this complements rather than duplicates.
 */
class UserDeleteScopeTest extends WebTestCase
{
    use ChamiloTestTrait;

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
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
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();
        $root = $this->getAccessUrl();

        $child = (new AccessUrl())
            ->setUrl('https://user-delete-scope-child-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        return $child;
    }

    public function testScopedAdminCannotDeleteAUserOutsideItsUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $root = $this->getAccessUrl();

        $scopedAdmin = $this->createUserOnUrl('delete_scope_admin_'.uniqid(), $child, 'ROLE_ADMIN');
        $outsideTarget = $this->createUserOnUrl('delete_scope_target_'.uniqid(), $root);

        $client->loginUser($scopedAdmin);
        $client->request(
            'DELETE',
            '/api/users/'.$outsideTarget->getId(),
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(403);

        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
        $stillThere = $repo->find($outsideTarget->getId());
        $this->getEntityManager()->refresh($stillThere);
        $this->assertSame(User::ACTIVE, $stillThere->getActive());
    }

    public function testScopedAdminCanDeleteAUserWithinItsOwnUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();

        $scopedAdmin = $this->createUserOnUrl('delete_scope_admin2_'.uniqid(), $child, 'ROLE_ADMIN');
        $insideTarget = $this->createUserOnUrl('delete_scope_target2_'.uniqid(), $child);

        $client->loginUser($scopedAdmin);
        $client->request(
            'DELETE',
            '/api/users/'.$insideTarget->getId(),
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testPlainAdminCannotDeleteAUserOnAChildUrlEvenThoughAGlobalAdminInTheSameSpotCould(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();

        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $grandchild = (new AccessUrl())
            ->setUrl('https://user-delete-scope-grandchild-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($child)
        ;
        $urlRepo->create($grandchild);

        $plainScopedAdmin = $this->createUserOnUrl('delete_scope_plain_admin_'.uniqid(), $child, 'ROLE_ADMIN');
        $onGrandchildTarget = $this->createUserOnUrl('delete_scope_plain_target_'.uniqid(), $grandchild);

        $client->loginUser($plainScopedAdmin);
        $client->request(
            'DELETE',
            '/api/users/'.$onGrandchildTarget->getId(),
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGlobalAdminCanDeleteAUserOnAChildUrlViaSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();

        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $grandchild = (new AccessUrl())
            ->setUrl('https://user-delete-scope-grandchild2-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($child)
        ;
        $urlRepo->create($grandchild);

        $globalAdmin = $this->createUserOnUrl('delete_scope_global_admin_'.uniqid(), $child, 'ROLE_GLOBAL_ADMIN');
        $onGrandchildTarget = $this->createUserOnUrl('delete_scope_global_target_'.uniqid(), $grandchild);

        $client->loginUser($globalAdmin);
        $client->request(
            'DELETE',
            '/api/users/'.$onGrandchildTarget->getId(),
            [],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
    }
}
