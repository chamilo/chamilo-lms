<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The account status (active) replaces the 1.11.x webservice used by external tools to block and
 * unblock accounts. UserVoter::EDIT also lets users edit their own account, so only an admin may
 * change it — never their own — and only to ACTIVE or INACTIVE: see UserRolesProcessor.
 */
final class UserActiveApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAdminCanBlockAndUnblockAnAccount(): void
    {
        $admin = $this->createUserOnUrl('active_api_admin', 'ROLE_ADMIN');
        $learner = $this->createUserOnUrl('active_api_learner');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($admin));

        $this->patchActive($client, $learner, User::INACTIVE);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['active' => User::INACTIVE]);
        $this->assertSame(User::INACTIVE, $this->reloadActive($learner));

        $this->patchActive($client, $learner, User::ACTIVE);
        $this->assertResponseIsSuccessful();
        $this->assertSame(User::ACTIVE, $this->reloadActive($learner));
    }

    public function testUserCannotChangeTheirOwnStatus(): void
    {
        // A learner can edit their own profile, but must not be able to block their account.
        $learner = $this->createUserOnUrl('active_api_self');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($learner));

        $this->patchActive($client, $learner, User::INACTIVE);
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(User::ACTIVE, $this->reloadActive($learner));
    }

    public function testAdminCannotBlockTheirOwnAccount(): void
    {
        $admin = $this->createUserOnUrl('active_api_self_admin', 'ROLE_ADMIN');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($admin));

        $this->patchActive($client, $admin, User::INACTIVE);
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(User::ACTIVE, $this->reloadActive($admin));
    }

    public function testOnlyActiveOrInactiveCanBeSet(): void
    {
        // Soft deletion (-2) goes through DELETE and the trash, never through the status field.
        $admin = $this->createUserOnUrl('active_api_value_admin', 'ROLE_ADMIN');
        $learner = $this->createUserOnUrl('active_api_value_learner');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($admin));

        $this->patchActive($client, $learner, User::SOFT_DELETED);
        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(User::ACTIVE, $this->reloadActive($learner));
    }

    public function testOwnProfileEditWithUnchangedStatusIsNotBlocked(): void
    {
        $learner = $this->createUserOnUrl('active_api_profile');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($learner));

        $client->request('PATCH', '/api/users/'.$learner->getId(), [
            'headers' => ['content-type' => ['application/merge-patch+json']],
            'json' => ['firstname' => 'Renamed', 'active' => User::ACTIVE],
        ]);
        $this->assertResponseIsSuccessful();
    }

    private function createUserOnUrl(string $username, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $url = $this->getAccessUrl();

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(5)
            ->setPlainPassword($username)
            ->setEmail($username.'@example.com')
            ->setCreator($this->getAdmin())
            ->setCurrentUrl($url)
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $url)
        ;

        if ('' !== $role) {
            $user->addRole($role);
        }

        $repo->updateUser($user);

        return $user;
    }

    private function patchActive(Client $client, User $user, int $active): void
    {
        $client->request('PATCH', '/api/users/'.$user->getId(), [
            'headers' => ['content-type' => ['application/merge-patch+json']],
            'json' => ['active' => $active],
        ]);
    }

    private function reloadActive(User $user): int
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->find(User::class, $user->getId())->getActive();
    }
}
