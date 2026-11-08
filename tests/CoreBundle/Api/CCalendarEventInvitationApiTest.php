<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * An invitation puts a personal event in the invitee's own calendar, so the API must only accept
 * the invitees the interface offers: the user's contacts, unless the platform lets users message
 * everyone. Each denied case asserts the invitee got no link to the event, not only the status
 * code, and has a positive twin proving the denial does not come from a broken setup.
 */
final class CCalendarEventInvitationApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCannotInviteAStranger(): void
    {
        $ctx = $this->bootstrapScenario('stranger');

        $this->createEvent($ctx['inviter'], 'StrangerEvent', $ctx['stranger']);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame([], $this->invitedUserIds('StrangerEvent'));
    }

    public function testCanInviteAContact(): void
    {
        $ctx = $this->bootstrapScenario('contact');

        $this->createEvent($ctx['inviter'], 'ContactEvent', $ctx['contact']);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([(int) $ctx['contact']->getId()], $this->invitedUserIds('ContactEvent'));
    }

    public function testPendingRequestIsNotAContact(): void
    {
        $ctx = $this->bootstrapScenario('pending');

        $this->createEvent($ctx['inviter'], 'PendingEvent', $ctx['pending']);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame([], $this->invitedUserIds('PendingEvent'));
    }

    public function testCanInviteAStrangerWhenMessagingAllUsersIsAllowed(): void
    {
        $ctx = $this->bootstrapScenario('message_all');
        self::getContainer()->get(SettingsManager::class)->updateSetting(
            'message.allow_send_message_to_all_platform_users',
            'true'
        );

        $this->createEvent($ctx['inviter'], 'MessageAllEvent', $ctx['stranger']);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([(int) $ctx['stranger']->getId()], $this->invitedUserIds('MessageAllEvent'));
    }

    public function testAdminCanInviteAStranger(): void
    {
        $ctx = $this->bootstrapScenario('admin');
        $admin = $this->createUser('cei_admin_admin', '', '', 'ROLE_ADMIN');

        $this->createEvent($admin, 'AdminEvent', $ctx['stranger']);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([(int) $ctx['stranger']->getId()], $this->invitedUserIds('AdminEvent'));
    }

    public function testUpdateCannotAddAStranger(): void
    {
        $ctx = $this->bootstrapScenario('update_stranger');
        $eventId = $this->createEvent($ctx['inviter'], 'UpdateStrangerEvent');
        $this->assertResponseStatusCodeSame(201);

        $this->updateEvent($ctx['inviter'], $eventId, 'UpdateStrangerEvent', $ctx['stranger']);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame([], $this->invitedUserIds('UpdateStrangerEvent'));
    }

    public function testUpdateCanAddAContact(): void
    {
        $ctx = $this->bootstrapScenario('update_contact');
        $eventId = $this->createEvent($ctx['inviter'], 'UpdateContactEvent');
        $this->assertResponseStatusCodeSame(201);

        $this->updateEvent($ctx['inviter'], $eventId, 'UpdateContactEvent', $ctx['contact']);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame([(int) $ctx['contact']->getId()], $this->invitedUserIds('UpdateContactEvent'));
    }

    /**
     * @return array{inviter: User, contact: User, pending: User, stranger: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $inviter = $this->createUser('cei_inviter_'.$suffix);
        $contact = $this->createUser('cei_contact_'.$suffix);
        $pending = $this->createUser('cei_pending_'.$suffix);
        $stranger = $this->createUser('cei_stranger_'.$suffix);

        $em = $this->getEntityManager();
        // The contact accepted the relation; the pending user only received a request.
        $em->persist(
            (new UserRelUser())
                ->setUser($contact)
                ->setFriend($inviter)
                ->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND)
        );
        $em->persist(
            (new UserRelUser())
                ->setUser($inviter)
                ->setFriend($pending)
                ->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST)
        );
        $em->flush();

        return [
            'inviter' => $inviter,
            'contact' => $contact,
            'pending' => $pending,
            'stranger' => $stranger,
        ];
    }

    private function createEvent(User $creator, string $title, ?User $invitee = null): int
    {
        $payload = $this->eventPayload($title);
        $payload['parentResourceNode'] = (int) $creator->getResourceNode()?->getId();
        if ($invitee instanceof User) {
            $payload['resourceLinkList'] = [['uid' => (int) $invitee->getId(), 'visibility' => 2]];
        }

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($creator))->request(
            'POST',
            '/api/c_calendar_events',
            ['json' => $payload],
        );

        return (int) ($response->toArray(false)['iid'] ?? 0);
    }

    private function updateEvent(User $creator, int $eventId, string $title, User $invitee): void
    {
        $payload = $this->eventPayload($title);
        $payload['resourceLinkListFromEntity'] = [['uid' => (int) $invitee->getId(), 'visibility' => 2]];

        $this->createClientWithCredentials($this->getUserTokenFromUser($creator))->request(
            'PUT',
            '/api/c_calendar_events/'.$eventId,
            ['json' => $payload],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(string $title): array
    {
        return [
            'title' => $title,
            'content' => '<p>'.$title.'</p>',
            'startDate' => '2040-06-30 11:00:00',
            'endDate' => '2040-06-30 15:00:00',
            'allDay' => false,
            'collective' => false,
        ];
    }

    /**
     * Ids of the users the events with this title are linked to, other than their creator.
     *
     * @return list<int>
     */
    private function invitedUserIds(string $title): array
    {
        $em = $this->getEntityManager();
        $em->clear();

        $userIds = [];
        foreach ($em->getRepository(CCalendarEvent::class)->findBy(['title' => $title]) as $event) {
            $creatorId = (int) $event->getResourceNode()?->getCreator()?->getId();
            foreach ($event->getResourceNode()?->getResourceLinks() ?? [] as $link) {
                $userId = (int) $link->getUser()?->getId();
                if ($userId > 0 && $userId !== $creatorId) {
                    $userIds[] = $userId;
                }
            }
        }

        return $userIds;
    }
}
