<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mcp;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Mcp\McpApiKey;
use Chamilo\CoreBundle\Service\Mcp\McpApiKeyManager;

/**
 * @implements ProviderInterface<McpApiKey>
 */
final readonly class McpApiKeyProvider implements ProviderInterface
{
    public function __construct(
        private McpApiKeyManager $apiKeyManager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): McpApiKey
    {
        return McpApiKey::fromArray($this->apiKeyManager->getCurrentMetadata());
    }
}
