<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\NativeClock;

use const JSON_THROW_ON_ERROR;
use const STR_PAD_LEFT;

/**
 * Password changes and 2FA changes must be independently authorized and must
 * never overwrite existing credentials when their validation fails.
 */
class AccountTwoFactorActivationTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const URL = '/account/change-password';

    public function testActivationIsRefusedBeforeTheSecretWasShown(): void
    {
        $client = $this->loginAs('mfa_activation_unseen');

        $this->submitActivation($client, 'mfa_activation_unseen', '123456');

        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->reloadUser('mfa_activation_unseen')->getMfaEnabled());
    }

    public function testActivationIsRefusedWithAWrongCode(): void
    {
        $client = $this->loginAs('mfa_activation_wrong');

        // First submission: ask for 2FA, which generates the secret and shows the QR code.
        $this->submitActivation($client, 'mfa_activation_wrong', '');
        $secret = $this->getTemporarySecret($client);

        $wrongCode = str_pad((string) (((int) $this->currentCode($secret) + 1) % 1000000), 6, '0', STR_PAD_LEFT);
        $this->submitActivation($client, 'mfa_activation_wrong', $wrongCode);

        $this->assertResponseIsSuccessful();
        $user = $this->reloadUser('mfa_activation_wrong');
        $this->assertFalse($user->getMfaEnabled());
        $this->assertEmpty($user->getMfaSecret());
    }

    public function testActivationSucceedsWithTheCodeOfTheGeneratedSecret(): void
    {
        $client = $this->loginAs('mfa_activation_ok');
        $before = $this->credentialSnapshot($this->reloadUser('mfa_activation_ok'));

        $this->submitActivation($client, 'mfa_activation_ok', '');
        $secret = $this->getTemporarySecret($client);

        $this->submitActivation($client, 'mfa_activation_ok', $this->currentCode($secret));

        $this->assertResponseRedirects('/account/home');
        $user = $this->reloadUser('mfa_activation_ok');
        $this->assertTrue($user->getMfaEnabled());
        $this->assertSame('TOTP', $user->getMfaService());
        $this->assertNotEmpty($user->getMfaSecret());
        $this->assertPasswordUnchanged($before, $user, 'mfa_activation_ok');
    }

    /**
     * @dataProvider invalidPasswordSubmissions
     */
    public function testInvalidPasswordSubmissionPreservesCredentials(bool $globalMfa, string $newPassword, string $confirmation, bool $rotate): void
    {
        $username = 'mfa_invalid_password';
        $client = $this->loginAs($username, $globalMfa);
        $before = $this->credentialSnapshot($this->reloadUser($username));

        $this->submitChange($client, $username, $newPassword, $confirmation, url: $rotate ? self::URL.'?rotate=1' : self::URL);

        self::assertResponseIsSuccessful();
        $user = $this->reloadUser($username);
        $this->assertPasswordUnchanged($before, $user, $username);
        self::assertFalse($user->getMfaEnabled());
    }

    public static function invalidPasswordSubmissions(): iterable
    {
        yield 'current password only, global 2FA disabled' => [false, '', '', false];

        yield 'current password only, global 2FA enabled' => [true, '', '', false];

        yield 'confirmation only' => [true, '', 'New-password-42!', false];

        yield 'new password without confirmation' => [true, 'New-password-42!', '', false];

        yield 'mismatched confirmation' => [true, 'New-password-42!', 'Other-password-42!', false];

        yield 'forced rotation without a new password' => [true, '', '', true];
    }

    public function testZeroPasswordDoesNotBypassThePasswordPolicy(): void
    {
        $username = 'mfa_zero_password';
        $client = $this->loginAs($username, false);
        self::getContainer()->get(SettingsManager::class)->updateSetting('security.check_password', 'true');
        self::getContainer()->get(SettingsManager::class)->updateSetting('security.password_requirements', '');
        $before = $this->credentialSnapshot($this->reloadUser($username));

        $this->submitChange($client, $username, '0', '0');

        self::assertResponseIsSuccessful();
        $this->assertPasswordUnchanged($before, $this->reloadUser($username), $username);
    }

    public function testDeactivationPreservesPasswordAndAllowsFreshLogin(): void
    {
        $username = 'mfa_deactivate_ok';
        $client = $this->loginAs($username);
        $secret = $this->activate($client, $username);
        $user = $this->reloadUser($username);
        $user->setMfaBackupCodes('test-only-backup-codes');
        $user->setMfaLastUsed(new DateTime());
        self::getContainer()->get(UserRepository::class)->updateUser($user);
        $before = $this->credentialSnapshot($user);

        $this->submitChange($client, $username, enable2FA: false, code: $this->currentCode($secret));

        self::assertResponseRedirects('/account/home');
        $user = $this->reloadUser($username);
        $this->assertPasswordUnchanged($before, $user, $username);
        self::assertFalse($user->getMfaEnabled());
        self::assertNull($user->getMfaSecret());
        self::assertNull($user->getMfaService());
        self::assertNull($user->getMfaBackupCodes());
        self::assertNull($user->getMfaLastUsed());

        // A fresh browser session must authenticate with the original password and no TOTP.
        $client->restart();
        $client->jsonRequest('POST', '/login_json', ['username' => $username, 'password' => $username], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('requires2FA', $data);
        self::assertArrayNotHasKey('error', $data);
    }

    /**
     * @dataProvider refusedDeactivationSubmissions
     */
    public function testDeactivationWithInvalidInputDoesNotChangeEitherCredential(string $failure): void
    {
        $username = 'mfa_deactivate_invalid';
        $client = $this->loginAs($username);
        $secret = $this->activate($client, $username);
        $user = $this->reloadUser($username);
        $before = $this->credentialSnapshot($user);
        $storedSecret = $user->getMfaSecret();
        $code = $this->currentCode($secret);
        if ('wrong code' === $failure) {
            $code = str_pad((string) (((int) $code + 1) % 1000000), 6, '0', STR_PAD_LEFT);
        } elseif ('missing code' === $failure) {
            $code = '';
        }

        $this->submitChange(
            $client,
            'wrong password' === $failure ? 'wrong-current-password' : $username,
            'missing confirmation' === $failure ? 'New-password-42!' : '',
            '',
            false,
            $code,
        );

        self::assertResponseIsSuccessful();
        $user = $this->reloadUser($username);
        $this->assertPasswordUnchanged($before, $user, $username);
        self::assertTrue($user->getMfaEnabled());
        self::assertSame('TOTP', $user->getMfaService());
        self::assertSame($storedSecret, $user->getMfaSecret());
    }

    public static function refusedDeactivationSubmissions(): iterable
    {
        yield ['wrong code'];

        yield ['missing code'];

        yield ['wrong password'];

        yield ['missing confirmation'];
    }

    public function testEnabledMfaWithoutASecretCannotBeDisabledWithAnArbitraryCode(): void
    {
        $username = 'mfa_missing_secret';
        $client = $this->loginAs($username);
        $user = $this->reloadUser($username);
        $user->setMfaEnabled(true);
        $user->setMfaService('TOTP');
        self::getContainer()->get(UserRepository::class)->updateUser($user);
        $before = $this->credentialSnapshot($user);

        $this->submitChange($client, $username, enable2FA: false, code: '123456');

        self::assertResponseIsSuccessful();
        $user = $this->reloadUser($username);
        self::assertTrue($user->getMfaEnabled());
        self::assertNull($user->getMfaSecret());
        $this->assertPasswordUnchanged($before, $user, $username);
    }

    public function testActivationAndPasswordChangeAreBothSaved(): void
    {
        $username = 'mfa_activate_and_password';
        $client = $this->loginAs($username);
        $this->submitActivation($client, $username, '');
        $secret = $this->getTemporarySecret($client);

        $this->submitChange($client, $username, 'New-password-42!', 'New-password-42!', true, $this->currentCode($secret));

        self::assertResponseRedirects('/account/home');
        $user = $this->reloadUser($username);
        self::assertTrue($user->getMfaEnabled());
        self::assertNotEmpty($user->getMfaSecret());
        $this->assertPasswordChanged($user, $username, 'New-password-42!');
    }

    public function testDeactivationAndPasswordChangeAreBothSaved(): void
    {
        $username = 'mfa_deactivate_and_password';
        $client = $this->loginAs($username);
        $secret = $this->activate($client, $username);

        $this->submitChange($client, $username, 'New-password-42!', 'New-password-42!', false, $this->currentCode($secret));

        self::assertResponseRedirects('/account/home');
        $user = $this->reloadUser($username);
        self::assertFalse($user->getMfaEnabled());
        self::assertNull($user->getMfaService());
        self::assertNull($user->getMfaSecret());
        $this->assertPasswordChanged($user, $username, 'New-password-42!');
    }

    public function testPasswordChangeWithoutMfaStillWorks(): void
    {
        $username = 'mfa_password_only';
        $client = $this->loginAs($username, false);

        $this->submitChange($client, $username, 'New-password-42!', 'New-password-42!');

        self::assertResponseRedirects('/account/home');
        $user = $this->reloadUser($username);
        self::assertFalse($user->getMfaEnabled());
        $this->assertPasswordChanged($user, $username, 'New-password-42!');
    }

    public function testPasswordChangeKeepsEnabledMfa(): void
    {
        $username = 'mfa_password_keep_mfa';
        $client = $this->loginAs($username);
        $secret = $this->activate($client, $username);
        $storedSecret = $this->reloadUser($username)->getMfaSecret();

        $this->submitChange($client, $username, 'New-password-42!', 'New-password-42!', true, $this->currentCode($secret));

        self::assertResponseRedirects('/account/home');
        $user = $this->reloadUser($username);
        self::assertTrue($user->getMfaEnabled());
        self::assertSame($storedSecret, $user->getMfaSecret());
        $this->assertPasswordChanged($user, $username, 'New-password-42!');
    }

    public function testWrongCurrentPasswordCannotChangePasswordWithoutMfa(): void
    {
        $username = 'mfa_password_wrong_current';
        $client = $this->loginAs($username, false);
        $before = $this->credentialSnapshot($this->reloadUser($username));

        $this->submitChange($client, 'wrong-current-password', 'New-password-42!', 'New-password-42!');

        self::assertResponseIsSuccessful();
        $this->assertPasswordUnchanged($before, $this->reloadUser($username), $username);
    }

    public function testRequiredFirstLoginChangeRejectsEmptyPasswordAndAcceptsAValidOne(): void
    {
        $username = 'mfa_first_login_password';
        $client = $this->loginAs($username, false);
        self::getContainer()->get(SettingsManager::class)->updateSetting('security.force_renew_password_at_first_login', 'true');
        $user = $this->reloadUser($username);
        $user->setPasswordRequestedAt(new DateTime());
        self::getContainer()->get(UserRepository::class)->updateUser($user);
        $before = $this->credentialSnapshot($user);

        $this->submitChange($client, $username);

        self::assertResponseIsSuccessful();
        $user = $this->reloadUser($username);
        $this->assertPasswordUnchanged($before, $user, $username);
        self::assertNotNull($user->getPasswordRequestedAt());

        $this->submitChange($client, $username, 'New-password-42!', 'New-password-42!');

        self::assertResponseRedirects('/account/home');
        $user = $this->reloadUser($username);
        $this->assertPasswordChanged($user, $username, 'New-password-42!');
        self::assertNull($user->getPasswordRequestedAt());
    }

    /**
     * @return array{password: ?string, updatedAt: ?string}
     */
    private function credentialSnapshot(User $user): array
    {
        return [
            'password' => $user->getPassword(),
            'updatedAt' => $user->getPasswordUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array{password: ?string, updatedAt: ?string} $before
     */
    private function assertPasswordUnchanged(array $before, User $user, string $password): void
    {
        self::assertSame($before, $this->credentialSnapshot($user));
        self::assertNotEmpty($user->getPassword());
        self::assertTrue(self::getContainer()->get(UserRepository::class)->isPasswordValid($user, $password));
    }

    private function assertPasswordChanged(User $user, string $oldPassword, string $newPassword): void
    {
        $repo = self::getContainer()->get(UserRepository::class);
        self::assertFalse($repo->isPasswordValid($user, $oldPassword));
        self::assertTrue($repo->isPasswordValid($user, $newPassword));
        self::assertNotNull($user->getPasswordUpdatedAt());
    }

    private function activate(KernelBrowser $client, string $username): string
    {
        $this->submitActivation($client, $username, '');
        $secret = $this->getTemporarySecret($client);
        $this->submitActivation($client, $username, $this->currentCode($secret));
        self::assertResponseRedirects('/account/home');

        return $secret;
    }

    private function loginAs(string $username, bool $globalMfa = true): KernelBrowser
    {
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)->updateSetting('security.2fa_enable', $globalMfa ? 'true' : 'false');

        $client->loginUser($this->createUser($username));

        return $client;
    }

    private function submitActivation(KernelBrowser $client, string $password, string $code): void
    {
        $this->submitChange($client, $password, enable2FA: true, code: $code);
    }

    private function submitChange(
        KernelBrowser $client,
        string $currentPassword,
        string $newPassword = '',
        string $confirmation = '',
        ?bool $enable2FA = null,
        string $code = '',
        string $url = self::URL,
    ): void {
        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="change_password"]')->form();
        $form['change_password[currentPassword]'] = $currentPassword;
        $form['change_password[newPassword]'] = $newPassword;
        $form['change_password[confirmPassword]'] = $confirmation;
        if (null !== $enable2FA) {
            if ($enable2FA) {
                $form['change_password[enable2FA]']->tick();
            } else {
                $form['change_password[enable2FA]']->untick();
            }
            $form['change_password[confirm2FACode]'] = $code;
        }

        $client->submit($form, [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
    }

    private function getTemporarySecret(KernelBrowser $client): string
    {
        $secret = (string) $client->getRequest()->getSession()->get('temporary_mfa_secret', '');
        $this->assertNotSame('', $secret, 'A temporary 2FA secret should be generated when 2FA is requested.');

        return $secret;
    }

    private function currentCode(string $secret): string
    {
        return TOTP::create($secret, clock: new NativeClock())->now();
    }

    private function reloadUser(string $username): User
    {
        $repo = static::getContainer()->get(UserRepository::class);
        static::getContainer()->get('doctrine')->getManager()->clear();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }
}
