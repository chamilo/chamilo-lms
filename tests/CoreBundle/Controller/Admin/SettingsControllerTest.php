<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingsControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testIndex(): void
    {
        $client = static::createClient();

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings/admin');
        $this->assertStringContainsString('Administrator email', $client->getResponse()->getContent());
    }

    public function testSearchSettingAction(): void
    {
        $client = static::createClient();

        // retrieve the admin
        $admin = $this->getUser('admin');

        $client->loginUser($admin);

        $client->request('GET', '/admin/settings/admin');

        $client->submitForm('Search', [
            'search[keyword]' => 'allow_message_tool',
        ]);

        $this->assertStringContainsString(
            'Allow message tool',
            $client->getResponse()->getContent()
        );
    }

    public function testUpdateSettingAction(): void
    {
        $client = static::createClient();

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings/platform');

        $client->submitForm('Save settings', [
            'form[institution]' => 'Chamilo modified 123',
        ]);

        $client->request('GET', '/admin/settings/platform');
        $this->assertStringContainsString('Chamilo modified 123', $client->getResponse()->getContent());
    }
}
