<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientRegistrar;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthResourceRegistry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\RequestStack;

final class OAuthClientRegistrarTest extends TestCase
{
    public function testItRejectsMissingRedirectUris(): void
    {
        $this->expectRegistrationError([], 'invalid_client_metadata');
    }

    public function testItRejectsMoreThanFiveRedirectUris(): void
    {
        $uris = [];
        for ($i = 0; $i < 6; ++$i) {
            $uris[] = "https://client.example/callback{$i}";
        }

        $this->expectRegistrationError(['redirect_uris' => $uris], 'invalid_client_metadata');
    }

    public function testItRejectsNonHttpsNonLoopbackRedirectUri(): void
    {
        $this->expectRegistrationError(
            ['redirect_uris' => ['http://evil.example/callback']],
            'invalid_redirect_uri',
        );
    }

    public function testItRejectsRedirectUriWithFragment(): void
    {
        $this->expectRegistrationError(
            ['redirect_uris' => ['https://client.example/callback#frag']],
            'invalid_redirect_uri',
        );
    }

    public function testItRejectsMalformedRedirectUri(): void
    {
        $this->expectRegistrationError(
            ['redirect_uris' => ['not-a-uri']],
            'invalid_redirect_uri',
        );
    }

    public function testItAcceptsHttpsRedirectUri(): void
    {
        $response = $this->register(['redirect_uris' => ['https://client.example/callback']]);

        self::assertSame(['https://client.example/callback'], $response['redirect_uris']);
        self::assertArrayNotHasKey('client_secret', $response);
    }

    public function testItAcceptsLoopbackHttpRedirectUri(): void
    {
        $response = $this->register(['redirect_uris' => ['http://127.0.0.1:8765/callback']]);

        self::assertSame(['http://127.0.0.1:8765/callback'], $response['redirect_uris']);
    }

    public function testItDefaultsToPublicClientWithNoSecret(): void
    {
        $response = $this->register(['redirect_uris' => ['https://client.example/callback']]);

        self::assertSame('none', $response['token_endpoint_auth_method']);
        self::assertArrayNotHasKey('client_secret', $response);
    }

    public function testItIssuesASecretForConfidentialClients(): void
    {
        $response = $this->register([
            'redirect_uris' => ['https://client.example/callback'],
            'token_endpoint_auth_method' => 'client_secret_post',
        ]);

        self::assertArrayHasKey('client_secret', $response);
        self::assertSame(0, $response['client_secret_expires_at']);
    }

    public function testItRejectsUnsupportedGrantType(): void
    {
        $this->expectRegistrationError(
            [
                'redirect_uris' => ['https://client.example/callback'],
                'grant_types' => ['client_credentials'],
            ],
            'invalid_client_metadata',
        );
    }

    public function testItDefaultsToMcpScope(): void
    {
        $response = $this->register(['redirect_uris' => ['https://client.example/callback']]);

        self::assertSame('mcp', $response['scope']);
    }

    public function testItAcceptsExplicitMcpScope(): void
    {
        $response = $this->register([
            'redirect_uris' => ['https://client.example/callback'],
            'scope' => 'mcp',
        ]);

        self::assertSame('mcp', $response['scope']);
    }

    public function testItRejectsUnknownScope(): void
    {
        $this->expectRegistrationError(
            [
                'redirect_uris' => ['https://client.example/callback'],
                'scope' => 'mcp storefront',
            ],
            'invalid_client_metadata',
        );
    }

    public function testSelfRegisteredClientIsAllowlistedOnlyForMcp(): void
    {
        $capturedClient = null;
        $this->register(
            ['redirect_uris' => ['https://client.example/callback']],
            false,
            $capturedClient,
        );

        self::assertNotNull($capturedClient);
        self::assertSame([OAuthResourceRegistry::MCP_RESOURCE], $capturedClient->getAllowedResources());
    }

    public function testItKeepsOnlyHttpsInformationUris(): void
    {
        $capturedClient = null;
        $response = $this->register(
            [
                'redirect_uris' => ['https://client.example/callback'],
                'client_uri' => 'https://client.example/',
                'logo_uri' => 'http://client.example/logo.png',
                'policy_uri' => 'data:text/html,policy',
                'tos_uri' => '//client.example/tos',
            ],
            false,
            $capturedClient,
        );

        self::assertNotNull($capturedClient);
        self::assertSame('https://client.example/', $capturedClient->getClientUri());
        self::assertNull($capturedClient->getLogoUri());
        self::assertNull($capturedClient->getPolicyUri());
        self::assertNull($capturedClient->getTosUri());
        self::assertSame('https://client.example/', $response['client_uri']);
        self::assertArrayNotHasKey('logo_uri', $response);
        self::assertArrayNotHasKey('policy_uri', $response);
        self::assertArrayNotHasKey('tos_uri', $response);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function expectRegistrationError(array $metadata, string $expectedErrorCode): void
    {
        try {
            $this->register($metadata);
            self::fail('Expected an OAuthException to be thrown.');
        } catch (OAuthException $exception) {
            self::assertSame($expectedErrorCode, $exception->getErrorCode());
        }
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    private function register(array $metadata, bool $dynamicClientRegistration = false, ?OAuthClient &$capturedClient = null): array
    {
        $accessUrl = new AccessUrl();
        (new ReflectionProperty(AccessUrl::class, 'id'))->setValue($accessUrl, 1);

        $accessUrlRepository = $this->createMock(AccessUrlRepository::class);
        $accessUrlRepository->method('getFirstId')->willReturn(1);
        $accessUrlRepository->method('find')->with(1)->willReturn($accessUrl);
        $accessUrlHelper = new AccessUrlHelper($accessUrlRepository, new RequestStack());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(static function (object $entity) use (&$capturedClient): void {
            if ($entity instanceof OAuthClient) {
                $capturedClient = $entity;
            }
        });

        $registrar = new OAuthClientRegistrar($entityManager, $accessUrlHelper, new OAuthResourceRegistry());

        return $registrar->register($metadata, '127.0.0.1', $dynamicClientRegistration);
    }
}
