<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

class SettingsControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testOnlyAnAuthenticatedAdministratorCanExportConfiguration(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/settings/export');
        self::assertContains($client->getResponse()->getStatusCode(), [302, 401, 403]);
    }

    public function testAdministratorCanDownloadSanitizedConfiguration(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));
        $client->request('GET', '/admin/settings/export');

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('application/json', (string) $client->getResponse()->headers->get('Content-Type'));
        self::assertStringContainsString('attachment', (string) $client->getResponse()->headers->get('Content-Disposition'));
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
        $export = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('current_access_url_only', $export['scope']);
        self::assertArrayHasKey('settings', $export);
        self::assertArrayHasKey('plugins', $export);
    }

    public function testIndex(): void
    {
        $client = static::createClient();
        $admin = $this->getUser('admin');
        $client->loginUser($admin);

        $client->request('GET', '/admin/settings');
        $this->assertResponseRedirects('/admin/settings/platform');
    }

    public function testConfigurationDumpIsAvailableFromSettingsAndSearch(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        foreach (['/admin/settings/platform', '/admin/settings/search_settings?keyword=allow'] as $path) {
            $client->request('GET', $path);
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('a#configuration-dump-download[href="/admin/settings/export"]');
        }

        $client->request('GET', '/admin/settings/export');
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('application/json', (string) $client->getResponse()->headers->get('Content-Type'));
        self::assertStringContainsString('attachment', (string) $client->getResponse()->headers->get('Content-Disposition'));
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
        $content = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('chamilo-configuration-dump', $content['format']);
        self::assertSame('current_access_url_only', $content['scope']);
        self::assertIsArray($content['settings']);
        self::assertIsArray($content['plugins']);
        // Security credentials must remain hidden. Ordinary switches such as
        // check_password and password_rotation_days are valid diagnostic data.
        $secretVariables = [
            'administrator_email',
            'etherpad_api_key',
            'inbound_mail_dsn',
            'mailer_dkim',
            'mailer_dsn',
            'mailer_dsn_massmailing',
            'messaging_gdc_api_key',
        ];
        foreach ($content['settings'] as $setting) {
            if (!\in_array($setting['variable'] ?? null, $secretVariables, true)) {
                continue;
            }

            self::assertSame('[REDACTED]', $setting['value'], (string) $setting['variable']);
            self::assertTrue($setting['redacted'], (string) $setting['variable']);
        }
    }

    public function testConfigurationDumpRequiresAdministrator(): void
    {
        $client = static::createClient();
        // ChamiloTestTrait creates users in the isolated PHPUnit transaction.
        $nonAdmin = $this->createUser('configuration_dump_non_admin');
        self::assertNotNull($nonAdmin);
        self::assertNotContains('ROLE_ADMIN', $nonAdmin->getRoles());

        $client->loginUser($nonAdmin);
        $client->request('GET', '/admin/settings/export');
        self::assertContains($client->getResponse()->getStatusCode(), [302, 403]);
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

    public function testJsonSettingsAreMarkedForClientSideValidation(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request('GET', '/admin/settings/display');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('textarea[name="form[show_tabs]"][data-json-setting="true"]');
        $this->assertSelectorExists('textarea[name="form[show_tabs]"] + p[role="alert"]');
        // A template-backed JSON field without an explicit Json constraint must also be covered.
        $this->assertSelectorExists('textarea[name="form[show_tabs_per_role]"][data-json-setting="true"]');

        // The DKIM setting has an official JSON template and must be validated.
        $client->request('GET', '/admin/settings/mail');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('textarea[name="form[mailer_dkim]"][data-json-setting="true"]');

        // A plain-text textarea must remain editable without JSON validation.
        $client->request('GET', '/admin/settings/certificate');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('textarea[name="form[transcript_grading_scale]"][data-json-setting="true"]');
        $this->assertSelectorExists('textarea[name="form[transcript_gpa_note]"]');
        $this->assertSelectorNotExists('textarea[name="form[transcript_gpa_note]"][data-json-setting="true"]');
    }

    public function testJsonValidationIsAlsoPresentInSettingsSearch(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request('GET', '/admin/settings/search_settings?keyword=show_tabs');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('textarea[name="form[show_tabs]"][data-json-setting="true"]');
        $this->assertSelectorExists('textarea[name="form[show_tabs]"] + p[role="alert"]');
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
