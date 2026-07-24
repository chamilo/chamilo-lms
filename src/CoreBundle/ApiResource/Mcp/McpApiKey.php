<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mcp;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\Mcp\McpApiKeyProcessor;
use Chamilo\CoreBundle\State\Mcp\McpApiKeyProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'McpApiKey',
    operations: [
        new Get(
            uriTemplate: '/mcp_api_key',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_current_mcp_api_key',
            provider: McpApiKeyProvider::class,
        ),
        new Post(
            uriTemplate: '/mcp_api_key',
            input: false,
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'generate_current_mcp_api_key',
            processor: McpApiKeyProcessor::class,
        ),
        new Delete(
            uriTemplate: '/mcp_api_key',
            input: false,
            output: false,
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'revoke_current_mcp_api_key',
            processor: McpApiKeyProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['mcp_api_key:read']],
)]
final class McpApiKey
{
    #[ApiProperty(identifier: true)]
    #[Groups(['mcp_api_key:read'])]
    public string $id = 'current';

    #[Groups(['mcp_api_key:read'])]
    public bool $active = false;

    #[Groups(['mcp_api_key:read'])]
    public ?string $maskedKey = null;

    /**
     * Only populated in the immediate response to a generation or rotation.
     */
    #[Groups(['mcp_api_key:read'])]
    public ?string $plainKey = null;

    #[Groups(['mcp_api_key:read'])]
    public string $endpoint = '';

    #[Groups(['mcp_api_key:read'])]
    public ?string $createdAt = null;

    #[Groups(['mcp_api_key:read'])]
    public ?string $lastUsedAt = null;

    #[Groups(['mcp_api_key:read'])]
    public ?string $revokedAt = null;

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
        $resource->id = (string) ($data['id'] ?? 'current');
        $resource->active = (bool) ($data['active'] ?? false);
        $resource->maskedKey = isset($data['maskedKey']) ? (string) $data['maskedKey'] : null;
        $resource->plainKey = isset($data['plainKey']) ? (string) $data['plainKey'] : null;
        $resource->endpoint = (string) ($data['endpoint'] ?? '');
        $resource->createdAt = isset($data['createdAt']) ? (string) $data['createdAt'] : null;
        $resource->lastUsedAt = isset($data['lastUsedAt']) ? (string) $data['lastUsedAt'] : null;
        $resource->revokedAt = isset($data['revokedAt']) ? (string) $data['revokedAt'] : null;

        return $resource;
    }
}
