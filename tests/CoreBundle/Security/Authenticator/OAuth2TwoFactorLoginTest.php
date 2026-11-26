<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security\Authenticator;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Security\Authenticator\OAuth2\AbstractAuthenticator;
use Chamilo\CoreBundle\Security\Authenticator\OAuth2\KeycloakAuthenticator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

use const OPENSSL_RAW_DATA;
use const PHP_URL_PATH;
use const STR_PAD_LEFT;

/**
 * A login through an OAuth2 provider must still ask for the TOTP code of users
 * who turned on 2FA, as the password login does.
 */
final class OAuth2TwoFactorLoginTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const string TOTP_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    public function testMfaUserIsHeldUntilTheTotpStep(): void
    {
        self::createClient();
        $this->setPlatform2fa(true);
        $this->setLoginRedirect('{"STUDENT":"sessions"}');
        $user = $this->createMfaUser('oauth2_mfa_held');

        [$response, $request] = $this->runOnAuthenticationSuccess($user);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login/2fa', parse_url($response->getTargetUrl(), PHP_URL_PATH));
        $this->assertNull(self::getContainer()->get(TokenStorageInterface::class)->getToken());

        $pending = $request->getSession()->get(AbstractAuthenticator::PENDING_2FA_SESSION_KEY);
        $this->assertIsArray($pending);
        $this->assertSame($user->getId(), $pending['user_id']);
    }

    public function testUserWithoutMfaIsLoggedInDirectly(): void
    {
        self::createClient();
        $this->setPlatform2fa(true);
        $this->setLoginRedirect('{}');
        $user = $this->createUser('oauth2_no_mfa');

        [$response, $request] = $this->runOnAuthenticationSuccess($user);

        $this->assertSame('/', parse_url($response->getTargetUrl(), PHP_URL_PATH));
        $this->assertNotNull(self::getContainer()->get(TokenStorageInterface::class)->getToken());
        $this->assertFalse($request->getSession()->has(AbstractAuthenticator::PENDING_2FA_SESSION_KEY));
    }

    public function testTotpStepIsSkippedWhenPlatform2faIsOff(): void
    {
        self::createClient();
        $this->setPlatform2fa(false);
        $this->setLoginRedirect('{"STUDENT":"sessions"}');
        $user = $this->createMfaUser('oauth2_mfa_platform_off');

        [$response] = $this->runOnAuthenticationSuccess($user);

        $this->assertSame('/sessions', parse_url($response->getTargetUrl(), PHP_URL_PATH));
        $this->assertNotNull(self::getContainer()->get(TokenStorageInterface::class)->getToken());
    }

    public function testOAuth2UsesTheConfiguredProfileRedirectWithoutMfa(): void
    {
        self::createClient();
        $this->setPlatform2fa(true);
        $this->setLoginRedirect('{"STUDENT":"sessions","COURSEMANAGER":"courses"}');
        $user = $this->createUser('oauth2_redirect_student');
        $user->setRoles(['ROLE_STUDENT']);
        [$response] = $this->runOnAuthenticationSuccess($user);
        $this->assertSame('/sessions', $response->getTargetUrl());

        $user->setRoles(['ROLE_TEACHER']);
        [$response] = $this->runOnAuthenticationSuccess($user);
        $this->assertSame('/courses', $response->getTargetUrl());
    }

    public function testOAuth2RejectsAnExternalRedirect(): void
    {
        self::createClient();
        $this->setPlatform2fa(true);
        $this->setLoginRedirect('{"STUDENT":"//example.invalid/"}');
        $user = $this->createUser('oauth2_redirect_unsafe');
        $user->setRoles(['ROLE_STUDENT']);

        [$response] = $this->runOnAuthenticationSuccess($user);

        $this->assertSame('/', $response->getTargetUrl());
    }

    public function testTotpPageWithoutPendingLoginGoesBackToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login/2fa');

        $this->assertResponseRedirects('/login');
    }

    public function testWrongCodeKeepsTheSessionAnonymous(): void
    {
        $client = self::createClient();
        $this->setPlatform2fa(true);
        $user = $this->createMfaUser('oauth2_mfa_wrong');
        $this->startPendingLogin($client, $user);

        $this->submitCode($client, $this->wrongCode());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#login-2fa .alert-danger');
        $this->assertNotLoggedIn($client);
    }

    public function testValidCodeCompletesTheLogin(): void
    {
        $client = self::createClient();
        $this->setPlatform2fa(true);
        $this->setLoginRedirect('{"STUDENT":"sessions"}');
        $user = $this->createMfaUser('oauth2_mfa_ok');
        $this->startPendingLogin($client, $user);

        $this->submitCode($client, $this->currentCode());

        $this->assertResponseRedirects('/sessions');
        $client->request('GET', '/account/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testDisabledUserCannotCompleteAPendingLogin(): void
    {
        $client = self::createClient();
        $this->setPlatform2fa(true);
        $user = $this->createMfaUser('oauth2_mfa_disabled');
        $this->startPendingLogin($client, $user);

        // An administrator can disable the account after the OAuth provider callback.
        $user->setActive(0);
        self::getContainer()->get(UserRepository::class)->updateUser($user);

        $client->request('GET', '/login/2fa');
        $this->assertResponseRedirects('/login');
        $this->assertNotLoggedIn($client);
    }

    public function testTooManyWrongCodesCancelTheLogin(): void
    {
        $client = self::createClient();
        $this->setPlatform2fa(true);
        $user = $this->createMfaUser('oauth2_mfa_attempts');
        $this->startPendingLogin($client, $user);

        for ($i = 1; $i < 5; $i++) {
            $this->submitCode($client, $this->wrongCode());
            $this->assertResponseIsSuccessful();
        }

        $this->submitCode($client, $this->wrongCode());
        $this->assertResponseRedirects('/login');

        // The pending login is gone, even a valid code no longer helps.
        $client->request('GET', '/login/2fa');
        $this->assertResponseRedirects('/login');
        $this->assertNotLoggedIn($client);
    }

    /**
     * @return array{0: RedirectResponse, 1: Request}
     */
    private function runOnAuthenticationSuccess(User $user): array
    {
        $container = self::getContainer();
        $authenticator = $container->get(KeycloakAuthenticator::class);

        $request = Request::create('/connect/keycloak/check');
        $request->setSession(new Session(new MockArraySessionStorage()));

        // As the authenticator manager does before calling onAuthenticationSuccess().
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());
        $container->get(TokenStorageInterface::class)->setToken($token);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');
        $this->assertInstanceOf(RedirectResponse::class, $response);

        return [$response, $request];
    }

    private function startPendingLogin(KernelBrowser $client, User $user): void
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        $session->set(AbstractAuthenticator::PENDING_2FA_SESSION_KEY, [
            'user_id' => $user->getId(),
            'expires_at' => time() + AbstractAuthenticator::PENDING_2FA_LIFETIME,
            'attempts' => 0,
        ]);
        $session->save();

        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }

    private function submitCode(KernelBrowser $client, string $code): void
    {
        $crawler = $client->request('GET', '/login/2fa');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#login-2fa form')->form();
        $form['totp'] = $code;
        $client->submit($form);
    }

    private function assertNotLoggedIn(KernelBrowser $client): void
    {
        $client->request('GET', '/account/edit');
        $this->assertResponseStatusCodeSame(302);
    }

    private function setPlatform2fa(bool $enabled): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('security.2fa_enable', $enabled ? 'true' : 'false')
        ;
    }

    private function setLoginRedirect(string $setting): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('registration.redirect_after_login', $setting)
        ;
    }

    private function createMfaUser(string $username): User
    {
        $user = $this->createUser($username);
        $user
            ->setRoles(['ROLE_STUDENT'])
            ->setMfaEnabled(true)
            ->setMfaService('TOTP')
            ->setMfaSecret($this->encryptSecret(self::TOTP_SECRET))
        ;
        self::getContainer()->get(UserRepository::class)->updateUser($user);

        return $user;
    }

    /**
     * Same storage format as AccountController::encryptTOTPSecret().
     */
    private function encryptSecret(string $secret): string
    {
        $key = hash_hkdf('sha256', (string) $_ENV['APP_SECRET'], 32, 'chamilo:mfa:totp-secret:v1');
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $ciphertext = openssl_encrypt($secret, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return 'v2:'.base64_encode($iv.$ciphertext);
    }

    private function currentCode(): string
    {
        return TOTP::create(self::TOTP_SECRET, clock: new NativeClock())->now();
    }

    private function wrongCode(): string
    {
        return str_pad((string) (((int) $this->currentCode() + 1) % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
