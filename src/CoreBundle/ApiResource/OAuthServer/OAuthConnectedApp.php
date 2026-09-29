<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\OAuthServer;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\State\OAuthServer\OAuthConnectedAppProcessor;
use Chamilo\CoreBundle\State\OAuthServer\OAuthConnectedAppProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * An OAuth application the current user has authorized ("Connected apps").
 * Never MCP-specific: any future resource server built on the same OAuth
 * Authorization Server shows up here too.
 */
#[ApiResource(
    shortName: 'OAuthConnectedApp',
    operations: [
        new GetCollection(
            uriTemplate: '/oauth_connected_apps',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_oauth_connected_apps',
            provider: OAuthConnectedAppProvider::class,
        ),
        new Delete(
            uriTemplate: '/oauth_connected_apps/{id}',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            input: false,
            output: false,
            read: false,
            name: 'revoke_oauth_connected_app',
            processor: OAuthConnectedAppProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['oauth_connected_app:read']],
)]
final class OAuthConnectedApp
{
    #[ApiProperty(identifier: true)]
    #[Groups(['oauth_connected_app:read'])]
    public string $id = '';

    #[Groups(['oauth_connected_app:read'])]
    public string $clientName = '';

    #[Groups(['oauth_connected_app:read'])]
    public ?string $clientUri = null;

    #[Groups(['oauth_connected_app:read'])]
    public ?string $connectedAt = null;

    #[Groups(['oauth_connected_app:read'])]
    public ?string $lastUsedAt = null;

    #[Groups(['oauth_connected_app:read'])]
    public ?string $expiresAt = null;

    #[Groups(['oauth_connected_app:read'])]
    public ?string $scope = null;

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $resource = new self();
        $resource->id = (string) ($data['id'] ?? '');
        $resource->clientName = (string) ($data['clientName'] ?? '');
        $resource->clientUri = isset($data['clientUri']) ? (string) $data['clientUri'] : null;
        $resource->connectedAt = isset($data['connectedAt']) ? (string) $data['connectedAt'] : null;
        $resource->lastUsedAt = isset($data['lastUsedAt']) ? (string) $data['lastUsedAt'] : null;
        $resource->expiresAt = isset($data['expiresAt']) ? (string) $data['expiresAt'] : null;
        $resource->scope = isset($data['scope']) ? (string) $data['scope'] : null;

        return $resource;
    }
}
