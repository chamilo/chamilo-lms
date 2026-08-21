<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserAddControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testDataEndpointAsAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request('GET', '/admin/user-add-data', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('authSources', $data);
        $this->assertArrayHasKey('roleOptions', $data);
        $this->assertArrayHasKey('extraFields', $data);
        $this->assertNotEmpty($data['authSources']);
    }

    public function testDataEndpointDeniedForStudent(): void
    {
        $client = static::createClient();
        $student = $this->createUser('user_add_ctrl_student', '', '', 'ROLE_STUDENT');
        $client->loginUser($student);

        $client->request('GET', '/admin/user-add-data', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateUserAsAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'UserAddCtrl',
                'email' => 'test_user_add_ctrl@example.com',
                'username' => 'test_user_add_ctrl',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'sendMail' => '0',
                'active' => '1',
                'passwordMode' => 'auto',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertIsInt($data['userId']);
    }

    public function testCreateUserWithoutRolesFails(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'NoRole',
                'username' => 'test_user_add_norole',
                'authSource' => ['platform'],
                'locale' => 'en_US',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Error', $data['error']);
    }

    public function testCreateUserWithInvalidUsernameCharactersFails(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Ni',
                'lastname' => 'Ño',
                'email' => 'nino@example.com',
                'username' => 'NIÑO',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Only letters and numbers allowed', $data['error']);
    }

    public function testCreateUserWithInvalidEmailFails(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Juls',
                'lastname' => 'Juls',
                'email' => 'NI -NO@example.com',
                'username' => 'juls_email_test',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('The email address is not complete or contains some invalid characters', $data['error']);
    }

    public function testManualPasswordIsIgnoredWhenAdminsCanSetUsersPassIsDisabled(): void
    {
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)->updateSetting('security.admins_can_set_users_pass', 'false');
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'IgnoredManualPassword',
                'username' => 'test_user_add_ignored_manual_pass',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                // Client tries to force a manual, attacker-known password even
                // though the setting is off — the server must ignore it and
                // auto-generate one instead (defense in depth).
                'passwordMode' => 'manual',
                'password' => 'AttackerChosen123!',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $user = static::getContainer()->get(UserRepository::class)->find($data['userId']);
        $this->assertInstanceOf(User::class, $user);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertFalse($hasher->isPasswordValid($user, 'AttackerChosen123!'));
    }

    public function testManualPasswordEmptyFailsWhenSettingEnabled(): void
    {
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)->updateSetting('security.admins_can_set_users_pass', 'true');
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'EmptyManualPassword',
                'username' => 'test_user_add_empty_manual_pass',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'passwordMode' => 'manual',
                'password' => '',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('The password is too short', $data['error']);
    }

    public function testManualPasswordTooWeakFailsWhenCheckPasswordEnabled(): void
    {
        $client = static::createClient();
        $settingsManager = static::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('security.admins_can_set_users_pass', 'true');
        $settingsManager->updateSetting('security.check_password', 'true');
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'WeakManualPassword',
                'username' => 'test_user_add_weak_manual_pass',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'passwordMode' => 'manual',
                'password' => 'abc',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        // The translated string (messages.en_US.po's msgstr), not the odd
        // double-spaced legacy msgid used as the lookup key.
        $this->assertStringStartsWith('This password is too simple. Use a password like this', $data['error']);
    }

    public function testManualPasswordStrongEnoughSucceedsAndIsUsable(): void
    {
        $client = static::createClient();
        $settingsManager = static::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('security.admins_can_set_users_pass', 'true');
        $settingsManager->updateSetting('security.check_password', 'true');
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'StrongManualPassword',
                'username' => 'test_user_add_strong_manual_pass',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'passwordMode' => 'manual',
                'password' => 'Str0ng#Passw0rd!',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $user = static::getContainer()->get(UserRepository::class)->find($data['userId']);
        $this->assertInstanceOf(User::class, $user);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($user, 'Str0ng#Passw0rd!'));
    }

    public function testNonPlatformAuthSourceUsesPlaceholderPassword(): void
    {
        // config/authentication.yaml now has LDAP enabled (a real, standing
        // config change, not test-specific), so "extldap" is a genuinely
        // allowed auth source here — this now exercises the actual
        // "!hasPlatformAuth" branch (password forced to the 'PLACEHOLDER'
        // sentinel, auth handled externally) rather than the "no valid auth
        // source submitted" branch it used to fall into when LDAP was off.
        $client = static::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'NonPlatformAuth',
                'username' => 'test_user_add_non_platform_auth',
                'authSource' => ['extldap'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $user = static::getContainer()->get(UserRepository::class)->find($data['userId']);
        $this->assertInstanceOf(User::class, $user);
        $authentications = array_map(
            static fn ($authSource) => $authSource->getAuthentication(),
            $user->getAuthSources()->toArray()
        );
        $this->assertSame(['extldap'], $authentications);
    }

    public function testLoginIsEmailUsesEmailAsUsername(): void
    {
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)->updateSetting('login_is_email', 'true');
        $client->loginUser($this->getUser('admin'));

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'LoginIsEmail',
                'email' => 'login_is_email_test@example.com',
                // No 'username' field at all — the legacy form doesn't render one
                // in this mode either.
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $user = static::getContainer()->get(UserRepository::class)->find($data['userId']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('login_is_email_test@example.com', $user->getUsername());
    }

    public function testLoginIsEmailRejectsDuplicateEmail(): void
    {
        // Regression test for a bug caught in review before this code ever
        // shipped: the first draft's uniqueness check was
        // `$username !== $email && !isUsernameAvailable($username)` — when
        // login_is_email is on, $username always equals $email, so that
        // condition was always false and the uniqueness check never ran at
        // all, silently allowing duplicate accounts. Confirms the fixed,
        // unconditional check actually rejects a second account with the
        // same email/username in this mode.
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)->updateSetting('login_is_email', 'true');
        $client->loginUser($this->getUser('admin'));

        $payload = [
            'firstname' => 'Test',
            'lastname' => 'LoginIsEmailDuplicate',
            'email' => 'login_is_email_duplicate@example.com',
            'authSource' => ['platform'],
            'roles' => ['ROLE_STUDENT'],
            'locale' => 'en_US',
        ];

        $client->request('POST', '/admin/user-add-action', $payload, [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/admin/user-add-action', $payload, [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        $this->assertResponseStatusCodeSame(409);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('This login is already in use', $data['error']);
    }

    public function testHideNeverExpireOptionForcesExpirationForSessionAdminEvenIfClientSaysOtherwise(): void
    {
        // Legacy behaviour: when registration.user_hide_never_expire_option is on
        // (and the requester isn't a platform admin), user_add.php unconditionally
        // overwrites $user['radio_expiration_date'] = '1' server-side, regardless
        // of what was submitted — the "Never expires" radio isn't even rendered
        // in this mode. A tampered/malicious request explicitly claiming
        // hasExpirationDate=0 must not be able to create a non-expiring account.
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)->updateSetting('registration.user_hide_never_expire_option', 'true');
        $sessionAdmin = $this->createUser('user_add_ctrl_session_admin', '', '', 'ROLE_SESSION_MANAGER');
        $client->loginUser($sessionAdmin);

        $client->request(
            'POST',
            '/admin/user-add-action',
            [
                'firstname' => 'Test',
                'lastname' => 'ForcedExpiration',
                'username' => 'test_user_add_forced_expiration',
                'authSource' => ['platform'],
                'roles' => ['ROLE_STUDENT'],
                'locale' => 'en_US',
                'hasExpirationDate' => '0',
            ],
            [],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $user = static::getContainer()->get(UserRepository::class)->find($data['userId']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull(
            $user->getExpirationDate(),
            'A session admin must not be able to bypass registration.user_hide_never_expire_option by claiming hasExpirationDate=0.'
        );
    }
}
