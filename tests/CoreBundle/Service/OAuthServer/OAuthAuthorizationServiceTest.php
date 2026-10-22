<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Dto\OAuthServer\OAuthAuthorizeRequest;
use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\OAuthClientConsentRepository;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthAuthorizationService;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientResolver;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthMetadataService;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthResourceRegistry;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class OAuthAuthorizationServiceTest extends TestCase
{
    public function testItAcceptsRegisteredMcpResourceAndScope(): void
    {
        $service = $this->createService();
        $client = $this->createClient();
        $request = $this->createAuthorizeRequest('https://lms.example/mcp', 'mcp');

        $validated = $service->validateAuthorizeParameters($request, $client);

        self::assertSame('https://lms.example/mcp', $validated->resource);
        self::assertSame('mcp', $validated->scope);
    }

    public function testItUsesRegisteredScopeWhenAuthorizeRequestOmitsScope(): void
    {
        $service = $this->createService();
        $client = $this->createClient();
        $request = $this->createAuthorizeRequest('https://lms.example/mcp', null);

        $validated = $service->validateAuthorizeParameters($request, $client);

        self::assertSame('mcp', $validated->scope);
    }

    public function testItRejectsResourceNotAllowlistedForClient(): void
    {
        $service = $this->createService();
        $client = $this->createClient()->setAllowedResources([]);

        $this->assertOAuthError(
            'invalid_target',
            fn () => $service->validateAuthorizeParameters(
                $this->createAuthorizeRequest('https://lms.example/mcp', 'mcp'),
                $client,
            ),
        );
    }

    public function testItRejectsUnknownResourceIdentifier(): void
    {
        $service = $this->createService();
        $client = $this->createClient();

        $this->assertOAuthError(
            'invalid_target',
            fn () => $service->validateAuthorizeParameters(
                $this->createAuthorizeRequest('https://evil.example/mcp', 'mcp'),
                $client,
            ),
        );
    }

    public function testItRejectsScopeNotRegisteredForClient(): void
    {
        $service = $this->createService();
        $client = $this->createClient();

        $this->assertOAuthError(
            'invalid_scope',
            fn () => $service->validateAuthorizeParameters(
                $this->createAuthorizeRequest('https://lms.example/mcp', 'storefront'),
                $client,
            ),
        );
    }

    public function testPendingRequestIsOnlyReturnedForItsOwnIdentifier(): void
    {
        $service = $this->createService();
        $request = $this->createSessionRequest();
        $first = $this->createPendingRequest('first-state');
        $second = $this->createPendingRequest('second-state');

        $firstId = $service->stashInSession($request, $first);
        $secondId = $service->stashInSession($request, $second);

        self::assertNotSame($firstId, $secondId);
        self::assertSame($first, $service->popFromSession($request, $firstId));
        self::assertNull($service->popFromSession($request, $firstId));
        self::assertSame($second, $service->popFromSession($request, $secondId));
    }

    public function testPendingRequestIsNotReturnedForUnknownIdentifier(): void
    {
        $service = $this->createService();
        $request = $this->createSessionRequest();
        $requestId = $service->stashInSession($request, $this->createPendingRequest('state'));

        self::assertNull($service->popFromSession($request, ''));
        self::assertNull($service->popFromSession($request, 'unknown'));
        self::assertNotNull($service->popFromSession($request, $requestId));
    }

    public function testPendingRequestsAreBounded(): void
    {
        $service = $this->createService();
        $request = $this->createSessionRequest();

        $oldestId = $service->stashInSession($request, $this->createPendingRequest('oldest'));
        for ($i = 0; $i < OAuthAuthorizationService::MAX_PENDING_REQUESTS; ++$i) {
            $service->stashInSession($request, $this->createPendingRequest('state-'.$i));
        }

        self::assertCount(
            OAuthAuthorizationService::MAX_PENDING_REQUESTS,
            $request->getSession()->get(OAuthAuthorizationService::SESSION_KEY),
        );
        self::assertNull($service->popFromSession($request, $oldestId));
    }

    private function createSessionRequest(): Request
    {
        $request = Request::create('https://lms.example/oauth/authorize');
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function createPendingRequest(string $state): OAuthAuthorizeRequest
    {
        return new OAuthAuthorizeRequest(
            clientId: 'chamilo_oauth_client_test',
            redirectUri: 'https://client.example/callback',
            state: $state,
            codeChallenge: str_repeat('a', 43),
            codeChallengeMethod: 'S256',
            scope: 'mcp',
            resource: null,
            createdAt: time(),
        );
    }

    private function createService(): OAuthAuthorizationService
    {
        $registry = new OAuthResourceRegistry();
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://lms.example/'));
        $metadata = new OAuthMetadataService(
            $requestStack,
            $this->withoutConstructor(SettingsManager::class),
            $registry,
        );

        return new OAuthAuthorizationService(
            $this->withoutConstructor(OAuthClientResolver::class),
            $this->createMock(EntityManagerInterface::class),
            $this->withoutConstructor(AccessUrlHelper::class),
            $this->withoutConstructor(AccessUrlRepository::class),
            $this->withoutConstructor(OAuthClientConsentRepository::class),
            $metadata,
            $registry,
        );
    }

    private function createClient(): OAuthClient
    {
        return (new OAuthClient())
            ->setClientId('chamilo_oauth_client_test')
            ->setTokenEndpointAuthMethod('none')
            ->setRedirectUris(['https://client.example/callback'])
            ->setGrantTypes(['authorization_code', 'refresh_token'])
            ->setResponseTypes(['code'])
            ->setScope('mcp')
            ->setAllowedResources([OAuthResourceRegistry::MCP_RESOURCE])
        ;
    }

    private function createAuthorizeRequest(string $resource, ?string $scope): Request
    {
        $query = [
            'client_id' => 'chamilo_oauth_client_test',
            'redirect_uri' => 'https://client.example/callback',
            'response_type' => 'code',
            'state' => 'test-state',
            'code_challenge_method' => 'S256',
            'code_challenge' => str_repeat('a', 43),
            'resource' => $resource,
        ];
        if (null !== $scope) {
            $query['scope'] = $scope;
        }

        return Request::create('https://lms.example/oauth/authorize', 'GET', $query);
    }

    /**
     * @param callable(): mixed $operation
     */
    private function assertOAuthError(string $errorCode, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected an OAuthException to be thrown.');
        } catch (OAuthException $exception) {
            self::assertSame($errorCode, $exception->getErrorCode());
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function withoutConstructor(string $class): object
    {
        return (new ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}
