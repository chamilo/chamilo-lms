<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\PushSubscription;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\PushSubscriptionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Regression tests for the PushSubscription IDOR / mass-assignment advisory.
 *
 * The advisory (reporter: Nguyen Manh Thuan, 2026-05-20) reported that every
 * operation on /api/push_subscriptions was gated only by is_granted('ROLE_USER'),
 * with the `user` relation in the write group and the cryptographic `authToken`
 * in the read group. As a result any authenticated low-privileged user could:
 *
 *   1. Read the Web Push secrets (endpoint, publicKey, authToken) of every user.
 *   2. Create a subscription owned by another user (mass-assignment of `user`)
 *      to hijack server-initiated push notifications.
 *   3. Delete any user's subscription.
 *
 * These tests assert the expected post-fix behaviour:
 *   - a user may only read/delete their own subscription (object.user == user);
 *   - the collection only ever returns the caller's own rows, even when the
 *     `user.id`/`endpoint` filters target a victim;
 *   - the `authToken`/`publicKey` secrets are never serialized in responses;
 *   - the owning `user` cannot be mass-assigned on POST — the persisted row is
 *     always owned by the authenticated caller.
 */
final class PushSubscriptionApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Persists one PushSubscription owned by the given user and returns it.
     */
    private function seedSubscription(User $owner, string $suffix): PushSubscription
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $subscription = (new PushSubscription())
            ->setEndpoint('https://push.example/endpoint-'.$suffix)
            ->setPublicKey('public-key-'.$suffix)
            ->setAuthToken('secret-auth-token-'.$suffix)
            ->setContentEncoding('aesgcm')
            ->setUserAgent('PHPUnit')
            ->setUser($owner)
        ;

        $em->persist($subscription);
        $em->flush();

        return $subscription;
    }

    public function testForeignUserCannotReadOthersSubscription(): void
    {
        $victim = $this->createUser('push_sec_victim_read');
        $attacker = $this->createUser('push_sec_attacker_read');
        $subscription = $this->seedSubscription($victim, 'read');

        $token = $this->getUserTokenFromUser($attacker);
        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/push_subscriptions/'.$subscription->getId(),
        );

        // The provider scopes items to the caller, so a foreign id is not found
        // rather than forbidden — its existence is never disclosed.
        $this->assertResponseStatusCodeSame(404);
    }

    public function testOwnerCanReadOwnSubscription(): void
    {
        $owner = $this->createUser('push_sec_owner_read');
        $subscription = $this->seedSubscription($owner, 'owner');

        $token = $this->getUserTokenFromUser($owner);
        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/push_subscriptions/'.$subscription->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminCanReadAnyUsersSubscription(): void
    {
        $victim = $this->createUser('push_sec_victim_admin_read');
        $admin = $this->createUser('push_sec_admin_read', '', '', 'ROLE_ADMIN');
        $subscription = $this->seedSubscription($victim, 'admin_read');

        $token = $this->getUserTokenFromUser($admin);
        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/push_subscriptions/'.$subscription->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminCollectionSeesOtherUsersSubscriptions(): void
    {
        $victim = $this->createUser('push_sec_victim_admin_list');
        $admin = $this->createUser('push_sec_admin_list', '', '', 'ROLE_ADMIN');
        $victimSub = $this->seedSubscription($victim, 'admin_list');

        $token = $this->getUserTokenFromUser($admin);
        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/push_subscriptions?user.id='.$victim->getId(),
        );

        $this->assertResponseStatusCodeSame(200);

        $userIris = array_column($response->toArray()['hydra:member'] ?? [], 'user');
        $this->assertContains(
            '/api/users/'.$victim->getId(),
            $userIris,
            'An admin must be able to list subscriptions owned by other users.'
        );
        $this->assertNotNull($victimSub->getId());
    }

    public function testCollectionOnlyReturnsOwnSubscriptionsEvenWhenFilteringVictim(): void
    {
        $victim = $this->createUser('push_sec_victim_list');
        $attacker = $this->createUser('push_sec_attacker_list');
        $victimSub = $this->seedSubscription($victim, 'list_victim');
        $this->seedSubscription($attacker, 'list_attacker');

        $token = $this->getUserTokenFromUser($attacker);

        // Even targeting the victim directly via the legacy filters must not leak.
        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/push_subscriptions?user.id='.$victim->getId().'&endpoint='.urlencode($victimSub->getEndpoint()),
        );

        $this->assertResponseStatusCodeSame(200);

        $members = $response->toArray()['hydra:member'] ?? [];
        foreach ($members as $member) {
            $this->assertSame(
                '/api/users/'.$attacker->getId(),
                $member['user'] ?? null,
                'The push_subscriptions collection must only return rows owned by the caller.'
            );
        }
    }

    public function testCollectionDoesNotExposeWebPushSecrets(): void
    {
        $owner = $this->createUser('push_sec_owner_secrets');
        $this->seedSubscription($owner, 'secrets');

        $token = $this->getUserTokenFromUser($owner);
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/push_subscriptions');

        $this->assertResponseStatusCodeSame(200);

        $members = $response->toArray()['hydra:member'] ?? [];
        $this->assertNotEmpty($members);
        foreach ($members as $member) {
            $this->assertArrayNotHasKey('authToken', $member, 'The RFC 8291 authToken secret must never be serialized.');
            $this->assertArrayNotHasKey('publicKey', $member, 'The publicKey must never be serialized.');
        }
    }

    public function testCannotMassAssignUserOnPost(): void
    {
        $victim = $this->createUser('push_sec_victim_post');
        $attacker = $this->createUser('push_sec_attacker_post');

        $token = $this->getUserTokenFromUser($attacker);
        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/push_subscriptions',
            [
                'json' => [
                    'endpoint' => 'https://attacker.example/collector',
                    'publicKey' => 'attacker-public-key',
                    'authToken' => 'attacker-auth-token',
                    'contentEncoding' => 'aesgcm',
                    'user' => '/api/users/'.$victim->getId(),
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(201);

        // The owning user must be forced to the authenticated caller, never the victim.
        $this->assertSame('/api/users/'.$attacker->getId(), $response->toArray()['user'] ?? null);

        /** @var PushSubscriptionRepository $repository */
        $repository = self::getContainer()->get(PushSubscriptionRepository::class);
        $persisted = $repository->findOneByEndpoint('https://attacker.example/collector');
        $this->assertNotNull($persisted);
        $this->assertSame(
            $attacker->getId(),
            $persisted->getUser()?->getId(),
            'The persisted subscription must be owned by the caller, not the mass-assigned victim.'
        );
    }

    public function testForeignUserCannotDeleteOthersSubscription(): void
    {
        $victim = $this->createUser('push_sec_victim_del');
        $attacker = $this->createUser('push_sec_attacker_del');
        $subscription = $this->seedSubscription($victim, 'del');
        $id = $subscription->getId();

        $token = $this->getUserTokenFromUser($attacker);
        $this->createClientWithCredentials($token)->request('DELETE', '/api/push_subscriptions/'.$id);

        $this->assertResponseStatusCodeSame(404);

        /** @var PushSubscriptionRepository $repository */
        $repository = self::getContainer()->get(PushSubscriptionRepository::class);
        $this->assertNotNull($repository->find($id), 'The victim subscription must survive a foreign delete attempt.');
    }
}
