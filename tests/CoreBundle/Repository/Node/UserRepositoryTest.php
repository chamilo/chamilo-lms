<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * @covers \UserRepository
 */
class UserRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCount(): void
    {
        self::bootKernel();
        $count = self::getContainer()->get(UserRepository::class)->count([]);
        // Admin + anon (registered in the DataFixture\AccessUrlAdminFixtures.php)
        $this->assertSame(2, $count);
    }

    public function testCreateUser(): void
    {
        self::bootKernel();
        $this->createUser('user', 'user');

        $count = self::getContainer()->get(UserRepository::class)->count([]);
        // By default there are 2 users: admin + anon.
        $this->assertSame(3, $count);
    }

    public function testAddFriendToUser(): void
    {
        self::bootKernel();
        $manager = self::getContainer()->get('doctrine')->getManager();

        $user = $this->createUser('user', 'user');
        $friend = $this->createUser('friend', 'friend');

        $userRepo = self::getContainer()->get(UserRepository::class);

        // user -> friend
        $user->addFriend($friend);
        $userRepo->update($user);

        $this->assertSame(1, $user->getFriends()->count());
        $this->assertSame('friend', $user->getFriends()->first()->getFriend()->getUsername());
        $this->assertSame(0, $user->getFriendsWithMe()->count());

        $manager->clear();

        // Check friend
        $friend = $userRepo->find($friend->getId());
        $this->assertSame(1, $friend->getFriendsWithMe()->count());
        $this->assertSame(0, $friend->getFriends()->count());

        // another_friend -> user
        $anotherFriend = $this->createUser('anotherfriend', 'anotherfriend');
        $user = $userRepo->find($user->getId());

        $anotherFriend->addFriend($user);
        $userRepo->update($anotherFriend);

        $this->assertSame(1, $anotherFriend->getFriends()->count());
        $this->assertSame('user', $anotherFriend->getFriends()->first()->getFriend()->getUsername());
        $this->assertSame(0, $anotherFriend->getFriendsWithMe()->count());

        $manager->clear();

        /** @var User $user */
        $user = $userRepo->find($user->getId());

        $this->assertSame(1, $user->getFriends()->count());
        $this->assertSame(1, $user->getFriendsWithMe()->count());

        $manager->clear();

        // Delete friend
        $friend = $userRepo->find($friend->getId());
        $userRepo->delete($friend);

        $user = $userRepo->find($user->getId());
        $this->assertSame(0, $user->getFriends()->count());
        $this->assertSame(1, $user->getFriendsWithMe()->count());
    }

    public function testCreateUserWithApi(): void
    {
        $token = $this->getUserToken([]);
        $username = 'test';
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/users',
            [
                'json' => [
                    'username' => $username,
                    'firstname' => 'test',
                    'lastname' => 'test',
                    'website' => '',
                    'biography' => '',
                    'locale' => 'en',
                    'plainPassword' => 'test',
                    'timezone' => 'Europe\Paris',
                    'email' => 'test@example.com',
                    //'expiresAt' => new \DateTime(),
                    'phone' => '123456',
                    'address' => 'Paris',
                    'roles' => [
                        'ROLE_USER',
                    ],
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            'username' => $username,
        ]);
    }

    public function testUpdateUserWithApi(): void
    {
        $user = $this->createUser('test');

        $token = $this->getUserToken([]);

        $this->createClientWithCredentials($token)->request(
            'PUT',
            '/api/users/'.$user->getId(),
            [
                'json' => [
                    'firstname' => 'updated',
                    'lastname' => 'updated',
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            'firstname' => 'updated',
            'lastname' => 'updated',
        ]);
    }
}
