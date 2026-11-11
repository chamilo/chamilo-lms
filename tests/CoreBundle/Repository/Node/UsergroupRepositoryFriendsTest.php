<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Who counts as a friend when a group admin adds users to a social group (SocialController::addUsersToGroup):
 * only confirmed friends and good friends, not pending requests, deleted relations, HR managers or bosses.
 * Fixture users: admin (id 1) befriends the anonymous account (id 2).
 */
final class UsergroupRepositoryFriendsTest extends KernelTestCase
{
    use ChamiloTestTrait;

    /**
     * @dataProvider relationProvider
     */
    public function testOnlyConfirmedFriendshipsCount(int $relationType, bool $isFriend): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(UsergroupRepository::class);
        self::getContainer()->get(Connection::class)->executeStatement(
            'INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at)
             VALUES (1, 2, ?, ?, ?)',
            [$relationType, '2026-01-01 00:00:00', '2026-01-01 00:00:00']
        );

        $this->assertSame($isFriend, \in_array(2, $repo->getConfirmedFriendIds(1), true));
        // The relation is one-way: the other side is not a friend of anyone because of it.
        $this->assertNotContains(1, $repo->getConfirmedFriendIds(2));
    }

    public static function relationProvider(): iterable
    {
        yield 'friend' => [3, true];
        yield 'good friend' => [4, true];
        yield 'deleted' => [6, false];
        yield 'hr manager' => [7, false];
        yield 'boss' => [8, false];
        yield 'friend request' => [10, false];
    }
}
