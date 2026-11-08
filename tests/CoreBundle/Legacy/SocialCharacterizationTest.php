<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use SocialManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for SocialManager (social.lib.php).
 *
 * LIVE and working (user_rel_user based): get_relation_between_contacts() (chat, video signalling, vCard export:
 * it decides who may contact / see whom), get_friends() (social.ajax, profile pages).
 *
 * BROKEN/DEAD: everything that reads the `message` table through user_receiver_id/msg_status
 * (getCountMessagesSent/Received, getCountWallPostedMessages, the invitation list/count/accept/deny helpers,
 * getWallMessages): those two columns no longer exist (recipients live in message_rel_user, the status column is
 * `status`), so each call throws. Their callers are `if (false)` blocks, an AJAX action behind the abandoned wall,
 * or nothing at all; the wall itself is the Vue SocialPost feature. Pinned cheaply and marked @deprecated.
 *
 * Fixtures: users 1 (admin), 2 (anonymous), 3 (soft deleted fallback_user) and raw user_rel_user rows (sc_).
 */
final class SocialCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/social/home.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        \ChamiloSession::erase('_user');
    }

    protected function tearDown(): void
    {
        \ChamiloSession::erase('_user');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ------------------------------------------------------------------ get_relation_between_contacts

    public function testRelationIsTheStoredTypeOfTheDirectedPair(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_GOODFRIEND);

        $this->assertSame(
            UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
            SocialManager::get_relation_between_contacts(1, 2)
        );
        // Directed: nothing was stored from 2 to 1.
        $this->assertSame(UserRelUser::USER_UNKNOWN, SocialManager::get_relation_between_contacts(2, 1));
    }

    public function testRelationDistinguishesFriendFromGoodFriendAndDeleted(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_FRIEND);
        $this->rel(2, 1, UserRelUser::USER_RELATION_TYPE_DELETED);

        $this->assertSame(UserRelUser::USER_RELATION_TYPE_FRIEND, SocialManager::get_relation_between_contacts(1, 2));
        $this->assertSame(UserRelUser::USER_RELATION_TYPE_DELETED, SocialManager::get_relation_between_contacts(2, 1));
    }

    public function testRelationOfStrangersIsUnknown(): void
    {
        $this->assertSame(0, SocialManager::get_relation_between_contacts(1, 2));
        $this->assertSame(0, SocialManager::get_relation_between_contacts('1 OR 1=1', 2), 'Ids are cast to int.');
    }

    public function testRelationIgnoresHumanResourcesLinksUnlessAsked(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_RRHH);

        $this->assertSame(UserRelUser::USER_UNKNOWN, SocialManager::get_relation_between_contacts(1, 2));
        // PINNED QUIRK: with $includeRH the type is still looked up in user_friend_relation_type, which only holds
        // ids 1..6, so an HR link (7) is not found either and the answer is "unknown". No caller passes true.
        $this->assertSame(UserRelUser::USER_UNKNOWN, SocialManager::get_relation_between_contacts(1, 2, true));
    }

    public function testRelationPendingFriendRequestIsNotAFriendship(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST);

        $this->assertSame(
            UserRelUser::USER_UNKNOWN,
            SocialManager::get_relation_between_contacts(1, 2),
            'A pending request (10) is not a type the chat / vCard / video checks accept.'
        );
    }

    public function testTeachersAreFriendsWithEveryoneSettingGrantsAdministratorsGoodFriendStatus(): void
    {
        $this->setSetting('social.social_make_teachers_friend_all', 'true');
        \ChamiloSession::write('_user', ['id' => 1]);

        $this->assertSame(
            UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
            SocialManager::get_relation_between_contacts(2, 1),
            'The current user is a platform administrator.'
        );
    }

    public function testStrangersGetUnknownEvenWhenTheTeachersFriendAllSettingIsOn(): void
    {
        $this->setSetting('social.social_make_teachers_friend_all', 'true');

        // The current user is nobody (anonymous): not an administrator, not a teacher of user 2's courses.
        // PINNED QUIRK (security): the function falls off its end and returns NULL, not USER_UNKNOWN. vcard_export.php
        // tests "0 !== get_relation_between_contacts()", and null !== 0, so with this setting on any logged-in
        // stranger passes the relation check and can export another user's vCard. Intended: USER_UNKNOWN (0).
        $this->assertNull(SocialManager::get_relation_between_contacts(1, 2));
    }

    // ------------------------------------------------------------------ get_friends

    public function testGetFriendsListsFriendsAndGoodFriendsOnly(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_FRIEND);
        $this->rel(1, 3, UserRelUser::USER_RELATION_TYPE_DELETED);
        $this->rel(2, 1, UserRelUser::USER_RELATION_TYPE_GOODFRIEND);

        $mine = SocialManager::get_friends(1, null, null, false);

        $this->assertSame([['friend_user_id' => 2]], $mine, 'The deleted contact is not listed.');
        $theirs = SocialManager::get_friends(2, null, null, false);
        $this->assertSame([['friend_user_id' => 1]], $theirs, 'Friendship is directed.');
    }

    public function testGetFriendsExcludesHumanResourcesLinksAndSelf(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_RRHH);
        $this->rel(1, 1, UserRelUser::USER_RELATION_TYPE_FRIEND);

        $this->assertSame([], SocialManager::get_friends(1, null, null, false));
    }

    public function testGetFriendsFiltersByGroupIsTheRelationType(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_FRIEND);
        $this->rel(1, 3, UserRelUser::USER_RELATION_TYPE_GOODFRIEND);

        $this->assertSame([['friend_user_id' => 3]], SocialManager::get_friends(1, UserRelUser::USER_RELATION_TYPE_GOODFRIEND, null, false));
        $this->assertCount(2, SocialManager::get_friends(1, null, null, false));
        $this->assertCount(2, SocialManager::get_friends(1, 0, null, false), 'A zero group means no group filter.');
    }

    public function testGetFriendsSearchByNameFindsNothingBecauseOfAnUnqualifiedColumn(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_FRIEND);   // Anonymous Joe
        $this->rel(1, 3, UserRelUser::USER_RELATION_TYPE_FRIEND);   // User Fallback

        // PINNED QUIRK: the name filter is "friend_user_id IN (SELECT user_id FROM user WHERE firstName LIKE ...)",
        // but `user` has no user_id column, so MySQL resolves it against the OUTER user_rel_user row (the searcher
        // himself): the sub-select yields the searcher's own id whenever ANY user matches the name, and a friend is
        // never returned. Intended: the friends whose first name, last name or glued names match.
        $ids = static fn (array $l) => array_column($l, 'friend_user_id');
        $this->assertSame([], $ids(SocialManager::get_friends(1, null, 'anonym', false)));
        $this->assertSame([], $ids(SocialManager::get_friends(1, null, 'fallb', false)));
        $this->assertSame([], SocialManager::get_friends(1, null, 'nobody-like-this', false));
        $this->assertSame([], SocialManager::get_friends(1, null, '" OR "1"="1', false), 'Quotes are escaped.');
        // An empty-string search still filters (isset, not empty): everything matches "%%" yet no friend returns.
        $this->assertSame([], SocialManager::get_friends(1, null, '', false));
    }

    public function testGetFriendsWithExtraInfoAddsTheUserCard(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_FRIEND);

        $friends = SocialManager::get_friends(1);

        $this->assertCount(1, $friends);
        $this->assertSame('2', (string) $friends[0]['friend_user_id']);
        $this->assertSame('Anonymous', $friends[0]['firstName']);
        $this->assertSame('Joe', $friends[0]['lastName']);
        $this->assertSame('anon', $friends[0]['username']);
        $this->assertArrayHasKey('image', $friends[0]);
        $this->assertSame('anon', $friends[0]['user_info']['username']);
    }

    // ------------------------------------------------------------------ message based helpers (broken)

    /**
     * @dataProvider brokenMessageQueries
     */
    public function testMessageTableHelpersThrowBecauseTheirColumnsAreGone(callable $call): void
    {
        // PINNED QUIRK: user_receiver_id / msg_status do not exist on `message` any more, so the query fails.
        // Intended (if ever revived): counts and lists computed through message_rel_user and message.status.
        $this->expectException(\Throwable::class);
        $call();
    }

    public static function brokenMessageQueries(): iterable
    {
        yield 'messages sent' => [static fn () => SocialManager::getCountMessagesSent(1)];
        yield 'messages received' => [static fn () => SocialManager::getCountMessagesReceived(1)];
        yield 'wall posts' => [static fn () => SocialManager::getCountWallPostedMessages(1)];
        yield 'invitations received' => [static fn () => SocialManager::get_list_invitation_of_friends_by_user_id(1, 3)];
        yield 'invitations sent' => [static fn () => SocialManager::get_list_invitation_sent_by_user_id(1)];
        yield 'invitation count' => [static fn () => SocialManager::getCountInvitationSent(1)];
        yield 'accept invitation' => [static fn () => SocialManager::invitation_accepted(1, 2)];
        yield 'deny invitation' => [static fn () => SocialManager::invitation_denied(1, 2)];
        yield 'wall messages' => [static fn () => SocialManager::getWallMessages(1)];
    }

    public function testInvitationHelpersRejectEmptyIdsBeforeTouchingTheDatabase(): void
    {
        $this->assertSame([], SocialManager::get_list_invitation_of_friends_by_user_id(0));
        $this->assertSame([], SocialManager::get_list_invitation_sent_by_user_id(0));
        $this->assertSame(0, SocialManager::getCountInvitationSent(0));
        $this->assertSame(0, SocialManager::getCountWallPostedMessages(0));
        $this->assertFalse(SocialManager::invitation_accepted(0, 2));
        $this->assertFalse(SocialManager::invitation_denied(1, 0));
        $this->assertSame([], SocialManager::getThreadList(1));
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    private function loginAsAdmin(): void
    {
        $admin = $this->getUser('admin');
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );
    }

    private function rel(int $user, int $friend, int $type): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at)
             VALUES (?, ?, ?, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$user, $friend, $type]
        );
    }
}
