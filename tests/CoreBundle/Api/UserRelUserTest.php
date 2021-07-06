<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * @covers \UserRelUser
 */
class UserRelUserTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAddFriend(): void
    {
        self::bootKernel();

        $user = $this->createUser('user');
        $friend = $this->createUser('friend');

        $em = self::getContainer()->get('doctrine')->getManager();
        $userRepo = self::getContainer()->get(UserRepository::class);

        $tokenTest = $this->getUserToken(
            [
                'username' => 'user',
                'password' => 'user',
            ]
        );

        // 1. user sends request to friend.
        $response = $this->createClientWithCredentials($tokenTest)->request(
            'POST',
            '/api/user_rel_users',
            [
                'json' => [
                    'user' => $user->getIri(),
                    'friend' => $friend->getIri(),
                    'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/UserRelUser',
                '@type' => 'UserRelUser',
                'user' => [
                    '@id' => $user->getIri(),
                ],
                'friend' => [
                    '@id' => $friend->getIri(),
                ],
                'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST,
            ]
        );

        $id = $response->toArray()['@id'];

        // 2. friend accepts request from user
        $tokenFriend = $this->getUserToken(
            [
                'username' => 'friend',
                'password' => 'friend',
            ],
            true
        );

        $this->createClientWithCredentials($tokenFriend)->request(
            'PUT',
            $id,
            [
                'json' => [
                    'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/UserRelUser',
                '@type' => 'UserRelUser',
                'user' => [
                    '@id' => $user->getIri(),
                ],
                'friend' => [
                    '@id' => $friend->getIri(),
                ],
                'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND,
            ]
        );

        // User has a new friend.
        /** @var User $user */
        $user = $userRepo->find($user->getId());
        $this->assertSame(1, $user->getFriends()->count());
        /** @var UserRelUser $userRelUser */
        $userRelUser = $user->getFriends()->first();
        $this->assertSame(UserRelUser::USER_RELATION_TYPE_FRIEND, $userRelUser->getRelationType());

        // friend has a new friend
        /** @var User $friend */
        $friend = $userRepo->find($friend->getId());
        /** @var UserRelUser $userRelUser */
        $userRelUser = $friend->getFriends()->first();
        $this->assertSame(1, $friend->getFriends()->count());
        $this->assertSame(UserRelUser::USER_RELATION_TYPE_FRIEND, $userRelUser->getRelationType());

        $em->clear();

        // 3. friend removes user :(
        $this->createClientWithCredentials($tokenFriend)->request(
            'DELETE',
            $id,
        );
        $this->assertResponseIsSuccessful();

        // 4. user has no friends :(
        /** @var User $user */
        $user = $userRepo->find($user->getId());
        $this->assertSame(0, $user->getFriends()->count());
        $this->assertSame(0, $user->getFriendsWithMe()->count());
    }

    public function testAddFriendAccess(): void
    {
        self::bootKernel();

        $user = $this->createUser('user');
        $friend = $this->createUser('friend');
        $friend2 = $this->createUser('friend2');

        $userRepo = self::getContainer()->get(UserRepository::class);

        // user adds friend
        $user->addFriend($friend);
        $userRepo->update($user);
        $userRelUser = $user->getFriends()->first();

        $this->assertSame(1, $user->getFriends()->count());

        // friend2 can get the friend list from user
        $tokenFriend2 = $this->getUserToken(
            [
                'username' => 'friend2',
                'password' => 'friend2',
            ]
        );

        $this->createClientWithCredentials($tokenFriend2)->request(
            'GET',
            '/api/user_rel_users',
            [
                'json' => [
                    'user' => $user->getIri(),
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/UserRelUser',
                '@type' => 'hydra:Collection',
                'hydra:totalItems' => 1,
            ]
        );

        // friend2 tries to add a friend to user, this should not be possible!
        $this->createClientWithCredentials($tokenFriend2)->request(
            'POST',
            '/api/user_rel_users',
            [
                'json' => [
                    'user' => $user->getIri(),
                    'friend' => $friend->getIri(),
                    'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST,
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        // friend2 tries to delete the relation with  friend :(
        $this->createClientWithCredentials($tokenFriend2)->request(
            'DELETE',
            '/api/user_rel_users/'.$userRelUser->getId()
        );

        $this->assertResponseStatusCodeSame(403);
    }
}
