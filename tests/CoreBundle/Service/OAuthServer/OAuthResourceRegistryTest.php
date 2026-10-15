<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Service\OAuthServer\OAuthResourceRegistry;
use PHPUnit\Framework\TestCase;

final class OAuthResourceRegistryTest extends TestCase
{
    public function testMcpIsTheOnlyRegisteredResourceToday(): void
    {
        $registry = new OAuthResourceRegistry();

        self::assertSame(['mcp'], $registry->getSupportedScopes());
        self::assertSame(['mcp'], $registry->getScopesForResource(OAuthResourceRegistry::MCP_RESOURCE));
        self::assertSame('mcp', $registry->getResourcePath(OAuthResourceRegistry::MCP_RESOURCE));
    }

    public function testItResolvesOnlyTheExactCurrentIssuerResource(): void
    {
        $registry = new OAuthResourceRegistry();

        self::assertSame(
            OAuthResourceRegistry::MCP_RESOURCE,
            $registry->resolveResourceKey('https://lms.example/mcp', 'https://lms.example'),
        );
        self::assertSame(
            OAuthResourceRegistry::MCP_RESOURCE,
            $registry->resolveResourceKey('https://lms.example/mcp/', 'https://lms.example/'),
        );
        self::assertNull($registry->resolveResourceKey('https://lms.example.evil/mcp', 'https://lms.example'));
        self::assertNull($registry->resolveResourceKey('https://other.example/mcp', 'https://lms.example'));
    }

    public function testItRejectsUnknownProtectedResourcePaths(): void
    {
        $registry = new OAuthResourceRegistry();

        self::assertSame(OAuthResourceRegistry::MCP_RESOURCE, $registry->resolveResourceKeyFromPath('/mcp/'));
        self::assertNull($registry->resolveResourceKeyFromPath('/future-resource'));
    }
}
