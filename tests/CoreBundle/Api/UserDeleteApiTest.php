<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\UserDeletedEvent;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * DELETE /api/users/{id} used to hit the plain Doctrine remove processor, which tore the
 * row out while skipping everything the platform does on deletion. It now goes through
 * UserManager::delete_user(), so the deletion is the reversible soft delete the admin
 * user list performs, and the USER_DELETED listeners plugins rely on actually run.
 */
class UserDeleteApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testDeleteSoftDeletesTheUserAndNotifiesListeners(): void
    {
        $userId = $this->createUser('user_deleted_over_api')->getId();

        $client = $this->createClientWithCredentials($this->getUserToken());

        $seen = [];
        self::getContainer()->get('event_dispatcher')->addListener(
            Events::USER_DELETED,
            function (UserDeletedEvent $event) use (&$seen): void {
                $seen[] = $event->getDeleteType();
            }
        );

        $client->request('DELETE', '/api/users/'.$userId);

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($seen, 'Deleting a user over the API must notify USER_DELETED listeners.');
        $this->assertSame(
            [UserDeletedEvent::DELETE_TYPE_SOFT],
            array_unique($seen),
            'The API delete must be the soft delete, not a destroying one.'
        );

        $user = self::getContainer()->get(UserRepository::class)->find($userId);

        $this->assertNotNull($user, 'A soft delete keeps the row so the account can be restored.');

        // The soft delete is a raw UPDATE through the DBAL connection, so the entity
        // Doctrine has in memory still carries the old active value.
        $this->getEntityManager()->refresh($user);

        $this->assertSame(User::SOFT_DELETED, $user->getActive());
    }

    public function testDeleteIsRefusedToNonAdmins(): void
    {
        $victim = $this->createUser('victim_of_non_admin');
        $attacker = $this->createUser('non_admin_attacker');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($attacker));
        $client->request('DELETE', '/api/users/'.$victim->getId());

        $this->assertResponseStatusCodeSame(403);

        $repo = self::getContainer()->get(UserRepository::class);
        $stillThere = $repo->find($victim->getId());
        $this->getEntityManager()->refresh($stillThere);

        $this->assertSame(
            User::ACTIVE,
            $stillThere->getActive(),
            'UserVoter grants DELETE to admins only; a non-admin must not reach the processor.'
        );
    }

    public function testDeleteRefusesToDeleteTheCallersOwnAccount(): void
    {
        $client = $this->createClientWithCredentials($this->getUserToken());
        $adminId = $this->getUser('admin')->getId();

        $client->request('DELETE', '/api/users/'.$adminId);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(
            User::ACTIVE,
            self::getContainer()->get(UserRepository::class)->find($adminId)->getActive(),
            'The refusal must leave the account untouched.'
        );
    }
}
