<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientRegistrar;
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
    private function register(array $metadata): array
    {
        $accessUrl = new AccessUrl();
        (new ReflectionProperty(AccessUrl::class, 'id'))->setValue($accessUrl, 1);

        $accessUrlRepository = $this->createMock(AccessUrlRepository::class);
        $accessUrlRepository->method('getFirstId')->willReturn(1);
        $accessUrlRepository->method('find')->with(1)->willReturn($accessUrl);
        $accessUrlHelper = new AccessUrlHelper($accessUrlRepository, new RequestStack());

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $registrar = new OAuthClientRegistrar($entityManager, $accessUrlHelper);

        return $registrar->register($metadata, '127.0.0.1');
    }
}
