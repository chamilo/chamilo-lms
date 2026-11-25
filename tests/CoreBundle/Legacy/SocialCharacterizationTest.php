<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use DateTime;
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
 * get_list_invitation_of_friends_by_user_id() (the notifications poll of message.ajax.php) reads the pending invitations
 * received by a user through message_rel_user and message.status.
 *
 * (getWallMessages was removed: it read message.user_receiver_id/msg_status, which no longer exist, and its only caller, the
 * list_wall_message AJAX action, was unreachable; the wall is the Vue SocialPost feature.)
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
        ChamiloSession::erase('_user');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
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
        // INTENTIONAL (no caller passes true; the lookup in user_friend_relation_type is what restricts the answer to the
        // known friendship types): with $includeRH the type is still looked up in user_friend_relation_type, which only holds
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
        ChamiloSession::write('_user', ['id' => 1]);

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
        // vcard_export.php tests "0 !== get_relation_between_contacts()": anything other than 0 here (the function
        // used to fall off its end and return NULL) would let any logged-in stranger export another user's vCard.
        $this->assertSame(UserRelUser::USER_UNKNOWN, SocialManager::get_relation_between_contacts(1, 2));
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

    public function testGetFriendsSearchMatchesFirstNameLastNameOrBothGluedTogether(): void
    {
        $this->rel(1, 2, UserRelUser::USER_RELATION_TYPE_FRIEND);   // Anonymous Joe
        $this->rel(1, 3, UserRelUser::USER_RELATION_TYPE_FRIEND);   // User Fallback

        $ids = static fn (array $l) => array_column($l, 'friend_user_id');
        $this->assertSame([2], $ids(SocialManager::get_friends(1, null, 'anonym', false)));
        $this->assertSame([2], $ids(SocialManager::get_friends(1, null, 'joe', false)), 'Last name.');
        $this->assertSame([3], $ids(SocialManager::get_friends(1, null, 'fallb', false)));
        // Spaces are removed before searching, so a "full name" search works on the glued names, in the display
        // order of the platform (first+last for western order, last+first otherwise) and only in that one.
        $western = api_is_western_name_order();
        $glued = $western ? ' Anonymous Joe ' : ' Joe Anonymous ';
        $other = $western ? 'Joe Anonymous' : 'Anonymous Joe';
        $this->assertSame([2], $ids(SocialManager::get_friends(1, null, $glued, false)));
        $this->assertSame([], $ids(SocialManager::get_friends(1, null, $other, false)));
        $this->assertSame([], SocialManager::get_friends(1, null, 'nobody-like-this', false));
        $this->assertSame([], SocialManager::get_friends(1, null, '" OR "1"="1', false), 'Quotes are escaped.');
        // Only the searcher's own friends: a user matching the name who is not a friend is not returned.
        $this->assertSame([], SocialManager::get_friends(2, null, 'fallb', false));
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

    public function testPendingInvitationsReceivedAreListedNewestFirstWithTheirSenderAndLimit(): void
    {
        $sender = $this->createUser('sc_inv_sender');
        $other = $this->createUser('sc_inv_other');
        $receiver = $this->createUser('sc_inv_receiver');
        $em = $this->getEntityManager();
        $make = function (User $from, User $to, string $title, int $type, int $status, string $date) use ($em): void {
            $message = (new Message())
                ->setSender($from)
                ->setMsgType($type)
                ->setStatus($status)
                ->setTitle($title)
                ->setContent('content of '.$title)
                ->setSendDate(new DateTime($date))
            ;
            $message->addReceiverTo($to);
            $em->persist($message);
        };
        $pending = Message::MESSAGE_STATUS_INVITATION_PENDING;
        $invitation = Message::MESSAGE_TYPE_INVITATION;
        $make($sender, $receiver, 'inv old', $invitation, $pending, '2030-01-01 10:00:00');
        $make($other, $receiver, 'inv new', $invitation, $pending, '2030-01-02 10:00:00');
        $make($sender, $receiver, 'inv answered', $invitation, Message::MESSAGE_STATUS_INVITATION_ACCEPTED, '2030-01-03 10:00:00');
        $make($sender, $receiver, 'plain inbox', Message::MESSAGE_TYPE_INBOX, $pending, '2030-01-04 10:00:00');
        $make($sender, $other, 'for someone else', $invitation, $pending, '2030-01-05 10:00:00');
        $em->flush();

        $list = SocialManager::get_list_invitation_of_friends_by_user_id($receiver->getId());

        $this->assertSame(['inv new', 'inv old'], array_column($list, 'title'));
        $this->assertSame((string) $other->getId(), (string) $list[0]['user_sender_id']);
        $this->assertSame((string) $sender->getId(), (string) $list[1]['user_sender_id']);
        $this->assertSame('content of inv new', $list[0]['content']);
        $this->assertSame('2030-01-02 10:00:00', $list[0]['send_date']);
        $this->assertSame(['inv new'], array_column(SocialManager::get_list_invitation_of_friends_by_user_id($receiver->getId(), 1), 'title'));
        $this->assertSame([], SocialManager::get_list_invitation_of_friends_by_user_id($sender->getId()));
    }

    public function testInvitationHelpersRejectEmptyIdsBeforeTouchingTheDatabase(): void
    {
        $this->assertSame([], SocialManager::get_list_invitation_of_friends_by_user_id(0));
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
