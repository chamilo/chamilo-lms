<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use const PHP_QUERY_RFC3986;

final readonly class MicrosoftTeamsJoinAuthenticationService
{
    private const string SESSION_KEY = '_teams_join_auth';
    private const int STATE_TTL = 600;
    private const string GRAPH_ME_URL = 'https://graph.microsoft.com/v1.0/me?%24select=id,mail,userPrincipalName';

    public function __construct(
        private HttpClientInterface $httpClient,
        private TeamsPluginConfigurationInterface $pluginConfiguration,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function begin(Request $request, int $meetingId, int $chamiloUserId, string $loginHint): string
    {
        $this->assertConfigured();

        if (!$request->hasSession()) {
            throw new RuntimeException('A session is required to sign in to Microsoft Teams.');
        }

        if ($meetingId <= 0 || $chamiloUserId <= 0) {
            throw new RuntimeException('The Microsoft Teams sign-in context is invalid.');
        }

        $state = $this->base64UrlEncode(random_bytes(32));
        $codeVerifier = $this->base64UrlEncode(random_bytes(64));
        $codeChallenge = $this->base64UrlEncode(hash('sha256', $codeVerifier, true));

        $request->getSession()->set(self::SESSION_KEY, [
            'state' => $state,
            'code_verifier' => $codeVerifier,
            'meeting_id' => $meetingId,
            'user_id' => $chamiloUserId,
            'created_at' => time(),
        ]);

        $callbackUrl = $this->getCallbackUrl();
        $params = [
            'client_id' => $this->pluginConfiguration->getClientId(),
            'response_type' => 'code',
            'redirect_uri' => $callbackUrl,
            'response_mode' => 'query',
            'scope' => 'User.Read',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        $loginHint = trim($loginHint);
        if ('' !== $loginHint) {
            $params['login_hint'] = $loginHint;
        }

        return 'https://login.microsoftonline.com/'
            .rawurlencode($this->pluginConfiguration->getTenantId())
            .'/oauth2/v2.0/authorize?'
            .http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{meetingId: int, microsoftUserId: string, mail: string, userPrincipalName: string}
     */
    public function complete(Request $request, int $chamiloUserId): array
    {
        $this->assertConfigured();

        if (!$request->hasSession()) {
            throw new RuntimeException('The Microsoft Teams sign-in session is missing.');
        }

        $pending = $request->getSession()->get(self::SESSION_KEY);
        if (!\is_array($pending)) {
            throw new RuntimeException('The Microsoft Teams sign-in request has expired.');
        }

        $meetingId = (int) ($pending['meeting_id'] ?? 0);
        $pendingUserId = (int) ($pending['user_id'] ?? 0);
        $createdAt = (int) ($pending['created_at'] ?? 0);
        $expectedState = (string) ($pending['state'] ?? '');
        $codeVerifier = (string) ($pending['code_verifier'] ?? '');

        if (
            $meetingId <= 0
            || $pendingUserId <= 0
            || $pendingUserId !== $chamiloUserId
            || $createdAt <= 0
            || (time() - $createdAt) > self::STATE_TTL
            || '' === $expectedState
            || '' === $codeVerifier
        ) {
            $request->getSession()->remove(self::SESSION_KEY);

            throw new RuntimeException('The Microsoft Teams sign-in request is invalid or expired.');
        }

        $state = trim((string) $request->query->get('state', ''));
        if ('' === $state || !hash_equals($expectedState, $state)) {
            $request->getSession()->remove(self::SESSION_KEY);

            throw new RuntimeException('The Microsoft Teams sign-in state is invalid.');
        }

        if ('' !== trim((string) $request->query->get('error', ''))) {
            $request->getSession()->remove(self::SESSION_KEY);

            throw new RuntimeException('Microsoft sign-in was cancelled or denied.');
        }

        $code = trim((string) $request->query->get('code', ''));
        if ('' === $code) {
            $request->getSession()->remove(self::SESSION_KEY);

            throw new RuntimeException('Microsoft did not return an authorization code.');
        }

        // One-time state: consume it before the outbound token exchange so the callback
        // cannot be replayed even when the provider or Graph request fails afterwards.
        // Persist/close the session before the slow Microsoft requests to avoid holding
        // the PHP session lock while the user has another Chamilo tab open.
        $request->getSession()->remove(self::SESSION_KEY);
        $request->getSession()->save();

        $tokenResponse = $this->httpClient->request(
            'POST',
            'https://login.microsoftonline.com/'
                .rawurlencode($this->pluginConfiguration->getTenantId())
                .'/oauth2/v2.0/token',
            [
                'body' => [
                    'client_id' => $this->pluginConfiguration->getClientId(),
                    'client_secret' => $this->pluginConfiguration->getClientSecret(),
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'scope' => 'User.Read',
                    'redirect_uri' => $this->getCallbackUrl(),
                    'code_verifier' => $codeVerifier,
                ],
            ]
        );

        $tokenStatus = $tokenResponse->getStatusCode();
        $tokenData = $tokenResponse->toArray(false);
        if ($tokenStatus < 200 || $tokenStatus >= 300) {
            throw new RuntimeException('Microsoft sign-in could not be completed.');
        }

        $accessToken = trim((string) ($tokenData['access_token'] ?? ''));
        if ('' === $accessToken) {
            throw new RuntimeException('Microsoft sign-in returned no access token.');
        }

        $profileResponse = $this->httpClient->request(
            'GET',
            self::GRAPH_ME_URL,
            [
                'auth_bearer' => $accessToken,
            ]
        );

        $profileStatus = $profileResponse->getStatusCode();
        $profileData = $profileResponse->toArray(false);
        if ($profileStatus < 200 || $profileStatus >= 300) {
            throw new RuntimeException('Microsoft Graph could not read the signed-in account.');
        }

        $microsoftUserId = trim((string) ($profileData['id'] ?? ''));
        if ('' === $microsoftUserId) {
            throw new RuntimeException('Microsoft Graph returned an incomplete signed-in account.');
        }

        return [
            'meetingId' => $meetingId,
            'microsoftUserId' => $microsoftUserId,
            'mail' => trim((string) ($profileData['mail'] ?? '')),
            'userPrincipalName' => trim((string) ($profileData['userPrincipalName'] ?? '')),
        ];
    }

    public function getPendingMeetingId(Request $request): ?int
    {
        if (!$request->hasSession()) {
            return null;
        }

        $pending = $request->getSession()->get(self::SESSION_KEY);
        if (!\is_array($pending)) {
            return null;
        }

        $meetingId = (int) ($pending['meeting_id'] ?? 0);

        return $meetingId > 0 ? $meetingId : null;
    }

    private function getCallbackUrl(): string
    {
        return $this->urlGenerator->generate(
            'teams_conference_auth_callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    private function assertConfigured(): void
    {
        if (!$this->pluginConfiguration->isConfigured()) {
            throw new RuntimeException('Microsoft Teams integration is not configured.');
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
