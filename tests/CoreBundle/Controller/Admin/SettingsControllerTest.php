<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingsControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $admin = $this->getUser('admin');
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings');
        $this->assertResponseRedirects('/admin/settings/platform');
    }

    public function testAdminSettings(): void
    {
        $client = static::createClient();
        $admin = $this->getUser('admin');
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#sectionMainContent', 'Administrator email');
    }

    public function testSearchSettingAction(): void
    {
        $client = static::createClient();

        $admin = $this->getUser('admin');

        $client->loginUser($admin);

        $client->request('GET', '/admin/settings/admin');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Search', [
            'search[keyword]' => 'allow_message_tool',
        ]);

        $this->assertStringContainsString('Allow message tool', $client->getResponse()->getContent());
    }

    public function testUpdateSettingAction(): void
    {
        $client = static::createClient();

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings/platform');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Save settings', [
            'form[institution]' => 'Chamilo modified 123',
        ]);

        $client->request('GET', '/admin/settings/platform');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Chamilo modified 123', $client->getResponse()->getContent());
    }

    public function testListSettings(): void
    {
        $client = static::createClient();

        $settingsManager = $this->getContainer()->get(SettingsManager::class);

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $schemas = $settingsManager->getSchemas();
        foreach ($schemas as $name => $schema) {
            $category = $settingsManager->convertServiceToNameSpace($name);
            $client->request('GET', '/admin/settings/'.$category);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testSyncSettings(): void
    {
        $client = static::createClient();

        //$settingsManager = $this->getContainer()->get(SettingsManager::class);

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings_sync');
        $this->assertResponseIsSuccessful();
    }
}
