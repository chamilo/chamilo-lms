<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\Service\Conference\MicrosoftTeamsJoinAuthenticationService;
use Chamilo\CoreBundle\Service\Conference\TeamsPluginConfigurationInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use const JSON_THROW_ON_ERROR;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;

final class MicrosoftTeamsJoinAuthenticationServiceTest extends TestCase
{
    public function testBuildsAuthorizationUrlWithStatePkceAndLoginHint(): void
    {
        $service = $this->service(new MockHttpClient());
        $request = self::requestWithSession();

        $url = $service->begin($request, 42, 8, 'teacher@example.org');
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('https', parse_url($url, PHP_URL_SCHEME));
        self::assertSame('login.microsoftonline.com', parse_url($url, PHP_URL_HOST));
        self::assertSame('/tenant-id/oauth2/v2.0/authorize', parse_url($url, PHP_URL_PATH));
        self::assertSame('client-id', $query['client_id']);
        self::assertSame('code', $query['response_type']);
        self::assertSame('query', $query['response_mode']);
        self::assertSame('User.Read', $query['scope']);
        self::assertSame('teacher@example.org', $query['login_hint']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertNotSame('', $query['state']);
        self::assertNotSame('', $query['code_challenge']);

        $pending = $request->getSession()->get('_teams_join_auth');
        self::assertIsArray($pending);
        self::assertSame(42, $pending['meeting_id']);
        self::assertSame(8, $pending['user_id']);
        self::assertSame($query['state'], $pending['state']);
    }

    public function testCompletesAuthorizationAndReadsSignedInMicrosoftAccount(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$requests): MockResponse {
                $requests[] = [$method, $url, $options];

                return 1 === \count($requests)
                    ? self::jsonResponse(['access_token' => 'delegated-token'])
                    : self::jsonResponse([
                        'id' => 'microsoft-user-id',
                        'mail' => 'teacher@example.org',
                        'userPrincipalName' => 'teacher@example.org',
                    ]);
            }
        );

        $service = $this->service($httpClient);
        $startRequest = self::requestWithSession();
        $service->begin($startRequest, 42, 8, 'teacher@example.org');

        $pending = $startRequest->getSession()->get('_teams_join_auth');
        self::assertIsArray($pending);

        $callbackRequest = new Request([
            'state' => $pending['state'],
            'code' => 'authorization-code',
        ]);
        $callbackRequest->setSession($startRequest->getSession());

        $identity = $service->complete($callbackRequest, 8);

        self::assertSame(42, $identity['meetingId']);
        self::assertSame('microsoft-user-id', $identity['microsoftUserId']);
        self::assertSame('teacher@example.org', $identity['mail']);
        self::assertSame('teacher@example.org', $identity['userPrincipalName']);
        self::assertCount(2, $requests);
        self::assertSame('POST', $requests[0][0]);
        self::assertSame('https://login.microsoftonline.com/tenant-id/oauth2/v2.0/token', $requests[0][1]);
        self::assertSame('GET', $requests[1][0]);
        self::assertSame(
            'https://graph.microsoft.com/v1.0/me?%24select=id,mail,userPrincipalName',
            $requests[1][1]
        );
        self::assertNull($callbackRequest->getSession()->get('_teams_join_auth'));
    }

    public function testRejectsInvalidStateWithoutCallingMicrosoft(): void
    {
        $httpClient = new MockHttpClient(
            static function (): MockResponse {
                self::fail('No Microsoft request should be sent when OAuth state is invalid.');
            }
        );
        $service = $this->service($httpClient);
        $startRequest = self::requestWithSession();
        $service->begin($startRequest, 42, 8, 'teacher@example.org');

        $callbackRequest = new Request([
            'state' => 'wrong-state',
            'code' => 'authorization-code',
        ]);
        $callbackRequest->setSession($startRequest->getSession());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('state is invalid');

        $service->complete($callbackRequest, 8);
    }

    private function service(MockHttpClient $httpClient): MicrosoftTeamsJoinAuthenticationService
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->with('teams_conference_auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://chamilo.example/conference/teams-auth/callback')
        ;

        return new MicrosoftTeamsJoinAuthenticationService(
            $httpClient,
            self::configuration(),
            $urlGenerator,
        );
    }

    private static function requestWithSession(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private static function configuration(): TeamsPluginConfigurationInterface
    {
        return new class implements TeamsPluginConfigurationInterface {
            public function isEnabled(): bool
            {
                return true;
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function getTenantId(): string
            {
                return 'tenant-id';
            }

            public function getClientId(): string
            {
                return 'client-id';
            }

            public function getClientSecret(): string
            {
                return 'client-secret';
            }

            public function isPersonalConferenceEnabled(): bool
            {
                return true;
            }

            public function isGlobalConferenceEnabled(): bool
            {
                return true;
            }
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function jsonResponse(array $data, int $status = 200): MockResponse
    {
        return new MockResponse(
            json_encode($data, JSON_THROW_ON_ERROR),
            [
                'http_code' => $status,
                'response_headers' => ['content-type: application/json'],
            ]
        );
    }
}
