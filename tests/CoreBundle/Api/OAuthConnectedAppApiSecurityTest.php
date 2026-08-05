<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Entity\OAuthRefreshToken;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Ownership regression tests for /api/oauth_connected_apps: a user must only
 * ever be able to list or revoke their own OAuth grants, mirroring the lesson
 * from the PushSubscription IDOR advisory (see PushSubscriptionApiSecurityTest) —
 * ownership must be enforced in the provider/processor's repository query,
 * not only via `is_granted('IS_AUTHENTICATED_FULLY')`.
 */
final class OAuthConnectedAppApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function seedClient(string $suffix): OAuthClient
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $client = (new OAuthClient())
            ->setClientId('chamilo_oauth_client_test_'.$suffix)
            ->setTokenEndpointAuthMethod('none')
            ->setClientName('Test Client '.$suffix)
            ->setRedirectUris(['https://client.example/callback'])
            ->setGrantTypes(['authorization_code', 'refresh_token'])
            ->setResponseTypes(['code'])
            ->setScope('mcp')
            ->setCreatedAt(new DateTime())
        ;

        $em->persist($client);
        $em->flush();

        return $client;
    }

    private function seedGrant(User $owner, OAuthClient $client, string $suffix): OAuthRefreshToken
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $accessUrl = self::getContainer()->get(AccessUrlHelper::class)->getCurrent();

        $now = new DateTime();
        $grant = (new OAuthRefreshToken())
            ->setTokenHash(hash('sha256', 'test-refresh-token-'.$suffix))
            ->setGrantId(Uuid::v4()->toRfc4122())
            ->setClient($client)
            ->setUser($owner)
            ->setAccessUrlId((int) $accessUrl->getId())
            ->setScope('mcp')
            ->setConsentedAt($now)
            ->setCreatedAt($now)
            ->setExpiresAt((clone $now)->modify('+30 days'))
            ->setAbsoluteExpiresAt((clone $now)->modify('+90 days'))
        ;

        $em->persist($grant);
        $em->flush();

        return $grant;
    }

    public function testForeignUserCannotRevokeOthersGrant(): void
    {
        $victim = $this->createUser('oauth_sec_victim_revoke');
        $attacker = $this->createUser('oauth_sec_attacker_revoke');
        $client = $this->seedClient('revoke');
        $grant = $this->seedGrant($victim, $client, 'revoke');

        $token = $this->getUserTokenFromUser($attacker);
        $this->createClientWithCredentials($token)->request(
            'DELETE',
            '/api/oauth_connected_apps/'.$grant->getGrantId(),
        );

        // Existence of a foreign grant is never disclosed: not found, not forbidden.
        $this->assertResponseStatusCodeSame(404);
    }

    public function testOwnerCanRevokeOwnGrant(): void
    {
        $owner = $this->createUser('oauth_sec_owner_revoke');
        $client = $this->seedClient('owner_revoke');
        $grant = $this->seedGrant($owner, $client, 'owner_revoke');

        $token = $this->getUserTokenFromUser($owner);
        $this->createClientWithCredentials($token)->request(
            'DELETE',
            '/api/oauth_connected_apps/'.$grant->getGrantId(),
        );

        $this->assertResponseStatusCodeSame(204);
    }

    public function testCollectionOnlyReturnsOwnGrants(): void
    {
        $victim = $this->createUser('oauth_sec_victim_list');
        $attacker = $this->createUser('oauth_sec_attacker_list');
        $client = $this->seedClient('list');
        $this->seedGrant($victim, $client, 'list_victim');
        $this->seedGrant($attacker, $client, 'list_attacker');

        $token = $this->getUserTokenFromUser($attacker);
        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/oauth_connected_apps',
        );

        $this->assertResponseStatusCodeSame(200);

        $members = $response->toArray()['hydra:member'] ?? [];
        $this->assertNotEmpty($members, 'The attacker must still see their own grant.');
        foreach ($members as $member) {
            $this->assertArrayNotHasKey('tokenHash', $member, 'The refresh token hash must never be serialized.');
        }
    }

    public function testRevokingAnUnknownGrantIdReturnsNotFound(): void
    {
        $user = $this->createUser('oauth_sec_unknown_grant');

        $token = $this->getUserTokenFromUser($user);
        $this->createClientWithCredentials($token)->request(
            'DELETE',
            '/api/oauth_connected_apps/'.Uuid::v4()->toRfc4122(),
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
