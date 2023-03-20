<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Repository\SettingsCurrentRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginJsonWrongFormat(): void
    {
        $client = static::createClient();

        $params = [
            'username' => 'admin',
            'password' => 'admin',
        ];

        $client->request(
            'POST',
            '/login_json',
            [
                'headers' => ['Content-Type' => 'application/test'],
                'body' => json_encode($params),
            ]
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('lang="en"', $client->getResponse()->getContent());
    }

    public function testLoginChangeLanguage(): void
    {
        $client = static::createClient();

        $repo = $this->getContainer()->get(SettingsCurrentRepository::class);

        /** @var SettingsCurrent $setting */
        $setting = $repo->findOneBy(['variable' => 'platform_language']);
        $this->assertNotNull($setting);

        $setting->setSelectedValue('fr_FR');
        $repo->update($setting);

        $setting = $repo->findOneBy(['variable' => 'platform_language']);
        $this->assertSame('fr_FR', $setting->getSelectedValue());

        $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('lang="fr_FR"', $client->getResponse()->getContent());
    }

    public function testToggleStudentViewAction(): void
    {
        $client = static::createClient();

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/toggle_student_view');
        $this->assertResponseIsSuccessful();
    }

    public function testLogout(): void
    {
        $client = static::createClient();
        $response = $client->request('GET', '/');

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/account/home');
        $this->assertResponseIsSuccessful();

        //$client->request('GET', '/logout');
        //$this->assertResponseRedirects($defaultUrl);

        //$client->request('GET', '/main/admin/index.php');
        //$this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }
}
