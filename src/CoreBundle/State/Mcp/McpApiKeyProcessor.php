<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mcp;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Mcp\McpApiKey;
use Chamilo\CoreBundle\Service\Mcp\McpApiKeyManager;
use LogicException;

/**
 * @implements ProcessorInterface<mixed, McpApiKey|null>
 */
final readonly class McpApiKeyProcessor implements ProcessorInterface
{
    public function __construct(
        private McpApiKeyManager $apiKeyManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?McpApiKey
    {
        if ($operation instanceof Post) {
            return McpApiKey::fromArray($this->apiKeyManager->generateForCurrentUser());
        }

        if ($operation instanceof Delete) {
            $this->apiKeyManager->revokeForCurrentUser();

            return null;
        }

        throw new LogicException('Unsupported MCP API key operation.');
    }
}
