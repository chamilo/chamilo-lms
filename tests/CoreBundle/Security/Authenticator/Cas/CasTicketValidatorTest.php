<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Security\Authenticator\Cas\CasTicketValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

use const PHP_URL_QUERY;

final class CasTicketValidatorTest extends TestCase
{
    private const string SUCCESS = '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"><cas:authenticationSuccess><cas:user>cas_student</cas:user><cas:attributes><cas:firstname>CAS</cas:firstname><cas:lastname>Student</cas:lastname><cas:email>student@example.org</cas:email><cas:role>ROLE_ADMIN</cas:role></cas:attributes></cas:authenticationSuccess></cas:serviceResponse>';

    public function testValidationUsesExactServiceAndVerifiedTls(): void
    {
        $response = new MockResponse(self::SUCCESS, ['http_code' => 200]);
        $validator = new CasTicketValidator(new MockHttpClient($response));
        $service = 'https://portal.example.org/connect/cas/check?state=one-time';
        $identity = $validator->validate(['base' => 'https://cas.example.org/cas', 'validation' => '/p3/serviceValidate'], $service, 'ST-example');
        self::assertSame('cas_student', $identity['username']);
        self::assertSame(['student@example.org'], $identity['attributes']['email']);
        parse_str((string) parse_url($response->getRequestUrl(), PHP_URL_QUERY), $query);
        self::assertSame(['service' => $service, 'ticket' => 'ST-example'], $query);
        self::assertSame(0, $response->getRequestOptions()['max_redirects']);
        self::assertTrue($response->getRequestOptions()['verify_peer']);
        self::assertTrue($response->getRequestOptions()['verify_host']);
    }

    public function testLegacyResponse(): void
    {
        self::assertSame(['username' => 'cas_student', 'attributes' => []], CasTicketValidator::parse("yes\ncas_student\n", true));
    }

    public function testNamespacePrefixDoesNotAffectValidation(): void
    {
        $body = str_replace(['xmlns:cas=', 'cas:'], ['xmlns:trusted=', 'trusted:'], self::SUCCESS);
        $body = str_replace('<trusted:serviceResponse ', '<trusted:serviceResponse xmlns:cas="urn:other" ', $body);
        self::assertSame('cas_student', CasTicketValidator::parse($body)['username']);
    }

    public function testDefaultNamespaceIsAccepted(): void
    {
        $body = str_replace(['xmlns:cas=', 'cas:'], ['xmlns=', ''], self::SUCCESS);
        self::assertSame('cas_student', CasTicketValidator::parse($body)['username']);
    }

    /**
     * @dataProvider invalidResponses
     */
    public function testInvalidResponsesNeverAuthenticate(string $body, bool $legacy): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        CasTicketValidator::parse($body, $legacy);
    }

    public static function invalidResponses(): iterable
    {
        yield 'failed v1' => ["no\n", true];

        yield 'extra v1 data' => ["yes\nstudent\nadmin\n", true];

        yield 'unknown namespace' => [str_replace('http://www.yale.edu/tp/cas', 'urn:other', self::SUCCESS), false];

        yield 'wrong success namespace' => [str_replace('<cas:authenticationSuccess>', '<cas:authenticationSuccess xmlns:cas="urn:other">', self::SUCCESS), false];

        yield 'wrong principal namespace' => [str_replace('<cas:user>', '<cas:user xmlns:cas="urn:other">', self::SUCCESS), false];

        yield 'multiple principals' => [str_replace('</cas:user>', '</cas:user><cas:user>admin</cas:user>', self::SUCCESS), false];

        yield 'failure and success' => [str_replace('</cas:serviceResponse>', '<cas:authenticationFailure code="INVALID_TICKET"/></cas:serviceResponse>', self::SUCCESS), false];

        yield 'DTD' => ['<!DOCTYPE cas:serviceResponse [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'.self::SUCCESS, false];

        yield 'oversized response' => [str_repeat('a', 65537), false];

        yield 'broken XML' => ['<broken', false];
    }

    public function testHttpRedirectNeverAuthenticates(): void
    {
        $validator = new CasTicketValidator(new MockHttpClient(new MockResponse(self::SUCCESS, ['http_code' => 302, 'response_headers' => ['location: https://other.example.org/']])));
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $validator->validate(['base' => 'https://cas.example.org', 'validation' => '/serviceValidate'], 'https://portal.example.org/check', 'ST-example');
    }

    public function testProxyTicketNeverReachesProvider(): void
    {
        $client = new MockHttpClient(static function (): never {
            self::fail('Invalid ticket was sent to CAS.');
        });
        $this->expectException(CustomUserMessageAuthenticationException::class);
        (new CasTicketValidator($client))->validate(['base' => 'https://cas.example.org', 'validation' => '/serviceValidate'], 'https://portal.example.org/check', 'PT-proxy');
    }
}
