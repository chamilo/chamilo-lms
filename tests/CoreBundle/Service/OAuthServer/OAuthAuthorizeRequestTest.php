<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Dto\OAuthServer\OAuthAuthorizeRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class OAuthAuthorizeRequestTest extends TestCase
{
    public function testItReturnsExplicitScope(): void
    {
        $request = new OAuthAuthorizeRequest(
            clientId: 'client',
            redirectUri: 'https://client.example/callback',
            state: 'state',
            codeChallenge: str_repeat('a', 43),
            codeChallengeMethod: 'S256',
            scope: 'mcp',
            resource: 'https://lms.example/mcp',
            createdAt: 1,
        );

        self::assertSame('mcp', $request->getScope());
    }

    public function testItKeepsPreDeploymentSessionObjectsCompatible(): void
    {
        $request = (new ReflectionClass(OAuthAuthorizeRequest::class))->newInstanceWithoutConstructor();

        self::assertSame('mcp', $request->getScope());
    }
}
