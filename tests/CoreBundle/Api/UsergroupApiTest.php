<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for the IDOR fix on /api/usergroups/{usergroupId}/messages.
 *
 * Before the fix, any authenticated user could dump messages of any
 * Usergroup. The endpoint must reject non-members with 403, members and
 * admins receive 200, and unknown groups resolve to 404.
 */
class UsergroupApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAnonymousIsRejected(): void
    {
        $group = $this->createOpenUsergroup('idor_anon_group');

        static::createClient()->request(
            'GET',
            '/api/usergroups/'.$group->getId().'/messages'
        );

        // Anonymous requests carry no JWT, so the security layer returns 401
        // (Unauthorized), not 403 (Forbidden) — the latter is for authenticated
        // non-members (see testNonMemberIsForbidden).
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testNonMemberIsForbidden(): void
    {
        $group = $this->createOpenUsergroup('idor_target_group');
        $member = $this->createUser('idor_target_member');
        $this->addReader($member, $group);

        $this->createGroupMessage($member, $group, 'Internal HR memo', 'confidential body');

        $attacker = $this->createUser('idor_attacker');
        $attackerToken = $this->getUserTokenFromUser($attacker);

        $this
            ->createClientWithCredentials($attackerToken)
            ->request('GET', '/api/usergroups/'.$group->getId().'/messages')
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMemberCanListGroupMessages(): void
    {
        $group = $this->createOpenUsergroup('idor_member_group');
        $sender = $this->createUser('idor_member_sender');
        $reader = $this->createUser('idor_member_reader');
        $this->addReader($sender, $group);
        $this->addReader($reader, $group);

        $this->createGroupMessage($sender, $group, 'Hello team', 'agenda for monday');

        $readerToken = $this->getUserTokenFromUser($reader);

        $response = $this
            ->createClientWithCredentials($readerToken)
            ->request('GET', '/api/usergroups/'.$group->getId().'/messages')
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $payload = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $payload);

        $members = $payload['hydra:member'];
        $this->assertCount(1, $members);

        $message = $members[0];
        foreach (['sender', 'msgType', 'status', 'title', 'content'] as $requiredField) {
            $this->assertArrayHasKey($requiredField, $message);
        }

        $this->assertSame('Hello team', $message['title']);
        $this->assertSame('agenda for monday', $message['content']);
        $this->assertSame(Message::MESSAGE_TYPE_GROUP, $message['msgType']);
    }

    public function testAdminCanListAnyGroupMessages(): void
    {
        $group = $this->createOpenUsergroup('idor_admin_group');
        $sender = $this->createUser('idor_admin_sender');
        $this->addReader($sender, $group);

        $this->createGroupMessage($sender, $group, 'Admin oversight', 'visible to admin');

        $adminToken = $this->getUserToken();

        $response = $this
            ->createClientWithCredentials($adminToken)
            ->request('GET', '/api/usergroups/'.$group->getId().'/messages')
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertCount(1, $response->toArray()['hydra:member']);
    }

    public function testUnknownGroupReturnsNotFound(): void
    {
        $user = $this->createUser('idor_unknown_group_user');
        $token = $this->getUserTokenFromUser($user);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/usergroups/999999999/messages')
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createOpenUsergroup(string $title): Usergroup
    {
        $group = $this->createUserGroup($title);
        $group->setVisibility((string) Usergroup::GROUP_PERMISSION_OPEN);

        $em = $this->getEntityManager();
        $em->persist($group);
        $em->flush();

        return $group;
    }

    private function addReader(User $user, Usergroup $group): void
    {
        $repo = static::getContainer()->get(UsergroupRepository::class);
        $repo->addUserToGroup(
            $user->getId(),
            $group->getId(),
            Usergroup::GROUP_USER_PERMISSION_READER
        );
    }

    private function createGroupMessage(User $sender, Usergroup $group, string $title, string $content): Message
    {
        $repo = static::getContainer()->get(MessageRepository::class);

        $message = (new Message())
            ->setSender($sender)
            ->setGroup($group)
            ->setMsgType(Message::MESSAGE_TYPE_GROUP)
            ->setTitle($title)
            ->setContent($content)
        ;

        $repo->update($message);

        return $message;
    }
}
