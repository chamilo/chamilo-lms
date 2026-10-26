<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Login;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mime\Email;

/**
 * A password reset link carries a token that grants the account. It is read in a mailbox, so its
 * host must be the platform's, taken from the access URL: the Host header of the request that
 * asked for the reset is chosen by whoever sent it, who is not the account owner.
 */
final class PasswordResetLinkHostTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const string PLATFORM_URL = 'https://lms.example.org/';
    private const string FOREIGN_HOST = 'foreign.example.net';

    public function testLegacyResetLinkUsesTheAccessUrl(): void
    {
        self::bootKernel();
        $this->setMainUrl(self::PLATFORM_URL);
        $user = $this->createUser('reset_host_legacy', '', 'reset_host_legacy@example.org');

        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());
        $request = Request::create('http://'.self::FOREIGN_HOST.'/main/auth/lostPassword.php', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);

        Login::sendResetEmail($user);

        $body = $this->getSentBody();
        $this->assertStringContainsString(self::PLATFORM_URL.'main/auth/reset.php?token='.$user->getConfirmationToken(), $body);
        $this->assertStringNotContainsString(self::FOREIGN_HOST, $body);
    }

    public function testResetPasswordRouteLinkUsesTheAccessUrl(): void
    {
        $client = self::createClient();
        $this->setMainUrl(self::PLATFORM_URL);
        $this->createUser('reset_host_route', '', 'reset_host_route@example.org');

        // Stateless form CSRF: the placeholder token is accepted when the Origin matches the Host.
        $client->request(
            'POST',
            '/reset-password',
            [
                'reset_password_request_form' => [
                    'email' => 'reset_host_route@example.org',
                    '_token' => 'csrf-token',
                ],
            ],
            [],
            ['HTTP_HOST' => self::FOREIGN_HOST, 'HTTP_ORIGIN' => 'http://'.self::FOREIGN_HOST]
        );

        $this->assertResponseRedirects('/reset-password/check-email');
        $body = $this->getSentBody();
        $this->assertStringContainsString(self::PLATFORM_URL.'reset-password/reset/', $body);
        $this->assertStringNotContainsString(self::FOREIGN_HOST, $body);
    }

    /**
     * Until an administrator sets the main URL, the installation placeholder is not a real
     * address, so links keep following the request rather than pointing to localhost.
     */
    public function testPlaceholderUrlFallsBackToTheRequest(): void
    {
        self::bootKernel();
        $this->setMainUrl(AccessUrl::DEFAULT_ACCESS_URL);
        self::getContainer()->get('request_stack')->push(Request::create('https://campus.example.org/'));

        $this->assertSame(
            'https://campus.example.org/',
            self::getContainer()->get(AccessUrlHelper::class)->getCurrentBaseUrl()
        );
    }

    private function setMainUrl(string $url): void
    {
        $accessUrl = self::getContainer()->get(AccessUrlHelper::class)->getFirstAccessUrl();
        $this->assertNotNull($accessUrl);
        $accessUrl->setUrl($url);
        $this->getEntityManager()->flush();

        // Mail is only sent once the platform has a sender address.
        $this->getEntityManager()->getConnection()->executeStatement(
            "UPDATE settings SET selected_value = 'noreply@example.org' WHERE variable = 'mailer_from_email'"
        );
    }

    private function getSentBody(): string
    {
        $this->assertEmailCount(1);
        $email = self::getMailerMessage();
        $this->assertInstanceOf(Email::class, $email);

        return (string) $email->getHtmlBody();
    }
}
