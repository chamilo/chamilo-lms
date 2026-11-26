<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

use const STR_PAD_LEFT;

/**
 * Enabling 2FA from /account/change-password must only succeed once the user
 * proves, with a valid code, that their authenticator app holds the secret
 * that was generated for them.
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

        $this->submitActivation($client, 'mfa_activation_ok', '');
        $secret = $this->getTemporarySecret($client);

        $this->submitActivation($client, 'mfa_activation_ok', $this->currentCode($secret));

        $this->assertResponseRedirects('/account/home');
        $user = $this->reloadUser('mfa_activation_ok');
        $this->assertTrue($user->getMfaEnabled());
        $this->assertSame('TOTP', $user->getMfaService());
        $this->assertNotEmpty($user->getMfaSecret());
    }

    private function loginAs(string $username): KernelBrowser
    {
        $client = static::createClient();
        static::getContainer()->get(SettingsManager::class)->updateSetting('security.2fa_enable', 'true');

        $client->loginUser($this->createUser($username));

        return $client;
    }

    private function submitActivation(KernelBrowser $client, string $password, string $code): void
    {
        $crawler = $client->request('GET', self::URL);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="change_password"]')->form();
        $form['change_password[currentPassword]'] = $password;
        $enable2FA = $form['change_password[enable2FA]'];
        $this->assertInstanceOf(ChoiceFormField::class, $enable2FA);
        $enable2FA->tick();
        $form['change_password[confirm2FACode]'] = $code;

        $client->submit($form);
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
