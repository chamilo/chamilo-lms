<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\OAuthServer;

/**
 * Registry of OAuth resource servers hosted by this Chamilo installation.
 *
 * The keys stored in OAuthClient.allowedResources are stable resource keys
 * (for example "mcp"), not absolute URLs. The absolute RFC 8707 resource
 * identifier is rebuilt from the current issuer, so moving a portal to a new
 * hostname does not silently invalidate every client allowlist.
 *
 * Adding another protected resource server requires registering it here with
 * its path and scopes. Dynamic Client Registration may then register those
 * scopes, but a client still cannot use the resource until an administrator
 * explicitly adds the resource key to that client's allowedResources list.
 */
final class OAuthResourceRegistry
{
    public const string MCP_RESOURCE = 'mcp';

    /**
     * @var array<string, array{path: string, scopes: array<int, string>}>
     */
    private const array RESOURCES = [
        self::MCP_RESOURCE => [
            'path' => 'mcp',
            'scopes' => ['mcp'],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public function getSupportedScopes(): array
    {
        $scopes = [];

        foreach (self::RESOURCES as $definition) {
            foreach ($definition['scopes'] as $scope) {
                $scopes[$scope] = true;
            }
        }

        return array_keys($scopes);
    }

    /**
     * @return array<int, string>
     */
    public function getScopesForResource(string $resourceKey): array
    {
        return self::RESOURCES[$resourceKey]['scopes'] ?? [];
    }

    public function getResourcePath(string $resourceKey): ?string
    {
        return self::RESOURCES[$resourceKey]['path'] ?? null;
    }

    public function resolveResourceKey(string $resourceIdentifier, string $issuer): ?string
    {
        $resourceIdentifier = rtrim($resourceIdentifier, '/');
        $issuer = rtrim($issuer, '/');

        foreach (self::RESOURCES as $resourceKey => $definition) {
            $expected = $issuer.'/'.ltrim($definition['path'], '/');
            if (hash_equals($expected, $resourceIdentifier)) {
                return $resourceKey;
            }
        }

        return null;
    }

    public function resolveResourceKeyFromPath(string $resourcePath): ?string
    {
        $resourcePath = trim($resourcePath, '/');

        foreach (self::RESOURCES as $resourceKey => $definition) {
            if (hash_equals(trim($definition['path'], '/'), $resourcePath)) {
                return $resourceKey;
            }
        }

        return null;
    }
}
