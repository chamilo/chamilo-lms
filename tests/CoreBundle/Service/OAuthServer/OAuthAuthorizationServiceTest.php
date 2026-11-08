<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Dto\OAuthServer\OAuthAuthorizeRequest;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthAuthorizationService;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthClientResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class OAuthAuthorizationServiceTest extends TestCase
{
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

    private function createService(): OAuthAuthorizationService
    {
        return new OAuthAuthorizationService(
            $this->withoutConstructor(OAuthClientResolver::class),
            $this->createMock(EntityManagerInterface::class),
            $this->withoutConstructor(AccessUrlHelper::class),
            $this->withoutConstructor(AccessUrlRepository::class),
        );
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
            resource: null,
            createdAt: time(),
        );
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
