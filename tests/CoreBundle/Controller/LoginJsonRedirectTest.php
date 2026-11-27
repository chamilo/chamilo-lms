<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

/**
 * The password login form no longer reads the redirect settings: the server sends the destination
 * with the user, so every login method uses the same rule.
 */
final class LoginJsonRedirectTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testLoginSendsTheProfileDestinationWithTheUser(): void
    {
        $client = self::createClient();
        $this->setSetting('registration.redirect_after_login', '{"COURSEMANAGER":"sessions?page=2"}');
        $this->setSetting('workflows.go_to_course_after_login', 'false');
        $this->createUser('login_json_redirect', role: 'ROLE_TEACHER');

        $data = $this->login($client, 'login_json_redirect');

        self::assertSame('/sessions?page=2', $data['afterLogin']);
        self::assertSame('login_json_redirect', $data['username']);
    }

    public function testLoginNeverSendsAnExternalDestination(): void
    {
        $client = self::createClient();
        $this->setSetting('registration.redirect_after_login', '{"COURSEMANAGER":"//example.invalid/"}');
        $this->setSetting('workflows.go_to_course_after_login', 'false');
        $this->createUser('login_json_redirect_unsafe', role: 'ROLE_TEACHER');

        $data = $this->login($client, 'login_json_redirect_unsafe');

        self::assertSame('/', $data['afterLogin']);
    }

    private function login(KernelBrowser $client, string $username): array
    {
        $client->jsonRequest(
            'POST',
            '/login_json',
            ['username' => $username, 'password' => $username],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );
        self::assertResponseIsSuccessful();

        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }
}
