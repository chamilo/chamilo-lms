<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWiki;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

use const FILTER_VALIDATE_URL;

final readonly class EtherpadClient
{
    private const string API_VERSION = '1';
    private const int SESSION_TTL_SECONDS = 7200;

    public function __construct(
        private HttpClientInterface $httpClient,
        private SettingsManager $settingsManager,
    ) {}

    public function isEnabled(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('editor.etherpad_enabled', true);
    }

    public function isConfigured(): bool
    {
        try {
            $this->getBaseUrl();
            $this->getApiKey();
        } catch (RuntimeException) {
            return false;
        }

        return true;
    }

    public function assertReady(Request $request): void
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Etherpad integration is disabled.');
        }

        $baseUrl = $this->getBaseUrl();
        $parts = parse_url($baseUrl);
        $requestParts = parse_url($request->getSchemeAndHttpHost());

        if (!\is_array($parts) || !\is_array($requestParts)) {
            throw new RuntimeException('Etherpad URL configuration is invalid.');
        }

        $baseScheme = strtolower((string) ($parts['scheme'] ?? ''));
        $requestScheme = strtolower((string) ($requestParts['scheme'] ?? ''));
        $baseHost = strtolower((string) ($parts['host'] ?? ''));
        $requestHost = strtolower((string) ($requestParts['host'] ?? ''));
        $basePort = $this->resolvePort($parts);
        $requestPort = $this->resolvePort($requestParts);

        if ($baseScheme !== $requestScheme || $baseHost !== $requestHost || $basePort !== $requestPort) {
            throw new RuntimeException('Etherpad must be exposed on the same origin as Chamilo for secure embedded Wiki sessions.');
        }

        $this->getApiKey();
    }

    public function ensurePad(CWiki $wiki): string
    {
        $existingPadId = trim((string) $wiki->getEtherpadId());
        if ('' !== $existingPadId) {
            return $existingPadId;
        }

        $pageId = (int) ($wiki->getPageId() ?? 0);
        if ($pageId <= 0) {
            throw new RuntimeException('The Wiki page must be saved before an Etherpad pad can be created.');
        }

        $groupData = $this->call('createGroupIfNotExistsFor', [
            'groupMapper' => $this->buildGroupMapper($wiki),
        ]);
        $groupId = trim((string) ($groupData['groupID'] ?? ''));
        if ('' === $groupId) {
            throw new RuntimeException('Etherpad did not return a group identifier.');
        }

        $padName = 'wiki-'.$pageId;
        $createPayload = $this->callRaw('createGroupPad', [
            'groupID' => $groupId,
            'padName' => $padName,
        ]);
        $code = (int) ($createPayload['code'] ?? -1);
        $message = (string) ($createPayload['message'] ?? '');

        if (0 !== $code && !str_contains(strtolower($message), 'already exist')) {
            throw new RuntimeException($this->buildApiError($message));
        }

        $data = $createPayload['data'] ?? null;
        $padId = \is_array($data) ? trim((string) ($data['padID'] ?? '')) : '';
        if ('' === $padId) {
            $padId = $groupId.'$'.$padName;
        }

        $this->setHtml($padId, $wiki->getContent());

        return $padId;
    }

    /**
     * @return array{sessionId:string, editorUrl:string, expiresAt:int}
     */
    public function createEditorSession(User $user, string $padId): array
    {
        [$groupId] = $this->splitPadId($padId);

        $authorData = $this->call('createAuthorIfNotExistsFor', [
            'authorMapper' => $this->buildMapperPrefix().'-user-'.(int) $user->getId(),
            'name' => $user->getFullName(),
        ]);
        $authorId = trim((string) ($authorData['authorID'] ?? ''));
        if ('' === $authorId) {
            throw new RuntimeException('Etherpad did not return an author identifier.');
        }

        $expiresAt = time() + self::SESSION_TTL_SECONDS;
        $sessionData = $this->call('createSession', [
            'groupID' => $groupId,
            'authorID' => $authorId,
            'validUntil' => $expiresAt,
        ]);
        $sessionId = trim((string) ($sessionData['sessionID'] ?? ''));
        if ('' === $sessionId) {
            throw new RuntimeException('Etherpad did not return a session identifier.');
        }

        return [
            'sessionId' => $sessionId,
            'editorUrl' => $this->getBaseUrl().'/p/'.rawurlencode($padId).'?showControls=true&showChat=true&showLineNumbers=true',
            'expiresAt' => $expiresAt,
        ];
    }

    public function getHtml(string $padId): string
    {
        $data = $this->call('getHTML', ['padID' => $padId]);
        $html = $data['html'] ?? null;

        if (!\is_string($html)) {
            throw new RuntimeException('Etherpad did not return valid HTML content.');
        }

        return $html;
    }

    public function setHtml(string $padId, string $html): void
    {
        $this->call('setHTML', [
            'padID' => $padId,
            'html' => '' !== trim($html) ? $html : '<p>&nbsp;</p>',
        ]);
    }

    public function deletePad(string $padId): void
    {
        $payload = $this->callRaw('deletePad', ['padID' => $padId]);
        $code = (int) ($payload['code'] ?? -1);
        $message = (string) ($payload['message'] ?? '');

        if (0 === $code || str_contains(strtolower($message), 'does not exist')) {
            return;
        }

        throw new RuntimeException($this->buildApiError($message));
    }

    public function deleteSession(string $sessionId): void
    {
        $payload = $this->callRaw('deleteSession', ['sessionID' => $sessionId]);
        $code = (int) ($payload['code'] ?? -1);
        $message = (string) ($payload['message'] ?? '');

        if (0 === $code || str_contains(strtolower($message), 'does not exist')) {
            return;
        }

        throw new RuntimeException($this->buildApiError($message));
    }

    private function buildGroupMapper(CWiki $wiki): string
    {
        return \sprintf(
            $this->buildMapperPrefix().'-wiki-%d-%d-%d-%d',
            $wiki->getCId(),
            (int) ($wiki->getSessionId() ?? 0),
            (int) ($wiki->getGroupId() ?? 0),
            (int) ($wiki->getPageId() ?? 0),
        );
    }

    private function buildMapperPrefix(): string
    {
        return 'chamilo-'.substr(hash('sha256', $this->getBaseUrl()), 0, 12);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitPadId(string $padId): array
    {
        $parts = explode('$', $padId, 2);
        if (2 !== \count($parts) || '' === trim($parts[0]) || '' === trim($parts[1])) {
            throw new RuntimeException('The stored Etherpad pad identifier is invalid.');
        }

        return [$parts[0], $parts[1]];
    }

    /**
     * @param array<string, int|string> $parameters
     *
     * @return array<string, mixed>
     */
    private function call(string $method, array $parameters): array
    {
        $payload = $this->callRaw($method, $parameters);
        $code = (int) ($payload['code'] ?? -1);
        if (0 !== $code) {
            throw new RuntimeException($this->buildApiError((string) ($payload['message'] ?? '')));
        }

        $data = $payload['data'] ?? [];

        return \is_array($data) ? $data : [];
    }

    /**
     * @param array<string, int|string> $parameters
     *
     * @return array<string, mixed>
     */
    private function callRaw(string $method, array $parameters): array
    {
        $url = $this->getBaseUrl().'/api/'.self::API_VERSION.'/'.$method;
        $parameters['apikey'] = $this->getApiKey();

        try {
            $response = $this->httpClient->request('POST', $url, [
                'body' => $parameters,
                'timeout' => 10.0,
                'max_redirects' => 0,
            ]);
            $statusCode = $response->getStatusCode();
            $payload = $response->toArray(false);
        } catch (Throwable $throwable) {
            throw new RuntimeException('Unable to contact the configured Etherpad server.', 0, $throwable);
        }

        if ($statusCode < 200 || $statusCode >= 300 || !\is_array($payload)) {
            throw new RuntimeException('The configured Etherpad server returned an invalid response.');
        }

        return $payload;
    }

    private function getBaseUrl(): string
    {
        $url = rtrim(trim((string) $this->settingsManager->getSetting('editor.etherpad_url', true)), '/');
        if ('' === $url || false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Etherpad URL is not configured correctly.');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) (\is_array($parts) ? ($parts['scheme'] ?? '') : ''));
        $host = trim((string) (\is_array($parts) ? ($parts['host'] ?? '') : ''));
        if (!\in_array($scheme, ['http', 'https'], true) || '' === $host) {
            throw new RuntimeException('Etherpad URL must use HTTP or HTTPS.');
        }
        if (\is_array($parts) && (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']))) {
            throw new RuntimeException('Etherpad URL must not contain credentials, a query string or a fragment.');
        }

        return $url;
    }

    private function getApiKey(): string
    {
        $apiKey = trim((string) $this->settingsManager->getSetting('editor.etherpad_api_key', true));
        if ('' === $apiKey) {
            throw new RuntimeException('Etherpad API key is not configured.');
        }

        return $apiKey;
    }

    /**
     * @param array<string, mixed> $parts
     */
    private function resolvePort(array $parts): int
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return 'https' === strtolower((string) ($parts['scheme'] ?? '')) ? 443 : 80;
    }

    private function buildApiError(string $message): string
    {
        $message = trim(strip_tags($message));
        $apiKey = $this->getApiKey();
        if ('' !== $apiKey) {
            $message = str_replace($apiKey, '[redacted]', $message);
        }

        return '' !== $message ? 'Etherpad API error: '.$message : 'Etherpad API returned an error.';
    }
}
