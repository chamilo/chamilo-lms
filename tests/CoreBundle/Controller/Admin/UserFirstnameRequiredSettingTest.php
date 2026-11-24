<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * #1783: registration.firstname_required (default on) decides whether the admin user forms accept a user without a
 * first name. The last name stays required either way.
 */
final class UserFirstnameRequiredSettingTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testFirstNameIsRequiredByDefault(): void
    {
        $client = $this->adminClient();

        $client->request('GET', '/admin/user-add-data', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_decode((string) $client->getResponse()->getContent(), true)['firstnameRequired']);

        $this->addUser($client, '', 'Mononym', 'fnreq_default');
        $this->assertResponseStatusCodeSame(400);
        $this->assertNull($this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'fnreq_default']));
    }

    public function testAdminCanCreateAndEditAUserWithoutFirstNameWhenTheSettingIsOff(): void
    {
        $this->setFirstnameRequired('false');
        $client = $this->adminClient();

        $client->request('GET', '/admin/user-add-data', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertFalse(json_decode((string) $client->getResponse()->getContent(), true)['firstnameRequired']);

        $this->addUser($client, '', 'Mononym', 'fnreq_off');
        $this->assertResponseIsSuccessful();
        $userId = json_decode((string) $client->getResponse()->getContent(), true)['userId'];

        $em = $this->getEntityManager();
        $em->clear();
        $user = $em->getRepository(User::class)->find($userId);
        $this->assertSame('', (string) $user->getFirstname());
        $this->assertSame('Mononym', $user->getFullName(), 'No stray space before the last name.');

        $client->request('POST', '/admin/user-edit-action', [
            'user_id' => $userId,
            'firstname' => '',
            'lastname' => 'Renamed',
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'authSource' => ['platform'],
            'roles' => ['ROLE_STUDENT'],
            'locale' => 'en_US',
            'sendMail' => '0',
            'active' => '1',
            'resetPassword' => '0',
        ], [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        $this->assertResponseIsSuccessful();

        $em->clear();
        $this->assertSame('Renamed', $em->getRepository(User::class)->find($userId)->getLastname());
    }

    public function testLastNameStaysRequiredWhenTheSettingIsOff(): void
    {
        $this->setFirstnameRequired('false');
        $client = $this->adminClient();

        $this->addUser($client, 'Only', '', 'fnreq_nolast');
        $this->assertResponseStatusCodeSame(400);
    }

    private function adminClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($this->getUser('admin'));

        return $client;
    }

    private function setFirstnameRequired(string $value): void
    {
        self::bootKernel();
        self::getContainer()->get(SettingsManager::class)->updateSetting('registration.firstname_required', $value);
    }

    private function addUser(KernelBrowser $client, string $firstname, string $lastname, string $username): void
    {
        $client->request('POST', '/admin/user-add-action', [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $username.'@example.com',
            'username' => $username,
            'authSource' => ['platform'],
            'roles' => ['ROLE_STUDENT'],
            'locale' => 'en_US',
            'sendMail' => '0',
            'active' => '1',
            'passwordMode' => 'auto',
        ], [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
    }
}
