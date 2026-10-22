<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use const DATE_ATOM;

final class MicrosoftTeamsGraphClient implements MicrosoftTeamsGraphClientInterface
{
    private const string GRAPH_BASE_URL = 'https://graph.microsoft.com/v1.0';
    private const string GRAPH_SCOPE = 'https://graph.microsoft.com/.default';

    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;

    /**
     * @var array<string, string>
     */
    private array $organizerUserIds = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly TeamsPluginConfigurationInterface $pluginConfiguration,
    ) {}

    public function isConfigured(): bool
    {
        return $this->pluginConfiguration->isConfigured();
    }

    /**
     * Creates a standalone Microsoft Teams onlineMeeting using application permissions.
     *
     * @return array{meetingId: string, joinUrl: string}
     */
    public function createOnlineMeeting(
        string $organizer,
        string $subject,
        DateTimeInterface $start,
        DateTimeInterface $end,
    ): array {
        $this->assertConfigured();

        $organizer = $this->normalizeIdentifier($organizer, 'organizer');
        $subject = trim($subject);

        if ('' === $subject) {
            throw new RuntimeException('A Microsoft Teams meeting title is required.');
        }

        if ($end <= $start) {
            throw new RuntimeException('The Microsoft Teams meeting end date must be after its start date.');
        }

        [$startUtc, $endUtc] = $this->normalizeDates($start, $end);
        $organizerUserId = $this->resolveOrganizerUserId($organizer);

        $response = $this->httpClient->request(
            'POST',
            self::GRAPH_BASE_URL.'/users/'.rawurlencode($organizerUserId).'/onlineMeetings',
            [
                'auth_bearer' => $this->getAccessToken(),
                'json' => [
                    'startDateTime' => $startUtc->format(DATE_ATOM),
                    'endDateTime' => $endUtc->format(DATE_ATOM),
                    'subject' => $subject,
                ],
            ]
        );

        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Microsoft Graph could not create the Teams meeting. Verify OnlineMeetings.ReadWrite.All and the application access policy for the organizer.');
        }

        $meetingId = trim((string) ($data['id'] ?? ''));
        $joinUrl = trim((string) ($data['joinWebUrl'] ?? ''));

        if ('' === $meetingId) {
            throw new RuntimeException('Microsoft Graph returned an incomplete Teams meeting.');
        }

        if ('' === $joinUrl) {
            try {
                $this->deleteOnlineMeetingByUserId($organizerUserId, $meetingId);
            } catch (RuntimeException) {
                // Best-effort compensation. Preserve the incomplete-meeting failure.
            }

            throw new RuntimeException('Microsoft Graph returned a Teams meeting without a join URL.');
        }

        return [
            'meetingId' => $meetingId,
            'joinUrl' => $joinUrl,
        ];
    }

    public function updateOnlineMeeting(
        string $organizer,
        string $meetingId,
        string $subject,
        DateTimeInterface $start,
        DateTimeInterface $end,
    ): void {
        $this->assertConfigured();

        $organizer = $this->normalizeIdentifier($organizer, 'organizer');
        $meetingId = $this->normalizeIdentifier($meetingId, 'meeting id');
        $subject = trim($subject);

        if ('' === $subject) {
            throw new RuntimeException('A Microsoft Teams meeting title is required.');
        }

        if ($end <= $start) {
            throw new RuntimeException('The Microsoft Teams meeting end date must be after its start date.');
        }

        [$startUtc, $endUtc] = $this->normalizeDates($start, $end);
        $organizerUserId = $this->resolveOrganizerUserId($organizer);

        $response = $this->httpClient->request(
            'PATCH',
            self::GRAPH_BASE_URL.'/users/'.rawurlencode($organizerUserId).'/onlineMeetings/'.rawurlencode($meetingId),
            [
                'auth_bearer' => $this->getAccessToken(),
                'json' => [
                    'subject' => $subject,
                    'startDateTime' => $startUtc->format(DATE_ATOM),
                    'endDateTime' => $endUtc->format(DATE_ATOM),
                ],
            ]
        );

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Microsoft Graph could not update the Teams meeting.');
        }
    }

    public function deleteOnlineMeeting(string $organizer, string $meetingId): void
    {
        $this->assertConfigured();

        $organizer = $this->normalizeIdentifier($organizer, 'organizer');
        $meetingId = $this->normalizeIdentifier($meetingId, 'meeting id');
        $organizerUserId = $this->resolveOrganizerUserId($organizer);

        $this->deleteOnlineMeetingByUserId($organizerUserId, $meetingId);
    }

    private function deleteOnlineMeetingByUserId(string $organizerUserId, string $meetingId): void
    {
        $response = $this->httpClient->request(
            'DELETE',
            self::GRAPH_BASE_URL.'/users/'.rawurlencode($organizerUserId).'/onlineMeetings/'.rawurlencode($meetingId),
            [
                'auth_bearer' => $this->getAccessToken(),
            ]
        );

        $status = $response->getStatusCode();
        if (404 === $status) {
            return;
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Microsoft Graph could not cancel the Teams meeting.');
        }
    }

    private function resolveOrganizerUserId(string $organizer): string
    {
        $cacheKey = strtolower($organizer);
        if (isset($this->organizerUserIds[$cacheKey])) {
            return $this->organizerUserIds[$cacheKey];
        }

        $response = $this->httpClient->request(
            'GET',
            self::GRAPH_BASE_URL.'/users/'.rawurlencode($organizer).'?%24select=id',
            [
                'auth_bearer' => $this->getAccessToken(),
            ]
        );

        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Microsoft Graph could not resolve the Teams organizer. Verify the organizer e-mail and the User.Read.All application permission.');
        }

        $userId = trim((string) ($data['id'] ?? ''));
        if ('' === $userId) {
            throw new RuntimeException('Microsoft Graph returned an incomplete Teams organizer.');
        }

        $this->organizerUserIds[$cacheKey] = $userId;

        return $userId;
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function normalizeDates(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $utc = new DateTimeZone('UTC');

        return [
            DateTimeImmutable::createFromInterface($start)->setTimezone($utc),
            DateTimeImmutable::createFromInterface($end)->setTimezone($utc),
        ];
    }

    private function normalizeIdentifier(string $value, string $label): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new RuntimeException(\sprintf('A Microsoft Teams %s is required.', $label));
        }

        return $value;
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Microsoft Teams integration is not configured.');
        }
    }

    private function getAccessToken(): string
    {
        if (null !== $this->accessToken && time() < $this->accessTokenExpiresAt) {
            return $this->accessToken;
        }

        $response = $this->httpClient->request(
            'POST',
            'https://login.microsoftonline.com/'.rawurlencode($this->pluginConfiguration->getTenantId()).'/oauth2/v2.0/token',
            [
                'body' => [
                    'client_id' => $this->pluginConfiguration->getClientId(),
                    'client_secret' => $this->pluginConfiguration->getClientSecret(),
                    'scope' => self::GRAPH_SCOPE,
                    'grant_type' => 'client_credentials',
                ],
            ]
        );

        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Microsoft Graph authentication failed.');
        }

        $token = trim((string) ($data['access_token'] ?? ''));
        if ('' === $token) {
            throw new RuntimeException('Microsoft Graph authentication returned no access token.');
        }

        $expiresIn = max(120, (int) ($data['expires_in'] ?? 3600));

        $this->accessToken = $token;
        $this->accessTokenExpiresAt = time() + $expiresIn - 60;

        return $token;
    }
}
