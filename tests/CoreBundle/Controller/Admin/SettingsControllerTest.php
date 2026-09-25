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
        $this->assertSelectorTextContains('#sectionMainContent', 'Portal Administrator: e-mail');
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

        $this->assertStringContainsString('Internal messaging tool', $client->getResponse()->getContent());
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

    /**
     * The /admin block list is cached per admin for 120 s, and an admin lands on /admin right
     * after login. Without clearing that cache on save, a setting that adds a block link
     * (here Terms and Conditions) stays invisible for up to 2 minutes after being enabled,
     * which is what broke SpecialCase1's "Verify settings that require creating courses and users".
     */
    public function testSavingSettingsRefreshesTheCachedAdminBlocks(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $cache = static::getContainer()->get('chamilo.admin_index_blocks');
        $cache->clear();

        $client->request('GET', '/admin/index');
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('item-terms-and-conditions', $client->getResponse()->getContent());

        $client->request('GET', '/admin/settings/registration');
        $client->submitForm('Save settings', [
            'form[allow_terms_conditions]' => 'true',
        ]);

        $client->request('GET', '/admin/index');
        $content = $client->getResponse()->getContent();
        // The cache lives outside the rolled-back test transaction; don't leak it to other tests.
        $cache->clear();

        $this->assertStringContainsString('item-terms-and-conditions', $content);
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

        // $settingsManager = $this->getContainer()->get(SettingsManager::class);

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings_sync');
        $this->assertResponseIsSuccessful();
    }
}
