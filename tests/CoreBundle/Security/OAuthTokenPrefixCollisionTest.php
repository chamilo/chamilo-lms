<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Service\Mcp\McpApiKeyManager;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthTokenService;
use PHPUnit\Framework\TestCase;

/**
 * McpBearerAuthenticator::authenticate() branches on these two static guards
 * to decide how to interpret a bearer credential presented at /mcp. They must
 * never both match the same string — otherwise which branch runs would depend
 * on call order rather than the token's actual origin. Guards this
 * codebase-conformance invariant explicitly so a future prefix change to
 * either scheme cannot silently reintroduce the collision.
 */
final class OAuthTokenPrefixCollisionTest extends TestCase
{
    public function testGenericOAuthAccessTokenIsNeverAlsoAPersonalMcpApiKey(): void
    {
        $token = OAuthTokenService::ACCESS_TOKEN_PREFIX.str_repeat('a', 43);

        self::assertTrue(OAuthTokenService::isAccessToken($token));
        self::assertFalse(McpApiKeyManager::isMcpKey($token));
    }

    public function testPersonalMcpApiKeyIsNeverAlsoAGenericOAuthAccessToken(): void
    {
        $token = McpApiKeyManager::KEY_PREFIX.str_repeat('a', 43);

        self::assertTrue(McpApiKeyManager::isMcpKey($token));
        self::assertFalse(OAuthTokenService::isAccessToken($token));
    }

    public function testOAuthRefreshTokensAreNeverAcceptedAsAccessTokens(): void
    {
        $refreshToken = OAuthTokenService::REFRESH_TOKEN_PREFIX.str_repeat('a', 43);

        self::assertFalse(OAuthTokenService::isAccessToken($refreshToken));
        self::assertFalse(McpApiKeyManager::isMcpKey($refreshToken));
    }
}
