<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\Service\Conference\MicrosoftTeamsGraphClient;
use Chamilo\CoreBundle\Service\Conference\TeamsPluginConfigurationInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use const JSON_THROW_ON_ERROR;

final class MicrosoftTeamsGraphClientTest extends TestCase
{
    public function testConfigurationRequiresAllCredentials(): void
    {
        $httpClient = new MockHttpClient();

        self::assertTrue(
            (new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant', 'client', 'secret')))->isConfigured()
        );
        self::assertFalse(
            (new MicrosoftTeamsGraphClient($httpClient, self::configuration('', 'client', 'secret')))->isConfigured()
        );
        self::assertFalse(
            (new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant', '', 'secret')))->isConfigured()
        );
        self::assertFalse(
            (new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant', 'client', '')))->isConfigured()
        );
    }

    public function testCreatesTeamsOnlineMeetingUsingClientCredentials(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$requests): MockResponse {
                $requests[] = [$method, $url, $options];

                return match (\count($requests)) {
                    1 => self::jsonResponse([
                        'access_token' => 'graph-token',
                        'expires_in' => 3600,
                    ]),
                    2 => self::jsonResponse(['id' => 'user-object-id']),
                    default => self::jsonResponse([
                        'id' => 'meeting/123',
                        'joinWebUrl' => 'https://teams.microsoft.com/l/meetup-join/example',
                    ], 201),
                };
            }
        );

        $client = new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant-id', 'client-id', 'client-secret'));

        $meeting = $client->createOnlineMeeting(
            'teacher@example.org',
            'Course review',
            new DateTimeImmutable('2026-10-08 15:00:00+00:00'),
            new DateTimeImmutable('2026-10-08 16:00:00+00:00'),
        );

        self::assertSame('meeting/123', $meeting['meetingId']);
        self::assertSame('https://teams.microsoft.com/l/meetup-join/example', $meeting['joinUrl']);
        self::assertCount(3, $requests);
        self::assertSame('POST', $requests[0][0]);
        self::assertSame('https://login.microsoftonline.com/tenant-id/oauth2/v2.0/token', $requests[0][1]);
        self::assertSame('GET', $requests[1][0]);
        self::assertSame(
            'https://graph.microsoft.com/v1.0/users/teacher%40example.org?%24select=id',
            $requests[1][1]
        );
        self::assertSame('POST', $requests[2][0]);
        self::assertSame(
            'https://graph.microsoft.com/v1.0/users/user-object-id/onlineMeetings',
            $requests[2][1]
        );

        $payload = json_decode((string) $requests[2][2]['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Course review', $payload['subject']);
        self::assertSame('2026-10-08T15:00:00+00:00', $payload['startDateTime']);
        self::assertSame('2026-10-08T16:00:00+00:00', $payload['endDateTime']);
    }

    public function testUpdatesTeamsOnlineMeeting(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$requests): MockResponse {
                $requests[] = [$method, $url, $options];

                return match (\count($requests)) {
                    1 => self::jsonResponse(['access_token' => 'graph-token', 'expires_in' => 3600]),
                    2 => self::jsonResponse(['id' => 'user-object-id']),
                    default => new MockResponse('', ['http_code' => 204]),
                };
            }
        );

        $client = new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant-id', 'client-id', 'client-secret'));
        $client->updateOnlineMeeting(
            'teacher@example.org',
            'meeting/123',
            'Updated review',
            new DateTimeImmutable('2026-10-09 15:00:00+00:00'),
            new DateTimeImmutable('2026-10-09 16:30:00+00:00'),
        );

        self::assertCount(3, $requests);
        self::assertSame('PATCH', $requests[2][0]);
        self::assertSame(
            'https://graph.microsoft.com/v1.0/users/user-object-id/onlineMeetings/meeting%2F123',
            $requests[2][1]
        );

        $payload = json_decode((string) $requests[2][2]['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Updated review', $payload['subject']);
        self::assertSame('2026-10-09T15:00:00+00:00', $payload['startDateTime']);
        self::assertSame('2026-10-09T16:30:00+00:00', $payload['endDateTime']);
    }

    public function testDeletesTeamsOnlineMeetingAndTreatsRemote404AsIdempotent(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$requests): MockResponse {
                $requests[] = [$method, $url, $options];

                return match (\count($requests)) {
                    1 => self::jsonResponse(['access_token' => 'graph-token', 'expires_in' => 3600]),
                    2 => self::jsonResponse(['id' => 'user-object-id']),
                    default => new MockResponse('', ['http_code' => 404]),
                };
            }
        );

        $client = new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant-id', 'client-id', 'client-secret'));
        $client->deleteOnlineMeeting('teacher@example.org', 'meeting-123');

        self::assertCount(3, $requests);
        self::assertSame('DELETE', $requests[2][0]);
        self::assertSame(
            'https://graph.microsoft.com/v1.0/users/user-object-id/onlineMeetings/meeting-123',
            $requests[2][1]
        );
    }

    public function testRejectsInvalidMeetingDateRangeBeforeGraphCall(): void
    {
        $httpClient = new MockHttpClient(
            static function (): MockResponse {
                self::fail('No HTTP request should be sent for an invalid date range.');
            }
        );
        $client = new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant-id', 'client-id', 'client-secret'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('end date must be after');

        $client->createOnlineMeeting(
            'teacher@example.org',
            'Invalid meeting',
            new DateTimeImmutable('2026-10-08 16:00:00+00:00'),
            new DateTimeImmutable('2026-10-08 15:00:00+00:00')
        );
    }

    public function testFailsWhenOrganizerCannotBeResolved(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$requests): MockResponse {
                $requests[] = [$method, $url, $options];

                if (1 === \count($requests)) {
                    return self::jsonResponse(['access_token' => 'graph-token', 'expires_in' => 3600]);
                }

                return self::jsonResponse(['error' => ['code' => 'Request_ResourceNotFound']], 404);
            }
        );

        $client = new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant-id', 'client-id', 'client-secret'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not resolve the Teams organizer');

        $client->createOnlineMeeting(
            'missing@example.org',
            'Course review',
            new DateTimeImmutable('2026-10-08 15:00:00+00:00'),
            new DateTimeImmutable('2026-10-08 16:00:00+00:00')
        );
    }

    public function testFailsClosedAndDeletesRemoteMeetingWhenJoinUrlIsMissing(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$requests): MockResponse {
                $requests[] = [$method, $url, $options];

                return match (\count($requests)) {
                    1 => self::jsonResponse(['access_token' => 'graph-token', 'expires_in' => 3600]),
                    2 => self::jsonResponse(['id' => 'user-object-id']),
                    3 => self::jsonResponse(['id' => 'meeting-123'], 201),
                    default => new MockResponse('', ['http_code' => 204]),
                };
            }
        );

        $client = new MicrosoftTeamsGraphClient($httpClient, self::configuration('tenant-id', 'client-id', 'client-secret'));

        try {
            $client->createOnlineMeeting(
                'teacher@example.org',
                'Course review',
                new DateTimeImmutable('2026-10-08 15:00:00+00:00'),
                new DateTimeImmutable('2026-10-08 16:00:00+00:00')
            );
            self::fail('An incomplete Teams meeting must fail closed.');
        } catch (RuntimeException $exception) {
            self::assertSame('Microsoft Graph returned a Teams meeting without a join URL.', $exception->getMessage());
        }

        self::assertCount(4, $requests);
        self::assertSame('DELETE', $requests[3][0]);
        self::assertSame(
            'https://graph.microsoft.com/v1.0/users/user-object-id/onlineMeetings/meeting-123',
            $requests[3][1]
        );
    }

    private static function configuration(
        string $tenantId,
        string $clientId,
        string $clientSecret,
    ): TeamsPluginConfigurationInterface {
        return new class($tenantId, $clientId, $clientSecret) implements TeamsPluginConfigurationInterface {
            public function __construct(
                private readonly string $tenantId,
                private readonly string $clientId,
                private readonly string $clientSecret,
            ) {}

            public function isEnabled(): bool
            {
                return true;
            }

            public function isConfigured(): bool
            {
                return '' !== trim($this->tenantId)
                    && '' !== trim($this->clientId)
                    && '' !== trim($this->clientSecret);
            }

            public function getTenantId(): string
            {
                return $this->tenantId;
            }

            public function getClientId(): string
            {
                return $this->clientId;
            }

            public function getClientSecret(): string
            {
                return $this->clientSecret;
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
