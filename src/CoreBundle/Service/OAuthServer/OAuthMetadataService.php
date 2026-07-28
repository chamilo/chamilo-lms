<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Settings\SettingsManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Single source of truth for the OAuth Authorization Server's own URLs.
 *
 * Consumed by the discovery controller (RFC 8414 / RFC 9728 documents) and by
 * resource-server authenticators (e.g. McpBearerAuthenticator's
 * WWW-Authenticate header) so the two can never drift apart.
 */
final readonly class OAuthMetadataService
{
    public function __construct(
        private RequestStack $requestStack,
        private SettingsManager $settingsManager,
    ) {}

    public function getIssuer(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new RuntimeException('The OAuth issuer requires an active HTTP request.');
        }

        return rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->getIssuer().'/oauth/authorize';
    }

    public function getTokenEndpoint(): string
    {
        return $this->getIssuer().'/oauth/token';
    }

    public function getRegistrationEndpoint(): string
    {
        return $this->getIssuer().'/oauth/register';
    }

    public function getRevocationEndpoint(): string
    {
        return $this->getIssuer().'/oauth/revoke';
    }

    /**
     * Builds the audience identifier for a given resource server path, e.g.
     * "/mcp" -> "<issuer>/mcp". Not validated against a registry — each
     * resource server owns its own identifier.
     */
    public function getResourceIdentifier(string $resourcePath): string
    {
        return $this->getIssuer().'/'.ltrim($resourcePath, '/');
    }

    /**
     * The RFC 9728 metadata URL a resource server should advertise in its
     * WWW-Authenticate header. $resourcePath (e.g. "mcp") is appended per the
     * spec's path-insertion convention so clients that probe the
     * path-suffixed form first still find it.
     */
    public function getResourceMetadataUrl(string $resourcePath = ''): string
    {
        $base = $this->getIssuer().'/.well-known/oauth-protected-resource';
        $resourcePath = trim($resourcePath, '/');

        return '' === $resourcePath ? $base : $base.'/'.$resourcePath;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAuthorizationServerMetadata(): array
    {
        return [
            'issuer' => $this->getIssuer(),
            'authorization_endpoint' => $this->getAuthorizationEndpoint(),
            'token_endpoint' => $this->getTokenEndpoint(),
            'registration_endpoint' => $this->getRegistrationEndpoint(),
            'revocation_endpoint' => $this->getRevocationEndpoint(),
            'scopes_supported' => ['mcp'],
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
            'revocation_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
            'code_challenge_methods_supported' => ['S256'],
            'service_documentation' => $this->getIssuer().'/documentation/',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildProtectedResourceMetadata(string $resourcePath): array
    {
        $resourceName = (string) ($this->settingsManager->getSetting('platform.site_name') ?: 'Chamilo');

        return [
            'resource' => $this->getResourceIdentifier($resourcePath),
            'authorization_servers' => [$this->getIssuer()],
            'bearer_methods_supported' => ['header'],
            'scopes_supported' => ['mcp'],
            'resource_name' => $resourceName,
            'resource_documentation' => $this->getIssuer().'/documentation/',
        ];
    }
}
