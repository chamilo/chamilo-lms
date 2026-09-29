<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\ExternalApi;

use Chamilo\CoreBundle\Service\ExternalApi\ExternalApiKeyManager;
use PHPUnit\Framework\TestCase;

final class ExternalApiKeyManagerTest extends TestCase
{
    public function testItAcceptsAWellFormedKey(): void
    {
        $plainKey = ExternalApiKeyManager::KEY_PREFIX.str_repeat('a', 43);

        self::assertTrue(ExternalApiKeyManager::isExternalKey($plainKey));
    }

    public function testItRejectsAnMcpKey(): void
    {
        // Disjoint from ExternalApiKeyAuthenticator::supports() by design, so the two
        // authenticators can never both claim the same bearer token.
        $mcpShapedKey = 'chamilo_mcp_'.str_repeat('a', 43);

        self::assertFalse(ExternalApiKeyManager::isExternalKey($mcpShapedKey));
    }

    public function testItRejectsAJwtShapedString(): void
    {
        $jwtLike = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U';

        self::assertFalse(ExternalApiKeyManager::isExternalKey($jwtLike));
    }

    public function testItRejectsATruncatedSecret(): void
    {
        $tooShort = ExternalApiKeyManager::KEY_PREFIX.str_repeat('a', 42);

        self::assertFalse(ExternalApiKeyManager::isExternalKey($tooShort));
    }

    public function testItRejectsAnEmptyString(): void
    {
        self::assertFalse(ExternalApiKeyManager::isExternalKey(''));
    }

    public function testGeneratedKeysAreRecognizedByIsExternalKey(): void
    {
        // KEY_PREFIX + 32 random bytes, base64url-encoded without padding, is always 43
        // characters — pin that invariant so a future change to the byte count doesn't
        // silently produce keys isExternalKey() itself would reject.
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        self::assertSame(43, \strlen($secret));
        self::assertTrue(ExternalApiKeyManager::isExternalKey(ExternalApiKeyManager::KEY_PREFIX.$secret));
    }
}
