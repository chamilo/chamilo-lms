<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The push notification settings hold the Gotify token and the VAPID private key, which only the
 * server uses to send notifications. The browser only needs to know whether push is enabled and
 * the VAPID public key to subscribe, so the platform configuration given to every user must not
 * carry the credentials.
 */
final class PlatformConfigurationPushSettingsTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testUsersOnlyReceiveThePublicPushSettings(): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting(
            'platform.push_notification_settings',
            (string) json_encode([
                'gotify_url' => 'https://gotify.example.invalid',
                'gotify_token' => 'server-only-token',
                'enabled' => true,
                'vapid_public_key' => 'public-key',
                'vapid_private_key' => 'server-only-private-key',
            ]),
        );

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser(
            $this->createUser('push_settings_student'),
        ));
        $response = $client->request('GET', '/platform-config/list');
        $this->assertResponseIsSuccessful();

        $raw = $response->toArray()['settings']['platform.push_notification_settings'];
        $this->assertStringNotContainsString('server-only', $raw);
        $this->assertSame(
            ['enabled' => true, 'vapid_public_key' => 'public-key'],
            json_decode($raw, true),
            'The browser still gets what it needs to subscribe.',
        );
    }

    public function testAnonymousVisitorsDoNotReceiveThePushSettings(): void
    {
        $response = self::createClient()->request('GET', '/platform-config/list');
        $this->assertResponseIsSuccessful();

        $this->assertArrayNotHasKey('platform.push_notification_settings', $response->toArray()['settings']);
    }
}
